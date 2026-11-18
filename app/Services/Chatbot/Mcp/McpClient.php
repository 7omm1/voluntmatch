<?php

namespace App\Services\Chatbot\Mcp;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Cliente MCP sobre HTTP (JSON-RPC 2.0 y respuestas SSE).
 *
 * Solo se conecta a servidores declarados en config/chatbot.php. No acepta
 * URLs, cabeceras ni nombres de herramientas que vengan del usuario o del
 * modelo. La allowlist se comprueba dos veces: aquí y en McpManager.
 */
class McpClient
{
    /**
     * @param  array<string, mixed>  $server
     * @return list<array<string, mixed>>
     */
    public function listTools(array $server, string $serverName): array
    {
        $response = $this->request($server, $serverName, 'tools/list', []);

        $tools = $response['tools'] ?? [];

        return is_array($tools) ? array_values(array_filter($tools, 'is_array')) : [];
    }

    /**
     * @param  array<string, mixed>  $server
     * @param  array<string, mixed>  $arguments
     * @return array{ok: bool, text: string, structured: mixed}
     */
    public function callTool(array $server, string $serverName, string $tool, array $arguments): array
    {
        $allowed = (array) ($server['allowed_tools'] ?? []);
        if (! in_array('*', $allowed, true) && ! in_array($tool, $allowed, true)) {
            throw new RuntimeException('La herramienta MCP no está permitida.');
        }

        $response = $this->request($server, $serverName, 'tools/call', [
            'name' => $tool,
            'arguments' => (object) $arguments,
        ]);

        $texts = [];
        foreach (($response['content'] ?? []) as $item) {
            if (! is_array($item)) {
                continue;
            }

            if (($item['type'] ?? null) === 'text' && isset($item['text'])) {
                $texts[] = (string) $item['text'];
            }
        }

        $text = implode("\n", $texts);
        $max = (int) config('chatbot.mcp.max_result_chars', 6000);
        if (mb_strlen($text) > $max) {
            $text = mb_substr($text, 0, $max).'…';
        }

        $structured = $response['structuredContent'] ?? null;
        if (is_array($structured)) {
            $encoded = json_encode($structured, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (is_string($encoded) && strlen($encoded) > $max) {
                $structured = ['truncado' => true, 'nota' => 'El servidor MCP devolvió más datos de los permitidos.'];
            }
        }

        return [
            'ok' => ! (bool) ($response['isError'] ?? false),
            'text' => $text,
            'structured' => $structured,
        ];
    }

    /**
     * @param  array<string, mixed>  $server
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function request(array $server, string $serverName, string $method, array $params): array
    {
        $this->validateServer($server, $serverName);
        $session = $this->ensureInitialized($server, $serverName);

        $id = random_int(1000, 999999);
        $payload = [
            'jsonrpc' => '2.0',
            'id' => $id,
            'method' => $method,
            'params' => (object) $params,
        ];

        $response = $this->post($server, $payload, $session);

        if (! $response->successful()) {
            throw new RuntimeException('El servidor MCP respondió con error '.$response->status().'.');
        }

        $data = $this->decode($response);

        if (! is_array($data)) {
            throw new RuntimeException('El servidor MCP devolvió una respuesta no válida.');
        }

        if (isset($data['error'])) {
            $message = is_array($data['error']) ? ($data['error']['message'] ?? 'error') : 'error';

            throw new RuntimeException('MCP: '.Str::limit((string) $message, 120));
        }

        $result = $data['result'] ?? [];

        return is_array($result) ? $result : [];
    }

    /**
     * @param  array<string, mixed>  $server
     * @return array{session_id: ?string, protocol: string}
     */
    private function ensureInitialized(array $server, string $serverName): array
    {
        $key = 'chatbot.mcp.session.'.hash('sha256', $serverName.'|'.($server['url'] ?? ''));
        $cached = Cache::get($key);

        if (is_array($cached)) {
            return $cached;
        }

        $id = random_int(1000, 999999);
        $payload = [
            'jsonrpc' => '2.0',
            'id' => $id,
            'method' => 'initialize',
            'params' => [
                'protocolVersion' => (string) config('chatbot.mcp.protocol_version', '2025-06-18'),
                'capabilities' => (object) [],
                'clientInfo' => [
                    'name' => 'voluntmatch-chatbot',
                    'version' => '1.0',
                ],
            ],
        ];

        $response = $this->post($server, $payload, null);

        if (! $response->successful()) {
            throw new RuntimeException('No se pudo inicializar el servidor MCP.');
        }

        $data = $this->decode($response);
        if (! is_array($data) || isset($data['error'])) {
            throw new RuntimeException('El servidor MCP rechazó la inicialización.');
        }

        $session = [
            'session_id' => $response->header('Mcp-Session-Id') ?: null,
            'protocol' => (string) ($data['result']['protocolVersion'] ?? config('chatbot.mcp.protocol_version')),
        ];

        Cache::put($key, $session, 1800);

        // Notificación obligatoria del handshake. Un fallo aquí no debe
        // impedir la consulta: muchos servidores ya están listos.
        try {
            $this->post($server, [
                'jsonrpc' => '2.0',
                'method' => 'notifications/initialized',
            ], $session);
        } catch (\Throwable) {
            // Silencioso a propósito.
        }

        return $session;
    }

    /**
     * @param  array<string, mixed>  $server
     * @param  array<string, mixed>  $payload
     * @param  array{session_id: ?string, protocol: string}|null  $session
     */
    private function post(array $server, array $payload, ?array $session, bool $withSession = true): Response
    {
        $headers = [
            'Accept' => 'application/json, text/event-stream',
            'Content-Type' => 'application/json',
        ];

        $token = (string) ($server['token'] ?? '');
        if ($token !== '') {
            $headers['Authorization'] = 'Bearer '.$token;
        }

        if ($withSession && $session !== null) {
            if (! empty($session['session_id'])) {
                $headers['Mcp-Session-Id'] = (string) $session['session_id'];
            }
            if (! empty($session['protocol'])) {
                $headers['MCP-Protocol-Version'] = (string) $session['protocol'];
            }
        }

        $timeout = max(2, (int) ($server['timeout'] ?? config('chatbot.mcp.timeout', 8)));

        try {
            return Http::withHeaders($headers)
                ->withoutRedirecting()
                ->timeout($timeout)
                ->connectTimeout(3)
                ->post((string) $server['url'], $payload);
        } catch (\Throwable $exception) {
            throw new RuntimeException('No se pudo contactar al servidor MCP.', 0, $exception);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decode(Response $response): ?array
    {
        $contentType = (string) $response->header('Content-Type');

        if (str_contains($contentType, 'text/event-stream')) {
            $decoded = null;

            foreach (preg_split('/\r?\n/', $response->body()) ?: [] as $line) {
                $line = trim($line);
                if (! str_starts_with($line, 'data:')) {
                    continue;
                }

                $json = trim(substr($line, 5));
                if ($json === '' || $json === '[DONE]') {
                    continue;
                }

                $candidate = json_decode($json, true);
                if (is_array($candidate) && (isset($candidate['result']) || isset($candidate['error']))) {
                    $decoded = $candidate;
                }
            }

            return $decoded;
        }

        $data = $response->json();

        return is_array($data) ? $data : null;
    }

    /**
     * @param  array<string, mixed>  $server
     */
    private function validateServer(array $server, string $serverName): void
    {
        $url = (string) ($server['url'] ?? '');

        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new RuntimeException('El servidor MCP «'.$serverName.'» no tiene una URL válida.');
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        if ($scheme !== 'https' && ! config('chatbot.mcp.allow_insecure', false)) {
            throw new RuntimeException('El servidor MCP «'.$serverName.'» debe usar HTTPS.');
        }

        if (config('app.env') === 'production' && $scheme !== 'https') {
            throw new RuntimeException('MCP inseguro bloqueado en producción.');
        }
    }
}

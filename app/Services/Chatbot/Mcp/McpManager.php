<?php

namespace App\Services\Chatbot\Mcp;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Orquestador de servidores MCP declarados en configuración.
 *
 * Los nombres expuestos al modelo llevan el prefijo mcp_<servidor>_<tool>
 * para evitar colisiones con herramientas locales y dejar claro el origen.
 * Un servidor sin allowlist no expone ninguna herramienta.
 */
class McpManager
{
    public function __construct(private readonly McpClient $client) {}

    /**
     * @return array<string, array<string, mixed>>
     */
    public function definitions(string $audience): array
    {
        if (! config('chatbot.mcp.enabled', false)) {
            return [];
        }

        $definitions = [];
        $usedNames = [];

        foreach ($this->servers() as $serverName => $server) {
            $allowed = (array) ($server['allowed_tools'] ?? []);

            if ($allowed === []) {
                continue;
            }

            foreach ($this->toolsForServer($serverName, $server) as $tool) {
                $remoteName = (string) ($tool['name'] ?? '');

                if ($remoteName === '') {
                    continue;
                }

                if (! in_array('*', $allowed, true) && ! in_array($remoteName, $allowed, true)) {
                    continue;
                }

                $name = $this->safeToolName($serverName, $remoteName);

                if (isset($usedNames[$name])) {
                    $name = substr($name, 0, 55).'_'.substr(hash('sha256', $serverName.'|'.$remoteName), 0, 8);
                }

                $usedNames[$name] = true;

                $readOnly = (bool) ($server['read_only'] ?? false);
                $audiences = array_values(array_intersect(
                    (array) ($server['audiences'] ?? ['admin']),
                    ['public', 'voluntario', 'organizacion', 'admin']
                ));

                if ($audiences === []) {
                    continue;
                }

                if (! in_array('public', $audiences, true) && ! in_array($audience, $audiences, true)) {
                    continue;
                }

                $schema = is_array($tool['inputSchema'] ?? null)
                    ? $tool['inputSchema']
                    : ['type' => 'object', 'properties' => [], 'additionalProperties' => false];

                if (! isset($schema['type'])) {
                    $schema['type'] = 'object';
                }
                $schema['additionalProperties'] = (bool) ($schema['additionalProperties'] ?? false);

                $definitions[$name] = [
                    'server' => $serverName,
                    'remote_name' => $remoteName,
                    'description' => Str::limit((string) ($tool['description'] ?? "Herramienta MCP {$remoteName}"), 500, ''),
                    'audiences' => $audiences,
                    'requires_auth' => (bool) ($server['requires_auth'] ?? true),
                    'read_only' => $readOnly,
                    'requires_confirmation' => (bool) ($server['requires_confirmation'] ?? ! $readOnly),
                    'input_schema' => $schema,
                ];
            }
        }

        return $definitions;
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array{ok: bool, summary: string, data?: array<string, mixed>, links?: list<array<string, string>>}
     */
    public function call(string $serverName, string $tool, array $args): array
    {
        $server = $this->servers()[$serverName] ?? null;

        if ($server === null) {
            return ['ok' => false, 'summary' => 'El servidor de herramientas no está configurado.'];
        }

        $result = $this->client->callTool($server, $serverName, $tool, $args);

        return [
            'ok' => $result['ok'],
            'summary' => $result['text'] !== '' ? $result['text'] : 'La herramienta MCP no devolvió contenido textual.',
            'data' => is_array($result['structured']) ? $result['structured'] : [],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function servers(): array
    {
        $servers = config('chatbot.mcp.servers', []);

        return is_array($servers) ? $servers : [];
    }

    /**
     * @param  array<string, mixed>  $server
     * @return list<array<string, mixed>>
     */
    private function toolsForServer(string $serverName, array $server): array
    {
        $key = 'chatbot.mcp.tools.'.hash('sha256', $serverName.'|'.($server['url'] ?? ''));
        $cached = Cache::get($key);

        if (is_array($cached)) {
            return $cached;
        }

        try {
            $tools = $this->client->listTools($server, $serverName);
        } catch (\Throwable $exception) {
            Log::warning('chatbot.mcp.tools_unavailable', [
                'server' => $serverName,
                'message' => Str::limit($exception->getMessage(), 160),
            ]);

            return [];
        }

        Cache::put($key, $tools, (int) config('chatbot.mcp.tool_cache_ttl', 300));

        return $tools;
    }

    private function safeToolName(string $server, string $tool): string
    {
        $server = substr(preg_replace('/[^A-Za-z0-9]+/', '_', $server) ?? 'server', 0, 18);
        $tool = substr(preg_replace('/[^A-Za-z0-9]+/', '_', $tool) ?? 'tool', 0, 38);

        return Str::limit('mcp_'.$server.'_'.$tool, 64, '');
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Chatbot\Mcp\ToolExecutor;
use App\Services\Chatbot\Mcp\ToolRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

/**
 * Servidor MCP de VoluntMatch (Streamable HTTP, JSON-RPC 2.0).
 *
 * Expone las herramientas locales ya autorizadas para el rol del token,
 * con los mismos guardrails y confirmaciones que usa el widget. No actúa
 * como proxy de otros servidores MCP: solo publica lo que es nuestro.
 */
class McpController extends Controller
{
    public function __invoke(Request $request, ToolRegistry $registry, ToolExecutor $executor)
    {
        if (! config('api.mcp.enabled', true)) {
            return $this->rpcError(null, -32000, 'El servidor MCP está desactivado.', 503);
        }

        if ($request->isMethod('get')) {
            return response()->json([
                'ok' => true,
                'transport' => 'streamable-http',
                'protocol' => (string) config('api.mcp.protocol_version'),
                'message' => 'Usa POST con JSON-RPC 2.0 y un token Bearer con el scope mcp:use.',
            ])->header('Allow', 'POST');
        }

        $payload = $request->json()->all();

        if (! is_array($payload) || ! isset($payload['method'])) {
            return $this->rpcError($payload['id'] ?? null, -32600, 'Solicitud JSON-RPC no válida.');
        }

        $method = (string) $payload['method'];
        $id = $payload['id'] ?? null;
        $params = is_array($payload['params'] ?? null) ? $payload['params'] : [];

        try {
            return match ($method) {
                'initialize' => $this->initialize($id),
                'notifications/initialized' => response('', 202),
                'ping' => $this->rpcResult($id, (object) []),
                'tools/list' => $this->toolsList($request, $registry, $id),
                'tools/call' => $this->toolsCall($request, $executor, $id, $params),
                default => $this->rpcError($id, -32601, 'Método no soportado: '.$method),
            };
        } catch (Throwable $exception) {
            return $this->rpcError($id, -32603, 'Error interno del servidor MCP.');
        }
    }

    private function initialize(mixed $id)
    {
        return $this->rpcResult($id, [
            'protocolVersion' => (string) config('api.mcp.protocol_version', '2025-06-18'),
            'capabilities' => [
                'tools' => ['listChanged' => false],
            ],
            'serverInfo' => [
                'name' => (string) config('api.mcp.server_name', 'voluntmatch'),
                'version' => (string) config('api.mcp.server_version', '1.0.0'),
            ],
            'instructions' => 'Herramientas de VoluntMatch para consultar oportunidades, cuentas y abrir acciones. Las escrituras devuelven un token de confirmación de un solo uso; confírmalo con la herramienta confirmar_accion.',
        ])->header('Mcp-Session-Id', (string) Str::uuid());
    }

    private function toolsList(Request $request, ToolRegistry $registry, mixed $id)
    {
        $tools = [];

        foreach ($registry->all($this->audience($request)) as $name => $definition) {
            if (($definition['server'] ?? 'local') !== 'local') {
                continue;
            }

            $schema = $definition['input_schema'] ?? ['type' => 'object', 'properties' => []];
            if (isset($schema['properties']) && $schema['properties'] === []) {
                $schema['properties'] = (object) [];
            }

            $tools[] = [
                'name' => $name,
                'description' => (string) ($definition['description'] ?? ''),
                'inputSchema' => $schema,
                'annotations' => [
                    'readOnlyHint' => (bool) ($definition['read_only'] ?? true),
                    'destructiveHint' => false,
                    'requiresConfirmation' => (bool) ($definition['requires_confirmation'] ?? false),
                ],
            ];
        }

        return $this->rpcResult($id, ['tools' => $tools]);
    }

    private function toolsCall(Request $request, ToolExecutor $executor, mixed $id, array $params)
    {
        $name = (string) ($params['name'] ?? '');
        $arguments = is_array($params['arguments'] ?? null) ? $params['arguments'] : [];

        if ($name === '') {
            return $this->rpcError($id, -32602, 'Falta el nombre de la herramienta.');
        }

        $context = [
            'user' => $request->user(),
            'audience' => $this->audience($request),
            'conversation' => null,
            'request_id' => (string) Str::uuid(),
            'owner_key' => 'api:'.$request->attributes->get('api_client')?->id,
            'api_client' => $request->attributes->get('api_client'),
        ];

        $resultado = $executor->execute($name, $arguments, $context);

        return $this->rpcResult($id, [
            'content' => [
                ['type' => 'text', 'text' => (string) ($resultado['summary'] ?? 'Sin contenido.')],
            ],
            'structuredContent' => $resultado,
            'isError' => ! (bool) ($resultado['ok'] ?? false),
        ]);
    }

    private function audience(Request $request): string
    {
        return $request->attributes->get('api_client')?->audience() ?? 'public';
    }

    private function rpcResult(mixed $id, array|object $result): JsonResponse
    {
        return response()->json([
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => $result,
        ])->header('MCP-Protocol-Version', (string) config('api.mcp.protocol_version'))
            ->header('Cache-Control', 'no-store, private');
    }

    private function rpcError(mixed $id, int $code, string $message, int $status = 200): JsonResponse
    {
        return response()->json([
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => ['code' => $code, 'message' => $message],
        ], $status)->header('Cache-Control', 'no-store, private');
    }
}

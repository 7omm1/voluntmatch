<?php

namespace Tests\Feature;

use App\Services\Chatbot\Mcp\McpManager;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChatbotMcpTest extends TestCase
{
    public function test_mcp_solo_expone_herramientas_de_la_allowlist_y_su_audiencia(): void
    {
        config([
            'chatbot.mcp.enabled' => true,
            'chatbot.mcp.servers' => [
                'documentacion' => [
                    'url' => 'https://mcp.test/mcp',
                    'token' => 'token-de-prueba',
                    'allowed_tools' => ['buscar_documento'],
                    'audiences' => ['admin'],
                    'read_only' => true,
                    'timeout' => 5,
                ],
            ],
        ]);

        Http::preventStrayRequests();
        Http::fake(function ($request) {
            $payload = $request->data();
            $method = $payload['method'] ?? '';
            $id = $payload['id'] ?? 1;

            return match ($method) {
                'initialize' => Http::response([
                    'jsonrpc' => '2.0',
                    'id' => $id,
                    'result' => ['protocolVersion' => '2025-06-18'],
                ], 200, ['Content-Type' => 'application/json', 'Mcp-Session-Id' => 'sesion-1']),
                'notifications/initialized' => Http::response('', 202),
                'tools/list' => Http::response([
                    'jsonrpc' => '2.0',
                    'id' => $id,
                    'result' => [
                        'tools' => [
                            [
                                'name' => 'buscar_documento',
                                'description' => 'Busca en la documentación oficial.',
                                'inputSchema' => [
                                    'type' => 'object',
                                    'properties' => ['consulta' => ['type' => 'string']],
                                    'required' => ['consulta'],
                                ],
                            ],
                            [
                                'name' => 'borrar_todo',
                                'description' => 'No está en la allowlist.',
                                'inputSchema' => ['type' => 'object', 'properties' => []],
                            ],
                        ],
                    ],
                ], 200, ['Content-Type' => 'application/json']),
                'tools/call' => Http::response([
                    'jsonrpc' => '2.0',
                    'id' => $id,
                    'result' => [
                        'content' => [['type' => 'text', 'text' => 'Documento encontrado: guía de registro.']],
                        'isError' => false,
                    ],
                ], 200, ['Content-Type' => 'application/json']),
                default => Http::response(['error' => 'método no esperado'], 500),
            };
        });

        $manager = app(McpManager::class);

        $admin = $manager->definitions('admin');
        $this->assertArrayHasKey('mcp_documentacion_buscar_documento', $admin);
        $this->assertArrayNotHasKey('mcp_documentacion_borrar_todo', $admin);
        $this->assertSame('buscar_documento', $admin['mcp_documentacion_buscar_documento']['remote_name']);
        $this->assertSame('documentacion', $admin['mcp_documentacion_buscar_documento']['server']);
        $this->assertTrue($admin['mcp_documentacion_buscar_documento']['read_only']);

        $this->assertArrayNotHasKey('mcp_documentacion_buscar_documento', $manager->definitions('public'));

        $resultado = $manager->call('documentacion', 'buscar_documento', ['consulta' => 'registro']);

        $this->assertTrue($resultado['ok']);
        $this->assertStringContainsString('Documento encontrado', $resultado['summary']);

        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer token-de-prueba')
            && $request->hasHeader('Mcp-Session-Id', 'sesion-1'));
    }

    public function test_mcp_rechaza_herramientas_fuera_de_la_allowlist(): void
    {
        config([
            'chatbot.mcp.enabled' => true,
            'chatbot.mcp.servers' => [
                'documentacion' => [
                    'url' => 'https://mcp.test/mcp',
                    'allowed_tools' => ['buscar_documento'],
                    'audiences' => ['admin'],
                ],
            ],
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('no está permitida');

        app(McpManager::class)->call('documentacion', 'borrar_todo', []);
    }

    public function test_mcp_rechaza_urls_sin_https(): void
    {
        config([
            'chatbot.mcp.enabled' => true,
            'chatbot.mcp.allow_insecure' => false,
            'chatbot.mcp.servers' => [
                'interno' => [
                    'url' => 'http://mcp.test/mcp',
                    'allowed_tools' => ['*'],
                    'audiences' => ['admin'],
                ],
            ],
        ]);

        Http::preventStrayRequests();

        $this->assertSame([], app(McpManager::class)->definitions('admin'));
    }
}

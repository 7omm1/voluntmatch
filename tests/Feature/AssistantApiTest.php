<?php

namespace Tests\Feature;

use App\Models\ApiClient;
use App\Models\ChatbotConversation;
use App\Models\Oportunidad;
use App\Models\Postulacion;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\ChatbotKnowledgeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssistantApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ChatbotKnowledgeSeeder::class);
    }

    public function test_la_api_exige_un_token_bearer(): void
    {
        $this->postJson('/api/v1/assistant/messages', ['message' => 'Hola'])
            ->assertStatus(401)
            ->assertJsonPath('ok', false);
    }

    public function test_un_token_publico_conversa_y_guarda_el_canal_api(): void
    {
        [$client, $plain] = ApiClient::issue('Widget externo', 'public', null, ['assistant:chat']);

        $response = $this->withToken($plain)->postJson('/api/v1/assistant/messages', [
            'message' => '¿Cómo me registro como voluntario con mi DNI?',
        ])->assertOk();

        $this->assertNotEmpty($response->json('message'));
        $this->assertSame('api', ChatbotConversation::where('public_id', $response->json('conversation_id'))->value('channel'));
        $this->assertSame('api:'.$client->id, ChatbotConversation::where('public_id', $response->json('conversation_id'))->value('owner_key'));
        $this->assertNotNull($client->fresh()->last_used_at);
    }

    public function test_la_interfaz_compatible_con_openai_devuelve_una_completion(): void
    {
        [, $plain] = ApiClient::issue('Cliente OpenAI', 'public', null, ['assistant:chat']);

        $response = $this->withToken($plain)->postJson('/api/v1/chat/completions', [
            'model' => 'vera',
            'messages' => [
                ['role' => 'system', 'content' => 'Eres un asistente.'],
                ['role' => 'user', 'content' => '¿Cómo funciona el proceso de postulación?'],
            ],
        ])->assertOk();

        $this->assertSame('chat.completion', $response->json('object'));
        $this->assertSame('assistant', $response->json('choices.0.message.role'));
        $this->assertNotEmpty($response->json('choices.0.message.content'));
        $this->assertNotEmpty($response->json('voluntmatch.conversation_id'));

        $this->withToken($plain)->postJson('/api/v1/chat/completions', [
            'messages' => [['role' => 'assistant', 'content' => 'Hola']],
        ])->assertStatus(422);
    }

    public function test_los_scopes_se_exigen_por_endpoint(): void
    {
        [, $plain] = ApiClient::issue('Solo chat', 'public', null, ['assistant:chat']);

        $this->withToken($plain)->getJson('/api/v1/tools')
            ->assertStatus(403)
            ->assertJsonPath('ok', false);

        $this->withToken($plain)->postJson('/api/v1/mcp', [
            'jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize', 'params' => [],
        ])->assertStatus(403);
    }

    public function test_un_token_privado_solo_ve_los_datos_de_su_cuenta(): void
    {
        $voluntario = User::factory()->create(['role' => 'voluntario']);
        $otro = User::factory()->create(['role' => 'voluntario']);

        $propia = Oportunidad::factory()->create(['titulo' => 'Reto API propio', 'estado' => 'publicada']);
        $ajena = Oportunidad::factory()->create(['titulo' => 'Reto API ajeno', 'estado' => 'publicada']);

        Postulacion::create(['user_id' => $voluntario->id, 'oportunidad_id' => $propia->id, 'estado' => 'en entrevista']);
        Postulacion::create(['user_id' => $otro->id, 'oportunidad_id' => $ajena->id, 'estado' => 'aceptado']);

        [, $plain] = ApiClient::issue('App del voluntario', 'voluntario', $voluntario, ['assistant:tools']);

        $response = $this->withToken($plain)
            ->postJson('/api/v1/tools/consultar_mis_postulaciones', ['arguments' => []])
            ->assertOk();

        $items = json_encode($response->json('result.data.items'), JSON_UNESCAPED_UNICODE);
        $this->assertStringContainsString('Reto API propio', (string) $items);
        $this->assertStringNotContainsString('Reto API ajeno', (string) $items);
    }

    public function test_una_escritura_devuelve_token_de_confirmacion_y_es_de_un_solo_uso(): void
    {
        $voluntario = User::factory()->create(['role' => 'voluntario']);
        [, $plain] = ApiClient::issue('Backoffice', 'voluntario', $voluntario, ['assistant:tools', 'assistant:confirm']);

        $pendiente = $this->withToken($plain)
            ->postJson('/api/v1/tools/crear_ticket_soporte', [
                'arguments' => [
                    'asunto' => 'No puedo completar mi perfil',
                    'mensaje' => 'El botón de guardar no responde desde ayer.',
                ],
            ])
            ->assertOk();

        $token = (string) $pendiente->json('result.pending.token');
        $this->assertNotSame('', $token);
        $this->assertSame(0, Ticket::count());

        $this->withToken($plain)->postJson('/api/v1/assistant/confirm', [
            'token' => $token,
            'decision' => 'accept',
        ])->assertOk();

        $this->assertSame(1, Ticket::count());
        $this->assertSame('asistente', Ticket::first()->canal);

        // El token ya se consumió.
        $this->withToken($plain)->postJson('/api/v1/assistant/confirm', [
            'token' => $token,
            'decision' => 'accept',
        ])->assertOk()->assertJsonPath('ok', false);

        $this->assertSame(1, Ticket::count());
    }

    public function test_un_token_revocado_deja_de_funcionar(): void
    {
        [$client, $plain] = ApiClient::issue('Temporal', 'public', null, ['assistant:chat']);

        $this->withToken($plain)->postJson('/api/v1/assistant/messages', ['message' => 'Hola'])
            ->assertOk();

        $client->forceFill(['active' => false, 'revoked_at' => now()])->save();

        $this->withToken($plain)->postJson('/api/v1/assistant/messages', ['message' => 'Hola'])
            ->assertStatus(401);
    }

    public function test_el_servidor_mcp_completa_el_handshake_y_expone_solo_herramientas_locales(): void
    {
        [, $plain] = ApiClient::issue('Agente MCP', 'public', null, ['mcp:use']);

        $init = $this->withToken($plain)->postJson('/api/v1/mcp', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => ['protocolVersion' => '2025-06-18'],
        ])->assertOk();

        $this->assertSame('voluntmatch', $init->json('result.serverInfo.name'));
        $init->assertHeader('Mcp-Session-Id');

        $lista = $this->withToken($plain)->postJson('/api/v1/mcp', [
            'jsonrpc' => '2.0',
            'id' => 2,
            'method' => 'tools/list',
        ])->assertOk();

        $nombres = array_column($lista->json('result.tools'), 'name');
        $this->assertContains('buscar_oportunidades', $nombres);
        $this->assertNotContains('consultar_mis_postulaciones', $nombres);

        $llamada = $this->withToken($plain)->postJson('/api/v1/mcp', [
            'jsonrpc' => '2.0',
            'id' => 3,
            'method' => 'tools/call',
            'params' => ['name' => 'resumen_plataforma', 'arguments' => []],
        ])->assertOk();

        $this->assertFalse((bool) $llamada->json('result.isError'));
        $this->assertStringContainsString('oportunidades', (string) $llamada->json('result.content.0.text'));
    }

    public function test_mcp_confirma_una_escritura_con_el_token_de_un_solo_uso(): void
    {
        $voluntario = User::factory()->create(['role' => 'voluntario']);
        [, $plain] = ApiClient::issue('Agente MCP privado', 'voluntario', $voluntario, ['mcp:use']);

        $pendiente = $this->withToken($plain)->postJson('/api/v1/mcp', [
            'jsonrpc' => '2.0',
            'id' => 4,
            'method' => 'tools/call',
            'params' => [
                'name' => 'crear_ticket_soporte',
                'arguments' => ['asunto' => 'Caso desde MCP', 'mensaje' => 'Necesito ayuda con mi cuenta.'],
            ],
        ])->assertOk();

        $this->assertTrue((bool) $pendiente->json('result.structuredContent.requires_confirmation'));
        $token = (string) $pendiente->json('result.structuredContent.pending.token');
        $this->assertSame(0, Ticket::count());

        $confirmado = $this->withToken($plain)->postJson('/api/v1/mcp', [
            'jsonrpc' => '2.0',
            'id' => 5,
            'method' => 'tools/call',
            'params' => ['name' => 'confirmar_accion', 'arguments' => ['token' => $token]],
        ])->assertOk();

        $this->assertFalse((bool) $confirmado->json('result.isError'));
        $this->assertSame(1, Ticket::count());
    }
}

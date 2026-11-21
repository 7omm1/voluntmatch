<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Chatbot\LlmClient;
use Database\Seeders\ChatbotKnowledgeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChatbotLlmTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ChatbotKnowledgeSeeder::class);

        config([
            'chatbot.driver' => 'gemini',
            'chatbot.llm.base_url' => 'https://llm.test/v1',
            'chatbot.llm.api_key' => 'test-key',
            'chatbot.llm.model' => 'test-model',
            'chatbot.llm.model_small' => 'test-model',
            'chatbot.llm.model_large' => 'test-model',
            'chatbot.llm.answer_without_context' => true,
            'chatbot.rag.embeddings' => false,
            'chatbot.cache.enabled' => false,
        ]);
    }

    public function test_el_modelo_recibe_el_historial_de_la_conversacion(): void
    {
        Http::fake([
            'llm.test/*' => Http::sequence()
                ->push($this->completion('Primera respuesta de Vera.'))
                ->push($this->completion('Segunda respuesta de Vera.')),
        ]);

        $user = User::factory()->create(['role' => 'voluntario']);

        $primera = $this->actingAs($user)->postJson(route('chatbot.mensaje'), [
            'message' => '¿Cómo funciona el proceso de postulación?',
        ])->assertOk();

        $this->actingAs($user)->postJson(route('chatbot.mensaje'), [
            'message' => '¿Y cuánto suele demorar?',
            'conversation_id' => $primera->json('conversation_id'),
        ])->assertOk();

        $peticiones = Http::recorded();
        $this->assertCount(2, $peticiones);

        $mensajes = $peticiones[1][0]->data()['messages'];

        $this->assertSame(['system', 'user', 'assistant', 'user'], array_column($mensajes, 'role'));
        $this->assertSame('¿Cómo funciona el proceso de postulación?', $mensajes[1]['content']);
        $this->assertSame('Primera respuesta de Vera.', $mensajes[2]['content']);
        $this->assertStringContainsString('¿Y cuánto suele demorar?', $mensajes[3]['content']);
    }

    public function test_sin_contexto_recuperado_el_modelo_sigue_respondiendo(): void
    {
        Http::fake([
            'llm.test/*' => Http::response($this->completion('No tengo ese dato exacto, pero puedo orientarte.')),
        ]);

        $response = $this->postJson(route('chatbot.mensaje'), [
            'message' => 'zzzz qqqq wwww',
        ])->assertOk();

        $this->assertStringContainsString('No tengo ese dato', (string) $response->json('message'));
        Http::assertSentCount(1);
    }

    public function test_sin_contexto_se_puede_desactivar_la_llamada_al_modelo(): void
    {
        config(['chatbot.llm.answer_without_context' => false]);

        Http::fake();

        $response = $this->postJson(route('chatbot.mensaje'), [
            'message' => 'zzzz qqqq wwww',
        ])->assertOk();

        $this->assertStringContainsString('No encontré', (string) $response->json('message'));
        Http::assertNothingSent();
    }

    public function test_el_cliente_detecta_cada_proveedor_y_sus_embeddings(): void
    {
        $llm = app(LlmClient::class);

        config(['chatbot.driver' => 'local']);
        $this->assertFalse($llm->isConfigured());

        config(['chatbot.driver' => 'gemini', 'chatbot.llm.api_key' => '']);
        $this->assertFalse($llm->isConfigured());

        config(['chatbot.llm.api_key' => 'test-key']);
        $this->assertTrue($llm->isConfigured());
        $this->assertTrue($llm->supportsEmbeddings());

        config(['chatbot.driver' => 'ollama', 'chatbot.llm.api_key' => null]);
        $this->assertTrue($llm->isConfigured());

        config(['chatbot.driver' => 'groq', 'chatbot.llm.api_key' => 'test-key', 'chatbot.llm.embedding_model' => '']);
        $this->assertTrue($llm->isConfigured());
        $this->assertFalse($llm->supportsEmbeddings());
    }

    public function test_los_presets_definen_urls_y_modelos_por_proveedor(): void
    {
        $this->assertSame(
            'https://generativelanguage.googleapis.com/v1beta/openai',
            config('chatbot.presets.gemini.base_url')
        );
        $this->assertSame('gemini-2.5-flash', config('chatbot.presets.gemini.model'));
        $this->assertTrue(config('chatbot.presets.gemini.embeddings'));

        $this->assertSame('https://api.groq.com/openai/v1', config('chatbot.presets.groq.base_url'));
        $this->assertFalse(config('chatbot.presets.groq.embeddings'));
    }

    /**
     * @return array<string, mixed>
     */
    private function completion(string $content): array
    {
        return [
            'id' => 'chatcmpl-test',
            'object' => 'chat.completion',
            'created' => now()->timestamp,
            'model' => 'test-model',
            'choices' => [[
                'index' => 0,
                'message' => [
                    'role' => 'assistant',
                    'content' => $content,
                ],
                'finish_reason' => 'stop',
            ]],
            'usage' => [
                'prompt_tokens' => 10,
                'completion_tokens' => 5,
                'total_tokens' => 15,
            ],
        ];
    }
}

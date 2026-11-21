<?php

namespace App\Console\Commands;

use App\Services\Chatbot\LlmClient;
use Illuminate\Console\Command;
use Throwable;

/**
 * Comprueba que el proveedor de lenguaje configurado responde de verdad.
 *
 * Sirve para diagnosticar un host antes de publicar: valida la URL, la
 * clave, el modelo y, si se pide, el endpoint de embeddings.
 */
class ChatbotDoctor extends Command
{
    protected $signature = 'chatbot:doctor {--embeddings : Prueba también el endpoint de embeddings}';

    protected $description = 'Verifica la conexión con el proveedor de lenguaje del asistente';

    public function handle(LlmClient $llm): int
    {
        $driver = $llm->driver();
        $baseUrl = (string) config('chatbot.llm.base_url');
        $model = $llm->modelFor('small');

        $this->line('Driver: '.$driver);
        $this->line('Base URL: '.$baseUrl);
        $this->line('Modelo: '.$model);
        $this->line('Clave: '.(filled(config('chatbot.llm.api_key')) ? 'configurada' : 'no configurada'));

        if ($driver === 'local') {
            $this->newLine();
            $this->warn('El asistente está en modo local: no se llamará a ninguna API externa.');
            $this->line('Define CHATBOT_DRIVER (gemini, openai, groq, deepseek, openrouter u ollama) y CHATBOT_LLM_API_KEY.');

            return self::FAILURE;
        }

        if (! $llm->isConfigured()) {
            $this->newLine();
            $this->error('El proveedor no está configurado. Revisa CHATBOT_LLM_API_KEY y CHATBOT_LLM_BASE_URL.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Probando Chat Completions…');

        try {
            $started = hrtime(true);

            $response = $llm->chat(
                [['role' => 'user', 'content' => 'Responde únicamente con la palabra OK.']],
                [],
                $model
            );

            $latency = (int) round((hrtime(true) - $started) / 1_000_000);

            $this->line('Respuesta: '.($response['content'] !== '' ? $response['content'] : '(vacía)'));
            $this->line('Modelo devuelto: '.$response['model']);
            $this->line('Tokens: '.$response['tokens_in'].' entrada / '.$response['tokens_out'].' salida');
            $this->info("Chat OK en {$latency} ms.");
        } catch (Throwable $exception) {
            $this->error('Falló Chat Completions: '.$exception->getMessage());

            return self::FAILURE;
        }

        if ($this->option('embeddings')) {
            $this->newLine();
            $this->info('Probando embeddings…');

            try {
                $vectors = $llm->embed('prueba de conexión de VoluntMatch');

                if ($vectors === [] || ! is_array($vectors[0] ?? null)) {
                    $this->warn('El proveedor no devolvió vectores. Revisa CHATBOT_EMBEDDING_MODEL o desactiva CHATBOT_RAG_EMBEDDINGS.');

                    return self::FAILURE;
                }

                $this->info('Embeddings OK: '.count($vectors[0]).' dimensiones.');
            } catch (Throwable $exception) {
                $this->error('Fallaron los embeddings: '.$exception->getMessage());

                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }
}

<?php

namespace App\Services\Chatbot;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Cliente para cualquier endpoint compatible con OpenAI (Chat Completions
 * y Embeddings). Si no hay credenciales, el chatbot funciona en modo local:
 * nunca se llama a la red por accidente.
 */
class LlmClient
{
    public function driver(): string
    {
        return (string) config('chatbot.driver', 'local');
    }

    public function isConfigured(): bool
    {
        if ($this->driver() !== 'openai') {
            return false;
        }

        return filled(config('chatbot.llm.api_key')) && filled(config('chatbot.llm.base_url'));
    }

    public function modelFor(string $complexity = 'small'): string
    {
        return (string) config(
            'chatbot.llm.'.($complexity === 'large' ? 'model_large' : 'model_small'),
            config('chatbot.llm.model')
        );
    }

    /**
     * @param  list<array<string, mixed>>  $messages
     * @param  list<array<string, mixed>>  $tools
     * @return array{
     *     content: string,
     *     tool_calls: list<array{id: string, name: string, arguments: array<string, mixed>}>,
     *     tokens_in: int,
     *     tokens_out: int,
     *     model: string
     * }
     */
    public function chat(array $messages, array $tools = [], ?string $model = null): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('El proveedor de lenguaje no está configurado.');
        }

        $model ??= $this->modelFor('small');

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => (float) config('chatbot.llm.temperature', 0.2),
            'max_tokens' => (int) config('chatbot.llm.max_tokens', 700),
        ];

        if ($tools !== []) {
            $payload['tools'] = $tools;
            $payload['tool_choice'] = 'auto';
        }

        $response = $this->request('/chat/completions', $payload);

        $choice = $response['choices'][0] ?? null;
        if (! is_array($choice)) {
            throw new RuntimeException('El proveedor de lenguaje devolvió una respuesta vacía.');
        }

        $message = $choice['message'] ?? [];
        $toolCalls = [];

        foreach (($message['tool_calls'] ?? []) as $call) {
            $arguments = $call['function']['arguments'] ?? '{}';

            if (is_string($arguments)) {
                $decoded = json_decode($arguments, true);
                $arguments = is_array($decoded) ? $decoded : [];
            }

            $toolCalls[] = [
                'id' => (string) ($call['id'] ?? ''),
                'name' => (string) ($call['function']['name'] ?? ''),
                'arguments' => is_array($arguments) ? $arguments : [],
            ];
        }

        return [
            'content' => trim((string) ($message['content'] ?? '')),
            'tool_calls' => $toolCalls,
            'tokens_in' => (int) ($response['usage']['prompt_tokens'] ?? 0),
            'tokens_out' => (int) ($response['usage']['completion_tokens'] ?? 0),
            'model' => (string) ($response['model'] ?? $model),
        ];
    }

    /**
     * @param  string|list<string>  $input
     * @return list<list<float>>
     */
    public function embed(string|array $input): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        $response = $this->request('/embeddings', [
            'model' => (string) config('chatbot.llm.embedding_model'),
            'input' => $input,
        ]);

        $vectors = [];
        foreach (($response['data'] ?? []) as $item) {
            if (isset($item['embedding']) && is_array($item['embedding'])) {
                $vectors[] = array_map('floatval', $item['embedding']);
            }
        }

        return $vectors;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function request(string $path, array $payload): array
    {
        $base = rtrim((string) config('chatbot.llm.base_url'), '/');
        $key = (string) config('chatbot.llm.api_key');

        $request = Http::acceptJson()
            ->asJson()
            ->timeout((int) config('chatbot.llm.timeout', 20))
            ->connectTimeout((int) config('chatbot.llm.connect_timeout', 5));

        if ($key !== '') {
            $request = $request->withToken($key);
        }

        try {
            $response = $request->retry(
                max(0, (int) config('chatbot.llm.retries', 1)),
                200
            )->post($base.$path, $payload);
        } catch (\Throwable $exception) {
            throw new RuntimeException('No se pudo contactar al proveedor de lenguaje.', 0, $exception);
        }

        if (! $response->successful()) {
            throw new RuntimeException('El proveedor de lenguaje respondió con error '.$response->status().'.');
        }

        $data = $response->json();

        return is_array($data) ? $data : [];
    }
}

<?php

namespace App\Services\Chatbot;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
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
        if ($this->driver() === 'local') {
            return false;
        }

        if (blank(config('chatbot.llm.base_url'))) {
            return false;
        }

        // Ollama corre en la misma máquina y no usa clave.
        if ($this->driver() === 'ollama') {
            return true;
        }

        return filled(config('chatbot.llm.api_key'));
    }

    public function supportsEmbeddings(): bool
    {
        return $this->isConfigured()
            && filled(config('chatbot.llm.embedding_model'));
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
     *     tool_calls: list<array{id: string, name: string, arguments: array<string, mixed>, extra_content: array<string, mixed>|null}>,
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

            // Gemini 3 exige devolver este bloque al reenviar la llamada.
            $extra = $call['extra_content'] ?? null;

            $toolCalls[] = [
                'id' => (string) ($call['id'] ?? ''),
                'name' => (string) ($call['function']['name'] ?? ''),
                'arguments' => is_array($arguments) ? $arguments : [],
                'extra_content' => is_array($extra) ? $extra : null,
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

    private function errorDetail(mixed $body): string
    {
        if (is_string($body)) {
            $body = json_decode($body, true);
        }

        if (! is_array($body)) {
            return '';
        }

        $message = $body['error']['message'] ?? $body['error'] ?? $body['message'] ?? '';

        if (is_array($message)) {
            $message = $message['message'] ?? '';
        }

        $message = trim(preg_replace('/\s+/u', ' ', (string) $message) ?? '');

        return Str::limit($message, 300, '…');
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

        // OpenRouter pide identificación del cliente para atribuir el uso.
        if ($this->driver() === 'openrouter') {
            $request = $request->withHeaders([
                'HTTP-Referer' => (string) config('app.url'),
                'X-Title' => (string) config('app.name'),
            ]);
        }

        try {
            $response = $request->retry(
                max(1, (int) config('chatbot.llm.retries', 2)),
                200,
                throw: false
            )->post($base.$path, $payload);
        } catch (\Throwable $exception) {
            throw new RuntimeException('No se pudo contactar al proveedor de lenguaje.', 0, $exception);
        }

        if (! $response->successful()) {
            $detalle = $this->errorDetail($response->json());

            throw new RuntimeException(
                'El proveedor de lenguaje respondió con error '.$response->status()
                .($detalle !== '' ? ': '.$detalle : '.')
            );
        }

        $data = $response->json();

        return is_array($data) ? $data : [];
    }
}

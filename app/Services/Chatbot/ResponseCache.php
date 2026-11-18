<?php

namespace App\Services\Chatbot;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;

/**
 * Caché de respuestas no personalizadas.
 *
 * La clave incluye audiencia, versión del prompt, versión del conocimiento,
 * ruta, modelo y el texto normalizado. Nunca se usa para resultados de
 * herramientas, acciones pendientes ni conversaciones autenticadas.
 */
class ResponseCache
{
    public function key(
        string $message,
        string $audience,
        string $route,
        ?string $model,
        int $knowledgeVersion
    ): string {
        $normalized = mb_strtolower(preg_replace('/\s+/u', ' ', trim($message)) ?? $message);

        return sprintf(
            '%s.response.v%s.%d.%s.%s.%s.%s',
            (string) config('chatbot.cache.prefix', 'chatbot'),
            (string) config('chatbot.prompt.version', '1'),
            $knowledgeVersion,
            $audience,
            $route,
            hash('sha256', $normalized),
            hash('sha256', (string) $model)
        );
    }

    public function get(string $key): ?array
    {
        if (! config('chatbot.cache.enabled', true)) {
            return null;
        }

        $value = $this->store()->get($key);

        return is_array($value) ? $value : null;
    }

    public function put(string $key, array $value): void
    {
        if (! config('chatbot.cache.enabled', true)) {
            return;
        }

        $this->store()->put($key, $value, (int) config('chatbot.cache.ttl', 300));
    }

    public function forget(string $key): void
    {
        $this->store()->forget($key);
    }

    private function store(): Repository
    {
        $store = config('chatbot.cache.store');

        return $store ? Cache::store($store) : Cache::store();
    }
}

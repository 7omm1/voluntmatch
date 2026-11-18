<?php

namespace App\Services\Chatbot;

use App\Models\ChatbotConversation;
use App\Models\ChatbotMessage;
use App\Models\ChatbotTrace;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Observabilidad del asistente.
 *
 * Las trazas son metadatos: intención, ruta, caché, latencia, tokens,
 * fuentes, herramientas y flags de guardrail. El contenido de la
 * conversación se guarda en otra tabla para el historial del usuario y se
 * puede desactivar con CHATBOT_STORE_CONTENT=false.
 */
class ChatbotObserver
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function trace(array $data): ?ChatbotTrace
    {
        if (! config('chatbot.observability.store', true)) {
            return null;
        }

        try {
            return ChatbotTrace::create([
                'request_id' => (string) $data['request_id'],
                'conversation_id' => $data['conversation_id'] ?? null,
                'user_id' => $data['user_id'] ?? null,
                'audience' => (string) ($data['audience'] ?? 'public'),
                'intent' => isset($data['intent']) ? Str::limit((string) $data['intent'], 40, '') : null,
                'route' => isset($data['route']) ? Str::limit((string) $data['route'], 40, '') : null,
                'model' => isset($data['model']) ? Str::limit((string) $data['model'], 80, '') : null,
                'cache_hit' => (bool) ($data['cache_hit'] ?? false),
                'latency_ms' => max(0, (int) ($data['latency_ms'] ?? 0)),
                'tokens_in' => max(0, (int) ($data['tokens_in'] ?? 0)),
                'tokens_out' => max(0, (int) ($data['tokens_out'] ?? 0)),
                'retrieved' => $data['retrieved'] ?? null,
                'tool_calls' => $data['tool_calls'] ?? null,
                'guardrails' => $data['guardrails'] ?? null,
                'status' => (string) ($data['status'] ?? 'ok'),
                'error' => isset($data['error']) ? Str::limit((string) $data['error'], 1000, '…') : null,
                'ip_hash' => $data['ip_hash'] ?? null,
            ]);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function userMessage(
        ChatbotConversation $conversation,
        string $requestId,
        string $content,
        array $metadata = []
    ): ChatbotMessage {
        return $this->message($conversation, $requestId, 'user', $content, $metadata);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function assistantMessage(
        ChatbotConversation $conversation,
        string $requestId,
        string $content,
        array $metadata = []
    ): ChatbotMessage {
        return $this->message($conversation, $requestId, 'assistant', $content, $metadata);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function message(
        ChatbotConversation $conversation,
        string $requestId,
        string $role,
        string $content,
        array $metadata = []
    ): ChatbotMessage {
        $store = (bool) config('chatbot.observability.store_message_content', true);

        return ChatbotMessage::create([
            'conversation_id' => $conversation->id,
            'request_id' => Str::limit($requestId, 64, ''),
            'role' => $role,
            'content' => $store ? $content : '[contenido no almacenado]',
            'intent' => isset($metadata['intent']) ? Str::limit((string) $metadata['intent'], 40, '') : null,
            'route' => isset($metadata['route']) ? Str::limit((string) $metadata['route'], 40, '') : null,
            'citations' => $metadata['citations'] ?? null,
            'tool_calls' => $metadata['tool_calls'] ?? null,
            'guardrails' => $metadata['guardrails'] ?? null,
            'metadata' => $metadata['meta'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function log(string $event, array $context): void
    {
        $channel = config('chatbot.observability.log_channel');

        try {
            if ($channel) {
                Log::channel($channel)->info($event, $context);
            } else {
                Log::info($event, $context);
            }
        } catch (\Throwable) {
            // El logging nunca debe interrumpir la respuesta.
        }
    }
}

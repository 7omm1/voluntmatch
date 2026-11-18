<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Una traza por petición al asistente. No guarda el texto de la
 * conversación: solo metadatos, capas ejecutadas y resultado.
 */
class ChatbotTrace extends Model
{
    protected $table = 'chatbot_traces';

    protected $fillable = [
        'request_id',
        'conversation_id',
        'user_id',
        'audience',
        'intent',
        'route',
        'model',
        'cache_hit',
        'latency_ms',
        'tokens_in',
        'tokens_out',
        'retrieved',
        'tool_calls',
        'guardrails',
        'status',
        'error',
        'ip_hash',
    ];

    protected function casts(): array
    {
        return [
            'cache_hit' => 'boolean',
            'retrieved' => 'array',
            'tool_calls' => 'array',
            'guardrails' => 'array',
            'latency_ms' => 'integer',
            'tokens_in' => 'integer',
            'tokens_out' => 'integer',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatbotConversation::class, 'conversation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

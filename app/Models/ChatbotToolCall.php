<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de cada herramienta invocada (local o servidor MCP).
 * Los argumentos se guardan redactados para no conservar datos sensibles.
 */
class ChatbotToolCall extends Model
{
    protected $table = 'chatbot_tool_calls';

    protected $fillable = [
        'request_id',
        'conversation_id',
        'user_id',
        'tool',
        'server',
        'arguments',
        'status',
        'latency_ms',
        'result_summary',
        'error',
    ];

    protected function casts(): array
    {
        return [
            'arguments' => 'array',
            'latency_ms' => 'integer',
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

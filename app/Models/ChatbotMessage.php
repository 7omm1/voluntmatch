<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotMessage extends Model
{
    protected $table = 'chatbot_messages';

    protected $fillable = [
        'conversation_id',
        'request_id',
        'role',
        'content',
        'intent',
        'route',
        'citations',
        'tool_calls',
        'guardrails',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'citations' => 'array',
            'tool_calls' => 'array',
            'guardrails' => 'array',
            'metadata' => 'array',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatbotConversation::class, 'conversation_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Hilo de conversación con el asistente. El id público es un UUID:
 * los ids secuenciales nunca se exponen al navegador.
 */
class ChatbotConversation extends Model
{
    protected $table = 'chatbot_conversations';

    protected $fillable = [
        'public_id',
        'user_id',
        'owner_key',
        'role',
        'channel',
        'status',
        'escalated_at',
        'metadata',
        'last_activity_at',
    ];

    protected function casts(): array
    {
        return [
            'escalated_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $conversation) {
            $conversation->public_id ??= (string) Str::uuid();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatbotMessage::class, 'conversation_id');
    }

    public function traces(): HasMany
    {
        return $this->hasMany(ChatbotTrace::class, 'conversation_id');
    }
}

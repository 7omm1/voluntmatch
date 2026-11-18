<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotKnowledgeChunk extends Model
{
    protected $table = 'chatbot_knowledge_chunks';

    protected $fillable = [
        'document_id',
        'position',
        'content',
        'tokens',
        'terms',
        'embedding',
    ];

    protected function casts(): array
    {
        return [
            'terms' => 'array',
            'embedding' => 'array',
            'tokens' => 'integer',
            'position' => 'integer',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(ChatbotKnowledgeDocument::class, 'document_id');
    }
}

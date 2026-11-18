<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatbotKnowledgeDocument extends Model
{
    protected $table = 'chatbot_knowledge_documents';

    protected $fillable = [
        'slug',
        'title',
        'body',
        'audiences',
        'tags',
        'source',
        'version',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'audiences' => 'array',
            'tags' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(ChatbotKnowledgeChunk::class, 'document_id')->orderBy('position');
    }

    /** ¿El documento es visible para el rol que está conversando? */
    public function visiblePara(string $audience): bool
    {
        $audiences = $this->audiences ?: ['public'];

        return in_array('public', $audiences, true) || in_array($audience, $audiences, true);
    }
}

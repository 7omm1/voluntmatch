<?php

namespace App\Console\Commands;

use App\Models\ChatbotKnowledgeDocument;
use App\Services\Chatbot\Rag\KnowledgeIndexer;
use Illuminate\Console\Command;

/**
 * Reconstruye los fragmentos RAG y sube la versión del conocimiento.
 * Al cambiar la versión se invalidan las claves de caché de respuestas.
 */
class ChatbotReindex extends Command
{
    protected $signature = 'chatbot:reindex';

    protected $description = 'Reindexa los documentos de conocimiento del asistente virtual';

    public function handle(KnowledgeIndexer $indexer): int
    {
        $documentos = ChatbotKnowledgeDocument::where('is_active', true)->count();

        if ($documentos === 0) {
            $this->warn('No hay documentos activos. Ejecuta: php artisan db:seed --class=ChatbotKnowledgeSeeder');

            return self::FAILURE;
        }

        $chunks = $indexer->reindex();

        $this->info("Listo: {$documentos} documentos y {$chunks} fragmentos indexados.");
        $this->line('Versión de conocimiento: '.$indexer->version());

        return self::SUCCESS;
    }
}

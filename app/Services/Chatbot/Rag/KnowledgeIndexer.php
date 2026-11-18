<?php

namespace App\Services\Chatbot\Rag;

use App\Models\ChatbotKnowledgeChunk;
use App\Models\ChatbotKnowledgeDocument;
use App\Services\Chatbot\LlmClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Convierte los documentos de conocimiento en fragmentos consultables.
 *
 * El chunking respeta párrafos para que cada fragmento tenga sentido por sí
 * solo. Si los embeddings están activos se generan de forma opcional; si el
 * proveedor falla, el índice léxico sigue funcionando.
 */
class KnowledgeIndexer
{
    public const VERSION_KEY = 'chatbot.knowledge.version';

    public function __construct(
        private readonly Tokenizer $tokenizer,
        private readonly LlmClient $llm,
    ) {}

    public function reindex(): int
    {
        $total = 0;

        $documents = ChatbotKnowledgeDocument::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        foreach ($documents as $document) {
            $document->chunks()->delete();

            $chunks = $this->chunk((string) $document->body);
            $embeddings = $this->embeddingsFor($chunks);

            foreach ($chunks as $position => $content) {
                ChatbotKnowledgeChunk::create([
                    'document_id' => $document->id,
                    'position' => $position,
                    'content' => $content,
                    'tokens' => count($this->tokenizer->tokens($content)),
                    'terms' => $this->tokenizer->termFrequencies($content),
                    'embedding' => $embeddings[$position] ?? null,
                ]);

                $total++;
            }
        }

        $this->bumpVersion();
        Cache::forget('chatbot.rag.corpus');

        return $total;
    }

    /**
     * @return list<string>
     */
    public function chunk(string $body, ?int $size = null, ?int $overlap = null): array
    {
        $size = $size ?? (int) config('chatbot.rag.chunk_chars', 700);
        $overlap = $overlap ?? (int) config('chatbot.rag.chunk_overlap', 90);
        $body = trim(preg_replace('/\r\n?/u', "\n", $body) ?? $body);

        if ($body === '') {
            return [];
        }

        $blocks = preg_split('/\n{2,}/u', $body, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $chunks = [];
        $current = '';

        foreach ($blocks as $block) {
            $block = trim($block);

            if ($block === '') {
                continue;
            }

            if (mb_strlen($block) > $size) {
                if ($current !== '') {
                    $chunks[] = trim($current);
                    $current = '';
                }

                foreach ($this->splitLargeBlock($block, $size) as $piece) {
                    $chunks[] = $piece;
                }

                continue;
            }

            $candidate = $current === '' ? $block : $current."\n\n".$block;

            if (mb_strlen($candidate) <= $size) {
                $current = $candidate;

                continue;
            }

            if ($current !== '') {
                $chunks[] = trim($current);
            }

            $tail = $overlap > 0 ? mb_substr($current, -$overlap) : '';
            $current = trim($tail."\n\n".$block);
        }

        if ($current !== '') {
            $chunks[] = trim($current);
        }

        return array_values(array_filter($chunks, fn (string $chunk) => $chunk !== ''));
    }

    public function version(): int
    {
        return (int) Cache::get(self::VERSION_KEY, 1);
    }

    public function bumpVersion(): int
    {
        $version = $this->version() + 1;
        Cache::forever(self::VERSION_KEY, $version);

        return $version;
    }

    /**
     * @param  list<string>  $chunks
     * @return array<int, list<float>>
     */
    private function embeddingsFor(array $chunks): array
    {
        if (! config('chatbot.rag.embeddings') || ! $this->llm->isConfigured() || $chunks === []) {
            return [];
        }

        $vectors = [];

        try {
            foreach (array_chunk($chunks, 32) as $offset => $batch) {
                $batchVectors = $this->llm->embed($batch);

                foreach ($batchVectors as $index => $vector) {
                    $vectors[$offset * 32 + $index] = $vector;
                }
            }
        } catch (\Throwable $exception) {
            Log::warning('chatbot.rag.embeddings_failed', [
                'message' => Str::limit($exception->getMessage(), 160),
            ]);

            return [];
        }

        return $vectors;
    }

    /**
     * @return list<string>
     */
    private function splitLargeBlock(string $block, int $size): array
    {
        $sentences = preg_split('/(?<=[.!?])\s+/u', $block, -1, PREG_SPLIT_NO_EMPTY) ?: [$block];
        $pieces = [];
        $current = '';

        foreach ($sentences as $sentence) {
            if (mb_strlen($sentence) > $size) {
                if ($current !== '') {
                    $pieces[] = trim($current);
                    $current = '';
                }

                foreach (mb_str_split($sentence, $size) as $piece) {
                    $pieces[] = trim($piece);
                }

                continue;
            }

            $candidate = $current === '' ? $sentence : $current.' '.$sentence;

            if (mb_strlen($candidate) <= $size) {
                $current = $candidate;
            } else {
                $pieces[] = trim($current);
                $current = $sentence;
            }
        }

        if ($current !== '') {
            $pieces[] = trim($current);
        }

        return array_values(array_filter($pieces));
    }
}

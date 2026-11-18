<?php

namespace App\Services\Chatbot\Rag;

use App\Models\ChatbotKnowledgeDocument;
use App\Services\Chatbot\LlmClient;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Recuperador RAG híbrido.
 *
 * 1. Filtra el corpus por audiencia (público, voluntario, ONG o admin).
 * 2. Puntúa con BM25 en PHP, sin enviar documentos a terceros.
 * 3. Si hay embeddings configurados, combina similitud coseno con BM25.
 *
 * El corpus se cachea por versión de conocimiento; al reindexar cambia la
 * versión y las entradas viejas dejan de utilizarse.
 */
class RagRetriever
{
    public function __construct(
        private readonly Tokenizer $tokenizer,
        private readonly KnowledgeIndexer $indexer,
        private readonly LlmClient $llm,
    ) {}

    /**
     * @return list<array{
     *     chunk_id: int,
     *     document_id: int,
     *     title: string,
     *     slug: string,
     *     content: string,
     *     score: float,
     *     confidence: float,
     *     tags: list<string>,
     *     source: ?string
     * }>
     */
    public function retrieve(string $query, string $audience = 'public', ?int $limit = null): array
    {
        if (! config('chatbot.rag.enabled', true)) {
            return [];
        }

        $query = $this->tokenizer->normalizar($query);
        if ($query === '') {
            return [];
        }

        $limit = $limit ?? (int) config('chatbot.rag.top_k', 4);
        $version = $this->indexer->version();
        $store = $this->store();

        $cacheKey = sprintf(
            '%s.rag.%d.%s.%s.%d',
            (string) config('chatbot.cache.prefix', 'chatbot'),
            $version,
            $audience,
            hash('sha256', $query),
            $limit
        );

        if (config('chatbot.cache.enabled', true)) {
            $cached = $store->get($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }
        }

        try {
            $chunks = $this->corpusFor($audience);
        } catch (\Throwable $exception) {
            Log::warning('chatbot.rag.corpus_unavailable', [
                'message' => Str::limit($exception->getMessage(), 160),
            ]);

            return [];
        }

        if ($chunks === []) {
            return [];
        }

        $queryTokens = array_values(array_unique($this->tokenizer->tokens($query)));
        if ($queryTokens === []) {
            return [];
        }

        $scores = $this->bm25($chunks, $queryTokens);
        $maxScore = max(array_column($scores, 'bm25') ?: [1]);

        if (config('chatbot.rag.embeddings') && $this->llm->isConfigured()) {
            $scores = $this->combineEmbeddings($query, $scores, $maxScore, $chunks);
        }

        $minConfidence = (float) config('chatbot.rag.min_confidence', 0.2);

        $results = [];
        foreach ($scores as $score) {
            if ($score['confidence'] < $minConfidence) {
                continue;
            }

            $chunk = $chunks[$score['index']];

            $results[] = [
                'chunk_id' => $chunk['chunk_id'],
                'document_id' => $chunk['document_id'],
                'title' => $chunk['title'],
                'slug' => $chunk['slug'],
                'content' => $chunk['content'],
                'score' => round($score['final'], 4),
                'confidence' => round($score['confidence'], 4),
                'tags' => $chunk['tags'],
                'source' => $chunk['source'],
            ];
        }

        usort($results, fn (array $a, array $b) => $b['score'] <=> $a['score']);
        $results = array_slice($results, 0, $limit);

        if (config('chatbot.cache.enabled', true)) {
            $store->put($cacheKey, $results, (int) config('chatbot.rag.cache_ttl', 600));
        }

        return $results;
    }

    /**
     * Corpus visible para una audiencia, cacheado por versión.
     *
     * @return list<array<string, mixed>>
     */
    private function corpusFor(string $audience): array
    {
        $version = $this->indexer->version();
        $key = sprintf(
            '%s.rag.corpus.%d.%s',
            (string) config('chatbot.cache.prefix', 'chatbot'),
            $version,
            $audience
        );

        $store = $this->store();

        $corpus = $store->get($key);
        if (is_array($corpus)) {
            return $corpus;
        }

        $documents = ChatbotKnowledgeDocument::query()
            ->with(['chunks' => fn ($query) => $query->orderBy('position')])
            ->where('is_active', true)
            ->get();

        $corpus = [];
        $max = (int) config('chatbot.rag.max_corpus_chunks', 800);

        foreach ($documents as $document) {
            if (! $document->visiblePara($audience)) {
                continue;
            }

            foreach ($document->chunks as $chunk) {
                if (count($corpus) >= $max) {
                    break 2;
                }

                $content = (string) $chunk->content;
                $terms = $chunk->terms;

                if (! is_array($terms) || $terms === []) {
                    $terms = $this->tokenizer->termFrequencies($content);
                }

                $corpus[] = [
                    'chunk_id' => $chunk->id,
                    'document_id' => $document->id,
                    'title' => (string) $document->title,
                    'slug' => (string) $document->slug,
                    'content' => $content,
                    'terms' => $terms,
                    'length' => max(1, (int) ($chunk->tokens ?: count($this->tokenizer->tokens($content)))),
                    'title_terms' => $this->tokenizer->termFrequencies((string) $document->title),
                    'tag_terms' => $this->tokenizer->termFrequencies(implode(' ', $document->tags ?: [])),
                    'tags' => array_values($document->tags ?: []),
                    'source' => $document->source,
                    'embedding' => is_array($chunk->embedding) ? $chunk->embedding : null,
                ];
            }
        }

        $store->put($key, $corpus, (int) config('chatbot.rag.cache_ttl', 600));

        return $corpus;
    }

    /**
     * @param  list<array<string, mixed>>  $chunks
     * @param  list<string>  $queryTokens
     * @return list<array{index: int, bm25: float, confidence: float, final: float}>
     */
    private function bm25(array $chunks, array $queryTokens): array
    {
        $total = count($chunks);
        $avgLength = max(1, array_sum(array_column($chunks, 'length')) / $total);
        $k1 = 1.5;
        $b = 0.75;

        $documentFrequency = [];
        foreach ($queryTokens as $token) {
            $documentFrequency[$token] = 0;
            foreach ($chunks as $chunk) {
                if (($chunk['terms'][$token] ?? 0) > 0) {
                    $documentFrequency[$token]++;
                }
            }
        }

        $scores = [];

        foreach ($chunks as $index => $chunk) {
            $score = 0.0;
            $matched = 0;

            foreach ($queryTokens as $token) {
                $tf = (int) ($chunk['terms'][$token] ?? 0);
                if ($tf <= 0) {
                    continue;
                }

                $matched++;

                $df = max(1, $documentFrequency[$token]);
                $idf = log(1 + (($total - $df + 0.5) / ($df + 0.5)));
                $lengthNorm = 1 - $b + $b * ((int) $chunk['length'] / $avgLength);
                $score += $idf * (($tf * ($k1 + 1)) / ($tf + $k1 * $lengthNorm));

                if (($chunk['title_terms'][$token] ?? 0) > 0) {
                    $score += $idf * 0.9;
                }

                if (($chunk['tag_terms'][$token] ?? 0) > 0) {
                    $score += $idf * 0.5;
                }
            }

            $scores[] = [
                'index' => $index,
                'bm25' => $score,
                'confidence' => $matched / max(1, count($queryTokens)),
                'final' => $score,
            ];
        }

        return $scores;
    }

    /**
     * @param  list<array{index: int, bm25: float, confidence: float, final: float}>  $scores
     * @param  list<array<string, mixed>>  $chunks
     * @return list<array{index: int, bm25: float, confidence: float, final: float}>
     */
    private function combineEmbeddings(string $query, array $scores, float $maxScore, array $chunks): array
    {
        try {
            $vectors = $this->llm->embed($query);
        } catch (\Throwable) {
            return $scores;
        }

        $queryVector = $vectors[0] ?? null;
        if (! is_array($queryVector) || $queryVector === []) {
            return $scores;
        }

        // Se recalcula sobre las entradas con embedding disponible.
        foreach ($scores as &$score) {
            $chunk = $chunks[$score['index']] ?? null;

            if (! is_array($chunk) || ! is_array($chunk['embedding'] ?? null)) {
                $score['final'] = $score['bm25'];

                continue;
            }

            $cosine = $this->cosine($queryVector, $chunk['embedding']);
            $normalizedBm25 = $maxScore > 0 ? $score['bm25'] / $maxScore : 0;

            $score['final'] = 0.7 * $cosine + 0.3 * $normalizedBm25;
        }
        unset($score);

        return $scores;
    }

    /**
     * @param  list<float>  $a
     * @param  list<float>  $b
     */
    private function cosine(array $a, array $b): float
    {
        $length = min(count($a), count($b));
        if ($length === 0) {
            return 0.0;
        }

        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        for ($i = 0; $i < $length; $i++) {
            $dot += $a[$i] * $b[$i];
            $normA += $a[$i] ** 2;
            $normB += $b[$i] ** 2;
        }

        if ($normA <= 0 || $normB <= 0) {
            return 0.0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }

    private function store(): Repository
    {
        $store = config('chatbot.cache.store');

        return $store ? Cache::store($store) : Cache::store();
    }
}

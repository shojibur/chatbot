<?php

namespace App\Services;

use App\Models\Client;
use App\Models\KnowledgeChunk;
use App\Models\UsageLog;
use Illuminate\Support\Collection;
use Pgvector\Laravel\Distance;
use Pgvector\Laravel\Vector;

class RetrievalService
{
    public function __construct(
        private readonly EmbeddingService $embeddingService,
    ) {}

    /**
     * Search for the most relevant knowledge chunks for a query.
     *
     * @return Collection<int, KnowledgeChunk>
     */
    public function search(Client $client, string $query, ?int $limit = null): Collection
    {
        $limit ??= $client->retrieval_chunk_count ?? 3;
        $embeddingModel = $client->embedding_model ?? 'text-embedding-3-small';

        $queryEmbedding = $this->embeddingService->generate($query, $embeddingModel, $client);
        $vector = new Vector($queryEmbedding);

        $chunks = KnowledgeChunk::query()
            ->where('client_id', $client->id)
            ->whereNotNull('embedding_vector')
            ->nearestNeighbors('embedding_vector', $vector, Distance::Cosine)
            ->take($limit)
            ->get();

        if ($chunks->isNotEmpty()) {
            UsageLog::create([
                'client_id' => $client->id,
                'interaction_type' => 'retrieval',
                'model' => $embeddingModel,
                'prompt_tokens' => 0,
                'completion_tokens' => 0,
                'cached_input_tokens' => 0,
                'total_tokens' => 0,
                'estimated_cost' => 0,
                'request_excerpt' => mb_substr($query, 0, 200),
                'meta' => [
                    'chunks_returned' => $chunks->count(),
                    'chunk_ids' => $chunks->pluck('id')->toArray(),
                ],
            ]);
        }

        return $chunks;
    }

    /**
     * Build a context string from retrieved chunks for the RAG prompt.
     *
     * @param  Collection<int, KnowledgeChunk>  $chunks
     */
    public function buildContext(Collection $chunks): string
    {
        if ($chunks->isEmpty()) {
            return '';
        }

        $chunks->loadMissing('knowledgeSource:id,title');

        return $this->filterRedundantChunks($chunks)
            ->map(fn (KnowledgeChunk $chunk, int $i) => '[Source '.($i + 1).': '.($chunk->knowledgeSource?->title ?? 'Unknown')."]\n".$chunk->content)
            ->implode("\n\n---\n\n");
    }

    /**
     * Reduce repeated or highly overlapping chunks before prompt assembly.
     *
     * @param  Collection<int, KnowledgeChunk>  $chunks
     * @return Collection<int, KnowledgeChunk>
     */
    private function filterRedundantChunks(Collection $chunks): Collection
    {
        $selected = collect();
        $fingerprints = [];

        foreach ($chunks as $chunk) {
            $fingerprint = $this->chunkFingerprint($chunk->content);

            if ($fingerprint === '') {
                continue;
            }

            $isRedundant = collect($fingerprints)->contains(
                fn (string $existing) => $this->areFingerprintsSimilar($existing, $fingerprint)
            );

            if ($isRedundant) {
                continue;
            }

            $selected->push($chunk);
            $fingerprints[] = $fingerprint;
        }

        return $selected->values();
    }

    private function chunkFingerprint(string $content): string
    {
        $normalized = mb_strtolower($content);
        $normalized = preg_replace('/[^\pL\pN\s]/u', ' ', $normalized) ?? '';
        $normalized = preg_replace('/\s+/u', ' ', trim($normalized)) ?? '';

        if ($normalized === '') {
            return '';
        }

        $tokens = collect(explode(' ', $normalized))
            ->filter(fn (string $token) => mb_strlen($token) > 2)
            ->map(fn (string $token) => match ($token) {
                'services' => 'service',
                'offering', 'offerings', 'offers' => 'offer',
                'provides', 'providing', 'provided' => 'provide',
                default => $token,
            })
            ->unique()
            ->sort()
            ->values()
            ->all();

        return implode(' ', $tokens);
    }

    private function areFingerprintsSimilar(string $left, string $right): bool
    {
        if ($left === $right || str_contains($left, $right) || str_contains($right, $left)) {
            return true;
        }

        $leftTokens = array_values(array_filter(explode(' ', $left)));
        $rightTokens = array_values(array_filter(explode(' ', $right)));

        if ($leftTokens === [] || $rightTokens === []) {
            return false;
        }

        $intersection = count(array_intersect($leftTokens, $rightTokens));
        $union = count(array_unique([...$leftTokens, ...$rightTokens]));

        if ($union === 0) {
            return false;
        }

        return ($intersection / $union) >= 0.8;
    }
}

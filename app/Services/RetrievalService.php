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

        return $chunks
            ->map(fn (KnowledgeChunk $chunk, int $i) => '[Source '.($i + 1).': '.($chunk->knowledgeSource?->title ?? 'Unknown')."]\n".$chunk->content)
            ->implode("\n\n---\n\n");
    }
}

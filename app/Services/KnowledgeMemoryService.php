<?php

namespace App\Services;

use App\Models\Client;
use App\Models\KnowledgeChunk;
use App\Models\KnowledgeSource;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Pgvector\Laravel\Vector;

class KnowledgeMemoryService
{
    public function __construct(
        private readonly KnowledgeChunkingService $chunkingService,
        private readonly EmbeddingService $embeddingService,
    ) {}

    /**
     * Create a source payload with a stable dedup hash.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public function makeSourcePayload(
        Client $client,
        array $validated,
        ?UploadedFile $file,
        ?string $uploadedBy,
        ?string $sourceHash = null,
    ): array {
        $sourceType = $validated['source_type'];
        $content = $validated['content'] ?? null;
        $sourceHash ??= $this->resolveSourceHash($validated, $file);
        $supportsImmediateProcessing = $this->supportsImmediateProcessing($sourceType, $file);
        $normalizedContent = $supportsImmediateProcessing ? $this->extractContent($sourceType, $content, $file) : null;
        $filePath = $file?->store('knowledge-sources/'.$client->id, 'local');

        return [
            'client_id' => $client->id,
            'title' => $validated['title'],
            'source_type' => $sourceType,
            'status' => $supportsImmediateProcessing ? 'ready' : 'queued',
            'source_url' => $validated['source_url'] ?? null,
            'source_hash' => $sourceHash,
            'file_name' => $file?->getClientOriginalName(),
            'file_path' => $filePath,
            'mime_type' => $file?->getMimeType(),
            'file_size' => $file?->getSize(),
            'content' => $normalizedContent,
            'token_estimate' => $normalizedContent ? $this->chunkingService->estimateTokens($normalizedContent) : $this->estimateTokensFromFile($file),
            'chunk_count' => $normalizedContent ? count($this->chunkingService->chunk($normalizedContent)) : 0,
            'last_synced_at' => now(),
            'content_extracted_at' => $normalizedContent ? now() : null,
            'processed_at' => $supportsImmediateProcessing ? now() : null,
            'processing_error' => null,
            'processing_meta' => [
                'dedup_hash' => $sourceHash,
                'supports_immediate_processing' => $supportsImmediateProcessing,
            ],
            'meta' => [
                'uploaded_by' => $uploadedBy,
                'ingestion_strategy' => $supportsImmediateProcessing ? 'local-chunking' : 'queued-for-parser',
            ],
        ];
    }

    /**
     * Persist chunks for a source we can process immediately.
     */
    public function syncChunks(KnowledgeSource $source): void
    {
        if (! $source->content) {
            return;
        }

        $chunks = $this->chunkingService->chunk($source->content);
        $client = $source->client;
        $embeddingModel = $client->embedding_model ?? 'text-embedding-3-small';

        // Generate embeddings for all chunks in a single batch API call
        $texts = array_column($chunks, 'content');
        $embeddings = [];

        try {
            $embeddings = $this->embeddingService->generateBatch($texts, $embeddingModel, $client);
        } catch (\Throwable $e) {
            Log::warning('Embedding generation failed, storing chunks without vectors', [
                'source_id' => $source->id,
                'error' => $e->getMessage(),
            ]);
        }

        DB::transaction(function () use ($source, $chunks, $embeddings, $embeddingModel): void {
            $source->knowledgeChunks()->delete();

            foreach ($chunks as $i => $chunk) {
                KnowledgeChunk::create([
                    'client_id' => $source->client_id,
                    'knowledge_source_id' => $source->id,
                    'chunk_index' => $chunk['chunk_index'],
                    'content' => $chunk['content'],
                    'content_hash' => $chunk['content_hash'],
                    'token_estimate' => $chunk['token_estimate'],
                    'character_count' => $chunk['character_count'],
                    'embedding_model' => $embeddingModel,
                    'embedding' => null,
                    'embedding_vector' => isset($embeddings[$i]) ? new Vector($embeddings[$i]) : null,
                    'meta' => [
                        'source_type' => $source->source_type,
                        'chunking_strategy' => 'char-window-overlap',
                        'has_embedding' => isset($embeddings[$i]),
                    ],
                ]);
            }

            $source->forceFill([
                'chunk_count' => count($chunks),
                'token_estimate' => collect($chunks)->sum('token_estimate'),
                'processed_at' => now(),
                'processing_error' => null,
                'processing_meta' => array_merge($source->processing_meta ?? [], [
                    'chunked_at' => now()->toDateTimeString(),
                    'embedded_at' => ! empty($embeddings) ? now()->toDateTimeString() : null,
                    'embedding_model' => $embeddingModel,
                ]),
            ])->save();
        });
    }

    /**
     * Check whether the source already exists for the client.
     */
    public function existingSource(Client $client, string $sourceHash): ?KnowledgeSource
    {
        return KnowledgeSource::query()
            ->where('client_id', $client->id)
            ->where('source_hash', $sourceHash)
            ->first();
    }

    /**
     * Resolve the stable source hash before persisting files or records.
     *
     * @param  array<string, mixed>  $validated
     */
    public function resolveSourceHash(array $validated, ?UploadedFile $file): string
    {
        return $this->sourceHash(
            $validated['source_type'],
            $validated['content'] ?? null,
            $validated['source_url'] ?? null,
            $file,
        );
    }

    /**
     * Determine if we can process the source locally right now.
     */
    private function supportsImmediateProcessing(string $sourceType, ?UploadedFile $file): bool
    {
        if ($sourceType === 'manual') {
            return true;
        }

        return $sourceType === 'file'
            && in_array(Str::lower($file?->getClientOriginalExtension() ?? ''), ['txt'], true);
    }

    /**
     * Extract plain text content for supported source types.
     */
    private function extractContent(string $sourceType, ?string $content, ?UploadedFile $file): ?string
    {
        if ($sourceType === 'manual') {
            return $content ? $this->chunkingService->normalizeText($content) : null;
        }

        if ($sourceType === 'file' && $file?->getRealPath()) {
            $raw = file_get_contents($file->getRealPath());

            return $raw === false ? null : $this->chunkingService->normalizeText($raw);
        }

        return null;
    }

    /**
     * Create a stable hash for source deduplication.
     */
    private function sourceHash(string $sourceType, ?string $content, ?string $url, ?UploadedFile $file): string
    {
        return match ($sourceType) {
            'manual' => hash('sha256', $this->chunkingService->normalizeText($content ?? '')),
            'url' => hash('sha256', Str::lower(trim($url ?? ''))),
            'file' => hash_file('sha256', $file?->getRealPath() ?: ''),
            default => hash('sha256', Str::uuid()->toString()),
        };
    }

    /**
     * Estimate tokens for queued files before parsing.
     */
    private function estimateTokensFromFile(?UploadedFile $file): int
    {
        if (! $file?->getSize()) {
            return 0;
        }

        return max(1, (int) ceil($file->getSize() / 4));
    }
}

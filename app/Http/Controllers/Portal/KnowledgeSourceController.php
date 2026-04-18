<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\StoreKnowledgeSourceRequest;
use App\Jobs\ProcessKnowledgeSource;
use App\Models\KnowledgeSource;
use App\Services\KnowledgeChunkingService;
use App\Services\KnowledgeMemoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class KnowledgeSourceController extends Controller
{
    public function __construct(
        private readonly KnowledgeMemoryService $knowledgeMemoryService,
    ) {}

    /**
     * Store a newly created knowledge source for the logged-in client.
     */
    public function store(StoreKnowledgeSourceRequest $request): RedirectResponse
    {
        $client = $request->user()->client;

        // Enforce plan knowledge-source limit
        if ($client->atKnowledgeSourceLimit()) {
            return to_route('portal.dashboard')
                ->with('status', 'knowledge-source-limit-reached');
        }

        $validated = $request->validated();
        $file = $request->file('source_file');
        $sourceHash = $this->knowledgeMemoryService->resolveSourceHash($validated, $file);

        if ($this->knowledgeMemoryService->existingSource($client, $sourceHash)) {
            return to_route('portal.dashboard')->with('status', 'knowledge-source-duplicate');
        }

        $source = $client->knowledgeSources()->create(
            $this->knowledgeMemoryService->makeSourcePayload(
                $client,
                $validated,
                $file,
                $request->user()?->email,
                $sourceHash,
            ),
        );

        if ($source->content) {
            $source->loadMissing('client');
            $this->knowledgeMemoryService->syncChunks($source);
        } elseif ($source->status === 'queued') {
            ProcessKnowledgeSource::dispatch($source);
        }

        return to_route('portal.dashboard')->with('status', 'knowledge-source-created');
    }

    /**
     * Update a knowledge source for the logged-in client.
     */
    public function update(Request $request, KnowledgeSource $knowledgeSource): RedirectResponse
    {
        $client = $request->user()->client;

        if ($knowledgeSource->client_id !== $client->id) {
            abort(403);
        }

        $rules = [
            'title' => ['required', 'string', 'max:255'],
        ];

        if ($knowledgeSource->source_type === 'manual') {
            $rules['content'] = ['nullable', 'string', 'max:100000'];
        }

        $validated = $request->validate($rules);

        $knowledgeSource->update(['title' => $validated['title']]);

        // If manual source content changed, re-chunk and re-embed
        if ($knowledgeSource->source_type === 'manual' && array_key_exists('content', $validated)) {
            $chunkingService = app(KnowledgeChunkingService::class);
            $normalizedContent = $validated['content']
                ? $chunkingService->normalizeText($validated['content'])
                : null;

            $knowledgeSource->update([
                'content' => $normalizedContent,
                'source_hash' => hash('sha256', $normalizedContent ?? ''),
                'token_estimate' => $normalizedContent ? $chunkingService->estimateTokens($normalizedContent) : 0,
                'chunk_count' => $normalizedContent ? count($chunkingService->chunk($normalizedContent)) : 0,
            ]);

            $knowledgeSource->loadMissing('client');
            $this->knowledgeMemoryService->syncChunks($knowledgeSource);
        }

        return to_route('portal.dashboard')->with('status', 'knowledge-source-updated');
    }

    /**
     * Delete a knowledge source for the logged-in client.
     */
    public function destroy(Request $request, KnowledgeSource $knowledgeSource): RedirectResponse
    {
        $client = $request->user()->client;

        if ($knowledgeSource->client_id !== $client->id) {
            abort(403);
        }

        $knowledgeSource->delete();

        return to_route('portal.dashboard')->with('status', 'knowledge-source-deleted');
    }

    /**
     * Return the extracted chunks for a knowledge source.
     */
    public function chunks(Request $request, KnowledgeSource $knowledgeSource): JsonResponse
    {
        $client = $request->user()->client;

        if ($knowledgeSource->client_id !== $client->id) {
            abort(403);
        }

        $chunks = $knowledgeSource->knowledgeChunks()
            ->orderBy('chunk_index')
            ->get()
            ->map(fn ($chunk) => [
                'id' => $chunk->id,
                'chunk_index' => $chunk->chunk_index,
                'content' => $chunk->content,
                'token_estimate' => $chunk->token_estimate,
                'character_count' => $chunk->character_count,
                'has_embedding' => $chunk->embedding_vector !== null,
                'embedding_model' => $chunk->embedding_model,
            ]);

        return response()->json([
            'source_id' => $knowledgeSource->id,
            'source_title' => $knowledgeSource->title,
            'source_type' => $knowledgeSource->source_type,
            'total_chunks' => $chunks->count(),
            'chunks' => $chunks,
        ]);
    }

    /**
     * Retry processing a failed knowledge source.
     */
    public function retry(Request $request, KnowledgeSource $knowledgeSource): RedirectResponse
    {
        $client = $request->user()->client;

        if ($knowledgeSource->client_id !== $client->id) {
            abort(403);
        }

        $knowledgeSource->update([
            'status' => 'queued',
            'processing_error' => null,
        ]);

        ProcessKnowledgeSource::dispatch($knowledgeSource);

        return to_route('portal.dashboard')->with('status', 'knowledge-source-retrying');
    }
}

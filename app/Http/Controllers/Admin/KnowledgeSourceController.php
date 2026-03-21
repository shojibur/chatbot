<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreKnowledgeSourceRequest;
use App\Jobs\ProcessKnowledgeSource;
use App\Models\Client;
use App\Models\KnowledgeSource;
use App\Services\KnowledgeMemoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class KnowledgeSourceController extends Controller
{
    public function __construct(
        private readonly KnowledgeMemoryService $knowledgeMemoryService,
    ) {}

    /**
     * Store a newly created knowledge source for a client.
     */
    public function store(StoreKnowledgeSourceRequest $request, Client $client): RedirectResponse
    {
        $validated = $request->validated();
        $file = $request->file('source_file');
        $sourceHash = $this->knowledgeMemoryService->resolveSourceHash($validated, $file);

        if ($this->knowledgeMemoryService->existingSource($client, $sourceHash)) {
            return to_route('clients.show', $client)->with('status', 'knowledge-source-duplicate');
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

        return to_route('clients.show', $client)->with('status', 'knowledge-source-created');
    }

    /**
     * Update a knowledge source title.
     */
    public function update(Request $request, Client $client, KnowledgeSource $knowledgeSource): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $knowledgeSource->update(['title' => $validated['title']]);

        return to_route('clients.show', $client)->with('status', 'knowledge-source-updated');
    }

    /**
     * Delete a knowledge source and its chunks.
     */
    public function destroy(Client $client, KnowledgeSource $knowledgeSource): RedirectResponse
    {
        $knowledgeSource->delete();

        return to_route('clients.show', $client)->with('status', 'knowledge-source-deleted');
    }

    /**
     * Retry processing a failed knowledge source.
     */
    public function retry(Client $client, KnowledgeSource $knowledgeSource): RedirectResponse
    {
        $knowledgeSource->update([
            'status' => 'queued',
            'processing_error' => null,
        ]);

        ProcessKnowledgeSource::dispatch($knowledgeSource);

        return to_route('clients.show', $client)->with('status', 'knowledge-source-retrying');
    }
}

<?php

namespace App\Http\Controllers\Mobile;

use App\Jobs\ProcessKnowledgeSource;
use App\Models\KnowledgeSource;
use App\Services\KnowledgeMemoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KnowledgeSourceController extends MobileController
{
    public function __construct(
        private readonly KnowledgeMemoryService $knowledgeMemoryService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $client = $this->currentClient($request);

        $paginator = $client->knowledgeSources()
            ->latest()
            ->paginate(15, ['*'], 'page', $request->integer('page', 1));

        return response()->json([
            'knowledge_sources' => $paginator->map(fn ($source) => $this->transformKnowledgeSource($source)),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'has_more'     => $paginator->hasMorePages(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $client = $this->currentClient($request);
        $maxUploadKb = (($client?->plan?->max_file_upload_mb ?? 10) * 1024);

        if ($client->atKnowledgeSourceLimit()) {
            return response()->json([
                'message' => 'Knowledge source limit reached for this plan.',
            ], 422);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'source_type' => ['required', Rule::in(['manual', 'url', 'file'])],
            'content' => [
                Rule::requiredIf($request->input('source_type') === 'manual'),
                'nullable',
                'string',
                'max:50000',
            ],
            'source_url' => [
                Rule::requiredIf($request->input('source_type') === 'url'),
                'nullable',
                'url:http,https',
                'max:1000',
            ],
            'source_file' => [
                Rule::requiredIf($request->input('source_type') === 'file'),
                'nullable',
                'file',
                'mimes:pdf,txt,doc,docx',
                'max:'.$maxUploadKb,
            ],
        ]);

        $file = $request->file('source_file');
        $sourceHash = $this->knowledgeMemoryService->resolveSourceHash($validated, $file);

        if ($this->knowledgeMemoryService->existingSource($client, $sourceHash)) {
            return response()->json([
                'message' => 'This knowledge source already exists.',
            ], 422);
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
            $source->refresh();
        } elseif ($source->status === 'queued') {
            ProcessKnowledgeSource::dispatch($source);
        }

        return response()->json([
            'knowledge_source' => $this->transformKnowledgeSource($source),
        ], 201);
    }

    public function destroy(Request $request, KnowledgeSource $knowledgeSource): JsonResponse
    {
        $client = $this->currentClient($request);
        abort_unless($knowledgeSource->client_id === $client->id, 403);

        $knowledgeSource->delete();

        return response()->json([
            'message' => 'Knowledge source deleted.',
        ]);
    }

    public function retry(Request $request, KnowledgeSource $knowledgeSource): JsonResponse
    {
        $client = $this->currentClient($request);
        abort_unless($knowledgeSource->client_id === $client->id, 403);

        $knowledgeSource->update([
            'status' => 'queued',
            'processing_error' => null,
        ]);

        ProcessKnowledgeSource::dispatch($knowledgeSource);

        return response()->json([
            'knowledge_source' => $this->transformKnowledgeSource($knowledgeSource->fresh()),
        ]);
    }
}

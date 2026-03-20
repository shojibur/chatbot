<?php

namespace App\Jobs;

use App\Models\KnowledgeSource;
use App\Services\ContentExtractorService;
use App\Services\KnowledgeMemoryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessKnowledgeSource implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public readonly KnowledgeSource $knowledgeSource,
    ) {}

    public function handle(
        ContentExtractorService $extractor,
        KnowledgeMemoryService $memoryService,
    ): void {
        $source = $this->knowledgeSource;

        $source->forceFill(['status' => 'processing'])->save();

        try {
            $content = $this->extractContent($source, $extractor);

            if (! $content || trim($content) === '') {
                throw new \RuntimeException('No text content could be extracted from the source.');
            }

            $source->forceFill([
                'content' => $content,
                'content_extracted_at' => now(),
            ])->save();

            $source->loadMissing('client');
            $memoryService->syncChunks($source);

            $source->forceFill([
                'status' => 'ready',
                'processing_error' => null,
                'processing_meta' => array_merge($source->processing_meta ?? [], [
                    'processed_via' => 'queue',
                    'completed_at' => now()->toDateTimeString(),
                ]),
            ])->save();

        } catch (\Throwable $e) {
            Log::error('Knowledge source processing failed', [
                'source_id' => $source->id,
                'error' => $e->getMessage(),
            ]);

            $source->forceFill([
                'status' => 'failed',
                'processing_error' => mb_substr($e->getMessage(), 0, 1000),
                'processing_meta' => array_merge($source->processing_meta ?? [], [
                    'failed_at' => now()->toDateTimeString(),
                    'attempt' => $this->attempts(),
                ]),
            ])->save();

            if ($this->attempts() >= $this->tries) {
                return;
            }

            throw $e;
        }
    }

    private function extractContent(KnowledgeSource $source, ContentExtractorService $extractor): string
    {
        return match ($source->source_type) {
            'url' => $extractor->extractFromUrl($source->source_url),
            'file' => $extractor->extractFromFile(
                Storage::disk('local')->path($source->file_path),
                $source->mime_type ?? 'text/plain',
            ),
            default => throw new \RuntimeException("Cannot process source type: {$source->source_type}"),
        };
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Spatie\PdfToText\Pdf;

class ContentExtractorService
{
    public function __construct(
        private readonly KnowledgeChunkingService $chunkingService,
    ) {}

    /**
     * Extract plain text from a PDF file.
     */
    public function extractFromPdf(string $path): string
    {
        $text = Pdf::getText($path);

        return $this->chunkingService->normalizeText($text);
    }

    /**
     * Extract plain text from a URL by fetching HTML and stripping tags.
     */
    public function extractFromUrl(string $url): string
    {
        $response = Http::timeout(30)
            ->withHeaders(['User-Agent' => 'DaveyChatbot/1.0'])
            ->get($url);

        if (! $response->successful()) {
            throw new \RuntimeException("Failed to fetch URL: {$url} (HTTP {$response->status()})");
        }

        $html = $response->body();

        // Remove script, style, nav, header, footer tags and their contents
        $html = preg_replace('/<(script|style|nav|header|footer)[^>]*>.*?<\/\1>/si', '', $html);

        // Remove all remaining HTML tags
        $text = strip_tags($html);

        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return $this->chunkingService->normalizeText($text);
    }

    /**
     * Extract plain text from a local file based on its MIME type.
     */
    public function extractFromFile(string $path, string $mime): string
    {
        return match (true) {
            str_contains($mime, 'pdf') => $this->extractFromPdf($path),
            str_starts_with($mime, 'text/') => $this->chunkingService->normalizeText(file_get_contents($path) ?: ''),
            default => throw new \RuntimeException("Unsupported file type: {$mime}"),
        };
    }
}

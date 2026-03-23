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
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.9',
                'Accept-Encoding' => 'gzip, deflate, br',
                'Connection' => 'keep-alive',
                'Upgrade-Insecure-Requests' => '1',
                'Cache-Control' => 'max-age=0',
            ])
            ->withOptions([
                'allow_redirects' => [
                    'max' => 5,
                    'strict' => false,
                    'referer' => true,
                    'track_redirects' => true,
                ],
                'verify' => true,
            ])
            ->get($url);

        if (! $response->successful()) {
            throw new \RuntimeException("Failed to fetch URL: {$url} (HTTP {$response->status()})");
        }

        $html = $response->body();

        return $this->extractTextFromHtml($html);
    }

    /**
     * Parse HTML and extract meaningful text content.
     */
    private function extractTextFromHtml(string $html): string
    {
        // Remove script, style, nav, header, footer, aside, form tags and their contents
        $html = preg_replace('/<(script|style|nav|header|footer|aside|form|svg|noscript)[^>]*>.*?<\/\1>/si', '', $html);

        // Remove HTML comments
        $html = preg_replace('/<!--.*?-->/s', '', $html);

        // Try to extract only the main content area if it exists
        if (preg_match('/<(main|article)[^>]*>(.*?)<\/\1>/si', $html, $matches)) {
            $html = $matches[2];
        }

        // Convert block-level elements to newlines for readability
        $html = preg_replace('/<\/(p|div|h[1-6]|li|tr|blockquote|section)>/i', "\n", $html);
        $html = preg_replace('/<br\s*\/?>/i', "\n", $html);

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

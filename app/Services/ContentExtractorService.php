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
        // Re-validate the resolved IP right before the fetch to prevent DNS rebinding attacks.
        // (The URL was already validated at request time, but DNS can change between then and now.)
        $this->validateUrlIsPublic($url);

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

    /**
     * Resolve the URL's hostname and confirm it points to a public IP.
     *
     * Called immediately before every HTTP fetch so DNS rebinding attacks
     * (a domain that resolves to 1.2.3.4 at validation time but to 10.x.x.x
     * by the time the queued job runs) are caught at both stages.
     *
     * @throws \RuntimeException if the hostname resolves to a private/reserved address.
     */
    private function validateUrlIsPublic(string $url): void
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (! $host) {
            throw new \RuntimeException("Could not parse host from URL: {$url}");
        }

        $ip = gethostbyname($host);

        // gethostbyname() returns the original string when resolution fails
        if ($ip === $host) {
            throw new \RuntimeException("Could not resolve hostname: {$host}");
        }

        $isPrivate = filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;

        // Also block link-local (169.254.x.x — cloud metadata endpoints) and
        // CGNAT (100.64.x.x) which FILTER_FLAG_NO_RES_RANGE does not cover.
        $isLinkLocal = str_starts_with($ip, '169.254.');
        $isCgnat     = (function () use ($ip): bool {
            $packed = ip2long($ip);
            return $packed !== false
                && $packed >= ip2long('100.64.0.0')
                && $packed <= ip2long('100.127.255.255');
        })();

        if ($isPrivate || $isLinkLocal || $isCgnat) {
            throw new \RuntimeException(
                "URL '{$url}' resolves to a restricted internal address ({$ip}) and cannot be fetched."
            );
        }
    }
}


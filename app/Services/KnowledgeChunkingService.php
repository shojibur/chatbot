<?php

namespace App\Services;

use Illuminate\Support\Str;

class KnowledgeChunkingService
{
    /**
     * Split text into overlapping chunks sized for later retrieval.
     *
     * @return array<int, array<string, int|string>>
     */
    public function chunk(string $text, int $chunkSize = 1400, int $overlap = 220): array
    {
        $normalized = $this->normalizeText($text);

        if ($normalized === '') {
            return [];
        }

        $length = Str::length($normalized);

        // If text fits in a single chunk, return it directly
        if ($length <= $chunkSize) {
            return [[
                'chunk_index' => 0,
                'content' => $normalized,
                'content_hash' => hash('sha256', $normalized),
                'character_count' => strlen($normalized),
                'token_estimate' => $this->estimateTokens($normalized),
            ]];
        }

        $chunks = [];
        $offset = 0;
        $index = 0;
        $step = $chunkSize - $overlap;

        while ($offset < $length) {
            $window = Str::substr($normalized, $offset, $chunkSize);

            if ($offset + $chunkSize < $length) {
                $lastWhitespace = max(
                    strrpos($window, ' ') ?: 0,
                    strrpos($window, "\n") ?: 0,
                );

                if ($lastWhitespace > (int) ($chunkSize * 0.6)) {
                    $window = substr($window, 0, $lastWhitespace);
                }
            }

            $window = trim($window);

            if ($window === '') {
                break;
            }

            $chunks[] = [
                'chunk_index' => $index,
                'content' => $window,
                'content_hash' => hash('sha256', $window),
                'character_count' => strlen($window),
                'token_estimate' => $this->estimateTokens($window),
            ];

            $offset += max($step, strlen($window) - $overlap);
            $index++;
        }

        return $chunks;
    }

    /**
     * Normalize source content before chunking.
     */
    public function normalizeText(string $text): string
    {
        return Str::of($text)
            ->replace(["\r\n", "\r"], "\n")
            ->replaceMatches('/[ \t]+/', ' ')
            ->replaceMatches("/\n{3,}/", "\n\n")
            ->trim()
            ->value();
    }

    /**
     * Rough token estimate without an embedding API call.
     */
    public function estimateTokens(string $text): int
    {
        return max(1, (int) ceil(Str::length($text) / 4));
    }
}

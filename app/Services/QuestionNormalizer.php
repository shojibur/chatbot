<?php

namespace App\Services;

use Illuminate\Support\Str;

class QuestionNormalizer
{
    /**
     * Normalize a visitor question for exact cache matching.
     */
    public function normalize(string $question): string
    {
        $question = Str::of($question)
            ->lower()
            ->replaceMatches("/\bu\b/u", ' you ')
            ->replaceMatches("/\bur\b/u", ' your ')
            ->replaceMatches('/[^\pL\pN\s]/u', ' ')
            ->squish()
            ->value();

        $intent = $this->detectCanonicalIntent($question);

        if ($intent !== null) {
            return $intent;
        }

        $stopWords = [
            'a', 'an', 'any', 'are', 'can', 'could', 'do', 'for', 'give', 'i',
            'is', 'list', 'me', 'of', 'please', 'provide', 'show', 'tell',
            'the', 'to', 'u', 'us', 'what', 'you', 'your',
        ];

        $tokens = collect(explode(' ', $question))
            ->map(fn (string $token) => trim($token))
            ->filter()
            ->reject(fn (string $token) => in_array($token, $stopWords, true))
            ->map(function (string $token): string {
                return match ($token) {
                    'services' => 'service',
                    'offering', 'offerings', 'offers', 'provided', 'provides', 'providing' => 'provide',
                    default => $token,
                };
            })
            ->values()
            ->all();

        if ($tokens === []) {
            return Str::limit($question, 2000, '');
        }

        sort($tokens);

        return Str::limit(implode(' ', $tokens), 2000, '');
    }

    /**
     * Generate a stable cache key for a question + model + prompt scope.
     */
    public function cacheKey(string $normalizedQuestion, string $chatModel, string $promptHash): string
    {
        return hash('sha256', $normalizedQuestion.'::'.$chatModel.'::'.$promptHash);
    }

    private function detectCanonicalIntent(string $question): ?string
    {
        $serviceSignals = [
            'service',
            'services',
            'offer',
            'offers',
            'offering',
            'offerings',
            'provide',
            'provides',
        ];

        $listSignals = [
            'list',
            'what',
            'show',
            'tell',
            'please',
        ];

        $hasServiceSignal = collect($serviceSignals)->contains(
            fn (string $signal) => str_contains($question, $signal)
        );

        $hasListSignal = collect($listSignals)->contains(
            fn (string $signal) => str_contains($question, $signal)
        );

        if ($hasServiceSignal && $hasListSignal) {
            return 'intent_service_list';
        }

        return null;
    }
}

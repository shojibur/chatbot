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
            ->replaceMatches('/[^\pL\pN\s]/u', ' ')
            ->squish()
            ->value();

        return Str::limit($question, 2000, '');
    }

    /**
     * Generate a stable cache key for a question + model + prompt scope.
     */
    public function cacheKey(string $normalizedQuestion, string $chatModel, string $promptHash): string
    {
        return hash('sha256', $normalizedQuestion.'::'.$chatModel.'::'.$promptHash);
    }
}

<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ConversationCache;

class ConversationCacheService
{
    public function __construct(
        private readonly QuestionNormalizer $questionNormalizer,
    ) {
    }

    /**
     * Look up an exact persistent cache entry for a client question.
     */
    public function find(Client $client, string $question, string $chatModel, string $promptHash): ?ConversationCache
    {
        $normalizedQuestion = $this->questionNormalizer->normalize($question);
        $cacheKey = $this->questionNormalizer->cacheKey($normalizedQuestion, $chatModel, $promptHash);

        return ConversationCache::query()
            ->where('client_id', $client->id)
            ->where('cache_key', $cacheKey)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();
    }

    /**
     * Store or update a persistent answer cache entry.
     */
    public function remember(
        Client $client,
        string $question,
        string $answer,
        string $contextExcerpt,
        string $chatModel,
        string $embeddingModel,
        string $promptHash,
        ?int $ttlHours = null,
    ): ConversationCache {
        $normalizedQuestion = $this->questionNormalizer->normalize($question);
        $cacheKey = $this->questionNormalizer->cacheKey($normalizedQuestion, $chatModel, $promptHash);

        return ConversationCache::query()->updateOrCreate(
            [
                'client_id' => $client->id,
                'cache_key' => $cacheKey,
            ],
            [
                'normalized_question' => $normalizedQuestion,
                'question' => $question,
                'answer' => $answer,
                'context_excerpt' => $contextExcerpt,
                'question_hash' => hash('sha256', $normalizedQuestion),
                'answer_hash' => hash('sha256', $answer),
                'last_hit_at' => now(),
                'expires_at' => $ttlHours ? now()->addHours($ttlHours) : null,
                'chat_model' => $chatModel,
                'embedding_model' => $embeddingModel,
                'question_embedding' => null,
                'meta' => [
                    'prompt_hash' => $promptHash,
                    'strategy' => 'exact',
                ],
            ],
        );
    }

    /**
     * Increment usage counters when a cache entry is reused.
     */
    public function markHit(
        ConversationCache $cache,
        int $promptTokensSaved = 0,
        int $completionTokensSaved = 0,
    ): void {
        $cache->incrementEach([
            'hit_count' => 1,
            'prompt_tokens_saved' => $promptTokensSaved,
            'completion_tokens_saved' => $completionTokensSaved,
            'total_tokens_saved' => $promptTokensSaved + $completionTokensSaved,
        ]);

        $cache->forceFill([
            'last_hit_at' => now(),
        ])->save();
    }
}

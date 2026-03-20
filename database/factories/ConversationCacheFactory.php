<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\ConversationCache;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConversationCache>
 */
class ConversationCacheFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<ConversationCache>
     */
    protected $model = ConversationCache::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $question = fake()->sentence();
        $answer = fake()->paragraph();
        $normalizedQuestion = str($question)->lower()->squish()->value();

        return [
            'client_id' => Client::factory(),
            'cache_key' => hash('sha256', $normalizedQuestion.'::gpt-4o-mini'),
            'normalized_question' => $normalizedQuestion,
            'question' => $question,
            'answer' => $answer,
            'context_excerpt' => fake()->sentence(),
            'question_hash' => hash('sha256', $normalizedQuestion),
            'answer_hash' => hash('sha256', $answer),
            'hit_count' => fake()->numberBetween(0, 50),
            'prompt_tokens_saved' => fake()->numberBetween(0, 2000),
            'completion_tokens_saved' => fake()->numberBetween(0, 300),
            'total_tokens_saved' => fake()->numberBetween(0, 2300),
            'last_hit_at' => now(),
            'expires_at' => now()->addDay(),
            'chat_model' => 'gpt-4o-mini',
            'embedding_model' => 'text-embedding-3-small',
            'question_embedding' => null,
            'meta' => [
                'strategy' => 'exact',
            ],
        ];
    }
}

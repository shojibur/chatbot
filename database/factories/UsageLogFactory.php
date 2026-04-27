<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\UsageLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UsageLog>
 */
class UsageLogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<UsageLog>
     */
    protected $model = UsageLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $promptTokens = fake()->numberBetween(100, 2500);
        $completionTokens = fake()->numberBetween(40, 900);
        $cachedTokens = fake()->numberBetween(0, 600);

        return [
            'client_id' => Client::factory(),
            'interaction_type' => fake()->randomElement(UsageLog::INTERACTION_TYPES),
            'model' => fake()->randomElement(['gpt-4o-mini', 'text-embedding-3-small']),
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'cached_input_tokens' => $cachedTokens,
            'total_tokens' => $promptTokens + $completionTokens,
            'estimated_cost' => fake()->randomFloat(8, 0.0001, 0.05),
            'request_excerpt' => fake()->sentence(),
            'meta' => [
                'cache_hit' => fake()->boolean(),
            ],
        ];
    }
}

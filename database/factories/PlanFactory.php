<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Plan>
     */
    protected $model = Plan::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->randomElement(['Free', 'Pro', 'Ultra Pro', fake()->words(2, true)]);

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(100, 999),
            'description' => fake()->sentence(),
            'price_monthly' => fake()->randomFloat(2, 0, 199),
            'monthly_token_limit' => fake()->numberBetween(100000, 5000000),
            'monthly_message_limit' => fake()->optional()->numberBetween(100, 10000),
            'max_knowledge_sources' => fake()->numberBetween(5, 100),
            'max_file_upload_mb' => fake()->numberBetween(5, 100),
            'features' => [
                fake()->words(3, true),
                fake()->words(3, true),
            ],
            'is_active' => true,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Client>
     */
    protected $model = Client::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'plan_id' => Plan::factory(),
            'name' => fake()->company(),
            'unique_code' => (string) Str::uuid(),
            'contact_email' => fake()->companyEmail(),
            'website_url' => fake()->url(),
            'business_description' => fake()->sentence(12),
            'system_prompt' => 'Answer only from the provided knowledge base. If unsure, say you do not know.',
            'chat_model' => fake()->randomElement(Client::CHAT_MODELS),
            'embedding_model' => fake()->randomElement(Client::EMBEDDING_MODELS),
            'retrieval_chunk_count' => fake()->numberBetween(3, 5),
            'cache_ttl_hours' => fake()->numberBetween(12, 72),
            'prompt_caching_enabled' => true,
            'semantic_cache_enabled' => true,
            'allowed_domains' => [fake()->domainName()],
            'monthly_token_limit' => fake()->numberBetween(100000, 1500000),
            'status' => fake()->randomElement(Client::STATUSES),
            'widget_style' => fake()->randomElement(Client::WIDGET_STYLES),
            'widget_settings' => [
                'primary_color' => '#111827',
                'accent_color' => '#0f766e',
                'welcome_message' => 'Ask us anything about our services.',
                'position' => fake()->randomElement(Client::WIDGET_POSITIONS),
                'show_branding' => fake()->boolean(80),
            ],
            'notes' => fake()->optional()->sentence(),
        ];
    }
}

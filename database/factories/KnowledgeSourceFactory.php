<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\KnowledgeSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KnowledgeSource>
 */
class KnowledgeSourceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<KnowledgeSource>
     */
    protected $model = KnowledgeSource::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'title' => fake()->sentence(3),
            'source_type' => fake()->randomElement(KnowledgeSource::SOURCE_TYPES),
            'status' => fake()->randomElement(KnowledgeSource::STATUSES),
            'source_url' => fake()->optional()->url(),
            'source_hash' => fake()->sha256(),
            'file_name' => fake()->optional()->slug().'.pdf',
            'file_path' => fake()->optional()->filePath(),
            'mime_type' => fake()->optional()->mimeType(),
            'file_size' => fake()->optional()->numberBetween(1024, 1048576),
            'content' => fake()->optional()->paragraphs(3, true),
            'token_estimate' => fake()->numberBetween(50, 5000),
            'chunk_count' => fake()->numberBetween(1, 25),
            'last_synced_at' => fake()->optional()->dateTime(),
            'content_extracted_at' => fake()->optional()->dateTime(),
            'processed_at' => fake()->optional()->dateTime(),
            'processing_error' => null,
            'processing_meta' => [
                'source_label' => fake()->word(),
            ],
            'meta' => [
                'source_label' => fake()->word(),
            ],
        ];
    }
}

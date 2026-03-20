<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\KnowledgeChunk;
use App\Models\KnowledgeSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KnowledgeChunk>
 */
class KnowledgeChunkFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<KnowledgeChunk>
     */
    protected $model = KnowledgeChunk::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $content = fake()->paragraphs(2, true);

        return [
            'client_id' => Client::factory(),
            'knowledge_source_id' => KnowledgeSource::factory(),
            'chunk_index' => fake()->numberBetween(0, 20),
            'content' => $content,
            'content_hash' => hash('sha256', $content),
            'token_estimate' => max(1, (int) ceil(strlen($content) / 4)),
            'character_count' => strlen($content),
            'embedding_model' => 'text-embedding-3-small',
            'embedding' => null,
            'meta' => [
                'window' => 'default',
            ],
        ];
    }
}

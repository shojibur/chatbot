<?php

namespace App\Services;

use App\Models\Client;
use App\Models\UsageLog;
use OpenAI\Laravel\Facades\OpenAI;

class EmbeddingService
{
    /**
     * Generate an embedding vector for a single text.
     *
     * @return array<int, float>
     */
    public function generate(string $text, string $model = 'text-embedding-3-small', ?Client $client = null): array
    {
        $response = OpenAI::embeddings()->create([
            'model' => $model,
            'input' => $text,
        ]);

        $usage = $response->usage;

        if ($client) {
            $this->logUsage($client, $model, $usage->promptTokens ?? 0, $text);
        }

        return $response->embeddings[0]->embedding;
    }

    /**
     * Generate embeddings for multiple texts in a single API call.
     *
     * @param  array<int, string>  $texts
     * @return array<int, array<int, float>>
     */
    public function generateBatch(array $texts, string $model = 'text-embedding-3-small', ?Client $client = null): array
    {
        if (empty($texts)) {
            return [];
        }

        $response = OpenAI::embeddings()->create([
            'model' => $model,
            'input' => $texts,
        ]);

        $usage = $response->usage;

        if ($client) {
            $this->logUsage($client, $model, $usage->promptTokens ?? 0, 'batch:'.count($texts).' chunks');
        }

        $embeddings = [];
        foreach ($response->embeddings as $embedding) {
            $embeddings[$embedding->index] = $embedding->embedding;
        }

        ksort($embeddings);

        return array_values($embeddings);
    }

    private function logUsage(Client $client, string $model, int $promptTokens, string $excerpt): void
    {
        UsageLog::create([
            'client_id' => $client->id,
            'interaction_type' => 'embedding',
            'model' => $model,
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => 0,
            'cached_input_tokens' => 0,
            'total_tokens' => $promptTokens,
            'estimated_cost' => $this->estimateCost($model, $promptTokens),
            'request_excerpt' => mb_substr($excerpt, 0, 200),
            'meta' => ['service' => 'embedding'],
        ]);
    }

    private function estimateCost(string $model, int $tokens): float
    {
        $costPerMillionTokens = match ($model) {
            'text-embedding-3-small' => 0.02,
            'text-embedding-3-large' => 0.13,
            default => 0.02,
        };

        return round(($tokens / 1_000_000) * $costPerMillionTokens, 6);
    }
}

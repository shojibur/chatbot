<?php

namespace App\Services;

use App\Models\Client;
use App\Models\UsageLog;

class EmbeddingService
{
    public function __construct(
        private readonly AiClientFactory $aiClientFactory,
        private readonly AiModelCatalog $modelCatalog,
    ) {}

    /**
     * Generate an embedding vector for a single text.
     *
     * @return array<int, float>
     */
    public function generate(string $text, string $model = 'text-embedding-3-small', ?Client $client = null): array
    {
        $model = $this->modelCatalog->embeddingModel($model);

        $response = $this->aiClientFactory->make()->embeddings()->create([
            'model' => $model,
            'input' => $text,
        ]);

        $usage = $response->usage;
        $usageArray = $response->toArray()['usage'] ?? [];

        if ($client) {
            $this->logUsage($client, $model, $usage->promptTokens ?? 0, $text, $usageArray);
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

        $model = $this->modelCatalog->embeddingModel($model);

        $response = $this->aiClientFactory->make()->embeddings()->create([
            'model' => $model,
            'input' => $texts,
        ]);

        $usage = $response->usage;
        $usageArray = $response->toArray()['usage'] ?? [];

        if ($client) {
            $this->logUsage($client, $model, $usage->promptTokens ?? 0, 'batch:'.count($texts).' chunks', $usageArray);
        }

        $embeddings = [];
        foreach ($response->embeddings as $embedding) {
            $embeddings[$embedding->index] = $embedding->embedding;
        }

        ksort($embeddings);

        return array_values($embeddings);
    }

    private function logUsage(Client $client, string $model, int $promptTokens, string $excerpt, array $usage = []): void
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
            'meta' => [
                'service' => 'embedding',
                'provider' => $this->modelCatalog->provider(),
                'cost_source' => 'estimate',
                'usage_details' => $usage,
            ],
        ]);
    }

    private function estimateCost(string $model, int $tokens): float
    {
        return $this->modelCatalog->estimateEmbeddingCost($model, $tokens);
    }
}

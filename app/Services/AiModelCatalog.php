<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class AiModelCatalog
{
    public function provider(): string
    {
        return (string) config('ai.provider', 'openai');
    }

    /**
     * @return list<string>
     */
    public function chatSuggestions(): array
    {
        return array_values(array_unique(array_filter(
            (array) config('ai.models.chat_suggestions', ['gpt-4o-mini', 'gpt-4o']),
            fn ($model) => is_string($model) && trim($model) !== '',
        )));
    }

    /**
     * @return list<string>
     */
    public function embeddingSuggestions(): array
    {
        return array_values(array_unique(array_filter(
            (array) config('ai.models.embedding_suggestions', ['text-embedding-3-small']),
            fn ($model) => is_string($model) && trim($model) !== '',
        )));
    }

    public function defaultChatModel(): string
    {
        $default = (string) config('ai.models.default_chat', 'gpt-4o-mini');

        return $this->normalizeModel($default);
    }

    public function defaultEmbeddingModel(): string
    {
        $default = (string) config('ai.models.default_embedding', 'text-embedding-3-small');

        return $this->normalizeModel($default);
    }

    public function intentClassifierModel(): string
    {
        $model = (string) config('ai.models.intent_classifier', $this->defaultChatModel());

        return $this->normalizeModel($model);
    }

    public function chatModel(?string $model): string
    {
        return $this->normalizeModel($model ?: $this->defaultChatModel());
    }

    public function embeddingModel(?string $model): string
    {
        return $this->normalizeModel($model ?: $this->defaultEmbeddingModel());
    }

    public function estimateChatCost(string $model, int $promptTokens, int $completionTokens): float
    {
        $model = $this->normalizeModel($model);
        $pricing = config('ai.pricing.chat.'.$model);

        if (! is_array($pricing) && $this->provider() === 'openrouter') {
            $pricing = $this->openRouterPricing($model);
        }

        if (! is_array($pricing)) {
            return 0.0;
        }

        $input = (float) ($pricing['input'] ?? 0);
        $output = (float) ($pricing['output'] ?? 0);
        $request = (float) ($pricing['request'] ?? 0);

        return round(
            ($promptTokens * $input) + ($completionTokens * $output) + $request,
            6
        );
    }

    public function estimateChatCostWithCache(string $model, int $promptTokens, int $completionTokens, int $cachedTokens = 0): float
    {
        $model = $this->normalizeModel($model);
        $pricing = config('ai.pricing.chat.'.$model);

        if (! is_array($pricing) && $this->provider() === 'openrouter') {
            $pricing = $this->openRouterPricing($model);
        }

        if (! is_array($pricing)) {
            return $this->estimateChatCost($model, $promptTokens, $completionTokens);
        }

        $promptRate = (float) ($pricing['input'] ?? 0);
        $completionRate = (float) ($pricing['output'] ?? 0);
        $requestRate = (float) ($pricing['request'] ?? 0);
        $cacheReadRate = array_key_exists('input_cache_read', $pricing)
            ? (float) ($pricing['input_cache_read'] ?? 0)
            : $promptRate;

        $cachedTokens = max(0, $cachedTokens);
        $billablePromptTokens = max(0, $promptTokens - $cachedTokens);

        return round(
            ($billablePromptTokens * $promptRate)
            + ($cachedTokens * $cacheReadRate)
            + ($completionTokens * $completionRate)
            + $requestRate,
            6
        );
    }

    public function estimateEmbeddingCost(string $model, int $tokens): float
    {
        $model = $this->normalizeModel($model);
        $costPerToken = (float) config('ai.pricing.embedding.'.$model, 0);
        $requestCost = 0.0;

        if ($costPerToken === 0.0 && $this->provider() === 'openrouter') {
            $pricing = $this->openRouterPricing($model);

            if (is_array($pricing)) {
                $costPerToken = (float) ($pricing['input'] ?? 0);
                $requestCost = (float) ($pricing['request'] ?? 0);
            }
        }

        return round(($tokens * $costPerToken) + $requestCost, 6);
    }

    private function normalizeModel(string $model): string
    {
        $model = trim($model);

        if ($model === '' || $this->provider() !== 'openrouter' || str_contains($model, '/')) {
            return $model;
        }

        return match ($model) {
            'gpt-4o-mini' => 'openai/gpt-4o-mini',
            'gpt-4o' => 'openai/gpt-4o',
            'text-embedding-3-small' => 'openai/text-embedding-3-small',
            default => $model,
        };
    }

    /**
     * @return array{input: float, output: float, request: float, input_cache_read?: float}|null
     */
    private function openRouterPricing(string $model): ?array
    {
        if ($model === '') {
            return null;
        }

        return Cache::remember("openrouter-pricing:{$model}", now()->addHours(12), function () use ($model): ?array {
            $response = Http::timeout((int) config('ai.request_timeout', 30))
                ->acceptJson()
                ->get(rtrim((string) config('ai.openrouter.base_uri', 'https://openrouter.ai/api/v1'), '/').'/models');

            if (! $response->successful()) {
                return null;
            }

            $models = $response->json('data', []);

            if (! is_array($models)) {
                return null;
            }

            foreach ($models as $candidate) {
                if (! is_array($candidate) || ($candidate['id'] ?? null) !== $model) {
                    continue;
                }

                $pricing = is_array($candidate['pricing'] ?? null) ? $candidate['pricing'] : [];

                return [
                    'input' => (float) ($pricing['prompt'] ?? 0),
                    'output' => (float) ($pricing['completion'] ?? 0),
                    'request' => (float) ($pricing['request'] ?? 0),
                    'input_cache_read' => (float) ($pricing['input_cache_read'] ?? 0),
                ];
            }

            return null;
        });
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ChatRequest;
use App\Models\Client;
use App\Models\UsageLog;
use App\Services\ConversationCacheService;
use App\Services\RetrievalService;
use Illuminate\Http\JsonResponse;
use OpenAI\Laravel\Facades\OpenAI;

class ChatController extends Controller
{
    public function __construct(
        private readonly RetrievalService $retrievalService,
        private readonly ConversationCacheService $cacheService,
    ) {}

    public function chat(ChatRequest $request): JsonResponse
    {
        $client = Client::where('unique_code', $request->input('client_code'))
            ->where('status', 'active')
            ->first();

        if (! $client) {
            return response()->json(['error' => 'Client not found or inactive.'], 404);
        }

        // Check monthly token limit
        $monthlyUsage = $this->currentMonthTokens($client);
        if ($monthlyUsage >= $client->monthly_token_limit) {
            return response()->json(['error' => 'Monthly usage limit reached.'], 429);
        }

        $message = $request->input('message');
        $chatModel = $client->chat_model ?? 'gpt-4o-mini';
        $promptHash = hash('sha256', $client->system_prompt ?? '');

        // Check cache first
        if ($client->semantic_cache_enabled) {
            $cached = $this->cacheService->find($client, $message, $chatModel, $promptHash);

            if ($cached) {
                $this->cacheService->markHit($cached, $cached->prompt_tokens_saved, $cached->completion_tokens_saved);

                UsageLog::create([
                    'client_id' => $client->id,
                    'interaction_type' => 'cache_hit',
                    'model' => $chatModel,
                    'prompt_tokens' => 0,
                    'completion_tokens' => 0,
                    'cached_input_tokens' => 0,
                    'total_tokens' => 0,
                    'estimated_cost' => 0,
                    'request_excerpt' => mb_substr($message, 0, 200),
                    'meta' => ['cache_id' => $cached->id],
                ]);

                return response()->json([
                    'answer' => $cached->answer,
                    'cached' => true,
                ]);
            }
        }

        // Retrieve relevant chunks via vector search
        $chunks = $this->retrievalService->search($client, $message);
        $context = $this->retrievalService->buildContext($chunks);

        // Build the RAG prompt
        $systemPrompt = $this->buildSystemPrompt($client, $context);

        $response = OpenAI::chat()->create([
            'model' => $chatModel,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $message],
            ],
            'max_tokens' => 1024,
            'temperature' => 0.7,
        ]);

        $answer = $response->choices[0]->message->content ?? '';
        $usage = $response->usage;
        $promptTokens = $usage->promptTokens ?? 0;
        $completionTokens = $usage->completionTokens ?? 0;
        $totalTokens = $promptTokens + $completionTokens;

        // Log usage
        UsageLog::create([
            'client_id' => $client->id,
            'interaction_type' => 'chat',
            'model' => $chatModel,
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'cached_input_tokens' => $usage->promptTokensCached ?? 0,
            'total_tokens' => $totalTokens,
            'estimated_cost' => $this->estimateChatCost($chatModel, $promptTokens, $completionTokens),
            'request_excerpt' => mb_substr($message, 0, 200),
            'meta' => [
                'chunks_used' => $chunks->count(),
                'context_length' => mb_strlen($context),
            ],
        ]);

        // Cache the response
        if ($client->semantic_cache_enabled) {
            $cache = $this->cacheService->remember(
                $client,
                $message,
                $answer,
                mb_substr($context, 0, 500),
                $chatModel,
                $client->embedding_model ?? 'text-embedding-3-small',
                $promptHash,
                $client->cache_ttl_hours,
            );

            // Store token counts so future cache hits know how much was saved
            $cache->forceFill([
                'prompt_tokens_saved' => $promptTokens,
                'completion_tokens_saved' => $completionTokens,
            ])->save();
        }

        return response()->json([
            'answer' => $answer,
            'cached' => false,
        ]);
    }

    /**
     * Return widget configuration for a client (public endpoint).
     */
    public function widgetConfig(string $clientCode): JsonResponse
    {
        $client = Client::where('unique_code', $clientCode)
            ->where('status', 'active')
            ->first();

        if (! $client) {
            return response()->json(['error' => 'Client not found.'], 404);
        }

        return response()->json([
            'name' => $client->name,
            'widget_style' => $client->widget_style,
            'widget_settings' => $client->widget_settings,
            'welcome_message' => $client->widget_settings['welcome_message'] ?? 'Hi! How can I help you?',
        ]);
    }

    private function buildSystemPrompt(Client $client, string $context): string
    {
        $base = $client->system_prompt ?: 'You are a helpful assistant for '.$client->name.'. Answer questions based only on the provided context. If you don\'t know the answer, say so.';

        if (! $context) {
            return $base."\n\nNo relevant knowledge base content was found for this question. Let the user know you don't have specific information about their query.";
        }

        return $base."\n\n--- KNOWLEDGE BASE CONTEXT ---\n\n".$context."\n\n--- END CONTEXT ---\n\nAnswer the user's question using ONLY the context above. If the context doesn't contain relevant information, say you don't have that information.";
    }

    private function currentMonthTokens(Client $client): int
    {
        return (int) UsageLog::where('client_id', $client->id)
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('total_tokens');
    }

    private function estimateChatCost(string $model, int $promptTokens, int $completionTokens): float
    {
        [$inputCost, $outputCost] = match ($model) {
            'gpt-4o-mini' => [0.15, 0.60],
            'gpt-4o' => [2.50, 10.00],
            default => [0.15, 0.60],
        };

        return round(
            ($promptTokens / 1_000_000) * $inputCost + ($completionTokens / 1_000_000) * $outputCost,
            6
        );
    }
}

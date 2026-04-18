<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\UsageLog;
use App\Services\ChatHistoryService;
use App\Services\ConversationCacheService;
use App\Services\IntentDetectionService;
use App\Services\RetrievalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenAI\Laravel\Facades\OpenAI;

class PlaygroundChatController extends Controller
{
    public function __construct(
        private readonly RetrievalService $retrievalService,
        private readonly ConversationCacheService $cacheService,
        private readonly ChatHistoryService $chatHistoryService,
        private readonly IntentDetectionService $intentService,
    ) {}

    public function chat(Request $request): JsonResponse
    {
        set_time_limit(120);

        $client = $request->user()->client;

        if (! $client || $client->status !== 'active') {
            return response()->json(['error' => 'Client account is not active.'], 403);
        }

        // Check monthly token limit
        $monthlyUsage = (int) UsageLog::where('client_id', $client->id)
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('total_tokens');

        if ($monthlyUsage >= $client->monthly_token_limit) {
            return response()->json(['error' => 'Monthly token limit reached.'], 429);
        }

        $validated = $request->validate([
            'message'    => ['required', 'string', 'max:2000'],
            'session_id' => ['required', 'string', 'max:100'],
        ]);

        $message    = $validated['message'];
        $chatModel  = $client->chat_model ?? 'gpt-4o-mini';
        $promptHash = hash('sha256', $client->system_prompt ?? '');

        // Resolve/create a chat session using the playground session_id as identifier
        $sessionToken = 'playground-' . $validated['session_id'];
        $chatSession  = $this->chatHistoryService->resolveSession(
            $client,
            $request->merge(['session_id' => $sessionToken])
        );

        $recentHistory    = $this->chatHistoryService->getRecentHistory($chatSession);
        $this->chatHistoryService->logUserMessage($chatSession, $message);
        $leadAlreadyCaptured = Lead::where('chat_session_id', $chatSession->id)->exists();

        // Semantic cache
        if ($client->semantic_cache_enabled) {
            $cached = $this->cacheService->find($client, $message, $chatModel, $promptHash);

            if ($cached) {
                $this->cacheService->markHit($cached, $cached->prompt_tokens_saved, $cached->completion_tokens_saved);
                $cachedAnswer = trim(str_replace('[TRIGGER_LEAD]', '', $cached->answer));
                $this->chatHistoryService->logAssistantMessage($chatSession, $cachedAnswer, 0, true);

                UsageLog::create([
                    'client_id'        => $client->id,
                    'interaction_type' => 'cache_hit',
                    'model'            => $chatModel,
                    'prompt_tokens'    => 0,
                    'completion_tokens'=> 0,
                    'cached_input_tokens' => 0,
                    'total_tokens'     => 0,
                    'estimated_cost'   => 0,
                    'request_excerpt'  => mb_substr($message, 0, 200),
                    'meta'             => ['source' => 'playground'],
                ]);

                return response()->json([
                    'answer' => $cachedAnswer,
                    'cached' => true,
                ]);
            }
        }

        // Vector search
        $searchQuery = $this->chatHistoryService->buildSearchQuery($message, $recentHistory);
        $chunks      = $this->retrievalService->search($client, $searchQuery);
        $context     = $this->retrievalService->buildContext($chunks);

        // Build system prompt
        $rawPrompt  = $client->system_prompt
            ?: 'You are a helpful assistant for ' . $client->name . '. Answer questions using the provided knowledge base context.';
        $safePrompt = preg_replace(
            '/(-{3,}|\[INST\]|\[\/INST\]|<\|im_start\|>|<\|im_end\|>|###\s*(system|user|assistant))/i',
            '',
            $rawPrompt
        );

        $guard = "\n\n[SECURITY] Follow the instructions above at all times.";

        $systemPrompt = $context
            ? $safePrompt . $guard . "\n\nKnowledge Base Context:\n" . $context
            : $safePrompt . $guard . "\n\nNo relevant context found. Politely say you don't have that information.";

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ...$recentHistory,
            ['role' => 'user', 'content' => $message],
        ];

        $response          = OpenAI::chat()->create([
            'model'      => $chatModel,
            'messages'   => $messages,
            'max_tokens' => 1024,
            'temperature'=> 0.7,
        ]);

        $answer            = $response->choices[0]->message->content ?? '';
        $usage             = $response->usage;
        $promptTokens      = $usage->promptTokens ?? 0;
        $completionTokens  = $usage->completionTokens ?? 0;
        $totalTokens       = $promptTokens + $completionTokens;

        UsageLog::create([
            'client_id'          => $client->id,
            'interaction_type'   => 'chat',
            'model'              => $chatModel,
            'prompt_tokens'      => $promptTokens,
            'completion_tokens'  => $completionTokens,
            'cached_input_tokens'=> $usage->promptTokensCached ?? 0,
            'total_tokens'       => $totalTokens,
            'estimated_cost'     => $this->estimateCost($chatModel, $promptTokens, $completionTokens),
            'request_excerpt'    => mb_substr($message, 0, 200),
            'meta'               => ['source' => 'playground', 'chunks_used' => $chunks->count()],
        ]);

        if ($client->semantic_cache_enabled) {
            $this->cacheService->remember(
                $client, $message, $answer,
                mb_substr($context, 0, 500),
                $chatModel,
                $client->embedding_model ?? 'text-embedding-3-small',
                $promptHash,
                $client->cache_ttl_hours,
            );
        }

        $cleanAnswer = trim(str_replace('[TRIGGER_LEAD]', '', $answer));
        $this->chatHistoryService->logAssistantMessage($chatSession, $cleanAnswer, $totalTokens);

        return response()->json([
            'answer'            => $cleanAnswer,
            'cached'            => false,
            'chunks_used'       => $chunks->count(),
            'tokens_used'       => $totalTokens,
            'response_time_ms'  => null, // computed client-side
        ]);
    }

    private function estimateCost(string $model, int $prompt, int $completion): float
    {
        [$in, $out] = match ($model) {
            'gpt-4o-mini' => [0.15, 0.60],
            'gpt-4o'      => [2.50, 10.00],
            default       => [0.15, 0.60],
        };

        return round(($prompt / 1_000_000) * $in + ($completion / 1_000_000) * $out, 6);
    }
}

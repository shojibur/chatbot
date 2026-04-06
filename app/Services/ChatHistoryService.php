<?php

namespace App\Services;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChatHistoryService
{
    /**
     * Find or create a chat session for this request.
     */
    public function resolveSession(Client $client, Request $request): ChatSession
    {
        $sessionToken = $request->input('session_token');

        if ($sessionToken) {
            $session = ChatSession::where('client_id', $client->id)
                ->where('session_token', $sessionToken)
                ->first();

            if ($session) {
                return $session;
            }
            // If the supplied token doesn't match any real session, discard it
            // and issue a fresh server-generated token below — never echo back
            // an attacker-controlled value as the authoritative session token.
        }

        return ChatSession::create([
            'client_id'          => $client->id,
            'session_token'      => Str::random(64), // Always server-generated for new sessions
            'visitor_ip'         => $request->ip(),
            'visitor_identifier' => $request->input('visitor_id'),
            'page_url'           => $request->input('page_url'),
            'user_agent'         => mb_substr($request->userAgent() ?? '', 0, 500),
            'last_activity_at'   => now(),
        ]);
    }

    /**
     * Log a user message.
     */
    public function logUserMessage(ChatSession $session, string $content): ChatMessage
    {
        return $this->logMessage($session, 'user', $content);
    }

    /**
     * Log an assistant response.
     */
    public function logAssistantMessage(
        ChatSession $session,
        string $content,
        int $tokenCount = 0,
        bool $fromCache = false,
        ?array $meta = null,
    ): ChatMessage {
        return $this->logMessage($session, 'assistant', $content, $tokenCount, $fromCache, $meta);
    }

    /**
     * Return recent messages for a session formatted for the OpenAI messages array.
     * Fetches the last $limit messages (excluding the current turn, call this before logging).
     *
     * @return array<int, array{role: string, content: string}>
     */
    public function getRecentHistory(ChatSession $session, int $limit = 6): array
    {
        return $session->messages()
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['role', 'content'])
            ->reverse()
            ->values()
            ->map(fn ($m) => ['role' => $m->role, 'content' => $m->content])
            ->all();
    }

    /**
     * Build a search query that adds prior context so vague follow-ups
     * ("What is it?", "Tell me more") retrieve meaningful chunks.
     */
    public function buildSearchQuery(string $currentMessage, array $recentHistory): string
    {
        // Collect the last 1-2 user turns from history to give the query context
        $priorUserMessages = array_filter($recentHistory, fn ($m) => $m['role'] === 'user');
        $priorUserMessages = array_values($priorUserMessages);
        $lastPrior = end($priorUserMessages);

        if ($lastPrior && mb_strlen($currentMessage) < 60) {
            return $lastPrior['content'] . ' ' . $currentMessage;
        }

        return $currentMessage;
    }

    /**
     * Record a message and update session counters.
     */
    private function logMessage(
        ChatSession $session,
        string $role,
        string $content,
        int $tokenCount = 0,
        bool $fromCache = false,
        ?array $meta = null,
    ): ChatMessage {
        $message = ChatMessage::create([
            'chat_session_id' => $session->id,
            'client_id' => $session->client_id,
            'role' => $role,
            'content' => $content,
            'token_count' => $tokenCount,
            'from_cache' => $fromCache,
            'meta' => $meta,
        ]);

        $session->increment('message_count');
        $session->forceFill([
            'total_tokens' => $session->total_tokens + $tokenCount,
            'last_activity_at' => now(),
        ])->save();

        return $message;
    }
}

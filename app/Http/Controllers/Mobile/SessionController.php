<?php

namespace App\Http\Controllers\Mobile;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionController extends MobileController
{
    public function index(Request $request): JsonResponse
    {
        $client = $this->currentClient($request);

        $sessions = $client->chatSessions()
            ->where('session_token', 'not like', 'playground-%')
            ->addSelect([
                'first_message' => ChatMessage::select('content')
                    ->whereColumn('chat_session_id', 'chat_sessions.id')
                    ->oldest('created_at')
                    ->limit(1),
            ])
            ->orderByDesc('last_activity_at')
            ->limit(25)
            ->get()
            ->map(fn (ChatSession $session) => [
                'id' => $session->id,
                'session_token' => $session->session_token,
                'visitor_ip' => $session->visitor_ip,
                'visitor_identifier' => $session->visitor_identifier,
                'page_url' => $session->page_url,
                'user_agent' => $session->user_agent,
                'message_count' => $session->message_count,
                'total_tokens' => $session->total_tokens,
                'last_activity_at' => $session->last_activity_at?->toISOString(),
                'created_at' => $session->created_at?->toISOString(),
                'first_message' => $session->first_message,
            ]);

        return response()->json([
            'sessions' => $sessions,
        ]);
    }

    public function messages(Request $request, ChatSession $session): JsonResponse
    {
        $client = $this->currentClient($request);
        abort_unless($session->client_id === $client->id, 403);

        $messages = $session->messages()
            ->orderBy('created_at')
            ->get()
            ->map(fn ($message) => [
                'id' => $message->id,
                'role' => $message->role,
                'content' => $message->content,
                'token_count' => $message->token_count,
                'from_cache' => $message->from_cache,
                'created_at' => $message->created_at?->toISOString(),
            ]);

        return response()->json([
            'session_id' => $session->id,
            'messages' => $messages,
        ]);
    }
}

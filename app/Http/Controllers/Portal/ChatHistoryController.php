<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\ChatSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChatHistoryController extends Controller
{
    public function index(Request $request): Response
    {
        $client = $request->user()->client;

        $sessions = $client->chatSessions()
            ->where('session_token', 'not like', 'playground-%')
            ->addSelect([
                'first_message' => \App\Models\ChatMessage::select('content')
                    ->whereColumn('chat_session_id', 'chat_sessions.id')
                    ->oldest('created_at')
                    ->limit(1),
            ])
            ->orderByDesc('last_activity_at')
            ->simplePaginate(20);

        return Inertia::render('portal/ChatHistory', [
            'sessions' => $sessions->through(fn (ChatSession $session): array => [
                'id'                 => $session->id,
                'session_token'      => $session->session_token,
                'visitor_ip'         => $session->visitor_ip,
                'visitor_identifier' => $session->visitor_identifier,
                'page_url'           => $session->page_url,
                'user_agent'         => $session->user_agent,
                'message_count'      => $session->message_count,
                'total_tokens'       => $session->total_tokens,
                'last_activity_at'   => $session->last_activity_at?->toDateTimeString(),
                'created_at'         => $session->created_at?->toDateTimeString(),
                'first_message'      => $session->first_message,
                'messages'           => [],
            ]),
        ]);
    }

    /**
     * Return messages for a chat session (lazy-loaded via fetch).
     */
    public function messages(Request $request, int $sessionId): JsonResponse
    {
        $client = $request->user()->client;

        $session = $client->chatSessions()->findOrFail($sessionId);

        $messages = $session->messages()
            ->orderBy('created_at')
            ->get()
            ->map(fn ($m) => [
                'id'         => $m->id,
                'role'       => $m->role,
                'content'    => $m->content,
                'token_count'=> $m->token_count,
                'from_cache' => $m->from_cache,
                'created_at' => $m->created_at?->toDateTimeString(),
            ]);

        return response()->json(['messages' => $messages]);
    }
}

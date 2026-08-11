<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class LiveSessionsController extends Controller
{
    public function index(Request $request): Response
    {
        $client = $request->user()->client;

        // Auto-release stale takeovers (15+ min without a human reply)
        $client->chatSessions()
            ->where('is_human_takeover', true)
            ->where('taken_over_at', '<', now()->subMinutes(15))
            ->update([
                'is_human_takeover'      => false,
                'taken_over_by_user_id'  => null,
                'taken_over_at'          => null,
            ]);

        $sessions = $client->chatSessions()
            ->where('session_token', 'not like', 'playground-%')
            ->addSelect([
                'first_message' => ChatMessage::select('content')
                    ->whereColumn('chat_session_id', 'chat_sessions.id')
                    ->oldest('created_at')
                    ->limit(1),
            ])
            ->orderByDesc('last_activity_at')
            ->limit(50)
            ->get()
            ->map(fn (ChatSession $s) => $this->transformSession($s));

        return Inertia::render('portal/LiveSessions', [
            'sessions' => $sessions,
        ]);
    }

    public function messages(Request $request, int $sessionId): JsonResponse
    {
        $client = $request->user()->client;
        $session = $client->chatSessions()->findOrFail($sessionId);

        $messages = $session->messages()
            ->orderBy('created_at')
            ->get()
            ->map(fn (ChatMessage $m) => [
                'id'            => $m->id,
                'role'          => $m->role,
                'content'       => $m->content,
                'token_count'   => $m->token_count,
                'from_cache'    => $m->from_cache,
                'source'        => (string) data_get($m->meta, 'source', $m->role === 'assistant' ? 'ai' : 'visitor'),
                'sent_by_name'  => data_get($m->meta, 'sent_by_name'),
                'human_takeover'=> (bool) data_get($m->meta, 'human_takeover', false),
                'created_at'    => $m->created_at?->toISOString(),
            ]);

        return response()->json([
            'session'  => $this->transformSession($session),
            'messages' => $messages,
        ]);
    }

    public function takeover(Request $request, int $sessionId): JsonResponse
    {
        $client = $request->user()->client;
        $user   = $request->user();
        $session = $client->chatSessions()->findOrFail($sessionId);

        $session->forceFill([
            'is_human_takeover'     => true,
            'taken_over_by_user_id' => $user->id,
            'taken_over_at'         => now(),
        ])->save();

        Log::info('[Portal] Human takeover started', [
            'session_id' => $session->id,
            'user_id'    => $user->id,
        ]);

        return response()->json(['session' => $this->transformSession($session->fresh())]);
    }

    public function releaseTakeover(Request $request, int $sessionId): JsonResponse
    {
        $client  = $request->user()->client;
        $user    = $request->user();
        $session = $client->chatSessions()->findOrFail($sessionId);

        $session->forceFill([
            'is_human_takeover'     => false,
            'taken_over_by_user_id' => null,
            'taken_over_at'         => null,
        ])->save();

        Log::info('[Portal] Human takeover released', [
            'session_id' => $session->id,
            'user_id'    => $user->id,
        ]);

        return response()->json(['session' => $this->transformSession($session->fresh())]);
    }

    public function sendMessage(Request $request, int $sessionId): JsonResponse
    {
        $client  = $request->user()->client;
        $user    = $request->user();
        $session = $client->chatSessions()->findOrFail($sessionId);

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:4000'],
        ]);

        if (! $session->is_human_takeover) {
            $session->forceFill([
                'is_human_takeover'     => true,
                'taken_over_by_user_id' => $user->id,
                'taken_over_at'         => now(),
            ])->save();
        }

        $message = ChatMessage::create([
            'chat_session_id' => $session->id,
            'client_id'       => $session->client_id,
            'role'            => 'assistant',
            'content'         => $validated['content'],
            'token_count'     => 0,
            'from_cache'      => false,
            'meta'            => [
                'source'          => 'portal_operator',
                'human_takeover'  => true,
                'sent_by_user_id' => $user->id,
                'sent_by_name'    => $user->name,
            ],
        ]);

        $session->increment('message_count');
        $session->forceFill(['last_activity_at' => now()])->save();

        Log::info('[Portal] Operator message sent', [
            'session_id' => $session->id,
            'message_id' => $message->id,
            'user_id'    => $user->id,
        ]);

        return response()->json([
            'session' => $this->transformSession($session->fresh()),
            'message' => [
                'id'             => $message->id,
                'role'           => $message->role,
                'content'        => $message->content,
                'token_count'    => $message->token_count,
                'from_cache'     => $message->from_cache,
                'source'         => 'portal_operator',
                'sent_by_name'   => $user->name,
                'human_takeover' => true,
                'created_at'     => $message->created_at?->toISOString(),
            ],
        ], 201);
    }

    protected function transformSession(ChatSession $session): array
    {
        $lastActivity = $session->last_activity_at;
        $isActive = $lastActivity && $lastActivity->gt(now()->subMinutes(10));

        return [
            'id'                    => $session->id,
            'session_token'         => $session->session_token,
            'visitor_ip'            => $session->visitor_ip,
            'visitor_identifier'    => $session->visitor_identifier,
            'page_url'              => $session->page_url,
            'user_agent'            => $session->user_agent,
            'message_count'         => $session->message_count,
            'total_tokens'          => $session->total_tokens,
            'last_activity_at'      => $lastActivity?->toISOString(),
            'created_at'            => $session->created_at?->toISOString(),
            'first_message'         => $session->first_message ?? null,
            'is_active'             => $isActive,
            'is_human_takeover'     => (bool) $session->is_human_takeover,
            'taken_over_by_user_id' => $session->taken_over_by_user_id,
            'taken_over_at'         => $session->taken_over_at?->toISOString(),
        ];
    }
}

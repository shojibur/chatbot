<?php

namespace App\Http\Controllers\Mobile;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SessionController extends MobileController
{
    public function index(Request $request): JsonResponse
    {
        $client = $this->currentClient($request);

        // Auto-release takeover only when the human operator hasn't sent a reply
        // for 15+ minutes (based on taken_over_at, not last_activity_at which
        // updates on every visitor message and would release active sessions).
        $client->chatSessions()
            ->where('is_human_takeover', true)
            ->where('taken_over_at', '<', now()->subMinutes(15))
            ->update([
                'is_human_takeover' => false,
                'taken_over_by_user_id' => null,
                'taken_over_at' => null,
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
            ->limit(25)
            ->get()
            ->map(fn (ChatSession $session) => $this->transformSession($session, [
                'first_message' => $session->first_message,
            ]));

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
            ->map(fn (ChatMessage $message) => $this->transformMessage($message));

        return response()->json([
            'session_id' => $session->id,
            'session' => $this->transformSession($session),
            'messages' => $messages,
        ]);
    }

    public function takeover(Request $request, ChatSession $session): JsonResponse
    {
        $client = $this->currentClient($request);
        $user = $this->currentUser($request);
        abort_unless($session->client_id === $client->id, 403);

        $session->forceFill([
            'is_human_takeover' => true,
            'taken_over_by_user_id' => $user->id,
            'taken_over_at' => now(),
        ])->save();

        return response()->json([
            'session' => $this->transformSession($session->fresh()),
        ]);
    }

    public function releaseTakeover(Request $request, ChatSession $session): JsonResponse
    {
        $client = $this->currentClient($request);
        abort_unless($session->client_id === $client->id, 403);

        $session->forceFill([
            'is_human_takeover' => false,
            'taken_over_by_user_id' => null,
            'taken_over_at' => null,
        ])->save();

        return response()->json([
            'session' => $this->transformSession($session->fresh()),
        ]);
    }

    public function sendMessage(Request $request, ChatSession $session): JsonResponse
    {
        $client = $this->currentClient($request);
        $user = $this->currentUser($request);
        abort_unless($session->client_id === $client->id, 403);

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:4000'],
            'send_as' => ['nullable', Rule::in(['assistant'])],
        ]);

        if (! $session->is_human_takeover) {
            $session->forceFill([
                'is_human_takeover' => true,
                'taken_over_by_user_id' => $user->id,
                'taken_over_at' => now(),
            ])->save();
        }

        $message = ChatMessage::create([
            'chat_session_id' => $session->id,
            'client_id' => $session->client_id,
            'role' => 'assistant',
            'content' => $validated['content'],
            'token_count' => 0,
            'from_cache' => false,
            'meta' => [
                'source' => 'mobile_operator',
                'human_takeover' => true,
                'sent_by_user_id' => $user->id,
                'sent_by_name' => $user->name,
            ],
        ]);

        $session->increment('message_count');
        $session->forceFill([
            'last_activity_at' => now(),
        ])->save();

        return response()->json([
            'session' => $this->transformSession($session->fresh()),
            'message' => $this->transformMessage($message->fresh()),
        ], 201);
    }

    protected function transformSession(ChatSession $session, array $extra = []): array
    {
        return [
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
            'is_human_takeover' => (bool) $session->is_human_takeover,
            'taken_over_by_user_id' => $session->taken_over_by_user_id,
            'taken_over_at' => $session->taken_over_at?->toISOString(),
            ...$extra,
        ];
    }

    protected function transformMessage(ChatMessage $message): array
    {
        return [
            'id' => $message->id,
            'role' => $message->role,
            'content' => $message->content,
            'token_count' => $message->token_count,
            'from_cache' => $message->from_cache,
            'source' => (string) data_get($message->meta, 'source', $message->role === 'assistant' ? 'ai' : 'visitor'),
            'sent_by_name' => data_get($message->meta, 'sent_by_name'),
            'human_takeover' => (bool) data_get($message->meta, 'human_takeover', false),
            'created_at' => $message->created_at?->toISOString(),
        ];
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatSession;
use App\Models\Client;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'client_code'   => 'required|string',
            'session_token' => 'nullable|string',
            'name'          => 'required|string|max:255',
            'contact'       => 'required|string|max:255',
            'user_request'  => 'nullable|string|max:1000',
            'notes'         => 'nullable|string|max:1000',
            'trigger'       => 'nullable|in:intent,no_answer,manual',
        ]);

        $client = Client::where('unique_code', $data['client_code'])
            ->where('status', 'active')
            ->firstOrFail();

        $session = ! empty($data['session_token'])
            ? ChatSession::where('client_id', $client->id)
                ->where('session_token', $data['session_token'])
                ->first()
            : null;

        // Snapshot last 10 messages so the sales team has full context
        $snapshot = $session
            ? $session->messages()
                ->orderByDesc('id')
                ->limit(10)
                ->get(['role', 'content'])
                ->reverse()
                ->values()
                ->toArray()
            : [];

        Lead::create([
            'client_id'             => $client->id,
            'chat_session_id'       => $session?->id,
            'name'                  => $data['name'],
            'contact'               => $data['contact'],
            'user_request'          => $data['user_request'] ?? null,
            'notes'                 => $data['notes'] ?? null,
            'conversation_snapshot' => $snapshot,
            'trigger'               => $data['trigger'] ?? 'intent',
        ]);

        return response()->json(['success' => true]);
    }
}

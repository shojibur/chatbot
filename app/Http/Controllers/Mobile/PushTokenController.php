<?php

namespace App\Http\Controllers\Mobile;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PushTokenController extends MobileController
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fcm_token' => ['required', 'string', 'max:500'],
        ]);

        $user = $this->currentUser($request);
        $user->update(['fcm_token' => $validated['fcm_token']]);

        Log::info('[Push] FCM token registered', [
            'user_id'       => $user->id,
            'email'         => $user->email,
            'token_preview' => substr($validated['fcm_token'], 0, 20) . '...',
        ]);

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $user = $this->currentUser($request);
        $user->update(['fcm_token' => null]);

        Log::info('[Push] FCM token cleared on logout', [
            'user_id' => $user->id,
            'email'   => $user->email,
        ]);

        return response()->json(['ok' => true]);
    }
}

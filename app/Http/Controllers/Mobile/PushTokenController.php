<?php

namespace App\Http\Controllers\Mobile;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushTokenController extends MobileController
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fcm_token' => ['required', 'string', 'max:500'],
        ]);

        $this->currentUser($request)->update([
            'fcm_token' => $validated['fcm_token'],
        ]);

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $this->currentUser($request)->update([
            'fcm_token' => null,
        ]);

        return response()->json(['ok' => true]);
    }
}

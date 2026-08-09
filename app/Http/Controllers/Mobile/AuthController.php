<?php

namespace App\Http\Controllers\Mobile;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends MobileController
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email:rfc'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->user_type !== User::TYPE_CLIENT || ! $user->client_id || ! $user->client) {
            throw ValidationException::withMessages([
                'email' => ['This account cannot use the mobile client portal.'],
            ]);
        }

        $token = $user->createToken($validated['device_name'] ?? 'zaochat-mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'client_id' => $user->client_id,
                'client_name' => $user->client->name,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $this->currentUser($request);
        $client = $user->client()->with('plan')->firstOrFail();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'user_type' => $user->user_type,
            ],
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'status' => $client->status,
                'contact_email' => $client->contact_email,
                'website_url' => $client->website_url,
                'plan' => $this->transformPlan($client->plan),
            ],
        ]);
    }
}

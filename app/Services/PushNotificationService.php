<?php

namespace App\Services;

use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Throwable;

class PushNotificationService
{
    public function __construct(
        private readonly Messaging $messaging,
    ) {}

    public function newSession(Client $client): void
    {
        $this->notifyClientUsers($client, [
            'title' => 'New visitor chat started',
            'body'  => "Someone is chatting on {$client->name}'s widget",
            'data'  => ['type' => 'new_session', 'client_id' => (string) $client->id],
        ]);
    }

    public function visitorRepliedDuringTakeover(Client $client, int $sessionId): void
    {
        $this->notifyClientUsers($client, [
            'title' => 'Visitor replied — you are live',
            'body'  => 'The visitor sent a new message while you have takeover active',
            'data'  => ['type' => 'takeover_reply', 'session_id' => (string) $sessionId],
        ]);
    }

    public function newLead(Client $client, string $leadName): void
    {
        $this->notifyClientUsers($client, [
            'title' => 'New lead captured',
            'body'  => "{$leadName} left their contact details",
            'data'  => ['type' => 'new_lead', 'client_id' => (string) $client->id],
        ]);
    }

    private function notifyClientUsers(Client $client, array $payload): void
    {
        $context = [
            'client_id'   => $client->id,
            'client_name' => $client->name,
            'type'        => $payload['data']['type'] ?? 'unknown',
            'title'       => $payload['title'],
        ];

        $tokens = User::where('client_id', $client->id)
            ->whereNotNull('fcm_token')
            ->pluck('fcm_token', 'email')
            ->all();

        if (empty($tokens)) {
            Log::channel('single')->info('[Push] Skipped — no FCM tokens registered', $context);
            return;
        }

        Log::channel('single')->info('[Push] Sending to ' . count($tokens) . ' device(s)', $context);

        foreach ($tokens as $email => $token) {
            try {
                $message = CloudMessage::new()
                    ->toToken($token)
                    ->withNotification(Notification::create($payload['title'], $payload['body']))
                    ->withData($payload['data']);

                $this->messaging->send($message);

                Log::channel('single')->info('[Push] Sent OK', [
                    ...$context,
                    'recipient' => $email,
                    'token_preview' => substr($token, 0, 20) . '...',
                ]);
            } catch (Throwable $e) {
                Log::channel('single')->error('[Push] Failed', [
                    ...$context,
                    'recipient' => $email,
                    'token_preview' => substr($token, 0, 20) . '...',
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}

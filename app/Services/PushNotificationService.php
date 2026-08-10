<?php

namespace App\Services;

use App\Models\Client;
use App\Models\User;
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
        $tokens = User::where('client_id', $client->id)
            ->whereNotNull('fcm_token')
            ->pluck('fcm_token')
            ->all();

        if (empty($tokens)) {
            return;
        }

        $notification = Notification::create($payload['title'], $payload['body']);

        foreach ($tokens as $token) {
            try {
                $message = CloudMessage::withTarget('token', $token)
                    ->withNotification($notification)
                    ->withData($payload['data']);

                $this->messaging->send($message);
            } catch (Throwable) {
                // Stale token — ignore, will be overwritten on next login
            }
        }
    }
}

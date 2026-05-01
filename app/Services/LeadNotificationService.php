<?php

namespace App\Services;

use App\Mail\NewLeadCaptured;
use App\Models\Lead;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class LeadNotificationService
{
    public function notifyCapturedLead(Lead $lead): void
    {
        $lead->loadMissing('client');

        $this->queueLeadEmail($lead);
        $this->sendLeadSms($lead);
    }

    private function queueLeadEmail(Lead $lead): void
    {
        $contactEmail = trim((string) ($lead->client->contact_email ?? ''));

        if ($contactEmail === '') {
            Log::info('Lead email notification skipped: no client email configured.', [
                'lead_id' => $lead->id,
                'client_id' => $lead->client_id,
            ]);

            return;
        }

        try {
            Mail::to($contactEmail)->queue(new NewLeadCaptured($lead));

            Log::info('Lead email notification queued.', [
                'lead_id' => $lead->id,
                'client_id' => $lead->client_id,
                'contact_email' => $contactEmail,
            ]);
        } catch (Throwable $e) {
            Log::warning('Lead saved but email notification failed to queue.', [
                'lead_id' => $lead->id,
                'client_id' => $lead->client_id,
                'contact_email' => $contactEmail,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sendLeadSms(Lead $lead): void
    {
        $client = $lead->client;

        if (! config('services.twilio.sms_enabled', false)) {
            Log::info('Lead SMS notification skipped: Twilio SMS disabled.', [
                'lead_id' => $lead->id,
                'client_id' => $lead->client_id,
            ]);

            return;
        }

        if (! $client->lead_sms_enabled) {
            Log::info('Lead SMS notification skipped: client SMS disabled.', [
                'lead_id' => $lead->id,
                'client_id' => $lead->client_id,
            ]);

            return;
        }

        $to = trim((string) ($client->lead_sms_to ?? ''));
        $from = trim((string) config('services.twilio.sms_from'));
        $accountSid = trim((string) config('services.twilio.account_sid'));
        $authToken = trim((string) config('services.twilio.auth_token'));
        $timeout = (int) config('services.twilio.sms_timeout', 15);

        if ($to === '' || $from === '' || $accountSid === '' || $authToken === '') {
            Log::warning('Lead SMS notification skipped: Twilio config incomplete.', [
                'lead_id' => $lead->id,
                'client_id' => $lead->client_id,
                'has_to' => $to !== '',
                'has_from' => $from !== '',
                'has_account_sid' => $accountSid !== '',
                'has_auth_token' => $authToken !== '',
            ]);

            return;
        }

        try {
            $response = Http::asForm()
                ->withBasicAuth($accountSid, $authToken)
                ->timeout($timeout)
                ->post(
                    "https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json",
                    [
                        'To' => $to,
                        'From' => $from,
                        'Body' => $this->buildSmsBody($lead),
                    ]
                );

            if ($response->failed()) {
                Log::error('Lead SMS notification failed.', [
                    'lead_id' => $lead->id,
                    'client_id' => $lead->client_id,
                    'sms_to' => $to,
                    'twilio_status' => $response->status(),
                    'twilio_response' => $response->json() ?? $response->body(),
                ]);

                return;
            }

            $payload = $response->json();

            Log::info('Lead SMS notification sent.', [
                'lead_id' => $lead->id,
                'client_id' => $lead->client_id,
                'sms_to' => $to,
                'twilio_sid' => $payload['sid'] ?? null,
                'twilio_status' => $payload['status'] ?? null,
            ]);
        } catch (Throwable $e) {
            Log::error('Lead SMS notification request threw an exception.', [
                'lead_id' => $lead->id,
                'client_id' => $lead->client_id,
                'sms_to' => $to,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function buildSmsBody(Lead $lead): string
    {
        $parts = [
            'New lead for '.$lead->client->name.'.',
            'Name: '.$lead->name.'.',
            'Contact: '.$lead->contact.'.',
        ];

        if ($lead->user_request) {
            $parts[] = 'Request: '.$lead->user_request.'.';
        }

        if ($lead->notes) {
            $parts[] = 'Notes: '.$lead->notes.'.';
        }

        return implode(' ', $parts);
    }
}

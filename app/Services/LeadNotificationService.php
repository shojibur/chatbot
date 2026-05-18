<?php

namespace App\Services;

use App\Mail\NewLeadCaptured;
use App\Models\Lead;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use SentDm\Client as SentDMClient;
use Throwable;

class LeadNotificationService
{
    public function __construct(
        private readonly SentDMClient $sentClient
    ) {}

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

        if (! config('services.sent_dm.sms_enabled', false)) {
            Log::info('Lead SMS notification skipped: Sent DM SMS disabled.', [
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
        $apiKey = trim((string) config('services.sent_dm.api_key'));
        $templateId = trim((string) config('services.sent_dm.template_id'));

        if ($to === '' || $apiKey === '' || $templateId === '') {
            Log::warning('Lead SMS notification skipped: Sent DM config incomplete.', [
                'lead_id' => $lead->id,
                'client_id' => $lead->client_id,
                'has_to' => $to !== '',
                'has_api_key' => $apiKey !== '',
                'has_template_id' => $templateId !== '',
            ]);

            return;
        }

        try {
            $result = $this->sentClient->messages->send(
                to: [$to],
                channel: ['sms', 'whatsapp'],
                template: [
                    'id' => $templateId,
                    'parameters' => [
                        'businessName' => $lead->client->name ?? 'Unknown',
                        'leadName' => $lead->name ?? 'Unknown',
                        'leadContact' => $lead->contact ?? 'N/A',
                        'request' => $lead->user_request ?? 'No request specified',
                    ],
                ]
            );

            Log::info('Lead SMS notification sent.', [
                'lead_id' => $lead->id,
                'client_id' => $lead->client_id,
                'sms_to' => $to,
                'message_id' => $result->data->recipients[0]->messageID ?? null,
                'status' => $result->data->status ?? null,
            ]);
        } catch (\SentDm\Core\Exceptions\APIException $e) {
            Log::error('Lead SMS notification failed (SentDM API error).', [
                'lead_id' => $lead->id,
                'client_id' => $lead->client_id,
                'sms_to' => $to,
                'error' => $e->getMessage(),
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

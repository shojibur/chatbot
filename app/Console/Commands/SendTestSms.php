<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use SentDm\Client as SentDMClient;

class SendTestSms extends Command
{
    protected $signature = 'sms:test {to}';
    protected $description = 'Send a test lead SMS notification';

    public function handle(SentDMClient $client): int
    {
        $to = $this->argument('to');
        $templateId = config('services.sent_dm.template_id');

        $this->info("Sending test SMS to {$to}...");

        try {
            $result = $client->messages->send(
                to: [$to],
                template: [
                    'id' => $templateId,
                    'parameters' => [
                        'businessName' => 'Acme Corp',
                        'leadName' => 'John Doe',
                        'leadContact' => '+60123456789',
                        'request' => 'Interested in pricing plans',
                    ],
                ]
            );

            $this->info('SMS sent successfully!');
            $this->info('Response: ' . json_encode($result, JSON_PRETTY_PRINT));

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}

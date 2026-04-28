<?php

namespace App\Services;

use App\Models\Client;

class LeadCaptureService
{
    private const DEFAULT_INTRO_MESSAGE = 'I can help with that! May I get your name first so our team can follow up with you?';

    public function __construct(
        private readonly AiClientFactory $aiClientFactory,
        private readonly AiModelCatalog $modelCatalog,
    ) {}

    public function initialPrompt(Client $client, string $triggerMessage, string $assistantAnswer): string
    {
        $widgetSettings = is_array($client->widget_settings) ? $client->widget_settings : [];
        $fallback = trim((string) ($widgetSettings['lead_capture_intro_message'] ?? ''));
        $fallback = $fallback !== '' ? $fallback : self::DEFAULT_INTRO_MESSAGE;

        $prompt = <<<PROMPT
You are writing the first message in a lead-capture flow for {$client->name}.

The visitor asked:
"{$triggerMessage}"

The chatbot just answered:
"{$assistantAnswer}"

Write exactly one short, natural message that asks for the visitor's name first.
Rules:
- Be warm and concise.
- Do not answer the business question further.
- Do not ask for phone or email yet.
- Do not use markdown.
- Return plain text only.
PROMPT;

        try {
            $response = $this->aiClientFactory->make()->chat()->create([
                'model' => $this->modelCatalog->intentClassifierModel(),
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.2,
                ...$this->modelCatalog->chatTokenLimitOptions(
                    $this->modelCatalog->intentClassifierModel(),
                    80,
                ),
            ]);

            $message = trim((string) ($response->choices[0]->message->content ?? ''));

            return $message !== '' ? $message : $fallback;
        } catch (\Throwable) {
            return $fallback;
        }
    }

    /**
     * @param  array{name?: string, contact?: string}  $leadData
     * @return array{
     *     name: string,
     *     contact: string,
     *     next_step: 'ask_name'|'ask_contact'|'done',
     *     assistant_message: string,
     *     cancel_capture: bool
     * }
     */
    public function processStep(Client $client, string $step, string $visitorMessage, array $leadData = []): array
    {
        $knownName = trim((string) ($leadData['name'] ?? ''));
        $knownContact = trim((string) ($leadData['contact'] ?? ''));

        $prompt = <<<PROMPT
You are an AI lead-capture extractor for {$client->name}.

Current step: {$step}
Known name: {$knownName}
Known contact: {$knownContact}
Visitor reply: "{$visitorMessage}"

Your job:
1. Extract the real person's name only.
2. Extract the best contact detail only (phone or email).
3. Decide the next step.
4. Write the next assistant message naturally.

Important extraction rules:
- If the visitor says "David is my name and 0102469458 is my number", the name is "David" and the contact is "0102469458".
- Do not include phrases like "is my name", "my number", or extra words in the extracted fields.
- If the visitor refuses, set cancel_capture to true.
- If both name and contact are available after this message, set next_step to "done".
- If only the name is available, set next_step to "ask_contact".
- If the name is still missing, keep next_step as "ask_name".
- When asking for contact, ask for one field only: the best phone number or email.
- Return JSON only, no markdown, no explanation.

Return exactly this JSON shape:
{"name":"","contact":"","next_step":"ask_name","assistant_message":"","cancel_capture":false}
PROMPT;

        try {
            $response = $this->aiClientFactory->make()->chat()->create([
                'model' => $this->modelCatalog->intentClassifierModel(),
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0,
                ...$this->modelCatalog->chatTokenLimitOptions(
                    $this->modelCatalog->intentClassifierModel(),
                    220,
                ),
            ]);

            $content = (string) ($response->choices[0]->message->content ?? '');
            $decoded = $this->decodeJsonObject($content);

            $name = trim((string) ($decoded['name'] ?? $knownName));
            $contact = trim((string) ($decoded['contact'] ?? $knownContact));
            $cancel = (bool) ($decoded['cancel_capture'] ?? false);
            $nextStep = (string) ($decoded['next_step'] ?? '');
            $assistantMessage = trim((string) ($decoded['assistant_message'] ?? ''));

            if ($name === '') {
                $name = $knownName;
            }

            if ($contact === '') {
                $contact = $knownContact;
            }

            if ($cancel) {
                return [
                    'name' => $name,
                    'contact' => $contact,
                    'next_step' => $step === 'ask_contact' ? 'ask_contact' : 'ask_name',
                    'assistant_message' => $assistantMessage !== ''
                        ? $assistantMessage
                        : 'No problem at all. Feel free to keep chatting if you need anything else.',
                    'cancel_capture' => true,
                ];
            }

            if (! in_array($nextStep, ['ask_name', 'ask_contact', 'done'], true)) {
                $nextStep = $this->resolveNextStep($name, $contact);
            }

            if ($assistantMessage === '') {
                $assistantMessage = $this->fallbackAssistantMessage($name, $nextStep);
            }

            return [
                'name' => $name,
                'contact' => $contact,
                'next_step' => $nextStep,
                'assistant_message' => $assistantMessage,
                'cancel_capture' => false,
            ];
        } catch (\Throwable) {
            $nextStep = $this->resolveNextStep($knownName, $knownContact);

            return [
                'name' => $knownName,
                'contact' => $knownContact,
                'next_step' => $nextStep,
                'assistant_message' => $this->fallbackAssistantMessage($knownName, $nextStep),
                'cancel_capture' => false,
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonObject(string $content): array
    {
        $trimmed = trim($content);
        $trimmed = preg_replace('/^```json\s*|\s*```$/i', '', $trimmed) ?? $trimmed;

        $decoded = json_decode($trimmed, true);

        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/s', $trimmed, $matches) === 1) {
            $decoded = json_decode($matches[0], true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        throw new \RuntimeException('Lead capture AI did not return valid JSON.');
    }

    private function resolveNextStep(string $name, string $contact): string
    {
        if ($name !== '' && $contact !== '') {
            return 'done';
        }

        if ($name !== '') {
            return 'ask_contact';
        }

        return 'ask_name';
    }

    private function fallbackAssistantMessage(string $name, string $nextStep): string
    {
        return match ($nextStep) {
            'ask_contact' => $name !== ''
                ? "Thanks {$name}! What's the best phone number or email address to reach you?"
                : "Thanks! What's the best phone number or email address to reach you?",
            'done' => $name !== ''
                ? "Thanks {$name}! We've got your contact details."
                : "Thanks! We've got your contact details.",
            default => 'I want to make sure I get this right. What is your name?',
        };
    }
}

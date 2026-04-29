<?php

namespace App\Services;

use App\Models\Client;

class LeadCaptureService
{
    private const DEFAULT_INTRO_MESSAGE = 'I can help with that! May I get your name first so our team can follow up with you?';

    /**
     * @var list<string>
     */
    private const REFUSAL_PATTERNS = [
        '/\b(no|nah|nope|skip|pass|cancel|stop)\b/i',
        '/\b(do\s+not|don\'t|dont)\s+(contact|call|reach)\b/i',
        '/\b(not\s+interested|never\s+mind|nevermind|forget\s+it|rather\s+not|prefer\s+not)\b/i',
    ];

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
        $extractedName = $knownName !== '' ? $knownName : $this->extractName($visitorMessage);
        $extractedContact = $knownContact !== '' ? $knownContact : $this->extractContact($visitorMessage);

        if ($this->isRefusal($visitorMessage)) {
            return [
                'name' => $extractedName,
                'contact' => $extractedContact,
                'next_step' => $step === 'ask_contact' ? 'ask_contact' : 'ask_name',
                'assistant_message' => 'No problem at all. Feel free to keep chatting if you need anything else.',
                'cancel_capture' => true,
            ];
        }

        if ($step === 'ask_contact' && $extractedContact !== '') {
            return [
                'name' => $extractedName,
                'contact' => $extractedContact,
                'next_step' => 'done',
                'assistant_message' => $this->fallbackAssistantMessage($extractedName, 'done'),
                'cancel_capture' => false,
            ];
        }

        if ($step === 'ask_name' && $extractedName !== '') {
            $nextStep = $extractedContact !== '' ? 'done' : 'ask_contact';

            return [
                'name' => $extractedName,
                'contact' => $extractedContact,
                'next_step' => $nextStep,
                'assistant_message' => $this->fallbackAssistantMessage($extractedName, $nextStep),
                'cancel_capture' => false,
            ];
        }

        $prompt = <<<PROMPT
You are an AI lead-capture extractor for {$client->name}.

Current step: {$step}
Known name: {$extractedName}
Known contact: {$extractedContact}
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

            $name = $this->coalesceName(
                trim((string) ($decoded['name'] ?? '')),
                $extractedName,
                $visitorMessage,
            );
            $contact = $this->coalesceContact(
                trim((string) ($decoded['contact'] ?? '')),
                $extractedContact,
                $visitorMessage,
            );
            $cancel = (bool) ($decoded['cancel_capture'] ?? false) || $this->isRefusal($visitorMessage);
            $nextStep = (string) ($decoded['next_step'] ?? '');
            $assistantMessage = trim((string) ($decoded['assistant_message'] ?? ''));

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
            $nextStep = $this->resolveNextStep($extractedName, $extractedContact);

            return [
                'name' => $extractedName,
                'contact' => $extractedContact,
                'next_step' => $nextStep,
                'assistant_message' => $this->fallbackAssistantMessage($extractedName, $nextStep),
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

    private function coalesceName(string $candidate, string $fallback, string $visitorMessage): string
    {
        $candidate = $this->cleanName($candidate);

        if ($candidate !== '') {
            return $candidate;
        }

        if ($fallback !== '') {
            return $fallback;
        }

        return $this->extractName($visitorMessage);
    }

    private function coalesceContact(string $candidate, string $fallback, string $visitorMessage): string
    {
        $candidate = $this->extractContact($candidate);

        if ($candidate !== '') {
            return $candidate;
        }

        if ($fallback !== '') {
            return $fallback;
        }

        return $this->extractContact($visitorMessage);
    }

    private function extractContact(string $message): string
    {
        $message = trim($message);

        if ($message === '') {
            return '';
        }

        if (preg_match('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $message, $matches) === 1) {
            return mb_strtolower($matches[0]);
        }

        if (preg_match('/(?<!\d)(?:\+?\d[\d\s\-()]{7,}\d)(?!\d)/', $message, $matches) === 1) {
            return trim($matches[0]);
        }

        return '';
    }

    private function extractName(string $message): string
    {
        $message = trim($message);

        if ($message === '' || $this->extractContact($message) !== '') {
            return '';
        }

        $candidate = preg_replace('/\b(my\s+name\s+is|name\s+is|name\'s|i\s+am|i\'m|this\s+is)\b/iu', '', $message) ?? $message;
        $candidate = $this->cleanName($candidate);

        if ($candidate === '') {
            return '';
        }

        $wordCount = preg_match_all('/[\p{L}]+(?:[\'\-][\p{L}]+)*/u', $candidate);

        if ($wordCount === false || $wordCount < 1 || $wordCount > 4) {
            return '';
        }

        if (preg_match('/\d/', $candidate) === 1) {
            return '';
        }

        return $candidate;
    }

    private function cleanName(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/^[^\\p{L}]+|[^\\p{L}\'\\-\\s]+$/u', '', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    private function isRefusal(string $message): bool
    {
        foreach (self::REFUSAL_PATTERNS as $pattern) {
            if (preg_match($pattern, $message) === 1) {
                return true;
            }
        }

        return false;
    }
}

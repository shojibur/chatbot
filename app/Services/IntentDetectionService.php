<?php

namespace App\Services;

class IntentDetectionService
{
    /**
     * Deterministic fallback for explicit requests to be contacted by a human.
     *
     * @var list<string>
     */
    private const HUMAN_CONTACT_PATTERNS = [
        '/\b(contact|reach)\s+(the\s+)?(team|someone|somebody|staff|sales|support|you)\b/i',
        '/\b(can|could)\s+(someone|somebody|you)\s+(contact|call|reach)\s+me\b/i',
        '/\b(call\s+me|contact\s+me|reach\s+me|get\s+back\s+to\s+me)\b/i',
        '/\b(speak|talk)\s+to\s+(a|someone|somebody|the)\s+(human|person|real\s+person|agent|team)\b/i',
        '/\b(can\s+i\s+contact\s+from\s+here)\b/i',
        '/\b(submit|leave|share)\s+(my\s+)?(info|information|details|number|email)\b/i',
        '/\b(be\s+contacted|get\s+a\s+callback|request\s+a\s+callback)\b/i',
    ];

    public function __construct(
        private readonly AiClientFactory $aiClientFactory,
        private readonly AiModelCatalog $modelCatalog,
    ) {}

    /**
     * Route the conversation before generating a normal bot answer.
     *
     * @param  list<array{role?: string, content?: string}>  $recentHistory
     * @return array{capture: bool, trigger: string|null, route: string, reason: string|null}
     */
    public function detectLeadRoute(string $userMessage, array $recentHistory = []): array
    {
        if ($this->hasExplicitHumanContactIntent($userMessage)) {
            return [
                'capture' => true,
                'trigger' => 'intent',
                'route' => 'lead_capture_now',
                'reason' => 'explicit_human_contact',
            ];
        }

        $history = $this->formatHistory($recentHistory);
        $prompt = <<<PROMPT
You are a lead-routing classifier for a business chatbot.

Decide whether this message should immediately start lead capture before the normal assistant answer is generated.

Return JSON only in this exact shape:
{"route":"normal_answer","reason":""}

Valid route values:
- "lead_capture_now"
- "normal_answer"

Choose "lead_capture_now" when ANY of these are true:
- The visitor wants a human, callback, demo, quote, pricing follow-up, sales follow-up, or to leave their info
- The visitor is clearly trying to buy, book, hire, sign up, get started, or speak with the team
- The visitor asks how to contact the business, whether someone can reach out, or how to submit their details
- The visitor asks something that a strong sales agent should convert into a lead instead of sending away

Choose "normal_answer" when the visitor is only browsing, asking informational questions, making casual conversation, or does not appear ready for contact or follow-up.

Important:
- Bias toward lead_capture_now for business/contact/buying intent.
- Do not wait for the assistant to tell the user to visit a contact page.
- If uncertain between the two, prefer "lead_capture_now".

RECENT HISTORY:
{$history}

LATEST VISITOR MESSAGE:
{$userMessage}
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
                    $this->modelCatalog->intentClassifierOutputTokens(),
                ),
            ]);

            $content = (string) ($response->choices[0]->message->content ?? '');
            $decoded = $this->decodeJsonObject($content);
            $route = (string) ($decoded['route'] ?? 'normal_answer');
            $reason = trim((string) ($decoded['reason'] ?? ''));

            return [
                'capture' => $route === 'lead_capture_now',
                'trigger' => $route === 'lead_capture_now' ? 'ai' : null,
                'route' => $route === 'lead_capture_now' ? 'lead_capture_now' : 'normal_answer',
                'reason' => $reason !== '' ? $reason : null,
            ];
        } catch (\Throwable) {
            return [
                'capture' => false,
                'trigger' => null,
                'route' => 'normal_answer',
                'reason' => null,
            ];
        }
    }

    /**
     * Backward-compatible wrapper.
     *
     * @param  list<array{role?: string, content?: string}>  $recentHistory
     * @return array{capture: bool, trigger: string|null}
     */
    public function detectLeadCapture(string $userMessage, array $recentHistory = []): array
    {
        $route = $this->detectLeadRoute($userMessage, $recentHistory);

        return [
            'capture' => $route['capture'],
            'trigger' => $route['trigger'],
        ];
    }

    /**
     * @param  list<array{role?: string, content?: string}>  $recentHistory
     */
    private function formatHistory(array $recentHistory): string
    {
        if ($recentHistory === []) {
            return '- none';
        }

        $lines = [];

        foreach ($recentHistory as $message) {
            $role = (string) ($message['role'] ?? 'unknown');
            $content = trim((string) ($message['content'] ?? ''));

            if ($content === '') {
                continue;
            }

            $lines[] = strtoupper($role).': '.$content;
        }

        return $lines === [] ? '- none' : implode("\n", $lines);
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

        throw new \RuntimeException('Lead router AI did not return valid JSON.');
    }

    private function hasExplicitHumanContactIntent(string $message): bool
    {
        $clean = trim($message);

        if ($clean === '') {
            return false;
        }

        foreach (self::HUMAN_CONTACT_PATTERNS as $pattern) {
            if (preg_match($pattern, $clean) === 1) {
                return true;
            }
        }

        return false;
    }
}

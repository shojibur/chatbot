<?php

namespace App\Services;

class IntentDetectionService
{
    public function __construct(
        private readonly AiClientFactory $aiClientFactory,
        private readonly AiModelCatalog $modelCatalog,
    ) {}

    /**
     * Decide whether lead capture should trigger, preferring the AI classifier.
     *
     * Returns a structured decision so callers can persist the trigger source.
     *
     * @return array{capture: bool, trigger: string|null}
     */
    public function detectLeadCapture(string $userMessage, string $botAnswer): array
    {
        $prompt = <<<PROMPT
You are a lead-capture classifier for a business chatbot. Given the visitor's message and the bot's reply, decide if this visitor is ready to be contacted by the business.

Return ONLY "yes" or "no".

ALWAYS return "yes" if the visitor explicitly asks to speak to a human, talk to someone, be contacted, get a callback, or be connected to a real person — regardless of how the bot replied. These visitors want human contact and their info should be captured.

Otherwise, the MOST important rule: if the bot gave a helpful, complete answer — return "no". Lead capture is for when the visitor needs human help that the bot cannot provide.

Return "yes" if ANY of these are true:
1. The visitor explicitly requests human contact (talk to someone, speak to a person, call me, contact me, connect me to a human, etc.)
2. The visitor has clear intent to transact (pricing, buying, booking, hiring) AND the bot could NOT fully help — it said it doesn't have the info, suggested contacting directly, gave a vague/incomplete answer, or couldn't provide what the visitor needs

Return "no" if:
- The bot answered the question well AND the visitor did NOT ask for human contact
- The visitor is asking general questions (what services, hours, location, team, etc.)
- The visitor is browsing or learning
- The visitor is making casual conversation (greetings, thanks, follow-ups)
- The question is informational

Be CONSERVATIVE for transactional queries, but ALWAYS capture leads when the visitor wants to talk to a human.

VISITOR: {$userMessage}
BOT REPLY: {$botAnswer}
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

            $result = mb_strtolower(trim($response->choices[0]->message->content ?? ''));

            return [
                'capture' => $result === 'yes',
                'trigger' => $result === 'yes' ? 'ai' : null,
            ];
        } catch (\Throwable) {
            return [
                'capture' => false,
                'trigger' => null,
            ];
        }
    }

    public function shouldCaptureLead(string $userMessage, string $botAnswer): bool
    {
        return $this->detectLeadCapture($userMessage, $botAnswer)['capture'];
    }
}

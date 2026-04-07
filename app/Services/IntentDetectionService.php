<?php

namespace App\Services;

use OpenAI\Laravel\Facades\OpenAI;

class IntentDetectionService
{
    /**
     * Use a fast, cheap AI call to decide if lead capture should trigger.
     *
     * Analyses both the user's message and the chatbot's answer together,
     * so it catches cases like "What do you charge?" → "We don't have pricing info"
     * that keyword matching would miss.
     *
     * Cost: ~100 tokens on gpt-4o-mini ≈ $0.00002 per call.
     */
    public function shouldCaptureLead(string $userMessage, string $botAnswer): bool
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
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => 3,
                'temperature' => 0,
            ]);

            $result = mb_strtolower(trim($response->choices[0]->message->content ?? ''));

            return $result === 'yes';
        } catch (\Throwable) {
            // If the classification call fails, don't block the chat — just skip lead capture
            return false;
        }
    }
}

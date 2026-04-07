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

The MOST important rule: if the bot gave a helpful, complete answer — ALWAYS return "no". Lead capture is ONLY for when the visitor needs human help that the bot cannot provide.

Return "yes" ONLY if BOTH conditions are met:
1. The visitor has clear intent to transact (pricing, buying, booking, hiring, requesting contact)
2. AND the bot could NOT fully help — it said it doesn't have the info, suggested contacting directly, gave a vague/incomplete answer, or couldn't provide what the visitor needs

Return "no" if:
- The bot answered the question well, even if it was about pricing or services
- The visitor is asking general questions (what services, hours, location, team, etc.)
- The visitor is browsing or learning
- The visitor is making casual conversation (greetings, thanks, follow-ups)
- The question is informational

Be VERY CONSERVATIVE. Only return "yes" when the bot clearly failed to help someone who wants to transact.

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

<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Support\Str;

class VisitorMessagePolicyService
{
    /**
     * Regex groups for low-value / abusive off-topic requests.
     *
     * @var array<string, list<string>>
     */
    private const BLOCK_PATTERNS = [
        'coding' => [
            '/\b(code|coding|programming|software|developer|algorithm|debug|syntax|compile|framework)\b/i',
            '/\b(javascript|typescript|python|php|java|c\+\+|c#|react|vue|node(?:\.js)?|laravel|sql|regex|html|css|git|docker|kubernetes)\b/i',
            '/\b(pyhon|pyhton|phyton)\b/i',
            '/\bhello\s+world\b/i',
        ],
        'math' => [
            '/^\s*[\d\.\s\+\-\*\/\(\)=xX^%]+\s*$/',
            '/\b\d+\s*[\+\-\*xX\/]\s*\d+\s*=?\b/i',
            '/\bwhat\s+is\s+\d+\s*[\+\-\*xX\/]\s*\d+\b/i',
            '/\b(?:solve|calculate|compute)\s+\d+\s*[\+\-\*xX\/]\s*\d+\b/i',
            '/\b\d+\s*(?:plus|minus|times|multiplied\s+by|divided\s+by)\s*\d+\b/i',
            '/\b(algebra|calculus|derivative|integral|equation|trigonometry)\b/i',
        ],
        'homework' => [
            '/\b(homework|assignment|exam|quiz|test\s+question|school\s+project)\b/i',
        ],
    ];

    public function blockedCategory(string $message): ?string
    {
        $clean = trim($message);
        if ($clean === '') {
            return null;
        }

        if ($this->looksLikeContactDetail($clean)) {
            return null;
        }

        foreach (self::BLOCK_PATTERNS as $category => $patterns) {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $clean) === 1) {
                    return $category;
                }
            }
        }

        return null;
    }

    private function looksLikeContactDetail(string $message): bool
    {
        if (preg_match('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $message) === 1) {
            return true;
        }

        $digitsOnly = preg_replace('/\D+/', '', $message) ?? '';

        if ($digitsOnly === '') {
            return false;
        }

        if (strlen($digitsOnly) < 7 || strlen($digitsOnly) > 16) {
            return false;
        }

        // Treat plain phone-like inputs as contact details, not math.
        return preg_match('/^[\d\s\-\+\(\)]+$/', $message) === 1;
    }

    public function blockedResponse(Client $client): string
    {
        $businessScope = trim((string) ($client->business_description ?? ''));

        if ($businessScope !== '') {
            $businessScope = (string) Str::of($businessScope)->squish()->limit(180);

            return "I can help with {$client->name}-related questions only. ".
                "I can't assist with coding, math, or homework topics here.\n\n".
                "Best topics to ask me about: {$businessScope}";
        }

        return "I can help with {$client->name}-related questions only. ".
            "I can't assist with coding, math, or homework topics here.\n\n".
            "Try asking about services, pricing, availability, location, booking, or contact details.";
    }
}

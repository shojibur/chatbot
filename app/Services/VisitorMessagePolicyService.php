<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Support\Str;

class VisitorMessagePolicyService
{
    /**
     * Coding-topic clients should not be blocked for coding questions.
     *
     * @var list<string>
     */
    private const TECH_BUSINESS_PATTERNS = [
        '/\b(software|saas|app|application|developer|development|programming)\b/i',
        '/\b(web\s+development|mobile\s+development|digital\s+agency|it\s+services)\b/i',
        '/\b(automation|ai\s+agency|tech\s+consulting)\b/i',
    ];

    /**
     * Regex groups for low-value / abusive off-topic requests.
     *
     * @var array<string, list<string>>
     */
    private const BLOCK_PATTERNS = [
        'coding' => [
            '/\b(code|coding|programming|software|developer|algorithm|debug|syntax|compile|framework)\b/i',
            '/\b(javascript|typescript|python|php|java|c\+\+|c#|react|vue|node(?:\.js)?|laravel|sql|regex|html|css|git|docker|kubernetes)\b/i',
        ],
        'math' => [
            '/^\s*[\d\.\s\+\-\*\/\(\)=xX^%]+\s*$/',
            '/\bwhat\s+is\s+\d+\s*[\+\-\*xX\/]\s*\d+\b/i',
            '/\b(?:solve|calculate|compute)\s+\d+\s*[\+\-\*xX\/]\s*\d+\b/i',
            '/\b(algebra|calculus|derivative|integral|equation|trigonometry)\b/i',
        ],
        'homework' => [
            '/\b(homework|assignment|exam|quiz|test\s+question|school\s+project)\b/i',
        ],
    ];

    public function blockedCategory(Client $client, string $message): ?string
    {
        $clean = trim($message);
        if ($clean === '') {
            return null;
        }

        foreach (self::BLOCK_PATTERNS as $category => $patterns) {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $clean) === 1) {
                    if ($category === 'coding' && $this->clientIsTechBusiness($client)) {
                        return null;
                    }

                    return $category;
                }
            }
        }

        return null;
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

    private function clientIsTechBusiness(Client $client): bool
    {
        $scope = trim(implode(' ', [
            (string) $client->name,
            (string) ($client->business_description ?? ''),
            (string) ($client->website_url ?? ''),
        ]));

        if ($scope === '') {
            return false;
        }

        foreach (self::TECH_BUSINESS_PATTERNS as $pattern) {
            if (preg_match($pattern, $scope) === 1) {
                return true;
            }
        }

        return false;
    }
}

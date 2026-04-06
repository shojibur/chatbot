<?php

namespace App\Services;

class IntentDetectionService
{
    /**
     * Keyword patterns that signal buying or contact intent.
     * Grouped by category for easy maintenance.
     */
    private const INTENT_PATTERNS = [
        // Price / quote
        'how much',
        'what is the price',
        'pricing',
        'how much does',
        'what does it cost',
        'cost of',
        'quote',
        'get a quote',
        'price list',
        'rate',
        'rates',
        'do you charge',
        'fee',
        'fees',
        'package',
        'packages',
        'plan',
        'plans',

        // Availability
        'are you available',
        'availability',
        'when can',
        'do you do',
        'can you do',
        'do you offer',
        'do you provide',
        'do you have',
        'do you sell',

        // Service / help / contact request
        'i need',
        'i want',
        'looking for',
        'interested in',
        'can someone contact',
        'can someone call',
        'contact me',
        'contact you',
        'how can i contact',
        'how to contact',
        'call me',
        'reach you',
        'get in touch',
        'phone number',
        'i need help',
        'can you help',
        'help me with',
        'i would like',
        'i am looking',
        'i\'m looking',
        'i\'m interested',

        // Purchase intent
        'buy',
        'purchase',
        'order',
        'sign up',
        'get started',
        'book',
        'schedule',
        'appointment',
        'hire',
        'hire you',
    ];

    /**
     * Returns true if the message contains any buying/contact intent keyword.
     */
    public function hasIntent(string $message): bool
    {
        $lower = mb_strtolower($message);

        foreach (self::INTENT_PATTERNS as $pattern) {
            if (str_contains($lower, $pattern)) {
                return true;
            }
        }

        return false;
    }
}

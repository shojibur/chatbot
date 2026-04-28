<?php

use App\Services\AiClientFactory;
use App\Services\AiModelCatalog;
use App\Services\IntentDetectionService;

it('captures explicit human contact intent without relying on ai classification', function () {
    $service = new IntentDetectionService(
        Mockery::mock(AiClientFactory::class),
        Mockery::mock(AiModelCatalog::class),
    );

    $decision = $service->detectLeadCapture(
        'Can someone contact me about pricing?',
        'We offer several packages depending on your needs.'
    );

    expect($decision)->toBe([
        'capture' => true,
        'trigger' => 'intent',
    ]);
});

it('captures when the bot explicitly asks for name and contact details', function () {
    $service = new IntentDetectionService(
        Mockery::mock(AiClientFactory::class),
        Mockery::mock(AiModelCatalog::class),
    );

    $decision = $service->detectLeadCapture(
        'Tell me more about your lead generation service.',
        'I would love to show you how I can help. What is your name and the best phone number to reach you at?'
    );

    expect($decision)->toBe([
        'capture' => true,
        'trigger' => 'ai',
    ]);
});

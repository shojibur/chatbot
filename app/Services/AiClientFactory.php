<?php

namespace App\Services;

use GuzzleHttp\Client as GuzzleClient;
use OpenAI;
use OpenAI\Client;
use OpenAI\Contracts\ClientContract;
use RuntimeException;

class AiClientFactory
{
    public function make(): ClientContract
    {
        $provider = (string) config('ai.provider', 'openai');

        if ($provider !== 'openrouter') {
            /** @var ClientContract $client */
            $client = app(ClientContract::class);

            return $client;
        }

        $apiKey = config('ai.openrouter.api_key');

        if (! is_string($apiKey) || trim($apiKey) === '') {
            throw new RuntimeException('OPENROUTER_API_KEY is missing while AI_PROVIDER is set to openrouter.');
        }

        $factory = OpenAI::factory()
            ->withApiKey($apiKey)
            ->withBaseUri((string) config('ai.openrouter.base_uri', 'https://openrouter.ai/api/v1'))
            ->withHttpClient(new GuzzleClient([
                'timeout' => (int) config('ai.request_timeout', 30),
            ]));

        $siteUrl = config('ai.openrouter.site_url');
        if (is_string($siteUrl) && trim($siteUrl) !== '') {
            $factory->withHttpHeader('HTTP-Referer', $siteUrl);
        }

        $appName = config('ai.openrouter.app_name');
        if (is_string($appName) && trim($appName) !== '') {
            $factory->withHttpHeader('X-Title', $appName);
        }

        /** @var Client $client */
        $client = $factory->make();

        return $client;
    }
}

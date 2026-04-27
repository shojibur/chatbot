<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Provider
    |--------------------------------------------------------------------------
    |
    | "openai" uses the standard OpenAI Laravel client configuration.
    | "openrouter" reuses the same SDK against OpenRouter's OpenAI-compatible
    | API and adds the recommended attribution headers.
    |
    */

    'provider' => env('AI_PROVIDER', 'openai'),

    /*
    |--------------------------------------------------------------------------
    | AI Request Timeout
    |--------------------------------------------------------------------------
    */

    'request_timeout' => env('AI_REQUEST_TIMEOUT', env('OPENAI_REQUEST_TIMEOUT', 30)),

    /*
    |--------------------------------------------------------------------------
    | OpenRouter
    |--------------------------------------------------------------------------
    */

    'openrouter' => [
        'api_key' => env('OPENROUTER_API_KEY'),
        'base_uri' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
        'site_url' => env('OPENROUTER_SITE_URL', env('APP_URL')),
        'app_name' => env('OPENROUTER_APP_NAME', env('APP_NAME')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Defaults And Suggestions
    |--------------------------------------------------------------------------
    */

    'models' => [
        'default_chat' => env('AI_DEFAULT_CHAT_MODEL', 'gpt-4o-mini'),
        'default_embedding' => env('AI_DEFAULT_EMBEDDING_MODEL', 'text-embedding-3-small'),
        'intent_classifier' => env('AI_INTENT_MODEL', env('AI_DEFAULT_CHAT_MODEL', 'gpt-4o-mini')),

        'chat_suggestions' => [
            'openai/gpt-5',
            'openai/gpt-5-mini',
            'openai/gpt-5.1',
            'openai/gpt-5.1-chat',
            'openai/gpt-4.1',
            'openai/gpt-4.1-mini',
            'openai/gpt-4o',
            'openai/gpt-4o-mini',
            'anthropic/claude-opus-4.6',
            'anthropic/claude-sonnet-4.6',
            'anthropic/claude-3.5-haiku',
            'google/gemini-2.5-pro',
            'google/gemini-2.5-flash',
            'google/gemini-2.0-flash-001',
            'deepseek/deepseek-v3.2',
            'deepseek/deepseek-r1',
            'x-ai/grok-4',
            'meta-llama/llama-4-maverick',
            'qwen/qwen3-235b-a22b',
            'mistralai/mistral-large-2411',
            'moonshotai/kimi-k2',
            'gpt-4o-mini',
            'gpt-4o',
        ],

        /*
         * Keep embedding suggestions restricted to 1536-dimension models until
         * the pgvector schema supports multiple dimensions and re-embedding.
         */
        'embedding_suggestions' => [
            'text-embedding-3-small',
            'openai/text-embedding-3-small',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Usage Cost Estimates
    |--------------------------------------------------------------------------
    |
    | Static entries below are stored in USD per token so they match
    | OpenRouter's models API pricing format. Unknown OpenRouter models can be
    | priced dynamically from the models endpoint at runtime.
    |
    */

    'pricing' => [
        'chat' => [
            'gpt-4o-mini' => ['input' => 0.15 / 1_000_000, 'output' => 0.60 / 1_000_000],
            'gpt-4o' => ['input' => 2.50 / 1_000_000, 'output' => 10.00 / 1_000_000],
            'openai/gpt-4o-mini' => ['input' => 0.15 / 1_000_000, 'output' => 0.60 / 1_000_000],
            'openai/gpt-4o' => ['input' => 2.50 / 1_000_000, 'output' => 10.00 / 1_000_000],
        ],
        'embedding' => [
            'text-embedding-3-small' => 0.02 / 1_000_000,
            'text-embedding-3-large' => 0.13 / 1_000_000,
            'openai/text-embedding-3-small' => 0.02 / 1_000_000,
            'openai/text-embedding-3-large' => 0.13 / 1_000_000,
        ],
    ],
];

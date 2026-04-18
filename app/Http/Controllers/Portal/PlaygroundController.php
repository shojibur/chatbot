<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlaygroundController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $client = $request->user()->client;
        $client->load('plan');

        return Inertia::render('portal/Playground', [
            'client' => $this->transformClientWorkspace($client),
            'api_base_url' => url('/'),
        ]);
    }

    private function transformClientWorkspace($client): array
    {
        $settings = collect($client->widget_settings);

        return [
            'id' => $client->id,
            'name' => $client->name,
            'unique_code' => $client->unique_code,
            'contact_email' => $client->contact_email,
            'website_url' => $client->website_url,
            'business_description' => $client->business_description,
            'system_prompt' => $client->system_prompt,
            'chat_model' => $client->chat_model,
            'embedding_model' => $client->embedding_model,
            'retrieval_chunk_count' => $client->retrieval_chunk_count,
            'cache_ttl_hours' => $client->cache_ttl_hours,
            'prompt_caching_enabled' => $client->prompt_caching_enabled,
            'semantic_cache_enabled' => $client->semantic_cache_enabled,
            'allowed_domains' => $client->allowed_domains ?? [],
            'monthly_token_limit' => $client->monthly_token_limit,
            'status' => $client->status,
            'widget_style' => $client->widget_style,
            'plan' => $client->plan ? [
                'name' => $client->plan->name,
            ] : null,
            'widget_settings' => [
                'primary_color' => $settings->get('primary_color', '#111827'),
                'accent_color' => $settings->get('accent_color', '#0f766e'),
                'welcome_message' => $settings->get('welcome_message', 'Ask us anything.'),
                'toggle_text' => $settings->get('toggle_text', 'Ask anything about this business'),
                'position' => $settings->get('position', 'right'),
                'theme_mode' => $settings->get('theme_mode', 'system'),
                'show_branding' => (bool) $settings->get('show_branding', true),
            ],
        ];
    }
}

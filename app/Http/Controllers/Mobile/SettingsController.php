<?php

namespace App\Http\Controllers\Mobile;

use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsController extends MobileController
{
    public function show(Request $request): JsonResponse
    {
        $client = $this->currentClient($request);
        $client->load('plan');

        $periodStart = CarbonImmutable::now()->startOfMonth();
        $monthlyTokens = (int) $client->usageLogs()
            ->where('created_at', '>=', $periodStart)
            ->sum('total_tokens');

        $settings = collect($client->widget_settings);

        return response()->json([
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'status' => $client->status,
                'contact_email' => $client->contact_email,
                'website_url' => $client->website_url,
            ],
            'widget' => [
                'widget_style' => $client->widget_style ?? 'classic',
                'primary_color' => $settings->get('primary_color', '#111827'),
                'accent_color' => $settings->get('accent_color', '#0f766e'),
                'welcome_message' => $settings->get('welcome_message', 'Ask us anything.'),
                'toggle_text' => $settings->get('toggle_text', 'Ask anything about this business'),
                'position' => $settings->get('position', 'right'),
                'theme_mode' => $settings->get('theme_mode', 'system'),
                'show_branding' => (bool) $settings->get('show_branding', true),
                'default_expanded' => (bool) $settings->get('default_expanded', true),
            ],
            'subscription' => [
                'plan' => $this->transformPlan($client->plan),
                'usage' => [
                    'monthly_tokens' => $monthlyTokens,
                    'knowledge_sources' => $client->knowledgeSources()->count(),
                ],
                'limits' => $client->limits(),
            ],
        ]);
    }

    public function updateWidget(Request $request): JsonResponse
    {
        $client = $this->currentClient($request);

        $validated = $request->validate([
            'widget_style' => ['required', 'in:' . implode(',', \App\Models\Client::WIDGET_STYLES)],
            'primary_color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'accent_color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'welcome_message' => ['required', 'string', 'max:300'],
            'toggle_text' => ['required', 'string', 'max:150'],
            'position' => ['required', 'in:' . implode(',', \App\Models\Client::WIDGET_POSITIONS)],
            'theme_mode' => ['required', 'in:' . implode(',', \App\Models\Client::WIDGET_THEME_MODES)],
            'show_branding' => ['required', 'boolean'],
            'default_expanded' => ['required', 'boolean'],
        ]);

        $existingSettings = collect($client->widget_settings ?? [])->toArray();
        $widgetSettings = array_merge($existingSettings, [
            'primary_color' => $validated['primary_color'],
            'accent_color' => $validated['accent_color'],
            'welcome_message' => $validated['welcome_message'],
            'toggle_text' => $validated['toggle_text'],
            'position' => $validated['position'],
            'theme_mode' => $validated['theme_mode'],
            'show_branding' => (bool) $validated['show_branding'],
            'default_expanded' => (bool) $validated['default_expanded'],
        ]);

        $client->update([
            'widget_style' => $validated['widget_style'],
            'widget_settings' => $widgetSettings,
        ]);

        return $this->show($request);
    }
}

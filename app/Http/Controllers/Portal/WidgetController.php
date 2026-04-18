<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WidgetController extends Controller
{
    public function edit(Request $request): Response
    {
        $client   = $request->user()->client;
        $settings = collect($client->widget_settings);

        return Inertia::render('portal/Widget', [
            'widget_styles'    => Client::WIDGET_STYLES,
            'widget_positions' => Client::WIDGET_POSITIONS,
            'form' => [
                'widget_style'    => $client->widget_style ?? 'classic',
                'primary_color'   => $settings->get('primary_color', '#111827'),
                'accent_color'    => $settings->get('accent_color', '#0f766e'),
                'welcome_message' => $settings->get('welcome_message', 'Ask us anything.'),
                'toggle_text'     => $settings->get('toggle_text', 'Ask anything about this business'),
                'position'        => $settings->get('position', 'right'),
                'show_branding'   => (bool) $settings->get('show_branding', true),
            ],
            'status' => $request->session()->get('status'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $client = $request->user()->client;

        $validated = $request->validate([
            'widget_style'    => ['required', 'in:' . implode(',', Client::WIDGET_STYLES)],
            'primary_color'   => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'accent_color'    => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'welcome_message' => ['required', 'string', 'max:300'],
            'toggle_text'     => ['required', 'string', 'max:150'],
            'position'        => ['required', 'in:' . implode(',', Client::WIDGET_POSITIONS)],
            'show_branding'   => ['boolean'],
        ]);

        $client->update([
            'widget_style'    => $validated['widget_style'],
            'widget_settings' => [
                'primary_color'   => $validated['primary_color'],
                'accent_color'    => $validated['accent_color'],
                'welcome_message' => $validated['welcome_message'],
                'toggle_text'     => $validated['toggle_text'],
                'position'        => $validated['position'],
                'show_branding'   => (bool) ($validated['show_branding'] ?? false),
            ],
        ]);

        return back()->with('status', 'widget-updated');
    }
}

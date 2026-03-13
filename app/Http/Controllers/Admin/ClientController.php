<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreClientRequest;
use App\Http\Requests\Admin\UpdateClientRequest;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    /**
     * Show the admin dashboard for tenant management.
     */
    public function index(Request $request): Response
    {
        $clients = Client::query()
            ->latest()
            ->get()
            ->map(fn (Client $client): array => $this->transformClient($client));

        return Inertia::render('Dashboard', [
            'clients' => $clients,
            'summary' => [
                'total_clients' => $clients->count(),
                'active_clients' => $clients->where('status', 'active')->count(),
                'paused_clients' => $clients->where('status', 'paused')->count(),
                'monthly_token_capacity' => $clients->sum('monthly_token_limit'),
            ],
            'status' => $request->session()->get('status'),
            'widget_script_url' => url('/widget.js'),
            'widget_styles' => Client::WIDGET_STYLES,
            'client_statuses' => Client::STATUSES,
            'widget_positions' => Client::WIDGET_POSITIONS,
        ]);
    }

    /**
     * Store a newly created client.
     */
    public function store(StoreClientRequest $request): RedirectResponse
    {
        Client::create([
            ...$this->payload($request),
            'unique_code' => (string) Str::uuid(),
        ]);

        return to_route('dashboard')->with('status', 'client-created');
    }

    /**
     * Update the specified client.
     */
    public function update(UpdateClientRequest $request, Client $client): RedirectResponse
    {
        $client->update($this->payload($request));

        return to_route('dashboard')->with('status', 'client-updated');
    }

    /**
     * Transform client form data into a persistable payload.
     *
     * @return array<string, mixed>
     */
    private function payload(StoreClientRequest|UpdateClientRequest $request): array
    {
        $validated = $request->validated();

        return [
            'name' => $validated['name'],
            'contact_email' => $validated['contact_email'],
            'website_url' => $validated['website_url'],
            'monthly_token_limit' => $validated['monthly_token_limit'],
            'status' => $validated['status'],
            'widget_style' => $validated['widget_style'],
            'widget_settings' => [
                'primary_color' => $validated['primary_color'],
                'accent_color' => $validated['accent_color'],
                'welcome_message' => $validated['welcome_message'],
                'position' => $validated['position'],
                'show_branding' => $validated['show_branding'],
            ],
            'notes' => $validated['notes'],
        ];
    }

    /**
     * Format a client for the admin dashboard payload.
     *
     * @return array<string, mixed>
     */
    private function transformClient(Client $client): array
    {
        $settings = Collection::make($client->widget_settings);

        return [
            'id' => $client->id,
            'name' => $client->name,
            'unique_code' => $client->unique_code,
            'contact_email' => $client->contact_email,
            'website_url' => $client->website_url,
            'monthly_token_limit' => $client->monthly_token_limit,
            'status' => $client->status,
            'widget_style' => $client->widget_style,
            'notes' => $client->notes,
            'widget_settings' => [
                'primary_color' => $settings->get('primary_color', '#111827'),
                'accent_color' => $settings->get('accent_color', '#0f766e'),
                'welcome_message' => $settings->get('welcome_message', 'Ask us anything.'),
                'position' => $settings->get('position', 'right'),
                'show_branding' => (bool) $settings->get('show_branding', true),
            ],
            'created_at' => $client->created_at?->toDateTimeString(),
        ];
    }
}

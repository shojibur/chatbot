<?php

use App\Models\Client;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('admin dashboard renders client data', function () {
    $this->withoutVite();

    $user = User::factory()->create();

    Client::factory()->create([
        'status' => 'active',
        'monthly_token_limit' => 250000,
    ]);

    Client::factory()->create([
        'status' => 'paused',
        'monthly_token_limit' => 100000,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('summary.total_clients', 2)
            ->where('summary.active_clients', 1)
            ->where('summary.paused_clients', 1)
            ->where('summary.monthly_token_capacity', 350000)
            ->has('clients', 2)
            ->where('widget_styles', Client::WIDGET_STYLES)
            ->where('client_statuses', Client::STATUSES)
            ->where('widget_positions', Client::WIDGET_POSITIONS),
        );
});

test('authenticated users can create a client', function () {
    $this->withoutVite();

    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('clients.store'), [
            'name' => 'Acme Dental',
            'contact_email' => 'ops@acme.test',
            'website_url' => 'https://acme.test',
            'monthly_token_limit' => 800000,
            'status' => 'active',
            'widget_style' => 'glass',
            'primary_color' => '#111827',
            'accent_color' => '#0f766e',
            'welcome_message' => 'Ask us anything about appointments.',
            'position' => 'right',
            'show_branding' => true,
            'notes' => 'Priority onboarding client.',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard'));

    $client = Client::query()->first();

    expect($client)->not->toBeNull();
    expect($client->name)->toBe('Acme Dental');
    expect($client->unique_code)->not->toBe('');
    expect($client->widget_settings)->toMatchArray([
        'primary_color' => '#111827',
        'accent_color' => '#0f766e',
        'welcome_message' => 'Ask us anything about appointments.',
        'position' => 'right',
        'show_branding' => true,
    ]);
});

test('authenticated users can update a client', function () {
    $this->withoutVite();

    $user = User::factory()->create();
    $client = Client::factory()->create([
        'name' => 'Acme Dental',
        'status' => 'draft',
        'widget_style' => 'classic',
        'widget_settings' => [
            'primary_color' => '#111827',
            'accent_color' => '#0f766e',
            'welcome_message' => 'Ask us anything.',
            'position' => 'right',
            'show_branding' => true,
        ],
    ]);

    $response = $this
        ->actingAs($user)
        ->patch(route('clients.update', $client), [
            'name' => 'Acme Dental Group',
            'contact_email' => 'hello@acme.test',
            'website_url' => 'https://acme.test',
            'monthly_token_limit' => 1200000,
            'status' => 'active',
            'widget_style' => 'modern',
            'primary_color' => '#1d4ed8',
            'accent_color' => '#f97316',
            'welcome_message' => 'Ask about plans, hours, or support.',
            'position' => 'left',
            'show_branding' => false,
            'notes' => 'Updated by admin.',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard'));

    $client->refresh();

    expect($client->name)->toBe('Acme Dental Group');
    expect($client->status)->toBe('active');
    expect($client->widget_style)->toBe('modern');
    expect($client->monthly_token_limit)->toBe(1200000);
    expect($client->widget_settings)->toMatchArray([
        'primary_color' => '#1d4ed8',
        'accent_color' => '#f97316',
        'welcome_message' => 'Ask about plans, hours, or support.',
        'position' => 'left',
        'show_branding' => false,
    ]);
});

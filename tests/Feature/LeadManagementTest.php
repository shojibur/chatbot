<?php

use App\Mail\NewLeadCaptured;
use App\Models\Client;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

it('queues email and sends sms for a lead when client sms alerts are enabled', function () {
    Config::set('services.twilio.sms_enabled', true);
    Config::set('services.twilio.account_sid', 'AC-test');
    Config::set('services.twilio.auth_token', 'auth-token');
    Config::set('services.twilio.sms_from', '+18666515581');
    Config::set('services.twilio.sms_timeout', 15);

    Http::fake([
        'https://api.twilio.com/*' => Http::response([
            'sid' => 'SM123',
            'status' => 'queued',
        ], 201),
    ]);

    Mail::fake();

    $client = Client::factory()->create([
        'status' => 'active',
        'contact_email' => 'owner@example.com',
        'lead_sms_enabled' => true,
        'lead_sms_to' => '+15551234567',
    ]);

    $response = $this->postJson('/api/v1/leads', [
        'client_code' => $client->unique_code,
        'name' => 'John Doe',
        'contact' => '0123456789',
        'user_request' => 'Please call me back.',
        'trigger' => 'ai',
    ]);

    $response
        ->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    Mail::assertQueued(NewLeadCaptured::class, function (NewLeadCaptured $mail) use ($client) {
        return $mail->lead->client_id === $client->id
            && $mail->hasTo('owner@example.com');
    });

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.twilio.com/2010-04-01/Accounts/AC-test/Messages.json'
            && $request['To'] === '+15551234567'
            && $request['From'] === '+18666515581'
            && str_contains((string) $request['Body'], 'John Doe');
    });
});

it('stores a lead even when notification queueing fails', function () {
    $client = Client::factory()->create([
        'status' => 'active',
        'contact_email' => 'owner@example.com',
        'lead_sms_enabled' => false,
    ]);

    Mail::shouldReceive('to')
        ->once()
        ->with('owner@example.com')
        ->andReturnSelf();

    Mail::shouldReceive('queue')
        ->once()
        ->with(\Mockery::type(NewLeadCaptured::class))
        ->andThrow(new \RuntimeException('Mail provider rejected the request.'));

    $response = $this->postJson('/api/v1/leads', [
        'client_code' => $client->unique_code,
        'name' => 'John Doe',
        'contact' => '0123456789',
        'user_request' => 'Please call me back.',
        'trigger' => 'ai',
    ]);

    $response
        ->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    $this->assertDatabaseHas('leads', [
        'client_id' => $client->id,
        'name' => 'John Doe',
        'contact' => '0123456789',
        'trigger' => 'ai',
    ]);
});

it('stores a lead and logs an error when twilio rejects the sms request', function () {
    Config::set('services.twilio.sms_enabled', true);
    Config::set('services.twilio.account_sid', 'AC-test');
    Config::set('services.twilio.auth_token', 'auth-token');
    Config::set('services.twilio.sms_from', '+18666515581');
    Config::set('services.twilio.sms_timeout', 15);

    Http::fake([
        'https://api.twilio.com/*' => Http::response([
            'code' => 21408,
            'message' => 'Permission to send an SMS has not been enabled for the region indicated by the To number.',
        ], 400),
    ]);

    Log::spy();
    Mail::fake();

    $client = Client::factory()->create([
        'status' => 'active',
        'contact_email' => 'owner@example.com',
        'lead_sms_enabled' => true,
        'lead_sms_to' => '+15551234567',
    ]);

    $response = $this->postJson('/api/v1/leads', [
        'client_code' => $client->unique_code,
        'name' => 'John Doe',
        'contact' => '0123456789',
        'user_request' => 'Please call me back.',
        'trigger' => 'ai',
    ]);

    $response
        ->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    $this->assertDatabaseHas('leads', [
        'client_id' => $client->id,
        'name' => 'John Doe',
    ]);

    Log::shouldHaveReceived('error')->once()->withArgs(function (string $message, array $context) use ($client) {
        return $message === 'Lead SMS notification failed.'
            && $context['client_id'] === $client->id
            && $context['twilio_status'] === 400;
    });
});

it('allows an admin to delete a lead', function () {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->create();
    $lead = Lead::create([
        'client_id' => $client->id,
        'name' => 'Jane Doe',
        'contact' => 'jane@example.com',
        'trigger' => 'manual',
        'status' => 'new',
    ]);

    $response = $this
        ->actingAs($admin)
        ->delete(route('leads.destroy', $lead));

    $response->assertRedirect(route('leads.index'));

    $this->assertDatabaseMissing('leads', [
        'id' => $lead->id,
    ]);
});

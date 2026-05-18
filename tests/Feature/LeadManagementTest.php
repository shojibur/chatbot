<?php

use App\Mail\NewLeadCaptured;
use App\Models\Client;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

it('queues email for a lead', function () {
    Mail::fake();

    $client = Client::factory()->create([
        'status' => 'active',
        'contact_email' => 'owner@example.com',
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

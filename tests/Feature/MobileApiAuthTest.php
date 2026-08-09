<?php

use App\Models\Client;
use App\Models\User;

it('allows a client user to log in through the mobile api and fetch me', function () {
    $client = Client::factory()->create([
        'status' => 'active',
        'name' => 'Zao Demo Client',
    ]);

    $user = User::factory()->client()->create([
        'client_id' => $client->id,
        'email' => 'client@example.com',
        'password' => 'password',
    ]);

    $login = $this->postJson('/api/mobile/auth/login', [
        'email' => $user->email,
        'password' => 'password',
        'device_name' => 'pest-suite',
    ]);

    $login
        ->assertOk()
        ->assertJsonStructure([
            'token',
            'user' => ['id', 'name', 'email', 'client_id', 'client_name'],
        ]);

    $token = $login->json('token');

    $this->assertDatabaseHas('personal_access_tokens', [
        'tokenable_id' => $user->id,
        'tokenable_type' => User::class,
        'name' => 'pest-suite',
    ]);

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/mobile/me')
        ->assertOk()
        ->assertJsonPath('client.name', 'Zao Demo Client')
        ->assertJsonPath('user.email', 'client@example.com');
});

it('rejects an admin user on the mobile login endpoint', function () {
    $admin = User::factory()->admin()->create([
        'email' => 'admin@example.com',
        'password' => 'password',
    ]);

    $response = $this->postJson('/api/mobile/auth/login', [
        'email' => $admin->email,
        'password' => 'password',
        'device_name' => 'pest-suite',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

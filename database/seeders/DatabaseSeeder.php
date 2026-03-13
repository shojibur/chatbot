<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $clients = [
            [
                'name' => 'Bright Dental',
                'unique_code' => '08c055af-f26d-4988-b95f-a98c3d938801',
                'contact_email' => 'owner@brightdental.test',
                'website_url' => 'https://brightdental.test',
                'monthly_token_limit' => 800000,
                'status' => 'active',
                'widget_style' => 'classic',
                'widget_settings' => [
                    'primary_color' => '#111827',
                    'accent_color' => '#0f766e',
                    'welcome_message' => 'Ask us anything about appointments, pricing, or support.',
                    'position' => 'right',
                    'show_branding' => true,
                ],
                'notes' => 'Demo healthcare client for onboarding flows.',
                'user' => [
                    'name' => 'Bright Dental Owner',
                    'email' => 'owner@brightdental.test',
                ],
            ],
            [
                'name' => 'Northwind Legal',
                'unique_code' => '2a973d38-f68a-4528-b285-9234ccde5b12',
                'contact_email' => 'ops@northwindlegal.test',
                'website_url' => 'https://northwindlegal.test',
                'monthly_token_limit' => 1200000,
                'status' => 'active',
                'widget_style' => 'modern',
                'widget_settings' => [
                    'primary_color' => '#1d4ed8',
                    'accent_color' => '#f97316',
                    'welcome_message' => 'Ask about services, documents, or consultation hours.',
                    'position' => 'left',
                    'show_branding' => false,
                ],
                'notes' => 'Demo professional services client with branding hidden.',
                'user' => [
                    'name' => 'Northwind Legal Manager',
                    'email' => 'ops@northwindlegal.test',
                ],
            ],
            [
                'name' => 'Atlas Auto Spa',
                'unique_code' => 'fdba37ba-401e-4c7c-92fc-bf8a6c2b34d9',
                'contact_email' => 'hello@atlasautospa.test',
                'website_url' => 'https://atlasautospa.test',
                'monthly_token_limit' => 450000,
                'status' => 'draft',
                'widget_style' => 'glass',
                'widget_settings' => [
                    'primary_color' => '#7c3aed',
                    'accent_color' => '#06b6d4',
                    'welcome_message' => 'Ask about packages, openings, and membership plans.',
                    'position' => 'right',
                    'show_branding' => true,
                ],
                'notes' => 'Draft demo client for premium widget styling.',
                'user' => [
                    'name' => 'Atlas Auto Spa Team',
                    'email' => 'hello@atlasautospa.test',
                ],
            ],
        ];

        User::query()->updateOrCreate(
            ['email' => 'admin@chatbot.test'],
            [
                'name' => 'Platform Admin',
                'user_type' => User::TYPE_ADMIN,
                'email_verified_at' => now(),
                'password' => 'password',
            ],
        );

        foreach ($clients as $clientData) {
            Client::query()->updateOrCreate(
                ['unique_code' => $clientData['unique_code']],
                [
                    'name' => $clientData['name'],
                    'contact_email' => $clientData['contact_email'],
                    'website_url' => $clientData['website_url'],
                    'monthly_token_limit' => $clientData['monthly_token_limit'],
                    'status' => $clientData['status'],
                    'widget_style' => $clientData['widget_style'],
                    'widget_settings' => $clientData['widget_settings'],
                    'notes' => $clientData['notes'],
                ],
            );

            User::query()->updateOrCreate(
                ['email' => $clientData['user']['email']],
                [
                    'name' => $clientData['user']['name'],
                    'user_type' => User::TYPE_CLIENT,
                    'email_verified_at' => now(),
                    'password' => 'password',
                ],
            );
        }
    }
}

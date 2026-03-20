<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\KnowledgeSource;
use App\Models\Plan;
use App\Models\UsageLog;
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
        $plans = [
            [
                'name' => 'Free',
                'slug' => 'free',
                'description' => 'Entry package for early testing and low-volume support.',
                'price_monthly' => 0,
                'monthly_token_limit' => 100000,
                'monthly_message_limit' => 200,
                'max_knowledge_sources' => 5,
                'max_file_upload_mb' => 10,
                'features' => [
                    '1 website widget',
                    'Manual text and file sources',
                    'Basic usage reporting',
                ],
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'description' => 'Balanced production package for growing businesses.',
                'price_monthly' => 29,
                'monthly_token_limit' => 1000000,
                'monthly_message_limit' => 3000,
                'max_knowledge_sources' => 25,
                'max_file_upload_mb' => 25,
                'features' => [
                    'Prompt caching and semantic cache',
                    'Website + file knowledge sources',
                    'Usage history and billing controls',
                ],
            ],
            [
                'name' => 'Ultra Pro',
                'slug' => 'ultrapro',
                'description' => 'High-volume package with larger knowledge and token allowances.',
                'price_monthly' => 99,
                'monthly_token_limit' => 5000000,
                'monthly_message_limit' => 15000,
                'max_knowledge_sources' => 100,
                'max_file_upload_mb' => 100,
                'features' => [
                    'Higher token capacity',
                    'Large file allowance',
                    'Priority ingestion workflows',
                ],
            ],
        ];

        $planMap = collect($plans)->mapWithKeys(function (array $planData): array {
            $plan = Plan::query()->updateOrCreate(
                ['slug' => $planData['slug']],
                [
                    'name' => $planData['name'],
                    'description' => $planData['description'],
                    'price_monthly' => $planData['price_monthly'],
                    'monthly_token_limit' => $planData['monthly_token_limit'],
                    'monthly_message_limit' => $planData['monthly_message_limit'],
                    'max_knowledge_sources' => $planData['max_knowledge_sources'],
                    'max_file_upload_mb' => $planData['max_file_upload_mb'],
                    'features' => $planData['features'],
                    'is_active' => true,
                ],
            );

            return [$planData['slug'] => $plan];
        });

        $clients = [
            [
                'name' => 'Bright Dental',
                'plan_slug' => 'pro',
                'unique_code' => '08c055af-f26d-4988-b95f-a98c3d938801',
                'contact_email' => 'owner@brightdental.test',
                'website_url' => 'https://brightdental.test',
                'business_description' => 'Dental clinic focused on appointments, pricing, and insurance questions.',
                'system_prompt' => 'Only answer from the approved Bright Dental knowledge base. If details are missing, ask the visitor to contact the clinic directly.',
                'chat_model' => 'gpt-4o-mini',
                'embedding_model' => 'text-embedding-3-small',
                'retrieval_chunk_count' => 3,
                'cache_ttl_hours' => 24,
                'prompt_caching_enabled' => true,
                'semantic_cache_enabled' => true,
                'allowed_domains' => ['brightdental.test'],
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
                'knowledge_sources' => [
                    [
                        'title' => 'Appointment policies',
                        'source_type' => 'manual',
                        'status' => 'ready',
                        'content' => 'Appointments can be booked online or by phone. Cancellations should happen 24 hours in advance. Emergency slots are limited.',
                        'token_estimate' => 34,
                        'chunk_count' => 1,
                    ],
                    [
                        'title' => 'New patient PDF',
                        'source_type' => 'file',
                        'status' => 'queued',
                        'file_name' => 'bright-dental-new-patient.pdf',
                        'token_estimate' => 0,
                        'chunk_count' => 0,
                    ],
                ],
                'usage_logs' => [
                    [
                        'interaction_type' => 'chat',
                        'model' => 'gpt-4o-mini',
                        'prompt_tokens' => 680,
                        'completion_tokens' => 140,
                        'cached_input_tokens' => 320,
                        'total_tokens' => 820,
                        'estimated_cost' => 0.0014,
                        'request_excerpt' => 'What time do you open on Saturdays?',
                    ],
                    [
                        'interaction_type' => 'embedding',
                        'model' => 'text-embedding-3-small',
                        'prompt_tokens' => 0,
                        'completion_tokens' => 0,
                        'cached_input_tokens' => 0,
                        'total_tokens' => 1200,
                        'estimated_cost' => 0.0001,
                        'request_excerpt' => 'Embedded uploaded appointment policy document.',
                    ],
                ],
            ],
            [
                'name' => 'Northwind Legal',
                'plan_slug' => 'ultrapro',
                'unique_code' => '2a973d38-f68a-4528-b285-9234ccde5b12',
                'contact_email' => 'ops@northwindlegal.test',
                'website_url' => 'https://northwindlegal.test',
                'business_description' => 'Law practice using the bot for consultation, service, and intake questions.',
                'system_prompt' => 'Use only the firm knowledge base and avoid giving legal advice beyond the supplied service information.',
                'chat_model' => 'gpt-4o-mini',
                'embedding_model' => 'text-embedding-3-small',
                'retrieval_chunk_count' => 4,
                'cache_ttl_hours' => 72,
                'prompt_caching_enabled' => true,
                'semantic_cache_enabled' => true,
                'allowed_domains' => ['northwindlegal.test', 'www.northwindlegal.test'],
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
                'knowledge_sources' => [
                    [
                        'title' => 'Practice areas',
                        'source_type' => 'manual',
                        'status' => 'ready',
                        'content' => 'The firm handles immigration, family law, and small business compliance. Consultation requests are reviewed within one business day.',
                        'token_estimate' => 39,
                        'chunk_count' => 1,
                    ],
                    [
                        'title' => 'FAQ website crawl',
                        'source_type' => 'url',
                        'status' => 'queued',
                        'source_url' => 'https://northwindlegal.test/faq',
                        'token_estimate' => 0,
                        'chunk_count' => 0,
                    ],
                ],
                'usage_logs' => [
                    [
                        'interaction_type' => 'chat',
                        'model' => 'gpt-4o-mini',
                        'prompt_tokens' => 890,
                        'completion_tokens' => 210,
                        'cached_input_tokens' => 510,
                        'total_tokens' => 1100,
                        'estimated_cost' => 0.0018,
                        'request_excerpt' => 'How quickly can I schedule an intake consultation?',
                    ],
                    [
                        'interaction_type' => 'cache_hit',
                        'model' => 'gpt-4o-mini',
                        'prompt_tokens' => 0,
                        'completion_tokens' => 0,
                        'cached_input_tokens' => 740,
                        'total_tokens' => 0,
                        'estimated_cost' => 0,
                        'request_excerpt' => 'Returned cached answer for office hours question.',
                    ],
                ],
            ],
            [
                'name' => 'Atlas Auto Spa',
                'plan_slug' => 'free',
                'unique_code' => 'fdba37ba-401e-4c7c-92fc-bf8a6c2b34d9',
                'contact_email' => 'hello@atlasautospa.test',
                'website_url' => 'https://atlasautospa.test',
                'business_description' => 'Car detailing and membership service preparing chatbot launch content.',
                'system_prompt' => 'Answer with simple sales and booking language using only the approved Atlas Auto Spa knowledge.',
                'chat_model' => 'gpt-4o-mini',
                'embedding_model' => 'text-embedding-3-small',
                'retrieval_chunk_count' => 3,
                'cache_ttl_hours' => 24,
                'prompt_caching_enabled' => true,
                'semantic_cache_enabled' => false,
                'allowed_domains' => ['atlasautospa.test'],
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
                'knowledge_sources' => [
                    [
                        'title' => 'Service packages',
                        'source_type' => 'manual',
                        'status' => 'draft',
                        'content' => 'Basic wash, deluxe detail, ceramic coating, and monthly membership packages are offered.',
                        'token_estimate' => 24,
                        'chunk_count' => 1,
                    ],
                ],
                'usage_logs' => [
                    [
                        'interaction_type' => 'chat',
                        'model' => 'gpt-4o-mini',
                        'prompt_tokens' => 250,
                        'completion_tokens' => 60,
                        'cached_input_tokens' => 0,
                        'total_tokens' => 310,
                        'estimated_cost' => 0.0005,
                        'request_excerpt' => 'Do you offer ceramic coating packages?',
                    ],
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
            $client = Client::query()->updateOrCreate(
                ['unique_code' => $clientData['unique_code']],
                [
                    'plan_id' => $planMap[$clientData['plan_slug']]->id,
                    'name' => $clientData['name'],
                    'contact_email' => $clientData['contact_email'],
                    'website_url' => $clientData['website_url'],
                    'business_description' => $clientData['business_description'],
                    'system_prompt' => $clientData['system_prompt'],
                    'chat_model' => $clientData['chat_model'],
                    'embedding_model' => $clientData['embedding_model'],
                    'retrieval_chunk_count' => $clientData['retrieval_chunk_count'],
                    'cache_ttl_hours' => $clientData['cache_ttl_hours'],
                    'prompt_caching_enabled' => $clientData['prompt_caching_enabled'],
                    'semantic_cache_enabled' => $clientData['semantic_cache_enabled'],
                    'allowed_domains' => $clientData['allowed_domains'],
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

            foreach ($clientData['knowledge_sources'] as $source) {
                KnowledgeSource::query()->updateOrCreate(
                    [
                        'client_id' => $client->id,
                        'title' => $source['title'],
                    ],
                    [
                        'source_type' => $source['source_type'],
                        'status' => $source['status'],
                        'source_url' => $source['source_url'] ?? null,
                        'file_name' => $source['file_name'] ?? null,
                        'content' => $source['content'] ?? null,
                        'token_estimate' => $source['token_estimate'],
                        'chunk_count' => $source['chunk_count'],
                        'last_synced_at' => now(),
                    ],
                );
            }

            foreach ($clientData['usage_logs'] as $usageLog) {
                UsageLog::query()->updateOrCreate(
                    [
                        'client_id' => $client->id,
                        'interaction_type' => $usageLog['interaction_type'],
                        'request_excerpt' => $usageLog['request_excerpt'],
                    ],
                    [
                        'model' => $usageLog['model'],
                        'prompt_tokens' => $usageLog['prompt_tokens'],
                        'completion_tokens' => $usageLog['completion_tokens'],
                        'cached_input_tokens' => $usageLog['cached_input_tokens'],
                        'total_tokens' => $usageLog['total_tokens'],
                        'estimated_cost' => $usageLog['estimated_cost'],
                    ],
                );
            }
        }
    }
}

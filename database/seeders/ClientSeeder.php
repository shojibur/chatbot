<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\KnowledgeSource;
use App\Models\Plan;
use App\Models\UsageLog;
use App\Models\User;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        $planMap = Plan::query()->pluck('id', 'slug');

        foreach ($this->clients() as $clientData) {
            $client = Client::query()->updateOrCreate(
                ['unique_code' => $clientData['unique_code']],
                [
                    'plan_id' => $planMap[$clientData['plan_slug']],
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

    /**
     * @return array<int, array<string, mixed>>
     */
    private function clients(): array
    {
        return [
            [
                'name' => 'The Hawaii Agency',
                'plan_slug' => 'pro',
                'unique_code' => '08c055af-f26d-4988-b95f-a98c3d938801',
                'contact_email' => 'info@thehawaiiagency.com',
                'website_url' => 'https://thehawaiiagency.com',
                'business_description' => 'Full-service digital agency in Hawaii offering web design, SEO, Google Ads, branding, ecommerce, app development, and more.',
                'system_prompt' => 'You are the friendly virtual assistant for The Hawaii Agency. Answer questions about our services, pricing, and expertise using the approved knowledge base. Be helpful and conversational. If specific details are missing, encourage the visitor to call (808) 437-5445 or visit our contact page.',
                'chat_model' => 'gpt-4o-mini',
                'embedding_model' => 'text-embedding-3-small',
                'retrieval_chunk_count' => 3,
                'cache_ttl_hours' => 24,
                'prompt_caching_enabled' => true,
                'semantic_cache_enabled' => true,
                'allowed_domains' => ['thehawaiiagency.com', 'www.thehawaiiagency.com'],
                'monthly_token_limit' => 800000,
                'status' => 'active',
                'widget_style' => 'classic',
                'widget_settings' => [
                    'primary_color' => '#111827',
                    'accent_color' => '#0f766e',
                    'welcome_message' => 'Aloha! Ask us about web design, SEO, branding, or any of our digital services.',
                    'position' => 'right',
                    'show_branding' => true,
                ],
                'notes' => 'Demo agency client with real website content.',
                'user' => [
                    'name' => 'Hawaii Agency Team',
                    'email' => 'info@thehawaiiagency.com',
                ],
                'knowledge_sources' => [
                    [
                        'title' => 'Homepage',
                        'source_type' => 'url',
                        'status' => 'queued',
                        'source_url' => 'https://thehawaiiagency.com/',
                        'token_estimate' => 0,
                        'chunk_count' => 0,
                    ],
                    [
                        'title' => 'Case Studies',
                        'source_type' => 'url',
                        'status' => 'queued',
                        'source_url' => 'https://thehawaiiagency.com/case-studies/',
                        'token_estimate' => 0,
                        'chunk_count' => 0,
                    ],
                    [
                        'title' => 'Website Hosting',
                        'source_type' => 'url',
                        'status' => 'queued',
                        'source_url' => 'https://thehawaiiagency.com/website-hosting/',
                        'token_estimate' => 0,
                        'chunk_count' => 0,
                    ],
                    [
                        'title' => 'Web Design',
                        'source_type' => 'url',
                        'status' => 'queued',
                        'source_url' => 'https://thehawaiiagency.com/web-design/',
                        'token_estimate' => 0,
                        'chunk_count' => 0,
                    ],
                    [
                        'title' => 'App Developer',
                        'source_type' => 'url',
                        'status' => 'queued',
                        'source_url' => 'https://thehawaiiagency.com/app-developer/',
                        'token_estimate' => 0,
                        'chunk_count' => 0,
                    ],
                    [
                        'title' => 'Google Ads',
                        'source_type' => 'url',
                        'status' => 'queued',
                        'source_url' => 'https://thehawaiiagency.com/google-ads/',
                        'token_estimate' => 0,
                        'chunk_count' => 0,
                    ],
                    [
                        'title' => 'SEO',
                        'source_type' => 'url',
                        'status' => 'queued',
                        'source_url' => 'https://thehawaiiagency.com/seo/',
                        'token_estimate' => 0,
                        'chunk_count' => 0,
                    ],
                    [
                        'title' => 'Branding',
                        'source_type' => 'url',
                        'status' => 'queued',
                        'source_url' => 'https://thehawaiiagency.com/branding/',
                        'token_estimate' => 0,
                        'chunk_count' => 0,
                    ],
                    [
                        'title' => 'Ecommerce',
                        'source_type' => 'url',
                        'status' => 'queued',
                        'source_url' => 'https://thehawaiiagency.com/ecommerce/',
                        'token_estimate' => 0,
                        'chunk_count' => 0,
                    ],
                    [
                        'title' => 'Software',
                        'source_type' => 'url',
                        'status' => 'queued',
                        'source_url' => 'https://thehawaiiagency.com/software/',
                        'token_estimate' => 0,
                        'chunk_count' => 0,
                    ],
                    [
                        'title' => 'UI/UX Design',
                        'source_type' => 'url',
                        'status' => 'queued',
                        'source_url' => 'https://thehawaiiagency.com/ui-ux/',
                        'token_estimate' => 0,
                        'chunk_count' => 0,
                    ],
                    [
                        'title' => 'Phone Systems',
                        'source_type' => 'url',
                        'status' => 'queued',
                        'source_url' => 'https://thehawaiiagency.com/phone-systems/',
                        'token_estimate' => 0,
                        'chunk_count' => 0,
                    ],
                    [
                        'title' => 'Contact',
                        'source_type' => 'url',
                        'status' => 'queued',
                        'source_url' => 'https://thehawaiiagency.com/contact/',
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
                        'request_excerpt' => 'What services do you offer?',
                    ],
                    [
                        'interaction_type' => 'embedding',
                        'model' => 'text-embedding-3-small',
                        'prompt_tokens' => 0,
                        'completion_tokens' => 0,
                        'cached_input_tokens' => 0,
                        'total_tokens' => 1200,
                        'estimated_cost' => 0.0001,
                        'request_excerpt' => 'Embedded website crawl content.',
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
    }
}

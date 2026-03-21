<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
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

        foreach ($plans as $planData) {
            Plan::query()->updateOrCreate(
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
        }
    }
}

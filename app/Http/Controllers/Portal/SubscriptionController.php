<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $client = $request->user()->client;
        $client->load('plan');

        $periodStart  = CarbonImmutable::now()->startOfMonth();
        $monthlyTokens = (int) $client->usageLogs()
            ->where('created_at', '>=', $periodStart)
            ->sum('total_tokens');

        $limits = $client->limits();

        return Inertia::render('portal/Subscription', [
            'plan' => $client->plan ? [
                'name'                  => $client->plan->name,
                'description'           => $client->plan->description,
                'price_monthly'         => (float) $client->plan->price_monthly,
                'monthly_token_limit'   => $client->plan->monthly_token_limit,
                'monthly_message_limit' => $client->plan->monthly_message_limit,
                'max_knowledge_sources' => $client->plan->max_knowledge_sources,
                'max_file_upload_mb'    => $client->plan->max_file_upload_mb,
                'features'              => $client->plan->features ?? [],
            ] : null,
            'usage' => [
                'monthly_tokens'     => $monthlyTokens,
                'knowledge_sources'  => $client->knowledgeSources()->count(),
            ],
            'limits'        => $limits,
            'client_status' => $client->status,
        ]);
    }
}

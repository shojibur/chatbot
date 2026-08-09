<?php

namespace App\Http\Controllers\Mobile;

use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends MobileController
{
    public function __invoke(Request $request): JsonResponse
    {
        $client = $this->currentClient($request);

        $periodStart = CarbonImmutable::now()->startOfMonth();
        $usageSummary = $client->usageLogs()
            ->where('created_at', '>=', $periodStart)
            ->selectRaw('COALESCE(SUM(total_tokens), 0) as total_tokens')
            ->selectRaw('COALESCE(SUM(cached_input_tokens), 0) as cached_tokens')
            ->selectRaw('COALESCE(SUM(estimated_cost), 0) as total_cost')
            ->selectRaw('COUNT(*) as request_count')
            ->first();

        return response()->json([
            'usage_summary' => [
                'current_period_tokens' => (int) $usageSummary->total_tokens,
                'current_period_cached_tokens' => (int) $usageSummary->cached_tokens,
                'current_period_cost' => (float) $usageSummary->total_cost,
                'current_period_requests' => (int) $usageSummary->request_count,
            ],
            'counts' => [
                'open_sessions' => $client->chatSessions()->where('session_token', 'not like', 'playground-%')->count(),
                'leads' => $client->leads()->count(),
                'knowledge_sources' => $client->knowledgeSources()->count(),
                'ready_knowledge_sources' => $client->knowledgeSources()->where('status', 'ready')->count(),
            ],
        ]);
    }
}

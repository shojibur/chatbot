<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\KnowledgeChunk;
use App\Models\KnowledgeSource;
use App\Models\UsageLog;
use Carbon\CarbonImmutable;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $periodStart = CarbonImmutable::now()->startOfMonth();
        $previousPeriodStart = CarbonImmutable::now()->subMonth()->startOfMonth();

        $totalClients = Client::count();
        $activeClients = Client::where('status', 'active')->count();
        $pausedClients = Client::where('status', 'paused')->count();
        $draftClients = Client::where('status', 'draft')->count();

        $totalKnowledgeSources = KnowledgeSource::count();
        $readySources = KnowledgeSource::where('status', 'ready')->count();
        $totalChunks = KnowledgeChunk::count();

        $currentMonthTokens = UsageLog::where('created_at', '>=', $periodStart)->sum('total_tokens');
        $currentMonthCost = (float) UsageLog::where('created_at', '>=', $periodStart)->sum('estimated_cost');
        $currentMonthRequests = UsageLog::where('created_at', '>=', $periodStart)->count();

        $previousMonthTokens = UsageLog::whereBetween('created_at', [$previousPeriodStart, $periodStart])->sum('total_tokens');
        $previousMonthCost = (float) UsageLog::whereBetween('created_at', [$previousPeriodStart, $periodStart])->sum('estimated_cost');
        $previousMonthRequests = UsageLog::whereBetween('created_at', [$previousPeriodStart, $periodStart])->count();

        $recentClients = Client::query()
            ->with('plan')
            ->withCount('knowledgeSources')
            ->withSum([
                'usageLogs as current_month_tokens' => fn ($query) => $query->where('created_at', '>=', $periodStart),
            ], 'total_tokens')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (Client $client) => [
                'id' => $client->id,
                'name' => $client->name,
                'status' => $client->status,
                'plan_name' => $client->plan?->name,
                'knowledge_sources_count' => $client->knowledge_sources_count ?? 0,
                'current_month_tokens' => (int) ($client->current_month_tokens ?? 0),
                'created_at' => $client->created_at?->toDateTimeString(),
            ]);

        $recentLogs = UsageLog::query()
            ->with('client:id,name')
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (UsageLog $log) => [
                'id' => $log->id,
                'client_name' => $log->client?->name ?? 'Unknown',
                'interaction_type' => $log->interaction_type,
                'total_tokens' => $log->total_tokens,
                'estimated_cost' => (float) $log->estimated_cost,
                'created_at' => $log->created_at?->toDateTimeString(),
            ]);

        return Inertia::render('Dashboard', [
            'stats' => [
                'total_clients' => $totalClients,
                'active_clients' => $activeClients,
                'paused_clients' => $pausedClients,
                'draft_clients' => $draftClients,
                'total_knowledge_sources' => $totalKnowledgeSources,
                'ready_sources' => $readySources,
                'total_chunks' => $totalChunks,
                'current_month_tokens' => (int) $currentMonthTokens,
                'current_month_cost' => $currentMonthCost,
                'current_month_requests' => $currentMonthRequests,
                'previous_month_tokens' => (int) $previousMonthTokens,
                'previous_month_cost' => $previousMonthCost,
                'previous_month_requests' => $previousMonthRequests,
            ],
            'recent_clients' => $recentClients,
            'recent_logs' => $recentLogs,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeSource;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $client = $user->client;

        $periodStart = CarbonImmutable::now()->startOfMonth();

        $client->load([
            'plan',
            'knowledgeSources' => fn ($query) => $query->latest(),
        ]);

        $usageSummary = $client->usageLogs()
            ->where('created_at', '>=', $periodStart)
            ->selectRaw('COALESCE(SUM(total_tokens), 0) as total_tokens')
            ->selectRaw('COALESCE(SUM(cached_input_tokens), 0) as cached_tokens')
            ->selectRaw('COALESCE(SUM(estimated_cost), 0) as total_cost')
            ->selectRaw('COUNT(*) as request_count')
            ->first();

        $limits = $client->limits();
        $ksCount = $client->knowledgeSources->count();

        return Inertia::render('portal/Dashboard', [
            'client' => $this->transformClientWorkspace($client),
            'knowledge_sources' => $client->knowledgeSources->map(
                fn (KnowledgeSource $knowledgeSource): array => $this->transformKnowledgeSource($knowledgeSource),
            ),
            'usage_summary' => [
                'current_period_tokens'        => (int) $usageSummary->total_tokens,
                'current_period_cached_tokens' => (int) $usageSummary->cached_tokens,
                'current_period_cost'          => (float) $usageSummary->total_cost,
                'current_period_requests'      => (int) $usageSummary->request_count,
            ],
            'memory_summary' => [
                'knowledge_sources' => $ksCount,
                'ready_sources'     => $client->knowledgeSources->where('status', 'ready')->count(),
                'queued_sources'    => $client->knowledgeSources->whereIn('status', ['queued', 'processing'])->count(),
                'chunk_count'       => $client->knowledgeChunks()->count(),
                'cache_entries'     => $client->conversationCaches()->count(),
                'cache_hits'        => (int) $client->conversationCaches()->sum('hit_count'),
                'saved_tokens'      => (int) $client->conversationCaches()->sum('total_tokens_saved'),
            ],
            'plan_limits' => $limits,
            'at_knowledge_source_limit' => $ksCount >= $limits['max_knowledge_sources'],
            'knowledge_source_types' => KnowledgeSource::SOURCE_TYPES,
            'status'     => $request->session()->get('status'),
            'lead_count' => $client->leads()->count(),
        ]);
    }

    private function transformClientWorkspace($client): array
    {
        $settings = collect($client->widget_settings);

        return [
            'id' => $client->id,
            'name' => $client->name,
            'unique_code' => $client->unique_code,
            'contact_email' => $client->contact_email,
            'website_url' => $client->website_url,
            'business_description' => $client->business_description,
            'system_prompt' => $client->system_prompt,
            'chat_model' => $client->chat_model,
            'embedding_model' => $client->embedding_model,
            'retrieval_chunk_count' => $client->retrieval_chunk_count,
            'cache_ttl_hours' => $client->cache_ttl_hours,
            'prompt_caching_enabled' => $client->prompt_caching_enabled,
            'semantic_cache_enabled' => $client->semantic_cache_enabled,
            'allowed_domains' => $client->allowed_domains ?? [],
            'monthly_token_limit' => $client->monthly_token_limit,
            'status' => $client->status,
            'widget_style' => $client->widget_style,
            'plan' => $client->plan ? [
                'name' => $client->plan->name,
                'monthly_token_limit' => $client->plan->monthly_token_limit,
                'max_knowledge_sources' => $client->plan->max_knowledge_sources,
            ] : null,
            'widget_settings' => [
                'primary_color' => $settings->get('primary_color', '#111827'),
                'accent_color' => $settings->get('accent_color', '#0f766e'),
                'welcome_message' => $settings->get('welcome_message', 'Ask us anything.'),
                'toggle_text' => $settings->get('toggle_text', 'Ask anything about this business'),
                'position' => $settings->get('position', 'right'),
                'show_branding' => (bool) $settings->get('show_branding', true),
            ],
        ];
    }

    private function transformKnowledgeSource(KnowledgeSource $knowledgeSource): array
    {
        return [
            'id' => $knowledgeSource->id,
            'title' => $knowledgeSource->title,
            'source_type' => $knowledgeSource->source_type,
            'status' => $knowledgeSource->status,
            'source_url' => $knowledgeSource->source_url,
            'file_name' => $knowledgeSource->file_name,
            'token_estimate' => $knowledgeSource->token_estimate,
            'chunk_count' => $knowledgeSource->chunk_count,
            'last_synced_at' => $knowledgeSource->last_synced_at?->toDateTimeString(),
            'processing_error' => $knowledgeSource->processing_error,
            'created_at' => $knowledgeSource->created_at?->toDateTimeString(),
        ];
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreClientRequest;
use App\Http\Requests\Admin\UpdateClientRequest;
use App\Models\Client;
use App\Models\ConversationCache;
use App\Models\KnowledgeSource;
use App\Models\Plan;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    /**
     * Show the admin client index.
     */
    public function index(Request $request): Response
    {
        $periodStart = CarbonImmutable::now()->startOfMonth();

        $clients = Client::query()
            ->with('plan')
            ->withCount('knowledgeSources')
            ->withSum([
                'usageLogs as current_month_tokens' => fn ($query) => $query->where('created_at', '>=', $periodStart),
            ], 'total_tokens')
            ->latest()
            ->get()
            ->map(fn (Client $client): array => $this->transformClientListItem($client));

        return Inertia::render('clients/Index', [
            'clients' => $clients,
            'summary' => [
                'total_clients' => $clients->count(),
                'active_clients' => $clients->where('status', 'active')->count(),
                'paused_clients' => $clients->where('status', 'paused')->count(),
                'monthly_token_capacity' => $clients->sum('monthly_token_limit'),
                'current_month_usage' => $clients->sum('current_month_tokens'),
                'knowledge_sources' => $clients->sum('knowledge_sources_count'),
            ],
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Show the create client page.
     */
    public function create(): Response
    {
        return Inertia::render('clients/Create', [
            'client' => $this->emptyClientForm(),
            ...$this->formOptions(),
        ]);
    }

    /**
     * Store a newly created client.
     */
    public function store(StoreClientRequest $request): RedirectResponse
    {
        $client = Client::create([
            ...$this->payload($request),
            'unique_code' => (string) Str::uuid(),
        ]);

        return to_route('clients.show', $client)->with('status', 'client-created');
    }

    /**
     * Show the client workspace page.
     */
    public function show(Request $request, Client $client): Response
    {
        $periodStart = CarbonImmutable::now()->startOfMonth();

        $client->load([
            'plan',
            'knowledgeSources' => fn ($query) => $query->latest(),
            'usageLogs' => fn ($query) => $query->latest()->limit(25),
            'conversationCaches' => fn ($query) => $query
                ->orderByDesc('last_hit_at')
                ->orderByDesc('id')
                ->limit(20),
        ]);

        $currentPeriodLogs = $client->usageLogs()
            ->where('created_at', '>=', $periodStart)
            ->get();

        $conversationCaches = $client->conversationCaches;

        return Inertia::render('clients/Show', [
            'client' => $this->transformClientWorkspace($client),
            'knowledge_sources' => $client->knowledgeSources->map(
                fn (KnowledgeSource $knowledgeSource): array => $this->transformKnowledgeSource($knowledgeSource),
            ),
            'usage_summary' => [
                'current_period_tokens' => $currentPeriodLogs->sum('total_tokens'),
                'current_period_cached_tokens' => $currentPeriodLogs->sum('cached_input_tokens'),
                'current_period_cost' => (float) $currentPeriodLogs->sum('estimated_cost'),
                'current_period_requests' => $currentPeriodLogs->count(),
            ],
            'memory_summary' => [
                'knowledge_sources' => $client->knowledgeSources->count(),
                'ready_sources' => $client->knowledgeSources->where('status', 'ready')->count(),
                'queued_sources' => $client->knowledgeSources->whereIn('status', ['queued', 'processing'])->count(),
                'chunk_count' => $client->knowledgeChunks()->count(),
                'cache_entries' => $client->conversationCaches()->count(),
                'cache_hits' => (int) $client->conversationCaches()->sum('hit_count'),
                'saved_tokens' => (int) $client->conversationCaches()->sum('total_tokens_saved'),
            ],
            'usage_logs' => $client->usageLogs->map(fn ($usageLog): array => [
                'id' => $usageLog->id,
                'interaction_type' => $usageLog->interaction_type,
                'model' => $usageLog->model,
                'prompt_tokens' => $usageLog->prompt_tokens,
                'completion_tokens' => $usageLog->completion_tokens,
                'cached_input_tokens' => $usageLog->cached_input_tokens,
                'total_tokens' => $usageLog->total_tokens,
                'estimated_cost' => (float) $usageLog->estimated_cost,
                'request_excerpt' => $usageLog->request_excerpt,
                'created_at' => $usageLog->created_at?->toDateTimeString(),
            ]),
            'cache_entries' => $conversationCaches->map(fn (ConversationCache $cache): array => [
                'id' => $cache->id,
                'question' => $cache->question,
                'answer' => $cache->answer,
                'hit_count' => $cache->hit_count,
                'total_tokens_saved' => $cache->total_tokens_saved,
                'last_hit_at' => $cache->last_hit_at?->toDateTimeString(),
                'expires_at' => $cache->expires_at?->toDateTimeString(),
            ]),
            'knowledge_source_types' => KnowledgeSource::SOURCE_TYPES,
            'widget_script_url' => url('/widget.js'),
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Show the edit client page.
     */
    public function edit(Client $client): Response
    {
        $client->load('plan');

        return Inertia::render('clients/Edit', [
            'client' => $this->transformClientForm($client),
            ...$this->formOptions(),
        ]);
    }

    /**
     * Update the specified client.
     */
    public function update(UpdateClientRequest $request, Client $client): RedirectResponse
    {
        $client->update($this->payload($request));

        return to_route('clients.show', $client)->with('status', 'client-updated');
    }

    /**
     * Delete the specified client and all related data.
     */
    public function destroy(Client $client): RedirectResponse
    {
        $client->delete();

        return to_route('clients.index')->with('status', 'client-deleted');
    }

    /**
     * Transform client form data into a persistable payload.
     *
     * @return array<string, mixed>
     */
    private function payload(StoreClientRequest|UpdateClientRequest $request): array
    {
        $validated = $request->validated();

        return [
            'plan_id' => $validated['plan_id'],
            'name' => $validated['name'],
            'contact_email' => $validated['contact_email'],
            'website_url' => $validated['website_url'],
            'business_description' => $validated['business_description'],
            'system_prompt' => $validated['system_prompt'],
            'chat_model' => $validated['chat_model'],
            'embedding_model' => $validated['embedding_model'],
            'retrieval_chunk_count' => $validated['retrieval_chunk_count'],
            'cache_ttl_hours' => $validated['cache_ttl_hours'],
            'prompt_caching_enabled' => $validated['prompt_caching_enabled'],
            'semantic_cache_enabled' => $validated['semantic_cache_enabled'],
            'allowed_domains' => $this->normalizeAllowedDomains($validated['allowed_domains'] ?? null),
            'monthly_token_limit' => $validated['monthly_token_limit'],
            'status' => $validated['status'],
            'widget_style' => $validated['widget_style'],
            'widget_settings' => [
                'primary_color' => $validated['primary_color'],
                'accent_color' => $validated['accent_color'],
                'welcome_message' => $validated['welcome_message'],
                'position' => $validated['position'],
                'show_branding' => $validated['show_branding'],
            ],
            'notes' => $validated['notes'],
        ];
    }

    /**
     * Build shared form options for create and edit pages.
     *
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'plans' => Plan::query()
                ->where('is_active', true)
                ->orderBy('price_monthly')
                ->get()
                ->map(fn (Plan $plan): array => [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'slug' => $plan->slug,
                    'description' => $plan->description,
                    'price_monthly' => (float) $plan->price_monthly,
                    'monthly_token_limit' => $plan->monthly_token_limit,
                    'monthly_message_limit' => $plan->monthly_message_limit,
                    'max_knowledge_sources' => $plan->max_knowledge_sources,
                    'max_file_upload_mb' => $plan->max_file_upload_mb,
                    'features' => $plan->features ?? [],
                ]),
            'widget_styles' => Client::WIDGET_STYLES,
            'client_statuses' => Client::STATUSES,
            'widget_positions' => Client::WIDGET_POSITIONS,
            'chat_models' => Client::CHAT_MODELS,
            'embedding_models' => Client::EMBEDDING_MODELS,
        ];
    }

    /**
     * Get the empty client form payload.
     *
     * @return array<string, mixed>
     */
    private function emptyClientForm(): array
    {
        $defaultPlan = Plan::query()->where('slug', 'free')->first() ?? Plan::query()->orderBy('price_monthly')->first();

        return [
            'id' => null,
            'plan_id' => $defaultPlan?->id,
            'name' => '',
            'contact_email' => '',
            'website_url' => '',
            'business_description' => '',
            'system_prompt' => 'Answer only from the approved knowledge base. If the answer is not in the knowledge base, say you do not know.',
            'chat_model' => Client::CHAT_MODELS[0],
            'embedding_model' => Client::EMBEDDING_MODELS[0],
            'retrieval_chunk_count' => 3,
            'cache_ttl_hours' => 24,
            'prompt_caching_enabled' => true,
            'semantic_cache_enabled' => true,
            'allowed_domains' => '',
            'monthly_token_limit' => $defaultPlan?->monthly_token_limit ?? 100000,
            'status' => 'draft',
            'widget_style' => Client::WIDGET_STYLES[0],
            'primary_color' => '#111827',
            'accent_color' => '#0f766e',
            'welcome_message' => 'Ask us anything about pricing, support, or appointments.',
            'position' => Client::WIDGET_POSITIONS[0],
            'show_branding' => true,
            'notes' => '',
        ];
    }

    /**
     * Transform a client for the index page list.
     *
     * @return array<string, mixed>
     */
    private function transformClientListItem(Client $client): array
    {
        $settings = Collection::make($client->widget_settings);

        return [
            'id' => $client->id,
            'name' => $client->name,
            'unique_code' => $client->unique_code,
            'contact_email' => $client->contact_email,
            'website_url' => $client->website_url,
            'monthly_token_limit' => $client->monthly_token_limit,
            'current_month_tokens' => (int) ($client->current_month_tokens ?? 0),
            'knowledge_sources_count' => $client->knowledge_sources_count ?? 0,
            'status' => $client->status,
            'widget_style' => $client->widget_style,
            'plan' => $client->plan ? [
                'id' => $client->plan->id,
                'name' => $client->plan->name,
                'slug' => $client->plan->slug,
            ] : null,
            'widget_settings' => [
                'primary_color' => $settings->get('primary_color', '#111827'),
                'accent_color' => $settings->get('accent_color', '#0f766e'),
                'welcome_message' => $settings->get('welcome_message', 'Ask us anything.'),
            ],
            'created_at' => $client->created_at?->toDateTimeString(),
        ];
    }

    /**
     * Transform a client for the create/edit form.
     *
     * @return array<string, mixed>
     */
    private function transformClientForm(Client $client): array
    {
        $settings = Collection::make($client->widget_settings);

        return [
            'id' => $client->id,
            'plan_id' => $client->plan_id,
            'name' => $client->name,
            'contact_email' => $client->contact_email ?? '',
            'website_url' => $client->website_url ?? '',
            'business_description' => $client->business_description ?? '',
            'system_prompt' => $client->system_prompt ?? '',
            'chat_model' => $client->chat_model,
            'embedding_model' => $client->embedding_model,
            'retrieval_chunk_count' => $client->retrieval_chunk_count,
            'cache_ttl_hours' => $client->cache_ttl_hours,
            'prompt_caching_enabled' => $client->prompt_caching_enabled,
            'semantic_cache_enabled' => $client->semantic_cache_enabled,
            'allowed_domains' => implode("\n", $client->allowed_domains ?? []),
            'monthly_token_limit' => $client->monthly_token_limit,
            'status' => $client->status,
            'widget_style' => $client->widget_style,
            'primary_color' => $settings->get('primary_color', '#111827'),
            'accent_color' => $settings->get('accent_color', '#0f766e'),
            'welcome_message' => $settings->get('welcome_message', 'Ask us anything.'),
            'position' => $settings->get('position', Client::WIDGET_POSITIONS[0]),
            'show_branding' => (bool) $settings->get('show_branding', true),
            'notes' => $client->notes ?? '',
        ];
    }

    /**
     * Transform a client for the workspace page.
     *
     * @return array<string, mixed>
     */
    private function transformClientWorkspace(Client $client): array
    {
        $settings = Collection::make($client->widget_settings);

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
            'notes' => $client->notes,
            'plan' => $client->plan ? [
                'id' => $client->plan->id,
                'name' => $client->plan->name,
                'slug' => $client->plan->slug,
                'description' => $client->plan->description,
                'price_monthly' => (float) $client->plan->price_monthly,
                'monthly_token_limit' => $client->plan->monthly_token_limit,
                'monthly_message_limit' => $client->plan->monthly_message_limit,
                'max_knowledge_sources' => $client->plan->max_knowledge_sources,
                'max_file_upload_mb' => $client->plan->max_file_upload_mb,
                'features' => $client->plan->features ?? [],
            ] : null,
            'widget_settings' => [
                'primary_color' => $settings->get('primary_color', '#111827'),
                'accent_color' => $settings->get('accent_color', '#0f766e'),
                'welcome_message' => $settings->get('welcome_message', 'Ask us anything.'),
                'position' => $settings->get('position', Client::WIDGET_POSITIONS[0]),
                'show_branding' => (bool) $settings->get('show_branding', true),
            ],
            'created_at' => $client->created_at?->toDateTimeString(),
        ];
    }

    /**
     * Transform a knowledge source for the workspace page.
     *
     * @return array<string, mixed>
     */
    private function transformKnowledgeSource(KnowledgeSource $knowledgeSource): array
    {
        return [
            'id' => $knowledgeSource->id,
            'title' => $knowledgeSource->title,
            'source_type' => $knowledgeSource->source_type,
            'status' => $knowledgeSource->status,
            'source_url' => $knowledgeSource->source_url,
            'source_hash' => $knowledgeSource->source_hash,
            'file_name' => $knowledgeSource->file_name,
            'token_estimate' => $knowledgeSource->token_estimate,
            'chunk_count' => $knowledgeSource->chunk_count,
            'last_synced_at' => $knowledgeSource->last_synced_at?->toDateTimeString(),
            'content_extracted_at' => $knowledgeSource->content_extracted_at?->toDateTimeString(),
            'processed_at' => $knowledgeSource->processed_at?->toDateTimeString(),
            'processing_error' => $knowledgeSource->processing_error,
            'created_at' => $knowledgeSource->created_at?->toDateTimeString(),
        ];
    }

    /**
     * Normalize textarea domains into a clean array for storage.
     *
     * @return array<int, string>
     */
    private function normalizeAllowedDomains(?string $allowedDomains): array
    {
        if (! $allowedDomains) {
            return [];
        }

        return collect(preg_split('/\r\n|\r|\n/', $allowedDomains) ?: [])
            ->map(fn (string $domain): string => trim(Str::lower($domain)))
            ->filter()
            ->map(function (string $domain): string {
                $parsed = parse_url(str_contains($domain, '://') ? $domain : 'https://'.$domain, PHP_URL_HOST);

                return $parsed ?: $domain;
            })
            ->unique()
            ->values()
            ->all();
    }
}

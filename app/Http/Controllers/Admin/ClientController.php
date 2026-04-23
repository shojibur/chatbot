<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreClientRequest;
use App\Http\Requests\Admin\UpdateClientRequest;
use App\Models\ChatSession;
use App\Models\Client;
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
        ]);

        // Lightweight aggregate queries only — no loading full records
        $usageSummary = $client->usageLogs()
            ->where('created_at', '>=', $periodStart)
            ->selectRaw('COALESCE(SUM(total_tokens), 0) as total_tokens')
            ->selectRaw('COALESCE(SUM(cached_input_tokens), 0) as cached_tokens')
            ->selectRaw('COALESCE(SUM(estimated_cost), 0) as total_cost')
            ->selectRaw('COUNT(*) as request_count')
            ->first();

        return Inertia::render('clients/Show', [
            'client' => $this->transformClientWorkspace($client),
            'knowledge_sources' => $client->knowledgeSources->map(
                fn (KnowledgeSource $knowledgeSource): array => $this->transformKnowledgeSource($knowledgeSource),
            ),
            'usage_summary' => [
                'current_period_tokens' => (int) $usageSummary->total_tokens,
                'current_period_cached_tokens' => (int) $usageSummary->cached_tokens,
                'current_period_cost' => (float) $usageSummary->total_cost,
                'current_period_requests' => (int) $usageSummary->request_count,
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
            'knowledge_source_types' => KnowledgeSource::SOURCE_TYPES,
            'widget_script_url' => url('/widget/widget.js').'?v='.(file_exists(public_path('widget/widget.js')) ? filemtime(public_path('widget/widget.js')) : time()),
            'widget_iframe_url' => route('widget.iframe', ['clientCode' => $client->unique_code]),
            'iframe_settings' => $this->transformIframeEmbedSettings($client),
            'status' => $request->session()->get('status'),
            'lead_count' => $client->leads()->count(),
        ]);
    }

    /**
     * Show the chat playground for testing a client's chatbot.
     */
    public function playground(Client $client): Response
    {
        $client->load('plan');

        return Inertia::render('clients/Playground', [
            'client' => $this->transformClientWorkspace($client),
            'api_base_url' => url('/'),
        ]);
    }

    /**
     * Show the chat history for a client.
     */
    public function chatHistory(Request $request, Client $client): Response
    {
        $client->load('plan');

        $sessions = $client->chatSessions()
            ->addSelect([
                'first_message' => \App\Models\ChatMessage::select('content')
                    ->whereColumn('chat_session_id', 'chat_sessions.id')
                    ->oldest('created_at')
                    ->limit(1)
            ])
            ->orderByDesc('last_activity_at')
            ->simplePaginate(20);

        return Inertia::render('clients/ChatHistory', [
            'client' => $this->transformClientWorkspace($client),
            'sessions' => $sessions->through(fn (ChatSession $session): array => [
                'id' => $session->id,
                'session_token' => $session->session_token,
                'visitor_ip' => $session->visitor_ip,
                'visitor_identifier' => $session->visitor_identifier,
                'page_url' => $session->page_url,
                'user_agent' => $session->user_agent,
                'message_count' => $session->message_count,
                'total_tokens' => $session->total_tokens,
                'last_activity_at' => $session->last_activity_at?->toDateTimeString(),
                'created_at' => $session->created_at?->toDateTimeString(),
                'first_message' => $session->first_message,
                'messages' => [], // Messages will be loaded lazily
            ]),
        ]);
    }

    /**
     * Get the messages for a specific chat session (lazy loaded API endpoint).
     */
    public function chatSessionMessages(Request $request, Client $client, ChatSession $session): \Illuminate\Http\JsonResponse
    {
        if ($session->client_id !== $client->id) {
            abort(404);
        }

        $messages = $session->messages()->orderBy('created_at')->get()
            ->map(fn ($message): array => [
                'id' => $message->id,
                'role' => $message->role,
                'content' => $message->content,
                'token_count' => $message->token_count,
                'from_cache' => $message->from_cache,
                'created_at' => $message->created_at?->toDateTimeString(),
            ]);

        return response()->json(['messages' => $messages]);
    }

    /**
     * Show the usage logs for a client.
     */
    public function usageLogs(Request $request, Client $client): Response
    {
        $client->load('plan');

        $logs = $client->usageLogs()
            ->latest()
            ->simplePaginate(50);

        return Inertia::render('clients/UsageLogs', [
            'client' => $this->transformClientWorkspace($client),
            'logs' => $logs->through(fn ($log): array => [
                'id' => $log->id,
                'request_excerpt' => $log->request_excerpt,
                'interaction_type' => $log->interaction_type,
                'model' => $log->model,
                'total_tokens' => $log->total_tokens,
                'estimated_cost' => (float) $log->estimated_cost,
                'created_at' => $log->created_at?->toDateTimeString(),
            ]),
        ]);
    }

    /**
     * Show the cache entries for a client.
     */
    public function cacheEntries(Request $request, Client $client): Response
    {
        $client->load('plan');

        $entries = $client->conversationCaches()
            ->latest('last_hit_at')
            ->simplePaginate(50);

        return Inertia::render('clients/CacheEntries', [
            'client' => $this->transformClientWorkspace($client),
            'entries' => $entries->through(fn ($entry): array => [
                'id' => $entry->id,
                'question' => $entry->question,
                'answer' => $entry->answer,
                'hit_count' => $entry->hit_count,
                'total_tokens_saved' => $entry->total_tokens_saved,
                'expires_at' => $entry->expires_at?->toDateTimeString(),
                'created_at' => $entry->created_at?->toDateTimeString(),
            ]),
        ]);
    }

    /**
     * Delete a single cache entry.
     */
    public function destroyCacheEntry(Client $client, int $cacheEntry): RedirectResponse
    {
        $client->conversationCaches()->where('id', $cacheEntry)->delete();

        return back()->with('status', 'cache-entry-deleted');
    }

    /**
     * Clear all cache entries for a client.
     */
    public function clearCache(Client $client): RedirectResponse
    {
        $client->conversationCaches()->delete();

        return back()->with('status', 'cache-cleared');
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

        \Illuminate\Support\Facades\Cache::forget("widget_config_{$client->unique_code}");

        return to_route('clients.show', $client)->with('status', 'client-updated');
    }

    /**
     * Update only iframe embed builder settings for a client workspace.
     */
    public function updateIframeSettings(Request $request, Client $client): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'width' => ['required', 'integer', 'min:200', 'max:1600'],
            'height' => ['required', 'integer', 'min:240', 'max:2000'],
            'max_width' => ['required', 'integer', 'min:200', 'max:2000'],
            'border_radius' => ['required', 'integer', 'min:0', 'max:48'],
            'widget_style' => ['required', 'in:'.implode(',', Client::WIDGET_STYLES)],
            'theme_mode' => ['required', 'in:'.implode(',', Client::WIDGET_THEME_MODES)],
            'loading' => ['required', 'in:lazy,eager'],
            'referrer_policy' => ['required', 'in:origin,no-referrer,strict-origin-when-cross-origin'],
        ]);

        $settings = Collection::make($client->widget_settings)->toArray();
        $settings['iframe'] = [
            'title' => $validated['title'],
            'width' => (int) $validated['width'],
            'height' => (int) $validated['height'],
            'max_width' => (int) $validated['max_width'],
            'border_radius' => (int) $validated['border_radius'],
            'widget_style' => $validated['widget_style'],
            'theme_mode' => $validated['theme_mode'],
            'loading' => $validated['loading'],
            'referrer_policy' => $validated['referrer_policy'],
        ];

        $client->update([
            'widget_settings' => $settings,
        ]);

        \Illuminate\Support\Facades\Cache::forget("widget_config_{$client->unique_code}");

        return back()->with('status', 'iframe-settings-updated');
    }

    /**
     * Delete the specified client and all related data.
     */
    public function destroy(Client $client): RedirectResponse
    {
        \Illuminate\Support\Facades\Cache::forget("widget_config_{$client->unique_code}");
        
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
        $existingWidgetSettings = Collection::make(
            optional($request->route('client'))->widget_settings
        )->toArray();

        $widgetSettings = array_merge($existingWidgetSettings, [
            'primary_color' => $validated['primary_color'],
            'accent_color' => $validated['accent_color'],
            'welcome_message' => $validated['welcome_message'],
            'toggle_text' => $validated['toggle_text'] ?? 'Ask anything about this business',
            'position' => $validated['position'],
            'theme_mode' => $validated['theme_mode'],
            'show_branding' => $validated['show_branding'],
        ]);

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
            'widget_settings' => $widgetSettings,
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
            'widget_theme_modes' => Client::WIDGET_THEME_MODES,
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
            'system_prompt' => 'Use approved knowledge base context first for business-specific facts. If the context does not cover a question, provide helpful general guidance, clearly label it as general information, and do not invent business-specific details.',
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
            'toggle_text' => 'Ask anything about this business',
            'position' => Client::WIDGET_POSITIONS[0],
            'theme_mode' => Client::WIDGET_THEME_MODES[0],
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
                'theme_mode' => $settings->get('theme_mode', Client::WIDGET_THEME_MODES[0]),
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
            'toggle_text' => $settings->get('toggle_text', 'Ask anything about this business'),
            'position' => $settings->get('position', Client::WIDGET_POSITIONS[0]),
            'theme_mode' => $settings->get('theme_mode', Client::WIDGET_THEME_MODES[0]),
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
                'toggle_text' => $settings->get('toggle_text', 'Ask anything about this business'),
                'position' => $settings->get('position', Client::WIDGET_POSITIONS[0]),
                'theme_mode' => $settings->get('theme_mode', Client::WIDGET_THEME_MODES[0]),
                'show_branding' => (bool) $settings->get('show_branding', true),
            ],
            'created_at' => $client->created_at?->toDateTimeString(),
        ];
    }

    /**
     * Transform iframe embed builder settings with safe defaults.
     *
     * @return array<string, mixed>
     */
    private function transformIframeEmbedSettings(Client $client): array
    {
        $iframe = Collection::make(Collection::make($client->widget_settings)->get('iframe', []));
        $width = (int) $iframe->get('width', 400);

        return [
            'title' => (string) $iframe->get('title', 'Chat assistant'),
            'width' => $width > 0 ? $width : 400,
            'height' => (int) $iframe->get('height', 640) ?: 640,
            'max_width' => (int) $iframe->get('max_width', $width > 0 ? $width : 400),
            'border_radius' => (int) $iframe->get('border_radius', 16),
            'widget_style' => in_array($iframe->get('widget_style'), Client::WIDGET_STYLES, true)
                ? $iframe->get('widget_style')
                : Client::WIDGET_STYLES[0],
            'theme_mode' => in_array($iframe->get('theme_mode'), Client::WIDGET_THEME_MODES, true)
                ? $iframe->get('theme_mode')
                : Collection::make($client->widget_settings)->get('theme_mode', Client::WIDGET_THEME_MODES[0]),
            'loading' => in_array($iframe->get('loading'), ['lazy', 'eager'], true) ? $iframe->get('loading') : 'lazy',
            'referrer_policy' => in_array(
                $iframe->get('referrer_policy'),
                ['origin', 'no-referrer', 'strict-origin-when-cross-origin'],
                true
            ) ? $iframe->get('referrer_policy') : 'origin',
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
            'content' => $knowledgeSource->source_type === 'manual' ? $knowledgeSource->content : null,
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

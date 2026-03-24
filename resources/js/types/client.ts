export type PlanRecord = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    price_monthly: number;
    monthly_token_limit: number;
    monthly_message_limit: number | null;
    max_knowledge_sources: number;
    max_file_upload_mb: number;
    features: string[];
};

export type PlanListItem = PlanRecord & {
    is_active: boolean;
    clients_count: number;
    created_at: string | null;
};

export type PlanFormRecord = {
    id: number;
    name: string;
    slug: string;
    description: string;
    price_monthly: number;
    monthly_token_limit: number;
    monthly_message_limit: number;
    max_knowledge_sources: number;
    max_file_upload_mb: number;
    features: string[];
    is_active: boolean;
};

export type WidgetSettings = {
    primary_color: string;
    accent_color: string;
    welcome_message: string;
    position: string;
    show_branding: boolean;
};

export type ClientListItem = {
    id: number;
    name: string;
    unique_code: string;
    contact_email: string | null;
    website_url: string | null;
    monthly_token_limit: number;
    current_month_tokens: number;
    knowledge_sources_count: number;
    status: string;
    widget_style: string;
    plan: Pick<PlanRecord, 'id' | 'name' | 'slug'> | null;
    widget_settings: Pick<
        WidgetSettings,
        'primary_color' | 'accent_color' | 'welcome_message'
    >;
    created_at: string | null;
};

export type ClientFormRecord = {
    id: number | null;
    plan_id: number | null;
    name: string;
    contact_email: string;
    website_url: string;
    business_description: string;
    system_prompt: string;
    chat_model: string;
    embedding_model: string;
    retrieval_chunk_count: number;
    cache_ttl_hours: number;
    prompt_caching_enabled: boolean;
    semantic_cache_enabled: boolean;
    allowed_domains: string;
    monthly_token_limit: number;
    status: string;
    widget_style: string;
    primary_color: string;
    accent_color: string;
    welcome_message: string;
    position: string;
    show_branding: boolean;
    notes: string;
};

export type ClientWorkspace = {
    id: number;
    name: string;
    unique_code: string;
    contact_email: string | null;
    website_url: string | null;
    business_description: string | null;
    system_prompt: string | null;
    chat_model: string;
    embedding_model: string;
    retrieval_chunk_count: number;
    cache_ttl_hours: number;
    prompt_caching_enabled: boolean;
    semantic_cache_enabled: boolean;
    allowed_domains: string[];
    monthly_token_limit: number;
    status: string;
    widget_style: string;
    notes: string | null;
    plan: PlanRecord | null;
    widget_settings: WidgetSettings;
    created_at: string | null;
};

export type KnowledgeSourceRecord = {
    id: number;
    title: string;
    source_type: string;
    status: string;
    source_url: string | null;
    source_hash: string | null;
    content: string | null;
    file_name: string | null;
    token_estimate: number;
    chunk_count: number;
    last_synced_at: string | null;
    content_extracted_at: string | null;
    processed_at: string | null;
    processing_error: string | null;
    created_at: string | null;
};

export type UsageSummary = {
    current_period_tokens: number;
    current_period_cached_tokens: number;
    current_period_cost: number;
    current_period_requests: number;
};

export type UsageLogRecord = {
    id: number;
    interaction_type: string;
    model: string | null;
    prompt_tokens: number;
    completion_tokens: number;
    cached_input_tokens: number;
    total_tokens: number;
    estimated_cost: number;
    request_excerpt: string | null;
    created_at: string | null;
};

export type MemorySummary = {
    knowledge_sources: number;
    ready_sources: number;
    queued_sources: number;
    chunk_count: number;
    cache_entries: number;
    cache_hits: number;
    saved_tokens: number;
};

export type CacheEntryRecord = {
    id: number;
    question: string;
    answer: string;
    hit_count: number;
    total_tokens_saved: number;
    last_hit_at: string | null;
    expires_at: string | null;
    created_at: string | null;
};

export type ChatMessageRecord = {
    id: number;
    role: 'user' | 'assistant';
    content: string;
    token_count: number;
    from_cache: boolean;
    created_at: string | null;
};

export type ChatSessionRecord = {
    id: number;
    session_token: string;
    visitor_ip: string | null;
    visitor_identifier: string | null;
    page_url: string | null;
    user_agent: string | null;
    message_count: number;
    total_tokens: number;
    last_activity_at: string | null;
    created_at: string | null;
    messages: ChatMessageRecord[];
};

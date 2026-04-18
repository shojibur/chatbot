<?php

namespace App\Models;

use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory, SoftDeletes;

    public const STATUSES = [
        'draft',
        'active',
        'paused',
    ];

    public const WIDGET_STYLES = [
        'classic',
        'modern',
        'glass',
    ];

    public const WIDGET_POSITIONS = [
        'right',
        'left',
    ];

    public const CHAT_MODELS = [
        'gpt-4o-mini',
        'gpt-4o',
    ];

    public const EMBEDDING_MODELS = [
        'text-embedding-3-small',
        'text-embedding-3-large',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'plan_id',
        'name',
        'unique_code',
        'contact_email',
        'website_url',
        'business_description',
        'system_prompt',
        'chat_model',
        'embedding_model',
        'retrieval_chunk_count',
        'cache_ttl_hours',
        'prompt_caching_enabled',
        'semantic_cache_enabled',
        'allowed_domains',
        'monthly_token_limit',
        'status',
        'widget_style',
        'widget_settings',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'plan_id' => 'integer',
            'monthly_token_limit' => 'integer',
            'retrieval_chunk_count' => 'integer',
            'cache_ttl_hours' => 'integer',
            'prompt_caching_enabled' => 'boolean',
            'semantic_cache_enabled' => 'boolean',
            'allowed_domains' => 'array',
            'widget_settings' => 'array',
        ];
    }

    /**
     * Get the subscription plan for the client.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Get the knowledge sources for the client.
     */
    public function knowledgeSources(): HasMany
    {
        return $this->hasMany(KnowledgeSource::class);
    }

    /**
     * Get the usage logs for the client.
     */
    public function usageLogs(): HasMany
    {
        return $this->hasMany(UsageLog::class);
    }

    /**
     * Get the knowledge chunks for the client.
     */
    public function knowledgeChunks(): HasMany
    {
        return $this->hasMany(KnowledgeChunk::class);
    }

    /**
     * Get the persistent conversation cache entries for the client.
     */
    public function conversationCaches(): HasMany
    {
        return $this->hasMany(ConversationCache::class);
    }

    /**
     * Get the chat sessions for the client.
     */
    public function chatSessions(): HasMany
    {
        return $this->hasMany(ChatSession::class);
    }

    /**
     * Get all chat messages for the client.
     */
    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    /**
     * Get all leads captured for this client.
     */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    /**
     * Get all users associated with this client.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get all usage limits for this client based on their plan.
     * Falls back to sensible defaults when no plan is assigned.
     */
    public function limits(): array
    {
        $plan = $this->plan;

        return [
            'max_knowledge_sources' => $plan?->max_knowledge_sources ?? 5,
            'max_file_upload_mb'    => $plan?->max_file_upload_mb    ?? 10,
            'monthly_token_limit'   => $plan?->monthly_token_limit   ?? $this->monthly_token_limit ?? 100000,
            'monthly_message_limit' => $plan?->monthly_message_limit ?? 500,
        ];
    }

    /**
     * Check whether this client has reached their knowledge source quota.
     */
    public function atKnowledgeSourceLimit(): bool
    {
        $max = $this->limits()['max_knowledge_sources'];
        return $this->knowledgeSources()->count() >= $max;
    }
}

<?php

namespace App\Models;

use Database\Factories\ConversationCacheFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationCache extends Model
{
    /** @use HasFactory<ConversationCacheFactory> */
    use HasFactory;

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'question_embedding',
        'question_embedding_vector',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'client_id',
        'cache_key',
        'normalized_question',
        'question',
        'answer',
        'context_excerpt',
        'question_hash',
        'answer_hash',
        'hit_count',
        'prompt_tokens_saved',
        'completion_tokens_saved',
        'total_tokens_saved',
        'last_hit_at',
        'expires_at',
        'chat_model',
        'embedding_model',
        'question_embedding',
        'meta',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'hit_count' => 'integer',
            'prompt_tokens_saved' => 'integer',
            'completion_tokens_saved' => 'integer',
            'total_tokens_saved' => 'integer',
            'last_hit_at' => 'datetime',
            'expires_at' => 'datetime',
            'question_embedding' => 'array',
            'meta' => 'array',
        ];
    }

    /**
     * Get the client that owns the cache entry.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}

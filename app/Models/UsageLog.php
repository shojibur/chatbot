<?php

namespace App\Models;

use Database\Factories\UsageLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsageLog extends Model
{
    /** @use HasFactory<UsageLogFactory> */
    use HasFactory;

    public const INTERACTION_TYPES = [
        'chat',
        'embedding',
        'cache_hit',
        'retrieval',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'client_id',
        'interaction_type',
        'model',
        'prompt_tokens',
        'completion_tokens',
        'cached_input_tokens',
        'total_tokens',
        'estimated_cost',
        'request_excerpt',
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
            'prompt_tokens' => 'integer',
            'completion_tokens' => 'integer',
            'cached_input_tokens' => 'integer',
            'total_tokens' => 'integer',
            'estimated_cost' => 'decimal:4',
            'meta' => 'array',
        ];
    }

    /**
     * Get the client that owns the usage log.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}

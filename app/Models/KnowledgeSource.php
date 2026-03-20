<?php

namespace App\Models;

use Database\Factories\KnowledgeSourceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KnowledgeSource extends Model
{
    /** @use HasFactory<KnowledgeSourceFactory> */
    use HasFactory;

    public const SOURCE_TYPES = [
        'manual',
        'url',
        'file',
    ];

    public const STATUSES = [
        'draft',
        'queued',
        'processing',
        'ready',
        'failed',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'client_id',
        'title',
        'source_type',
        'status',
        'source_url',
        'source_hash',
        'file_name',
        'file_path',
        'mime_type',
        'file_size',
        'content',
        'token_estimate',
        'chunk_count',
        'last_synced_at',
        'content_extracted_at',
        'processed_at',
        'processing_error',
        'processing_meta',
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
            'token_estimate' => 'integer',
            'chunk_count' => 'integer',
            'last_synced_at' => 'datetime',
            'file_size' => 'integer',
            'content_extracted_at' => 'datetime',
            'processed_at' => 'datetime',
            'processing_meta' => 'array',
            'meta' => 'array',
        ];
    }

    /**
     * Get the client that owns the knowledge source.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the chunks generated for the source.
     */
    public function knowledgeChunks(): HasMany
    {
        return $this->hasMany(KnowledgeChunk::class);
    }
}

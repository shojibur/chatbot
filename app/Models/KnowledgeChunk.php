<?php

namespace App\Models;

use Database\Factories\KnowledgeChunkFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Pgvector\Laravel\HasNeighbors;
use Pgvector\Laravel\Vector;

class KnowledgeChunk extends Model
{
    /** @use HasFactory<KnowledgeChunkFactory> */
    use HasFactory;
    use HasNeighbors;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'client_id',
        'knowledge_source_id',
        'chunk_index',
        'content',
        'content_hash',
        'token_estimate',
        'character_count',
        'embedding_model',
        'embedding',
        'embedding_vector',
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
            'chunk_index' => 'integer',
            'token_estimate' => 'integer',
            'character_count' => 'integer',
            'embedding' => 'array',
            'embedding_vector' => Vector::class,
            'meta' => 'array',
        ];
    }

    /**
     * Get the client that owns the chunk.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the source that owns the chunk.
     */
    public function knowledgeSource(): BelongsTo
    {
        return $this->belongsTo(KnowledgeSource::class);
    }
}

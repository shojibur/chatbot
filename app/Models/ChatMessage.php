<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'chat_session_id',
        'client_id',
        'role',
        'content',
        'token_count',
        'from_cache',
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
            'token_count' => 'integer',
            'from_cache' => 'boolean',
            'meta' => 'array',
        ];
    }

    /**
     * Get the session this message belongs to.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(ChatSession::class, 'chat_session_id');
    }

    /**
     * Get the client that owns this message.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}

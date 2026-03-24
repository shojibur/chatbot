<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatSession extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'client_id',
        'session_token',
        'visitor_ip',
        'visitor_identifier',
        'page_url',
        'user_agent',
        'message_count',
        'total_tokens',
        'last_activity_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'message_count' => 'integer',
            'total_tokens' => 'integer',
            'last_activity_at' => 'datetime',
        ];
    }

    /**
     * Get the client that owns the session.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the messages in this session.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }
}

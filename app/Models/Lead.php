<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends Model
{
    public const TRIGGERS = ['intent', 'no_answer', 'manual'];
    public const STATUSES = ['new', 'contacted', 'closed'];

    protected $fillable = [
        'client_id',
        'chat_session_id',
        'name',
        'contact',
        'user_request',
        'notes',
        'conversation_snapshot',
        'trigger',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'conversation_snapshot' => 'array',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function chatSession(): BelongsTo
    {
        return $this->belongsTo(ChatSession::class);
    }
}

<?php

namespace App\Models;

use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory;

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

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'unique_code',
        'contact_email',
        'website_url',
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
            'monthly_token_limit' => 'integer',
            'widget_settings' => 'array',
        ];
    }
}

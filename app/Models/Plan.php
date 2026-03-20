<?php

namespace App\Models;

use Database\Factories\PlanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    /** @use HasFactory<PlanFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_monthly',
        'monthly_token_limit',
        'monthly_message_limit',
        'max_knowledge_sources',
        'max_file_upload_mb',
        'features',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_monthly' => 'decimal:2',
            'monthly_token_limit' => 'integer',
            'monthly_message_limit' => 'integer',
            'max_knowledge_sources' => 'integer',
            'max_file_upload_mb' => 'integer',
            'features' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the clients subscribed to the plan.
     */
    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    protected $fillable = [
        'name', 'slug', 'plan', 'status', 'settings_json',
        'max_users', 'max_leads', 'max_pipelines', 'max_custom_fields', 'api_rate_limit',
    ];

    protected $casts = [
        'settings_json' => 'array',
        'max_users' => 'integer',
        'max_leads' => 'integer',
        'max_pipelines' => 'integer',
        'max_custom_fields' => 'integer',
        'api_rate_limit' => 'integer',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function pipelines(): HasMany
    {
        return $this->hasMany(Pipeline::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    protected $fillable = [
        'name', 'slug', 'logo', 'plan', 'status', 'trial_ends_at', 'settings_json',
        'max_users', 'max_leads', 'max_pipelines', 'max_custom_fields', 'api_rate_limit',
        'asaas_customer_id', 'asaas_subscription_id', 'subscription_status', 'subscription_ends_at',
    ];

    protected $casts = [
        'settings_json'      => 'array',
        'trial_ends_at'      => 'datetime',
        'subscription_ends_at' => 'datetime',
        'max_users'          => 'integer',
        'max_leads'          => 'integer',
        'max_pipelines'      => 'integer',
        'max_custom_fields'  => 'integer',
        'api_rate_limit'     => 'integer',
    ];

    public function isExemptFromBilling(): bool
    {
        return $this->status === 'partner';
    }

    public function isTrialExpired(): bool
    {
        return $this->status === 'trial'
            && $this->trial_ends_at !== null
            && $this->trial_ends_at->isPast();
    }

    public function hasActiveSubscription(): bool
    {
        return $this->subscription_status === 'active';
    }

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

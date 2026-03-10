<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UpsellTrigger extends Model
{
    protected $fillable = [
        'name',
        'source_plan',
        'target_plan',
        'metric',
        'threshold_type',
        'threshold_value',
        'action_type',
        'action_config',
        'cooldown_hours',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'threshold_value' => 'decimal:2',
        'action_config'   => 'array',
        'cooldown_hours'  => 'integer',
        'priority'        => 'integer',
        'is_active'       => 'boolean',
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(UpsellTriggerLog::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function matchesPlan(string $planName): bool
    {
        return $this->source_plan === null || $this->source_plan === $planName;
    }

    public function targetPlanDefinition(): ?PlanDefinition
    {
        return PlanDefinition::where('name', $this->target_plan)->first();
    }
}

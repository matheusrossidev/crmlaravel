<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesGoal extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'user_id', 'type', 'period', 'target_value',
        'start_date', 'end_date', 'created_by',
        'is_recurring', 'growth_rate', 'parent_goal_id', 'bonus_tiers',
    ];

    protected $casts = [
        'target_value'  => 'decimal:2',
        'growth_rate'   => 'decimal:2',
        'start_date'    => 'date',
        'end_date'      => 'date',
        'is_recurring'  => 'boolean',
        'bonus_tiers'   => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_goal_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_goal_id');
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(SalesGoalSnapshot::class, 'goal_id');
    }

    public function isTeamGoal(): bool
    {
        return $this->user_id === null && $this->children()->exists();
    }

    public function isChildGoal(): bool
    {
        return $this->parent_goal_id !== null;
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScoringRule extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'category',
        'event_type',
        'conditions',
        'points',
        'is_active',
        'cooldown_hours',
        'sort_order',
    ];

    protected $casts = [
        'conditions'     => 'array',
        'points'         => 'integer',
        'is_active'      => 'boolean',
        'cooldown_hours' => 'integer',
        'sort_order'     => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scoreLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LeadScoreLog::class, 'scoring_rule_id');
    }
}

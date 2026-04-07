<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScoringRule extends Model
{
    use BelongsToTenant, LogsActivity;

    protected $fillable = [
        'tenant_id',
        'name',
        'category',
        'event_type',
        'pipeline_id',
        'stage_id',
        'conditions',
        'points',
        'is_active',
        'cooldown_hours',
        'valid_from',
        'valid_until',
        'max_triggers_per_lead',
        'sort_order',
    ];

    protected $casts = [
        'conditions'            => 'array',
        'points'                => 'integer',
        'is_active'             => 'boolean',
        'cooldown_hours'        => 'integer',
        'sort_order'            => 'integer',
        'pipeline_id'           => 'integer',
        'stage_id'              => 'integer',
        'valid_from'            => 'date',
        'valid_until'           => 'date',
        'max_triggers_per_lead' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(PipelineStage::class, 'stage_id');
    }

    public function scoreLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LeadScoreLog::class, 'scoring_rule_id');
    }
}

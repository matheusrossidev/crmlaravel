<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadScoreLog extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'lead_id',
        'scoring_rule_id',
        'points',
        'reason',
        'data_json',
        'created_at',
    ];

    protected $casts = [
        'points'     => 'integer',
        'data_json'  => 'array',
        'created_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function scoringRule(): BelongsTo
    {
        return $this->belongsTo(ScoringRule::class);
    }
}

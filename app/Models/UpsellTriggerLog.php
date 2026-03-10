<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UpsellTriggerLog extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $fillable = [
        'upsell_trigger_id',
        'tenant_id',
        'action_type',
        'metric_value',
        'metric_limit',
        'fired_at',
        'clicked_at',
        'converted_at',
    ];

    protected $casts = [
        'metric_value' => 'integer',
        'metric_limit' => 'integer',
        'fired_at'     => 'datetime',
        'clicked_at'   => 'datetime',
        'converted_at' => 'datetime',
    ];

    public function trigger(): BelongsTo
    {
        return $this->belongsTo(UpsellTrigger::class, 'upsell_trigger_id');
    }
}

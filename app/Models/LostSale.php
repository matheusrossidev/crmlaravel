<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LostSale extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $fillable = [
        'tenant_id', 'lead_id', 'campaign_id', 'pipeline_id',
        'reason_id', 'reason_notes', 'lost_at', 'lost_by',
    ];

    protected $casts = [
        'lost_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function reason(): BelongsTo
    {
        return $this->belongsTo(LostSaleReason::class, 'reason_id');
    }

    public function lostBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lost_by');
    }
}

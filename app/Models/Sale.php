<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sale extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $fillable = [
        'tenant_id', 'lead_id', 'campaign_id', 'pipeline_id',
        'value', 'closed_by', 'closed_at', 'notes',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'closed_at' => 'datetime',
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

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}

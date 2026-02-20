<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadEvent extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $fillable = [
        'tenant_id', 'lead_id', 'event_type', 'description', 'data_json', 'performed_by', 'created_at',
    ];

    protected $casts = [
        'data_json' => 'array',
        'created_at' => 'datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadDuplicate extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'lead_id_a',
        'lead_id_b',
        'score',
        'status',
        'detected_by',
        'reviewed_by',
        'reviewed_at',
        'created_at',
    ];

    protected $casts = [
        'score'       => 'integer',
        'reviewed_at' => 'datetime',
        'created_at'  => 'datetime',
    ];

    public function leadA(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id_a');
    }

    public function leadB(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id_b');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}

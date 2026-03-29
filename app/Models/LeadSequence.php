<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadSequence extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'lead_id',
        'sequence_id',
        'current_step_position',
        'status',
        'next_step_at',
        'started_at',
        'completed_at',
        'exited_at',
        'exit_reason',
    ];

    protected $casts = [
        'current_step_position' => 'integer',
        'next_step_at'          => 'datetime',
        'started_at'            => 'datetime',
        'completed_at'          => 'datetime',
        'exited_at'             => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function sequence(): BelongsTo
    {
        return $this->belongsTo(NurtureSequence::class, 'sequence_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeDue($query)
    {
        return $query->where('status', 'active')->where('next_step_at', '<=', now());
    }

    public function markExited(string $reason): void
    {
        $this->update([
            'status'      => 'exited',
            'exited_at'   => now(),
            'exit_reason' => $reason,
        ]);
    }

    public function markCompleted(): void
    {
        $this->update([
            'status'       => 'completed',
            'completed_at' => now(),
        ]);

        NurtureSequence::withoutGlobalScope('tenant')
            ->where('id', $this->sequence_id)
            ->increment('stats_completed');
    }
}

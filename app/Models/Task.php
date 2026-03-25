<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use BelongsToTenant;

    public const TYPES = ['call', 'email', 'task', 'visit', 'whatsapp', 'meeting'];

    public const TYPE_LABELS = [
        'call'     => 'Ligar',
        'email'    => 'Email',
        'task'     => 'Tarefa',
        'visit'    => 'Visita',
        'whatsapp' => 'WhatsApp',
        'meeting'  => 'Reunião',
    ];

    public const TYPE_ICONS = [
        'call'     => 'bi-telephone',
        'email'    => 'bi-envelope',
        'task'     => 'bi-check2-square',
        'visit'    => 'bi-geo-alt',
        'whatsapp' => 'bi-whatsapp',
        'meeting'  => 'bi-camera-video',
    ];

    protected $fillable = [
        'tenant_id',
        'subject',
        'description',
        'type',
        'status',
        'priority',
        'due_date',
        'due_time',
        'completed_at',
        'lead_id',
        'whatsapp_conversation_id',
        'instagram_conversation_id',
        'assigned_to',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'due_date'     => 'date',
        'completed_at' => 'datetime',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];

    // ── Relationships ───────────────────────────────────────────────────

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function whatsappConversation(): BelongsTo
    {
        return $this->belongsTo(WhatsappConversation::class);
    }

    public function instagramConversation(): BelongsTo
    {
        return $this->belongsTo(InstagramConversation::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Scopes ──────────────────────────────────────────────────────────

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', 'pending')->where('due_date', '<', today());
    }

    public function scopeDueToday(Builder $query): Builder
    {
        return $query->where('status', 'pending')->where('due_date', today());
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('assigned_to', $userId);
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    public function isOverdue(): bool
    {
        return $this->status === 'pending' && $this->due_date && $this->due_date->lt(today());
    }

    public function daysUntilDue(): int
    {
        return $this->due_date ? (int) today()->diffInDays($this->due_date, false) : 999;
    }

    public function urgencyColor(): string
    {
        $days = $this->daysUntilDue();
        if ($days <= 1) return '#ef4444';
        if ($days <= 3) return '#f59e0b';
        return '#10b981';
    }
}

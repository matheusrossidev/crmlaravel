<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventReminder extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'lead_id',
        'conversation_id',
        'ai_agent_id',
        'google_event_id',
        'event_title',
        'event_starts_at',
        'offset_minutes',
        'send_at',
        'body',
        'status',
        'error',
        'sent_at',
    ];

    protected $casts = [
        'event_starts_at' => 'datetime',
        'send_at'         => 'datetime',
        'sent_at'         => 'datetime',
        'offset_minutes'  => 'integer',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(WhatsappConversation::class, 'conversation_id');
    }

    public function aiAgent(): BelongsTo
    {
        return $this->belongsTo(AiAgent::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending')->where('send_at', '<=', now());
    }
}

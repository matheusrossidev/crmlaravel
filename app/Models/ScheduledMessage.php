<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledMessage extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id', 'lead_id', 'conversation_id', 'instance_id', 'created_by',
        'type', 'body', 'media_path', 'media_mime', 'media_filename',
        'quick_message_id', 'send_at', 'sent_at', 'status', 'error',
    ];

    protected $casts = [
        'send_at'          => 'datetime',
        'sent_at'          => 'datetime',
        'lead_id'          => 'integer',
        'conversation_id'  => 'integer',
        'instance_id'      => 'integer',
        'created_by'       => 'integer',
        'quick_message_id' => 'integer',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(WhatsappConversation::class, 'conversation_id');
    }

    public function instance(): BelongsTo
    {
        return $this->belongsTo(WhatsappInstance::class, 'instance_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending')->where('send_at', '<=', now());
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WhatsappConversation extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $fillable = [
        'tenant_id', 'instance_id', 'lead_id', 'phone', 'is_group',
        'contact_name', 'contact_picture_url', 'tags',
        'whatsapp_message_id', 'referral_source', 'referral_campaign_id',
        'status', 'assigned_user_id', 'ai_agent_id', 'unread_count',
        'started_at', 'last_message_at', 'closed_at',
    ];

    protected $casts = [
        'tags'            => 'array',
        'is_group'        => 'boolean',
        'started_at'      => 'datetime',
        'last_message_at' => 'datetime',
        'closed_at'       => 'datetime',
        'created_at'      => 'datetime',
        'ai_agent_id'     => 'integer',
    ];

    public function instance(): BelongsTo
    {
        return $this->belongsTo(WhatsappInstance::class, 'instance_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function referralCampaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'referral_campaign_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function aiAgent(): BelongsTo
    {
        return $this->belongsTo(AiAgent::class, 'ai_agent_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(WhatsappMessage::class, 'conversation_id');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(WhatsappMessage::class, 'conversation_id')
                    ->latestOfMany('sent_at');
    }
}

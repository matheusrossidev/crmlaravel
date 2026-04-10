<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\ConversationContract;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\EnforcesExclusiveHandler;
use App\Models\Traits\HasTags;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WebsiteConversation extends Model implements ConversationContract
{
    use BelongsToTenant, EnforcesExclusiveHandler, HasTags;

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'flow_id',
        'ai_agent_id',
        'visitor_id',
        'contact_name',
        'contact_email',
        'contact_phone',
        'lead_id',
        'chatbot_node_id',
        'chatbot_cursor',
        'chatbot_variables',
        'tags',
        'status',
        'unread_count',
        'started_at',
        'last_message_at',
        'utm_id',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'utm_term',
        'fbclid',
        'gclid',
        'page_url',
        'referrer_url',
    ];

    protected $casts = [
        'chatbot_variables' => 'array',
        'chatbot_cursor'    => 'array',
        'tags'              => 'array',
        'started_at'        => 'datetime',
        'last_message_at'   => 'datetime',
    ];

    public function flow(): BelongsTo
    {
        return $this->belongsTo(ChatbotFlow::class, 'flow_id');
    }

    public function aiAgent(): BelongsTo
    {
        return $this->belongsTo(AiAgent::class, 'ai_agent_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(WebsiteMessage::class, 'conversation_id');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(WebsiteMessage::class, 'conversation_id')->latestOfMany('sent_at');
    }

    // ── ConversationContract ─────────────────────────────────────────────────

    public function getChannelName(): string
    {
        return 'website';
    }

    public function getContactName(): ?string
    {
        return $this->contact_name;
    }

    public function getContactPhone(): ?string
    {
        return $this->contact_phone;
    }

    public function getContactPictureUrl(): ?string
    {
        return null;
    }

    public function getDisplayLabel(): string
    {
        return $this->contact_name
            ?: ($this->contact_email ?: 'Visitante #' . $this->id);
    }
}

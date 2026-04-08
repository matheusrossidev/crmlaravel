<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\ConversationContract;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasTags;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class InstagramConversation extends Model implements ConversationContract
{
    use BelongsToTenant, HasTags;

    public $timestamps = false;

    protected $fillable = [
        'tenant_id', 'instance_id', 'lead_id',
        'igsid', 'contact_name', 'contact_username', 'contact_picture_url',
        'tags', 'assigned_user_id', 'department_id', 'ai_agent_id',
        'chatbot_flow_id', 'chatbot_node_id', 'chatbot_variables',
        'status', 'unread_count',
        'started_at', 'last_message_at', 'closed_at',
    ];

    protected $casts = [
        'tags'               => 'array',
        'chatbot_variables'  => 'array',
        'started_at'         => 'datetime',
        'last_message_at'    => 'datetime',
        'closed_at'          => 'datetime',
        'unread_count'       => 'integer',
        'ai_agent_id'        => 'integer',
    ];

    public function instance(): BelongsTo
    {
        return $this->belongsTo(InstagramInstance::class, 'instance_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function aiAgent(): BelongsTo
    {
        return $this->belongsTo(AiAgent::class, 'ai_agent_id');
    }

    public function chatbotFlow(): BelongsTo
    {
        return $this->belongsTo(ChatbotFlow::class, 'chatbot_flow_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(InstagramMessage::class, 'conversation_id');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(InstagramMessage::class, 'conversation_id')
                    ->latestOfMany('sent_at');
    }

    // ── ConversationContract ─────────────────────────────────────────────────

    public function getChannelName(): string
    {
        return 'instagram';
    }

    public function getContactName(): ?string
    {
        return $this->contact_name ?: $this->contact_username;
    }

    public function getContactPhone(): ?string
    {
        return null;
    }

    public function getContactPictureUrl(): ?string
    {
        return $this->contact_picture_url;
    }

    public function getDisplayLabel(): string
    {
        return $this->contact_name
            ?: ($this->contact_username ? '@' . $this->contact_username : 'Instagram #' . $this->id);
    }
}

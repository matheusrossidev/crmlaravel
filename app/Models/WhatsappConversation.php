<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\ConversationContract;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\EnforcesExclusiveHandler;
use App\Models\Traits\HasTags;
use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WhatsappConversation extends Model implements ConversationContract
{
    use BelongsToTenant, EnforcesExclusiveHandler, LogsActivity, HasTags, HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'tenant_id', 'instance_id', 'lead_id', 'phone', 'lid', 'is_group',
        'contact_name', 'contact_picture_url', 'tags',
        'whatsapp_message_id',
        'status', 'assigned_user_id', 'department_id', 'ai_agent_id',
        'chatbot_flow_id', 'chatbot_node_id', 'chatbot_variables',
        'unread_count', 'started_at', 'last_message_at', 'last_inbound_at', 'first_response_at', 'closed_at',
        'followup_count', 'last_followup_at',
        'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term',
        'fbclid', 'gclid',
    ];

    protected $casts = [
        'tags'               => 'array',
        'chatbot_variables'  => 'array',
        'is_group'           => 'boolean',
        'started_at'         => 'datetime',
        'last_message_at'    => 'datetime',
        'last_inbound_at'    => 'datetime',
        'first_response_at'  => 'datetime',
        'closed_at'          => 'datetime',
        'last_followup_at'   => 'datetime',
        'created_at'         => 'datetime',
        'ai_agent_id'        => 'integer',
        'chatbot_flow_id'    => 'integer',
        'chatbot_node_id'    => 'integer',
        'followup_count'     => 'integer',
    ];

    public function instance(): BelongsTo
    {
        return $this->belongsTo(WhatsappInstance::class, 'instance_id');
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
        return $this->hasMany(WhatsappMessage::class, 'conversation_id');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(WhatsappMessage::class, 'conversation_id')
                    ->latestOfMany('sent_at');
    }

    /**
     * Restringe a query as conversas visiveis pra um user.
     *
     * Regra:
     * - Admin/manager/super_admin (allowedWhatsappInstanceIds == null pra esses)
     *   nao filtra por instancia.
     * - Outros users so veem conversas das instancias atribuidas a eles
     *   (pivot user_whatsapp_instance), OU conversas onde ja sao
     *   `assigned_user_id`, OU conversas do mesmo department deles.
     *
     * Tudo OR — uniao das fontes de visibilidade.
     */
    public function scopeVisibleToUser($query, ?\App\Models\User $user)
    {
        if (! $user) {
            return $query;
        }

        $instanceIds = $user->allowedWhatsappInstanceIds();
        if ($instanceIds === null) {
            // Sem restricao por instancia (admin/manager/super)
            return $query;
        }

        $deptIds = $user->departments()->pluck('departments.id')->all();

        return $query->where(function ($q) use ($instanceIds, $deptIds, $user) {
            $q->whereIn('instance_id', $instanceIds)
              ->orWhere('assigned_user_id', $user->id);
            if (! empty($deptIds)) {
                $q->orWhereIn('department_id', $deptIds);
            }
        });
    }

    // ── ConversationContract ─────────────────────────────────────────────────

    public function getChannelName(): string
    {
        return 'whatsapp';
    }

    public function getContactName(): ?string
    {
        return $this->contact_name;
    }

    public function getContactPhone(): ?string
    {
        return $this->phone;
    }

    public function getContactPictureUrl(): ?string
    {
        return $this->contact_picture_url;
    }

    public function getDisplayLabel(): string
    {
        return $this->contact_name ?: ($this->phone ?: 'WhatsApp #' . $this->id);
    }
}

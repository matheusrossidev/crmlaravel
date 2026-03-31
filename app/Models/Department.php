<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use BelongsToTenant, LogsActivity;

    protected $fillable = [
        'tenant_id', 'name', 'description', 'icon', 'color',
        'default_ai_agent_id', 'default_chatbot_flow_id',
        'assignment_strategy', 'last_assigned_user_id', 'is_active',
    ];

    protected $casts = [
        'is_active'              => 'boolean',
        'default_ai_agent_id'    => 'integer',
        'default_chatbot_flow_id'=> 'integer',
        'last_assigned_user_id'  => 'integer',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function defaultAiAgent(): BelongsTo
    {
        return $this->belongsTo(AiAgent::class, 'default_ai_agent_id');
    }

    public function defaultChatbotFlow(): BelongsTo
    {
        return $this->belongsTo(ChatbotFlow::class, 'default_chatbot_flow_id');
    }

    public function whatsappConversations(): HasMany
    {
        return $this->hasMany(WhatsappConversation::class);
    }

    public function instagramConversations(): HasMany
    {
        return $this->hasMany(InstagramConversation::class);
    }

    /**
     * Transfere uma conversa para este departamento.
     * Atribui o agente IA/chatbot padrão ou faz round-robin de usuários.
     */
    public function assignConversation(WhatsappConversation|InstagramConversation $conv): void
    {
        $update = ['department_id' => $this->id];

        if ($this->default_ai_agent_id) {
            $update['ai_agent_id'] = $this->default_ai_agent_id;
            if ($conv instanceof WhatsappConversation) {
                $update['chatbot_flow_id']   = null;
                $update['chatbot_node_id']   = null;
                $update['chatbot_variables'] = null;
            }
        } elseif ($this->default_chatbot_flow_id && $conv instanceof WhatsappConversation) {
            $update['chatbot_flow_id']   = $this->default_chatbot_flow_id;
            $update['chatbot_node_id']   = null;
            $update['chatbot_variables'] = null;
            $update['ai_agent_id']       = null;
        } else {
            $userId = $this->pickNextUser();
            if ($userId) {
                $update['assigned_user_id'] = $userId;
                $update['ai_agent_id']      = null;
                if ($conv instanceof WhatsappConversation) {
                    $update['chatbot_flow_id']   = null;
                    $update['chatbot_node_id']   = null;
                    $update['chatbot_variables'] = null;
                }
            }
        }

        $convClass = get_class($conv);
        $convClass::withoutGlobalScope('tenant')
            ->where('id', $conv->id)
            ->update($update);
    }

    /**
     * Seleciona o próximo usuário do departamento via round-robin ou least-busy.
     */
    public function pickNextUser(): ?int
    {
        $userIds = $this->users()->pluck('users.id')->toArray();

        if (empty($userIds)) {
            return null;
        }

        if ($this->assignment_strategy === 'least_busy') {
            $counts = WhatsappConversation::withoutGlobalScope('tenant')
                ->whereIn('assigned_user_id', $userIds)
                ->where('status', 'open')
                ->selectRaw('assigned_user_id, COUNT(*) as total')
                ->groupBy('assigned_user_id')
                ->pluck('total', 'assigned_user_id');

            $minCount  = PHP_INT_MAX;
            $bestUser  = $userIds[0];
            foreach ($userIds as $uid) {
                $count = $counts->get($uid, 0);
                if ($count < $minCount) {
                    $minCount = $count;
                    $bestUser = $uid;
                }
            }

            return $bestUser;
        }

        // round_robin (padrão)
        $lastId = $this->last_assigned_user_id;
        $nextUser = null;

        if ($lastId !== null) {
            $found = false;
            foreach ($userIds as $uid) {
                if ($found) {
                    $nextUser = $uid;
                    break;
                }
                if ($uid === $lastId) {
                    $found = true;
                }
            }
        }

        if ($nextUser === null) {
            $nextUser = $userIds[0];
        }

        $this->update(['last_assigned_user_id' => $nextUser]);

        return $nextUser;
    }
}

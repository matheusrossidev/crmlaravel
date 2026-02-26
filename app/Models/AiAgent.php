<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiAgent extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'name', 'objective', 'communication_style',
        'company_name', 'industry', 'language',
        'persona_description', 'behavior',
        'on_finish_action', 'on_transfer_message', 'on_invalid_response',
        'conversation_stages', 'knowledge_base',
        'max_message_length', 'response_delay_seconds', 'response_wait_seconds',
        'channel', 'is_active', 'auto_assign',
        'enable_pipeline_tool', 'enable_tags_tool', 'enable_intent_notify',
        'followup_enabled', 'followup_delay_minutes', 'followup_max_count',
        'followup_hour_start', 'followup_hour_end',
        'transfer_to_user_id',
    ];

    protected $casts = [
        'conversation_stages'    => 'array',
        'max_message_length'     => 'integer',
        'response_delay_seconds' => 'integer',
        'response_wait_seconds'  => 'integer',
        'is_active'              => 'boolean',
        'auto_assign'            => 'boolean',
        'enable_pipeline_tool'   => 'boolean',
        'enable_tags_tool'       => 'boolean',
        'enable_intent_notify'   => 'boolean',
        'followup_enabled'       => 'boolean',
        'followup_delay_minutes' => 'integer',
        'followup_max_count'     => 'integer',
        'followup_hour_start'    => 'integer',
        'followup_hour_end'      => 'integer',
    ];

    public function conversations(): HasMany
    {
        return $this->hasMany(WhatsappConversation::class, 'ai_agent_id');
    }

    public function knowledgeFiles(): HasMany
    {
        return $this->hasMany(AiAgentKnowledgeFile::class, 'ai_agent_id');
    }
}

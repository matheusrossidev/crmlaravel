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
        'max_message_length', 'response_delay_seconds',
        'channel', 'is_active', 'auto_assign',
    ];

    protected $casts = [
        'conversation_stages'   => 'array',
        'max_message_length'    => 'integer',
        'response_delay_seconds'=> 'integer',
        'is_active'             => 'boolean',
        'auto_assign'           => 'boolean',
    ];

    public function conversations(): HasMany
    {
        return $this->hasMany(WhatsappConversation::class, 'ai_agent_id');
    }
}

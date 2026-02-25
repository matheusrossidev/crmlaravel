<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatbotFlow extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'name', 'channel', 'description', 'is_active',
        'trigger_keywords', 'variables',
    ];

    protected $casts = [
        'is_active'        => 'boolean',
        'trigger_keywords' => 'array',
        'variables'        => 'array',
    ];

    public function nodes(): HasMany
    {
        return $this->hasMany(ChatbotFlowNode::class, 'flow_id')->orderBy('canvas_y');
    }

    public function edges(): HasMany
    {
        return $this->hasMany(ChatbotFlowEdge::class, 'flow_id');
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(WhatsappConversation::class, 'chatbot_flow_id');
    }
}

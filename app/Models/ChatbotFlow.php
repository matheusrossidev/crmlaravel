<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatbotFlow extends Model
{
    use BelongsToTenant, LogsActivity;

    protected $fillable = [
        'tenant_id', 'name', 'slug', 'channel', 'website_token', 'description', 'is_active', 'is_catch_all',
        'trigger_keywords', 'trigger_type', 'trigger_media_id', 'trigger_media_thumbnail', 'trigger_media_caption', 'trigger_reply_comment',
        'variables', 'steps',
        'bot_name', 'bot_avatar', 'welcome_message', 'widget_type', 'widget_color',
    ];

    protected $casts = [
        'is_active'        => 'boolean',
        'is_catch_all'     => 'boolean',
        'trigger_keywords' => 'array',
        'variables'        => 'array',
        'steps'            => 'array',
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

    public function websiteConversations(): HasMany
    {
        return $this->hasMany(WebsiteConversation::class, 'flow_id');
    }

    /**
     * Conta nós a partir do JSON `steps` (campo nodes dentro do array).
     */
    public function getStepsNodeCountAttribute(): int
    {
        $steps = $this->steps;
        if (! is_array($steps)) {
            return 0;
        }

        // steps pode ser { nodes: [...], edges: [...] } ou array direto
        $nodes = $steps['nodes'] ?? $steps;
        if (! is_array($nodes)) {
            return 0;
        }

        return count(array_filter($nodes, fn ($n) => isset($n['type'])));
    }
}

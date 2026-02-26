<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiIntentSignal extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'ai_agent_id', 'conversation_id',
        'contact_name', 'phone', 'intent_type', 'context', 'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(AiAgent::class, 'ai_agent_id');
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(WhatsappConversation::class, 'conversation_id');
    }
}

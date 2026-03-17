<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsappInstance extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'session_name',
        'status',
        'phone_number',
        'display_name',
        'label',
        'history_imported',
    ];

    protected $casts = [
        'history_imported' => 'boolean',
    ];

    public function conversations(): HasMany
    {
        return $this->hasMany(WhatsappConversation::class, 'instance_id');
    }

    public function aiAgents(): BelongsToMany
    {
        return $this->belongsToMany(AiAgent::class, 'ai_agent_whatsapp_instance');
    }
}

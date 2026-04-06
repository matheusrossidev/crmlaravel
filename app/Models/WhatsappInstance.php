<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsappInstance extends Model
{
    use BelongsToTenant, LogsActivity;

    protected $fillable = [
        'tenant_id',
        'session_name',
        'status',
        'provider',
        'phone_number',
        'phone_number_id',
        'waba_id',
        'business_account_id',
        'access_token',
        'token_expires_at',
        'display_name',
        'label',
        'history_imported',
    ];

    protected $casts = [
        'history_imported' => 'boolean',
        'access_token'     => 'encrypted',
        'token_expires_at' => 'datetime',
    ];

    public function isWaha(): bool
    {
        return ($this->provider ?? 'waha') === 'waha';
    }

    public function isCloudApi(): bool
    {
        return $this->provider === 'cloud_api';
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(WhatsappConversation::class, 'instance_id');
    }

    public function aiAgents(): BelongsToMany
    {
        return $this->belongsToMany(AiAgent::class, 'ai_agent_whatsapp_instance');
    }
}

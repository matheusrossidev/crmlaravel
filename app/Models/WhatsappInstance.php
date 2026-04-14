<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsappInstance extends Model
{
    use BelongsToTenant, LogsActivity, HasFactory;

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
        'system_user_token',
        'token_expires_at',
        'token_last_checked_at',
        'token_status',
        'display_name',
        'label',
        'is_primary',
        'history_imported',
    ];

    protected $casts = [
        'history_imported'      => 'boolean',
        'is_primary'            => 'boolean',
        'access_token'          => 'encrypted',
        'system_user_token'     => 'encrypted',
        'token_expires_at'      => 'datetime',
        'token_last_checked_at' => 'datetime',
    ];

    public function isWaha(): bool
    {
        return ($this->provider ?? 'waha') === 'waha';
    }

    public function isCloudApi(): bool
    {
        return $this->provider === 'cloud_api';
    }

    /**
     * True se o token user (access_token) está próximo de expirar ou já expirou.
     * Usado pelo banner de alerta e notification de "reconectar".
     * Não considera o system_user_token (esse é permanente).
     */
    public function needsTokenRefresh(): bool
    {
        return in_array($this->token_status, ['expiring', 'expired', 'invalid'], true);
    }

    /**
     * True se a instância tem um token funcional pra operar (qualquer dos 3).
     * Prioridade:
     *   1. system_user_token da própria instância (linkado pro BM Syncro)
     *   2. WHATSAPP_CLOUD_SYSTEM_USER_TOKEN global do env (permanente)
     *   3. access_token do user (60 dias, fallback)
     */
    public function hasUsableToken(): bool
    {
        return ! empty($this->system_user_token)
            || ! empty(config('services.whatsapp_cloud.system_user_token'))
            || ! empty($this->access_token);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(WhatsappConversation::class, 'instance_id');
    }

    public function aiAgents(): BelongsToMany
    {
        return $this->belongsToMany(AiAgent::class, 'ai_agent_whatsapp_instance');
    }

    /**
     * Users autorizados a ver/responder mensagens dessa instancia.
     * Pivot: user_whatsapp_instance.
     * Se vazio, comportamento depende do role do user (admin/manager veem tudo,
     * outros usam fallback de assigned_user_id e department).
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_whatsapp_instance');
    }

    /**
     * Resolve a instancia "primary" do tenant pra usar como fallback quando
     * uma sequence/automation precisa enviar mensagem mas nao tem conversa
     * previa nem instancia explicita configurada.
     *
     * Estrategia:
     *   1. Instancia marcada `is_primary=true` E status='connected'
     *   2. Primeira instancia connected (deterministica via id ASC)
     *   3. null se nada estiver conectado
     */
    public static function resolvePrimary(int $tenantId): ?self
    {
        return static::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('status', 'connected')
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->first();
    }
}

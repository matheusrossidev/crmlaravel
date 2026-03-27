<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class Tenant extends Model
{
    protected $fillable = [
        'name', 'slug', 'logo', 'plan', 'status', 'trial_ends_at', 'settings_json',
        'max_users', 'max_leads', 'max_pipelines', 'max_custom_fields', 'max_chatbot_flows', 'max_ai_agents', 'max_departments', 'max_whatsapp_instances', 'api_rate_limit',
        'asaas_customer_id', 'asaas_subscription_id', 'subscription_status', 'subscription_ends_at',
        'onboarding_completed_at', 'ai_tokens_exhausted', 'referred_by_agency_id',
        'partner_billing_starts_at', 'locale',
    ];

    protected $casts = [
        'settings_json'           => 'array',
        'trial_ends_at'           => 'datetime',
        'subscription_ends_at'    => 'datetime',
        'onboarding_completed_at'      => 'datetime',
        'partner_billing_starts_at'    => 'datetime',
        'max_users'          => 'integer',
        'max_leads'          => 'integer',
        'max_pipelines'      => 'integer',
        'max_custom_fields'  => 'integer',
        'max_chatbot_flows'  => 'integer',
        'max_ai_agents'      => 'integer',
        'max_departments'    => 'integer',
        'max_whatsapp_instances' => 'integer',
        'api_rate_limit'       => 'integer',
        'ai_tokens_exhausted'    => 'boolean',
        'referred_by_agency_id'  => 'integer',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Tenant $tenant): void {
            $id = $tenant->id;

            // ── WhatsApp ──────────────────────────────────────────
            DB::table('whatsapp_messages')->where('tenant_id', $id)->delete();
            DB::table('whatsapp_conversations')->where('tenant_id', $id)->delete();
            DB::table('whatsapp_instances')->where('tenant_id', $id)->delete();
            DB::table('whatsapp_tags')->where('tenant_id', $id)->delete();
            DB::table('whatsapp_quick_messages')->where('tenant_id', $id)->delete();

            // ── Instagram ─────────────────────────────────────────
            DB::table('instagram_messages')->where('tenant_id', $id)->delete();
            DB::table('instagram_conversations')->where('tenant_id', $id)->delete();
            DB::table('instagram_instances')->where('tenant_id', $id)->delete();
            DB::table('instagram_automations')->where('tenant_id', $id)->delete();

            // ── Chatbot ───────────────────────────────────────────
            DB::table('chatbot_flow_nodes')->where('tenant_id', $id)->delete();
            DB::table('chatbot_flow_edges')->where('tenant_id', $id)->delete();
            DB::table('chatbot_flows')->where('tenant_id', $id)->delete();

            // ── IA ────────────────────────────────────────────────
            DB::table('ai_agent_knowledge_files')->where('tenant_id', $id)->delete();
            DB::table('ai_agents')->where('tenant_id', $id)->delete();
            DB::table('ai_configurations')->where('tenant_id', $id)->delete();
            DB::table('ai_usage_logs')->where('tenant_id', $id)->delete();
            DB::table('ai_analyst_suggestions')->where('tenant_id', $id)->delete();
            DB::table('ai_intent_signals')->where('tenant_id', $id)->delete();

            // ── Automações / Integrações ──────────────────────────
            DB::table('automations')->where('tenant_id', $id)->delete();
            DB::table('oauth_connections')->where('tenant_id', $id)->delete();
            DB::table('webhook_configs')->where('tenant_id', $id)->delete();

            // ── CRM ───────────────────────────────────────────────
            DB::table('scheduled_messages')->where('tenant_id', $id)->delete();
            DB::table('lead_events')->where('tenant_id', $id)->delete();
            DB::table('lead_notes')->where('tenant_id', $id)->delete();
            DB::table('custom_field_values')->where('tenant_id', $id)->delete();
            DB::table('custom_field_definitions')->where('tenant_id', $id)->delete();
            DB::table('lost_sales')->where('tenant_id', $id)->delete();
            DB::table('lost_sale_reasons')->where('tenant_id', $id)->delete();
            DB::table('sales')->where('tenant_id', $id)->delete();
            DB::table('ad_spends')->where('tenant_id', $id)->delete();
            DB::table('campaigns')->where('tenant_id', $id)->delete();
            DB::table('leads')->where('tenant_id', $id)->delete();

            // ── Pipelines (stages não têm tenant_id direto) ───────
            DB::table('pipeline_stages')
                ->whereIn('pipeline_id', DB::table('pipelines')->where('tenant_id', $id)->pluck('id'))
                ->delete();
            DB::table('pipelines')->where('tenant_id', $id)->delete();

            // ── Misc ──────────────────────────────────────────────
            DB::table('api_keys')->where('tenant_id', $id)->delete();
            DB::table('master_notifications')->where('tenant_id', $id)->delete();
            DB::table('audit_logs')->where('tenant_id', $id)->delete();
            DB::table('site_events')->where('tenant_id', $id)->delete();
            DB::table('site_visits')->where('tenant_id', $id)->delete();

            // ── Usuários (por último — FK com cascadeOnDelete) ────
            DB::table('users')->where('tenant_id', $id)->delete();
        });
    }

    public function isPartner(): bool
    {
        return $this->plan === 'partner' || $this->status === 'partner';
    }

    public function isExemptFromBilling(): bool
    {
        if (! $this->isPartner()) return false;

        // Parceiro com assinatura ativa → sempre isento
        if ($this->subscription_status === 'active') return true;

        // Sem data de início de cobrança → master ainda não agendou cobrança
        if ($this->partner_billing_starts_at === null) return true;

        // Data ainda no futuro → período de graça ainda vigente
        if ($this->partner_billing_starts_at->isFuture()) return true;

        // Data passou e sem assinatura ativa → cobrança obrigatória
        return false;
    }

    public function isTrialExpired(): bool
    {
        return $this->status === 'trial'
            && $this->trial_ends_at !== null
            && $this->trial_ends_at->isPast();
    }

    public function hasActiveSubscription(): bool
    {
        return $this->subscription_status === 'active';
    }

    /**
     * Serviços bloqueados: trial expirado sem assinatura, suspenso ou inativo.
     */
    public function isServiceBlocked(): bool
    {
        if ($this->isExemptFromBilling()) {
            return false;
        }

        if (in_array($this->status, ['suspended', 'inactive'], true)) {
            return true;
        }

        if ($this->isTrialExpired() && ! $this->hasActiveSubscription()) {
            return true;
        }

        return false;
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function pipelines(): HasMany
    {
        return $this->hasMany(Pipeline::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }

    /** Código de parceiro atribuído a esta agência (só agências têm este registro) */
    public function partnerAgencyCode(): HasOne
    {
        return $this->hasOne(PartnerAgencyCode::class);
    }

    /** Agência que indicou este tenant */
    public function referringAgency(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'referred_by_agency_id');
    }

    /** Clientes que esta agência indicou (só faz sentido para tenants parceiros) */
    public function referredClients(): HasMany
    {
        return $this->hasMany(Tenant::class, 'referred_by_agency_id');
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'phone', 'cnpj', 'website', 'city', 'state', 'segment',
        'logo', 'plan', 'status', 'trial_ends_at', 'settings_json',
        'max_users', 'max_leads', 'max_pipelines', 'max_custom_fields', 'max_chatbot_flows', 'max_ai_agents', 'max_departments', 'max_whatsapp_instances',
        'max_automations', 'max_nurture_sequences', 'max_forms', 'max_whatsapp_templates', 'api_rate_limit',
        'asaas_customer_id', 'asaas_subscription_id', 'subscription_status', 'subscription_ends_at',
        'onboarding_completed_at', 'ai_tokens_exhausted', 'referred_by_agency_id', 'partner_commission_pct',
        'partner_billing_starts_at', 'locale',
        'billing_provider', 'billing_country', 'billing_currency',
        'stripe_customer_id', 'stripe_subscription_id',
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
        'max_automations'        => 'integer',
        'max_nurture_sequences'  => 'integer',
        'max_forms'              => 'integer',
        'max_whatsapp_templates' => 'integer',
        'api_rate_limit'       => 'integer',
        'ai_tokens_exhausted'    => 'boolean',
        'referred_by_agency_id'  => 'integer',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Tenant $tenant): void {
            $id = $tenant->id;
            $database = DB::connection()->getDatabaseName();

            // Auto-discovery: limpa qualquer tabela com coluna tenant_id.
            // Robusto contra tabelas dropadas/novas sem manutencao manual.
            $tenantTables = DB::table('information_schema.COLUMNS')
                ->where('TABLE_SCHEMA', $database)
                ->where('COLUMN_NAME', 'tenant_id')
                ->where('TABLE_NAME', '!=', 'tenants')
                ->pluck('TABLE_NAME');

            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            try {
                foreach ($tenantTables as $table) {
                    DB::table($table)->where('tenant_id', $id)->delete();
                }

                // Pipeline stages (sem tenant_id direto)
                if (Schema::hasTable('pipeline_stages') && Schema::hasTable('pipelines')) {
                    DB::table('pipeline_stages')
                        ->whereIn('pipeline_id', DB::table('pipelines')->where('tenant_id', $id)->pluck('id'))
                        ->delete();
                }
            } finally {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }
        });
    }

    public function isPartner(): bool
    {
        return $this->plan === 'partner' || $this->status === 'partner';
    }

    public function isUnlimited(): bool
    {
        return $this->plan === 'unlimited';
    }

    public function isExemptFromBilling(): bool
    {
        if ($this->isUnlimited()) return true;
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

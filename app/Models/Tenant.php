<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Tenant extends Model
{
    protected $fillable = [
        'name', 'slug', 'logo', 'plan', 'status', 'trial_ends_at', 'settings_json',
        'max_users', 'max_leads', 'max_pipelines', 'max_custom_fields', 'api_rate_limit',
        'asaas_customer_id', 'asaas_subscription_id', 'subscription_status', 'subscription_ends_at',
        'onboarding_completed_at',
    ];

    protected $casts = [
        'settings_json'           => 'array',
        'trial_ends_at'           => 'datetime',
        'subscription_ends_at'    => 'datetime',
        'onboarding_completed_at' => 'datetime',
        'max_users'          => 'integer',
        'max_leads'          => 'integer',
        'max_pipelines'      => 'integer',
        'max_custom_fields'  => 'integer',
        'api_rate_limit'     => 'integer',
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

    public function isExemptFromBilling(): bool
    {
        return $this->status === 'partner';
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
}

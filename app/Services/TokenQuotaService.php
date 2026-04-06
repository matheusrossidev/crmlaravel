<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AiUsageLog;
use App\Models\PlanDefinition;
use App\Models\Tenant;
use App\Models\TenantTokenIncrement;

/**
 * Centraliza checagem de cota de tokens IA e gravação de uso.
 *
 * Lógica extraída de ProcessAiResponse::checkTokenQuota para permitir
 * reuso por features fora do contexto de chat (extração de dados, etc.).
 */
class TokenQuotaService
{
    /**
     * Retorna true se o tenant ainda tem cota disponível pra consumir tokens IA.
     * Marca ai_tokens_exhausted=true atomicamente quando a cota acaba.
     */
    public static function canSpend(Tenant $tenant): bool
    {
        if ($tenant->isExemptFromBilling()) {
            return true;
        }

        $plan = PlanDefinition::where('name', $tenant->plan)->first();
        $base = (int) ($plan?->features_json['ai_tokens_monthly'] ?? 0);

        if ($base === 0) {
            return false; // plano free — sem AI
        }

        // Incrementos pagos no mês corrente somam ao limite base
        $extra = (int) TenantTokenIncrement::where('tenant_id', $tenant->id)
            ->where('status', 'paid')
            ->whereYear('paid_at', now()->year)
            ->whereMonth('paid_at', now()->month)
            ->sum('tokens_added');

        $limit = $base + $extra;

        $used = (int) AiUsageLog::where('tenant_id', $tenant->id)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->sum('tokens_total');

        if ($used >= $limit) {
            // Atomic: only update if not already set (prevents race condition)
            Tenant::withoutGlobalScope('tenant')
                ->where('id', $tenant->id)
                ->where('ai_tokens_exhausted', false)
                ->update(['ai_tokens_exhausted' => true]);
            return false;
        }

        return true;
    }

    /**
     * Grava o consumo de tokens em ai_usage_logs.
     *
     * @param  Tenant    $tenant
     * @param  string    $model    e.g. "gpt-4o-mini"
     * @param  string    $provider e.g. "openai", "anthropic", "google"
     * @param  int       $promptTokens
     * @param  int       $completionTokens
     * @param  string    $type     chat|extraction|knowledge|test
     * @param  int|null  $conversationId optional WhatsApp conversation ref
     */
    public static function recordUsage(
        Tenant $tenant,
        string $model,
        string $provider,
        int $promptTokens,
        int $completionTokens,
        string $type = 'chat',
        ?int $conversationId = null,
    ): AiUsageLog {
        return AiUsageLog::create([
            'tenant_id'         => $tenant->id,
            'conversation_id'   => $conversationId,
            'model'             => $model,
            'provider'          => $provider,
            'tokens_prompt'     => max(0, $promptTokens),
            'tokens_completion' => max(0, $completionTokens),
            'tokens_total'      => max(0, $promptTokens + $completionTokens),
            'type'              => $type,
        ]);
    }
}

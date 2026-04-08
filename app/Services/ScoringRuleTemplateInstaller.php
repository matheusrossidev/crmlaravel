<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ScoringRule;
use App\Support\ScoringRuleTemplates;
use Illuminate\Support\Facades\Log;

/**
 * Instala um template de regra de scoring para um tenant.
 *
 * Idempotente: se já existe regra com o mesmo nome no tenant, retorna a
 * existente sem duplicar (não lança erro). User pode clicar no mesmo
 * "Usar este modelo" várias vezes sem criar lixo.
 */
class ScoringRuleTemplateInstaller
{
    /**
     * @throws \RuntimeException Quando o slug não existe no catálogo
     */
    public function install(int $tenantId, string $slug): ScoringRule
    {
        $template = ScoringRuleTemplates::find($slug);
        if (! $template) {
            throw new \RuntimeException("Template de scoring não encontrado: {$slug}");
        }

        $rule = $template['rule'];
        $name = (string) $rule['name'];

        // Idempotência: se já existe regra com mesmo nome, retorna a existente
        $existing = ScoringRule::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('name', $name)
            ->first();

        if ($existing) {
            Log::info('ScoringRuleTemplateInstaller: regra já existe, retornando existente', [
                'tenant_id' => $tenantId,
                'slug'      => $slug,
                'rule_id'   => $existing->id,
            ]);
            return $existing;
        }

        $sortOrder = (int) ScoringRule::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->max('sort_order') + 1;

        return ScoringRule::create([
            'tenant_id'      => $tenantId,
            'name'           => $name,
            'category'       => $rule['category'],
            'event_type'     => $rule['event_type'],
            'points'         => (int) $rule['points'],
            'cooldown_hours' => (int) ($rule['cooldown_hours'] ?? 0),
            'conditions'     => $rule['conditions'] ?? null,
            'is_active'      => true,
            'sort_order'     => $sortOrder,
        ]);
    }
}

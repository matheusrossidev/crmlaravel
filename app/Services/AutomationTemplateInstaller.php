<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Automation;
use App\Support\AutomationTemplates;
use Illuminate\Support\Facades\Log;

/**
 * Instala um template de automação para um tenant.
 *
 * Idempotente: se já existe automação com mesmo nome no tenant, retorna a
 * existente sem duplicar.
 */
class AutomationTemplateInstaller
{
    /**
     * @throws \RuntimeException Quando o slug não existe no catálogo
     */
    public function install(int $tenantId, string $slug): Automation
    {
        $template = AutomationTemplates::find($slug);
        if (! $template) {
            throw new \RuntimeException("Template de automação não encontrado: {$slug}");
        }

        $automation = $template['automation'];
        $name = (string) $automation['name'];

        $existing = Automation::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('name', $name)
            ->first();

        if ($existing) {
            Log::info('AutomationTemplateInstaller: já existe, retornando existente', [
                'tenant_id'    => $tenantId,
                'slug'         => $slug,
                'automation_id' => $existing->id,
            ]);
            return $existing;
        }

        return Automation::create([
            'tenant_id'      => $tenantId,
            'name'           => $name,
            'trigger_type'   => $automation['trigger_type'],
            'trigger_config' => $automation['trigger_config'] ?? null,
            'conditions'     => $automation['conditions'] ?? null,
            'actions'        => $automation['actions'],
            'is_active'      => true,
        ]);
    }
}

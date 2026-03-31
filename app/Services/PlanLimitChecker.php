<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AiAgent;
use App\Models\ChatbotFlow;
use App\Models\CustomFieldDefinition;
use App\Models\Department;
use App\Models\Lead;
use App\Models\Pipeline;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WhatsappInstance;

class PlanLimitChecker
{
    /**
     * Verifica se o tenant atingiu o limite de um recurso.
     * Retorna null se OK, ou uma mensagem de erro se limite atingido.
     *
     * @param string $resource  Ex: 'leads', 'users', 'pipelines', etc.
     * @param Tenant|null $tenant  Se null, usa o tenant do usuário logado.
     */
    public static function check(string $resource, ?Tenant $tenant = null): ?string
    {
        $tenant ??= auth()->user()?->tenant;
        if (!$tenant || $tenant->plan === 'unlimited') {
            return null;
        }

        $config = self::getResourceConfig($resource, $tenant);
        if (!$config) {
            return null;
        }

        $max = $config['max'];
        if ($max <= 0) {
            return null; // 0 = ilimitado
        }

        $current = ($config['count'])();
        if ($current >= $max) {
            return "Limite de {$max} {$config['label']} atingido no seu plano. Faça upgrade para continuar.";
        }

        return null;
    }

    /**
     * Retorna quantos recursos ainda cabem antes de atingir o limite.
     * Retorna null se ilimitado.
     */
    public static function remaining(string $resource, ?Tenant $tenant = null): ?int
    {
        $tenant ??= auth()->user()?->tenant;
        if (!$tenant || $tenant->plan === 'unlimited') {
            return null;
        }

        $config = self::getResourceConfig($resource, $tenant);
        if (!$config || $config['max'] === null || $config['max'] <= 0) {
            return null; // ilimitado
        }

        $current = ($config['count'])();

        return max(0, $config['max'] - $current);
    }

    /**
     * @return array{max: int, count: \Closure, label: string}|null
     */
    private static function getResourceConfig(string $resource, Tenant $tenant): ?array
    {
        $limits = [
            'users' => [
                'max'   => $tenant->max_users,
                'count' => fn () => User::where('tenant_id', $tenant->id)->count(),
                'label' => 'usuários',
            ],
            'leads' => [
                'max'   => $tenant->max_leads,
                'count' => fn () => Lead::where('tenant_id', $tenant->id)->count(),
                'label' => 'leads',
            ],
            'pipelines' => [
                'max'   => $tenant->max_pipelines,
                'count' => fn () => Pipeline::where('tenant_id', $tenant->id)->count(),
                'label' => 'pipelines',
            ],
            'custom_fields' => [
                'max'   => $tenant->max_custom_fields,
                'count' => fn () => CustomFieldDefinition::where('tenant_id', $tenant->id)->count(),
                'label' => 'campos personalizados',
            ],
            'departments' => [
                'max'   => $tenant->max_departments,
                'count' => fn () => Department::where('tenant_id', $tenant->id)->count(),
                'label' => 'departamentos',
            ],
            'chatbot_flows' => [
                'max'   => $tenant->max_chatbot_flows,
                'count' => fn () => ChatbotFlow::where('tenant_id', $tenant->id)->count(),
                'label' => 'fluxos de chatbot',
            ],
            'ai_agents' => [
                'max'   => $tenant->max_ai_agents ?: 1,
                'count' => fn () => AiAgent::where('tenant_id', $tenant->id)->count(),
                'label' => 'agentes de IA',
            ],
            'whatsapp_instances' => [
                'max'   => $tenant->max_whatsapp_instances > 0 ? $tenant->max_whatsapp_instances : null,
                'count' => fn () => WhatsappInstance::where('tenant_id', $tenant->id)->count(),
                'label' => 'números de WhatsApp',
            ],
        ];

        return $limits[$resource] ?? null;
    }
}

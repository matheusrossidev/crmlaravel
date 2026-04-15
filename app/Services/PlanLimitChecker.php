<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant;

class PlanLimitChecker
{
    /**
     * Verifica se o tenant atingiu o limite de um recurso.
     * Retorna null se OK, ou uma mensagem de erro se limite atingido.
     *
     * @param string $resource  Chave de config/plan_limits.php (ex: 'leads', 'forms').
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
        if ($max === null || $max <= 0) {
            return null;
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
            return null;
        }

        $current = ($config['count'])();

        return max(0, $config['max'] - $current);
    }

    /**
     * @return array{max: int|null, count: \Closure, label: string}|null
     */
    private static function getResourceConfig(string $resource, Tenant $tenant): ?array
    {
        $cfg = config("plan_limits.{$resource}");
        if (!$cfg || !isset($cfg['column'], $cfg['model'], $cfg['noun'])) {
            return null;
        }

        $column = $cfg['column'];
        $model  = $cfg['model'];
        $max    = $tenant->{$column} ?? null;

        return [
            'max'   => $max !== null ? (int) $max : null,
            'count' => fn () => $model::where('tenant_id', $tenant->id)->count(),
            'label' => $cfg['noun'],
        ];
    }
}

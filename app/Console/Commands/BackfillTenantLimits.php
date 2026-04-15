<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\PlanDefinition;
use App\Models\Tenant;
use Illuminate\Console\Command;

class BackfillTenantLimits extends Command
{
    protected $signature = 'plans:backfill-tenant-limits
                            {--dry-run : Não salva, só mostra o que mudaria}
                            {--tenant= : ID específico}';

    protected $description = 'Sincroniza tenants.max_* com PlanDefinition.features_json (idempotente).';

    public function handle(): int
    {
        $dry      = (bool) $this->option('dry-run');
        $tenantId = $this->option('tenant');

        $query = Tenant::query();
        if ($tenantId) {
            $query->where('id', (int) $tenantId);
        }

        $plans = PlanDefinition::all()->keyBy('name');
        $limits = config('plan_limits', []);
        $touched = 0;
        $skipped = 0;

        foreach ($query->cursor() as $tenant) {
            $plan = $plans[$tenant->plan] ?? null;
            if (!$plan) {
                $this->line("  [skip] tenant #{$tenant->id} ({$tenant->name}) — plano '{$tenant->plan}' não encontrado em plan_definitions");
                $skipped++;
                continue;
            }

            $features = $plan->features_json ?? [];
            $updates  = [];

            foreach ($limits as $key => $cfg) {
                $column = $cfg['column'] ?? null;
                if (!$column) continue;

                $planValue = $features[$column] ?? $features[$key] ?? null;
                if ($planValue === null) continue;

                $current = $tenant->{$column};
                if ((string) $current === (string) $planValue) continue;

                $updates[$column] = $planValue;
            }

            if (empty($updates)) {
                $skipped++;
                continue;
            }

            $this->line(sprintf(
                '  tenant #%d (%s) [%s] → %s',
                $tenant->id,
                $tenant->name,
                $tenant->plan,
                json_encode($updates, JSON_UNESCAPED_UNICODE),
            ));

            if (!$dry) {
                $tenant->update($updates);
            }
            $touched++;
        }

        $this->info(sprintf(
            '%s — %d tenants atualizados, %d pulados.',
            $dry ? 'DRY RUN' : 'Backfill concluído',
            $touched,
            $skipped,
        ));

        return self::SUCCESS;
    }
}

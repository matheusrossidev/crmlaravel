<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\UpsellTrigger;
use App\Services\UpsellEvaluator;
use Illuminate\Console\Command;

class EvaluateUpsellTriggers extends Command
{
    protected $signature = 'upsell:evaluate';

    protected $description = 'Evaluate upsell triggers for all active tenants';

    public function handle(UpsellEvaluator $evaluator): int
    {
        $activeTriggersCount = UpsellTrigger::active()->count();

        if ($activeTriggersCount === 0) {
            $this->info('No active upsell triggers found. Skipping.');
            return self::SUCCESS;
        }

        $this->info("Found {$activeTriggersCount} active trigger(s). Evaluating tenants...");

        $fired = 0;

        Tenant::whereIn('status', ['active', 'trial'])
            ->chunk(100, function ($tenants) use ($evaluator, &$fired) {
                foreach ($tenants as $tenant) {
                    $trigger = $evaluator->evaluateAndFire($tenant);

                    if ($trigger !== null) {
                        $fired++;
                        $this->line("  -> Fired [{$trigger->name}] for tenant [{$tenant->name}] (ID:{$tenant->id})");
                    }
                }
            });

        $this->info("Done. {$fired} trigger(s) fired.");

        return self::SUCCESS;
    }
}

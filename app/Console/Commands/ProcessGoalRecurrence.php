<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\SalesGoal;
use App\Services\SalesGoalService;
use Illuminate\Console\Command;

class ProcessGoalRecurrence extends Command
{
    protected $signature = 'goals:process-recurrence';
    protected $description = 'Snapshot expired goals and renew recurring ones';

    public function handle(SalesGoalService $service): int
    {
        $yesterday = now()->subDay()->toDateString();

        // Find goals that ended yesterday (or earlier and not yet snapshotted)
        $expiredGoals = SalesGoal::withoutGlobalScope('tenant')
            ->where('end_date', '<=', $yesterday)
            ->whereDoesntHave('snapshots', fn ($q) => $q->whereColumn('start_date', 'sales_goals.start_date'))
            ->whereNull('parent_goal_id') // skip child goals — parent handles them
            ->get();

        $snapshotted = 0;
        $renewed     = 0;

        foreach ($expiredGoals as $goal) {
            // Snapshot this goal
            $service->generateSnapshot($goal);
            $snapshotted++;

            // Also snapshot child goals (cascata)
            foreach ($goal->children as $child) {
                $service->generateSnapshot($child);
                $snapshotted++;
            }

            // Renew if recurring
            if ($goal->is_recurring) {
                $newGoal = $service->renewRecurring($goal);
                $renewed++;

                // Renew children too, linked to new parent
                foreach ($goal->children as $child) {
                    $newChild = $service->renewRecurring($child);
                    $newChild->update(['parent_goal_id' => $newGoal->id]);
                }
            }
        }

        $this->info("Snapshots: {$snapshotted} | Renewed: {$renewed}");

        return self::SUCCESS;
    }
}

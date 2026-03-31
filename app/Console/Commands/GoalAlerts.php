<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\SalesGoal;
use App\Models\User;
use App\Notifications\GoalAlertNotification;
use App\Services\SalesGoalService;
use Illuminate\Console\Command;

class GoalAlerts extends Command
{
    protected $signature = 'goals:check-alerts';
    protected $description = 'Send alerts to managers about goal performance risks';

    public function handle(SalesGoalService $service): int
    {
        $today = now();
        $alertsSent = 0;

        // Get all active individual goals (not child goals — parent handles team)
        $goals = SalesGoal::withoutGlobalScope('tenant')
            ->whereNull('parent_goal_id')
            ->whereNotNull('user_id')
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->with('user:id,name,tenant_id')
            ->get();

        foreach ($goals as $goal) {
            if (!$goal->user) {
                continue;
            }

            $progress = $service->progress($goal);
            $forecast = $service->forecast($goal, $progress);

            // Skip already achieved
            if ($progress['status'] === 'achieved') {
                continue;
            }

            $managers = User::where('tenant_id', $goal->tenant_id)
                ->whereIn('role', ['admin', 'manager'])
                ->get();

            if ($managers->isEmpty()) {
                continue;
            }

            $userName = $goal->user->name;

            // Alert 1: Behind pace with ≤10 days remaining and <50%
            if ($forecast['remaining_days'] <= 10 && $progress['percentage'] < 50) {
                $title = "Meta em risco — {$userName}";
                $body  = "{$userName} está em {$progress['percentage']}% da meta com {$forecast['remaining_days']} dias restantes.";

                foreach ($managers as $manager) {
                    $manager->notify(new GoalAlertNotification($title, $body));
                }
                $alertsSent++;
                continue; // one alert per goal per day
            }

            // Alert 2: Projected to exceed 120% — positive alert
            if ($forecast['projected_percentage'] >= 120 && $forecast['elapsed_days'] >= 7) {
                $title = "Meta acima do esperado — {$userName}";
                $body  = "{$userName} está projetado para {$forecast['projected_percentage']}% da meta. Considere aumentar o desafio.";

                foreach ($managers as $manager) {
                    $manager->notify(new GoalAlertNotification($title, $body));
                }
                $alertsSent++;
                continue;
            }

            // Alert 3: Needs significant acceleration (>30%)
            if ($forecast['remaining_days'] <= 15 && $forecast['acceleration_needed'] > 30) {
                $title = "Atenção — {$userName} precisa acelerar";
                $body  = "{$userName} precisa aumentar o ritmo em {$forecast['acceleration_needed']}% para bater a meta.";

                foreach ($managers as $manager) {
                    $manager->notify(new GoalAlertNotification($title, $body));
                }
                $alertsSent++;
            }
        }

        $this->info("Alerts sent: {$alertsSent}");

        return self::SUCCESS;
    }
}

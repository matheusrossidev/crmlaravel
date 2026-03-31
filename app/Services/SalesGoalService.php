<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Lead;
use App\Models\Sale;
use App\Models\SalesGoal;
use App\Models\SalesGoalSnapshot;
use App\Models\Task;
use App\Models\WhatsappMessage;
use Carbon\Carbon;

class SalesGoalService
{
    // ── Progress calculation ─────────────────────────────────────────

    public function progress(SalesGoal $goal): array
    {
        $current = $this->calculateCurrent($goal);
        $target  = (float) $goal->target_value;
        $pct     = $target > 0 ? round(($current / $target) * 100, 1) : 0;

        return [
            'current'    => $current,
            'target'     => $target,
            'percentage' => min($pct, 100),
            'raw_pct'    => $pct, // sem cap — para bônus (pode ser 120%, 150%)
            'remaining'  => max($target - $current, 0),
            'status'     => $pct >= 100 ? 'achieved' : ($pct >= 70 ? 'on_track' : 'behind'),
        ];
    }

    private function calculateCurrent(SalesGoal $goal): float
    {
        return match ($goal->type) {
            'leads_won'    => (float) $this->salesQuery($goal)->count(),
            'revenue'      => (float) $this->salesQuery($goal)->sum('value'),
            'leads_created' => (float) $this->leadsCreatedQuery($goal)->count(),
            'messages_sent' => (float) $this->messagesSentQuery($goal)->count(),
            'leads_contacted' => (float) $this->leadsContactedQuery($goal)->count(),
            'tasks_completed' => (float) $this->tasksCompletedQuery($goal)->count(),
            default => 0,
        };
    }

    private function salesQuery(SalesGoal $goal)
    {
        return Sale::where('tenant_id', $goal->tenant_id)
            ->when($goal->user_id, fn ($q) => $q->where('closed_by', $goal->user_id))
            ->whereBetween('closed_at', [$goal->start_date, $goal->end_date]);
    }

    private function leadsCreatedQuery(SalesGoal $goal)
    {
        return Lead::withoutGlobalScope('tenant')
            ->where('tenant_id', $goal->tenant_id)
            ->when($goal->user_id, fn ($q) => $q->where('assigned_to', $goal->user_id))
            ->whereBetween('created_at', [$goal->start_date, $goal->end_date]);
    }

    private function messagesSentQuery(SalesGoal $goal)
    {
        return WhatsappMessage::where('tenant_id', $goal->tenant_id)
            ->where('direction', 'outgoing')
            ->when($goal->user_id, fn ($q) => $q->where('user_id', $goal->user_id))
            ->whereBetween('created_at', [$goal->start_date, $goal->end_date]);
    }

    private function leadsContactedQuery(SalesGoal $goal)
    {
        return WhatsappMessage::where('tenant_id', $goal->tenant_id)
            ->where('direction', 'outgoing')
            ->when($goal->user_id, fn ($q) => $q->where('user_id', $goal->user_id))
            ->whereBetween('created_at', [$goal->start_date, $goal->end_date])
            ->distinct('conversation_id');
    }

    private function tasksCompletedQuery(SalesGoal $goal)
    {
        return Task::withoutGlobalScope('tenant')
            ->where('tenant_id', $goal->tenant_id)
            ->where('status', 'completed')
            ->when($goal->user_id, fn ($q) => $q->where('assigned_to', $goal->user_id))
            ->whereBetween('completed_at', [$goal->start_date, $goal->end_date]);
    }

    // ── Forecast / Projection ────────────────────────────────────────

    public function forecast(SalesGoal $goal, ?array $progress = null): array
    {
        $progress ??= $this->progress($goal);
        $now       = Carbon::today();
        $start     = $goal->start_date;
        $end       = $goal->end_date;

        $totalDays  = max($start->diffInDays($end), 1);
        $elapsedDays = min($start->diffInDays($now), $totalDays);
        $remainingDays = max($totalDays - $elapsedDays, 0);

        // Se não passou nenhum dia ainda, sem projeção
        if ($elapsedDays <= 0) {
            return [
                'projected_value'      => 0,
                'projected_percentage' => 0,
                'pace'                 => 'not_started',
                'acceleration_needed'  => 0,
                'daily_rate'           => 0,
                'needed_daily_rate'    => $progress['target'] / max($totalDays, 1),
                'remaining_days'       => $remainingDays,
                'total_days'           => $totalDays,
                'elapsed_days'         => 0,
            ];
        }

        $dailyRate       = $progress['current'] / $elapsedDays;
        $projectedValue  = $dailyRate * $totalDays;
        $projectedPct    = $progress['target'] > 0 ? round(($projectedValue / $progress['target']) * 100, 1) : 0;

        // Ritmo necessário para bater a meta no resto do período
        $neededDailyRate = $remainingDays > 0
            ? max($progress['remaining'] / $remainingDays, 0)
            : 0;

        // Aceleração necessária (% a mais que o ritmo atual)
        $acceleration = $dailyRate > 0 && $neededDailyRate > $dailyRate
            ? round((($neededDailyRate / $dailyRate) - 1) * 100, 0)
            : 0;

        // Classificação do ritmo
        $pace = match (true) {
            $progress['raw_pct'] >= 100   => 'achieved',
            $projectedPct >= 110          => 'ahead',
            $projectedPct >= 90           => 'on_pace',
            default                       => 'behind',
        };

        return [
            'projected_value'      => round($projectedValue, 1),
            'projected_percentage' => min($projectedPct, 999),
            'pace'                 => $pace,
            'acceleration_needed'  => (int) $acceleration,
            'daily_rate'           => round($dailyRate, 2),
            'needed_daily_rate'    => round($neededDailyRate, 2),
            'remaining_days'       => $remainingDays,
            'total_days'           => $totalDays,
            'elapsed_days'         => $elapsedDays,
        ];
    }

    // ── Team progress (cascata) ──────────────────────────────────────

    public function teamProgress(SalesGoal $parentGoal): array
    {
        $children = $parentGoal->children()->with('user:id,name')->get();

        $contributions = [];
        $totalCurrent  = 0;

        foreach ($children as $child) {
            $childProgress = $this->progress($child);
            $totalCurrent += $childProgress['current'];
            $contributions[] = [
                'goal'     => $child,
                'user'     => $child->user,
                'progress' => $childProgress,
                'forecast' => $this->forecast($child, $childProgress),
            ];
        }

        $target = (float) $parentGoal->target_value;
        $pct    = $target > 0 ? round(($totalCurrent / $target) * 100, 1) : 0;

        return [
            'current'       => $totalCurrent,
            'target'        => $target,
            'percentage'    => min($pct, 100),
            'raw_pct'       => $pct,
            'remaining'     => max($target - $totalCurrent, 0),
            'status'        => $pct >= 100 ? 'achieved' : ($pct >= 70 ? 'on_track' : 'behind'),
            'contributions' => $contributions,
        ];
    }

    // ── Bonus tier check ─────────────────────────────────────────────

    public function achievedBonusTier(SalesGoal $goal, float $rawPct): ?array
    {
        $tiers = $goal->bonus_tiers;
        if (!$tiers || !is_array($tiers)) {
            return null;
        }

        // Ordena por threshold decrescente e retorna o maior atingido
        usort($tiers, fn ($a, $b) => ($b['threshold'] ?? 0) <=> ($a['threshold'] ?? 0));

        foreach ($tiers as $tier) {
            if ($rawPct >= ($tier['threshold'] ?? 0)) {
                return $tier;
            }
        }

        return null;
    }

    // ── Snapshot (save history) ──────────────────────────────────────

    public function generateSnapshot(SalesGoal $goal): SalesGoalSnapshot
    {
        $progress = $this->progress($goal);

        return SalesGoalSnapshot::create([
            'tenant_id'      => $goal->tenant_id,
            'user_id'        => $goal->user_id,
            'goal_id'        => $goal->id,
            'type'           => $goal->type,
            'period'         => $goal->period,
            'target_value'   => $progress['target'],
            'achieved_value' => $progress['current'],
            'percentage'     => $progress['raw_pct'],
            'start_date'     => $goal->start_date,
            'end_date'       => $goal->end_date,
            'created_at'     => now(),
        ]);
    }

    // ── Recurrence ───────────────────────────────────────────────────

    public function renewRecurring(SalesGoal $goal): SalesGoal
    {
        $newTarget = (float) $goal->target_value;
        if ($goal->growth_rate && $goal->growth_rate > 0) {
            $newTarget = round($newTarget * (1 + $goal->growth_rate / 100), 2);
        }

        // Calcula próximo período
        [$newStart, $newEnd] = $this->nextPeriodDates($goal);

        return SalesGoal::create([
            'tenant_id'      => $goal->tenant_id,
            'user_id'        => $goal->user_id,
            'type'           => $goal->type,
            'period'         => $goal->period,
            'target_value'   => $newTarget,
            'start_date'     => $newStart,
            'end_date'       => $newEnd,
            'created_by'     => $goal->created_by,
            'is_recurring'   => true,
            'growth_rate'    => $goal->growth_rate,
            'parent_goal_id' => $goal->parent_goal_id,
            'bonus_tiers'    => $goal->bonus_tiers,
        ]);
    }

    private function nextPeriodDates(SalesGoal $goal): array
    {
        $end = $goal->end_date->copy();

        return match ($goal->period) {
            'weekly' => [
                $end->addDay(),
                $end->addDay()->copy()->addDays(6),
            ],
            'quarterly' => [
                $end->addDay(),
                $end->addDay()->copy()->addMonths(3)->subDay(),
            ],
            default => [ // monthly
                $end->addDay(),
                $end->addDay()->copy()->endOfMonth(),
            ],
        };
    }

    // ── Ranking ──────────────────────────────────────────────────────

    public function ranking(int $tenantId): array
    {
        // Build ranking from individual goals in current period
        $goals = SalesGoal::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->whereNotNull('user_id')
            ->whereNull('parent_goal_id')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->with('user:id,name')
            ->get();

        // Group by user, pick best goal percentage per user
        $byUser = [];
        foreach ($goals as $goal) {
            $progress = $this->progress($goal);
            $forecast = $this->forecast($goal, $progress);
            $uid = $goal->user_id;

            if (!isset($byUser[$uid]) || $progress['raw_pct'] > $byUser[$uid]['best_pct']) {
                $byUser[$uid] = [
                    'user_id'   => $uid,
                    'user_name' => $goal->user?->name ?? 'Vendedor',
                    'best_pct'  => $progress['raw_pct'],
                    'goals'     => [],
                    'total_pct' => 0,
                    'goal_count' => 0,
                ];
            }
            $byUser[$uid]['goals'][] = [
                'goal'     => $goal,
                'progress' => $progress,
                'forecast' => $forecast,
            ];
            $byUser[$uid]['total_pct'] += $progress['raw_pct'];
            $byUser[$uid]['goal_count']++;
        }

        // Calculate average and streak for each user
        $ranking = [];
        foreach ($byUser as $uid => $data) {
            $avgPct = $data['goal_count'] > 0 ? round($data['total_pct'] / $data['goal_count'], 1) : 0;
            $streak = $this->calculateStreak($tenantId, $uid);
            $ranking[] = [
                'user_id'   => $uid,
                'user_name' => $data['user_name'],
                'avg_pct'   => $avgPct,
                'goal_count' => $data['goal_count'],
                'streak'    => $streak,
                'goals'     => $data['goals'],
            ];
        }

        // Sort by avg_pct descending
        usort($ranking, fn ($a, $b) => $b['avg_pct'] <=> $a['avg_pct']);

        // Add position
        foreach ($ranking as $i => &$r) {
            $r['position'] = $i + 1;
        }
        unset($r);

        return $ranking;
    }

    // ── Streak (consecutive days with sales activity) ────────────────

    public function calculateStreak(int $tenantId, int $userId): int
    {
        // Count consecutive days backwards from today where user closed a sale
        $streak = 0;
        $date = Carbon::today();

        for ($i = 0; $i < 60; $i++) {
            $hasSale = Sale::where('tenant_id', $tenantId)
                ->where('closed_by', $userId)
                ->whereDate('closed_at', $date)
                ->exists();

            if ($hasSale) {
                $streak++;
                $date->subDay();
            } else {
                // Allow skipping weekends
                if ($date->isWeekend()) {
                    $date->subDay();
                    continue;
                }
                break;
            }
        }

        return $streak;
    }

    // ── History for a user ───────────────────────────────────────────

    public function userHistory(int $tenantId, ?int $userId, int $months = 12): array
    {
        $query = SalesGoalSnapshot::where('tenant_id', $tenantId)
            ->where('start_date', '>=', now()->subMonths($months)->startOfMonth())
            ->orderBy('start_date');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $snapshots = $query->get();

        if ($snapshots->isEmpty()) {
            return [
                'snapshots'  => [],
                'avg_pct'    => 0,
                'best_month' => null,
                'worst_month' => null,
                'trend'      => 'stable',
            ];
        }

        $grouped = $snapshots->groupBy(fn ($s) => $s->start_date->format('Y-m'));
        $monthlyData = [];

        foreach ($grouped as $month => $items) {
            $avgPct = $items->avg('percentage');
            $monthlyData[] = [
                'month'      => $month,
                'label'      => Carbon::parse($month . '-01')->translatedFormat('M/y'),
                'target'     => $items->sum('target_value'),
                'achieved'   => $items->sum('achieved_value'),
                'percentage' => round($avgPct, 1),
            ];
        }

        $percentages = collect($monthlyData)->pluck('percentage');
        $bestIdx     = $percentages->search($percentages->max());
        $worstIdx    = $percentages->search($percentages->min());

        // Trend: compare last 2 months
        $trend = 'stable';
        if (count($monthlyData) >= 2) {
            $last  = $monthlyData[count($monthlyData) - 1]['percentage'];
            $prev  = $monthlyData[count($monthlyData) - 2]['percentage'];
            $diff  = $last - $prev;
            $trend = $diff > 5 ? 'improving' : ($diff < -5 ? 'declining' : 'stable');
        }

        return [
            'snapshots'   => $monthlyData,
            'avg_pct'     => round($percentages->avg(), 1),
            'best_month'  => $monthlyData[$bestIdx] ?? null,
            'worst_month' => $monthlyData[$worstIdx] ?? null,
            'trend'       => $trend,
        ];
    }
}

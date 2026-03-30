<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Lead;
use App\Models\Sale;
use App\Models\SalesGoal;

class SalesGoalService
{
    public function progress(SalesGoal $goal): array
    {
        $query = match ($goal->type) {
            'leads_won' => Sale::where('tenant_id', $goal->tenant_id)
                ->when($goal->user_id, fn ($q) => $q->where('closed_by', $goal->user_id))
                ->whereBetween('closed_at', [$goal->start_date, $goal->end_date]),

            'revenue' => Sale::where('tenant_id', $goal->tenant_id)
                ->when($goal->user_id, fn ($q) => $q->where('closed_by', $goal->user_id))
                ->whereBetween('closed_at', [$goal->start_date, $goal->end_date]),

            'leads_created' => Lead::withoutGlobalScope('tenant')
                ->where('tenant_id', $goal->tenant_id)
                ->when($goal->user_id, fn ($q) => $q->where('assigned_to', $goal->user_id))
                ->whereBetween('created_at', [$goal->start_date, $goal->end_date]),

            default => null,
        };

        if (!$query) {
            return ['current' => 0, 'target' => $goal->target_value, 'percentage' => 0, 'remaining' => $goal->target_value, 'status' => 'behind'];
        }

        $current = match ($goal->type) {
            'revenue' => (float) $query->sum('value'),
            default   => $query->count(),
        };

        $target = (float) $goal->target_value;
        $pct    = $target > 0 ? round(($current / $target) * 100, 1) : 0;

        return [
            'current'    => $current,
            'target'     => $target,
            'percentage' => min($pct, 100),
            'remaining'  => max($target - $current, 0),
            'status'     => $pct >= 100 ? 'achieved' : ($pct >= 70 ? 'on_track' : 'behind'),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadScoreLog;
use App\Models\ScoringRule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class LeadScoringService
{
    /**
     * Evaluate scoring rules for a lead based on an event.
     *
     * @param Lead   $lead      The lead to score
     * @param string $eventType The event that triggered scoring
     * @param array  $context   Additional context (message, conversation, stage_old_id, etc.)
     */
    public function evaluate(Lead $lead, string $eventType, array $context = []): void
    {
        $tenantId = $lead->tenant_id;

        $rules = ScoringRule::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('event_type', $eventType)
            ->get();

        if ($rules->isEmpty()) {
            return;
        }

        $totalDelta = 0;

        foreach ($rules as $rule) {
            try {
                if (!$this->matchesConditions($rule, $lead, $context)) {
                    continue;
                }

                if (!$this->passesCooldown($rule, $lead)) {
                    continue;
                }

                $points = $rule->points;
                $totalDelta += $points;

                LeadScoreLog::create([
                    'tenant_id'       => $tenantId,
                    'lead_id'         => $lead->id,
                    'scoring_rule_id' => $rule->id,
                    'points'          => $points,
                    'reason'          => $rule->name,
                    'data_json'       => [
                        'event_type' => $eventType,
                        'rule_category' => $rule->category,
                    ],
                    'created_at'      => now(),
                ]);
            } catch (\Throwable $e) {
                Log::warning('LeadScoring: rule evaluation failed', [
                    'rule_id' => $rule->id,
                    'lead_id' => $lead->id,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        if ($totalDelta !== 0) {
            $newScore = max(0, $lead->score + $totalDelta);
            $lead->update([
                'score'            => $newScore,
                'score_updated_at' => now(),
            ]);
        }
    }

    /**
     * Apply decay points directly (used by scoring:decay command).
     */
    public function applyDecay(Lead $lead, string $reason, int $points): void
    {
        if ($points === 0 || $lead->score <= 0) {
            return;
        }

        $newScore = max(0, $lead->score + $points); // points is negative

        LeadScoreLog::create([
            'tenant_id'       => $lead->tenant_id,
            'lead_id'         => $lead->id,
            'scoring_rule_id' => null,
            'points'          => $points,
            'reason'          => $reason,
            'data_json'       => ['decay' => true],
            'created_at'      => now(),
        ]);

        $lead->update([
            'score'            => $newScore,
            'score_updated_at' => now(),
        ]);
    }

    /**
     * Recalculate a lead's score from all logs.
     */
    public function recalculate(Lead $lead): int
    {
        $total = LeadScoreLog::withoutGlobalScope('tenant')
            ->where('lead_id', $lead->id)
            ->sum('points');

        $score = max(0, (int) $total);

        $lead->update([
            'score'            => $score,
            'score_updated_at' => now(),
        ]);

        return $score;
    }

    /**
     * Get score breakdown by category for a lead.
     */
    public function getBreakdown(Lead $lead): array
    {
        $logs = LeadScoreLog::withoutGlobalScope('tenant')
            ->where('lead_id', $lead->id)
            ->join('scoring_rules', 'lead_score_logs.scoring_rule_id', '=', 'scoring_rules.id')
            ->selectRaw('scoring_rules.category, SUM(lead_score_logs.points) as total')
            ->groupBy('scoring_rules.category')
            ->pluck('total', 'category')
            ->toArray();

        // Add uncategorized (decay, AI, etc.)
        $uncategorized = LeadScoreLog::withoutGlobalScope('tenant')
            ->where('lead_id', $lead->id)
            ->whereNull('scoring_rule_id')
            ->sum('points');

        if ($uncategorized != 0) {
            $logs['other'] = (int) $uncategorized;
        }

        return $logs;
    }

    /**
     * Check if rule conditions match.
     */
    private function matchesConditions(ScoringRule $rule, Lead $lead, array $context): bool
    {
        $conditions = $rule->conditions;

        if (empty($conditions)) {
            return true;
        }

        foreach ($conditions as $condition) {
            $field    = $condition['field'] ?? '';
            $operator = $condition['operator'] ?? 'equals';
            $value    = $condition['value'] ?? '';

            $actual = match ($field) {
                'lead_source'  => $lead->source,
                'lead_value'   => (float) $lead->value,
                'lead_tag'     => $lead->tags ?? [],
                'has_email'    => !empty($lead->email),
                'has_company'  => !empty($lead->company),
                'has_phone'    => !empty($lead->phone),
                'message_type' => $context['message_type'] ?? null,
                default        => null,
            };

            if (!$this->evaluateCondition($actual, $operator, $value)) {
                return false;
            }
        }

        return true;
    }

    private function evaluateCondition(mixed $actual, string $operator, mixed $value): bool
    {
        if (is_array($actual)) {
            return match ($operator) {
                'contains'     => in_array($value, $actual),
                'not_contains' => !in_array($value, $actual),
                default        => false,
            };
        }

        if (is_bool($actual)) {
            return (bool) $value === $actual;
        }

        return match ($operator) {
            'equals'       => $actual == $value,
            'not_equals'   => $actual != $value,
            'gt'           => $actual > $value,
            'gte'          => $actual >= $value,
            'lt'           => $actual < $value,
            'lte'          => $actual <= $value,
            'contains'     => is_string($actual) && str_contains($actual, (string) $value),
            'not_contains' => is_string($actual) && !str_contains($actual, (string) $value),
            default        => true,
        };
    }

    /**
     * Check cooldown — prevent same rule firing too often for same lead.
     */
    private function passesCooldown(ScoringRule $rule, Lead $lead): bool
    {
        if ($rule->cooldown_hours <= 0) {
            return true;
        }

        $cacheKey = "scoring:cooldown:{$rule->id}:{$lead->id}";

        if (Cache::has($cacheKey)) {
            return false;
        }

        Cache::put($cacheKey, 1, now()->addHours($rule->cooldown_hours));

        return true;
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\UpsellUpgrade;
use App\Models\AiAgent;
use App\Models\AiUsageLog;
use App\Models\Automation;
use App\Models\ChatbotFlow;
use App\Models\Lead;
use App\Models\PlanDefinition;
use App\Models\Pipeline;
use App\Models\Tenant;
use App\Models\UpsellTrigger;
use App\Models\UpsellTriggerLog;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class UpsellEvaluator
{
    /**
     * Evaluate all active triggers for a tenant.
     * Returns the highest-priority trigger that fires, or null.
     */
    public function evaluate(Tenant $tenant): ?UpsellTrigger
    {
        $triggers = UpsellTrigger::active()
            ->orderByDesc('priority')
            ->get();

        $metrics = null;

        foreach ($triggers as $trigger) {
            if (!$trigger->matchesPlan($tenant->plan)) {
                continue;
            }

            if ($this->isInCooldown($trigger, $tenant)) {
                continue;
            }

            // Lazy-load metrics only when needed
            if ($metrics === null) {
                $metrics = $this->collectMetrics($tenant);
            }

            $metricValue = $metrics[$trigger->metric] ?? 0;
            $metricLimit = $this->getLimit($tenant, $trigger->metric);

            if ($this->meetsThreshold($trigger, $metricValue, $metricLimit)) {
                return $trigger;
            }
        }

        return null;
    }

    /**
     * Execute trigger action and create log entry.
     */
    public function fire(UpsellTrigger $trigger, Tenant $tenant, ?array $metrics = null): void
    {
        if ($metrics === null) {
            $metrics = $this->collectMetrics($tenant);
        }

        $metricValue = $metrics[$trigger->metric] ?? 0;
        $metricLimit = $this->getLimit($tenant, $trigger->metric);

        $log = UpsellTriggerLog::withoutGlobalScope('tenant')->create([
            'upsell_trigger_id' => $trigger->id,
            'tenant_id'         => $tenant->id,
            'action_type'       => $trigger->action_type,
            'metric_value'      => $metricValue,
            'metric_limit'      => $metricLimit,
            'fired_at'          => now(),
        ]);

        $actionType = $trigger->action_type;

        if ($actionType === 'email' || $actionType === 'all') {
            $this->sendEmail($trigger, $tenant);
        }

        // banner and notification are handled passively (view composer reads the log)

        Log::channel('single')->info('Upsell trigger fired', [
            'trigger_id'   => $trigger->id,
            'trigger_name' => $trigger->name,
            'tenant_id'    => $tenant->id,
            'tenant_name'  => $tenant->name,
            'metric'       => $trigger->metric,
            'value'        => $metricValue,
            'limit'        => $metricLimit,
        ]);
    }

    /**
     * Full evaluate + fire cycle for a tenant.
     */
    public function evaluateAndFire(Tenant $tenant): ?UpsellTrigger
    {
        $trigger = $this->evaluate($tenant);

        if ($trigger !== null) {
            $this->fire($trigger, $tenant);
        }

        return $trigger;
    }

    /**
     * Collect all metric values for a tenant.
     */
    private function collectMetrics(Tenant $tenant): array
    {
        return [
            'leads'         => Lead::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->count(),
            'users'         => User::where('tenant_id', $tenant->id)->count(),
            'pipelines'     => Pipeline::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->count(),
            'ai_agents'     => AiAgent::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->count(),
            'ai_tokens'     => (int) AiUsageLog::withoutGlobalScope('tenant')
                ->where('tenant_id', $tenant->id)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('tokens_total'),
            'chatbot_flows' => ChatbotFlow::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->count(),
            'automations'   => Automation::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->count(),
        ];
    }

    /**
     * Get the limit for a given metric from the tenant's plan.
     */
    private function getLimit(Tenant $tenant, string $metric): int
    {
        return match ($metric) {
            'leads'         => $tenant->max_leads ?: 0,
            'users'         => $tenant->max_users ?: 0,
            'pipelines'     => $tenant->max_pipelines ?: 0,
            'ai_agents'     => $tenant->max_ai_agents ?: 0,
            'chatbot_flows' => $tenant->max_chatbot_flows ?: 0,
            'ai_tokens'     => $this->getAiTokensLimit($tenant),
            'automations'   => 999999, // no hard limit on automations currently
            default         => 0,
        };
    }

    private function getAiTokensLimit(Tenant $tenant): int
    {
        $plan = PlanDefinition::where('name', $tenant->plan)->first();

        if (!$plan) {
            return 0;
        }

        return (int) ($plan->features_json['ai_tokens_monthly'] ?? 0);
    }

    private function meetsThreshold(UpsellTrigger $trigger, int $value, int $limit): bool
    {
        if ($trigger->threshold_type === 'absolute') {
            return $value >= (int) $trigger->threshold_value;
        }

        // percentage
        if ($limit <= 0) {
            return false;
        }

        $percentage = ($value / $limit) * 100;

        return $percentage >= (float) $trigger->threshold_value;
    }

    private function isInCooldown(UpsellTrigger $trigger, Tenant $tenant): bool
    {
        $cutoff = now()->subHours($trigger->cooldown_hours);

        return UpsellTriggerLog::withoutGlobalScope('tenant')
            ->where('upsell_trigger_id', $trigger->id)
            ->where('tenant_id', $tenant->id)
            ->where('fired_at', '>=', $cutoff)
            ->exists();
    }

    private function sendEmail(UpsellTrigger $trigger, Tenant $tenant): void
    {
        $admin = User::where('tenant_id', $tenant->id)
            ->where('role', 'admin')
            ->first();

        if (!$admin) {
            return;
        }

        try {
            Mail::to($admin->email)->send(new UpsellUpgrade($admin, $tenant, $trigger));
        } catch (\Throwable $e) {
            Log::error('Failed to send upsell email', [
                'trigger_id' => $trigger->id,
                'tenant_id'  => $tenant->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}

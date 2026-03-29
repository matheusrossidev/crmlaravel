<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Lead;
use App\Models\LeadScoreLog;
use App\Models\ScoringRule;
use App\Models\Tenant;
use App\Models\WhatsappConversation;
use App\Services\LeadScoringService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessScoringDecay extends Command
{
    protected $signature = 'scoring:decay';
    protected $description = 'Apply score decay for inactive leads (3d no reply, 7d stuck in stage)';

    public function handle(): int
    {
        $scorer = new LeadScoringService();
        $processed = 0;

        // Process each tenant
        $tenants = Tenant::where('status', 'active')
            ->orWhere('status', 'trial')
            ->get(['id']);

        foreach ($tenants as $tenant) {
            // Check if tenant has any active decay rules
            $hasDecayRules = ScoringRule::withoutGlobalScope('tenant')
                ->where('tenant_id', $tenant->id)
                ->where('is_active', true)
                ->whereIn('event_type', ['inactive_3d', 'inactive_7d'])
                ->exists();

            if (! $hasDecayRules) {
                continue;
            }

            // ── Inactive 3 days: leads with conversations that haven't replied in 3+ days ──
            $inactiveLeads = Lead::withoutGlobalScope('tenant')
                ->where('leads.tenant_id', $tenant->id)
                ->where('leads.score', '>', 0)
                ->whereHas('whatsappConversation', function ($q) {
                    $q->where('status', 'open')
                      ->where('last_message_at', '<', now()->subDays(3));
                })
                ->get();

            foreach ($inactiveLeads as $lead) {
                // Check if already decayed today for this reason
                $alreadyDecayed = LeadScoreLog::withoutGlobalScope('tenant')
                    ->where('lead_id', $lead->id)
                    ->where('reason', 'inactive_3d')
                    ->where('created_at', '>=', today())
                    ->exists();

                if (! $alreadyDecayed) {
                    $scorer->evaluate($lead, 'inactive_3d', ['tenant_id' => $tenant->id]);
                    $processed++;
                }
            }

            // ── Inactive 7 days: leads stuck in same stage for 7+ days ──
            $stuckLeads = Lead::withoutGlobalScope('tenant')
                ->where('leads.tenant_id', $tenant->id)
                ->where('leads.score', '>', 0)
                ->where('leads.updated_at', '<', now()->subDays(7))
                ->whereNotNull('leads.stage_id')
                ->get();

            foreach ($stuckLeads as $lead) {
                $alreadyDecayed = LeadScoreLog::withoutGlobalScope('tenant')
                    ->where('lead_id', $lead->id)
                    ->where('reason', 'inactive_7d')
                    ->where('created_at', '>=', today())
                    ->exists();

                if (! $alreadyDecayed) {
                    $scorer->evaluate($lead, 'inactive_7d', ['tenant_id' => $tenant->id]);
                    $processed++;
                }
            }
        }

        $this->info("Scoring decay: {$processed} leads processed.");

        return self::SUCCESS;
    }
}

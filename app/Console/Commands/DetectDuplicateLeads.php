<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Lead;
use App\Models\LeadDuplicate;
use App\Models\Tenant;
use App\Services\DuplicateLeadDetector;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DetectDuplicateLeads extends Command
{
    protected $signature = 'leads:detect-duplicates {--tenant= : Process only a specific tenant ID}';
    protected $description = 'Scan active leads for potential duplicates (phone/email)';

    public function handle(): int
    {
        $detector = new DuplicateLeadDetector();
        $totalFound = 0;

        $tenants = Tenant::withoutGlobalScope('tenant')
            ->whereIn('status', ['active', 'trial', 'partner']);

        if ($tenantId = $this->option('tenant')) {
            $tenants->where('id', $tenantId);
        }

        $tenants->each(function (Tenant $tenant) use ($detector, &$totalFound) {
            $found = $this->processTenant($tenant, $detector);
            $totalFound += $found;
        });

        $this->info("Done. {$totalFound} new duplicate pair(s) detected.");
        Log::info("DetectDuplicateLeads: {$totalFound} pairs found");

        return self::SUCCESS;
    }

    private function processTenant(Tenant $tenant, DuplicateLeadDetector $detector): int
    {
        $found = 0;

        // Group by normalized phone — O(n) instead of O(n²)
        $leads = Lead::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->get(['id', 'tenant_id', 'name', 'phone', 'email', 'company',
                   'source', 'tags', 'pipeline_id', 'stage_id', 'assigned_to',
                   'value', 'birthday', 'instagram_username', 'status',
                   'merged_into', 'merged_at', 'created_at']);

        // Phone-based grouping
        $phoneGroups = [];
        foreach ($leads as $lead) {
            if (!$lead->phone) continue;
            $normalized = $detector->normalizePhone($lead->phone);
            if (strlen($normalized) < 8) continue;
            // Use last 11 digits as key (ignores country code)
            $key = substr($normalized, -min(11, strlen($normalized)));
            $phoneGroups[$key][] = $lead;
        }

        foreach ($phoneGroups as $group) {
            if (count($group) < 2) continue;
            $found += $this->createPairsFromGroup($group, $detector, $tenant->id);
        }

        // Email-based grouping
        $emailGroups = [];
        foreach ($leads as $lead) {
            if (!$lead->email) continue;
            $key = strtolower($lead->email);
            $emailGroups[$key][] = $lead;
        }

        foreach ($emailGroups as $group) {
            if (count($group) < 2) continue;
            $found += $this->createPairsFromGroup($group, $detector, $tenant->id);
        }

        if ($found > 0) {
            $this->line("  Tenant #{$tenant->id} ({$tenant->name}): {$found} pairs");
        }

        return $found;
    }

    private function createPairsFromGroup(array $leads, DuplicateLeadDetector $detector, int $tenantId): int
    {
        $created = 0;

        for ($i = 0; $i < count($leads); $i++) {
            for ($j = $i + 1; $j < count($leads); $j++) {
                $a = $leads[$i];
                $b = $leads[$j];

                $score = $detector->scoreMatch($a, $b);
                if ($score < 40) continue;

                $idA = min($a->id, $b->id);
                $idB = max($a->id, $b->id);

                $existing = LeadDuplicate::where('lead_id_a', $idA)
                    ->where('lead_id_b', $idB)
                    ->exists();

                if ($existing) continue;

                LeadDuplicate::create([
                    'tenant_id'   => $tenantId,
                    'lead_id_a'   => $idA,
                    'lead_id_b'   => $idB,
                    'score'       => $score,
                    'status'      => 'pending',
                    'detected_by' => 'scheduled_job',
                    'created_at'  => now(),
                ]);

                $created++;
            }
        }

        return $created;
    }
}

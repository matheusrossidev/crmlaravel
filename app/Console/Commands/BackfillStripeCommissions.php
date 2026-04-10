<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\PartnerCommission;
use App\Models\PaymentLog;
use App\Models\Tenant;
use App\Services\PartnerCommissionService;
use Illuminate\Console\Command;

class BackfillStripeCommissions extends Command
{
    protected $signature   = 'partners:backfill-stripe-commissions {--dry-run : Simula sem criar}';
    protected $description = 'Cria comissões de parceiro pra pagamentos Stripe que não geraram (bug histórico)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $this->line(($dryRun ? '[DRY-RUN] ' : '') . 'Buscando pagamentos Stripe sem comissão...');

        // PaymentLog com IDs do Stripe (cs_ = checkout session, in_ = invoice)
        $payments = PaymentLog::where('status', 'confirmed')
            ->where(function ($q) {
                $q->where('asaas_payment_id', 'like', 'cs_%')
                  ->orWhere('asaas_payment_id', 'like', 'in_%');
            })
            ->whereNotNull('tenant_id')
            ->orderBy('id')
            ->get();

        $created = 0;
        $skipped = 0;

        foreach ($payments as $pl) {
            $tenant = Tenant::find($pl->tenant_id);
            if (! $tenant || ! $tenant->referred_by_agency_id) {
                continue; // Tenant sem parceiro indicador
            }

            // Já tem comissão pra esse payment?
            $exists = PartnerCommission::where('asaas_payment_id', $pl->asaas_payment_id)->exists();
            if ($exists) {
                $skipped++;
                continue;
            }

            $amount = (float) $pl->amount;
            if ($amount <= 0) {
                continue;
            }

            if ($dryRun) {
                $this->line("  [CRIARIA] tenant={$tenant->id} ({$tenant->name}) amount=R\${$amount} payment={$pl->asaas_payment_id} partner={$tenant->referred_by_agency_id}");
                $created++;
                continue;
            }

            PartnerCommissionService::generateCommission($tenant, $amount, $pl->asaas_payment_id);
            $created++;
            $this->line("  ✓ tenant={$tenant->id} ({$tenant->name}) payment={$pl->asaas_payment_id}");
        }

        $this->line('');
        $this->line(($dryRun ? '[DRY-RUN] ' : '') . "Criadas: {$created} | Já existiam: {$skipped}");

        return self::SUCCESS;
    }
}

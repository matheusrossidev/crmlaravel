<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PartnerCommission;
use App\Models\PartnerRank;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;

class PartnerCommissionService
{
    /**
     * Gera comissão de parceiro quando um tenant indicado faz pagamento.
     *
     * Centraliza a lógica que antes vivia duplicada no AsaasWebhookController.
     * Chamado por AMBOS os webhook controllers (Asaas e Stripe).
     *
     * Aceita $paymentId de qualquer gateway (Asaas payment_id, Stripe session_id,
     * Stripe invoice_id) — usado pra dedup atomico via lockForUpdate.
     *
     * A comissão fica 'pending' por 30 dias antes de virar 'available' pra saque
     * (cron partners:release-commissions). Se o tenant cancelar nesse período,
     * a comissão é cancelada automaticamente pelo webhook de subscription.deleted.
     */
    public static function generateCommission(Tenant $tenant, float $paymentValue, ?string $paymentId = null): void
    {
        if ($paymentValue <= 0 || ! $tenant->referred_by_agency_id) {
            return;
        }

        $partnerTenantId = $tenant->referred_by_agency_id;

        // Dedup atômico por payment_id — previne comissão duplicada se o webhook
        // chegar 2x (retry do gateway). Funciona com IDs de qualquer gateway.
        if ($paymentId) {
            $exists = PartnerCommission::where('asaas_payment_id', $paymentId)
                ->lockForUpdate()
                ->exists();
            if ($exists) {
                return;
            }
        }

        // Commission % locked no tenant (setado na primeira compra do cliente).
        // Se ainda não foi setado (tenant legado ou primeiro pagamento), calcula
        // a partir do rank atual do parceiro e locka pra todos os pagamentos futuros.
        $commissionPct = (float) ($tenant->partner_commission_pct ?? 0);

        if ($commissionPct <= 0) {
            $activeClients = Tenant::where('referred_by_agency_id', $partnerTenantId)
                ->whereIn('status', ['active', 'partner', 'trial'])
                ->count();

            $rank = PartnerRank::forSalesCount($activeClients);
            $commissionPct = (float) ($rank?->commission_pct ?? 0);

            if ($commissionPct > 0) {
                $tenant->update(['partner_commission_pct' => $commissionPct]);
            }
        }

        if ($commissionPct <= 0) {
            return;
        }

        $amount    = round($paymentValue * ($commissionPct / 100), 2);
        $graceDays = 30;

        PartnerCommission::create([
            'tenant_id'        => $partnerTenantId,
            'client_tenant_id' => $tenant->id,
            'asaas_payment_id' => $paymentId,
            'amount'           => $amount,
            'status'           => 'pending',
            'available_at'     => now()->addDays($graceDays)->toDateString(),
        ]);

        Log::info("PartnerCommission: R\${$amount} ({$commissionPct}% locked) para partner {$partnerTenantId} via {$paymentId}");
    }
}

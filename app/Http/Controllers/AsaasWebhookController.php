<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Mail\PaymentFailed;
use App\Models\PaymentLog;
use App\Models\Tenant;
use App\Models\TenantTokenIncrement;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;

class AsaasWebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        // Validar token do webhook
        $token = $request->header('asaas-access-token')
            ?? $request->header('userAuthToken')
            ?? $request->input('userAuthToken');

        $expectedToken = config('services.asaas.webhook_token');
        if ($expectedToken && $token !== $expectedToken) {
            \Log::warning('AsaasWebhook: token inválido', ['ip' => $request->ip()]);
            return response('Unauthorized', 401);
        }

        $payload = $request->all();
        $event   = $payload['event'] ?? null;

        \Log::info("AsaasWebhook recebido: {$event}", ['payload' => $payload]);

        try {
            $this->processEvent($event, $payload);
        } catch (\Throwable $e) {
            \Log::error('AsaasWebhook erro ao processar evento', [
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
        }

        // Sempre retornar 200 para o Asaas não retentar
        return response('OK', 200);
    }

    private function processEvent(string|null $event, array $payload): void
    {
        if (!$event) {
            return;
        }

        // ── Transfer events (partner withdrawals) ────────────────────
        if (str_starts_with($event, 'TRANSFER_')) {
            $this->handleTransferEvent($event, $payload);
            return;
        }

        // ── Refund events (cancel partner commission) ────────────────
        if (in_array($event, ['PAYMENT_REFUNDED', 'PAYMENT_CHARGEBACK_REQUESTED'], true)) {
            $this->handlePaymentRefund($payload);
            // Don't return — let it continue to handle subscription status too
        }

        // Pagamentos de incremento de tokens identificados pelo externalReference
        $extRef = $payload['payment']['externalReference'] ?? '';
        if (
            in_array($event, ['PAYMENT_RECEIVED', 'PAYMENT_CONFIRMED'], true)
            && str_starts_with($extRef, 'token_increment:')
        ) {
            $this->handleTokenIncrementPaid($extRef);
            return;
        }

        $subscriptionId = $payload['payment']['subscription'] ?? null;
        if (!$subscriptionId) {
            return;
        }

        $tenant = Tenant::where('asaas_subscription_id', $subscriptionId)->first();
        if (!$tenant) {
            \Log::warning("AsaasWebhook: tenant não encontrado para subscription {$subscriptionId}");
            return;
        }

        match($event) {
            'PAYMENT_RECEIVED',
            'PAYMENT_CONFIRMED'      => $this->handlePaymentConfirmed($tenant, $payload),
            'PAYMENT_OVERDUE'        => $this->handlePaymentOverdue($tenant),
            'SUBSCRIPTION_INACTIVATED',
            'PAYMENT_DELETED'        => $this->handleSubscriptionInactivated($tenant),
            default                  => null,
        };
    }

    private function handleTokenIncrementPaid(string $externalReference): void
    {
        $incrementId = (int) str_replace('token_increment:', '', $externalReference);
        $increment   = TenantTokenIncrement::find($incrementId);

        if (! $increment || $increment->status === 'paid') {
            return;
        }

        $increment->update(['status' => 'paid', 'paid_at' => now()]);

        // Libera o agente desativado por quota
        Tenant::withoutGlobalScope('tenant')
            ->where('id', $increment->tenant_id)
            ->update(['ai_tokens_exhausted' => false]);

        // Registrar pagamento
        PaymentLog::create([
            'tenant_id'        => $increment->tenant_id,
            'type'             => 'token_increment',
            'description'      => "Pacote de {$increment->tokens_added} tokens",
            'amount'           => $increment->price_paid ?? 0,
            'asaas_payment_id' => null,
            'status'           => 'confirmed',
            'paid_at'          => now(),
        ]);

        // Notifica grupo master via WhatsApp
        $tokenTenant = Tenant::withoutGlobalScope('tenant')->find($increment->tenant_id);
        if ($tokenTenant) {
            \App\Services\MasterWhatsappNotifier::tokenPurchase(
                $tokenTenant,
                (int) $increment->tokens_added,
                (float) ($increment->price_paid ?? 0),
                'Asaas',
            );
        }

        \Log::info('AsaasWebhook: incremento de tokens confirmado', [
            'increment_id' => $increment->id,
            'tenant_id'    => $increment->tenant_id,
            'tokens_added' => $increment->tokens_added,
        ]);
    }

    private function handlePaymentConfirmed(Tenant $tenant, array $payload = []): void
    {
        $tenant->update([
            'subscription_status' => 'active',
            'status'              => $tenant->plan === 'partner' ? 'partner' : 'active',
        ]);

        // Registrar pagamento
        $paymentValue = $payload['payment']['value'] ?? null;
        $paymentId    = $payload['payment']['id'] ?? null;

        if ($paymentValue) {
            PaymentLog::create([
                'tenant_id'        => $tenant->id,
                'type'             => 'subscription',
                'description'      => "Assinatura plano {$tenant->plan}",
                'amount'           => (float) $paymentValue,
                'asaas_payment_id' => $paymentId,
                'status'           => 'confirmed',
                'paid_at'          => now(),
            ]);
        }

        // Notifica grupo master via WhatsApp
        if ($paymentValue) {
            \App\Services\MasterWhatsappNotifier::paymentConfirmed(
                $tenant,
                (float) $paymentValue,
                'Asaas',
                $paymentId,
            );
        }

        // Gera comissão para parceiro se tenant foi indicado
        $this->generatePartnerCommission($tenant, $paymentValue, $paymentId);

        \Log::info("AsaasWebhook: pagamento confirmado para tenant {$tenant->id}");
    }

    private function generatePartnerCommission(Tenant $tenant, ?float $paymentValue, ?string $paymentId): void
    {
        if (!$paymentValue || $paymentValue <= 0 || !$tenant->referred_by_agency_id) {
            return;
        }

        $partnerTenantId = $tenant->referred_by_agency_id;

        // Avoid duplicate commission for same payment
        if ($paymentId && \App\Models\PartnerCommission::where('asaas_payment_id', $paymentId)->exists()) {
            return;
        }

        // Get partner rank to determine commission percentage
        $activeClients = Tenant::withoutGlobalScope('tenant')
            ->where('referred_by_agency_id', $partnerTenantId)
            ->whereIn('status', ['active', 'partner', 'trial'])
            ->count();

        $rank = \App\Models\PartnerRank::forSalesCount($activeClients);
        $commissionPct = $rank?->commission_pct ?? 0;

        if ($commissionPct <= 0) {
            return;
        }

        $commissionAmount = round($paymentValue * ($commissionPct / 100), 2);
        $graceDays = 30; // carência antes de liberar

        \App\Models\PartnerCommission::create([
            'tenant_id'        => $partnerTenantId,
            'client_tenant_id' => $tenant->id,
            'asaas_payment_id' => $paymentId,
            'amount'           => $commissionAmount,
            'status'           => 'pending',
            'available_at'     => now()->addDays($graceDays)->toDateString(),
        ]);

        \Log::info("PartnerCommission: R\${$commissionAmount} ({$commissionPct}%) para tenant {$partnerTenantId} de pagamento {$paymentId}");
    }

    private function handlePaymentOverdue(Tenant $tenant): void
    {
        $tenant->update(['subscription_status' => 'overdue']);

        // Envia email de falha de pagamento para o admin do tenant
        $admin = $tenant->users()->where('role', 'admin')->first();
        if ($admin) {
            try {
                Mail::to($admin->email)->send(new PaymentFailed($admin, $tenant));
            } catch (\Throwable $e) {
                \Log::warning('AsaasWebhook: falha ao enviar email PaymentFailed', ['error' => $e->getMessage()]);
            }
        }

        \Log::info("AsaasWebhook: pagamento atrasado para tenant {$tenant->id}");
    }

    private function handleSubscriptionInactivated(Tenant $tenant): void
    {
        $tenant->update([
            'subscription_status' => 'inactive',
            'status'              => 'suspended',
        ]);
        \Log::info("AsaasWebhook: assinatura inativada para tenant {$tenant->id}");
    }

    // ── Transfer webhook (partner withdrawals) ───────────────────────

    private function handleTransferEvent(string $event, array $payload): void
    {
        $transferId = $payload['transfer']['id'] ?? null;
        $extRef     = $payload['transfer']['externalReference'] ?? '';

        if (!str_starts_with($extRef, 'withdrawal:')) {
            return; // not a partner withdrawal
        }

        $withdrawalId = (int) str_replace('withdrawal:', '', $extRef);
        $withdrawal = \App\Models\PartnerWithdrawal::find($withdrawalId);

        if (!$withdrawal) {
            \Log::warning("AsaasWebhook: withdrawal #{$withdrawalId} não encontrado para transfer {$transferId}");
            return;
        }

        match ($event) {
            'TRANSFER_DONE' => $withdrawal->update([
                'status'  => 'paid',
                'paid_at' => now(),
            ]),
            'TRANSFER_FAILED', 'TRANSFER_CANCELLED' => $withdrawal->update([
                'status'          => 'approved', // volta para aprovado, pode tentar novamente
                'rejected_reason' => "Transferência falhou: {$event}",
            ]),
            default => null,
        };

        \Log::info("AsaasWebhook: transfer {$event} para withdrawal #{$withdrawalId}");
    }

    // ── Payment refund (cancel partner commission) ───────────────────

    private function handlePaymentRefund(array $payload): void
    {
        $paymentId = $payload['payment']['id'] ?? null;
        if (!$paymentId) {
            return;
        }

        $cancelled = \App\Models\PartnerCommission::where('asaas_payment_id', $paymentId)
            ->whereIn('status', ['pending', 'available'])
            ->update(['status' => 'cancelled']);

        if ($cancelled > 0) {
            \Log::info("AsaasWebhook: {$cancelled} comissão(ões) cancelada(s) por estorno do pagamento {$paymentId}");
        }
    }
}

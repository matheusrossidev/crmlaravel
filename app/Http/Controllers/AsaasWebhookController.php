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

        \Log::info("AsaasWebhook: pagamento confirmado para tenant {$tenant->id}");
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
}

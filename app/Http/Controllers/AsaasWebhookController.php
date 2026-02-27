<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Mail\PaymentFailed;
use App\Models\Tenant;
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
            'PAYMENT_CONFIRMED'      => $this->handlePaymentConfirmed($tenant),
            'PAYMENT_OVERDUE'        => $this->handlePaymentOverdue($tenant),
            'SUBSCRIPTION_INACTIVATED',
            'PAYMENT_DELETED'        => $this->handleSubscriptionInactivated($tenant),
            default                  => null,
        };
    }

    private function handlePaymentConfirmed(Tenant $tenant): void
    {
        $tenant->update([
            'subscription_status' => 'active',
            'status'              => 'active',
        ]);
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

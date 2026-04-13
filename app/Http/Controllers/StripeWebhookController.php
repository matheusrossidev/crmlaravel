<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PaymentLog;
use App\Models\Tenant;
use App\Models\TenantTokenIncrement;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        $payload   = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature', '');

        try {
            $event = (new StripeService())->constructWebhookEvent($payload, $sigHeader);
        } catch (\Stripe\Exception\SignatureVerificationException) {
            Log::warning('Stripe webhook: assinatura inválida');
            return response('Invalid signature', 400);
        } catch (\Throwable $e) {
            Log::error('Stripe webhook: erro ao construir evento', ['error' => $e->getMessage()]);
            return response('Webhook error', 400);
        }

        Log::info('Stripe webhook recebido', ['type' => $event->type, 'id' => $event->id]);

        match ($event->type) {
            'checkout.session.completed'      => $this->handleCheckoutCompleted($event->data->object),
            'invoice.payment_succeeded'       => $this->handleInvoicePaid($event->data->object),
            'invoice.payment_failed'          => $this->handleInvoiceFailed($event->data->object),
            'customer.subscription.deleted'   => $this->handleSubscriptionDeleted($event->data->object),
            default                           => null,
        };

        return response('OK', 200);
    }

    // ── Checkout Session Completed ──────────────────────────────────────────

    private function handleCheckoutCompleted(object $session): void
    {
        $metadata = (array) ($session->metadata ?? []);
        $tenantId = $metadata['tenant_id'] ?? null;

        // Fallback: buscar tenant pelo stripe_customer_id quando metadata não tem tenant_id
        // (pode acontecer se session foi criada antes do fix do metadata, ou por checkout externo)
        if (! $tenantId && ! empty($session->customer)) {
            $fallbackTenant = Tenant::where('stripe_customer_id', $session->customer)->first();
            if ($fallbackTenant) {
                $tenantId = $fallbackTenant->id;
                Log::info('Stripe: tenant_id resolvido via stripe_customer_id fallback', [
                    'tenant_id'   => $tenantId,
                    'customer_id' => $session->customer,
                    'session_id'  => $session->id,
                ]);
            }
        }

        if (! $tenantId) {
            Log::warning('Stripe checkout.session.completed sem tenant_id', ['session_id' => $session->id, 'customer' => $session->customer ?? null]);
            return;
        }

        $tenant = Tenant::find($tenantId);
        if (! $tenant) {
            return;
        }

        // Token increment checkout
        if (($metadata['type'] ?? '') === 'token_increment') {
            $this->handleTokenIncrementPaid($tenant, $metadata);
            return;
        }

        // Subscription checkout
        $subscriptionId = $session->subscription ?? null;
        $customerId     = $session->customer ?? null;
        $planName       = $metadata['plan_name'] ?? null;

        if ($subscriptionId && $planName) {
            $plan = \App\Models\PlanDefinition::where('name', $planName)->first();

            $tenant->update([
                'stripe_customer_id'     => $customerId,
                'stripe_subscription_id' => $subscriptionId,
                'subscription_status'    => 'active',
                'plan'                   => $planName,
                'status'                 => $tenant->isPartner() ? 'partner' : 'active',
                'max_users'              => $plan?->features_json['max_users'] ?? $tenant->max_users,
                'max_leads'              => $plan?->features_json['max_leads'] ?? $tenant->max_leads,
                'max_pipelines'          => $plan?->features_json['max_pipelines'] ?? $tenant->max_pipelines,
                'max_custom_fields'      => $plan?->features_json['max_custom_fields'] ?? $tenant->max_custom_fields,
                'max_chatbot_flows'      => $plan?->features_json['max_chatbot_flows'] ?? $tenant->max_chatbot_flows,
                'max_ai_agents'          => $plan?->features_json['max_ai_agents'] ?? $tenant->max_ai_agents,
                'max_departments'        => $plan?->features_json['max_departments'] ?? $tenant->max_departments,
            ]);

            PaymentLog::create([
                'tenant_id'        => $tenant->id,
                'type'             => 'subscription',
                'description'      => "Stripe subscription: {$planName}",
                'amount'           => ($session->amount_total ?? 0) / 100,
                'asaas_payment_id' => $session->id,
                'status'           => 'confirmed',
                'paid_at'          => now(),
            ]);

            // Notifica grupo master via WhatsApp
            \App\Services\MasterWhatsappNotifier::paymentConfirmed(
                $tenant,
                ($session->amount_total ?? 0) / 100,
                'Stripe',
                $session->id ?? null,
            );

            // Gera comissão pra parceiro (se tenant foi indicado por um)
            \App\Services\PartnerCommissionService::generateCommission(
                $tenant,
                ($session->amount_total ?? 0) / 100,
                $session->id,
            );

            Log::info('Stripe: subscription ativada', [
                'tenant_id'       => $tenant->id,
                'plan'            => $planName,
                'subscription_id' => $subscriptionId,
            ]);
        }
    }

    // ── Invoice Paid (recurring) ────────────────────────────────────────────

    private function handleInvoicePaid(object $invoice): void
    {
        $subscriptionId = $invoice->subscription ?? null;
        if (! $subscriptionId) {
            return;
        }

        $tenant = Tenant::where('stripe_subscription_id', $subscriptionId)->first();
        if (! $tenant) {
            return;
        }

        $tenant->update([
            'subscription_status' => 'active',
            'status'              => $tenant->isPartner() ? 'partner' : 'active',
        ]);

        PaymentLog::create([
            'tenant_id'        => $tenant->id,
            'type'             => 'subscription',
            'description'      => "Stripe invoice paid",
            'amount'           => ($invoice->amount_paid ?? 0) / 100,
            'asaas_payment_id' => $invoice->id,
            'status'           => 'confirmed',
            'paid_at'          => now(),
        ]);

        // Notifica grupo master via WhatsApp
        \App\Services\MasterWhatsappNotifier::paymentConfirmed(
            $tenant,
            ($invoice->amount_paid ?? 0) / 100,
            'Stripe',
            $invoice->id ?? null,
        );

        // Gera comissão pra parceiro (pagamento recorrente)
        \App\Services\PartnerCommissionService::generateCommission(
            $tenant,
            ($invoice->amount_paid ?? 0) / 100,
            $invoice->id,
        );

        Log::info('Stripe: invoice paga', ['tenant_id' => $tenant->id, 'invoice_id' => $invoice->id]);
    }

    // ── Invoice Failed ──────────────────────────────────────────────────────

    private function handleInvoiceFailed(object $invoice): void
    {
        $subscriptionId = $invoice->subscription ?? null;
        if (! $subscriptionId) {
            return;
        }

        $tenant = Tenant::where('stripe_subscription_id', $subscriptionId)->first();
        if (! $tenant) {
            return;
        }

        $tenant->update(['subscription_status' => 'overdue']);

        Log::warning('Stripe: invoice falhou', ['tenant_id' => $tenant->id, 'invoice_id' => $invoice->id]);
    }

    // ── Subscription Deleted ────────────────────────────────────────────────

    private function handleSubscriptionDeleted(object $subscription): void
    {
        $tenant = Tenant::where('stripe_subscription_id', $subscription->id)->first();
        if (! $tenant) {
            return;
        }

        $tenant->update([
            'subscription_status'    => 'cancelled',
            'stripe_subscription_id' => null,
            'status'                 => 'suspended',
        ]);

        // Cancelar comissões pendentes do parceiro que indicou este tenant
        if ($tenant->referred_by_agency_id) {
            $cancelled = \App\Models\PartnerCommission::where('client_tenant_id', $tenant->id)
                ->where('status', 'pending')
                ->update(['status' => 'cancelled']);

            if ($cancelled > 0) {
                Log::info("Stripe: {$cancelled} comissão(ões) pendente(s) cancelada(s) — tenant {$tenant->id} suspenso");
            }
        }

        Log::info('Stripe: subscription cancelada', ['tenant_id' => $tenant->id]);
    }

    // ── Token Increment ─────────────────────────────────────────────────────

    private function handleTokenIncrementPaid(Tenant $tenant, array $metadata): void
    {
        $incrementId = $metadata['increment_id'] ?? null;
        if (! $incrementId) {
            return;
        }

        $increment = TenantTokenIncrement::find($incrementId);
        if ($increment && $increment->status !== 'paid') {
            $increment->update([
                'status'  => 'paid',
                'paid_at' => now(),
            ]);

            $tenant->update(['ai_tokens_exhausted' => false]);

            PaymentLog::create([
                'tenant_id'        => $tenant->id,
                'type'             => 'token_increment',
                'description'      => "Stripe token increment: +{$increment->tokens_added}",
                'amount'           => $increment->price_paid,
                'asaas_payment_id' => $metadata['session_id'] ?? null,
                'status'           => 'confirmed',
                'paid_at'          => now(),
            ]);

            // Notifica grupo master via WhatsApp
            \App\Services\MasterWhatsappNotifier::tokenPurchase(
                $tenant,
                (int) $increment->tokens_added,
                (float) ($increment->price_paid ?? 0),
                'Stripe',
            );

            Log::info('Stripe: token increment pago', [
                'tenant_id'    => $tenant->id,
                'increment_id' => $incrementId,
                'tokens'       => $increment->tokens_added,
            ]);
        }
    }
}

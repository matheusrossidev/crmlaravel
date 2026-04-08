<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session as CheckoutSession;
use Stripe\Customer;
use Stripe\BillingPortal\Session as PortalSession;
use Stripe\Stripe;
use Stripe\Subscription;
use Stripe\Webhook;

class StripeService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    // ── Customer ─────────────────────────────────────────────────────────────

    public function createCustomer(string $email, string $name): Customer
    {
        return Customer::create([
            'email' => $email,
            'name'  => $name,
        ]);
    }

    public function getOrCreateCustomer(string $email, string $name, ?string $existingId = null): Customer
    {
        if ($existingId) {
            try {
                return Customer::retrieve($existingId);
            } catch (\Throwable $e) {
                Log::warning('Stripe: customer não encontrado, criando novo', [
                    'existing_id' => $existingId,
                    'error'       => $e->getMessage(),
                ]);
            }
        }

        return $this->createCustomer($email, $name);
    }

    // ── Checkout Session (redirect) ──────────────────────────────────────────

    /**
     * Create a Stripe Checkout Session for a subscription.
     * Returns the Checkout URL to redirect the user to.
     *
     * NOTA: Stripe nao suporta PIX em mode=subscription (recurring) — so em
     * mode=payment (one-time). Por isso `payment_method_types` aqui fica
     * limitado a metodos compativeis com subscriptions: card (sempre) e
     * outros como sepa_debit, boleto, etc se a moeda permitir. Pra novos
     * brasileiros oferecemos so cartao em recurring; PIX so esta disponivel
     * em compras one-time como token increments.
     */
    public function createSubscriptionCheckout(
        string $customerId,
        string $priceId,
        string $successUrl,
        string $cancelUrl,
        array $metadata = [],
        array $paymentMethodTypes = ['card'],
    ): CheckoutSession {
        return CheckoutSession::create([
            'customer'             => $customerId,
            'mode'                 => 'subscription',
            'payment_method_types' => $paymentMethodTypes,
            'line_items'           => [
                [
                    'price'    => $priceId,
                    'quantity' => 1,
                ],
            ],
            'success_url'          => $successUrl,
            'cancel_url'           => $cancelUrl,
            'metadata'             => $metadata,
            'allow_promotion_codes'=> true,
        ]);
    }

    /**
     * Create a Stripe Checkout Session for a one-time payment (token increment).
     */
    public function createPaymentCheckout(
        string $customerId,
        string $priceId,
        string $successUrl,
        string $cancelUrl,
        array $metadata = [],
    ): CheckoutSession {
        return CheckoutSession::create([
            'customer'             => $customerId,
            'mode'                 => 'payment',
            'payment_method_types' => ['card'],
            'line_items'           => [
                [
                    'price'    => $priceId,
                    'quantity' => 1,
                ],
            ],
            'success_url' => $successUrl,
            'cancel_url'  => $cancelUrl,
            'metadata'    => $metadata,
        ]);
    }

    // ── Customer Portal ──────────────────────────────────────────────────────

    /**
     * Create a Stripe Customer Portal session for managing subscription.
     */
    public function createPortalSession(string $customerId, string $returnUrl): PortalSession
    {
        return PortalSession::create([
            'customer'   => $customerId,
            'return_url' => $returnUrl,
        ]);
    }

    // ── Subscription Management ──────────────────────────────────────────────

    public function cancelSubscription(string $subscriptionId): Subscription
    {
        return Subscription::retrieve($subscriptionId)->cancel();
    }

    public function getSubscription(string $subscriptionId): Subscription
    {
        return Subscription::retrieve($subscriptionId);
    }

    // ── Webhook ──────────────────────────────────────────────────────────────

    /**
     * Construct and verify a Stripe webhook event from the raw payload.
     */
    public function constructWebhookEvent(string $payload, string $sigHeader): \Stripe\Event
    {
        return Webhook::constructEvent(
            $payload,
            $sigHeader,
            config('services.stripe.webhook_secret'),
        );
    }

    // ── Checkout Session retrieval ───────────────────────────────────────────

    public function retrieveCheckoutSession(string $sessionId): CheckoutSession
    {
        return CheckoutSession::retrieve([
            'id'     => $sessionId,
            'expand' => ['subscription', 'customer'],
        ]);
    }
}

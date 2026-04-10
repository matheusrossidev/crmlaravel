<?php

declare(strict_types=1);

namespace Tests\Feature\Webhook;

use App\Http\Controllers\StripeWebhookController;
use App\Models\PartnerCommission;
use App\Models\PaymentLog;
use App\Models\Tenant;
use App\Models\TenantTokenIncrement;
use Tests\TestCase;

/**
 * Tests for StripeWebhookController handlers.
 *
 * We call the private handler methods directly via reflection because
 * the controller uses `new StripeService()` (not injectable) for
 * signature verification — HTTP tests would need real Stripe signatures.
 */
class StripeWebhookTest extends TestCase
{
    private Tenant $clientTenant;
    private StripeWebhookController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clientTenant = Tenant::factory()->withStripe()->create([
            'plan'                   => 'professional',
            'stripe_subscription_id' => 'sub_existing',
        ]);

        $this->controller = new StripeWebhookController();
    }

    // ── checkout.session.completed ─────────────────────────────────────

    public function test_checkout_completed_activates_subscription(): void
    {
        $session = $this->fakeSession();

        $this->callHandler('handleCheckoutCompleted', $session);

        $this->clientTenant->refresh();
        $this->assertEquals('active', $this->clientTenant->subscription_status);
        $this->assertNotNull($this->clientTenant->stripe_subscription_id);

        $this->assertDatabaseHas('payment_logs', [
            'tenant_id' => $this->clientTenant->id,
            'type'      => 'subscription',
            'status'    => 'confirmed',
        ]);
    }

    public function test_checkout_without_tenant_id_is_ignored(): void
    {
        $session = $this->fakeSession(tenantId: null);

        $this->callHandler('handleCheckoutCompleted', $session);

        $this->assertDatabaseMissing('payment_logs', [
            'tenant_id' => $this->clientTenant->id,
        ]);
    }

    public function test_checkout_generates_partner_commission(): void
    {
        $partner = Tenant::factory()->partner()->create();
        $this->clientTenant->update([
            'referred_by_agency_id'  => $partner->id,
            'partner_commission_pct' => 20,
        ]);

        $session = $this->fakeSession(amount: 29900);

        $this->callHandler('handleCheckoutCompleted', $session);

        $this->assertDatabaseHas('partner_commissions', [
            'tenant_id'        => $partner->id,
            'client_tenant_id' => $this->clientTenant->id,
            'status'           => 'pending',
        ]);

        $commission = PartnerCommission::where('client_tenant_id', $this->clientTenant->id)->first();
        $this->assertEquals(59.80, (float) $commission->amount); // 20% of 299.00
    }

    public function test_checkout_dedup_prevents_duplicate_commission(): void
    {
        $partner = Tenant::factory()->partner()->create();
        $this->clientTenant->update([
            'referred_by_agency_id'  => $partner->id,
            'partner_commission_pct' => 20,
        ]);

        $session = $this->fakeSession();

        $this->callHandler('handleCheckoutCompleted', $session);
        $this->callHandler('handleCheckoutCompleted', $session);

        $count = PartnerCommission::where('client_tenant_id', $this->clientTenant->id)->count();
        $this->assertEquals(1, $count);
    }

    // ── invoice.payment_succeeded ──────────────────────────────────────

    public function test_invoice_paid_creates_payment_log(): void
    {
        $invoice = $this->fakeInvoice();

        $this->callHandler('handleInvoicePaid', $invoice);

        $this->assertDatabaseHas('payment_logs', [
            'tenant_id' => $this->clientTenant->id,
            'type'      => 'subscription',
            'status'    => 'confirmed',
        ]);

        $this->clientTenant->refresh();
        $this->assertEquals('active', $this->clientTenant->subscription_status);
    }

    public function test_invoice_paid_generates_commission_for_recurring(): void
    {
        $partner = Tenant::factory()->partner()->create();
        $this->clientTenant->update([
            'referred_by_agency_id'  => $partner->id,
            'partner_commission_pct' => 15,
        ]);

        $invoice = $this->fakeInvoice(amount: 19900);

        $this->callHandler('handleInvoicePaid', $invoice);

        $commission = PartnerCommission::where('client_tenant_id', $this->clientTenant->id)->first();
        $this->assertNotNull($commission);
        $this->assertEquals(29.85, (float) $commission->amount); // 15% of 199.00
    }

    // ── invoice.payment_failed ─────────────────────────────────────────

    public function test_invoice_failed_marks_tenant_overdue(): void
    {
        $invoice = $this->fakeInvoice();

        $this->callHandler('handleInvoiceFailed', $invoice);

        $this->clientTenant->refresh();
        $this->assertEquals('overdue', $this->clientTenant->subscription_status);
    }

    // ── customer.subscription.deleted ──────────────────────────────────

    public function test_subscription_deleted_suspends_tenant(): void
    {
        $subscription = (object) ['id' => 'sub_existing'];

        $this->callHandler('handleSubscriptionDeleted', $subscription);

        $this->clientTenant->refresh();
        $this->assertEquals('cancelled', $this->clientTenant->subscription_status);
        $this->assertEquals('suspended', $this->clientTenant->status);
        $this->assertNull($this->clientTenant->stripe_subscription_id);
    }

    public function test_subscription_deleted_cancels_pending_commissions(): void
    {
        $partner = Tenant::factory()->partner()->create();
        $this->clientTenant->update([
            'referred_by_agency_id'  => $partner->id,
            'stripe_subscription_id' => 'sub_cancel',
        ]);

        PartnerCommission::factory()->count(2)->create([
            'tenant_id'        => $partner->id,
            'client_tenant_id' => $this->clientTenant->id,
            'status'           => 'pending',
        ]);
        PartnerCommission::factory()->create([
            'tenant_id'        => $partner->id,
            'client_tenant_id' => $this->clientTenant->id,
            'status'           => 'available',
        ]);

        $subscription = (object) ['id' => 'sub_cancel'];
        $this->callHandler('handleSubscriptionDeleted', $subscription);

        $this->assertEquals(2, PartnerCommission::where('client_tenant_id', $this->clientTenant->id)
            ->where('status', 'cancelled')->count());
        $this->assertEquals(1, PartnerCommission::where('client_tenant_id', $this->clientTenant->id)
            ->where('status', 'available')->count());
    }

    // ── Token increment ────────────────────────────────────────────────

    public function test_token_increment_checkout_marks_paid(): void
    {
        // Create required plan first
        $plan = \App\Models\TokenIncrementPlan::create([
            'display_name'  => 'Pacote 100k',
            'tokens_amount' => 100000,
            'price'         => 49.90,
            'is_active'     => true,
        ]);

        $increment = TenantTokenIncrement::create([
            'tenant_id'               => $this->clientTenant->id,
            'token_increment_plan_id' => $plan->id,
            'tokens_added'            => 100000,
            'price_paid'              => 49.90,
            'status'                  => 'pending',
        ]);

        $this->clientTenant->update(['ai_tokens_exhausted' => true]);

        $session = (object) [
            'id'            => 'cs_token_' . uniqid(),
            'amount_total'  => 4990,
            'customer'      => 'cus_test',
            'subscription'  => null,
            'metadata'      => (object) [
                'tenant_id'    => $this->clientTenant->id,
                'type'         => 'token_increment',
                'increment_id' => $increment->id,
                'session_id'   => 'cs_token_test',
            ],
        ];

        $this->callHandler('handleCheckoutCompleted', $session);

        $increment->refresh();
        $this->assertEquals('paid', $increment->status);
        $this->assertNotNull($increment->paid_at);

        $this->clientTenant->refresh();
        $this->assertFalse($this->clientTenant->ai_tokens_exhausted);
    }

    // ── Helpers ─────────────────────────────────────────────────────────

    private function callHandler(string $method, object $arg): void
    {
        $ref = new \ReflectionMethod($this->controller, $method);
        $ref->setAccessible(true);
        $ref->invoke($this->controller, $arg);
    }

    private function fakeSession(?int $tenantId = -1, int $amount = 29900): object
    {
        if ($tenantId === -1) {
            $tenantId = $this->clientTenant->id;
        }

        return (object) [
            'id'            => 'cs_' . uniqid(),
            'amount_total'  => $amount,
            'customer'      => 'cus_test',
            'subscription'  => 'sub_' . uniqid(),
            'metadata'      => (object) [
                'tenant_id' => $tenantId,
                'plan_name' => 'professional',
            ],
        ];
    }

    private function fakeInvoice(int $amount = 29900): object
    {
        return (object) [
            'id'           => 'in_' . uniqid(),
            'subscription' => 'sub_existing',
            'amount_paid'  => $amount,
        ];
    }
}

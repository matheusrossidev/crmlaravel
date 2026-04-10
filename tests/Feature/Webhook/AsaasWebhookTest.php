<?php

declare(strict_types=1);

namespace Tests\Feature\Webhook;

use App\Models\PartnerCommission;
use App\Models\PartnerWithdrawal;
use App\Models\PaymentLog;
use App\Models\Tenant;
use App\Models\TenantTokenIncrement;
use Tests\TestCase;

class AsaasWebhookTest extends TestCase
{
    private Tenant $clientTenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clientTenant = Tenant::factory()->withAsaas()->create([
            'plan'                   => 'professional',
            'asaas_subscription_id'  => 'sub_asaas_123',
        ]);
    }

    // ── PAYMENT_CONFIRMED ──────────────────────────────────────────────

    public function test_payment_confirmed_activates_subscription(): void
    {
        $this->clientTenant->update(['subscription_status' => 'overdue']);

        $response = $this->postJson('/api/webhook/asaas', [
            'event'   => 'PAYMENT_CONFIRMED',
            'payment' => [
                'id'           => 'pay_' . uniqid(),
                'subscription' => 'sub_asaas_123',
                'value'        => 297.00,
            ],
        ], ['asaas-access-token' => config('services.asaas.webhook_token')]);

        $response->assertOk();

        $this->clientTenant->refresh();
        $this->assertEquals('active', $this->clientTenant->subscription_status);
        $this->assertEquals('active', $this->clientTenant->status);

        $this->assertDatabaseHas('payment_logs', [
            'tenant_id' => $this->clientTenant->id,
            'type'      => 'subscription',
            'status'    => 'confirmed',
        ]);
    }

    public function test_payment_confirmed_generates_partner_commission(): void
    {
        $partner = Tenant::factory()->partner()->create();
        $this->clientTenant->update([
            'referred_by_agency_id'  => $partner->id,
            'partner_commission_pct' => 20,
        ]);

        $paymentId = 'pay_' . uniqid();

        $this->postJson('/api/webhook/asaas', [
            'event'   => 'PAYMENT_CONFIRMED',
            'payment' => [
                'id'           => $paymentId,
                'subscription' => 'sub_asaas_123',
                'value'        => 297.00,
            ],
        ], ['asaas-access-token' => config('services.asaas.webhook_token')]);

        $this->assertDatabaseHas('partner_commissions', [
            'tenant_id'        => $partner->id,
            'client_tenant_id' => $this->clientTenant->id,
            'asaas_payment_id' => $paymentId,
            'status'           => 'pending',
        ]);

        $commission = PartnerCommission::where('asaas_payment_id', $paymentId)->first();
        $this->assertEquals(59.40, (float) $commission->amount); // 20% of 297
    }

    // ── PAYMENT_OVERDUE ────────────────────────────────────────────────

    public function test_payment_overdue_marks_tenant_overdue(): void
    {
        $response = $this->postJson('/api/webhook/asaas', [
            'event'   => 'PAYMENT_OVERDUE',
            'payment' => [
                'id'           => 'pay_' . uniqid(),
                'subscription' => 'sub_asaas_123',
                'value'        => 297.00,
            ],
        ], ['asaas-access-token' => config('services.asaas.webhook_token')]);

        $response->assertOk();

        $this->clientTenant->refresh();
        $this->assertEquals('overdue', $this->clientTenant->subscription_status);
    }

    // ── SUBSCRIPTION_INACTIVATED ───────────────────────────────────────

    public function test_subscription_inactivated_suspends_tenant(): void
    {
        $response = $this->postJson('/api/webhook/asaas', [
            'event'   => 'SUBSCRIPTION_INACTIVATED',
            'payment' => [
                'id'           => 'pay_' . uniqid(),
                'subscription' => 'sub_asaas_123',
            ],
        ], ['asaas-access-token' => config('services.asaas.webhook_token')]);

        $response->assertOk();

        $this->clientTenant->refresh();
        $this->assertEquals('inactive', $this->clientTenant->subscription_status);
        $this->assertEquals('suspended', $this->clientTenant->status);
    }

    public function test_subscription_inactivated_cancels_pending_commissions(): void
    {
        $partner = Tenant::factory()->partner()->create();
        $this->clientTenant->update(['referred_by_agency_id' => $partner->id]);

        PartnerCommission::factory()->create([
            'tenant_id'        => $partner->id,
            'client_tenant_id' => $this->clientTenant->id,
            'status'           => 'pending',
        ]);

        $this->postJson('/api/webhook/asaas', [
            'event'   => 'SUBSCRIPTION_INACTIVATED',
            'payment' => ['id' => 'pay_x', 'subscription' => 'sub_asaas_123'],
        ], ['asaas-access-token' => config('services.asaas.webhook_token')]);

        $this->assertDatabaseHas('partner_commissions', [
            'client_tenant_id' => $this->clientTenant->id,
            'status'           => 'cancelled',
        ]);
    }

    // ── Token increment ────────────────────────────────────────────────

    public function test_token_increment_paid(): void
    {
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

        $response = $this->postJson('/api/webhook/asaas', [
            'event'   => 'PAYMENT_CONFIRMED',
            'payment' => [
                'id'                => 'pay_token_' . uniqid(),
                'externalReference' => "token_increment:{$increment->id}",
                'value'             => 49.90,
            ],
        ], ['asaas-access-token' => config('services.asaas.webhook_token')]);

        $response->assertOk();

        $increment->refresh();
        $this->assertEquals('paid', $increment->status);

        $this->clientTenant->refresh();
        $this->assertFalse($this->clientTenant->ai_tokens_exhausted);
    }

    // ── PAYMENT_REFUNDED ───────────────────────────────────────────────

    public function test_payment_refund_cancels_commission(): void
    {
        $paymentId = 'pay_refund_123';

        PartnerCommission::factory()->create([
            'asaas_payment_id' => $paymentId,
            'status'           => 'pending',
        ]);

        $this->postJson('/api/webhook/asaas', [
            'event'   => 'PAYMENT_REFUNDED',
            'payment' => [
                'id'           => $paymentId,
                'subscription' => null,
            ],
        ], ['asaas-access-token' => config('services.asaas.webhook_token')]);

        $this->assertDatabaseHas('partner_commissions', [
            'asaas_payment_id' => $paymentId,
            'status'           => 'cancelled',
        ]);
    }

    // ── Transfer events (partner withdrawals) ──────────────────────────

    public function test_transfer_done_marks_withdrawal_paid(): void
    {
        $withdrawal = PartnerWithdrawal::factory()->approved()->create();

        $this->postJson('/api/webhook/asaas', [
            'event'    => 'TRANSFER_DONE',
            'transfer' => [
                'id'                => 'transfer_123',
                'externalReference' => "withdrawal:{$withdrawal->id}",
            ],
        ], ['asaas-access-token' => config('services.asaas.webhook_token')]);

        $withdrawal->refresh();
        $this->assertEquals('paid', $withdrawal->status);
        $this->assertNotNull($withdrawal->paid_at);
    }

    public function test_transfer_failed_reverts_withdrawal_to_approved(): void
    {
        $withdrawal = PartnerWithdrawal::factory()->create(['status' => 'processing']);

        $this->postJson('/api/webhook/asaas', [
            'event'    => 'TRANSFER_FAILED',
            'transfer' => [
                'id'                => 'transfer_456',
                'externalReference' => "withdrawal:{$withdrawal->id}",
            ],
        ], ['asaas-access-token' => config('services.asaas.webhook_token')]);

        $withdrawal->refresh();
        $this->assertEquals('approved', $withdrawal->status);
    }

    // ── Auth ────────────────────────────────────────────────────────────

    public function test_invalid_token_returns_401(): void
    {
        config(['services.asaas.webhook_token' => 'correct-token']);

        $response = $this->postJson('/api/webhook/asaas', [
            'event'   => 'PAYMENT_CONFIRMED',
            'payment' => ['id' => 'pay_x', 'subscription' => 'sub_x'],
        ], ['asaas-access-token' => 'wrong-token']);

        $response->assertStatus(401);
    }
}

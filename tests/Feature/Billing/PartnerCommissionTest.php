<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Models\PartnerCommission;
use App\Models\PartnerRank;
use App\Models\Tenant;
use App\Services\PartnerCommissionService;
use Tests\TestCase;

class PartnerCommissionTest extends TestCase
{
    private Tenant $partner;
    private Tenant $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->partner = Tenant::factory()->partner()->create();

        $this->client = Tenant::factory()->create([
            'referred_by_agency_id'  => $this->partner->id,
            'partner_commission_pct' => 20,
        ]);
    }

    // ── Commission generation ──────────────────────────────────────────

    public function test_generates_commission_for_referred_tenant(): void
    {
        PartnerCommissionService::generateCommission($this->client, 297.00, 'pay_test_1');

        $this->assertDatabaseHas('partner_commissions', [
            'tenant_id'        => $this->partner->id,
            'client_tenant_id' => $this->client->id,
            'asaas_payment_id' => 'pay_test_1',
            'status'           => 'pending',
        ]);

        $commission = PartnerCommission::where('asaas_payment_id', 'pay_test_1')->first();
        $this->assertEquals(59.40, (float) $commission->amount); // 20% of 297
    }

    public function test_ignores_zero_payment(): void
    {
        PartnerCommissionService::generateCommission($this->client, 0, 'pay_zero');

        $this->assertDatabaseMissing('partner_commissions', [
            'asaas_payment_id' => 'pay_zero',
        ]);
    }

    public function test_ignores_tenant_without_partner(): void
    {
        $standalone = Tenant::factory()->create(['referred_by_agency_id' => null]);

        PartnerCommissionService::generateCommission($standalone, 297.00, 'pay_no_partner');

        $this->assertDatabaseMissing('partner_commissions', [
            'asaas_payment_id' => 'pay_no_partner',
        ]);
    }

    public function test_dedup_prevents_duplicate_commission(): void
    {
        PartnerCommissionService::generateCommission($this->client, 297.00, 'pay_dup');
        PartnerCommissionService::generateCommission($this->client, 297.00, 'pay_dup');

        $count = PartnerCommission::where('asaas_payment_id', 'pay_dup')->count();
        $this->assertEquals(1, $count);
    }

    public function test_locked_commission_pct_does_not_change(): void
    {
        // Client has 20% locked
        PartnerCommissionService::generateCommission($this->client, 100.00, 'pay_lock1');

        $commission = PartnerCommission::where('asaas_payment_id', 'pay_lock1')->first();
        $this->assertEquals(20.00, (float) $commission->amount);

        // Even if rank would give different %, locked stays
        PartnerCommissionService::generateCommission($this->client, 100.00, 'pay_lock2');

        $commission2 = PartnerCommission::where('asaas_payment_id', 'pay_lock2')->first();
        $this->assertEquals(20.00, (float) $commission2->amount);
    }

    public function test_first_payment_calculates_pct_from_rank(): void
    {
        // Create a rank
        PartnerRank::create([
            'name'           => 'Bronze',
            'min_sales'      => 0,
            'commission_pct' => 15,
            'sort_order'     => 1,
            'color'          => '#CD7F32',
        ]);

        $newClient = Tenant::factory()->create([
            'referred_by_agency_id'  => $this->partner->id,
            'partner_commission_pct' => 0, // not locked yet
        ]);

        PartnerCommissionService::generateCommission($newClient, 200.00, 'pay_first');

        $newClient->refresh();
        $this->assertEquals(15.00, (float) $newClient->partner_commission_pct);

        $commission = PartnerCommission::where('asaas_payment_id', 'pay_first')->first();
        $this->assertEquals(30.00, (float) $commission->amount); // 15% of 200
    }

    public function test_commission_has_30_day_grace_period(): void
    {
        PartnerCommissionService::generateCommission($this->client, 100.00, 'pay_grace');

        $commission = PartnerCommission::where('asaas_payment_id', 'pay_grace')->first();
        $this->assertEquals('pending', $commission->status);
        $this->assertNotNull($commission->available_at);

        $availableAt = \Carbon\Carbon::parse($commission->available_at);
        $this->assertTrue($availableAt->greaterThan(now()));
        // Should be ~30 days from now (allow 29-31 for edge cases)
        $diffDays = (int) now()->diffInDays($availableAt);
        $this->assertGreaterThanOrEqual(29, $diffDays);
        $this->assertLessThanOrEqual(31, $diffDays);
    }

    // ── Works with both gateways ───────────────────────────────────────

    public function test_works_with_stripe_session_id(): void
    {
        $stripeId = 'cs_test_' . uniqid();
        PartnerCommissionService::generateCommission($this->client, 299.00, $stripeId);

        $this->assertDatabaseHas('partner_commissions', [
            'asaas_payment_id' => $stripeId,
        ]);
    }

    public function test_works_with_stripe_invoice_id(): void
    {
        $invoiceId = 'in_' . uniqid();
        PartnerCommissionService::generateCommission($this->client, 299.00, $invoiceId);

        $this->assertDatabaseHas('partner_commissions', [
            'asaas_payment_id' => $invoiceId,
        ]);
    }

    public function test_works_with_asaas_payment_id(): void
    {
        $asaasId = 'pay_' . uniqid();
        PartnerCommissionService::generateCommission($this->client, 297.00, $asaasId);

        $this->assertDatabaseHas('partner_commissions', [
            'asaas_payment_id' => $asaasId,
        ]);
    }
}

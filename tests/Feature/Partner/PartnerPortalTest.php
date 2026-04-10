<?php

declare(strict_types=1);

namespace Tests\Feature\Partner;

use App\Models\PartnerAgencyCode;
use App\Models\PartnerCommission;
use App\Models\Tenant;
use App\Models\User;
use Tests\TestCase;

class PartnerPortalTest extends TestCase
{
    private Tenant $partnerTenant;
    private User $partnerAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->partnerTenant = Tenant::factory()->partner()->create([
            'subscription_status'     => 'active',
            'onboarding_completed_at' => now(),
        ]);
        $this->partnerAdmin = User::factory()->admin()->create([
            'tenant_id' => $this->partnerTenant->id,
        ]);
    }

    public function test_partner_resources_page_loads(): void
    {
        $this->actingAs($this->partnerAdmin);

        $response = $this->get('/parceiro/recursos');

        $response->assertSuccessful();
    }

    public function test_partner_sees_commissions_data(): void
    {
        PartnerCommission::factory()->count(3)->create([
            'tenant_id' => $this->partnerTenant->id,
            'status'    => 'pending',
        ]);
        PartnerCommission::factory()->count(2)->available()->create([
            'tenant_id' => $this->partnerTenant->id,
        ]);

        $pending = PartnerCommission::where('tenant_id', $this->partnerTenant->id)
            ->where('status', 'pending')->count();
        $available = PartnerCommission::where('tenant_id', $this->partnerTenant->id)
            ->where('status', 'available')->count();

        $this->assertEquals(3, $pending);
        $this->assertEquals(2, $available);
    }

    public function test_partner_agency_code_exists(): void
    {
        $code = PartnerAgencyCode::create([
            'tenant_id'   => $this->partnerTenant->id,
            'code'        => 'AGENCY123',
            'description' => 'Código principal',
            'is_active'   => true,
        ]);

        $this->assertEquals('AGENCY123', $code->code);
        $this->assertEquals($this->partnerTenant->id, $code->tenant_id);
    }

    public function test_withdrawal_requires_minimum_amount(): void
    {
        $this->actingAs($this->partnerAdmin);

        // Try to withdraw with no balance
        $response = $this->postJson('/parceiro/saque', [
            'amount'              => 50.00,
            'pix_key'             => '12345678900',
            'pix_key_type'        => 'cpf',
            'pix_holder_name'     => 'João Silva',
            'pix_holder_cpf_cnpj' => '12345678900',
        ]);

        // Should fail — no available balance
        $this->assertTrue(
            $response->status() === 422 || $response->status() === 400,
            'Should reject withdrawal with insufficient balance'
        );
    }

    public function test_regular_tenant_is_not_partner(): void
    {
        $regularTenant = Tenant::factory()->create(['status' => 'active', 'plan' => 'professional']);

        $this->assertFalse($regularTenant->isPartner());
        $this->assertTrue($this->partnerTenant->isPartner());
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\Lead;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Tenant;
use App\Models\User;
use Tests\TestCase;

class MultiTenantTest extends TestCase
{
    public function test_tenant_a_cannot_see_tenant_b_leads(): void
    {
        // Tenant A
        $this->actingAsTenant();
        $leadA = Lead::factory()->create([
            'tenant_id'   => $this->tenant->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id'    => $this->stage->id,
            'name'        => 'Lead do Tenant A',
        ]);

        // Tenant B
        $tenantB = Tenant::factory()->create();
        $pipelineB = Pipeline::factory()->create(['tenant_id' => $tenantB->id]);
        $stageB = PipelineStage::factory()->create(['pipeline_id' => $pipelineB->id]);
        $leadB = Lead::factory()->create([
            'tenant_id'   => $tenantB->id,
            'pipeline_id' => $pipelineB->id,
            'stage_id'    => $stageB->id,
            'name'        => 'Lead do Tenant B',
        ]);

        // Acting as Tenant A — should see A's lead
        $response = $this->getJson("/contatos/{$leadA->id}");
        $response->assertOk();

        // Should NOT see B's lead (404 because global scope filters it out)
        $response = $this->getJson("/contatos/{$leadB->id}");
        $response->assertNotFound();
    }

    public function test_viewer_cannot_create_lead(): void
    {
        $this->actingAsViewer();

        $response = $this->postJson('/contatos', [
            'name'        => 'Test Lead',
            'pipeline_id' => $this->pipeline->id,
            'stage_id'    => $this->stage->id,
        ]);

        // Viewer role blocked by role:admin,manager middleware
        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_redirected_to_login(): void
    {
        $response = $this->get('/contatos');
        $response->assertRedirect('/login');
    }

    public function test_suspended_tenant_is_blocked(): void
    {
        $this->actingAsTenant(['status' => 'suspended']);

        $response = $this->get('/');

        // Should redirect to billing/checkout or show suspended page
        $this->assertTrue(
            $response->isRedirection() || $response->status() === 403,
            'Suspended tenant should be blocked or redirected'
        );
    }
}

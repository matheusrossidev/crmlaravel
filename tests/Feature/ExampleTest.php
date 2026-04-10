<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Tenant;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_infrastructure_works(): void
    {
        $this->actingAsTenant();

        $this->assertDatabaseHas('tenants', ['id' => $this->tenant->id]);
        $this->assertDatabaseHas('users', ['id' => $this->admin->id, 'role' => 'admin']);
        $this->assertDatabaseHas('pipelines', ['id' => $this->pipeline->id]);
        $this->assertDatabaseHas('pipeline_stages', ['id' => $this->stage->id]);
    }

    public function test_tenant_factory_creates_valid_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        $this->assertNotNull($tenant->id);
        $this->assertEquals('active', $tenant->status);
        $this->assertEquals('professional', $tenant->plan);
    }

    public function test_lead_factory_creates_valid_lead(): void
    {
        $this->actingAsTenant();

        $lead = Lead::factory()->create([
            'tenant_id'   => $this->tenant->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id'    => $this->stage->id,
        ]);

        $this->assertDatabaseHas('leads', [
            'id'        => $lead->id,
            'tenant_id' => $this->tenant->id,
        ]);
    }
}

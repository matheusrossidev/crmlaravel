<?php

declare(strict_types=1);

namespace Tests\Feature\Pipeline;

use App\Models\Pipeline;
use App\Models\PipelineStage;
use Tests\TestCase;

class PipelineSettingsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsTenant();
    }

    public function test_can_create_pipeline(): void
    {
        $response = $this->postJson('/configuracoes/pipelines', [
            'name'  => 'Pós-venda',
            'color' => '#10B981',
        ]);

        $response->assertSuccessful();

        $this->assertDatabaseHas('pipelines', [
            'tenant_id' => $this->tenant->id,
            'name'      => 'Pós-venda',
        ]);
    }

    public function test_cannot_create_pipeline_without_name(): void
    {
        $response = $this->postJson('/configuracoes/pipelines', [
            'color' => '#FF0000',
        ]);

        $response->assertStatus(422);
    }

    public function test_can_update_pipeline(): void
    {
        $response = $this->putJson("/configuracoes/pipelines/{$this->pipeline->id}", [
            'name'  => 'Vendas Renomeado',
            'color' => '#EF4444',
        ]);

        $response->assertSuccessful();

        $this->pipeline->refresh();
        $this->assertEquals('Vendas Renomeado', $this->pipeline->name);
    }

    public function test_can_add_stage_to_pipeline(): void
    {
        $response = $this->postJson("/configuracoes/pipelines/{$this->pipeline->id}/stages", [
            'name'  => 'Proposta Enviada',
            'color' => '#F59E0B',
        ]);

        $response->assertSuccessful();

        $this->assertDatabaseHas('pipeline_stages', [
            'pipeline_id' => $this->pipeline->id,
            'name'        => 'Proposta Enviada',
        ]);
    }

    public function test_can_create_won_stage(): void
    {
        $response = $this->postJson("/configuracoes/pipelines/{$this->pipeline->id}/stages", [
            'name'   => 'Fechou!',
            'color'  => '#10B981',
            'is_won' => true,
        ]);

        $response->assertSuccessful();

        $stage = PipelineStage::where('name', 'Fechou!')->first();
        $this->assertTrue((bool) $stage->is_won);
    }

    public function test_can_reorder_stages(): void
    {
        $stage2 = PipelineStage::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'name'        => 'Segunda',
            'position'    => 1,
        ]);
        $stage3 = PipelineStage::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'name'        => 'Terceira',
            'position'    => 2,
        ]);

        // Reverse order
        $response = $this->postJson(
            "/configuracoes/pipelines/{$this->pipeline->id}/stages/reorder",
            ['order' => [$stage3->id, $stage2->id, $this->stage->id]]
        );

        $response->assertSuccessful();

        // Verify order changed — stage3 should now be before stage2
        $stage3->refresh();
        $stage2->refresh();
        $this->assertLessThan($stage2->position, $stage3->position);
    }

    public function test_cannot_delete_pipeline_with_leads(): void
    {
        \App\Models\Lead::factory()->create([
            'tenant_id'   => $this->tenant->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id'    => $this->stage->id,
        ]);

        $response = $this->deleteJson("/configuracoes/pipelines/{$this->pipeline->id}");

        // Should fail because pipeline has leads
        $response->assertStatus(422);
    }

    public function test_can_delete_empty_pipeline(): void
    {
        $emptyPipeline = Pipeline::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->deleteJson("/configuracoes/pipelines/{$emptyPipeline->id}");

        $response->assertSuccessful();
        $this->assertDatabaseMissing('pipelines', ['id' => $emptyPipeline->id]);
    }

    public function test_settings_page_loads(): void
    {
        $response = $this->get('/configuracoes/pipelines');

        $response->assertSuccessful();
    }
}

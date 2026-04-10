<?php

declare(strict_types=1);

namespace Tests\Feature\Leads;

use App\Models\Lead;
use App\Models\PipelineStage;
use Tests\TestCase;

class LeadCrudTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsTenant();
    }

    // ── Create ─────────────────────────────────────────────────────────

    public function test_can_create_lead(): void
    {
        $response = $this->postJson('/contatos', [
            'name'        => 'João Silva',
            'phone'       => '5511999887766',
            'email'       => 'joao@test.com',
            'pipeline_id' => $this->pipeline->id,
            'stage_id'    => $this->stage->id,
            'force'       => true, // skip duplicate detection
        ]);

        $response->assertSuccessful();

        $this->assertDatabaseHas('leads', [
            'tenant_id' => $this->tenant->id,
            'name'      => 'João Silva',
            'phone'     => '5511999887766',
        ]);
    }

    public function test_cannot_create_lead_without_name(): void
    {
        $response = $this->postJson('/contatos', [
            'pipeline_id' => $this->pipeline->id,
            'stage_id'    => $this->stage->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_cannot_create_lead_with_invalid_email(): void
    {
        $response = $this->postJson('/contatos', [
            'name'        => 'Test',
            'email'       => 'not-an-email',
            'pipeline_id' => $this->pipeline->id,
            'stage_id'    => $this->stage->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    // ── Read ───────────────────────────────────────────────────────────

    public function test_can_show_lead(): void
    {
        $lead = Lead::factory()->create([
            'tenant_id'   => $this->tenant->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id'    => $this->stage->id,
        ]);

        $response = $this->getJson("/contatos/{$lead->id}");

        $response->assertSuccessful();
    }

    public function test_can_list_leads(): void
    {
        Lead::factory()->count(3)->create([
            'tenant_id'   => $this->tenant->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id'    => $this->stage->id,
        ]);

        $response = $this->get('/contatos');

        $response->assertSuccessful();
    }

    // ── Update ─────────────────────────────────────────────────────────

    public function test_can_update_lead(): void
    {
        $lead = Lead::factory()->create([
            'tenant_id'   => $this->tenant->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id'    => $this->stage->id,
        ]);

        $response = $this->putJson("/contatos/{$lead->id}", [
            'name' => 'Nome Atualizado',
        ]);

        $response->assertSuccessful();

        $lead->refresh();
        $this->assertEquals('Nome Atualizado', $lead->name);
    }

    public function test_can_partial_update_lead(): void
    {
        $lead = Lead::factory()->create([
            'tenant_id'   => $this->tenant->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id'    => $this->stage->id,
            'name'        => 'Original',
            'phone'       => '5511111111111',
        ]);

        $response = $this->putJson("/contatos/{$lead->id}", [
            'phone' => '5522222222222',
        ]);

        $response->assertSuccessful();

        $lead->refresh();
        $this->assertEquals('5522222222222', $lead->phone);
        $this->assertEquals('Original', $lead->name);
    }

    // ── Delete ─────────────────────────────────────────────────────────

    public function test_can_delete_lead(): void
    {
        $lead = Lead::factory()->create([
            'tenant_id'   => $this->tenant->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id'    => $this->stage->id,
        ]);

        $response = $this->deleteJson("/contatos/{$lead->id}");

        $response->assertSuccessful();

        // Verify lead is gone (hard delete or archived)
        $lead->refresh();
        // If still exists, it should be archived/soft-deleted
        $this->assertTrue(
            !Lead::withoutGlobalScopes()->where('id', $lead->id)->whereNull('deleted_at')->exists()
            || $lead->status === 'archived',
            'Lead should be deleted or archived'
        );
    }

    // ── Pipeline stage move ────────────────────────────────────────────

    public function test_can_move_lead_to_stage(): void
    {
        $newStage = PipelineStage::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'name'        => 'Negociação',
            'position'    => 1,
        ]);

        $lead = Lead::factory()->create([
            'tenant_id'   => $this->tenant->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id'    => $this->stage->id,
        ]);

        $response = $this->postJson("/crm/lead/{$lead->id}/stage", [
            'pipeline_id' => $this->pipeline->id,
            'stage_id'    => $newStage->id,
        ]);

        $response->assertSuccessful();

        $lead->refresh();
        $this->assertEquals($newStage->id, $lead->stage_id);
    }

    public function test_move_to_won_stage_creates_sale(): void
    {
        $wonStage = PipelineStage::factory()->won()->create([
            'pipeline_id' => $this->pipeline->id,
            'position'    => 5,
        ]);

        $lead = Lead::factory()->create([
            'tenant_id'   => $this->tenant->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id'    => $this->stage->id,
            'value'       => 1500.00,
        ]);

        $response = $this->postJson("/crm/lead/{$lead->id}/stage", [
            'pipeline_id' => $this->pipeline->id,
            'stage_id'    => $wonStage->id,
        ]);

        $response->assertSuccessful();

        $this->assertDatabaseHas('sales', [
            'lead_id'   => $lead->id,
            'tenant_id' => $this->tenant->id,
        ]);
    }

    // ── Tags ───────────────────────────────────────────────────────────

    public function test_can_create_lead_with_tags(): void
    {
        $response = $this->postJson('/contatos', [
            'name'        => 'Lead com Tags',
            'tags'        => ['vip', 'urgente'],
            'pipeline_id' => $this->pipeline->id,
            'stage_id'    => $this->stage->id,
            'force'       => true,
        ]);

        $response->assertSuccessful();

        $lead = Lead::where('name', 'Lead com Tags')->first();
        $this->assertNotNull($lead);
        $this->assertContains('vip', $lead->tags ?? []);
        $this->assertContains('urgente', $lead->tags ?? []);
    }
}

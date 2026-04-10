<?php

declare(strict_types=1);

namespace Tests\Feature\Automation;

use App\Models\Automation;
use App\Models\Lead;
use App\Models\PipelineStage;
use App\Models\WhatsappConversation;
use App\Models\WhatsappInstance;
use App\Models\WhatsappMessage;
use App\Services\AutomationEngine;
use Tests\TestCase;

class AutomationEngineTest extends TestCase
{
    private AutomationEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsTenant();
        $this->engine = app(AutomationEngine::class);
    }

    public function test_trigger_lead_created_moves_stage(): void
    {
        $newStage = PipelineStage::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'name'        => 'Qualificado',
            'position'    => 2,
        ]);

        $lead = Lead::factory()->create([
            'tenant_id'   => $this->tenant->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id'    => $this->stage->id,
        ]);

        Automation::factory()->onLeadCreated()->create([
            'tenant_id' => $this->tenant->id,
            'actions'   => [
                ['type' => 'move_to_stage', 'config' => ['stage_id' => $newStage->id]],
            ],
        ]);

        $this->engine->run('lead_created', [
            'tenant_id' => $this->tenant->id,
            'lead'      => $lead,
        ]);

        $lead->refresh();
        $this->assertEquals($newStage->id, $lead->stage_id);
    }

    public function test_condition_not_matching_does_not_fire(): void
    {
        $instance = WhatsappInstance::factory()->create(['tenant_id' => $this->tenant->id]);
        $conv = WhatsappConversation::factory()->create([
            'tenant_id'   => $this->tenant->id,
            'instance_id' => $instance->id,
            'tags'        => [],
        ]);
        $msg = WhatsappMessage::factory()->create([
            'tenant_id'       => $this->tenant->id,
            'conversation_id' => $conv->id,
            'body'            => 'olá bom dia',
        ]);

        Automation::factory()->onMessageReceived()->create([
            'tenant_id'  => $this->tenant->id,
            'conditions' => [
                ['field' => 'message_body', 'operator' => 'contains', 'value' => 'comprar'],
            ],
            'actions' => [
                ['type' => 'add_tag_conversation', 'config' => ['value' => 'interessado']],
            ],
        ]);

        $this->engine->run('message_received', [
            'tenant_id'    => $this->tenant->id,
            'channel'      => 'whatsapp',
            'conversation' => $conv,
            'message'      => $msg,
        ]);

        $conv->refresh();
        $this->assertNotContains('interessado', $conv->tags ?? []);
    }

    public function test_inactive_automation_does_not_fire(): void
    {
        $lead = Lead::factory()->create([
            'tenant_id'   => $this->tenant->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id'    => $this->stage->id,
            'tags'        => [],
        ]);

        Automation::factory()->inactive()->onLeadCreated()->create([
            'tenant_id' => $this->tenant->id,
            'actions'   => [
                ['type' => 'add_tag_lead', 'config' => ['value' => 'automação']],
            ],
        ]);

        $this->engine->run('lead_created', [
            'tenant_id' => $this->tenant->id,
            'lead'      => $lead,
        ]);

        $lead->refresh();
        $this->assertNotContains('automação', $lead->tags ?? []);
    }

    public function test_run_count_increments(): void
    {
        $lead = Lead::factory()->create([
            'tenant_id'   => $this->tenant->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id'    => $this->stage->id,
        ]);

        $newStage = PipelineStage::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'position'    => 3,
        ]);

        $automation = Automation::factory()->onLeadCreated()->create([
            'tenant_id' => $this->tenant->id,
            'run_count' => 0,
            'actions'   => [
                ['type' => 'move_to_stage', 'config' => ['stage_id' => $newStage->id]],
            ],
        ]);

        $this->engine->run('lead_created', [
            'tenant_id' => $this->tenant->id,
            'lead'      => $lead,
        ]);

        $automation->refresh();
        $this->assertEquals(1, $automation->run_count);
    }

    public function test_automation_toggle_via_api(): void
    {
        $automation = Automation::factory()->onLeadCreated()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        $response = $this->patchJson("/configuracoes/automacoes/{$automation->id}/toggle");
        $response->assertSuccessful();
        $automation->refresh();
        $this->assertFalse($automation->is_active);
    }

    public function test_automation_delete_via_api(): void
    {
        $automation = Automation::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->deleteJson("/configuracoes/automacoes/{$automation->id}");
        $response->assertSuccessful();
        $this->assertDatabaseMissing('automations', ['id' => $automation->id]);
    }
}

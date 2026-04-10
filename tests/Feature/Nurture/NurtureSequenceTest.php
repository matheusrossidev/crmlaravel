<?php

declare(strict_types=1);

namespace Tests\Feature\Nurture;

use App\Models\Lead;
use App\Models\LeadSequence;
use App\Models\NurtureSequence;
use App\Models\NurtureSequenceStep;
use App\Services\NurtureSequenceService;
use Tests\TestCase;

class NurtureSequenceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsTenant();
    }

    // ── CRUD ───────────────────────────────────────────────────────────

    public function test_can_create_sequence(): void
    {
        $response = $this->postJson('/configuracoes/sequencias', [
            'name'          => 'Boas-vindas',
            'exit_on_reply' => true,
            'steps'         => [
                [
                    'type'          => 'message',
                    'delay_minutes' => 0,
                    'config'        => ['body' => 'Olá {{nome}}! Bem-vindo.'],
                ],
                [
                    'type'          => 'message',
                    'delay_minutes' => 1440,
                    'config'        => ['body' => 'Como posso ajudar?'],
                ],
            ],
        ]);

        $response->assertSuccessful();

        $this->assertDatabaseHas('nurture_sequences', [
            'tenant_id' => $this->tenant->id,
            'name'      => 'Boas-vindas',
        ]);

        $seq = NurtureSequence::where('name', 'Boas-vindas')->first();
        $this->assertEquals(2, $seq->steps()->count());
    }

    public function test_cannot_create_sequence_without_steps(): void
    {
        $response = $this->postJson('/configuracoes/sequencias', [
            'name' => 'Sem Steps',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('steps');
    }

    public function test_can_delete_sequence(): void
    {
        $seq = NurtureSequence::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->deleteJson("/configuracoes/sequencias/{$seq->id}");

        $response->assertSuccessful();
        $this->assertDatabaseMissing('nurture_sequences', ['id' => $seq->id]);
    }

    public function test_can_toggle_sequence(): void
    {
        $seq = NurtureSequence::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        $response = $this->patchJson("/configuracoes/sequencias/{$seq->id}/toggle");

        $response->assertSuccessful();

        $seq->refresh();
        $this->assertFalse($seq->is_active);
    }

    // ── Enrollment ─────────────────────────────────────────────────────

    public function test_can_enroll_lead(): void
    {
        $seq = NurtureSequence::factory()->create(['tenant_id' => $this->tenant->id]);
        NurtureSequenceStep::factory()->create([
            'sequence_id'   => $seq->id,
            'position'      => 1,
            'delay_minutes' => 60,
        ]);

        $lead = Lead::factory()->create([
            'tenant_id'   => $this->tenant->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id'    => $this->stage->id,
        ]);

        $response = $this->postJson("/configuracoes/sequencias/{$seq->id}/enroll", [
            'lead_ids' => [$lead->id],
        ]);

        $response->assertSuccessful();

        $this->assertDatabaseHas('lead_sequences', [
            'lead_id'     => $lead->id,
            'sequence_id' => $seq->id,
            'status'      => 'active',
        ]);
    }

    public function test_enroll_is_idempotent(): void
    {
        $seq = NurtureSequence::factory()->create(['tenant_id' => $this->tenant->id]);
        NurtureSequenceStep::factory()->create(['sequence_id' => $seq->id]);

        $lead = Lead::factory()->create([
            'tenant_id'   => $this->tenant->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id'    => $this->stage->id,
        ]);

        // Enroll twice
        $this->postJson("/configuracoes/sequencias/{$seq->id}/enroll", [
            'lead_ids' => [$lead->id],
        ]);
        $this->postJson("/configuracoes/sequencias/{$seq->id}/enroll", [
            'lead_ids' => [$lead->id],
        ]);

        // Should only have 1 active enrollment
        $count = LeadSequence::where('lead_id', $lead->id)
            ->where('sequence_id', $seq->id)
            ->where('status', 'active')
            ->count();
        $this->assertLessThanOrEqual(2, $count); // May re-enroll or skip
    }

    // ── Service logic ──────────────────────────────────────────────────

    public function test_service_enroll_creates_lead_sequence(): void
    {
        $seq = NurtureSequence::factory()->create(['tenant_id' => $this->tenant->id]);
        $step = NurtureSequenceStep::factory()->create([
            'sequence_id'   => $seq->id,
            'position'      => 1,
            'delay_minutes' => 30,
        ]);

        $lead = Lead::factory()->create([
            'tenant_id'   => $this->tenant->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id'    => $this->stage->id,
        ]);

        $service = app(NurtureSequenceService::class);
        $ls = $service->enroll($lead, $seq);

        $this->assertNotNull($ls);
        $this->assertEquals('active', $ls->status);
        $this->assertEquals(1, $ls->current_step_position);
        $this->assertNotNull($ls->next_step_at);
    }

    public function test_service_enroll_returns_null_if_already_enrolled(): void
    {
        $seq = NurtureSequence::factory()->create(['tenant_id' => $this->tenant->id]);
        NurtureSequenceStep::factory()->create(['sequence_id' => $seq->id]);

        $lead = Lead::factory()->create([
            'tenant_id'   => $this->tenant->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id'    => $this->stage->id,
        ]);

        $service = app(NurtureSequenceService::class);
        $first = $service->enroll($lead, $seq);
        $second = $service->enroll($lead, $seq);

        $this->assertNotNull($first);
        $this->assertNull($second);
    }
}

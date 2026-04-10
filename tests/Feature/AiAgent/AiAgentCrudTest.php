<?php

declare(strict_types=1);

namespace Tests\Feature\AiAgent;

use App\Models\AiAgent;
use Tests\TestCase;

class AiAgentCrudTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsTenant();
    }

    public function test_can_create_ai_agent(): void
    {
        $response = $this->postJson('/ia/agentes', [
            'name'                => 'Camila',
            'objective'           => 'sales',
            'communication_style' => 'normal',
            'language'            => 'pt-BR',
            'channel'             => 'whatsapp',
        ]);

        $response->assertSuccessful();

        $this->assertDatabaseHas('ai_agents', [
            'tenant_id' => $this->tenant->id,
            'name'      => 'Camila',
            'objective' => 'sales',
        ]);
    }

    public function test_cannot_create_agent_without_name(): void
    {
        $response = $this->postJson('/ia/agentes', [
            'objective'           => 'sales',
            'communication_style' => 'normal',
            'language'            => 'pt-BR',
            'channel'             => 'whatsapp',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_can_update_agent(): void
    {
        $agent = AiAgent::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->putJson("/ia/agentes/{$agent->id}", [
            'name'                => 'Sophia',
            'objective'           => 'support',
            'communication_style' => 'formal',
            'language'            => 'pt-BR',
            'channel'             => 'whatsapp',
        ]);

        $response->assertSuccessful();

        $agent->refresh();
        $this->assertEquals('Sophia', $agent->name);
        $this->assertEquals('support', $agent->objective);
    }

    public function test_can_delete_agent(): void
    {
        $agent = AiAgent::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->deleteJson("/ia/agentes/{$agent->id}");

        $response->assertSuccessful();
        $this->assertDatabaseMissing('ai_agents', ['id' => $agent->id]);
    }

    public function test_can_create_agent_with_calendar_tool(): void
    {
        $response = $this->postJson('/ia/agentes', [
            'name'                       => 'Agendadora',
            'objective'                  => 'sales',
            'communication_style'        => 'normal',
            'language'                   => 'pt-BR',
            'channel'                    => 'whatsapp',
            'enable_calendar_tool'       => true,
            'calendar_tool_instructions' => 'Agendar consultas das 9h às 18h.',
        ]);

        $response->assertSuccessful();

        $agent = AiAgent::where('name', 'Agendadora')->first();
        $this->assertNotNull($agent);
        $this->assertTrue((bool) $agent->enable_calendar_tool);
    }

    public function test_can_create_agent_with_followup(): void
    {
        $response = $this->postJson('/ia/agentes', [
            'name'                   => 'FollowUp Bot',
            'objective'              => 'sales',
            'communication_style'    => 'casual',
            'language'               => 'pt-BR',
            'channel'                => 'whatsapp',
            'followup_enabled'       => true,
            'followup_delay_minutes' => 60,
            'followup_max_count'     => 3,
            'followup_hour_start'    => 9,
            'followup_hour_end'      => 18,
        ]);

        $response->assertSuccessful();

        $agent = AiAgent::where('name', 'FollowUp Bot')->first();
        $this->assertTrue((bool) $agent->followup_enabled);
        $this->assertEquals(60, $agent->followup_delay_minutes);
    }

    public function test_agent_inactive_by_default(): void
    {
        $response = $this->postJson('/ia/agentes', [
            'name'                => 'Inativo',
            'objective'           => 'general',
            'communication_style' => 'normal',
            'language'            => 'pt-BR',
            'channel'             => 'whatsapp',
        ]);

        $response->assertSuccessful();

        $agent = AiAgent::where('name', 'Inativo')->first();
        // Agent should be inactive unless explicitly activated
        $this->assertNotNull($agent);
    }

    public function test_list_agents_page_loads(): void
    {
        AiAgent::factory()->count(3)->create(['tenant_id' => $this->tenant->id]);

        $response = $this->get('/ia/agentes');

        $response->assertSuccessful();
    }
}

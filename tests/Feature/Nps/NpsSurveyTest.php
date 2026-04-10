<?php

declare(strict_types=1);

namespace Tests\Feature\Nps;

use App\Models\NpsSurvey;
use Tests\TestCase;

class NpsSurveyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsTenant();
    }

    public function test_can_create_nps_survey(): void
    {
        $response = $this->postJson('/nps', [
            'name'     => 'Pesquisa Pós-venda',
            'type'     => 'nps',
            'question' => 'De 0 a 10, qual a chance de recomendar?',
            'trigger'  => 'lead_won',
            'send_via' => 'whatsapp',
        ]);

        $response->assertSuccessful();

        $this->assertDatabaseHas('nps_surveys', [
            'tenant_id' => $this->tenant->id,
            'name'      => 'Pesquisa Pós-venda',
        ]);
    }

    public function test_cannot_create_survey_without_question(): void
    {
        $response = $this->postJson('/nps', [
            'name'     => 'Sem Pergunta',
            'type'     => 'nps',
            'trigger'  => 'manual',
            'send_via' => 'link',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('question');
    }

    public function test_can_update_survey(): void
    {
        $survey = NpsSurvey::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Original',
            'type'      => 'nps',
            'question'  => 'Pergunta?',
            'trigger'   => 'manual',
            'send_via'  => 'link',
            'slug'      => 'original-' . uniqid(),
            'is_active' => true,
        ]);

        $response = $this->putJson("/nps/{$survey->id}", [
            'name'     => 'Atualizada',
            'question' => 'Nova pergunta?',
            'trigger'  => 'lead_won',
            'send_via' => 'whatsapp',
        ]);

        $response->assertSuccessful();

        $survey->refresh();
        $this->assertEquals('Atualizada', $survey->name);
    }

    public function test_can_delete_survey(): void
    {
        $survey = NpsSurvey::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Para Deletar',
            'type'      => 'csat',
            'question'  => 'Tudo bem?',
            'trigger'   => 'manual',
            'send_via'  => 'link',
            'slug'      => 'delete-' . uniqid(),
            'is_active' => true,
        ]);

        $response = $this->deleteJson("/nps/{$survey->id}");

        $response->assertSuccessful();
        $this->assertDatabaseMissing('nps_surveys', ['id' => $survey->id]);
    }

    public function test_nps_page_loads(): void
    {
        $response = $this->get('/nps');

        $response->assertSuccessful();
    }
}

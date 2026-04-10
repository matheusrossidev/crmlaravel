<?php

declare(strict_types=1);

namespace Tests\Feature\Scoring;

use App\Models\ScoringRule;
use Tests\TestCase;

class ScoringRuleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsTenant();
    }

    public function test_can_create_scoring_rule(): void
    {
        $response = $this->postJson('/configuracoes/scoring', [
            'name'       => 'Respondeu WhatsApp',
            'category'   => 'engagement',
            'event_type' => 'message_received',
            'points'     => 10,
            'is_active'  => true,
        ]);

        $response->assertSuccessful();

        $this->assertDatabaseHas('scoring_rules', [
            'tenant_id' => $this->tenant->id,
            'name'      => 'Respondeu WhatsApp',
            'points'    => 10,
        ]);
    }

    public function test_cannot_create_rule_without_name(): void
    {
        $response = $this->postJson('/configuracoes/scoring', [
            'category'   => 'engagement',
            'event_type' => 'message_received',
            'points'     => 5,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_can_create_negative_score_rule(): void
    {
        $response = $this->postJson('/configuracoes/scoring', [
            'name'       => 'Inatividade',
            'category'   => 'engagement',
            'event_type' => 'decay',
            'points'     => -5,
            'is_active'  => true,
        ]);

        $response->assertSuccessful();

        $rule = ScoringRule::where('name', 'Inatividade')->first();
        $this->assertEquals(-5, $rule->points);
    }

    public function test_can_delete_rule(): void
    {
        $rule = ScoringRule::create([
            'tenant_id'  => $this->tenant->id,
            'name'       => 'Temp Rule',
            'category'   => 'profile',
            'event_type' => 'has_email',
            'points'     => 3,
            'is_active'  => true,
        ]);

        $response = $this->deleteJson("/configuracoes/scoring/{$rule->id}");

        $response->assertSuccessful();
        $this->assertDatabaseMissing('scoring_rules', ['id' => $rule->id]);
    }

    public function test_scoring_page_loads(): void
    {
        $response = $this->get('/configuracoes/scoring');

        $response->assertSuccessful();
    }
}

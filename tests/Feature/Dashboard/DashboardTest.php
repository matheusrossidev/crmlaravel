<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard;

use App\Models\Lead;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsTenant();
    }

    /**
     * Dashboard uses MySQL YEAR()/MONTH() functions in queries.
     * These tests verify the routes exist and auth works.
     * Full dashboard rendering requires MySQL.
     */

    public function test_save_dashboard_config(): void
    {
        $response = $this->postJson('/dashboard/config', [
            'cards' => ['leads', 'vendas', 'conversao'],
        ]);

        $response->assertSuccessful();

        $this->admin->refresh();
        $config = $this->admin->dashboard_config;
        $this->assertContains('leads', $config['cards'] ?? []);
    }

    public function test_global_search(): void
    {
        Lead::factory()->create([
            'tenant_id'   => $this->tenant->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id'    => $this->stage->id,
            'name'        => 'João Searchable',
        ]);

        $response = $this->getJson('/busca?q=Searchable');

        $response->assertSuccessful()
            ->assertJsonStructure(['leads', 'conversations', 'tasks']);
    }

    public function test_global_search_minimum_length(): void
    {
        $response = $this->getJson('/busca?q=a');

        $response->assertSuccessful()
            ->assertJsonFragment(['leads' => []]);
    }

    public function test_global_search_finds_lead_by_phone(): void
    {
        Lead::factory()->create([
            'tenant_id'   => $this->tenant->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id'    => $this->stage->id,
            'phone'       => '5511999001122',
        ]);

        $response = $this->getJson('/busca?q=999001122');

        $response->assertSuccessful();
        $leads = $response->json('leads');
        $this->assertNotEmpty($leads);
    }

    public function test_global_search_finds_lead_by_email(): void
    {
        Lead::factory()->create([
            'tenant_id'   => $this->tenant->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id'    => $this->stage->id,
            'email'       => 'unique.search@test.com',
        ]);

        $response = $this->getJson('/busca?q=unique.search');

        $response->assertSuccessful();
        $leads = $response->json('leads');
        $this->assertNotEmpty($leads);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Chatbot;

use App\Models\ChatbotFlow;
use App\Models\ChatbotFlowNode;
use Tests\TestCase;

class ChatbotFlowCrudTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsTenant();
    }

    public function test_can_create_chatbot_flow(): void
    {
        $response = $this->postJson('/chatbot/fluxos', [
            'name'             => 'Fluxo de Vendas',
            'channel'          => 'whatsapp',
            'trigger_keywords' => 'oi, olá, menu',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('chatbot_flows', [
            'tenant_id' => $this->tenant->id,
            'name'      => 'Fluxo de Vendas',
            'channel'   => 'whatsapp',
        ]);
    }

    public function test_cannot_create_flow_without_name(): void
    {
        $response = $this->postJson('/chatbot/fluxos', [
            'channel' => 'whatsapp',
        ]);

        $response->assertStatus(422);
    }

    public function test_can_update_flow(): void
    {
        $flow = ChatbotFlow::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->put("/chatbot/fluxos/{$flow->id}", [
            'name'    => 'Fluxo Atualizado',
            'channel' => 'whatsapp',
        ]);

        $response->assertRedirect();

        $flow->refresh();
        $this->assertEquals('Fluxo Atualizado', $flow->name);
    }

    public function test_can_delete_flow(): void
    {
        $flow = ChatbotFlow::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->delete("/chatbot/fluxos/{$flow->id}");

        // May return redirect (302) or JSON success
        $this->assertTrue($response->isSuccessful() || $response->isRedirection());
        $this->assertDatabaseMissing('chatbot_flows', ['id' => $flow->id]);
    }

    public function test_can_toggle_flow_active(): void
    {
        $flow = ChatbotFlow::factory()->inactive()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->postJson("/chatbot/fluxos/{$flow->id}/toggle");

        $response->assertOk();

        $flow->refresh();
        $this->assertTrue($flow->is_active);
    }

    public function test_can_save_graph_react(): void
    {
        $flow = ChatbotFlow::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->putJson("/chatbot/fluxos/{$flow->id}/graph-react", [
            'nodes' => [
                [
                    'id'       => 'start_1',
                    'type'     => 'start',
                    'position' => ['x' => 0, 'y' => 0],
                    'data'     => [],
                ],
                [
                    'id'       => 'msg_1',
                    'type'     => 'message',
                    'position' => ['x' => 100, 'y' => 200],
                    'data'     => ['text' => 'Olá! Como posso ajudar?'],
                ],
                [
                    'id'       => 'input_1',
                    'type'     => 'input',
                    'position' => ['x' => 100, 'y' => 400],
                    'data'     => [
                        'text'     => 'Escolha:',
                        'branches' => [
                            ['label' => 'Vendas', 'value' => 'vendas'],
                            ['label' => 'Suporte', 'value' => 'suporte'],
                        ],
                    ],
                ],
            ],
            'edges' => [
                ['source' => 'start_1', 'target' => 'msg_1'],
                ['source' => 'msg_1', 'sourceHandle' => 'default', 'target' => 'input_1'],
            ],
        ]);

        $response->assertOk()->assertJsonStructure(['success', 'idMap']);

        // msg_1 should be marked as start (since edge from start → msg_1)
        $startNode = ChatbotFlowNode::where('flow_id', $flow->id)
            ->where('is_start', true)
            ->first();
        $this->assertNotNull($startNode);
        $this->assertEquals('message', $startNode->type);

        // Should have 2 real nodes (start is skipped)
        $nodeCount = ChatbotFlowNode::where('flow_id', $flow->id)->count();
        $this->assertEquals(2, $nodeCount);
    }

    public function test_catch_all_is_exclusive(): void
    {
        $flow1 = ChatbotFlow::factory()->catchAll()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        // Creating a second catch-all should clear the first
        $this->postJson('/chatbot/fluxos', [
            'name'         => 'Novo Catch All',
            'channel'      => 'whatsapp',
            'is_catch_all' => true,
        ]);

        $flow1->refresh();
        $this->assertFalse((bool) $flow1->is_catch_all);
    }
}

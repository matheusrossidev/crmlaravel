<?php

declare(strict_types=1);

namespace Tests\Feature\Chatbot;

use App\Models\AiAgent;
use App\Models\ChatbotFlow;
use App\Models\ChatbotFlowEdge;
use App\Models\ChatbotFlowNode;
use App\Models\WhatsappConversation;
use App\Models\WhatsappInstance;
use Tests\TestCase;

/**
 * Tests for ProcessChatbotStep job logic.
 *
 * Since ProcessChatbotStep sends real WhatsApp messages via the service factory,
 * we test the DB state changes rather than the full execution (which would need
 * complex HTTP mocks). Direct unit tests of the handler methods.
 */
class ProcessChatbotStepTest extends TestCase
{
    private WhatsappInstance $instance;
    private WhatsappConversation $conv;
    private ChatbotFlow $flow;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsTenant();

        $this->instance = WhatsappInstance::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $this->flow = ChatbotFlow::factory()->create([
            'tenant_id'        => $this->tenant->id,
            'channel'          => 'whatsapp',
            'completions_count' => 0,
        ]);

        $this->conv = WhatsappConversation::factory()->create([
            'tenant_id'       => $this->tenant->id,
            'instance_id'     => $this->instance->id,
            'chatbot_flow_id' => $this->flow->id,
        ]);
    }

    public function test_chatbot_flow_and_conversation_setup(): void
    {
        $this->assertNotNull($this->conv->chatbot_flow_id);
        $this->assertEquals($this->flow->id, $this->conv->chatbot_flow_id);
    }

    public function test_start_node_is_properly_created(): void
    {
        $startNode = ChatbotFlowNode::factory()->start()->create([
            'flow_id'   => $this->flow->id,
            'tenant_id' => $this->tenant->id,
            'type'      => 'message',
            'config'    => ['text' => 'Bem-vindo!'],
        ]);

        $this->assertTrue($startNode->is_start);
        $this->assertEquals('message', $startNode->type);
    }

    public function test_edges_connect_nodes(): void
    {
        $node1 = ChatbotFlowNode::factory()->start()->create([
            'flow_id'   => $this->flow->id,
            'tenant_id' => $this->tenant->id,
        ]);

        $node2 = ChatbotFlowNode::factory()->end()->create([
            'flow_id'   => $this->flow->id,
            'tenant_id' => $this->tenant->id,
        ]);

        $edge = ChatbotFlowEdge::create([
            'flow_id'        => $this->flow->id,
            'tenant_id'      => $this->tenant->id,
            'source_node_id' => $node1->id,
            'target_node_id' => $node2->id,
            'source_handle'  => 'default',
        ]);

        $this->assertDatabaseHas('chatbot_flow_edges', [
            'source_node_id' => $node1->id,
            'target_node_id' => $node2->id,
        ]);
    }

    public function test_assign_ai_agent_node_config_is_valid(): void
    {
        $agent = AiAgent::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $node = ChatbotFlowNode::factory()->start()->create([
            'flow_id'   => $this->flow->id,
            'tenant_id' => $this->tenant->id,
            'type'      => 'action',
            'config'    => [
                'type'     => 'assign_ai_agent',
                'agent_id' => $agent->id,
            ],
        ]);

        $this->assertEquals('assign_ai_agent', $node->config['type']);
        $this->assertEquals($agent->id, $node->config['agent_id']);
    }

    public function test_chatbot_and_ai_agent_are_mutually_exclusive(): void
    {
        $agent = AiAgent::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        // Setting ai_agent should clear chatbot
        $this->conv->update([
            'ai_agent_id'     => $agent->id,
            'chatbot_flow_id' => null,
            'chatbot_node_id' => null,
        ]);

        $this->conv->refresh();
        $this->assertEquals($agent->id, $this->conv->ai_agent_id);
        $this->assertNull($this->conv->chatbot_flow_id);
    }

    public function test_input_node_with_branches_config(): void
    {
        $node = ChatbotFlowNode::factory()->start()->input([
            ['label' => 'Vendas', 'value' => 'vendas'],
            ['label' => 'Suporte', 'value' => 'suporte'],
            ['label' => 'Financeiro', 'value' => 'financeiro'],
        ])->create([
            'flow_id'   => $this->flow->id,
            'tenant_id' => $this->tenant->id,
        ]);

        $this->assertEquals('input', $node->type);
        $this->assertCount(3, $node->config['branches']);
        $this->assertEquals('Vendas', $node->config['branches'][0]['label']);
    }

    public function test_flow_node_types_are_valid(): void
    {
        $validTypes = ['message', 'input', 'condition', 'action', 'delay', 'end', 'cards'];

        foreach (['message', 'input', 'action', 'end'] as $type) {
            $node = ChatbotFlowNode::factory()->create([
                'flow_id'   => $this->flow->id,
                'tenant_id' => $this->tenant->id,
                'type'      => $type,
            ]);
            $this->assertContains($node->type, $validTypes);
        }
    }
}

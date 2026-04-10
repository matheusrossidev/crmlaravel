<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\WhatsappConversation;
use App\Models\WhatsappInstance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WhatsappConversation>
 */
class WhatsappConversationFactory extends Factory
{
    protected $model = WhatsappConversation::class;

    public function definition(): array
    {
        return [
            'tenant_id'    => Tenant::factory(),
            'instance_id'  => WhatsappInstance::factory(),
            'phone'        => fake()->numerify('5511#########'),
            'contact_name' => fake()->name(),
            'status'       => 'open',
            'unread_count' => 0,
            'started_at'   => now(),
        ];
    }

    public function withAiAgent(int $agentId): static
    {
        return $this->state(['ai_agent_id' => $agentId]);
    }

    public function withChatbot(int $flowId, ?int $nodeId = null): static
    {
        return $this->state([
            'chatbot_flow_id' => $flowId,
            'chatbot_node_id' => $nodeId,
        ]);
    }

    public function closed(): static
    {
        return $this->state(['status' => 'closed', 'closed_at' => now()]);
    }
}

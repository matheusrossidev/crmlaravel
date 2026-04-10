<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\WhatsappConversation;
use App\Models\WhatsappMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WhatsappMessage>
 */
class WhatsappMessageFactory extends Factory
{
    protected $model = WhatsappMessage::class;

    public function definition(): array
    {
        return [
            'tenant_id'       => Tenant::factory(),
            'conversation_id' => WhatsappConversation::factory(),
            'waha_message_id' => 'msg_' . fake()->unique()->uuid(),
            'direction'       => 'inbound',
            'type'            => 'text',
            'body'            => fake()->sentence(),
            'sent_at'         => now(),
            'ack'             => 'pending',
        ];
    }

    public function outbound(): static
    {
        return $this->state(['direction' => 'outbound']);
    }

    public function fromAi(int $agentId): static
    {
        return $this->state([
            'direction'        => 'outbound',
            'sent_by'          => 'ai_agent',
            'sent_by_agent_id' => $agentId,
        ]);
    }

    public function fromChatbot(): static
    {
        return $this->state([
            'direction' => 'outbound',
            'sent_by'   => 'chatbot',
        ]);
    }

    public function image(): static
    {
        return $this->state([
            'type'       => 'image',
            'media_url'  => 'https://example.com/image.jpg',
            'media_mime' => 'image/jpeg',
        ]);
    }
}

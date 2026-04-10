<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\InstagramConversation;
use App\Models\InstagramMessage;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InstagramMessage>
 */
class InstagramMessageFactory extends Factory
{
    protected $model = InstagramMessage::class;

    public function definition(): array
    {
        return [
            'tenant_id'       => Tenant::factory(),
            'conversation_id' => InstagramConversation::factory(),
            'ig_message_id'   => 'ig_' . fake()->unique()->uuid(),
            'direction'       => 'inbound',
            'type'            => 'text',
            'body'            => fake()->sentence(),
            'sent_at'         => now(),
        ];
    }

    public function outbound(): static
    {
        return $this->state(['direction' => 'outbound']);
    }
}

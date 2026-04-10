<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Lead;
use App\Models\ScheduledMessage;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScheduledMessage>
 */
class ScheduledMessageFactory extends Factory
{
    protected $model = ScheduledMessage::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'lead_id'   => Lead::factory(),
            'type'      => 'text',
            'body'      => fake()->sentence(),
            'send_at'   => now()->addHour(),
            'status'    => 'pending',
        ];
    }

    public function overdue(): static
    {
        return $this->state(['send_at' => now()->subMinutes(5)]);
    }

    public function sent(): static
    {
        return $this->state([
            'status'  => 'sent',
            'sent_at' => now(),
        ]);
    }
}

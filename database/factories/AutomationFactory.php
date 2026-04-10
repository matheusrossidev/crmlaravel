<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Automation;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Automation>
 */
class AutomationFactory extends Factory
{
    protected $model = Automation::class;

    public function definition(): array
    {
        return [
            'tenant_id'    => Tenant::factory(),
            'name'         => 'Automação ' . fake()->word(),
            'is_active'    => true,
            'trigger_type' => 'conversation_created',
            'conditions'   => [],
            'actions'      => [
                ['type' => 'add_tag', 'config' => ['tag' => 'novo']],
            ],
            'run_count'    => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function onMessageReceived(): static
    {
        return $this->state(['trigger_type' => 'message_received']);
    }

    public function onLeadCreated(): static
    {
        return $this->state(['trigger_type' => 'lead_created']);
    }
}

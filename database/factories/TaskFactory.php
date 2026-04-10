<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Task;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'tenant_id'   => Tenant::factory(),
            'subject'     => fake()->sentence(4),
            'type'        => fake()->randomElement(['call', 'email', 'task', 'visit', 'whatsapp', 'meeting']),
            'status'      => 'pending',
            'priority'    => 'medium',
            'due_date'    => now()->addDays(3),
        ];
    }

    public function completed(): static
    {
        return $this->state(['status' => 'completed', 'completed_at' => now()]);
    }

    public function highPriority(): static
    {
        return $this->state(['priority' => 'high']);
    }
}

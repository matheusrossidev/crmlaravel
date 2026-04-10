<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ChatbotFlow;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChatbotFlow>
 */
class ChatbotFlowFactory extends Factory
{
    protected $model = ChatbotFlow::class;

    public function definition(): array
    {
        return [
            'tenant_id'        => Tenant::factory(),
            'name'             => 'Fluxo ' . fake()->word(),
            'channel'          => 'whatsapp',
            'is_active'        => true,
            'trigger_keywords' => ['oi', 'olá', 'menu'],
            'trigger_type'     => 'keyword',
        ];
    }

    public function instagram(): static
    {
        return $this->state(['channel' => 'instagram']);
    }

    public function website(): static
    {
        return $this->state(['channel' => 'website']);
    }

    public function catchAll(): static
    {
        return $this->state(['is_catch_all' => true, 'trigger_keywords' => []]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}

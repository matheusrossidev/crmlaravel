<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AiAgent;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiAgent>
 */
class AiAgentFactory extends Factory
{
    protected $model = AiAgent::class;

    public function definition(): array
    {
        return [
            'tenant_id'              => Tenant::factory(),
            'name'                   => fake()->randomElement(['Camila', 'Sophia', 'Ana', 'Lucas']),
            'objective'              => 'sales',
            'communication_style'    => 'friendly',
            'company_name'           => fake()->company(),
            'language'               => 'pt-BR',
            'persona_description'    => 'Assistente comercial dedicada.',
            'max_message_length'     => 500,
            'response_delay_seconds' => 2,
            'response_wait_seconds'  => 5,
            'is_active'              => true,
            'use_agno'               => true,
            'channel'                => 'whatsapp',
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function withCalendar(): static
    {
        return $this->state([
            'enable_calendar_tool'      => true,
            'calendar_tool_instructions' => 'Agendar consultas das 9h às 18h, segunda a sexta.',
        ]);
    }

    public function withPipeline(): static
    {
        return $this->state(['enable_pipeline_tool' => true]);
    }

    public function website(): static
    {
        return $this->state([
            'channel'      => 'website',
            'website_token' => fake()->uuid(),
        ]);
    }
}

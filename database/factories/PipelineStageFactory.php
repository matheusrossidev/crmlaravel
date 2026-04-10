<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Pipeline;
use App\Models\PipelineStage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PipelineStage>
 */
class PipelineStageFactory extends Factory
{
    protected $model = PipelineStage::class;

    public function definition(): array
    {
        return [
            'pipeline_id' => Pipeline::factory(),
            'name'        => fake()->randomElement(['Novo', 'Qualificação', 'Proposta', 'Negociação', 'Fechamento']),
            'color'       => '#6B7280',
            'position'    => 0,
            'is_won'      => false,
            'is_lost'     => false,
        ];
    }

    public function won(): static
    {
        return $this->state(['is_won' => true, 'name' => 'Ganho', 'color' => '#10B981']);
    }

    public function lost(): static
    {
        return $this->state(['is_lost' => true, 'name' => 'Perdido', 'color' => '#EF4444']);
    }
}

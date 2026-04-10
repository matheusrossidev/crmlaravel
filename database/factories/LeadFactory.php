<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Lead;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        return [
            'tenant_id'   => Tenant::factory(),
            'name'        => fake()->name(),
            'phone'       => fake()->numerify('5511#########'),
            'email'       => fake()->unique()->safeEmail(),
            'pipeline_id' => Pipeline::factory(),
            'stage_id'    => PipelineStage::factory(),
            'source'      => 'manual',
            'tags'        => [],
        ];
    }

    public function withCompany(): static
    {
        return $this->state([
            'company' => fake()->company(),
            'value'   => fake()->randomFloat(2, 100, 50000),
        ]);
    }

    public function won(): static
    {
        return $this->state(function () {
            $stage = PipelineStage::factory()->won();
            return ['stage_id' => $stage];
        });
    }

    public function archived(): static
    {
        return $this->state(['status' => 'archived']);
    }
}

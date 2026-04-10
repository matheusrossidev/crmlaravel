<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\NurtureSequence;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NurtureSequence>
 */
class NurtureSequenceFactory extends Factory
{
    protected $model = NurtureSequence::class;

    public function definition(): array
    {
        return [
            'tenant_id'            => Tenant::factory(),
            'name'                 => 'Sequência ' . fake()->word(),
            'is_active'            => true,
            'channel'              => 'whatsapp',
            'exit_on_reply'        => true,
            'exit_on_stage_change' => false,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}

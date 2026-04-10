<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CustomFieldDefinition;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomFieldDefinition>
 */
class CustomFieldDefinitionFactory extends Factory
{
    protected $model = CustomFieldDefinition::class;

    public function definition(): array
    {
        return [
            'tenant_id'  => Tenant::factory(),
            'name'       => fake()->unique()->slug(2),
            'label'      => fake()->words(2, true),
            'field_type' => 'text',
            'is_active'  => true,
            'sort_order' => 0,
        ];
    }

    public function select(array $options = ['A', 'B', 'C']): static
    {
        return $this->state([
            'field_type'   => 'select',
            'options_json' => $options,
        ]);
    }

    public function number(): static
    {
        return $this->state(['field_type' => 'number']);
    }

    public function date(): static
    {
        return $this->state(['field_type' => 'date']);
    }
}

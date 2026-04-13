<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Form;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Form>
 */
class FormFactory extends Factory
{
    protected $model = Form::class;

    public function definition(): array
    {
        return [
            'tenant_id'         => Tenant::factory(),
            'name'              => 'Formulário ' . fake()->word(),
            'slug'              => fake()->unique()->slug(3),
            'type'              => 'classic',
            'is_active'         => true,
            'fields'            => [
                ['id' => 'f1', 'type' => 'text', 'label' => 'Nome', 'required' => true, 'order' => 0],
                ['id' => 'f2', 'type' => 'email', 'label' => 'E-mail', 'required' => true, 'order' => 1],
                ['id' => 'f3', 'type' => 'tel', 'label' => 'Telefone', 'required' => false, 'order' => 2],
            ],
            'mappings'          => ['f1' => 'name', 'f2' => 'email', 'f3' => 'phone'],
            'confirmation_type' => 'message',
            'confirmation_value' => 'Obrigado pelo contato!',
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function conversational(): static
    {
        return $this->state(['type' => 'conversational']);
    }

    public function expired(): static
    {
        return $this->state(['expires_at' => now()->subDay()]);
    }
}

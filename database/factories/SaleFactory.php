<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Lead;
use App\Models\Pipeline;
use App\Models\Sale;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    protected $model = Sale::class;

    public function definition(): array
    {
        return [
            'tenant_id'   => Tenant::factory(),
            'lead_id'     => Lead::factory(),
            'pipeline_id' => Pipeline::factory(),
            'value'       => fake()->randomFloat(2, 100, 50000),
            'closed_at'   => now(),
        ];
    }
}

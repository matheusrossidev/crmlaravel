<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PartnerCommission;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PartnerCommission>
 */
class PartnerCommissionFactory extends Factory
{
    protected $model = PartnerCommission::class;

    public function definition(): array
    {
        return [
            'tenant_id'        => Tenant::factory(),
            'client_tenant_id' => Tenant::factory(),
            'asaas_payment_id' => 'pay_' . fake()->unique()->uuid(),
            'amount'           => fake()->randomFloat(2, 10, 500),
            'status'           => 'pending',
            'available_at'     => now()->addDays(30)->toDateString(),
        ];
    }

    public function available(): static
    {
        return $this->state([
            'status'       => 'available',
            'available_at' => now()->subDay()->toDateString(),
        ]);
    }

    public function paid(): static
    {
        return $this->state(['status' => 'paid']);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => 'cancelled']);
    }
}

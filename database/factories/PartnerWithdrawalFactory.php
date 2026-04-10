<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PartnerWithdrawal;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PartnerWithdrawal>
 */
class PartnerWithdrawalFactory extends Factory
{
    protected $model = PartnerWithdrawal::class;

    public function definition(): array
    {
        return [
            'tenant_id'          => Tenant::factory(),
            'amount'             => fake()->randomFloat(2, 50, 2000),
            'status'             => 'pending',
            'pix_key'            => fake()->numerify('###########'),
            'pix_key_type'       => 'cpf',
            'pix_holder_name'    => fake()->name(),
            'pix_holder_cpf_cnpj' => fake()->numerify('###########'),
            'requested_at'       => now(),
        ];
    }

    public function approved(): static
    {
        return $this->state(['status' => 'approved', 'approved_at' => now()]);
    }

    public function paid(): static
    {
        return $this->state([
            'status'      => 'paid',
            'approved_at' => now()->subHour(),
            'paid_at'     => now(),
        ]);
    }
}

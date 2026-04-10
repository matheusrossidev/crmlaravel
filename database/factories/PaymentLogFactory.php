<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PaymentLog;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentLog>
 */
class PaymentLogFactory extends Factory
{
    protected $model = PaymentLog::class;

    public function definition(): array
    {
        return [
            'tenant_id'        => Tenant::factory(),
            'type'             => 'subscription',
            'description'      => 'Assinatura plano professional',
            'amount'           => fake()->randomFloat(2, 50, 500),
            'asaas_payment_id' => 'pay_' . fake()->unique()->uuid(),
            'status'           => 'confirmed',
            'paid_at'          => now(),
        ];
    }

    public function stripe(): static
    {
        return $this->state([
            'asaas_payment_id' => 'cs_' . fake()->unique()->regexify('[a-zA-Z0-9]{24}'),
            'description'      => 'Stripe subscription',
        ]);
    }

    public function tokenIncrement(): static
    {
        return $this->state([
            'type'        => 'token_increment',
            'description' => 'Pacote de 100000 tokens',
        ]);
    }
}

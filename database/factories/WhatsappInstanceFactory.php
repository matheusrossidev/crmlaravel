<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\WhatsappInstance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WhatsappInstance>
 */
class WhatsappInstanceFactory extends Factory
{
    protected $model = WhatsappInstance::class;

    public function definition(): array
    {
        return [
            'tenant_id'    => Tenant::factory(),
            'session_name' => 'tenant_' . fake()->unique()->randomNumber(5),
            'provider'     => 'waha',
            'status'       => 'connected',
            'phone_number' => fake()->numerify('5511#########'),
        ];
    }

    public function cloudApi(): static
    {
        return $this->state([
            'provider'         => 'cloud_api',
            'phone_number_id'  => fake()->numerify('##############'),
            'waba_id'          => fake()->numerify('##############'),
            'access_token'     => 'EAAtest' . fake()->regexify('[a-zA-Z0-9]{40}'),
            'token_expires_at' => now()->addDays(60),
        ]);
    }

    public function disconnected(): static
    {
        return $this->state(['status' => 'disconnected']);
    }
}

<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\InstagramInstance;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InstagramInstance>
 */
class InstagramInstanceFactory extends Factory
{
    protected $model = InstagramInstance::class;

    public function definition(): array
    {
        return [
            'tenant_id'              => Tenant::factory(),
            'ig_business_account_id' => fake()->numerify('##############'),
            'username'               => fake()->userName(),
            'access_token'           => 'EAAtest' . fake()->regexify('[a-zA-Z0-9]{40}'),
            'status'                 => 'connected',
        ];
    }
}

<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ApiKey;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ApiKey>
 */
class ApiKeyFactory extends Factory
{
    protected $model = ApiKey::class;

    public function definition(): array
    {
        $key = Str::random(40);

        return [
            'tenant_id'        => Tenant::factory(),
            'name'             => 'Test API Key',
            'key_hash'         => hash('sha256', $key),
            'key_prefix'       => substr($key, 0, 8),
            'permissions_json' => ['leads' => true, 'pipelines' => true],
            'is_active'        => true,
        ];
    }
}

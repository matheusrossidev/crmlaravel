<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\InstagramConversation;
use App\Models\InstagramInstance;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InstagramConversation>
 */
class InstagramConversationFactory extends Factory
{
    protected $model = InstagramConversation::class;

    public function definition(): array
    {
        return [
            'tenant_id'        => Tenant::factory(),
            'instance_id'      => InstagramInstance::factory(),
            'igsid'            => fake()->unique()->numerify('##############'),
            'contact_name'     => fake()->name(),
            'contact_username' => fake()->userName(),
            'status'           => 'open',
            'unread_count'     => 0,
            'started_at'       => now(),
            'tags'             => [],
        ];
    }
}

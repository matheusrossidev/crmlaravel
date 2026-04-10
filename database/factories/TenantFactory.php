<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        return [
            'name'                => fake()->company(),
            'slug'                => fake()->unique()->slug(2),
            'plan'                => 'professional',
            'status'              => 'active',
            'subscription_status' => 'active',
            'max_users'           => 10,
            'max_leads'           => 5000,
            'max_pipelines'       => 5,
            'max_custom_fields'   => 20,
            'max_chatbot_flows'   => 5,
            'max_ai_agents'       => 3,
            'max_departments'          => 5,
            'onboarding_completed_at'  => now(),
        ];
    }

    public function trial(): static
    {
        return $this->state([
            'status'        => 'trial',
            'plan'          => 'free',
            'trial_ends_at' => now()->addDays(7),
        ]);
    }

    public function suspended(): static
    {
        return $this->state(['status' => 'suspended']);
    }

    public function partner(): static
    {
        return $this->state([
            'status' => 'partner',
            'plan'   => 'partner',
        ]);
    }

    public function withStripe(): static
    {
        return $this->state([
            'billing_provider'       => 'stripe',
            'billing_country'        => 'US',
            'billing_currency'       => 'usd',
            'stripe_customer_id'     => 'cus_' . fake()->regexify('[a-zA-Z0-9]{14}'),
            'stripe_subscription_id' => 'sub_' . fake()->regexify('[a-zA-Z0-9]{14}'),
        ]);
    }

    public function withAsaas(): static
    {
        return $this->state([
            'billing_provider'      => 'asaas',
            'billing_country'       => 'BR',
            'billing_currency'      => 'brl',
            'asaas_customer_id'     => 'cus_' . fake()->regexify('[a-zA-Z0-9]{16}'),
            'asaas_subscription_id' => 'sub_' . fake()->regexify('[a-zA-Z0-9]{16}'),
        ]);
    }

    public function referredBy(Tenant $partner): static
    {
        return $this->state([
            'referred_by_agency_id'  => $partner->id,
            'partner_commission_pct' => 20,
        ]);
    }
}

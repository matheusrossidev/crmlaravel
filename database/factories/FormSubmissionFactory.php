<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FormSubmission>
 */
class FormSubmissionFactory extends Factory
{
    protected $model = FormSubmission::class;

    public function definition(): array
    {
        return [
            'form_id'      => Form::factory(),
            'tenant_id'    => Tenant::factory(),
            'data'         => ['f1' => fake()->name(), 'f2' => fake()->email(), 'f3' => fake()->phoneNumber()],
            'ip_address'   => fake()->ipv4(),
            'user_agent'   => fake()->userAgent(),
            'submitted_at' => now(),
        ];
    }
}

<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\NurtureSequence;
use App\Models\NurtureSequenceStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NurtureSequenceStep>
 */
class NurtureSequenceStepFactory extends Factory
{
    protected $model = NurtureSequenceStep::class;

    public function definition(): array
    {
        return [
            'sequence_id'   => NurtureSequence::factory(),
            'position'      => 1,
            'delay_minutes' => 60,
            'type'          => 'message',
            'config'        => ['body' => 'Olá {{nome}}, tudo bem?'],
            'is_active'     => true,
        ];
    }
}

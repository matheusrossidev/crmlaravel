<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanDefinition extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'price_monthly',
        'trial_days',
        'features_json',
        'is_active',
    ];

    protected $casts = [
        'price_monthly' => 'decimal:2',
        'trial_days'    => 'integer',
        'features_json' => 'array',
        'is_active'     => 'boolean',
    ];
}

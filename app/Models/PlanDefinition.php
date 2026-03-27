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
        'price_usd',
        'stripe_price_id',
        'trial_days',
        'features_json',
        'features_en_json',
        'is_active',
        'is_visible',
    ];

    protected $casts = [
        'price_monthly'   => 'decimal:2',
        'price_usd'       => 'decimal:2',
        'trial_days'      => 'integer',
        'features_json'   => 'array',
        'features_en_json'=> 'array',
        'is_active'       => 'boolean',
        'is_visible'      => 'boolean',
    ];
}

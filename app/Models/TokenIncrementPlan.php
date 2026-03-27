<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TokenIncrementPlan extends Model
{
    protected $fillable = [
        'display_name',
        'tokens_amount',
        'price',
        'price_usd',
        'stripe_price_id',
        'is_active',
    ];

    protected $casts = [
        'tokens_amount' => 'integer',
        'price'         => 'decimal:2',
        'price_usd'     => 'decimal:2',
        'is_active'     => 'boolean',
    ];

    public function increments(): HasMany
    {
        return $this->hasMany(TenantTokenIncrement::class);
    }

    public function tokensFormatted(): string
    {
        $k = $this->tokens_amount / 1000;
        return $k >= 1000
            ? number_format($k / 1000, 0, ',', '.') . 'M'
            : number_format($k, 0, ',', '.') . 'k';
    }
}

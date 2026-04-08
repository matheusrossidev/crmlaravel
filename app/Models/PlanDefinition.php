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
        'stripe_price_id',      // legacy — manter por compat backward
        'stripe_price_id_brl',
        'stripe_price_id_usd',
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

    /**
     * Resolve o price_id do Stripe correto baseado na moeda do tenant.
     * Cada plano tem 2 produtos no Stripe (BRL e USD) — sao prices diferentes
     * porque os valores nao sao 1:1 (R$ 197 != US$ 39).
     *
     * Fallback: se a coluna especifica nao tiver, tenta o legacy stripe_price_id.
     */
    public function stripePriceIdFor(string $currency): ?string
    {
        $currency = strtoupper($currency);

        if ($currency === 'BRL') {
            return $this->stripe_price_id_brl ?? $this->stripe_price_id;
        }

        if ($currency === 'USD') {
            return $this->stripe_price_id_usd ?? $this->stripe_price_id;
        }

        return $this->stripe_price_id;
    }

    /**
     * Resolve o preco do plano na moeda informada.
     */
    public function priceFor(string $currency): float
    {
        $currency = strtoupper($currency);
        return $currency === 'USD'
            ? (float) ($this->price_usd ?? 0)
            : (float) ($this->price_monthly ?? 0);
    }
}

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
        'billing_cycle',
        'group_slug',
        'is_recommended',
    ];

    protected $casts = [
        'price_monthly'   => 'decimal:2',
        'price_usd'       => 'decimal:2',
        'trial_days'      => 'integer',
        'features_json'   => 'array',
        'features_en_json'=> 'array',
        'is_active'       => 'boolean',
        'is_visible'      => 'boolean',
        'is_recommended'  => 'boolean',
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

    public function isYearly(): bool
    {
        return $this->billing_cycle === 'yearly';
    }

    public function isMonthly(): bool
    {
        return $this->billing_cycle !== 'yearly';
    }

    public function monthlyVariant(): ?self
    {
        if (! $this->group_slug) {
            return $this->isMonthly() ? $this : null;
        }

        return self::where('group_slug', $this->group_slug)
            ->where('billing_cycle', 'monthly')
            ->where('is_active', true)
            ->first();
    }

    public function yearlyVariant(): ?self
    {
        if (! $this->group_slug) {
            return $this->isYearly() ? $this : null;
        }

        return self::where('group_slug', $this->group_slug)
            ->where('billing_cycle', 'yearly')
            ->where('is_active', true)
            ->first();
    }

    /**
     * Desconto percentual do anual vs 12× mensal. Retorna null se nao houver
     * plano anual vinculado ou se o valor anual for >= 12× mensal.
     */
    public function yearlyDiscountPctVs(self $monthly, string $currency): ?int
    {
        $yearly  = $this->priceFor($currency);
        $monthlyPrice = $monthly->priceFor($currency);

        if ($monthlyPrice <= 0 || $yearly <= 0) {
            return null;
        }

        $baseline = $monthlyPrice * 12;
        if ($yearly >= $baseline) {
            return null;
        }

        return (int) round((1 - $yearly / $baseline) * 100);
    }
}

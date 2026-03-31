<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartnerRank extends Model
{
    protected $fillable = [
        'name', 'image_path', 'min_sales', 'commission_pct',
        'sort_order', 'color',
    ];

    protected $casts = [
        'min_sales'      => 'integer',
        'commission_pct' => 'decimal:2',
        'sort_order'     => 'integer',
    ];

    /**
     * Get the rank for a given number of active clients.
     */
    public static function forSalesCount(int $count): ?self
    {
        return static::where('min_sales', '<=', $count)
            ->orderByDesc('min_sales')
            ->first();
    }

    /**
     * Get the next rank above the current one.
     */
    public static function nextAfter(int $currentMinSales): ?self
    {
        return static::where('min_sales', '>', $currentMinSales)
            ->orderBy('min_sales')
            ->first();
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdSpend extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $fillable = [
        'tenant_id', 'campaign_id', 'date',
        'spend', 'impressions', 'clicks', 'conversions', 'cpc', 'cpm', 'ctr',
    ];

    protected $casts = [
        'date' => 'date',
        'spend' => 'decimal:2',
        'impressions' => 'integer',
        'clicks' => 'integer',
        'conversions' => 'integer',
        'cpc' => 'decimal:4',
        'cpm' => 'decimal:4',
        'ctr' => 'decimal:4',
        'created_at' => 'datetime',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}

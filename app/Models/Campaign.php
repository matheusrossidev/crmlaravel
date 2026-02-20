<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'platform', 'external_id', 'name', 'status',
        'objective', 'budget_daily', 'budget_lifetime', 'metrics_json', 'last_sync_at',
    ];

    protected $casts = [
        'metrics_json' => 'array',
        'budget_daily' => 'decimal:2',
        'budget_lifetime' => 'decimal:2',
        'last_sync_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function adSpends(): HasMany
    {
        return $this->hasMany(AdSpend::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}

<?php

declare(strict_types=1);

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (auth()->check()) {
                // Always filter by tenant_id. Super admin with tenant_id=null gets 0 → sees nothing.
                $builder->where($builder->getModel()->getTable() . '.tenant_id', auth()->user()->tenant_id ?? 0);
            }
        });

        static::creating(function ($model) {
            if (auth()->check() && !$model->tenant_id) {
                $model->tenant_id = auth()->user()->tenant_id;
            }
        });
    }

    public function tenant()
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->withoutGlobalScope('tenant')->where('tenant_id', $tenantId);
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Cache;

class FeatureFlag extends Model
{
    protected $fillable = [
        'slug',
        'label',
        'description',
        'is_enabled_globally',
        'sort_order',
    ];

    protected $casts = [
        'is_enabled_globally' => 'boolean',
    ];

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'feature_tenant', 'feature_id', 'tenant_id')
            ->withPivot('is_enabled')
            ->withTimestamps();
    }

    /**
     * Check if a feature is enabled for a specific tenant.
     */
    public static function isEnabled(string $slug, int $tenantId): bool
    {
        $cacheKey = "feature:{$slug}:{$tenantId}";

        return Cache::remember($cacheKey, 300, function () use ($slug, $tenantId) {
            $feature = self::where('slug', $slug)->first();
            if (! $feature) {
                return false;
            }

            if ($feature->is_enabled_globally) {
                return true;
            }

            return $feature->tenants()
                ->where('tenant_id', $tenantId)
                ->where('is_enabled', true)
                ->exists();
        });
    }

    /**
     * Clear cached feature flag for a tenant.
     */
    public static function clearCache(string $slug, ?int $tenantId = null): void
    {
        if ($tenantId) {
            Cache::forget("feature:{$slug}:{$tenantId}");
        } else {
            // Clear for all tenants — called when toggling global
            $tenantIds = Tenant::pluck('id');
            foreach ($tenantIds as $id) {
                Cache::forget("feature:{$slug}:{$id}");
            }
        }
    }
}

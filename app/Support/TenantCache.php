<?php

declare(strict_types=1);

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

class TenantCache
{
    /**
     * Build a tenant-scoped cache key.
     */
    public static function key(string $section, string $suffix = ''): string
    {
        $tenantId = activeTenantId() ?? 0;

        return "t:{$tenantId}:{$section}" . ($suffix !== '' ? ":{$suffix}" : '');
    }

    /**
     * Cache::remember with automatic tenant scoping.
     */
    public static function remember(string $section, int $ttlSeconds, Closure $callback, string $suffix = ''): mixed
    {
        return Cache::remember(self::key($section, $suffix), $ttlSeconds, $callback);
    }

    /**
     * Forget a specific tenant-scoped cache key.
     */
    public static function forget(string $section, string $suffix = ''): void
    {
        Cache::forget(self::key($section, $suffix));
    }

    /**
     * Forget multiple cache keys for a tenant by section prefix.
     */
    public static function forgetMany(int $tenantId, array $sections): void
    {
        foreach ($sections as $section) {
            Cache::forget("t:{$tenantId}:{$section}");
        }
    }
}

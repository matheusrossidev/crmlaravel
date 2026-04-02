<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\FeatureFlag;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeatureController extends Controller
{
    public function index(): View
    {
        $features = FeatureFlag::orderBy('sort_order')->get();
        $tenants  = Tenant::orderBy('name')->get(['id', 'name', 'slug']);

        // Load enabled tenant IDs per feature
        $features->each(function (FeatureFlag $f) {
            $f->setAttribute('enabled_tenant_ids', $f->tenants()->where('is_enabled', true)->pluck('tenant_id')->toArray());
        });

        return view('master.features.index', compact('features', 'tenants'));
    }

    public function toggleGlobal(FeatureFlag $feature): JsonResponse
    {
        $feature->update(['is_enabled_globally' => ! $feature->is_enabled_globally]);
        FeatureFlag::clearCache($feature->slug);

        return response()->json([
            'success' => true,
            'is_enabled_globally' => $feature->is_enabled_globally,
        ]);
    }

    public function updateTenants(Request $request, FeatureFlag $feature): JsonResponse
    {
        $validated = $request->validate([
            'tenant_ids'   => 'present|array',
            'tenant_ids.*' => 'integer|exists:tenants,id',
        ]);

        $tenantIds = $validated['tenant_ids'];

        // Sync: remove old, add new
        $feature->tenants()->detach();
        foreach ($tenantIds as $id) {
            $feature->tenants()->attach($id, ['is_enabled' => true]);
        }

        // Clear cache for all tenants of this feature
        FeatureFlag::clearCache($feature->slug);

        return response()->json([
            'success'    => true,
            'tenant_ids' => $tenantIds,
        ]);
    }
}

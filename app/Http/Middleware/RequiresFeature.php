<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Bloqueia rotas que dependem de uma feature flag estar habilitada pro tenant.
 *
 * Uso nas rotas:
 *   Route::middleware('requires.feature:instagram')->group(...)
 *
 * Resolução (ver `tenantHasFeature` em app/helpers.php):
 *  1. Override individual do tenant (feature_tenant pivot)
 *  2. Plano do tenant lista a feature em features_json.features_enabled[]
 *  3. FeatureFlag.is_enabled_globally
 */
class RequiresFeature
{
    public function handle(Request $request, Closure $next, string $slug)
    {
        if (tenantHasFeature($slug)) {
            return $next($request);
        }

        $message = 'Esta funcionalidade não está disponível no seu plano. Faça upgrade para continuar.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'feature' => $slug,
            ], 403);
        }

        return redirect()
            ->route('billing')
            ->with('error', $message)
            ->with('upgrade_feature', $slug);
    }
}

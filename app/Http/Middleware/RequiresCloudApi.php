<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\WhatsappInstance;
use Closure;
use Illuminate\Http\Request;

/**
 * Bloqueia rotas que só fazem sentido se o tenant tem uma instância
 * WhatsApp Cloud API conectada (provider='cloud_api').
 *
 * Usado pra: templates HSM, health check de tokens, envio via template,
 * relatórios específicos da Cloud API etc.
 *
 * WAHA é fluxo separado — não aciona esse middleware.
 */
class RequiresCloudApi
{
    public function handle(Request $request, Closure $next)
    {
        if (! tenantHasCloudApi()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('wa_cloud.requires_cloud_api'),
                ], 403);
            }

            return redirect()
                ->route('settings.integrations.index')
                ->with('error', __('wa_cloud.requires_cloud_api'));
        }

        return $next($request);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Valida chamadas internas do microsserviço Agno para Laravel.
 *
 * Agno envia: X-Agno-Token: <AGNO_INTERNAL_TOKEN>
 * Laravel valida contra env('AGNO_INTERNAL_TOKEN').
 *
 * O tenant_id deve vir no corpo da requisição para que
 * BelongsToTenant funcione corretamente.
 */
class AgnoInternalMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('X-Agno-Token');

        $expected = env('AGNO_INTERNAL_TOKEN', '');

        if (! $token || $expected === '' || ! hash_equals($expected, $token)) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        // Autentica o tenant a partir do tenant_id no payload
        $tenantId = (int) ($request->input('tenant_id') ?? 0);

        if ($tenantId > 0) {
            $tenant = Tenant::find($tenantId);
            if ($tenant) {
                $request->attributes->set('tenant_id', $tenantId);

                $user = \App\Models\User::where('tenant_id', $tenantId)
                    ->orderByRaw("FIELD(role, 'admin', 'manager', 'viewer')")
                    ->first();

                if ($user) {
                    auth()->setUser($user);
                }
            }
        }

        return $next($request);
    }
}

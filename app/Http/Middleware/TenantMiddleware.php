<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        if (!$user->tenant_id) {
            abort(403, 'Usuário sem tenant associado.');
        }

        $tenant = $user->tenant;

        if ($tenant) {
            // Conta suspensa ou inativa → página de bloqueio
            if (in_array($tenant->status, ['suspended', 'inactive'], true)) {
                // Permitir logout
                if ($request->routeIs('logout')) {
                    return $next($request);
                }
                return redirect()->route('account.suspended');
            }

            // Trial expirado → modal bloqueante exibido no layout (sem redirect)
            // O modal em app.blade.php detecta a situação e bloqueia a interface
        }

        return $next($request);
    }
}

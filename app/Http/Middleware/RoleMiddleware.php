<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Verifica se o usuário autenticado possui um dos roles permitidos.
     *
     * Uso: middleware('role:admin') ou middleware('role:admin,manager')
     * Super admins passam sempre.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Não autenticado.');
        }

        // Super admin sempre passa
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        if (! in_array($user->role, $roles, true)) {
            abort(403, 'Sem permissão para esta ação.');
        }

        return $next($request);
    }
}

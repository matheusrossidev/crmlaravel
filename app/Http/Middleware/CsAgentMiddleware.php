<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CsAgentMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user || (!$user->is_cs_agent && !$user->isSuperAdmin())) {
            abort(403, 'Acesso restrito ao Customer Success.');
        }

        return $next($request);
    }
}

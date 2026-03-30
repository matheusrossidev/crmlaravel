<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TwoFactorMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if ($user && $user->isSuperAdmin() && $user->totp_enabled && !session('2fa:verified')) {
            auth()->logout();
            session(['2fa:redirect_after' => $request->url()]);
            return redirect('/2fa/challenge');
        }

        return $next($request);
    }
}

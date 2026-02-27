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
                // Permitir logout e rotas de billing para regularização
                if ($request->routeIs('logout', 'billing.checkout', 'billing.subscribe')) {
                    return $next($request);
                }
                return redirect()->route('account.suspended');
            }

            if (!$tenant->isExemptFromBilling()) {
                // Trial expirado sem assinatura ativa → redireciona para checkout
                if ($tenant->isTrialExpired() && !$tenant->hasActiveSubscription()) {
                    if (!$request->routeIs('billing.checkout', 'billing.subscribe', 'logout', 'account.suspended')) {
                        return redirect()->route('billing.checkout');
                    }
                }

                // Assinatura overdue/inactive → redireciona para página de cobrança
                if (in_array($tenant->subscription_status, ['overdue', 'inactive'], true)) {
                    if (!$request->routeIs('billing.checkout', 'billing.subscribe', 'settings.billing', 'billing.cancel', 'logout')) {
                        return redirect()->route('settings.billing');
                    }
                }
            }
        }

        return $next($request);
    }
}

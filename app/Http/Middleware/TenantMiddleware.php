<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Super admin sem impersonação → redireciona para master
        if ($user->isSuperAdmin() && !session('impersonating_tenant_id')) {
            return redirect()->route('master.dashboard');
        }

        // CS agent → redireciona para painel CS
        if ($user->isCsAgent()) {
            return redirect()->route('cs.index');
        }

        // Super admin impersonando → permite acesso ao tenant
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        if (!$user->tenant_id) {
            auth()->logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();
            return redirect()->route('login')->withErrors(['email' => 'Sua conta não está associada a nenhuma empresa. Entre em contato com o suporte.']);
        }

        // ── Impersonação de agência parceira ──────────────────────────────
        $impersonatingId = session('impersonating_tenant_id');
        if ($impersonatingId) {
            // Garante que apenas parceiros podem impersonar
            if (!$user->tenant?->isPartner()) {
                session()->forget('impersonating_tenant_id');
                $impersonatingId = null;
            } else {
                // Garante que o tenant-alvo pertence a esta agência
                $targetTenant = \App\Models\Tenant::withoutGlobalScope('tenant')
                    ->where('id', $impersonatingId)
                    ->where('referred_by_agency_id', $user->tenant_id)
                    ->first();

                if (!$targetTenant) {
                    Log::warning('Impersonação rejeitada: tenant-alvo inválido', [
                        'user_id'   => $user->id,
                        'agency_id' => $user->tenant_id,
                        'target_id' => $impersonatingId,
                        'ip'        => $request->ip(),
                    ]);
                    session()->forget('impersonating_tenant_id');
                    $impersonatingId = null;
                } else {
                    // Log de impersonação ativa (uma vez por sessão)
                    if (!session('impersonation_logged')) {
                        Log::info('Impersonação de agência ativa', [
                            'user_id'       => $user->id,
                            'agency_id'     => $user->tenant_id,
                            'target_tenant' => $targetTenant->name,
                            'target_id'     => $impersonatingId,
                            'ip'            => $request->ip(),
                        ]);
                        session(['impersonation_logged' => true]);
                    }
                    // Injeta o tenant ativo no container para o BelongsToTenant trait
                    app()->instance('active_tenant_id', (int) $impersonatingId);
                }
            }
        }

        // Parceiro logado (sem impersonação) → redireciona para portal do parceiro
        if (!$impersonatingId && $user->tenant?->isPartner()) {
            if (!$request->routeIs('partner.*', 'agency.*', 'logout', 'settings.profile*', 'settings.notifications*', 'settings.billing*', 'billing.*', 'account.*', 'feedback.*')) {
                return redirect()->route('partner.dashboard');
            }
        }

        $tenant = $impersonatingId
            ? \App\Models\Tenant::withoutGlobalScope('tenant')->find($impersonatingId)
            : $user->tenant;

        if ($tenant) {
            // Parceiro aguardando aprovação
            if ($tenant->status === 'pending_approval') {
                if (!$request->routeIs('logout', 'account.pending-approval', 'agency.access.exit')) {
                    return redirect()->route('account.pending-approval');
                }
                return $next($request);
            }

            // Conta suspensa ou inativa → página de bloqueio
            if (in_array($tenant->status, ['suspended', 'inactive'], true)) {
                // Permitir logout e rotas de billing para regularização
                if ($request->routeIs('logout', 'billing.checkout', 'billing.subscribe', 'agency.access.exit')) {
                    return $next($request);
                }
                return redirect()->route('account.suspended');
            }

            if (!$tenant->isExemptFromBilling()) {
                // Parceiro sem assinatura → forçar checkout exclusivo do plano parceiro
                if ($tenant->isPartner() && $tenant->subscription_status === null) {
                    if (!$request->routeIs('billing.checkout', 'billing.subscribe', 'logout', 'account.suspended', 'agency.access.exit')) {
                        return redirect()->route('billing.checkout');
                    }
                }

                // Trial expirado sem assinatura ativa → redireciona para checkout
                if ($tenant->isTrialExpired() && !$tenant->hasActiveSubscription()) {
                    if (!$request->routeIs('billing.checkout', 'billing.subscribe', 'logout', 'account.suspended', 'agency.access.exit')) {
                        return redirect()->route('billing.checkout');
                    }
                }

                // Assinatura overdue/inactive → redireciona para página de cobrança
                if (in_array($tenant->subscription_status, ['overdue', 'inactive'], true)) {
                    if (!$request->routeIs('billing.checkout', 'billing.subscribe', 'settings.billing', 'billing.cancel', 'logout', 'agency.access.exit')) {
                        return redirect()->route('settings.billing');
                    }
                }
            }
        }

        // Onboarding obrigatório para admins na primeira vez
        if (
            $tenant &&
            !$tenant->isPartner() &&
            $tenant->onboarding_completed_at === null &&
            ! $request->routeIs('onboarding.*') &&
            ! $request->routeIs('logout') &&
            $user->isAdmin()
        ) {
            return redirect()->route('onboarding.show');
        }

        return $next($request);
    }
}

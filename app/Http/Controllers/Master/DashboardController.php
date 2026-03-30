<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\PaymentLog;
use App\Models\PlanDefinition;
use App\Models\Tenant;
use App\Models\TenantTokenIncrement;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $now = now();

        // --- Contagens de tenants ---
        $stats = [
            'total'         => Tenant::count(),
            'active'        => Tenant::where('status', 'active')->count(),
            'trial'         => Tenant::where('status', 'trial')->count(),
            'partner'       => Tenant::where('status', 'partner')->count(),
            'paying'        => Tenant::where('subscription_status', 'active')->count(),
            'suspended'     => Tenant::whereIn('status', ['suspended', 'inactive'])->count(),
            'new_month'     => Tenant::whereMonth('created_at', $now->month)
                                     ->whereYear('created_at', $now->year)
                                     ->count(),
        ];

        // --- MRR (Monthly Recurring Revenue) ---
        $plans = PlanDefinition::pluck('price_monthly', 'name');

        $mrrSubscriptions = Tenant::where('subscription_status', 'active')
            ->pluck('plan')
            ->sum(fn (string $plan) => (float) ($plans[$plan] ?? 0));

        $mrrTokens = TenantTokenIncrement::where('status', 'paid')
            ->whereMonth('paid_at', $now->month)
            ->whereYear('paid_at', $now->year)
            ->sum('price_paid');

        $revenue = [
            'mrr'            => $mrrSubscriptions,
            'tokens_month'   => (float) $mrrTokens,
            'total_mrr'      => $mrrSubscriptions + (float) $mrrTokens,
            'arr'            => $mrrSubscriptions * 12,
            'churn_month'    => Tenant::where('subscription_status', 'cancelled')
                                    ->whereMonth('updated_at', $now->month)
                                    ->whereYear('updated_at', $now->year)
                                    ->count(),
        ];

        // --- Últimos pagamentos ---
        $recentPayments = PaymentLog::with('tenant')
            ->orderByDesc('paid_at')
            ->limit(10)
            ->get();

        // --- Crescimento: dados por semana (últimas 26 semanas) ---
        $growthWeeks = [];
        for ($i = 25; $i >= 0; $i--) {
            $weekStart = now()->subWeeks($i)->startOfWeek();
            $weekEnd   = (clone $weekStart)->endOfWeek();
            $growthWeeks[] = [
                'label'   => $weekStart->format('d/m'),
                'trial'   => Tenant::where('status', 'trial')->whereBetween('created_at', [$weekStart, $weekEnd])->count(),
                'paying'  => Tenant::where('subscription_status', 'active')->whereBetween('created_at', [$weekStart, $weekEnd])->count(),
                'partner' => Tenant::where('status', 'partner')->whereBetween('created_at', [$weekStart, $weekEnd])->count(),
            ];
        }
        // Agrupar dados — JS filtra no frontend
        $monthlyGrowth = $growthWeeks;

        $recentTenants = Tenant::orderByDesc('created_at')->limit(10)->get();

        // --- Crescimento de Receita: últimos 12 meses ---
        $revenueGrowth = [];
        for ($i = 11; $i >= 0; $i--) {
            $monthStart = now()->subMonths($i)->startOfMonth();
            $monthEnd   = (clone $monthStart)->endOfMonth();
            $label      = $monthStart->format('M/y');

            $subscriptions = (float) PaymentLog::where('type', 'subscription')
                ->where('status', 'confirmed')
                ->whereBetween('paid_at', [$monthStart, $monthEnd])
                ->sum('amount');

            $tokens = (float) PaymentLog::where('type', 'token_increment')
                ->where('status', 'confirmed')
                ->whereBetween('paid_at', [$monthStart, $monthEnd])
                ->sum('amount');

            $revenueGrowth[] = [
                'label'         => $label,
                'subscriptions' => $subscriptions,
                'tokens'        => $tokens,
                'total'         => $subscriptions + $tokens,
            ];
        }

        // Projeção: média dos últimos 3 meses
        $last3 = array_slice($revenueGrowth, -4, 3);
        $avgRevenue = count($last3) > 0 ? array_sum(array_column($last3, 'total')) / count($last3) : 0;
        $projection = round($avgRevenue, 2);

        // Variação mês anterior vs atual
        $currentMonthRev = end($revenueGrowth)['total'] ?? 0;
        $prevMonthRev    = $revenueGrowth[count($revenueGrowth) - 2]['total'] ?? 0;
        $revenueDelta    = $prevMonthRev > 0 ? round((($currentMonthRev - $prevMonthRev) / $prevMonthRev) * 100, 1) : 0;

        return view('master.dashboard', compact(
            'stats', 'revenue', 'recentPayments', 'recentTenants',
            'monthlyGrowth', 'revenueGrowth', 'projection', 'revenueDelta',
        ));
    }
}

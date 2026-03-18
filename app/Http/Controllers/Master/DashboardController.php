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

        $recentTenants = Tenant::orderByDesc('created_at')->limit(10)->get();

        return view('master.dashboard', compact('stats', 'revenue', 'recentPayments', 'recentTenants'));
    }
}

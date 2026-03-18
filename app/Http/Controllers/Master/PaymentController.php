<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\PaymentLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $now = now();

        $query = PaymentLog::with('tenant')->orderByDesc('paid_at');

        // Filtros
        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }
        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->input('tenant_id'));
        }
        if ($request->filled('from')) {
            $query->whereDate('paid_at', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('paid_at', '<=', $request->input('to'));
        }

        $payments = $query->paginate(50)->appends($request->query());

        // Stats
        $revenueThisMonth = PaymentLog::whereMonth('paid_at', $now->month)
            ->whereYear('paid_at', $now->year)
            ->sum('amount');

        $revenueLastMonth = PaymentLog::whereMonth('paid_at', $now->copy()->subMonth()->month)
            ->whereYear('paid_at', $now->copy()->subMonth()->year)
            ->sum('amount');

        $variation = $revenueLastMonth > 0
            ? round((($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100, 1)
            : ($revenueThisMonth > 0 ? 100 : 0);

        $transactionsThisMonth = PaymentLog::whereMonth('paid_at', $now->month)
            ->whereYear('paid_at', $now->year)
            ->count();

        $stats = [
            'revenue_month'      => (float) $revenueThisMonth,
            'revenue_last_month' => (float) $revenueLastMonth,
            'variation'          => $variation,
            'transactions'       => $transactionsThisMonth,
        ];

        return view('master.payments.index', compact('payments', 'stats'));
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\AiUsageLog;
use App\Models\Tenant;
use Illuminate\View\View;

class UsageController extends Controller
{
    public function index(): View
    {
        // Tokens por dia nos últimos 30 dias
        $daily = AiUsageLog::selectRaw('DATE(created_at) as day, SUM(tokens_total) as total')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupByRaw('DATE(created_at)')
            ->orderBy('day')
            ->get();

        // Top tenants por uso total
        $topTenants = AiUsageLog::selectRaw('tenant_id, SUM(tokens_total) as total, COUNT(*) as requests')
            ->groupBy('tenant_id')
            ->orderByDesc('total')
            ->limit(20)
            ->with('tenant:id,name,logo')
            ->get();

        $grandTotal = AiUsageLog::sum('tokens_total');

        return view('master.usage.index', compact('daily', 'topTenants', 'grandTotal'));
    }

    public function show(Tenant $tenant): View
    {
        $daily = AiUsageLog::selectRaw('DATE(created_at) as day, SUM(tokens_total) as total, COUNT(*) as requests')
            ->where('tenant_id', $tenant->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->groupByRaw('DATE(created_at)')
            ->orderBy('day')
            ->get();

        $tenantTotal = AiUsageLog::where('tenant_id', $tenant->id)->sum('tokens_total');

        return view('master.usage.show', compact('tenant', 'daily', 'tenantTotal'));
    }
}

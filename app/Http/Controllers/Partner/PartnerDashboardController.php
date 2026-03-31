<?php

declare(strict_types=1);

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\PartnerAgencyCode;
use App\Models\PartnerCommission;
use App\Models\PartnerRank;
use App\Models\PartnerWithdrawal;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\View\View;

class PartnerDashboardController extends Controller
{
    public function index(): View
    {
        $tenant = auth()->user()->tenant;
        $tenantId = $tenant->id;

        // Clients referred by this partner
        $clients = Tenant::withoutGlobalScope('tenant')
            ->where('referred_by_agency_id', $tenantId)
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'plan', 'status', 'subscription_status', 'created_at']);

        $activeClients = $clients->filter(fn ($c) => in_array($c->status, ['active', 'partner', 'trial']))->count();
        $trialClients = $clients->filter(fn ($c) => $c->status === 'trial')->count();

        // Rank
        $currentRank = PartnerRank::forSalesCount($activeClients);
        $nextRank = $currentRank ? PartnerRank::nextAfter($currentRank->min_sales) : PartnerRank::orderBy('min_sales')->first();

        // Commissions
        $totalCommission = PartnerCommission::where('tenant_id', $tenantId)
            ->whereIn('status', ['pending', 'available', 'withdrawn'])
            ->sum('amount');

        $availableBalance = PartnerCommission::where('tenant_id', $tenantId)
            ->where('status', 'available')
            ->sum('amount');

        $totalWithdrawn = PartnerWithdrawal::where('tenant_id', $tenantId)
            ->where('status', 'paid')
            ->sum('amount');

        // Agency code
        $agencyCode = PartnerAgencyCode::where('tenant_id', $tenantId)->first();

        // Monthly chart data (last 6 months)
        $chartData = $this->buildChartData($tenantId);

        return view('partner.dashboard', compact(
            'tenant', 'clients', 'activeClients', 'trialClients',
            'currentRank', 'nextRank',
            'totalCommission', 'availableBalance', 'totalWithdrawn',
            'agencyCode', 'chartData'
        ));
    }

    private function buildChartData(int $tenantId): array
    {
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $start = $date->copy()->startOfMonth();
            $end = $date->copy()->endOfMonth();

            $newClients = Tenant::withoutGlobalScope('tenant')
                ->where('referred_by_agency_id', $tenantId)
                ->whereBetween('created_at', [$start, $end])
                ->count();

            $commission = PartnerCommission::where('tenant_id', $tenantId)
                ->whereBetween('created_at', [$start, $end])
                ->whereIn('status', ['pending', 'available', 'withdrawn'])
                ->sum('amount');

            $months[] = [
                'label'      => $date->translatedFormat('M/y'),
                'clients'    => $newClients,
                'commission' => round((float) $commission, 2),
            ];
        }

        return $months;
    }
}

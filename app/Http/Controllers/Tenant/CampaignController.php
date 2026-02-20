<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use Illuminate\View\View;

class CampaignController extends Controller
{
    public function index(): View
    {
        $campaigns = Campaign::where('status', 'active')
            ->withCount('leads')
            ->with(['adSpends' => fn ($q) => $q->orderByDesc('date')])
            ->orderBy('name')
            ->get()
            ->map(function (Campaign $campaign) {
                $spends = $campaign->adSpends;
                $totalSpend       = (float) $spends->sum('spend');
                $totalImpressions = (int)   $spends->sum('impressions');
                $totalClicks      = (int)   $spends->sum('clicks');
                $leadsCount       = $campaign->leads_count;

                return [
                    'campaign'          => $campaign,
                    'total_spend'       => $totalSpend,
                    'total_impressions' => $totalImpressions,
                    'total_clicks'      => $totalClicks,
                    'leads_count'       => $leadsCount,
                    'cost_per_lead'     => $leadsCount > 0 ? round($totalSpend / $leadsCount, 2) : null,
                    'ctr'               => $totalImpressions > 0
                        ? round($totalClicks / $totalImpressions * 100, 2)
                        : null,
                ];
            });

        return view('tenant.campaigns.index', compact('campaigns'));
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\AdSpend;
use App\Models\Campaign;
use App\Models\Lead;
use App\Models\LostSale;
use App\Models\LostSaleReason;
use App\Models\Pipeline;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        // ── Período ────────────────────────────────────────────────────────────
        $dateTo   = $request->get('date_to')
            ? Carbon::parse($request->get('date_to'))->endOfDay()
            : now()->endOfDay();

        $dateFrom = $request->get('date_from')
            ? Carbon::parse($request->get('date_from'))->startOfDay()
            : now()->subDays(29)->startOfDay();

        $days     = (int) $dateFrom->diffInDays($dateTo) + 1;
        $prevTo   = (clone $dateFrom)->subDay()->endOfDay();
        $prevFrom = (clone $prevTo)->subDays($days - 1)->startOfDay();

        // ── Filtros opcionais ──────────────────────────────────────────────────
        $filterCampaign  = $request->get('campaign_id') ?: null;
        $filterPipeline  = $request->get('pipeline_id') ?: null;
        $filterUser      = $request->get('user_id') ?: null;

        // ── Selectbox options ──────────────────────────────────────────────────
        $campaigns = Campaign::orderBy('name')->get(['id', 'name', 'platform']);
        $pipelines = Pipeline::orderBy('sort_order')->get(['id', 'name']);

        // ══════════════════════════════════════════════════════════════════════
        // 1. VISÃO GERAL
        // ══════════════════════════════════════════════════════════════════════

        $leadQuery = fn () => Lead::whereBetween('created_at', [$dateFrom, $dateTo])
            ->when($filterCampaign, fn ($q) => $q->where('campaign_id', $filterCampaign))
            ->when($filterPipeline, fn ($q) => $q->where('pipeline_id', $filterPipeline))
            ->when($filterUser,     fn ($q) => $q->where('assigned_to', $filterUser));

        $totalLeads  = $leadQuery()->count();
        $prevLeads   = Lead::whereBetween('created_at', [$prevFrom, $prevTo])
            ->when($filterCampaign, fn ($q) => $q->where('campaign_id', $filterCampaign))
            ->when($filterPipeline, fn ($q) => $q->where('pipeline_id', $filterPipeline))
            ->when($filterUser,     fn ($q) => $q->where('assigned_to', $filterUser))
            ->count();

        $saleQuery = fn () => Sale::whereBetween('closed_at', [$dateFrom, $dateTo])
            ->when($filterCampaign, fn ($q) => $q->where('campaign_id', $filterCampaign))
            ->when($filterPipeline, fn ($q) => $q->where('pipeline_id', $filterPipeline))
            ->when($filterUser,     fn ($q) => $q->where('closed_by', $filterUser));

        $salesCount   = $saleQuery()->count();
        $totalRevenue = (float) ($saleQuery()->sum('value') ?? 0);
        $avgTicket    = $salesCount > 0 ? $totalRevenue / $salesCount : 0;
        $convRate     = $totalLeads > 0 ? round($salesCount / $totalLeads * 100, 1) : 0;

        $prevRevenue  = (float) (Sale::whereBetween('closed_at', [$prevFrom, $prevTo])
            ->when($filterCampaign, fn ($q) => $q->where('campaign_id', $filterCampaign))
            ->when($filterPipeline, fn ($q) => $q->where('pipeline_id', $filterPipeline))
            ->when($filterUser,     fn ($q) => $q->where('closed_by', $filterUser))
            ->sum('value') ?? 0);

        // Δ% comparativo
        $deltaLeads   = $prevLeads   > 0 ? round(($totalLeads - $prevLeads)   / $prevLeads * 100, 1) : null;
        $deltaRevenue = $prevRevenue > 0 ? round(($totalRevenue - $prevRevenue) / $prevRevenue * 100, 1) : null;

        // Gráfico: leads por dia
        $leadsByDay = $leadQuery()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date');

        // Preenche dias sem leads com 0
        $chartDates = [];
        $chartLeads = [];
        for ($d = clone $dateFrom; $d->lte($dateTo); $d->addDay()) {
            $key = $d->format('Y-m-d');
            $chartDates[] = $d->format('d/m');
            $chartLeads[] = $leadsByDay->get($key, 0);
        }

        // Gráfico: leads por origem
        $leadsBySource = $leadQuery()
            ->selectRaw('COALESCE(source, "manual") as source, COUNT(*) as total')
            ->groupBy('source')
            ->orderByDesc('total')
            ->get();

        // ══════════════════════════════════════════════════════════════════════
        // 2. CAMPANHAS
        // ══════════════════════════════════════════════════════════════════════

        $campaignRows = Campaign::when($filterCampaign, fn ($q) => $q->where('id', $filterCampaign))
            ->get()
            ->map(function (Campaign $campaign) use ($dateFrom, $dateTo) {
                $spends = AdSpend::where('campaign_id', $campaign->id)
                    ->whereBetween('date', [$dateFrom->toDateString(), $dateTo->toDateString()])
                    ->get();

                $spend       = (float) $spends->sum('spend');
                $impressions = (int)   $spends->sum('impressions');
                $clicks      = (int)   $spends->sum('clicks');
                $leadsCount  = Lead::where('campaign_id', $campaign->id)
                    ->whereBetween('created_at', [$dateFrom, $dateTo])->count();
                $revenue     = (float) (Sale::where('campaign_id', $campaign->id)
                    ->whereBetween('closed_at', [$dateFrom, $dateTo])->sum('value') ?? 0);

                return [
                    'campaign'     => $campaign,
                    'spend'        => $spend,
                    'impressions'  => $impressions,
                    'clicks'       => $clicks,
                    'ctr'          => $impressions > 0 ? round($clicks / $impressions * 100, 2) : null,
                    'leads_count'  => $leadsCount,
                    'cost_per_lead'=> $leadsCount > 0 ? round($spend / $leadsCount, 2) : null,
                    'revenue'      => $revenue,
                    'roi'          => $spend > 0 ? round(($revenue - $spend) / $spend * 100, 1) : null,
                ];
            })
            ->filter(fn ($row) => $row['spend'] > 0 || $row['leads_count'] > 0)
            ->sortByDesc('revenue')
            ->values();

        // ══════════════════════════════════════════════════════════════════════
        // 3. PIPELINE / FUNIL
        // ══════════════════════════════════════════════════════════════════════

        $pipelineRows = Pipeline::when($filterPipeline, fn ($q) => $q->where('id', $filterPipeline))
            ->with(['stages' => fn ($q) => $q->orderBy('position')])
            ->orderBy('sort_order')
            ->get()
            ->map(function (Pipeline $pipeline) use ($dateFrom, $dateTo) {
                $stagesData = $pipeline->stages->map(function ($stage) use ($dateFrom, $dateTo) {
                    return [
                        'stage' => $stage,
                        'count' => Lead::where('stage_id', $stage->id)
                            ->whereBetween('created_at', [$dateFrom, $dateTo])
                            ->count(),
                    ];
                });

                $totalInPipeline = $stagesData->sum('count');

                return [
                    'pipeline'  => $pipeline,
                    'stages'    => $stagesData,
                    'total'     => $totalInPipeline,
                ];
            });

        // ══════════════════════════════════════════════════════════════════════
        // 4. LEADS PERDIDOS
        // ══════════════════════════════════════════════════════════════════════

        $lostQuery = fn () => LostSale::whereBetween('lost_at', [$dateFrom, $dateTo])
            ->when($filterCampaign, fn ($q) => $q->where('campaign_id', $filterCampaign))
            ->when($filterPipeline, fn ($q) => $q->where('pipeline_id', $filterPipeline))
            ->when($filterUser,     fn ($q) => $q->where('lost_by', $filterUser));

        $totalLost = $lostQuery()->count();

        // Valor potencial perdido (soma do value do lead associado)
        $lostPotentialValue = (float) ($lostQuery()
            ->join('leads', 'lost_sales.lead_id', '=', 'leads.id')
            ->sum('leads.value') ?? 0);

        // Por motivo
        $lostByReason = $lostQuery()
            ->selectRaw('reason_id, COUNT(*) as total')
            ->groupBy('reason_id')
            ->orderByDesc('total')
            ->with('reason')
            ->get()
            ->map(fn ($row) => [
                'reason' => $row->reason?->name ?? 'Sem motivo',
                'total'  => $row->total,
                'pct'    => $totalLost > 0 ? round($row->total / $totalLost * 100, 1) : 0,
            ]);

        // Por campanha
        $lostByCampaign = $lostQuery()
            ->selectRaw('campaign_id, COUNT(*) as total')
            ->groupBy('campaign_id')
            ->orderByDesc('total')
            ->with('campaign')
            ->get()
            ->map(fn ($row) => [
                'campaign' => $row->campaign?->name ?? 'Sem campanha',
                'total'    => $row->total,
            ]);

        return view('tenant.reports.index', compact(
            // filtros aplicados
            'dateFrom', 'dateTo', 'filterCampaign', 'filterPipeline', 'filterUser',
            'campaigns', 'pipelines',
            // visão geral
            'totalLeads', 'prevLeads', 'deltaLeads',
            'salesCount', 'totalRevenue', 'prevRevenue', 'deltaRevenue',
            'avgTicket', 'convRate',
            'chartDates', 'chartLeads', 'leadsBySource',
            // campanhas
            'campaignRows',
            // funil
            'pipelineRows',
            // perdidos
            'totalLost', 'lostPotentialValue', 'lostByReason', 'lostByCampaign',
        ));
    }
}

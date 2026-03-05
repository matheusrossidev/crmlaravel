<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Lead;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CampaignController extends Controller
{
    // ── GET /campanhas — Relatórios UTM ──────────────────────────────────────
    public function index(Request $request): View
    {
        $days  = (int) $request->get('days', 30);
        $since = Carbon::now()->subDays($days)->startOfDay();
        $prevSince = Carbon::now()->subDays($days * 2)->startOfDay();

        $utmFilter = function ($q) {
            $q->whereNotNull('leads.utm_source')
              ->orWhereNotNull('leads.utm_medium')
              ->orWhereNotNull('leads.utm_campaign');
        };

        // ── UTM Breakdown (agrupamento por utm_source + utm_medium + utm_campaign)
        $utmBreakdown = Lead::query()
            ->select([
                DB::raw('COALESCE(leads.utm_source, "(direto)") as utm_source'),
                DB::raw('COALESCE(leads.utm_medium, "(direto)") as utm_medium'),
                DB::raw('COALESCE(leads.utm_campaign, "(direto)") as utm_campaign'),
                DB::raw('COUNT(DISTINCT leads.id) as leads_count'),
                DB::raw('COUNT(DISTINCT sales.id) as conversions'),
                DB::raw('COALESCE(SUM(sales.value), 0) as revenue'),
            ])
            ->leftJoin('sales', 'sales.lead_id', '=', 'leads.id')
            ->where('leads.created_at', '>=', $since)
            ->where($utmFilter)
            ->groupBy('utm_source', 'utm_medium', 'utm_campaign')
            ->orderByDesc('leads_count')
            ->limit(100)
            ->get()
            ->map(function ($row) {
                $leads = (int) $row->leads_count;
                $conv  = (int) $row->conversions;
                return [
                    'utm_source'   => $row->utm_source,
                    'utm_medium'   => $row->utm_medium,
                    'utm_campaign' => $row->utm_campaign,
                    'leads'        => $leads,
                    'conversions'  => $conv,
                    'revenue'      => (float) $row->revenue,
                    'conv_rate'    => $leads > 0 ? round($conv / $leads * 100, 1) : 0,
                ];
            });

        // ── KPIs totais (período atual)
        $totalLeads       = $utmBreakdown->sum('leads');
        $totalConversions = $utmBreakdown->sum('conversions');
        $totalRevenue     = $utmBreakdown->sum('revenue');
        $convRate         = $totalLeads > 0 ? round($totalConversions / $totalLeads * 100, 1) : 0;

        // ── KPIs período anterior (para comparação)
        $prevKpis = Lead::query()
            ->select([
                DB::raw('COUNT(DISTINCT leads.id) as leads_count'),
                DB::raw('COUNT(DISTINCT sales.id) as conversions'),
                DB::raw('COALESCE(SUM(sales.value), 0) as revenue'),
            ])
            ->leftJoin('sales', 'sales.lead_id', '=', 'leads.id')
            ->where('leads.created_at', '>=', $prevSince)
            ->where('leads.created_at', '<', $since)
            ->where($utmFilter)
            ->first();

        $prevLeads = (int) ($prevKpis->leads_count ?? 0);
        $prevConv  = (int) ($prevKpis->conversions ?? 0);
        $prevRev   = (float) ($prevKpis->revenue ?? 0);

        $deltaLeads = $prevLeads > 0 ? round(($totalLeads - $prevLeads) / $prevLeads * 100, 1) : null;
        $deltaConv  = $prevConv > 0 ? round(($totalConversions - $prevConv) / $prevConv * 100, 1) : null;
        $deltaRev   = $prevRev > 0 ? round(($totalRevenue - $prevRev) / $prevRev * 100, 1) : null;

        // ── Top Performers
        $topSource = $utmBreakdown->groupBy('utm_source')
            ->map(fn ($g) => $g->sum('leads'))
            ->sortDesc()
            ->first();
        $topSourceName = $utmBreakdown->groupBy('utm_source')
            ->map(fn ($g) => $g->sum('leads'))
            ->sortDesc()
            ->keys()
            ->first() ?? '—';

        $topMedium = $utmBreakdown->groupBy('utm_medium')
            ->map(fn ($g) => $g->sum('leads'))
            ->sortDesc()
            ->first();
        $topMediumName = $utmBreakdown->groupBy('utm_medium')
            ->map(fn ($g) => $g->sum('leads'))
            ->sortDesc()
            ->keys()
            ->first() ?? '—';

        $topCampaign = $utmBreakdown->where('leads', '>=', 3)
            ->sortByDesc('conv_rate')
            ->first();

        // ── Chart: Doughnut — distribuição por source
        $bySource = $utmBreakdown->groupBy('utm_source')
            ->map(fn ($g) => $g->sum('leads'))
            ->sortDesc()
            ->take(8);
        $doughnutLabels = $bySource->keys()->toArray();
        $doughnutData   = $bySource->values()->toArray();

        // ── Chart: leads por utm_campaign (bar)
        $byUtmCampaign = $utmBreakdown->groupBy('utm_campaign')
            ->map(fn ($g) => $g->sum('leads'))
            ->sortDesc()
            ->take(10);
        $barLabels = $byUtmCampaign->keys()->toArray();
        $barData   = $byUtmCampaign->values()->toArray();

        // ── Chart: evolução semanal (line)
        $weeks = collect();
        for ($i = 7; $i >= 0; $i--) {
            $weeks->push(Carbon::now()->subWeeks($i)->startOfWeek()->format('Y-m-d'));
        }

        $weeklyRaw = Lead::query()
            ->select([
                DB::raw("DATE_FORMAT(DATE_SUB(leads.created_at, INTERVAL (DAYOFWEEK(leads.created_at)-2+7)%7 DAY), '%Y-%m-%d') as week_start"),
                DB::raw('COUNT(*) as leads_count'),
            ])
            ->where('leads.created_at', '>=', Carbon::now()->subWeeks(8))
            ->where($utmFilter)
            ->groupBy('week_start')
            ->get()
            ->keyBy('week_start');

        $lineLabels = $weeks->map(fn ($w) => Carbon::parse($w)->format('d/m'))->toArray();
        $lineData   = $weeks->map(fn ($w) => (int) ($weeklyRaw[$w]->leads_count ?? 0))->toArray();

        // ── Max leads (para barra de progresso na tabela)
        $maxLeads = $utmBreakdown->max('leads') ?: 1;

        return view('tenant.campaigns.index', compact(
            'utmBreakdown', 'days', 'maxLeads',
            'totalLeads', 'totalConversions', 'totalRevenue', 'convRate',
            'deltaLeads', 'deltaConv', 'deltaRev',
            'topSourceName', 'topSource', 'topMediumName', 'topMedium', 'topCampaign',
            'doughnutLabels', 'doughnutData',
            'barLabels', 'barData',
            'lineLabels', 'lineData'
        ));
    }

    // ── GET /campanhas/drill-down (AJAX) ─────────────────────────────────────
    public function drillDown(Request $request): JsonResponse
    {
        $source   = $request->get('source');
        $medium   = $request->get('medium');
        $campaign = $request->get('campaign');
        $days     = (int) $request->get('days', 30);
        $since    = Carbon::now()->subDays($days)->startOfDay();

        $query = Lead::with(['stage', 'pipeline'])
            ->where('created_at', '>=', $since);

        if ($source && $source !== '(direto)') {
            $query->where('utm_source', $source);
        } elseif ($source === '(direto)') {
            $query->whereNull('utm_source');
        }
        if ($medium && $medium !== '(direto)') {
            $query->where('utm_medium', $medium);
        } elseif ($medium === '(direto)') {
            $query->whereNull('utm_medium');
        }
        if ($campaign && $campaign !== '(direto)') {
            $query->where('utm_campaign', $campaign);
        } elseif ($campaign === '(direto)') {
            $query->whereNull('utm_campaign');
        }

        $leads = $query->orderByDesc('created_at')->limit(50)->get()->map(fn (Lead $l) => [
            'id'         => $l->id,
            'name'       => $l->name,
            'email'      => $l->email,
            'phone'      => $l->phone,
            'created_at' => $l->created_at?->format('d/m/Y H:i'),
            'stage'      => $l->stage?->name,
            'pipeline'   => $l->pipeline?->name,
        ]);

        return response()->json(['leads' => $leads]);
    }

    // ── POST /campanhas ───────────────────────────────────────────────────────
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'            => 'required|string|max:500',
            'status'          => 'required|in:active,paused,archived',
            'type'            => 'required|in:manual,facebook,google',
            'campaign_type'   => 'nullable|string|max:50',
            'objective'       => 'nullable|string|max:100',
            'budget_daily'    => 'nullable|numeric|min:0',
            'budget_lifetime' => 'nullable|numeric|min:0',
            'destination_url' => 'nullable|url|max:2000',
            'utm_source'      => 'nullable|string|max:100',
            'utm_medium'      => 'nullable|string|max:100',
            'utm_campaign'    => 'nullable|string|max:200',
            'utm_term'        => 'nullable|string|max:200',
            'utm_content'     => 'nullable|string|max:200',
        ]);

        $data['tenant_id'] = Auth::user()->tenant_id;

        $campaign = Campaign::create($data);

        return response()->json(['success' => true, 'campaign' => $this->formatCampaign($campaign)], 201);
    }

    // ── PUT /campanhas/{campaign} ──────────────────────────────────────────────
    public function update(Request $request, Campaign $campaign): JsonResponse
    {
        $data = $request->validate([
            'name'            => 'required|string|max:500',
            'status'          => 'required|in:active,paused,archived',
            'type'            => 'required|in:manual,facebook,google',
            'campaign_type'   => 'nullable|string|max:50',
            'objective'       => 'nullable|string|max:100',
            'budget_daily'    => 'nullable|numeric|min:0',
            'budget_lifetime' => 'nullable|numeric|min:0',
            'destination_url' => 'nullable|url|max:2000',
            'utm_source'      => 'nullable|string|max:100',
            'utm_medium'      => 'nullable|string|max:100',
            'utm_campaign'    => 'nullable|string|max:200',
            'utm_term'        => 'nullable|string|max:200',
            'utm_content'     => 'nullable|string|max:200',
        ]);

        $campaign->update($data);

        return response()->json(['success' => true, 'campaign' => $this->formatCampaign($campaign->fresh())]);
    }

    // ── DELETE /campanhas/{campaign} ──────────────────────────────────────────
    public function destroy(Campaign $campaign): JsonResponse
    {
        $campaign->delete();

        return response()->json(['success' => true]);
    }

    // ── GET /campanhas/relatorios ─────────────────────────────────────────────
    public function reports(Request $request): View
    {
        $days      = (int) $request->get('days', 30);
        $platform  = $request->get('platform', '');
        $status    = $request->get('status', '');
        $since     = Carbon::now()->subDays($days)->startOfDay();

        // ── Ranking de campanhas ───────────────────────────────────────────────
        $rankingQuery = Campaign::query()
            ->select([
                'campaigns.*',
                DB::raw('COUNT(DISTINCT leads.id) as leads_count'),
                DB::raw('COUNT(DISTINCT sales.id) as conversions'),
                DB::raw('COALESCE(SUM(DISTINCT sales.value), 0) as revenue'),
                DB::raw('COALESCE(SUM(ad_spends.spend), 0) as total_spend'),
            ])
            ->leftJoin('leads', function ($j) use ($since) {
                $j->on('leads.campaign_id', '=', 'campaigns.id')
                  ->where('leads.created_at', '>=', $since);
            })
            ->leftJoin('sales', 'sales.campaign_id', '=', 'campaigns.id')
            ->leftJoin('ad_spends', 'ad_spends.campaign_id', '=', 'campaigns.id')
            ->groupBy('campaigns.id');

        if ($platform) {
            $rankingQuery->where('campaigns.platform', $platform)
                         ->orWhere('campaigns.type', $platform);
        }
        if ($status) {
            $rankingQuery->where('campaigns.status', $status);
        }

        $ranking = $rankingQuery->orderByDesc('leads_count')->get()->map(function ($c) {
            $leads       = (int) $c->leads_count;
            $conversions = (int) $c->conversions;
            $revenue     = (float) $c->revenue;
            $spend       = (float) $c->total_spend;

            return [
                'campaign'      => $c,
                'leads'         => $leads,
                'conversions'   => $conversions,
                'revenue'       => $revenue,
                'spend'         => $spend,
                'conv_rate'     => $leads > 0 ? round($conversions / $leads * 100, 1) : 0,
                'roi'           => $spend > 0 ? round(($revenue - $spend) / $spend * 100, 1) : null,
                'cpl'           => $leads > 0 && $spend > 0 ? round($spend / $leads, 2) : null,
            ];
        });

        // ── Breakdown por UTM ──────────────────────────────────────────────────
        $utmBreakdown = Lead::query()
            ->select([
                DB::raw('COALESCE(utm_source, "(sem source)") as utm_source'),
                DB::raw('COALESCE(utm_medium, "(sem medium)") as utm_medium'),
                DB::raw('COALESCE(utm_campaign, "(sem campaign)") as utm_campaign'),
                DB::raw('COUNT(*) as leads_count'),
            ])
            ->leftJoin('sales', 'sales.lead_id', '=', 'leads.id')
            ->where('leads.created_at', '>=', $since)
            ->whereNotNull('leads.utm_source')
            ->groupBy('utm_source', 'utm_medium', 'utm_campaign')
            ->orderByDesc('leads_count')
            ->limit(50)
            ->get();

        $utmConversions = Lead::query()
            ->select([
                DB::raw('COALESCE(utm_source, "(sem source)") as utm_source'),
                DB::raw('COALESCE(utm_medium, "(sem medium)") as utm_medium'),
                DB::raw('COALESCE(utm_campaign, "(sem campaign)") as utm_campaign'),
                DB::raw('COUNT(sales.id) as conversions'),
                DB::raw('COALESCE(SUM(sales.value), 0) as revenue'),
            ])
            ->leftJoin('sales', 'sales.lead_id', '=', 'leads.id')
            ->where('leads.created_at', '>=', $since)
            ->whereNotNull('leads.utm_source')
            ->groupBy('utm_source', 'utm_medium', 'utm_campaign')
            ->get()
            ->keyBy(fn ($r) => "{$r->utm_source}|{$r->utm_medium}|{$r->utm_campaign}");

        $utmBreakdown = $utmBreakdown->map(function ($row) use ($utmConversions) {
            $key  = "{$row->utm_source}|{$row->utm_medium}|{$row->utm_campaign}";
            $conv = $utmConversions->get($key);
            return [
                'utm_source'   => $row->utm_source,
                'utm_medium'   => $row->utm_medium,
                'utm_campaign' => $row->utm_campaign,
                'leads'        => (int) $row->leads_count,
                'conversions'  => $conv ? (int) $conv->conversions : 0,
                'revenue'      => $conv ? (float) $conv->revenue : 0,
            ];
        });

        // ── Dados para Chart.js (barras — leads por campanha) ──────────────────
        $barLabels = $ranking->pluck('campaign.name')->toArray();
        $barData   = $ranking->pluck('leads')->toArray();
        $barColors = $ranking->map(fn ($r) => match ($r['campaign']->platform ?? $r['campaign']->type) {
            'facebook' => '#1877F2',
            'google'   => '#34A853',
            default    => '#6366F1',
        })->toArray();

        // ── Dados para Chart.js (linha — evolução semanal) ────────────────────
        $weeks = collect();
        for ($i = 7; $i >= 0; $i--) {
            $weeks->push(Carbon::now()->subWeeks($i)->startOfWeek()->format('Y-m-d'));
        }

        $topCampaigns = $ranking->take(5);

        $weeklyRaw = Lead::query()
            ->select([
                'campaign_id',
                DB::raw("DATE_FORMAT(DATE_SUB(leads.created_at, INTERVAL (DAYOFWEEK(leads.created_at)-2+7)%7 DAY), '%Y-%m-%d') as week_start"),
                DB::raw('COUNT(*) as leads_count'),
            ])
            ->whereIn('campaign_id', $topCampaigns->pluck('campaign.id'))
            ->where('leads.created_at', '>=', Carbon::now()->subWeeks(8))
            ->groupBy('campaign_id', 'week_start')
            ->get()
            ->groupBy('campaign_id');

        $lineDatasets = $topCampaigns->map(function ($item) use ($weeks, $weeklyRaw) {
            $campaignId = $item['campaign']->id;
            $byWeek     = ($weeklyRaw[$campaignId] ?? collect())->keyBy('week_start');

            $colors = ['#1877F2', '#34A853', '#F59E0B', '#EF4444', '#8B5CF6'];
            static $idx = 0;
            $color = $colors[$idx++ % count($colors)];

            return [
                'label'           => $item['campaign']->name,
                'data'            => $weeks->map(fn ($w) => (int) ($byWeek[$w]->leads_count ?? 0))->toArray(),
                'borderColor'     => $color,
                'backgroundColor' => $color . '20',
                'tension'         => 0.3,
                'fill'            => true,
            ];
        })->values()->toArray();

        $lineLabels = $weeks->map(fn ($w) => Carbon::parse($w)->format('d/m'))->toArray();

        return view('tenant.campaigns.reports', compact(
            'ranking', 'utmBreakdown', 'days', 'platform', 'status',
            'barLabels', 'barData', 'barColors',
            'lineLabels', 'lineDatasets'
        ));
    }

    private function formatCampaign(Campaign $c): array
    {
        return [
            'id'              => $c->id,
            'name'            => $c->name,
            'status'          => $c->status,
            'type'            => $c->type ?? 'manual',
            'platform'        => $c->platform,
            'campaign_type'   => $c->campaign_type,
            'objective'       => $c->objective,
            'budget_daily'    => $c->budget_daily,
            'budget_lifetime' => $c->budget_lifetime,
            'destination_url' => $c->destination_url,
            'utm_source'      => $c->utm_source,
            'utm_medium'      => $c->utm_medium,
            'utm_campaign'    => $c->utm_campaign,
            'utm_term'        => $c->utm_term,
            'utm_content'     => $c->utm_content,
            'created_at'      => $c->created_at?->toISOString(),
        ];
    }
}

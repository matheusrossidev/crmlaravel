<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LostSale;
use App\Models\Pipeline;
use App\Models\Sale;
use App\Support\TenantCache;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private const AVAILABLE_CARDS = ['leads', 'vendas', 'conversao', 'ticket', 'perdidos'];

    public function saveConfig(Request $request): JsonResponse
    {
        $cards = array_values(array_intersect(
            $request->input('cards', []),
            self::AVAILABLE_CARDS
        ));
        auth()->user()->update(['dashboard_config' => ['cards' => $cards]]);
        return response()->json(['success' => true]);
    }

    public function leadsChart(Request $request): JsonResponse
    {
        $period = $request->get('period', 'month');

        $now = Carbon::now();

        [$since, $groupExpr, $labelFn] = match ($period) {
            'week'    => [
                $now->copy()->startOfWeek(),
                'DATE(created_at)',
                fn ($row) => Carbon::parse($row->period)->translatedFormat('D d'),
            ],
            '3months' => [
                $now->copy()->subMonths(2)->startOfMonth(),
                "DATE_FORMAT(created_at, '%Y-%m-01')",
                fn ($row) => ucfirst(Carbon::parse($row->period)->translatedFormat('M/y')),
            ],
            '6months' => [
                $now->copy()->subMonths(5)->startOfMonth(),
                "DATE_FORMAT(created_at, '%Y-%m-01')",
                fn ($row) => ucfirst(Carbon::parse($row->period)->translatedFormat('M/y')),
            ],
            default   => [ // month
                $now->copy()->startOfMonth(),
                'DAY(created_at)',
                fn ($row) => (string) $row->period,
            ],
        };

        $allowedPipelineIds = auth()->user()->allowedPipelineIds();

        $raw = Lead::where('exclude_from_pipeline', false)
            ->when($allowedPipelineIds, fn ($q) => $q->whereIn('pipeline_id', $allowedPipelineIds))
            ->selectRaw("{$groupExpr} as period, source, COUNT(*) as total")
            ->where('created_at', '>=', $since)
            ->groupBy('period', 'source')
            ->get();

        // Build ordered labels
        if ($period === 'month') {
            $labels = range(1, $now->daysInMonth);
        } elseif ($period === 'week') {
            $start  = $now->copy()->startOfWeek();
            $labels = [];
            for ($d = $start->copy(); $d->lte($now); $d->addDay()) {
                $labels[] = $d->toDateString();
            }
        } else {
            $months = $period === '3months' ? 2 : 5;
            $labels = [];
            for ($i = $months; $i >= 0; $i--) {
                $labels[] = $now->copy()->subMonths($i)->startOfMonth()->toDateString();
            }
        }

        $sources  = $raw->pluck('source')->unique()->filter()->values();
        $datasets = [];

        foreach ($sources as $src) {
            $data = [];
            foreach ($labels as $lbl) {
                $data[] = (int) $raw->where('source', $src)->where('period', $lbl)->sum('total');
            }
            $datasets[(string) $src] = $data;
        }

        // Format labels for display
        $displayLabels = collect($labels)->map(function ($lbl) use ($period, $now) {
            if ($period === 'month') {
                return (string) $lbl;
            }
            if ($period === 'week') {
                return ucfirst(Carbon::parse($lbl)->translatedFormat('D d'));
            }
            return ucfirst(Carbon::parse($lbl)->translatedFormat('M/y'));
        })->all();

        $total = $raw->sum('total');

        return response()->json([
            'labels'   => $displayLabels,
            'datasets' => $datasets,
            'total'    => $total,
        ]);
    }

    public function index(Request $request): View
    {
        $dashConfig   = auth()->user()->dashboard_config ?? [];
        $visibleCards = $dashConfig['cards'] ?? self::AVAILABLE_CARDS;
        $visibleCards = array_values(array_intersect($visibleCards, self::AVAILABLE_CARDS));

        $data                 = $this->buildDashboardData();
        $data['visibleCards'] = $visibleCards;

        return view('tenant.dashboard', $data);
    }

    private function buildDashboardData(): array
    {
        $allowedPipelineIds = auth()->user()->allowedPipelineIds();
        $tenantId           = activeTenantId();

        // ── Compact number formatting helper ─────────────────────────────────
        $cfFmt = static function (float $v, string $pre = '', string $suf = ''): string {
            if ($v >= 1_000_000) return $pre . number_format($v / 1_000_000, 1, ',', '.') . 'M' . $suf;
            if ($v >= 1_000)     return $pre . number_format($v / 1_000,     1, ',', '.') . 'K' . $suf;
            return $pre . number_format($v, 0, ',', '.') . $suf;
        };

        // ── Stat cards (cached 5 min) ────────────────────────────────────────
        $stats = TenantCache::remember('dashboard:stats', 300, function () use ($allowedPipelineIds) {
            $leadsThisMonth = Lead::where('exclude_from_pipeline', false)
                ->when($allowedPipelineIds, fn ($q) => $q->whereIn('pipeline_id', $allowedPipelineIds))
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();

            $leadsLastMonth = Lead::where('exclude_from_pipeline', false)
                ->when($allowedPipelineIds, fn ($q) => $q->whereIn('pipeline_id', $allowedPipelineIds))
                ->whereMonth('created_at', now()->subMonth()->month)
                ->whereYear('created_at', now()->subMonth()->year)
                ->count();

            $totalSales = (float) Sale::whereMonth('closed_at', now()->month)
                ->whereYear('closed_at', now()->year)->sum('value');
            $salesLastMonth = (float) Sale::whereMonth('closed_at', now()->subMonth()->month)
                ->whereYear('closed_at', now()->subMonth()->year)->sum('value');
            $leadsGanhos = Sale::whereMonth('closed_at', now()->month)
                ->whereYear('closed_at', now()->year)->count();
            $leadsPerdidos = LostSale::whereMonth('lost_at', now()->month)
                ->whereYear('lost_at', now()->year)->count();
            $totalLeads = Lead::where('exclude_from_pipeline', false)
                ->when($allowedPipelineIds, fn ($q) => $q->whereIn('pipeline_id', $allowedPipelineIds))->count();
            $wonTotal = Sale::count();

            return compact('leadsThisMonth', 'leadsLastMonth', 'totalSales', 'salesLastMonth',
                'leadsGanhos', 'leadsPerdidos', 'totalLeads', 'wonTotal');
        });

        $leadsThisMonth = $stats['leadsThisMonth'];
        $totalSales     = $stats['totalSales'];
        $leadsGanhos    = $stats['leadsGanhos'];
        $leadsPerdidos  = $stats['leadsPerdidos'];
        $ticketMedio    = $leadsGanhos > 0 ? $totalSales / $leadsGanhos : 0;
        $conversionRate = $stats['totalLeads'] > 0 ? round($stats['wonTotal'] / $stats['totalLeads'] * 100, 1) : 0;
        $leadsTrend     = $stats['leadsLastMonth'] > 0
            ? (int) round(($leadsThisMonth - $stats['leadsLastMonth']) / $stats['leadsLastMonth'] * 100) : null;
        $salesTrend     = $stats['salesLastMonth'] > 0
            ? (int) round(($totalSales - $stats['salesLastMonth']) / $stats['salesLastMonth'] * 100) : null;

        $cfLeads    = $cfFmt((float) $leadsThisMonth);
        $cfSales    = $cfFmt((float) $totalSales, 'R$ ');
        $cfTicket   = $cfFmt((float) $ticketMedio, 'R$ ');
        $cfPerdidos = $cfFmt((float) $leadsPerdidos);

        // ── Lost by reason (cached 6h) ───────────────────────────────────────
        $lostByReason = TenantCache::remember('dashboard:lostReasons', 21600, function () use ($tenantId) {
            return DB::table('lost_sales')
                ->select(DB::raw('lost_sale_reasons.name as reason_name'), DB::raw('count(*) as total'))
                ->leftJoin('lost_sale_reasons', 'lost_sales.reason_id', '=', 'lost_sale_reasons.id')
                ->where('lost_sales.tenant_id', $tenantId)
                ->groupBy('lost_sales.reason_id', 'lost_sale_reasons.name')
                ->orderByDesc('total')->limit(8)->get()
                ->map(fn ($r) => ['name' => $r->reason_name ?? 'Sem motivo', 'total' => (int) $r->total])
                ->toArray();
        });

        // ── 6 months chart (cached 1h) — FIX N+1: single GROUP BY ────────────
        $monthlyData = TenantCache::remember('dashboard:monthly', 3600, function () use ($allowedPipelineIds) {
            $since = now()->subMonths(5)->startOfMonth();

            $leads = Lead::where('exclude_from_pipeline', false)
                ->when($allowedPipelineIds, fn ($q) => $q->whereIn('pipeline_id', $allowedPipelineIds))
                ->where('created_at', '>=', $since)
                ->selectRaw('YEAR(created_at) as y, MONTH(created_at) as m, COUNT(*) as total')
                ->groupByRaw('YEAR(created_at), MONTH(created_at)')
                ->get()->mapWithKeys(fn ($r) => ["{$r->y}-{$r->m}" => (int) $r->total]);

            $sales = Sale::where('closed_at', '>=', $since)
                ->selectRaw('YEAR(closed_at) as y, MONTH(closed_at) as m, SUM(value) as total')
                ->groupByRaw('YEAR(closed_at), MONTH(closed_at)')
                ->get()->mapWithKeys(fn ($r) => ["{$r->y}-{$r->m}" => (float) $r->total]);

            return ['leads' => $leads, 'sales' => $sales];
        });

        $monthLabels = $leadsPerMonth = $salesPerMonth = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = now()->copy()->subMonths($i);
            $monthLabels[]   = ucfirst($m->translatedFormat('M/y'));
            $key             = "{$m->year}-{$m->month}";
            $leadsPerMonth[] = $monthlyData['leads'][$key] ?? 0;
            $salesPerMonth[] = $monthlyData['sales'][$key] ?? 0;
        }

        // ── Leads per day by source (cached 5 min) ───────────────────────────
        $daySourceData = TenantCache::remember('dashboard:daySource', 300, function () use ($allowedPipelineIds) {
            return Lead::where('exclude_from_pipeline', false)
                ->when($allowedPipelineIds, fn ($q) => $q->whereIn('pipeline_id', $allowedPipelineIds))
                ->selectRaw('DAY(created_at) as day, source, COUNT(*) as total')
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->groupBy('day', 'source')->get();
        });

        $daysInMonth = now()->daysInMonth;
        $dayLabels   = range(1, $daysInMonth);
        $srcKeysDay  = $daySourceData->pluck('source')->unique()->filter()->values();
        $leadsPerDayBySource = [];
        foreach ($srcKeysDay as $src) {
            $leadsPerDayBySource[(string) $src] = collect($dayLabels)->map(
                fn($day) => (int) $daySourceData->where('source', $src)->where('day', $day)->sum('total')
            )->values()->all();
        }
        $leadsPerDay = collect($dayLabels)->map(
            fn($day) => (int) $daySourceData->where('day', $day)->sum('total')
        )->values()->all();

        // ── Top sources (cached 2h) ──────────────────────────────────────────
        $leadsBySource = TenantCache::remember('dashboard:sources', 7200, function () use ($allowedPipelineIds) {
            return Lead::where('exclude_from_pipeline', false)
                ->when($allowedPipelineIds, fn ($q) => $q->whereIn('pipeline_id', $allowedPipelineIds))
                ->selectRaw('source, count(*) as total')
                ->whereNotNull('source')->where('source', '!=', '')
                ->groupBy('source')->orderByDesc('total')->limit(6)
                ->pluck('total', 'source')->toArray();
        });

        // ── Pipeline funnel (cached 5 min) — FIX N+1: single GROUP BY ────────
        $pipeline = Pipeline::where('is_default', true)->with('stages')
            ->when($allowedPipelineIds, fn ($q) => $q->whereIn('id', $allowedPipelineIds))
            ->first()
            ?? Pipeline::with('stages')
                ->when($allowedPipelineIds, fn ($q) => $q->whereIn('id', $allowedPipelineIds))
                ->first();

        $stagesWithCount = [];
        if ($pipeline) {
            $stageCounts = TenantCache::remember('dashboard:stages', 300, function () use ($pipeline) {
                return Lead::where('exclude_from_pipeline', false)
                    ->whereIn('stage_id', $pipeline->stages->pluck('id'))
                    ->selectRaw('stage_id, COUNT(*) as total')
                    ->groupBy('stage_id')
                    ->pluck('total', 'stage_id');
            });

            foreach ($pipeline->stages as $stage) {
                $stagesWithCount[] = [
                    'name'  => $stage->name,
                    'count' => $stageCounts[$stage->id] ?? 0,
                    'color' => $stage->color,
                ];
            }
        }

        $maxStageCount = collect($stagesWithCount)->max('count') ?: 1;

        return compact(
            'leadsThisMonth', 'leadsTrend', 'totalSales', 'salesTrend',
            'leadsGanhos', 'leadsPerdidos', 'ticketMedio', 'conversionRate',
            'cfLeads', 'cfSales', 'cfTicket', 'cfPerdidos',
            'lostByReason', 'monthLabels', 'leadsPerMonth', 'salesPerMonth',
            'leadsBySource', 'dayLabels', 'leadsPerDay', 'leadsPerDayBySource',
            'stagesWithCount', 'pipeline', 'maxStageCount',
        );
    }
}

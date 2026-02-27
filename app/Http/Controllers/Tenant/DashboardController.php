<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LostSale;
use App\Models\Pipeline;
use App\Models\Sale;
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

    public function index(Request $request): View
    {
        // ── Card visibility config ─────────────────────────────────────────
        $dashConfig   = auth()->user()->dashboard_config ?? [];
        $visibleCards = $dashConfig['cards'] ?? self::AVAILABLE_CARDS;
        $visibleCards = array_values(array_intersect($visibleCards, self::AVAILABLE_CARDS));
        // ── Métricas principais ────────────────────────────────────────────
        $leadsThisMonth = Lead::where('exclude_from_pipeline', false)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $leadsLastMonth = Lead::where('exclude_from_pipeline', false)
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();

        $leadsTrend = $leadsLastMonth > 0
            ? (int) round(($leadsThisMonth - $leadsLastMonth) / $leadsLastMonth * 100)
            : null;

        $totalSales = (float) Sale::whereMonth('closed_at', now()->month)
            ->whereYear('closed_at', now()->year)
            ->sum('value');

        $salesLastMonth = (float) Sale::whereMonth('closed_at', now()->subMonth()->month)
            ->whereYear('closed_at', now()->subMonth()->year)
            ->sum('value');

        $salesTrend = $salesLastMonth > 0
            ? (int) round(($totalSales - $salesLastMonth) / $salesLastMonth * 100)
            : null;

        $leadsGanhos = Sale::whereMonth('closed_at', now()->month)
            ->whereYear('closed_at', now()->year)
            ->count();

        $ticketMedio = $leadsGanhos > 0 ? $totalSales / $leadsGanhos : 0;

        // Leads perdidos este mês
        $leadsPerdidos = LostSale::whereMonth('lost_at', now()->month)
            ->whereYear('lost_at', now()->year)
            ->count();

        // Taxa de conversão geral (vendas totais / leads totais)
        $totalLeads     = Lead::where('exclude_from_pipeline', false)->count();
        $wonTotal       = Sale::count();
        $conversionRate = $totalLeads > 0 ? round($wonTotal / $totalLeads * 100, 1) : 0;

        // ── Compact number formatting ──────────────────────────────────────
        $cfFmt = static function (float $v, string $pre = '', string $suf = ''): string {
            if ($v >= 1_000_000) return $pre . number_format($v / 1_000_000, 1, ',', '.') . 'M' . $suf;
            if ($v >= 1_000)     return $pre . number_format($v / 1_000,     1, ',', '.') . 'K' . $suf;
            return $pre . number_format($v, 0, ',', '.') . $suf;
        };
        $cfLeads    = $cfFmt((float) $leadsThisMonth);
        $cfSales    = $cfFmt((float) $totalSales, 'R$ ');
        $cfTicket   = $cfFmt((float) $ticketMedio, 'R$ ');
        $cfPerdidos = $cfFmt((float) $leadsPerdidos);

        // Motivos de perda (todos os tempos, top 8)
        $tenantId     = auth()->user()->tenant_id;
        $lostByReason = DB::table('lost_sales')
            ->select(DB::raw('lost_sale_reasons.name as reason_name'), DB::raw('count(*) as total'))
            ->leftJoin('lost_sale_reasons', 'lost_sales.reason_id', '=', 'lost_sale_reasons.id')
            ->where('lost_sales.tenant_id', $tenantId)
            ->groupBy('lost_sales.reason_id', 'lost_sale_reasons.name')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(fn ($r) => [
                'name'  => $r->reason_name ?? 'Sem motivo',
                'total' => (int) $r->total,
            ])
            ->toArray();

        // ── Gráficos: últimos 6 meses ──────────────────────────────────────
        $monthLabels   = [];
        $leadsPerMonth = [];
        $salesPerMonth = [];

        for ($i = 5; $i >= 0; $i--) {
            $m             = now()->copy()->subMonths($i);
            $monthLabels[] = ucfirst($m->translatedFormat('M/y'));

            $leadsPerMonth[] = Lead::where('exclude_from_pipeline', false)
                ->whereYear('created_at', $m->year)
                ->whereMonth('created_at', $m->month)
                ->count();

            $salesPerMonth[] = (float) Sale::whereYear('closed_at', $m->year)
                ->whereMonth('closed_at', $m->month)
                ->sum('value');
        }

        // ── Gráfico de leads: dias do mês atual por origem ────────────────
        $daysInMonth = now()->daysInMonth;
        $dayLabels   = range(1, $daysInMonth);

        $rawBySourceDay = Lead::where('exclude_from_pipeline', false)
            ->selectRaw('DAY(created_at) as day, source, COUNT(*) as total')
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->groupBy('day', 'source')
            ->get();

        $srcKeysDay = $rawBySourceDay->pluck('source')->unique()->filter()->values();
        $leadsPerDayBySource = [];
        foreach ($srcKeysDay as $src) {
            $leadsPerDayBySource[(string) $src] = collect($dayLabels)->map(
                fn($day) => (int) $rawBySourceDay->where('source', $src)->where('day', $day)->sum('total')
            )->values()->all();
        }

        $leadsPerDay = collect($dayLabels)->map(
            fn($day) => (int) $rawBySourceDay->where('day', $day)->sum('total')
        )->values()->all();

        // ── Leads por origem (top 6) ───────────────────────────────────────
        $leadsBySource = Lead::where('exclude_from_pipeline', false)
            ->selectRaw('source, count(*) as total')
            ->whereNotNull('source')
            ->where('source', '!=', '')
            ->groupBy('source')
            ->orderByDesc('total')
            ->limit(6)
            ->pluck('total', 'source')
            ->toArray();

        // ── Funil do pipeline padrão ───────────────────────────────────────
        $pipeline = Pipeline::where('is_default', true)->with('stages')->first()
            ?? Pipeline::with('stages')->first();

        $stagesWithCount = [];
        if ($pipeline) {
            foreach ($pipeline->stages as $stage) {
                $stagesWithCount[] = [
                    'name'  => $stage->name,
                    'count' => Lead::where('exclude_from_pipeline', false)->where('stage_id', $stage->id)->count(),
                    'color' => $stage->color,
                ];
            }
        }

        $maxStageCount = collect($stagesWithCount)->max('count') ?: 1;

        return view('tenant.dashboard', compact(
            'leadsThisMonth',
            'leadsTrend',
            'totalSales',
            'salesTrend',
            'leadsGanhos',
            'leadsPerdidos',
            'ticketMedio',
            'conversionRate',
            'cfLeads',
            'cfSales',
            'cfTicket',
            'cfPerdidos',
            'lostByReason',
            'monthLabels',
            'leadsPerMonth',
            'salesPerMonth',
            'leadsBySource',
            'dayLabels',
            'leadsPerDay',
            'leadsPerDayBySource',
            'stagesWithCount',
            'pipeline',
            'maxStageCount',
            'visibleCards',
        ));
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LostSale;
use App\Models\Pipeline;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        // ── Métricas principais ────────────────────────────────────────────
        $leadsThisMonth = Lead::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $leadsLastMonth = Lead::whereMonth('created_at', now()->subMonth()->month)
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
        $totalLeads     = Lead::count();
        $wonTotal       = Sale::count();
        $conversionRate = $totalLeads > 0 ? round($wonTotal / $totalLeads * 100, 1) : 0;

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

            $leadsPerMonth[] = Lead::whereYear('created_at', $m->year)
                ->whereMonth('created_at', $m->month)
                ->count();

            $salesPerMonth[] = (float) Sale::whereYear('closed_at', $m->year)
                ->whereMonth('closed_at', $m->month)
                ->sum('value');
        }

        // ── Leads por origem (top 6) ───────────────────────────────────────
        $leadsBySource = Lead::selectRaw('source, count(*) as total')
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
                    'count' => Lead::where('stage_id', $stage->id)->count(),
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
            'lostByReason',
            'monthLabels',
            'leadsPerMonth',
            'salesPerMonth',
            'leadsBySource',
            'stagesWithCount',
            'pipeline',
            'maxStageCount',
        ));
    }
}

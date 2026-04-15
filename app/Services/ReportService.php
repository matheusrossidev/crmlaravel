<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadEvent;
use App\Models\LostSale;
use App\Models\Pipeline;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Models\WhatsappButton;
use App\Models\WhatsappButtonClick;
use App\Models\WhatsappConversation;
use App\Models\WhatsappMessage;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Gera o payload completo do relatório (/relatorios).
 *
 * Usado por:
 *   - ReportController::index (HTML)
 *   - ReportController::exportPdf (PDF)
 *
 * Single source of truth pra garantir que HTML e PDF nunca divergem.
 *
 * Recebe filtros por request via array associativo:
 *   - date_from, date_to, pipeline_id, user_id
 */
class ReportService
{
    public function generate(array $filters = []): array
    {
        // ── Período ────────────────────────────────────────────────────────────
        $dateTo   = ! empty($filters['date_to'])
            ? Carbon::parse($filters['date_to'])->endOfDay()
            : now()->endOfDay();

        $dateFrom = ! empty($filters['date_from'])
            ? Carbon::parse($filters['date_from'])->startOfDay()
            : now()->subDays(29)->startOfDay();

        $days     = (int) $dateFrom->diffInDays($dateTo) + 1;
        $prevTo   = (clone $dateFrom)->subDay()->endOfDay();
        $prevFrom = (clone $prevTo)->subDays($days - 1)->startOfDay();

        $filterPipeline = $filters['pipeline_id'] ?? null;
        $filterUser     = $filters['user_id'] ?? null;

        $pipelines = Pipeline::orderBy('sort_order')->get(['id', 'name']);

        // ── 1. VISÃO GERAL ─────────────────────────────────────────────────────
        $leadQuery = fn () => Lead::where('exclude_from_pipeline', false)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->when($filterPipeline, fn ($q) => $q->where('pipeline_id', $filterPipeline))
            ->when($filterUser,     fn ($q) => $q->where('assigned_to', $filterUser));

        $totalLeads = $leadQuery()->count();
        $prevLeads  = Lead::where('exclude_from_pipeline', false)
            ->whereBetween('created_at', [$prevFrom, $prevTo])
            ->when($filterPipeline, fn ($q) => $q->where('pipeline_id', $filterPipeline))
            ->when($filterUser,     fn ($q) => $q->where('assigned_to', $filterUser))
            ->count();

        $saleQuery = fn () => Sale::whereBetween('closed_at', [$dateFrom, $dateTo])
            ->when($filterPipeline, fn ($q) => $q->where('pipeline_id', $filterPipeline))
            ->when($filterUser,     fn ($q) => $q->where('closed_by', $filterUser));

        $salesCount   = $saleQuery()->count();
        $totalRevenue = (float) ($saleQuery()->sum('value') ?? 0);
        $avgTicket    = $salesCount > 0 ? $totalRevenue / $salesCount : 0;
        $convRate     = $totalLeads > 0 ? round($salesCount / $totalLeads * 100, 1) : 0;

        $prevRevenue  = (float) (Sale::whereBetween('closed_at', [$prevFrom, $prevTo])
            ->when($filterPipeline, fn ($q) => $q->where('pipeline_id', $filterPipeline))
            ->when($filterUser,     fn ($q) => $q->where('closed_by', $filterUser))
            ->sum('value') ?? 0);

        $deltaLeads   = $prevLeads   > 0 ? round(($totalLeads - $prevLeads)   / $prevLeads * 100, 1) : null;
        $deltaRevenue = $prevRevenue > 0 ? round(($totalRevenue - $prevRevenue) / $prevRevenue * 100, 1) : null;

        // Gráfico: leads por dia (preenche dias sem leads com 0)
        $leadsByDay = $leadQuery()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date');

        $chartDates = [];
        $chartLeads = [];
        for ($d = clone $dateFrom; $d->lte($dateTo); $d->addDay()) {
            $key = $d->format('Y-m-d');
            $chartDates[] = $d->format('d/m');
            $chartLeads[] = $leadsByDay->get($key, 0);
        }

        $leadsBySource = $leadQuery()
            ->selectRaw('COALESCE(source, "manual") as source, COUNT(*) as total')
            ->groupBy('source')
            ->orderByDesc('total')
            ->get();

        // ── 2. CAMPANHAS (UTM) ─────────────────────────────────────────────────
        $campaignRows = Lead::where('exclude_from_pipeline', false)
            ->whereBetween('leads.created_at', [$dateFrom, $dateTo])
            ->whereNotNull('leads.utm_campaign')
            ->when($filterPipeline, fn ($q) => $q->where('leads.pipeline_id', $filterPipeline))
            ->when($filterUser, fn ($q) => $q->where('leads.assigned_to', $filterUser))
            ->select([
                'leads.utm_campaign',
                DB::raw('COALESCE(leads.utm_source, "(direto)") as utm_source'),
                DB::raw('COUNT(DISTINCT leads.id) as leads_count'),
                DB::raw('COUNT(DISTINCT sales.id) as sales_count'),
                DB::raw('COALESCE(SUM(sales.value), 0) as revenue'),
            ])
            ->leftJoin('sales', 'sales.lead_id', '=', 'leads.id')
            ->groupBy('leads.utm_campaign', DB::raw('COALESCE(leads.utm_source, "(direto)")'))
            ->orderByDesc('leads_count')
            ->get()
            ->groupBy('utm_campaign')
            ->map(function ($rows, $campaignName) {
                $leadsCount = (int) $rows->sum('leads_count');
                $salesCount = (int) $rows->sum('sales_count');
                $revenue    = (float) $rows->sum('revenue');
                $source     = $rows->first()->utm_source ?? '—';
                return [
                    'name'          => $campaignName,
                    'source'        => $source,
                    'leads_count'   => $leadsCount,
                    'sales_count'   => $salesCount,
                    'revenue'       => $revenue,
                    'conv'          => $leadsCount > 0 ? round($salesCount / $leadsCount * 100, 1) : 0,
                ];
            })
            ->sortByDesc('leads_count')
            ->values();

        // ── 3. PIPELINE / FUNIL ────────────────────────────────────────────────
        $pipelinesRaw = Pipeline::when($filterPipeline, fn ($q) => $q->where('id', $filterPipeline))
            ->with(['stages' => fn ($q) => $q->orderBy('position')])
            ->orderBy('sort_order')->get();

        $allStageIds = $pipelinesRaw->flatMap(fn ($p) => $p->stages->pluck('id'))->unique();

        $stageCounts = $allStageIds->isNotEmpty()
            ? Lead::where('exclude_from_pipeline', false)
                ->whereIn('stage_id', $allStageIds)
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->selectRaw('stage_id, COUNT(*) as total')
                ->groupBy('stage_id')
                ->pluck('total', 'stage_id')
            : collect();

        $stageAvgDays = $allStageIds->isNotEmpty()
            ? Lead::where('exclude_from_pipeline', false)
                ->whereIn('stage_id', $allStageIds)
                ->selectRaw('stage_id, AVG(DATEDIFF(NOW(), updated_at)) as avg_d')
                ->groupBy('stage_id')
                ->pluck('avg_d', 'stage_id')
            : collect();

        $pipelineRows = $pipelinesRaw->map(function (Pipeline $pipeline) use ($stageCounts, $stageAvgDays) {
            $stagesData = $pipeline->stages->map(fn ($stage) => [
                'stage'    => $stage,
                'count'    => (int) ($stageCounts[$stage->id] ?? 0),
                'avg_days' => isset($stageAvgDays[$stage->id]) ? (int) round((float) $stageAvgDays[$stage->id]) : null,
            ]);

            $normalStages = $stagesData->filter(fn ($s) => ! $s['stage']->is_won && ! $s['stage']->is_lost);
            $normalCount  = max($normalStages->count(), 1);
            $normalIdx    = 0;
            $stagesData   = $stagesData->map(function ($s) use (&$normalIdx, $normalCount) {
                if ($s['stage']->is_won || $s['stage']->is_lost) {
                    $s['bar_width'] = 28;
                } else {
                    $s['bar_width'] = $normalCount > 1
                        ? (int) round(100 - (68 * $normalIdx / ($normalCount - 1)))
                        : 100;
                    $normalIdx++;
                }
                return $s;
            });

            return ['pipeline' => $pipeline, 'stages' => $stagesData, 'total' => $stagesData->sum('count')];
        });

        // ── 4. LEADS PERDIDOS ──────────────────────────────────────────────────
        $lostQuery = fn () => LostSale::whereBetween('lost_at', [$dateFrom, $dateTo])
            ->when($filterPipeline, fn ($q) => $q->where('pipeline_id', $filterPipeline))
            ->when($filterUser,     fn ($q) => $q->where('lost_by', $filterUser));

        $totalLost = $lostQuery()->count();

        $lostPotentialValue = (float) ($lostQuery()
            ->join('leads', 'lost_sales.lead_id', '=', 'leads.id')
            ->sum('leads.value') ?? 0);

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

        $lostByVendedor = $lostQuery()
            ->selectRaw('lost_by, COUNT(*) as total')
            ->groupBy('lost_by')
            ->orderByDesc('total')
            ->with('lostBy')
            ->get()
            ->map(fn ($row) => [
                'user'  => $row->lostBy?->name ?? 'Sem usuário',
                'total' => $row->total,
                'pct'   => $totalLost > 0 ? round($row->total / $totalLost * 100, 1) : 0,
            ]);

        // ── 5. VENDEDORES ──────────────────────────────────────────────────────
        $tenantId = activeTenantId();
        $allUsers = User::where('tenant_id', $tenantId)->orderBy('name')->get();

        $vendorLeadCounts = Lead::whereBetween('created_at', [$dateFrom, $dateTo])
            ->when($filterPipeline, fn ($q) => $q->where('pipeline_id', $filterPipeline))
            ->whereNotNull('assigned_to')
            ->selectRaw('assigned_to, COUNT(*) as total')
            ->groupBy('assigned_to')
            ->pluck('total', 'assigned_to');

        $vendorSaleData = Sale::whereBetween('closed_at', [$dateFrom, $dateTo])
            ->when($filterPipeline, fn ($q) => $q->where('pipeline_id', $filterPipeline))
            ->whereNotNull('closed_by')
            ->selectRaw('closed_by, COUNT(*) as cnt, SUM(value) as revenue')
            ->groupBy('closed_by')
            ->get()->keyBy('closed_by');

        $vendedores = $allUsers->map(function (User $user) use ($vendorLeadCounts, $vendorSaleData) {
            $leads   = (int) ($vendorLeadCounts[$user->id] ?? 0);
            $saleRow = $vendorSaleData[$user->id] ?? null;
            $vendas  = $saleRow ? (int) $saleRow->cnt : 0;
            $receita = $saleRow ? (float) $saleRow->revenue : 0;
            return [
                'user'    => $user,
                'leads'   => $leads,
                'vendas'  => $vendas,
                'receita' => $receita,
                'conv'    => $leads > 0 ? round($vendas / $leads * 100, 1) : 0,
            ];
        })->filter(fn ($r) => $r['leads'] > 0 || $r['vendas'] > 0)
          ->sortByDesc('receita')->values();

        // ── 6. WHATSAPP ────────────────────────────────────────────────────────
        $waTotal    = WhatsappConversation::whereBetween('started_at', [$dateFrom, $dateTo])->where('is_group', false)->count();
        $waFechadas = WhatsappConversation::whereBetween('started_at', [$dateFrom, $dateTo])->where('is_group', false)->where('status', 'closed')->count();
        $waComLead  = WhatsappConversation::whereBetween('started_at', [$dateFrom, $dateTo])->where('is_group', false)->whereNotNull('lead_id')->count();
        $waIA       = WhatsappConversation::whereBetween('started_at', [$dateFrom, $dateTo])->where('is_group', false)->whereNotNull('ai_agent_id')->count();

        $avgFirstResponse = $this->calculateAvgFirstResponse($tenantId, $dateFrom, $dateTo);

        $waMsgByUser = WhatsappMessage::where('direction', 'outbound')
            ->whereBetween('sent_at', [$dateFrom, $dateTo])
            ->whereNotNull('user_id')->where('is_deleted', false)
            ->selectRaw('user_id, COUNT(*) as total')
            ->groupBy('user_id')->orderByDesc('total')
            ->with('user')->get();

        // ── 7. ORIGEM × CONVERSÃO ──────────────────────────────────────────────
        $sourceConversion = DB::table('leads')
            ->where('leads.exclude_from_pipeline', false)
            ->whereBetween('leads.created_at', [$dateFrom, $dateTo])
            ->leftJoin('sales', function ($join) use ($dateFrom, $dateTo) {
                $join->on('sales.lead_id', '=', 'leads.id')
                     ->whereBetween('sales.closed_at', [$dateFrom, $dateTo]);
            })
            ->selectRaw('COALESCE(leads.source, "manual") as src, COUNT(DISTINCT leads.id) as leads_count, COUNT(DISTINCT sales.id) as sales_count, COALESCE(SUM(sales.value), 0) as revenue')
            ->where('leads.tenant_id', $tenantId)
            ->groupByRaw('COALESCE(leads.source, "manual")')
            ->orderByDesc('leads_count')
            ->get()
            ->map(fn ($row) => [
                'source'  => ucfirst((string) $row->src),
                'leads'   => (int) $row->leads_count,
                'vendas'  => (int) $row->sales_count,
                'receita' => (float) $row->revenue,
                'conv'    => $row->leads_count > 0 ? round($row->sales_count / $row->leads_count * 100, 1) : 0,
            ])
            ->values();

        // ── 8. FUNIL VISUAL ────────────────────────────────────────────────────
        $funnelEmAberto = max(0, $totalLeads - $salesCount - $totalLost);

        // ── 9. ATIVIDADE DA EQUIPE ─────────────────────────────────────────────
        $msgsByUser = WhatsappMessage::where('direction', 'outbound')
            ->whereBetween('sent_at', [$dateFrom, $dateTo])
            ->where('is_deleted', false)->whereNotNull('user_id')
            ->selectRaw('user_id, COUNT(*) as total')
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

        $eventsByUser = LeadEvent::whereBetween('created_at', [$dateFrom, $dateTo])
            ->whereNotNull('performed_by')
            ->selectRaw('performed_by, COUNT(*) as total')
            ->groupBy('performed_by')
            ->pluck('total', 'performed_by');

        $teamActivity = $allUsers->map(function (User $user) use ($msgsByUser, $eventsByUser) {
            $msgs   = (int) ($msgsByUser[$user->id] ?? 0);
            $events = (int) ($eventsByUser[$user->id] ?? 0);
            return ['user' => $user, 'msgs' => $msgs, 'events' => $events, 'total' => $msgs + $events];
        })->filter(fn ($r) => $r['total'] > 0)->sortByDesc('total')->values();

        // ── Produtos top 10 ────────────────────────────────────────────────────
        $topProducts = Product::withoutGlobalScope('tenant')
            ->select('products.id', 'products.name', 'products.price')
            ->selectRaw('COUNT(DISTINCT lead_products.lead_id) as won_count')
            ->selectRaw('SUM(lead_products.total) as total_value')
            ->join('lead_products', 'products.id', '=', 'lead_products.product_id')
            ->join('sales', 'lead_products.lead_id', '=', 'sales.lead_id')
            ->where('products.tenant_id', $tenantId)
            ->whereBetween('sales.closed_at', [$dateFrom, $dateTo])
            ->groupBy('products.id', 'products.name', 'products.price')
            ->orderByDesc('won_count')
            ->limit(10)
            ->get();

        // ── Cliques botão WhatsApp ─────────────────────────────────────────────
        $waButton         = WhatsappButton::first();
        $waClicksTotal    = 0;
        $waClicksMatched  = 0;
        $waClicksMobile   = 0;
        $waClicksByDay    = [];
        $waClicksBySource = [];
        $waClicksByPage   = [];

        if ($waButton) {
            $clicksQuery = WhatsappButtonClick::where('button_id', $waButton->id)
                ->whereBetween('clicked_at', [$dateFrom, $dateTo]);

            $waClicksTotal   = (clone $clicksQuery)->count();
            $waClicksMatched = (clone $clicksQuery)->where('matched', true)->count();
            $waClicksMobile  = (clone $clicksQuery)->where('device_type', 'mobile')->count();

            $waClicksByDay = (clone $clicksQuery)
                ->selectRaw('DATE(clicked_at) as day, COUNT(*) as total')
                ->groupBy('day')
                ->orderBy('day')
                ->pluck('total', 'day')
                ->toArray();

            $waClicksBySource = (clone $clicksQuery)
                ->selectRaw("COALESCE(NULLIF(utm_source, ''), 'direto') as src, COUNT(*) as total")
                ->groupBy('src')
                ->orderByDesc('total')
                ->limit(6)
                ->pluck('total', 'src')
                ->toArray();

            $waClicksByPage = (clone $clicksQuery)
                ->whereNotNull('page_url')
                ->where('page_url', '!=', '')
                ->selectRaw('page_url, COUNT(*) as total')
                ->groupBy('page_url')
                ->orderByDesc('total')
                ->limit(5)
                ->pluck('total', 'page_url')
                ->toArray();
        }

        return compact(
            'dateFrom', 'dateTo', 'filterPipeline', 'filterUser',
            'pipelines',
            'totalLeads', 'prevLeads', 'deltaLeads',
            'salesCount', 'totalRevenue', 'prevRevenue', 'deltaRevenue',
            'avgTicket', 'convRate',
            'chartDates', 'chartLeads', 'leadsBySource',
            'campaignRows',
            'pipelineRows',
            'totalLost', 'lostPotentialValue', 'lostByReason', 'lostByVendedor',
            'vendedores',
            'waTotal', 'waFechadas', 'waComLead', 'waIA', 'avgFirstResponse', 'waMsgByUser',
            'sourceConversion',
            'funnelEmAberto',
            'teamActivity',
            'topProducts',
            'waClicksTotal', 'waClicksMatched', 'waClicksMobile',
            'waClicksByDay', 'waClicksBySource', 'waClicksByPage',
        );
    }

    /**
     * Calcula tempo médio (minutos) da 1ª resposta humana em conversas WA.
     */
    private function calculateAvgFirstResponse(int $tenantId, Carbon $dateFrom, Carbon $dateTo): ?int
    {
        try {
            $convIds = WhatsappConversation::withoutGlobalScope('tenant')
                ->where('tenant_id', $tenantId)
                ->whereBetween('started_at', [$dateFrom, $dateTo])
                ->where('is_group', false)->whereNull('ai_agent_id')
                ->pluck('id');

            if ($convIds->isEmpty()) {
                return null;
            }

            $result = DB::selectOne("
                SELECT AVG(diff_minutes) as avg_min FROM (
                    SELECT TIMESTAMPDIFF(MINUTE, fi.first_in, fo.first_out) as diff_minutes
                    FROM (
                        SELECT conversation_id, MIN(sent_at) as first_in
                        FROM whatsapp_messages
                        WHERE direction = 'inbound' AND is_deleted = 0
                          AND conversation_id IN ({$convIds->implode(',')})
                        GROUP BY conversation_id
                    ) fi
                    JOIN (
                        SELECT conversation_id, MIN(sent_at) as first_out
                        FROM whatsapp_messages
                        WHERE direction = 'outbound' AND user_id IS NOT NULL AND is_deleted = 0
                          AND conversation_id IN ({$convIds->implode(',')})
                        GROUP BY conversation_id
                    ) fo ON fi.conversation_id = fo.conversation_id
                    WHERE fo.first_out > fi.first_in
                      AND TIMESTAMPDIFF(MINUTE, fi.first_in, fo.first_out) <= 1440
                ) sub
            ");

            return $result && $result->avg_min !== null ? (int) round((float) $result->avg_min) : null;
        } catch (\Throwable) {
            return null;
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\AdSpend;
use App\Models\Campaign;
use App\Models\Lead;
use App\Models\LeadEvent;
use App\Models\LostSale;
use App\Models\LostSaleReason;
use App\Models\Pipeline;
use App\Models\Sale;
use App\Models\User;
use App\Models\WhatsappConversation;
use App\Models\WhatsappMessage;
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

        $leadQuery = fn () => Lead::where('exclude_from_pipeline', false)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->when($filterCampaign, fn ($q) => $q->where('campaign_id', $filterCampaign))
            ->when($filterPipeline, fn ($q) => $q->where('pipeline_id', $filterPipeline))
            ->when($filterUser,     fn ($q) => $q->where('assigned_to', $filterUser));

        $totalLeads  = $leadQuery()->count();
        $prevLeads   = Lead::where('exclude_from_pipeline', false)
            ->whereBetween('created_at', [$prevFrom, $prevTo])
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
                $leadsCount  = Lead::where('exclude_from_pipeline', false)
                    ->where('campaign_id', $campaign->id)
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
                    $avgDays = Lead::where('exclude_from_pipeline', false)
                        ->where('stage_id', $stage->id)
                        ->selectRaw('AVG(DATEDIFF(NOW(), updated_at)) as avg_d')
                        ->value('avg_d');
                    return [
                        'stage'    => $stage,
                        'count'    => Lead::where('exclude_from_pipeline', false)
                            ->where('stage_id', $stage->id)
                            ->whereBetween('created_at', [$dateFrom, $dateTo])
                            ->count(),
                        'avg_days' => $avgDays !== null ? (int) round((float) $avgDays) : null,
                    ];
                });

                // Calcula largura visual do funil: normal stages 100→32%, won/lost ambos em 28%
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

        // Por vendedor
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

        // ══════════════════════════════════════════════════════════════════════
        // 5. DESEMPENHO POR VENDEDOR
        // ══════════════════════════════════════════════════════════════════════

        $tenantId   = auth()->user()->tenant_id;
        $vendedores = User::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get()
            ->map(function (User $user) use ($dateFrom, $dateTo, $filterCampaign, $filterPipeline) {
                $leads   = Lead::where('assigned_to', $user->id)
                    ->whereBetween('created_at', [$dateFrom, $dateTo])
                    ->when($filterCampaign, fn ($q) => $q->where('campaign_id', $filterCampaign))
                    ->when($filterPipeline, fn ($q) => $q->where('pipeline_id', $filterPipeline))
                    ->count();
                $vendas  = Sale::where('closed_by', $user->id)
                    ->whereBetween('closed_at', [$dateFrom, $dateTo])
                    ->when($filterCampaign, fn ($q) => $q->where('campaign_id', $filterCampaign))
                    ->when($filterPipeline, fn ($q) => $q->where('pipeline_id', $filterPipeline))
                    ->count();
                $receita = (float) (Sale::where('closed_by', $user->id)
                    ->whereBetween('closed_at', [$dateFrom, $dateTo])
                    ->sum('value') ?? 0);
                return [
                    'user'    => $user,
                    'leads'   => $leads,
                    'vendas'  => $vendas,
                    'receita' => $receita,
                    'conv'    => $leads > 0 ? round($vendas / $leads * 100, 1) : 0,
                ];
            })
            ->filter(fn ($r) => $r['leads'] > 0 || $r['vendas'] > 0)
            ->sortByDesc('receita')
            ->values();

        // ══════════════════════════════════════════════════════════════════════
        // 6. WHATSAPP ANALYTICS
        // ══════════════════════════════════════════════════════════════════════

        $waTotal    = WhatsappConversation::whereBetween('started_at', [$dateFrom, $dateTo])->where('is_group', false)->count();
        $waFechadas = WhatsappConversation::whereBetween('started_at', [$dateFrom, $dateTo])->where('is_group', false)->where('status', 'closed')->count();
        $waComLead  = WhatsappConversation::whereBetween('started_at', [$dateFrom, $dateTo])->where('is_group', false)->whereNotNull('lead_id')->count();
        $waIA       = WhatsappConversation::whereBetween('started_at', [$dateFrom, $dateTo])->where('is_group', false)->whereNotNull('ai_agent_id')->count();

        // Tempo médio de 1ª resposta humana (sample 300 conversas, exclui IA)
        $avgFirstResponse = null;
        $convSample       = WhatsappConversation::whereBetween('started_at', [$dateFrom, $dateTo])
            ->where('is_group', false)->whereNull('ai_agent_id')->limit(300)->pluck('id');
        $times = [];
        foreach ($convSample as $cid) {
            $firstIn  = WhatsappMessage::where('conversation_id', $cid)->where('direction', 'inbound')
                ->where('is_deleted', false)->orderBy('sent_at')->value('sent_at');
            if (! $firstIn) continue;
            $firstOut = WhatsappMessage::where('conversation_id', $cid)->where('direction', 'outbound')
                ->whereNotNull('user_id')->where('is_deleted', false)
                ->where('sent_at', '>', $firstIn)->orderBy('sent_at')->value('sent_at');
            if (! $firstOut) continue;
            $diff = Carbon::parse($firstIn)->diffInMinutes(Carbon::parse($firstOut));
            if ($diff <= 1440) $times[] = $diff;
        }
        $avgFirstResponse = count($times) > 0 ? (int) round(array_sum($times) / count($times)) : null;

        // Mensagens enviadas por atendente humano no período
        $waMsgByUser = WhatsappMessage::where('direction', 'outbound')
            ->whereBetween('sent_at', [$dateFrom, $dateTo])
            ->whereNotNull('user_id')->where('is_deleted', false)
            ->selectRaw('user_id, COUNT(*) as total')
            ->groupBy('user_id')->orderByDesc('total')
            ->with('user')->get();

        // ══════════════════════════════════════════════════════════════════════
        // 7. ORIGEM × CONVERSÃO
        // ══════════════════════════════════════════════════════════════════════

        $sourceConversion = Lead::where('exclude_from_pipeline', false)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('COALESCE(source, "manual") as src, COUNT(*) as total')
            ->groupBy('src')
            ->get()
            ->map(function ($row) use ($dateFrom, $dateTo) {
                $leadIds = Lead::where('exclude_from_pipeline', false)
                    ->whereBetween('created_at', [$dateFrom, $dateTo])
                    ->whereRaw('COALESCE(source, "manual") = ?', [$row->src])
                    ->pluck('id');
                $vendas  = Sale::whereIn('lead_id', $leadIds)
                    ->whereBetween('closed_at', [$dateFrom, $dateTo])->count();
                $receita = (float) (Sale::whereIn('lead_id', $leadIds)
                    ->whereBetween('closed_at', [$dateFrom, $dateTo])->sum('value') ?? 0);
                return [
                    'source'  => ucfirst((string) $row->src),
                    'leads'   => (int) $row->total,
                    'vendas'  => $vendas,
                    'receita' => $receita,
                    'conv'    => $row->total > 0 ? round($vendas / $row->total * 100, 1) : 0,
                ];
            })
            ->sortByDesc('leads')
            ->values();

        // ══════════════════════════════════════════════════════════════════════
        // 8. FUNIL DE CONVERSÃO VISUAL (sem queries extras)
        // ══════════════════════════════════════════════════════════════════════

        $funnelEmAberto = max(0, $totalLeads - $salesCount - $totalLost);

        // ══════════════════════════════════════════════════════════════════════
        // 9. ATIVIDADE DA EQUIPE
        // ══════════════════════════════════════════════════════════════════════

        $teamActivity = User::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get()
            ->map(function (User $user) use ($dateFrom, $dateTo) {
                $msgs   = WhatsappMessage::where('user_id', $user->id)->where('direction', 'outbound')
                    ->whereBetween('sent_at', [$dateFrom, $dateTo])->where('is_deleted', false)->count();
                $events = LeadEvent::where('performed_by', $user->id)
                    ->whereBetween('created_at', [$dateFrom, $dateTo])->count();
                return ['user' => $user, 'msgs' => $msgs, 'events' => $events, 'total' => $msgs + $events];
            })
            ->filter(fn ($r) => $r['total'] > 0)
            ->sortByDesc('total')
            ->values();

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
            'totalLost', 'lostPotentialValue', 'lostByReason', 'lostByCampaign', 'lostByVendedor',
            // vendedores
            'vendedores',
            // whatsapp
            'waTotal', 'waFechadas', 'waComLead', 'waIA', 'avgFirstResponse', 'waMsgByUser',
            // origem × conversão
            'sourceConversion',
            // funil visual
            'funnelEmAberto',
            // atividade
            'teamActivity',
        ));
    }
}

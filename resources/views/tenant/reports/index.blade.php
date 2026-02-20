@extends('tenant.layouts.app')
@php
    $title   = 'Relatórios';
    $pageIcon = 'bar-chart-line';
@endphp

@push('styles')
<style>
    /* ── Layout ─────────────────────────────────────────────────── */
    .report-section {
        background: #fff;
        border-radius: 14px;
        border: 1px solid #e8eaf0;
        margin-bottom: 22px;
        overflow: hidden;
    }

    .report-section-header {
        padding: 16px 22px;
        border-bottom: 1px solid #f0f2f7;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 14px;
        font-weight: 700;
        color: #1a1d23;
    }

    .report-section-header i { color: #3B82F6; font-size: 16px; }
    .report-section-body { padding: 20px 22px; }

    /* ── Filtros ─────────────────────────────────────────────────── */
    .report-filter-wrap {
        background: #fff;
        border-bottom: 1px solid #e8eaf0;
        padding: 12px 28px;
        width: 100%;
    }

    .report-filter-inner {
        display: flex;
        align-items: flex-end;
        gap: 10px;
        flex-wrap: wrap;
    }

    .report-filter-inner label {
        font-size: 11px;
        font-weight: 600;
        color: #9ca3af;
        margin-bottom: 3px;
        display: block;
    }

    .report-filter-inner input,
    .report-filter-inner select {
        padding: 7px 10px;
        border: 1.5px solid #e8eaf0;
        border-radius: 8px;
        font-size: 13px;
        font-family: inherit;
        color: #374151;
        background: #fafafa;
        outline: none;
    }

    .report-filter-inner input:focus,
    .report-filter-inner select:focus { border-color: #3B82F6; background: #fff; }

    .btn-apply {
        padding: 8px 18px;
        background: #3B82F6;
        color: #fff;
        border: none;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        align-self: flex-end;
        transition: background .15s;
    }

    .btn-apply:hover { background: #2563EB; }

    .btn-clear {
        padding: 8px 14px;
        background: #fff;
        color: #6b7280;
        border: 1.5px solid #e8eaf0;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        align-self: flex-end;
        transition: all .15s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .btn-clear:hover { background: #f4f6fb; color: #374151; }

    /* ── KPI Cards ───────────────────────────────────────────────── */
    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 14px;
        margin-bottom: 22px;
    }

    @media (max-width: 900px) { .kpi-grid { grid-template-columns: repeat(2, 1fr); } }

    .kpi-card {
        background: #fff;
        border-radius: 14px;
        border: 1px solid #e8eaf0;
        padding: 18px 20px;
    }

    .kpi-label {
        font-size: 12px;
        font-weight: 600;
        color: #9ca3af;
        text-transform: uppercase;
        letter-spacing: .06em;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .kpi-value {
        font-size: 26px;
        font-weight: 700;
        color: #1a1d23;
        line-height: 1;
        margin-bottom: 5px;
    }

    .kpi-delta {
        font-size: 12px;
        font-weight: 600;
    }

    .kpi-delta.up   { color: #10B981; }
    .kpi-delta.down { color: #EF4444; }
    .kpi-delta.neu  { color: #9ca3af; }

    /* ── Charts ──────────────────────────────────────────────────── */
    .charts-row {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 14px;
        margin-bottom: 0;
    }

    @media (max-width: 900px) { .charts-row { grid-template-columns: 1fr; } }

    .chart-box {
        background: #fff;
        border-radius: 14px;
        border: 1px solid #e8eaf0;
        padding: 18px 20px;
    }

    .chart-title {
        font-size: 13px;
        font-weight: 700;
        color: #374151;
        margin-bottom: 14px;
    }

    /* ── Tabelas ─────────────────────────────────────────────────── */
    .report-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }

    .report-table thead th {
        padding: 10px 14px;
        font-size: 11px;
        font-weight: 700;
        color: #9ca3af;
        text-transform: uppercase;
        letter-spacing: .06em;
        border-bottom: 1px solid #f0f2f7;
        background: #fafafa;
        white-space: nowrap;
        text-align: left;
    }

    .report-table thead th.num { text-align: right; }

    .report-table tbody tr { border-bottom: 1px solid #f7f8fa; }
    .report-table tbody tr:last-child { border-bottom: none; }
    .report-table tbody tr:hover { background: #f8faff; }

    .report-table tbody td {
        padding: 10px 14px;
        color: #374151;
        vertical-align: middle;
    }

    .report-table tbody td.num {
        text-align: right;
        font-variant-numeric: tabular-nums;
    }

    .platform-dot {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 12px;
        font-weight: 600;
    }

    .platform-dot .dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
    }

    .dot-facebook { background: #1877F2; }
    .dot-google   { background: #EA4335; }

    /* Funil barra */
    .funnel-bar-wrap {
        height: 8px;
        background: #f0f2f7;
        border-radius: 99px;
        overflow: hidden;
        flex: 1;
        min-width: 60px;
    }

    .funnel-bar-fill {
        height: 100%;
        border-radius: 99px;
        transition: width .3s;
    }

    /* Badges */
    .badge-won  { background: #d1fae5; color: #065f46; font-size: 10px; font-weight: 700; padding: 2px 7px; border-radius: 99px; }
    .badge-lost { background: #fee2e2; color: #991b1b; font-size: 10px; font-weight: 700; padding: 2px 7px; border-radius: 99px; }

    .empty-row td {
        text-align: center;
        padding: 40px;
        color: #9ca3af;
        font-size: 13px;
    }

    /* ROI color */
    .roi-positive { color: #10B981; font-weight: 700; }
    .roi-negative { color: #EF4444; font-weight: 700; }
    .roi-neutral  { color: #9ca3af; }

    /* Reason bar (lost) */
    .reason-bar-wrap {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .reason-bar-bg {
        flex: 1;
        height: 6px;
        background: #f0f2f7;
        border-radius: 99px;
        overflow: hidden;
    }
    .reason-bar-fill {
        height: 100%;
        background: #EF4444;
        border-radius: 99px;
    }
</style>
@endpush

@section('content')

{{-- ── Barra de filtros (full width) ─────────────────────────────────── --}}
<div class="report-filter-wrap">
    <form method="GET" action="{{ route('reports.index') }}" id="reportFilterForm">
        <div class="report-filter-inner">

            <div>
                <label>De</label>
                <input type="date" name="date_from" value="{{ $dateFrom->format('Y-m-d') }}">
            </div>

            <div>
                <label>Até</label>
                <input type="date" name="date_to" value="{{ $dateTo->format('Y-m-d') }}">
            </div>

            <div>
                <label>Campanha</label>
                <select name="campaign_id" style="min-width:150px;">
                    <option value="">Todas</option>
                    @foreach($campaigns as $camp)
                    <option value="{{ $camp->id }}" @selected($filterCampaign == $camp->id)>{{ $camp->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label>Pipeline</label>
                <select name="pipeline_id" style="min-width:140px;">
                    <option value="">Todos</option>
                    @foreach($pipelines as $pipe)
                    <option value="{{ $pipe->id }}" @selected($filterPipeline == $pipe->id)>{{ $pipe->name }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="btn-apply">
                <i class="bi bi-funnel"></i> Aplicar
            </button>

            @if($filterCampaign || $filterPipeline || $filterUser || request('date_from') || request('date_to'))
            <a href="{{ route('reports.index') }}" class="btn-clear">
                <i class="bi bi-x"></i> Limpar
            </a>
            @endif

        </div>
    </form>
</div>

<div class="page-container">

    {{-- ════════════════════════════════════════════════════════════ --}}
    {{-- VISÃO GERAL —  KPI Cards                                    --}}
    {{-- ════════════════════════════════════════════════════════════ --}}
    <div class="kpi-grid">
        {{-- Leads --}}
        <div class="kpi-card">
            <div class="kpi-label"><i class="bi bi-people" style="color:#3B82F6;"></i> Leads</div>
            <div class="kpi-value">{{ number_format($totalLeads, 0, ',', '.') }}</div>
            @if($deltaLeads !== null)
            <div class="kpi-delta {{ $deltaLeads >= 0 ? 'up' : 'down' }}">
                {{ $deltaLeads >= 0 ? '↑' : '↓' }} {{ abs($deltaLeads) }}% vs período anterior
            </div>
            @else
            <div class="kpi-delta neu">Sem dados anteriores</div>
            @endif
        </div>

        {{-- Receita --}}
        <div class="kpi-card">
            <div class="kpi-label"><i class="bi bi-cash-stack" style="color:#10B981;"></i> Receita</div>
            <div class="kpi-value" style="color:#10B981;">R$ {{ number_format($totalRevenue, 0, ',', '.') }}</div>
            @if($deltaRevenue !== null)
            <div class="kpi-delta {{ $deltaRevenue >= 0 ? 'up' : 'down' }}">
                {{ $deltaRevenue >= 0 ? '↑' : '↓' }} {{ abs($deltaRevenue) }}% vs período anterior
            </div>
            @else
            <div class="kpi-delta neu">Sem dados anteriores</div>
            @endif
        </div>

        {{-- Ticket Médio --}}
        <div class="kpi-card">
            <div class="kpi-label"><i class="bi bi-tag" style="color:#8B5CF6;"></i> Ticket Médio</div>
            <div class="kpi-value" style="color:#8B5CF6;">R$ {{ number_format($avgTicket, 0, ',', '.') }}</div>
            <div class="kpi-delta neu">{{ $salesCount }} venda(s) no período</div>
        </div>

        {{-- Taxa de Conversão --}}
        <div class="kpi-card">
            <div class="kpi-label"><i class="bi bi-arrow-up-right-circle" style="color:#F59E0B;"></i> Taxa de Conversão</div>
            <div class="kpi-value" style="color:#F59E0B;">{{ $convRate }}%</div>
            <div class="kpi-delta neu">Leads → Vendas</div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════════════ --}}
    {{-- Gráficos — Linha + Origem                                   --}}
    {{-- ════════════════════════════════════════════════════════════ --}}
    <div class="charts-row" style="margin-bottom:22px;">

        <div class="chart-box">
            <div class="chart-title">Leads por dia</div>
            <canvas id="chartLeadsByDay" height="120"></canvas>
        </div>

        <div class="chart-box">
            <div class="chart-title">Leads por origem</div>
            <canvas id="chartLeadsBySource" height="180"></canvas>
        </div>

    </div>

    {{-- ════════════════════════════════════════════════════════════ --}}
    {{-- CAMPANHAS                                                   --}}
    {{-- ════════════════════════════════════════════════════════════ --}}
    <div class="report-section">
        <div class="report-section-header">
            <i class="bi bi-megaphone"></i>
            Desempenho de Campanhas
        </div>
        <div style="overflow-x:auto;">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Campanha</th>
                        <th>Plataforma</th>
                        <th class="num">Investido</th>
                        <th class="num">Impressões</th>
                        <th class="num">Cliques</th>
                        <th class="num">CTR</th>
                        <th class="num">Leads</th>
                        <th class="num">Custo/Lead</th>
                        <th class="num">Receita</th>
                        <th class="num">ROI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($campaignRows as $row)
                    <tr>
                        <td style="font-weight:600;color:#1a1d23;">{{ $row['campaign']->name }}</td>
                        <td>
                            <span class="platform-dot">
                                <span class="dot {{ $row['campaign']->platform === 'facebook' ? 'dot-facebook' : 'dot-google' }}"></span>
                                {{ $row['campaign']->platform === 'facebook' ? 'Facebook' : 'Google' }}
                            </span>
                        </td>
                        <td class="num">{{ $row['spend'] > 0 ? 'R$ ' . number_format($row['spend'], 2, ',', '.') : '—' }}</td>
                        <td class="num">{{ $row['impressions'] > 0 ? number_format($row['impressions'], 0, ',', '.') : '—' }}</td>
                        <td class="num">{{ $row['clicks'] > 0 ? number_format($row['clicks'], 0, ',', '.') : '—' }}</td>
                        <td class="num">{{ $row['ctr'] !== null ? $row['ctr'] . '%' : '—' }}</td>
                        <td class="num" style="font-weight:700;color:#3B82F6;">{{ $row['leads_count'] }}</td>
                        <td class="num">{{ $row['cost_per_lead'] !== null ? 'R$ ' . number_format($row['cost_per_lead'], 2, ',', '.') : '—' }}</td>
                        <td class="num" style="color:#10B981;font-weight:700;">{{ $row['revenue'] > 0 ? 'R$ ' . number_format($row['revenue'], 2, ',', '.') : '—' }}</td>
                        <td class="num">
                            @if($row['roi'] !== null)
                                <span class="{{ $row['roi'] >= 0 ? 'roi-positive' : 'roi-negative' }}">
                                    {{ $row['roi'] >= 0 ? '+' : '' }}{{ $row['roi'] }}%
                                </span>
                            @else
                                <span class="roi-neutral">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr class="empty-row">
                        <td colspan="10">
                            <i class="bi bi-megaphone" style="font-size:28px;opacity:.2;display:block;margin-bottom:6px;"></i>
                            Nenhuma campanha com dados no período selecionado
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════════════ --}}
    {{-- PIPELINE / FUNIL                                            --}}
    {{-- ════════════════════════════════════════════════════════════ --}}
    @foreach($pipelineRows as $pipeRow)
    <div class="report-section">
        <div class="report-section-header">
            <i class="bi bi-filter-left"></i>
            Funil: {{ $pipeRow['pipeline']->name }}
            <span style="margin-left:auto;font-size:12px;font-weight:500;color:#9ca3af;">
                {{ $pipeRow['total'] }} lead(s) no período
            </span>
        </div>
        <div style="overflow-x:auto;">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Etapa</th>
                        <th class="num">Leads</th>
                        <th>% do Total</th>
                        <th>Visualização</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pipeRow['stages'] as $stageRow)
                    @php
                        $stage   = $stageRow['stage'];
                        $count   = $stageRow['count'];
                        $pipeTotal = $pipeRow['total'];
                        $pct     = $pipeTotal > 0 ? round($count / $pipeTotal * 100, 1) : 0;
                    @endphp
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <span style="width:10px;height:10px;border-radius:50%;background:{{ $stage->color ?? '#3B82F6' }};flex-shrink:0;display:inline-block;"></span>
                                {{ $stage->name }}
                                @if($stage->is_won)  <span class="badge-won">GANHO</span>  @endif
                                @if($stage->is_lost) <span class="badge-lost">PERDIDO</span> @endif
                            </div>
                        </td>
                        <td class="num" style="font-weight:700;color:#1a1d23;">{{ $count }}</td>
                        <td class="num" style="color:#6b7280;">{{ $pct }}%</td>
                        <td style="padding-right:24px;">
                            <div class="funnel-bar-wrap">
                                <div class="funnel-bar-fill"
                                     style="width:{{ $pct }}%;background:{{ $stage->color ?? '#3B82F6' }};"></div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr class="empty-row"><td colspan="4">Nenhuma etapa configurada</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endforeach

    @if($pipelineRows->isEmpty())
    <div class="report-section">
        <div class="report-section-header"><i class="bi bi-filter-left"></i> Pipeline / Funil</div>
        <div class="report-section-body" style="text-align:center;color:#9ca3af;padding:40px;">
            Nenhum pipeline configurado.
        </div>
    </div>
    @endif

    {{-- ════════════════════════════════════════════════════════════ --}}
    {{-- LEADS PERDIDOS                                              --}}
    {{-- ════════════════════════════════════════════════════════════ --}}
    <div class="report-section">
        <div class="report-section-header">
            <i class="bi bi-x-circle" style="color:#EF4444;"></i>
            Leads Perdidos
            <span style="margin-left:auto;font-size:12px;font-weight:500;color:#9ca3af;">
                {{ $totalLost }} perda(s) · Valor potencial:
                <strong style="color:#EF4444;">R$ {{ number_format($lostPotentialValue, 0, ',', '.') }}</strong>
            </span>
        </div>

        <div class="report-section-body" style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">

            {{-- Por Motivo --}}
            <div>
                <div style="font-size:13px;font-weight:700;color:#374151;margin-bottom:14px;">Por Motivo</div>
                @forelse($lostByReason as $row)
                <div style="margin-bottom:12px;">
                    <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px;">
                        <span style="color:#374151;font-weight:500;">{{ $row['reason'] }}</span>
                        <span style="color:#EF4444;font-weight:700;">{{ $row['total'] }}
                            <span style="color:#9ca3af;font-weight:400;">({{ $row['pct'] }}%)</span>
                        </span>
                    </div>
                    <div class="reason-bar-bg">
                        <div class="reason-bar-fill" style="width:{{ $row['pct'] }}%;"></div>
                    </div>
                </div>
                @empty
                <p style="color:#9ca3af;font-size:13px;">Nenhuma perda no período.</p>
                @endforelse
            </div>

            {{-- Por Campanha --}}
            <div>
                <div style="font-size:13px;font-weight:700;color:#374151;margin-bottom:14px;">Por Campanha</div>
                @forelse($lostByCampaign as $row)
                <div style="display:flex;justify-content:space-between;align-items:center;padding:7px 0;border-bottom:1px solid #f7f8fa;">
                    <span style="font-size:13px;color:#374151;">{{ $row['campaign'] }}</span>
                    <span style="font-size:13px;font-weight:700;color:#EF4444;">{{ $row['total'] }}</span>
                </div>
                @empty
                <p style="color:#9ca3af;font-size:13px;">Nenhuma perda no período.</p>
                @endforelse
            </div>

        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
// ── Dados do servidor ─────────────────────────────────────────────────────
const chartDates = @json($chartDates);
const chartLeads = @json($chartLeads);

@php
    $srcLabels = $leadsBySource->pluck('source')->map(fn($s) => ucfirst($s ?? 'manual'))->toArray();
    $srcData   = $leadsBySource->pluck('total')->toArray();
@endphp
const sourceLabels = @json($srcLabels);
const sourceData   = @json($srcData);

// ── Gráfico: Leads por dia (linha) ────────────────────────────────────────
(function () {
    const ctx = document.getElementById('chartLeadsByDay');
    if (!ctx || !window.Chart) return;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartDates,
            datasets: [{
                label: 'Leads',
                data: chartLeads,
                borderColor: '#3B82F6',
                backgroundColor: 'rgba(59,130,246,.08)',
                borderWidth: 2,
                pointRadius: chartDates.length > 30 ? 0 : 3,
                pointBackgroundColor: '#3B82F6',
                fill: true,
                tension: .35,
            }],
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 11 }, maxTicksLimit: 10 } },
                y: { beginAtZero: true, ticks: { font: { size: 11 }, precision: 0 } },
            },
        },
    });
}());

// ── Gráfico: Leads por origem (barra horizontal) ──────────────────────────
(function () {
    const ctx = document.getElementById('chartLeadsBySource');
    if (!ctx || !window.Chart) return;
    const colors = ['#3B82F6','#10B981','#8B5CF6','#F59E0B','#EF4444','#06B6D4','#F97316','#84CC16'];
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: sourceLabels,
            datasets: [{
                label: 'Leads',
                data: sourceData,
                backgroundColor: sourceLabels.map((_, i) => colors[i % colors.length] + 'cc'),
                borderRadius: 6,
                borderSkipped: false,
            }],
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero: true, ticks: { font: { size: 11 }, precision: 0 } },
                y: { grid: { display: false }, ticks: { font: { size: 11 } } },
            },
        },
    });
}());
</script>
@endpush

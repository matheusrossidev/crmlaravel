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

    /* ── Funil de Conversão Visual ───────────────────────────────── */
    .funnel-visual {
        display: flex;
        gap: 6px;
        align-items: stretch;
        margin-bottom: 20px;
    }

    .funnel-visual-step {
        flex: 1;
        border-radius: 10px;
        padding: 14px 16px;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .funnel-visual-step .fv-count {
        font-size: 22px;
        font-weight: 800;
        color: #1a1d23;
        line-height: 1;
    }

    .funnel-visual-step .fv-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .05em;
    }

    .funnel-visual-step .fv-pct {
        font-size: 11px;
        margin-top: 2px;
        opacity: .65;
    }

    .fv-total   { background: #EFF6FF; }
    .fv-total   .fv-label { color: #3B82F6; }
    .fv-open    { background: #FFFBEB; }
    .fv-open    .fv-label { color: #D97706; }
    .fv-won     { background: #F0FDF4; }
    .fv-won     .fv-label { color: #16A34A; }
    .fv-lost    { background: #FEF2F2; }
    .fv-lost    .fv-label { color: #DC2626; }

    .funnel-arrow {
        color: #d1d5db;
        font-size: 18px;
        display: flex;
        align-items: center;
        flex-shrink: 0;
    }

    /* ── WhatsApp mini KPIs ──────────────────────────────────────── */
    .wa-kpi-mini-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
        margin-bottom: 18px;
    }

    @media (max-width: 900px) { .wa-kpi-mini-grid { grid-template-columns: repeat(2, 1fr); } }

    .wa-kpi-mini {
        background: #f8fafc;
        border-radius: 10px;
        border: 1px solid #e8eaf0;
        padding: 14px 16px;
    }

    .wa-kpi-mini .wk-label {
        font-size: 11px;
        font-weight: 600;
        color: #9ca3af;
        text-transform: uppercase;
        letter-spacing: .05em;
        margin-bottom: 5px;
    }

    .wa-kpi-mini .wk-value {
        font-size: 20px;
        font-weight: 800;
        color: #1a1d23;
        line-height: 1;
    }

    .wa-kpi-mini .wk-sub {
        font-size: 11px;
        color: #9ca3af;
        margin-top: 3px;
    }

    /* ── Barra de atividade ─────────────────────────────────────── */
    .activity-bar-wrap { display: flex; align-items: center; gap: 8px; }
    .activity-bar-bg   { flex: 1; height: 6px; background: #f0f2f7; border-radius: 99px; overflow: hidden; }
    .activity-bar-fill { height: 100%; background: #3B82F6; border-radius: 99px; }

    /* ── Taxa conversão colorida ────────────────────────────────── */
    .conv-high { color: #16A34A; font-weight: 700; }
    .conv-mid  { color: #D97706; font-weight: 700; }
    .conv-low  { color: #6b7280; }

    /* ── Leads perdidos: grid 3 colunas ────────────────────────── */
    .lost-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 24px; }
    @media (max-width: 900px) { .lost-grid-3 { grid-template-columns: 1fr 1fr; } }
    @media (max-width: 600px) { .lost-grid-3 { grid-template-columns: 1fr; } }

    /* ── Layout Funil + Campanhas 50/50 ─────────────────────────── */
    .funnel-campaigns-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        margin-bottom: 22px;
    }
    @media (max-width: 900px) { .funnel-campaigns-grid { grid-template-columns: 1fr; } }

    /* ── Funil Real Vertical ─────────────────────────────────────── */
    .real-funnel { display: flex; flex-direction: column; gap: 5px; }

    .funnel-rrow { display: flex; flex-direction: column; gap: 3px; }

    .funnel-rbar-outer { width: 100%; display: flex; justify-content: center; }

    .funnel-rbar-inner {
        height: 44px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 14px;
        transition: width .5s ease;
        min-width: 50px;
    }

    .funnel-rbar-label { font-size: 13px; font-weight: 700; color: #fff; }
    .funnel-rbar-pct   { font-size: 11px; color: rgba(255,255,255,.85); font-weight: 600; }

    .funnel-rrow-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 3px;
    }

    .funnel-rrow-name { font-size: 12px; color: #6b7280; font-weight: 500; }

    .funnel-rrow-time {
        font-size: 11px;
        color: #9ca3af;
        display: flex;
        align-items: center;
        gap: 3px;
    }

    .funnel-rconnector {
        display: flex;
        justify-content: center;
        color: #d1d5db;
        font-size: 13px;
        line-height: 1;
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
            <div style="position:relative;height:180px;">
                <canvas id="chartLeadsByDay"></canvas>
            </div>
        </div>

        <div class="chart-box">
            <div class="chart-title">Leads por origem</div>
            <div style="position:relative;height:180px;">
                <canvas id="chartLeadsBySource"></canvas>
            </div>
        </div>

    </div>

    {{-- ════════════════════════════════════════════════════════════ --}}
    {{-- FUNIL REAL + CAMPANHAS (50/50)                             --}}
    {{-- ════════════════════════════════════════════════════════════ --}}
    <div class="funnel-campaigns-grid">

        {{-- Funil Real de Pipeline --}}
        <div class="report-section" style="margin-bottom:0;">
            <div class="report-section-header">
                <i class="bi bi-funnel"></i>
                Funil de Conversão
            </div>
            <div class="report-section-body">
                @php $funnelPipe = $pipelineRows->first(fn($r) => $r['stages']->isNotEmpty()); @endphp
                @if($funnelPipe)
                    @php $funnelBase = max($funnelPipe['total'], 1); @endphp
                    <div class="real-funnel">
                        @foreach($funnelPipe['stages'] as $i => $stageRow)
                        @php
                            $stage   = $stageRow['stage'];
                            $count   = $stageRow['count'];
                            $avgDays = $stageRow['avg_days'] ?? null;
                            $pct     = round($count / $funnelBase * 100, 1);
                            $barW    = $stageRow['bar_width'];
                            $color   = $stage->color ?? '#3B82F6';
                        @endphp
                        @if($i > 0)
                        <div class="funnel-rconnector"><i class="bi bi-chevron-down"></i></div>
                        @endif
                        <div class="funnel-rrow">
                            <div class="funnel-rrow-meta">
                                <span class="funnel-rrow-name">
                                    {{ $stage->name }}
                                    @if($stage->is_won)  <span class="badge-won" style="font-size:9px;vertical-align:middle;">GANHO</span>   @endif
                                    @if($stage->is_lost) <span class="badge-lost" style="font-size:9px;vertical-align:middle;">PERDIDO</span> @endif
                                </span>
                                @if($avgDays !== null)
                                <span class="funnel-rrow-time">
                                    <i class="bi bi-clock" style="font-size:10px;"></i> {{ $avgDays }}d méd.
                                </span>
                                @endif
                            </div>
                            <div class="funnel-rbar-outer">
                                <div class="funnel-rbar-inner" style="width:{{ $barW }}%;background:{{ $color }};">
                                    <span class="funnel-rbar-label">{{ number_format($count, 0, ',', '.') }}</span>
                                    <span class="funnel-rbar-pct">{{ $pct }}%</span>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <p style="color:#9ca3af;font-size:13px;text-align:center;padding:24px 0;">Nenhum pipeline com dados.</p>
                @endif
            </div>
        </div>

        {{-- Campanhas --}}
        <div class="report-section" style="margin-bottom:0;">
            <div class="report-section-header">
                <i class="bi bi-megaphone"></i>
                Campanhas
            </div>
            <div style="overflow-x:auto;">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Campanha</th>
                            <th class="num">Investido</th>
                            <th class="num">Leads</th>
                            <th class="num">C/Lead</th>
                            <th class="num">Receita</th>
                            <th class="num">ROI</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($campaignRows as $row)
                        <tr>
                            <td style="font-size:12px;">
                                <span style="font-weight:600;color:#1a1d23;">{{ $row['campaign']->name }}</span><br>
                                <span style="font-size:11px;color:#9ca3af;">{{ $row['campaign']->platform === 'facebook' ? 'Facebook' : 'Google' }}</span>
                            </td>
                            <td class="num">{{ $row['spend'] > 0 ? 'R$ '.number_format($row['spend'], 0, ',', '.') : '—' }}</td>
                            <td class="num" style="font-weight:700;color:#3B82F6;">{{ $row['leads_count'] }}</td>
                            <td class="num">{{ $row['cost_per_lead'] !== null ? 'R$ '.number_format($row['cost_per_lead'], 0, ',', '.') : '—' }}</td>
                            <td class="num" style="color:#10B981;font-weight:700;">{{ $row['revenue'] > 0 ? 'R$ '.number_format($row['revenue'], 0, ',', '.') : '—' }}</td>
                            <td class="num">
                                @if($row['roi'] !== null)
                                    <span class="{{ $row['roi'] >= 0 ? 'roi-positive' : 'roi-negative' }}">{{ $row['roi'] >= 0 ? '+' : '' }}{{ $row['roi'] }}%</span>
                                @else
                                    <span class="roi-neutral">—</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr class="empty-row">
                            <td colspan="6">
                                <i class="bi bi-megaphone" style="font-size:28px;opacity:.2;display:block;margin-bottom:6px;"></i>
                                Nenhuma campanha com dados
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>{{-- /funnel-campaigns-grid --}}

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

        <div class="report-section-body lost-grid-3">

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

            {{-- Por Vendedor --}}
            <div>
                <div style="font-size:13px;font-weight:700;color:#374151;margin-bottom:14px;">Por Vendedor</div>
                @forelse($lostByVendedor as $row)
                <div style="margin-bottom:12px;">
                    <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px;">
                        <span style="color:#374151;font-weight:500;">{{ $row['user'] }}</span>
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

        </div>
    </div>


    {{-- ════════════════════════════════════════════════════════════ --}}
    {{-- DESEMPENHO POR VENDEDOR                                     --}}
    {{-- ════════════════════════════════════════════════════════════ --}}
    <div class="report-section">
        <div class="report-section-header">
            <i class="bi bi-person-badge"></i>
            Desempenho por Vendedor
        </div>
        <div style="overflow-x:auto;">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Vendedor</th>
                        <th class="num">Leads atribuídos</th>
                        <th class="num">Vendas fechadas</th>
                        <th class="num">Conversão</th>
                        <th class="num">Receita</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vendedores as $row)
                    <tr>
                        <td style="font-weight:600;color:#1a1d23;">{{ $row['user']->name }}</td>
                        <td class="num">{{ $row['leads'] }}</td>
                        <td class="num">{{ $row['vendas'] }}</td>
                        <td class="num">
                            @php $conv = $row['conv']; @endphp
                            <span class="{{ $conv >= 30 ? 'conv-high' : ($conv >= 10 ? 'conv-mid' : 'conv-low') }}">
                                {{ $conv }}%
                            </span>
                        </td>
                        <td class="num" style="color:#10B981;font-weight:700;">
                            {{ $row['receita'] > 0 ? 'R$ ' . number_format($row['receita'], 0, ',', '.') : '—' }}
                        </td>
                    </tr>
                    @empty
                    <tr class="empty-row">
                        <td colspan="5">
                            <i class="bi bi-person-badge" style="font-size:28px;opacity:.2;display:block;margin-bottom:6px;"></i>
                            Nenhum vendedor com dados no período
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════════════ --}}
    {{-- WHATSAPP — ATENDIMENTO                                      --}}
    {{-- ════════════════════════════════════════════════════════════ --}}
    <div class="report-section">
        <div class="report-section-header">
            <i class="bi bi-whatsapp" style="color:#25D366;"></i>
            WhatsApp — Atendimento
            @if($avgFirstResponse !== null)
            <span style="margin-left:auto;font-size:12px;font-weight:500;color:#9ca3af;">
                Tempo médio de 1ª resposta:
                <strong style="color:#3B82F6;">
                    @if($avgFirstResponse < 60)
                        {{ $avgFirstResponse }} min
                    @else
                        {{ floor($avgFirstResponse / 60) }}h {{ $avgFirstResponse % 60 }}min
                    @endif
                </strong>
                (atendimento humano)
            </span>
            @endif
        </div>
        <div class="report-section-body">
            <div class="wa-kpi-mini-grid">
                <div class="wa-kpi-mini">
                    <div class="wk-label">Conversas iniciadas</div>
                    <div class="wk-value">{{ number_format($waTotal, 0, ',', '.') }}</div>
                    <div class="wk-sub">no período</div>
                </div>
                <div class="wa-kpi-mini">
                    <div class="wk-label">Fechadas</div>
                    <div class="wk-value">{{ number_format($waFechadas, 0, ',', '.') }}</div>
                    <div class="wk-sub">{{ $waTotal > 0 ? round($waFechadas / $waTotal * 100, 1) : 0 }}% do total</div>
                </div>
                <div class="wa-kpi-mini">
                    <div class="wk-label">Viraram lead</div>
                    <div class="wk-value">{{ number_format($waComLead, 0, ',', '.') }}</div>
                    <div class="wk-sub">{{ $waTotal > 0 ? round($waComLead / $waTotal * 100, 1) : 0 }}% do total</div>
                </div>
                <div class="wa-kpi-mini">
                    <div class="wk-label">Atendidas por IA</div>
                    <div class="wk-value">{{ number_format($waIA, 0, ',', '.') }}</div>
                    <div class="wk-sub">{{ $waTotal > 0 ? round($waIA / $waTotal * 100, 1) : 0 }}% do total</div>
                </div>
            </div>

            @if($waMsgByUser->isNotEmpty())
            <div style="font-size:13px;font-weight:700;color:#374151;margin-bottom:12px;">Mensagens enviadas por atendente</div>
            @php $maxMsgs = $waMsgByUser->max('total') ?: 1; @endphp
            @foreach($waMsgByUser as $row)
            <div style="margin-bottom:10px;">
                <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px;">
                    <span style="color:#374151;font-weight:500;">{{ $row->user?->name ?? 'Usuário #' . $row->user_id }}</span>
                    <span style="color:#3B82F6;font-weight:700;">{{ number_format($row->total, 0, ',', '.') }} msgs</span>
                </div>
                <div class="activity-bar-bg">
                    <div class="activity-bar-fill" style="width:{{ round($row->total / $maxMsgs * 100) }}%;"></div>
                </div>
            </div>
            @endforeach
            @else
            <p style="color:#9ca3af;font-size:13px;margin:0;">Nenhuma mensagem enviada por atendentes no período.</p>
            @endif
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════════════ --}}
    {{-- ORIGEM × CONVERSÃO                                          --}}
    {{-- ════════════════════════════════════════════════════════════ --}}
    <div class="report-section">
        <div class="report-section-header">
            <i class="bi bi-diagram-3"></i>
            Origem × Conversão
        </div>
        <div style="overflow-x:auto;">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Origem</th>
                        <th class="num">Leads</th>
                        <th class="num">Vendas</th>
                        <th class="num">Conversão</th>
                        <th class="num">Receita</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sourceConversion as $row)
                    <tr>
                        <td style="font-weight:600;color:#1a1d23;">{{ $row['source'] }}</td>
                        <td class="num">{{ $row['leads'] }}</td>
                        <td class="num">{{ $row['vendas'] }}</td>
                        <td class="num">
                            @php $conv = $row['conv']; @endphp
                            <span class="{{ $conv >= 30 ? 'conv-high' : ($conv >= 10 ? 'conv-mid' : 'conv-low') }}">
                                {{ $conv }}%
                            </span>
                        </td>
                        <td class="num" style="color:#10B981;font-weight:700;">
                            {{ $row['receita'] > 0 ? 'R$ ' . number_format($row['receita'], 0, ',', '.') : '—' }}
                        </td>
                    </tr>
                    @empty
                    <tr class="empty-row">
                        <td colspan="5">Nenhum dado de origem no período</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════════════ --}}
    {{-- ATIVIDADE DA EQUIPE                                         --}}
    {{-- ════════════════════════════════════════════════════════════ --}}
    @if($teamActivity->isNotEmpty())
    <div class="report-section">
        <div class="report-section-header">
            <i class="bi bi-activity"></i>
            Atividade da Equipe
        </div>
        <div style="overflow-x:auto;">
            @php $maxActivity = $teamActivity->max('total') ?: 1; @endphp
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Usuário</th>
                        <th class="num">Msgs WhatsApp</th>
                        <th class="num">Eventos CRM</th>
                        <th class="num">Total</th>
                        <th style="min-width:120px;">Atividade</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($teamActivity as $row)
                    <tr>
                        <td style="font-weight:600;color:#1a1d23;">{{ $row['user']->name }}</td>
                        <td class="num">{{ number_format($row['msgs'], 0, ',', '.') }}</td>
                        <td class="num">{{ number_format($row['events'], 0, ',', '.') }}</td>
                        <td class="num" style="font-weight:700;color:#3B82F6;">{{ number_format($row['total'], 0, ',', '.') }}</td>
                        <td style="padding-right:24px;">
                            <div class="activity-bar-bg">
                                <div class="activity-bar-fill" style="width:{{ round($row['total'] / $maxActivity * 100) }}%;"></div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
// ── Paleta de cores por origem ─────────────────────────────────────────────
const SOURCE_COLORS = {
    'whatsapp':  '#25D366',
    'instagram': '#E1306C',
    'facebook':  '#1877F2',
    'site':      '#3B82F6',
    'google':    '#FBBC04',
    'linkedin':  '#0A66C2',
    'indicacao': '#8B5CF6',
    'manual':    '#94A3B8',
};
const SOURCE_COLORS_FALLBACK = ['#10B981','#F59E0B','#EF4444','#06B6D4','#F97316','#EC4899'];
function sourceColor(name, idx) {
    const key = (name || '').toLowerCase().trim();
    return SOURCE_COLORS[key] ?? SOURCE_COLORS_FALLBACK[idx % SOURCE_COLORS_FALLBACK.length];
}

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
    const grad = ctx.getContext('2d').createLinearGradient(0, 0, 0, 260);
    grad.addColorStop(0, 'rgba(59,130,246,0.35)');
    grad.addColorStop(1, 'rgba(59,130,246,0.00)');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartDates,
            datasets: [{
                label: 'Leads',
                data: chartLeads,
                borderColor: '#3B82F6',
                backgroundColor: grad,
                borderWidth: 2.5,
                pointRadius: chartDates.length > 30 ? 0 : 3,
                pointBackgroundColor: '#3B82F6',
                fill: true,
                stepped: 'after',
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
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
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: sourceLabels,
            datasets: [{
                label: 'Leads',
                data: sourceData,
                backgroundColor: sourceLabels.map((src, i) => sourceColor(src, i)),
                borderRadius: 6,
                borderSkipped: false,
            }],
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
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

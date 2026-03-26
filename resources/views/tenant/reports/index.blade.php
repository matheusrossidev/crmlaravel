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
        background: #0085f3;
        color: #fff;
        border: none;
        border-radius: 100px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        align-self: flex-end;
        transition: background .15s;
    }

    .btn-apply:hover { background: #0070d1; }

    .btn-clear {
        padding: 8px 14px;
        background: #fff;
        color: #6b7280;
        border: 1.5px solid #e8eaf0;
        border-radius: 100px;
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
        display: flex;
        flex-direction: column;
    }
    .kpi-spark { position: relative; height: 48px; margin-top: auto; }

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
        font-size: 11px;
        font-weight: 500;
        opacity: .85;
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
    .lost-grid-3 { display: flex; flex-direction: column; gap: 20px; }

    .report-triple-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        margin-bottom: 22px;
    }
    .report-triple-grid > .report-section { margin-bottom: 0; }
    @media (max-width: 1000px) { .report-triple-grid { grid-template-columns: 1fr; } }

    .report-double-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        margin-bottom: 22px;
    }
    .report-double-grid > .report-section { margin-bottom: 0; }
    @media (max-width: 1000px) { .report-double-grid { grid-template-columns: 1fr; } }

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
        align-items: center;
        gap: 6px;
        color: #d1d5db;
        font-size: 13px;
        line-height: 1;
        padding: 2px 0;
    }
    .funnel-rconnector .conv-rate {
        font-size: 10px;
        font-weight: 600;
        color: #9ca3af;
        background: #f8fafc;
        padding: 1px 8px;
        border-radius: 99px;
        border: 1px solid #e8eaf0;
    }
    .funnel-rconnector .conv-rate.bottleneck {
        color: #EF4444;
        background: #fef2f2;
        border-color: #fecaca;
    }

    /* ── Mobile ── */
    @media (max-width: 768px) {
        .report-filter-wrap { padding: 12px 16px; }
        .report-filter-inner {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            align-items: end;
        }
        .report-filter-inner > div { min-width: 0; }
        .report-filter-inner input,
        .report-filter-inner select {
            width: 100%;
            min-height: 40px;
            font-size: 14px;
            border-radius: 100px;
        }
        .report-filter-actions {
            grid-column: 1 / -1;
            display: flex;
            gap: 8px;
        }
        .btn-apply {
            flex: 1;
            min-height: 42px;
            font-size: 14px;
            justify-content: center;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-clear {
            min-height: 42px;
            font-size: 14px;
        }
    }
    @media (max-width: 900px) {
        .charts-row { grid-template-columns: 1fr !important; }
    }
    @media (max-width: 480px) {
        .report-filter-inner { grid-template-columns: 1fr !important; }
        .kpi-grid { grid-template-columns: 1fr !important; }
        .kpi-value { font-size: 20px; }
        .report-section-body { overflow-x: auto; -webkit-overflow-scrolling: touch; }

        /* Funil stages: scroll horizontal */
        .funnel-scroll-wrap {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .funnel-scroll-wrap > .funnel-scroll-inner {
            min-width: 700px;
        }

        /* Campanhas + Pipeline: stack */
        .report-double-grid,
        .report-triple-grid { grid-template-columns: 1fr !important; }

        /* Tabelas: scroll */
        .report-table { min-width: 500px; }
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
                <select name="campaign_id">
                    <option value="">Todas</option>
                    @foreach($campaigns as $camp)
                    <option value="{{ $camp->id }}" @selected($filterCampaign == $camp->id)>{{ $camp->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label>Pipeline</label>
                <select name="pipeline_id">
                    <option value="">Todos</option>
                    @foreach($pipelines as $pipe)
                    <option value="{{ $pipe->id }}" @selected($filterPipeline == $pipe->id)>{{ $pipe->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="report-filter-actions">
                <button type="submit" class="btn-apply">
                    <i class="bi bi-funnel"></i> Aplicar
                </button>

                @if($filterCampaign || $filterPipeline || $filterUser || request('date_from') || request('date_to'))
                <a href="{{ route('reports.index') }}" class="btn-clear">
                    <i class="bi bi-x"></i> Limpar
                </a>
                @endif

                <a href="{{ route('reports.pdf', request()->query()) }}" class="btn-apply" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
                    <i class="bi bi-download"></i> Baixar relatório
                </a>
            </div>

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
                <i class="bi bi-arrow-{{ $deltaLeads >= 0 ? 'up' : 'down' }}"></i> {{ abs($deltaLeads) }}% vs período anterior
            </div>
            @else
            <div class="kpi-delta neu">Sem dados anteriores</div>
            @endif
            <div class="kpi-spark"><canvas id="sparkLeads"></canvas></div>
        </div>

        {{-- Receita --}}
        <div class="kpi-card">
            <div class="kpi-label"><i class="bi bi-cash-stack" style="color:#10B981;"></i> Receita</div>
            <div class="kpi-value" style="color:#10B981;">R$ {{ number_format($totalRevenue, 0, ',', '.') }}</div>
            @if($deltaRevenue !== null)
            <div class="kpi-delta {{ $deltaRevenue >= 0 ? 'up' : 'down' }}">
                <i class="bi bi-arrow-{{ $deltaRevenue >= 0 ? 'up' : 'down' }}"></i> {{ abs($deltaRevenue) }}% vs período anterior
            </div>
            @else
            <div class="kpi-delta neu">Sem dados anteriores</div>
            @endif
            <div class="kpi-spark"><canvas id="sparkRevenue"></canvas></div>
        </div>

        {{-- Ticket Médio --}}
        <div class="kpi-card">
            <div class="kpi-label"><i class="bi bi-tag" style="color:#8B5CF6;"></i> Ticket Médio</div>
            <div class="kpi-value" style="color:#8B5CF6;">R$ {{ number_format($avgTicket, 0, ',', '.') }}</div>
            <div class="kpi-delta neu">{{ $salesCount }} venda(s) no período</div>
            <div class="kpi-spark"><canvas id="sparkTicket"></canvas></div>
        </div>

        {{-- Taxa de Conversão --}}
        <div class="kpi-card">
            <div class="kpi-label"><i class="bi bi-arrow-up-right-circle" style="color:#F59E0B;"></i> Taxa de Conversão</div>
            <div class="kpi-value" style="color:#F59E0B;">{{ number_format((float)$convRate, 1, ',', '.') }}%</div>
            <div class="kpi-delta neu">Leads → Vendas</div>
            <div class="kpi-spark"><canvas id="sparkConv"></canvas></div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════════════ --}}
    {{-- Gráficos — Linha + Origem                                   --}}
    {{-- ════════════════════════════════════════════════════════════ --}}
    <div class="charts-row" style="margin-bottom:22px;">

        <div class="chart-box">
            <div class="chart-title">Leads por dia</div>
            <div style="display:flex;gap:12px;margin-bottom:12px;font-size:12px;color:#6b7280;">
                <span style="display:flex;align-items:center;gap:4px;">
                    <span style="width:10px;height:10px;border-radius:2px;background:#3B82F6;"></span> Leads por dia
                </span>
                <span style="display:flex;align-items:center;gap:4px;">
                    <span style="display:inline-block;width:16px;border-top:1.5px dashed #F59E0B;"></span> Média 7 dias
                </span>
            </div>
            <div style="position:relative;height:200px;">
                <canvas id="chartLeadsByDay"></canvas>
            </div>
        </div>

        <div class="chart-box">
            <div class="chart-title">Leads por origem</div>
            <div style="position:relative;height:200px;">
                <canvas id="chartLeadsBySource"></canvas>
            </div>
        </div>

    </div>

    {{-- ════════════════════════════════════════════════════════════ --}}
    {{-- FUNIL DE CONVERSÃO (100% width + canvas stream)            --}}
    {{-- ════════════════════════════════════════════════════════════ --}}
    <div class="report-section">
        <div class="report-section-header">
            <i class="bi bi-funnel"></i>
            Funil de Conversão
        </div>
        <div style="padding:0;">
            @php $funnelPipe = $pipelineRows->first(fn($r) => $r['stages']->isNotEmpty()); @endphp
            @if($funnelPipe)
                @php
                    $stagesArr = $funnelPipe['stages']->values();
                    $funnelMax = max($stagesArr->max('count'), 1);
                @endphp
                <div class="funnel-scroll-wrap"><div class="funnel-scroll-inner">
                <div style="display:grid;grid-template-columns:repeat({{ $stagesArr->count() }}, 1fr);width:100%;">
                    @foreach($stagesArr as $i => $stageRow)
                    @php
                        $stage   = $stageRow['stage'];
                        $count   = $stageRow['count'];
                        $avgDays = $stageRow['avg_days'] ?? null;
                        $color   = $stage->color ?? '#3B82F6';
                        $prevCount = $i > 0 ? $stagesArr[$i - 1]['count'] : null;
                        $convRate  = $prevCount && $prevCount > 0 ? round($count / $prevCount * 100) : null;
                        $totalStages = $stagesArr->sum('count') ?: 1;
                        $stagePct = round($count / $totalStages * 100);
                    @endphp
                    <div style="padding:14px 12px;{{ $i > 0 ? 'border-left:1px solid #f0f2f7;' : '' }}">
                        <div style="display:flex;align-items:center;gap:5px;margin-bottom:6px;">
                            <span style="width:8px;height:8px;border-radius:2px;background:{{ $color }};"></span>
                            <span style="font-size:11px;font-weight:600;color:#6b7280;white-space:nowrap;">{{ $stage->name }}</span>
                            @if($stage->is_won)  <span class="badge-won" style="font-size:8px;">GANHO</span> @endif
                            @if($stage->is_lost) <span class="badge-lost" style="font-size:8px;">PERDIDO</span> @endif
                        </div>
                        <div style="font-size:20px;font-weight:800;color:{{ $count === 0 ? '#d1d5db' : '#1a1d23' }};margin-bottom:6px;">{{ $count }}</div>
                        <div style="display:flex;flex-direction:column;gap:3px;margin-bottom:6px;">
                            @if($convRate !== null)
                            <span style="font-size:10px;font-weight:600;color:{{ $convRate === 0 ? '#EF4444' : ($convRate < 50 ? '#EF4444' : ($convRate < 80 ? '#F59E0B' : '#10B981')) }};{{ $convRate === 0 ? 'background:#fef2f2;padding:1px 6px;border-radius:4px;' : '' }}">
                                <i class="bi bi-arrow-right" style="font-size:9px;"></i> {{ $convRate }}% da anterior
                            </span>
                            @endif
                            @if($avgDays !== null)
                            <span style="font-size:10px;color:#9ca3af;">
                                <i class="bi bi-clock" style="font-size:9px;"></i> {{ $avgDays }}d média
                            </span>
                            @endif
                        </div>
                        <div style="height:24px;background:{{ $color }}15;border-radius:99px;display:flex;align-items:center;justify-content:center;">
                            <span style="font-size:10px;font-weight:700;color:{{ $color }};">{{ $stagePct }}%</span>
                        </div>
                    </div>
                    @endforeach
                </div>
                {{-- Canvas stream --}}
                <div style="height:180px;overflow:hidden;border-radius:0 0 14px 14px;background:#f8fafc;position:relative;">
                    <div style="position:absolute;inset:0;display:grid;grid-template-columns:repeat({{ $stagesArr->count() }}, 1fr);pointer-events:none;z-index:1;">
                        @foreach($stagesArr as $idx => $s)
                        <div style="{{ $idx > 0 ? 'border-left:1px solid rgba(0,0,0,.06);' : '' }}"></div>
                        @endforeach
                    </div>
                    <canvas id="reportFunnelCanvas" style="width:100%;height:180px;display:block;position:relative;z-index:0;"></canvas>
                </div>
                </div></div>{{-- /funnel-scroll-wrap --}}
            @else
                <div class="report-section-body">
                    <p style="color:#9ca3af;font-size:13px;text-align:center;padding:24px 0;">Nenhum pipeline com dados.</p>
                </div>
            @endif
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════════════ --}}
    {{-- CAMPANHAS + PIPELINE (lado a lado)                         --}}
    {{-- ════════════════════════════════════════════════════════════ --}}
    <div class="report-double-grid">

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
                            <th class="num">Leads</th>
                            <th class="num">Conv.</th>
                            <th class="num">Conv.%</th>
                            <th class="num">Receita</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $maxCampLeads = $campaignRows->max('leads_count') ?: 1; @endphp
                        @forelse($campaignRows as $row)
                        @php
                            $convColor = $row['conv'] == 0 ? '#9ca3af' : ($row['conv'] <= 30 ? '#EF4444' : ($row['conv'] <= 70 ? '#F59E0B' : '#10B981'));
                            $convBg    = $row['conv'] == 0 ? '#f3f4f6' : ($row['conv'] <= 30 ? '#fef2f2' : ($row['conv'] <= 70 ? '#fff7ed' : '#f0fdf4'));
                            $isBest    = $row['leads_count'] === $maxCampLeads && $row['leads_count'] > 0;
                        @endphp
                        <tr style="{{ $isBest ? 'background:#f8fafc;' : '' }}">
                            <td style="font-size:12px;">
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <div style="width:28px;height:28px;border-radius:7px;background:#eff6ff;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                        <i class="bi bi-megaphone" style="font-size:12px;color:#3B82F6;"></i>
                                    </div>
                                    <div>
                                        <span style="font-weight:600;color:#1a1d23;">{{ $row['name'] }}</span><br>
                                        <span style="font-size:10px;color:#9ca3af;">{{ $row['source'] }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="num" style="font-weight:700;color:#3B82F6;">{{ $row['leads_count'] }}</td>
                            <td class="num" style="font-weight:600;">{{ $row['sales_count'] }}</td>
                            <td class="num">
                                <span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:99px;background:{{ $convBg }};color:{{ $convColor }};">{{ $row['conv'] }}%</span>
                            </td>
                            <td class="num" style="font-weight:700;color:#1a1d23;">{{ $row['revenue'] > 0 ? 'R$ '.number_format($row['revenue'], 0, ',', '.') : '—' }}</td>
                        </tr>
                        @empty
                        <tr class="empty-row">
                            <td colspan="5">
                                <i class="bi bi-megaphone" style="font-size:28px;opacity:.2;display:block;margin-bottom:6px;"></i>
                                Nenhuma campanha com dados
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pipeline / Etapas --}}
        <div class="report-section" style="margin-bottom:0;">
            @php $firstPipe = $pipelineRows->first(); @endphp
            @if($firstPipe)
            <div class="report-section-header">
                <i class="bi bi-filter-left"></i>
                Funil: {{ $firstPipe['pipeline']->name }}
                <span style="margin-left:auto;font-size:12px;font-weight:500;color:#9ca3af;">
                    {{ $firstPipe['total'] }} lead(s)
                </span>
            </div>
            <div style="overflow-x:auto;">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Etapa</th>
                            <th class="num">Leads</th>
                            <th class="num">%</th>
                            <th>Visualização</th>
                            <th class="num">Tempo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $maxStageCount = $firstPipe['stages']->max('count') ?: 1; @endphp
                        @forelse($firstPipe['stages'] as $stageRow)
                        @php
                            $stage     = $stageRow['stage'];
                            $count     = $stageRow['count'];
                            $avgDays   = $stageRow['avg_days'] ?? null;
                            $pipeTotal = $firstPipe['total'] ?: 1;
                            $pct       = round($count / $pipeTotal * 100, 1);
                            $color     = $stage->color ?? '#3B82F6';
                            $isMax     = $count === $maxStageCount && $count > 0;
                            // Format time — always in days for consistency
                            $timeStr = '—';
                            if ($avgDays !== null) {
                                if ($avgDays < 1) {
                                    $timeStr = '< 1d';
                                } else {
                                    $timeStr = round($avgDays) . 'd';
                                }
                            }
                        @endphp
                        <tr style="{{ $isMax ? 'background:#f0f4ff;' : '' }}">
                            <td>
                                <div style="display:flex;align-items:center;gap:6px;">
                                    <span style="width:8px;height:8px;border-radius:2px;background:{{ $color }};flex-shrink:0;"></span>
                                    <span style="font-size:12px;font-weight:{{ $isMax ? '700' : '500' }};">{{ $stage->name }}</span>
                                    @if($stage->is_won)  <span class="badge-won" style="font-size:8px;">GANHO</span>  @endif
                                    @if($stage->is_lost) <span class="badge-lost" style="font-size:8px;">PERDIDO</span> @endif
                                    @if($isMax) <span style="font-size:9px;color:#3B82F6;font-weight:700;">★</span> @endif
                                </div>
                            </td>
                            <td class="num" style="font-weight:700;color:#1a1d23;">{{ $count }}</td>
                            <td class="num">
                                <div>
                                    <span style="font-size:11px;font-weight:600;color:#374151;">{{ $pct }}%</span>
                                    <div style="margin-top:3px;height:6px;border-radius:3px;background:#f0f2f7;width:60px;">
                                        <div style="height:6px;border-radius:3px;width:{{ max($pct, 3) }}%;background:{{ $color }};min-width:3px;"></div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding-right:12px;">
                                <div style="height:6px;border-radius:3px;background:{{ $color }}15;width:100%;">
                                    <div style="height:6px;border-radius:3px;width:{{ $pct }}%;background:{{ $color }};transition:width .5s;"></div>
                                </div>
                            </td>
                            <td class="num" style="font-size:11px;color:#9ca3af;white-space:nowrap;">
                                {{ $timeStr }}
                            </td>
                        </tr>
                        @empty
                        <tr class="empty-row"><td colspan="5">Nenhuma etapa configurada</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @else
            <div class="report-section-header"><i class="bi bi-filter-left"></i> Pipeline / Funil</div>
            <div class="report-section-body" style="text-align:center;color:#9ca3af;padding:40px;">
                Nenhum pipeline configurado.
            </div>
            @endif
        </div>

    </div>

    {{-- ════════════════════════════════════════════════════════════ --}}
    {{-- PERDIDOS + VENDEDOR + WHATSAPP (3 colunas)                  --}}
    {{-- ════════════════════════════════════════════════════════════ --}}
    <div class="report-triple-grid">
    <div class="report-section">
        <div class="report-section-header">
            <i class="bi bi-x-circle" style="color:#EF4444;"></i>
            Leads Perdidos
            <span style="margin-left:auto;font-size:12px;font-weight:500;color:#9ca3af;">
                {{ $totalLost }} perda(s) · Valor potencial:
                <strong style="color:#EF4444;">R$ {{ number_format($lostPotentialValue, 0, ',', '.') }}</strong>
            </span>
        </div>

        @if($totalLost === 0)
        <div style="text-align:center;padding:40px 20px;color:#10B981;">
            <i class="bi bi-shield-check" style="font-size:32px;display:block;margin-bottom:8px;opacity:.5;"></i>
            <span style="font-size:13px;font-weight:600;">Nenhuma perda no período — excelente!</span>
        </div>
        @else
        <div class="report-section-body lost-grid-3">

            {{-- Por Motivo (Heatmap) --}}
            <div>
                <div style="font-size:13px;font-weight:700;color:#374151;margin-bottom:14px;">Por Motivo</div>
                @php $maxReason = collect($lostByReason)->max('total') ?: 1; @endphp
                @forelse($lostByReason as $row)
                @php
                    $ratio = $row['total'] / $maxReason;
                    $heatBg = $ratio >= 0.8 ? '#A32D2D' : ($ratio >= 0.6 ? '#E24B4A' : ($ratio >= 0.4 ? '#F09595' : ($ratio >= 0.2 ? '#F7C1C1' : '#FCEBEB')));
                    $heatFg = $ratio >= 0.6 ? '#fff' : '#A32D2D';
                @endphp
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                    <span style="font-size:12px;color:#374151;font-weight:500;flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $row['reason'] }}</span>
                    <span style="min-width:42px;height:26px;border-radius:5px;background:{{ $heatBg }};color:{{ $heatFg }};font-size:12px;font-weight:600;display:flex;align-items:center;justify-content:center;">{{ $row['total'] }}</span>
                    <span style="font-size:10px;color:#9ca3af;min-width:32px;text-align:right;">{{ $row['pct'] }}%</span>
                </div>
                @empty
                <p style="color:#9ca3af;font-size:13px;">—</p>
                @endforelse
                @if(count($lostByReason) > 0)
                <div style="display:flex;align-items:center;gap:5px;margin-top:12px;">
                    <span style="font-size:10px;color:#9ca3af;">menos</span>
                    <div style="width:12px;height:8px;border-radius:2px;background:#FCEBEB;"></div>
                    <div style="width:12px;height:8px;border-radius:2px;background:#F7C1C1;"></div>
                    <div style="width:12px;height:8px;border-radius:2px;background:#F09595;"></div>
                    <div style="width:12px;height:8px;border-radius:2px;background:#E24B4A;"></div>
                    <div style="width:12px;height:8px;border-radius:2px;background:#A32D2D;"></div>
                    <span style="font-size:10px;color:#9ca3af;">mais</span>
                </div>
                @endif
            </div>

            {{-- Por Campanha (heatmap) --}}
            <div>
                <div style="font-size:13px;font-weight:700;color:#374151;margin-bottom:14px;">Por Campanha</div>
                @php $maxCampLost = collect($lostByCampaign)->max('total') ?: 1; @endphp
                @forelse($lostByCampaign as $row)
                @php
                    $cRatio = $row['total'] / $maxCampLost;
                    $cHeatBg = $cRatio >= 0.8 ? '#A32D2D' : ($cRatio >= 0.6 ? '#E24B4A' : ($cRatio >= 0.4 ? '#F09595' : ($cRatio >= 0.2 ? '#F7C1C1' : '#FCEBEB')));
                    $cHeatFg = $cRatio >= 0.6 ? '#fff' : '#A32D2D';
                @endphp
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                    <span style="font-size:12px;color:#374151;font-weight:500;flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $row['campaign'] }}</span>
                    <span style="min-width:36px;height:26px;border-radius:5px;background:{{ $cHeatBg }};color:{{ $cHeatFg }};font-size:12px;font-weight:600;display:flex;align-items:center;justify-content:center;">{{ $row['total'] }}</span>
                </div>
                @empty
                <p style="color:#9ca3af;font-size:13px;">—</p>
                @endforelse
            </div>

            {{-- Por Vendedor (avatar + barra azul neutra) --}}
            <div>
                <div style="font-size:13px;font-weight:700;color:#374151;margin-bottom:14px;">Por Vendedor</div>
                @php $maxVendLost = collect($lostByVendedor)->max('total') ?: 1; @endphp
                @forelse($lostByVendedor as $row)
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
                    <div style="width:32px;height:32px;border-radius:50%;background:#eff6ff;color:#3B82F6;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:600;flex-shrink:0;">
                        {{ strtoupper(substr($row['user'], 0, 2)) }}
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:3px;">
                            <span style="color:#374151;font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $row['user'] }}</span>
                            <span style="font-weight:700;color:#374151;flex-shrink:0;">{{ $row['total'] }} <span style="color:#9ca3af;font-weight:400;font-size:10px;">({{ $row['pct'] }}%)</span></span>
                        </div>
                        <div style="height:5px;border-radius:3px;background:#eff6ff;">
                            <div style="height:5px;border-radius:3px;background:#3B82F6;width:{{ round($row['total'] / $maxVendLost * 100) }}%;"></div>
                        </div>
                    </div>
                </div>
                @empty
                <p style="color:#9ca3af;font-size:13px;">—</p>
                @endforelse
            </div>

        </div>
        @endif
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
                        <th class="num">Leads</th>
                        <th class="num">Vendas</th>
                        <th class="num">Conversão</th>
                        <th class="num">Receita</th>
                    </tr>
                </thead>
                <tbody>
                    @php $topConv = collect($vendedores)->max('conv'); @endphp
                    @forelse($vendedores as $row)
                    @php
                        $conv = $row['conv'];
                        $isTop = $conv === $topConv && $conv > 0;
                        $initials = collect(explode(' ', $row['user']->name))->map(fn($w) => mb_strtoupper(mb_substr($w,0,1)))->take(2)->join('');
                        $convColor = $conv >= 30 ? '#10B981' : ($conv >= 10 ? '#F59E0B' : '#9ca3af');
                        $convBg    = $conv >= 30 ? '#f0fdf4' : ($conv >= 10 ? '#fff7ed' : '#f3f4f6');
                    @endphp
                    <tr style="{{ $isTop ? 'background:#f0fdf4;' : '' }}">
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div style="width:32px;height:32px;border-radius:50%;background:#eff6ff;color:#3B82F6;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:600;flex-shrink:0;">
                                    {{ $initials }}
                                </div>
                                <div>
                                    <span style="font-weight:600;color:#1a1d23;font-size:13px;">{{ $row['user']->name }}</span>
                                    @if($isTop)
                                    <span style="font-size:10px;font-weight:700;color:#F59E0B;margin-left:4px;">★ Top</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="num" style="font-weight:700;color:#3B82F6;">{{ $row['leads'] }}</td>
                        <td class="num" style="font-weight:600;">{{ $row['vendas'] }}</td>
                        <td class="num">
                            <div>
                                <span style="font-size:12px;font-weight:600;padding:2px 8px;border-radius:99px;background:{{ $convBg }};color:{{ $convColor }};">{{ $conv }}%</span>
                                <div style="margin-top:4px;height:4px;border-radius:2px;background:#f0f2f7;width:70px;">
                                    <div style="height:4px;border-radius:2px;width:{{ min($conv, 100) }}%;background:{{ $convColor }};"></div>
                                </div>
                            </div>
                        </td>
                        <td class="num" style="font-weight:700;color:#1a1d23;">
                            {{ $row['receita'] > 0 ? 'R$ ' . number_format($row['receita'], 2, ',', '.') : '—' }}
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
                1ª resposta:
                <strong style="color:#3B82F6;">
                    @if($avgFirstResponse < 60)
                        {{ $avgFirstResponse }} min
                    @else
                        {{ floor($avgFirstResponse / 60) }}h {{ $avgFirstResponse % 60 }}min
                    @endif
                </strong>
            </span>
            @endif
        </div>
        <div class="report-section-body">
            @php
                $waRest = max($waTotal - $waFechadas - $waComLead, 0);
                $pctLead = $waTotal > 0 ? round($waComLead / $waTotal * 100, 1) : 0;
                $pctFech = $waTotal > 0 ? round($waFechadas / $waTotal * 100, 1) : 0;
                $pctRest = max(100 - $pctLead - $pctFech, 0);
            @endphp
            <div style="display:flex;align-items:center;gap:24px;margin-bottom:20px;">
                {{-- Mini donut SVG --}}
                <div style="flex-shrink:0;">
                    @php
                        $pctIA   = $waTotal > 0 ? round($waIA / $waTotal * 100, 1) : 0;
                        $r = 34; $cx = 48; $cy = 48; $circ = 2 * M_PI * $r;
                        $dLead = ($pctLead / 100) * $circ;
                        $dFech = ($pctFech / 100) * $circ;
                        $dIA   = ($pctIA / 100) * $circ;
                        $dRest = max($circ - $dLead - $dFech - $dIA, 0);
                        $off1 = 0; $off2 = $dLead; $off3 = $dLead + $dFech; $off4 = $dLead + $dFech + $dIA;
                    @endphp
                    <svg width="96" height="96" viewBox="0 0 96 96">
                        <circle cx="{{ $cx }}" cy="{{ $cy }}" r="{{ $r }}" fill="none" stroke="#10B981" stroke-width="12"
                            stroke-dasharray="{{ $dLead }} {{ $circ - $dLead }}" stroke-dashoffset="{{ -$off1 }}"
                            style="transform:rotate(-90deg);transform-origin:center;"/>
                        <circle cx="{{ $cx }}" cy="{{ $cy }}" r="{{ $r }}" fill="none" stroke="#3B82F6" stroke-width="12"
                            stroke-dasharray="{{ $dFech }} {{ $circ - $dFech }}" stroke-dashoffset="{{ -$off2 }}"
                            style="transform:rotate(-90deg);transform-origin:center;"/>
                        <circle cx="{{ $cx }}" cy="{{ $cy }}" r="{{ $r }}" fill="none" stroke="#F59E0B" stroke-width="12"
                            stroke-dasharray="{{ $dIA }} {{ $circ - $dIA }}" stroke-dashoffset="{{ -$off3 }}"
                            style="transform:rotate(-90deg);transform-origin:center;"/>
                        <circle cx="{{ $cx }}" cy="{{ $cy }}" r="{{ $r }}" fill="none" stroke="#e8eaf0" stroke-width="12"
                            stroke-dasharray="{{ $dRest }} {{ $circ - $dRest }}" stroke-dashoffset="{{ -$off4 }}"
                            style="transform:rotate(-90deg);transform-origin:center;"/>
                        <text x="{{ $cx }}" y="{{ $cy }}" text-anchor="middle" font-size="16" font-weight="700" fill="#1a1d23">{{ $waTotal }}</text>
                        <text x="{{ $cx }}" y="{{ $cy + 12 }}" text-anchor="middle" font-size="8" fill="#9ca3af">conversas</text>
                    </svg>
                </div>
                {{-- KPI list --}}
                <div style="display:flex;flex-direction:column;gap:8px;flex:1;">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span style="width:8px;height:8px;border-radius:2px;background:#10B981;"></span>
                        <span style="font-size:12px;color:#6b7280;flex:1;">Viraram lead</span>
                        <span style="font-size:13px;font-weight:700;color:#1a1d23;">{{ $waComLead }}</span>
                        <span style="font-size:11px;color:#10B981;font-weight:600;min-width:40px;text-align:right;">{{ $pctLead }}%</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span style="width:8px;height:8px;border-radius:2px;background:#3B82F6;"></span>
                        <span style="font-size:12px;color:#6b7280;flex:1;">Fechadas</span>
                        <span style="font-size:13px;font-weight:700;color:#1a1d23;">{{ $waFechadas }}</span>
                        <span style="font-size:11px;color:#3B82F6;font-weight:600;min-width:40px;text-align:right;">{{ $pctFech }}%</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span style="width:8px;height:8px;border-radius:2px;background:#e8eaf0;"></span>
                        <span style="font-size:12px;color:#6b7280;flex:1;">Apenas conversa</span>
                        <span style="font-size:13px;font-weight:700;color:#1a1d23;">{{ $waRest }}</span>
                        <span style="font-size:11px;color:#9ca3af;font-weight:600;min-width:40px;text-align:right;">{{ number_format($pctRest, 1, ',', '.') }}%</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span style="width:8px;height:8px;border-radius:2px;background:#F59E0B;"></span>
                        <span style="font-size:12px;color:#6b7280;flex:1;">Atendidas por IA</span>
                        <span style="font-size:13px;font-weight:700;color:#1a1d23;">{{ $waIA }}</span>
                        <span style="font-size:11px;color:#F59E0B;font-weight:600;min-width:40px;text-align:right;">{{ $waTotal > 0 ? round($waIA / $waTotal * 100, 1) : 0 }}%</span>
                    </div>
                </div>
            </div>

            @if($waMsgByUser->isNotEmpty())
            <div style="font-size:13px;font-weight:700;color:#374151;margin-bottom:12px;">Mensagens por atendente</div>
            @php $maxMsgs = $waMsgByUser->max('total') ?: 1; @endphp
            @foreach($waMsgByUser as $row)
            @php
                $userName = $row->user?->name ?? 'Usuário #' . $row->user_id;
                $userInit = collect(explode(' ', $userName))->map(fn($w) => mb_strtoupper(mb_substr($w,0,1)))->take(2)->join('');
            @endphp
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                <div style="width:28px;height:28px;border-radius:50%;background:#f0fdf4;color:#25D366;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:600;flex-shrink:0;">
                    {{ $userInit }}
                </div>
                <span style="font-size:12px;color:#374151;font-weight:500;width:100px;flex-shrink:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $userName }}</span>
                <div style="flex:1;height:6px;border-radius:3px;background:#f0f2f7;">
                    <div style="height:6px;border-radius:3px;background:#25D366;width:{{ round($row->total / $maxMsgs * 100) }}%;transition:width .5s;"></div>
                </div>
                <span style="font-size:12px;font-weight:700;color:#1a1d23;min-width:55px;text-align:right;">{{ number_format($row->total, 0, ',', '.') }} msgs</span>
            </div>
            @endforeach
            @else
            <div style="text-align:center;padding:24px 0;color:#9ca3af;">
                <i class="bi bi-chat-dots" style="font-size:24px;opacity:.3;display:block;margin-bottom:6px;"></i>
                <span style="font-size:13px;">Nenhuma mensagem enviada por atendentes no período.</span>
            </div>
            @endif
        </div>
    </div>

    </div>{{-- /report-triple-grid --}}

    {{-- ════════════════════════════════════════════════════════════ --}}
    {{-- BOTÃO WHATSAPP — CLIQUES                                    --}}
    {{-- ════════════════════════════════════════════════════════════ --}}
    <div class="report-section">
        <div class="report-section-header">
            <i class="bi bi-phone" style="color:#25D366;"></i>
            Botão WhatsApp — Cliques
            <span style="margin-left:auto;font-size:12px;font-weight:500;color:#9ca3af;">
                {{ $waClicksTotal }} clique(s) no período
            </span>
        </div>
        @if($waClicksTotal > 0)
        <div class="report-section-body">
            {{-- Mini KPIs --}}
            @php
                $waMatchRate = $waClicksTotal > 0 ? round($waClicksMatched / $waClicksTotal * 100, 1) : 0;
                $waMobilePct = $waClicksTotal > 0 ? round($waClicksMobile / $waClicksTotal * 100, 1) : 0;
            @endphp
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:18px;">
                <div style="background:#f8fafc;border-radius:8px;padding:12px 14px;">
                    <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;">Total cliques</div>
                    <div style="font-size:22px;font-weight:700;color:#1a1d23;">{{ number_format($waClicksTotal, 0, ',', '.') }}</div>
                </div>
                <div style="background:#f8fafc;border-radius:8px;padding:12px 14px;">
                    <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;">Matched</div>
                    <div style="font-size:22px;font-weight:700;color:#10B981;">{{ $waClicksMatched }}</div>
                    <div style="font-size:11px;color:#10B981;font-weight:600;">{{ $waMatchRate }}% do total</div>
                </div>
                <div style="background:#f8fafc;border-radius:8px;padding:12px 14px;">
                    <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;">Taxa de match</div>
                    @php
                        $matchColor = $waMatchRate >= 50 ? '#10B981' : ($waMatchRate >= 20 ? '#F59E0B' : '#EF4444');
                        $matchBg = $waMatchRate >= 50 ? '#f0fdf4' : ($waMatchRate >= 20 ? '#fff7ed' : '#fef2f2');
                    @endphp
                    <div style="font-size:22px;font-weight:700;color:{{ $matchColor }};">{{ $waMatchRate }}%</div>
                </div>
                <div style="background:#f8fafc;border-radius:8px;padding:12px 14px;">
                    <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;">Mobile</div>
                    <div style="font-size:22px;font-weight:700;color:#3B82F6;">{{ $waMobilePct }}%</div>
                    <div style="font-size:11px;color:#9ca3af;">{{ $waClicksMobile }} de {{ $waClicksTotal }}</div>
                </div>
            </div>

            {{-- Chart cliques por dia --}}
            @if(count($waClicksByDay) > 1)
            <div style="margin-bottom:18px;">
                <div style="font-size:13px;font-weight:700;color:#374151;margin-bottom:8px;">Cliques por dia</div>
                <div style="position:relative;height:120px;">
                    <canvas id="chartWaClicks"></canvas>
                </div>
            </div>
            @endif

            {{-- Por Origem + Por Página lado a lado --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                <div>
                    <div style="font-size:13px;font-weight:700;color:#374151;margin-bottom:10px;">Por Origem</div>
                    @php $maxSrc = count($waClicksBySource) ? max($waClicksBySource) : 1; @endphp
                    @forelse($waClicksBySource as $src => $total)
                    @php
                        $ratio = $total / $maxSrc;
                        $hBg = $ratio >= 0.8 ? '#065f46' : ($ratio >= 0.6 ? '#10B981' : ($ratio >= 0.4 ? '#6ee7b7' : ($ratio >= 0.2 ? '#a7f3d0' : '#d1fae5')));
                        $hFg = $ratio >= 0.6 ? '#fff' : '#065f46';
                    @endphp
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:5px;">
                        <span style="font-size:12px;color:#374151;font-weight:500;flex:1;">{{ ucfirst($src) }}</span>
                        <span style="min-width:36px;height:24px;border-radius:5px;background:{{ $hBg }};color:{{ $hFg }};font-size:11px;font-weight:600;display:flex;align-items:center;justify-content:center;">{{ $total }}</span>
                    </div>
                    @empty
                    <p style="color:#9ca3af;font-size:12px;">—</p>
                    @endforelse
                </div>
                <div>
                    <div style="font-size:13px;font-weight:700;color:#374151;margin-bottom:10px;">Por Página</div>
                    @php $maxPg = count($waClicksByPage) ? max($waClicksByPage) : 1; @endphp
                    @forelse($waClicksByPage as $page => $total)
                    @php
                        $pageShort = strlen($page) > 40 ? '...' . substr($page, -37) : $page;
                        $parsed = parse_url($page);
                        $pagePath = ($parsed['path'] ?? '/') . (isset($parsed['query']) ? '?' . substr($parsed['query'], 0, 20) : '');
                    @endphp
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:5px;">
                        <span style="font-size:12px;color:#374151;font-weight:500;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $page }}">{{ $pagePath }}</span>
                        <span style="font-size:12px;font-weight:700;color:#3B82F6;flex-shrink:0;">{{ $total }}</span>
                    </div>
                    @empty
                    <p style="color:#9ca3af;font-size:12px;">—</p>
                    @endforelse
                </div>
            </div>
        </div>
        @else
        <div style="text-align:center;padding:32px 20px;color:#9ca3af;">
            <i class="bi bi-phone" style="font-size:28px;opacity:.3;display:block;margin-bottom:6px;"></i>
            <span style="font-size:13px;">Nenhum clique no botão WhatsApp no período.</span>
        </div>
        @endif
    </div>

    {{-- ════════════════════════════════════════════════════════════ --}}
    {{-- ORIGEM × CONVERSÃO + PRODUTOS (2 colunas)                   --}}
    {{-- ════════════════════════════════════════════════════════════ --}}
    <div class="report-double-grid">
    <div class="report-section" style="margin-bottom:0;">
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
                    @php
                        $srcDisplayNames = ['indicacao' => 'Indicação', 'manual' => 'Manual', 'whatsapp' => 'WhatsApp', 'instagram' => 'Instagram', 'facebook' => 'Facebook', 'google' => 'Google', 'linkedin' => 'LinkedIn', 'site' => 'Site', 'telefone' => 'Telefone', 'email' => 'Email'];
                        $srcColors = ['whatsapp' => '#25D366', 'instagram' => '#E1306C', 'facebook' => '#1877F2', 'site' => '#3B82F6', 'google' => '#FBBC04', 'linkedin' => '#0A66C2', 'indicacao' => '#8B5CF6', 'manual' => '#94A3B8', 'telefone' => '#F97316', 'email' => '#06B6D4'];
                        $srcFallback = ['#10B981','#F59E0B','#EF4444','#06B6D4','#F97316','#EC4899'];
                    @endphp
                    @forelse($sourceConversion as $si => $row)
                    @php
                        $conv = $row['conv'];
                        $convColor = $conv == 0 ? '#9ca3af' : ($conv <= 30 ? '#EF4444' : ($conv <= 70 ? '#F59E0B' : '#10B981'));
                        $convBg    = $conv == 0 ? '#f3f4f6' : ($conv <= 30 ? '#fef2f2' : ($conv <= 70 ? '#fff7ed' : '#f0fdf4'));
                        $srcKey = strtolower(trim($row['source'] ?? ''));
                        $srcColor = $srcColors[$srcKey] ?? ($srcFallback[$si % count($srcFallback)]);
                        $srcName = $srcDisplayNames[$srcKey] ?? ucfirst($row['source'] ?? 'Desconhecido');
                    @endphp
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <span style="width:8px;height:8px;border-radius:2px;background:{{ $srcColor }};flex-shrink:0;"></span>
                                <span style="font-weight:600;color:#1a1d23;">{{ $srcName }}</span>
                            </div>
                        </td>
                        <td class="num" style="font-weight:700;color:#3B82F6;">{{ $row['leads'] }}</td>
                        <td class="num" style="font-weight:600;">{{ $row['vendas'] }}</td>
                        <td class="num">
                            <span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:99px;background:{{ $convBg }};color:{{ $convColor }};">{{ number_format((float)$conv, 1, ',', '.') }}%</span>
                        </td>
                        <td class="num" style="font-weight:700;color:#1a1d23;">
                            {{ $row['receita'] > 0 ? 'R$ ' . number_format($row['receita'], 2, ',', '.') : '—' }}
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
    {{-- PRODUTOS MAIS VENDIDOS (lado a lado com Origem acima)       --}}
    {{-- ════════════════════════════════════════════════════════════ --}}
    @if(isset($topProducts) && $topProducts->isNotEmpty())
    @php
        $topProdMax = $topProducts->max('total_value') ?: 1;
        $topProdRevenue = $topProducts->sum('total_value');
    @endphp
    <div class="report-section" style="margin-bottom:0;">
        <div class="report-section-header">
            <i class="bi bi-box-seam"></i>
            Produtos Mais Vendidos
            <span style="margin-left:auto;font-size:12px;font-weight:500;color:#9ca3af;">
                Receita total: <strong style="color:#10B981;">R$ {{ number_format((float)$topProdRevenue, 2, ',', '.') }}</strong>
            </span>
        </div>
        <div style="overflow-x:auto;">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Produto</th>
                        <th class="num">Preço Unit.</th>
                        <th class="num">Vendas</th>
                        <th class="num">Receita</th>
                        <th style="min-width:100px;">Participação</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($topProducts as $i => $prod)
                    @php
                        $isChamp = $i === 0;
                        $prodPct = $topProdRevenue > 0 ? round($prod->total_value / $topProdRevenue * 100, 1) : 0;
                        $barW = $topProdMax > 0 ? round($prod->total_value / $topProdMax * 100) : 0;
                    @endphp
                    <tr style="{{ $isChamp ? 'background:#fff7ed;' : '' }}">
                        <td>
                            @if($isChamp)
                            <span style="font-size:11px;font-weight:700;color:#F59E0B;">★</span>
                            @else
                            <span style="color:#9ca3af;font-weight:600;">{{ $i + 1 }}</span>
                            @endif
                        </td>
                        <td>
                            <div>
                                <span style="font-weight:600;color:#1a1d23;">{{ $prod->name }}</span>
                                @if($isChamp)
                                <span style="font-size:10px;font-weight:700;color:#F59E0B;margin-left:4px;">Campeão</span>
                                @endif
                            </div>
                        </td>
                        <td class="num" style="color:#374151;">R$ {{ number_format((float) $prod->price, 2, ',', '.') }}</td>
                        <td class="num" style="font-weight:700;color:#3B82F6;">{{ $prod->won_count }}</td>
                        <td class="num" style="font-weight:700;color:#10B981;">R$ {{ number_format((float) $prod->total_value, 2, ',', '.') }}</td>
                        <td>
                            <div>
                                <div style="height:6px;border-radius:3px;background:#f0f2f7;">
                                    <div style="height:6px;border-radius:3px;background:{{ $isChamp ? '#F59E0B' : '#3B82F6' }};width:{{ $barW }}%;transition:width .5s;"></div>
                                </div>
                                <span style="font-size:10px;font-weight:600;color:{{ $isChamp ? '#F59E0B' : '#6b7280' }};margin-top:2px;display:inline-block;">{{ $prodPct }}%</span>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    </div>{{-- /report-double-grid --}}

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
            @php
                $maxActivity = $teamActivity->max('total') ?: 1;
                $topActivity = $teamActivity->max('total');
            @endphp
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Usuário</th>
                        <th class="num">Msgs WA</th>
                        <th class="num">Eventos CRM</th>
                        <th class="num">Total</th>
                        <th style="min-width:140px;">Atividade</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($teamActivity as $row)
                    @php
                        $isTopAct = $row['total'] === $topActivity && $row['total'] > 0;
                        $initials = collect(explode(' ', $row['user']->name))->map(fn($w) => mb_strtoupper(mb_substr($w,0,1)))->take(2)->join('');
                        $barPct = round($row['total'] / $maxActivity * 100);
                        $msgPct = $row['total'] > 0 ? round($row['msgs'] / $row['total'] * 100) : 0;
                    @endphp
                    <tr style="{{ $isTopAct ? 'background:#f0f4ff;' : '' }}">
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div style="width:32px;height:32px;border-radius:50%;background:#eff6ff;color:#3B82F6;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:600;flex-shrink:0;">
                                    {{ $initials }}
                                </div>
                                <div>
                                    <span style="font-weight:600;color:#1a1d23;font-size:13px;">{{ $row['user']->name }}</span>
                                    @if($isTopAct)
                                    <span style="font-size:10px;font-weight:700;color:#3B82F6;margin-left:4px;">★ Mais ativo</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="num">
                            <div style="display:flex;align-items:center;gap:4px;justify-content:flex-end;">
                                <i class="bi bi-whatsapp" style="font-size:11px;color:#25D366;"></i>
                                <span style="font-weight:600;">{{ number_format($row['msgs'], 0, ',', '.') }}</span>
                            </div>
                        </td>
                        <td class="num">
                            <div style="display:flex;align-items:center;gap:4px;justify-content:flex-end;">
                                <i class="bi bi-calendar-event" style="font-size:11px;color:#8B5CF6;"></i>
                                <span style="font-weight:600;">{{ number_format($row['events'], 0, ',', '.') }}</span>
                            </div>
                        </td>
                        <td class="num" style="font-weight:700;color:#3B82F6;">{{ number_format($row['total'], 0, ',', '.') }}</td>
                        <td style="padding-right:16px;">
                            <div style="height:8px;border-radius:4px;background:#f0f2f7;overflow:hidden;display:flex;">
                                <div style="height:8px;background:#25D366;width:{{ round($msgPct * $barPct / 100) }}%;transition:width .5s;" title="WhatsApp: {{ $row['msgs'] }}"></div>
                                <div style="height:8px;background:#8B5CF6;width:{{ round((100 - $msgPct) * $barPct / 100) }}%;transition:width .5s;" title="CRM: {{ $row['events'] }}"></div>
                            </div>
                            <div style="display:flex;gap:8px;margin-top:5px;">
                                <span style="font-size:10px;color:#25D366;font-weight:600;">WA {{ $msgPct }}%</span>
                                <span style="font-size:10px;color:#8B5CF6;font-weight:600;">CRM {{ 100 - $msgPct }}%</span>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/chartjs-plugin-datalabels/2.2.0/chartjs-plugin-datalabels.min.js"></script>
<script>
// Desabilitar datalabels globalmente — só ativar nos charts que precisam
if (window.ChartDataLabels) Chart.defaults.plugins.datalabels = { display: false };

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
    $sourceDisplayNames = ['indicacao' => 'Indicação', 'manual' => 'Manual', 'whatsapp' => 'WhatsApp', 'instagram' => 'Instagram', 'facebook' => 'Facebook', 'google' => 'Google', 'linkedin' => 'LinkedIn', 'site' => 'Site'];
    $srcLabels = $leadsBySource->pluck('source')->map(fn($s) => $sourceDisplayNames[$s] ?? ucfirst($s ?? 'manual'))->toArray();
    $srcData   = $leadsBySource->pluck('total')->toArray();
@endphp
const sourceLabels = @json($srcLabels);
const sourceData   = @json($srcData);

// ── Sparklines nos KPI cards ──────────────────────────────────────────────
(function() {
    function spark(id, data, color) {
        const el = document.getElementById(id);
        if (!el || !window.Chart) return;
        new Chart(el, {
            type: 'line',
            data: {
                labels: data.map((_,i) => i),
                datasets: [{
                    data: data,
                    borderColor: color,
                    borderWidth: 1.5,
                    backgroundColor: color + '18',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 0,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { enabled: false } },
                scales: { x: { display: false }, y: { display: false } },
                layout: { padding: { top: 6 } },
            }
        });
    }
    spark('sparkLeads',   chartLeads, '#3B82F6');
    spark('sparkRevenue', chartLeads.map(function(v){return v * {{ $avgTicket > 0 ? $avgTicket : 1 }};}), '#10B981');
    spark('sparkTicket',  chartLeads, '#8B5CF6');
    spark('sparkConv',    chartLeads, '#F59E0B');
})();

// ── Canvas: Funnel stream (igual dashboard) ──────────────────────────────
(function(){
    var cv = document.getElementById('reportFunnelCanvas');
    if (!cv) return;
    cv.width = cv.offsetWidth * 2;
    cv.height = 360;
    var ctx = cv.getContext('2d');
    var W = cv.width, H = cv.height;
    @php
        $funnelPipeJs = $pipelineRows->first(fn($r) => $r['stages']->isNotEmpty());
        $funnelCountsJs = $funnelPipeJs ? $funnelPipeJs['stages']->pluck('count')->values() : collect([]);
    @endphp
    var data = {!! json_encode($funnelCountsJs) !!};
    var n = data.length;
    if (n < 2) return;
    var maxV = Math.max.apply(null, data) || 1;
    var colW = W / n;
    var pts = data.map(function(v, i){
        var h = Math.max(v / maxV, 0.15) * H * 0.8;
        return { x: colW * i + colW / 2, top: (H - h) / 2, bot: (H + h) / 2 };
    });
    ctx.fillStyle = '#60a5fa';
    ctx.globalAlpha = 0.5;
    ctx.beginPath();
    ctx.moveTo(0, pts[0].top);
    for (var i = 0; i < pts.length - 1; i++) {
        var cpx = (pts[i].x + pts[i+1].x) / 2;
        ctx.quadraticCurveTo(pts[i].x, pts[i].top, cpx, (pts[i].top + pts[i+1].top) / 2);
    }
    ctx.quadraticCurveTo(pts[n-1].x, pts[n-1].top, W, pts[n-1].top);
    ctx.lineTo(W, pts[n-1].bot);
    for (var i = pts.length - 1; i > 0; i--) {
        var cpx = (pts[i].x + pts[i-1].x) / 2;
        ctx.quadraticCurveTo(pts[i].x, pts[i].bot, cpx, (pts[i].bot + pts[i-1].bot) / 2);
    }
    ctx.quadraticCurveTo(pts[0].x, pts[0].bot, 0, pts[0].bot);
    ctx.closePath();
    ctx.fill();
})();

// ── Plugin: crosshair vertical ────────────────────────────────────────────
const crosshairPlugin = {
    id: 'crosshair',
    afterDraw(chart) {
        if (!chart.tooltip?._active?.length) return;
        const ctx = chart.ctx;
        const x   = chart.tooltip._active[0].element.x;
        ctx.save();
        ctx.beginPath();
        ctx.moveTo(x, chart.chartArea.top);
        ctx.lineTo(x, chart.chartArea.bottom);
        ctx.lineWidth   = 1.5;
        ctx.strokeStyle = 'rgba(59,130,246,0.25)';
        ctx.setLineDash([5, 4]);
        ctx.stroke();
        ctx.restore();
    }
};

// ── Gráfico: Leads por dia — barras + média móvel 7 dias ─────────────────
(function () {
    const canvas = document.getElementById('chartLeadsByDay');
    if (!canvas || !window.Chart) return;

    function movingAverage(data, w) {
        return data.map(function(_, i) {
            var slice = data.slice(Math.max(0, i - w + 1), i + 1);
            return Math.round(slice.reduce(function(a, b) { return a + b; }, 0) / slice.length * 10) / 10;
        });
    }

    var ma = movingAverage(chartLeads, 7);

    new Chart(canvas, {
        type: 'bar',
        plugins: [crosshairPlugin],
        data: {
            labels: chartDates,
            datasets: [
                {
                    type: 'bar',
                    label: 'Leads',
                    data: chartLeads,
                    backgroundColor: '#3B82F6',
                    borderRadius: 3,
                    borderSkipped: false,
                    order: 2,
                },
                {
                    type: 'line',
                    label: 'Média 7d',
                    data: ma,
                    borderColor: '#F59E0B',
                    borderWidth: 2,
                    borderDash: [4, 3],
                    pointRadius: 0,
                    pointHoverRadius: 3,
                    pointHoverBackgroundColor: '#F59E0B',
                    fill: false,
                    tension: 0.3,
                    order: 1,
                }
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1a1d23',
                    titleColor: '#9ca3af',
                    bodyColor: '#fff',
                    padding: 10,
                    callbacks: {
                        label: function(ctx) {
                            if (ctx.datasetIndex === 0) return ' ' + ctx.parsed.y + ' lead(s)';
                            return ' Média: ' + ctx.parsed.y.toFixed(1);
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: { font: { size: 10 }, color: '#9ca3af', maxTicksLimit: 15, maxRotation: 0 },
                },
                y: {
                    beginAtZero: true,
                    grid: { color: '#f0f2f7' },
                    border: { display: false },
                    ticks: { font: { size: 11 }, color: '#9ca3af', precision: 0 },
                },
            },
        },
    });
}());

// ── Gráfico: Leads por origem (barras rankeadas + datalabels) ────────────
(function () {
    const canvas = document.getElementById('chartLeadsBySource');
    if (!canvas || !window.Chart) return;

    // Ordenar por volume (maior → menor)
    var paired = sourceLabels.map(function(l, i) { return { label: l, value: sourceData[i] }; });
    paired.sort(function(a, b) { return b.value - a.value; });
    var sortedLabels = paired.map(function(p) { return p.label; });
    var sortedData   = paired.map(function(p) { return p.value; });
    var sortedColors = sortedLabels.map(function(src, i) { return sourceColor(src, i); });
    var total = sortedData.reduce(function(a, b) { return a + b; }, 0) || 1;

    new Chart(canvas, {
        type: 'bar',
        plugins: [window.ChartDataLabels || {}],
        data: {
            labels: sortedLabels,
            datasets: [{
                data: sortedData,
                backgroundColor: sortedColors,
                borderRadius: 4,
                borderSkipped: false,
            }],
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { enabled: false },
                datalabels: {
                    display: true,
                    anchor: 'end',
                    align: 'end',
                    formatter: function(v) {
                        return v + '  (' + Math.round(v / total * 100) + '%)';
                    },
                    font: { size: 11, weight: 600 },
                    color: '#374151',
                }
            },
            scales: {
                x: { display: false, grid: { display: false } },
                y: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: { font: { size: 12, weight: 500 }, color: '#374151' },
                },
            },
            layout: { padding: { right: 70 } },
        },
    });
}());

// ── Gráfico: Cliques botão WA por dia ────────────────────────────────────
(function () {
    const canvas = document.getElementById('chartWaClicks');
    if (!canvas || !window.Chart) return;
    const data = @json($waClicksByDay);
    const labels = Object.keys(data);
    const values = Object.values(data);
    if (labels.length < 2) return;

    new Chart(canvas, {
        type: 'bar',
        data: {
            labels: labels.map(function(d) { var p = d.split('-'); return p[2] + '/' + p[1]; }),
            datasets: [{
                data: values,
                backgroundColor: '#25D366',
                borderRadius: 3,
                borderSkipped: false,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: {
                backgroundColor: '#1a1d23', titleColor: '#9ca3af', bodyColor: '#fff', padding: 8,
                callbacks: { label: function(ctx) { return ' ' + ctx.parsed.y + ' clique(s)'; } }
            }},
            scales: {
                x: { grid: { display: false }, border: { display: false }, ticks: { font: { size: 10 }, color: '#9ca3af', maxRotation: 0, maxTicksLimit: 15 } },
                y: { beginAtZero: true, grid: { color: '#f0f2f7' }, border: { display: false }, ticks: { font: { size: 10 }, color: '#9ca3af', precision: 0 } },
            },
        },
    });
}());
</script>
@endpush

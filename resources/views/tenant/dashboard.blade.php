@extends('tenant.layouts.app')

@php($title = 'Início')
@php($pageIcon = 'house')

@section('topbar_actions')
<div class="topbar-actions">
    <button class="topbar-btn" onclick="openCustomize()" title="Personalizar dashboard">
        <i class="bi bi-sliders"></i>
    </button>
</div>
@endsection

@push('styles')
<style>
    /* ── Welcome Banner ────────────────────────────────────────────────── */
    .welcome-banner {
        margin-bottom: 20px;
    }

    .welcome-banner-label {
        font-size: 11px;
        font-weight: 600;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: #97A3B7;
        display: block;
        margin-bottom: 4px;
    }

    .welcome-banner-title {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 22px;
        font-weight: 700;
        color: #1a1d23;
        margin: 0 0 4px;
        line-height: 1.3;
    }

    .welcome-banner-sub {
        font-size: 13.5px;
        color: #677489;
        margin: 0;
        line-height: 1.55;
    }

    @media (max-width: 640px) {
        .welcome-banner-title { font-size: 18px; }
    }

    /* ── Stat Cards ───────────────────────────────────────────────────── */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 14px;
        margin-bottom: 20px;
    }
    .stat-card {
        background: #fff;
        border-radius: 14px;
        padding: 16px 18px;
        border: 1px solid #e8eaf0;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .stat-card-top {
        display: flex;
        align-items: center;
        gap: 9px;
    }
    .stat-icon {
        width: 30px; height: 30px;
        border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        font-size: 14px;
        flex-shrink: 0;
    }
    .stat-icon.blue   { background: #eff6ff; color: #007DFF; }
    .stat-icon.green  { background: #f0fdf4; color: #10B981; }
    .stat-icon.purple { background: #f5f3ff; color: #8B5CF6; }
    .stat-icon.orange { background: #fffbeb; color: #F59E0B; }
    .stat-icon.red    { background: #fef2f2; color: #EF4444; }
    .stat-label {
        font-size: 12px;
        color: #97A3B7;
        font-weight: 500;
        line-height: 1.3;
    }
    .stat-bottom {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .stat-value-row {
        display: flex;
        align-items: baseline;
        gap: 8px;
    }
    .stat-value {
        font-size: 22px;
        font-weight: 700;
        color: #1a1d23;
        line-height: 1;
    }
    .stat-sub {
        font-size: 11px;
        color: #97A3B7;
    }
    .trend-badge {
        display: inline-flex;
        align-items: center;
        gap: 2px;
        font-size: 11px;
        font-weight: 600;
        padding: 2px 6px;
        border-radius: 99px;
    }
    .trend-badge.up   { background: #f0fdf4; color: #16a34a; }
    .trend-badge.down { background: #fef2f2; color: #dc2626; }

    /* ── Content Cards ────────────────────────────────────────────────── */
    .content-card {
        background: #fff;
        border-radius: 14px;
        border: 1px solid #e8eaf0;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    .content-card-header {
        padding: 16px 20px;
        border-bottom: 1px solid #f0f2f7;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-shrink: 0;
    }
    .content-card-header h3 {
        font-size: 13.5px;
        font-weight: 600;
        color: #1a1d23;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .content-card-header h3 { font-family: 'Plus Jakarta Sans', sans-serif; }
    .content-card-header h3 i { color: #007DFF; }
    .content-card-header .card-link {
        font-size: 12px;
        color: #007DFF;
        text-decoration: none;
        font-weight: 500;
    }
    .content-card-header .card-link:hover { text-decoration: underline; }
    .content-card-body {
        padding: 18px 20px;
        flex: 1;
    }

    /* ── Row 2: Leads chart + Quick Actions ───────────────────────────── */
    .mid-grid {
        display: grid;
        grid-template-columns: 1fr 300px;
        gap: 16px;
        margin-bottom: 20px;
    }
    .chart-wrap {
        position: relative;
        height: 260px;
    }
    .chart-wrap canvas {
        width: 100% !important;
        height: 100% !important;
    }

    /* ── Quick actions ────────────────────────────────────────────────── */
    .quick-actions {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .quick-action {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 11px 14px;
        border: 1px solid #e8eaf0;
        border-radius: 10px;
        text-decoration: none;
        color: #374151;
        font-size: 13px;
        font-weight: 500;
        transition: all .15s;
    }
    .quick-action:hover {
        border-color: #CDDEF6;
        background: #f8faff;
        color: #007DFF;
    }
    .quick-action .qa-icon {
        width: 32px; height: 32px;
        border-radius: 9px;
        background: #eff6ff;
        color: #007DFF;
        display: flex; align-items: center; justify-content: center;
        font-size: 15px;
        flex-shrink: 0;
        transition: background .15s;
    }
    .quick-action:hover .qa-icon { background: #dbeafe; }

    /* ── Row 3: 3-col grid ────────────────────────────────────────────── */
    .bottom-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
    }

    /* Loss reasons list */
    .reason-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .reason-row { display: flex; flex-direction: column; gap: 4px; }
    .reason-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .reason-name {
        font-size: 12.5px;
        color: #374151;
        font-weight: 500;
    }
    .reason-right {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .reason-count {
        font-size: 12px;
        color: #97A3B7;
        font-weight: 600;
    }

    /* Funnel */
    .stage-list {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }
    .stage-row { display: flex; flex-direction: column; gap: 5px; }
    .stage-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .stage-name {
        font-size: 12.5px;
        color: #374151;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 7px;
    }
    .stage-dot {
        width: 7px; height: 7px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .stage-right {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .stage-count {
        font-size: 12px;
        color: #97A3B7;
        font-weight: 600;
    }
    .stage-pct {
        font-size: 11px;
        color: #677489;
        font-weight: 500;
    }
    .stage-bar-track {
        height: 5px;
        background: #f0f2f7;
        border-radius: 99px;
        overflow: hidden;
    }
    .stage-bar-fill {
        height: 100%;
        border-radius: 99px;
        transition: width .5s ease;
    }

    /* Origin doughnut centering */
    .donut-wrap {
        position: relative;
        height: 180px;
        margin-bottom: 4px;
    }
    .donut-wrap canvas { width: 100% !important; height: 100% !important; }

    /* Sales chart */
    .sales-chart-wrap {
        position: relative;
        height: 180px;
    }
    .sales-chart-wrap canvas { width: 100% !important; height: 100% !important; }

    /* Empty state */
    .empty-state {
        text-align: center;
        padding: 36px 20px;
        color: #97A3B7;
    }
    .empty-state i { font-size: 32px; margin-bottom: 10px; opacity: .45; display: block; }
    .empty-state p { font-size: 13px; margin: 0; }

    /* ── Responsive ───────────────────────────────────────────────────── */
    @media (max-width: 1000px) {
        .bottom-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 820px) {
        .mid-grid { grid-template-columns: 1fr; }
    }

    /* ── Modal: botões ─────────────────────────────────────────────────── */
    .btn-clear {
        display: inline-flex;
        align-items: center;
        padding: 8px 18px;
        background: transparent;
        color: #677489;
        border: 1px solid #CDDEF6;
        border-radius: 100px;
        font-size: 13.5px;
        font-weight: 600;
        cursor: pointer;
        transition: all .4s;
    }
    .btn-clear:hover {
        background: #f3f4f6;
        border-color: #007DFF;
        color: #374151;
    }
    .btn-apply {
        display: inline-flex;
        align-items: center;
        padding: 8px 22px;
        background: linear-gradient(148deg, #2C83FB 0%, #1970EA 100%);
        color: #fff;
        border: none;
        border-radius: 100px;
        font-size: 13.5px;
        font-weight: 600;
        cursor: pointer;
        transition: all .4s;
    }
    .btn-apply:hover { background: #0066FF; }
    .btn-apply:disabled { background: #93c5fd; cursor: not-allowed; }

    /* ── Mobile ── */
    @media (max-width: 768px) {
        .topbar-date { display: none; }
    }
    @media (max-width: 1000px) {
        .bottom-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 768px) {
        .funnel-scroll-container {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .funnel-scroll-inner {
            min-width: calc(140px * var(--stage-count, 6));
        }
    }
    @media (max-width: 640px) {
        .donut-flex { flex-direction: column; align-items: center; }
        .donut-flex #donutSvgWrap { flex: unset; }
        .donut-flex #donutList { width: 100%; }
        .leads-metric-grid { grid-template-columns: 1fr !important; }
    }
    @media (max-width: 480px) {
        .stats-grid {
            display: flex;
            overflow-x: auto;
            scroll-snap-type: x mandatory;
            -webkit-overflow-scrolling: touch;
            gap: 10px;
            padding-bottom: 4px;
        }
        .stat-card {
            min-width: 160px;
            flex-shrink: 0;
            scroll-snap-align: start;
            padding: 12px 14px;
        }
        .stat-value { font-size: 18px; }
        .content-card-header { padding: 12px 14px; flex-wrap: wrap; gap: 8px; }
        .content-card-body { padding: 14px; }
        .welcome-banner-sub { font-size: 12.5px; }
    }
</style>
@endpush

@section('content')
<div class="page-container">

    {{-- Welcome Banner --}}
    <div class="welcome-banner">
        <span class="welcome-banner-label">{{ now()->translatedFormat('l, d \d\e F') }}</span>
        <h1 class="welcome-banner-title">Bem-vindo de volta, {{ auth()->user()->name }}.</h1>
        <p class="welcome-banner-sub">Acompanhe seus leads, pipeline de vendas e conversas — tudo em um só lugar.</p>
    </div>

    {{-- ── Row 1: Stat Cards ─────────────────────────────────────────── --}}
    <div class="stats-grid">
    @foreach($visibleCards as $cardKey)
        @switch($cardKey)
        @case('leads')
        <div class="stat-card blue">
            <div class="stat-card-top">
                <div class="stat-icon blue"><i class="bi bi-people"></i></div>
                <span class="stat-label" title="Leads criados de 1 a {{ now()->endOfMonth()->day }} de {{ now()->translatedFormat('F/Y') }}">Leads este mês</span>
            </div>
            <div class="stat-bottom">
                <div class="stat-value-row">
                    <span class="stat-value" data-val="{{ $leadsThisMonth }}" data-prefix="" data-suffix="">{{ $cfLeads }}</span>
                    @if($leadsTrend !== null)
                    <span class="trend-badge {{ $leadsTrend >= 0 ? 'up' : 'down' }}"><i class="bi bi-arrow-{{ $leadsTrend >= 0 ? 'up' : 'down' }}-right"></i> {{ abs($leadsTrend) }}%</span>
                    @endif
                </div>
                <span class="stat-sub">{{ $leadsTrend !== null ? 'vs mês ant.' : 'sem dados anteriores' }}</span>
            </div>
        </div>
        @break
        @case('vendas')
        <div class="stat-card green">
            <div class="stat-card-top">
                <div class="stat-icon green"><i class="bi bi-currency-dollar"></i></div>
                <span class="stat-label">Vendas este mês</span>
            </div>
            <div class="stat-bottom">
                <div class="stat-value-row">
                    <span class="stat-value" data-val="{{ $totalSales }}" data-prefix="R$ " data-suffix="">{{ $cfSales }}</span>
                    @if($salesTrend !== null)
                    <span class="trend-badge {{ $salesTrend >= 0 ? 'up' : 'down' }}"><i class="bi bi-arrow-{{ $salesTrend >= 0 ? 'up' : 'down' }}-right"></i> {{ abs($salesTrend) }}%</span>
                    @endif
                </div>
                <span class="stat-sub">{{ $salesTrend !== null ? 'vs mês ant.' : 'receita fechada' }}</span>
            </div>
        </div>
        @break
        @case('conversao')
        <div class="stat-card purple">
            <div class="stat-card-top">
                <div class="stat-icon purple"><i class="bi bi-percent"></i></div>
                <span class="stat-label">Taxa de Conversão</span>
            </div>
            <div class="stat-bottom">
                <div class="stat-value-row">
                    <span class="stat-value" data-val="{{ $conversionRate }}" data-prefix="" data-suffix="%" data-decimals="1">{{ $conversionRate }}%</span>
                </div>
                <span class="stat-sub">leads → vendas</span>
            </div>
        </div>
        @break
        @case('ticket')
        <div class="stat-card orange">
            <div class="stat-card-top">
                <div class="stat-icon orange"><i class="bi bi-graph-up"></i></div>
                <span class="stat-label">Ticket Médio</span>
            </div>
            <div class="stat-bottom">
                <div class="stat-value-row">
                    <span class="stat-value" data-val="{{ $ticketMedio }}" data-prefix="R$ " data-suffix="">{{ $cfTicket }}</span>
                </div>
                <span class="stat-sub">{{ $leadsGanhos }} negócio{{ $leadsGanhos !== 1 ? 's' : '' }} este mês</span>
            </div>
        </div>
        @break
        @case('perdidos')
        <div class="stat-card red">
            <div class="stat-card-top">
                <div class="stat-icon red"><i class="bi bi-x-circle"></i></div>
                <span class="stat-label">Leads Perdidos</span>
            </div>
            <div class="stat-bottom">
                <div class="stat-value-row">
                    <span class="stat-value" data-val="{{ $leadsPerdidos }}" data-prefix="" data-suffix="">{{ $cfPerdidos }}</span>
                </div>
                <span class="stat-sub">perdidos este mês</span>
            </div>
        </div>
        @break
        @endswitch
    @endforeach
    </div>

    {{-- ── Funil de Vendas ──────────────────────────────────────────────── --}}
    <div class="content-card" style="margin-bottom:16px;">
        <div class="content-card-header">
            <h3><i class="bi bi-funnel"></i> {{ $pipeline?->name ?? 'Funil de Vendas' }}</h3>
            <a href="{{ route('crm.kanban') }}" class="card-link">Kanban <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="content-card-body" style="padding:0;">
            @if(count($stagesWithCount) > 0)
            <div class="funnel-scroll-container" style="--stage-count:{{ count($stagesWithCount) }};">
                <div class="funnel-scroll-inner">
                    <div style="display:grid;grid-template-columns:repeat({{ count($stagesWithCount) }}, 1fr);width:100%;">
                        @foreach($stagesWithCount as $idx => $stage)
                        <div style="padding:16px 18px;{{ $idx > 0 ? 'border-left:1px solid #f0f2f7;' : '' }}">
                            <div style="display:flex;align-items:center;gap:5px;margin-bottom:8px;">
                                <span style="width:8px;height:8px;border-radius:2px;background:{{ $stage['color'] ?? '#3B82F6' }};"></span>
                                <span style="font-size:11px;font-weight:600;color:#6b7280;white-space:nowrap;">{{ $stage['name'] }}</span>
                            </div>
                            <div style="font-size:18px;font-weight:800;color:#1a1d23;margin-bottom:10px;">R$ {{ number_format($stage['value'], 0, ',', '.') }}</div>
                            <div style="font-size:11px;color:#6b7280;margin-bottom:4px;">
                                <span>Quantidade</span><br>
                                <span style="font-weight:700;color:#374151;">{{ $stage['count'] }} negócios</span>
                            </div>
                            <div style="height:24px;background:{{ $stage['color'] ?? '#3B82F6' }}15;border-radius:99px;display:flex;align-items:center;justify-content:center;margin-top:6px;">
                                <span style="font-size:10px;font-weight:700;color:{{ $stage['color'] ?? '#3B82F6' }};">{{ collect($stagesWithCount)->sum('count') > 0 ? round($stage['count'] * 100 / collect($stagesWithCount)->sum('count')) : 0 }}%</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <div style="height:250px;overflow:hidden;border-radius:0 0 14px 14px;background:#f8fafc;position:relative;">
                        <div style="position:absolute;inset:0;display:grid;grid-template-columns:repeat({{ count($stagesWithCount) }}, 1fr);pointer-events:none;z-index:1;">
                            @foreach($stagesWithCount as $idx => $stage)
                            <div style="{{ $idx > 0 ? 'border-left:1px solid rgba(0,0,0,.08);' : '' }}"></div>
                            @endforeach
                        </div>
                        <canvas id="funnelCanvas" style="width:100%;height:250px;display:block;position:relative;z-index:0;"></canvas>
                    </div>
                </div>
            </div>
            <script>
            requestAnimationFrame(function(){
                var cv = document.getElementById('funnelCanvas');
                if(!cv) return;
                cv.width = cv.offsetWidth * 2;
                cv.height = 500;
                var ctx = cv.getContext('2d');
                var W = cv.width, H = cv.height;
                var data = {!! json_encode(collect($stagesWithCount)->pluck('count')->values()) !!};
                var n = data.length;
                if(n < 2) return;
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
                for(var i = 0; i < pts.length - 1; i++){
                    var cpx = (pts[i].x + pts[i+1].x) / 2;
                    ctx.quadraticCurveTo(pts[i].x, pts[i].top, cpx, (pts[i].top + pts[i+1].top) / 2);
                }
                ctx.quadraticCurveTo(pts[n-1].x, pts[n-1].top, W, pts[n-1].top);
                ctx.lineTo(W, pts[n-1].bot);
                for(var i = pts.length - 1; i > 0; i--){
                    var cpx = (pts[i].x + pts[i-1].x) / 2;
                    ctx.quadraticCurveTo(pts[i].x, pts[i].bot, cpx, (pts[i].bot + pts[i-1].bot) / 2);
                }
                ctx.quadraticCurveTo(pts[0].x, pts[0].bot, 0, pts[0].bot);
                ctx.closePath();
                ctx.fill();
            });
            </script>
            @else
            <div class="empty-state" style="padding:24px;">
                <i class="bi bi-kanban"></i>
                <p>Nenhum pipeline configurado. <a href="{{ route('settings.pipelines') }}" style="color:#3B82F6;">Criar pipeline</a></p>
            </div>
            @endif
        </div>
    </div>

    {{-- ── Row 2: Gráfico Leads + Ações Rápidas ──────────────────────── --}}
    <div class="mid-grid">

        {{-- Leads por Canal (stacked bars) --}}
        <div class="content-card">
            <div class="content-card-header" style="flex-wrap:wrap;gap:10px;">
                <div style="display:flex;align-items:center;gap:8px;">
                    <h3><i class="bi bi-people"></i> Leads</h3>
                    <span id="leadsBadge" style="background:#eff6ff;color:#0085f3;font-size:11px;font-weight:700;padding:3px 10px;border-radius:99px;">{{ $leadsThisMonth }} este mês</span>
                </div>
                <div style="display:flex;align-items:center;gap:12px;">
                    <div id="leadsChartFilter" style="display:flex;gap:4px;">
                        <button type="button" class="leads-period-btn" data-period="week" style="padding:4px 10px;font-size:11px;font-weight:600;border-radius:6px;border:1px solid #e5e7eb;background:#fff;color:#6b7280;cursor:pointer;">Semana</button>
                        <button type="button" class="leads-period-btn active" data-period="month" style="padding:4px 10px;font-size:11px;font-weight:600;border-radius:6px;border:1px solid #0085f3;background:#0085f3;color:#fff;cursor:pointer;">Mês</button>
                        <button type="button" class="leads-period-btn" data-period="3months" style="padding:4px 10px;font-size:11px;font-weight:600;border-radius:6px;border:1px solid #e5e7eb;background:#fff;color:#6b7280;cursor:pointer;">3 Meses</button>
                        <button type="button" class="leads-period-btn" data-period="6months" style="padding:4px 10px;font-size:11px;font-weight:600;border-radius:6px;border:1px solid #e5e7eb;background:#fff;color:#6b7280;cursor:pointer;">6 Meses</button>
                    </div>
                    <a href="{{ route('leads.index') }}" class="card-link">Ver todos <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
            <div class="content-card-body">
                {{-- Metric cards --}}
                <div class="leads-metric-grid" style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:16px;">
                    <div style="background:#f5f5f5;border-radius:8px;padding:12px 14px;">
                        <div style="font-size:12px;color:#6b7280;">Total de leads</div>
                        <div id="metricTotal" style="font-size:20px;font-weight:500;color:#1a1d23;">{{ $leadsThisMonth }}</div>
                        <div style="font-size:11px;color:#9ca3af;">Mês atual</div>
                    </div>
                    <div style="background:#f5f5f5;border-radius:8px;padding:12px 14px;">
                        <div style="font-size:12px;color:#6b7280;">Canal principal</div>
                        <div id="metricTopChannel" style="font-size:20px;font-weight:500;color:#1a1d23;">
                            @if(count($leadsBySource) > 0)
                                {{ ucfirst(collect($leadsBySource)->keys()->first(fn($k) => $leadsBySource[$k] === max($leadsBySource)) ?? 'N/A') }}
                            @else
                                N/A
                            @endif
                        </div>
                        <div id="metricTopChannelSub" style="font-size:11px;color:#9ca3af;">
                            @if(count($leadsBySource) > 0)
                                {{ max($leadsBySource) }} leads · {{ $leadsThisMonth > 0 ? round(max($leadsBySource) * 100 / $leadsThisMonth) : 0 }}%
                            @endif
                        </div>
                    </div>
                    <div style="background:#f5f5f5;border-radius:8px;padding:12px 14px;">
                        <div style="font-size:12px;color:#6b7280;">Dias com leads</div>
                        <div id="metricDaysWithLeads" style="font-size:20px;font-weight:500;color:#1a1d23;">
                            {{ collect($leadsPerDay)->filter(fn($v) => $v > 0)->count() }}
                        </div>
                        <div style="font-size:11px;color:#9ca3af;">de {{ count($dayLabels) }} dias</div>
                    </div>
                </div>
                {{-- Legenda interativa --}}
                <div id="leadsLegend" style="display:flex;flex-wrap:wrap;gap:12px;margin-bottom:12px;font-size:12px;color:#6b7280;"></div>
                {{-- Gráfico --}}
                <div style="height:240px;">
                    <canvas id="chartLeads"></canvas>
                </div>
            </div>
        </div>

        {{-- Ações Rápidas --}}
        <div class="content-card">
            <div class="content-card-header">
                <h3><i class="bi bi-lightning-charge"></i> Ações Rápidas</h3>
            </div>
            <div class="content-card-body">
                <div class="quick-actions">
                    <a href="{{ route('leads.index') }}" class="quick-action">
                        <div class="qa-icon"><i class="bi bi-person-plus"></i></div>
                        Adicionar Lead
                    </a>
                    <a href="{{ route('crm.kanban') }}" class="quick-action">
                        <div class="qa-icon"><i class="bi bi-kanban"></i></div>
                        Ver Kanban
                    </a>
                    <a href="{{ route('settings.pipelines') }}" class="quick-action">
                        <div class="qa-icon"><i class="bi bi-funnel"></i></div>
                        Pipelines
                    </a>
                    <a href="{{ route('settings.profile') }}" class="quick-action">
                        <div class="qa-icon"><i class="bi bi-gear"></i></div>
                        Configurações
                    </a>
                </div>
            </div>
        </div>

    </div>

    {{-- ── Row 3: Origem + Perda + Vendas (3 colunas) ────────────────── --}}
    <div class="bottom-grid">

        {{-- Card 1: Leads por Origem (SVG donut + lista) --}}
        <div class="content-card" style="padding:14px;">
            <div style="font-size:12px;font-weight:500;display:flex;align-items:center;gap:6px;margin-bottom:14px;color:#374151;">
                <i class="bi bi-pie-chart" style="color:#0085f3;"></i> Leads por Origem
            </div>
            @if(count($leadsBySource) > 0)
            <div class="donut-flex" style="display:flex;align-items:center;gap:14px;">
                <div id="donutSvgWrap" style="flex:4;display:flex;justify-content:center;"></div>
                <div style="display:flex;flex-direction:column;gap:5px;flex:1;min-width:0;" id="donutList"></div>
            </div>
            <script>
            document.addEventListener('DOMContentLoaded', function(){
                var SC = {whatsapp:'#25D366',instagram:'#E1306C',facebook:'#1877F2',site:'#3B82F6',google:'#FBBC04',linkedin:'#0A66C2',indicacao:'#8B5CF6',manual:'#94A3B8',telefone:'#F97316',email:'#06B6D4'};
                var FB = ['#10B981','#F59E0B','#EF4444','#06B6D4','#F97316','#EC4899'];
                function gc(k,i){return SC[k.toLowerCase()]||FB[i%FB.length];}
                var data = @json($leadsBySource);
                var entries = Object.entries(data).sort(function(a,b){return b[1]-a[1];});
                var total = entries.reduce(function(s,e){return s+e[1];},0) || 1;
                var r = 32, cx = 45, cy = 45, circ = 2 * Math.PI * r;
                var offset = 0;
                var paths = '';
                entries.forEach(function(e, i){
                    var dash = (e[1] / total) * circ;
                    paths += '<circle cx="'+cx+'" cy="'+cy+'" r="'+r+'" fill="none" stroke="'+gc(e[0],i)+'" stroke-width="16" stroke-dasharray="'+dash+' '+(circ-dash)+'" stroke-dashoffset="'+(-offset)+'" style="transform:rotate(-90deg);transform-origin:center;"/>';
                    offset += dash;
                });
                document.getElementById('donutSvgWrap').innerHTML = '<svg width="160" height="160" viewBox="0 0 90 90">'+paths+'<text x="45" y="43" text-anchor="middle" font-size="16" font-weight="700" fill="#1a1d23">'+total+'</text><text x="45" y="54" text-anchor="middle" font-size="8" fill="#9ca3af">leads</text></svg>';
                var list = '';
                entries.forEach(function(e, i){
                    var pct = Math.round(e[1] / total * 100);
                    list += '<div style="display:flex;align-items:center;justify-content:space-between;gap:8px;"><div style="display:flex;align-items:center;gap:6px;min-width:0;"><span style="width:8px;height:8px;min-width:8px;border-radius:2px;background:'+gc(e[0],i)+';"></span><span style="font-size:11px;color:#6b7280;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">'+e[0].charAt(0).toUpperCase()+e[0].slice(1)+'</span></div><span style="font-size:11px;font-weight:600;color:#1a1d23;white-space:nowrap;">'+pct+'%</span></div>';
                });
                document.getElementById('donutList').innerHTML = list;
            });
            </script>
            @else
            <div class="empty-state"><i class="bi bi-pie-chart"></i><p>Nenhuma origem registrada.</p></div>
            @endif
        </div>

        {{-- Card 2: Motivos de Perda (Heatmap) --}}
        <div class="content-card" style="padding:14px;">
            <div style="font-size:12px;font-weight:500;display:flex;align-items:center;gap:6px;margin-bottom:14px;color:#374151;">
                <i class="bi bi-slash-circle" style="color:#EF4444;"></i> Motivos de Perda
            </div>
            @if(count($lostByReason) > 0)
            <div style="display:grid;grid-template-columns:1fr 52px 52px;gap:4px 6px;align-items:center;">
                <div style="font-size:10px;color:#9ca3af;font-weight:600;text-transform:uppercase;letter-spacing:.3px;">Motivo</div>
                <div style="font-size:10px;color:#9ca3af;font-weight:600;text-align:center;">Atual</div>
                <div style="font-size:10px;color:#9ca3af;font-weight:600;text-align:center;">Anterior</div>
                @foreach($lostByReason as $reason)
                <div style="font-size:12px;color:#374151;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $reason['name'] }}</div>
                <div style="background:{{ $reason['total'] >= 5 ? '#A32D2D' : ($reason['total'] >= 4 ? '#E24B4A' : ($reason['total'] >= 3 ? '#F09595' : ($reason['total'] >= 2 ? '#F7C1C1' : '#FCEBEB'))) }};border-radius:5px;height:26px;display:flex;align-items:center;justify-content:center;">
                    <span style="font-size:12px;font-weight:600;color:{{ $reason['total'] >= 4 ? '#fff' : '#A32D2D' }};">{{ $reason['total'] }}</span>
                </div>
                <div style="background:{{ ($reason['prev'] ?? 0) >= 5 ? '#A32D2D' : (($reason['prev'] ?? 0) >= 4 ? '#E24B4A' : (($reason['prev'] ?? 0) >= 3 ? '#F09595' : (($reason['prev'] ?? 0) >= 2 ? '#F7C1C1' : '#FCEBEB'))) }};border-radius:5px;height:26px;display:flex;align-items:center;justify-content:center;">
                    <span style="font-size:12px;font-weight:600;color:{{ ($reason['prev'] ?? 0) >= 4 ? '#fff' : '#A32D2D' }};">{{ $reason['prev'] ?? 0 }}</span>
                </div>
                @endforeach
            </div>
            <div style="display:flex;align-items:center;gap:6px;margin-top:12px;">
                <span style="font-size:10px;color:#9ca3af;">menos</span>
                <div style="width:14px;height:10px;border-radius:2px;background:#FCEBEB;"></div>
                <div style="width:14px;height:10px;border-radius:2px;background:#F7C1C1;"></div>
                <div style="width:14px;height:10px;border-radius:2px;background:#F09595;"></div>
                <div style="width:14px;height:10px;border-radius:2px;background:#E24B4A;"></div>
                <div style="width:14px;height:10px;border-radius:2px;background:#A32D2D;"></div>
                <span style="font-size:10px;color:#9ca3af;">mais</span>
            </div>
            @else
            <div class="empty-state"><i class="bi bi-slash-circle"></i><p>Nenhum lead perdido.</p></div>
            @endif
        </div>

        {{-- Card 3: Vendas (Barras + Média Móvel) --}}
        <div class="content-card" style="padding:14px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
                <span style="font-size:12px;font-weight:500;display:flex;align-items:center;gap:6px;color:#374151;">
                    <i class="bi bi-currency-dollar" style="color:#10b981;"></i> Vendas
                </span>
                <span style="font-size:10px;color:#9ca3af;">
                    <span>12m:</span> <strong style="color:#1a1d23;">R$ {{ number_format(array_sum($salesPerMonth), 0, ',', '.') }}</strong>
                    &nbsp;
                    <span style="color:#0085f3;font-weight:500;">Mês: R$ {{ number_format($totalSales, 0, ',', '.') }}</span>
                </span>
            </div>
            <div style="display:flex;gap:12px;margin-bottom:10px;">
                <div style="display:flex;align-items:center;gap:4px;">
                    <span style="width:10px;height:10px;border-radius:2px;background:#93c5fd;"></span>
                    <span style="font-size:10px;color:#6b7280;">Vendas mensais</span>
                </div>
                <div style="display:flex;align-items:center;gap:4px;">
                    <span style="width:16px;border-top:1.5px dashed #f59e0b;"></span>
                    <span style="font-size:10px;color:#6b7280;">Média móvel (3m)</span>
                </div>
            </div>
            <div style="position:relative;width:100%;height:150px;">
                <canvas id="chartSales"></canvas>
            </div>
        </div>

    </div>

</div>

{{-- ── Modal: Personalizar Dashboard ─────────────────────────────────── --}}
<div id="customizeOverlay"
     style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.4); z-index:1050; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:16px; width:360px; max-width:95vw; padding:24px; box-shadow:0 8px 48px rgba(0,0,0,.2);">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:6px;">
            <h3 style="font-size:15px; font-weight:700; color:#1a1d23; margin:0;">Personalizar Dashboard</h3>
            <button onclick="closeCustomize()" style="background:none; border:none; cursor:pointer; font-size:22px; color:#9ca3af; line-height:1; padding:0;">×</button>
        </div>
        <p style="font-size:12px; color:#9ca3af; margin:0 0 16px;">Arraste para reordenar. Marque para exibir.</p>
        <ul id="cardSortList" style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:6px;"></ul>
        <div style="margin-top:20px; display:flex; gap:10px; justify-content:flex-end;">
            <button onclick="closeCustomize()" class="btn-clear">Cancelar</button>
            <button onclick="saveCustomize()" class="btn-apply" id="btnSaveCustomize">Salvar</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/chartjs-plugin-datalabels/2.2.0/chartjs-plugin-datalabels.min.js"></script>
<script>
(function () {
    const monthLabels            = @json($monthLabels);
    const leadsPerMonth          = @json($leadsPerMonth);
    const salesPerMonth          = @json($salesPerMonth);
    const origLabels             = @json(array_keys($leadsBySource));
    const origData               = @json(array_values($leadsBySource));
    const dayLabels              = @json($dayLabels);
    const leadsPerDay            = @json($leadsPerDay);
    const leadsPerDayBySource    = {!! json_encode($leadsPerDayBySource) !!};

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

    const chartDefaults = {
        font: { family: "'Inter', sans-serif" },
    };

    // ── Plugin: crosshair vertical no hover ───────────────────────────
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

    const crosshairGreen = {
        id: 'crosshairGreen',
        afterDraw(chart) {
            if (!chart.tooltip?._active?.length) return;
            const ctx = chart.ctx;
            const x   = chart.tooltip._active[0].element.x;
            ctx.save();
            ctx.beginPath();
            ctx.moveTo(x, chart.chartArea.top);
            ctx.lineTo(x, chart.chartArea.bottom);
            ctx.lineWidth   = 1.5;
            ctx.strokeStyle = 'rgba(16,185,129,0.25)';
            ctx.setLineDash([5, 4]);
            ctx.stroke();
            ctx.restore();
        }
    };

    // ── Leads stacked bar chart (spec completa) ─────────────────────────
    (function () {
        const LEADS_CHART_URL = "{{ url('/dashboard/leads-chart') }}";
        const canvas = document.getElementById('chartLeads');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        let leadsChart = null;
        const hidden = new Set();

        function getColor(src, idx) { return sourceColor(src, idx || 0); }
        function capitalize(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : ''; }

        function buildLegend(datasetsObj) {
            const el = document.getElementById('leadsLegend');
            if (!el) return;
            el.innerHTML = '';
            Object.keys(datasetsObj).forEach((src, i) => {
                const item = document.createElement('span');
                item.style.cssText = 'display:inline-flex;align-items:center;gap:5px;cursor:pointer;padding:2px 6px;border-radius:4px;transition:opacity .15s;user-select:none;';
                item.style.opacity = hidden.has(i) ? '0.35' : '1';
                item.innerHTML = '<span style="width:10px;height:10px;border-radius:2px;background:' + getColor(src, i) + ';display:inline-block;"></span>' + capitalize(src);
                item.onclick = function() {
                    if (hidden.has(i)) hidden.delete(i); else hidden.add(i);
                    item.style.opacity = hidden.has(i) ? '0.35' : '1';
                    if (leadsChart) {
                        leadsChart.data.datasets.forEach((ds, di) => {
                            ds.hidden = hidden.has(di);
                        });
                        leadsChart.update();
                    }
                };
                el.appendChild(item);
            });
        }

        function calcDayTotal(ctx) {
            let total = 0;
            const chart = ctx.chart;
            chart.data.datasets.forEach((ds, i) => {
                if (!hidden.has(i)) total += (ds.data[ctx.dataIndex] || 0);
            });
            return total;
        }

        function buildChart(labels, datasetsObj) {
            // Filter days with data
            const allSources = Object.keys(datasetsObj);
            const dayTotals = labels.map((_, di) => allSources.reduce((s, src) => s + (datasetsObj[src][di] || 0), 0));
            const filteredIdx = [];
            dayTotals.forEach((t, i) => { if (t > 0) filteredIdx.push(i); });
            const filtLabels = filteredIdx.map(i => labels[i]);

            const datasets = allSources.map((src, i) => ({
                label: capitalize(src),
                data: filteredIdx.map(di => datasetsObj[src][di] || 0),
                backgroundColor: getColor(src, i),
                borderRadius: { topLeft: 3, topRight: 3 },
                borderSkipped: false,
                barPercentage: 0.75,
                categoryPercentage: 0.8,
                hidden: hidden.has(i),
            }));

            if (leadsChart) leadsChart.destroy();

            leadsChart = new Chart(ctx, {
                type: 'bar',
                data: { labels: filtLabels, datasets },
                plugins: [ChartDataLabels],
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: { padding: { top: 20 } },
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#1a1d23', titleColor: '#9ca3af', bodyColor: '#fff', padding: 10,
                            callbacks: {
                                title: items => items[0].label,
                                footer: items => {
                                    const total = items.reduce((s, i) => s + i.raw, 0);
                                    return 'Total: ' + total + ' lead' + (total !== 1 ? 's' : '');
                                }
                            }
                        },
                        datalabels: {
                            display: function(ctx) {
                                const lastVisible = ctx.chart.data.datasets.reduce((last, _, i) => !hidden.has(i) ? i : last, -1);
                                if (ctx.datasetIndex !== lastVisible) return false;
                                return calcDayTotal(ctx) > 0;
                            },
                            anchor: 'end', align: 'end',
                            formatter: function(_, ctx) { return calcDayTotal(ctx); },
                            color: '#888', font: { size: 11, weight: '500' }
                        }
                    },
                    scales: {
                        x: { stacked: true, grid: { display: false }, border: { display: false }, ticks: { font: { size: 10 }, color: '#9ca3af', autoSkip: false } },
                        y: {
                            stacked: true, beginAtZero: true,
                            grid: { color: 'rgba(128,128,128,0.1)' }, border: { display: false },
                            ticks: { precision: 0, font: { size: 11 }, color: '#9ca3af' },
                            suggestedMax: Math.max(...dayTotals.filter((_, i) => filteredIdx.includes(i))) + 1
                        }
                    }
                }
            });

            buildLegend(datasetsObj);
        }

        // Initial render
        buildChart(dayLabels, leadsPerDayBySource);

        // Period filter
        document.querySelectorAll('.leads-period-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.leads-period-btn').forEach(b => {
                    b.style.border = '1px solid #e5e7eb'; b.style.background = '#fff'; b.style.color = '#6b7280'; b.classList.remove('active');
                });
                this.style.border = '1px solid #0085f3'; this.style.background = '#0085f3'; this.style.color = '#fff'; this.classList.add('active');
                hidden.clear();

                const period = this.dataset.period;
                if (period === 'month') {
                    buildChart(dayLabels, leadsPerDayBySource);
                    document.getElementById('leadsBadge').textContent = '{{ $leadsThisMonth }} este mês';
                    return;
                }

                fetch(`${LEADS_CHART_URL}?period=${period}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(r => r.json())
                .then(data => {
                    buildChart(data.labels, data.datasets);
                    document.getElementById('leadsBadge').textContent = data.total + ' leads';
                    document.getElementById('metricTotal').textContent = data.total;
                })
                .catch(() => toastr.error('Erro ao carregar dados do gráfico'));
            });
        });
    }());

    // ── Leads por Origem agora é SVG puro (não Chart.js) ────────────────

    // ── Vendas — barras + média móvel (3m) ─────────────────────────────
    (function () {
        const canvas = document.getElementById('chartSales');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');

        // Média móvel janela 3
        function movingAvg(data, w) {
            return data.map(function(_, i) {
                var slice = data.slice(Math.max(0, i - w + 1), i + 1);
                return Math.round(slice.reduce(function(a, b) { return a + b; }, 0) / slice.length);
            });
        }
        var ma = movingAvg(salesPerMonth, 3);

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: monthLabels,
                datasets: [
                    {
                        type: 'bar', label: 'Vendas', data: salesPerMonth,
                        backgroundColor: '#93c5fd', borderRadius: 3, borderSkipped: false, order: 2,
                    },
                    {
                        type: 'line', label: 'Média móvel (3m)', data: ma,
                        borderColor: '#f59e0b', borderWidth: 2, borderDash: [4, 3],
                        pointRadius: 3, pointBackgroundColor: '#f59e0b', pointBorderColor: '#fff', pointBorderWidth: 1.5,
                        fill: false, tension: 0.4, order: 1,
                    }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false, layout: { padding: { top: 4 } },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#fff', borderColor: 'rgba(0,0,0,0.1)', borderWidth: 1,
                        titleColor: '#374151', bodyColor: '#1a1d23', padding: 9,
                        callbacks: {
                            label: function(ctx) { return ctx.dataset.label + ': R$ ' + ctx.raw.toLocaleString('pt-BR'); }
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false }, border: { display: false }, ticks: { font: { size: 10 }, color: '#9ca3af', autoSkip: false, maxRotation: 0 } },
                    y: {
                        beginAtZero: true, grid: { color: 'rgba(128,128,128,0.08)' }, border: { display: false },
                        ticks: { font: { size: 10 }, color: '#9ca3af', callback: function(v) { return 'R$ ' + v.toLocaleString('pt-BR'); } }
                    }
                }
            }
        });
    }());
}());

// ── Stat Cards: compact format + count-up animation ────────────────────────
function statCompact(val, prefix, suffix, decimals) {
    const dec = parseInt(decimals || 0);
    let display;
    if (val >= 1_000_000) {
        display = (val / 1_000_000).toLocaleString('pt-BR', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + 'M';
    } else if (val >= 1_000) {
        display = (val / 1_000).toLocaleString('pt-BR', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + 'K';
    } else {
        display = val.toLocaleString('pt-BR', { minimumFractionDigits: dec, maximumFractionDigits: dec });
    }
    return (prefix || '') + display + (suffix || '');
}

document.querySelectorAll('.stat-value[data-val]').forEach(el => {
    const target   = parseFloat(el.dataset.val   || 0);
    const prefix   = el.dataset.prefix || '';
    const suffix   = el.dataset.suffix || '';
    const decimals = el.dataset.decimals || '0';
    const duration = 1100;
    const startTs  = performance.now();

    function step(now) {
        const progress = Math.min((now - startTs) / duration, 1);
        const eased    = 1 - Math.pow(1 - progress, 3); // ease-out cubic
        el.textContent = statCompact(target * eased, prefix, suffix, decimals);
        if (progress < 1) requestAnimationFrame(step);
        else el.textContent = statCompact(target, prefix, suffix, decimals);
    }
    el.textContent = statCompact(0, prefix, suffix, decimals);
    requestAnimationFrame(step);
});

// ── Personalizar Dashboard ──────────────────────────────────────────────────
const ALL_CARDS = {
    leads:     'Leads este mês',
    vendas:    'Vendas este mês',
    conversao: 'Taxa de Conversão',
    ticket:    'Ticket Médio',
    perdidos:  'Leads Perdidos',
};
const currentCards = @json($visibleCards);

let sortableInstance = null;

function openCustomize() {
    const list    = document.getElementById('cardSortList');
    const visible = new Set(currentCards);
    const ordered = [...currentCards, ...Object.keys(ALL_CARDS).filter(k => !visible.has(k))];

    list.innerHTML = ordered.map(key => `
        <li data-key="${key}" style="display:flex;align-items:center;gap:10px;padding:10px 12px;border:1.5px solid #e8eaf0;border-radius:9px;background:#fff;user-select:none;">
            <i class="bi bi-grip-vertical drag-handle" style="color:#d1d5db;cursor:grab;font-size:16px;flex-shrink:0;"></i>
            <span style="flex:1;font-size:13px;font-weight:500;color:#374151;">${ALL_CARDS[key]}</span>
            <label style="display:flex;align-items:center;cursor:pointer;gap:0;">
                <input type="checkbox" ${visible.has(key) ? 'checked' : ''} style="width:16px;height:16px;cursor:pointer;">
            </label>
        </li>
    `).join('');

    if (sortableInstance) { sortableInstance.destroy(); }
    sortableInstance = Sortable.create(list, { animation: 150, handle: '.drag-handle' });

    const overlay = document.getElementById('customizeOverlay');
    overlay.style.display = 'flex';
}

function closeCustomize() {
    document.getElementById('customizeOverlay').style.display = 'none';
}

function saveCustomize() {
    const btn   = document.getElementById('btnSaveCustomize');
    const items = [...document.querySelectorAll('#cardSortList li')];
    const cards = items
        .filter(li => li.querySelector('input[type=checkbox]').checked)
        .map(li => li.dataset.key);

    btn.disabled = true;
    btn.textContent = 'Salvando…';

    fetch('{{ route("dashboard.config") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ cards }),
    })
    .then(r => r.json())
    .then(() => {
        if (typeof toastr !== 'undefined') toastr.success('Dashboard atualizado!');
        location.reload();
    })
    .catch(() => {
        btn.disabled = false;
        btn.textContent = 'Salvar';
        if (typeof toastr !== 'undefined') toastr.error('Erro ao salvar. Tente novamente.');
    });
}

// Fechar ao clicar fora do painel
document.getElementById('customizeOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeCustomize();
});
</script>
@endpush

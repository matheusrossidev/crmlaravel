@extends('tenant.layouts.app')

@php($title = 'Início')
@php($pageIcon = 'house')

@section('topbar_actions')
<div class="topbar-actions">
    <span style="font-size:13px;color:#6b7280;">
        {{ now()->translatedFormat('l, d \d\e F') }}
    </span>
    <button class="topbar-btn" onclick="openCustomize()" title="Personalizar dashboard">
        <i class="bi bi-sliders"></i>
    </button>
    <button class="topbar-btn" title="Notificações">
        <i class="bi bi-bell"></i>
    </button>
    <a href="{{ route('leads.index') }}" class="btn-primary-sm">
        <i class="bi bi-plus-lg"></i>
        Novo Lead
    </a>
</div>
@endsection

@push('styles')
<style>
    /* ── Greeting ─────────────────────────────────────────────────────── */
    .greeting {
        margin-bottom: 24px;
    }
    .greeting h1 {
        font-size: 22px;
        font-weight: 700;
        color: #1a1d23;
        margin: 0 0 4px;
    }
    .greeting p {
        font-size: 14px;
        color: #6b7280;
        margin: 0;
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
    .stat-icon.blue   { background: #eff6ff; color: #3B82F6; }
    .stat-icon.green  { background: #f0fdf4; color: #10B981; }
    .stat-icon.purple { background: #f5f3ff; color: #8B5CF6; }
    .stat-icon.orange { background: #fffbeb; color: #F59E0B; }
    .stat-icon.red    { background: #fef2f2; color: #EF4444; }
    .stat-label {
        font-size: 12px;
        color: #9ca3af;
        font-weight: 500;
        line-height: 1.3;
    }
    .stat-bottom {
        display: flex;
        align-items: baseline;
        gap: 7px;
        flex-wrap: wrap;
    }
    .stat-value {
        font-size: 22px;
        font-weight: 700;
        color: #1a1d23;
        line-height: 1;
    }
    .stat-sub {
        font-size: 11px;
        color: #9ca3af;
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
    .content-card-header h3 i { color: #3B82F6; }
    .content-card-header .card-link {
        font-size: 12px;
        color: #3B82F6;
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
        border-color: #dbeafe;
        background: #f8faff;
        color: #3B82F6;
    }
    .quick-action .qa-icon {
        width: 32px; height: 32px;
        border-radius: 9px;
        background: #eff6ff;
        color: #3B82F6;
        display: flex; align-items: center; justify-content: center;
        font-size: 15px;
        flex-shrink: 0;
        transition: background .15s;
    }
    .quick-action:hover .qa-icon { background: #dbeafe; }

    /* ── Row 3: 2×2 grid ─────────────────────────────────────────────── */
    .bottom-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
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
        color: #9ca3af;
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
        color: #9ca3af;
        font-weight: 600;
    }
    .stage-pct {
        font-size: 11px;
        color: #6b7280;
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
        color: #9ca3af;
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
</style>
@endpush

@section('content')
<div class="page-container">

    {{-- Saudação --}}
    <div class="greeting">
        <h1>Olá, {{ auth()->user()->name }} 👋</h1>
        <p>Aqui está um resumo do seu negócio em {{ now()->translatedFormat('F Y') }}.</p>
    </div>

    {{-- ── Row 1: Stat Cards ─────────────────────────────────────────── --}}
    <div class="stats-grid">
    @foreach($visibleCards as $cardKey)
        @switch($cardKey)
        @case('leads')
        <div class="stat-card blue">
            <div class="stat-card-top">
                <div class="stat-icon blue"><i class="bi bi-people"></i></div>
                <span class="stat-label">Leads este mês</span>
            </div>
            <div class="stat-bottom">
                <span class="stat-value" data-val="{{ $leadsThisMonth }}" data-prefix="" data-suffix="">{{ $cfLeads }}</span>
                @if($leadsTrend !== null)
                    <span class="trend-badge {{ $leadsTrend >= 0 ? 'up' : 'down' }}">{{ $leadsTrend >= 0 ? '↗' : '↘' }} {{ abs($leadsTrend) }}%</span>
                    <span class="stat-sub">vs mês ant.</span>
                @else
                    <span class="stat-sub">sem dados anteriores</span>
                @endif
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
                <span class="stat-value" data-val="{{ $totalSales }}" data-prefix="R$ " data-suffix="">{{ $cfSales }}</span>
                @if($salesTrend !== null)
                    <span class="trend-badge {{ $salesTrend >= 0 ? 'up' : 'down' }}">{{ $salesTrend >= 0 ? '↗' : '↘' }} {{ abs($salesTrend) }}%</span>
                    <span class="stat-sub">vs mês ant.</span>
                @else
                    <span class="stat-sub">receita fechada</span>
                @endif
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
                <span class="stat-value" data-val="{{ $conversionRate }}" data-prefix="" data-suffix="%" data-decimals="1">{{ $conversionRate }}%</span>
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
                <span class="stat-value" data-val="{{ $ticketMedio }}" data-prefix="R$ " data-suffix="">{{ $cfTicket }}</span>
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
                <span class="stat-value" data-val="{{ $leadsPerdidos }}" data-prefix="" data-suffix="">{{ $cfPerdidos }}</span>
                <span class="stat-sub">perdidos este mês</span>
            </div>
        </div>
        @break
        @endswitch
    @endforeach
    </div>

    {{-- ── Row 2: Gráfico Leads + Ações Rápidas ──────────────────────── --}}
    <div class="mid-grid">

        {{-- Novos Leads (6 meses) --}}
        <div class="content-card">
            <div class="content-card-header">
                <h3><i class="bi bi-bar-chart"></i> Novos Leads — Últimos 6 Meses</h3>
                <a href="{{ route('leads.index') }}" class="card-link">
                    Ver todos <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="content-card-body">
                <div class="chart-wrap">
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
                    <a href="{{ route('campaigns.index') }}" class="quick-action">
                        <div class="qa-icon"><i class="bi bi-megaphone"></i></div>
                        Campanhas
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

    {{-- ── Row 3: Funil + Origem + Vendas ─────────────────────────────── --}}
    <div class="bottom-grid">

        {{-- Funil de Conversão --}}
        <div class="content-card">
            <div class="content-card-header">
                <h3><i class="bi bi-funnel"></i>
                    {{ $pipeline?->name ?? 'Funil de Vendas' }}
                </h3>
                <a href="{{ route('crm.kanban') }}" class="card-link">
                    Kanban <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="content-card-body">
                @if(count($stagesWithCount) > 0)
                    @php($totalInPipeline = collect($stagesWithCount)->sum('count') ?: 1)
                    <div class="stage-list">
                        @foreach($stagesWithCount as $stage)
                        <div class="stage-row">
                            <div class="stage-meta">
                                <span class="stage-name">
                                    <span class="stage-dot" style="background:{{ $stage['color'] ?? '#3B82F6' }};"></span>
                                    {{ $stage['name'] }}
                                </span>
                                <div class="stage-right">
                                    <span class="stage-pct">{{ round($stage['count'] * 100 / $totalInPipeline) }}%</span>
                                    <span class="stage-count">{{ $stage['count'] }}</span>
                                </div>
                            </div>
                            <div class="stage-bar-track">
                                <div class="stage-bar-fill"
                                     style="width:{{ intval($stage['count'] * 100 / $maxStageCount) }}%; background:{{ $stage['color'] ?? '#3B82F6' }};"></div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">
                        <i class="bi bi-kanban"></i>
                        <p>Nenhum pipeline configurado.<br>
                           <a href="{{ route('settings.pipelines') }}" style="color:#3B82F6;">Criar pipeline</a>
                        </p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Leads por Origem --}}
        <div class="content-card">
            <div class="content-card-header">
                <h3><i class="bi bi-pie-chart"></i> Leads por Origem</h3>
            </div>
            <div class="content-card-body">
                @if(count($leadsBySource) > 0)
                    <div class="donut-wrap">
                        <canvas id="chartOrigin"></canvas>
                    </div>
                @else
                    <div class="empty-state">
                        <i class="bi bi-pie-chart"></i>
                        <p>Nenhuma origem registrada ainda.</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Motivos de Perda --}}
        <div class="content-card">
            <div class="content-card-header">
                <h3><i class="bi bi-slash-circle" style="color:#EF4444;"></i> Motivos de Perda</h3>
                <a href="{{ route('settings.lost-reasons') }}" class="card-link">
                    Gerenciar <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="content-card-body">
                @if(count($lostByReason) > 0)
                    @php($maxLost = collect($lostByReason)->max('total') ?: 1)
                    <div class="reason-list">
                        @foreach($lostByReason as $reason)
                        <div class="reason-row">
                            <div class="reason-meta">
                                <span class="reason-name">{{ $reason['name'] }}</span>
                                <div class="reason-right">
                                    <span class="reason-count">{{ $reason['total'] }}</span>
                                </div>
                            </div>
                            <div class="stage-bar-track">
                                <div class="stage-bar-fill"
                                     style="width:{{ intval($reason['total'] * 100 / $maxLost) }}%; background:#EF4444;"></div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">
                        <i class="bi bi-slash-circle"></i>
                        <p>Nenhum lead perdido registrado ainda.</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Evolução de Vendas --}}
        <div class="content-card">
            <div class="content-card-header">
                <h3><i class="bi bi-currency-dollar"></i> Evolução de Vendas</h3>
            </div>
            <div class="content-card-body">
                <div class="sales-chart-wrap">
                    <canvas id="chartSales"></canvas>
                </div>
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
<script>
(function () {
    const monthLabels            = @json($monthLabels);
    const leadsPerMonth          = @json($leadsPerMonth);
    const salesPerMonth          = @json($salesPerMonth);
    const origLabels             = @json(array_keys($leadsBySource));
    const origData               = @json(array_values($leadsBySource));
    const leadsPerMonthBySource  = {!! json_encode($leadsPerMonthBySource) !!};

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

    // ── Novos Leads (stacked bar por origem) ───────────────────────────
    const sourceNames = Object.keys(leadsPerMonthBySource);
    const datasets = sourceNames.length > 0
        ? sourceNames.map((src, i) => ({
            label: src.charAt(0).toUpperCase() + src.slice(1),
            data: leadsPerMonthBySource[src],
            backgroundColor: sourceColor(src, i),
            borderWidth: 2,
            borderColor: '#ffffff',
            borderRadius: { topLeft: 4, topRight: 4, bottomLeft: 0, bottomRight: 0 },
            borderSkipped: false,
            stack: 'leads',
        }))
        : [{
            label: 'Leads',
            data: leadsPerMonth,
            backgroundColor: '#3B82F6',
            borderRadius: 6,
            borderSkipped: false,
        }];

    new Chart(document.getElementById('chartLeads'), {
        type: 'bar',
        data: { labels: monthLabels, datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: sourceNames.length > 0,
                    position: 'bottom',
                    labels: { boxWidth: 10, padding: 12, font: { size: 11 } },
                },
                tooltip: {
                    callbacks: {
                        footer: (items) => {
                            const total = items.reduce((s, i) => s + i.parsed.y, 0);
                            return 'Total: ' + total + ' lead(s)';
                        }
                    }
                }
            },
            scales: {
                x: { stacked: true, grid: { display: false }, ticks: { font: { size: 11 } } },
                y: { stacked: true, beginAtZero: true, ticks: { precision: 0, font: { size: 11 } }, grid: { color: '#f0f2f7' } },
            },
        }
    });

    // ── Leads por Origem ───────────────────────────────────────────────
    if (document.getElementById('chartOrigin')) {
        new Chart(document.getElementById('chartOrigin'), {
            type: 'doughnut',
            data: {
                labels: origLabels,
                datasets: [{
                    data: origData,
                    backgroundColor: origLabels.map((src, i) => sourceColor(src, i)),
                    borderWidth: 2,
                    borderColor: '#fff',
                    hoverOffset: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 10, padding: 10, font: { size: 11 } }
                    }
                }
            }
        });
    }

    // ── Evolução de Vendas ─────────────────────────────────────────────
    new Chart(document.getElementById('chartSales'), {
        type: 'bar',
        data: {
            labels: monthLabels,
            datasets: [{
                label: 'Vendas (R$)',
                data: salesPerMonth,
                backgroundColor: '#10B981',
                borderRadius: 6,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => ` R$ ${ctx.parsed.y.toLocaleString('pt-BR', { minimumFractionDigits: 0 })}`
                    }
                }
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 11 } } },
                y: {
                    beginAtZero: true,
                    grid: { color: '#f0f2f7' },
                    ticks: {
                        font: { size: 11 },
                        callback: v => 'R$ ' + v.toLocaleString('pt-BR', { minimumFractionDigits: 0 })
                    }
                },
            }
        }
    });
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

@extends('tenant.layouts.app')
@php
    $title    = 'Relatórios de Campanhas';
    $pageIcon = 'bar-chart-line';
@endphp

@section('topbar_actions')
<a href="{{ route('campaigns.index') }}" class="btn-secondary-sm">
    <i class="bi bi-arrow-left me-1"></i> Voltar
</a>
@endsection

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
    .report-section-body { padding: 0; }

    /* ── Filtros ─────────────────────────────────────────────────── */
    .report-filter-wrap {
        background: #fff;
        border: 1px solid #e8eaf0;
        border-radius: 14px;
        padding: 14px 20px;
        margin-bottom: 22px;
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
        grid-template-columns: repeat(6, 1fr);
        gap: 14px;
        margin-bottom: 22px;
    }

    @media (max-width: 1100px) { .kpi-grid { grid-template-columns: repeat(3, 1fr); } }
    @media (max-width: 700px)  { .kpi-grid { grid-template-columns: repeat(2, 1fr); } }

    .kpi-card {
        background: #fff;
        border-radius: 14px;
        border: 1px solid #e8eaf0;
        padding: 18px 20px;
    }

    .kpi-label {
        font-size: 11px;
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
        font-size: 24px;
        font-weight: 700;
        color: #1a1d23;
        line-height: 1;
        margin-bottom: 4px;
    }

    .kpi-delta { font-size: 12px; font-weight: 600; }
    .kpi-delta.up  { color: #10B981; }
    .kpi-delta.down{ color: #EF4444; }
    .kpi-delta.neu { color: #9ca3af; }

    /* ── Charts ──────────────────────────────────────────────────── */
    .charts-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px;
        margin-bottom: 22px;
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
        display: flex;
        align-items: center;
        gap: 6px;
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
        cursor: pointer;
        user-select: none;
    }

    .report-table thead th.num { text-align: right; }
    .report-table thead th:hover { background: #f3f4f6; }
    .report-table thead th .sort-icon { margin-left: 4px; opacity: .4; }
    .report-table thead th.sorted-asc  .sort-icon::after { content: '▲'; }
    .report-table thead th.sorted-desc .sort-icon::after { content: '▼'; }
    .report-table thead th:not(.sorted-asc):not(.sorted-desc) .sort-icon::after { content: '⇅'; }

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

    .empty-row td {
        text-align: center;
        color: #9ca3af;
        padding: 40px;
        font-size: 13px;
    }

    /* ── Extras ──────────────────────────────────────────────────── */
    .plat-dot {
        display: inline-block;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        margin-right: 5px;
        flex-shrink: 0;
    }

    .dot-facebook { background: #1877F2; }
    .dot-google   { background: #34A853; }
    .dot-manual   { background: #6366F1; }

    .badge-conv {
        font-size: 11px;
        font-weight: 600;
        padding: 2px 7px;
        border-radius: 99px;
    }

    .badge-high { background: #d1fae5; color: #065f46; }
    .badge-mid  { background: #fef3c7; color: #92400e; }
    .badge-low  { background: #f3f4f6; color: #6b7280; }

    .roi-positive { color: #10B981; font-weight: 700; }
    .roi-negative { color: #EF4444; font-weight: 700; }
    .roi-neutral  { color: #9ca3af; }

    .btn-secondary-sm {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 7px 14px;
        border: 1.5px solid #e8eaf0;
        border-radius: 9px;
        background: #fff;
        font-size: 13px;
        font-weight: 600;
        color: #374151;
        cursor: pointer;
        transition: all .15s;
        text-decoration: none;
    }
    .btn-secondary-sm:hover { background: #f0f4ff; border-color: #dbeafe; color: #3B82F6; }
</style>
@endpush

@section('content')
<div class="page-container">

    {{-- Filtros --}}
    <div class="report-filter-wrap">
        <form method="GET" action="{{ route('campaigns.reports') }}">
            <div class="report-filter-inner">
                <div>
                    <label>Período</label>
                    <select name="days">
                        <option value="30"  {{ $days == 30  ? 'selected' : '' }}>Últimos 30 dias</option>
                        <option value="60"  {{ $days == 60  ? 'selected' : '' }}>Últimos 60 dias</option>
                        <option value="90"  {{ $days == 90  ? 'selected' : '' }}>Últimos 90 dias</option>
                        <option value="180" {{ $days == 180 ? 'selected' : '' }}>Últimos 6 meses</option>
                        <option value="365" {{ $days == 365 ? 'selected' : '' }}>Último ano</option>
                    </select>
                </div>
                <div>
                    <label>Plataforma</label>
                    <select name="platform">
                        <option value="">Todas</option>
                        <option value="facebook" {{ $platform === 'facebook' ? 'selected' : '' }}>Facebook Ads</option>
                        <option value="google"   {{ $platform === 'google'   ? 'selected' : '' }}>Google Ads</option>
                        <option value="manual"   {{ $platform === 'manual'   ? 'selected' : '' }}>Manual</option>
                    </select>
                </div>
                <div>
                    <label>Status</label>
                    <select name="status">
                        <option value="">Todos</option>
                        <option value="active"   {{ $status === 'active'   ? 'selected' : '' }}>Ativos</option>
                        <option value="paused"   {{ $status === 'paused'   ? 'selected' : '' }}>Pausados</option>
                        <option value="archived" {{ $status === 'archived' ? 'selected' : '' }}>Arquivados</option>
                    </select>
                </div>
                <button type="submit" class="btn-apply">
                    <i class="bi bi-funnel me-1"></i> Filtrar
                </button>
                @if($days != 30 || $platform || $status)
                <a href="{{ route('campaigns.reports') }}" class="btn-clear">
                    <i class="bi bi-x"></i> Limpar
                </a>
                @endif
                <a href="{{ route('campaigns.reports.pdf', request()->query()) }}" class="btn-apply" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
                    <i class="bi bi-download"></i> Baixar relatório
                </a>
            </div>
        </form>
    </div>

    {{-- KPIs --}}
    @php
        $totalLeads   = $ranking->sum('leads');
        $totalConv    = $ranking->sum('conversions');
        $totalRevenue = $ranking->sum('revenue');
        $totalSpend   = $ranking->sum('spend');
        $avgConvRate  = $totalLeads > 0 ? round($totalConv / $totalLeads * 100, 1) : 0;
        $totalRoi     = $totalSpend > 0 ? round(($totalRevenue - $totalSpend) / $totalSpend * 100, 1) : null;
    @endphp
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-label"><i class="bi bi-person-plus"></i> Leads</div>
            <div class="kpi-value">{{ number_format($totalLeads) }}</div>
            <div class="kpi-delta neu">no período</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label"><i class="bi bi-check2-circle"></i> Conversões</div>
            <div class="kpi-value">{{ number_format($totalConv) }}</div>
            <div class="kpi-delta neu">vendas fechadas</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label"><i class="bi bi-percent"></i> Taxa de Conv.</div>
            <div class="kpi-value">{{ $avgConvRate }}%</div>
            <div class="kpi-delta {{ $avgConvRate >= 5 ? 'up' : ($avgConvRate >= 2 ? 'neu' : 'down') }}">
                {{ $avgConvRate >= 5 ? 'excelente' : ($avgConvRate >= 2 ? 'regular' : 'baixa') }}
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label"><i class="bi bi-currency-dollar"></i> Receita</div>
            <div class="kpi-value">{{ __('common.currency') }} {{ number_format($totalRevenue, 0, __('common.decimal_sep'), __('common.thousands_sep')) }}</div>
            <div class="kpi-delta neu">total gerado</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label"><i class="bi bi-graph-up-arrow"></i> Investimento</div>
            <div class="kpi-value">{{ __('common.currency') }} {{ number_format($totalSpend, 0, __('common.decimal_sep'), __('common.thousands_sep')) }}</div>
            <div class="kpi-delta neu">ad spend total</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label"><i class="bi bi-lightning"></i> ROI Geral</div>
            <div class="kpi-value {{ $totalRoi === null ? '' : ($totalRoi >= 0 ? 'roi-positive' : 'roi-negative') }}" style="font-size:22px">
                {{ $totalRoi !== null ? ($totalRoi >= 0 ? '+' : '') . $totalRoi . '%' : '—' }}
            </div>
            <div class="kpi-delta {{ $totalRoi === null ? 'neu' : ($totalRoi >= 0 ? 'up' : 'down') }}">
                {{ $totalRoi === null ? 'sem dados' : ($totalRoi >= 0 ? 'retorno positivo' : 'prejuízo') }}
            </div>
        </div>
    </div>

    {{-- Gráficos --}}
    @if($ranking->isNotEmpty())
    <div class="charts-row">
        <div class="chart-box">
            <div class="chart-title"><i class="bi bi-bar-chart text-primary"></i> Leads por Campanha</div>
            <canvas id="barChart" height="180"></canvas>
        </div>
        <div class="chart-box">
            <div class="chart-title"><i class="bi bi-graph-up text-primary"></i> Evolução Semanal de Leads</div>
            <canvas id="lineChart" height="180"></canvas>
        </div>
    </div>
    @endif

    {{-- Ranking de Campanhas --}}
    <div class="report-section">
        <div class="report-section-header">
            <i class="bi bi-trophy"></i> Ranking de Campanhas
            <span style="font-size:12px;font-weight:400;color:#9ca3af;margin-left:auto">clique no cabeçalho para ordenar</span>
        </div>
        <div class="report-section-body">
            <table class="report-table" id="rankingTable">
                <thead>
                    <tr>
                        <th data-col="name">Campanha <span class="sort-icon"></span></th>
                        <th data-col="platform">Plataforma <span class="sort-icon"></span></th>
                        <th data-col="leads" class="num">Leads <span class="sort-icon"></span></th>
                        <th data-col="conversions" class="num">Conversões <span class="sort-icon"></span></th>
                        <th data-col="conv_rate" class="num">Taxa Conv. <span class="sort-icon"></span></th>
                        <th data-col="spend" class="num">Investido <span class="sort-icon"></span></th>
                        <th data-col="revenue" class="num">Receita <span class="sort-icon"></span></th>
                        <th data-col="roi" class="num">ROI <span class="sort-icon"></span></th>
                        <th data-col="cpl" class="num">CPL <span class="sort-icon"></span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ranking as $row)
                    @php
                        $plat      = $row['campaign']->type ?? $row['campaign']->platform ?? 'manual';
                        $convRate  = $row['conv_rate'];
                        $convClass = $convRate >= 10 ? 'badge-high' : ($convRate >= 3 ? 'badge-mid' : 'badge-low');
                        $roiClass  = $row['roi'] === null ? 'roi-neutral' : ($row['roi'] >= 0 ? 'roi-positive' : 'roi-negative');
                    @endphp
                    <tr
                        data-name="{{ $row['campaign']->name }}"
                        data-platform="{{ $plat }}"
                        data-leads="{{ $row['leads'] }}"
                        data-conversions="{{ $row['conversions'] }}"
                        data-conv_rate="{{ $row['conv_rate'] }}"
                        data-spend="{{ $row['spend'] }}"
                        data-revenue="{{ $row['revenue'] }}"
                        data-roi="{{ $row['roi'] ?? -9999 }}"
                        data-cpl="{{ $row['cpl'] ?? 999999 }}"
                    >
                        <td>
                            <span class="plat-dot dot-{{ $plat }}"></span>
                            {{ $row['campaign']->name }}
                            @if($row['campaign']->campaign_type)
                            <small class="text-muted d-block" style="padding-left:13px">{{ $row['campaign']->campaign_type }}</small>
                            @endif
                        </td>
                        <td>{{ match($plat) { 'facebook' => 'Facebook Ads', 'google' => 'Google Ads', default => 'Manual' } }}</td>
                        <td class="num">{{ number_format($row['leads']) }}</td>
                        <td class="num">{{ number_format($row['conversions']) }}</td>
                        <td class="num"><span class="badge-conv {{ $convClass }}">{{ $row['conv_rate'] }}%</span></td>
                        <td class="num">{{ $row['spend'] > 0 ? __('common.currency') . ' ' . number_format($row['spend'], 2, __('common.decimal_sep'), __('common.thousands_sep')) : '—' }}</td>
                        <td class="num">{{ $row['revenue'] > 0 ? __('common.currency') . ' ' . number_format($row['revenue'], 2, __('common.decimal_sep'), __('common.thousands_sep')) : '—' }}</td>
                        <td class="num">
                            @if($row['roi'] !== null)
                                <span class="{{ $roiClass }}">{{ $row['roi'] >= 0 ? '+' : '' }}{{ $row['roi'] }}%</span>
                            @else
                                <span class="roi-neutral">—</span>
                            @endif
                        </td>
                        <td class="num">{{ $row['cpl'] !== null ? __('common.currency') . ' ' . number_format($row['cpl'], 2, __('common.decimal_sep'), __('common.thousands_sep')) : '—' }}</td>
                    </tr>
                    @empty
                    <tr class="empty-row"><td colspan="9">Nenhum dado para o período selecionado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Breakdown UTM --}}
    <div class="report-section">
        <div class="report-section-header">
            <i class="bi bi-link-45deg"></i> Breakdown por UTM
            <small style="font-weight:400;font-size:12px;color:#9ca3af;margin-left:6px">leads com parâmetros UTM preenchidos</small>
        </div>
        <div class="report-section-body">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>utm_source</th>
                        <th>utm_medium</th>
                        <th>utm_campaign</th>
                        <th class="num">Leads</th>
                        <th class="num">Conversões</th>
                        <th class="num">Receita</th>
                        <th class="num">Taxa Conv.</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($utmBreakdown as $row)
                    @php
                        $uConvRate  = $row['leads'] > 0 ? round($row['conversions'] / $row['leads'] * 100, 1) : 0;
                        $uConvClass = $uConvRate >= 10 ? 'badge-high' : ($uConvRate >= 3 ? 'badge-mid' : 'badge-low');
                    @endphp
                    <tr>
                        <td>{{ $row['utm_source'] }}</td>
                        <td>{{ $row['utm_medium'] }}</td>
                        <td>{{ $row['utm_campaign'] }}</td>
                        <td class="num">{{ number_format($row['leads']) }}</td>
                        <td class="num">{{ number_format($row['conversions']) }}</td>
                        <td class="num">{{ $row['revenue'] > 0 ? __('common.currency') . ' ' . number_format($row['revenue'], 2, __('common.decimal_sep'), __('common.thousands_sep')) : '—' }}</td>
                        <td class="num"><span class="badge-conv {{ $uConvClass }}">{{ $uConvRate }}%</span></td>
                    </tr>
                    @empty
                    <tr class="empty-row"><td colspan="7">Nenhum lead com dados UTM no período.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
@if($ranking->isNotEmpty())
@php
    $barLabelsJson   = json_encode($barLabels);
    $barDataJson     = json_encode($barData);
    $barColorsJson   = json_encode($barColors);
    $lineLabelsJson  = json_encode($lineLabels);
    $lineDatasetsJson = json_encode($lineDatasets);
@endphp
(function () {
    // ── Bar Chart — Leads por campanha ─────────────────────────────
    const barLabels  = {!! $barLabelsJson !!};
    const barData    = {!! $barDataJson !!};
    const barColors  = {!! $barColorsJson !!};

    new Chart(document.getElementById('barChart'), {
        type: 'bar',
        data: {
            labels: barLabels,
            datasets: [{
                label: 'Leads',
                data: barData,
                backgroundColor: barColors,
                borderRadius: 6,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: ctx => ' ' + ctx.parsed.y + ' leads' } }
            },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } },
                x: { ticks: { maxRotation: 30 } }
            }
        }
    });

    // ── Line Chart — Evolução semanal ──────────────────────────────
    const lineLabels   = {!! $lineLabelsJson !!};
    const lineDatasets = {!! $lineDatasetsJson !!};

    new Chart(document.getElementById('lineChart'), {
        type: 'line',
        data: { labels: lineLabels, datasets: lineDatasets },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom', labels: { font: { size: 11 } } }
            },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });
})();
@endif

// ── Sortable ranking table ─────────────────────────────────────────
(function () {
    const table = document.getElementById('rankingTable');
    if (!table) return;

    let sortCol = null, sortAsc = true;

    table.querySelectorAll('th[data-col]').forEach(th => {
        th.addEventListener('click', function () {
            const col = this.dataset.col;
            if (sortCol === col) {
                sortAsc = !sortAsc;
            } else {
                sortCol = col;
                sortAsc = col === 'name';
            }

            table.querySelectorAll('th').forEach(t => t.classList.remove('sorted-asc', 'sorted-desc'));
            this.classList.add(sortAsc ? 'sorted-asc' : 'sorted-desc');

            const tbody = table.querySelector('tbody');
            const rows  = Array.from(tbody.querySelectorAll('tr:not(.empty-row)'));

            rows.sort((a, b) => {
                const va  = a.dataset[col] || '';
                const vb  = b.dataset[col] || '';
                const num = !isNaN(parseFloat(va));
                const cmp = num ? parseFloat(va) - parseFloat(vb) : va.localeCompare(vb);
                return sortAsc ? cmp : -cmp;
            });

            rows.forEach(r => tbody.appendChild(r));
        });
    });
})();
</script>
@endpush

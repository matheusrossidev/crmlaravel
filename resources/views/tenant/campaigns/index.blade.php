@extends('tenant.layouts.app')
@php
    $title    = 'Campanhas';
    $pageIcon = 'megaphone';
@endphp

@section('topbar_actions')
<div class="topbar-actions" style="display:flex;gap:8px;align-items:center;">
    <a href="{{ route('campaigns.reports.pdf', ['days' => $days]) }}" style="display:inline-flex;align-items:center;gap:6px;padding:7px 16px;background:#0085f3;color:#fff;border-radius:100px;font-size:12.5px;font-weight:600;text-decoration:none;transition:background .15s;" onmouseover="this.style.background='#0070d1'" onmouseout="this.style.background='#0085f3'">
        <i class="bi bi-download"></i> Baixar relatório
    </a>
    <select id="periodFilter" onchange="window.location.href='?days='+this.value"
            style="border:1.5px solid #e8eaf0;border-radius:8px;padding:6px 12px;font-size:12.5px;color:#374151;background:#fff;outline:none;">
        @foreach([7 => '7 dias', 30 => '30 dias', 60 => '60 dias', 90 => '90 dias', 180 => '6 meses', 365 => '1 ano'] as $d => $label)
        <option value="{{ $d }}" {{ $days == $d ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
</div>
@endsection

@push('styles')
<style>
    /* ── KPI Cards ── */
    .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 20px; }
    @media (max-width: 900px) { .kpi-grid { grid-template-columns: repeat(2, 1fr); } }
    .kpi-card {
        background: #fff; border: 1.5px solid #e8eaf0; border-radius: 14px;
        padding: 18px 20px;
    }
    .kpi-label {
        font-size: 11px; font-weight: 600; color: #9ca3af;
        text-transform: uppercase; letter-spacing: .06em;
        margin-bottom: 6px; display: flex; align-items: center; gap: 6px;
    }
    .kpi-value { font-size: 26px; font-weight: 800; color: #1a1d23; line-height: 1; margin-bottom: 4px; }
    .kpi-delta { font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 3px; }
    .kpi-delta.up   { color: #10B981; }
    .kpi-delta.down { color: #EF4444; }
    .kpi-delta.neu  { color: #9ca3af; }

    /* ── Top Performers ── */
    .top-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom: 20px; }
    @media (max-width: 800px) { .top-row { grid-template-columns: 1fr; } }
    .top-card {
        background: #fff; border: 1.5px solid #e8eaf0; border-radius: 14px;
        padding: 16px 20px; display: flex; align-items: center; gap: 14px;
    }
    .top-icon {
        width: 44px; height: 44px; border-radius: 12px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center; font-size: 20px;
    }
    .top-icon.source   { background: #eff6ff; color: #2563eb; }
    .top-icon.medium   { background: #f0fdf4; color: #16a34a; }
    .top-icon.campaign { background: #fef3c7; color: #d97706; }
    .top-label { font-size: 11px; color: #9ca3af; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; }
    .top-name { font-size: 15px; font-weight: 700; color: #1a1d23; margin-top: 1px; }
    .top-count { font-size: 12px; color: #6b7280; margin-top: 1px; }

    /* ── Charts ── */
    .charts-row { display: grid; grid-template-columns: 280px 1fr 1fr; gap: 14px; margin-bottom: 20px; }
    @media (max-width: 1100px) { .charts-row { grid-template-columns: 1fr 1fr; } }
    @media (max-width: 700px) { .charts-row { grid-template-columns: 1fr; } }
    .chart-card {
        background: #fff; border: 1.5px solid #e8eaf0; border-radius: 14px;
        padding: 18px 20px; display: flex; flex-direction: column;
    }
    .chart-card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
    .chart-card-header h4 { font-size: 13px; font-weight: 700; color: #1a1d23; margin: 0; display: flex; align-items: center; gap: 6px; }
    .chart-dim-select {
        border: 1.5px solid #e8eaf0; border-radius: 6px; padding: 3px 8px;
        font-size: 11px; color: #374151; background: #fff; outline: none; cursor: pointer;
    }
    .chart-dim-select:focus { border-color: #3B82F6; }

    /* ── Table ── */
    .utm-card {
        background: #fff; border: 1.5px solid #e8eaf0; border-radius: 14px; overflow: hidden;
        margin-bottom: 20px;
    }
    .utm-card-header {
        padding: 16px 22px; border-bottom: 1px solid #f0f2f7;
        display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;
    }
    .utm-card-header h3 { font-size: 14px; font-weight: 700; color: #1a1d23; margin: 0; display: flex; align-items: center; gap: 6px; }
    .utm-filters { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
    .utm-filters input {
        border: 1.5px solid #e8eaf0; border-radius: 8px; padding: 6px 12px;
        font-size: 12.5px; color: #374151; background: #fff; outline: none;
    }
    .utm-filters input:focus { border-color: #3B82F6; }

    .utm-table-wrap { overflow-x: auto; }
    .utm-table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
    .utm-table th {
        background: #f8fafc; color: #6b7280; font-weight: 700; font-size: 11px;
        text-transform: uppercase; letter-spacing: .04em;
        padding: 10px 14px; text-align: left; white-space: nowrap;
        border-bottom: 1px solid #f0f2f7; cursor: pointer; user-select: none;
    }
    .utm-table th.num { text-align: right; }
    .utm-table th:hover { background: #f3f4f6; }
    .utm-table th .sort-icon { margin-left: 4px; opacity: .4; font-size: 10px; }
    .utm-table th.sorted-asc  .sort-icon::after { content: '\25B2'; }
    .utm-table th.sorted-desc .sort-icon::after { content: '\25BC'; }
    .utm-table th:not(.sorted-asc):not(.sorted-desc) .sort-icon::after { content: '\21C5'; }

    .utm-table td {
        padding: 10px 14px; border-bottom: 1px solid #f8f9fa;
        color: #374151; vertical-align: middle;
    }
    .utm-table tr:hover td { background: #f8fafc; }
    .utm-table td.num { text-align: right; font-variant-numeric: tabular-nums; }

    .utm-badge {
        display: inline-block; padding: 2px 8px; border-radius: 6px;
        font-size: 11px; font-weight: 600; background: #eff6ff; color: #2563eb;
    }

    .badge-conv { font-size: 11px; font-weight: 600; padding: 2px 7px; border-radius: 99px; }
    .badge-high { background: #d1fae5; color: #065f46; }
    .badge-mid  { background: #fef3c7; color: #92400e; }
    .badge-low  { background: #f3f4f6; color: #6b7280; }

    .leads-bar { display: inline-flex; align-items: center; gap: 8px; width: 100%; }
    .leads-bar-track { flex: 1; height: 6px; background: #f0f2f7; border-radius: 3px; overflow: hidden; }
    .leads-bar-fill { height: 100%; background: #3B82F6; border-radius: 3px; transition: width .3s; }

    .btn-export {
        background: #eff6ff; color: #0085f3; border: 1.5px solid #bfdbfe;
        border-radius: 8px; padding: 6px 14px; font-size: 12px; font-weight: 600;
        cursor: pointer; display: inline-flex; align-items: center; gap: 5px;
    }
    .btn-export:hover { background: #dbeafe; }

    /* ── Column toggle ── */
    .col-term, .col-content { display: none; }
    .col-term.visible, .col-content.visible { display: table-cell; }

    .col-toggle-dropdown {
        position: relative; display: inline-block;
    }
    .col-toggle-menu {
        display: none; position: absolute; right: 0; top: 100%; margin-top: 4px;
        background: #fff; border: 1.5px solid #e8eaf0; border-radius: 10px;
        padding: 10px 14px; z-index: 10; min-width: 170px;
        box-shadow: 0 4px 12px rgba(0,0,0,.08);
    }
    .col-toggle-menu.open { display: block; }
    .col-toggle-menu label {
        display: flex; align-items: center; gap: 8px; padding: 4px 0;
        font-size: 12px; color: #374151; cursor: pointer; font-weight: 500;
    }

    /* ── Drill-down ── */
    .drill-row td { padding: 0 !important; }
    .drill-body { padding: 12px 20px; background: #f8fafc; }
    .drill-table { width: 100%; border-collapse: collapse; font-size: 12px; }
    .drill-table th { padding: 6px 10px; font-size: 10px; font-weight: 700; color: #9ca3af; text-transform: uppercase; text-align: left; border-bottom: 1px solid #e8eaf0; }
    .drill-table td { padding: 6px 10px; color: #374151; border-bottom: 1px solid #f0f2f7; }

    .empty-utm { padding: 60px 20px; text-align: center; color: #9ca3af; }
    .empty-utm i { font-size: 42px; opacity: .2; display: block; margin-bottom: 10px; }

    /* ── Analytics Sections ── */
    .analytics-section {
        background: #fff; border: 1.5px solid #e8eaf0; border-radius: 14px;
        overflow: hidden; margin-bottom: 20px;
    }
    .analytics-section-header {
        padding: 16px 22px; display: flex; align-items: center; justify-content: space-between;
        cursor: pointer; user-select: none; transition: background .15s;
    }
    .analytics-section-header:hover { background: #f8fafc; }
    .analytics-section-header h3 {
        font-size: 14px; font-weight: 700; color: #1a1d23; margin: 0;
        display: flex; align-items: center; gap: 8px;
    }
    .analytics-section-header .chevron { transition: transform .2s; color: #9ca3af; font-size: 14px; }
    .analytics-section-header.open .chevron { transform: rotate(180deg); }
    .analytics-section-body { display: none; padding: 20px 22px; border-top: 1px solid #f0f2f7; }
    .analytics-section-body.open { display: block; }

    /* ── Dim Tabs ── */
    .dim-tabs { display: flex; gap: 6px; margin-bottom: 16px; flex-wrap: wrap; }
    .dim-tab {
        background: #eff6ff; color: #0085f3; border: 1.5px solid #bfdbfe;
        border-radius: 8px; padding: 6px 14px; font-size: 12px; font-weight: 600;
        cursor: pointer; transition: all .15s;
    }
    .dim-tab:hover { background: #dbeafe; }
    .dim-tab.active { background: #0085f3; color: #fff; border-color: #0085f3; }

    /* ── Granularity Toggle ── */
    .gran-tabs { display: flex; gap: 4px; }
    .gran-tab {
        background: #f3f4f6; color: #6b7280; border: none; border-radius: 6px;
        padding: 4px 12px; font-size: 11px; font-weight: 600; cursor: pointer;
    }
    .gran-tab.active { background: #0085f3; color: #fff; }

    /* ── Comparison Cards ── */
    .comparison-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    @media (max-width: 600px) { .comparison-grid { grid-template-columns: 1fr; } }
    .comparison-card {
        background: #f8fafc; border: 1.5px solid #e8eaf0; border-radius: 12px;
        padding: 20px; text-align: center;
    }
    .comparison-card h4 { font-size: 16px; font-weight: 700; color: #1a1d23; margin: 0 0 16px; }
    .comparison-metric { margin-bottom: 12px; }
    .comparison-metric-label { font-size: 11px; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 2px; }
    .comparison-metric-value { font-size: 22px; font-weight: 800; color: #1a1d23; }
    .comparison-bar-wrap { display: flex; align-items: center; gap: 8px; margin-top: 4px; }
    .comparison-bar { height: 8px; border-radius: 4px; transition: width .4s; }
    .comparison-bar.a { background: #3B82F6; }
    .comparison-bar.b { background: #10B981; }

    /* ── Heatmap ── */
    .heatmap-wrap { overflow-x: auto; }
    .heatmap-table { width: 100%; border-collapse: collapse; font-size: 12px; }
    .heatmap-table th { padding: 8px 10px; font-size: 10px; font-weight: 700; color: #6b7280; text-transform: uppercase; text-align: center; white-space: nowrap; }
    .heatmap-table th:first-child { text-align: left; }
    .heatmap-table td { padding: 6px 10px; text-align: center; }
    .heatmap-table td:first-child { text-align: left; font-weight: 600; color: #374151; white-space: nowrap; }
    .heatmap-cell {
        display: inline-block; min-width: 36px; padding: 4px 8px; border-radius: 6px;
        font-size: 11px; font-weight: 700; color: #1a1d23;
    }

    /* ── Dim analytics table ── */
    .dim-table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
    .dim-table th {
        background: #f8fafc; color: #6b7280; font-weight: 700; font-size: 11px;
        text-transform: uppercase; padding: 10px 14px; text-align: left;
        border-bottom: 1px solid #f0f2f7;
    }
    .dim-table th.num { text-align: right; }
    .dim-table td { padding: 10px 14px; border-bottom: 1px solid #f8f9fa; color: #374151; }
    .dim-table td.num { text-align: right; font-variant-numeric: tabular-nums; }
    .dim-table tr:hover td { background: #f8fafc; }

    .analytics-loader { text-align: center; padding: 30px; color: #9ca3af; font-size: 13px; }
    .analytics-empty { text-align: center; padding: 30px; color: #9ca3af; font-size: 13px; }

    /* ── Mobile ── */
    @media (max-width: 480px) {
        .kpi-grid { grid-template-columns: 1fr 1fr; }
        .kpi-value { font-size: 20px; }
        .top-card { padding: 12px 14px; }
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const DAYS   = {{ $days }};
    const ANALYTICS_URL = "{{ url('/campanhas/analytics') }}";
    const DRILLDOWN_URL = "{{ url('/campanhas/drill-down') }}";
    const COLORS = ['#3B82F6','#10B981','#F59E0B','#EF4444','#8B5CF6','#EC4899','#14B8A6','#6366F1'];

    // ────────────────────────────────────────────────────
    // Main Charts
    // ────────────────────────────────────────────────────
    const dCtx = document.getElementById('doughnutChart');
    if (dCtx) {
        new Chart(dCtx, {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($doughnutLabels) !!},
                datasets: [{
                    data: {!! json_encode($doughnutData) !!},
                    backgroundColor: COLORS.slice(0, {!! count($doughnutLabels) !!}),
                    borderWidth: 0, hoverOffset: 6,
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false, cutout: '65%',
                plugins: { legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 10, boxWidth: 12 } } }
            }
        });
    }

    // Bar chart (with dimension switching)
    let barChart = null;
    const bCtx = document.getElementById('barChart');
    if (bCtx) {
        barChart = new Chart(bCtx, {
            type: 'bar',
            data: {
                labels: {!! json_encode($barLabels) !!},
                datasets: [{
                    label: 'Leads', data: {!! json_encode($barData) !!},
                    backgroundColor: '#3B82F6', borderRadius: 6, maxBarThickness: 40,
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } }, x: { ticks: { maxRotation: 45 } } }
            }
        });
    }

    // Line chart (with dimension switching)
    let lineChart = null;
    const lCtx = document.getElementById('lineChart');
    if (lCtx) {
        lineChart = new Chart(lCtx, {
            type: 'line',
            data: {
                labels: {!! json_encode($lineLabels) !!},
                datasets: [{
                    label: 'Leads com UTM', data: {!! json_encode($lineData) !!},
                    borderColor: '#3B82F6', backgroundColor: 'rgba(59,130,246,.1)',
                    tension: 0.3, fill: true, pointRadius: 4, pointBackgroundColor: '#3B82F6',
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });
    }

    // ── Chart dimension switch ──
    window.switchBarDimension = function(dim) {
        if (!barChart) return;
        fetch(`${ANALYTICS_URL}?section=dimension&dimension=${dim}&days=${DAYS}`, {headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}})
            .then(r => r.json())
            .then(data => {
                const items = (data.data || []).slice(0, 10);
                barChart.data.labels = items.map(i => i.value);
                barChart.data.datasets[0].data = items.map(i => i.leads);
                barChart.update();
            });
    };

    window.switchLineDimension = function(dim) {
        if (!lineChart) return;
        fetch(`${ANALYTICS_URL}?section=trends&dimension=${dim}&granularity=weekly&days=${DAYS}`, {headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}})
            .then(r => r.json())
            .then(data => {
                lineChart.data.labels = data.labels || [];
                lineChart.data.datasets = (data.datasets || []).map(ds => ({
                    ...ds, pointRadius: 3, pointBackgroundColor: ds.borderColor, fill: false,
                }));
                lineChart.options.plugins.legend = { display: true, labels: { font: { size: 11 }, boxWidth: 12 } };
                lineChart.update();
            });
    };

    // ────────────────────────────────────────────────────
    // Sort table
    // ────────────────────────────────────────────────────
    (function () {
        const table = document.getElementById('utmTable');
        if (!table) return;
        let sortCol = null, sortAsc = true;

        table.querySelectorAll('th[data-col]').forEach(th => {
            th.addEventListener('click', function () {
                const col = this.dataset.col;
                if (sortCol === col) { sortAsc = !sortAsc; }
                else { sortCol = col; sortAsc = col === 'source'; }
                table.querySelectorAll('th').forEach(t => t.classList.remove('sorted-asc', 'sorted-desc'));
                this.classList.add(sortAsc ? 'sorted-asc' : 'sorted-desc');
                const tbody = table.querySelector('tbody');
                const rows  = Array.from(tbody.querySelectorAll('tr.utm-row'));
                rows.sort((a, b) => {
                    const va = a.dataset[col] || '';
                    const vb = b.dataset[col] || '';
                    const num = !isNaN(parseFloat(va)) && !['source','medium','campaign','term','content'].includes(col);
                    const cmp = num ? parseFloat(va) - parseFloat(vb) : va.localeCompare(vb);
                    return sortAsc ? cmp : -cmp;
                });
                rows.forEach(r => tbody.appendChild(r));
            });
        });
    })();

    // ────────────────────────────────────────────────────
    // Column toggles
    // ────────────────────────────────────────────────────
    document.querySelectorAll('.col-toggle-cb').forEach(cb => {
        cb.addEventListener('change', function() {
            const cls = 'col-' + this.dataset.col;
            const show = this.checked;
            document.querySelectorAll('.' + cls).forEach(el => {
                el.classList.toggle('visible', show);
            });
        });
    });

    window.toggleColMenu = function() {
        document.getElementById('colToggleMenu').classList.toggle('open');
    };
    document.addEventListener('click', function(e) {
        const menu = document.getElementById('colToggleMenu');
        const btn  = document.getElementById('colToggleBtn');
        if (menu && btn && !menu.contains(e.target) && !btn.contains(e.target)) {
            menu.classList.remove('open');
        }
    });

    // ────────────────────────────────────────────────────
    // Analytics Sections (collapsible + AJAX lazy load)
    // ────────────────────────────────────────────────────
    const sectionLoaded = {};

    window.toggleAnalyticsSection = function(section) {
        const header = document.getElementById('header-' + section);
        const body   = document.getElementById('body-' + section);
        const isOpen = body.classList.contains('open');
        header.classList.toggle('open', !isOpen);
        body.classList.toggle('open', !isOpen);

        if (!isOpen && !sectionLoaded[section]) {
            sectionLoaded[section] = true;
            loadSection(section);
        }
    };

    function loadSection(section) {
        switch (section) {
            case 'dimension':  loadDimension('source'); break;
            case 'comparison': loadComparisonValues('source'); break;
            case 'funnel':     loadFunnel('source'); break;
            case 'trends':     loadTrends('source', 'weekly'); break;
        }
    }

    // ── Dimension tab click ──
    window.dimTabClick = function(section, dim, btn) {
        btn.closest('.dim-tabs').querySelectorAll('.dim-tab').forEach(t => t.classList.remove('active'));
        btn.classList.add('active');
        switch (section) {
            case 'dimension':  loadDimension(dim); break;
            case 'comparison': loadComparisonValues(dim); break;
            case 'funnel':     loadFunnel(dim); break;
            case 'trends':     loadTrends(dim, document.querySelector('.gran-tab.active')?.dataset.gran || 'weekly'); break;
        }
    };

    // ── 1. Performance by Dimension ──
    let dimChart = null;
    function loadDimension(dim) {
        const container = document.getElementById('dimension-content');
        container.innerHTML = '<div class="analytics-loader"><i class="bi bi-arrow-repeat"></i> Carregando...</div>';
        fetch(`${ANALYTICS_URL}?section=dimension&dimension=${dim}&days=${DAYS}`, {headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}})
            .then(r => r.json())
            .then(data => {
                const items = data.data || [];
                if (!items.length) { container.innerHTML = '<div class="analytics-empty">Sem dados para esta dimensao.</div>'; return; }
                const maxL = Math.max(...items.map(i => i.leads), 1);
                let html = '<div style="display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start;">';
                html += '<div><table class="dim-table"><thead><tr><th>Valor</th><th class="num">Leads</th><th class="num">Conv.</th><th class="num">Receita</th><th class="num">Conv. %</th></tr></thead><tbody>';
                items.forEach(i => {
                    const crClass = i.conv_rate >= 10 ? 'badge-high' : (i.conv_rate >= 3 ? 'badge-mid' : 'badge-low');
                    html += `<tr><td><span class="utm-badge">${escapeHtml(i.value)}</span></td><td class="num" style="font-weight:700;">${i.leads}</td><td class="num">${i.conversions}</td><td class="num">R$ ${formatMoney(i.revenue)}</td><td class="num"><span class="badge-conv ${crClass}">${i.conv_rate}%</span></td></tr>`;
                });
                html += '</tbody></table></div>';
                html += '<div><canvas id="dimBarChart" height="250"></canvas></div>';
                html += '</div>';
                container.innerHTML = html;

                const ctx = document.getElementById('dimBarChart');
                if (dimChart) dimChart.destroy();
                dimChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: items.slice(0, 10).map(i => i.value.length > 20 ? i.value.slice(0,18)+'...' : i.value),
                        datasets: [{
                            label: 'Leads', data: items.slice(0, 10).map(i => i.leads),
                            backgroundColor: '#3B82F6', borderRadius: 6, maxBarThickness: 36,
                        }]
                    },
                    options: {
                        indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } }
                    }
                });
            });
    }

    // ── 2. Comparison ──
    window.loadComparisonValues = function(dim) {
        const selectA = document.getElementById('compareA');
        const selectB = document.getElementById('compareB');
        const wrap    = document.getElementById('comparison-results');
        wrap.innerHTML = '';
        selectA.innerHTML = '<option value="">Selecione...</option>';
        selectB.innerHTML = '<option value="">Selecione...</option>';
        document.getElementById('compareDim').value = dim;

        fetch(`${ANALYTICS_URL}?section=comparison&dimension=${dim}&days=${DAYS}`, {headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}})
            .then(r => r.json())
            .then(data => {
                (data.values || []).forEach(v => {
                    selectA.innerHTML += `<option value="${escapeHtml(v)}">${escapeHtml(v)}</option>`;
                    selectB.innerHTML += `<option value="${escapeHtml(v)}">${escapeHtml(v)}</option>`;
                });
            });
    };

    window.runComparison = function() {
        const dim = document.getElementById('compareDim').value;
        const a   = document.getElementById('compareA').value;
        const b   = document.getElementById('compareB').value;
        const wrap = document.getElementById('comparison-results');
        if (!a || !b) { if (typeof toastr !== 'undefined') toastr.warning('Selecione dois valores para comparar.'); return; }
        wrap.innerHTML = '<div class="analytics-loader"><i class="bi bi-arrow-repeat"></i> Carregando...</div>';

        fetch(`${ANALYTICS_URL}?section=comparison&dimension=${dim}&a=${encodeURIComponent(a)}&b=${encodeURIComponent(b)}&days=${DAYS}`, {headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}})
            .then(r => r.json())
            .then(data => {
                if (!data.a || !data.b) { wrap.innerHTML = '<div class="analytics-empty">Sem dados.</div>'; return; }
                const maxLeads = Math.max(data.a.leads, data.b.leads, 1);
                const maxRev   = Math.max(data.a.revenue, data.b.revenue, 1);
                wrap.innerHTML = `
                    <div class="comparison-grid">
                        ${renderComparisonCard(data.a, '#3B82F6', maxLeads, maxRev)}
                        ${renderComparisonCard(data.b, '#10B981', maxLeads, maxRev)}
                    </div>`;
            });
    };

    function renderComparisonCard(d, color, maxL, maxR) {
        const avgConvDays = d.avg_hours_conv ? (d.avg_hours_conv / 24).toFixed(1) : '—';
        return `<div class="comparison-card" style="border-top:3px solid ${color};">
            <h4>${escapeHtml(d.value)}</h4>
            <div class="comparison-metric"><div class="comparison-metric-label">Leads</div><div class="comparison-metric-value">${d.leads}</div>
                <div class="comparison-bar-wrap"><div class="comparison-bar" style="width:${(d.leads/maxL*100).toFixed(0)}%;background:${color};height:8px;border-radius:4px;"></div></div></div>
            <div class="comparison-metric"><div class="comparison-metric-label">Taxa de Conversao</div><div class="comparison-metric-value">${d.conv_rate}%</div></div>
            <div class="comparison-metric"><div class="comparison-metric-label">Receita</div><div class="comparison-metric-value">R$ ${formatMoney(d.revenue)}</div>
                <div class="comparison-bar-wrap"><div class="comparison-bar" style="width:${(d.revenue/maxR*100).toFixed(0)}%;background:${color};height:8px;border-radius:4px;"></div></div></div>
            <div class="comparison-metric"><div class="comparison-metric-label">Tempo medio (dias)</div><div class="comparison-metric-value">${avgConvDays}</div></div>
        </div>`;
    }

    // ── 3. Funnel ──
    function loadFunnel(dim) {
        const container = document.getElementById('funnel-content');
        container.innerHTML = '<div class="analytics-loader"><i class="bi bi-arrow-repeat"></i> Carregando...</div>';
        fetch(`${ANALYTICS_URL}?section=funnel&dimension=${dim}&days=${DAYS}`, {headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}})
            .then(r => r.json())
            .then(data => {
                const matrix = data.matrix || {};
                const stages = data.stages || [];
                const sources = Object.keys(matrix);
                if (!sources.length || !stages.length) { container.innerHTML = '<div class="analytics-empty">Sem dados de funil.</div>'; return; }
                let maxVal = 0;
                sources.forEach(s => stages.forEach(st => { maxVal = Math.max(maxVal, matrix[s][st] || 0); }));

                let html = '<div class="heatmap-wrap"><table class="heatmap-table"><thead><tr><th>' + escapeHtml(dim) + '</th>';
                stages.forEach(st => { html += '<th>' + escapeHtml(st) + '</th>'; });
                html += '<th>Total</th></tr></thead><tbody>';
                sources.forEach(src => {
                    let total = 0;
                    html += '<tr><td>' + escapeHtml(src) + '</td>';
                    stages.forEach(st => {
                        const v = matrix[src][st] || 0;
                        total += v;
                        const intensity = maxVal > 0 ? (v / maxVal) : 0;
                        const bg = v > 0 ? `rgba(59,130,246,${(0.1 + intensity * 0.7).toFixed(2)})` : '#f8fafc';
                        const textColor = intensity > 0.5 ? '#fff' : '#1a1d23';
                        html += `<td><span class="heatmap-cell" style="background:${bg};color:${textColor};">${v}</span></td>`;
                    });
                    html += `<td style="font-weight:700;">${total}</td></tr>`;
                });
                html += '</tbody></table></div>';
                container.innerHTML = html;
            });
    }

    // ── 4. Trends ──
    let trendsChart = null;
    function loadTrends(dim, granularity) {
        const container = document.getElementById('trends-content');
        container.innerHTML = '<div class="analytics-loader"><i class="bi bi-arrow-repeat"></i> Carregando...</div>';
        fetch(`${ANALYTICS_URL}?section=trends&dimension=${dim}&granularity=${granularity}&days=${DAYS}`, {headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}})
            .then(r => r.json())
            .then(data => {
                if (!data.labels || !data.labels.length) { container.innerHTML = '<div class="analytics-empty">Sem dados de tendencia.</div>'; return; }
                container.innerHTML = '<canvas id="trendsChart" height="280"></canvas>';
                const ctx = document.getElementById('trendsChart');
                if (trendsChart) trendsChart.destroy();
                trendsChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: (data.datasets || []).map(ds => ({
                            ...ds, pointRadius: 3, pointBackgroundColor: ds.borderColor,
                        }))
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom', labels: { font: { size: 11 }, boxWidth: 12 } } },
                        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
                    }
                });
            });
    }

    window.granTabClick = function(gran, btn) {
        btn.closest('.gran-tabs').querySelectorAll('.gran-tab').forEach(t => t.classList.remove('active'));
        btn.classList.add('active');
        const activeDim = document.querySelector('#body-trends .dim-tab.active')?.dataset.dim || 'source';
        loadTrends(activeDim, gran);
    };


    // ── Helpers ──
    function formatMoney(v) { return Number(v).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2}); }
});

// ── Filter ──
function filterUtmTable() {
    const q = document.getElementById('utmSearch').value.toLowerCase();
    document.querySelectorAll('.utm-row').forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(q) ? '' : 'none';
        const next = row.nextElementSibling;
        if (next && next.classList.contains('drill-row')) next.style.display = 'none';
    });
}

// ── Drill-down ──
function drillDown(btn, source, medium, campaign, term, content) {
    const row = btn.closest('tr');
    const existing = row.nextElementSibling;
    if (existing && existing.classList.contains('drill-row')) {
        existing.style.display = existing.style.display === 'none' ? '' : 'none';
        return;
    }

    btn.disabled = true;
    const params = new URLSearchParams({ source, medium, campaign, days: {{ $days }} });
    if (term) params.set('term', term);
    if (content) params.set('content', content);

    fetch(`${DRILLDOWN_URL}?${params}`, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(data => {
            const leads = data.leads || [];
            const tr = document.createElement('tr');
            tr.className = 'drill-row';
            const colspan = row.children.length;
            tr.innerHTML = `<td colspan="${colspan}"><div class="drill-body">${
                leads.length
                ? `<table class="drill-table"><thead><tr><th>Nome</th><th>Email</th><th>Telefone</th><th>Data</th><th>Pipeline</th><th>Etapa</th><th>UTM ID</th><th>Term</th><th>Content</th></tr></thead><tbody>${
                    leads.map(l => `<tr><td>${escapeHtml(l.name||'—')}</td><td>${escapeHtml(l.email||'—')}</td><td>${escapeHtml(l.phone||'—')}</td><td>${escapeHtml(l.created_at||'—')}</td><td>${escapeHtml(l.pipeline||'—')}</td><td>${escapeHtml(l.stage||'—')}</td><td>${escapeHtml(l.utm_id||'—')}</td><td>${escapeHtml(l.utm_term||'—')}</td><td>${escapeHtml(l.utm_content||'—')}</td></tr>`).join('')
                }</tbody></table>`
                : '<span style="color:#9ca3af;font-size:12px;">Nenhum lead encontrado.</span>'
            }</div></td>`;
            row.after(tr);
        })
        .catch(() => { if (typeof toastr !== 'undefined') toastr.error('Erro ao carregar leads.'); })
        .finally(() => { btn.disabled = false; });
}

// ── Export CSV ──
function exportCSV() {
    const table = document.getElementById('utmTable');
    if (!table) return;
    const rows = [];
    table.querySelectorAll('tr').forEach(tr => {
        if (tr.classList.contains('drill-row')) return;
        const cells = [];
        tr.querySelectorAll('th, td').forEach(cell => {
            if (cell.classList.contains('col-actions')) return;
            if (cell.style.display === 'none' || (!cell.classList.contains('visible') && (cell.classList.contains('col-term') || cell.classList.contains('col-content')))) return;
            cells.push('"' + cell.textContent.trim().replace(/"/g, '""') + '"');
        });
        if (cells.length) rows.push(cells.join(','));
    });
    const blob = new Blob(['\uFEFF' + rows.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'campanhas-utm-' + new Date().toISOString().slice(0,10) + '.csv';
    a.click();
}
</script>
@endpush

@section('content')
<div class="page-container">

    {{-- KPI Cards --}}
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-label"><i class="bi bi-person-plus"></i> Leads</div>
            <div class="kpi-value">{{ number_format($totalLeads) }}</div>
            @if($deltaLeads !== null)
                <span class="kpi-delta {{ $deltaLeads >= 0 ? 'up' : 'down' }}">
                    <i class="bi bi-arrow-{{ $deltaLeads >= 0 ? 'up' : 'down' }}-short"></i>
                    {{ abs($deltaLeads) }}% vs período anterior
                </span>
            @else
                <span class="kpi-delta neu">com UTM no periodo</span>
            @endif
        </div>
        <div class="kpi-card">
            <div class="kpi-label"><i class="bi bi-check2-circle"></i> Conversões</div>
            <div class="kpi-value">{{ number_format($totalConversions) }}</div>
            @if($deltaConv !== null)
                <span class="kpi-delta {{ $deltaConv >= 0 ? 'up' : 'down' }}">
                    <i class="bi bi-arrow-{{ $deltaConv >= 0 ? 'up' : 'down' }}-short"></i>
                    {{ abs($deltaConv) }}% vs período anterior
                </span>
            @else
                <span class="kpi-delta neu">vendas fechadas</span>
            @endif
        </div>
        <div class="kpi-card">
            <div class="kpi-label"><i class="bi bi-percent"></i> Taxa de Conv.</div>
            <div class="kpi-value">{{ $convRate }}%</div>
            <span class="kpi-delta {{ $convRate >= 5 ? 'up' : ($convRate >= 2 ? 'neu' : 'down') }}">
                {{ $convRate >= 5 ? 'excelente' : ($convRate >= 2 ? 'regular' : 'baixa') }}
            </span>
        </div>
        <div class="kpi-card">
            <div class="kpi-label"><i class="bi bi-currency-dollar"></i> Receita</div>
            <div class="kpi-value">R$ {{ number_format($totalRevenue, 0, ',', '.') }}</div>
            @if($deltaRev !== null)
                <span class="kpi-delta {{ $deltaRev >= 0 ? 'up' : 'down' }}">
                    <i class="bi bi-arrow-{{ $deltaRev >= 0 ? 'up' : 'down' }}-short"></i>
                    {{ abs($deltaRev) }}% vs período anterior
                </span>
            @else
                <span class="kpi-delta neu">total gerado</span>
            @endif
        </div>
    </div>

    {{-- Top Performers --}}
    @if($utmBreakdown->isNotEmpty())
    <div class="top-row">
        <div class="top-card">
            <div class="top-icon source"><i class="bi bi-box-arrow-in-right"></i></div>
            <div>
                <div class="top-label">Melhor Fonte</div>
                <div class="top-name">{{ $topSourceName }}</div>
                <div class="top-count">{{ number_format($topSource ?? 0) }} leads</div>
            </div>
        </div>
        <div class="top-card">
            <div class="top-icon medium"><i class="bi bi-broadcast"></i></div>
            <div>
                <div class="top-label">Melhor Mídia</div>
                <div class="top-name">{{ $topMediumName }}</div>
                <div class="top-count">{{ number_format($topMedium ?? 0) }} leads</div>
            </div>
        </div>
        <div class="top-card">
            <div class="top-icon campaign"><i class="bi bi-trophy"></i></div>
            <div>
                <div class="top-label">Melhor Campanha</div>
                @if($topCampaign)
                    <div class="top-name">{{ $topCampaign['utm_campaign'] }}</div>
                    <div class="top-count">{{ $topCampaign['conv_rate'] }}% conversão ({{ $topCampaign['leads'] }} leads)</div>
                @else
                    <div class="top-name">&mdash;</div>
                    <div class="top-count">sem dados suficientes</div>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- Charts with dimension selectors --}}
    @if($utmBreakdown->isNotEmpty())
    <div class="charts-row">
        <div class="chart-card">
            <div class="chart-card-header">
                <h4><i class="bi bi-pie-chart" style="color:#3B82F6;"></i> Por Source</h4>
            </div>
            <div style="flex:1;min-height:220px;display:flex;align-items:center;justify-content:center;">
                <canvas id="doughnutChart"></canvas>
            </div>
        </div>
        <div class="chart-card">
            <div class="chart-card-header">
                <h4><i class="bi bi-bar-chart" style="color:#3B82F6;"></i> Leads por</h4>
                <select class="chart-dim-select" onchange="switchBarDimension(this.value)">
                    <option value="campaign">Campaign</option>
                    <option value="source">Source</option>
                    <option value="medium">Medium</option>
                    <option value="term">Term</option>
                    <option value="content">Content</option>
                </select>
            </div>
            <div style="flex:1;min-height:220px;">
                <canvas id="barChart"></canvas>
            </div>
        </div>
        <div class="chart-card">
            <div class="chart-card-header">
                <h4><i class="bi bi-graph-up" style="color:#3B82F6;"></i> Tendencia por</h4>
                <select class="chart-dim-select" onchange="switchLineDimension(this.value)">
                    <option value="source">Source</option>
                    <option value="medium">Medium</option>
                    <option value="campaign">Campaign</option>
                    <option value="term">Term</option>
                    <option value="content">Content</option>
                </select>
            </div>
            <div style="flex:1;min-height:220px;">
                <canvas id="lineChart"></canvas>
            </div>
        </div>
    </div>
    @endif

    {{-- UTM Breakdown Table --}}
    <div class="utm-card">
        <div class="utm-card-header">
            <h3><i class="bi bi-funnel"></i> Detalhamento UTM</h3>
            <div class="utm-filters">
                <input type="text" id="utmSearch" placeholder="Buscar..." oninput="filterUtmTable()">
                <div class="col-toggle-dropdown">
                    <button class="btn-export" id="colToggleBtn" onclick="toggleColMenu()">
                        <i class="bi bi-sliders"></i> Colunas
                    </button>
                    <div class="col-toggle-menu" id="colToggleMenu">
                        <label><input type="checkbox" class="col-toggle-cb" data-col="term"> utm_term</label>
                        <label><input type="checkbox" class="col-toggle-cb" data-col="content"> utm_content</label>
                    </div>
                </div>
                <button class="btn-export" onclick="exportCSV()">
                    <i class="bi bi-download"></i> CSV
                </button>
            </div>
        </div>

        @if($utmBreakdown->isEmpty())
            <div class="empty-utm">
                <i class="bi bi-funnel"></i>
                <p>Nenhum lead com UTM nos ultimos {{ $days }} dias.<br>
                   UTMs sao capturados automaticamente do widget do chatbot.</p>
            </div>
        @else
            <div class="utm-table-wrap">
                <table class="utm-table" id="utmTable">
                    <thead>
                        <tr>
                            <th data-col="source">Source <span class="sort-icon"></span></th>
                            <th data-col="medium">Medium <span class="sort-icon"></span></th>
                            <th data-col="campaign">Campaign <span class="sort-icon"></span></th>
                            <th data-col="term" class="col-term">Term <span class="sort-icon"></span></th>
                            <th data-col="content" class="col-content">Content <span class="sort-icon"></span></th>
                            <th data-col="leads" class="num">Leads <span class="sort-icon"></span></th>
                            <th data-col="conversions" class="num">Conversões <span class="sort-icon"></span></th>
                            <th data-col="revenue" class="num">Receita <span class="sort-icon"></span></th>
                            <th data-col="conv_rate" class="num">Conv. % <span class="sort-icon"></span></th>
                            <th class="col-actions" style="width:40px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($utmBreakdown as $row)
                        @php
                            $cr = $row['conv_rate'];
                            $crClass = $cr >= 10 ? 'badge-high' : ($cr >= 3 ? 'badge-mid' : 'badge-low');
                            $pct = $maxLeads > 0 ? round($row['leads'] / $maxLeads * 100) : 0;
                        @endphp
                        <tr class="utm-row"
                            data-source="{{ $row['utm_source'] }}"
                            data-medium="{{ $row['utm_medium'] }}"
                            data-campaign="{{ $row['utm_campaign'] }}"
                            data-term="{{ $row['utm_term'] }}"
                            data-content="{{ $row['utm_content'] }}"
                            data-leads="{{ $row['leads'] }}"
                            data-conversions="{{ $row['conversions'] }}"
                            data-revenue="{{ $row['revenue'] }}"
                            data-conv_rate="{{ $row['conv_rate'] }}">
                            <td><span class="utm-badge">{{ $row['utm_source'] }}</span></td>
                            <td>{{ $row['utm_medium'] }}</td>
                            <td>{{ $row['utm_campaign'] }}</td>
                            <td class="col-term">{{ $row['utm_term'] ?: '—' }}</td>
                            <td class="col-content">{{ $row['utm_content'] ?: '—' }}</td>
                            <td class="num">
                                <div class="leads-bar">
                                    <span style="font-weight:700;min-width:28px;text-align:right;">{{ $row['leads'] }}</span>
                                    <div class="leads-bar-track"><div class="leads-bar-fill" style="width:{{ $pct }}%"></div></div>
                                </div>
                            </td>
                            <td class="num">{{ $row['conversions'] }}</td>
                            <td class="num">R$ {{ number_format($row['revenue'], 2, ',', '.') }}</td>
                            <td class="num"><span class="badge-conv {{ $crClass }}">{{ $cr }}%</span></td>
                            <td class="col-actions">
                                <button type="button"
                                        onclick="drillDown(this, '{{ addslashes($row['utm_source']) }}', '{{ addslashes($row['utm_medium']) }}', '{{ addslashes($row['utm_campaign']) }}', '{{ addslashes($row['utm_term']) }}', '{{ addslashes($row['utm_content']) }}')"
                                        style="background:none;border:none;color:#3B82F6;cursor:pointer;font-size:13px;padding:4px 6px;border-radius:6px;"
                                        title="Ver leads">
                                    <i class="bi bi-chevron-down"></i>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- ════════════════════════════════════════════════════════════ --}}
    {{-- ANALYTICS SECTIONS (collapsible, AJAX lazy-loaded)         --}}
    {{-- ════════════════════════════════════════════════════════════ --}}

    {{-- 1. Performance by Dimension --}}
    <div class="analytics-section">
        <div class="analytics-section-header" id="header-dimension" onclick="toggleAnalyticsSection('dimension')">
            <h3><i class="bi bi-grid-3x3-gap" style="color:#3B82F6;"></i> Performance por Dimensao UTM</h3>
            <i class="bi bi-chevron-down chevron"></i>
        </div>
        <div class="analytics-section-body" id="body-dimension">
            <div class="dim-tabs">
                <button class="dim-tab active" data-dim="source" onclick="dimTabClick('dimension','source',this)">Source</button>
                <button class="dim-tab" data-dim="medium" onclick="dimTabClick('dimension','medium',this)">Medium</button>
                <button class="dim-tab" data-dim="campaign" onclick="dimTabClick('dimension','campaign',this)">Campaign</button>
                <button class="dim-tab" data-dim="term" onclick="dimTabClick('dimension','term',this)">Term</button>
                <button class="dim-tab" data-dim="content" onclick="dimTabClick('dimension','content',this)">Content</button>
            </div>
            <div id="dimension-content"><div class="analytics-loader">Selecione uma dimensao para carregar.</div></div>
        </div>
    </div>

    {{-- 2. Comparison --}}
    <div class="analytics-section">
        <div class="analytics-section-header" id="header-comparison" onclick="toggleAnalyticsSection('comparison')">
            <h3><i class="bi bi-arrow-left-right" style="color:#3B82F6;"></i> Comparar</h3>
            <i class="bi bi-chevron-down chevron"></i>
        </div>
        <div class="analytics-section-body" id="body-comparison">
            <div style="display:flex;gap:10px;align-items:end;flex-wrap:wrap;margin-bottom:20px;">
                <div>
                    <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:4px;">Dimensao</label>
                    <div class="dim-tabs" style="margin-bottom:0;">
                        <button class="dim-tab active" onclick="dimTabClick('comparison','source',this)">Source</button>
                        <button class="dim-tab" onclick="dimTabClick('comparison','medium',this)">Medium</button>
                        <button class="dim-tab" onclick="dimTabClick('comparison','campaign',this)">Campaign</button>
                        <button class="dim-tab" onclick="dimTabClick('comparison','term',this)">Term</button>
                        <button class="dim-tab" onclick="dimTabClick('comparison','content',this)">Content</button>
                    </div>
                </div>
                <input type="hidden" id="compareDim" value="source">
                <div>
                    <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:4px;">Valor A</label>
                    <select id="compareA" class="chart-dim-select" style="min-width:140px;padding:6px 10px;font-size:12px;"><option value="">Selecione...</option></select>
                </div>
                <div>
                    <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:4px;">Valor B</label>
                    <select id="compareB" class="chart-dim-select" style="min-width:140px;padding:6px 10px;font-size:12px;"><option value="">Selecione...</option></select>
                </div>
                <button class="btn-export" onclick="runComparison()" style="background:#0085f3;color:#fff;border-color:#0085f3;">
                    <i class="bi bi-arrow-left-right"></i> Comparar
                </button>
            </div>
            <div id="comparison-results"></div>
        </div>
    </div>

    {{-- 3. Funnel Attribution --}}
    <div class="analytics-section">
        <div class="analytics-section-header" id="header-funnel" onclick="toggleAnalyticsSection('funnel')">
            <h3><i class="bi bi-bar-chart-steps" style="color:#3B82F6;"></i> Funil por Origem</h3>
            <i class="bi bi-chevron-down chevron"></i>
        </div>
        <div class="analytics-section-body" id="body-funnel">
            <div class="dim-tabs">
                <button class="dim-tab active" data-dim="source" onclick="dimTabClick('funnel','source',this)">Source</button>
                <button class="dim-tab" data-dim="medium" onclick="dimTabClick('funnel','medium',this)">Medium</button>
                <button class="dim-tab" data-dim="campaign" onclick="dimTabClick('funnel','campaign',this)">Campaign</button>
                <button class="dim-tab" data-dim="term" onclick="dimTabClick('funnel','term',this)">Term</button>
                <button class="dim-tab" data-dim="content" onclick="dimTabClick('funnel','content',this)">Content</button>
            </div>
            <div id="funnel-content"><div class="analytics-loader">Selecione uma dimensao para carregar.</div></div>
        </div>
    </div>

    {{-- 4. Trends --}}
    <div class="analytics-section">
        <div class="analytics-section-header" id="header-trends" onclick="toggleAnalyticsSection('trends')">
            <h3><i class="bi bi-graph-up-arrow" style="color:#3B82F6;"></i> Tendencias</h3>
            <i class="bi bi-chevron-down chevron"></i>
        </div>
        <div class="analytics-section-body" id="body-trends">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:16px;">
                <div class="dim-tabs" style="margin-bottom:0;">
                    <button class="dim-tab active" data-dim="source" onclick="dimTabClick('trends','source',this)">Source</button>
                    <button class="dim-tab" data-dim="medium" onclick="dimTabClick('trends','medium',this)">Medium</button>
                    <button class="dim-tab" data-dim="campaign" onclick="dimTabClick('trends','campaign',this)">Campaign</button>
                    <button class="dim-tab" data-dim="term" onclick="dimTabClick('trends','term',this)">Term</button>
                    <button class="dim-tab" data-dim="content" onclick="dimTabClick('trends','content',this)">Content</button>
                </div>
                <div class="gran-tabs">
                    <button class="gran-tab" data-gran="daily" onclick="granTabClick('daily',this)">Diario</button>
                    <button class="gran-tab active" data-gran="weekly" onclick="granTabClick('weekly',this)">Semanal</button>
                    <button class="gran-tab" data-gran="monthly" onclick="granTabClick('monthly',this)">Mensal</button>
                </div>
            </div>
            <div id="trends-content"><div class="analytics-loader">Selecione uma dimensao para carregar.</div></div>
        </div>
    </div>


</div>
@endsection

@extends('tenant.layouts.app')
@php
    $title    = 'Campanhas';
    $pageIcon = 'megaphone';
@endphp

@section('topbar_actions')
<div class="topbar-actions" style="display:flex;gap:8px;align-items:center;">
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
    .chart-card h4 { font-size: 13px; font-weight: 700; color: #1a1d23; margin: 0 0 14px; display: flex; align-items: center; gap: 6px; }

    /* ── Table ── */
    .utm-card {
        background: #fff; border: 1.5px solid #e8eaf0; border-radius: 14px; overflow: hidden;
    }
    .utm-card-header {
        padding: 16px 22px; border-bottom: 1px solid #f0f2f7;
        display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;
    }
    .utm-card-header h3 { font-size: 14px; font-weight: 700; color: #1a1d23; margin: 0; }
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
    .utm-table th.sorted-asc  .sort-icon::after { content: '▲'; }
    .utm-table th.sorted-desc .sort-icon::after { content: '▼'; }
    .utm-table th:not(.sorted-asc):not(.sorted-desc) .sort-icon::after { content: '⇅'; }

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

    .leads-bar {
        display: inline-flex; align-items: center; gap: 8px; width: 100%;
    }
    .leads-bar-track {
        flex: 1; height: 6px; background: #f0f2f7; border-radius: 3px; overflow: hidden;
    }
    .leads-bar-fill {
        height: 100%; background: #3B82F6; border-radius: 3px; transition: width .3s;
    }

    .btn-export {
        background: #eff6ff; color: #0085f3; border: 1.5px solid #bfdbfe;
        border-radius: 8px; padding: 6px 14px; font-size: 12px; font-weight: 600;
        cursor: pointer; display: inline-flex; align-items: center; gap: 5px;
    }
    .btn-export:hover { background: #dbeafe; }

    /* ── Drill-down ── */
    .drill-row td { padding: 0 !important; }
    .drill-body {
        padding: 12px 20px; background: #f8fafc;
    }
    .drill-table { width: 100%; border-collapse: collapse; font-size: 12px; }
    .drill-table th { padding: 6px 10px; font-size: 10px; font-weight: 700; color: #9ca3af; text-transform: uppercase; text-align: left; border-bottom: 1px solid #e8eaf0; }
    .drill-table td { padding: 6px 10px; color: #374151; border-bottom: 1px solid #f0f2f7; }

    .empty-utm { padding: 60px 20px; text-align: center; color: #9ca3af; }
    .empty-utm i { font-size: 42px; opacity: .2; display: block; margin-bottom: 10px; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const COLORS = ['#3B82F6','#10B981','#F59E0B','#EF4444','#8B5CF6','#EC4899','#14B8A6','#6366F1'];

    // Doughnut chart
    const dCtx = document.getElementById('doughnutChart');
    if (dCtx) {
        new Chart(dCtx, {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($doughnutLabels) !!},
                datasets: [{
                    data: {!! json_encode($doughnutData) !!},
                    backgroundColor: COLORS.slice(0, {!! count($doughnutLabels) !!}),
                    borderWidth: 0,
                    hoverOffset: 6,
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false, cutout: '65%',
                plugins: {
                    legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 10, boxWidth: 12 } }
                }
            }
        });
    }

    // Bar chart
    const bCtx = document.getElementById('barChart');
    if (bCtx) {
        new Chart(bCtx, {
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

    // Line chart
    const lCtx = document.getElementById('lineChart');
    if (lCtx) {
        new Chart(lCtx, {
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
});

// ── Sort table ──
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
                const num = !isNaN(parseFloat(va)) && col !== 'source' && col !== 'medium' && col !== 'campaign';
                const cmp = num ? parseFloat(va) - parseFloat(vb) : va.localeCompare(vb);
                return sortAsc ? cmp : -cmp;
            });
            rows.forEach(r => tbody.appendChild(r));
        });
    });
})();

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
function drillDown(btn, source, medium, campaign) {
    const row = btn.closest('tr');
    const existing = row.nextElementSibling;
    if (existing && existing.classList.contains('drill-row')) {
        existing.style.display = existing.style.display === 'none' ? '' : 'none';
        return;
    }

    btn.disabled = true;
    const params = new URLSearchParams({ source, medium, campaign, days: {{ $days }} });
    fetch(`/campanhas/drill-down?${params}`, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(data => {
            const leads = data.leads || [];
            const tr = document.createElement('tr');
            tr.className = 'drill-row';
            const colspan = row.children.length;
            tr.innerHTML = `<td colspan="${colspan}"><div class="drill-body">${
                leads.length
                ? `<table class="drill-table"><thead><tr><th>Nome</th><th>Email</th><th>Telefone</th><th>Data</th><th>Pipeline</th><th>Etapa</th></tr></thead><tbody>${
                    leads.map(l => `<tr><td>${escapeHtml(l.name||'—')}</td><td>${escapeHtml(l.email||'—')}</td><td>${escapeHtml(l.phone||'—')}</td><td>${escapeHtml(l.created_at||'—')}</td><td>${escapeHtml(l.pipeline||'—')}</td><td>${escapeHtml(l.stage||'—')}</td></tr>`).join('')
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
                <span class="kpi-delta neu">com UTM no período</span>
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
                <div class="top-label">Melhor Source</div>
                <div class="top-name">{{ $topSourceName }}</div>
                <div class="top-count">{{ number_format($topSource ?? 0) }} leads</div>
            </div>
        </div>
        <div class="top-card">
            <div class="top-icon medium"><i class="bi bi-broadcast"></i></div>
            <div>
                <div class="top-label">Melhor Medium</div>
                <div class="top-name">{{ $topMediumName }}</div>
                <div class="top-count">{{ number_format($topMedium ?? 0) }} leads</div>
            </div>
        </div>
        <div class="top-card">
            <div class="top-icon campaign"><i class="bi bi-trophy"></i></div>
            <div>
                <div class="top-label">Melhor Campaign</div>
                @if($topCampaign)
                    <div class="top-name">{{ $topCampaign['utm_campaign'] }}</div>
                    <div class="top-count">{{ $topCampaign['conv_rate'] }}% conversão ({{ $topCampaign['leads'] }} leads)</div>
                @else
                    <div class="top-name">—</div>
                    <div class="top-count">sem dados suficientes</div>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- Charts --}}
    @if($utmBreakdown->isNotEmpty())
    <div class="charts-row">
        <div class="chart-card">
            <h4><i class="bi bi-pie-chart" style="color:#3B82F6;"></i> Por Source</h4>
            <div style="flex:1;min-height:220px;display:flex;align-items:center;justify-content:center;">
                <canvas id="doughnutChart"></canvas>
            </div>
        </div>
        <div class="chart-card">
            <h4><i class="bi bi-bar-chart" style="color:#3B82F6;"></i> Leads por Campaign</h4>
            <div style="flex:1;min-height:220px;">
                <canvas id="barChart"></canvas>
            </div>
        </div>
        <div class="chart-card">
            <h4><i class="bi bi-graph-up" style="color:#3B82F6;"></i> Evolução Semanal</h4>
            <div style="flex:1;min-height:220px;">
                <canvas id="lineChart"></canvas>
            </div>
        </div>
    </div>
    @endif

    {{-- UTM Breakdown Table --}}
    <div class="utm-card">
        <div class="utm-card-header">
            <h3><i class="bi bi-funnel" style="margin-right:6px;"></i> Detalhamento UTM</h3>
            <div class="utm-filters">
                <input type="text" id="utmSearch" placeholder="Buscar..." oninput="filterUtmTable()">
                <button class="btn-export" onclick="exportCSV()">
                    <i class="bi bi-download"></i> CSV
                </button>
            </div>
        </div>

        @if($utmBreakdown->isEmpty())
            <div class="empty-utm">
                <i class="bi bi-funnel"></i>
                <p>Nenhum lead com UTM nos últimos {{ $days }} dias.<br>
                   UTMs são capturados automaticamente do widget do chatbot.</p>
            </div>
        @else
            <div class="utm-table-wrap">
                <table class="utm-table" id="utmTable">
                    <thead>
                        <tr>
                            <th data-col="source">Source <span class="sort-icon"></span></th>
                            <th data-col="medium">Medium <span class="sort-icon"></span></th>
                            <th data-col="campaign">Campaign <span class="sort-icon"></span></th>
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
                            data-leads="{{ $row['leads'] }}"
                            data-conversions="{{ $row['conversions'] }}"
                            data-revenue="{{ $row['revenue'] }}"
                            data-conv_rate="{{ $row['conv_rate'] }}">
                            <td><span class="utm-badge">{{ $row['utm_source'] }}</span></td>
                            <td>{{ $row['utm_medium'] }}</td>
                            <td>{{ $row['utm_campaign'] }}</td>
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
                                        onclick="drillDown(this, '{{ addslashes($row['utm_source']) }}', '{{ addslashes($row['utm_medium']) }}', '{{ addslashes($row['utm_campaign']) }}')"
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

</div>
@endsection

@extends('master.layouts.app')
@php
    $title    = 'Dashboard';
    $pageIcon = 'grid-1x2';
@endphp

@push('styles')
<style>
    .stats-grid {
        display: flex;
        gap: 14px;
        margin-bottom: 20px;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
        padding-bottom: 2px;
    }
    .stats-grid::-webkit-scrollbar { display: none; }
    .stat-card { min-width: 170px; flex-shrink: 0; flex: 1; }
    .stat-card {
        background: #fff;
        border-radius: 14px;
        padding: 16px 18px;
        border: 1px solid #e8eaf0;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .stat-card-top { display: flex; align-items: center; gap: 9px; }
    .stat-icon {
        width: 30px; height: 30px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        font-size: 14px; flex-shrink: 0;
    }
    .stat-icon.blue   { background: #eff6ff; color: #007DFF; }
    .stat-icon.green  { background: #f0fdf4; color: #10B981; }
    .stat-icon.purple { background: #f5f3ff; color: #8B5CF6; }
    .stat-icon.orange { background: #fffbeb; color: #F59E0B; }
    .stat-icon.red    { background: #fef2f2; color: #EF4444; }
    .stat-icon.teal   { background: #f0fdfa; color: #0d9488; }
    .stat-label { font-size: 12px; color: #97A3B7; font-weight: 500; line-height: 1.3; }
    .stat-value { font-size: 22px; font-weight: 700; color: #1a1d23; line-height: 1; }
    .stat-sub { font-size: 11px; color: #97A3B7; margin-top: 2px; }
    .trend-badge {
        display: inline-flex; align-items: center; gap: 2px;
        font-size: 11px; font-weight: 600; padding: 2px 6px; border-radius: 99px;
    }
    .trend-badge.up   { background: #f0fdf4; color: #16a34a; }
    .trend-badge.down { background: #fef2f2; color: #dc2626; }

    .charts-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }
    @media (max-width: 900px) { .charts-row { grid-template-columns: 1fr; } }
    @media (max-width: 768px) {
        .stats-grid { gap: 10px; }
        .stat-card { min-width: 150px; padding: 14px; gap: 8px; }
        .stat-value { font-size: 18px; }
        .stat-label { font-size: 11px; }
        .stat-icon { width: 26px; height: 26px; font-size: 12px; border-radius: 6px; }
        .charts-row { gap: 14px; }
    }
</style>
@endpush

@section('content')

{{-- Revenue Cards --}}
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card-top">
            <div class="stat-icon green"><i class="bi bi-cash-stack"></i></div>
            <span class="stat-label">MRR (Assinaturas)</span>
        </div>
        <div class="stat-value">R$ {{ number_format($revenue['mrr'], 2, ',', '.') }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <div class="stat-icon purple"><i class="bi bi-coin"></i></div>
            <span class="stat-label">Tokens este mês</span>
        </div>
        <div class="stat-value">R$ {{ number_format($revenue['tokens_month'], 2, ',', '.') }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <div class="stat-icon blue"><i class="bi bi-wallet2"></i></div>
            <span class="stat-label">Receita Total Mês</span>
        </div>
        <div>
            <span class="stat-value">R$ {{ number_format($revenue['total_mrr'], 2, ',', '.') }}</span>
            @if($revenueDelta != 0)
                <span class="trend-badge {{ $revenueDelta >= 0 ? 'up' : 'down' }}">
                    <i class="bi bi-arrow-{{ $revenueDelta >= 0 ? 'up' : 'down' }}-short"></i>
                    {{ abs($revenueDelta) }}%
                </span>
            @endif
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <div class="stat-icon orange"><i class="bi bi-graph-up-arrow"></i></div>
            <span class="stat-label">ARR Projetado</span>
        </div>
        <div class="stat-value">R$ {{ number_format($revenue['arr'], 2, ',', '.') }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <div class="stat-icon red"><i class="bi bi-arrow-down-circle"></i></div>
            <span class="stat-label">Churn este mês</span>
        </div>
        <div class="stat-value">{{ $revenue['churn_month'] }}</div>
    </div>
</div>

{{-- Tenant Stats --}}
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card-top">
            <div class="stat-icon blue"><i class="bi bi-buildings"></i></div>
            <span class="stat-label">Total de Empresas</span>
        </div>
        <div class="stat-value">{{ $stats['total'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <div class="stat-icon green"><i class="bi bi-check-circle"></i></div>
            <span class="stat-label">Ativas</span>
        </div>
        <div class="stat-value">{{ $stats['active'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <div class="stat-icon teal"><i class="bi bi-credit-card"></i></div>
            <span class="stat-label">Pagantes</span>
        </div>
        <div class="stat-value">{{ $stats['paying'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <div class="stat-icon orange"><i class="bi bi-hourglass-split"></i></div>
            <span class="stat-label">Em Trial</span>
        </div>
        <div class="stat-value">{{ $stats['trial'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <div class="stat-icon purple"><i class="bi bi-people"></i></div>
            <span class="stat-label">Parceiros</span>
        </div>
        <div class="stat-value">{{ $stats['partner'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <div class="stat-icon red"><i class="bi bi-x-circle"></i></div>
            <span class="stat-label">Suspensas</span>
        </div>
        <div class="stat-value">{{ $stats['suspended'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <div class="stat-icon blue"><i class="bi bi-plus-circle"></i></div>
            <span class="stat-label">Novas este mês</span>
        </div>
        <div class="stat-value">{{ $stats['new_month'] }}</div>
    </div>
</div>

{{-- Charts Row --}}
<div class="charts-row">
    {{-- Crescimento de Usuários --}}
    <div class="m-card">
        <div class="m-card-header" style="flex-wrap:wrap;gap:8px;">
            <div class="m-card-title">
                <i class="bi bi-graph-up"></i>
                Crescimento de Usuários
            </div>
            <div style="display:flex;gap:4px;">
                <button class="m-btn m-btn-ghost m-btn-sm growth-period" data-period="week" style="font-size:11px;padding:4px 10px;border-radius:6px;">1S</button>
                <button class="m-btn m-btn-ghost m-btn-sm growth-period active" data-period="month" style="font-size:11px;padding:4px 10px;border-radius:6px;background:#0085f3;color:#fff;">1M</button>
                <button class="m-btn m-btn-ghost m-btn-sm growth-period" data-period="3months" style="font-size:11px;padding:4px 10px;border-radius:6px;">3M</button>
                <button class="m-btn m-btn-ghost m-btn-sm growth-period" data-period="6months" style="font-size:11px;padding:4px 10px;border-radius:6px;">6M</button>
            </div>
        </div>
        <div style="padding:16px 20px;">
            <canvas id="growthChart" height="220"></canvas>
        </div>
    </div>

    {{-- Crescimento de Receita --}}
    <div class="m-card">
        <div class="m-card-header" style="flex-wrap:wrap;gap:8px;">
            <div class="m-card-title">
                <i class="bi bi-currency-dollar"></i>
                Receita Mensal
            </div>
            <div style="font-size:12px;color:#6b7280;">
                Projeção: <strong style="color:#0085f3;">R$ {{ number_format($projection, 2, ',', '.') }}</strong>/mês
            </div>
        </div>
        <div style="padding:16px 20px;">
            <canvas id="revenueChart" height="220"></canvas>
        </div>
    </div>
</div>

{{-- Últimos Pagamentos --}}
<div class="m-card" style="margin-bottom:20px;">
    <div class="m-card-header">
        <div class="m-card-title">
            <i class="bi bi-cash-stack"></i>
            Últimos Recebimentos
        </div>
        <a href="{{ route('master.payments') }}" class="m-btn m-btn-ghost m-btn-sm" style="text-decoration:none;">Ver todos</a>
    </div>
    <div style="overflow-x:auto;">
        <table class="m-table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Empresa</th>
                    <th>Tipo</th>
                    <th>Descrição</th>
                    <th style="text-align:right;">Valor</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentPayments as $p)
                <tr>
                    <td style="font-size:12.5px;color:#6b7280;">{{ $p->paid_at->format('d/m/Y H:i') }}</td>
                    <td style="font-weight:600;">{{ $p->tenant?->name ?? '—' }}</td>
                    <td>
                        @if($p->type === 'subscription')
                            <span class="m-badge m-badge-active">Assinatura</span>
                        @else
                            <span class="m-badge m-badge-partner">Tokens</span>
                        @endif
                    </td>
                    <td style="font-size:13px;color:#374151;">{{ $p->description ?? '—' }}</td>
                    <td style="text-align:right;font-weight:700;color:#10B981;">R$ {{ number_format($p->amount, 2, ',', '.') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align:center;padding:24px;color:#9ca3af;">Nenhum pagamento registrado ainda.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Empresas Recentes --}}
<div class="m-card">
    <div class="m-card-header">
        <div class="m-card-title">
            <i class="bi bi-building"></i>
            Empresas Recentes
        </div>
        <a href="{{ route('master.tenants') }}" class="m-btn m-btn-ghost m-btn-sm" style="text-decoration:none;">Ver todas</a>
    </div>
    <div style="overflow-x:auto;">
        <table class="m-table">
            <thead>
                <tr>
                    <th>Empresa</th>
                    <th>Plano</th>
                    <th>Status</th>
                    <th>Trial até</th>
                    <th>Criada em</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentTenants as $t)
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:32px;height:32px;border-radius:8px;background:#2a84ef;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:13px;overflow:hidden;flex-shrink:0;">
                                @if($t->logo)
                                    <img src="{{ $t->logo }}" style="width:100%;height:100%;object-fit:cover;">
                                @else
                                    {{ strtoupper(substr($t->name, 0, 1)) }}
                                @endif
                            </div>
                            <div>
                                <div style="font-weight:600;">{{ $t->name }}</div>
                                @if($t->phone)
                                    <a href="https://wa.me/{{ preg_replace('/\D/', '', $t->phone) }}" target="_blank" style="font-size:11px;color:#25D366;text-decoration:none;"><i class="bi bi-whatsapp"></i> {{ $t->phone }}</a>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td><span class="m-badge" style="background:#EFF6FF;color:#1D4ED8;">{{ $t->plan }}</span></td>
                    <td>
                        @php
                            $bc = match($t->status) { 'active' => 'm-badge-active', 'trial' => 'm-badge-trial', 'partner' => 'm-badge-partner', 'suspended' => 'm-badge-suspended', default => 'm-badge-inactive' };
                            $sl = match($t->status) { 'active' => 'Ativo', 'trial' => 'Trial', 'partner' => 'Parceiro', 'suspended' => 'Suspenso', 'inactive' => 'Inativo', default => ucfirst($t->status) };
                        @endphp
                        <span class="m-badge {{ $bc }}">{{ $sl }}</span>
                    </td>
                    <td style="font-size:12.5px;color:#6b7280;">{{ $t->trial_ends_at ? $t->trial_ends_at->format('d/m/Y') : '—' }}</td>
                    <td style="font-size:12.5px;color:#6b7280;">{{ $t->created_at->format('d/m/Y') }}</td>
                    <td>
                        <a href="{{ route('master.tenants.show', $t->id) }}" class="m-btn m-btn-ghost m-btn-sm" style="text-decoration:none;">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center;padding:32px;color:#9ca3af;">Nenhuma empresa cadastrada.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
(function(){
    // ── Growth Chart ─────────────────────────────────────────────────
    const allData = @json($monthlyGrowth);
    let growthChart = null;

    function sliceData(period) {
        switch(period) {
            case 'week':    return allData.slice(-1);
            case 'month':   return allData.slice(-4);
            case '3months': return allData.slice(-13);
            case '6months': return allData;
            default:        return allData.slice(-4);
        }
    }

    function renderGrowthChart(period) {
        const data = sliceData(period);
        if (growthChart) growthChart.destroy();
        const ctx = document.getElementById('growthChart').getContext('2d');

        growthChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.map(d => d.label),
                datasets: [
                    { label: 'Trial', data: data.map(d => d.trial), borderColor: '#F59E0B', backgroundColor: 'rgba(245,158,11,0.1)', borderWidth: 2, fill: true, tension: 0.4, pointRadius: 3, pointBackgroundColor: '#fff', pointBorderColor: '#F59E0B', pointBorderWidth: 2 },
                    { label: 'Pagantes', data: data.map(d => d.paying), borderColor: '#0085f3', backgroundColor: 'rgba(0,133,243,0.1)', borderWidth: 2, fill: true, tension: 0.4, pointRadius: 3, pointBackgroundColor: '#fff', pointBorderColor: '#0085f3', pointBorderWidth: 2 },
                    { label: 'Parceiros', data: data.map(d => d.partner), borderColor: '#8B5CF6', backgroundColor: 'rgba(139,92,246,0.1)', borderWidth: 2, fill: true, tension: 0.4, pointRadius: 3, pointBackgroundColor: '#fff', pointBorderColor: '#8B5CF6', pointBorderWidth: 2 },
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, pointStyle: 'circle', padding: 16, font: { size: 11 } } },
                    tooltip: { backgroundColor: '#1a1d23', cornerRadius: 8, padding: 10, titleFont: { size: 12 }, bodyFont: { size: 12 } }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 10 }, color: '#9ca3af', maxRotation: 0 }, border: { display: false } },
                    y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 10 }, color: '#9ca3af' }, grid: { color: '#f3f4f6' }, border: { display: false } }
                }
            }
        });
    }

    document.querySelectorAll('.growth-period').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.growth-period').forEach(b => { b.style.background = ''; b.style.color = ''; });
            this.style.background = '#0085f3'; this.style.color = '#fff';
            renderGrowthChart(this.dataset.period);
        });
    });
    renderGrowthChart('month');

    // ── Revenue Chart ────────────────────────────────────────────────
    const revData = @json($revenueGrowth);
    const projection = {{ $projection }};

    // Add projection as next month
    const projLabel = 'Projeção';
    const labels = revData.map(d => d.label).concat([projLabel]);
    const subData = revData.map(d => d.subscriptions);
    const tokData = revData.map(d => d.tokens);
    const projSub = subData.length > 0 ? subData[subData.length - 1] : 0;
    const projTok = projection - projSub > 0 ? projection - projSub : 0;

    new Chart(document.getElementById('revenueChart'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Assinaturas',
                    data: [...subData, projSub],
                    backgroundColor: revData.map(() => 'rgba(0,133,243,0.7)').concat(['rgba(0,133,243,0.25)']),
                    borderRadius: 4, borderSkipped: false,
                },
                {
                    label: 'Tokens',
                    data: [...tokData, projTok],
                    backgroundColor: revData.map(() => 'rgba(139,92,246,0.6)').concat(['rgba(139,92,246,0.2)']),
                    borderRadius: 4, borderSkipped: false,
                },
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, pointStyle: 'rect', padding: 16, font: { size: 11 } } },
                tooltip: {
                    backgroundColor: '#1a1d23', cornerRadius: 8, padding: 10,
                    callbacks: { label: ctx => ctx.dataset.label + ': R$ ' + ctx.parsed.y.toLocaleString('pt-BR', { minimumFractionDigits: 2 }) }
                }
            },
            scales: {
                x: { stacked: true, grid: { display: false }, ticks: { font: { size: 10 }, color: '#9ca3af', maxRotation: 45 }, border: { display: false } },
                y: {
                    stacked: true, beginAtZero: true,
                    ticks: { font: { size: 10 }, color: '#9ca3af', callback: v => 'R$ ' + v.toLocaleString('pt-BR') },
                    grid: { color: '#f3f4f6' }, border: { display: false }
                }
            }
        }
    });
})();
</script>
@endpush

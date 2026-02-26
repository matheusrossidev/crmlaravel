@extends('master.layouts.app')
@php
    $title    = 'Uso de Tokens IA';
    $pageIcon = 'cpu';
@endphp

@section('content')

{{-- Stat header --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:24px;">
    <div class="m-stat">
        <div class="m-stat-label">Total de Tokens (all-time)</div>
        <div class="m-stat-value" style="color:#3B82F6;">{{ number_format($grandTotal) }}</div>
    </div>
    <div class="m-stat">
        <div class="m-stat-label">Dias com uso (30d)</div>
        <div class="m-stat-value" style="color:#10B981;">{{ $daily->count() }}</div>
    </div>
    <div class="m-stat">
        <div class="m-stat-label">Tokens nos últimos 30d</div>
        <div class="m-stat-value" style="color:#F59E0B;">{{ number_format($daily->sum('total')) }}</div>
    </div>
</div>

{{-- Chart --}}
<div class="m-card" style="margin-bottom:24px;">
    <div class="m-card-header">
        <div class="m-card-title"><i class="bi bi-graph-up"></i> Tokens por Dia (últimos 30 dias)</div>
    </div>
    <div style="position:relative;height:260px;padding:8px 0;">
        <canvas id="tokenChart"></canvas>
    </div>
</div>

{{-- Top tenants table --}}
<div class="m-card">
    <div class="m-card-header">
        <div class="m-card-title"><i class="bi bi-trophy"></i> Top Empresas por Uso</div>
    </div>
    <div style="overflow-x:auto;">
        <table class="m-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Empresa</th>
                    <th>Requisições</th>
                    <th>Tokens Totais</th>
                    <th>Média/req</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($topTenants as $i => $row)
                <tr>
                    <td style="color:#9ca3af;font-size:12px;">{{ $i + 1 }}</td>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            @if($row->tenant?->logo)
                                <img src="{{ $row->tenant->logo }}" style="width:32px;height:32px;border-radius:8px;object-fit:cover;" alt="">
                            @else
                                <div style="width:32px;height:32px;border-radius:8px;background:#3B82F6;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:13px;">
                                    {{ strtoupper(substr($row->tenant?->name ?? '?', 0, 1)) }}
                                </div>
                            @endif
                            <span style="font-weight:600;">{{ $row->tenant?->name ?? 'Tenant #'.$row->tenant_id }}</span>
                        </div>
                    </td>
                    <td>{{ number_format($row->requests) }}</td>
                    <td style="font-weight:600;">{{ number_format($row->total) }}</td>
                    <td style="color:#6b7280;">{{ $row->requests > 0 ? number_format($row->total / $row->requests) : '—' }}</td>
                    <td>
                        @if($row->tenant)
                        <a href="{{ route('master.usage.show', $row->tenant) }}" class="m-btn m-btn-ghost m-btn-sm">
                            <i class="bi bi-arrow-right"></i>
                        </a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center;color:#9ca3af;padding:32px;">
                        Nenhum dado de uso registrado ainda.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const dailyData = @json($daily);

const labels = dailyData.map(d => {
    const [y, m, day] = d.day.split('-');
    return `${day}/${m}`;
});
const values = dailyData.map(d => parseInt(d.total));

const ctx = document.getElementById('tokenChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels,
        datasets: [{
            label: 'Tokens',
            data: values,
            backgroundColor: 'rgba(59,130,246,0.7)',
            borderColor: '#3B82F6',
            borderWidth: 1,
            borderRadius: 4,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => ' ' + ctx.parsed.y.toLocaleString('pt-BR') + ' tokens'
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: v => v >= 1000 ? (v/1000).toFixed(1)+'k' : v
                }
            }
        }
    }
});
</script>
@endpush

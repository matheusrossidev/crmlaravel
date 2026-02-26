@extends('master.layouts.app')
@php
    $title    = 'Uso — ' . $tenant->name;
    $pageIcon = 'cpu';
@endphp

@section('topbar_actions')
<a href="{{ route('master.usage.index') }}" class="m-btn m-btn-ghost">
    <i class="bi bi-arrow-left"></i> Voltar
</a>
@endsection

@section('content')

<div style="display:flex;align-items:center;gap:14px;margin-bottom:24px;">
    @if($tenant->logo)
        <img src="{{ $tenant->logo }}" style="width:48px;height:48px;border-radius:12px;object-fit:cover;" alt="">
    @else
        <div style="width:48px;height:48px;border-radius:12px;background:#3B82F6;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:20px;">
            {{ strtoupper(substr($tenant->name, 0, 1)) }}
        </div>
    @endif
    <div>
        <div style="font-size:18px;font-weight:700;color:#1a1d23;">{{ $tenant->name }}</div>
        <div style="font-size:13px;color:#6b7280;">{{ $tenant->slug }} · Total: <strong>{{ number_format($tenantTotal) }} tokens</strong></div>
    </div>
</div>

<div class="m-card" style="margin-bottom:24px;">
    <div class="m-card-header">
        <div class="m-card-title"><i class="bi bi-graph-up"></i> Tokens por Dia (últimos 30 dias)</div>
    </div>
    <div style="position:relative;height:240px;padding:8px 0;">
        <canvas id="tenantChart"></canvas>
    </div>
</div>

<div class="m-card">
    <div class="m-card-header">
        <div class="m-card-title"><i class="bi bi-table"></i> Detalhe Diário</div>
    </div>
    <div style="overflow-x:auto;">
        <table class="m-table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Requisições</th>
                    <th>Tokens Totais</th>
                    <th>Média/req</th>
                </tr>
            </thead>
            <tbody>
                @forelse($daily as $row)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($row->day)->format('d/m/Y') }}</td>
                    <td>{{ number_format($row->requests) }}</td>
                    <td style="font-weight:600;">{{ number_format($row->total) }}</td>
                    <td style="color:#6b7280;">{{ $row->requests > 0 ? number_format($row->total / $row->requests) : '—' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" style="text-align:center;color:#9ca3af;padding:32px;">
                        Nenhum uso nos últimos 30 dias.
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
const labels = dailyData.map(d => { const [y,m,day] = d.day.split('-'); return `${day}/${m}`; });
const values = dailyData.map(d => parseInt(d.total));

new Chart(document.getElementById('tenantChart').getContext('2d'), {
    type: 'line',
    data: {
        labels,
        datasets: [{
            label: 'Tokens',
            data: values,
            fill: true,
            backgroundColor: 'rgba(59,130,246,0.1)',
            borderColor: '#3B82F6',
            borderWidth: 2,
            pointRadius: 3,
            tension: 0.3,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { callback: v => v >= 1000 ? (v/1000).toFixed(1)+'k' : v }
            }
        }
    }
});
</script>
@endpush

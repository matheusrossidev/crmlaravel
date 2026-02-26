@extends('master.layouts.app')
@php
    $title    = 'Monitoramento do Sistema';
    $pageIcon = 'activity';
@endphp

@section('content')

<div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
    <div id="statusDot" style="width:10px;height:10px;border-radius:50%;background:#10B981;"></div>
    <span style="font-size:13px;color:#6b7280;">Atualização automática a cada <strong>10s</strong></span>
    <span id="lastUpdate" style="font-size:12px;color:#9ca3af;margin-left:auto;"></span>
</div>

{{-- Cards de métricas --}}
<div id="statsGrid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin-bottom:24px;">

    {{-- CPU --}}
    <div class="m-stat" id="card-cpu">
        <div class="m-stat-label"><i class="bi bi-cpu"></i> CPU Load (1/5/15 min)</div>
        <div id="cpu-val" class="m-stat-value" style="color:#3B82F6;">—</div>
        <div id="cpu-bar" style="margin-top:10px;height:6px;border-radius:3px;background:#e5e7eb;overflow:hidden;">
            <div id="cpu-bar-fill" style="height:100%;background:#3B82F6;width:0%;transition:width .5s;border-radius:3px;"></div>
        </div>
    </div>

    {{-- RAM PHP --}}
    <div class="m-stat" id="card-php-mem">
        <div class="m-stat-label"><i class="bi bi-memory"></i> Memória PHP</div>
        <div id="php-mem-val" class="m-stat-value" style="color:#8B5CF6;">—</div>
        <div style="font-size:11.5px;color:#9ca3af;margin-top:4px;" id="php-mem-peak">Pico: —</div>
    </div>

    {{-- RAM Sistema --}}
    <div class="m-stat" id="card-ram">
        <div class="m-stat-label"><i class="bi bi-hdd-rack"></i> RAM do Servidor</div>
        <div id="ram-val" class="m-stat-value" style="color:#F59E0B;">—</div>
        <div id="ram-bar" style="margin-top:10px;height:6px;border-radius:3px;background:#e5e7eb;overflow:hidden;">
            <div id="ram-bar-fill" style="height:100%;background:#F59E0B;width:0%;transition:width .5s;border-radius:3px;"></div>
        </div>
        <div style="font-size:11px;color:#9ca3af;margin-top:4px;" id="ram-detail">—</div>
    </div>

    {{-- Disco --}}
    <div class="m-stat" id="card-disk">
        <div class="m-stat-label"><i class="bi bi-device-hdd"></i> Disco</div>
        <div id="disk-val" class="m-stat-value" style="color:#10B981;">—</div>
        <div id="disk-bar" style="margin-top:10px;height:6px;border-radius:3px;background:#e5e7eb;overflow:hidden;">
            <div id="disk-bar-fill" style="height:100%;background:#10B981;width:0%;transition:width .5s;border-radius:3px;"></div>
        </div>
        <div style="font-size:11px;color:#9ca3af;margin-top:4px;" id="disk-detail">—</div>
    </div>

</div>

{{-- Info do ambiente --}}
<div class="m-card">
    <div class="m-card-header">
        <div class="m-card-title"><i class="bi bi-info-circle"></i> Informações do Ambiente</div>
    </div>
    <table class="m-table">
        <tbody>
            <tr><td style="font-weight:600;width:180px;">PHP Version</td><td id="env-php">—</td></tr>
            <tr><td style="font-weight:600;">Laravel Env</td><td id="env-laravel">—</td></tr>
            <tr><td style="font-weight:600;">Última atualização</td><td id="env-ts">—</td></tr>
        </tbody>
    </table>
</div>

@endsection

@push('scripts')
<script>
const ROUTE_STATS = "{{ route('master.system.stats') }}";
const CSRF = document.querySelector('meta[name=csrf-token]')?.content;

function fmtBytes(bytes) {
    if (bytes <= 0) return '0 B';
    if (bytes < 1048576) return (bytes/1024).toFixed(1) + ' KB';
    if (bytes < 1073741824) return (bytes/1048576).toFixed(1) + ' MB';
    return (bytes/1073741824).toFixed(2) + ' GB';
}

function fmtKB(kb) {
    if (kb <= 0) return 'N/A';
    if (kb < 1024) return kb + ' KB';
    if (kb < 1048576) return (kb/1024).toFixed(1) + ' MB';
    return (kb/1048576).toFixed(2) + ' GB';
}

function pct(a, b) { return b > 0 ? Math.min(100, Math.round(a / b * 100)) : 0; }

async function fetchStats() {
    document.getElementById('statusDot').style.background = '#F59E0B';
    try {
        const res = await fetch(ROUTE_STATS, {
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
        });
        const d = await res.json();

        // CPU
        const load1 = parseFloat(d.cpu_load?.[0] ?? 0).toFixed(2);
        const load5 = parseFloat(d.cpu_load?.[1] ?? 0).toFixed(2);
        const load15 = parseFloat(d.cpu_load?.[2] ?? 0).toFixed(2);
        document.getElementById('cpu-val').textContent = `${load1} / ${load5} / ${load15}`;
        const cpuPct = Math.min(100, Math.round(parseFloat(load1) * 100));
        document.getElementById('cpu-bar-fill').style.width = cpuPct + '%';
        document.getElementById('cpu-bar-fill').style.background = cpuPct > 80 ? '#ef4444' : cpuPct > 50 ? '#F59E0B' : '#3B82F6';

        // PHP memory
        document.getElementById('php-mem-val').textContent = fmtBytes(d.memory_php || 0);
        document.getElementById('php-mem-peak').textContent = 'Pico: ' + fmtBytes(d.memory_peak || 0);

        // RAM do servidor
        const ramData = d.ram || {};
        if (ramData.total_kb > 0) {
            const used = ramData.total_kb - ramData.available_kb;
            const ramPct = pct(used, ramData.total_kb);
            document.getElementById('ram-val').textContent = fmtKB(used) + ' / ' + fmtKB(ramData.total_kb) + ' (' + ramPct + '%)';
            document.getElementById('ram-bar-fill').style.width = ramPct + '%';
            document.getElementById('ram-bar-fill').style.background = ramPct > 85 ? '#ef4444' : ramPct > 65 ? '#F59E0B' : '#10B981';
            document.getElementById('ram-detail').textContent = 'Disponível: ' + fmtKB(ramData.available_kb);
        } else {
            document.getElementById('ram-val').textContent = 'N/A (Linux only)';
        }

        // Disco
        const diskFree = d.disk_free || 0;
        const diskTotal = d.disk_total || 0;
        const diskUsed = diskTotal - diskFree;
        const diskPct = pct(diskUsed, diskTotal);
        document.getElementById('disk-val').textContent = fmtBytes(diskFree) + ' livre';
        document.getElementById('disk-bar-fill').style.width = diskPct + '%';
        document.getElementById('disk-bar-fill').style.background = diskPct > 90 ? '#ef4444' : diskPct > 70 ? '#F59E0B' : '#10B981';
        document.getElementById('disk-detail').textContent = `Usado: ${fmtBytes(diskUsed)} / ${fmtBytes(diskTotal)} (${diskPct}%)`;

        // Env info
        document.getElementById('env-php').textContent = d.php_version || '—';
        document.getElementById('env-laravel').textContent = d.laravel_env || '—';
        document.getElementById('env-ts').textContent = new Date(d.timestamp).toLocaleString('pt-BR');

        document.getElementById('lastUpdate').textContent = 'Atualizado: ' + new Date().toLocaleTimeString('pt-BR');
        document.getElementById('statusDot').style.background = '#10B981';
    } catch (e) {
        document.getElementById('statusDot').style.background = '#ef4444';
    }
}

fetchStats();
setInterval(fetchStats, 10000);
</script>
@endpush

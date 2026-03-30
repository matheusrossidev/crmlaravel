@extends('tenant.layouts.app')
@php $title = __('nav.goals') ?? 'Metas'; $pageIcon = 'trophy'; @endphp

@push('styles')
<style>
    .goal-card { background: #fff; border-radius: 14px; border: 1px solid #e8eaf0; padding: 20px; margin-bottom: 14px; }
    .goal-bar-wrap { height: 8px; background: #f3f4f6; border-radius: 99px; overflow: hidden; margin-top: 8px; }
    .goal-bar-fill { height: 100%; border-radius: 99px; transition: width .4s; }
    .goal-bar-fill.achieved { background: #10B981; }
    .goal-bar-fill.on_track { background: #F59E0B; }
    .goal-bar-fill.behind { background: #EF4444; }
    .goal-status { font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 99px; }
    .goal-status.achieved { background: #d1fae5; color: #065f46; }
    .goal-status.on_track { background: #fef3c7; color: #92400e; }
    .goal-status.behind { background: #fee2e2; color: #991b1b; }

    .page-drawer-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.3); z-index: 5000; }
    .page-drawer-overlay.open { display: block; }
    .page-drawer {
        position: fixed; top: 0; right: -440px; width: 420px; height: 100vh;
        background: #fff; z-index: 5001; box-shadow: -4px 0 24px rgba(0,0,0,.1);
        display: flex; flex-direction: column; transition: right .25s cubic-bezier(.4,0,.2,1);
    }
    .page-drawer.open { right: 0; }
    @media (max-width: 480px) { .page-drawer { width: 100%; right: -100%; } }
</style>
@endpush

@section('content')
<div class="page-container">

    <div style="margin-bottom:20px;">
        <div style="font-size:11px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:#97A3B7;margin-bottom:4px;">CRM</div>
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
            <div>
                <h1 style="font-family:'Plus Jakarta Sans',sans-serif;font-size:22px;font-weight:700;color:#1a1d23;margin:0 0 4px;">Metas de Vendas</h1>
                <p style="font-size:13.5px;color:#677489;margin:0;">Acompanhe o progresso da equipe em tempo real.</p>
            </div>
            <button class="btn-primary-sm" onclick="openGoalDrawer()">
                <i class="bi bi-plus-lg"></i> Nova Meta
            </button>
        </div>
    </div>

    @if($goals->isEmpty())
        <div class="content-card" style="padding:60px;text-align:center;color:#9ca3af;">
            <i class="bi bi-flag" style="font-size:40px;display:block;margin-bottom:12px;color:#d1d5db;"></i>
            <p style="font-size:14px;font-weight:600;color:#374151;margin:0 0 4px;">Nenhuma meta criada</p>
            <p style="font-size:13px;margin:0;">Crie metas para acompanhar o desempenho da equipe.</p>
        </div>
    @else
        @foreach($goals as $item)
        @php
            $g = $item['goal'];
            $p = $item['progress'];
            $typeLabels = ['leads_won' => 'Vendas', 'revenue' => 'Receita', 'leads_created' => 'Leads criados'];
            $periodLabels = ['monthly' => 'Mensal', 'weekly' => 'Semanal', 'quarterly' => 'Trimestral'];
            $statusLabels = ['achieved' => 'Atingida', 'on_track' => 'No caminho', 'behind' => 'Atrasada'];
        @endphp
        <div class="goal-card">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;flex-wrap:wrap;gap:8px;">
                <div>
                    <div style="font-size:15px;font-weight:700;color:#1a1d23;">
                        {{ $g->user?->name ?? 'Time inteiro' }}
                    </div>
                    <div style="font-size:12px;color:#6b7280;">
                        {{ $typeLabels[$g->type] ?? $g->type }} · {{ $periodLabels[$g->period] ?? $g->period }}
                        · {{ $g->start_date->format('d/m') }} a {{ $g->end_date->format('d/m/Y') }}
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <span class="goal-status {{ $p['status'] }}">{{ $statusLabels[$p['status']] ?? $p['status'] }}</span>
                    <button onclick="deleteGoal({{ $g->id }})" style="background:#fef2f2;color:#ef4444;border:none;border-radius:6px;padding:4px 8px;cursor:pointer;font-size:12px;">
                        <i class="bi bi-trash3"></i>
                    </button>
                </div>
            </div>
            <div style="display:flex;align-items:baseline;gap:8px;">
                <span style="font-size:24px;font-weight:800;color:#1a1d23;">
                    @if($g->type === 'revenue')
                        R$ {{ number_format($p['current'], 2, ',', '.') }}
                    @else
                        {{ number_format($p['current']) }}
                    @endif
                </span>
                <span style="font-size:13px;color:#6b7280;">
                    / @if($g->type === 'revenue')
                        R$ {{ number_format($p['target'], 2, ',', '.') }}
                    @else
                        {{ number_format($p['target']) }}
                    @endif
                </span>
                <span style="font-size:14px;font-weight:700;color:{{ $p['status'] === 'achieved' ? '#10B981' : ($p['status'] === 'on_track' ? '#F59E0B' : '#EF4444') }};">
                    {{ $p['percentage'] }}%
                </span>
            </div>
            <div class="goal-bar-wrap">
                <div class="goal-bar-fill {{ $p['status'] }}" style="width:{{ $p['percentage'] }}%;"></div>
            </div>
        </div>
        @endforeach
    @endif
</div>

{{-- Create Drawer --}}
<div class="page-drawer-overlay" id="goalDrawerOverlay" onclick="closeGoalDrawer()"></div>
<div class="page-drawer" id="goalDrawer">
    <div class="notif-drawer-header">
        <h3 style="margin:0;font-size:16px;font-weight:700;display:flex;align-items:center;gap:8px;">
            <i class="bi bi-trophy" style="color:#F59E0B;"></i> Nova Meta
        </h3>
        <button onclick="closeGoalDrawer()" style="background:none;border:none;font-size:18px;color:#9ca3af;cursor:pointer;"><i class="bi bi-x-lg"></i></button>
    </div>
    <div style="flex:1;overflow-y:auto;padding:24px;">
        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:4px;">Vendedor</label>
            <select id="gUser" style="width:100%;padding:9px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;">
                <option value="">Time inteiro</option>
                @foreach($users as $u)
                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                @endforeach
            </select>
        </div>
        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:4px;">Tipo de meta *</label>
            <select id="gType" style="width:100%;padding:9px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;">
                <option value="leads_won">Vendas fechadas</option>
                <option value="revenue">Receita (R$)</option>
                <option value="leads_created">Leads criados</option>
            </select>
        </div>
        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:4px;">Período</label>
            <select id="gPeriod" onchange="autoFillDates()" style="width:100%;padding:9px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;">
                <option value="monthly">Mensal</option>
                <option value="weekly">Semanal</option>
                <option value="quarterly">Trimestral</option>
            </select>
        </div>
        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:4px;">Meta *</label>
            <input type="number" id="gTarget" placeholder="Ex: 10 ou 50000" min="1" style="width:100%;padding:9px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;">
        </div>
        <div style="display:flex;gap:10px;margin-bottom:14px;">
            <div style="flex:1;">
                <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:4px;">Início</label>
                <input type="date" id="gStart" style="width:100%;padding:9px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;">
            </div>
            <div style="flex:1;">
                <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:4px;">Fim</label>
                <input type="date" id="gEnd" style="width:100%;padding:9px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;">
            </div>
        </div>
    </div>
    <div style="padding:16px 24px;border-top:1px solid #f0f2f7;display:flex;gap:10px;justify-content:flex-end;">
        <button class="btn-outline-sm" onclick="closeGoalDrawer()">Cancelar</button>
        <button class="btn-primary-sm" id="btnCreateGoal" onclick="createGoal()"><i class="bi bi-check-lg"></i> Criar Meta</button>
    </div>
</div>

@endsection

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

function autoFillDates() {
    const period = document.getElementById('gPeriod').value;
    const now = new Date();
    let start, end;
    if (period === 'weekly') {
        const day = now.getDay(); const diff = now.getDate() - day + (day === 0 ? -6 : 1);
        start = new Date(now.setDate(diff)); end = new Date(start); end.setDate(end.getDate() + 6);
    } else if (period === 'quarterly') {
        const q = Math.floor(now.getMonth() / 3); start = new Date(now.getFullYear(), q * 3, 1);
        end = new Date(now.getFullYear(), q * 3 + 3, 0);
    } else {
        start = new Date(now.getFullYear(), now.getMonth(), 1);
        end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
    }
    document.getElementById('gStart').value = start.toISOString().slice(0, 10);
    document.getElementById('gEnd').value = end.toISOString().slice(0, 10);
}
autoFillDates();

function openGoalDrawer() {
    document.getElementById('goalDrawerOverlay').classList.add('open');
    document.getElementById('goalDrawer').classList.add('open');
    autoFillDates();
}

function closeGoalDrawer() {
    document.getElementById('goalDrawerOverlay').classList.remove('open');
    document.getElementById('goalDrawer').classList.remove('open');
}

async function createGoal() {
    const target = parseFloat(document.getElementById('gTarget').value);
    if (!target || target <= 0) { toastr.error('Defina a meta'); return; }

    document.getElementById('btnCreateGoal').disabled = true;
    const res = await fetch('{{ route("goals.store") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' },
        body: JSON.stringify({
            user_id: document.getElementById('gUser').value || null,
            type: document.getElementById('gType').value,
            period: document.getElementById('gPeriod').value,
            target_value: target,
            start_date: document.getElementById('gStart').value,
            end_date: document.getElementById('gEnd').value,
        }),
    });
    const data = await res.json();
    if (data.success) { toastr.success('Meta criada!'); setTimeout(() => location.reload(), 800); }
    else { toastr.error(data.message || 'Erro'); document.getElementById('btnCreateGoal').disabled = false; }
}

function deleteGoal(id) {
    if (!confirm('Excluir esta meta?')) return;
    fetch('{{ route("goals.destroy", "__ID__") }}'.replace('__ID__', id), {
        method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' },
    }).then(r => r.json()).then(d => { if (d.success) location.reload(); });
}
</script>
@endpush

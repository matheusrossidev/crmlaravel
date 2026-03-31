@extends('tenant.layouts.app')
@php $title = __('nav.goals') ?? 'Metas'; $pageIcon = 'trophy'; @endphp

@push('styles')
<style>
/* ── Summary ── */
.g-summary { display: flex; gap: 14px; margin-bottom: 24px; overflow-x: auto; padding-bottom: 2px; -webkit-overflow-scrolling: touch; scrollbar-width: none; }
.g-summary::-webkit-scrollbar { display: none; }
.g-stat { background: #fff; border: 1px solid #e8eaf0; border-radius: 14px; padding: 16px 18px; min-width: 160px; flex: 1; flex-shrink: 0; display: flex; align-items: flex-start; gap: 12px; }
.g-stat-icon { width: 34px; height: 34px; border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: 15px; flex-shrink: 0; }
.g-stat-icon.blue   { background: #eff6ff; color: #0085f3; }
.g-stat-icon.green  { background: #f0fdf4; color: #10B981; }
.g-stat-icon.red    { background: #fef2f2; color: #EF4444; }
.g-stat-icon.purple { background: #f5f3ff; color: #8B5CF6; }
.g-stat-body {}
.g-stat-label { font-size: 11.5px; font-weight: 500; color: #97A3B7; }
.g-stat-val { font-size: 22px; font-weight: 700; color: #1a1d23; margin-top: 1px; }

/* ── Tabs ── */
.g-tabs { display: flex; gap: 0; border-bottom: 2px solid #e8eaf0; margin-bottom: 28px; overflow-x: auto; }
.g-tab { padding: 11px 22px; font-size: 13.5px; font-weight: 600; color: #6b7280; cursor: pointer; border: none; border-bottom: 2.5px solid transparent; margin-bottom: -2px; transition: all .15s; background: none; white-space: nowrap; }
.g-tab:hover { color: #1a1d23; }
.g-tab.active { color: #0085f3; border-bottom-color: #0085f3; }
.g-panel { display: none; }
.g-panel.active { display: block; }

/* ── Goal cards ── */
.gc { background: #fff; border: 1.5px solid #e8eaf0; border-radius: 14px; padding: 18px 20px; margin-bottom: 14px; transition: box-shadow .15s; }
.gc:hover { box-shadow: 0 2px 12px rgba(0,0,0,.04); }
.gc-top { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
.gc-avatar { width: 36px; height: 36px; border-radius: 50%; background: #eff6ff; color: #0085f3; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; flex-shrink: 0; letter-spacing: -.02em; }
.gc-info { flex: 1; min-width: 0; }
.gc-name { font-size: 14px; font-weight: 700; color: #1a1d23; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.gc-meta { font-size: 11.5px; color: #97A3B7; margin-top: 1px; }
.gc-badges { display: flex; gap: 6px; flex-shrink: 0; align-items: center; flex-wrap: wrap; }
.gc-badge { font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 99px; white-space: nowrap; }
.gc-badge.achieved { background: #ecfdf5; color: #065f46; }
.gc-badge.on_track { background: #fffbeb; color: #92400e; }
.gc-badge.behind { background: #fef2f2; color: #991b1b; }
.gc-badge.recurring { background: #eff6ff; color: #0085f3; }
.gc-badge.bonus { background: linear-gradient(135deg, #fef3c7, #fde68a); color: #78350f; }
.gc-actions { display: flex; gap: 6px; flex-shrink: 0; }
.gc-act { width: 30px; height: 30px; border-radius: 8px; border: none; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 14px; transition: background .12s; }
.gc-act.hist { background: #eff6ff; color: #0085f3; }
.gc-act.hist:hover { background: #dbeafe; }
.gc-act.del { background: #fef2f2; color: #ef4444; }
.gc-act.del:hover { background: #fee2e2; }

.gc-bottom { display: flex; align-items: center; gap: 14px; }
.gc-value { font-size: 18px; font-weight: 800; color: #1a1d23; white-space: nowrap; }
.gc-value span { font-size: 12px; font-weight: 500; color: #97A3B7; }
.gc-bar-wrap { flex: 1; height: 7px; background: #f3f4f6; border-radius: 99px; overflow: hidden; min-width: 60px; }
.gc-bar { height: 100%; border-radius: 99px; transition: width .4s; }
.gc-bar.achieved { background: #10B981; }
.gc-bar.on_track { background: #F59E0B; }
.gc-bar.behind { background: #EF4444; }
.gc-pct { font-size: 14px; font-weight: 700; white-space: nowrap; min-width: 44px; text-align: right; }

.gc-forecast { margin-top: 12px; padding: 10px 14px; background: #f8fafc; border: 1px solid #f0f2f7; border-radius: 10px; font-size: 12.5px; color: #374151; line-height: 1.7; }
.gc-forecast .lbl { color: #97A3B7; }
.fc-ahead { color: #059669; font-weight: 600; }
.fc-on_pace { color: #d97706; font-weight: 600; }
.fc-behind { color: #dc2626; font-weight: 600; }
.gc-streak { display: inline-flex; align-items: center; gap: 3px; font-size: 12px; font-weight: 700; color: #f59e0b; }

/* ── Team card ── */
.team-card { background: #fff; border: 1.5px solid #e8eaf0; border-radius: 14px; padding: 22px 24px; margin-bottom: 16px; }
.team-card:hover { box-shadow: 0 2px 12px rgba(0,0,0,.04); }
.team-header { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; }
.team-title { font-size: 16px; font-weight: 800; color: #1a1d23; }
.team-sub { font-size: 12px; color: #97A3B7; margin-left: 8px; }
.team-numbers { display: flex; align-items: baseline; gap: 10px; margin-top: 14px; }
.team-numbers .big { font-size: 26px; font-weight: 800; color: #1a1d23; }
.team-numbers .of { font-size: 13px; color: #97A3B7; }
.team-numbers .remaining { font-size: 12px; color: #97A3B7; }
.team-bar { height: 10px; background: #f3f4f6; border-radius: 99px; overflow: hidden; margin: 12px 0 0; }
.team-bar-fill { height: 100%; border-radius: 99px; transition: width .5s; }

.team-contribs { margin-top: 18px; padding-top: 16px; border-top: 1px solid #f0f2f7; }
.team-contribs-title { font-size: 11px; font-weight: 600; color: #97A3B7; text-transform: uppercase; letter-spacing: .06em; margin-bottom: 12px; }
.contrib-row { display: flex; align-items: center; gap: 12px; padding: 6px 0; }
.contrib-name { font-size: 13px; font-weight: 600; color: #374151; width: 110px; flex-shrink: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.contrib-bar-wrap { flex: 1; height: 6px; background: #f3f4f6; border-radius: 99px; overflow: hidden; }
.contrib-bar-fill { height: 100%; border-radius: 99px; background: #0085f3; }
.contrib-val { font-size: 12px; font-weight: 700; color: #1a1d23; width: 80px; text-align: right; flex-shrink: 0; }

/* ── Ranking scroll ── */
.rank-scroll { display: flex; gap: 16px; overflow-x: auto; padding-bottom: 6px; -webkit-overflow-scrolling: touch; scrollbar-width: none; }
.rank-scroll::-webkit-scrollbar { display: none; }

.rank-card { min-width: 240px; max-width: 280px; flex-shrink: 0; background: #fff; border: 1.5px solid #e8eaf0; border-radius: 16px; padding: 22px 24px; transition: transform .15s, box-shadow .15s; }
.rank-card:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,.06); }

/* position + medal row */
.rank-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; }
.rank-pos-label { font-size: 14px; font-weight: 700; color: #97A3B7; }
.rank-card.gold .rank-pos-label { color: #b45309; }
.rank-card.silver .rank-pos-label { color: #64748b; }
.rank-card.bronze .rank-pos-label { color: #c2410c; }
.rank-medal-icon { width: 32px; height: 32px; }

/* gold/silver/bronze card accents */
.rank-card.gold { border-color: #fbbf24; background: linear-gradient(180deg, #fffdf5 0%, #fff 50%); }
.rank-card.silver { border-color: #cbd5e1; background: linear-gradient(180deg, #f9fafb 0%, #fff 50%); }
.rank-card.bronze { border-color: #f59e0b; background: linear-gradient(180deg, #fffbeb 0%, #fff 50%); }

/* avatar + name */
.rank-profile { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; }
.rank-avatar { width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 15px; font-weight: 700; flex-shrink: 0; }
.rank-card.gold .rank-avatar { background: #fef3c7; color: #92400e; }
.rank-card.silver .rank-avatar { background: #f1f5f9; color: #475569; }
.rank-card.bronze .rank-avatar { background: #fff7ed; color: #c2410c; }
.rank-card:not(.gold):not(.silver):not(.bronze) .rank-avatar { background: #eff6ff; color: #0085f3; }
.rank-user-name { font-size: 15px; font-weight: 700; color: #1a1d23; }
.rank-user-sub { font-size: 12px; color: #97A3B7; margin-top: 1px; }

/* stats row at bottom */
.rank-stats { display: flex; gap: 0; border-top: 1px solid #f0f2f7; padding-top: 16px; }
.rank-stat { flex: 1; }
.rank-stat:not(:last-child) { border-right: 1px solid #f0f2f7; padding-right: 14px; margin-right: 14px; }
.rank-stat-val { font-size: 18px; font-weight: 800; }
.rank-stat-label { font-size: 11px; color: #97A3B7; margin-top: 2px; }

/* ── Drawers ── */
.page-drawer-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.3); z-index: 5000; }
.page-drawer-overlay.open { display: block; }
.page-drawer { position: fixed; top: 0; right: -520px; width: 480px; height: 100vh; background: #fff; z-index: 5001; box-shadow: -4px 0 24px rgba(0,0,0,.1); display: flex; flex-direction: column; transition: right .25s cubic-bezier(.4,0,.2,1); }
.page-drawer.open { right: 0; }
@media (max-width: 540px) { .page-drawer { width: 100%; right: -100%; } }
.dw-header { padding: 18px 24px; border-bottom: 1px solid #f0f2f7; display: flex; align-items: center; justify-content: space-between; }
.dw-header h3 { margin: 0; font-size: 15px; font-weight: 700; color: #1a1d23; }
.dw-body { flex: 1; overflow-y: auto; padding: 24px; }
.dw-footer { padding: 16px 24px; border-top: 1px solid #f0f2f7; display: flex; gap: 10px; justify-content: flex-end; }

.fg { margin-bottom: 14px; }
.fg label { display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 4px; }
.fg select, .fg input[type="number"], .fg input[type="date"], .fg input[type="text"] { width: 100%; padding: 9px 12px; border: 1.5px solid #e5e7eb; border-radius: 8px; font-size: 13px; font-family: inherit; outline: none; color: #1a1d23; background: #fff; }
.fg select:focus, .fg input:focus { border-color: #0085f3; box-shadow: 0 0 0 3px rgba(0,133,243,.08); }
.fg-row { display: flex; gap: 10px; }
.fg-row > * { flex: 1; }

.toggle-box { border: 1.5px solid #e8eaf0; border-radius: 12px; padding: 14px 16px; margin-bottom: 14px; }
.toggle-row { display: flex; align-items: center; gap: 10px; }
.toggle-row label { font-size: 13px; font-weight: 600; color: #374151; cursor: pointer; margin: 0; }

.tier-item { display: flex; gap: 8px; align-items: center; margin-bottom: 8px; }
.tier-item input { padding: 7px 10px; border: 1.5px solid #e5e7eb; border-radius: 8px; font-size: 12px; font-family: inherit; outline: none; }
.tier-item input:focus { border-color: #0085f3; }
.tier-item .td { width: 28px; height: 28px; border-radius: 6px; background: #fef2f2; color: #ef4444; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 13px; flex-shrink: 0; }
.add-btn { font-size: 12px; color: #0085f3; font-weight: 600; background: none; border: none; cursor: pointer; padding: 4px 0; }
.add-btn:hover { text-decoration: underline; }

.tm-row { display: flex; gap: 8px; align-items: center; margin-bottom: 8px; }
.tm-row select { flex: 1; padding: 8px 10px; border: 1.5px solid #e5e7eb; border-radius: 8px; font-size: 12px; font-family: inherit; }
.tm-row input { width: 100px; padding: 8px 10px; border: 1.5px solid #e5e7eb; border-radius: 8px; font-size: 12px; font-family: inherit; }
.tm-row .td { width: 28px; height: 28px; border-radius: 6px; background: #fef2f2; color: #ef4444; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 13px; flex-shrink: 0; }

/* ── History ── */
.hist-kpis { display: flex; gap: 14px; margin-bottom: 20px; overflow-x: auto; padding-bottom: 2px; -webkit-overflow-scrolling: touch; scrollbar-width: none; }
.hist-kpis::-webkit-scrollbar { display: none; }
.hist-kpi { background: #fff; border: 1.5px solid #e8eaf0; border-radius: 14px; padding: 16px 18px; min-width: 160px; flex: 1; flex-shrink: 0; display: flex; align-items: flex-start; gap: 12px; }
.hist-kpi-icon { width: 34px; height: 34px; border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: 15px; flex-shrink: 0; }
.hist-kpi .v { font-size: 20px; font-weight: 700; color: #1a1d23; }
.hist-kpi .l { font-size: 11.5px; color: #97A3B7; margin-top: 1px; }

.hist-split { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
@media (max-width: 768px) { .hist-split { grid-template-columns: 1fr; } }
.hist-chart-card { background: #fff; border: 1.5px solid #e8eaf0; border-radius: 14px; padding: 20px; display: flex; flex-direction: column; }
.hist-chart-card h4 { font-size: 13px; font-weight: 600; color: #1a1d23; margin: 0 0 14px; display: flex; align-items: center; gap: 6px; }
.hist-chart-card h4 i { color: #0085f3; }
.hist-tbl-card { background: #fff; border: 1.5px solid #e8eaf0; border-radius: 14px; overflow: hidden; display: flex; flex-direction: column; }
.hist-tbl-card h4 { font-size: 13px; font-weight: 600; color: #1a1d23; margin: 0; padding: 16px 18px; border-bottom: 1px solid #f0f2f7; display: flex; align-items: center; gap: 6px; }
.hist-tbl-card h4 i { color: #0085f3; }
.hist-tbl-scroll { flex: 1; overflow-y: auto; max-height: 340px; }
.hist-tbl { width: 100%; border-collapse: collapse; font-size: 13px; }
.hist-tbl th { text-align: left; padding: 10px 14px; font-size: 11px; font-weight: 600; color: #97A3B7; text-transform: uppercase; letter-spacing: .04em; background: #f8fafc; position: sticky; top: 0; }
.hist-tbl td { padding: 10px 14px; border-top: 1px solid #f3f4f6; }

/* ── Empty state ── */
.g-empty { padding: 60px 24px; text-align: center; background: #fff; border: 1.5px solid #e8eaf0; border-radius: 14px; }
.g-empty i { font-size: 40px; color: #d1d5db; display: block; margin-bottom: 14px; }
.g-empty .t { font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 4px; }
.g-empty .s { font-size: 13px; color: #97A3B7; margin-bottom: 16px; }

/* ── Colors ── */
.clr-achieved { color: #10B981; }
.clr-on_track { color: #F59E0B; }
.clr-behind { color: #EF4444; }
</style>
@endpush

@section('content')
@php
    $allGoals = $teamGoals->merge($individualGoals);
    $activeCount = $allGoals->count();
    $avgPct = $activeCount > 0 ? round($allGoals->avg(fn($i) => $i['progress']['percentage']), 1) : 0;
    $achievedCount = $allGoals->where('progress.status', 'achieved')->count();
    $behindCount = $allGoals->where('progress.status', 'behind')->count();
    $typeLabels = ['leads_won'=>'Vendas','revenue'=>'Receita','leads_created'=>'Leads criados','messages_sent'=>'Msgs enviadas','leads_contacted'=>'Leads contatados','tasks_completed'=>'Tarefas'];
    $statusLabels = ['achieved'=>'Atingida','on_track'=>'No caminho','behind'=>'Atrasada'];
@endphp

<div class="page-container">
    {{-- Header --}}
    <div style="margin-bottom:24px;">
        <div style="font-size:11px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:#97A3B7;margin-bottom:4px;">CRM</div>
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
            <div>
                <h1 style="font-family:'Plus Jakarta Sans',sans-serif;font-size:22px;font-weight:700;color:#1a1d23;margin:0 0 4px;">Metas de Vendas</h1>
                <p style="font-size:13.5px;color:#677489;margin:0;">Acompanhe performance, ranking e projeções da equipe.</p>
            </div>
            <button class="btn-primary-sm" onclick="openGoalDrawer()"><i class="bi bi-plus-lg"></i> Nova Meta</button>
        </div>
    </div>

    {{-- Summary --}}
    <div class="g-summary">
        <div class="g-stat">
            <div class="g-stat-icon blue"><i class="bi bi-flag-fill"></i></div>
            <div class="g-stat-body"><div class="g-stat-label">Metas ativas</div><div class="g-stat-val">{{ $activeCount }}</div></div>
        </div>
        <div class="g-stat">
            <div class="g-stat-icon purple"><i class="bi bi-graph-up"></i></div>
            <div class="g-stat-body"><div class="g-stat-label">Progresso médio</div><div class="g-stat-val">{{ $avgPct }}%</div></div>
        </div>
        <div class="g-stat">
            <div class="g-stat-icon green"><i class="bi bi-check-circle-fill"></i></div>
            <div class="g-stat-body"><div class="g-stat-label">Atingidas</div><div class="g-stat-val clr-achieved">{{ $achievedCount }}</div></div>
        </div>
        <div class="g-stat">
            <div class="g-stat-icon red"><i class="bi bi-exclamation-triangle-fill"></i></div>
            <div class="g-stat-body"><div class="g-stat-label">Em risco</div><div class="g-stat-val clr-behind">{{ $behindCount }}</div></div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="g-tabs">
        <button class="g-tab active" onclick="switchTab('overview', this)">Visão geral</button>
        <button class="g-tab" onclick="switchTab('ranking', this)">Ranking</button>
        <button class="g-tab" onclick="switchTab('individual', this)">Individual</button>
        <button class="g-tab" onclick="switchTab('history', this)">Histórico</button>
    </div>

    {{-- ═══ TAB: VISÃO GERAL ═══ --}}
    <div class="g-panel active" id="panel-overview">

        @foreach($teamGoals as $item)
            @php $g = $item['goal']; $p = $item['progress']; $f = $item['forecast']; $contribs = $item['contributions']; @endphp
            <div class="team-card">
                <div class="team-header">
                    <div>
                        <span class="team-title">Meta do Time</span>
                        <span class="team-sub">{{ $typeLabels[$g->type] ?? $g->type }} · {{ $g->start_date->format('d/m') }}—{{ $g->end_date->format('d/m/Y') }}</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:6px;">
                        <span class="gc-badge {{ $p['status'] }}">{{ $statusLabels[$p['status']] }}</span>
                        @if($g->is_recurring) <span class="gc-badge recurring">Recorrente</span> @endif
                        <button class="gc-act del" onclick="deleteGoal({{ $g->id }})"><i class="bi bi-trash3"></i></button>
                    </div>
                </div>

                <div class="team-numbers">
                    <span class="big">@if($g->type==='revenue') R$ {{ number_format($p['current'],2,',','.') }} @else {{ number_format($p['current']) }} @endif</span>
                    <span class="of">/ @if($g->type==='revenue') R$ {{ number_format($p['target'],2,',','.') }} @else {{ number_format($p['target']) }} @endif</span>
                    <span class="gc-pct clr-{{ $p['status'] }}" style="font-size:16px;font-weight:700;">{{ $p['percentage'] }}%</span>
                    @if($f['remaining_days'] > 0)
                        <span class="remaining">· {{ $f['remaining_days'] }}d restantes</span>
                    @endif
                </div>

                <div class="team-bar">
                    <div class="team-bar-fill" style="width:{{ $p['percentage'] }}%;background:{{ $p['status']==='achieved'?'#10B981':($p['status']==='on_track'?'#F59E0B':'#EF4444') }};"></div>
                </div>

                @if($contribs)
                    <div class="team-contribs">
                        <div class="team-contribs-title">Contribuições</div>
                        @foreach($contribs as $c)
                            <div class="contrib-row">
                                <div class="contrib-name">{{ $c['user']?->name ?? '—' }}</div>
                                <div class="contrib-bar-wrap"><div class="contrib-bar-fill" style="width:{{ $p['target'] > 0 ? min(round(($c['progress']['current']/$p['target'])*100,1),100) : 0 }}%;"></div></div>
                                <div class="contrib-val">@if($g->type==='revenue') R${{ number_format($c['progress']['current'],0,',','.') }} @else {{ number_format($c['progress']['current']) }} @endif</div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach

        @forelse($individualGoals as $item)
            @include('tenant.goals._goal_card', ['item' => $item, 'showForecast' => true])
        @empty
            @if($teamGoals->isEmpty())
                <div class="g-empty">
                    <i class="bi bi-flag"></i>
                    <div class="t">Nenhuma meta criada</div>
                    <div class="s">Crie metas para acompanhar o desempenho da equipe.</div>
                    <button class="btn-primary-sm" onclick="openGoalDrawer()"><i class="bi bi-plus-lg"></i> Criar primeira meta</button>
                </div>
            @endif
        @endforelse
    </div>

    {{-- ═══ TAB: RANKING ═══ --}}
    <div class="g-panel" id="panel-ranking">
        @if(count($ranking) === 0)
            <div class="g-empty">
                <i class="bi bi-bar-chart"></i>
                <div class="t">Sem dados para ranking</div>
                <div class="s">Crie metas individuais para gerar o ranking.</div>
            </div>
        @else
            <div class="rank-scroll">
                @foreach($ranking as $r)
                    @php
                        $classes = [1 => 'gold', 2 => 'silver', 3 => 'bronze'];
                        $cls = $classes[$r['position']] ?? '';
                        $medalSvgs = [
                            1 => '<svg viewBox="0 0 36 36" width="32" height="32"><circle cx="18" cy="18" r="16" fill="#fbbf24"/><circle cx="18" cy="18" r="12" fill="#f59e0b"/><text x="18" y="23" text-anchor="middle" fill="#fff" font-size="14" font-weight="800">1</text></svg>',
                            2 => '<svg viewBox="0 0 36 36" width="32" height="32"><circle cx="18" cy="18" r="16" fill="#cbd5e1"/><circle cx="18" cy="18" r="12" fill="#94a3b8"/><text x="18" y="23" text-anchor="middle" fill="#fff" font-size="14" font-weight="800">2</text></svg>',
                            3 => '<svg viewBox="0 0 36 36" width="32" height="32"><circle cx="18" cy="18" r="16" fill="#fdba74"/><circle cx="18" cy="18" r="12" fill="#f97316"/><text x="18" y="23" text-anchor="middle" fill="#fff" font-size="14" font-weight="800">3</text></svg>',
                        ];
                        $initials = strtoupper(mb_substr($r['user_name'], 0, 2));
                        $pctClr = $r['avg_pct'] >= 100 ? '#059669' : ($r['avg_pct'] >= 70 ? '#d97706' : '#dc2626');
                    @endphp
                    <div class="rank-card {{ $cls }}">
                        {{-- Position + Medal --}}
                        <div class="rank-top">
                            <span class="rank-pos-label">#{{ $r['position'] }}</span>
                            @if(isset($medalSvgs[$r['position']]))
                                {!! $medalSvgs[$r['position']] !!}
                            @endif
                        </div>

                        {{-- Avatar + Name --}}
                        <div class="rank-profile">
                            <div class="rank-avatar">{{ $initials }}</div>
                            <div>
                                <div class="rank-user-name">{{ $r['user_name'] }}</div>
                                <div class="rank-user-sub">{{ $r['goal_count'] }} meta{{ $r['goal_count'] > 1 ? 's' : '' }}
                                    @if($r['streak'] > 0) · <i class="bi bi-fire" style="color:#f59e0b;"></i> {{ $r['streak'] }}d @endif
                                </div>
                            </div>
                        </div>

                        {{-- Stats --}}
                        <div class="rank-stats">
                            <div class="rank-stat">
                                <div class="rank-stat-val" style="color:{{ $pctClr }};">{{ $r['avg_pct'] }}%</div>
                                <div class="rank-stat-label">Progresso</div>
                            </div>
                            <div class="rank-stat">
                                <div class="rank-stat-val" style="color:#1a1d23;">{{ $r['position'] }}º</div>
                                <div class="rank-stat-label">Posição</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- ═══ TAB: INDIVIDUAL ═══ --}}
    <div class="g-panel" id="panel-individual">
        @php
            $myGoals = $individualGoals->filter(fn($i) => $i['goal']->user_id === $currentUserId)->values();
            $myRank = collect($ranking)->firstWhere('user_id', $currentUserId);
        @endphp

        @if($myRank)
            <div class="g-summary" style="margin-bottom:20px;">
                <div class="g-stat">
                    <div class="g-stat-icon blue"><i class="bi bi-trophy-fill"></i></div>
                    <div class="g-stat-body"><div class="g-stat-label">Sua posição</div><div class="g-stat-val">{{ $myRank['position'] }}º</div></div>
                </div>
                <div class="g-stat">
                    <div class="g-stat-icon purple"><i class="bi bi-graph-up"></i></div>
                    <div class="g-stat-body"><div class="g-stat-label">Progresso médio</div><div class="g-stat-val">{{ $myRank['avg_pct'] }}%</div></div>
                </div>
                <div class="g-stat">
                    <div class="g-stat-icon" style="background:#fffbeb;color:#f59e0b;"><i class="bi bi-fire"></i></div>
                    <div class="g-stat-body"><div class="g-stat-label">Streak</div><div class="g-stat-val" style="color:#f59e0b;">{{ $myRank['streak'] > 0 ? $myRank['streak'].' dias' : '—' }}</div></div>
                </div>
            </div>
        @endif

        @forelse($myGoals as $item)
            @include('tenant.goals._goal_card', ['item' => $item, 'showForecast' => true])
        @empty
            <div class="g-empty">
                <i class="bi bi-person"></i>
                <div class="t">Nenhuma meta atribuída a você</div>
                <div class="s">Suas metas individuais aparecerão aqui.</div>
            </div>
        @endforelse
    </div>

    {{-- ═══ TAB: HISTÓRICO ═══ --}}
    <div class="g-panel" id="panel-history">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
            <label style="font-size:13px;font-weight:600;color:#374151;">Vendedor:</label>
            <select id="histUserSelect" onchange="loadHistory()" style="padding:9px 14px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;font-family:inherit;outline:none;">
                <option value="">Todos</option>
                @foreach($users as $u)
                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                @endforeach
            </select>
        </div>
        <div id="histLoading" style="text-align:center;padding:50px;color:#97A3B7;display:none;"><i class="bi bi-hourglass-split" style="font-size:24px;"></i><p style="margin-top:8px;">Carregando...</p></div>
        <div id="histEmpty" style="display:none;">
            <div class="g-empty"><i class="bi bi-inbox"></i><div class="t">Nenhum histórico encontrado</div><div class="s">Os dados aparecerão após o encerramento de períodos com metas.</div></div>
        </div>
        <div id="histContent" style="display:none;">
            {{-- KPI cards with icons --}}
            <div class="hist-kpis">
                <div class="hist-kpi">
                    <div class="hist-kpi-icon" style="background:#f5f3ff;color:#8B5CF6;"><i class="bi bi-percent"></i></div>
                    <div><div class="v" id="hAvg">—</div><div class="l">Média geral</div></div>
                </div>
                <div class="hist-kpi">
                    <div class="hist-kpi-icon" style="background:#f0fdf4;color:#10B981;"><i class="bi bi-arrow-up-circle-fill"></i></div>
                    <div><div class="v clr-achieved" id="hBest">—</div><div class="l">Melhor mês</div></div>
                </div>
                <div class="hist-kpi">
                    <div class="hist-kpi-icon" style="background:#eff6ff;color:#0085f3;"><i class="bi bi-activity"></i></div>
                    <div><div class="v" id="hTrend">—</div><div class="l">Tendência</div></div>
                </div>
            </div>

            {{-- Chart + Table side by side --}}
            <div class="hist-split">
                <div class="hist-chart-card">
                    <h4><i class="bi bi-graph-up"></i> Evolução</h4>
                    <div style="flex:1;"><canvas id="hChart" height="220"></canvas></div>
                </div>
                <div class="hist-tbl-card">
                    <h4><i class="bi bi-table"></i> Detalhamento</h4>
                    <div class="hist-tbl-scroll">
                        <table class="hist-tbl"><thead><tr><th>Período</th><th>Meta</th><th>Real</th><th>%</th></tr></thead><tbody id="hTbody"></tbody></table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ═══ CREATE DRAWER ═══ --}}
<div class="page-drawer-overlay" id="goalDrawerOverlay" onclick="closeGoalDrawer()"></div>
<div class="page-drawer" id="goalDrawer">
    <div class="dw-header">
        <h3>Nova Meta</h3>
        <button onclick="closeGoalDrawer()" style="background:none;border:none;font-size:18px;color:#9ca3af;cursor:pointer;"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="dw-body">
        <div class="fg"><label>Tipo de meta *</label>
            <select id="gType">
                <optgroup label="Resultado"><option value="leads_won">Vendas fechadas</option><option value="revenue">Receita (R$)</option><option value="leads_created">Leads criados</option></optgroup>
                <optgroup label="Atividade"><option value="messages_sent">Mensagens enviadas</option><option value="leads_contacted">Leads contatados</option><option value="tasks_completed">Tarefas concluídas</option></optgroup>
            </select>
        </div>
        <div class="fg"><label>Período</label>
            <select id="gPeriod" onchange="autoFillDates()"><option value="monthly">Mensal</option><option value="weekly">Semanal</option><option value="quarterly">Trimestral</option></select>
        </div>
        <div class="fg-row">
            <div class="fg"><label>Início</label><input type="date" id="gStart"></div>
            <div class="fg"><label>Fim</label><input type="date" id="gEnd"></div>
        </div>
        <div class="toggle-box">
            <div class="toggle-row"><input type="checkbox" id="gIsTeam" onchange="toggleTeamMode()"><label for="gIsTeam">Meta de time (cascata)</label></div>
            <div id="teamSection" style="display:none;margin-top:12px;">
                <div class="fg"><label>Meta total do time *</label><input type="number" id="gTeamTarget" placeholder="Ex: 100000" min="1"></div>
                <div style="font-size:11px;font-weight:600;color:#97A3B7;text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px;">Distribuição por membro</div>
                <div id="teamMembers"></div>
                <button type="button" class="add-btn" onclick="addTeamMember()"><i class="bi bi-plus"></i> Adicionar membro</button>
            </div>
        </div>
        <div id="individualSection">
            <div class="fg"><label>Vendedor</label>
                <select id="gUser"><option value="">Time inteiro</option>@foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select>
            </div>
            <div class="fg"><label>Meta *</label><input type="number" id="gTarget" placeholder="Ex: 10 ou 50000" min="1"></div>
        </div>
        <div class="toggle-box">
            <div class="toggle-row"><input type="checkbox" id="gRecurring" onchange="toggleRecurring()"><label for="gRecurring">Meta recorrente (auto-renova)</label></div>
            <div id="recurringSection" style="display:none;margin-top:10px;">
                <div class="fg"><label>Crescimento por período (%)</label><input type="number" id="gGrowth" placeholder="Ex: 5" min="0" max="100" step="0.5"></div>
                <p style="font-size:11px;color:#97A3B7;margin:4px 0 0;">0 ou vazio = mesmo valor. Ex: 5 = meta sobe 5% a cada renovação.</p>
            </div>
        </div>
        <div class="toggle-box">
            <div class="toggle-row"><input type="checkbox" id="gHasBonus" onchange="toggleBonus()"><label for="gHasBonus">Bônus por atingimento</label></div>
            <div id="bonusSection" style="display:none;margin-top:10px;">
                <div id="tierList"></div>
                <button type="button" class="add-btn" onclick="addTier()"><i class="bi bi-plus"></i> Adicionar faixa</button>
            </div>
        </div>
    </div>
    <div class="dw-footer">
        <button class="btn-outline-sm" onclick="closeGoalDrawer()">Cancelar</button>
        <button class="btn-primary-sm" id="btnCreateGoal" onclick="createGoal()"><i class="bi bi-check-lg"></i> Criar Meta</button>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
const USERS_JSON = @json($users->map(fn($u) => ['id' => $u->id, 'name' => $u->name]));

function switchTab(name, el) {
    document.querySelectorAll('.g-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.g-panel').forEach(p => p.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('panel-' + name).classList.add('active');
    if (name === 'history') loadHistory();
}

function autoFillDates() {
    const p = document.getElementById('gPeriod').value, now = new Date(); let s, e;
    if (p === 'weekly') { const d = now.getDay(), diff = now.getDate() - d + (d === 0 ? -6 : 1); s = new Date(now.getFullYear(), now.getMonth(), diff); e = new Date(s); e.setDate(e.getDate() + 6); }
    else if (p === 'quarterly') { const q = Math.floor(now.getMonth() / 3); s = new Date(now.getFullYear(), q * 3, 1); e = new Date(now.getFullYear(), q * 3 + 3, 0); }
    else { s = new Date(now.getFullYear(), now.getMonth(), 1); e = new Date(now.getFullYear(), now.getMonth() + 1, 0); }
    document.getElementById('gStart').value = s.toISOString().slice(0, 10);
    document.getElementById('gEnd').value = e.toISOString().slice(0, 10);
}
autoFillDates();

function openGoalDrawer() { document.getElementById('goalDrawerOverlay').classList.add('open'); document.getElementById('goalDrawer').classList.add('open'); autoFillDates(); }
function closeGoalDrawer() { document.getElementById('goalDrawerOverlay').classList.remove('open'); document.getElementById('goalDrawer').classList.remove('open'); }

function toggleTeamMode() { const on = document.getElementById('gIsTeam').checked; document.getElementById('teamSection').style.display = on ? 'block' : 'none'; document.getElementById('individualSection').style.display = on ? 'none' : 'block'; if (on && !document.getElementById('teamMembers').children.length) { addTeamMember(); addTeamMember(); } }
function toggleRecurring() { document.getElementById('recurringSection').style.display = document.getElementById('gRecurring').checked ? 'block' : 'none'; }
function toggleBonus() { const on = document.getElementById('gHasBonus').checked; document.getElementById('bonusSection').style.display = on ? 'block' : 'none'; if (on && !document.getElementById('tierList').children.length) { addTier(); addTier(); } }

function addTier() {
    const d = document.createElement('div'); d.className = 'tier-item';
    d.innerHTML = '<input type="number" class="tt" placeholder="% (ex: 100)" min="1" style="width:80px;"><input type="text" class="tl" placeholder="Label (ex: Bônus R$500)" style="flex:1;"><input type="number" class="tv" placeholder="R$" min="0" style="width:80px;"><button class="td" onclick="this.parentElement.remove()"><i class="bi bi-x"></i></button>';
    document.getElementById('tierList').appendChild(d);
}
function addTeamMember() {
    const d = document.createElement('div'); d.className = 'tm-row';
    let o = '<option value="">Selecione...</option>'; USERS_JSON.forEach(u => o += `<option value="${u.id}">${u.name}</option>`);
    d.innerHTML = `<select class="tmu">${o}</select><input type="number" class="tmt" placeholder="Meta" min="1"><button class="td" onclick="this.parentElement.remove()"><i class="bi bi-x"></i></button>`;
    document.getElementById('teamMembers').appendChild(d);
}

async function createGoal() {
    const isTeam = document.getElementById('gIsTeam').checked, btn = document.getElementById('btnCreateGoal');
    let body = { type: document.getElementById('gType').value, period: document.getElementById('gPeriod').value, start_date: document.getElementById('gStart').value, end_date: document.getElementById('gEnd').value, is_recurring: document.getElementById('gRecurring').checked, growth_rate: parseFloat(document.getElementById('gGrowth').value) || null };
    if (document.getElementById('gHasBonus').checked) { const t = []; document.querySelectorAll('.tier-item').forEach(el => { const th = parseFloat(el.querySelector('.tt').value), lb = el.querySelector('.tl').value.trim(); if (th && lb) t.push({ threshold: th, label: lb, value: parseFloat(el.querySelector('.tv').value) || 0 }); }); if (t.length) body.bonus_tiers = t; }
    if (isTeam) { const tt = parseFloat(document.getElementById('gTeamTarget').value); if (!tt || tt <= 0) { toastr.error('Defina a meta total'); return; } body.target_value = tt; body.user_id = null; const ch = []; document.querySelectorAll('.tm-row').forEach(el => { const uid = el.querySelector('.tmu').value, tv = parseFloat(el.querySelector('.tmt').value); if (uid && tv > 0) ch.push({ user_id: parseInt(uid), target_value: tv }); }); if (!ch.length) { toastr.error('Adicione membros'); return; } body.children = ch; }
    else { const tv = parseFloat(document.getElementById('gTarget').value); if (!tv || tv <= 0) { toastr.error('Defina a meta'); return; } body.target_value = tv; body.user_id = document.getElementById('gUser').value || null; }
    btn.disabled = true;
    try { const r = await fetch('{{ route("goals.store") }}', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' }, body: JSON.stringify(body) }); const d = await r.json(); if (d.success) { toastr.success('Meta criada!'); setTimeout(() => location.reload(), 800); } else { toastr.error(d.message || 'Erro'); btn.disabled = false; } } catch { toastr.error('Erro de conexão'); btn.disabled = false; }
}
function deleteGoal(id) { if (!confirm('Excluir esta meta e submetas vinculadas?')) return; fetch('{{ route("goals.destroy","__ID__") }}'.replace('__ID__', id), { method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' } }).then(r => r.json()).then(d => { if (d.success) location.reload(); }); }

/* ── History ── */
let hChart = null;
function loadHistory(userId) {
    userId = userId ?? document.getElementById('histUserSelect')?.value ?? '';
    document.getElementById('histLoading').style.display = 'block';
    document.getElementById('histContent').style.display = 'none';
    document.getElementById('histEmpty').style.display = 'none';
    const url = userId ? '{{ route("goals.history","__U__") }}'.replace('__U__', userId) : '{{ route("goals.history") }}';
    fetch(url, { headers: { Accept: 'application/json', 'X-CSRF-TOKEN': CSRF } }).then(r => r.json()).then(data => {
        document.getElementById('histLoading').style.display = 'none';
        const h = data.history;
        if (!h?.snapshots?.length) { document.getElementById('histEmpty').style.display = 'block'; return; }
        document.getElementById('histContent').style.display = 'block';
        document.getElementById('hAvg').textContent = h.avg_pct + '%';
        document.getElementById('hBest').textContent = h.best_month ? h.best_month.percentage + '%' : '—';
        const ti = { improving: '↑ Melhorando', declining: '↓ Caindo', stable: '→ Estável' };
        const tc = { improving: '#059669', declining: '#dc2626', stable: '#6b7280' };
        document.getElementById('hTrend').textContent = ti[h.trend] || h.trend;
        document.getElementById('hTrend').style.color = tc[h.trend] || '#1a1d23';
        const tb = document.getElementById('hTbody'); tb.innerHTML = '';
        h.snapshots.forEach(s => { const c = s.percentage >= 100 ? '#059669' : (s.percentage >= 70 ? '#d97706' : '#dc2626'); tb.innerHTML += `<tr><td>${s.label}</td><td>${fmtN(s.target)}</td><td>${fmtN(s.achieved)}</td><td style="font-weight:700;color:${c};">${s.percentage}%</td></tr>`; });
        if (hChart) hChart.destroy();
        hChart = new Chart(document.getElementById('hChart').getContext('2d'), { type: 'line', data: { labels: h.snapshots.map(s => s.label), datasets: [{ label: '% Atingimento', data: h.snapshots.map(s => s.percentage), borderColor: '#0085f3', backgroundColor: 'rgba(0,133,243,.06)', fill: true, tension: .3, pointBackgroundColor: '#0085f3', pointRadius: 5, pointHoverRadius: 7 }, { label: 'Meta (100%)', data: h.snapshots.map(() => 100), borderColor: '#e5e7eb', borderDash: [6, 4], pointRadius: 0, fill: false, borderWidth: 1.5 }] }, options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, max: Math.max(150, ...h.snapshots.map(s => s.percentage)) + 10, grid: { color: '#f3f4f6' } }, x: { grid: { display: false } } } } });
    }).catch(() => { document.getElementById('histLoading').style.display = 'none'; document.getElementById('histEmpty').style.display = 'block'; });
}
function fmtN(n) { return new Intl.NumberFormat('pt-BR', { maximumFractionDigits: 0 }).format(n); }
</script>
@endpush

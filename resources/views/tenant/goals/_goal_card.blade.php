@php
    $g = $item['goal'];
    $p = $item['progress'];
    $f = $item['forecast'];
    $bt = $item['bonus_tier'] ?? null;
    $typeLabels = ['leads_won'=>__('goals.type_sales_count'),'revenue'=>__('goals.type_sales_value'),'leads_created'=>__('goals.type_leads_created'),'messages_sent'=>__('goals.type_messages_sent'),'leads_contacted'=>__('goals.type_leads_contacted'),'tasks_completed'=>__('goals.type_tasks_completed')];
    $periodLabels = ['monthly'=>__('goals.period_monthly'),'weekly'=>__('goals.period_weekly'),'quarterly'=>__('goals.period_quarterly')];
    $statusLabels = ['achieved'=>__('goals.status_achieved'),'on_track'=>__('goals.status_on_track'),'behind'=>__('goals.status_behind')];
    $paceLabels = ['ahead'=>__('goals.pace_ahead'),'on_pace'=>__('goals.pace_on_pace'),'behind'=>__('goals.pace_behind'),'achieved'=>__('goals.pace_achieved'),'not_started'=>__('goals.pace_not_started')];
    $initials = $g->user ? strtoupper(mb_substr($g->user->name, 0, 2)) : 'TI';
    $streak = null;
    if ($g->user_id && isset($ranking)) {
        $ur = collect($ranking)->firstWhere('user_id', $g->user_id);
        $streak = $ur['streak'] ?? 0;
    }
@endphp
<div class="gc">
    {{-- Row 1: Avatar + Name + Status + Actions --}}
    <div class="gc-top">
        <div class="gc-avatar">{{ $initials }}</div>
        <div class="gc-info">
            <div class="gc-name">{{ $g->user?->name ?? 'Time inteiro' }}</div>
            <div class="gc-meta">{{ $typeLabels[$g->type] ?? $g->type }} · {{ $periodLabels[$g->period] ?? $g->period }} · {{ $g->start_date->format('d/m') }}—{{ $g->end_date->format('d/m/Y') }}</div>
        </div>
        <div class="gc-badges">
            <span class="gc-badge {{ $p['status'] }}">{{ $statusLabels[$p['status']] }}</span>
            @if($g->is_recurring) <span class="gc-badge recurring">Rec.</span> @endif
            @if($bt) <span class="gc-badge bonus">{{ $bt['label'] }}</span> @endif
        </div>
        <div class="gc-actions">
            @if($g->user_id)
                <button class="gc-act hist" onclick="switchTab('history', document.querySelectorAll('.g-tab')[3]); document.getElementById('histUserSelect').value='{{ $g->user_id }}'; loadHistory({{ $g->user_id }});" title="Histórico"><i class="bi bi-clock-history"></i></button>
            @endif
            <button class="gc-act del" onclick="deleteGoal({{ $g->id }})" title="Excluir"><i class="bi bi-trash3"></i></button>
        </div>
    </div>

    {{-- Row 2: Value + Bar + Percentage --}}
    <div class="gc-bottom">
        <div class="gc-value">
            @if($g->type === 'revenue') R$ {{ number_format($p['current'], 0, ',', '.') }}
            @else {{ number_format($p['current']) }}
            @endif
            <span>/ @if($g->type === 'revenue') R$ {{ number_format($p['target'], 0, ',', '.') }} @else {{ number_format($p['target']) }} @endif</span>
        </div>
        <div class="gc-bar-wrap"><div class="gc-bar {{ $p['status'] }}" style="width:{{ $p['percentage'] }}%;"></div></div>
        <div class="gc-pct clr-{{ $p['status'] }}">{{ $p['percentage'] }}%</div>
    </div>

    {{-- Row 3: Forecast (if applicable) --}}
    @if(($showForecast ?? false) && $f['pace'] !== 'not_started' && $p['status'] !== 'achieved')
        <div class="gc-forecast">
            <span class="fc-{{ $f['pace'] }}">
                @if($f['pace']==='ahead') <i class="bi bi-arrow-up-right"></i>
                @elseif($f['pace']==='behind') <i class="bi bi-arrow-down-right"></i>
                @else <i class="bi bi-arrow-right"></i>
                @endif
                {{ $paceLabels[$f['pace']] }}
            </span>
            &nbsp;·&nbsp;
            <span class="lbl">Projeção:</span>
            <strong>
            @if($g->type === 'revenue') R$ {{ number_format($f['projected_value'], 0, ',', '.') }}
            @else {{ number_format($f['projected_value'], 0) }}
            @endif
            ({{ $f['projected_percentage'] }}%)
            </strong>
            @if($f['remaining_days'] > 0)
                &nbsp;·&nbsp; <span class="lbl">{{ $f['remaining_days'] }}d restantes</span>
            @endif
            @if($f['acceleration_needed'] > 0)
                &nbsp;·&nbsp; <span class="fc-behind">Acelerar {{ $f['acceleration_needed'] }}%</span>
            @endif
            @if($streak && $streak > 0)
                &nbsp;&nbsp; <span class="gc-streak"><i class="bi bi-fire"></i> {{ $streak }}d</span>
            @endif
        </div>
    @endif
</div>

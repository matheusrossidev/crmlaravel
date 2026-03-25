@extends('tenant.layouts.app')

@php
    $title       = 'CRM';
    $pageIcon    = 'kanban';
    $tagColorMap = [];
@endphp

@section('topbar_actions')
<div class="topbar-actions">
    @if($pipelines->count())
    {{-- Pipeline selector --}}
    <select id="pipelineSelect"
            style="padding:7px 14px;border:1.5px solid #e8eaf0;border-radius:9px;font-size:13px;font-family:inherit;outline:none;background:#fafafa;color:#374151;cursor:pointer;font-weight:500;">
        @foreach($pipelines as $p)
        <option value="{{ $p->id }}" {{ $pipeline?->id === $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
        @endforeach
    </select>

    <button class="topbar-btn hide-mobile" title="Filtros" id="btnToggleFilters">
        <i class="bi bi-funnel{{ request()->hasAny(['source','date_from','date_to','campaign_id','tag']) ? '-fill' : '' }}"></i>
    </button>

    <button class="topbar-btn hide-mobile" title="Exportar leads" onclick="exportarLeads()">
        <i class="bi bi-download"></i>
    </button>

    <button class="topbar-btn hide-mobile" title="Importar leads" onclick="openImportModal()" {{ auth()->user()->isViewer() ? 'disabled' : '' }}>
        <i class="bi bi-upload"></i>
    </button>

    <button class="btn-primary-sm hide-mobile" id="btnNovoLead" {{ auth()->user()->isViewer() ? 'disabled style=opacity:.5;pointer-events:none;' : '' }}>
        <i class="bi bi-plus-lg"></i>
        Novo Lead
    </button>
    @else
    <button class="btn-primary-sm" onclick="openPipelineDrawer()">
        <i class="bi bi-plus-lg"></i>
        Criar funil
    </button>
    @endif
</div>
@endsection

@push('styles')
<style>
    /* Remove padding do page-container no kanban (board ocupa toda a largura) */
    .main-content { display: flex; flex-direction: column; }

    .kanban-header {
        padding: 16px 28px 0;
        display: flex;
        align-items: center;
        gap: 12px;
        flex-shrink: 0;
    }

    .kanban-pipeline-name {
        font-size: 13px;
        font-weight: 600;
        color: #6b7280;
    }

    .kanban-board {
        display: flex;
        gap: 14px;
        padding: 16px 28px 28px;
        overflow-x: auto;
        flex: 1;
        align-items: flex-start;
    }

    .kanban-board::-webkit-scrollbar {
        height: 6px;
    }
    .kanban-board::-webkit-scrollbar-track { background: transparent; }
    .kanban-board::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 99px; }
    .kanban-board { cursor: grab; }
    .kanban-board.is-grabbing { cursor: grabbing; user-select: none; }

    /* Coluna */
    .kanban-col {
        flex: 0 0 27%;
        min-width: 240px;
        display: flex;
        flex-direction: column;
        gap: 0;
    }

    .kanban-col-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 12px;
        border-radius: 10px 10px 0 0;
        background: #fff;
        border: 1px solid #e8eaf0;
        border-bottom: none;
    }

    .col-title {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        font-weight: 600;
        color: #374151;
    }

    .col-dot {
        width: 9px;
        height: 9px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .col-count {
        font-size: 11px;
        background: #f0f2f7;
        color: #6b7280;
        border-radius: 99px;
        padding: 1px 7px;
        font-weight: 600;
    }

    /* Lista de cards */
    .kanban-list {
        min-height: 60px;
        display: flex;
        flex-direction: column;
        gap: 8px;
        padding: 8px;
        background: #f8fafc;
        border: 1px solid #e8eaf0;
        border-top: 3px solid transparent;
        border-radius: 0 0 0 0;
        transition: background .15s;
    }

    .kanban-list.sortable-over {
        background: #eff6ff;
    }

    /* Card */
    .lead-card {
        background: #fff;
        border: 1px solid #e8eaf0;
        border-radius: 10px;
        padding: 12px 14px;
        cursor: grab;
        transition: box-shadow .15s, transform .1s;
        user-select: none;
    }

    .lead-card:hover {
        box-shadow: 0 2px 12px rgba(0,0,0,.08);
        transform: translateY(-1px);
    }

    .lead-card:active { cursor: grabbing; }

    .lead-card.sortable-ghost {
        opacity: .4;
    }

    .lead-card.sortable-drag {
        box-shadow: 0 8px 24px rgba(0,0,0,.12);
        transform: rotate(1deg);
    }

    .card-name {
        font-size: 14px;
        font-weight: 600;
        color: #1a1d23;
        margin-bottom: 0;
        cursor: pointer;
        line-height: 1.35;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        flex: 1;
        min-width: 0;
    }

    .card-name:hover { color: #3B82F6; }

    .card-meta {
        display: flex;
        flex-direction: column;
        gap: 3px;
        margin-bottom: 8px;
    }

    .card-meta-row {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        color: #6b7280;
    }

    .card-meta-row i { font-size: 12px; color: #9ca3af; }

    .card-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 8px;
        padding-top: 8px;
        border-top: 1px solid #f0f2f7;
        clear: both;
    }

    .source-badge {
        font-size: 10.5px;
        font-weight: 600;
        padding: 2px 8px;
        border-radius: 99px;
        background: #eff6ff;
        color: #3B82F6;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .card-value-row {
        font-size: 13px;
        font-weight: 700;
        color: #10B981;
        margin-bottom: 4px;
    }

    .card-value {
        font-size: 12px;
        font-weight: 700;
        color: #10B981;
    }

    .card-top-tags { display: flex; flex-wrap: wrap; gap: 4px; }
    .card-avatar {
        width: 36px; height: 36px; border-radius: 50%; flex-shrink: 0;
        background: #e0ecff; color: #0085f3; font-size: 13px; font-weight: 700;
        display: flex; align-items: center; justify-content: center;
        overflow: hidden; float: right; margin: 0 0 4px 8px;
    }
    .card-avatar img { width: 100%; height: 100%; object-fit: cover; }

    /* Linha nome + valor */
    .card-name-row {
        display: flex; align-items: center; justify-content: space-between;
        gap: 8px; margin-bottom: 2px;
    }
    .card-value-inline {
        font-size: 12px; font-weight: 700; color: #10B981; white-space: nowrap;
    }

    /* Footer: avatar atendente + ações + data */
    .card-actions {
        display: flex; align-items: center; gap: 2px;
    }
    .card-action-btn {
        display: flex; align-items: center; justify-content: center;
        width: 26px; height: 26px; border-radius: 6px; border: none;
        background: transparent; color: #9ca3af; cursor: pointer;
        font-size: 13px; transition: all .15s; text-decoration: none;
    }
    .card-action-btn:hover { background: #eff6ff; color: #0085f3; }
    .card-action-btn.wa-btn:hover { background: #dcfce7; color: #25D366; }
    .card-action-btn.has-unread { color: #10B981; }
    .card-action-btn .bubble-count {
        position: absolute; top: -2px; right: -2px;
        background: #10B981; color: #fff; font-size: 8px; font-weight: 700;
        min-width: 14px; height: 14px; border-radius: 99px; display: flex;
        align-items: center; justify-content: center; padding: 0 3px;
    }
    .card-assignee {
        width: 22px; height: 22px; border-radius: 50%; flex-shrink: 0;
        background: #f0f2f7; color: #6b7280; font-size: 9px; font-weight: 700;
        display: flex; align-items: center; justify-content: center;
        margin-right: 4px;
    }

    /* Data no footer */
    .card-date {
        display: flex;
        align-items: center;
        margin-left: auto;
        gap: 3px;
        font-size: 11px;
        color: #9ca3af;
    }

    /* Bubble WhatsApp */
    .card-bubble {
        display: flex;
        align-items: center;
        gap: 3px;
        background: #f0f2f7;
        border: none;
        border-radius: 99px;
        padding: 3px 7px;
        font-size: 11px;
        color: #9ca3af;
        cursor: default;
        line-height: 1;
        margin-left: auto;
    }
    .card-bubble.has-unread {
        background: #dcfce7;
        color: #10B981;
    }
    .card-bubble.has-unread i { color: #10B981; }
    .bubble-count { font-weight: 700; font-size: 10.5px; }
    .card-bubble.has-conversation { cursor: pointer; }
    .card-bubble.has-conversation:hover { background: #dbeafe; color: #3B82F6; }
    .card-bubble.has-conversation.has-unread:hover { background: #bbf7d0; color: #059669; }

    /* Cabeçalho da coluna — right side */
    .col-header-right {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .col-value {
        font-size: 11px;
        font-weight: 600;
        color: #10B981;
    }

    /* Botão adicionar */
    .btn-add-card {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 8px 12px;
        font-size: 12.5px;
        color: #9ca3af;
        background: none;
        border: none;
        cursor: pointer;
        border-radius: 0 0 10px 10px;
        border: 1px solid #e8eaf0;
        border-top: none;
        background: #f8fafc;
        width: 100%;
        transition: background .15s, color .15s;
    }

    .btn-add-card:hover { background: #eff6ff; color: #3B82F6; }

    /* Empty state na coluna */
    .col-empty {
        text-align: center;
        padding: 20px 10px;
        color: #9ca3af;
        font-size: 12.5px;
        border: 1.5px dashed #e8eaf0;
        border-radius: 8px;
        margin: 0;
    }

    /* Tag badges no card */
    .card-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        margin-top: 6px;
    }
    .card-tag-badge {
        font-size: 10.5px;
        font-weight: 600;
        padding: 2px 7px;
        border-radius: 99px;
        background: #f0f4ff;
        color: #6366f1;
        letter-spacing: .02em;
    }
    .card-task-bar {
        font-size: 10px;
        font-weight: 600;
        padding: 3px 8px;
        border-radius: 6px;
        margin-top: 6px;
        clear: both;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 4px;
    }
    .card-task-bar .ctb-left {
        display: flex;
        align-items: center;
        gap: 4px;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .card-task-bar .ctb-right {
        white-space: nowrap;
        flex-shrink: 0;
    }

    /* Filter bar */
    .kanban-filter-bar {
        display: none;
        padding: 10px 28px;
        gap: 8px;
        align-items: center;
        flex-wrap: wrap;
        background: #fafafa;
        border-bottom: 1px solid #f0f2f7;
        flex-shrink: 0;
    }
    .kanban-filter-bar.visible { display: flex; }
    .filter-control {
        padding: 6px 10px;
        border: 1.5px solid #e8eaf0;
        border-radius: 8px;
        font-size: 12.5px;
        font-family: inherit;
        outline: none;
        background: #fff;
        color: #374151;
        transition: border-color .15s;
    }
    .filter-control:focus { border-color: #3B82F6; }
    .filter-clear {
        font-size: 12px;
        color: #6b7280;
        text-decoration: none;
        padding: 5px 10px;
        border-radius: 8px;
        transition: background .15s;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .filter-clear:hover { background: #fee2e2; color: #ef4444; }

    /* ── Filtro Responsável multi-select ──────────────────────────────────── */
    .resp-filter-wrap { position: relative; }
    .resp-filter-btn {
        display: flex;
        align-items: center;
        gap: 5px;
        cursor: pointer;
        user-select: none;
        white-space: nowrap;
    }
    .resp-dropdown {
        display: none;
        position: absolute;
        top: calc(100% + 6px);
        left: 0;
        background: #fff;
        border: 1.5px solid #e8eaf0;
        border-radius: 10px;
        box-shadow: 0 4px 16px rgba(0,0,0,.1);
        padding: 6px 10px;
        min-width: 200px;
        z-index: 200;
        max-height: 260px;
        overflow-y: auto;
    }
    .resp-dropdown.open { display: block; }
    .resp-option {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 6px 4px;
        font-size: 13px;
        cursor: pointer;
        border-radius: 6px;
        transition: background .1s;
    }
    .resp-option:hover { background: #f0f7ff; }
    .resp-option input[type=checkbox] { cursor: pointer; accent-color: #3B82F6; }

    /* ── Empty state (sem pipelines) ─────────────────────────────────────── */
    .pipeline-empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        flex: 1;
        padding: 80px 20px;
        text-align: center;
    }
    .pipeline-empty-state .es-icon {
        font-size: 60px;
        opacity: .18;
        color: #374151;
        margin-bottom: 20px;
        display: block;
    }
    .pipeline-empty-state h3 {
        font-size: 18px;
        font-weight: 700;
        color: #1a1d23;
        margin: 0 0 8px;
    }
    .pipeline-empty-state p {
        font-size: 13.5px;
        color: #6b7280;
        margin: 0 0 28px;
        max-width: 420px;
        line-height: 1.6;
    }

    /* ── Drawer criar pipeline ─────────────────────────────────────────────── */
    #pipelineDrawerOverlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,.35);
        z-index: 199;
    }
    #pipelineDrawerOverlay.open { display: block; }
    #pipelineDrawer {
        position: fixed;
        top: 0; right: 0;
        width: 440px;
        height: 100vh;
        background: #fff;
        box-shadow: -4px 0 32px rgba(0,0,0,.1);
        z-index: 200;
        display: flex;
        flex-direction: column;
        transform: translateX(100%);
        transition: transform .25s cubic-bezier(.4,0,.2,1);
        overflow: hidden;
    }
    #pipelineDrawer.open { transform: translateX(0); }
    .pd-header {
        padding: 18px 22px;
        border-bottom: 1px solid #f0f2f7;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-shrink: 0;
    }
    .pd-title { font-size: 15px; font-weight: 700; color: #1a1d23; }
    .pd-close-btn {
        background: none; border: none; font-size: 18px;
        color: #9ca3af; cursor: pointer; width: 30px; height: 30px;
        display: flex; align-items: center; justify-content: center;
        border-radius: 6px; transition: all .15s;
    }
    .pd-close-btn:hover { background: #f3f4f6; color: #374151; }
    .pd-body { flex: 1; overflow-y: auto; padding: 22px; }
    .pd-footer {
        padding: 16px 22px;
        border-top: 1px solid #f0f2f7;
        display: flex;
        justify-content: flex-end;
        gap: 8px;
        flex-shrink: 0;
    }
    .pd-group { margin-bottom: 18px; }
    .pd-group label { display: block; font-size: 12px; font-weight: 600; color: #6b7280; margin-bottom: 5px; text-transform: uppercase; letter-spacing: .04em; }
    .pd-input {
        width: 100%; padding: 9px 12px;
        border: 1.5px solid #e8eaf0; border-radius: 9px;
        font-size: 13.5px; font-family: inherit;
        outline: none; transition: border-color .15s; box-sizing: border-box;
    }
    .pd-input:focus { border-color: #0085f3; }
    .pd-input.is-invalid { border-color: #ef4444; }
    .pd-color-row { display: flex; gap: 8px; align-items: center; }
    .pd-color-pick { width: 42px; height: 38px; padding: 2px; border: 1.5px solid #e8eaf0; border-radius: 9px; cursor: pointer; flex-shrink: 0; }
    .pd-btn-cancel {
        padding: 9px 20px; border-radius: 9px; border: 1.5px solid #e8eaf0;
        background: #fff; font-size: 13px; font-weight: 600; color: #6b7280;
        cursor: pointer; font-family: inherit; transition: all .15s;
    }
    .pd-btn-cancel:hover { background: #f0f2f7; }
    .pd-btn-save {
        padding: 9px 24px; border-radius: 9px; border: none;
        background: #0085f3; color: #fff; font-size: 13px; font-weight: 600;
        cursor: pointer; font-family: inherit; transition: background .15s;
        display: flex; align-items: center; gap: 6px;
    }
    .pd-btn-save:hover { background: #006acf; }
    .pd-btn-save:disabled { opacity: .6; cursor: not-allowed; }

    .fab-novo-lead {
        display: none;
        position: fixed; bottom: 24px; right: 20px; z-index: 90;
        width: 52px; height: 52px; border-radius: 50%;
        background: #0085f3; color: #fff; border: none;
        align-items: center; justify-content: center;
        font-size: 22px; cursor: pointer;
        box-shadow: 0 4px 14px rgba(0,133,243,.4);
    }

    /* ── Mobile ── */
    @media (max-width: 768px) {
        .kanban-board { padding: 12px 14px 14px; }
        .kanban-col { flex: 0 0 85vw; min-width: 240px; }
        .kanban-header { padding: 12px 14px 0; flex-wrap: wrap; gap: 8px; }
        .hide-mobile { display: none !important; }
        .fab-novo-lead { display: flex; }
    }
    @media (max-width: 480px) {
        .kanban-col { flex: 0 0 88vw; }
    }
</style>
@endpush

@section('content')

@if($pipelines->isNotEmpty())

<div class="kanban-header">
    @if($pipeline)
    <span class="kanban-pipeline-name">
        <i class="bi bi-diagram-3" style="color:#3B82F6;margin-right:4px;"></i>
        {{ $pipeline->name }}
    </span>
    @endif
    <span style="font-size:12px;color:#9ca3af;">
        {{ $stages->sum('count') }} leads no funil
    </span>
</div>

{{-- Filter bar --}}
<form method="GET" action="{{ route('crm.kanban') }}" id="kanbanFilterForm">
    @if(request('pipeline_id'))
    <input type="hidden" name="pipeline_id" value="{{ request('pipeline_id') }}">
    @endif
    <div class="kanban-filter-bar{{ request()->hasAny(['source','date_from','date_to','campaign_id','tag','responsible']) ? ' visible' : '' }}" id="filterBar">
        <select name="source" class="filter-control" onchange="this.form.submit()">
            <option value="">Todas as origens</option>
            @foreach(['manual','api','facebook','google','instagram','whatsapp','indicacao','site'] as $src)
            <option value="{{ $src }}" {{ request('source') == $src ? 'selected' : '' }}>{{ ucfirst($src) }}</option>
            @endforeach
        </select>

        <select name="tag" class="filter-control" onchange="this.form.submit()">
            <option value="">Todas as tags</option>
            @foreach($availableTags as $t)
            <option value="{{ $t->name }}" {{ request('tag') === $t->name ? 'selected' : '' }}>
                {{ $t->name }}
            </option>
            @endforeach
        </select>

        <input type="date" name="date_from" class="filter-control" value="{{ request('date_from') }}" title="Data de">
        <input type="date" name="date_to"   class="filter-control" value="{{ request('date_to') }}"   title="Data até">

        {{-- Multi-select: Responsável --}}
        @php $selectedResp = (array) request('responsible', []); @endphp
        <div class="resp-filter-wrap">
            <button type="button" class="filter-control resp-filter-btn" id="respDropBtn" onclick="toggleRespDrop(event)">
                <i class="bi bi-person"></i> Responsável
                @if(count($selectedResp) > 0)
                <span style="display:inline-flex;align-items:center;justify-content:center;
                             width:16px;height:16px;border-radius:50%;background:#3B82F6;
                             color:#fff;font-size:10px;font-weight:700;margin-left:3px;">
                    {{ count($selectedResp) }}
                </span>
                @endif
            </button>
            <div class="resp-dropdown" id="respDropdown">
                <label class="resp-option">
                    <input type="checkbox" name="responsible[]" value="ai"
                           {{ in_array('ai', $selectedResp) ? 'checked' : '' }}>
                    <i class="bi bi-robot" style="color:#8b5cf6;"></i> Agente IA
                </label>
                @foreach($users as $u)
                <label class="resp-option">
                    <input type="checkbox" name="responsible[]" value="{{ $u->id }}"
                           {{ in_array((string)$u->id, array_map('strval', $selectedResp)) ? 'checked' : '' }}>
                    {{ $u->name }}
                </label>
                @endforeach
            </div>
        </div>

        <button type="submit" class="btn-primary-sm" style="padding:6px 14px;">Aplicar</button>

        @if(request()->hasAny(['source','date_from','date_to','campaign_id','tag','responsible']))
        <a href="{{ route('crm.kanban', request()->only('pipeline_id')) }}" class="filter-clear">
            <i class="bi bi-x"></i> Limpar
        </a>
        @endif
    </div>
</form>

<div class="kanban-board" id="kanbanBoard">

    @if($stages->count())
    @php
        $cfOnCard    = $customFieldDefs->where('show_on_card', true);
        $tagColorMap = $availableTags->whereNotNull('color')->mapWithKeys(fn($t) => [$t->name => $t->color])->all();
    @endphp
    @foreach($stages as $stage)
    <div class="kanban-col" data-stage-id="{{ $stage['id'] }}">

        <div class="kanban-col-header">
            <div class="col-title">
                <span class="col-dot" style="background: {{ $stage['color'] }};"></span>
                {{ $stage['name'] }}
                @if($stage['is_won'])
                    <i class="bi bi-trophy-fill" style="color:#10B981;font-size:11px;"></i>
                @elseif($stage['is_lost'])
                    <i class="bi bi-x-circle-fill" style="color:#EF4444;font-size:11px;"></i>
                @endif
            </div>
            <div class="col-header-right">
                @if($stage['total_value'] > 0)
                <span class="col-value">R$ {{ number_format($stage['total_value'], 0, ',', '.') }}</span>
                @endif
                <span class="col-count" data-count="{{ $stage['id'] }}">{{ $stage['count'] }}</span>
            </div>
        </div>

        <div class="kanban-list sortable-zone"
             id="col-{{ $stage['id'] }}"
             data-stage-id="{{ $stage['id'] }}"
             data-pipeline-id="{{ $pipeline?->id }}"
             data-is-won="{{ $stage['is_won'] ? '1' : '0' }}"
             data-is-lost="{{ $stage['is_lost'] ? '1' : '0' }}">

            @if(count($stage['leads']))
            @foreach($stage['leads'] as $lead)
            @php
                $picUrl = $lead->whatsappConversation?->contact_picture_url;
                $initials = collect(explode(' ', $lead->name))->map(fn($w) => mb_strtoupper(mb_substr($w,0,1)))->take(2)->join('');
                $assignee = $lead->assignedTo?->name;
                $assigneeInit = $assignee ? collect(explode(' ', $assignee))->map(fn($w) => mb_strtoupper(mb_substr($w,0,1)))->take(2)->join('') : '';
                $convId = $lead->whatsappConversation?->id;
                $unread = $lead->whatsappConversation?->unread_count ?? 0;
            @endphp
            <div class="lead-card"
                 data-lead-id="{{ $lead->id }}"
                 data-stage-id="{{ $stage['id'] }}"
                 data-lead-value="{{ $lead->value ?? '' }}">

                {{-- Avatar float right --}}
                <div class="card-avatar">
                    @if($picUrl)
                    <img src="{{ $picUrl }}" alt="" onerror="this.style.display='none';this.parentElement.textContent='{{ $initials }}';">
                    @else
                    {{ $initials }}
                    @endif
                </div>

                {{-- Tags (se houver) --}}
                @if(!empty($lead->tags) && count($lead->tags))
                <div class="card-tags" style="margin-bottom:2px;">
                    @foreach($lead->tags as $tag)
                    <span class="card-tag-badge"
                        @if(isset($tagColorMap[$tag]))
                        style="background:{{ $tagColorMap[$tag] }}20;color:{{ $tagColorMap[$tag] }};border:1px solid {{ $tagColorMap[$tag] }}40;"
                        @endif
                    >{{ $tag }}</span>
                    @endforeach
                </div>
                @endif

                {{-- Nome --}}
                <div class="card-name btn-open-lead" data-lead-id="{{ $lead->id }}">{{ $lead->name }}</div>

                {{-- Valor (abaixo do avatar por causa do float) --}}
                @if($lead->value)
                <div class="card-value-row" style="text-align:right;clear:right;">R$ {{ number_format((float)$lead->value, 0, ',', '.') }}</div>
                @endif

                {{-- Custom fields (show_on_card=true) --}}
                @if($cfOnCard->count() && !empty($stage['lead_cf'][$lead->id]))
                <div class="card-meta" style="margin-top:4px;">
                    @foreach($cfOnCard as $cfDef)
                    @if(($stage['lead_cf'][$lead->id][$cfDef->name]['value'] ?? null) !== null
                        && ($stage['lead_cf'][$lead->id][$cfDef->name]['value'] ?? '') !== ''
                        && ($stage['lead_cf'][$lead->id][$cfDef->name]['value'] ?? false) !== false)
                    <div class="card-meta-row">
                        <i class="bi bi-tag" style="font-size:11px;"></i>
                        <span style="font-weight:600;color:#374151;">{{ $cfDef->label }}:</span>
                        <span>{{ is_array($stage['lead_cf'][$lead->id][$cfDef->name]['value']) ? implode(', ', $stage['lead_cf'][$lead->id][$cfDef->name]['value']) : ($stage['lead_cf'][$lead->id][$cfDef->name]['value'] ?? '') }}</span>
                    </div>
                    @endif
                    @endforeach
                </div>
                @endif

                {{-- Footer: avatar atendente + ações + data --}}
                <div class="card-footer">
                    <div style="display:flex;align-items:center;gap:2px;">
                        @if($assignee)
                        <div class="card-assignee" title="{{ $assignee }}">{{ $assigneeInit }}</div>
                        @endif
                        <div class="card-actions">
                            @if($lead->phone)
                            <a href="tel:{{ $lead->phone }}" class="card-action-btn" onclick="event.stopPropagation();" title="Ligar"><i class="bi bi-telephone-fill"></i></a>
                            @endif
                            @if($convId)
                            <a href="{{ route('chats.index') }}?open={{ $convId }}" class="card-action-btn wa-btn {{ $unread > 0 ? 'has-unread' : '' }}" onclick="event.stopPropagation();" title="Abrir conversa" style="position:relative;">
                                <i class="bi bi-whatsapp"></i>
                                @if($unread > 0)<span class="bubble-count">{{ $unread }}</span>@endif
                            </a>
                            @endif
                            @if($lead->email)
                            <a href="mailto:{{ $lead->email }}" class="card-action-btn" onclick="event.stopPropagation();" title="Enviar email"><i class="bi bi-envelope-fill"></i></a>
                            @endif
                        </div>
                    </div>
                    <span class="card-date">
                        <i class="bi bi-clock"></i>
                        {{ $lead->created_at?->diffForHumans(null, true, true) }}
                    </span>
                </div>

                @php
                    $nearestTask = $lead->tasks()->where('status', 'pending')->orderBy('due_date')->orderBy('due_time')->first();
                @endphp
                @if($nearestTask)
                @php
                    $tkDays = (int) today()->diffInDays($nearestTask->due_date, false);
                    $tkColor = $tkDays <= 1 ? '#ef4444' : ($tkDays <= 3 ? '#f59e0b' : '#10b981');
                    $tkIcons = ['call'=>'telephone','email'=>'envelope','task'=>'check2-square','visit'=>'geo-alt','whatsapp'=>'whatsapp','meeting'=>'camera-video'];
                    $tkIco = $tkIcons[$nearestTask->type] ?? 'check2-square';
                    $tkRel = $tkDays < 0 ? abs($tkDays).'d atrás' : ($tkDays === 0 ? 'Hoje' : ($tkDays === 1 ? 'Amanhã' : $tkDays.'d'));
                    $tkSubj = \Illuminate\Support\Str::limit($nearestTask->subject, 22);
                @endphp
                <div class="card-task-bar" style="background:{{ $tkColor }}20;color:{{ $tkColor }};border:1px solid {{ $tkColor }}40;">
                    <span class="ctb-left"><i class="bi bi-{{ $tkIco }}"></i> {{ $tkSubj }}</span>
                    <span class="ctb-right">{{ $tkRel }}</span>
                </div>
                @endif

            </div>
            @endforeach
            @else
            <div class="col-empty">Arraste leads aqui</div>
            @endif

        </div>

        <button class="btn-add-card btn-add-in-col"
                data-stage-id="{{ $stage['id'] }}"
                data-pipeline-id="{{ $pipeline?->id }}">
            <i class="bi bi-plus"></i>
            Adicionar lead
        </button>

    </div>
    @endforeach
    @else
    <div style="padding:60px;text-align:center;color:#9ca3af;">
        <i class="bi bi-kanban" style="font-size:48px;opacity:.3;"></i>
        <p style="margin-top:16px;">Nenhuma etapa configurada neste pipeline.</p>
    </div>
    @endif

</div>

{{-- Drawer compartilhado --}}
@include('tenant.leads._drawer', ['pipelines' => $pipelines ?? collect(), 'customFieldDefs' => $customFieldDefs ?? collect()])

{{-- Modal: Lead Ganho --}}
<div id="modalWon" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:600;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:14px;padding:28px;width:380px;max-width:94vw;box-shadow:0 20px 60px rgba(0,0,0,.18);">
        <div style="font-size:16px;font-weight:700;color:#1a1d23;margin-bottom:8px;">
            <i class="bi bi-trophy-fill" style="color:#10B981;margin-right:6px;"></i> Lead Ganho!
        </div>
        <p style="font-size:13px;color:#6b7280;margin-bottom:16px;">
            Informe o valor do negócio (opcional).
        </p>
        <input type="number" id="wonValueInput" min="0" step="0.01" placeholder="Valor (ex: 1500.00)"
               style="width:100%;padding:9px 12px;border:1.5px solid #e8eaf0;border-radius:9px;font-size:13px;box-sizing:border-box;margin-bottom:16px;font-family:inherit;"
               onkeydown="if(event.key==='Enter') confirmWonModal()">
        <div style="display:flex;gap:8px;justify-content:flex-end;">
            <button onclick="skipWonModal()" style="padding:8px 16px;border-radius:8px;border:1.5px solid #e8eaf0;background:#fff;font-size:13px;font-weight:600;color:#6b7280;cursor:pointer;font-family:inherit;">Pular</button>
            <button onclick="confirmWonModal()" style="padding:8px 20px;border-radius:8px;border:none;background:#10B981;color:#fff;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit;">Confirmar</button>
        </div>
    </div>
</div>

{{-- Modal: Lead Perdido --}}
<div id="modalLost" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:600;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:14px;padding:28px;width:380px;max-width:94vw;box-shadow:0 20px 60px rgba(0,0,0,.18);">
        <div style="font-size:16px;font-weight:700;color:#1a1d23;margin-bottom:8px;">
            <i class="bi bi-x-circle-fill" style="color:#EF4444;margin-right:6px;"></i> Lead Perdido
        </div>
        <p style="font-size:13px;color:#6b7280;margin-bottom:16px;">
            Selecione o motivo da perda (opcional).
        </p>
        <select id="lostReasonSelect"
                style="width:100%;padding:9px 12px;border:1.5px solid #e8eaf0;border-radius:9px;font-size:13px;box-sizing:border-box;margin-bottom:16px;font-family:inherit;background:#fff;color:#374151;">
            <option value="">Sem motivo</option>
            @foreach($lostReasons as $reason)
            <option value="{{ $reason->id }}">{{ $reason->name }}</option>
            @endforeach
        </select>
        <div style="display:flex;gap:8px;justify-content:flex-end;">
            <button onclick="skipLostModal()" style="padding:8px 16px;border-radius:8px;border:1.5px solid #e8eaf0;background:#fff;font-size:13px;font-weight:600;color:#6b7280;cursor:pointer;font-family:inherit;">Pular</button>
            <button onclick="confirmLostModal()" style="padding:8px 20px;border-radius:8px;border:none;background:#EF4444;color:#fff;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit;">Confirmar</button>
        </div>
    </div>
</div>

@else

{{-- Empty state: nenhum funil criado --}}
<div class="pipeline-empty-state">
    <i class="bi bi-diagram-3 es-icon"></i>
    <h3>Nenhum funil configurado</h3>
    <p>Crie seu primeiro funil de vendas para começar a organizar seus leads em etapas e acompanhar o progresso do seu negócio.</p>
    <button class="btn-primary-sm" onclick="openPipelineDrawer()" style="font-size:14px;padding:10px 28px;gap:8px;">
        <i class="bi bi-plus-lg"></i>
        Criar meu primeiro funil
    </button>
</div>

@endif

{{-- Overlay + Drawer: Criar Pipeline ─────────────────────────────────────── --}}
<div id="pipelineDrawerOverlay" onclick="closePipelineDrawer()"></div>

<aside id="pipelineDrawer">
    <div class="pd-header">
        <span class="pd-title">Criar novo funil</span>
        <button class="pd-close-btn" onclick="closePipelineDrawer()">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <div class="pd-body">
        <div class="pd-group">
            <label for="pdPipelineName">Nome do funil</label>
            <input type="text" id="pdPipelineName" class="pd-input" placeholder="Ex: Vendas 2025" autocomplete="off">
        </div>

        <div class="pd-group">
            <label for="pdPipelineColorText">Cor</label>
            <div class="pd-color-row">
                <input type="color" id="pdPipelineColor" class="pd-color-pick" value="#0085f3">
                <input type="text" id="pdPipelineColorText" class="pd-input" value="#0085f3" placeholder="#0085f3">
            </div>
        </div>
    </div>

    <div class="pd-footer">
        <button class="pd-btn-cancel" onclick="closePipelineDrawer()">Cancelar</button>
        <button class="pd-btn-save" id="pdBtnSave" onclick="savePipelineDrawer()">
            <i class="bi bi-check-lg"></i> Criar funil
        </button>
    </div>
</aside>

{{-- Modal: Importar Leads ────────────────────────────────────────────────── --}}
<div id="modalImport" style="display:none;position:fixed;inset:0;z-index:1060;background:rgba(0,0,0,.45);align-items:center;justify-content:center;">
    <div id="importModalBox" style="background:#fff;border-radius:16px;width:720px;max-width:96vw;padding:28px;box-shadow:0 20px 60px rgba(0,0,0,.18);display:flex;flex-direction:column;max-height:90vh;">

        {{-- ── TELA A: Upload ─────────────────────────────────────────────── --}}
        <div id="importScreenUpload">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:20px;">
                <div>
                    <h3 style="font-size:16px;font-weight:700;color:#1a1d23;margin:0 0 3px;">Importar Leads</h3>
                    <p id="importModalPipeline" style="font-size:12px;color:#9ca3af;margin:0;"></p>
                </div>
                <button onclick="closeImportModal()" style="background:none;border:none;font-size:18px;color:#9ca3af;cursor:pointer;line-height:1;padding:0;"><i class="bi bi-x-lg"></i></button>
            </div>

            {{-- Download template --}}
            <div style="background:#f0f9ff;border:1.5px solid #bae6fd;border-radius:10px;padding:14px;margin-bottom:18px;display:flex;align-items:center;gap:12px;">
                <i class="bi bi-file-earmark-spreadsheet" style="font-size:24px;color:#0ea5e9;flex-shrink:0;"></i>
                <div style="flex:1;min-width:0;">
                    <p style="font-size:12.5px;font-weight:600;color:#0369a1;margin:0 0 2px;">Planilha modelo</p>
                    <p style="font-size:11.5px;color:#6b7280;margin:0;">Inclui as etapas do funil atual como referência</p>
                </div>
                <a id="btnDownloadTemplate" href="#" class="btn-primary-sm" style="font-size:12px;padding:6px 14px;white-space:nowrap;text-decoration:none;">
                    <i class="bi bi-download"></i> Baixar
                </a>
            </div>

            {{-- File upload --}}
            <div style="margin-bottom:20px;">
                <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:6px;">Selecionar arquivo</label>
                <input type="file" id="importFileInput" accept=".xlsx,.xls,.csv"
                       style="width:100%;padding:10px;border:1.5px dashed #d1d5db;border-radius:9px;font-size:13px;box-sizing:border-box;cursor:pointer;background:#fafafa;font-family:inherit;">
                <p style="font-size:11px;color:#9ca3af;margin:5px 0 0;">Formatos: .xlsx, .xls, .csv — máximo 5 MB</p>
            </div>

            {{-- Actions --}}
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button onclick="closeImportModal()"
                        style="padding:9px 20px;border-radius:100px;border:1.5px solid #e8eaf0;background:#fff;font-size:13px;font-weight:600;color:#6b7280;cursor:pointer;font-family:inherit;">
                    Cancelar
                </button>
                <button id="btnImportPreview" onclick="submitPreview()"
                        style="padding:9px 24px;border-radius:100px;border:none;background:#0085f3;color:#fff;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit;display:flex;align-items:center;gap:6px;">
                    <i class="bi bi-eye"></i> Pré-visualizar
                </button>
            </div>
        </div>

        {{-- ── TELA B: Preview ─────────────────────────────────────────────── --}}
        <div id="importScreenPreview" style="display:none;flex-direction:column;flex:1;min-height:0;">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:14px;flex-shrink:0;">
                <div>
                    <h3 style="font-size:16px;font-weight:700;color:#1a1d23;margin:0 0 3px;">Pré-visualização</h3>
                    <p id="importPreviewSummary" style="font-size:12px;color:#6b7280;margin:0;"></p>
                </div>
                <button onclick="closeImportModal()" style="background:none;border:none;font-size:18px;color:#9ca3af;cursor:pointer;line-height:1;padding:0;"><i class="bi bi-x-lg"></i></button>
            </div>

            {{-- Tabela scrollável --}}
            <div style="flex:1;overflow-y:auto;min-height:0;max-height:420px;border:1.5px solid #e8eaf0;border-radius:10px;">
                <table style="width:100%;border-collapse:collapse;font-size:12.5px;">
                    <thead style="position:sticky;top:0;background:#f8fafc;z-index:1;">
                        <tr>
                            <th style="padding:9px 12px;text-align:left;font-weight:700;color:#374151;border-bottom:1.5px solid #e8eaf0;white-space:nowrap;">Nome</th>
                            <th style="padding:9px 12px;text-align:left;font-weight:700;color:#374151;border-bottom:1.5px solid #e8eaf0;white-space:nowrap;">Telefone</th>
                            <th style="padding:9px 12px;text-align:left;font-weight:700;color:#374151;border-bottom:1.5px solid #e8eaf0;white-space:nowrap;">E-mail</th>
                            <th style="padding:9px 12px;text-align:left;font-weight:700;color:#374151;border-bottom:1.5px solid #e8eaf0;white-space:nowrap;">Valor</th>
                            <th style="padding:9px 12px;text-align:left;font-weight:700;color:#374151;border-bottom:1.5px solid #e8eaf0;white-space:nowrap;">Etapa</th>
                            <th style="padding:9px 12px;text-align:left;font-weight:700;color:#374151;border-bottom:1.5px solid #e8eaf0;white-space:nowrap;">Tags</th>
                            <th style="padding:9px 12px;text-align:left;font-weight:700;color:#374151;border-bottom:1.5px solid #e8eaf0;white-space:nowrap;">Origem</th>
                            <th style="padding:9px 12px;text-align:left;font-weight:700;color:#374151;border-bottom:1.5px solid #e8eaf0;white-space:nowrap;">Criado em</th>
                        </tr>
                    </thead>
                    <tbody id="importPreviewTbody"></tbody>
                </table>
            </div>

            {{-- Actions --}}
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px;flex-shrink:0;">
                <button onclick="importGoBack()"
                        style="padding:9px 20px;border-radius:9px;border:1.5px solid #e8eaf0;background:#fff;font-size:13px;font-weight:600;color:#6b7280;cursor:pointer;font-family:inherit;display:flex;align-items:center;gap:6px;">
                    <i class="bi bi-arrow-left"></i> Voltar
                </button>
                <button id="btnImportConfirm" onclick="confirmImport()"
                        style="padding:9px 24px;border-radius:9px;border:none;background:#10B981;color:#fff;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit;display:flex;align-items:center;gap:6px;">
                    <i class="bi bi-check-circle"></i> Confirmar importação
                </button>
            </div>
        </div>

    </div>
</div>

<button class="fab-novo-lead" id="fabNovoLead" title="Novo Lead">
    <i class="bi bi-plus-lg"></i>
</button>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
function formatBrPhone(phone) {
    let d = (phone || '').replace(/\D/g, '');
    if (d.startsWith('55') && d.length >= 12) d = d.slice(2);
    if (d.length === 11) return `(${d.slice(0,2)}) ${d.slice(2,7)}-${d.slice(7)}`;
    if (d.length === 10) return `(${d.slice(0,2)}) ${d.slice(2,6)}-${d.slice(6)}`;
    return phone || '';
}

const STAGE_URL      = @json(route('crm.lead.stage', ['lead' => '__ID__']));
const LEAD_SHOW      = @json(route('leads.show',   ['lead' => '__ID__']));
const LEAD_STORE     = @json(route('leads.store'));
const LEAD_UPD       = @json(route('leads.update', ['lead' => '__ID__']));
const LEAD_DEL       = @json(route('leads.kanban-remove',['lead' => '__ID__']));
const KANBAN_POLL    = @json(route('crm.poll'));
const CF_ON_CARD     = @json($customFieldDefs->where('show_on_card', true)->values()->map(fn($d) => ['name' => $d->name, 'label' => $d->label])->toArray());
const TAG_COLORS     = {!! json_encode($tagColorMap) !!};

// ── Pipeline select ────────────────────────────────────────────────────────
document.getElementById('pipelineSelect')?.addEventListener('change', function() {
    const url = new URL(window.location.href);
    url.searchParams.set('pipeline_id', this.value);
    window.location.href = url.toString();
});

// ── Won/Lost pending state ─────────────────────────────────────────────────
let _wonPending  = null;
let _lostPending = null;

// ── Inicializa SortableJS em cada coluna ──────────────────────────────────
if (!window.isViewer) {
document.querySelectorAll('.sortable-zone').forEach(zone => {
    Sortable.create(zone, {
        group:     'kanban',
        animation: 150,
        delay: 150,
        delayOnTouchOnly: true,
        touchStartThreshold: 5,
        ghostClass:  'sortable-ghost',
        dragClass:   'sortable-drag',
        handle:    '.lead-card',
        onStart: () => { window._kDragging = true; },
        onEnd:   () => { setTimeout(() => { window._kDragging = false; }, 50); },
        onAdd(evt) {
            const leadId    = evt.item.dataset.leadId;
            const stageId   = evt.to.dataset.stageId;
            const pipId     = evt.to.dataset.pipelineId;
            const isWon     = evt.to.dataset.isWon  === '1';
            const isLost    = evt.to.dataset.isLost === '1';
            const leadValue = evt.item.dataset.leadValue;

            // Remove empty placeholder se existir
            evt.to.querySelector('.col-empty')?.remove();

            // Atualiza contadores
            updateCount(evt.from.dataset.stageId, -1);
            updateCount(stageId, +1);

            if (isLost) {
                showLostModal(leadId, stageId, pipId);
            } else if (isWon && !leadValue) {
                showWonModal(leadId, stageId, pipId);
            } else {
                saveStageChange(leadId, stageId, pipId);
            }
        },
    });
});
} // end if (!window.isViewer)

function saveStageChange(leadId, stageId, pipId, extra = {}) {
    $.ajax({
        url: STAGE_URL.replace('__ID__', leadId),
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ stage_id: stageId, pipeline_id: pipId, ...extra }),
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' },
    }).done(res => {
        if (res.success) {
            toastr.success('Lead movido!');
        }
    }).fail(() => {
        toastr.error('Erro ao mover lead. Recarregue a página.');
    });
}

// ── Won Modal ──────────────────────────────────────────────────────────────
function showWonModal(leadId, stageId, pipId) {
    _wonPending = { leadId, stageId, pipId };
    document.getElementById('wonValueInput').value = '';
    document.getElementById('modalWon').style.display = 'flex';
    setTimeout(() => document.getElementById('wonValueInput').focus(), 100);
}

function skipWonModal() {
    document.getElementById('modalWon').style.display = 'none';
    if (_wonPending) {
        saveStageChange(_wonPending.leadId, _wonPending.stageId, _wonPending.pipId);
        _wonPending = null;
    }
}

function confirmWonModal() {
    const value = document.getElementById('wonValueInput').value;
    document.getElementById('modalWon').style.display = 'none';
    if (_wonPending) {
        const extra = value ? { value: parseFloat(value) } : {};
        saveStageChange(_wonPending.leadId, _wonPending.stageId, _wonPending.pipId, extra);
        _wonPending = null;
    }
}

// ── Lost Modal ─────────────────────────────────────────────────────────────
function showLostModal(leadId, stageId, pipId) {
    _lostPending = { leadId, stageId, pipId };
    const sel = document.getElementById('lostReasonSelect');
    if (sel) sel.value = '';
    document.getElementById('modalLost').style.display = 'flex';
}

function skipLostModal() {
    document.getElementById('modalLost').style.display = 'none';
    if (_lostPending) {
        saveStageChange(_lostPending.leadId, _lostPending.stageId, _lostPending.pipId);
        _lostPending = null;
    }
}

function confirmLostModal() {
    const sel      = document.getElementById('lostReasonSelect');
    const reasonId = sel ? sel.value : '';
    document.getElementById('modalLost').style.display = 'none';
    if (_lostPending) {
        const extra = reasonId ? { lost_reason_id: parseInt(reasonId) } : {};
        saveStageChange(_lostPending.leadId, _lostPending.stageId, _lostPending.pipId, extra);
        _lostPending = null;
    }
}

function updateCount(stageId, delta) {
    const el = document.querySelector(`[data-count="${stageId}"]`);
    if (!el) return;
    el.textContent = Math.max(0, parseInt(el.textContent) + delta);
}

// ── Abrir drawer ao clicar no card (ou no nome) ───────────────────────────
document.addEventListener('click', e => {
    // Clique no nome (comportamento original)
    const btn = e.target.closest('.btn-open-lead');
    if (btn) { e.stopPropagation(); openLeadDrawer(btn.dataset.leadId); return; }

    // Clique em qualquer parte do card — ignorar se foi um drag ou clique em elemento interativo
    if (window._kDragging) return;
    if (e.target.closest('button, a, .card-tag-badge, .btn-add-in-col')) return;
    const card = e.target.closest('.lead-card[data-lead-id]');
    if (card) openLeadDrawer(card.dataset.leadId);
});

// ── Botão "Adicionar lead" por coluna ─────────────────────────────────────
document.addEventListener('click', e => {
    const btn = e.target.closest('.btn-add-in-col');
    if (!btn) return;
    openNewLeadDrawer({
        stage_id:    btn.dataset.stageId,
        pipeline_id: btn.dataset.pipelineId,
    });
});

// ── Botão global "Novo Lead" ───────────────────────────────────────────────
document.getElementById('btnNovoLead')?.addEventListener('click', () => {
    openNewLeadDrawer();
});
document.getElementById('fabNovoLead')?.addEventListener('click', () => {
    openNewLeadDrawer();
});

// ── Toggle filtros ─────────────────────────────────────────────────────────
document.getElementById('btnToggleFilters')?.addEventListener('click', () => {
    const bar = document.getElementById('filterBar');
    bar.classList.toggle('visible');
});

// ── Após salvar: atualiza ou adiciona card no board ───────────────────────
window.onLeadSaved = function(lead, isNew) {
    if (isNew) {
        addCardToBoard(lead);
    } else {
        updateCardInBoard(lead);
    }
};

window.onLeadDeleted = function(leadId) {
    document.querySelector(`.lead-card[data-lead-id="${leadId}"]`)?.remove();
};

function addCardToBoard(lead) {
    const col = document.getElementById(`col-${lead.stage_id}`);
    if (!col) return;
    col.querySelector('.col-empty')?.remove();
    col.insertAdjacentHTML('afterbegin', buildCard(lead));
    updateCount(lead.stage_id, +1);
}

function updateCardInBoard(lead) {
    const card = document.querySelector(`.lead-card[data-lead-id="${lead.id}"]`);
    if (!card) return;

    const oldStageId = card.dataset.stageId;
    const newStageId = String(lead.stage_id);

    if (oldStageId !== newStageId) {
        const newCol = document.getElementById(`col-${newStageId}`);
        if (newCol) {
            card.remove();
            updateCount(oldStageId, -1);
            newCol.querySelector('.col-empty')?.remove();
            newCol.insertAdjacentHTML('afterbegin', buildCard(lead));
            updateCount(newStageId, +1);
        }
    } else {
        card.outerHTML = buildCard(lead);
    }
}

const SOURCE_META = {
    facebook:  { icon: 'bi-facebook',    color: '#1877F2', label: 'Facebook Ads' },
    google:    { icon: 'bi-google',       color: '#4285F4', label: 'Google Ads' },
    instagram: { icon: 'bi-instagram',   color: '#E1306C', label: 'Instagram' },
    whatsapp:  { icon: 'bi-whatsapp',    color: '#25D366', label: 'WhatsApp' },
    site:      { icon: 'bi-globe',       color: '#6366F1', label: 'Site' },
    indicacao: { icon: 'bi-people-fill', color: '#F59E0B', label: 'Indicação' },
    api:       { icon: 'bi-code-slash',  color: '#8B5CF6', label: 'API' },
    manual:    { icon: 'bi-pencil',      color: '#6B7280', label: 'Manual' },
    outro:     { icon: 'bi-three-dots',  color: '#9CA3AF', label: 'Outro' },
};
function renderSourceBadge(source, cls = 'source-badge') {
    const m = SOURCE_META[source] || SOURCE_META.outro;
    return `<span class="${cls}"><i class="bi ${m.icon}" style="color:${m.color};margin-right:3px;"></i>${escapeHtml(m.label)}</span>`;
}

function getInitials(name) {
    if (!name) return '?';
    return name.split(' ').map(w => w.charAt(0).toUpperCase()).slice(0,2).join('');
}
function buildCard(lead) {
    const pic = lead.contact_picture_url;
    const initials = getInitials(lead.name);
    const avatarInner = pic
        ? `<img src="${pic}" alt="" onerror="this.style.display='none';this.parentElement.textContent='${initials}';">`
        : initials;

    const hasTags = lead.tags && lead.tags.length;
    const tagsHtml = hasTags
        ? lead.tags.map(t => {
            const c = TAG_COLORS[t];
            const s = c ? ` style="background:${c}20;color:${c};border:1px solid ${c}40;"` : '';
            return `<span class="card-tag-badge"${s}>${escapeHtml(t)}</span>`;
          }).join('')
        : '';

    const valueInline = lead.value_fmt ? `<span class="card-value-inline">${escapeHtml(lead.value_fmt)}</span>` : '';

    // Custom fields on card
    const cfSource = lead.cf_flat || {};
    if (lead.custom_fields && !Object.keys(cfSource).length) {
        Object.entries(lead.custom_fields).forEach(([k, v]) => {
            cfSource[k] = (v && typeof v === 'object' && 'value' in v) ? v.value : v;
        });
    }
    const cfRows = CF_ON_CARD.map(f => {
        const v = cfSource[f.name];
        if (v === undefined || v === null || v === '') return '';
        const d = Array.isArray(v) ? v.join(', ') : String(v);
        return `<div class="card-meta-row"><i class="bi bi-tag" style="font-size:11px;"></i><span style="font-weight:600;color:#374151;">${escapeHtml(f.label)}:</span> <span>${escapeHtml(d)}</span></div>`;
    }).join('');
    const cfBlock = cfRows ? `<div class="card-meta" style="margin-top:4px;">${cfRows}</div>` : '';

    // Footer: assignee + actions + date
    const assignee = lead.assigned_to_name;
    const assigneeHtml = assignee ? `<div class="card-assignee" title="${escapeHtml(assignee)}">${getInitials(assignee)}</div>` : '';

    let actions = '';
    if (lead.phone) actions += `<a href="tel:${lead.phone}" class="card-action-btn" onclick="event.stopPropagation();" title="Ligar"><i class="bi bi-telephone-fill"></i></a>`;
    if (lead.conversation_id) {
        const unread = lead.unread_count || 0;
        actions += `<a href="/chats?open=${lead.conversation_id}" class="card-action-btn wa-btn${unread > 0 ? ' has-unread' : ''}" onclick="event.stopPropagation();" title="Abrir conversa" style="position:relative;"><i class="bi bi-whatsapp"></i>${unread > 0 ? `<span class="bubble-count">${unread}</span>` : ''}</a>`;
    }
    if (lead.email) actions += `<a href="mailto:${lead.email}" class="card-action-btn" onclick="event.stopPropagation();" title="Enviar email"><i class="bi bi-envelope-fill"></i></a>`;

    const date = lead.created_at ? `<span class="card-date"><i class="bi bi-clock"></i>${escapeHtml(lead.created_at)}</span>` : '';

    const tagsBlock = hasTags ? `<div class="card-tags" style="margin-bottom:2px;">${tagsHtml}</div>` : '';

    // Task bar
    let taskBar = '';
    if (lead.nearest_task) {
        const d = lead.nearest_task.due_date;
        const diff = Math.ceil((new Date(d) - new Date(new Date().toDateString())) / 86400000);
        const cor = diff <= 1 ? '#ef4444' : diff <= 3 ? '#f59e0b' : '#10b981';
        const taskIcons = {call:'telephone',email:'envelope',task:'check2-square',visit:'geo-alt',whatsapp:'whatsapp',meeting:'camera-video'};
        const ico = taskIcons[lead.nearest_task.type] || 'check2-square';
        const rel = diff < 0 ? Math.abs(diff) + 'd atrás' : diff === 0 ? 'Hoje' : diff === 1 ? 'Amanhã' : diff + 'd';
        const subj = lead.nearest_task.subject.length > 22 ? lead.nearest_task.subject.substring(0, 22) + '…' : lead.nearest_task.subject;
        taskBar = `<div class="card-task-bar" style="background:${cor}20;color:${cor};border:1px solid ${cor}40;"><span class="ctb-left"><i class="bi bi-${ico}"></i> ${escapeHtml(subj)}</span><span class="ctb-right">${rel}</span></div>`;
    }

    return `
    <div class="lead-card" data-lead-id="${lead.id}" data-stage-id="${lead.stage_id}" data-lead-value="${lead.value || ''}">
        <div class="card-avatar">${avatarInner}</div>
        ${tagsBlock}
        <div class="card-name btn-open-lead" data-lead-id="${lead.id}">${escapeHtml(lead.name)}</div>
        ${lead.value_fmt ? `<div class="card-value-row" style="text-align:right;clear:right;">${escapeHtml(lead.value_fmt)}</div>` : ''}
        ${cfBlock}
        <div class="card-footer">
            <div style="display:flex;align-items:center;gap:2px;">
                ${assigneeHtml}
                <div class="card-actions">${actions}</div>
            </div>
            ${date}
        </div>
        ${taskBar}
    </div>`;
}

// ── Polling em tempo real (a cada 10 s) ────────────────────────────────────
let _lastPollTime = Math.floor(Date.now() / 1000);

function pollKanban() {
    const pipelineId = document.getElementById('pipelineSelect')?.value;
    if (!pipelineId) return;

    $.ajax({
        url: KANBAN_POLL,
        method: 'GET',
        data: { pipeline_id: pipelineId, since: _lastPollTime },
        headers: { 'Accept': 'application/json' },
        success(res) {
            (res.leads || []).forEach(lead => {
                const existing = document.querySelector(`.lead-card[data-lead-id="${lead.id}"]`);
                if (existing) {
                    updateCardInBoard(lead);
                } else {
                    addCardToBoard(lead);
                    toastr.info(`Novo lead: ${escapeHtml(lead.name)}`, '', { timeOut: 4000 });
                }
            });
            if (res.server_time) _lastPollTime = res.server_time;
        }
    });
}

setInterval(pollKanban, 10000);

// ── Drawer: Criar Pipeline ─────────────────────────────────────────────────
const CP_STORE_URL = @json(route('settings.pipelines.store'));
const CP_CRM_URL   = @json(route('crm.kanban'));
const CP_CSRF      = document.querySelector('meta[name="csrf-token"]')?.content;

function openPipelineDrawer() {
    document.getElementById('pdPipelineName').value      = '';
    document.getElementById('pdPipelineColor').value     = '#0085f3';
    document.getElementById('pdPipelineColorText').value = '#0085f3';
    document.getElementById('pdPipelineName').classList.remove('is-invalid');
    document.getElementById('pipelineDrawer').classList.add('open');
    document.getElementById('pipelineDrawerOverlay').classList.add('open');
    setTimeout(() => document.getElementById('pdPipelineName').focus(), 250);
}

function closePipelineDrawer() {
    document.getElementById('pipelineDrawer').classList.remove('open');
    document.getElementById('pipelineDrawerOverlay').classList.remove('open');
}

document.getElementById('pdPipelineColor').addEventListener('input', e => {
    document.getElementById('pdPipelineColorText').value = e.target.value;
});
document.getElementById('pdPipelineColorText').addEventListener('input', e => {
    if (/^#[0-9a-f]{6}$/i.test(e.target.value)) {
        document.getElementById('pdPipelineColor').value = e.target.value;
    }
});

async function savePipelineDrawer() {
    const name  = document.getElementById('pdPipelineName').value.trim();
    const color = document.getElementById('pdPipelineColorText').value.trim() || document.getElementById('pdPipelineColor').value;

    if (!name) {
        document.getElementById('pdPipelineName').classList.add('is-invalid');
        document.getElementById('pdPipelineName').focus();
        return;
    }

    const btn = document.getElementById('pdBtnSave');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Criando…';

    try {
        const res  = await fetch(CP_STORE_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CP_CSRF, 'Accept': 'application/json' },
            body: JSON.stringify({ name, color }),
        });
        const data = await res.json();
        if (!res.ok || !data.success) throw new Error(data.message || 'Erro ao criar funil');

        window.location.href = `${CP_CRM_URL}?pipeline_id=${data.pipeline.id}`;

    } catch (e) {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-lg"></i> Criar funil';
        toastr.error(e.message || 'Erro ao criar o funil. Tente novamente.');
    }
}

// ── Exportar / Importar Leads ──────────────────────────────────────────────
const KANBAN_EXPORT_URL  = @json(route('crm.export'));
const KANBAN_IMPORT_URL  = @json(route('crm.import'));
const KANBAN_PREVIEW_URL = @json(route('crm.import.preview'));
const KANBAN_TMPL_URL    = @json(route('crm.template'));

function exportarLeads() {
    const pipelineId = document.getElementById('pipelineSelect')?.value;
    const params = new URLSearchParams();
    if (pipelineId) params.set('pipeline_id', pipelineId);
    const urlParams = new URLSearchParams(window.location.search);
    ['source', 'campaign_id', 'date_from', 'date_to', 'tag'].forEach(k => {
        const v = urlParams.get(k);
        if (v) params.set(k, v);
    });
    window.location.href = `${KANBAN_EXPORT_URL}?${params.toString()}`;
}

let _importPipelineId = null;
let _importToken      = null;

function openImportModal() {
    const sel = document.getElementById('pipelineSelect');
    _importPipelineId = sel?.value || null;
    _importToken      = null;
    const pipelineName = sel?.options[sel.selectedIndex]?.text || '';

    document.getElementById('importModalPipeline').textContent = pipelineName ? `Funil: ${pipelineName}` : '';
    document.getElementById('btnDownloadTemplate').href = `${KANBAN_TMPL_URL}?pipeline_id=${_importPipelineId}`;
    document.getElementById('importFileInput').value = '';

    const btn = document.getElementById('btnImportPreview');
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-eye"></i> Pré-visualizar';

    document.getElementById('importScreenUpload').style.display  = '';
    document.getElementById('importScreenPreview').style.display = 'none';

    document.getElementById('modalImport').style.display = 'flex';
}

function closeImportModal() {
    document.getElementById('modalImport').style.display = 'none';
    _importToken = null;
}

function importGoBack() {
    _importToken = null;
    document.getElementById('importScreenPreview').style.display = 'none';
    document.getElementById('importScreenUpload').style.display  = '';
    const btn = document.getElementById('btnImportPreview');
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-eye"></i> Pré-visualizar';
}

async function submitPreview() {
    const file = document.getElementById('importFileInput').files[0];
    if (!file) { toastr.warning('Selecione um arquivo antes de pré-visualizar.'); return; }
    if (!_importPipelineId) { toastr.error('Nenhum funil selecionado.'); return; }

    const btn = document.getElementById('btnImportPreview');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Analisando...';

    const formData = new FormData();
    formData.append('file', file);
    formData.append('pipeline_id', _importPipelineId);
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

    try {
        const res  = await fetch(KANBAN_PREVIEW_URL, { method: 'POST', body: formData });
        const data = await res.json();
        if (!data.success) throw new Error(data.message || 'Erro no servidor');
        _importToken = data.token;
        renderPreviewScreen(data);
    } catch (e) {
        toastr.error('Erro ao analisar o arquivo. Verifique o formato e tente novamente.');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-eye"></i> Pré-visualizar';
    }
}

function renderPreviewScreen(data) {
    const willImport = data.total - data.skipped;
    let summary = `${data.total} linha${data.total !== 1 ? 's' : ''} encontrada${data.total !== 1 ? 's' : ''}`;
    if (data.skipped > 0) {
        summary += ` — <strong style="color:#DC2626;">${data.skipped} ser${data.skipped !== 1 ? 'ão' : 'á'} ignorada${data.skipped !== 1 ? 's' : ''} (sem nome)</strong>`;
    }
    summary += ` — <strong style="color:#10B981;">${willImport} ser${willImport !== 1 ? 'ão' : 'á'} importada${willImport !== 1 ? 's' : ''}</strong>`;
    document.getElementById('importPreviewSummary').innerHTML = summary;

    const tbody = document.getElementById('importPreviewTbody');
    tbody.innerHTML = '';
    data.rows.forEach((row, i) => {
        const tr = document.createElement('tr');
        if (row.will_skip) {
            tr.style.background = '#FEF2F2';
        } else if (i % 2 === 0) {
            tr.style.background = '#fafafa';
        }

        const nameCellHtml = row.will_skip
            ? `<span style="color:#DC2626;font-style:italic;">(sem nome — ignorado)</span>`
            : escapeHtml(row.name);

        const stageStyle = !row.stage_found && row.stage_raw
            ? 'background:#FEF3C7;color:#92400E;border-radius:4px;padding:1px 5px;font-size:11px;'
            : '';
        const stageWarn = !row.stage_found && row.stage_raw
            ? ' <i class="bi bi-exclamation-triangle-fill" style="font-size:10px;" title="Etapa não encontrada — será usada a etapa inicial"></i>'
            : '';
        const stageHtml = row.stage_raw
            ? `<span style="${stageStyle}">${escapeHtml(row.stage_raw)}${stageWarn}</span>`
            : '<span style="color:#9ca3af;">—</span>';

        tr.innerHTML = `
            <td style="padding:8px 12px;border-bottom:1px solid #f0f2f7;">${nameCellHtml}</td>
            <td style="padding:8px 12px;border-bottom:1px solid #f0f2f7;color:#6b7280;white-space:nowrap;">${escapeHtml(row.phone)}</td>
            <td style="padding:8px 12px;border-bottom:1px solid #f0f2f7;color:#6b7280;max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${escapeHtml(row.email)}</td>
            <td style="padding:8px 12px;border-bottom:1px solid #f0f2f7;font-weight:600;color:#10B981;white-space:nowrap;">${escapeHtml(row.value_fmt)}</td>
            <td style="padding:8px 12px;border-bottom:1px solid #f0f2f7;">${stageHtml}</td>
            <td style="padding:8px 12px;border-bottom:1px solid #f0f2f7;color:#6b7280;">${escapeHtml((row.tags || []).join(', '))}</td>
            <td style="padding:8px 12px;border-bottom:1px solid #f0f2f7;color:#6b7280;white-space:nowrap;">${escapeHtml(row.source)}</td>
            <td style="padding:8px 12px;border-bottom:1px solid #f0f2f7;color:#6b7280;white-space:nowrap;">${escapeHtml(row.created_at || '')}</td>
        `;
        tbody.appendChild(tr);
    });

    document.getElementById('importScreenUpload').style.display  = 'none';
    document.getElementById('importScreenPreview').style.display = 'flex';

    const confirmBtn = document.getElementById('btnImportConfirm');
    confirmBtn.disabled = false;
    confirmBtn.innerHTML = '<i class="bi bi-check-circle"></i> Confirmar importação';
}

async function confirmImport() {
    if (!_importToken)      { toastr.error('Token ausente. Volte e tente novamente.'); return; }
    if (!_importPipelineId) { toastr.error('Nenhum funil selecionado.'); return; }

    const btn = document.getElementById('btnImportConfirm');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Importando...';

    const formData = new FormData();
    formData.append('token', _importToken);
    formData.append('pipeline_id', _importPipelineId);
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

    try {
        const res  = await fetch(KANBAN_IMPORT_URL, { method: 'POST', body: formData });
        const data = await res.json();
        if (!data.success) throw new Error(data.message || 'Erro no servidor');

        toastr.success(
            `${data.imported} lead${data.imported !== 1 ? 's' : ''} importado${data.imported !== 1 ? 's' : ''} com sucesso!` +
            (data.skipped > 0 ? ` ${data.skipped} ignorado(s).` : ''),
            '', { timeOut: 4000 }
        );
        closeImportModal();
        setTimeout(() => window.location.reload(), 1500);
    } catch (e) {
        toastr.error(e.message || 'Erro ao importar. Tente novamente.');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-circle"></i> Confirmar importação';
    }
}

document.getElementById('modalImport')?.addEventListener('click', function(e) {
    if (e.target === this) closeImportModal();
});

// ── Filtro Responsável multi-select ──────────────────────────────────────────
function toggleRespDrop(e) {
    e.stopPropagation();
    document.getElementById('respDropdown').classList.toggle('open');
}

document.addEventListener('click', e => {
    const wrap = document.querySelector('.resp-filter-wrap');
    if (wrap && !wrap.contains(e.target)) {
        document.getElementById('respDropdown')?.classList.remove('open');
    }
});

// ── Drag-to-scroll horizontal ────────────────────────────────────
(function() {
    var board = document.getElementById('kanbanBoard');
    if (!board) return;
    var isDown = false, startX, scrollLeft;
    board.addEventListener('mousedown', function(e) {
        if (e.target.closest('.lead-card, button, a, input, select, textarea, .kanban-header, .add-lead-btn')) return;
        isDown = true;
        startX = e.pageX - board.offsetLeft;
        scrollLeft = board.scrollLeft;
        board.classList.add('is-grabbing');
    });
    board.addEventListener('mouseleave', stop);
    board.addEventListener('mouseup', stop);
    board.addEventListener('mousemove', function(e) {
        if (!isDown) return;
        e.preventDefault();
        var x = e.pageX - board.offsetLeft;
        board.scrollLeft = scrollLeft - (x - startX);
    });
    function stop() { isDown = false; board.classList.remove('is-grabbing'); }
})();
</script>
@endpush

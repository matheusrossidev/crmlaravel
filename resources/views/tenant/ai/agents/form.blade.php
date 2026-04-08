@extends('tenant.layouts.app')

@php
    $title    = __('ai_agents.form_title');
    $pageIcon = 'robot';
    $isEdit   = $agent->exists;
@endphp

@push('styles')
<style>
    .ai-form-wrap { width: 100%; }

    /* ═══════════════════════════════════════════════════════════════
       SIDEBAR-TABS LAYOUT (estilo /configuracoes/integracoes)
       ═══════════════════════════════════════════════════════════════ */
    .ae-layout {
        display: grid;
        grid-template-columns: 260px 1fr;
        gap: 20px;
        align-items: start;
    }

    .ae-sidebar {
        background: #fff;
        border: 1px solid #e8eaf0;
        border-radius: 14px;
        padding: 20px 14px;
        position: sticky;
        top: 16px;
        max-height: calc(100vh - 32px);
        overflow-y: auto;
    }

    @media (max-width: 900px) {
        .ae-layout { grid-template-columns: 1fr; gap: 12px; }
        .ae-sidebar {
            position: static;
            max-height: none;
            padding: 16px 14px 14px;
            overflow: visible;
        }
        /* Avatar centralizado + nome + status embaixo */
        .ae-agent-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding: 6px 8px 16px;
            margin-bottom: 14px;
            border-bottom: 1px solid #f0f2f7;
        }
        .ae-agent-avatar { width: 64px; height: 64px; margin: 0 auto 8px; }
        .ae-agent-name { text-align: center; }
        .ae-agent-status { margin-top: 6px; }

        /* Container das tabs com fade nas bordas (visual hint de scroll) */
        .ae-sidebar-tabs-wrap {
            position: relative;
            margin: 0 -14px;
        }
        .ae-sidebar-tabs-wrap::after {
            content: '';
            position: absolute;
            right: 0; top: 0; bottom: 4px;
            width: 36px;
            background: linear-gradient(90deg, transparent, #fff 70%);
            pointer-events: none;
            z-index: 2;
        }
        .ae-sidebar-tabs {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            scrollbar-width: thin;
            padding: 0 14px 8px;
            scroll-snap-type: x mandatory;
            -webkit-overflow-scrolling: touch;
        }
        .ae-sidebar-tabs::-webkit-scrollbar { height: 4px; }
        .ae-sidebar-tabs::-webkit-scrollbar-thumb { background: #e8eaf0; border-radius: 4px; }
        .ae-sect-item {
            flex-shrink: 0;
            margin-bottom: 0;
            padding: 9px 16px;
            white-space: nowrap;
            border: 1.5px solid #e8eaf0;
            background: #fff;
            scroll-snap-align: start;
            font-size: 12.5px;
        }
        .ae-sect-item.active {
            border-color: #0085f3;
            background: #eff6ff;
        }
        .ae-main { padding: 20px 18px; }
    }

    .ae-agent-card {
        text-align: center;
        padding: 6px 8px 16px;
        border-bottom: 1px solid #f0f2f7;
        margin-bottom: 12px;
    }
    .ae-agent-avatar {
        width: 76px; height: 76px;
        border-radius: 50%;
        margin: 0 auto 10px;
        object-fit: cover;
        border: 3px solid #eff6ff;
        display: block;
        background: #f4f6fb;
    }
    .ae-agent-name {
        font-size: 14px;
        font-weight: 700;
        color: #1a1d23;
        line-height: 1.3;
        word-break: break-word;
    }
    .ae-agent-status {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        margin-top: 6px;
        padding: 3px 10px;
        border-radius: 99px;
        font-size: 11px;
        font-weight: 600;
    }
    .ae-agent-status.on  { background: #d1fae5; color: #065f46; }
    .ae-agent-status.off { background: #f3f4f6; color: #6b7280; }

    .ae-sect-item {
        display: flex;
        align-items: center;
        gap: 10px;
        width: 100%;
        padding: 10px 12px;
        margin-bottom: 2px;
        background: none;
        border: none;
        border-radius: 9px;
        font-size: 13px;
        font-weight: 600;
        color: #6b7280;
        cursor: pointer;
        text-align: left;
        font-family: inherit;
        transition: all .15s;
    }
    .ae-sect-item:hover { background: #f3f4f6; color: #374151; }
    .ae-sect-item.active {
        background: #eff6ff;
        color: #0085f3;
    }
    .ae-sect-item i {
        font-size: 15px;
        width: 18px;
        text-align: center;
    }

    .ae-main {
        background: #fff;
        border: 1px solid #e8eaf0;
        border-radius: 14px;
        padding: 28px 32px;
        min-height: 400px;
    }

    .ae-pane { display: none; animation: ae-fade .25s ease; }
    .ae-pane.active { display: block; }
    @keyframes ae-fade {
        from { opacity: 0; transform: translateY(6px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .ae-pane-title {
        font-size: 16px;
        font-weight: 700;
        color: #1a1d23;
        margin: 0 0 4px;
        font-family: 'Plus Jakarta Sans', sans-serif;
    }
    .ae-pane-sub {
        font-size: 12.5px;
        color: #9ca3af;
        margin: 0 0 22px;
    }
    .ae-pane-divider {
        height: 1px; background: #f0f2f7; margin: 24px 0;
    }

    /* Avatar grid (display_avatar — admin only) */
    .ae-avatar-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(74px, 1fr));
        gap: 10px;
    }
    .ae-avatar-opt {
        cursor: pointer;
        text-align: center;
        padding: 6px 4px 8px;
        border-radius: 12px;
        border: 2px solid transparent;
        transition: all .15s;
        background: #fafbfd;
    }
    .ae-avatar-opt:hover { background: #eff6ff; }
    .ae-avatar-opt.selected {
        background: #eff6ff;
        border-color: #0085f3;
    }
    .ae-avatar-opt img {
        width: 48px; height: 48px; border-radius: 50%;
        object-fit: cover; display: block; margin: 0 auto 4px;
        border: 2px solid #fff;
    }
    .ae-avatar-opt .av-name { font-size: 10.5px; font-weight: 600; color: #6b7280; }
    .ae-avatar-opt.selected .av-name { color: #0085f3; }

    /* Sticky save bar */
    .ae-save-bar {
        position: sticky;
        bottom: 0;
        background: #fff;
        border-top: 1px solid #f0f2f7;
        padding: 16px 0 6px;
        margin-top: 28px;
        display: flex;
        gap: 10px;
        align-items: center;
    }

    /* Section-cards funcionam como "panes" — só o ativo aparece */
    .section-card {
        background: transparent;
        border: none;
        margin: 0;
        display: none;
        animation: ae-fade .25s ease;
    }
    .section-card.active-pane { display: block; }
    .section-card-header { display: none; }
    .section-card-body { padding: 0 !important; display: block !important; }
    .section-card-body.collapsed { display: block !important; }
    .chevron { display: none; }
    .section-card .form-row,
    .section-card .form-group { margin-bottom: 14px; }

    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .form-row.three { grid-template-columns: 1fr 1fr 1fr; }
    .form-group { margin-bottom: 14px; }
    .form-label {
        display: block; font-size: 12.5px; font-weight: 600;
        color: #374151; margin-bottom: 4px;
    }
    .form-control {
        width: 100%; padding: 9px 12px;
        border: 1.5px solid #e8eaf0; border-radius: 9px;
        font-size: 13.5px; outline: none;
        font-family: 'Inter', sans-serif;
        color: #1a1d23; background: #fafafa;
        transition: border-color .15s, box-shadow .15s;
        box-sizing: border-box;
    }
    .form-control:focus {
        border-color: #3B82F6;
        background: #fff;
        box-shadow: 0 0 0 3px rgba(59,130,246,.1);
    }
    textarea.form-control { resize: vertical; min-height: 90px; }
    select.form-control { background: #fff; }

    /* Etapas dinâmicas */
    .stages-list { display: flex; flex-direction: column; gap: 8px; }
    .stage-item {
        display: flex; gap: 8px; align-items: flex-start;
        padding: 10px; background: #f8fafc;
        border: 1px solid #e8eaf0; border-radius: 9px;
    }
    .stage-num {
        width: 24px; height: 24px; border-radius: 6px;
        background: #eff6ff; color: #0085f3;
        display: flex; align-items: center; justify-content: center;
        font-size: 11px; font-weight: 700; flex-shrink: 0; margin-top: 8px;
    }
    .stage-inputs { flex: 1; display: flex; flex-direction: column; gap: 6px; }
    .stage-del {
        width: 28px; height: 28px; border-radius: 7px;
        border: 1px solid #e8eaf0; background: #fff; color: #9ca3af;
        cursor: pointer; display: flex; align-items: center; justify-content: center;
        font-size: 13px; flex-shrink: 0; margin-top: 5px; transition: all .15s;
    }
    .stage-del:hover { background: #fee2e2; color: #ef4444; border-color: #fca5a5; }
    .btn-add-stage {
        padding: 8px 16px; border-radius: 8px;
        border: 1.5px dashed #d1d5db; background: transparent;
        font-size: 12.5px; font-weight: 600; color: #6b7280;
        cursor: pointer; transition: all .15s; margin-top: 8px;
    }
    .btn-add-stage:hover { border-color: #0085f3; color: #0085f3; background: #f0f8ff; }

    /* Toggle ativo */
    .toggle-wrap {
        display: flex; align-items: center; gap: 10px;
        padding: 12px 16px; background: #f8fafc;
        border: 1px solid #e8eaf0; border-radius: 10px;
        margin-bottom: 18px;
    }
    .toggle-switch {
        width: 44px; height: 24px; border-radius: 12px;
        background: #e5e7eb; position: relative; cursor: pointer;
        transition: background .2s; flex-shrink: 0;
    }
    .toggle-switch.on { background: #0085f3; }
    .toggle-switch::after {
        content: ''; position: absolute; top: 3px; left: 3px;
        width: 18px; height: 18px; border-radius: 50%; background: #fff;
        transition: left .2s; box-shadow: 0 1px 3px rgba(0,0,0,.2);
    }
    .toggle-switch.on::after { left: 23px; }

    .form-footer {
        display: flex; gap: 10px; align-items: center;
        padding: 20px 0;
    }
    .btn-primary {
        padding: 10px 28px; border-radius: 100px; border: none;
        background: #0085f3; color: #fff;
        font-size: 13.5px; font-weight: 600; cursor: pointer;
        transition: background .15s;
    }
    .btn-primary:hover { background: #0070d1; }
    .btn-cancel {
        padding: 10px 20px; border-radius: 100px;
        border: 1.5px solid #e8eaf0; background: #fff;
        font-size: 13.5px; font-weight: 600; color: #6b7280;
        cursor: pointer; transition: all .15s; text-decoration: none;
        display: inline-flex; align-items: center;
    }
    .btn-cancel:hover { background: #f0f2f7; }

    /* Widget de chat de teste */
    .test-chat-panel {
        position: fixed; bottom: 24px; right: 24px;
        width: 360px; border-radius: 16px;
        background: #fff; border: 1px solid #e8eaf0;
        box-shadow: 0 12px 48px rgba(0,0,0,.15);
        z-index: 500; display: flex; flex-direction: column;
        overflow: hidden;
        @if(!$isEdit) display: none; @endif
    }
    .test-chat-header {
        padding: 13px 16px; background: #0085f3;
        display: flex; align-items: center; justify-content: space-between;
        cursor: pointer;
    }
    .test-chat-title { color: #fff; font-size: 13.5px; font-weight: 700; }
    .test-chat-toggle { color: rgba(255,255,255,.8); font-size: 16px; }
    .test-chat-body { height: 320px; overflow-y: auto; padding: 14px; display: flex; flex-direction: column; gap: 8px; }
    .test-chat-body.collapsed { display: none; }
    .test-chat-input-wrap {
        padding: 10px 12px; border-top: 1px solid #f0f2f7;
        display: flex; gap: 7px;
    }
    .test-chat-input-wrap.collapsed { display: none; }
    .test-chat-input {
        flex: 1; padding: 8px 10px; border: 1.5px solid #e8eaf0;
        border-radius: 9px; font-size: 13px; outline: none; font-family: inherit;
    }
    .test-chat-input:focus { border-color: #0085f3; }
    .test-send-btn {
        width: 36px; height: 36px; border-radius: 100px;
        background: #0085f3; border: none; color: #fff;
        cursor: pointer; display: flex; align-items: center; justify-content: center;
        font-size: 15px; flex-shrink: 0; transition: background .15s;
    }
    .test-send-btn:hover { background: #0070d1; }
    .test-send-btn:disabled { opacity: .6; cursor: not-allowed; }

    .chat-bubble {
        max-width: 80%; padding: 8px 12px;
        border-radius: 12px; font-size: 13px; line-height: 1.45;
        white-space: pre-wrap; word-break: break-word;
    }
    .chat-bubble.user {
        align-self: flex-end; background: #0085f3; color: #fff;
        border-bottom-right-radius: 4px;
    }
    .chat-bubble.agent {
        align-self: flex-start; background: #f0f2f7; color: #1a1d23;
        border-bottom-left-radius: 4px;
    }
    .chat-bubble.typing { color: #9ca3af; font-style: italic; }

    @if(!$isEdit)
    .test-chat-panel { display: none; }
    @endif

    /* Knowledge file list */
    .kb-file-item {
        display: flex; align-items: flex-start; gap: 10px;
        padding: 10px 12px; border: 1px solid #e8eaf0;
        border-radius: 9px; margin-bottom: 7px; background: #fafafa;
    }
    .kb-file-icon { font-size: 22px; flex-shrink: 0; line-height: 1; padding-top: 2px; }
    .kb-file-info { flex: 1; min-width: 0; }
    .kb-file-name { font-size: 13px; font-weight: 600; color: #1a1d23; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .kb-status-badge {
        display: inline-block; font-size: 10.5px; font-weight: 700;
        padding: 1px 7px; border-radius: 20px; margin-top: 3px;
    }
    .kb-status-badge.done    { background: #dcfce7; color: #16a34a; }
    .kb-status-badge.failed  { background: #fee2e2; color: #dc2626; }
    .kb-status-badge.pending { background: #fef9c3; color: #ca8a04; }
    .kb-preview-btn {
        font-size: 11px; color: #0085f3; border: none; background: none;
        padding: 0; cursor: pointer; margin-left: 6px;
    }
    .kb-del-btn {
        flex-shrink: 0; width: 28px; height: 28px;
        border: 1px solid #e8eaf0; border-radius: 7px;
        background: #fff; color: #9ca3af; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        font-size: 13px; transition: all .15s;
    }
    .kb-del-btn:hover { background: #fee2e2; color: #ef4444; border-color: #fca5a5; }

    /* Media cards (grid) */
    .media-card {
        position: relative; border: 1.5px solid #e8eaf0; border-radius: 11px;
        overflow: hidden; background: #fff; transition: box-shadow .15s;
    }
    .media-card:hover { box-shadow: 0 2px 12px rgba(0,0,0,.08); }
    .media-card-thumb {
        width: 100%; height: 110px; background: #f4f6fb;
        display: flex; align-items: center; justify-content: center; overflow: hidden;
    }
    .media-card-body { padding: 9px 10px 8px; }
    .media-card-name {
        font-size: 11.5px; font-weight: 600; color: #1a1d23;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .media-card-desc {
        font-size: 11px; color: #6b7280; margin-top: 3px;
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
        overflow: hidden; line-height: 1.35;
    }
    .media-card-del {
        position: absolute; top: 6px; right: 6px; width: 26px; height: 26px;
        border: none; border-radius: 7px; background: rgba(255,255,255,.9);
        color: #9ca3af; cursor: pointer; display: flex; align-items: center;
        justify-content: center; font-size: 12px; transition: all .15s;
        opacity: 0; backdrop-filter: blur(4px);
    }
    .media-card:hover .media-card-del { opacity: 1; }
    .media-card-del:hover { background: #fee2e2; color: #ef4444; }
    .kb-file-preview {
        font-size: 11.5px; color: #6b7280; background: #f8fafc;
        border: 1px solid #e8eaf0; border-radius: 7px;
        padding: 8px 10px; margin-bottom: 7px; white-space: pre-wrap;
        line-height: 1.5;
    }
    .kb-uploading {
        display: flex; align-items: center; gap: 8px;
        padding: 10px 12px; border: 1px dashed #93c5fd;
        border-radius: 9px; margin-bottom: 7px; background: #eff6ff;
        font-size: 12.5px; color: #0085f3;
    }

    .channel-card {
        display: flex; flex-direction: column; align-items: center; gap: 5px;
        padding: 12px 8px; border: 2px solid #e8eaf0; border-radius: 10px;
        background: #fafafa; color: #6b7280; font-size: 12px; font-weight: 600;
        transition: all .15s; text-align: center;
    }
    .channel-card:hover { border-color: #93c5fd; background: #f0f8ff; color: #2563eb; }
    .channel-card.selected { border-color: #0085f3; background: #eff6ff; color: #0085f3; }
    @media (max-width: 768px) {
        .form-control { font-size: 16px !important; }
    }
</style>
@endpush

@section('content')
<div class="page-container">

    <div style="margin-bottom:22px;display:flex;align-items:center;gap:10px;">
        <a href="{{ route('ai.agents.index') }}" style="color:#9ca3af;font-size:14px;text-decoration:none;">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <div style="font-size:15px;font-weight:700;color:#1a1d23;">
                {{ $isEdit ? __('ai_agents.form_heading_edit') : __('ai_agents.form_heading_create') }}
            </div>
        </div>
    </div>

    @php
        $avatarsList = \App\Support\AgentAvatars::all();
        $currentDisplayAvatar = old('display_avatar', $agent->display_avatar ?: \App\Support\AgentAvatars::default());
    @endphp

    <div class="ae-layout">

        {{-- ═══════════════════════════════════════════════════════════════
             SIDEBAR (esquerda) — preview do agente + tabs de seções
             ═══════════════════════════════════════════════════════════════ --}}
        <aside class="ae-sidebar">
            @if($isEdit)
            <div class="ae-agent-card">
                <img class="ae-agent-avatar" id="aeAvatarPreview"
                     src="{{ asset($currentDisplayAvatar) }}"
                     alt="{{ $agent->name }}">
                <div class="ae-agent-name">{{ $agent->name }}</div>
                <div class="ae-agent-status {{ $agent->is_active ? 'on' : 'off' }}">
                    <i class="bi bi-circle-fill" style="font-size:6px;"></i>
                    {{ $agent->is_active ? __('ai_agents.badge_active') : __('ai_agents.badge_inactive') }}
                </div>
            </div>
            @endif

            <div class="ae-sidebar-tabs-wrap">
            <div class="ae-sidebar-tabs">
            <button type="button" class="ae-sect-item active" data-pane="identity" onclick="switchPane('identity', this)">
                <i class="bi bi-person-badge"></i> {{ __('ai_agents.edit_sect_identity') }}
            </button>
            <button type="button" class="ae-sect-item" data-pane="channel" onclick="switchPane('channel', this)">
                <i class="bi bi-broadcast"></i> {{ __('ai_agents.edit_sect_channel') }}
            </button>
            <button type="button" class="ae-sect-item" data-pane="persona" onclick="switchPane('persona', this)">
                <i class="bi bi-chat-quote"></i> {{ __('ai_agents.edit_sect_persona') }}
            </button>
            <button type="button" class="ae-sect-item" data-pane="flow" onclick="switchPane('flow', this)">
                <i class="bi bi-signpost-split"></i> {{ __('ai_agents.s3_title') }}
            </button>
            <button type="button" class="ae-sect-item" data-pane="stages" onclick="switchPane('stages', this)">
                <i class="bi bi-list-ol"></i> {{ __('ai_agents.edit_sect_stages') }}
            </button>
            <button type="button" class="ae-sect-item" data-pane="kb" onclick="switchPane('kb', this)">
                <i class="bi bi-database"></i> {{ __('ai_agents.edit_sect_kb') }}
            </button>
            @if($isEdit)
            <button type="button" class="ae-sect-item" data-pane="media" onclick="switchPane('media', this)">
                <i class="bi bi-images"></i> {{ __('ai_agents.edit_sect_media') }}
            </button>
            @endif
            <button type="button" class="ae-sect-item" data-pane="tools" onclick="switchPane('tools', this)">
                <i class="bi bi-tools"></i> {{ __('ai_agents.edit_sect_tools') }}
            </button>
            <button type="button" class="ae-sect-item" data-pane="followup" onclick="switchPane('followup', this)">
                <i class="bi bi-arrow-repeat"></i> {{ __('ai_agents.edit_sect_followup') }}
            </button>
            <button type="button" class="ae-sect-item" data-pane="advanced" onclick="switchPane('advanced', this)">
                <i class="bi bi-sliders"></i> {{ __('ai_agents.edit_sect_advanced') }}
            </button>
            <button type="button" class="ae-sect-item" id="aeWidgetTab" data-pane="widget" onclick="switchPane('widget', this)"
                    style="{{ ($agent->channel ?? 'whatsapp') === 'web_chat' ? '' : 'display:none;' }}">
                <i class="bi bi-window-stack"></i> {{ __('ai_agents.edit_sect_widget') }}
            </button>
            </div>{{-- /.ae-sidebar-tabs --}}
            </div>{{-- /.ae-sidebar-tabs-wrap --}}
        </aside>

        {{-- ═══════════════════════════════════════════════════════════════
             MAIN (direita) — content panes
             ═══════════════════════════════════════════════════════════════ --}}
        <main class="ae-main">

    <form method="POST"
          action="{{ $isEdit ? route('ai.agents.update', $agent) : route('ai.agents.store') }}"
          id="agentForm"
          class="ai-form-wrap">
        @csrf
        @if($isEdit) @method('PUT') @endif

        {{-- Hidden display_avatar (controlado pela seleção visual em Identidade) --}}
        <input type="hidden" name="display_avatar" id="displayAvatarInput" value="{{ $currentDisplayAvatar }}">

        {{-- ═══════════════ PANE: Channel + Toggles (movido pro pane "channel" via section-card) ═══════════════ --}}
        <div class="section-card" id="card-channel" data-pane-name="channel">
        <div class="section-card-body">
        {{-- Seletor de Canal --}}
        <div style="margin-bottom:16px;">
            <div style="font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px;">{{ __('ai_agents.channel_label') }}</div>
            <div style="display:flex;gap:10px;">
                @php $currentChannel = old('channel', $agent->channel ?? 'whatsapp'); @endphp
                @foreach([['whatsapp',__('ai_agents.channel_whatsapp'),'whatsapp'],['instagram',__('ai_agents.channel_instagram'),'instagram'],['web_chat',__('ai_agents.channel_web_chat'),'globe']] as [$val,$label,$icon])
                <label style="flex:1;cursor:pointer;">
                    <input type="radio" name="channel" value="{{ $val }}" {{ $currentChannel === $val ? 'checked' : '' }}
                           style="display:none;" onchange="updateChannelCards()">
                    <div class="channel-card {{ $currentChannel === $val ? 'selected' : '' }}" data-channel="{{ $val }}">
                        <i class="bi bi-{{ $icon }}" style="font-size:18px;"></i>
                        <span>{{ $label }}</span>
                    </div>
                </label>
                @endforeach
            </div>
        </div>

        {{-- Toggle ativo --}}
        <div class="toggle-wrap" onclick="toggleActive()">
            <div class="toggle-switch {{ $agent->is_active ? 'on' : '' }}" id="toggleSwitch"></div>
            <div>
                <div style="font-size:13px;font-weight:700;color:#1a1d23;" id="toggleLabel">
                    {{ $agent->is_active ? __('ai_agents.toggle_active_on') : __('ai_agents.toggle_active_off') }}
                </div>
                <div style="font-size:11.5px;color:#9ca3af;">{{ __('ai_agents.toggle_active_desc') }}</div>
            </div>
        </div>
        <input type="hidden" name="is_active" id="isActiveInput" value="{{ $agent->is_active ? '1' : '0' }}">

        {{-- Toggle auto-assign --}}
        <div class="toggle-wrap" onclick="toggleAutoAssign()" style="margin-bottom:10px;">
            <div class="toggle-switch {{ ($agent->auto_assign ?? false) ? 'on' : '' }}" id="autoAssignSwitch"></div>
            <div>
                <div style="font-size:13px;font-weight:700;color:#1a1d23;" id="autoAssignLabel">
                    {{ ($agent->auto_assign ?? false) ? __('ai_agents.toggle_auto_assign_on') : __('ai_agents.toggle_auto_assign_off') }}
                </div>
                <div style="font-size:11.5px;color:#9ca3af;">{{ __('ai_agents.toggle_auto_assign_desc') }}</div>
            </div>
        </div>
        <input type="hidden" name="auto_assign" id="autoAssignInput" value="{{ ($agent->auto_assign ?? false) ? '1' : '0' }}">

        {{-- Instâncias WhatsApp (visível apenas quando channel=whatsapp) --}}
        @if(isset($whatsappInstances) && $whatsappInstances->count() > 1)
        @php $selectedInstances = $agent->whatsappInstances?->pluck('id')->toArray() ?? []; @endphp
        <div id="whatsappInstancesSection" style="{{ $currentChannel === 'whatsapp' ? '' : 'display:none;' }}margin-top:6px;padding:12px 14px;background:#f9fafb;border-radius:10px;border:1px solid #e8eaf0;">
            <div style="font-size:12px;font-weight:700;color:#374151;margin-bottom:8px;">
                <i class="bi bi-telephone" style="margin-right:4px;"></i> {{ __('ai_agents.wa_instances_title') }}
            </div>
            <div style="font-size:11px;color:#6b7280;margin-bottom:8px;">{{ __('ai_agents.wa_instances_hint') }}</div>
            @foreach($whatsappInstances as $inst)
            <label style="display:flex;align-items:center;gap:8px;padding:6px 0;cursor:pointer;">
                <input type="checkbox" name="whatsapp_instance_ids[]" value="{{ $inst->id }}"
                       {{ in_array($inst->id, $selectedInstances) ? 'checked' : '' }}
                       style="accent-color:#0085f3;width:16px;height:16px;">
                <span style="font-size:13px;color:#1a1d23;font-weight:500;">{{ $inst->label ?: $inst->session_name }}</span>
                @if($inst->phone_number)
                <span style="font-size:11px;color:#6b7280;">({{ $inst->phone_number }})</span>
                @endif
            </label>
            @endforeach
        </div>
        @endif

        </div>{{-- /section-card-body channel --}}
        </div>{{-- /section-card channel --}}

        {{-- 1. Identidade --}}
        <div class="section-card active-pane" data-pane-name="identity">
            <div class="section-card-header" onclick="toggleSection('identity')">
                <div class="section-icon"><i class="bi bi-person-badge"></i></div>
                <div class="section-card-title">{{ __('ai_agents.s1_title') }}</div>
                <i class="bi bi-chevron-down chevron open" id="chevron-identity"></i>
            </div>
            <div class="section-card-body" id="body-identity">
                {{-- Avatar selector (display_avatar — admin only, NÃO vai pro lead) --}}
                <div class="form-group" style="margin-bottom:18px;">
                    <label class="form-label">{{ __('ai_agents.edit_avatar_label') }}</label>
                    <div class="ae-avatar-grid">
                        @foreach($avatarsList as $av)
                            <div class="ae-avatar-opt {{ $currentDisplayAvatar === $av['file'] ? 'selected' : '' }}"
                                 data-file="{{ $av['file'] }}"
                                 onclick="selectDisplayAvatar('{{ $av['file'] }}', this)">
                                <img src="{{ asset($av['file']) }}" alt="{{ $av['name'] }}">
                                <div class="av-name">{{ $av['name'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">{{ __('ai_agents.s1_name') }}</label>
                        <input type="text" name="name" class="form-control" required
                               value="{{ old('name', $agent->name) }}" placeholder="{{ __('ai_agents.s1_name_placeholder') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('ai_agents.s1_company') }}</label>
                        <input type="text" name="company_name" class="form-control"
                               value="{{ old('company_name', $agent->company_name) }}" placeholder="{{ __('ai_agents.s1_company_placeholder') }}">
                    </div>
                </div>
                <div class="form-row three">
                    <div class="form-group">
                        <label class="form-label">{{ __('ai_agents.s1_objective') }}</label>
                        <select name="objective" class="form-control">
                            @foreach(['sales' => __('ai_agents.s1_objective_sales'), 'support' => __('ai_agents.s1_objective_support'), 'general' => __('ai_agents.s1_objective_general')] as $v => $l)
                            <option value="{{ $v }}" {{ old('objective', $agent->objective) === $v ? 'selected' : '' }}>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('ai_agents.s1_communication') }}</label>
                        <select name="communication_style" class="form-control">
                            @foreach(['formal' => __('ai_agents.s1_style_formal'), 'normal' => __('ai_agents.s1_style_normal'), 'casual' => __('ai_agents.s1_style_casual')] as $v => $l)
                            <option value="{{ $v }}" {{ old('communication_style', $agent->communication_style) === $v ? 'selected' : '' }}>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('ai_agents.s1_language') }}</label>
                        <select name="language" class="form-control">
                            @foreach(['pt-BR' => __('ai_agents.s1_lang_pt'), 'en-US' => __('ai_agents.s1_lang_en'), 'es-ES' => __('ai_agents.s1_lang_es')] as $v => $l)
                            <option value="{{ $v }}" {{ old('language', $agent->language ?? 'pt-BR') === $v ? 'selected' : '' }}>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('ai_agents.s1_industry') }}</label>
                    <input type="text" name="industry" class="form-control"
                           value="{{ old('industry', $agent->industry) }}" placeholder="{{ __('ai_agents.s1_industry_placeholder') }}">
                </div>
            </div>
        </div>

        {{-- 2. Persona e Comportamento --}}
        <div class="section-card">
            <div class="section-card-header" onclick="toggleSection('persona')">
                <div class="section-icon"><i class="bi bi-chat-quote"></i></div>
                <div class="section-card-title">{{ __('ai_agents.s2_title') }}</div>
                <i class="bi bi-chevron-down chevron open" id="chevron-persona"></i>
            </div>
            <div class="section-card-body" id="body-persona">
                <div class="form-group">
                    <label class="form-label">{{ __('ai_agents.s2_persona') }}</label>
                    <textarea name="persona_description" class="form-control" rows="4"
                              placeholder="{{ __('ai_agents.s2_persona_placeholder') }}">{{ old('persona_description', $agent->persona_description) }}</textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('ai_agents.s2_behavior') }}</label>
                    <textarea name="behavior" class="form-control" rows="4"
                              placeholder="{{ __('ai_agents.s2_behavior_placeholder') }}">{{ old('behavior', $agent->behavior) }}</textarea>
                </div>
            </div>
        </div>

        {{-- 3. Fluxo --}}
        <div class="section-card">
            <div class="section-card-header" onclick="toggleSection('flow')">
                <div class="section-icon"><i class="bi bi-signpost-split"></i></div>
                <div class="section-card-title">{{ __('ai_agents.s3_title') }}</div>
                <i class="bi bi-chevron-down chevron open" id="chevron-flow"></i>
            </div>
            <div class="section-card-body" id="body-flow">
                <div class="form-group">
                    <label class="form-label">{{ __('ai_agents.s3_on_finish') }}</label>
                    <textarea name="on_finish_action" class="form-control" rows="3"
                              placeholder="{{ __('ai_agents.s3_on_finish_placeholder') }}">{{ old('on_finish_action', $agent->on_finish_action) }}</textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('ai_agents.s3_on_transfer') }}</label>
                    <textarea name="on_transfer_message" class="form-control" rows="3"
                              placeholder="{{ __('ai_agents.s3_on_transfer_placeholder') }}">{{ old('on_transfer_message', $agent->on_transfer_message) }}</textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('ai_agents.s3_on_invalid') }}</label>
                    <textarea name="on_invalid_response" class="form-control" rows="3"
                              placeholder="{{ __('ai_agents.s3_on_invalid_placeholder') }}">{{ old('on_invalid_response', $agent->on_invalid_response) }}</textarea>
                </div>
            </div>
        </div>

        {{-- 4. Etapas da Conversa --}}
        <div class="section-card">
            <div class="section-card-header" onclick="toggleSection('stages')">
                <div class="section-icon"><i class="bi bi-list-ol"></i></div>
                <div class="section-card-title">{{ __('ai_agents.s4_title') }}</div>
                <i class="bi bi-chevron-down chevron" id="chevron-stages"></i>
            </div>
            <div class="section-card-body collapsed" id="body-stages">
                <div style="font-size:12.5px;color:#9ca3af;margin-bottom:12px;">
                    {{ __('ai_agents.s4_description') }}
                </div>
                <div class="stages-list" id="stagesList">
                    @foreach(old('conversation_stages', $agent->conversation_stages ?? []) as $i => $stage)
                    <div class="stage-item" data-index="{{ $i }}">
                        <div class="stage-num">{{ $i + 1 }}</div>
                        <div class="stage-inputs">
                            <input type="text" name="conversation_stages[{{ $i }}][name]"
                                   class="form-control" style="min-height:unset;"
                                   value="{{ $stage['name'] ?? '' }}"
                                   placeholder="{{ __('ai_agents.s4_stage_name_placeholder') }}">
                            <input type="text" name="conversation_stages[{{ $i }}][description]"
                                   class="form-control" style="min-height:unset;"
                                   value="{{ $stage['description'] ?? '' }}"
                                   placeholder="{{ __('ai_agents.s4_stage_desc_placeholder') }}">
                        </div>
                        <button type="button" class="stage-del" onclick="removeStage(this)">×</button>
                    </div>
                    @endforeach
                </div>
                <button type="button" class="btn-add-stage" onclick="addStage()">
                    <i class="bi bi-plus"></i> {{ __('ai_agents.s4_add_stage') }}
                </button>
            </div>
        </div>

        {{-- 5. Base de Conhecimento --}}
        <div class="section-card">
            <div class="section-card-header" onclick="toggleSection('kb')">
                <div class="section-icon"><i class="bi bi-database"></i></div>
                <div class="section-card-title">{{ __('ai_agents.s5_title') }}</div>
                <i class="bi bi-chevron-down chevron" id="chevron-kb"></i>
            </div>
            <div class="section-card-body collapsed" id="body-kb">
                <div style="font-size:12.5px;color:#9ca3af;margin-bottom:10px;">
                    {{ __('ai_agents.s5_description') }}
                </div>
                <textarea name="knowledge_base" class="form-control" rows="8"
                          placeholder="{{ __('ai_agents.s5_kb_placeholder') }}">{{ old('knowledge_base', $agent->knowledge_base) }}</textarea>

                @if($isEdit)
                {{-- Upload de arquivos --}}
                <div style="margin-top:20px;">
                    <div style="font-size:12px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:10px;">
                        <i class="bi bi-paperclip" style="margin-right:4px;"></i>{{ __('ai_agents.s5_files_title') }}
                    </div>
                    <div style="font-size:12px;color:#9ca3af;margin-bottom:10px;">
                        {{ __('ai_agents.s5_files_description') }}
                    </div>

                    {{-- Dropzone --}}
                    <div id="kbDropzone" style="border:2px dashed #d1d5db;border-radius:10px;padding:20px 16px;text-align:center;cursor:pointer;transition:all .2s;margin-bottom:14px;"
                         onclick="document.getElementById('kbFileInput').click()"
                         ondragover="event.preventDefault();this.style.borderColor='#0085f3';this.style.background='#eff6ff';"
                         ondragleave="this.style.borderColor='#d1d5db';this.style.background='';"
                         ondrop="handleKbDrop(event)">
                        <i class="bi bi-cloud-arrow-up" style="font-size:26px;color:#9ca3af;display:block;margin-bottom:6px;"></i>
                        <div style="font-size:13px;color:#6b7280;font-weight:600;">{{ __('ai_agents.s5_dropzone_text') }}</div>
                        <div style="font-size:11.5px;color:#9ca3af;margin-top:3px;">{{ __('ai_agents.s5_dropzone_hint') }}</div>
                    </div>
                    <input type="file" id="kbFileInput" style="display:none;"
                           accept=".pdf,.txt,.csv,.png,.jpg,.jpeg,.webp,.gif"
                           onchange="uploadKbFile(this.files[0])">

                    {{-- Lista de arquivos --}}
                    <div id="kbFileList">
                        @foreach($knowledgeFiles as $kbFile)
                        <div class="kb-file-item" id="kb-file-{{ $kbFile->id }}">
                            <div class="kb-file-icon">
                                @if(str_starts_with($kbFile->mime_type, 'image/'))
                                    <i class="bi bi-file-earmark-image" style="color:#8b5cf6;"></i>
                                @elseif($kbFile->mime_type === 'application/pdf')
                                    <i class="bi bi-file-earmark-pdf" style="color:#ef4444;"></i>
                                @else
                                    <i class="bi bi-file-earmark-text" style="color:#0085f3;"></i>
                                @endif
                            </div>
                            <div class="kb-file-info">
                                <div class="kb-file-name">{{ $kbFile->original_name }}</div>
                                @if($kbFile->status === 'done')
                                    <span class="kb-status-badge done">{{ __('ai_agents.s5_status_extracted') }}</span>
                                    @if($kbFile->extracted_text)
                                    <button type="button" class="kb-preview-btn" onclick="toggleKbPreview({{ $kbFile->id }})">
                                        <i class="bi bi-eye"></i> {{ __('ai_agents.s5_preview_btn') }}
                                    </button>
                                    @endif
                                @elseif($kbFile->status === 'failed')
                                    <span class="kb-status-badge failed">{{ __('ai_agents.s5_status_failed') }}</span>
                                    @if($kbFile->error_message)
                                    <span style="font-size:11px;color:#ef4444;display:block;margin-top:2px;">{{ $kbFile->error_message }}</span>
                                    @endif
                                @else
                                    <span class="kb-status-badge pending">{{ __('ai_agents.s5_status_pending') }}</span>
                                @endif
                            </div>
                            <button type="button" class="kb-del-btn" onclick="deleteKbFile({{ $kbFile->id }}, '{{ e($kbFile->original_name) }}')" title="{{ __('ai_agents.s5_remove_btn') }}">
                                <i class="bi bi-trash3"></i>
                            </button>
                        </div>
                        @if($kbFile->extracted_text)
                        <div class="kb-file-preview" id="kb-preview-{{ $kbFile->id }}" style="display:none;">
                            {{ mb_substr($kbFile->extracted_text, 0, 600) }}{{ mb_strlen($kbFile->extracted_text) > 600 ? '…' : '' }}
                        </div>
                        @endif
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- 5b. Mídias do Agente (para envio) --}}
        @if($isEdit)
        <div class="section-card">
            <div class="section-card-header" onclick="toggleSection('media')">
                <div class="section-icon"><i class="bi bi-images"></i></div>
                <div class="section-card-title">{{ __('ai_agents.s5b_title') }}</div>
                <i class="bi bi-chevron-down chevron" id="chevron-media"></i>
            </div>
            <div class="section-card-body collapsed" id="body-media">
                <p style="font-size:12.5px;color:#6b7280;margin-bottom:14px;">
                    {!! __('ai_agents.s5b_description') !!}
                </p>

                {{-- Upload preview area --}}
                <div id="mediaPendingPreview" style="display:none;margin-bottom:14px;">
                    <div style="border:1.5px solid #e8eaf0;border-radius:12px;overflow:hidden;background:#fafbfc;">
                        <div id="mediaPendingThumb" style="display:flex;align-items:center;justify-content:center;background:#f4f6fb;min-height:140px;max-height:220px;overflow:hidden;">
                            {{-- filled by JS --}}
                        </div>
                        <div style="padding:12px 14px;">
                            <div id="mediaPendingName" style="font-size:12px;font-weight:600;color:#374151;margin-bottom:8px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"></div>
                            <input type="text" id="mediaDescEdit" style="width:100%;border:1.5px solid #e8eaf0;border-radius:8px;padding:9px 12px;font-size:13px;outline:none;transition:border-color .15s;"
                                   placeholder="{{ __('ai_agents.s5b_desc_placeholder') }}" maxlength="500"
                                   onfocus="this.style.borderColor='#0085f3'" onblur="this.style.borderColor='#e8eaf0'">
                            <div style="display:flex;gap:8px;margin-top:10px;">
                                <button type="button" onclick="cancelMediaPending()" style="flex:1;padding:9px;background:#f3f4f6;color:#374151;border:1.5px solid #e8eaf0;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;">{{ __('ai_agents.s5b_cancel') }}</button>
                                <button type="button" id="mediaUploadBtnEdit" onclick="uploadMediaEdit()" style="flex:1;padding:9px;background:#0085f3;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;">{{ __('ai_agents.s5b_upload') }}</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="mediaDropzone" style="border:2px dashed #d1d5db;border-radius:10px;padding:20px 16px;text-align:center;cursor:pointer;transition:all .2s;margin-bottom:14px;"
                     onclick="document.getElementById('mediaFileInput').click()"
                     ondragover="event.preventDefault();this.style.borderColor='#0085f3';this.style.background='#eff6ff';"
                     ondragleave="this.style.borderColor='#d1d5db';this.style.background='';"
                     ondrop="handleMediaDropEdit(event)">
                    <i class="bi bi-cloud-arrow-up" style="font-size:26px;color:#9ca3af;"></i>
                    <div style="font-size:13px;color:#6b7280;margin-top:4px;">{{ __('ai_agents.s5b_dropzone_text') }}</div>
                    <div style="font-size:11px;color:#9ca3af;margin-top:2px;">{{ __('ai_agents.s5b_dropzone_hint') }}</div>
                </div>
                <input type="file" id="mediaFileInput" style="display:none"
                       accept=".png,.jpg,.jpeg,.webp,.gif,.pdf,.doc,.docx"
                       onchange="prepareMediaEdit(this.files[0])">

                <div id="mediaFilesList" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px;">
                    @foreach($agent->mediaFiles ?? [] as $media)
                    <div class="media-card" id="media-file-{{ $media->id }}">
                        <div class="media-card-thumb">
                            @if(str_starts_with($media->mime_type, 'image/'))
                                <img src="{{ asset('storage/' . $media->storage_path) }}" alt="{{ $media->original_name }}" style="width:100%;height:100%;object-fit:cover;">
                            @elseif(str_contains($media->mime_type, 'pdf'))
                                <i class="bi bi-file-earmark-pdf" style="font-size:32px;color:#dc2626;"></i>
                            @else
                                <i class="bi bi-file-earmark-text" style="font-size:32px;color:#2563eb;"></i>
                            @endif
                        </div>
                        <div class="media-card-body">
                            <div class="media-card-name" title="{{ $media->original_name }}">{{ $media->original_name }}</div>
                            <div class="media-card-desc">{{ $media->description ?: __('ai_agents.s5b_no_description') }}</div>
                        </div>
                        <button type="button" class="media-card-del" onclick="deleteMediaEdit({{ $media->id }}, '{{ e($media->original_name) }}')" title="{{ __('ai_agents.s5_remove_btn') }}">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        {{-- 6. Ferramentas do Agente --}}
        <div class="section-card">
            <div class="section-card-header" onclick="toggleSection('tools')">
                <div class="section-icon"><i class="bi bi-tools"></i></div>
                <div class="section-card-title">{{ __('ai_agents.s6_title') }}</div>
                <i class="bi bi-chevron-down chevron" id="chevron-tools"></i>
            </div>
            <div class="section-card-body collapsed" id="body-tools">
                {{-- Toggle enable_pipeline_tool --}}
                <div class="toggle-wrap" onclick="togglePipelineTool()" style="margin-bottom:16px;">
                    <div class="toggle-switch {{ ($agent->enable_pipeline_tool ?? false) ? 'on' : '' }}" id="pipelineToolSwitch"></div>
                    <div style="margin-left:10px;">
                        <div style="font-size:13px;font-weight:700;color:#1a1d23;" id="pipelineToolLabel">
                            {{ ($agent->enable_pipeline_tool ?? false) ? __('ai_agents.s6_pipeline_on') : __('ai_agents.s6_pipeline_off') }}
                        </div>
                        <div style="font-size:11px;color:#9ca3af;">{{ __('ai_agents.s6_pipeline_desc') }}</div>
                    </div>
                </div>
                <input type="hidden" name="enable_pipeline_tool" id="pipelineToolInput" value="{{ ($agent->enable_pipeline_tool ?? false) ? '1' : '0' }}">

                {{-- Toggle enable_tags_tool --}}
                <div class="toggle-wrap" onclick="toggleTagsTool()">
                    <div class="toggle-switch {{ ($agent->enable_tags_tool ?? false) ? 'on' : '' }}" id="tagsToolSwitch"></div>
                    <div style="margin-left:10px;">
                        <div style="font-size:13px;font-weight:700;color:#1a1d23;" id="tagsToolLabel">
                            {{ ($agent->enable_tags_tool ?? false) ? __('ai_agents.s6_tags_on') : __('ai_agents.s6_tags_off') }}
                        </div>
                        <div style="font-size:11px;color:#9ca3af;">{{ __('ai_agents.s6_tags_desc') }}</div>
                    </div>
                </div>
                <input type="hidden" name="enable_tags_tool" id="tagsToolInput" value="{{ ($agent->enable_tags_tool ?? false) ? '1' : '0' }}">

                {{-- Toggle enable_intent_notify --}}
                <div class="toggle-wrap" style="margin-top:12px;" onclick="toggleIntentNotify()">
                    <div class="toggle-switch {{ ($agent->enable_intent_notify ?? false) ? 'on' : '' }}" id="intentNotifySwitch"></div>
                    <div style="margin-left:10px;">
                        <div style="font-size:13px;font-weight:700;color:#1a1d23;" id="intentNotifyLabel">
                            {{ ($agent->enable_intent_notify ?? false) ? __('ai_agents.s6_intent_on') : __('ai_agents.s6_intent_off') }}
                        </div>
                        <div style="font-size:11px;color:#9ca3af;">{{ __('ai_agents.s6_intent_desc') }}</div>
                    </div>
                </div>
                <input type="hidden" name="enable_intent_notify" id="intentNotifyInput" value="{{ ($agent->enable_intent_notify ?? false) ? '1' : '0' }}">

                {{-- Toggle enable_calendar_tool --}}
                <div class="toggle-wrap" style="margin-top:12px;" onclick="toggleCalendarTool()">
                    <div class="toggle-switch {{ ($agent->enable_calendar_tool ?? false) ? 'on' : '' }}" id="calendarToolSwitch"></div>
                    <div style="margin-left:10px;">
                        <div style="font-size:13px;font-weight:700;color:#1a1d23;" id="calendarToolLabel">
                            {{ ($agent->enable_calendar_tool ?? false) ? __('ai_agents.s6_calendar_on') : __('ai_agents.s6_calendar_off') }}
                        </div>
                        <div style="font-size:11px;color:#9ca3af;">{{ __('ai_agents.s6_calendar_desc') }}</div>
                    </div>
                </div>
                <input type="hidden" name="enable_calendar_tool" id="calendarToolInput" value="{{ ($agent->enable_calendar_tool ?? false) ? '1' : '0' }}">

                {{-- Toggle enable_products_tool --}}
                <div class="toggle-wrap" style="margin-top:12px;" onclick="toggleProductsTool()">
                    <div class="toggle-switch {{ ($agent->enable_products_tool ?? false) ? 'on' : '' }}" id="productsToolSwitch"></div>
                    <div style="margin-left:10px;">
                        <div style="font-size:13px;font-weight:700;color:#1a1d23;" id="productsToolLabel">
                            {{ ($agent->enable_products_tool ?? false) ? __('ai_agents.s6_products_on') : __('ai_agents.s6_products_off') }}
                        </div>
                        <div style="font-size:11px;color:#9ca3af;">{{ __('ai_agents.s6_products_desc') }}</div>
                    </div>
                </div>
                <input type="hidden" name="enable_products_tool" id="productsToolInput" value="{{ ($agent->enable_products_tool ?? false) ? '1' : '0' }}">

                {{-- Instruções de agenda (visível só quando habilitado) --}}
                <div id="calendarToolOptions" style="{{ ($agent->enable_calendar_tool ?? false) ? '' : 'display:none' }}">
                    {{-- Seleção de agenda --}}
                    <div style="margin-top:12px;">
                        <label class="form-label fw-semibold" style="font-size:13px;">{{ __('ai_agents.s6_calendar_select_label') }}</label>
                        <select name="calendar_id" id="calendarIdSelect" class="form-select form-select-sm" style="max-width:320px;">
                            <option value="">{{ __('ai_agents.s6_calendar_primary') }}</option>
                        </select>
                        <div class="form-text" style="font-size:11px;color:#9ca3af;margin-top:4px;">
                            {{ __('ai_agents.s6_calendar_hint') }}
                            <a href="#" onclick="loadAgentCalendars(); return false;" style="color:#0085f3;">{{ __('ai_agents.s6_calendar_reload') }}</a>
                        </div>
                    </div>

                    <div style="margin-top:12px;">
                        <label class="form-label fw-semibold" style="font-size:13px;">{{ __('ai_agents.s6_calendar_instructions') }}</label>
                        <textarea name="calendar_tool_instructions"
                                  class="form-control"
                                  rows="4"
                                  maxlength="2000"
                                  placeholder="{{ __('ai_agents.s6_calendar_instructions_ph') }}"
                                  style="font-size:13px;resize:vertical;">{{ old('calendar_tool_instructions', $agent->calendar_tool_instructions ?? '') }}</textarea>
                        <div class="form-text" style="font-size:11px;color:#9ca3af;margin-top:4px;">
                            {{ __('ai_agents.s6_calendar_integrations') }}
                            <a href="{{ route('settings.integrations.index') }}" target="_blank" style="color:#0085f3;">{{ __('ai_agents.s6_calendar_integrations_link') }}</a>.
                        </div>
                    </div>

                    {{-- Reminder Settings --}}
                    <div style="margin-top:16px;padding-top:14px;border-top:1px solid #f0f2f7;">
                        <label class="form-label fw-semibold" style="font-size:13px;">
                            <i class="bi bi-bell" style="color:#0085f3;margin-right:4px;"></i> {{ __('ai_agents.reminder_title') }}
                        </label>
                        <div class="form-text" style="font-size:11px;color:#9ca3af;margin-bottom:10px;">
                            {{ __('ai_agents.reminder_desc') }}
                        </div>

                        @php
                            $savedOffsets = old('reminder_offsets', $agent->reminder_offsets ?? [1440, 60]);
                            if (is_string($savedOffsets)) $savedOffsets = json_decode($savedOffsets, true) ?? [1440, 60];
                            $offsetOptions = [
                                ['value' => 15, 'label' => __('ai_agents.reminder_15min')],
                                ['value' => 30, 'label' => __('ai_agents.reminder_30min')],
                                ['value' => 60, 'label' => __('ai_agents.reminder_1h')],
                                ['value' => 120, 'label' => __('ai_agents.reminder_2h')],
                                ['value' => 720, 'label' => __('ai_agents.reminder_12h')],
                                ['value' => 1440, 'label' => __('ai_agents.reminder_1d')],
                                ['value' => 2880, 'label' => __('ai_agents.reminder_2d')],
                            ];
                        @endphp

                        <div style="display:flex;flex-wrap:wrap;gap:8px;">
                            @foreach($offsetOptions as $opt)
                                <label style="display:flex;align-items:center;gap:5px;padding:6px 12px;border:1.5px solid {{ in_array($opt['value'], $savedOffsets) ? '#0085f3' : '#e5e7eb' }};border-radius:8px;font-size:12px;font-weight:600;color:{{ in_array($opt['value'], $savedOffsets) ? '#0085f3' : '#6b7280' }};background:{{ in_array($opt['value'], $savedOffsets) ? '#eff6ff' : '#fff' }};cursor:pointer;transition:all .15s;">
                                    <input type="checkbox" name="reminder_offsets[]" value="{{ $opt['value'] }}"
                                           {{ in_array($opt['value'], $savedOffsets) ? 'checked' : '' }}
                                           style="accent-color:#0085f3;">
                                    {{ $opt['label'] }}
                                </label>
                            @endforeach
                        </div>

                        <div style="margin-top:12px;">
                            <label class="form-label fw-semibold" style="font-size:13px;">{{ __('ai_agents.reminder_template_label') }}</label>
                            <textarea name="reminder_message_template"
                                      class="form-control"
                                      rows="3"
                                      maxlength="1000"
                                      placeholder="{{ __('ai_agents.reminder_template_ph') }}"
                                      style="font-size:13px;resize:vertical;">{{ old('reminder_message_template', $agent->reminder_message_template ?? '') }}</textarea>
                            <div class="form-text" style="font-size:11px;color:#9ca3af;margin-top:4px;">
                                {{ __('ai_agents.reminder_template_hint') }}
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Voice reply hidden (feature temporarily disabled from UI) --}}
                <input type="hidden" name="enable_voice_reply" value="0">
                <input type="hidden" name="elevenlabs_voice_id" value="">

                {{-- Departamento de transferência --}}
                <div style="margin-top:16px;">
                    <label class="form-label fw-semibold" style="font-size:13px;">{{ __('ai_agents.s6_transfer_department') }}</label>
                    <select name="transfer_to_department_id" class="form-select form-select-sm" style="max-width:320px;">
                        <option value="">{{ __('ai_agents.s6_transfer_dept_none') }}</option>
                        @foreach($departments ?? [] as $dept)
                            <option value="{{ $dept->id }}"
                                {{ old('transfer_to_department_id', $agent->transfer_to_department_id ?? '') == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text" style="font-size:11px;color:#9ca3af;">{{ __('ai_agents.s6_transfer_dept_hint') }}</div>
                </div>

                {{-- Usuário de transferência --}}
                <div style="margin-top:16px;">
                    <label class="form-label fw-semibold" style="font-size:13px;">{{ __('ai_agents.s6_transfer_user') }}</label>
                    <select name="transfer_to_user_id" class="form-select form-select-sm" style="max-width:320px;">
                        <option value="">{{ __('ai_agents.s6_transfer_user_none') }}</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}"
                                {{ old('transfer_to_user_id', $agent->transfer_to_user_id ?? '') == $u->id ? 'selected' : '' }}>
                                {{ $u->name }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text" style="font-size:11px;color:#9ca3af;">{{ __('ai_agents.s6_transfer_user_hint') }}</div>
                </div>
            </div>
        </div>

        {{-- 7. Configurações Avançadas --}}
        <div class="section-card">
            <div class="section-card-header" onclick="toggleSection('advanced')">
                <div class="section-icon"><i class="bi bi-sliders"></i></div>
                <div class="section-card-title">{{ __('ai_agents.s7_title') }}</div>
                <i class="bi bi-chevron-down chevron" id="chevron-advanced"></i>
            </div>
            <div class="section-card-body collapsed" id="body-advanced">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">{{ __('ai_agents.s7_max_message_length') }}</label>
                        <input type="number" name="max_message_length" class="form-control"
                               value="{{ old('max_message_length', $agent->max_message_length ?? 500) }}"
                               min="50" max="4000" step="50">
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('ai_agents.s7_response_delay') }}</label>
                        <input type="number" name="response_delay_seconds" class="form-control"
                               value="{{ old('response_delay_seconds', $agent->response_delay_seconds ?? 2) }}"
                               min="0" max="30"
                               title="{{ __('ai_agents.s7_response_delay_tooltip') }}">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">{{ __('ai_agents.s7_response_wait') }}</label>
                        <input type="number" name="response_wait_seconds" class="form-control"
                               value="{{ old('response_wait_seconds', $agent->response_wait_seconds ?? 0) }}"
                               min="0" max="30"
                               title="{{ __('ai_agents.s7_response_wait_tooltip') }}">
                        <div style="font-size:11px;color:#9ca3af;margin-top:4px;">
                            {{ __('ai_agents.s7_response_wait_desc') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 8. Follow-up Automático --}}
        <div class="section-card">
            <div class="section-card-header" onclick="toggleSection('followup')">
                <div class="section-icon"><i class="bi bi-arrow-repeat"></i></div>
                <div class="section-card-title">{{ __('ai_agents.s8_title') }}</div>
                <i class="bi bi-chevron-down chevron" id="chevron-followup"></i>
            </div>
            <div class="section-card-body collapsed" id="body-followup">
                {{-- Toggle followup_enabled --}}
                <div class="toggle-wrap" onclick="toggleFollowup()" style="margin-bottom:16px;">
                    <div class="toggle-switch {{ ($agent->followup_enabled ?? false) ? 'on' : '' }}" id="followupSwitch"></div>
                    <div style="margin-left:10px;">
                        <div style="font-size:13px;font-weight:700;color:#1a1d23;" id="followupLabel">
                            {{ ($agent->followup_enabled ?? false) ? __('ai_agents.s8_followup_on') : __('ai_agents.s8_followup_off') }}
                        </div>
                        <div style="font-size:11px;color:#9ca3af;">{{ __('ai_agents.s8_followup_desc') }}</div>
                    </div>
                </div>
                <input type="hidden" name="followup_enabled" id="followupInput" value="{{ ($agent->followup_enabled ?? false) ? '1' : '0' }}">

                {{-- Opções (visíveis só quando habilitado) --}}
                <div id="followupOptions" style="{{ ($agent->followup_enabled ?? false) ? '' : 'display:none' }}">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">{{ __('ai_agents.s8_delay_minutes') }}</label>
                            <input type="number" name="followup_delay_minutes" class="form-control"
                                   value="{{ old('followup_delay_minutes', $agent->followup_delay_minutes ?? 40) }}"
                                   min="5" max="1440">
                            <div style="font-size:11px;color:#9ca3af;margin-top:4px;">{{ __('ai_agents.s8_delay_default') }}</div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">{{ __('ai_agents.s8_max_count') }}</label>
                            <input type="number" name="followup_max_count" class="form-control"
                                   value="{{ old('followup_max_count', $agent->followup_max_count ?? 3) }}"
                                   min="1" max="10">
                            <div style="font-size:11px;color:#9ca3af;margin-top:4px;">{{ __('ai_agents.s8_max_count_hint') }}</div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">{{ __('ai_agents.s8_hour_start') }}</label>
                            <input type="number" name="followup_hour_start" class="form-control"
                                   value="{{ old('followup_hour_start', $agent->followup_hour_start ?? 8) }}"
                                   min="0" max="23">
                            <div style="font-size:11px;color:#9ca3af;margin-top:4px;">{{ __('ai_agents.s8_hour_start_hint') }}</div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">{{ __('ai_agents.s8_hour_end') }}</label>
                            <input type="number" name="followup_hour_end" class="form-control"
                                   value="{{ old('followup_hour_end', $agent->followup_hour_end ?? 18) }}"
                                   min="1" max="23">
                            <div style="font-size:11px;color:#9ca3af;margin-top:4px;">{{ __('ai_agents.s8_hour_end_hint') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 9. Widget do Chat (só para web_chat) --}}
        @php
            $showWidget = old('channel', $agent->channel ?? 'whatsapp') === 'web_chat';
            $currentAvatar = old('bot_avatar', $agent->bot_avatar ?? '');
            $predefinedAvatars = [
                '/images/avatars/agent-1.png',
                '/images/avatars/agent-2.png',
                '/images/avatars/agent-3.png',
                '/images/avatars/agent-4.png',
                '/images/avatars/agent-5.png',
            ];
            $isCustomAvatar = $currentAvatar && !in_array($currentAvatar, $predefinedAvatars);
        @endphp
        <div class="section-card" id="widgetSection" style="{{ $showWidget ? '' : 'display:none' }}">
            <div class="section-card-header" onclick="toggleSection('widget')">
                <div class="section-icon"><i class="bi bi-window-stack"></i></div>
                <div class="section-card-title">{{ __('ai_agents.s9_title') }}</div>
                <i class="bi bi-chevron-down chevron" id="chevron-widget"></i>
            </div>
            <div class="section-card-body {{ $showWidget && $isEdit ? '' : 'collapsed' }}" id="body-widget">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">{{ __('ai_agents.s9_bot_name') }}</label>
                        <input type="text" name="bot_name" class="form-control"
                               value="{{ old('bot_name', $agent->bot_name ?? '') }}"
                               placeholder="{{ __('ai_agents.s9_bot_name_placeholder') }}" maxlength="100">
                        <div style="font-size:11px;color:#9ca3af;margin-top:4px;">{{ __('ai_agents.s9_bot_name_hint') }}</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('ai_agents.s9_widget_type') }}</label>
                        <div style="display:flex;gap:10px;margin-top:4px;">
                            @php $wType = old('widget_type', $agent->widget_type ?? 'bubble'); @endphp
                            <label style="flex:1;cursor:pointer;">
                                <input type="radio" name="widget_type" value="bubble" {{ $wType === 'bubble' ? 'checked' : '' }} style="display:none;" onchange="updateWidgetTypeCards()">
                                <div class="channel-card {{ $wType === 'bubble' ? 'selected' : '' }}" data-wtype="bubble">
                                    <i class="bi bi-chat-dots" style="font-size:16px;"></i>
                                    <span>{{ __('ai_agents.s9_widget_bubble') }}</span>
                                </div>
                            </label>
                            <label style="flex:1;cursor:pointer;">
                                <input type="radio" name="widget_type" value="inline" {{ $wType === 'inline' ? 'checked' : '' }} style="display:none;" onchange="updateWidgetTypeCards()">
                                <div class="channel-card {{ $wType === 'inline' ? 'selected' : '' }}" data-wtype="inline">
                                    <i class="bi bi-layout-sidebar-inset" style="font-size:16px;"></i>
                                    <span>{{ __('ai_agents.s9_widget_inline') }}</span>
                                </div>
                            </label>
                        </div>
                        <div style="font-size:11px;color:#9ca3af;margin-top:4px;">{{ __('ai_agents.s9_widget_type_hint') }}</div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">{{ __('ai_agents.s9_avatar') }}</label>
                    <input type="hidden" name="bot_avatar" id="agentAvatarValue" value="{{ $currentAvatar }}">
                    <div style="display:flex;gap:10px;flex-wrap:wrap;" id="agentAvatarGrid">
                        @foreach($predefinedAvatars as $av)
                        <div class="avatar-option {{ $currentAvatar === $av ? 'selected' : '' }}"
                             data-url="{{ $av }}"
                             onclick="selectAgentAvatar('{{ $av }}')"
                             style="width:52px;height:52px;border-radius:50%;overflow:hidden;cursor:pointer;border:2.5px solid {{ $currentAvatar === $av ? '#0085f3' : '#e8eaf0' }};transition:border-color .15s;flex-shrink:0;">
                            <img src="{{ asset($av) }}" alt="Avatar" style="width:100%;height:100%;object-fit:cover;"
                                 onerror="this.parentElement.style.display='none'">
                        </div>
                        @endforeach
                        <div id="agentAvatarUploadCard"
                             onclick="document.getElementById('agentAvatarUploadInput').click()"
                             style="width:52px;height:52px;border-radius:50%;overflow:hidden;cursor:pointer;border:2.5px solid {{ $isCustomAvatar ? '#0085f3' : '#e8eaf0' }};transition:border-color .15s;flex-shrink:0;background:#f8fafc;display:flex;align-items:center;justify-content:center;position:relative;">
                            @if($isCustomAvatar)
                                <img id="agentAvatarPreview" src="{{ $currentAvatar }}" alt="Avatar" style="width:100%;height:100%;object-fit:cover;position:absolute;inset:0;">
                            @else
                                <img id="agentAvatarPreview" src="" alt="" style="width:100%;height:100%;object-fit:cover;position:absolute;inset:0;display:none;">
                                <svg id="agentAvatarIcon" viewBox="0 0 24 24" style="width:20px;height:20px;fill:#9ca3af;"><path d="M19 3H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2V5a2 2 0 00-2-2zm-7 13l-4-4h3V9h2v3h3l-4 4z"/></svg>
                            @endif
                        </div>
                        <input type="file" id="agentAvatarUploadInput" accept="image/*" style="display:none;">
                    </div>
                    <div style="font-size:11px;color:#9ca3af;margin-top:6px;">{{ __('ai_agents.s9_avatar_hint') }}</div>
                </div>

                <div class="form-group">
                    <label class="form-label">{{ __('ai_agents.s9_welcome_message') }}</label>
                    <textarea name="welcome_message" class="form-control" rows="3"
                              placeholder="{{ __('ai_agents.s9_welcome_placeholder') }}">{{ old('welcome_message', $agent->welcome_message ?? '') }}</textarea>
                    <div style="font-size:11px;color:#9ca3af;margin-top:4px;">{{ __('ai_agents.s9_welcome_hint') }}</div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">{{ __('ai_agents.s9_widget_color') }}</label>
                        <div style="display:flex;gap:8px;align-items:center;">
                            <input type="color" id="widgetColorPicker"
                                   value="{{ old('widget_color', $agent->widget_color ?? '#0085f3') }}"
                                   style="width:42px;height:38px;border:1.5px solid #e8eaf0;border-radius:8px;cursor:pointer;padding:2px;"
                                   oninput="document.getElementById('widgetColorHex').value=this.value;document.querySelector('[name=widget_color]').value=this.value;">
                            <input type="text" id="widgetColorHex" class="form-control" style="width:120px;"
                                   value="{{ old('widget_color', $agent->widget_color ?? '#0085f3') }}"
                                   maxlength="10"
                                   oninput="document.getElementById('widgetColorPicker').value=this.value;document.querySelector('[name=widget_color]').value=this.value;">
                            <input type="hidden" name="widget_color" value="{{ old('widget_color', $agent->widget_color ?? '#0085f3') }}">
                        </div>
                    </div>
                </div>

                @if($isEdit && ($embedScriptUrl ?? null))
                <div class="form-group" style="margin-top:8px;">
                    <label class="form-label">{{ __('ai_agents.s9_embed_code') }}</label>
                    <div style="display:flex;gap:8px;align-items:center;">
                        <input type="text" class="form-control" readonly id="embedCodeInput"
                               value='<script src="{{ $embedScriptUrl }}"></script>'
                               style="font-family:monospace;font-size:12px;background:#f8fafc;">
                        <button type="button" class="btn-primary" style="white-space:nowrap;padding:9px 16px;" onclick="copyEmbedCode()">
                            <i class="bi bi-clipboard"></i> {{ __('ai_agents.s9_embed_copy') }}
                        </button>
                    </div>
                    <div style="font-size:11px;color:#9ca3af;margin-top:4px;">{{ __('ai_agents.s9_embed_hint') }}</div>
                </div>
                @endif
            </div>
        </div>

        <div class="ae-save-bar">
            <button type="submit" class="btn-primary">
                <i class="bi bi-floppy"></i> {{ $isEdit ? __('ai_agents.edit_save') : __('ai_agents.form_create') }}
            </button>
            <a href="{{ route('ai.agents.index') }}" class="btn-cancel">{{ __('ai_agents.form_cancel') }}</a>
            @if($isEdit)
            <button type="button" class="btn-cancel" style="margin-left:auto;" onclick="toggleTestChat()">
                <i class="bi bi-chat-dots"></i> {{ __('ai_agents.form_test_agent') }}
            </button>
            @endif
        </div>

    </form>

        </main>{{-- /.ae-main --}}
    </div>{{-- /.ae-layout --}}

    @if($isEdit)
    {{-- Widget de teste --}}
    <div class="test-chat-panel" id="testChatPanel" style="display:none;">
        <div class="test-chat-header" onclick="toggleTestChat()">
            <span class="test-chat-title"><i class="bi bi-robot"></i> {{ __('ai_agents.form_test_title') }} {{ $agent->name }}</span>
            <i class="bi bi-chevron-down test-chat-toggle" id="testChatChevron"></i>
        </div>
        <div class="test-chat-body" id="testChatBody">
            <div class="chat-bubble agent">{{ __('ai_agents.form_test_greeting', ['name' => $agent->name]) }}</div>
        </div>
        <div class="test-chat-input-wrap" id="testInputWrap">
            <input type="text" class="test-chat-input" id="testInput"
                   placeholder="{{ __('ai_agents.form_test_placeholder') }}"
                   onkeydown="if(event.key==='Enter'){event.preventDefault();sendTest();}">
            <button class="test-send-btn" id="testSendBtn" onclick="sendTest()">
                <i class="bi bi-send"></i>
            </button>
        </div>
    </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
const AILANG    = @json(__('ai_agents'));
const AGENT_ID  = {{ $agent->id ?? 'null' }};
const CSRF      = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
const KB_UPLOAD = '{{ $isEdit ? route('ai.agents.knowledge-files.store', $agent) : '' }}';
const KB_DELETE = '{{ $isEdit ? url('/ia/agentes/' . $agent->id . '/knowledge-files') : '' }}';

/* ── Knowledge Files ── */
function fileIcon(mime) {
    if (mime.startsWith('image/')) return '<i class="bi bi-file-earmark-image" style="color:#8b5cf6;font-size:22px;"></i>';
    if (mime === 'application/pdf') return '<i class="bi bi-file-earmark-pdf" style="color:#ef4444;font-size:22px;"></i>';
    return '<i class="bi bi-file-earmark-text" style="color:#0085f3;font-size:22px;"></i>';
}

function handleKbDrop(e) {
    e.preventDefault();
    const dz = document.getElementById('kbDropzone');
    dz.style.borderColor = '#d1d5db';
    dz.style.background  = '';
    const file = e.dataTransfer.files[0];
    if (file) uploadKbFile(file);
}

async function uploadKbFile(file) {
    if (!file || !AGENT_ID) return;

    const list = document.getElementById('kbFileList');
    const tmpId = 'tmp-' + Date.now();

    // Placeholder carregando
    const tmpEl = document.createElement('div');
    tmpEl.className = 'kb-uploading';
    tmpEl.id = tmpId;
    tmpEl.innerHTML = '<span class="spinner-border spinner-border-sm"></span> ' + AILANG.toast_kb_uploading + ' <strong>' + escapeHtml(file.name) + '</strong>…';
    list.prepend(tmpEl);

    const fd = new FormData();
    fd.append('file', file);
    fd.append('_token', CSRF);

    try {
        const res  = await fetch(KB_UPLOAD, { method: 'POST', body: fd });
        const data = await res.json();
        tmpEl.remove();

        if (!res.ok) {
            toastr.error(data.message ?? AILANG.toast_kb_upload_error);
            return;
        }

        // Montar HTML do novo arquivo
        let badgeHtml = '';
        if (data.status === 'done') {
            badgeHtml = '<span class="kb-status-badge done">' + AILANG.s5_status_extracted + '</span>';
            if (data.preview) {
                badgeHtml += ' <button type="button" class="kb-preview-btn" onclick="toggleKbPreview(' + data.id + ')"><i class="bi bi-eye"></i> ' + AILANG.s5_preview_btn + '</button>';
            }
        } else if (data.status === 'failed') {
            badgeHtml = '<span class="kb-status-badge failed">' + AILANG.s5_status_failed + '</span>';
            if (data.error_message) badgeHtml += '<span style="font-size:11px;color:#ef4444;display:block;margin-top:2px;">' + escapeHtml(data.error_message) + '</span>';
        } else {
            badgeHtml = '<span class="kb-status-badge pending">' + AILANG.s5_status_pending + '</span>';
        }

        const itemEl = document.createElement('div');
        itemEl.className = 'kb-file-item';
        itemEl.id = 'kb-file-' + data.id;
        itemEl.innerHTML = `
            <div class="kb-file-icon">${fileIcon(data.mime_type ?? '')}</div>
            <div class="kb-file-info">
                <div class="kb-file-name">${escapeHtml(data.original_name)}</div>
                ${badgeHtml}
            </div>
            <button type="button" class="kb-del-btn" onclick="deleteKbFile(${data.id}, '${escapeHtml(data.original_name)}')" title="${AILANG.s5_remove_btn}">
                <i class="bi bi-trash3"></i>
            </button>`;
        list.prepend(itemEl);

        if (data.preview) {
            const prevEl = document.createElement('div');
            prevEl.className = 'kb-file-preview';
            prevEl.id = 'kb-preview-' + data.id;
            prevEl.style.display = 'none';
            prevEl.textContent = data.preview;
            itemEl.insertAdjacentElement('afterend', prevEl);
        }

        if (data.status === 'done') toastr.success(AILANG.toast_kb_processed);
        else if (data.status === 'failed') toastr.warning(AILANG.toast_kb_extract_failed);
    } catch (err) {
        tmpEl.remove();
        toastr.error(AILANG.toast_kb_network_error);
    }

    // Reset input
    document.getElementById('kbFileInput').value = '';
}

function toggleKbPreview(id) {
    const el = document.getElementById('kb-preview-' + id);
    if (el) el.style.display = el.style.display === 'none' ? 'block' : 'none';
}

async function deleteKbFile(id, name) {
    if (!confirm(AILANG.toast_kb_delete_confirm.replace(':name', name))) return;

    try {
        const res = await fetch(KB_DELETE + '/' + id, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        });
        if (!res.ok) { toastr.error(AILANG.toast_kb_delete_error); return; }

        document.getElementById('kb-file-' + id)?.remove();
        document.getElementById('kb-preview-' + id)?.remove();
        toastr.success(AILANG.toast_kb_deleted);
    } catch {
        toastr.error(AILANG.toast_kb_network_error);
    }
}

/* ── Mídias do Agente (upload/delete) ── */
const MEDIA_UPLOAD = '{{ $isEdit ? route("ai.agents.media.store", $agent) : "" }}';
const MEDIA_DELETE = '{{ $isEdit ? url("/ia/agentes/" . $agent->id . "/media") : "" }}';
let _pendingMediaFile = null;

function handleMediaDropEdit(e) {
    e.preventDefault();
    e.currentTarget.style.borderColor = '#d1d5db';
    e.currentTarget.style.background = '';
    if (e.dataTransfer.files.length) prepareMediaEdit(e.dataTransfer.files[0]);
}

function prepareMediaEdit(file) {
    if (!file) return;
    if (file.size > 20 * 1024 * 1024) { toastr.error(AILANG.toast_media_too_large); return; }
    _pendingMediaFile = file;

    const preview = document.getElementById('mediaPendingPreview');
    const thumb = document.getElementById('mediaPendingThumb');
    const dropzone = document.getElementById('mediaDropzone');

    // Build thumbnail
    const isImg = file.type.startsWith('image/');
    const isPdf = file.type.includes('pdf');

    if (isImg) {
        const reader = new FileReader();
        reader.onload = function(e) {
            thumb.innerHTML = `<img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover;max-height:220px;">`;
        };
        reader.readAsDataURL(file);
    } else if (isPdf) {
        thumb.innerHTML = '<i class="bi bi-file-earmark-pdf" style="font-size:40px;color:#dc2626;"></i>';
    } else {
        thumb.innerHTML = '<i class="bi bi-file-earmark-text" style="font-size:40px;color:#2563eb;"></i>';
    }

    document.getElementById('mediaPendingName').textContent = file.name;
    document.getElementById('mediaDescEdit').value = '';
    document.getElementById('mediaDescEdit').placeholder = AILANG.s5b_desc_placeholder;

    preview.style.display = 'block';
    dropzone.style.display = 'none';
    document.getElementById('mediaDescEdit').focus();
    document.getElementById('mediaFileInput').value = '';
}

function cancelMediaPending() {
    _pendingMediaFile = null;
    document.getElementById('mediaPendingPreview').style.display = 'none';
    document.getElementById('mediaDropzone').style.display = 'block';
}

async function uploadMediaEdit() {
    if (!_pendingMediaFile) return;
    const desc = document.getElementById('mediaDescEdit').value.trim();
    if (!desc) { toastr.warning(AILANG.toast_media_describe); return; }

    const btn = document.getElementById('mediaUploadBtnEdit');
    btn.disabled = true; btn.textContent = AILANG.s5b_uploading;

    try {
        const fd = new FormData();
        fd.append('file', _pendingMediaFile);
        fd.append('description', desc);
        fd.append('_token', CSRF);

        const res = await fetch(MEDIA_UPLOAD, {
            method: 'POST',
            headers: { 'Accept': 'application/json' },
            body: fd,
        });
        const data = await res.json();
        btn.disabled = false; btn.textContent = AILANG.s5b_upload;

        if (!res.ok) {
            toastr.error(data.message || Object.values(data.errors || {}).flat().join(', ') || AILANG.toast_media_upload_error);
            return;
        }

        const isImg = (data.mime_type || '').startsWith('image/');
        const isPdf = (data.mime_type || '').includes('pdf');
        const thumbHtml = isImg
            ? `<img src="${data.url}" alt="${data.original_name}" style="width:100%;height:100%;object-fit:cover;">`
            : isPdf
                ? '<i class="bi bi-file-earmark-pdf" style="font-size:32px;color:#dc2626;"></i>'
                : '<i class="bi bi-file-earmark-text" style="font-size:32px;color:#2563eb;"></i>';

        const safeName = data.original_name.replace(/'/g, "\\'").replace(/</g, '&lt;');
        document.getElementById('mediaFilesList').insertAdjacentHTML('beforeend', `
            <div class="media-card" id="media-file-${data.id}">
                <div class="media-card-thumb">${thumbHtml}</div>
                <div class="media-card-body">
                    <div class="media-card-name" title="${data.original_name}">${data.original_name}</div>
                    <div class="media-card-desc">${data.description}</div>
                </div>
                <button type="button" class="media-card-del" onclick="deleteMediaEdit(${data.id}, '${safeName}')" title="${AILANG.s5_remove_btn}">
                    <i class="bi bi-trash3"></i>
                </button>
            </div>
        `);

        _pendingMediaFile = null;
        document.getElementById('mediaPendingPreview').style.display = 'none';
        document.getElementById('mediaDropzone').style.display = 'block';
        toastr.success(AILANG.toast_media_uploaded);
    } catch {
        btn.disabled = false; btn.textContent = AILANG.s5b_upload;
        toastr.error(AILANG.toast_media_network_error);
    }
}

async function deleteMediaEdit(id, name) {
    if (!confirm(AILANG.toast_media_delete_confirm.replace(':name', name))) return;
    try {
        const res = await fetch(MEDIA_DELETE + '/' + id, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        });
        if (!res.ok) { toastr.error(AILANG.toast_media_delete_error); return; }
        document.getElementById('media-file-' + id)?.remove();
        toastr.success(AILANG.toast_media_deleted);
    } catch {
        toastr.error(AILANG.toast_media_network_error);
    }
}

let testHistory = [];
let testChatOpen = false;

/* ── Canal ── */
function updateChannelCards() {
    const selected = document.querySelector('input[name="channel"]:checked')?.value;
    document.querySelectorAll('.channel-card[data-channel]').forEach(card => {
        card.classList.toggle('selected', card.dataset.channel === selected);
    });
    // Show/hide WhatsApp instances section
    const waSection = document.getElementById('whatsappInstancesSection');
    if (waSection) {
        waSection.style.display = selected === 'whatsapp' ? '' : 'none';
    }
    // Show/hide widget tab on sidebar
    const widgetTab = document.getElementById('aeWidgetTab');
    if (widgetTab) {
        widgetTab.style.display = selected === 'web_chat' ? '' : 'none';
    }
}

/* ── Widget type cards ── */
function updateWidgetTypeCards() {
    const selected = document.querySelector('input[name="widget_type"]:checked')?.value;
    document.querySelectorAll('.channel-card[data-wtype]').forEach(card => {
        card.classList.toggle('selected', card.dataset.wtype === selected);
    });
}

/* ── Agent Avatar ── */
function selectAgentAvatar(url) {
    document.getElementById('agentAvatarValue').value = url;
    const grid = document.getElementById('agentAvatarGrid');
    grid.querySelectorAll('.avatar-option').forEach(el => {
        el.style.borderColor = el.dataset.url === url ? '#0085f3' : '#e8eaf0';
        el.classList.toggle('selected', el.dataset.url === url);
    });
    const uploadCard = document.getElementById('agentAvatarUploadCard');
    uploadCard.style.borderColor = '#e8eaf0';
}

document.getElementById('agentAvatarUploadInput')?.addEventListener('change', function() {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function(e) {
        // Crop to square (centered on top) before saving
        const img = new Image();
        img.onload = function() {
            const size = Math.min(img.width, img.height, 512);
            const canvas = document.createElement('canvas');
            canvas.width = size;
            canvas.height = size;
            const ctx = canvas.getContext('2d');
            const sx = (img.width - size) / 2;
            const sy = 0; // top-aligned to keep face visible
            ctx.drawImage(img, sx, sy, size, size, 0, 0, size, size);
            const url = canvas.toDataURL('image/jpeg', 0.85);
            document.getElementById('agentAvatarValue').value = url;
            const preview = document.getElementById('agentAvatarPreview');
            preview.src = url;
            preview.style.display = '';
            const icon = document.getElementById('agentAvatarIcon');
            if (icon) icon.style.display = 'none';
            const uploadCard = document.getElementById('agentAvatarUploadCard');
            uploadCard.style.borderColor = '#0085f3';
            document.getElementById('agentAvatarGrid').querySelectorAll('.avatar-option').forEach(el => {
                el.style.borderColor = '#e8eaf0';
                el.classList.remove('selected');
            });
        };
        img.src = e.target.result;
    };
    reader.readAsDataURL(file);
});

/* ── Embed code ── */
function copyEmbedCode() {
    const input = document.getElementById('embedCodeInput');
    if (!input) return;
    navigator.clipboard.writeText(input.value).then(() => {
        const btn = input.nextElementSibling;
        const original = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check-lg"></i> ' + AILANG.s9_embed_copied;
        setTimeout(() => btn.innerHTML = original, 2000);
    });
}

/* ── Toggle ativo ── */
function toggleActive() {
    const sw    = document.getElementById('toggleSwitch');
    const input = document.getElementById('isActiveInput');
    const label = document.getElementById('toggleLabel');
    const isOn  = input.value === '1';
    input.value = isOn ? '0' : '1';
    sw.classList.toggle('on', !isOn);
    label.textContent = isOn ? AILANG.toggle_active_off : AILANG.toggle_active_on;
}

/* ── Toggle auto-assign ── */
function toggleAutoAssign() {
    const sw    = document.getElementById('autoAssignSwitch');
    const input = document.getElementById('autoAssignInput');
    const label = document.getElementById('autoAssignLabel');
    const isOn  = input.value === '1';
    input.value = isOn ? '0' : '1';
    sw.classList.toggle('on', !isOn);
    label.textContent = isOn ? AILANG.toggle_auto_assign_off : AILANG.toggle_auto_assign_on;
}

function togglePipelineTool() {
    const sw    = document.getElementById('pipelineToolSwitch');
    const input = document.getElementById('pipelineToolInput');
    const label = document.getElementById('pipelineToolLabel');
    const isOn  = input.value === '1';
    input.value = isOn ? '0' : '1';
    sw.classList.toggle('on', !isOn);
    label.textContent = isOn ? AILANG.s6_pipeline_off : AILANG.s6_pipeline_on;
}

function toggleTagsTool() {
    const sw    = document.getElementById('tagsToolSwitch');
    const input = document.getElementById('tagsToolInput');
    const label = document.getElementById('tagsToolLabel');
    const isOn  = input.value === '1';
    input.value = isOn ? '0' : '1';
    sw.classList.toggle('on', !isOn);
    label.textContent = isOn ? AILANG.s6_tags_off : AILANG.s6_tags_on;
}

function toggleIntentNotify() {
    const sw    = document.getElementById('intentNotifySwitch');
    const input = document.getElementById('intentNotifyInput');
    const label = document.getElementById('intentNotifyLabel');
    const isOn  = input.value === '1';
    input.value = isOn ? '0' : '1';
    sw.classList.toggle('on', !isOn);
    label.textContent = isOn ? AILANG.s6_intent_off : AILANG.s6_intent_on;
}

function toggleCalendarTool() {
    const sw      = document.getElementById('calendarToolSwitch');
    const input   = document.getElementById('calendarToolInput');
    const label   = document.getElementById('calendarToolLabel');
    const options = document.getElementById('calendarToolOptions');
    const isOn    = input.value === '1';
    input.value = isOn ? '0' : '1';
    sw.classList.toggle('on', !isOn);
    label.textContent = isOn ? AILANG.s6_calendar_off : AILANG.s6_calendar_on;
    options.style.display = isOn ? 'none' : '';
    // Load calendars when enabling for the first time
    if (!isOn) loadAgentCalendars();
}

function toggleProductsTool() {
    const sw    = document.getElementById('productsToolSwitch');
    const input = document.getElementById('productsToolInput');
    const label = document.getElementById('productsToolLabel');
    const isOn  = input.value === '1';
    input.value = isOn ? '0' : '1';
    sw.classList.toggle('on', !isOn);
    label.textContent = isOn ? AILANG.s6_products_off : AILANG.s6_products_on;
}

const SAVED_CALENDAR_ID = @json(old('calendar_id', $agent->calendar_id ?? ''));
let agentCalendarsLoaded = false;

function loadAgentCalendars() {
    if (agentCalendarsLoaded) return;
    const select = document.getElementById('calendarIdSelect');
    select.innerHTML = '<option value="">' + AILANG.s6_calendar_loading + '</option>';

    fetch('{{ route("calendar.calendars") }}', {
        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content },
    })
    .then(r => r.json())
    .then(data => {
        if (data.error) {
            select.innerHTML = '<option value="">' + AILANG.s6_calendar_primary + '</option>';
            return;
        }
        let html = '<option value="">' + AILANG.s6_calendar_primary + '</option>';
        data.forEach(c => {
            const label    = c.summary + (c.primary ? ' ' + AILANG.s6_calendar_principal : '');
            const selected = (SAVED_CALENDAR_ID === c.id) ? ' selected' : '';
            html += `<option value="${c.id}"${selected}>${label}</option>`;
        });
        select.innerHTML = html;
        agentCalendarsLoaded = true;
    })
    .catch(() => {
        select.innerHTML = '<option value="">' + AILANG.s6_calendar_primary + '</option>';
    });
}

// Auto-load if calendar tool is already enabled
if (document.getElementById('calendarToolInput')?.value === '1') {
    document.addEventListener('DOMContentLoaded', () => loadAgentCalendars());
}

/* Voice reply JS removed — feature temporarily disabled from UI */

function toggleFollowup() {
    const sw      = document.getElementById('followupSwitch');
    const input   = document.getElementById('followupInput');
    const label   = document.getElementById('followupLabel');
    const options = document.getElementById('followupOptions');
    const isOn    = input.value === '1';
    input.value = isOn ? '0' : '1';
    sw.classList.toggle('on', !isOn);
    label.textContent = isOn ? AILANG.s8_followup_off : AILANG.s8_followup_on;
    options.style.display = isOn ? 'none' : '';
}

/* ── Sidebar pane switching (substitui toggleSection) ── */
function switchPane(name, btn) {
    document.querySelectorAll('.section-card').forEach(el => el.classList.remove('active-pane'));
    // Match por data-pane-name OU pelo body-X interno (legado)
    let target = document.querySelector('.section-card[data-pane-name="' + name + '"]');
    if (!target) {
        const body = document.getElementById('body-' + name);
        if (body) target = body.closest('.section-card');
    }
    if (target) target.classList.add('active-pane');
    // Atualiza sidebar
    document.querySelectorAll('.ae-sect-item').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');
    // Calendar lazy-load se entrando em "tools"
    if (name === 'tools' && document.getElementById('calendarToolInput')?.value === '1') {
        loadAgentCalendars();
    }
    // Mobile: scroll pro main pra mostrar o pane recém-ativado
    if (window.innerWidth <= 900) {
        if (btn) btn.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        const main = document.querySelector('.ae-main');
        if (main) setTimeout(() => main.scrollIntoView({ behavior: 'smooth', block: 'start' }), 250);
    }
}

// Stub pra retrocompatibilidade — section headers estão escondidos via CSS, mas o JS antigo pode chamar
function toggleSection(id) { /* no-op — sidebar agora controla visibility */ }

/* ── Display avatar (admin-only) ── */
function selectDisplayAvatar(file, el) {
    document.getElementById('displayAvatarInput').value = file;
    document.querySelectorAll('.ae-avatar-opt').forEach(o => o.classList.remove('selected'));
    el.classList.add('selected');
    // Atualiza preview na sidebar
    const sidebarPreview = document.getElementById('aeAvatarPreview');
    if (sidebarPreview) sidebarPreview.src = file;
}

/* ── Etapas dinâmicas ── */
let stageCount = {{ count(old('conversation_stages', $agent->conversation_stages ?? [])) }};

function addStage() {
    const i    = stageCount++;
    const list = document.getElementById('stagesList');
    list.insertAdjacentHTML('beforeend', `
        <div class="stage-item" data-index="${i}">
            <div class="stage-num">${list.children.length + 1}</div>
            <div class="stage-inputs">
                <input type="text" name="conversation_stages[${i}][name]"
                       class="form-control" style="min-height:unset;"
                       placeholder="${AILANG.s4_stage_name_placeholder}">
                <input type="text" name="conversation_stages[${i}][description]"
                       class="form-control" style="min-height:unset;"
                       placeholder="${AILANG.s4_stage_desc_placeholder}">
            </div>
            <button type="button" class="stage-del" onclick="removeStage(this)">×</button>
        </div>
    `);
    renumberStages();
}

function removeStage(btn) {
    btn.closest('.stage-item').remove();
    renumberStages();
}

function renumberStages() {
    document.querySelectorAll('#stagesList .stage-item').forEach((el, i) => {
        el.querySelector('.stage-num').textContent = i + 1;
    });
}

/* ── Chat de Teste ── */
function toggleTestChat() {
    const panel = document.getElementById('testChatPanel');
    testChatOpen = !testChatOpen;
    panel.style.display = testChatOpen ? 'flex' : 'none';
    if (testChatOpen) {
        setTimeout(() => document.getElementById('testInput').focus(), 100);
    }
}

function appendBubble(role, text) {
    const body   = document.getElementById('testChatBody');
    const bubble = document.createElement('div');
    bubble.className = 'chat-bubble ' + role;
    bubble.textContent = text;
    body.appendChild(bubble);
    body.scrollTop = body.scrollHeight;
    return bubble;
}

async function sendTest() {
    const input = document.getElementById('testInput');
    const msg   = input.value.trim();
    if (!msg || !AGENT_ID) return;
    input.value = '';

    appendBubble('user', msg);
    const typingBubble = appendBubble('agent typing', '…');

    document.getElementById('testSendBtn').disabled = true;

    try {
        const res  = await fetch(`/ia/agentes/${AGENT_ID}/test-chat`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ message: msg, history: testHistory }),
        });
        const data = await res.json();

        typingBubble.remove();

        if (data.success) {
            appendBubble('agent', data.reply);
            testHistory.push({ role: 'user',  content: msg });
            testHistory.push({ role: 'agent', content: data.reply });
            // Mantém histórico máximo de 20 trocas
            if (testHistory.length > 40) testHistory = testHistory.slice(-40);
        } else {
            appendBubble('agent', '⚠️ ' + AILANG.form_test_error + (data.message || AILANG.form_test_error_generic));
        }
    } catch (e) {
        typingBubble.remove();
        appendBubble('agent', '⚠️ ' + AILANG.form_test_error_connection);
    } finally {
        document.getElementById('testSendBtn').disabled = false;
        input.focus();
    }
}
</script>
@endpush

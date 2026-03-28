@extends('tenant.layouts.app')
@php
    $title = __('integrations.title');
    $pageIcon = 'plugin';
@endphp

@push('styles')
<style>
    .integrations-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
    }

    @media (max-width: 1100px) {
        .integrations-grid { grid-template-columns: repeat(2, 1fr); }
    }

    @media (max-width: 700px) {
        .integrations-grid { grid-template-columns: 1fr; }
    }

    .integration-card {
        background: #fff;
        border-radius: 14px;
        border: 1px solid #e8eaf0;
        overflow: hidden;
    }

    .integration-header {
        padding: 20px 24px;
        display: flex;
        align-items: center;
        gap: 14px;
        border-bottom: 1px solid #f0f2f7;
    }

    .integration-logo {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        font-weight: 800;
        color: #fff;
        flex-shrink: 0;
    }

    .integration-logo.facebook   { background: #1877F2; }
    .integration-logo.google     { background: linear-gradient(135deg, #4285F4 0%, #EA4335 50%, #FBBC04 75%, #34A853 100%); }
    .integration-logo.whatsapp   { background: #25D366; }
    .integration-logo.instagram  { background: linear-gradient(135deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%); }

    .conn-soon { background: #f3f4f6; color: #9ca3af; }

    .integration-features {
        list-style: none;
        padding: 0;
        margin: 0 0 16px;
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .integration-features li {
        font-size: 12.5px;
        color: #4b5563;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .integration-features li::before {
        content: '✓';
        font-size: 11px;
        font-weight: 700;
        color: #9ca3af;
        flex-shrink: 0;
        width: 14px;
        text-align: center;
    }

    .btn-coming-soon {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 9px 18px;
        background: #f3f4f6;
        color: #9ca3af;
        border: 1.5px solid #e8eaf0;
        border-radius: 100px;
        font-size: 13px;
        font-weight: 600;
        cursor: not-allowed;
    }

    /* Modal QR */
    .wa-modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 10000;
        background: rgba(15,23,42,.55);
        align-items: center;
        justify-content: center;
    }
    .wa-modal-overlay.open { display: flex; }

    .wa-modal {
        background: #fff;
        border-radius: 16px;
        padding: 32px;
        width: 100%;
        max-width: 440px;
        margin: 16px;
        text-align: center;
        box-shadow: 0 24px 60px rgba(0,0,0,.18);
    }

    /* QR modal — layout horizontal 2 colunas */
    #waQrModal .wa-modal {
        max-width: 720px;
        display: flex;
        gap: 32px;
        text-align: left;
        padding: 36px;
    }

    .wa-modal-left {
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .wa-modal-left h4 {
        font-size: 18px;
        font-weight: 700;
        color: #1a1d23;
        margin: 0 0 6px;
    }

    .wa-modal-left .wa-subtitle {
        font-size: 13px;
        color: #6b7280;
        margin: 0 0 20px;
    }

    .wa-modal-left label {
        font-size: 13px;
        font-weight: 600;
        color: #1a1d23;
        display: block;
        margin-bottom: 6px;
    }

    .wa-modal-left input[type="text"] {
        width: 100%;
        padding: 10px 14px;
        border: 1.5px solid #e5e7eb;
        border-radius: 10px;
        font-size: 13px;
        color: #374151;
        outline: none;
        transition: border-color .15s;
        box-sizing: border-box;
    }

    .wa-modal-left input[type="text"]:focus { border-color: #25D366; }
    .wa-modal-left input[type="text"]:read-only { background: #f9fafb; color: #9ca3af; cursor: default; }

    .wa-modal-right {
        width: 260px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .wa-modal h4 {
        font-size: 18px;
        font-weight: 700;
        color: #1a1d23;
        margin: 0 0 6px;
    }

    .wa-modal p {
        font-size: 13px;
        color: #6b7280;
        margin: 0 0 20px;
    }

    .wa-steps {
        text-align: left;
        font-size: 13px;
        color: #374151;
        line-height: 1.7;
        margin-bottom: 22px;
        padding: 14px 16px;
        background: #f8fafc;
        border-radius: 10px;
        list-style: none;
        counter-reset: step;
    }

    .wa-steps li {
        counter-increment: step;
        display: flex;
        align-items: flex-start;
        gap: 10px;
        margin-bottom: 6px;
    }

    .wa-steps li:last-child { margin-bottom: 0; }

    .wa-steps li::before {
        content: counter(step);
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: #25D366;
        color: #fff;
        font-size: 11px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        margin-top: 2px;
    }

    .wa-qr-area {
        width: 220px;
        height: 220px;
        margin: 0 0 12px;
        border: 1.5px solid #e5e7eb;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    .wa-qr-area img { width: 100%; height: 100%; object-fit: contain; }

    .wa-qr-placeholder {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        color: #c9cdd5;
    }

    .wa-qr-placeholder i { font-size: 48px; }
    .wa-qr-placeholder span { font-size: 12px; color: #9ca3af; }

    #waQrStatus {
        font-size: 13px;
        color: #6b7280;
        text-align: center;
    }

    #waQrStatus.connected { color: #10B981; font-weight: 600; }
    #waQrStatus.error     { color: #EF4444; }

    .btn-wa-cancel {
        padding: 9px 20px;
        background: #fff;
        color: #374151;
        border: 1.5px solid #e8eaf0;
        border-radius: 100px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all .15s;
    }

    .btn-wa-cancel:hover { background: #f4f6fb; }

    .btn-wa-generate {
        padding: 10px 24px;
        background: #25D366;
        color: #fff;
        border: none;
        border-radius: 100px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all .15s;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .btn-wa-generate:hover { background: #1fb855; }
    .btn-wa-generate:disabled { opacity: .6; cursor: not-allowed; }

    .wa-modal-actions {
        display: flex;
        gap: 8px;
        margin-top: auto;
        padding-top: 16px;
    }

    @media (max-width: 640px) {
        #waQrModal .wa-modal {
            flex-direction: column;
            max-width: 420px;
            gap: 20px;
        }
        .wa-modal-right { width: 100%; }
        .wa-qr-area { margin: 0 auto 12px; }
    }

    .integration-title {
        flex: 1;
    }

    .integration-title h3 {
        font-size: 15px;
        font-weight: 700;
        color: #1a1d23;
        margin: 0 0 3px;
    }

    .integration-title p {
        font-size: 12px;
        color: #9ca3af;
        margin: 0;
    }

    .conn-badge {
        font-size: 11.5px;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 99px;
        white-space: nowrap;
    }

    .conn-active   { background: #d1fae5; color: #065f46; }
    .conn-expired  { background: #fef3c7; color: #92400e; }
    .conn-revoked,
    .conn-none     { background: #f3f4f6; color: #6b7280; }

    .integration-body {
        padding: 18px 24px;
    }

    .conn-detail {
        font-size: 13px;
        color: #374151;
        margin-bottom: 16px;
    }

    .conn-detail strong { color: #1a1d23; }
    .conn-detail span   { color: #9ca3af; font-size: 12px; }

    .integration-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .btn-connect {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 9px 18px;
        background: #0085f3;
        color: #fff;
        border: none;
        border-radius: 100px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        transition: background .15s;
    }

    .btn-connect:hover { background: #0070d1; color: #fff; }

    .btn-sync {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        background: #fff;
        color: #374151;
        border: 1.5px solid #e8eaf0;
        border-radius: 100px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all .15s;
    }

    .btn-sync:hover { background: #f0f4ff; border-color: #dbeafe; color: #0085f3; }

    .btn-disconnect {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        background: #fff;
        color: #EF4444;
        border: 1.5px solid #fecaca;
        border-radius: 100px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all .15s;
    }

    .btn-disconnect:hover { background: #fef2f2; }

    /* ── WhatsApp Instances (dentro do card) ───────────────────── */
    .wa-instance-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        background: #f8fafc;
        border-radius: 8px;
        margin-bottom: 8px;
    }
    .wa-instance-item:last-child { margin-bottom: 0; }

    .wa-dot {
        width: 9px;
        height: 9px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .wa-dot.connected {
        background: #10b981;
        animation: pulse-green 2s ease-in-out infinite;
    }
    .wa-dot.qr       { background: #f59e0b; animation: pulse-yellow 2s ease-in-out infinite; }
    .wa-dot.offline   { background: #d1d5db; }

    @keyframes pulse-green {
        0%, 100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, .5); }
        50%      { box-shadow: 0 0 0 5px rgba(16, 185, 129, 0); }
    }
    @keyframes pulse-yellow {
        0%, 100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, .5); }
        50%      { box-shadow: 0 0 0 5px rgba(245, 158, 11, 0); }
    }

    .wa-instance-detail {
        flex: 1;
        min-width: 0;
    }

    .wa-label-wrap {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .wa-label-input {
        border: 1px solid #e5e7eb;
        background: #fff;
        font-size: 12.5px;
        font-weight: 600;
        color: #1a1d23;
        padding: 3px 8px;
        border-radius: 6px;
        width: 100%;
        max-width: 160px;
        outline: none;
        transition: border-color .15s;
    }
    .wa-label-input:focus { border-color: #0085f3; }
    .wa-label-input::placeholder { color: #b0b7c3; font-weight: 400; font-style: italic; }

    .wa-edit-icon {
        color: #9ca3af;
        font-size: 11px;
        flex-shrink: 0;
    }

    .wa-instance-phone {
        font-size: 11.5px;
        color: #6b7280;
        display: block;
        margin-top: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .wa-instance-actions {
        display: flex;
        gap: 6px;
        flex-shrink: 0;
    }
</style>
@endpush

@section('content')
<div class="page-container">

    @include('tenant.settings._tabs')

    <div class="integrations-grid">

        {{-- ─── WhatsApp ─────────────────────────────────────────────────── --}}
        @if($enabledIntegrations['whatsapp'])
        <div class="integration-card">
            <div class="integration-header">
                <div class="integration-logo whatsapp">
                    <i class="bi bi-whatsapp" style="font-size:20px;"></i>
                </div>
                <div class="integration-title">
                    <h3>{{ __('integrations.wa_title') }}</h3>
                    <p>{{ __('integrations.wa_subtitle') }}</p>
                </div>
                @php
                    $waConnected = $whatsappInstances->where('status', 'connected')->count();
                @endphp
                @if($waConnected > 0)
                    <span class="conn-badge conn-active">{{ __('integrations.wa_active', ['count' => $waConnected]) }}</span>
                @else
                    <span class="conn-badge conn-none">{{ __('integrations.wa_disconnected') }}</span>
                @endif
            </div>
            <div class="integration-body">
                <ul class="integration-features">
                    <li>{{ __('integrations.wa_feat_1') }}</li>
                    <li>{{ __('integrations.wa_feat_2') }}</li>
                    <li>{{ __('integrations.wa_feat_3') }}</li>
                    <li>{{ __('integrations.wa_feat_4') }}</li>
                </ul>

                {{-- Instâncias conectadas --}}
                <div id="waInstancesList" style="margin-bottom:16px;">
                @foreach($whatsappInstances as $inst)
                    <div class="wa-instance-item" data-instance-id="{{ $inst->id }}">
                        <span class="wa-dot {{ $inst->status === 'connected' ? 'connected' : ($inst->status === 'qr' ? 'qr' : 'offline') }}"></span>
                        <div class="wa-instance-detail">
                            <div class="wa-label-wrap">
                                <input type="text" class="wa-label-input" value="{{ $inst->label ?? '' }}"
                                       placeholder="{{ __('integrations.wa_label_ph') }}"
                                       data-instance-id="{{ $inst->id }}" onblur="saveWaLabel(this)">
                                <i class="bi bi-pencil wa-edit-icon"></i>
                            </div>
                            <span class="wa-instance-phone">{{ $inst->phone_number ?? $inst->display_name ?? $inst->session_name }}</span>
                        </div>
                        <div class="wa-instance-actions">
                            @if($inst->status === 'connected')
                                <button class="btn-sync" style="padding:5px 12px;font-size:11.5px;" onclick="openImportModal({{ $inst->id }})" title="{{ __('integrations.wa_import') }}">
                                    <i class="bi bi-cloud-download"></i> {{ __('integrations.wa_import') }}
                                </button>
                                <button class="btn-disconnect" style="padding:5px 12px;font-size:11.5px;" onclick="disconnectWhatsapp(this, {{ $inst->id }})">
                                    <i class="bi bi-x-circle"></i> {{ __('integrations.wa_disconnect') }}
                                </button>
                            @elseif($inst->status === 'qr')
                                <button class="btn-connect" style="padding:5px 12px;font-size:11.5px;" onclick="openWaModal({{ $inst->id }})">
                                    <i class="bi bi-qr-code"></i> {{ __('integrations.wa_qr') }}
                                </button>
                                <button class="btn-disconnect" style="padding:5px 8px;font-size:11.5px;" onclick="deleteWhatsappInstance(this, {{ $inst->id }})">
                                    <i class="bi bi-trash"></i>
                                </button>
                            @else
                                <button class="btn-connect" style="padding:5px 12px;font-size:11.5px;" onclick="reconnectWhatsapp(this, {{ $inst->id }})">
                                    <i class="bi bi-arrow-clockwise"></i> {{ __('integrations.wa_reconnect') }}
                                </button>
                                <button class="btn-disconnect" style="padding:5px 8px;font-size:11.5px;" onclick="deleteWhatsappInstance(this, {{ $inst->id }})">
                                    <i class="bi bi-trash"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
                </div>

                <div class="integration-actions">
                    @if($whatsappInstancesRemain === null || $whatsappInstancesRemain > 0)
                        <button class="btn-connect" id="btnAddWaNumber" onclick="startWhatsappConnect(this)">
                            <i class="bi bi-plus-lg"></i> {{ __('integrations.wa_add_number') }}
                        </button>
                    @else
                        <span class="btn-coming-soon">
                            <i class="bi bi-lock"></i> {{ __('integrations.wa_limit', ['max' => $maxWhatsappInstances]) }}
                        </span>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- ─── Google Calendar ──────────────────────────────────────────── --}}
        @if($enabledIntegrations['google_calendar'])
        @php
            $hasCalendar   = $google && $google->status === 'active'
                             && (in_array('https://www.googleapis.com/auth/calendar.events', (array) ($google->scopes_json ?? []), true)
                              || in_array('https://www.googleapis.com/auth/calendar', (array) ($google->scopes_json ?? []), true));
            $needsReconnect = $google && $google->status === 'active' && !$hasCalendar;
        @endphp
        <div class="integration-card">
            <div class="integration-header">
                <div class="integration-logo google">G</div>
                <div class="integration-title">
                    <h3>{{ __('integrations.gcal_title') }}</h3>
                    <p>{{ __('integrations.gcal_subtitle') }}</p>
                </div>
                @if($hasCalendar)
                    <span class="conn-badge conn-active">{{ __('integrations.gcal_connected') }}</span>
                @elseif($needsReconnect)
                    <span class="conn-badge conn-expired">{{ __('integrations.gcal_reconnect_badge') }}</span>
                @else
                    <span class="conn-badge conn-none">{{ __('integrations.gcal_disconnected') }}</span>
                @endif
            </div>
            <div class="integration-body">
                <ul class="integration-features">
                    <li>{{ __('integrations.gcal_feat_1') }}</li>
                    <li>{{ __('integrations.gcal_feat_2') }}</li>
                    <li>{{ __('integrations.gcal_feat_3') }}</li>
                    <li>{{ __('integrations.gcal_feat_4') }}</li>
                </ul>

                @if($hasCalendar && $google)
                <div class="conn-detail">
                    <strong>{{ $google->platform_user_name ?? __('integrations.gcal_default_name') }}</strong><br>
                    <span>{{ __('integrations.gcal_ai_hint') }}</span>
                </div>
                @elseif($needsReconnect)
                <div class="conn-detail" style="color:#b45309;">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    {{ __('integrations.gcal_needs_reconnect') }}
                </div>
                @else
                <div class="conn-detail" style="color:#9ca3af;">
                    {{ __('integrations.gcal_not_connected') }}
                </div>
                @endif

                <div class="integration-actions">
                    @if($hasCalendar)
                        <a href="{{ route('calendar.index') }}" class="btn-sync" style="text-decoration:none;">
                            <i class="bi bi-calendar3"></i> {{ __('integrations.gcal_open') }}
                        </a>
                        <button class="btn-disconnect" onclick="disconnectPlatform('google', this)">
                            <i class="bi bi-x-circle"></i> {{ __('integrations.gcal_disconnect') }}
                        </button>
                    @else
                        <a href="{{ route('settings.integrations.google.redirect') }}" class="btn-connect">
                            <i class="bi bi-google"></i> {{ $needsReconnect ? __('integrations.gcal_reconnect') : __('integrations.gcal_connect') }}
                        </a>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- ─── Instagram ──────────────────────────────────────────────── --}}
        @if($enabledIntegrations['instagram'])
        <div class="integration-card">
            <div class="integration-header">
                <div class="integration-logo instagram">
                    <i class="bi bi-instagram" style="font-size:20px;"></i>
                </div>
                <div class="integration-title">
                    <h3>{{ __('integrations.ig_title') }}</h3>
                    <p>{{ __('integrations.ig_subtitle') }}</p>
                </div>
                @if($instagram && $instagram->status === 'connected')
                    <span class="conn-badge conn-active">{{ __('integrations.ig_connected') }}</span>
                @elseif($instagram)
                    <span class="conn-badge conn-expired">{{ __('integrations.ig_reconnect') }}</span>
                @else
                    <span class="conn-badge conn-none">{{ __('integrations.ig_disconnected') }}</span>
                @endif
            </div>
            <div class="integration-body">
                <ul class="integration-features">
                    <li>{{ __('integrations.ig_feat_1') }}</li>
                    <li>{{ __('integrations.ig_feat_2') }}</li>
                    <li>{{ __('integrations.ig_feat_3') }}</li>
                    <li>{{ __('integrations.ig_feat_4') }}</li>
                </ul>
                @if($instagram)
                <div class="conn-detail">
                    <strong>{{ $instagram->username ?? __('integrations.ig_default_name') }}</strong><br>
                    <span>{{ __('integrations.ig_connected') }} {{ $instagram->updated_at?->diffForHumans() ?? '' }}</span>
                </div>
                @else
                <div class="conn-detail" style="color:#9ca3af;">
                    {{ __('integrations.ig_not_connected') }}
                </div>
                @endif
                <div class="integration-actions">
                    @if($instagram)
                        <button class="btn-disconnect" onclick="disconnectInstagram(this)">
                            <i class="bi bi-x-circle"></i> {{ __('integrations.ig_disconnect') }}
                        </button>
                    @else
                        <a href="{{ route('settings.integrations.instagram.redirect') }}" class="btn-connect">
                            <i class="bi bi-instagram"></i> {{ __('integrations.ig_connect') }}
                        </a>
                    @endif
                </div>
            </div>
        </div>
        @endif

    {{-- ─── Botão WhatsApp (rastreamento de cliques) ──────────────────── --}}
    @php $waBtn = $waButtons->first(); @endphp
    <div class="integration-card">
        <div class="integration-header">
            <div class="integration-logo" style="background:#dcfce7;color:#25D366;">
                <i class="bi bi-chat-dots-fill" style="font-size:20px;"></i>
            </div>
            <div class="integration-title">
                <h3>{{ __('integrations.wabtn_title') }}</h3>
                <p>{{ __('integrations.wabtn_subtitle') }}</p>
            </div>
            @if($waBtn && $waBtn->is_active)
                <span class="conn-badge conn-active">{{ __('integrations.wabtn_active') }}</span>
            @else
                <span class="conn-badge conn-none">{{ __('integrations.wabtn_inactive') }}</span>
            @endif
        </div>
        <div class="integration-body">
            <ul class="integration-features">
                <li>{{ __('integrations.wabtn_feat_1') }}</li>
                <li>{{ __('integrations.wabtn_feat_2') }}</li>
                <li>{{ __('integrations.wabtn_feat_3') }}</li>
                <li>{{ __('integrations.wabtn_feat_4') }}</li>
            </ul>

            @if($waBtn)
                @php
                    $clicks7d = $waBtn->clicks()->where('clicked_at', '>=', now()->subDays(7))->count();
                @endphp
                <div class="conn-detail">
                    <strong>{{ $waBtn->phone_number }}</strong><br>
                    <span>{{ __('integrations.wabtn_clicks_7d', ['count' => $clicks7d]) }}</span>
                </div>
            @else
                <div class="conn-detail" style="color:#9ca3af;">{{ __('integrations.wabtn_no_button') }}</div>
            @endif

            <div class="integration-actions">
                <button class="btn-connect" style="background:#25D366;" onclick="openWaBtnDrawer()">
                    <i class="bi bi-{{ $waBtn ? 'gear' : 'plus-lg' }}"></i> {{ $waBtn ? __('integrations.wabtn_configure') : __('integrations.wabtn_create') }}
                </button>
                @if($waBtn)
                <button class="btn-disconnect" onclick="deleteWaButton()">
                    <i class="bi bi-trash"></i> {{ __('integrations.wabtn_remove') }}
                </button>
                @endif
            </div>
        </div>
    </div>

    {{-- ─── Drawer Botão WhatsApp ──────────────────────────────────────── --}}
    <div id="waBtnOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);z-index:300;" onclick="closeWaBtnDrawer()"></div>
    <div id="waBtnDrawer" style="position:fixed;top:0;right:-500px;width:480px;height:100%;background:#fff;z-index:301;box-shadow:-4px 0 20px rgba(0,0,0,0.1);transition:right .3s cubic-bezier(.4,0,.2,1);display:flex;flex-direction:column;">
        <div style="padding:20px 24px;border-bottom:1px solid #f0f2f7;display:flex;align-items:center;justify-content:space-between;">
            <h4 style="margin:0;font-size:16px;font-weight:700;color:#1a1d23;">{{ __('integrations.wabtn_drawer_title') }}</h4>
            <button onclick="closeWaBtnDrawer()" style="background:none;border:none;font-size:20px;color:#9ca3af;cursor:pointer;padding:4px;"><i class="bi bi-x-lg"></i></button>
        </div>
        <div style="flex:1;overflow-y:auto;padding:20px 24px;">
            <div style="margin-bottom:14px;">
                <label style="font-size:12.5px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">{{ __('integrations.wabtn_phone') }}</label>
                <input type="text" id="waBtnPhone" class="form-control" placeholder="{{ __('integrations.wabtn_phone_ph') }}" value="{{ $waBtn->phone_number ?? '' }}" style="font-size:13px;">
            </div>
            <div style="margin-bottom:14px;">
                <label style="font-size:12.5px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">{{ __('integrations.wabtn_label') }}</label>
                <input type="text" id="waBtnLabel" class="form-control" placeholder="{{ __('integrations.wabtn_label_ph') }}" value="{{ $waBtn->button_label ?? __('integrations.wabtn_label_ph') }}" style="font-size:13px;">
            </div>
            <div style="margin-bottom:14px;">
                <label style="font-size:12.5px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">{{ __('integrations.wabtn_message') }}</label>
                <textarea id="waBtnMessage" class="form-control" rows="3" placeholder="{{ __('integrations.wabtn_message_ph') }}" style="font-size:13px;resize:vertical;">{{ $waBtn->default_message ?? __('integrations.wabtn_message_ph') }}</textarea>
            </div>
            <div style="margin-bottom:20px;display:flex;align-items:center;gap:8px;">
                <input type="checkbox" id="waBtnFloating" {{ ($waBtn->show_floating ?? true) ? 'checked' : '' }} style="width:16px;height:16px;">
                <label for="waBtnFloating" style="font-size:12.5px;color:#374151;cursor:pointer;">{{ __('integrations.wabtn_floating') }}</label>
            </div>

            @if($waBtn)
            <div style="padding-top:16px;border-top:1px solid #f0f2f7;">
                <label style="font-size:13px;font-weight:700;color:#1a1d23;display:block;margin-bottom:6px;"><i class="bi bi-code-slash"></i> {{ __('integrations.wabtn_embed') }}</label>
                <p style="font-size:11.5px;color:#6b7280;margin-bottom:8px;">Cole antes do <code style="background:#f1f5f9;padding:1px 5px;border-radius:3px;font-size:10.5px;">&lt;/body&gt;</code> do seu site. Para botão inline: <code style="background:#f1f5f9;padding:1px 5px;border-radius:3px;font-size:10.5px;">&lt;div class="syncro-wa-inline"&gt;&lt;/div&gt;</code></p>
                <div style="position:relative;">
                    <textarea id="waBtnEmbed" readonly onclick="this.select()" style="width:100%;height:50px;font-family:monospace;font-size:11.5px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:10px 70px 10px 10px;resize:none;color:#334155;">&lt;script src="{{ rtrim(config('app.url'), '/') }}/api/widget/{{ $waBtn->website_token }}/wa-button.js"&gt;&lt;/script&gt;</textarea>
                    <button onclick="navigator.clipboard.writeText(document.getElementById('waBtnEmbed').value.replace(/&lt;/g,'<').replace(/&gt;/g,'>'));toastr.success(ILANG.toast_copied)" style="position:absolute;top:8px;right:8px;background:#0085f3;color:#fff;border:none;border-radius:6px;padding:4px 10px;font-size:11px;font-weight:600;cursor:pointer;"><i class="bi bi-clipboard"></i> {{ __('integrations.wabtn_copy') }}</button>
                </div>
            <div style="margin-top:16px;padding-top:16px;border-top:1px solid #f0f2f7;">
                <label style="font-size:13px;font-weight:700;color:#1a1d23;display:block;margin-bottom:6px;"><i class="bi bi-link-45deg"></i> {{ __('integrations.wabtn_tracking') }}</label>
                <p style="font-size:11.5px;color:#6b7280;margin-bottom:8px;">{{ __('integrations.wabtn_tracking_hint') }}</p>
                <div style="position:relative;">
                    <input type="text" id="waBtnTrackLink" readonly onclick="this.select()" value="{{ rtrim(config('app.url'), '/') }}/wa/{{ $waBtn->website_token }}" style="width:100%;font-family:monospace;font-size:11.5px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:10px 70px 10px 10px;color:#334155;">
                    <button onclick="navigator.clipboard.writeText(document.getElementById('waBtnTrackLink').value);toastr.success(ILANG.toast_link_copied)" style="position:absolute;top:6px;right:8px;background:#0085f3;color:#fff;border:none;border-radius:6px;padding:4px 10px;font-size:11px;font-weight:600;cursor:pointer;"><i class="bi bi-clipboard"></i> {{ __('integrations.wabtn_copy') }}</button>
                </div>
                <div style="margin-top:8px;padding:10px 12px;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;">
                    <div style="font-size:11px;font-weight:600;color:#92400e;margin-bottom:4px;">{{ __('integrations.wabtn_google_example') }}</div>
                    <code style="font-size:10px;color:#78350f;word-break:break-all;">{{ rtrim(config('app.url'), '/') }}/wa/{{ $waBtn->website_token }}?utm_source=google&utm_medium=cpc&utm_campaign=@{{campaignid}}&utm_term=@{{keyword}}&gclid=@{{gclid}}</code>
                </div>
            </div>

                @php
                    $clicksToday = $waBtn->clicks()->whereDate('clicked_at', today())->count();
                    $clicks7d_ = $waBtn->clicks()->where('clicked_at', '>=', now()->subDays(7))->count();
                    $clicks30d = $waBtn->clicks()->where('clicked_at', '>=', now()->subDays(30))->count();
                @endphp
                <div style="margin-top:16px;display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;">
                    <div style="text-align:center;padding:10px 8px;background:#f0fdf4;border-radius:8px;">
                        <div style="font-size:18px;font-weight:700;color:#16a34a;">{{ $clicksToday }}</div>
                        <div style="font-size:10.5px;color:#6b7280;">{{ __('integrations.wabtn_today') }}</div>
                    </div>
                    <div style="text-align:center;padding:10px 8px;background:#eff6ff;border-radius:8px;">
                        <div style="font-size:18px;font-weight:700;color:#0085f3;">{{ $clicks7d_ }}</div>
                        <div style="font-size:10.5px;color:#6b7280;">{{ __('integrations.wabtn_7d') }}</div>
                    </div>
                    <div style="text-align:center;padding:10px 8px;background:#f5f3ff;border-radius:8px;">
                        <div style="font-size:18px;font-weight:700;color:#8B5CF6;">{{ $clicks30d }}</div>
                        <div style="font-size:10.5px;color:#6b7280;">{{ __('integrations.wabtn_30d') }}</div>
                    </div>
                </div>
            </div>
            @endif
        </div>
        <div style="padding:16px 24px;border-top:1px solid #f0f2f7;display:flex;gap:8px;justify-content:flex-end;">
            <button onclick="closeWaBtnDrawer()" style="padding:8px 20px;border:1px solid #e2e8f0;background:#fff;border-radius:100px;font-size:13px;cursor:pointer;color:#374151;">{{ __('integrations.wabtn_cancel') }}</button>
            <button onclick="saveWaButton()" style="padding:8px 20px;background:#25D366;color:#fff;border:none;border-radius:100px;font-size:13px;font-weight:600;cursor:pointer;"><i class="bi bi-check-lg"></i> {{ __('integrations.wabtn_save') }}</button>
        </div>
    </div>

    </div>{{-- fecha integrations-grid --}}

</div>{{-- fecha page-container --}}

{{-- ─── Modal QR WhatsApp ──────────────────────────────────────────── --}}
<div id="waQrModal" class="wa-modal-overlay">
    <div class="wa-modal">
        {{-- Coluna esquerda: info + input --}}
        <div class="wa-modal-left">
            <h4><i class="bi bi-whatsapp" style="color:#25D366;margin-right:6px;"></i>{{ __('integrations.qr_title') }}</h4>
            <p class="wa-subtitle">{{ __('integrations.qr_subtitle') }}</p>

            <label for="waLabelInput">{{ __('integrations.wa_label_field') }}</label>
            <input type="text" id="waLabelInput" placeholder="{{ __('integrations.wa_label_placeholder') }}" maxlength="60">
            <p style="font-size:11.5px;color:#9ca3af;margin:6px 0 18px;">{{ __('integrations.wa_label_hint') }}</p>

            <ol class="wa-steps">
                <li>{!! __('integrations.qr_step_1') !!}</li>
                <li>{!! __('integrations.qr_step_2') !!}</li>
                <li>{!! __('integrations.qr_step_3') !!}</li>
                <li>{!! __('integrations.qr_step_4') !!}</li>
            </ol>

            <div class="wa-modal-actions" id="waModalActions">
                <button class="btn-wa-cancel" onclick="closeWaModal()">{{ __('integrations.qr_cancel') }}</button>
                <button class="btn-wa-generate" id="btnWaGenerate" onclick="generateWaQr()">
                    <i class="bi bi-qr-code"></i> {{ __('integrations.wa_generate_qr') }}
                </button>
            </div>
        </div>

        {{-- Coluna direita: QR code --}}
        <div class="wa-modal-right">
            <div class="wa-qr-area" id="waQrArea">
                <div class="wa-qr-placeholder">
                    <i class="bi bi-qr-code-scan"></i>
                    <span>{{ __('integrations.wa_qr_placeholder') }}</span>
                </div>
            </div>
            <p id="waQrStatus"></p>
        </div>
    </div>
</div>

{{-- ─── Modal Importar Histórico ──────────────────────────────────── --}}
<div id="waImportModal" class="wa-modal-overlay">
    <div class="wa-modal" style="max-width:420px;">
        {{-- Estado 1: Configuração --}}
        <div id="importConfigState">
            <h4 style="margin:0 0 4px;font-size:16px;font-weight:700;color:#1a1d23;">
                <i class="bi bi-cloud-download" style="color:#0085f3;margin-right:6px;"></i>{{ __('integrations.import_title') }}
            </h4>
            <p style="font-size:13px;color:#6b7280;margin:0 0 18px;">{{ __('integrations.import_subtitle') }}</p>

            <div style="text-align:left;margin-bottom:20px;">
                <label style="font-size:13px;font-weight:600;color:#1a1d23;display:block;margin-bottom:6px;">{{ __('integrations.import_period') }}</label>
                <select id="importDaysSelect" style="width:100%;padding:10px 12px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:13px;color:#374151;background:#fff;outline:none;">
                    <option value="7">{{ __('integrations.import_7d') }}</option>
                    <option value="15">{{ __('integrations.import_15d') }}</option>
                    <option value="30" selected>{{ __('integrations.import_30d') }}</option>
                </select>
                <p style="font-size:11.5px;color:#9ca3af;margin:8px 0 0;">{{ __('integrations.import_help') }}</p>
            </div>

            <div style="display:flex;gap:8px;justify-content:center;">
                <button class="btn-wa-cancel" onclick="closeImportModal()">{{ __('integrations.import_cancel') }}</button>
                <button class="btn-connect" id="btnStartImport" onclick="startImport()">
                    <i class="bi bi-cloud-download"></i> {{ __('integrations.import_btn') }}
                </button>
            </div>
        </div>

        {{-- Estado 2: Progresso --}}
        <div id="importProgressState" style="display:none;">
            <h4 id="importProgressTitle" style="margin:0 0 4px;font-size:16px;font-weight:700;color:#1a1d23;">
                <i class="bi bi-arrow-clockwise spin" style="color:#0085f3;margin-right:6px;"></i>{{ __('integrations.import_progress') }}
            </h4>
            <p id="importProgressSubtitle" style="font-size:13px;color:#6b7280;margin:0 0 18px;">{{ __('integrations.import_progress_sub') }}</p>

            {{-- Barra de progresso --}}
            <div style="background:#f3f4f6;border-radius:8px;height:10px;overflow:hidden;margin-bottom:18px;">
                <div id="importProgressBar" style="height:100%;background:#0085f3;border-radius:8px;transition:width .5s ease;width:0%;"></div>
            </div>

            {{-- Contadores --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px;">
                <div style="background:#f0f7ff;border-radius:10px;padding:12px 14px;text-align:center;">
                    <div style="font-size:22px;font-weight:700;color:#0085f3;" id="importCountChats">0</div>
                    <div style="font-size:11px;color:#6b7280;font-weight:600;">{{ __('integrations.import_conversations') }}</div>
                </div>
                <div style="background:#ecfdf5;border-radius:10px;padding:12px 14px;text-align:center;">
                    <div style="font-size:22px;font-weight:700;color:#059669;" id="importCountMessages">0</div>
                    <div style="font-size:11px;color:#6b7280;font-weight:600;">{{ __('integrations.import_messages') }}</div>
                </div>
                <div style="background:#fef3c7;border-radius:10px;padding:12px 14px;text-align:center;">
                    <div style="font-size:22px;font-weight:700;color:#d97706;" id="importCountSkipped">0</div>
                    <div style="font-size:11px;color:#6b7280;font-weight:600;">{{ __('integrations.import_duplicates') }}</div>
                </div>
                <div style="background:#f3f4f6;border-radius:10px;padding:12px 14px;text-align:center;">
                    <div style="font-size:22px;font-weight:700;color:#374151;" id="importCountTime">0:00</div>
                    <div style="font-size:11px;color:#6b7280;font-weight:600;">{{ __('integrations.import_time') }}</div>
                </div>
            </div>

            {{-- Chat atual --}}
            <div id="importCurrentChat" style="font-size:12px;color:#9ca3af;text-align:center;margin-bottom:16px;min-height:18px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"></div>

            {{-- Botão fechar --}}
            <div style="text-align:center;">
                <button class="btn-wa-cancel" id="importCloseBtn" onclick="closeImportModal()">{{ __('integrations.import_close') }}</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
const ILANG = @json(__('integrations'));
const SYNC_URL            = @json(route('settings.integrations.sync',       ['platform' => '__P__']));
const DISCONNECT_URL      = @json(route('settings.integrations.disconnect', ['platform' => '__P__']));
const WA_CONNECT_URL      = @json(route('settings.integrations.whatsapp.connect'));
const WA_BASE_URL         = @json(rtrim(route('settings.integrations.whatsapp.connect'), '/connect'));
const IG_DISCONNECT_URL   = @json(route('settings.integrations.instagram.disconnect'));

let waQrPollInterval = null;
let waQrNullCount    = 0;
let waCurrentInstanceId = null;
let waConnected      = false;

// ── WhatsApp ──────────────────────────────────────────────────────────────────

function startWhatsappConnect(btn) {
    // Abre o modal primeiro — POST só acontece ao clicar "Gerar QR"
    waConnected = false;
    waCurrentInstanceId = null;
    document.getElementById('waLabelInput').value = '';
    document.getElementById('waLabelInput').readOnly = false;
    resetWaModalUi();
    document.getElementById('waQrModal').classList.add('open');
}

function resetWaModalUi() {
    // Reset QR area to placeholder
    document.getElementById('waQrArea').innerHTML =
        '<div class="wa-qr-placeholder"><i class="bi bi-qr-code-scan"></i><span>' + (ILANG.wa_qr_placeholder || 'QR Code') + '</span></div>';
    document.getElementById('waQrStatus').textContent = '';
    document.getElementById('waQrStatus').className = '';

    // Reset actions: cancelar + gerar QR
    const actions = document.getElementById('waModalActions');
    actions.innerHTML =
        '<button class="btn-wa-cancel" onclick="closeWaModal()">' + ILANG.qr_cancel + '</button>' +
        '<button class="btn-wa-generate" id="btnWaGenerate" onclick="generateWaQr()">' +
        '<i class="bi bi-qr-code"></i> ' + (ILANG.wa_generate_qr || 'Gerar QR Code') + '</button>';

    // Remove retry button if exists
    const retry = document.getElementById('btnWaRetry');
    if (retry) retry.remove();
}

async function generateWaQr() {
    const btn = document.getElementById('btnWaGenerate');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-arrow-clockwise spin"></i> ' + (ILANG.import_connecting || 'Conectando...');

    const label = document.getElementById('waLabelInput').value.trim();

    try {
        const res = await fetch(WA_CONNECT_URL, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ label }),
        });
        const data = await res.json();

        if (data.success) {
            waCurrentInstanceId = data.instance_id;
            document.getElementById('waLabelInput').readOnly = true;

            // Trocar botões: só cancelar enquanto polling
            const actions = document.getElementById('waModalActions');
            actions.innerHTML = '<button class="btn-wa-cancel" onclick="closeWaModal()">' + ILANG.qr_cancel + '</button>';

            // Iniciar polling QR
            document.getElementById('waQrArea').innerHTML = '<i class="bi bi-arrow-clockwise spin" style="font-size:36px;color:#9ca3af;"></i>';
            document.getElementById('waQrStatus').textContent = ILANG.qr_waiting;
            waQrNullCount = 0;
            clearInterval(waQrPollInterval);
            pollWaQr();
            waQrPollInterval = setInterval(pollWaQr, 3000);
        } else {
            toastr.error(data.message || ILANG.toast_connect_error);
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-qr-code"></i> ' + (ILANG.wa_generate_qr || 'Gerar QR Code');
        }
    } catch (e) {
        toastr.error(ILANG.toast_conn_error);
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-qr-code"></i> ' + (ILANG.wa_generate_qr || 'Gerar QR Code');
    }
}

function openWaModal(instanceId) {
    // Usado pelo reconnect — já tem instância, vai direto pro QR
    waConnected = false;
    waCurrentInstanceId = instanceId;
    document.getElementById('waLabelInput').value = '';
    document.getElementById('waLabelInput').readOnly = true;
    document.getElementById('waQrModal').classList.add('open');

    const actions = document.getElementById('waModalActions');
    actions.innerHTML = '<button class="btn-wa-cancel" onclick="closeWaModal()">' + ILANG.qr_cancel + '</button>';

    document.getElementById('waQrArea').innerHTML = '<i class="bi bi-arrow-clockwise spin" style="font-size:36px;color:#9ca3af;"></i>';
    document.getElementById('waQrStatus').textContent = ILANG.qr_waiting;
    document.getElementById('waQrStatus').className = '';

    waQrNullCount = 0;
    clearInterval(waQrPollInterval);
    pollWaQr();
    waQrPollInterval = setInterval(pollWaQr, 3000);
}

function closeWaModal() {
    clearInterval(waQrPollInterval);
    document.getElementById('waQrModal').classList.remove('open');
}

async function pollWaQr() {
    if (!waCurrentInstanceId) return;
    try {
        const res  = await fetch(`${WA_BASE_URL}/${waCurrentInstanceId}/qr`, {
            headers: { 'Accept': 'application/json' }
        });
        const data = await res.json();

        if (data.status === 'connected') {
            clearInterval(waQrPollInterval);
            waConnected = true;
            document.getElementById('waQrArea').innerHTML = '<i class="bi bi-check-circle-fill" style="font-size:64px;color:#25D366;"></i>';
            const st = document.getElementById('waQrStatus');
            st.textContent = ILANG.qr_connected;
            st.className = 'connected';

            // Trocar ações: só botão fechar
            const actions = document.getElementById('waModalActions');
            actions.innerHTML = '<button class="btn-wa-generate" onclick="closeWaModal(); location.reload();">' +
                '<i class="bi bi-check-lg"></i> ' + (ILANG.qr_close || 'Fechar') + '</button>';

            // Auto-reload em 2.5s caso não feche manualmente
            setTimeout(() => location.reload(), 2500);
        } else if (data.qr_base64) {
            waQrNullCount = 0;
            document.getElementById('waQrArea').innerHTML = `<img src="data:image/png;base64,${data.qr_base64}" alt="QR Code">`;
            document.getElementById('waQrStatus').textContent = ILANG.qr_scan_now;
        } else if (data.status === 'disconnected' || ++waQrNullCount >= 5) {
            clearInterval(waQrPollInterval);
            document.getElementById('waQrArea').innerHTML =
                '<i class="bi bi-x-circle-fill" style="font-size:48px;color:#ef4444;margin-bottom:12px;display:block;"></i>';
            const st = document.getElementById('waQrStatus');
            st.textContent = ILANG.qr_expired;
            st.className = 'error';
            if (!document.getElementById('btnWaRetry')) {
                st.insertAdjacentHTML('afterend',
                    '<button id="btnWaRetry" style="margin-top:12px;padding:8px 20px;background:#25D366;color:#fff;border:none;border-radius:100px;cursor:pointer;font-weight:600;">'
                    + '<i class="bi bi-arrow-clockwise"></i> ' + ILANG.qr_retry + '</button>');
                document.getElementById('btnWaRetry').addEventListener('click', async () => {
                    document.getElementById('btnWaRetry').remove();
                    document.getElementById('waQrArea').innerHTML = '<i class="bi bi-arrow-clockwise spin" style="font-size:36px;color:#9ca3af;"></i>';
                    document.getElementById('waQrStatus').textContent = ILANG.qr_waiting;
                    document.getElementById('waQrStatus').className = '';
                    waQrNullCount = 0;
                    clearInterval(waQrPollInterval);
                    pollWaQr();
                    waQrPollInterval = setInterval(pollWaQr, 3000);
                });
            }
        }
    } catch (e) {
        // Silenciar erros de polling
    }
}

async function reconnectWhatsapp(btn, instanceId) {
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-arrow-clockwise spin"></i>';
    openWaModal(instanceId);
}

async function disconnectWhatsapp(btn, instanceId) {
    confirmAction({
        title: ILANG.confirm_wa_disc_title,
        message: ILANG.confirm_wa_disc_msg,
        confirmText: ILANG.confirm_wa_disc_btn,
        onConfirm: async () => {
            btn.disabled = true;
            try {
                const res  = await fetch(`${WA_BASE_URL}/${instanceId}/disconnect`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();
                if (data.success) {
                    toastr.success(ILANG.toast_wa_disconnected);
                    setTimeout(() => location.reload(), 1200);
                } else {
                    toastr.error(ILANG.toast_disconnect_error);
                    btn.disabled = false;
                }
            } catch (e) {
                toastr.error(ILANG.toast_conn_error);
                btn.disabled = false;
            }
        },
    });
}

async function deleteWhatsappInstance(btn, instanceId) {
    confirmAction({
        title: ILANG.confirm_wa_remove_title,
        message: ILANG.confirm_wa_remove_msg,
        confirmText: ILANG.confirm_wa_remove_btn,
        onConfirm: async () => {
            btn.disabled = true;
            try {
                const res  = await fetch(`${WA_BASE_URL}/${instanceId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();
                if (data.success) {
                    toastr.success(ILANG.toast_number_removed);
                    setTimeout(() => location.reload(), 1200);
                } else {
                    toastr.error(ILANG.toast_remove_error);
                    btn.disabled = false;
                }
            } catch (e) {
                toastr.error(ILANG.toast_conn_error);
                btn.disabled = false;
            }
        },
    });
}

async function saveWaLabel(input) {
    const instanceId = input.dataset.instanceId;
    const label = input.value.trim();
    if (!label) return;

    try {
        await fetch(`${WA_BASE_URL}/${instanceId}`, {
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ label }),
        });
    } catch (e) {
        // silenciar
    }
}

// Fechar modal clicando no overlay
document.getElementById('waQrModal').addEventListener('click', function(e) {
    if (e.target === this) closeWaModal();
});

// ── Import histórico ────────────────────────────────────────────────────────

let waImportInstanceId = null;
let importPollTimer    = null;
let importStartedTime  = null;
let importTimeTimer    = null;

function openImportModal(instanceId) {
    waImportInstanceId = instanceId;
    document.getElementById('importDaysSelect').value = '30';
    document.getElementById('importConfigState').style.display = '';
    document.getElementById('importProgressState').style.display = 'none';
    document.getElementById('waImportModal').classList.add('open');

    // Checar se já tem import rodando
    checkExistingImport(instanceId);
}

function closeImportModal() {
    document.getElementById('waImportModal').classList.remove('open');
    if (importPollTimer) { clearInterval(importPollTimer); importPollTimer = null; }
    if (importTimeTimer) { clearInterval(importTimeTimer); importTimeTimer = null; }
    waImportInstanceId = null;
}

document.getElementById('waImportModal').addEventListener('click', function(e) {
    if (e.target === this) closeImportModal();
});

async function checkExistingImport(instanceId) {
    try {
        const res  = await fetch(`${WA_BASE_URL}/${instanceId}/import/progress`, {
            headers: { 'Accept': 'application/json' },
        });
        const data = await res.json();
        if (data.status === 'running') {
            showProgressState();
            updateProgressUI(data);
            startProgressPolling(instanceId);
        }
    } catch (e) {}
}

async function startImport() {
    if (!waImportInstanceId) return;

    const days = document.getElementById('importDaysSelect').value;
    const btn  = document.getElementById('btnStartImport');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-arrow-clockwise spin"></i> ' + ILANG.import_starting;

    try {
        const res  = await fetch(`${WA_BASE_URL}/${waImportInstanceId}/import`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ days: parseInt(days) }),
        });
        const data = await res.json();

        if (data.success) {
            showProgressState();
            startProgressPolling(waImportInstanceId);
        } else {
            toastr.error(data.message || ILANG.toast_import_error);
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-cloud-download"></i> ' + ILANG.import_btn;
        }
    } catch (e) {
        toastr.error(ILANG.toast_conn_error);
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-cloud-download"></i> ' + ILANG.import_btn;
    }
}

function showProgressState() {
    document.getElementById('importConfigState').style.display = 'none';
    document.getElementById('importProgressState').style.display = '';
    document.getElementById('importProgressBar').style.width = '0%';
    document.getElementById('importCountChats').textContent = '0';
    document.getElementById('importCountMessages').textContent = '0';
    document.getElementById('importCountSkipped').textContent = '0';
    document.getElementById('importCountTime').textContent = '0:00';
    document.getElementById('importCurrentChat').textContent = '';
    document.getElementById('importProgressTitle').innerHTML = '<i class="bi bi-arrow-clockwise spin" style="color:#0085f3;margin-right:6px;"></i>' + ILANG.import_progress;
    document.getElementById('importProgressSubtitle').textContent = ILANG.import_progress_sub;

    importStartedTime = Date.now();
    if (importTimeTimer) clearInterval(importTimeTimer);
    importTimeTimer = setInterval(() => {
        const elapsed = Math.floor((Date.now() - importStartedTime) / 1000);
        const min = Math.floor(elapsed / 60);
        const sec = String(elapsed % 60).padStart(2, '0');
        document.getElementById('importCountTime').textContent = `${min}:${sec}`;
    }, 1000);
}

function startProgressPolling(instanceId) {
    if (importPollTimer) clearInterval(importPollTimer);

    importPollTimer = setInterval(async () => {
        try {
            const res  = await fetch(`${WA_BASE_URL}/${instanceId}/import/progress`, {
                headers: { 'Accept': 'application/json' },
            });
            const data = await res.json();
            updateProgressUI(data);

            if (data.status === 'completed' || data.status === 'failed' || data.status === 'idle') {
                clearInterval(importPollTimer);
                importPollTimer = null;
                if (importTimeTimer) { clearInterval(importTimeTimer); importTimeTimer = null; }
            }
        } catch (e) {}
    }, 2000);
}

function updateProgressUI(data) {
    if (!data || data.status === 'idle') return;

    const processed = data.processed || 0;
    const total     = data.total || 0;
    const messages  = data.messages || 0;
    const skipped   = data.skipped || 0;
    const current   = data.current || '';
    const pct       = total > 0 ? Math.min(Math.round((processed / total) * 100), 100) : 0;

    document.getElementById('importProgressBar').style.width = (total > 0 ? pct : 30) + '%';
    document.getElementById('importCountChats').textContent = total > 0 ? `${processed}/${total}` : processed;
    document.getElementById('importCountMessages').textContent = messages.toLocaleString('pt-BR');
    document.getElementById('importCountSkipped').textContent = skipped.toLocaleString('pt-BR');

    if (data.started_at && importStartedTime) {
        // Sincronizar com o tempo real do servidor
        const serverStart = new Date(data.started_at).getTime();
        if (Math.abs(serverStart - importStartedTime) > 5000) {
            importStartedTime = serverStart;
        }
    }

    if (current) {
        document.getElementById('importCurrentChat').textContent = ILANG.import_processing.replace(':current', current);
        document.getElementById('importProgressSubtitle').textContent = ILANG.import_processing_chats;
    }

    if (data.status === 'completed') {
        document.getElementById('importProgressTitle').innerHTML = '<i class="bi bi-check-circle-fill" style="color:#059669;margin-right:6px;"></i>' + ILANG.import_completed;
        document.getElementById('importProgressSubtitle').textContent = ILANG.import_completed_msg.replace(':count', processed);
        document.getElementById('importProgressBar').style.width = '100%';
        document.getElementById('importProgressBar').style.background = '#059669';
        document.getElementById('importCurrentChat').textContent = '';
        document.getElementById('importCloseBtn').textContent = ILANG.import_close;
    } else if (data.status === 'failed') {
        document.getElementById('importProgressTitle').innerHTML = '<i class="bi bi-exclamation-triangle-fill" style="color:#dc2626;margin-right:6px;"></i>' + ILANG.import_error_title;
        document.getElementById('importProgressSubtitle').textContent = data.error || ILANG.import_error_default;
        document.getElementById('importProgressBar').style.background = '#dc2626';
        document.getElementById('importCurrentChat').textContent = '';
    }
}

async function syncNow(platform, btn) {
    const url = SYNC_URL.replace('__P__', platform);
    btn.disabled = true;
    const icon = btn.querySelector('i');
    icon.className = 'bi bi-arrow-clockwise spin';

    try {
        const res  = await fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
        });
        const data = await res.json();
        if (data.success) {
            toastr.success(ILANG.toast_sync_started);
        } else {
            toastr.error(data.message || ILANG.toast_sync_error);
        }
    } catch (e) {
        toastr.error(ILANG.toast_conn_error);
    } finally {
        btn.disabled = false;
        icon.className = 'bi bi-arrow-clockwise';
    }
}

function disconnectPlatform(platform, btn) {
    confirmAction({
        title: ILANG.confirm_disc_title,
        message: ILANG.confirm_disc_msg,
        confirmText: ILANG.confirm_disc_btn,
        onConfirm: async () => {
            const url = DISCONNECT_URL.replace('__P__', platform);
            btn.disabled = true;
            try {
                const res  = await fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();
                if (data.success) {
                    toastr.success(ILANG.toast_integration_disc);
                    setTimeout(() => location.reload(), 1200);
                } else {
                    toastr.error(ILANG.toast_disconnect_error);
                    btn.disabled = false;
                }
            } catch (e) {
                toastr.error(ILANG.toast_conn_error);
                btn.disabled = false;
            }
        },
    });
}

async function disconnectInstagram(btn) {
    confirmAction({
        title: ILANG.confirm_ig_disc_title,
        message: ILANG.confirm_ig_disc_msg,
        confirmText: ILANG.confirm_ig_disc_btn,
        onConfirm: async () => {
            btn.disabled = true;
            try {
                const res  = await fetch(IG_DISCONNECT_URL, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();
                if (data.success) {
                    toastr.success(ILANG.toast_ig_disconnected);
                    setTimeout(() => location.reload(), 1200);
                } else {
                    toastr.error(ILANG.toast_disconnect_error);
                    btn.disabled = false;
                }
            } catch (e) {
                toastr.error(ILANG.toast_conn_error);
                btn.disabled = false;
            }
        },
    });
}

// ── WhatsApp Button CRUD ──────────────────────────────────────────────
var _waBtnId = {{ $waBtn->id ?? 'null' }};

function openWaBtnDrawer() {
    document.getElementById('waBtnOverlay').style.display = 'block';
    setTimeout(function(){ document.getElementById('waBtnDrawer').style.right = '0'; }, 10);
}
function closeWaBtnDrawer() {
    document.getElementById('waBtnDrawer').style.right = '-500px';
    setTimeout(function(){ document.getElementById('waBtnOverlay').style.display = 'none'; }, 300);
}

function saveWaButton() {
    var phone = document.getElementById('waBtnPhone').value.trim();
    if (!phone) { toastr.error(ILANG.toast_phone_required); return; }

    var data = {
        phone_number: phone,
        default_message: document.getElementById('waBtnMessage').value || ILANG.wabtn_message_ph,
        button_label: document.getElementById('waBtnLabel').value || ILANG.wabtn_label_ph,
        show_floating: document.getElementById('waBtnFloating').checked,
    };

    if (_waBtnId) {
        API.put("{{ route('settings.integrations.wa-button.store') }}/" + _waBtnId, data).done(function() {
            toastr.success(ILANG.toast_btn_updated);
            setTimeout(function(){ location.reload(); }, 800);
        });
    } else {
        API.post("{{ route('settings.integrations.wa-button.store') }}", data).done(function(r) {
            toastr.success(ILANG.toast_btn_created);
            _waBtnId = r.button?.id;
            setTimeout(function(){ location.reload(); }, 800);
        });
    }
}

function deleteWaButton() {
    if (!_waBtnId) return;
    if (!confirm(ILANG.confirm_btn_remove)) return;
    API.delete("{{ route('settings.integrations.wa-button.store') }}/" + _waBtnId).done(function() {
        toastr.success(ILANG.toast_btn_removed);
        setTimeout(function(){ location.reload(); }, 800);
    });
}
</script>

<style>
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
.spin { animation: spin .8s linear infinite; display: inline-block; }
</style>
@endpush

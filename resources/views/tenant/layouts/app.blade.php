<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Syncro CRM' }} — {{ auth()->user()->tenant?->name ?? 'Syncro CRM' }}</title>
    <meta name="description" content="CRM completo com atendimento automático via WhatsApp, agente de IA, funil de vendas e agenda integrada.">
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#0085f3">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Syncro CRM">
    <link rel="apple-touch-icon" href="{{ asset('images/favicon-192.png') }}">

    {{-- Open Graph / Social Sharing --}}
    <meta property="og:type"         content="website">
    <meta property="og:site_name"    content="Syncro CRM">
    <meta property="og:title"        content="Syncro CRM — Gestão de Clientes e Atendimento via WhatsApp">
    <meta property="og:description"  content="CRM completo com atendimento automático via WhatsApp, agente de IA, funil de vendas e agenda integrada. Gerencie leads e converta mais com menos esforço.">
    <meta property="og:image"        content="{{ asset('images/shared-image.jpg') }}">
    <meta property="og:image:width"  content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:url"          content="{{ url()->current() }}">
    <meta name="twitter:card"        content="summary_large_image">
    <meta name="twitter:title"       content="Syncro CRM — Gestão de Clientes e Atendimento via WhatsApp">
    <meta name="twitter:description" content="CRM completo com atendimento automático via WhatsApp, agente de IA, funil de vendas e agenda integrada. Gerencie leads e converta mais com menos esforço.">
    <meta name="twitter:image"       content="{{ asset('images/shared-image.jpg') }}">

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    {{-- Bootstrap Icons --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    {{-- Toastr --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

    {{-- Reverb config (injected server-side so Echo can connect without baked-in env vars) --}}
    <script>
        window.reverbConfig = {
            key:      '{{ config('reverb.apps.apps.0.key', 'crm-reverb-key') }}',
            wsHost:   '{{ config('reverb.apps.apps.0.options.host', request()->getHost()) }}',
            wsPort:   {{ (int) config('reverb.apps.apps.0.options.port', 443) }},
            wssPort:  {{ (int) config('reverb.apps.apps.0.options.port', 443) }},
            forceTLS: {{ config('reverb.apps.apps.0.options.scheme', 'https') === 'https' ? 'true' : 'false' }},
        };
        window.CURRENCY = @json(__('common.currency'));
        window.NUM_FMT  = { dec: @json(__('common.decimal_sep')), thou: @json(__('common.thousands_sep')) };
        window.vapidPublicKey = '{{ config('webpush.vapid.public_key') }}';
        window.pushSubscriptionUrl = '{{ route('push.store') }}';
        window.notificationPrefs = {!! json_encode(auth()->user()->notification_preferences ?? new \stdClass) !!};
    </script>

    {{-- Vite Assets --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')

    <style>
        * { box-sizing: border-box; scrollbar-width: thin; scrollbar-color: #d5d5d5 transparent; }

        /* ===== Scrollbar ===== */
        ::-webkit-scrollbar { width: 4px; height: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #d5d5d5; border-radius: 99px; }
        ::-webkit-scrollbar-thumb:hover { background: #bbb; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: #f4f6fb;
            margin: 0;
            color: #1a1d23;
        }

        /* ===== NAVBAR ===== */
        .navbar {
            position: fixed;
            top: 0; left: 0; right: 0;
            height: 56px;
            background: #fff;
            border-bottom: 1px solid #e8eaf0;
            z-index: 100;
            display: flex;
            align-items: center;
        }
        .navbar-inner {
            width: 100%;
            max-width: 100%;
            padding: 0 20px;
            display: flex;
            align-items: center;
            gap: 8px;
            height: 100%;
        }
        .navbar-logo { display: flex; align-items: center; flex-shrink: 0; margin-right: 16px; text-decoration: none; }
        .navbar-logo-img { height: 28px; }

        /* Workspace indicator (partner agencies) */
        .navbar-workspace {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 5px 10px;
            border-radius: 8px;
            background: #f8fafc;
            border: 1px solid #e8eaf0;
            cursor: pointer;
            margin-right: 8px;
            flex-shrink: 0;
            position: relative;
            transition: background .15s;
        }
        .navbar-workspace:hover { background: #f0f4ff; }
        .navbar-ws-avatar {
            width: 24px; height: 24px; border-radius: 6px; background: #2a84ef;
            display: flex; align-items: center; justify-content: center;
            font-size: 10px; font-weight: 700; color: #fff; flex-shrink: 0;
            overflow: hidden;
        }
        .navbar-ws-name {
            font-size: 12px; font-weight: 600; color: #1a1d23;
            max-width: 120px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .navbar-ws-chev { font-size: 10px; color: #97A3B7; transition: transform .2s; }

        /* Workspace dropdown */
        .workspace-dropdown {
            display: none;
            position: absolute;
            left: 0; top: calc(100% + 6px);
            background: #fff;
            border: 1px solid #e8eaf0;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,.12);
            z-index: 200;
            overflow: hidden;
            min-width: 240px;
        }
        .workspace-dropdown.open { display: block; }
        .workspace-dd-item {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 14px; cursor: pointer; font-size: 13px; color: #1a1d23;
            transition: background .1s;
        }
        .workspace-dd-item:hover { background: #f0f4ff; }
        .workspace-dd-item.active { background: #eff6ff; font-weight: 600; color: #1d4ed8; }
        .workspace-dd-divider { border: none; border-top: 1px solid #f3f4f6; margin: 4px 0; }
        .workspace-dd-avatar {
            width: 24px; height: 24px; border-radius: 6px; background: #2a84ef;
            display: flex; align-items: center; justify-content: center;
            font-size: 10px; font-weight: 700; color: #fff; flex-shrink: 0;
        }

        .navbar-menu {
            display: flex;
            align-items: center;
            gap: 2px;
            flex: 1;
        }
        .nm-item {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            color: #677489;
            text-decoration: none;
            cursor: pointer;
            border: none;
            background: none;
            font-family: inherit;
            transition: all .15s;
            white-space: nowrap;
        }
        .nm-item:hover { background: #f4f6fb; color: #007DFF; text-decoration: none; }
        .nm-item.active { background: #eff6ff; color: #007DFF; font-weight: 600; }
        .nm-item i { font-size: 15px; }
        .nm-chev { font-size: 10px; margin-left: 2px; transition: transform .2s; }

        .nm-dropdown { position: relative; }
        .nm-dropdown-menu {
            display: none;
            position: absolute;
            top: calc(100% + 6px);
            left: 0;
            background: #fff;
            border: 1px solid #e8eaf0;
            border-radius: 10px;
            box-shadow: 0 8px 32px rgba(0,0,0,.1);
            padding: 6px;
            min-width: 200px;
            z-index: 200;
        }
        .nm-dropdown.open .nm-dropdown-menu { display: block; }
        .nm-dropdown.open .nm-chev { transform: rotate(180deg); }

        .nm-dd-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 7px;
            font-size: 13px;
            color: #374151;
            text-decoration: none;
            transition: all .1s;
        }
        .nm-dd-item:hover { background: #eff6ff; color: #007DFF; text-decoration: none; }
        .nm-dd-item.active { background: #eff6ff; color: #007DFF; font-weight: 600; }
        .nm-dd-item i { font-size: 14px; color: #9ca3af; width: 18px; text-align: center; }
        .nm-dd-item:hover i, .nm-dd-item.active i { color: #007DFF; }

        .nm-dd-sep {
            height: 1px;
            background: #f0f2f7;
            margin: 4px 6px;
        }

        .navbar-right {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-left: auto;
            flex-shrink: 1;
            min-width: 0;
        }
        .navbar-right .topbar-actions {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* Hamburger for mobile */
        .navbar-hamburger {
            display: none;
            width: 36px; height: 36px;
            border: 1px solid #e8eaf0;
            border-radius: 9px;
            background: #fff;
            align-items: center;
            justify-content: center;
            color: #677489;
            font-size: 18px;
            cursor: pointer;
            flex-shrink: 0;
        }

        .topbar-btn {
            width: 36px;
            height: 36px;
            border: 1px solid #e8eaf0;
            border-radius: 9px;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #677489;
            font-size: 16px;
            cursor: pointer;
            transition: all .15s;
            text-decoration: none;
            position: relative;
        }

        .topbar-btn:hover {
            background: #f4f6fb;
            color: #007DFF;
            border-color: #CDDEF6;
        }

        .badge-dot {
            position: absolute;
            top: 6px; right: 6px;
            width: 7px; height: 7px;
            border-radius: 50%;
            background: #EF4444;
            border: 1.5px solid #fff;
        }

        .badge-num {
            position: absolute;
            top: 4px; right: 4px;
            background: #EF4444; color: #fff;
            border-radius: 10px; font-size: 9px; font-weight: 700;
            min-width: 16px; height: 16px; line-height: 16px;
            padding: 0 4px; text-align: center;
            border: 1.5px solid #fff;
        }

        .notif-item:hover { background: #f9fafb; }
        .notif-item.unread { background: #eff6ff; }
        .notif-item.unread:hover { background: #CDDEF6; }

        /* Notification Drawer */
        .notif-drawer-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,.3); z-index: 9000;
        }
        .notif-drawer-overlay.open { display: block; }
        .notif-drawer {
            position: fixed; top: 0; right: -400px; width: 400px; height: 100vh;
            background: #fff; z-index: 9001;
            box-shadow: -4px 0 24px rgba(0,0,0,.1);
            display: flex; flex-direction: column;
            transition: right .25s cubic-bezier(.4,0,.2,1);
        }
        .notif-drawer.open { right: 0; }
        .notif-drawer-header {
            padding: 16px 20px; border-bottom: 1px solid #f0f2f7;
            display: flex; align-items: center; justify-content: space-between;
            flex-shrink: 0;
        }
        .notif-drawer-body {
            flex: 1; overflow-y: auto; padding: 0;
        }
        .notif-empty {
            display: flex; flex-direction: column; align-items: center;
            justify-content: center; height: 200px;
            color: #9ca3af; font-size: 13px;
        }
        .nd-item {
            display: flex; align-items: flex-start; gap: 12px;
            padding: 14px 20px; border-bottom: 1px solid #f7f8fa;
            cursor: pointer; transition: background .15s;
        }
        .nd-item:hover { background: #f9fafb; }
        .nd-item.unread { background: #f0f7ff; }
        .nd-item.unread:hover { background: #e0efff; }
        .nd-icon {
            width: 32px; height: 32px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 14px; flex-shrink: 0;
        }
        .nd-content { flex: 1; min-width: 0; }
        .nd-title { font-size: 13px; font-weight: 600; color: #1a1d23; margin-bottom: 2px; }
        .nd-body { font-size: 12.5px; color: #6b7280; line-height: 1.4; }
        .nd-time { font-size: 11px; color: #9ca3af; margin-top: 3px; }

        @media (max-width: 480px) {
            .notif-drawer { width: 100%; right: -100%; }
        }

        .btn-primary-sm {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            background: #0085f3;
            color: #fff;
            border: none;
            border-radius: 100px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: background .15s;
            white-space: nowrap;
        }

        .btn-primary-sm:hover { background: #0070d1; color: #fff; }

        .btn-outline-sm {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border: 1.5px solid #e8eaf0;
            background: #fff;
            color: #374151;
            border-radius: 100px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all .15s;
            white-space: nowrap;
        }
        .btn-outline-sm:hover {
            background: #f0f4ff;
            border-color: #bfdbfe;
            color: #0085f3;
        }

        /* ===== TRIAL WIDGET ===== */
        .trial-widget {
            display: flex;
            flex-direction: column;
            gap: 3px;
            min-width: 120px;
        }
        .trial-widget-text {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
            color: #6b7280;
            white-space: nowrap;
        }
        .trial-widget-text i { font-size: 13px; color: #F97316; }
        .trial-widget-bar {
            width: 100%;
            height: 4px;
            background: #f3f4f6;
            border-radius: 99px;
            overflow: hidden;
        }
        .trial-widget-bar-fill {
            height: 100%;
            border-radius: 99px;
            position: relative;
            overflow: hidden;
            background: #F97316;
        }
        .trial-widget-bar-fill::after {
            content: '';
            position: absolute;
            inset: 0;
            background: repeating-linear-gradient(
                -45deg,
                transparent,
                transparent 4px,
                rgba(255,255,255,.35) 4px,
                rgba(255,255,255,.35) 8px
            );
            animation: barber-pole .6s linear infinite;
        }
        @keyframes barber-pole {
            0% { background-position: 0 0; }
            100% { background-position: 11.3px 0; }
        }
        /* Mobile: trial banner no topo */
        .trial-mobile-banner {
            display: none;
        }
        @media (max-width: 768px) {
            .trial-widget { display: none !important; }
            .trial-mobile-banner {
                display: flex;
                align-items: center;
                gap: 8px;
                background: #FFEDD5;
                padding: 8px 16px;
                font-size: 12px;
                font-weight: 600;
                color: #C2410C;
            }
            .trial-mobile-banner i { font-size: 14px; }
            .trial-mobile-bar {
                flex: 1;
                height: 4px;
                background: rgba(194,65,12,.15);
                border-radius: 99px;
                overflow: hidden;
                margin-left: 4px;
            }
            .trial-mobile-bar-fill {
                height: 100%;
                border-radius: 99px;
                position: relative;
                overflow: hidden;
                background: #C2410C;
            }
            .trial-mobile-bar-fill::after {
                content: '';
                position: absolute;
                inset: 0;
                background: repeating-linear-gradient(
                    -45deg,
                    transparent,
                    transparent 4px,
                    rgba(255,255,255,.35) 4px,
                    rgba(255,255,255,.35) 8px
                );
                animation: barber-pole .6s linear infinite;
            }
        }

        /* ===== MAIN CONTENT ===== */
        .main-content {
            margin-left: 0;
            padding-top: 56px;
            min-height: 100vh;
        }

        .page-container {
            padding: 28px 28px;
        }

        /* ===== SETTINGS NAV TABS ===== */
        .settings-nav-tabs {
            display: flex;
            gap: 0;
            border-bottom: 2px solid #e8eaf0;
            margin-bottom: 24px;
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            scrollbar-color: #0085f3 transparent;
        }
        .settings-nav-tabs::-webkit-scrollbar { height: 4px; }
        .settings-nav-tabs::-webkit-scrollbar-track { background: transparent; border-radius: 99px; }
        .settings-nav-tabs::-webkit-scrollbar-thumb { background: #0085f3; border-radius: 99px; }
        .settings-nav-tab {
            padding: 10px 18px;
            font-size: 13px;
            font-weight: 600;
            color: #6b7280;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            background: none;
            transition: color .15s;
            text-decoration: none;
            white-space: nowrap;
        }
        .settings-nav-tab:hover { color: #374151; text-decoration: none; }
        .settings-nav-tab.active { color: #0085f3; border-bottom-color: #0085f3; }

        /* ===== CARDS ===== */
        .stat-card {
            background: #fff;
            border-radius: 14px;
            padding: 22px 24px;
            border: 1px solid #e8eaf0;
        }

        .stat-card .stat-label {
            font-size: 13px;
            color: #677489;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
        }

        .stat-card .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #1a1d23;
            line-height: 1;
            margin-bottom: 6px;
        }

        .stat-card .stat-sub {
            font-size: 12px;
            color: #97A3B7;
        }

        .stat-card .stat-delta {
            font-size: 12px;
            font-weight: 600;
        }

        .stat-delta.up { color: #10B981; }
        .stat-delta.down { color: #EF4444; }

        .content-card {
            background: #fff;
            border-radius: 14px;
            border: 1px solid #e8eaf0;
            overflow: hidden;
        }

        .content-card-header {
            padding: 18px 22px;
            border-bottom: 1px solid #f0f2f7;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 600;
            color: #1a1d23;
        }

        .content-card-body {
            padding: 20px 22px;
        }

        /* ===== USER AVATAR (navbar) ===== */
        .user-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: linear-gradient(135deg, #10B981, #007DFF);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            flex-shrink: 0;
            overflow: hidden;
        }

        /* ===== IMPERSONATION ===== */
        body.impersonating .navbar { top: 42px !important; }
        body.impersonating .main-content { padding-top: calc(56px + 42px) !important; }

        /* ===== RESPONSIVO ===== */
        @media (max-width: 768px) {
            .navbar-hamburger { display: flex; }
            .navbar-menu {
                display: none;
                position: fixed;
                top: 56px; left: 0; right: 0; bottom: 0;
                background: #fff;
                flex-direction: column;
                padding: 24px 0 40px;
                overflow-y: auto;
                z-index: 150;
                border-top: 1px solid #e8eaf0;
                align-items: stretch;
            }
            .navbar-menu.open { display: flex; }

            /* Menu items — full width, large touch targets */
            .navbar-menu .nm-item {
                width: 100%;
                justify-content: flex-start;
                padding: 16px 24px;
                border-radius: 0;
                font-size: 15px;
                font-weight: 500;
                color: #1a1d23;
                border-bottom: 1px solid #f0f2f7;
            }
            .navbar-menu .nm-item i:first-child { display: none; }
            .navbar-menu .nm-item:hover { background: #f8fafc; }
            .navbar-menu .nm-item.active {
                background: #fff;
                color: #0085f3;
                font-weight: 600;
            }
            .navbar-menu .nm-chev {
                margin-left: auto;
                font-size: 14px;
                color: #9ca3af;
                transition: transform .2s;
            }

            /* Dropdown containers */
            .navbar-menu .nm-dropdown { width: 100%; }
            .navbar-menu .nm-dropdown > .nm-item { width: 100%; }
            .nm-dropdown-menu {
                position: static;
                box-shadow: none;
                border: none;
                border-radius: 0;
                padding: 0;
                min-width: unset;
                background: #f8fafc;
                border-bottom: 1px solid #f0f2f7;
            }
            .nm-dropdown.open .nm-dropdown-menu { display: block; }

            /* Dropdown sub-items */
            .nm-dd-item {
                padding: 14px 24px 14px 40px;
                font-size: 14px;
                color: #374151;
                border-radius: 0;
            }
            .nm-dd-item:hover { background: #eff6ff; }
            .nm-dd-item.active { color: #0085f3; font-weight: 600; }
            .nm-dd-item i { display: none; }
            .navbar-logo-img { height: 24px; }
            .navbar-ws-name { max-width: 80px; }
            body.impersonating .navbar { top: 42px !important; }
            body.impersonating .main-content { padding-top: calc(56px + 42px) !important; }
            body.impersonating .navbar-menu { top: calc(56px + 42px); }
            .page-container { padding: 16px 14px; }
            .section-title { font-size: 17px; }
            .section-subtitle { font-size: 12.5px; }
        }
        @media (max-width: 480px) {
            .page-container { padding: 12px 10px; }
            .section-title { font-size: 16px; }
            .section-subtitle { font-size: 12px; }
        }
    </style>
</head>
<body class="{{ session('impersonating_tenant_id') ? 'impersonating' : '' }}">

{{-- ===== Banner Impersonação (full-width, acima de tudo) ===== --}}
@if(session('impersonating_tenant_id'))
@php $impTarget = \App\Models\Tenant::withoutGlobalScope('tenant')->find(session('impersonating_tenant_id')); @endphp
@if($impTarget)
<div id="impersonationBar" style="position:fixed;top:0;left:0;right:0;z-index:10000;background:#FEF3C7;border-bottom:2px solid #F59E0B;padding:8px 20px;display:flex;align-items:center;justify-content:space-between;gap:16px;font-size:13px;">
    <div style="display:flex;align-items:center;gap:10px;">
        <i class="bi bi-eye-fill" style="color:#D97706;font-size:15px;"></i>
        <span style="color:#92400E;">
            Você está acessando a conta de
            <strong style="color:#78350F;">{{ $impTarget->name }}</strong>
            como agência parceira.
        </span>
    </div>
    <form method="POST" action="{{ route('agency.access.exit') }}" style="margin:0;">
        @csrf
        <button type="submit" style="background:#D97706;color:#fff;border:none;border-radius:7px;padding:5px 14px;font-size:12.5px;font-weight:600;cursor:pointer;">
            <i class="bi bi-box-arrow-right"></i> Sair e voltar para minha conta
        </button>
    </form>
</div>
@endif
@endif

{{-- ===== NAVBAR ===== --}}
@php
    $authTenant      = auth()->user()->tenant;
    $isPartnerUser   = $authTenant?->isPartner();
    $impersonatingId = session('impersonating_tenant_id');
    $activeTenant    = $impersonatingId
        ? \App\Models\Tenant::withoutGlobalScope('tenant')->find($impersonatingId)
        : $authTenant;
    $partnerClients  = $isPartnerUser
        ? \App\Models\Tenant::withoutGlobalScope('tenant')
            ->where('referred_by_agency_id', $authTenant->id)
            ->orderBy('name')
            ->get(['id', 'name', 'logo'])
        : collect();

    $crmActive = request()->routeIs('crm*', 'leads*', 'lists*', 'goals*', 'calendar.*', 'tasks*', 'forms*', 'settings.pipelines*', 'settings.products*', 'settings.custom-fields*', 'settings.lost-reasons*', 'settings.tags*', 'settings.scoring*');
    $autoActive = request()->routeIs('chatbot.flows.*', 'ai.agents.*', 'ai.intent-signals.*', 'settings.automations*', 'settings.sequences*', 'settings.ig-automations.*');
    $reportActive = request()->routeIs('reports*', 'campaigns*', 'nps*');
    $settingsActive = (request()->routeIs('settings.*') && !request()->routeIs('settings.automations*', 'settings.sequences*', 'settings.pipelines*', 'settings.products*', 'settings.custom-fields*', 'settings.lost-reasons*', 'settings.tags*', 'settings.scoring*', 'settings.ig-automations.*')) || request()->routeIs('billing.*');
    $igConnected = \App\Models\InstagramInstance::where('status', 'connected')->exists();
@endphp

<header class="navbar" id="navbar">
    <div class="navbar-inner">
        {{-- Left: Logo --}}
        <a href="{{ route('inicio') }}" class="navbar-logo">
            <img src="{{ asset('images/logo.png') }}" alt="Syncro" class="navbar-logo-img">
        </a>

        {{-- Workspace indicator (only when impersonating) --}}
        @if($isPartnerUser && $impersonatingId && $activeTenant)
        <div style="display:flex;align-items:center;gap:8px;padding:4px 12px;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;font-size:12px;color:#92400e;font-weight:600;">
            <i class="bi bi-eye-fill"></i>
            {{ $activeTenant->name }}
        </div>
        @endif

        {{-- Hamburger (mobile) --}}
        <button class="navbar-hamburger" id="navbarHamburger">
            <i class="bi bi-list"></i>
        </button>

        {{-- Center: Menu items --}}
        <nav class="navbar-menu" id="navbarMenu">

            @if(auth()->user()->tenant?->isPartner() && !session('impersonating_tenant_id'))
            {{-- ══ PARTNER-ONLY NAV ══ --}}
            <a href="{{ route('partner.dashboard') }}" class="nm-item {{ request()->routeIs('partner.dashboard') ? 'active' : '' }}">
                <i class="bi bi-grid-1x2"></i> {{ __('partner.nav_dashboard') }}
            </a>
            <a href="{{ route('agency.clients') }}" class="nm-item {{ request()->routeIs('agency.clients') ? 'active' : '' }}">
                <i class="bi bi-people"></i> {{ __('partner.nav_my_clients') }}
            </a>
            <a href="{{ route('partner.resources.index') }}" class="nm-item {{ request()->routeIs('partner.resources.*') ? 'active' : '' }}">
                <i class="bi bi-folder2-open"></i> {{ __('partner.nav_resources') }}
            </a>
            <a href="{{ route('partner.courses.index') }}" class="nm-item {{ request()->routeIs('partner.courses.*', 'partner.lessons.*') ? 'active' : '' }}">
                <i class="bi bi-mortarboard"></i> {{ __('partner.nav_courses') }}
            </a>
            <a href="{{ route('settings.profile') }}" class="nm-item {{ request()->routeIs('settings.profile*') ? 'active' : '' }}">
                <i class="bi bi-person-gear"></i> {{ __('partner.nav_profile') }}
            </a>
            @else
            {{-- ══ REGULAR TENANT NAV ══ --}}
            <a href="{{ route('inicio') }}" class="nm-item {{ request()->routeIs('inicio', 'dashboard') ? 'active' : '' }}">
                <i class="bi bi-house"></i> {{ __('nav.home') }}
            </a>
            <a href="{{ route('chats.index') }}" class="nm-item {{ request()->routeIs('chats.*') ? 'active' : '' }}">
                <i class="bi bi-chat-dots"></i> {{ __('nav.chats') }}
            </a>

            {{-- CRM dropdown --}}
            <div class="nm-dropdown">
                <button class="nm-item {{ $crmActive ? 'active' : '' }}" onclick="toggleNavDropdown(this)">
                    <i class="bi bi-kanban"></i> {{ __('nav.crm') }} <i class="bi bi-chevron-down nm-chev"></i>
                </button>
                <div class="nm-dropdown-menu">
                    <a href="{{ route('crm.kanban') }}" class="nm-dd-item {{ request()->routeIs('crm*') ? 'active' : '' }}">
                        <i class="bi bi-kanban"></i> {{ __('nav.deals') }}
                    </a>
                    <a href="{{ route('leads.index') }}" class="nm-dd-item {{ request()->routeIs('leads*') ? 'active' : '' }}">
                        <i class="bi bi-people"></i> {{ __('nav.contacts') }}
                    </a>
                    <a href="{{ route('calendar.index') }}" class="nm-dd-item {{ request()->routeIs('calendar.*') ? 'active' : '' }}">
                        <i class="bi bi-calendar3"></i> {{ __('nav.calendar') }}
                    </a>
                    <a href="{{ route('tasks.index') }}" class="nm-dd-item {{ request()->routeIs('tasks*') ? 'active' : '' }}">
                        <i class="bi bi-check2-square"></i> {{ __('nav.tasks') }}
                    </a>
                    <a href="{{ route('lists.index') }}" class="nm-dd-item {{ request()->routeIs('lists*') ? 'active' : '' }}">
                        <i class="bi bi-list-check"></i> {{ __('nav.lists') ?? 'Listas' }}
                    </a>
                    <a href="{{ route('goals.index') }}" class="nm-dd-item {{ request()->routeIs('goals*') ? 'active' : '' }}">
                        <i class="bi bi-trophy"></i> {{ __('nav.goals') ?? 'Metas' }}
                    </a>
                    <div class="nm-dd-sep"></div>
                    <a href="{{ route('settings.pipelines') }}" class="nm-dd-item {{ request()->routeIs('settings.pipelines*') ? 'active' : '' }}">
                        <i class="bi bi-funnel"></i> {{ __('nav.pipelines') }}
                    </a>
                    <a href="{{ route('forms.index') }}" class="nm-dd-item {{ request()->routeIs('forms*') ? 'active' : '' }}">
                        <i class="bi bi-ui-checks-grid"></i> {{ __('nav.forms') ?? 'Formulários' }}
                    </a>
                    <a href="{{ route('settings.products') }}" class="nm-dd-item {{ request()->routeIs('settings.products*') ? 'active' : '' }}">
                        <i class="bi bi-box-seam"></i> {{ __('nav.products') }}
                    </a>
                    <a href="{{ route('settings.custom-fields') }}" class="nm-dd-item {{ request()->routeIs('settings.custom-fields*') ? 'active' : '' }}">
                        <i class="bi bi-input-cursor-text"></i> {{ __('nav.custom_fields') }}
                    </a>
                    <a href="{{ route('settings.lost-reasons') }}" class="nm-dd-item {{ request()->routeIs('settings.lost-reasons*') ? 'active' : '' }}">
                        <i class="bi bi-x-circle"></i> {{ __('nav.lost_reasons') }}
                    </a>
                    <a href="{{ route('settings.tags') }}" class="nm-dd-item {{ request()->routeIs('settings.tags*') ? 'active' : '' }}">
                        <i class="bi bi-tags"></i> {{ __('nav.tags') }}
                    </a>
                    <a href="{{ route('settings.scoring') }}" class="nm-dd-item {{ request()->routeIs('settings.scoring*') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2"></i> {{ __('nav.lead_scoring') }}
                    </a>
                </div>
            </div>

            {{-- Automação dropdown --}}
            <div class="nm-dropdown">
                <button class="nm-item {{ $autoActive ? 'active' : '' }}" onclick="toggleNavDropdown(this)">
                    <i class="bi bi-lightning"></i> {{ __('nav.automation') }} <i class="bi bi-chevron-down nm-chev"></i>
                </button>
                <div class="nm-dropdown-menu">
                    <a href="{{ route('chatbot.flows.index') }}" class="nm-dd-item {{ request()->routeIs('chatbot.flows.*') ? 'active' : '' }}">
                        <i class="bi bi-diagram-3"></i> {{ __('nav.chatbot') }}
                    </a>
                    <a href="{{ route('ai.agents.index') }}" class="nm-dd-item {{ request()->routeIs('ai.agents.*', 'ai.intent-signals.*') ? 'active' : '' }}">
                        <i class="bi bi-robot"></i> {{ __('nav.ai_agents') }}
                    </a>
                    <a href="{{ route('settings.automations') }}" class="nm-dd-item {{ request()->routeIs('settings.automations*') ? 'active' : '' }}">
                        <i class="bi bi-lightning"></i> {{ __('nav.automations') }}
                    </a>
                    <a href="{{ route('settings.sequences') }}" class="nm-dd-item {{ request()->routeIs('settings.sequences*') ? 'active' : '' }}">
                        <i class="bi bi-arrow-repeat"></i> {{ __('sequences.nav_title') }}
                    </a>
                    @if($igConnected)
                    <a href="{{ route('settings.ig-automations.index') }}" class="nm-dd-item {{ request()->routeIs('settings.ig-automations.*') ? 'active' : '' }}">
                        <i class="bi bi-instagram"></i> {{ __('nav.ig_automations') }}
                    </a>
                    @endif
                </div>
            </div>

            {{-- Relatórios dropdown --}}
            <div class="nm-dropdown">
                <button class="nm-item {{ $reportActive ? 'active' : '' }}" onclick="toggleNavDropdown(this)">
                    <i class="bi bi-bar-chart-line"></i> {{ __('nav.reports') }} <i class="bi bi-chevron-down nm-chev"></i>
                </button>
                <div class="nm-dropdown-menu">
                    <a href="{{ route('reports.index') }}" class="nm-dd-item {{ request()->routeIs('reports*') ? 'active' : '' }}">
                        <i class="bi bi-bar-chart-line"></i> {{ __('nav.indicators') }}
                    </a>
                    <a href="{{ route('campaigns.index') }}" class="nm-dd-item {{ request()->routeIs('campaigns*') ? 'active' : '' }}">
                        <i class="bi bi-megaphone"></i> {{ __('nav.campaigns') }}
                    </a>
                    <a href="{{ route('nps.index') }}" class="nm-dd-item {{ request()->routeIs('nps*') ? 'active' : '' }}">
                        <i class="bi bi-emoji-smile"></i> {{ __('nav.nps') ?? 'NPS' }}
                    </a>
                </div>
            </div>

            {{-- Configurações dropdown --}}
            <div class="nm-dropdown">
                <button class="nm-item {{ $settingsActive ? 'active' : '' }}" onclick="toggleNavDropdown(this)">
                    <i class="bi bi-gear"></i> {{ __('nav.settings') }} <i class="bi bi-chevron-down nm-chev"></i>
                </button>
                <div class="nm-dropdown-menu">
                    <a href="{{ route('settings.profile') }}" class="nm-dd-item {{ request()->routeIs('settings.profile*') ? 'active' : '' }}">
                        <i class="bi bi-person"></i> {{ __('nav.profile') }}
                    </a>
                    <a href="{{ route('settings.notifications') }}" class="nm-dd-item {{ request()->routeIs('settings.notifications*') ? 'active' : '' }}">
                        <i class="bi bi-bell"></i> {{ __('nav.notifications') }}
                    </a>
                    <a href="{{ route('settings.users') }}" class="nm-dd-item {{ request()->routeIs('settings.users*') ? 'active' : '' }}">
                        <i class="bi bi-people-fill"></i> {{ __('nav.users') }}
                    </a>
                    <a href="{{ route('settings.integrations.index') }}" class="nm-dd-item {{ request()->routeIs('settings.integrations*') ? 'active' : '' }}">
                        <i class="bi bi-plug"></i> {{ __('nav.integrations') }}
                    </a>
                    <a href="{{ route('settings.departments') }}" class="nm-dd-item {{ request()->routeIs('settings.departments*') ? 'active' : '' }}">
                        <i class="bi bi-building"></i> {{ __('nav.departments') }}
                    </a>
                    <a href="{{ route('settings.billing') }}" class="nm-dd-item {{ request()->routeIs('settings.billing*', 'billing.*') ? 'active' : '' }}">
                        <i class="bi bi-credit-card"></i> {{ __('nav.billing') }}
                    </a>
                    <a href="{{ route('settings.api-keys') }}" class="nm-dd-item {{ request()->routeIs('settings.api-keys*') ? 'active' : '' }}">
                        <i class="bi bi-code-slash"></i> {{ __('nav.api_webhooks') }}
                    </a>
                    <a href="{{ route('settings.pwa') }}" class="nm-dd-item {{ request()->routeIs('settings.pwa*') ? 'active' : '' }}">
                        <i class="bi bi-download"></i> {{ __('settings.pwa_title') }}
                    </a>
                    @if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())
                    <a href="{{ route('settings.audit-log') }}" class="nm-dd-item {{ request()->routeIs('settings.audit-log*') ? 'active' : '' }}">
                        <i class="bi bi-shield-check"></i> Auditoria
                    </a>
                    @endif
                </div>
            </div>

            @endif
            {{-- ══ END REGULAR TENANT NAV ══ --}}

            {{-- Master (super_admin only) --}}
            @if(auth()->user()->isSuperAdmin())
            <a href="{{ route('master.tenants') }}" class="nm-item {{ request()->routeIs('master.tenants*') ? 'active' : '' }}">
                <i class="bi bi-shield-lock"></i> {{ __('nav.master_panel') }}
            </a>
            @endif
        </nav>

        {{-- Right: page actions + trial + notifications + avatar --}}
        <div class="navbar-right">
            @hasSection('topbar_actions')
                @yield('topbar_actions')
            @endif

            {{-- Trial badge --}}
            @php
                $__tenant = auth()->user()->tenant;
                $__showTrial = $__tenant
                    && $__tenant->status === 'trial'
                    && $__tenant->trial_ends_at
                    && !$__tenant->trial_ends_at->isPast();
                $__trialDays = $__showTrial ? (int) now()->diffInDays($__tenant->trial_ends_at, false) : 0;
                $__trialTotal = $__showTrial && $__tenant->created_at ? (int) $__tenant->created_at->diffInDays($__tenant->trial_ends_at) : 14;
                $__trialPct = $__trialTotal > 0 ? max(0, min(100, ($__trialDays / $__trialTotal) * 100)) : 0;
            @endphp
            @if($__showTrial)
            <div class="trial-widget" title="Seu período de teste termina em {{ $__trialDays }} dias">
                <div class="trial-widget-text">
                    <i class="bi bi-clock-history"></i>
                    <span>Trial: <strong>{{ $__trialDays }} {{ trans_choice('common.days_count', $__trialDays) }}</strong></span>
                </div>
                <div class="trial-widget-bar">
                    <div class="trial-widget-bar-fill" style="width:{{ $__trialPct }}%"></div>
                </div>
            </div>
            @endif

            {{-- Search button (Cmd+K) --}}
            <button class="topbar-btn" onclick="openGlobalSearch()" title="Ctrl+K" style="position:relative;">
                <i class="bi bi-search"></i>
            </button>

            {{-- Feedback button --}}
            @if(!$isPartnerUser || $impersonatingId)
            <a href="{{ route('feedback.create', ['from' => request()->path()]) }}" target="_blank"
               style="display:inline-flex;align-items:center;gap:6px;padding:6px 14px;background:rgba(0,133,243,.1);color:#0085f3;border:1px solid rgba(0,133,243,.25);border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;transition:background .12s;white-space:nowrap;"
               onmouseover="this.style.background='rgba(0,133,243,.18)'" onmouseout="this.style.background='rgba(0,133,243,.1)'">
                <i class="bi bi-lightbulb"></i> <span class="d-none d-md-inline">{{ __('feedback.topbar_button') }}</span>
            </a>
            @endif

            {{-- Notification bell --}}
            <button class="topbar-btn" id="notif-bell-btn" onclick="toggleNotifDrawer()" title="Notificações">
                <i class="bi bi-bell"></i>
                <span class="badge-num d-none" id="notif-badge-num"></span>
            </button>

            {{-- User avatar dropdown --}}
            <div class="dropdown">
                <div class="user-avatar" style="width:36px;height:36px;border-radius:9px;cursor:pointer;overflow:hidden;"
                     data-bs-toggle="dropdown">
                    @if(auth()->user()->avatar)
                        <img src="{{ auth()->user()->avatar }}" alt="{{ auth()->user()->name }}"
                             style="width:100%;height:100%;object-fit:cover;">
                    @else
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    @endif
                </div>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="min-width:180px;border-radius:10px;">
                    <li class="px-3 py-2">
                        <div style="font-size:13px;font-weight:600;">{{ auth()->user()->name }}</div>
                        <div style="font-size:11px;color:#97A3B7;">{{ auth()->user()->email }}</div>
                    </li>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li><a class="dropdown-item" href="{{ route('settings.profile') }}"><i class="bi bi-person me-2"></i>Perfil</a></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger">
                                <i class="bi bi-box-arrow-right me-2"></i>Sair
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</header>

{{-- ===== CONTEÚDO PRINCIPAL ===== --}}
<main class="main-content" id="mainContent">
    @if($__showTrial ?? false)
    <div class="trial-mobile-banner">
        <i class="bi bi-clock-history"></i>
        <span>Trial: {{ $__trialDays }} {{ trans_choice('common.days_count', $__trialDays) }}</span>
        <div class="trial-mobile-bar">
            <div class="trial-mobile-bar-fill" style="width:{{ $__trialPct }}%"></div>
        </div>
    </div>
    @endif

    {{-- Upsell Banner --}}
    @if(!empty($upsellBanner) && $upsellBanner->trigger)
    @php
        $ubTrigger = $upsellBanner->trigger;
        $ubCfg     = $ubTrigger->action_config ?? [];
        $ubTitle   = $ubCfg['title'] ?? 'Hora de crescer!';
        $ubBody    = $ubCfg['body'] ?? 'Você está chegando no limite do seu plano atual.';
        $ubCta     = $ubCfg['cta_text'] ?? 'Ver planos';
        $ubUrl     = $ubCfg['cta_url'] ?? route('billing.checkout', ['plan' => $ubTrigger->target_plan]);
    @endphp
    <div id="upsellBanner" style="background:#0085f3;color:#fff;padding:12px 24px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
        <div style="display:flex;align-items:center;gap:12px;flex:1;min-width:0;">
            <i class="bi bi-rocket-takeoff" style="font-size:18px;flex-shrink:0;"></i>
            <div>
                <strong style="font-size:14px;">{{ $ubTitle }}</strong>
                <span style="font-size:13px;opacity:.9;margin-left:8px;">{{ $ubBody }}</span>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:10px;flex-shrink:0;">
            <a href="{{ $ubUrl }}"
               onclick="dismissUpsellBanner({{ $upsellBanner->id }}, true)"
               style="background:#fff;color:#0085f3;padding:6px 18px;border-radius:7px;font-size:13px;font-weight:700;text-decoration:none;">
                {{ $ubCta }}
            </a>
            <button onclick="dismissUpsellBanner({{ $upsellBanner->id }}, false)"
                    style="background:none;border:none;color:#fff;cursor:pointer;font-size:18px;opacity:.7;padding:4px;"
                    title="Fechar">&times;</button>
        </div>
    </div>
    <script>
    function dismissUpsellBanner(logId, isClick) {
        const url = isClick
            ? `/upsell/${logId}/click`
            : `/upsell/${logId}/dismiss`;
        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                'Accept': 'application/json',
            },
        });
        if (!isClick) {
            document.getElementById('upsellBanner').style.display = 'none';
        }
    }
    </script>
    @endif

    @yield('content')
</main>

{{-- ===== CONFIRM MODAL ===== --}}
<div id="confirmModal" style="display:none;position:fixed;inset:0;z-index:10000;background:rgba(15,23,42,.5);align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;width:100%;max-width:440px;margin:16px;padding:28px;box-shadow:0 24px 64px rgba(0,0,0,.18);">
        <div style="display:flex;align-items:flex-start;gap:14px;margin-bottom:18px;">
            <div style="width:44px;height:44px;border-radius:12px;background:#FEF2F2;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="bi bi-exclamation-triangle-fill" style="color:#EF4444;font-size:20px;"></i>
            </div>
            <div style="flex:1;min-width:0;">
                <h5 id="confirmModalTitle" style="font-size:16px;font-weight:700;color:#111827;margin:0 0 6px;"></h5>
                <p id="confirmModalMessage" style="font-size:14px;color:#677489;margin:0 0 10px;line-height:1.5;"></p>
                <p style="font-size:12px;color:#EF4444;font-weight:500;margin:0;display:flex;align-items:center;gap:5px;">
                    <i class="bi bi-shield-exclamation"></i> Esta ação é irreversível e não pode ser desfeita.
                </p>
            </div>
        </div>
        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:24px;">
            <button id="confirmModalCancel" type="button"
                style="padding:9px 20px;border-radius:8px;border:1px solid #e5e7eb;background:#fff;color:#374151;font-size:13px;font-weight:500;cursor:pointer;">
                Cancelar
            </button>
            <button id="confirmModalConfirm" type="button"
                style="padding:9px 20px;border-radius:8px;border:none;background:#EF4444;color:#fff;font-size:13px;font-weight:600;cursor:pointer;min-width:100px;">
                Confirmar
            </button>
        </div>
    </div>
</div>

{{-- Scripts --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<script>
// ── Confirm Modal ─────────────────────────────────────────────────────────
window.confirmAction = function ({ title = 'Confirmar ação', message = '', confirmText = 'Confirmar', onConfirm }) {
    const modal = document.getElementById('confirmModal');
    document.getElementById('confirmModalTitle').textContent   = title;
    document.getElementById('confirmModalMessage').textContent = message;
    document.getElementById('confirmModalConfirm').textContent = confirmText;
    modal.style.display = 'flex';

    const close = () => { modal.style.display = 'none'; };
    document.getElementById('confirmModalCancel').onclick  = close;
    document.getElementById('confirmModalConfirm').onclick = () => { close(); onConfirm(); };
    modal.onclick = (e) => { if (e.target === modal) close(); };
};

// ── Navbar dropdown toggle ───────────────────────────────────────────────
function toggleNavDropdown(btn) {
    const wrap = btn.closest('.nm-dropdown');
    const wasOpen = wrap.classList.contains('open');
    // Close all dropdowns
    document.querySelectorAll('.nm-dropdown.open').forEach(d => d.classList.remove('open'));
    if (!wasOpen) wrap.classList.add('open');
}
// Close dropdowns on click outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.nm-dropdown')) {
        document.querySelectorAll('.nm-dropdown.open').forEach(d => d.classList.remove('open'));
    }
});
// Mobile hamburger — toggle menu + icon ☰ ↔ ✕
document.getElementById('navbarHamburger')?.addEventListener('click', function() {
    var menu = document.getElementById('navbarMenu');
    menu.classList.toggle('open');
    var icon = this.querySelector('i');
    if (menu.classList.contains('open')) {
        icon.className = 'bi bi-x-lg';
    } else {
        icon.className = 'bi bi-list';
    }
});

// ── Flash messages ────────────────────────────────────────────────────────
toastr.options = { positionClass: 'toast-bottom-right', timeOut: 4000, closeButton: true, progressBar: true };
@if(session('success'))
    toastr.success("{{ session('success') }}");
@endif
@if(session('error'))
    toastr.error("{{ session('error') }}");
@endif
@if(session('warning'))
    toastr.warning("{{ session('warning') }}");
@endif
@if(session('limit_error'))
    toastr.warning("{!! session('limit_error') !!}");
@endif

// ── API Helper ────────────────────────────────────────────────────────────
window.API = {
    call: function(method, url, data = null) {
        return $.ajax({
            url: url,
            method: method,
            data: data ? JSON.stringify(data) : null,
            contentType: 'application/json',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json',
            },
        }).fail(function(xhr) {
            if (xhr.status === 422) {
                const errors = xhr.responseJSON?.errors ?? {};
                Object.keys(errors).forEach(f => toastr.error(errors[f][0]));
            } else if (xhr.status === 403) {
                toastr.error('Sem permissão para esta ação.');
            } else if (xhr.status === 429) {
                toastr.warning('Muitas requisições. Aguarde.');
            } else if (xhr.status !== 0) {
                toastr.error('Erro inesperado. Tente novamente.');
            }
        });
    },
    get: (url, d) => API.call('GET', url, d),
    post: (url, d) => API.call('POST', url, d),
    put: (url, d) => API.call('PUT', url, d),
    delete: (url) => API.call('DELETE', url),
};

window.escapeHtml = (t) => {
    const m = {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'};
    return String(t ?? '').replace(/[&<>"']/g, c => m[c]);
};

window.userRole    = '{{ auth()->user()->role ?? '' }}';
window.isViewer    = {{ auth()->user()?->isViewer() ? 'true' : 'false' }};
window.isAdmin     = {{ auth()->user()?->isAdmin() ? 'true' : 'false' }};
</script>

@stack('scripts')
<script>
// ── Workspace selector (agência parceira) ────────────────────────────────────
function toggleWorkspaceDropdown(e) {
    e.stopPropagation();
    const dd = document.getElementById('workspaceDropdown');
    if (!dd) return;
    dd.classList.toggle('open');
}
function switchWorkspace(tenantId) {
    const form   = document.createElement('form');
    form.method  = 'POST';
    form.style.display = 'none';

    if (tenantId) {
        form.action = `/agencia/acessar/${tenantId}`;
    } else {
        form.action = '{{ route('agency.access.exit') }}';
    }

    const csrf = document.createElement('input');
    csrf.type  = 'hidden';
    csrf.name  = '_token';
    csrf.value = document.querySelector('meta[name="csrf-token"]').content;
    form.appendChild(csrf);

    document.body.appendChild(form);
    form.submit();
}
document.addEventListener('click', function (e) {
    const dd = document.getElementById('workspaceDropdown');
    if (dd && !dd.closest('.navbar-workspace')?.contains(e.target)) {
        dd.classList.remove('open');
    }
});
// ─────────────────────────────────────────────────────────────────────────────

</script>

<script>
// ── Notification Drawer ──────────────────────────────────────────────────────
(function () {
    const NOTIF_RECENT  = '{{ route("notifications.recent") }}';
    const NOTIF_READ    = '{{ route("notifications.read", ["id" => "__ID__"]) }}';
    const NOTIF_READALL = '{{ route("notifications.read-all") }}';
    const CSRF          = '{{ csrf_token() }}';
    const TYPE_ICONS = {
        new_lead: 'bi-person-plus-fill',
        lead_assigned: 'bi-person-check',
        lead_stage_changed: 'bi-arrow-right-circle',
        whatsapp_assigned: 'bi-chat-left-dots',
        ai_intent: 'bi-lightbulb',
        campaign_completed: 'bi-megaphone',
        system: 'bi-info-circle',
    };
    const TYPE_COLORS = {
        new_lead: '#10b981',
        lead_assigned: '#3b82f6',
        lead_stage_changed: '#8b5cf6',
        whatsapp_assigned: '#0085f3',
        ai_intent: '#f59e0b',
        campaign_completed: '#ec4899',
        system: '#6b7280',
    };
    const TYPE_BG = {
        new_lead: '#ecfdf5',
        lead_assigned: '#eff6ff',
        lead_stage_changed: '#f5f3ff',
        whatsapp_assigned: '#eff6ff',
        ai_intent: '#fffbeb',
        campaign_completed: '#fdf2f8',
        system: '#f3f4f6',
    };

    function updateBadge(count) {
        ['notif-badge-num', 'notif-drawer-badge'].forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            if (count > 0) { el.classList.remove('d-none'); el.textContent = count > 99 ? '99+' : count; }
            else { el.classList.add('d-none'); }
        });
    }

    function renderDrawer(notifications) {
        const body = document.getElementById('notifDrawerBody');
        const empty = document.getElementById('notifEmpty');
        if (!notifications || !notifications.length) {
            body.innerHTML = '';
            body.appendChild(empty);
            empty.style.display = '';
            return;
        }
        empty.style.display = 'none';
        body.innerHTML = notifications.map(n => {
            const icon = TYPE_ICONS[n.type] || 'bi-bell';
            const color = TYPE_COLORS[n.type] || '#6b7280';
            const bg = TYPE_BG[n.type] || '#f3f4f6';
            const unread = !n.read ? 'unread' : '';
            return `<div class="nd-item ${unread}" onclick="notifClick('${n.id}', ${n.url ? "'" + n.url + "'" : 'null'})">
                <div class="nd-icon" style="background:${bg};color:${color};"><i class="bi ${icon}"></i></div>
                <div class="nd-content">
                    <div class="nd-title">${n.title}</div>
                    <div class="nd-body">${n.body}</div>
                    <div class="nd-time">${n.created_at}</div>
                </div>
            </div>`;
        }).join('');
    }

    window.loadNotifications = function () {
        fetch(NOTIF_RECENT, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(data => {
                updateBadge(data.unread_count || 0);
                renderDrawer(data.notifications || []);
            })
            .catch(() => {});
    };

    window.toggleNotifDrawer = function () {
        const drawer = document.getElementById('notifDrawer');
        const overlay = document.getElementById('notifDrawerOverlay');
        const isOpen = drawer.classList.contains('open');
        if (isOpen) {
            closeNotifDrawer();
        } else {
            drawer.classList.add('open');
            overlay.classList.add('open');
            loadNotifications();
        }
    };

    window.closeNotifDrawer = function () {
        document.getElementById('notifDrawer').classList.remove('open');
        document.getElementById('notifDrawerOverlay').classList.remove('open');
    };

    window.notifClick = function (id, url) {
        fetch(NOTIF_READ.replace('__ID__', id), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
        }).then(() => {
            loadNotifications();
            if (url) window.location.href = url;
        }).catch(() => {});
    };

    window.markAllNotifsRead = function () {
        fetch(NOTIF_READALL, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
        }).then(() => loadNotifications()).catch(() => {});
    };

    // Load badge on page load
    loadNotifications();

    // Real-time via WebSocket
    document.addEventListener('DOMContentLoaded', function () {
        if (window.Echo) {
            const userChannel = window.Echo.private('App.Models.User.{{ auth()->id() }}');
            userChannel.notification(function (notif) {
                if (window.toastr) {
                    toastr.info(
                        '<b>' + (notif.title || 'Notificação') + '</b><br><small>' + (notif.body || '') + '</small>',
                        '',
                        { timeOut: 8000, closeButton: true, progressBar: true, escapeHtml: false }
                    );
                }
                loadNotifications();
            });
        }
    });
})();
</script>

{{-- PWA Install Banner (Android Chrome) — versão removida, usar a de baixo --}}

@php
    $trialExpired = false;
    if (auth()->check() && !auth()->user()->isSuperAdmin()) {
        $__tenant = auth()->user()->tenant;
        if ($__tenant
            && $__tenant->status === 'trial'
            && $__tenant->trial_ends_at !== null
            && $__tenant->trial_ends_at->isPast()
            && !request()->routeIs('billing.checkout', 'billing.stripe.*', 'billing.subscribe')
        ) {
            $trialExpired = true;
        }
    }
@endphp

@if($trialExpired)
{{-- Modal bloqueante de trial expirado — não pode ser fechado --}}
<div id="trialExpiredOverlay" style="position:fixed;inset:0;z-index:99999;background:rgba(15,23,42,.7);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;padding:24px;">
    <div style="background:#fff;border-radius:20px;width:100%;max-width:460px;padding:40px 36px;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.3);">
        <div style="width:72px;height:72px;border-radius:20px;background:#fff7ed;display:flex;align-items:center;justify-content:center;margin:0 auto 24px;">
            <i class="bi bi-clock-history" style="font-size:32px;color:#f97316;"></i>
        </div>
        <div style="display:inline-block;background:#fff7ed;color:#f97316;border:1px solid #fdba74;border-radius:20px;font-size:12px;font-weight:700;padding:4px 14px;margin-bottom:16px;text-transform:uppercase;letter-spacing:.04em;">
            {{ __('common.trial_badge') }}
        </div>
        <h2 style="font-size:20px;font-weight:700;color:#1a1d23;margin:0 0 12px;">{{ __('common.trial_title') }}</h2>
        <p style="font-size:14px;color:#677489;line-height:1.6;margin:0 0 28px;">
            {!! __('common.trial_message', ['name' => auth()->user()->tenant->name]) !!}
        </p>
        <div style="display:flex;flex-direction:column;gap:10px;align-items:center;">
            <a href="{{ route('billing.checkout') }}" style="display:inline-flex;align-items:center;gap:8px;padding:11px 28px;background:#0085f3;color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;text-decoration:none;transition:background .15s;">
                <i class="bi bi-credit-card"></i> {{ __('common.trial_choose_plan') }}
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" style="display:inline-flex;align-items:center;gap:8px;padding:11px 28px;background:transparent;color:#677489;border:1.5px solid #e5e7eb;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;transition:background .15s;">
                    <i class="bi bi-box-arrow-right"></i> {{ __('common.trial_logout') }}
                </button>
            </form>
        </div>
    </div>
</div>
@endif

@include('components.cookie-consent')

{{-- Modal global: Limite do plano atingido --}}
<div id="limitReachedModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.45);align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:14px;padding:32px;width:420px;max-width:92vw;box-shadow:0 20px 60px rgba(0,0,0,.18);text-align:center;">
        <i class="bi bi-lock" style="font-size:44px;color:#f59e0b;display:block;margin-bottom:8px;"></i>
        <h3 style="font-size:17px;font-weight:700;color:#1a1d23;margin:0 0 8px;">Limite do plano atingido</h3>
        <p id="limitReachedMessage" style="color:#6b7280;font-size:14px;margin:0 0 20px;line-height:1.5;"></p>
        <div style="display:flex;gap:10px;justify-content:center;">
            <button onclick="closeLimitModal()" style="padding:9px 20px;border-radius:100px;border:1.5px solid #e8eaf0;background:#fff;font-size:13px;font-weight:600;color:#6b7280;cursor:pointer;">Fechar</button>
            <a href="{{ route('settings.billing') }}" style="padding:9px 20px;border-radius:100px;border:none;background:#0085f3;color:#fff;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
                <i class="bi bi-arrow-up-circle"></i> Fazer Upgrade
            </a>
        </div>
    </div>
</div>

<script>
function showLimitModal(message) {
    document.getElementById('limitReachedMessage').textContent = message;
    document.getElementById('limitReachedModal').style.display = 'flex';
}
function closeLimitModal() {
    document.getElementById('limitReachedModal').style.display = 'none';
}
document.getElementById('limitReachedModal').addEventListener('click', function(e) {
    if (e.target === this) closeLimitModal();
});
</script>

{{-- PWA Install Banner (Android Chrome) --}}
<div id="pwaInstallBanner" style="display:none;position:fixed;bottom:0;left:0;right:0;z-index:9999;padding:12px 24px;background:#fff;border-top:2px solid #0085f3;box-shadow:0 -4px 24px rgba(0,0,0,.12);animation:pwaSlideUp .3s ease-out;">
    <div style="display:flex;align-items:center;gap:14px;width:100%;">
        <img src="{{ asset('images/favicon-192.png') }}" alt="Syncro" style="width:42px;height:42px;border-radius:10px;flex-shrink:0;">
        <div style="flex:1;min-width:0;">
            <div style="font-weight:700;font-size:14px;color:#1a1d23;">Instalar Syncro CRM</div>
            <div style="font-size:12px;color:#6b7280;margin-top:2px;">Acesse direto da tela inicial</div>
        </div>
        <button id="pwaInstallBtn" style="background:#0085f3;color:#fff;border:none;border-radius:100px;padding:10px 24px;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap;">Instalar</button>
        <button id="pwaDismissBtn" style="background:none;border:none;color:#9ca3af;font-size:22px;cursor:pointer;padding:4px 0 4px 4px;line-height:1;" aria-label="Fechar">&times;</button>
    </div>
</div>
<style>@keyframes pwaSlideUp{from{transform:translateY(100%)}to{transform:translateY(0)}}</style>
<script>
(function(){
    var dp=null,b=document.getElementById('pwaInstallBanner');
    if(!b)return;
    if(window.matchMedia('(display-mode:standalone)').matches)return;
    if(localStorage.getItem('pwa_install_dismissed'))return;
    window.addEventListener('beforeinstallprompt',function(e){
        e.preventDefault();dp=e;b.style.display='block';
    });
    document.getElementById('pwaInstallBtn').addEventListener('click',function(){
        if(!dp)return;
        dp.prompt();
        dp.userChoice.then(function(){b.style.display='none';dp=null;});
    });
    document.getElementById('pwaDismissBtn').addEventListener('click',function(){
        b.style.display='none';
        localStorage.setItem('pwa_install_dismissed','1');
    });
    window.addEventListener('appinstalled',function(){b.style.display='none';dp=null;});
})();
</script>

{{-- Notification Drawer --}}
<div class="notif-drawer-overlay" id="notifDrawerOverlay" onclick="closeNotifDrawer()"></div>
<div class="notif-drawer" id="notifDrawer">
    <div class="notif-drawer-header">
        <div style="display:flex;align-items:center;gap:8px;">
            <span style="font-size:15px;font-weight:700;color:#1a1d23;">{{ __('common.notifications') }}</span>
            <span class="badge-num d-none" id="notif-drawer-badge" style="position:static;font-size:11px;"></span>
        </div>
        <div style="display:flex;align-items:center;gap:10px;">
            <button onclick="markAllNotifsRead()" style="font-size:12px;color:#0085f3;background:none;border:none;cursor:pointer;font-weight:600;font-family:inherit;">
                {{ __('common.mark_all_read') }}
            </button>
            <button onclick="closeNotifDrawer()" style="background:none;border:none;font-size:18px;color:#9ca3af;cursor:pointer;padding:2px;">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>
    <div class="notif-drawer-body" id="notifDrawerBody">
        <div class="notif-empty" id="notifEmpty">
            <i class="bi bi-bell" style="font-size:32px;color:#d1d5db;display:block;margin-bottom:8px;"></i>
            <span>{{ __('common.no_notifications') }}</span>
        </div>
    </div>
</div>

@include('tenant.layouts._help_widget')
@include('tenant.layouts._tour')

{{-- Global Search Modal (Cmd+K / Ctrl+K) --}}
<style>
.gs-overlay { position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:500; display:none; align-items:flex-start; justify-content:center; padding-top:12vh; }
.gs-overlay.open { display:flex; }
.gs-modal { background:#fff; border-radius:16px; width:95%; max-width:580px; box-shadow:0 20px 60px rgba(0,0,0,.2); overflow:hidden; animation:gsSlideIn .15s ease; }
@keyframes gsSlideIn { from { opacity:0; transform:translateY(-12px); } to { opacity:1; transform:translateY(0); } }
.gs-input-wrap { display:flex; align-items:center; gap:10px; padding:14px 18px; border-bottom:1px solid #f0f2f7; }
.gs-input-wrap i { color:#9ca3af; font-size:16px; }
.gs-input { flex:1; border:none; outline:none; font-size:15px; font-family:inherit; color:#1a1d23; background:transparent; }
.gs-input::placeholder { color:#9ca3af; }
.gs-kbd { font-size:10px; color:#9ca3af; background:#f3f4f6; padding:2px 6px; border-radius:4px; border:1px solid #e5e7eb; font-family:monospace; }
.gs-results { max-height:50vh; overflow-y:auto; padding:8px; }
.gs-group-label { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#9ca3af; padding:8px 10px 4px; }
.gs-item { display:flex; align-items:center; gap:10px; padding:8px 10px; border-radius:8px; cursor:pointer; transition:background .1s; text-decoration:none; color:#374151; }
.gs-item:hover, .gs-item.active { background:#f0f7ff; }
.gs-item-icon { width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:14px; flex-shrink:0; }
.gs-item-icon.lead { background:#eff6ff; color:#0085f3; }
.gs-item-icon.chat { background:#f0fdf4; color:#10b981; }
.gs-item-icon.task { background:#fff7ed; color:#f59e0b; }
.gs-item-icon.ig { background:#fdf2f8; color:#ec4899; }
.gs-item-info { flex:1; min-width:0; }
.gs-item-name { font-size:13px; font-weight:600; color:#1a1d23; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.gs-item-meta { font-size:11px; color:#9ca3af; }
.gs-empty { text-align:center; padding:32px; color:#9ca3af; font-size:13px; }
.gs-hint { text-align:center; padding:24px; color:#d1d5db; font-size:12px; }
</style>

<div class="gs-overlay" id="gsOverlay" onclick="if(event.target===this)closeGlobalSearch()">
    <div class="gs-modal">
        <div class="gs-input-wrap">
            <i class="bi bi-search"></i>
            <input class="gs-input" id="gsInput" type="text" placeholder="{{ app()->getLocale() === 'en' ? 'Search leads, conversations, tasks...' : 'Buscar leads, conversas, tarefas...' }}" autocomplete="off">
            <span class="gs-kbd">ESC</span>
        </div>
        <div class="gs-results" id="gsResults">
            <div class="gs-hint">
                <i class="bi bi-command" style="font-size:16px;display:block;margin-bottom:6px;"></i>
                {{ app()->getLocale() === 'en' ? 'Type to search across your CRM' : 'Digite para buscar em todo o CRM' }}
            </div>
        </div>
    </div>
</div>

<script>
(function(){
    const overlay = document.getElementById('gsOverlay');
    const input   = document.getElementById('gsInput');
    const results = document.getElementById('gsResults');
    const BASE    = @json(url('/'));
    const isEn    = '{{ app()->getLocale() }}' === 'en';
    let timer = null;
    let activeIdx = -1;

    // Cmd+K / Ctrl+K
    document.addEventListener('keydown', function(e) {
        if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
            e.preventDefault();
            openGlobalSearch();
        }
        if (e.key === 'Escape' && overlay.classList.contains('open')) {
            closeGlobalSearch();
        }
    });

    window.openGlobalSearch = function() {
        overlay.classList.add('open');
        input.value = '';
        results.innerHTML = '<div class="gs-hint"><i class="bi bi-command" style="font-size:16px;display:block;margin-bottom:6px;"></i>' + (isEn ? 'Type to search across your CRM' : 'Digite para buscar em todo o CRM') + '</div>';
        activeIdx = -1;
        setTimeout(function(){ input.focus(); }, 50);
    };

    window.closeGlobalSearch = function() {
        overlay.classList.remove('open');
        input.value = '';
    };

    // Debounced search
    input.addEventListener('input', function() {
        clearTimeout(timer);
        const q = this.value.trim();
        if (q.length < 2) {
            results.innerHTML = '<div class="gs-hint"><i class="bi bi-command" style="font-size:16px;display:block;margin-bottom:6px;"></i>' + (isEn ? 'Type to search across your CRM' : 'Digite para buscar em todo o CRM') + '</div>';
            return;
        }
        timer = setTimeout(function(){ doSearch(q); }, 300);
    });

    // Arrow keys navigation
    input.addEventListener('keydown', function(e) {
        const items = results.querySelectorAll('.gs-item');
        if (e.key === 'ArrowDown') { e.preventDefault(); activeIdx = Math.min(activeIdx + 1, items.length - 1); highlightItem(items); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); activeIdx = Math.max(activeIdx - 1, 0); highlightItem(items); }
        else if (e.key === 'Enter' && activeIdx >= 0 && items[activeIdx]) { e.preventDefault(); items[activeIdx].click(); }
    });

    function highlightItem(items) {
        items.forEach(function(el, i) { el.classList.toggle('active', i === activeIdx); });
        if (items[activeIdx]) items[activeIdx].scrollIntoView({ block: 'nearest' });
    }

    function doSearch(q) {
        fetch(BASE + '/busca?q=' + encodeURIComponent(q), {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            activeIdx = -1;
            let html = '';
            const labels = isEn
                ? { leads: 'Leads', conversations: 'Conversations', tasks: 'Tasks' }
                : { leads: 'Leads', conversations: 'Conversas', tasks: 'Tarefas' };

            if (data.leads && data.leads.length) {
                html += '<div class="gs-group-label">' + labels.leads + '</div>';
                data.leads.forEach(function(l) {
                    html += '<a class="gs-item" href="' + BASE + l.url + '">' +
                        '<div class="gs-item-icon lead"><i class="bi bi-person"></i></div>' +
                        '<div class="gs-item-info"><div class="gs-item-name">' + esc(l.name) + '</div>' +
                        '<div class="gs-item-meta">' + esc(l.phone || '') + (l.email ? ' · ' + esc(l.email) : '') + (l.stage ? ' — ' + esc(l.stage) : '') + '</div></div></a>';
                });
            }

            if (data.conversations && data.conversations.length) {
                html += '<div class="gs-group-label">' + labels.conversations + '</div>';
                data.conversations.forEach(function(c) {
                    var iconClass = c.channel === 'instagram' ? 'ig' : 'chat';
                    var icon = c.channel === 'instagram' ? 'bi-instagram' : 'bi-whatsapp';
                    html += '<a class="gs-item" href="' + BASE + c.url + '">' +
                        '<div class="gs-item-icon ' + iconClass + '"><i class="bi ' + icon + '"></i></div>' +
                        '<div class="gs-item-info"><div class="gs-item-name">' + esc(c.name) + '</div>' +
                        '<div class="gs-item-meta">' + esc(c.phone || '') + ' · ' + esc(c.status) + '</div></div></a>';
                });
            }

            if (data.tasks && data.tasks.length) {
                html += '<div class="gs-group-label">' + labels.tasks + '</div>';
                data.tasks.forEach(function(t) {
                    html += '<a class="gs-item" href="' + BASE + t.url + '">' +
                        '<div class="gs-item-icon task"><i class="bi bi-check2-square"></i></div>' +
                        '<div class="gs-item-info"><div class="gs-item-name">' + esc(t.subject) + '</div>' +
                        '<div class="gs-item-meta">' + esc(t.type) + (t.due_date ? ' · ' + esc(t.due_date) : '') + '</div></div></a>';
                });
            }

            if (!html) {
                html = '<div class="gs-empty">' + (isEn ? 'No results found' : 'Nenhum resultado encontrado') + '</div>';
            }

            results.innerHTML = html;
        });
    }

    function esc(s) { return s ? String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;') : ''; }
})();
</script>

</body>
</html>

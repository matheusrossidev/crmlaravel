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

        /* ===== SIDEBAR ===== */
        .sidebar {
            position: fixed;
            top: 0; left: 0;
            width: 260px;
            height: 100vh;
            background: #fff;
            border-right: 1px solid #e8eaf0;
            display: flex;
            flex-direction: column;
            z-index: 100;
            overflow: hidden;
            transition: width .22s ease;
        }

        .sidebar-nav-scroll {
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
            scrollbar-width: none;
        }
        .sidebar-nav-scroll::-webkit-scrollbar { display: none; }

        .sidebar-logo {
            padding: 14px 16px;
            border-bottom: 1px solid #f0f2f7;
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 64px;
            flex-shrink: 0;
        }

        /* Logo: full e icon-only */
        .logo-full {
            max-height: 36px;
            max-width: 160px;
            object-fit: contain;
            flex: 1;
            min-width: 0;
            transition: opacity .15s;
        }

        .logo-icon-only {
            display: none;
            width: 32px;
            height: 32px;
            object-fit: contain;
            flex-shrink: 0;
        }

        /* Botão colapsar */
        .sidebar-collapse-btn {
            width: 28px;
            height: 28px;
            border-radius: 7px;
            border: 1px solid #e8eaf0;
            background: #fff;
            color: #97A3B7;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all .15s;
            font-size: 14px;
            flex-shrink: 0;
            margin-left: auto;
        }
        .sidebar-collapse-btn:hover { background: #f4f6fb; color: #007DFF; border-color: #CDDEF6; }

        .sidebar-logo .logo-icon {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #007DFF, #6366F1);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 16px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .sidebar-logo .logo-text {
            font-size: 15px;
            font-weight: 700;
            color: #1a1d23;
            line-height: 1.2;
        }

        .sidebar-logo .logo-sub {
            font-size: 11px;
            color: #97A3B7;
            font-weight: 400;
        }

        /* Workspace selector */
        .workspace-selector-wrap { position: relative; }
        .workspace-dropdown {
            display: none;
            position: absolute;
            left: 14px; right: 14px; top: calc(100% + 4px);
            background: #fff;
            border: 1px solid #e8eaf0;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,.12);
            z-index: 200;
            overflow: hidden;
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
        .workspace-selector {
            margin: 12px 14px;
            padding: 10px 12px;
            background: #f8fafc;
            border: 1px solid #e8eaf0;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: background .15s, padding .22s;
            flex-shrink: 0;
        }

        .workspace-selector:hover { background: #f0f4ff; }

        .workspace-avatar {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: #2a84ef;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .workspace-name {
            font-size: 13px;
            font-weight: 600;
            color: #1a1d23;
            flex: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Nav grupos */
        .nav-group {
            padding: 8px 14px 4px;
            flex-shrink: 0;
        }

        .nav-group-label {
            font-size: 10px;
            font-weight: 700;
            color: #97A3B7;
            letter-spacing: .08em;
            text-transform: uppercase;
            padding: 0 6px;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 10px;
            border-radius: 9px;
            color: #677489;
            font-size: 13.5px;
            font-weight: 500;
            text-decoration: none;
            transition: all .15s;
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
        }

        .nav-item:hover {
            background: #f4f6fb;
            color: #007DFF;
        }

        .nav-item.active {
            background: #eff6ff;
            color: #007DFF;
            font-weight: 600;
        }

        .nav-item.active .nav-icon { color: #007DFF; }

        .nav-icon {
            font-size: 16px;
            width: 20px;
            text-align: center;
            flex-shrink: 0;
        }

        /* Rótulos que somem no collapse */
        .nav-label {
            transition: opacity .15s;
        }

        /* Sidebar bottom: user info */
        .sidebar-footer {
            margin-top: auto;
            padding: 14px;
            border-top: 1px solid #f0f2f7;
            flex-shrink: 0;
        }

        .user-card {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 10px;
            border-radius: 10px;
            cursor: pointer;
            transition: background .15s;
        }

        .user-card:hover { background: #f4f6fb; }

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

        .user-info .user-name {
            font-size: 13px;
            font-weight: 600;
            color: #1a1d23;
        }

        .user-info .user-role {
            font-size: 11px;
            color: #97A3B7;
        }

        /* ===== TOPBAR ===== */
        .topbar {
            position: fixed;
            top: 0;
            left: 260px;
            right: 0;
            height: 64px;
            background: #fff;
            border-bottom: 1px solid #e8eaf0;
            display: flex;
            align-items: center;
            padding: 0 24px;
            gap: 16px;
            z-index: 99;
            transition: left .22s ease;
        }

        .topbar-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 16px;
            font-weight: 600;
            color: #1a1d23;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .topbar-title .page-icon {
            color: #007DFF;
            font-size: 18px;
        }

        .topbar-spacer { flex: 1; }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 8px;
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
            margin-left: 260px;
            padding-top: 64px;
            min-height: 100vh;
            transition: margin-left .22s ease;
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

        /* ===== SUBMENU ===== */
        .nav-submenu-wrap { position: relative; }

        .nav-submenu-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 10px;
            border-radius: 9px;
            color: #677489;
            font-size: 13.5px;
            font-weight: 500;
            text-decoration: none;
            transition: all .15s;
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
        }

        .nav-submenu-toggle:hover {
            background: #f4f6fb;
            color: #007DFF;
        }

        .nav-submenu {
            padding-left: 14px;
            margin-bottom: 2px;
        }

        .nav-subitem {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 7px 10px;
            border-radius: 9px;
            color: #677489;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            transition: all .15s;
            margin-bottom: 1px;
            white-space: nowrap;
            overflow: hidden;
        }

        .nav-subitem:hover {
            background: #f4f6fb;
            color: #007DFF;
        }

        .nav-subitem.active {
            background: #eff6ff;
            color: #007DFF;
            font-weight: 600;
        }

        /* ===== SIDEBAR COLAPSADO ===== */
        .sidebar--collapsed {
            width: 72px;
        }

        /* Logo: troca full → favicon */
        .sidebar--collapsed .logo-full { display: none; }
        .sidebar--collapsed .logo-icon-only { display: block; }
        .sidebar--collapsed .sidebar-logo {
            justify-content: center;
            flex-direction: column;
            gap: 6px;
            padding: 12px 8px;
        }
        .sidebar--collapsed .sidebar-collapse-btn { margin-left: 0; }

        /* Workspace: só o avatar */
        .sidebar--collapsed .workspace-selector {
            justify-content: center;
            padding: 10px 8px;
            margin: 12px 10px;
        }
        .sidebar--collapsed .workspace-name,
        .sidebar--collapsed .workspace-chevron { display: none; }

        /* Nav: centraliza ícone, esconde label e grupo */
        .sidebar--collapsed .nav-group { padding: 8px 10px 4px; }
        .sidebar--collapsed .nav-group-label { display: none; }
        .sidebar--collapsed .nav-label { display: none; }
        .sidebar--collapsed .nav-item {
            justify-content: center;
            padding: 9px;
            gap: 0;
        }
        .sidebar--collapsed .nav-submenu-toggle {
            justify-content: center;
            padding: 9px;
            gap: 0;
        }
        .sidebar--collapsed .nav-chevron { display: none; }
        .sidebar--collapsed .nav-submenu-wrap { overflow: visible; }

        /* Submenu: oculto no modo colapsado; reabre como flyout no hover */
        .sidebar--collapsed .nav-submenu { display: none !important; }
        .sidebar--collapsed .nav-submenu-wrap:hover .nav-submenu {
            display: block !important;
            position: fixed;
            left: 72px;
            width: 190px;
            background: #fff;
            border: 1px solid #e8eaf0;
            border-radius: 10px;
            padding: 6px;
            box-shadow: 0 6px 20px rgba(0,0,0,.1);
            z-index: 300;
        }
        .sidebar--collapsed .nav-submenu-wrap:hover .nav-subitem {
            white-space: normal;
        }

        /* Footer: só avatar */
        .sidebar--collapsed .user-info,
        .sidebar--collapsed .user-dots { display: none; }
        .sidebar--collapsed .user-card {
            justify-content: center;
            padding: 8px;
        }

        /* ===== RESPONSIVO ===== */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); transition: transform .25s; width: 260px !important; }
            .sidebar.open { transform: translateX(0); }
            .sidebar--collapsed { transform: translateX(-100%); width: 260px !important; }
            .sidebar--collapsed.open { transform: translateX(0); }
            /* Forçar sidebar expandido no mobile mesmo se collapsed no desktop */
            .sidebar--collapsed .nav-label { display: block !important; }
            .sidebar--collapsed .nav-group-label { display: block !important; }
            .sidebar--collapsed .nav-item { justify-content: flex-start !important; padding: 9px 14px !important; gap: 11px !important; }
            .sidebar--collapsed .nav-submenu-toggle { justify-content: flex-start !important; padding: 9px 14px !important; gap: 11px !important; }
            .sidebar--collapsed .nav-chevron { display: block !important; }
            .sidebar--collapsed .logo-full { display: block !important; }
            .sidebar--collapsed .logo-icon-only { display: none !important; }
            .sidebar--collapsed .sidebar-logo { padding: 18px 18px 12px !important; }
            .sidebar--collapsed .user-info { display: block !important; }
            .sidebar--collapsed .user-dots { display: block !important; }
            .sidebar--collapsed .user-card { justify-content: flex-start !important; padding: 12px 14px !important; }
            .sidebar--collapsed .nav-submenu { position: static !important; width: auto !important; border: none !important; box-shadow: none !important; background: transparent !important; padding: 0 !important; }
            .topbar { left: 0 !important; padding: 0 12px; gap: 8px; }
            .main-content { margin-left: 0 !important; }
            .sidebar-collapse-btn { display: none; }
            .page-container { padding: 16px 14px; }
            .topbar-title { font-size: 14px; }
            .topbar-title .page-icon { font-size: 15px; }
            .topbar-actions { flex-shrink: 1; min-width: 0; }
            .section-title { font-size: 17px; }
            .section-subtitle { font-size: 12.5px; }
        }
        @media (max-width: 480px) {
            .page-container { padding: 12px 10px; }
            .topbar-title { font-size: 13px; }
            .topbar-title .page-icon { font-size: 14px; }
            .section-title { font-size: 16px; }
            .section-subtitle { font-size: 12px; }
        }
    </style>
    <script>
        // Aplica estado da sidebar ANTES do render para evitar flash
        (function(){
            var s = localStorage.getItem('sidebar_collapsed');
            window.__sidebarCollapsed = s === null ? true : s === '1';
        }());
    </script>
</head>
<body>

{{-- ===== SIDEBAR ===== --}}
<aside class="sidebar" id="sidebar">
<script>if(window.__sidebarCollapsed) document.getElementById('sidebar').classList.add('sidebar--collapsed');</script>

    {{-- Logo --}}
    <div class="sidebar-logo">
        <img class="logo-full"
             src="{{ asset('images/logo.png') }}"
             alt="Logo">
        <img class="logo-icon-only"
             src="{{ asset('images/favicon.png') }}"
             alt="Logo">
        <button class="sidebar-collapse-btn" id="sidebarCollapseBtn" title="Recolher menu">
            <i class="bi bi-layout-sidebar" id="collapseIcon"></i>
        </button>
    </div>

    {{-- Workspace --}}
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
    @endphp

    <div class="workspace-selector-wrap">
        <div class="workspace-selector {{ $isPartnerUser ? 'ws-partner' : '' }}"
             title="{{ $activeTenant?->name ?? 'Minha Empresa' }}"
             @if($isPartnerUser) onclick="toggleWorkspaceDropdown(event)" style="cursor:pointer;" @endif>
            <div class="workspace-avatar">
                @if($activeTenant?->logo)
                    <img src="{{ $activeTenant->logo }}"
                         style="width:100%;height:100%;object-fit:cover;border-radius:8px;" alt="">
                @else
                    {{ strtoupper(substr($activeTenant?->name ?? 'P', 0, 1)) }}
                @endif
            </div>
            <span class="workspace-name nav-label">{{ $activeTenant?->name ?? 'Minha Empresa' }}</span>
            @if($isPartnerUser)
                <i class="bi bi-chevron-expand nav-label" id="wsChevron"
                   style="font-size:12px;color:#97A3B7;margin-left:auto;"></i>
            @endif
        </div>

        @if($isPartnerUser)
        <div class="workspace-dropdown" id="workspaceDropdown">
            {{-- Própria conta --}}
            <div class="workspace-dd-item {{ !$impersonatingId ? 'active' : '' }}"
                 onclick="switchWorkspace(null)">
                <div class="workspace-dd-avatar" style="background:#7C3AED;">
                    {{ strtoupper(substr($authTenant->name ?? 'P', 0, 1)) }}
                </div>
                <div style="min-width:0;">
                    <div style="font-size:12.5px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                        {{ $authTenant->name }}
                    </div>
                    <div style="font-size:11px;color:#97A3B7;">Minha conta</div>
                </div>
                @if(!$impersonatingId)<i class="bi bi-check2" style="margin-left:auto;color:#7C3AED;"></i>@endif
            </div>

            @if($partnerClients->isNotEmpty())
            <hr class="workspace-dd-divider">
            <div style="padding:6px 14px 4px;font-size:10.5px;font-weight:700;color:#97A3B7;text-transform:uppercase;letter-spacing:.06em;">
                Clientes
            </div>
            @foreach($partnerClients as $client)
            <div class="workspace-dd-item {{ (int)$impersonatingId === (int)$client->id ? 'active' : '' }}"
                 onclick="switchWorkspace({{ $client->id }})">
                <div class="workspace-dd-avatar" style="background:#007DFF;">
                    {{ strtoupper(substr($client->name, 0, 1)) }}
                </div>
                <div style="min-width:0;">
                    <div style="font-size:12.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                        {{ $client->name }}
                    </div>
                </div>
                @if((int)$impersonatingId === (int)$client->id)
                    <i class="bi bi-check2" style="margin-left:auto;color:#007DFF;"></i>
                @endif
            </div>
            @endforeach
            @else
            <hr class="workspace-dd-divider">
            <div style="padding:12px 14px;font-size:12.5px;color:#97A3B7;text-align:center;">
                Nenhum cliente vinculado ainda.
            </div>
            @endif
        </div>
        @endif
    </div>

    {{-- Nav: Geral --}}
    <div class="sidebar-nav-scroll">
    <nav class="nav-group">
        <div class="nav-group-label">Geral</div>

        <a href="{{ route('inicio') }}"
           class="nav-item {{ request()->routeIs('inicio', 'dashboard') ? 'active' : '' }}"
           title="Início">
            <i class="bi bi-house nav-icon"></i>
            <span class="nav-label">Início</span>
        </a>

        <a href="{{ route('crm.kanban') }}"
           class="nav-item {{ request()->routeIs('crm*') ? 'active' : '' }}"
           title="CRM">
            <i class="bi bi-kanban nav-icon"></i>
            <span class="nav-label">CRM</span>
        </a>

        <a href="{{ route('leads.index') }}"
           class="nav-item {{ request()->routeIs('leads*') ? 'active' : '' }}"
           title="Contatos">
            <i class="bi bi-people nav-icon"></i>
            <span class="nav-label">Contatos</span>
        </a>

        <a href="{{ route('campaigns.index') }}"
           class="nav-item {{ request()->routeIs('campaigns*') ? 'active' : '' }}"
           title="Campanhas">
            <i class="bi bi-megaphone nav-icon"></i>
            <span class="nav-label">Campanhas</span>
        </a>

        <a href="{{ route('chats.index') }}"
           class="nav-item {{ request()->routeIs('chats.*') ? 'active' : '' }}"
           title="Chats">
            <i class="bi bi-chat-dots nav-icon"></i>
            <span class="nav-label">Chats</span>
        </a>

        <a href="{{ route('calendar.index') }}"
           class="nav-item {{ request()->routeIs('calendar.*') ? 'active' : '' }}"
           title="Agenda">
            <i class="bi bi-calendar3 nav-icon"></i>
            <span class="nav-label">Agenda</span>
        </a>
        <a href="{{ route('reports.index') }}"
           class="nav-item {{ request()->routeIs('reports*') ? 'active' : '' }}"
           title="Relatórios">
            <i class="bi bi-bar-chart-line nav-icon"></i>
            <span class="nav-label">Relatórios</span>
        </a>

        <a href="{{ route('chatbot.flows.index') }}"
           class="nav-item {{ request()->routeIs('chatbot.flows.*') ? 'active' : '' }}"
           title="Chatbot Builder">
            <i class="bi bi-diagram-3 nav-icon"></i>
            <span class="nav-label">Chatbot</span>
        </a>

        <a href="{{ route('ai.agents.index') }}"
           class="nav-item {{ request()->routeIs('ai.agents.*') ? 'active' : '' }}"
           title="Agentes de IA">
            <i class="bi bi-robot nav-icon"></i>
            <span class="nav-label">Agentes de IA</span>
        </a>

        <a href="{{ route('settings.profile') }}"
           class="nav-item {{ request()->routeIs('settings.*') || request()->routeIs('billing.*') ? 'active' : '' }}"
           title="Configurações">
            <i class="bi bi-gear nav-icon"></i>
            <span class="nav-label">Configurações</span>
        </a>
    </nav>

    @if(auth()->user()->isSuperAdmin())
    <nav class="nav-group">
        <div class="nav-group-label">Master</div>
        <a href="{{ route('master.tenants') }}"
           class="nav-item {{ request()->routeIs('master.tenants*') ? 'active' : '' }}"
           title="Painel Master">
            <i class="bi bi-shield-check nav-icon"></i>
            <span class="nav-label">Painel Master</span>
        </a>
    </nav>
    @endif
    </div>{{-- /.sidebar-nav-scroll --}}

    {{-- Footer: User --}}
    <div class="sidebar-footer">
        <div class="dropdown">
            <div class="user-card" data-bs-toggle="dropdown" aria-expanded="false"
                 title="{{ auth()->user()->name }}">
                <div class="user-avatar">
                    @if(auth()->user()->avatar)
                        <img src="{{ auth()->user()->avatar }}" alt="{{ auth()->user()->name }}"
                             style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                    @else
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    @endif
                </div>
                <div class="user-info nav-label">
                    <div class="user-name">{{ auth()->user()->name }}</div>
                    <div class="user-role">{{ ucfirst(auth()->user()->role) }}</div>
                </div>
                <i class="bi bi-three-dots-vertical user-dots nav-label"
                   style="color:#97A3B7;margin-left:auto;flex-shrink:0;"></i>
            </div>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="min-width:180px;border-radius:10px;">
                <li><a class="dropdown-item" href="{{ route('settings.profile') }}"><i class="bi bi-person me-2"></i>Meu Perfil</a></li>
                <li><hr class="dropdown-divider my-1"></li>
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

</aside>
<div id="sidebarOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:99;"></div>

{{-- ===== TOPBAR ===== --}}
<header class="topbar" id="topbar" style="transition:none;">
<script>if(window.__sidebarCollapsed && window.innerWidth > 768) document.getElementById('topbar').style.left='72px';</script>
    <button class="topbar-btn d-md-none" id="sidebarToggle" style="border:none;">
        <i class="bi bi-list"></i>
    </button>

    <div class="topbar-title">
        <i class="bi bi-{{ $pageIcon ?? 'house' }} page-icon"></i>
        {{ $title ?? 'Início' }}
    </div>

    <div class="topbar-spacer"></div>

    {{-- Slot para ações da página (botões, filtros, etc) --}}
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
            <span>Trial: <strong>{{ $__trialDays }} {{ $__trialDays === 1 ? 'dia' : 'dias' }}</strong></span>
        </div>
        <div class="trial-widget-bar">
            <div class="trial-widget-bar-fill" style="width:{{ $__trialPct }}%"></div>
        </div>
    </div>
    @endif

    {{-- Bell de notificações + avatar — sempre visíveis --}}
    <div class="topbar-actions">
        <div class="dropdown">
            <button class="topbar-btn" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                    id="notif-bell-btn" title="Notificações">
                <i class="bi bi-bell"></i>
                <span class="badge-num d-none" id="notif-badge-num"></span>
            </button>
            <div class="dropdown-menu dropdown-menu-end shadow" id="notif-panel"
                 style="width:340px;max-height:420px;overflow-y:auto;border-radius:12px;padding:0;">
                <div style="padding:12px 16px;border-bottom:1px solid #f0f0f0;display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;background:#fff;z-index:1;">
                    <span style="font-weight:700;font-size:13px;">Notificações</span>
                    <button onclick="markAllIntentRead()" type="button" class="btn btn-link btn-sm p-0"
                            style="font-size:11px;text-decoration:none;color:#677489;">Marcar todas lidas</button>
                </div>
                <div id="notif-list">
                    <div style="padding:24px;text-align:center;color:#97A3B7;font-size:12px;">Nenhuma notificação</div>
                </div>
            </div>
        </div>
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
</header>

{{-- ===== CONTEÚDO PRINCIPAL ===== --}}
<main class="main-content" id="mainContent" style="transition:none;">
<script>if(window.__sidebarCollapsed && window.innerWidth > 768) document.getElementById('mainContent').style.marginLeft='72px';</script>
    @if($__showTrial ?? false)
    <div class="trial-mobile-banner">
        <i class="bi bi-clock-history"></i>
        <span>Trial: {{ $__trialDays }} {{ $__trialDays === 1 ? 'dia' : 'dias' }} restantes</span>
        <div class="trial-mobile-bar">
            <div class="trial-mobile-bar-fill" style="width:{{ $__trialPct }}%"></div>
        </div>
    </div>
    @endif
    @if(session('impersonating_tenant_id'))
    @php $impTarget = \App\Models\Tenant::withoutGlobalScope('tenant')->find(session('impersonating_tenant_id')); @endphp
    @if($impTarget)
    <div style="background:#FEF3C7;border-bottom:2px solid #F59E0B;padding:10px 20px;display:flex;align-items:center;justify-content:space-between;gap:16px;font-size:13.5px;">
        <div style="display:flex;align-items:center;gap:10px;">
            <i class="bi bi-eye-fill" style="color:#D97706;font-size:16px;"></i>
            <span style="color:#92400E;">
                Você está acessando a conta de
                <strong style="color:#78350F;">{{ $impTarget->name }}</strong>
                como agência parceira.
            </span>
        </div>
        <form method="POST" action="{{ route('agency.access.exit') }}" style="margin:0;">
            @csrf
            <button type="submit"
                    style="background:#D97706;color:#fff;border:none;border-radius:7px;padding:6px 14px;font-size:13px;font-weight:600;cursor:pointer;">
                <i class="bi bi-box-arrow-right me-1"></i> Sair e voltar para minha conta
            </button>
        </form>
    </div>
    @endif
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

// ── Sidebar collapse ──────────────────────────────────────────────────────
(function () {
    const STORAGE_KEY = 'sidebar_collapsed';
    const sidebar     = document.getElementById('sidebar');
    const topbar      = document.getElementById('topbar');
    const main        = document.getElementById('mainContent');
    const icon        = document.getElementById('collapseIcon');

    function applyState(collapsed, animate) {
        if (!animate) {
            sidebar.style.transition  = 'none';
            topbar.style.transition   = 'none';
            main.style.transition     = 'none';
        }

        if (collapsed) {
            sidebar.classList.add('sidebar--collapsed');
            topbar.style.left        = '72px';
            main.style.marginLeft    = '72px';
            if (icon) { icon.className = 'bi bi-layout-sidebar-reverse'; }
        } else {
            sidebar.classList.remove('sidebar--collapsed');
            topbar.style.left        = '260px';
            main.style.marginLeft    = '260px';
            if (icon) { icon.className = 'bi bi-layout-sidebar'; }
        }

        // Reativa transições após o frame inicial
        if (!animate) {
            requestAnimationFrame(() => {
                sidebar.style.transition  = '';
                topbar.style.transition   = '';
                main.style.transition     = '';
            });
        }
    }

    // Estado já aplicado por inline scripts — só atualiza ícone e restaura transições
    const saved = window.__sidebarCollapsed;
    if (icon) { icon.className = saved ? 'bi bi-layout-sidebar-reverse' : 'bi bi-layout-sidebar'; }
    requestAnimationFrame(() => {
        sidebar.style.transition = '';
        topbar.style.transition  = '';
        main.style.transition    = '';
    });

    // Botão toggle
    document.getElementById('sidebarCollapseBtn')?.addEventListener('click', function () {
        const willCollapse = !sidebar.classList.contains('sidebar--collapsed');
        applyState(willCollapse, true);
        localStorage.setItem(STORAGE_KEY, willCollapse ? '1' : '0');
    });
}());

// ── Sidebar mobile toggle ─────────────────────────────────────────────────
document.getElementById('sidebarToggle')?.addEventListener('click', () => {
    const sb = document.getElementById('sidebar');
    const ov = document.getElementById('sidebarOverlay');
    sb.classList.toggle('open');
    ov.style.display = sb.classList.contains('open') ? 'block' : 'none';
});
document.getElementById('sidebarOverlay')?.addEventListener('click', () => {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').style.display = 'none';
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
    if (dd && !dd.closest('.workspace-selector-wrap')?.contains(e.target)) {
        dd.classList.remove('open');
    }
});
// ─────────────────────────────────────────────────────────────────────────────

</script>

<script>
// ── Notification Bell (Intent Signals + AI Analyst) ──────────────────────────
(function () {
    const LIST_URL      = '{{ route("ai.intent-signals.list") }}';
    const READ_ALL      = '{{ route("ai.intent-signals.read-all") }}';
    const ANALYST_URL   = '{{ route("analyst.pending-count") }}';
    const MASTER_URL    = '{{ route("master-notifications.index") }}';
    const CSRF          = '{{ csrf_token() }}';
    const ICONS         = { buy: '🛒', schedule: '📅', close: '🤝', interest: '⭐' };
    const CONV_BASE     = '{{ rtrim(url("/"), "/") }}';
    let _masterNotifs   = [];

    function updateBadge(count) {
        const num = document.getElementById('notif-badge-num');
        if (!num) return;
        if (count > 0) {
            num.classList.remove('d-none');
            num.textContent = count > 99 ? '99+' : count;
        } else {
            num.classList.add('d-none');
        }
    }

    function renderList(signals, analystItems, masterItems) {
        const el = document.getElementById('notif-list');
        if (!el) return;

        const lastReadId = parseInt(localStorage.getItem('mn_last_read') || '0');
        const MASTER_TYPE_ICONS = { info: 'bi-info-circle-fill', warning: 'bi-exclamation-triangle-fill', alert: 'bi-exclamation-octagon-fill' };
        const MASTER_TYPE_COLORS = { info: '#3b82f6', warning: '#f59e0b', alert: '#ef4444' };
        const masterHtml = (masterItems && masterItems.length) ? [
            `<div style="padding:6px 16px 4px;font-size:10px;font-weight:700;color:#677489;letter-spacing:.5px;background:#f8fafc;border-bottom:1px solid #f0f2f7;display:flex;align-items:center;gap:5px;"><i class="bi bi-megaphone-fill"></i> AVISOS DO SISTEMA</div>`,
            ...masterItems.map(n => {
                const unread = n.id > lastReadId ? 'unread' : '';
                const iconClass = MASTER_TYPE_ICONS[n.type] || 'bi-info-circle-fill';
                const iconColor = MASTER_TYPE_COLORS[n.type] || '#3b82f6';
                const ts = new Date(n.created_at).toLocaleDateString('pt-BR', { day:'2-digit', month:'2-digit', year:'2-digit' });
                return `<div class="notif-item ${unread}" style="padding:10px 16px;border-bottom:1px solid #f3f4f6;">
                          <div style="display:flex;align-items:flex-start;gap:10px;">
                            <i class="bi ${iconClass}" style="font-size:16px;flex-shrink:0;margin-top:2px;color:${iconColor};"></i>
                            <div style="flex:1;min-width:0;">
                              <div style="font-size:12px;font-weight:600;color:#1a1d23;margin-bottom:2px;">${n.title}</div>
                              <div style="font-size:11px;color:#677489;line-height:1.4;">${n.body}</div>
                              <div style="font-size:10px;color:#97A3B7;margin-top:3px;">${ts}</div>
                            </div>
                          </div>
                        </div>`;
            })
        ].join('') : '';

        const intentHtml = (signals && signals.length) ? signals.map(s => {
            const icon    = ICONS[s.intent_type] || '⭐';
            const unread  = !s.read_at ? 'unread' : '';
            const convBtn = s.conversation_id
                ? `<a href="${CONV_BASE}/whatsapp?conv=${s.conversation_id}"
                      class="btn btn-link btn-sm p-0" style="font-size:11px;text-decoration:none;flex-shrink:0;"
                      onclick="event.stopPropagation()">Ver</a>`
                : '';
            return `<div class="notif-item ${unread}" data-id="${s.id}"
                        style="padding:10px 16px;border-bottom:1px solid #f3f4f6;cursor:pointer;"
                        onclick="markIntentRead(${s.id}, this)">
                      <div style="display:flex;align-items:flex-start;gap:10px;">
                        <span style="font-size:18px;flex-shrink:0;margin-top:1px;">${icon}</span>
                        <div style="flex:1;min-width:0;">
                          <div style="font-size:12px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${s.contact_name}</div>
                          <div style="font-size:11px;color:#677489;margin:2px 0;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">${s.context}</div>
                          <div style="font-size:10px;color:#97A3B7;">${s.time_ago}</div>
                        </div>
                        ${convBtn}
                      </div>
                    </div>`;
        }).join('') : '';

        const TYPE_ICONS_BELL = { stage_change: '📊', add_tag: '🏷️', add_note: '📝', fill_field: '📋', update_lead: '✏️' };
        const analystHtml = (analystItems && analystItems.length) ? [
            `<div style="padding:6px 16px 4px;font-size:10px;font-weight:700;color:#10b981;letter-spacing:.5px;background:#f0fdf4;border-bottom:1px solid #bbf7d0;">🤖 SUGESTÕES DA IA</div>`,
            ...analystItems.map(s => {
                const icon = TYPE_ICONS_BELL[s.type] || '🤖';
                const convLink = s.conversation_id
                    ? `<a href="${CONV_BASE}/chats?open=${s.conversation_id}" style="font-size:11px;color:#10b981;text-decoration:none;flex-shrink:0;" onclick="event.stopPropagation()">Ver</a>`
                    : '';
                return `<div style="padding:8px 16px;border-bottom:1px solid #f3f4f6;">
                          <div style="display:flex;align-items:flex-start;gap:8px;">
                            <span style="font-size:16px;flex-shrink:0;">${icon}</span>
                            <div style="flex:1;min-width:0;">
                              <div style="font-size:12px;font-weight:600;color:#065f46;">${s.lead_name}</div>
                              <div style="font-size:11px;color:#677489;">${s.type_label}</div>
                              <div style="font-size:10px;color:#97A3B7;">${s.time_ago}</div>
                            </div>
                            ${convLink}
                          </div>
                        </div>`;
            })
        ].join('') : '';

        if (!intentHtml && !analystHtml && !masterHtml) {
            el.innerHTML = '<div style="padding:24px;text-align:center;color:#97A3B7;font-size:12px;">Nenhuma notificação</div>';
            return;
        }

        el.innerHTML = masterHtml + analystHtml + intentHtml;
    }

    window.loadIntentSignals = function () {
        Promise.all([
            fetch(LIST_URL,    { headers: { 'X-Requested-With': 'XMLHttpRequest' } }).then(r => r.json()).catch(() => ({ signals: [], unread_count: 0 })),
            fetch(ANALYST_URL, { headers: { 'X-Requested-With': 'XMLHttpRequest' } }).then(r => r.json()).catch(() => ({ count: 0, recent: [] })),
            fetch(MASTER_URL,  { headers: { 'X-Requested-With': 'XMLHttpRequest' } }).then(r => r.json()).catch(() => ({ notifications: [] })),
        ]).then(([intentData, analystData, masterData]) => {
            _masterNotifs = masterData.notifications || [];
            const lastReadId = parseInt(localStorage.getItem('mn_last_read') || '0');
            const masterUnread = _masterNotifs.filter(n => n.id > lastReadId).length;
            const total = (intentData.unread_count || 0) + (analystData.count || 0) + masterUnread;
            updateBadge(total);
            renderList(intentData.signals || [], analystData.recent || [], _masterNotifs);
        });
    };

    window.markIntentRead = function (id, el) {
        fetch(`/ia/sinais/${id}/lida`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
        }).then(() => {
            el.classList.remove('unread');
            loadIntentSignals();
        }).catch(() => {});
    };

    window.markAllIntentRead = function () {
        fetch(READ_ALL, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
        }).then(() => loadIntentSignals()).catch(() => {});
    };

    // Carregar ao iniciar
    loadIntentSignals();

    // Escutar evento em tempo real via WebSocket
    document.addEventListener('DOMContentLoaded', function () {
        if (window.Echo) {
            const channel = window.Echo.private('tenant.{{ auth()->user()->tenant_id }}');

            channel.listen('.ai.intent', function (data) {
                const icon = ICONS[data.intent_type] || '⭐';
                if (window.toastr) {
                    toastr.info(
                        `${icon} <b>${data.contact_name}</b>: ${data.context}`,
                        'Sinal de Intenção',
                        { timeOut: 8000, closeButton: true, progressBar: true, escapeHtml: false }
                    );
                }
                if (window.NotifManager) {
                    window.NotifManager.notify(
                        'Sinal de Intenção',
                        (data.contact_name || '') + ': ' + (data.context || ''),
                        data.conversation_id ? '/chats?conv=' + data.conversation_id : null,
                        'ai_intent',
                        'notification-chime'
                    );
                }
                loadIntentSignals();
            });

            // ── Laravel Notifications via broadcast (canal do usuário) ──
            const userChannel = window.Echo.private('App.Models.User.{{ auth()->id() }}');
            userChannel.notification(function (notif) {
                if (window.toastr) {
                    toastr.info(
                        '<b>' + (notif.title || 'Notificação') + '</b><br><small>' + (notif.body || '') + '</small>',
                        'Notificação',
                        { timeOut: 8000, closeButton: true, progressBar: true, escapeHtml: false }
                    );
                }
                if (window.NotifManager) {
                    window.NotifManager.notify(
                        notif.title || 'Notificação',
                        notif.body || '',
                        notif.url || null,
                        notif.notification_type || 'master_notification',
                        null
                    );
                }
            });

            channel.listen('.master.notification', function (data) {
                if (window.toastr) {
                    const typeMap = {
                        info:    { fn: 'info',    title: 'Notificação' },
                        warning: { fn: 'warning', title: 'Aviso' },
                        alert:   { fn: 'error',   title: 'Alerta Importante' },
                    };
                    const t = typeMap[data.type] || typeMap.info;
                    toastr[t.fn](
                        `<b>${data.title}</b><br><small>${data.body}</small>`,
                        t.title,
                        { timeOut: 12000, closeButton: true, progressBar: true, escapeHtml: false }
                    );
                }
                if (window.NotifManager) {
                    window.NotifManager.notify(
                        data.title || 'Notificação',
                        data.body || '',
                        null,
                        'master_notification',
                        'alert'
                    );
                }
                loadIntentSignals();
            });
        }

        // Ao abrir o sino, marcar master notifications como lidas
        document.getElementById('notif-bell-btn')?.addEventListener('click', function () {
            if (_masterNotifs.length) {
                const maxId = Math.max(..._masterNotifs.map(n => n.id));
                localStorage.setItem('mn_last_read', maxId);
                // Recalcular badge sem unread do master
                setTimeout(loadIntentSignals, 50);
            }
        });
    });
})();
</script>

{{-- PWA Install Banner (Android Chrome) --}}
<div id="pwaInstallBanner" style="display:none; position:fixed; bottom:0; left:0; right:0; z-index:9999; padding:12px 16px; background:#fff; border-top:2px solid #0085f3; box-shadow:0 -4px 20px rgba(0,0,0,.12); animation:pwaSlideUp .3s ease-out;">
    <div style="display:flex; align-items:center; gap:12px; max-width:600px; margin:0 auto;">
        <img src="/images/favicon-192.png" alt="Syncro" style="width:44px; height:44px; border-radius:10px; flex-shrink:0;">
        <div style="flex:1; min-width:0;">
            <div style="font-weight:700; font-size:14px; color:#1a1d23;">Instalar Syncro CRM</div>
            <div style="font-size:12px; color:#6b7280; margin-top:2px;">Acesse direto da tela inicial</div>
        </div>
        <button id="pwaInstallBtn" style="background:#0085f3; color:#fff; border:none; border-radius:9px; padding:9px 18px; font-size:13px; font-weight:600; cursor:pointer; white-space:nowrap;">Instalar</button>
        <button id="pwaDismissBtn" style="background:none; border:none; color:#9ca3af; font-size:18px; cursor:pointer; padding:4px;" aria-label="Fechar">&times;</button>
    </div>
</div>
<style>@keyframes pwaSlideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }</style>
<script>
(function() {
    var deferredPrompt = null;
    var banner = document.getElementById('pwaInstallBanner');
    if (window.matchMedia('(display-mode: standalone)').matches) return;
    if (localStorage.getItem('pwa_dismissed') === 'true') return;

    window.addEventListener('beforeinstallprompt', function(e) {
        e.preventDefault();
        deferredPrompt = e;
        banner.style.display = 'block';
    });

    document.getElementById('pwaInstallBtn').addEventListener('click', function() {
        if (!deferredPrompt) return;
        deferredPrompt.prompt();
        deferredPrompt.userChoice.then(function() {
            banner.style.display = 'none';
            deferredPrompt = null;
        });
    });

    document.getElementById('pwaDismissBtn').addEventListener('click', function() {
        banner.style.display = 'none';
        localStorage.setItem('pwa_dismissed', 'true');
    });

    window.addEventListener('appinstalled', function() {
        banner.style.display = 'none';
        deferredPrompt = null;
    });
})();
</script>

@php
    $trialExpired = false;
    if (auth()->check() && !auth()->user()->isSuperAdmin()) {
        $__tenant = auth()->user()->tenant;
        if ($__tenant
            && $__tenant->status === 'trial'
            && $__tenant->trial_ends_at !== null
            && $__tenant->trial_ends_at->isPast()
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
            Trial Expirado
        </div>
        <h2 style="font-size:20px;font-weight:700;color:#1a1d23;margin:0 0 12px;">Seu período gratuito encerrou</h2>
        <p style="font-size:14px;color:#677489;line-height:1.6;margin:0 0 28px;">
            O trial gratuito da conta <strong>{{ auth()->user()->tenant->name }}</strong> expirou.<br>
            Entre em contato com o suporte para ativar um plano e continuar usando a plataforma.
        </p>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" style="display:inline-flex;align-items:center;gap:8px;padding:11px 28px;background:transparent;color:#677489;border:1.5px solid #e5e7eb;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;transition:background .15s;">
                <i class="bi bi-box-arrow-right"></i> Sair da conta
            </button>
        </form>
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
<div id="pwaInstallBanner" style="display:none;position:fixed;bottom:0;left:0;right:0;z-index:9999;padding:14px 16px;background:#fff;border-top:2px solid #0085f3;box-shadow:0 -4px 24px rgba(0,0,0,.12);animation:pwaSlideUp .3s ease-out;">
    <div style="display:flex;align-items:center;gap:12px;max-width:600px;margin:0 auto;">
        <img src="{{ asset('images/favicon-192.png') }}" alt="Syncro" style="width:46px;height:46px;border-radius:11px;flex-shrink:0;">
        <div style="flex:1;min-width:0;">
            <div style="font-weight:700;font-size:14px;color:#1a1d23;">Instalar Syncro CRM</div>
            <div style="font-size:12px;color:#6b7280;margin-top:2px;">Acesse direto da tela inicial, rápido e sem abrir o navegador</div>
        </div>
        <button id="pwaInstallBtn" style="background:#0085f3;color:#fff;border:none;border-radius:9px;padding:10px 20px;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap;">Instalar</button>
        <button id="pwaDismissBtn" style="background:none;border:none;color:#9ca3af;font-size:20px;cursor:pointer;padding:4px;line-height:1;" aria-label="Fechar">&times;</button>
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

</body>
</html>

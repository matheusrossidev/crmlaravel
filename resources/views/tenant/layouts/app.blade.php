<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Plataforma 360' }} — {{ auth()->user()->tenant?->name ?? 'Plataforma 360' }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

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
    </script>

    {{-- Vite Assets --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')

    <style>
        * { box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
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
            overflow-y: auto;
            overflow-x: hidden;
            transition: width .22s ease;
        }

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
            color: #9ca3af;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all .15s;
            font-size: 14px;
            flex-shrink: 0;
            margin-left: auto;
        }
        .sidebar-collapse-btn:hover { background: #f4f6fb; color: #3B82F6; border-color: #dbeafe; }

        .sidebar-logo .logo-icon {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #3B82F6, #6366F1);
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
            color: #9ca3af;
            font-weight: 400;
        }

        /* Workspace selector */
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
            background: linear-gradient(135deg, #3B82F6, #6366F1);
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
            color: #9ca3af;
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
            color: #6b7280;
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
            color: #3B82F6;
        }

        .nav-item.active {
            background: #eff6ff;
            color: #3B82F6;
            font-weight: 600;
        }

        .nav-item.active .nav-icon { color: #3B82F6; }

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
            background: linear-gradient(135deg, #10B981, #3B82F6);
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
            color: #9ca3af;
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
            font-size: 16px;
            font-weight: 600;
            color: #1a1d23;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .topbar-title .page-icon {
            color: #3B82F6;
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
            color: #6b7280;
            font-size: 16px;
            cursor: pointer;
            transition: all .15s;
            text-decoration: none;
            position: relative;
        }

        .topbar-btn:hover {
            background: #f4f6fb;
            color: #3B82F6;
            border-color: #dbeafe;
        }

        .badge-dot {
            position: absolute;
            top: 6px; right: 6px;
            width: 7px; height: 7px;
            border-radius: 50%;
            background: #EF4444;
            border: 1.5px solid #fff;
        }

        .btn-primary-sm {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            background: #3B82F6;
            color: #fff;
            border: none;
            border-radius: 9px;
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: background .15s;
            white-space: nowrap;
        }

        .btn-primary-sm:hover { background: #2563EB; color: #fff; }

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

        /* ===== CARDS ===== */
        .stat-card {
            background: #fff;
            border-radius: 14px;
            padding: 22px 24px;
            border: 1px solid #e8eaf0;
        }

        .stat-card .stat-label {
            font-size: 13px;
            color: #6b7280;
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
            color: #9ca3af;
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
            color: #6b7280;
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
            color: #3B82F6;
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
            color: #6b7280;
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
            color: #3B82F6;
        }

        .nav-subitem.active {
            background: #eff6ff;
            color: #3B82F6;
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
            .sidebar--collapsed { transform: translateX(-100%); }
            .sidebar--collapsed.open { transform: translateX(0); }
            .topbar { left: 0 !important; }
            .main-content { margin-left: 0 !important; }
            .sidebar-collapse-btn { display: none; }
        }
    </style>
</head>
<body>

{{-- ===== SIDEBAR ===== --}}
<aside class="sidebar" id="sidebar">

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
    <div class="workspace-selector" title="{{ auth()->user()->tenant?->name ?? 'Minha Empresa' }}">
        <div class="workspace-avatar">
            {{ strtoupper(substr(auth()->user()->tenant?->name ?? 'P', 0, 1)) }}
        </div>
        <span class="workspace-name nav-label">{{ auth()->user()->tenant?->name ?? 'Minha Empresa' }}</span>
        <i class="bi bi-chevron-expand workspace-chevron nav-label" style="color:#9ca3af;font-size:12px;flex-shrink:0;"></i>
    </div>

    {{-- Nav: Geral --}}
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
    </nav>

    {{-- Nav: Gerenciamento --}}
    <nav class="nav-group">
        <div class="nav-group-label">Gerenciamento</div>

        <a href="{{ route('reports.index') }}"
           class="nav-item {{ request()->routeIs('reports*') ? 'active' : '' }}"
           title="Relatórios">
            <i class="bi bi-bar-chart-line nav-icon"></i>
            <span class="nav-label">Relatórios</span>
        </a>

        <a href="{{ route('ai.agents.index') }}"
           class="nav-item {{ request()->routeIs('ai.agents.*') ? 'active' : '' }}"
           title="Agentes de IA">
            <i class="bi bi-robot nav-icon"></i>
            <span class="nav-label">Agentes de IA</span>
        </a>

        @php
            $settingsOpen = request()->routeIs('settings.*');
        @endphp
        <div class="nav-submenu-wrap {{ $settingsOpen ? 'open' : '' }}" id="settingsSubmenuWrap">
            <button type="button"
                    class="nav-item nav-submenu-toggle w-100"
                    onclick="toggleSubmenu('settingsSubmenu')"
                    title="Configurações"
                    style="background:none;border:none;cursor:pointer;text-align:left;{{ $settingsOpen ? 'color:#3B82F6;background:#eff6ff;font-weight:600;' : '' }}">
                <i class="bi bi-gear nav-icon"></i>
                <span class="nav-label">Configurações</span>
                <i class="bi bi-chevron-down nav-chevron nav-label" id="settingsChevron"
                   style="margin-left:auto;font-size:11px;transition:transform .2s;{{ $settingsOpen ? 'transform:rotate(180deg);' : '' }}"></i>
            </button>
            <div class="nav-submenu" id="settingsSubmenu" style="{{ $settingsOpen ? '' : 'display:none;' }}">
                <a href="{{ route('settings.profile') }}"
                   class="nav-subitem {{ request()->routeIs('settings.profile*') ? 'active' : '' }}">
                    <i class="bi bi-person nav-icon" style="font-size:14px;"></i>
                    <span class="nav-label">Perfil</span>
                </a>
                <a href="{{ route('settings.pipelines') }}"
                   class="nav-subitem {{ request()->routeIs('settings.pipelines*') || request()->routeIs('settings.lost-reasons*') ? 'active' : '' }}">
                    <i class="bi bi-funnel nav-icon" style="font-size:14px;"></i>
                    <span class="nav-label">Pipelines</span>
                </a>
                @if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())
                <a href="{{ route('settings.users') }}"
                   class="nav-subitem {{ request()->routeIs('settings.users*') ? 'active' : '' }}">
                    <i class="bi bi-people nav-icon" style="font-size:14px;"></i>
                    <span class="nav-label">Usuários</span>
                </a>
                <a href="{{ route('settings.custom-fields') }}"
                   class="nav-subitem {{ request()->routeIs('settings.custom-fields*') ? 'active' : '' }}">
                    <i class="bi bi-sliders nav-icon" style="font-size:14px;"></i>
                    <span class="nav-label">Campos extras</span>
                </a>
                @endif
                <a href="{{ route('settings.integrations.index') }}"
                   class="nav-subitem {{ request()->routeIs('settings.integrations*') ? 'active' : '' }}">
                    <i class="bi bi-plugin nav-icon" style="font-size:14px;"></i>
                    <span class="nav-label">Integrações</span>
                </a>
                <a href="{{ route('settings.tags') }}"
                   class="nav-subitem {{ request()->routeIs('settings.tags*') ? 'active' : '' }}">
                    <i class="bi bi-tag nav-icon" style="font-size:14px;"></i>
                    <span class="nav-label">Tags</span>
                </a>
                <a href="{{ route('settings.api-keys') }}"
                   class="nav-subitem {{ request()->routeIs('settings.api-keys*') ? 'active' : '' }}">
                    <i class="bi bi-key nav-icon" style="font-size:14px;"></i>
                    <span class="nav-label">API / Webhooks</span>
                </a>
            </div>
        </div>
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
        <a href="{{ route('master.ai.config') }}"
           class="nav-item {{ request()->routeIs('master.ai*') ? 'active' : '' }}"
           title="Config. Inteligencia Artificial">
            <i class="bi bi-cpu nav-icon"></i>
            <span class="nav-label">Config. IA</span>
        </a>
    </nav>
    @endif

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
                   style="color:#9ca3af;margin-left:auto;flex-shrink:0;"></i>
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

{{-- ===== TOPBAR ===== --}}
<header class="topbar" id="topbar">
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
    @else
        <div class="topbar-actions">
            <button class="topbar-btn" title="Notificações">
                <i class="bi bi-bell"></i>
                <span class="badge-dot"></span>
            </button>
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
                        <div style="font-size:11px;color:#9ca3af;">{{ auth()->user()->email }}</div>
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
    @endif
</header>

{{-- ===== CONTEÚDO PRINCIPAL ===== --}}
<main class="main-content" id="mainContent">
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
                <p id="confirmModalMessage" style="font-size:14px;color:#6b7280;margin:0 0 10px;line-height:1.5;"></p>
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

    // Restaura estado salvo (sem animação)
    const saved = localStorage.getItem(STORAGE_KEY) === '1';
    applyState(saved, false);

    // Botão toggle
    document.getElementById('sidebarCollapseBtn')?.addEventListener('click', function () {
        const willCollapse = !sidebar.classList.contains('sidebar--collapsed');
        applyState(willCollapse, true);
        localStorage.setItem(STORAGE_KEY, willCollapse ? '1' : '0');
    });
}());

// ── Sidebar mobile toggle ─────────────────────────────────────────────────
document.getElementById('sidebarToggle')?.addEventListener('click', () => {
    document.getElementById('sidebar').classList.toggle('open');
});

// ── Flash messages ────────────────────────────────────────────────────────
@if(session('success'))
    toastr.success("{{ session('success') }}");
@endif
@if(session('error'))
    toastr.error("{{ session('error') }}");
@endif
@if(session('warning'))
    toastr.warning("{{ session('warning') }}");
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
</script>

@stack('scripts')
<script>
function toggleSubmenu(id) {
    // No modo colapsado, o flyout é controlado por CSS (:hover); não faz nada via JS
    if (document.getElementById('sidebar')?.classList.contains('sidebar--collapsed')) return;

    const menu    = document.getElementById(id);
    const chevron = document.getElementById(id.replace('Submenu', 'Chevron'));
    if (!menu) return;
    const isOpen = menu.style.display !== 'none';
    menu.style.display = isOpen ? 'none' : '';
    if (chevron) chevron.style.transform = isOpen ? '' : 'rotate(180deg)';
}
</script>
</body>
</html>

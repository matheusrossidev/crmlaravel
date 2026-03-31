<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Syncro CRM' }} — Admin</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">

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

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

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
            background: #f0f2f8;
            margin: 0;
            color: #1a1d23;
        }

        /* ── NAVBAR ─────────────────────────────────────────────────────── */
        .m-navbar {
            position: fixed;
            top: 0; left: 0; right: 0;
            height: 56px;
            background: #fff;
            border-bottom: 1px solid #e8eaf0;
            z-index: 100;
            display: flex;
            align-items: center;
        }

        .m-navbar-inner {
            width: 100%;
            padding: 0 20px;
            display: flex;
            align-items: center;
            gap: 8px;
            height: 100%;
        }

        .m-navbar-logo {
            display: flex;
            align-items: center;
            flex-shrink: 0;
            margin-right: 6px;
            text-decoration: none;
        }
        .m-navbar-logo img { height: 28px; }

        .m-master-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: #1e293b;
            color: #60a5fa;
            border-radius: 6px;
            font-size: 10px;
            font-weight: 700;
            padding: 3px 8px;
            letter-spacing: .5px;
            flex-shrink: 0;
            margin-right: 8px;
        }

        /* Hamburger (mobile) */
        .m-navbar-hamburger {
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

        /* Menu */
        .m-navbar-menu {
            display: flex;
            align-items: center;
            gap: 2px;
            flex: 1;
        }

        .m-nm-item {
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
        .m-nm-item:hover { background: #f4f6fb; color: #007DFF; text-decoration: none; }
        .m-nm-item.active { background: #eff6ff; color: #007DFF; font-weight: 600; }
        .m-nm-item i { font-size: 15px; }
        .m-nm-chev { font-size: 10px; margin-left: 2px; transition: transform .2s; }

        /* Dropdowns */
        .m-nm-dropdown { position: relative; }
        .m-nm-dropdown-menu {
            display: none;
            position: absolute;
            top: calc(100% + 6px);
            left: 0;
            background: #fff;
            border: 1px solid #e8eaf0;
            border-radius: 10px;
            box-shadow: 0 8px 32px rgba(0,0,0,.1);
            padding: 6px;
            min-width: 210px;
            z-index: 200;
        }
        .m-nm-dropdown.open .m-nm-dropdown-menu { display: block; }
        .m-nm-dropdown.open .m-nm-chev { transform: rotate(180deg); }

        .m-nm-dd-item {
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
        .m-nm-dd-item:hover { background: #eff6ff; color: #007DFF; text-decoration: none; }
        .m-nm-dd-item.active { background: #eff6ff; color: #007DFF; font-weight: 600; }
        .m-nm-dd-item i { font-size: 14px; color: #9ca3af; width: 18px; text-align: center; }
        .m-nm-dd-item:hover i, .m-nm-dd-item.active i { color: #007DFF; }

        /* Right side */
        .m-navbar-right {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-left: auto;
            flex-shrink: 0;
        }

        .m-navbar-user {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
        }

        .m-navbar-user-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: #0f172a;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 13px;
            font-weight: 700;
            flex-shrink: 0;
            overflow: hidden;
        }

        .m-navbar-user-name {
            max-width: 120px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .m-navbar-logout {
            width: 34px; height: 34px;
            border: 1px solid #e8eaf0;
            border-radius: 9px;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #677489;
            font-size: 15px;
            cursor: pointer;
            transition: all .15s;
        }
        .m-navbar-logout:hover { background: #FEF2F2; color: #EF4444; border-color: #FECACA; }

        /* Go-to-tenant button */
        .m-go-tenant {
            width: 34px; height: 34px;
            border: 1px solid #e8eaf0;
            border-radius: 9px;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #677489;
            font-size: 15px;
            cursor: pointer;
            transition: all .15s;
            text-decoration: none;
        }
        .m-go-tenant:hover { background: #eff6ff; color: #007DFF; border-color: #bfdbfe; }

        /* ── MAIN ──────────────────────────────────────────────────────────── */
        .m-main {
            padding-top: 56px;
            min-height: 100vh;
        }

        .m-page {
            padding: 28px;
        }

        /* ── CARDS ─────────────────────────────────────────────────────────── */
        .m-card {
            background: #fff;
            border-radius: 14px;
            border: 1px solid #e8eaf0;
            overflow: hidden;
        }

        .m-card-header {
            padding: 16px 22px;
            border-bottom: 1px solid #f0f2f7;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .m-card-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 14px;
            font-weight: 700;
            color: #1a1d23;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .m-card-title i { color: #007DFF; }

        .m-card-body { padding: 22px; }

        /* ── STAT CARDS ────────────────────────────────────────────────────── */
        .m-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .m-stat {
            background: #fff;
            border-radius: 14px;
            border: 1px solid #e8eaf0;
            padding: 18px 20px;
        }

        .m-stat-label { font-size: 12px; color: #677489; font-weight: 500; margin-bottom: 6px; }
        .m-stat-value { font-size: 26px; font-weight: 700; color: #1a1d23; }

        /* ── TABLE ─────────────────────────────────────────────────────────── */
        .m-table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .m-table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
        .m-table th { padding: 10px 14px; text-align: left; font-size: 11.5px; font-weight: 600; color: #677489; text-transform: uppercase; letter-spacing: .5px; border-bottom: 1px solid #f0f2f7; white-space: nowrap; }
        .m-table td { padding: 12px 14px; color: #374151; border-bottom: 1px solid #f7f8fa; vertical-align: middle; }
        .m-table tr:last-child td { border-bottom: none; }
        .m-table tr:hover td { background: #f9fafb; }

        /* ── BADGES ────────────────────────────────────────────────────────── */
        .m-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11.5px;
            font-weight: 600;
        }
        .m-badge-active   { background: #D1FAE5; color: #065F46; }
        .m-badge-trial    { background: #FEF3C7; color: #92400E; }
        .m-badge-partner  { background: #EDE9FE; color: #5B21B6; }
        .m-badge-inactive { background: #F3F4F6; color: #677489; }
        .m-badge-suspended{ background: #FEE2E2; color: #991B1B; }

        /* ── BUTTONS ──────────────────────────────────────────────────────── */
        .m-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 9px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: background .15s, color .15s;
            text-decoration: none;
        }
        .m-btn-primary { background: #0085f3; color: #fff; border-radius: 100px; }
        .m-btn-primary:hover { background: #0070d1; color: #fff; }
        .m-btn-ghost { background: transparent; color: #677489; border: 1px solid #CDDEF6; border-radius: 100px; }
        .m-btn-ghost:hover { background: #f3f4f6; color: #374151; }
        .m-btn-danger { background: #FEE2E2; color: #DC2626; }
        .m-btn-danger:hover { background: #FECACA; }
        .m-btn-sm { padding: 5px 12px; font-size: 12px; }

        /* ── File inputs ── */
        input[type="file"] {
            width: 100%;
            padding: 10px 14px;
            border: 1.5px dashed #d1d5db;
            border-radius: 10px;
            font-size: 13px;
            font-family: inherit;
            color: #374151;
            background: #fafbfc;
            cursor: pointer;
            transition: border-color .15s, background .15s;
        }
        input[type="file"]:hover { border-color: #0085f3; background: #eff6ff; }
        input[type="file"]::file-selector-button {
            padding: 6px 14px;
            margin-right: 12px;
            background: #0085f3;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
        }
        input[type="file"]::file-selector-button:hover { background: #0070d1; }

        /* ── RESPONSIVE ────────────────────────────────────────────────────── */
        @media (max-width: 768px) {
            .m-navbar-hamburger { display: flex; }

            .m-navbar-menu {
                display: none;
                position: fixed;
                top: 56px; left: 0; right: 0; bottom: 0;
                background: #fff;
                flex-direction: column;
                padding: 12px 0 40px;
                overflow-y: auto;
                z-index: 150;
                border-top: 1px solid #e8eaf0;
                align-items: stretch;
            }
            .m-navbar-menu.open { display: flex; }

            .m-navbar-menu .m-nm-item {
                width: 100%;
                justify-content: flex-start;
                padding: 14px 24px;
                border-radius: 0;
                font-size: 15px;
                font-weight: 500;
                color: #1a1d23;
                border-bottom: 1px solid #f0f2f7;
            }
            .m-navbar-menu .m-nm-item:hover { background: #f8fafc; }
            .m-navbar-menu .m-nm-item.active { background: #fff; color: #0085f3; font-weight: 600; }
            .m-navbar-menu .m-nm-chev {
                margin-left: auto;
                font-size: 14px;
                color: #9ca3af;
            }

            .m-navbar-menu .m-nm-dropdown { width: 100%; }
            .m-navbar-menu .m-nm-dropdown > .m-nm-item { width: 100%; }
            .m-nm-dropdown-menu {
                position: static;
                box-shadow: none;
                border: none;
                border-radius: 0;
                padding: 0;
                min-width: unset;
                background: #f8fafc;
                border-bottom: 1px solid #f0f2f7;
            }

            .m-nm-dd-item {
                padding: 14px 24px 14px 44px;
                font-size: 14px;
                border-radius: 0;
            }
            .m-nm-dd-item i { display: none; }

            .m-navbar-user-name { display: none; }
            .m-navbar-right .topbar-actions-area { display: none; }

            .m-page { padding: 16px 14px; }

            .m-stats { grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 10px; }
            .m-stat { padding: 14px 16px; }
            .m-stat-value { font-size: 22px; }

            .m-card-header { padding: 14px 16px; flex-wrap: wrap; }
            .m-card-body { padding: 16px; }
        }

        @media (max-width: 480px) {
            .m-page { padding: 12px 10px; }
            .m-stats { grid-template-columns: repeat(2, 1fr); }
            .m-stat-value { font-size: 20px; }
        }
    </style>
</head>
<body>

@php
    $isGestao = request()->routeIs('master.tenants*')
             || request()->routeIs('master.payments*')
             || request()->routeIs('master.plans*')
             || request()->routeIs('master.agency-codes*')
             || request()->routeIs('master.token-increments*')
             || request()->routeIs('master.upsell*');

    $isMonitor = request()->routeIs('master.usage*')
              || request()->routeIs('master.logs*')
              || request()->routeIs('master.system*')
              || request()->routeIs('master.toolbox*');
@endphp

{{-- ===== NAVBAR ===== --}}
<nav class="m-navbar">
    <div class="m-navbar-inner">
        <a href="{{ route('master.dashboard') }}" class="m-navbar-logo">
            <img src="{{ asset('images/logo.png') }}" alt="Syncro">
        </a>
        <span class="m-master-badge"><i class="bi bi-shield-check" style="font-size:10px;"></i> MASTER</span>

        <button class="m-navbar-hamburger" onclick="document.querySelector('.m-navbar-menu').classList.toggle('open'); this.querySelector('i').classList.toggle('bi-list'); this.querySelector('i').classList.toggle('bi-x-lg');">
            <i class="bi bi-list"></i>
        </button>

        <div class="m-navbar-menu">
            {{-- Dashboard --}}
            <a href="{{ route('master.dashboard') }}"
               class="m-nm-item {{ request()->routeIs('master.dashboard') ? 'active' : '' }}">
                <i class="bi bi-grid-1x2"></i> Dashboard
            </a>

            {{-- Gestão --}}
            <div class="m-nm-dropdown">
                <button class="m-nm-item {{ $isGestao ? 'active' : '' }}" onclick="toggleMasterDropdown(this)">
                    <i class="bi bi-briefcase"></i> Gestão
                    <i class="bi bi-chevron-down m-nm-chev"></i>
                </button>
                <div class="m-nm-dropdown-menu">
                    <a href="{{ route('master.tenants') }}"
                       class="m-nm-dd-item {{ request()->routeIs('master.tenants*') ? 'active' : '' }}">
                        <i class="bi bi-building"></i> Empresas
                    </a>
                    <a href="{{ route('master.payments') }}"
                       class="m-nm-dd-item {{ request()->routeIs('master.payments*') ? 'active' : '' }}">
                        <i class="bi bi-cash-stack"></i> Recebimentos
                    </a>
                    <a href="{{ route('master.plans') }}"
                       class="m-nm-dd-item {{ request()->routeIs('master.plans*') ? 'active' : '' }}">
                        <i class="bi bi-layers"></i> Planos
                    </a>
                    <a href="{{ route('master.agency-codes.index') }}"
                       class="m-nm-dd-item {{ request()->routeIs('master.agency-codes*') ? 'active' : '' }}">
                        <i class="bi bi-building-check"></i> Agências Parceiras
                    </a>
                    <a href="{{ route('master.partner-ranks.index') }}"
                       class="m-nm-dd-item {{ request()->routeIs('master.partner-ranks*') ? 'active' : '' }}">
                        <i class="bi bi-award"></i> Ranks de Parceiros
                    </a>
                    <a href="{{ route('master.partner-commissions.index') }}"
                       class="m-nm-dd-item {{ request()->routeIs('master.partner-commissions*', 'master.partner-withdrawals*') ? 'active' : '' }}">
                        <i class="bi bi-cash-coin"></i> Comissões / Saques
                    </a>
                    <a href="{{ route('master.partner-resources.index') }}"
                       class="m-nm-dd-item {{ request()->routeIs('master.partner-resources*') ? 'active' : '' }}">
                        <i class="bi bi-folder2-open"></i> Recursos Parceiros
                    </a>
                    <a href="{{ route('master.partner-courses.index') }}"
                       class="m-nm-dd-item {{ request()->routeIs('master.partner-courses*') ? 'active' : '' }}">
                        <i class="bi bi-mortarboard"></i> Cursos Parceiros
                    </a>
                    <a href="{{ route('master.token-increments') }}"
                       class="m-nm-dd-item {{ request()->routeIs('master.token-increments*') ? 'active' : '' }}">
                        <i class="bi bi-coin"></i> Pacotes de Tokens
                    </a>
                    <a href="{{ route('master.upsell') }}"
                       class="m-nm-dd-item {{ request()->routeIs('master.upsell*') ? 'active' : '' }}">
                        <i class="bi bi-rocket-takeoff"></i> Upsell
                    </a>
                </div>
            </div>

            {{-- Monitoramento --}}
            <div class="m-nm-dropdown">
                <button class="m-nm-item {{ $isMonitor ? 'active' : '' }}" onclick="toggleMasterDropdown(this)">
                    <i class="bi bi-activity"></i> Monitoramento
                    <i class="bi bi-chevron-down m-nm-chev"></i>
                </button>
                <div class="m-nm-dropdown-menu">
                    <a href="{{ route('master.usage') }}"
                       class="m-nm-dd-item {{ request()->routeIs('master.usage*') ? 'active' : '' }}">
                        <i class="bi bi-graph-up"></i> Uso / Tokens
                    </a>
                    <a href="{{ route('master.logs') }}"
                       class="m-nm-dd-item {{ request()->routeIs('master.logs*') ? 'active' : '' }}">
                        <i class="bi bi-terminal"></i> Logs
                    </a>
                    <a href="{{ route('master.system') }}"
                       class="m-nm-dd-item {{ request()->routeIs('master.system*') ? 'active' : '' }}">
                        <i class="bi bi-cpu"></i> Sistema
                    </a>
                    <a href="{{ route('master.toolbox') }}"
                       class="m-nm-dd-item {{ request()->routeIs('master.toolbox*') ? 'active' : '' }}">
                        <i class="bi bi-tools"></i> Ferramentas
                    </a>
                    <a href="/pulse" target="_blank" class="m-nm-dd-item">
                        <i class="bi bi-speedometer2"></i> Pulse
                    </a>
                </div>
            </div>

            {{-- Notificações --}}
            <a href="{{ route('master.notifications') }}"
               class="m-nm-item {{ request()->routeIs('master.notifications*') ? 'active' : '' }}">
                <i class="bi bi-megaphone"></i> Notificações
            </a>

            {{-- Customer Success --}}
            <a href="{{ route('cs.index') }}"
               class="m-nm-item" target="_blank">
                <i class="bi bi-headset"></i> CS
            </a>
        </div>

        {{-- Right side --}}
        <div class="m-navbar-right">
            <div class="topbar-actions-area" style="display:flex;align-items:center;gap:6px;">
                @hasSection('topbar_actions')
                    @yield('topbar_actions')
                @endif
            </div>

            <a href="{{ route('master.2fa.setup') }}" class="m-go-tenant" title="Autenticação em Dois Fatores (2FA)" style="{{ auth()->user()->totp_enabled ? 'background:#d1fae5;border-color:#86efac;color:#065f46;' : 'background:#fef2f2;border-color:#fecaca;color:#ef4444;' }}">
                <i class="bi bi-shield-lock"></i>
            </a>

            <a href="{{ route('dashboard') }}" class="m-go-tenant" title="Ir para área do tenant">
                <i class="bi bi-box-arrow-up-right"></i>
            </a>

            <div class="m-navbar-user">
                <div class="m-navbar-user-avatar">
                    @if(auth()->user()->avatar)
                        <img src="{{ auth()->user()->avatar }}" style="width:100%;height:100%;object-fit:cover;">
                    @else
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    @endif
                </div>
                <span class="m-navbar-user-name">{{ auth()->user()->name }}</span>
            </div>

            <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                @csrf
                <button type="submit" class="m-navbar-logout" title="Sair">
                    <i class="bi bi-box-arrow-right"></i>
                </button>
            </form>
        </div>
    </div>
</nav>

{{-- ===== CONTEÚDO ===== --}}
<main class="m-main">
    <div class="m-page">
        @yield('content')
    </div>
</main>

{{-- jQuery + Bootstrap + Toastr --}}
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>
toastr.options = { positionClass: 'toast-top-right', timeOut: 4000, progressBar: true, closeButton: true };
</script>

{{-- Dropdown + hamburger logic --}}
<script>
function toggleMasterDropdown(btn) {
    const dd = btn.closest('.m-nm-dropdown');
    const wasOpen = dd.classList.contains('open');

    // Close all dropdowns
    document.querySelectorAll('.m-nm-dropdown.open').forEach(d => d.classList.remove('open'));

    if (!wasOpen) dd.classList.add('open');
}

// Close dropdowns on outside click
document.addEventListener('click', function(e) {
    if (!e.target.closest('.m-nm-dropdown')) {
        document.querySelectorAll('.m-nm-dropdown.open').forEach(d => d.classList.remove('open'));
    }
    if (!e.target.closest('.m-navbar-hamburger') && !e.target.closest('.m-navbar-menu')) {
        const menu = document.querySelector('.m-navbar-menu');
        if (menu) menu.classList.remove('open');
        const icon = document.querySelector('.m-navbar-hamburger i');
        if (icon) { icon.classList.remove('bi-x-lg'); icon.classList.add('bi-list'); }
    }
});
</script>

{{-- Confirm Modal --}}
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
                style="padding:9px 20px;border-radius:8px;border:1px solid #CDDEF6;background:#fff;color:#374151;font-size:13px;font-weight:500;cursor:pointer;">
                Cancelar
            </button>
            <button id="confirmModalConfirm" type="button"
                style="padding:9px 20px;border-radius:8px;border:none;background:#EF4444;color:#fff;font-size:13px;font-weight:600;cursor:pointer;min-width:100px;">
                Confirmar
            </button>
        </div>
    </div>
</div>

<script>
window.confirmAction = function ({ title = 'Confirmar ação', message = '', confirmText = 'Confirmar', onConfirm }) {
    const modal = document.getElementById('confirmModal');
    document.getElementById('confirmModalTitle').textContent   = title;
    document.getElementById('confirmModalMessage').innerHTML   = message;
    document.getElementById('confirmModalConfirm').textContent = confirmText;
    modal.style.display = 'flex';

    const close = () => { modal.style.display = 'none'; };
    document.getElementById('confirmModalCancel').onclick  = close;
    document.getElementById('confirmModalConfirm').onclick = () => { close(); onConfirm(); };
    modal.onclick = (e) => { if (e.target === modal) close(); };
};

window.escapeHtml = function (str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
};
</script>

@stack('scripts')

</body>
</html>

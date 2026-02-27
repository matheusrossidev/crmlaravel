<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Master' }} — Admin</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')

    <style>
        * { box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: #f0f2f8;
            margin: 0;
            color: #1a1d23;
        }

        /* ── SIDEBAR ─────────────────────────────────────────────────────── */
        .m-sidebar {
            position: fixed;
            top: 0; left: 0;
            width: 230px;
            height: 100vh;
            background: #0f172a;
            display: flex;
            flex-direction: column;
            z-index: 100;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .m-sidebar-logo {
            padding: 16px 20px;
            border-bottom: 1px solid rgba(255,255,255,.07);
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 64px;
        }

        .m-sidebar-logo img { max-height: 32px; object-fit: contain; filter: brightness(0) invert(1); }

        .m-sidebar-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: #1e3a5f;
            color: #60a5fa;
            border-radius: 6px;
            font-size: 10px;
            font-weight: 700;
            padding: 3px 8px;
            letter-spacing: .5px;
        }

        /* Nav */
        .m-nav { padding: 12px 0; flex: 1; }

        .m-nav-group { padding: 16px 20px 4px; }

        .m-nav-group-label {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1px;
            color: rgba(255,255,255,.3);
            text-transform: uppercase;
        }

        .m-nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 20px;
            font-size: 13.5px;
            font-weight: 500;
            color: rgba(255,255,255,.6);
            text-decoration: none;
            border-left: 3px solid transparent;
            transition: color .15s, background .15s, border-color .15s;
        }

        .m-nav-item:hover {
            background: rgba(255,255,255,.05);
            color: #fff;
        }

        .m-nav-item.active {
            background: rgba(59,130,246,.15);
            border-left-color: #3B82F6;
            color: #93c5fd;
        }

        .m-nav-item i { font-size: 16px; flex-shrink: 0; }

        /* Footer */
        .m-sidebar-footer {
            padding: 16px 20px;
            border-top: 1px solid rgba(255,255,255,.07);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .m-user-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3B82F6, #2563EB);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 13px;
            font-weight: 700;
            flex-shrink: 0;
            overflow: hidden;
        }

        .m-user-info { flex: 1; min-width: 0; }
        .m-user-name { font-size: 12.5px; font-weight: 600; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .m-user-role { font-size: 11px; color: rgba(255,255,255,.4); }

        /* ── TOPBAR ────────────────────────────────────────────────────────── */
        .m-topbar {
            position: fixed;
            top: 0;
            left: 230px;
            right: 0;
            height: 64px;
            background: #fff;
            border-bottom: 1px solid #e8eaf0;
            display: flex;
            align-items: center;
            padding: 0 28px;
            z-index: 90;
            gap: 12px;
        }

        .m-topbar-title {
            font-size: 16px;
            font-weight: 700;
            color: #1a1d23;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .m-topbar-title i { color: #3B82F6; font-size: 18px; }

        .m-topbar-spacer { flex: 1; }

        .m-topbar-user {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
        }

        .m-topbar-user-avatar {
            width: 34px;
            height: 34px;
            border-radius: 9px;
            background: linear-gradient(135deg, #3B82F6, #2563EB);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 13px;
            font-weight: 700;
            overflow: hidden;
        }

        /* ── MAIN ──────────────────────────────────────────────────────────── */
        .m-main {
            margin-left: 230px;
            padding-top: 64px;
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
            font-size: 14px;
            font-weight: 700;
            color: #1a1d23;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .m-card-title i { color: #3B82F6; }

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

        .m-stat-label { font-size: 12px; color: #6b7280; font-weight: 500; margin-bottom: 6px; }
        .m-stat-value { font-size: 26px; font-weight: 700; color: #1a1d23; }

        /* ── TABLE ─────────────────────────────────────────────────────────── */
        .m-table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
        .m-table th { padding: 10px 14px; text-align: left; font-size: 11.5px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: .5px; border-bottom: 1px solid #f0f2f7; }
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
        .m-badge-inactive { background: #F3F4F6; color: #6B7280; }
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
        .m-btn-primary { background: #3B82F6; color: #fff; }
        .m-btn-primary:hover { background: #2563EB; color: #fff; }
        .m-btn-ghost { background: transparent; color: #6b7280; border: 1.5px solid #e5e7eb; }
        .m-btn-ghost:hover { background: #f3f4f6; color: #374151; }
        .m-btn-danger { background: #FEE2E2; color: #DC2626; }
        .m-btn-danger:hover { background: #FECACA; }
        .m-btn-sm { padding: 5px 12px; font-size: 12px; }
    </style>
</head>
<body>

{{-- ===== SIDEBAR ===== --}}
<aside class="m-sidebar">
    <div class="m-sidebar-logo">
        <img src="{{ asset('images/logo.png') }}" alt="Logo">
    </div>

    <div class="m-nav">
        <div class="m-nav-group">
            <div class="m-nav-group-label">Visão Geral</div>
        </div>
        <a href="{{ route('master.dashboard') }}"
           class="m-nav-item {{ request()->routeIs('master.dashboard') ? 'active' : '' }}">
            <i class="bi bi-grid-1x2"></i> Dashboard
        </a>

        <div class="m-nav-group">
            <div class="m-nav-group-label">Gestão</div>
        </div>
        <a href="{{ route('master.tenants') }}"
           class="m-nav-item {{ request()->routeIs('master.tenants*') ? 'active' : '' }}">
            <i class="bi bi-building"></i> Empresas
        </a>
        <a href="{{ route('master.plans') }}"
           class="m-nav-item {{ request()->routeIs('master.plans*') ? 'active' : '' }}">
            <i class="bi bi-layers"></i> Planos
        </a>

        <div class="m-nav-group">
            <div class="m-nav-group-label">Monitoramento</div>
        </div>
        <a href="{{ route('master.usage') }}"
           class="m-nav-item {{ request()->routeIs('master.usage*') ? 'active' : '' }}">
            <i class="bi bi-graph-up"></i> Uso / Tokens
        </a>
        <a href="{{ route('master.logs') }}"
           class="m-nav-item {{ request()->routeIs('master.logs*') ? 'active' : '' }}">
            <i class="bi bi-terminal"></i> Logs
        </a>
        <a href="{{ route('master.system') }}"
           class="m-nav-item {{ request()->routeIs('master.system*') ? 'active' : '' }}">
            <i class="bi bi-cpu"></i> Sistema
        </a>
        <a href="{{ route('master.toolbox') }}"
           class="m-nav-item {{ request()->routeIs('master.toolbox*') ? 'active' : '' }}">
            <i class="bi bi-tools"></i> Ferramentas
        </a>

        <div class="m-nav-group">
            <div class="m-nav-group-label">Comunicação</div>
        </div>
        <a href="{{ route('master.notifications') }}"
           class="m-nav-item {{ request()->routeIs('master.notifications*') ? 'active' : '' }}">
            <i class="bi bi-megaphone"></i> Notificações
        </a>

    </div>

    <div class="m-sidebar-footer">
        <div class="m-user-avatar">
            @if(auth()->user()->avatar)
                <img src="{{ auth()->user()->avatar }}" style="width:100%;height:100%;object-fit:cover;">
            @else
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            @endif
        </div>
        <div class="m-user-info">
            <div class="m-user-name">{{ auth()->user()->name }}</div>
            <div class="m-user-role">Super Admin</div>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" title="Sair"
                    style="background:none;border:none;cursor:pointer;color:rgba(255,255,255,.4);font-size:16px;padding:4px;">
                <i class="bi bi-box-arrow-right"></i>
            </button>
        </form>
    </div>
</aside>

{{-- ===== TOPBAR ===== --}}
<header class="m-topbar">
    <div class="m-topbar-title">
        <i class="bi bi-{{ $pageIcon ?? 'shield-check' }}"></i>
        {{ $title ?? 'Master Admin' }}
    </div>
    <div class="m-topbar-spacer"></div>
    @hasSection('topbar_actions')
        @yield('topbar_actions')
    @endif
    <div class="m-topbar-user">
        <div class="m-topbar-user-avatar">
            @if(auth()->user()->avatar)
                <img src="{{ auth()->user()->avatar }}" style="width:100%;height:100%;object-fit:cover;">
            @else
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            @endif
        </div>
        {{ auth()->user()->name }}
    </div>
</header>

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

{{-- Confirm Modal --}}
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

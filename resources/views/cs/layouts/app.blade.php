<!DOCTYPE html>
<html lang="pt-BR">
<head>
    @include('partials._google-analytics')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Customer Success' }} — Syncro CRM</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')

    <style>
        * { box-sizing: border-box; scrollbar-width: thin; scrollbar-color: #d5d5d5 transparent; }
        ::-webkit-scrollbar { width: 4px; height: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #d5d5d5; border-radius: 99px; }

        body { font-family: 'DM Sans', sans-serif; background: #f4f6fb; margin: 0; color: #1a1d23; }

        .cs-navbar {
            position: fixed; top: 0; left: 0; right: 0; height: 56px;
            background: #fff; border-bottom: 1px solid #e8eaf0;
            z-index: 100; display: flex; align-items: center;
        }
        .cs-navbar-inner { width: 100%; padding: 0 20px; display: flex; align-items: center; gap: 12px; height: 100%; }
        .cs-navbar-logo { display: flex; align-items: center; text-decoration: none; margin-right: 8px; }
        .cs-navbar-logo img { height: 28px; }
        .cs-badge {
            display: inline-flex; align-items: center; gap: 4px;
            background: #eff6ff; color: #0085f3; border-radius: 6px;
            font-size: 10px; font-weight: 700; padding: 3px 8px; letter-spacing: .5px;
        }
        .cs-navbar-spacer { flex: 1; }
        .cs-navbar-user {
            display: flex; align-items: center; gap: 8px;
            font-size: 13px; font-weight: 600; color: #374151;
        }
        .cs-navbar-avatar {
            width: 34px; height: 34px; border-radius: 50%; background: #0085f3;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 13px; font-weight: 700; overflow: hidden;
        }
        .cs-logout {
            width: 34px; height: 34px; border: 1px solid #e8eaf0; border-radius: 9px;
            background: #fff; display: flex; align-items: center; justify-content: center;
            color: #677489; font-size: 15px; cursor: pointer; transition: all .15s;
        }
        .cs-logout:hover { background: #FEF2F2; color: #EF4444; border-color: #FECACA; }

        .cs-main { padding-top: 56px; min-height: 100vh; }
        .cs-page { padding: 28px; }

        /* Reuse m- classes */
        .m-card { background: #fff; border-radius: 14px; border: 1px solid #e8eaf0; overflow: hidden; }
        .m-card-header { padding: 16px 22px; border-bottom: 1px solid #f0f2f7; display: flex; align-items: center; justify-content: space-between; gap: 12px; }
        .m-card-title { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 14px; font-weight: 700; color: #1a1d23; display: flex; align-items: center; gap: 8px; }
        .m-card-title i { color: #0085f3; }
        .m-card-body { padding: 22px; }

        .m-table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
        .m-table th { padding: 10px 14px; text-align: left; font-size: 11.5px; font-weight: 600; color: #677489; text-transform: uppercase; letter-spacing: .5px; border-bottom: 1px solid #f0f2f7; white-space: nowrap; }
        .m-table td { padding: 12px 14px; color: #374151; border-bottom: 1px solid #f7f8fa; vertical-align: middle; }
        .m-table tr:last-child td { border-bottom: none; }
        .m-table tr:hover td { background: #f9fafb; }

        .m-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11.5px; font-weight: 600; }
        .m-badge-active { background: #D1FAE5; color: #065F46; }
        .m-badge-trial { background: #FEF3C7; color: #92400E; }
        .m-badge-suspended { background: #FEE2E2; color: #991B1B; }
        .m-badge-inactive { background: #F3F4F6; color: #677489; }

        .m-btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 100px; font-size: 13px; font-weight: 600; cursor: pointer; border: none; transition: background .15s; text-decoration: none; }
        .m-btn-primary { background: #0085f3; color: #fff; }
        .m-btn-primary:hover { background: #0070d1; color: #fff; }
        .m-btn-ghost { background: transparent; color: #677489; border: 1px solid #CDDEF6; }
        .m-btn-ghost:hover { background: #f3f4f6; color: #374151; }
        .m-btn-sm { padding: 5px 12px; font-size: 12px; }

        .m-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .m-stat { background: #fff; border-radius: 14px; border: 1px solid #e8eaf0; padding: 18px 20px; }
        .m-stat-label { font-size: 12px; color: #677489; font-weight: 500; margin-bottom: 6px; }
        .m-stat-value { font-size: 26px; font-weight: 700; color: #1a1d23; }

        .health-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
        .health-green { background: #10B981; }
        .health-yellow { background: #F59E0B; }
        .health-red { background: #EF4444; }

        @media (max-width: 768px) {
            .cs-page { padding: 16px 14px; }
            .cs-navbar-user span { display: none; }
            .m-stats { grid-template-columns: repeat(2, 1fr); gap: 10px; }
            .m-stat-value { font-size: 22px; }
            .m-table { font-size: 12px; }
            .m-table th, .m-table td { padding: 8px 10px; }
        }
        @media (max-width: 480px) {
            .cs-page { padding: 12px 10px; }
        }
    </style>
</head>
<body>

<nav class="cs-navbar">
    <div class="cs-navbar-inner">
        <a href="{{ route('cs.index') }}" class="cs-navbar-logo">
            <img src="{{ asset('images/logo.png') }}" alt="Syncro">
        </a>
        <span class="cs-badge"><i class="bi bi-headset" style="font-size:10px;"></i> CUSTOMER SUCCESS</span>
        <div class="cs-navbar-spacer"></div>
        <div class="cs-navbar-user">
            <div class="cs-navbar-avatar">
                @if(auth()->user()->avatar)
                    <img src="{{ auth()->user()->avatar }}" style="width:100%;height:100%;object-fit:cover;">
                @else
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                @endif
            </div>
            <span>{{ auth()->user()->name }}</span>
        </div>
        <form method="POST" action="{{ route('logout') }}" style="margin:0;">
            @csrf
            <button type="submit" class="cs-logout" title="Sair">
                <i class="bi bi-box-arrow-right"></i>
            </button>
        </form>
    </div>
</nav>

<main class="cs-main">
    <div class="cs-page">
        @yield('content')
    </div>
</main>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>
toastr.options = { positionClass: 'toast-top-right', timeOut: 4000, progressBar: true, closeButton: true };
</script>

@stack('scripts')
</body>
</html>

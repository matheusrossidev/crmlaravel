<!DOCTYPE html>
<html lang="pt-BR">
<head>
    @include("partials._google-analytics")
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trial Expirado — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 4px 32px rgba(0,0,0,.08);
            padding: 48px 40px;
            max-width: 480px;
            width: 100%;
            text-align: center;
        }
        .icon-wrap {
            width: 72px;
            height: 72px;
            border-radius: 20px;
            background: #FFF7ED;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
        }
        h1 { font-size: 22px; font-weight: 700; color: #1a1d23; margin-bottom: 12px; }
        p  { font-size: 14px; color: #6b7280; line-height: 1.6; margin-bottom: 28px; }
        .badge-trial {
            display: inline-block;
            background: #FFF7ED;
            color: #F97316;
            border: 1px solid #FDBA74;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            padding: 4px 14px;
            margin-bottom: 20px;
        }
        .btn-logout {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 24px;
            background: transparent;
            color: #6b7280;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: background .15s;
        }
        .btn-logout:hover { background: #f3f4f6; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon-wrap">
            <i class="bi bi-clock-history" style="font-size:32px;color:#F97316;"></i>
        </div>
        <div class="badge-trial">{{ __('common.trial_badge') }}</div>
        <h1>{{ __('common.trial_title_page') }}</h1>
        <p>
            {!! __('common.trial_message_page') !!}
        </p>
        <div style="display:flex;flex-direction:column;gap:12px;align-items:center;">
            <a href="{{ route('billing.checkout') }}" style="display:inline-flex;align-items:center;gap:8px;padding:12px 32px;background:#0085f3;color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;text-decoration:none;">
                <i class="bi bi-credit-card"></i> {{ __('common.trial_choose_plan') }}
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn-logout">
                    <i class="bi bi-box-arrow-right"></i>
                    {{ __('common.trial_logout') }}
                </button>
            </form>
        </div>
    </div>
</body>
</html>

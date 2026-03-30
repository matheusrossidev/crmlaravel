<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro em Análise — Syncro CRM</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://unpkg.com/@dotlottie/player-component@2.7.12/dist/dotlottie-player.mjs" type="module"></script>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'DM Sans', sans-serif; margin: 0; min-height: 100vh; background: #f4f6fb; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .card { background: #fff; border-radius: 24px; border: 1px solid #e8eaf0; padding: 48px 36px; width: 100%; max-width: 460px; text-align: center; box-shadow: 0 8px 40px rgba(0,0,0,.06); }
        .lottie-wrap { width: 140px; height: 140px; margin: 0 auto 12px; }
        .title { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 22px; font-weight: 700; color: #1a1d23; margin: 0 0 10px; }
        .desc { font-size: 14px; color: #677489; line-height: 1.6; margin: 0 0 28px; }
        .logout-btn { display: inline-flex; align-items: center; gap: 6px; padding: 10px 20px; background: #f3f4f6; color: #374151; border: none; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; transition: background .15s; }
        .logout-btn:hover { background: #e5e7eb; }
    </style>
</head>
<body>
<div class="card">
    <div style="margin-bottom:20px;"><img src="{{ asset('images/logo.png') }}" style="height:36px;" alt="Syncro"></div>
    <div class="lottie-wrap">
        <dotlottie-player src="{{ asset('images/lotties/loading-partner.json') }}" background="transparent" speed="1" loop autoplay style="width:140px;height:140px;"></dotlottie-player>
    </div>
    <h1 class="title">Cadastro em análise</h1>
    <p class="desc">
        Seu cadastro como parceiro está sendo analisado pela nossa equipe.
        Você receberá uma notificação assim que for aprovado.<br><br>
        Isso geralmente leva até 24 horas úteis.
    </p>
    @if(session('impersonating_tenant_id'))
        <form method="POST" action="{{ route('agency.access.exit') }}" style="margin-bottom:12px;">
            @csrf
            <button type="submit" style="display:inline-flex;align-items:center;gap:8px;padding:10px 22px;background:#eff6ff;color:#0085f3;border:1.5px solid #bfdbfe;border-radius:100px;font-size:13px;font-weight:600;font-family:'DM Sans',sans-serif;cursor:pointer;transition:background .15s;">
                <i class="bi bi-arrow-left"></i>
                Voltar para minha conta
            </button>
        </form>
    @endif
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="logout-btn">
            <i class="bi bi-box-arrow-left"></i> Sair
        </button>
    </form>
</div>
</body>
</html>

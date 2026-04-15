<!DOCTYPE html>
<html lang="pt-BR">
<head>
    @include("partials._google-analytics")
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Obrigado!</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'DM Sans', sans-serif; margin: 0; min-height: 100vh; background: #f4f6fb; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .thanks-card { background: #fff; border-radius: 20px; border: 1px solid #e8eaf0; padding: 48px 32px; width: 100%; max-width: 420px; text-align: center; box-shadow: 0 4px 24px rgba(0,0,0,.06); }
        .thanks-icon { font-size: 56px; margin-bottom: 16px; }
        .thanks-title { font-size: 22px; font-weight: 700; color: #1a1d23; margin: 0 0 10px; }
        .thanks-msg { font-size: 14px; color: #6b7280; line-height: 1.6; margin: 0; }
        .powered { margin-top: 28px; font-size: 11px; color: #c4c9d2; }
    </style>
</head>
<body>
<div class="thanks-card">
    @if($tenant?->logo)
        <div style="margin-bottom:20px;"><img src="{{ $tenant->logo }}" style="max-height:40px;" alt=""></div>
    @endif
    <div class="thanks-icon">{{ $alreadyAnswered ? '✅' : '🎉' }}</div>
    <h1 class="thanks-title">{{ $alreadyAnswered ? 'Resposta já registrada' : 'Obrigado!' }}</h1>
    <p class="thanks-msg">
        @if($alreadyAnswered)
            Você já respondeu a esta pesquisa. Agradecemos sua participação!
        @elseif($message)
            {{ $message }}
        @else
            Sua opinião é muito importante para nós. Agradecemos por dedicar seu tempo!
        @endif
    </p>
    <div class="powered">Powered by Syncro CRM</div>
</div>
</body>
</html>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    @include("partials._google-analytics")
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesquisa expirada</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'DM Sans', sans-serif; margin: 0; min-height: 100vh; background: #f4f6fb; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .card { background: #fff; border-radius: 20px; border: 1px solid #e8eaf0; padding: 48px 32px; width: 100%; max-width: 420px; text-align: center; box-shadow: 0 4px 24px rgba(0,0,0,.06); }
    </style>
</head>
<body>
<div class="card">
    <div style="font-size:56px;margin-bottom:16px;">⏰</div>
    <h1 style="font-size:22px;font-weight:700;color:#1a1d23;margin:0 0 10px;">Pesquisa expirada</h1>
    <p style="font-size:14px;color:#6b7280;line-height:1.6;margin:0;">O prazo para responder esta pesquisa já passou. Agradecemos pelo interesse!</p>
    <div style="margin-top:28px;font-size:11px;color:#c4c9d2;">Powered by Syncro CRM</div>
</div>
</body>
</html>

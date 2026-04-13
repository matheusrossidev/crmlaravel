<!DOCTYPE html>
<html lang="pt_BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $form->name }}</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: 'Inter', sans-serif; background: {{ $form->background_color ?? '#ffffff' }}; min-height:100vh; display:flex; align-items:center; justify-content:center; padding:24px; }
        .card { background:#fff; border-radius:16px; box-shadow:0 4px 24px rgba(0,0,0,.06); padding:48px 36px; max-width:460px; text-align:center; }
        .icon { width:64px; height:64px; border-radius:50%; background:#ecfdf5; display:flex; align-items:center; justify-content:center; margin:0 auto 20px; }
        h1 { font-size:20px; font-weight:700; color:#1a1d23; margin-bottom:10px; }
        p { font-size:14px; color:#6b7280; line-height:1.6; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">
            <svg width="28" height="28" fill="none" stroke="#059669" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
        </div>
        <h1>{{ $message }}</h1>
    </div>
</body>
</html>

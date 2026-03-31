<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificado — {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DM Sans', sans-serif; background: #f4f6fb; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .cert { background: #fff; border-radius: 24px; border: 2px solid #e8eaf0; max-width: 600px; width: 100%; padding: 48px 40px; text-align: center; box-shadow: 0 8px 40px rgba(0,0,0,.06); position: relative; overflow: hidden; }
        .cert::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 6px; background: linear-gradient(90deg, #0085f3, #8B5CF6, #f59e0b); }
        .cert-logo { height: 32px; margin-bottom: 28px; }
        .cert-icon { font-size: 48px; color: #10B981; margin-bottom: 16px; }
        .cert-label { font-size: 12px; font-weight: 600; color: #97A3B7; text-transform: uppercase; letter-spacing: .1em; margin-bottom: 6px; }
        .cert-name { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 24px; font-weight: 800; color: #1a1d23; margin-bottom: 8px; }
        .cert-course { font-size: 16px; font-weight: 600; color: #374151; margin-bottom: 24px; }
        .cert-meta { font-size: 13px; color: #6b7280; line-height: 1.8; margin-bottom: 24px; }
        .cert-code { display: inline-block; padding: 8px 20px; background: #f3f4f6; border-radius: 8px; font-family: monospace; font-size: 14px; font-weight: 700; color: #374151; letter-spacing: .08em; }
        .cert-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 16px; background: #ecfdf5; color: #059669; border-radius: 99px; font-size: 13px; font-weight: 600; margin-top: 20px; }
    </style>
</head>
<body>
<div class="cert">
    <img src="{{ asset('images/logo.png') }}" class="cert-logo" alt="Syncro">

    <div class="cert-icon"><i class="bi bi-patch-check-fill"></i></div>

    <div class="cert-label">Certificado de conclusão</div>
    <div class="cert-name">{{ $cert->tenant?->name ?? 'Parceiro' }}</div>
    <div class="cert-course">{{ $cert->course?->title ?? 'Curso' }}</div>

    <div class="cert-meta">
        Concluiu com sucesso o curso acima na plataforma Syncro.<br>
        Emitido em {{ $cert->issued_at?->format('d/m/Y') }}.
    </div>

    <div class="cert-code">{{ $cert->certificate_code }}</div>

    <div class="cert-badge"><i class="bi bi-shield-check"></i> Certificado verificado</div>
</div>
</body>
</html>

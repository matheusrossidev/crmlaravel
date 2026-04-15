<!DOCTYPE html>
<html lang="pt-BR">
<head>
    @include("partials._google-analytics")
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $success ? 'WhatsApp Cloud conectado!' : 'Erro' }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f8fafc;
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .card {
            background: #fff;
            border-radius: 14px;
            padding: 40px 30px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0,0,0,.08);
            max-width: 380px;
            width: 100%;
        }
        .icon {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            margin-bottom: 16px;
            color: #fff;
        }
        .icon.ok  { background: #25D366; }
        .icon.err { background: #ef4444; }
        h1 {
            font-size: 17px;
            font-weight: 700;
            color: #1a1d23;
            margin: 0 0 8px;
        }
        p {
            color: #6b7280;
            font-size: 13px;
            margin: 0 0 18px;
            line-height: 1.5;
        }
        .info {
            font-size: 11px;
            color: #9ca3af;
        }
        .err-msg {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 10px 12px;
            border-radius: 8px;
            font-size: 12px;
            margin-bottom: 14px;
            text-align: left;
            word-break: break-word;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon {{ $success ? 'ok' : 'err' }}">
            {!! $success ? '&#10003;' : '&#10005;' !!}
        </div>
        <h1>{{ $message }}</h1>
        @if($success && !empty($detail))
            <p>{{ $detail }}</p>
        @elseif(!$success && !empty($detail))
            <div class="err-msg">{{ $detail }}</div>
        @endif
        <p class="info">Esta janela vai fechar automaticamente...</p>
    </div>
    <script>
        (function() {
            // Notifica a página pai (caso esteja em pop-up)
            if (window.opener && !window.opener.closed) {
                try {
                    window.opener.postMessage({
                        type: 'wacloud_done',
                        success: {{ $success ? 'true' : 'false' }}
                    }, '*');
                } catch (e) {}
            }

            // Tenta fechar a janelinha após 1.5s (tempo do usuário ler)
            setTimeout(function() {
                try { window.close(); } catch (e) {}
                // Fallback: se window.close() falhar (alguns navegadores bloqueiam),
                // redireciona pra página de integrações.
                setTimeout(function() {
                    window.location.href = '/configuracoes/integracoes';
                }, 800);
            }, 1500);
        })();
    </script>
</body>
</html>

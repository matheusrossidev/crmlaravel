<!DOCTYPE html>
<html lang="pt-BR">
<head>
    @include("partials._google-analytics")
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>{{ $botName }} — {{ $tenantName }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html, body {
            height: 100%;
            overflow: hidden;
            background: #f9fafb;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        .hosted-wrapper {
            display: flex;
            flex-direction: column;
            height: 100vh;
            height: 100dvh;
        }

        .hosted-chat {
            flex: 1;
            min-height: 0;
        }

        .hosted-footer {
            flex-shrink: 0;
            text-align: center;
            padding: 8px 16px;
            background: #fff;
            border-top: 1px solid #f0f2f7;
        }

        .hosted-footer a {
            font-size: 11.5px;
            color: #9ca3af;
            text-decoration: none;
            transition: color .15s;
        }

        .hosted-footer a:hover {
            color: #6b7280;
        }

        .hosted-footer span {
            font-size: 11.5px;
            color: #d1d5db;
        }

        /* Override widget inline styles to fill the container */
        #syncro-chat {
            width: 100% !important;
            height: 100% !important;
        }
    </style>
</head>
<body>
    <div class="hosted-wrapper">
        <div class="hosted-chat">
            <div id="syncro-chat"></div>
        </div>
        <div class="hosted-footer">
            <a href="https://syncro.chat" target="_blank" rel="noopener" style="display:inline-flex;align-items:center;gap:5px;">
                <span>Feito com</span>
                <img src="{{ asset('images/logo.png') }}" alt="Syncro" style="height:16px;">
            </a>
        </div>
    </div>

    <script src="{{ $scriptUrl }}" data-color="{{ $widgetColor }}"></script>
</body>
</html>

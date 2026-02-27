<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Confirme seu email — Syncro</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:16px;color:#1f2937;">
  <table width="100%" cellpadding="0" cellspacing="0" style="padding:40px 16px;">
    <tr><td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.08),0 4px 16px rgba(0,0,0,.06);">

        <!-- Header -->
        <tr>
          <td style="background:linear-gradient(135deg,#0085f3 0%,#006fd6 100%);padding:36px 40px 32px;text-align:center;">
            <img src="{{ url('/images/logo-white.png') }}" alt="Syncro" style="height:44px;width:auto;display:block;margin:0 auto;" />
            <div style="margin:24px auto 0;width:64px;height:64px;background:rgba(255,255,255,.15);border-radius:50%;display:inline-flex;align-items:center;justify-content:center;">
              <svg width="32" height="32" viewBox="0 0 24 24" fill="#fff" xmlns="http://www.w3.org/2000/svg">
                <path d="M20 4H4C2.9 4 2 4.9 2 6v12c0 1.1.9 2 2 2h12.09A5.96 5.96 0 0 1 16 18c0-3.31 2.69-6 6-6 .34 0 .67.03 1 .09V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2zm4.5 9.5L23 19l-2.75 2.75L18.5 20l-1.5 1.5 2.75 2.75L23 22l3.5-3.5-1.5-1.5z"/>
              </svg>
            </div>
          </td>
        </tr>

        <!-- Body -->
        <tr>
          <td style="padding:40px;">
            <p style="font-size:22px;font-weight:700;color:#111827;margin:0 0 12px;">Confirme seu endereço de email</p>
            <p style="color:#6b7280;line-height:1.6;margin:0 0 20px;">
              Olá, <strong>{{ $user->name }}</strong>! Bem-vindo à Syncro.<br/>
              Para ativar sua conta e começar a usar a plataforma, confirme seu endereço de email clicando no botão abaixo.
            </p>

            <!-- CTA -->
            <table width="100%" cellpadding="0" cellspacing="0" style="margin:32px 0;">
              <tr><td align="center">
                <a href="{{ $verifyUrl }}" style="display:inline-block;background:#0085f3;color:#fff;text-decoration:none;font-weight:600;font-size:15px;padding:14px 40px;border-radius:8px;letter-spacing:0.2px;">
                  Confirmar meu email
                </a>
              </td></tr>
            </table>

            <p style="text-align:center;font-size:13px;color:#9ca3af;margin:0 0 20px;">
              Este link expira em <strong style="color:#374151;">48 horas</strong>.
            </p>

            <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:16px;word-break:break-all;font-size:13px;color:#6b7280;margin-top:24px;">
              <strong>Não conseguiu clicar no botão?</strong> Copie e cole o link abaixo no seu navegador:<br/><br/>
              <a href="{{ $verifyUrl }}" style="color:#0085f3;text-decoration:none;">{{ $verifyUrl }}</a>
            </div>

            <hr style="border:none;border-top:1px solid #f3f4f6;margin:32px 0;" />

            <p style="font-size:14px;color:#6b7280;line-height:1.6;margin:0;">
              Se você não criou uma conta na Syncro, pode ignorar este email com segurança. Nenhuma ação será tomada.
            </p>
          </td>
        </tr>

        <!-- Footer -->
        <tr>
          <td style="padding:0 40px 36px;text-align:center;">
            <p style="font-size:12px;color:#9ca3af;line-height:1.7;margin:0;">
              Syncro Plataforma · Você está recebendo este email porque se cadastrou em
              <a href="{{ config('app.url') }}" style="color:#9ca3af;text-decoration:underline;">app.syncro.chat</a>
            </p>
          </td>
        </tr>

      </table>

      <p style="text-align:center;font-size:12px;color:#9ca3af;margin-top:20px;">
        © {{ date('Y') }} Syncro. Todos os direitos reservados.
      </p>
    </td></tr>
  </table>
</body>
</html>

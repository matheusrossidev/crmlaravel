<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Redefinição de senha — Syncro</title>
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
              <svg width="30" height="30" viewBox="0 0 24 24" fill="#fff" xmlns="http://www.w3.org/2000/svg">
                <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM9 6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9V6zm3 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2z"/>
              </svg>
            </div>
          </td>
        </tr>

        <!-- Body -->
        <tr>
          <td style="padding:40px;">
            <p style="font-size:22px;font-weight:700;color:#111827;margin:0 0 12px;">Redefinição de senha</p>
            <p style="color:#6b7280;line-height:1.6;margin:0 0 20px;">
              Olá, <strong>{{ $user->name }}</strong>! Recebemos uma solicitação para redefinir a senha
              da sua conta Syncro. Clique no botão abaixo para criar uma nova senha.
            </p>

            <!-- Alert -->
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;">
              <tr>
                <td style="background:#fef3c7;border:1px solid #fde68a;border-radius:8px;padding:14px 16px;">
                  <table cellpadding="0" cellspacing="0">
                    <tr>
                      <td style="font-size:18px;padding-right:10px;vertical-align:top;">⏱️</td>
                      <td style="font-size:13px;color:#92400e;line-height:1.5;">
                        <strong>Atenção:</strong> Este link é válido por apenas <strong>15 minutos</strong>.
                        Após esse prazo, você precisará solicitar um novo link.
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>

            <!-- CTA -->
            <table width="100%" cellpadding="0" cellspacing="0" style="margin:28px 0;">
              <tr><td align="center">
                <a href="{{ $resetUrl }}" style="display:inline-block;background:#0085f3;color:#fff;text-decoration:none;font-weight:600;font-size:15px;padding:14px 40px;border-radius:8px;letter-spacing:0.2px;">
                  Redefinir minha senha
                </a>
              </td></tr>
            </table>

            <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:16px;word-break:break-all;font-size:13px;color:#6b7280;margin-top:24px;">
              <strong>Não conseguiu clicar no botão?</strong> Copie e cole o link abaixo no seu navegador:<br/><br/>
              <a href="{{ $resetUrl }}" style="color:#0085f3;text-decoration:none;">{{ $resetUrl }}</a>
            </div>

            <hr style="border:none;border-top:1px solid #f3f4f6;margin:32px 0;" />

            <p style="font-size:14px;color:#6b7280;line-height:1.6;margin:0 0 12px;">
              Se você não solicitou a redefinição de senha, ignore este email com segurança.
              Sua senha permanece a mesma e nenhuma alteração foi feita.
            </p>
            <p style="font-size:14px;color:#6b7280;line-height:1.6;margin:0;">
              Se você acredita que sua conta foi comprometida, entre em contato com nosso
              suporte em <a href="mailto:suporte@syncro.chat" style="color:#0085f3;">suporte@syncro.chat</a>.
            </p>
          </td>
        </tr>

        <!-- Footer -->
        <tr>
          <td style="padding:0 40px 36px;text-align:center;">
            <p style="font-size:12px;color:#9ca3af;line-height:1.7;margin:0;">
              Syncro Plataforma · Você está recebendo este email porque solicitou redefinição de senha em
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

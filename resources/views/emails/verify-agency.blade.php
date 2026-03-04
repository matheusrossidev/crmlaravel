<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Bem-vindo ao Programa de Parceiros — Syncro</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:16px;color:#1f2937;">
  <table width="100%" cellpadding="0" cellspacing="0" style="padding:40px 16px;">
    <tr><td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.08),0 4px 16px rgba(0,0,0,.06);">

        <!-- Header -->
        <tr>
          <td style="background:linear-gradient(135deg,#7C3AED 0%,#5B21B6 100%);padding:36px 40px 32px;text-align:center;">
            <img src="{{ url('/images/logo-white.png') }}" alt="Syncro" style="height:44px;width:auto;display:block;margin:0 auto;" />
            <div style="margin:24px auto 0;width:64px;height:64px;background:rgba(255,255,255,.15);border-radius:50%;display:inline-flex;align-items:center;justify-content:center;">
              <svg width="32" height="32" viewBox="0 0 24 24" fill="#fff" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
              </svg>
            </div>
            <h1 style="color:#fff;margin:16px 0 0;font-size:20px;font-weight:700;">Você foi aceito como Parceiro!</h1>
          </td>
        </tr>

        <!-- Body -->
        <tr>
          <td style="padding:40px;">
            <p style="font-size:22px;font-weight:700;color:#111827;margin:0 0 12px;">Confirme seu endereço de email</p>
            <p style="color:#6b7280;line-height:1.6;margin:0 0 16px;">
              Olá, <strong>{{ $user->name }}</strong>! Bem-vindo ao <strong>Programa de Parceiros da Syncro</strong>.<br/>
              Sua agência <strong>{{ $tenant->name }}</strong> foi cadastrada com sucesso.
            </p>

            <!-- Benefits -->
            <div style="background:#F5F3FF;border:1px solid #DDD6FE;border-radius:10px;padding:18px 20px;margin:0 0 24px;">
              <p style="font-size:13.5px;font-weight:700;color:#5B21B6;margin:0 0 10px;">Benefícios do Plano Parceiro:</p>
              <table cellpadding="0" cellspacing="0" style="width:100%;">
                <tr><td style="padding:3px 0;font-size:13.5px;color:#374151;">✓ &nbsp;Usuários ilimitados</td></tr>
                <tr><td style="padding:3px 0;font-size:13.5px;color:#374151;">✓ &nbsp;Leads e pipelines ilimitados</td></tr>
                <tr><td style="padding:3px 0;font-size:13.5px;color:#374151;">✓ &nbsp;Agentes de IA incluídos</td></tr>
                <tr><td style="padding:3px 0;font-size:13.5px;color:#374151;">✓ &nbsp;Acesso às contas dos seus clientes</td></tr>
                <tr><td style="padding:3px 0;font-size:13.5px;color:#374151;">✓ &nbsp;Sem cobrança mensal</td></tr>
              </table>
            </div>

            <p style="color:#6b7280;line-height:1.6;margin:0 0 20px;">
              Para ativar sua conta e começar, confirme seu endereço de email clicando no botão abaixo.
            </p>

            <!-- CTA -->
            <table width="100%" cellpadding="0" cellspacing="0" style="margin:24px 0;">
              <tr><td align="center">
                <a href="{{ $verifyUrl }}" style="display:inline-block;background:#7C3AED;color:#fff;text-decoration:none;font-weight:600;font-size:15px;padding:14px 40px;border-radius:8px;letter-spacing:0.2px;">
                  Confirmar e ativar minha conta
                </a>
              </td></tr>
            </table>

            <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:16px;word-break:break-all;font-size:13px;color:#6b7280;margin-top:16px;">
              <strong>Não conseguiu clicar no botão?</strong> Copie e cole o link abaixo no seu navegador:<br/><br/>
              <a href="{{ $verifyUrl }}" style="color:#7C3AED;text-decoration:none;">{{ $verifyUrl }}</a>
            </div>

            <hr style="border:none;border-top:1px solid #f3f4f6;margin:32px 0;" />
            <p style="font-size:14px;color:#6b7280;line-height:1.6;margin:0;">
              Se você não criou uma conta parceira na Syncro, pode ignorar este email.
            </p>
          </td>
        </tr>

        <!-- Footer -->
        <tr>
          <td style="padding:0 40px 36px;text-align:center;">
            <p style="font-size:12px;color:#9ca3af;line-height:1.7;margin:0;">
              Syncro Parceiros · <a href="{{ config('app.url') }}" style="color:#9ca3af;text-decoration:underline;">app.syncro.chat</a>
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

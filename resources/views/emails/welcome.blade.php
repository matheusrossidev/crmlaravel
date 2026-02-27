<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Bem-vindo à Syncro!</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:16px;color:#1f2937;">
  <table width="100%" cellpadding="0" cellspacing="0" style="padding:40px 16px;">
    <tr><td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.08),0 4px 16px rgba(0,0,0,.06);">

        <!-- Header -->
        <tr>
          <td style="background:linear-gradient(135deg,#0085f3 0%,#006fd6 100%);padding:48px 40px 40px;text-align:center;">
            <img src="{{ url('/images/logo-white.png') }}" alt="Syncro" style="height:44px;width:auto;display:block;margin:0 auto;" />
            <div style="font-size:52px;margin:20px 0 16px;display:block;">🎉</div>
            <div style="font-size:26px;font-weight:800;color:#fff;line-height:1.3;">Sua conta está ativa!</div>
            <div style="color:#bfdbfe;font-size:15px;margin-top:8px;">Email confirmado com sucesso</div>
          </td>
        </tr>

        <!-- Body -->
        <tr>
          <td style="padding:40px;">
            <p style="font-size:20px;font-weight:700;color:#111827;margin:0 0 12px;">Bem-vindo, {{ $user->name }}!</p>
            <p style="color:#6b7280;line-height:1.6;margin:0 0 28px;">
              Sua conta na empresa <strong>{{ $tenant->name }}</strong> foi ativada com sucesso.
              Você tem acesso a todos os recursos da plataforma durante seu período de teste.
            </p>

            <!-- Steps -->
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:12px;">
              <tr>
                <td style="background:#eff6ff;border:1px solid #dbeafe;border-radius:10px;padding:16px;">
                  <table cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                      <td width="36" style="vertical-align:top;padding-right:12px;">
                        <div style="background:#0085f3;color:#fff;font-weight:700;font-size:14px;width:28px;height:28px;border-radius:50%;text-align:center;line-height:28px;">1</div>
                      </td>
                      <td>
                        <strong style="font-size:15px;color:#1f2937;">Configure seu pipeline de vendas</strong><br/>
                        <span style="font-size:13px;color:#6b7280;line-height:1.5;">Crie etapas personalizadas para organizar seus leads.</span>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>

            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:12px;">
              <tr>
                <td style="background:#eff6ff;border:1px solid #dbeafe;border-radius:10px;padding:16px;">
                  <table cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                      <td width="36" style="vertical-align:top;padding-right:12px;">
                        <div style="background:#0085f3;color:#fff;font-weight:700;font-size:14px;width:28px;height:28px;border-radius:50%;text-align:center;line-height:28px;">2</div>
                      </td>
                      <td>
                        <strong style="font-size:15px;color:#1f2937;">Importe seus contatos</strong><br/>
                        <span style="font-size:13px;color:#6b7280;line-height:1.5;">Suba uma planilha Excel ou adicione manualmente pelo CRM.</span>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>

            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;">
              <tr>
                <td style="background:#eff6ff;border:1px solid #dbeafe;border-radius:10px;padding:16px;">
                  <table cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                      <td width="36" style="vertical-align:top;padding-right:12px;">
                        <div style="background:#0085f3;color:#fff;font-weight:700;font-size:14px;width:28px;height:28px;border-radius:50%;text-align:center;line-height:28px;">3</div>
                      </td>
                      <td>
                        <strong style="font-size:15px;color:#1f2937;">Conecte seu WhatsApp</strong><br/>
                        <span style="font-size:13px;color:#6b7280;line-height:1.5;">Integre o WhatsApp Business e responda diretamente no painel.</span>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>

            <!-- CTA -->
            <table width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 12px;">
              <tr><td align="center">
                <a href="{{ $loginUrl }}" style="display:inline-block;background:#0085f3;color:#fff;text-decoration:none;font-weight:600;font-size:15px;padding:14px 40px;border-radius:8px;">
                  Acessar minha conta
                </a>
              </td></tr>
            </table>

            <p style="text-align:center;font-size:13px;color:#059669;font-weight:600;margin:0 0 32px;">
              ✅ 14 dias de teste grátis — sem cartão de crédito
            </p>

            <hr style="border:none;border-top:1px solid #f3f4f6;margin:0 0 24px;" />

            <div style="background:#f9fafb;border-radius:8px;padding:20px;text-align:center;">
              <p style="font-size:14px;color:#6b7280;margin:0;">Ficou com alguma dúvida? Estamos aqui para ajudar.</p>
              <p style="font-size:14px;color:#6b7280;margin:8px 0 0;">
                Fale conosco em <a href="mailto:suporte@syncro.chat" style="color:#0085f3;font-weight:600;text-decoration:none;">suporte@syncro.chat</a>
              </p>
            </div>
          </td>
        </tr>

        <!-- Footer -->
        <tr>
          <td style="padding:0 40px 36px;text-align:center;">
            <p style="font-size:12px;color:#9ca3af;line-height:1.7;margin:0;">
              Syncro Plataforma · Você está recebendo este email porque confirmou seu cadastro em
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

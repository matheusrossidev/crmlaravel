<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Assinatura cancelada</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:16px;color:#1f2937;">
  <table width="100%" cellpadding="0" cellspacing="0" style="padding:40px 16px;">
    <tr><td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.08),0 4px 16px rgba(0,0,0,.06);">

        <!-- Header -->
        <tr>
          <td style="background:linear-gradient(135deg,#6b7280 0%,#4b5563 100%);padding:48px 40px 40px;text-align:center;">
            <img src="{{ url('/images/logo-white.png') }}" alt="Syncro" style="height:44px;width:auto;display:block;margin:0 auto;" />
            <div style="font-size:52px;margin:20px 0 16px;display:block;">👋</div>
            <div style="font-size:26px;font-weight:800;color:#fff;line-height:1.3;">Assinatura cancelada</div>
            <div style="color:#d1d5db;font-size:15px;margin-top:8px;">Sentiremos sua falta</div>
          </td>
        </tr>

        <!-- Body -->
        <tr>
          <td style="padding:40px;">
            <p style="font-size:20px;font-weight:700;color:#111827;margin:0 0 12px;">Olá, {{ $user->name }}.</p>
            <p style="color:#6b7280;line-height:1.6;margin:0 0 24px;">
              Confirmamos o cancelamento da assinatura da <strong>{{ $tenant->name }}</strong>.
              @if($plan)
                Seu acesso ao plano <strong>{{ $plan->display_name }}</strong> foi encerrado.
              @endif
            </p>

            <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:20px;margin-bottom:28px;">
              <p style="font-size:14px;color:#6b7280;margin:0;">
                Se você cancelou por engano ou quer reativar sua conta, basta assinar novamente a qualquer momento.
              </p>
            </div>

            <hr style="border:none;border-top:1px solid #f3f4f6;margin:0 0 24px;" />

            <div style="background:#f9fafb;border-radius:8px;padding:20px;text-align:center;">
              <p style="font-size:14px;color:#6b7280;margin:0;">Algum problema que podemos resolver?
                <a href="mailto:suporte@syncro.chat" style="color:#0085f3;font-weight:600;text-decoration:none;">suporte@syncro.chat</a>
              </p>
            </div>
          </td>
        </tr>

        <!-- Footer -->
        <tr>
          <td style="padding:0 40px 36px;text-align:center;">
            <p style="font-size:12px;color:#9ca3af;line-height:1.7;margin:0;">
              Syncro Plataforma · <a href="{{ config('app.url') }}" style="color:#9ca3af;text-decoration:underline;">app.syncro.chat</a>
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

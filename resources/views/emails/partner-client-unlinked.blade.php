<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Cliente desvinculado — Syncro Parceiros</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:16px;color:#1f2937;">
  <table width="100%" cellpadding="0" cellspacing="0" style="padding:40px 16px;">
    <tr><td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.08),0 4px 16px rgba(0,0,0,.06);">

        <!-- Header -->
        <tr>
          <td style="background:linear-gradient(135deg,#dc2626 0%,#b91c1c 100%);padding:36px 40px 32px;text-align:center;">
            <img src="{{ url('/images/logo-white.png') }}" alt="Syncro" style="height:44px;width:auto;display:block;margin:0 auto;" />
            <div style="margin:24px auto 0;width:64px;height:64px;background:rgba(255,255,255,.15);border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:32px;">
              ⚠️
            </div>
            <h1 style="color:#fff;margin:16px 0 0;font-size:20px;font-weight:700;">Cliente desvinculado</h1>
          </td>
        </tr>

        <!-- Body -->
        <tr>
          <td style="padding:40px;">
            <p style="font-size:18px;font-weight:700;color:#111827;margin:0 0 12px;">
              {{ $partnerName }}, um cliente se desvinculou.
            </p>
            <p style="color:#6b7280;line-height:1.6;margin:0 0 24px;">
              O cliente abaixo se desvinculou da sua agência parceira no Syncro.
            </p>

            <!-- Client card -->
            <div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:10px;padding:20px 22px;margin:0 0 24px;">
              <p style="font-size:12px;font-weight:700;color:#dc2626;text-transform:uppercase;letter-spacing:.08em;margin:0 0 8px;">Cliente desvinculado</p>
              <p style="font-size:20px;font-weight:700;color:#111827;margin:0 0 4px;">{{ $clientName }}</p>
              <p style="font-size:13.5px;color:#6b7280;margin:0;">Desvinculado em {{ now()->translatedFormat('d \d\e F \d\e Y \à\s H:i') }}</p>
            </div>

            <!-- Info -->
            <div style="background:#FFFBEB;border:1px solid #FDE68A;border-radius:10px;padding:16px 20px;margin:0 0 28px;">
              <p style="font-size:13px;color:#92400E;margin:0;line-height:1.6;">
                <strong>O que acontece com suas comissões?</strong><br>
                • Comissões <strong>pendentes</strong> (em período de carência) foram canceladas.<br>
                • Comissões já <strong>liberadas</strong> ou <strong>sacadas</strong> foram mantidas integralmente.
              </p>
            </div>

            <!-- CTA -->
            <table width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;">
              <tr><td align="center">
                <a href="{{ url('/parceiro') }}" style="display:inline-block;background:#0085f3;color:#fff;text-decoration:none;font-weight:600;font-size:15px;padding:13px 36px;border-radius:8px;">
                  Acessar painel de parceiro
                </a>
              </td></tr>
            </table>

            <hr style="border:none;border-top:1px solid #f3f4f6;margin:0 0 20px;" />
            <p style="font-size:13px;color:#9ca3af;line-height:1.6;margin:0;">
              Você está recebendo este email porque é parceiro Syncro. Para dúvidas sobre comissões, entre em contato com o suporte.
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

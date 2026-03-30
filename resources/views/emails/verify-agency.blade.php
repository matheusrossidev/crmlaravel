<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Confirme seu email — Syncro Parceiros</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body style="margin:0;padding:0;background:#f4f6fb;font-family:'DM Sans',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:16px;color:#1f2937;">
  <table width="100%" cellpadding="0" cellspacing="0" style="padding:40px 16px;">
    <tr><td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#fff;border-radius:16px;overflow:hidden;border:1px solid #e8eaf0;">

        <tr><td style="padding:28px 40px;border-bottom:1px solid #f0f2f7;"><img src="{{ url('/images/logo.png') }}" alt="Syncro" style="height:28px;width:auto;" /></td></tr>

        <tr>
          <td style="padding:40px 40px 32px;">
            <h1 style="font-family:'DM Sans',sans-serif;font-size:24px;font-weight:700;color:#1a1d23;margin:0 0 16px;">Confirme seu email de parceiro</h1>
            <p style="color:#677489;line-height:1.7;margin:0 0 20px;font-size:15px;">
              Olá, <strong style="color:#1a1d23;">{{ $user->name }}</strong>! Sua agência
              <strong style="color:#1a1d23;">{{ $tenant->name }}</strong> foi cadastrada no Programa de Parceiros.
            </p>

            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;"><tr>
              <td style="background:#f8fafc;border:1px solid #e8eaf0;border-radius:12px;padding:18px 20px;">
                <p style="font-size:13px;font-weight:600;color:#1a1d23;margin:0 0 6px;">Próximo passo</p>
                <p style="font-size:13px;color:#677489;margin:0;line-height:1.5;">Após confirmar seu email, seu cadastro será analisado pela nossa equipe. Você receberá uma notificação quando aprovado (geralmente em até 24h).</p>
              </td>
            </tr></table>

            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;"><tr><td align="center">
              <a href="{{ $verifyUrl }}" style="display:inline-block;background:#0085f3;color:#fff;text-decoration:none;font-family:'DM Sans',sans-serif;font-weight:600;font-size:15px;padding:14px 36px;border-radius:100px;">Confirmar meu email →</a>
            </td></tr></table>

            <div style="background:#f8fafc;border:1px solid #e8eaf0;border-radius:12px;padding:16px;word-break:break-all;font-size:13px;color:#677489;">
              <strong style="color:#374151;">Não conseguiu clicar?</strong> Copie e cole:<br/><br/>
              <a href="{{ $verifyUrl }}" style="color:#0085f3;text-decoration:none;">{{ $verifyUrl }}</a>
            </div>

            <hr style="border:none;border-top:1px solid #f0f2f7;margin:28px 0 20px;" />
            <p style="font-size:13px;color:#97A3B7;line-height:1.6;margin:0;">Se você não se cadastrou como parceiro, ignore este email.</p>
          </td>
        </tr>

        <tr><td style="padding:24px 40px;border-top:1px solid #f0f2f7;background:#f8fafc;"><table width="100%" cellpadding="0" cellspacing="0"><tr><td style="text-align:center;">
          <img src="{{ url('/images/logo.png') }}" alt="Syncro" style="height:20px;width:auto;margin-bottom:12px;opacity:.5;" />
          <p style="font-size:12px;color:#97A3B7;margin:0 0 6px;">Syncro CRM — Gestão de clientes e atendimento via WhatsApp</p>
          <p style="font-size:11px;color:#c4c9d2;margin:0;">© {{ date('Y') }} Syncro · <a href="https://syncro.chat" style="color:#97A3B7;text-decoration:none;">syncro.chat</a></p>
        </td></tr></table></td></tr>

      </table>
    </td></tr>
  </table>
</body>
</html>

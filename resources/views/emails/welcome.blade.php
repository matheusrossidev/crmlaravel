<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Bem-vindo à Syncro!</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body style="margin:0;padding:0;background:#f4f6fb;font-family:'DM Sans',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:16px;color:#1f2937;">
  <table width="100%" cellpadding="0" cellspacing="0" style="padding:40px 16px;">
    <tr><td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#fff;border-radius:16px;overflow:hidden;border:1px solid #e8eaf0;">

        <tr><td style="padding:28px 40px;border-bottom:1px solid #f0f2f7;"><img src="{{ url('/images/logo.png') }}" alt="Syncro" style="height:28px;width:auto;" /></td></tr>

        <tr><td style="padding:15px 15px 0;">
          <div style="border-radius:12px;overflow:hidden;height:220px;background:#f0f2f7;">
            <img src="{{ url('/images/mocks/kanban.png') }}" alt="Syncro CRM" style="width:100%;height:220px;display:block;object-fit:cover;object-position:top;" />
          </div>
        </td></tr>

        <tr>
          <td style="padding:40px 40px 32px;">
            <h1 style="font-family:'DM Sans',sans-serif;font-size:24px;font-weight:700;color:#1a1d23;margin:0 0 16px;">Bem-vindo, {{ $user->name }}!</h1>
            <p style="color:#677489;line-height:1.7;margin:0 0 28px;font-size:15px;">Sua conta na <strong style="color:#1a1d23;">{{ $tenant->name }}</strong> está ativa. Aqui estão os primeiros passos:</p>

            @foreach([['1','Configure seu pipeline','Crie etapas para organizar seus leads.'],['2','Importe seus contatos','Suba uma planilha ou adicione manualmente.'],['3','Conecte seu WhatsApp','Responda seus clientes direto pelo painel.']] as $step)
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:10px;"><tr><td style="background:#f8fafc;border:1px solid #e8eaf0;border-radius:12px;padding:14px 16px;"><table cellpadding="0" cellspacing="0" width="100%"><tr>
              <td width="32" style="vertical-align:top;padding-right:12px;"><div style="background:#0085f3;color:#fff;font-weight:700;font-size:13px;width:26px;height:26px;border-radius:50%;text-align:center;line-height:26px;">{{ $step[0] }}</div></td>
              <td><strong style="font-size:14px;color:#1a1d23;">{{ $step[1] }}</strong><br/><span style="font-size:13px;color:#677489;">{{ $step[2] }}</span></td>
            </tr></table></td></tr></table>
            @endforeach

            <table width="100%" cellpadding="0" cellspacing="0" style="margin:28px 0 16px;"><tr><td align="center">
              <a href="{{ $loginUrl }}" style="display:inline-block;background:#0085f3;color:#fff;text-decoration:none;font-family:'DM Sans',sans-serif;font-weight:600;font-size:15px;padding:14px 36px;border-radius:100px;">Acessar minha conta →</a>
            </td></tr></table>

            <p style="text-align:center;font-size:13px;color:#10B981;font-weight:600;margin:0 0 20px;">14 dias de teste grátis — sem cartão de crédito</p>
            <hr style="border:none;border-top:1px solid #f0f2f7;margin:0 0 20px;" />
            <p style="font-size:13px;color:#97A3B7;line-height:1.6;margin:0;text-align:center;">Dúvidas? <a href="mailto:suporte@syncro.chat" style="color:#0085f3;text-decoration:none;">suporte@syncro.chat</a></p>
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

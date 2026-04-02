<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>{{ $template->subject ?? 'Syncro' }}</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body style="margin:0;padding:0;background:#f4f6fb;font-family:'DM Sans',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:16px;color:#1f2937;">
  <table width="100%" cellpadding="0" cellspacing="0" style="padding:40px 16px;">
    <tr><td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#fff;border-radius:16px;overflow:hidden;border:1px solid #e8eaf0;">

        {{-- Header with logo --}}
        <tr><td style="padding:28px 40px;border-bottom:1px solid #f0f2f7;">
          <img src="{{ url('/images/logo.png') }}" alt="Syncro" style="height:28px;width:auto;" />
        </td></tr>

        {{-- Body --}}
        <tr><td style="padding:40px 40px 32px;">
          <h1 style="font-family:'DM Sans',sans-serif;font-size:22px;font-weight:700;color:#1a1d23;margin:0 0 20px;">
            {{ $userName }}, seus leads estão esperando!
          </h1>

          <div style="color:#677489;line-height:1.8;font-size:15px;margin:0 0 28px;">
            {!! nl2br(e($renderedBody)) !!}
          </div>

          {{-- CTA Button --}}
          <table width="100%" cellpadding="0" cellspacing="0" style="margin:28px 0 16px;">
            <tr><td align="center">
              <a href="{{ $loginUrl }}" style="display:inline-block;background:#0085f3;color:#fff;text-decoration:none;font-family:'DM Sans',sans-serif;font-weight:600;font-size:15px;padding:14px 36px;border-radius:100px;">
                Acessar minha conta →
              </a>
            </td></tr>
          </table>

          <hr style="border:none;border-top:1px solid #f0f2f7;margin:24px 0;" />

          <p style="font-size:13px;color:#97A3B7;line-height:1.6;margin:0;text-align:center;">
            Dúvidas? <a href="mailto:suporte@syncro.chat" style="color:#0085f3;text-decoration:none;">suporte@syncro.chat</a>
          </p>
        </td></tr>

        {{-- Footer --}}
        <tr><td style="padding:20px 40px;background:#f8fafc;border-top:1px solid #f0f2f7;">
          <p style="font-size:11px;color:#97A3B7;line-height:1.5;margin:0;text-align:center;">
            © {{ date('Y') }} Syncro — Plataforma de CRM e Marketing
          </p>
        </td></tr>

      </table>
    </td></tr>
  </table>
</body>
</html>

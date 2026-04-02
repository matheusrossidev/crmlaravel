<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>@yield('title', 'Syncro')</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body style="margin:0;padding:0;background:#f4f6fb;font-family:'DM Sans',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:16px;color:#1f2937;">
  @hasSection('preheader')
  <div style="display:none;font-size:1px;color:#f4f6fb;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">
    @yield('preheader')
  </div>
  @endif

  <table width="100%" cellpadding="0" cellspacing="0" style="padding:40px 16px;">
    <tr><td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#fff;border-radius:16px;overflow:hidden;border:1px solid #e8eaf0;">

        {{-- Header with logo --}}
        <tr>
          <td style="padding:28px 40px;border-bottom:1px solid #f0f2f7;">
            <img src="{{ url('/images/logo.png') }}" alt="Syncro" style="height:28px;width:auto;" />
          </td>
        </tr>

        {{-- Content --}}
        <tr>
          <td style="padding:40px 40px 32px;">
            @yield('content')
          </td>
        </tr>

        {{-- Optional CTA --}}
        @hasSection('cta')
        <tr>
          <td style="padding:0 40px 32px;">
            <table width="100%" cellpadding="0" cellspacing="0">
              <tr><td align="center">
                @yield('cta')
              </td></tr>
            </table>
          </td>
        </tr>
        @endif

        {{-- Footer --}}
        <tr>
          <td style="padding:20px 40px;background:#f8fafc;border-top:1px solid #f0f2f7;">
            <table width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td style="text-align:center;">
                  <img src="{{ url('/images/logo.png') }}" alt="Syncro" style="height:20px;width:auto;margin-bottom:12px;opacity:.5;" />
                  <p style="font-size:12px;color:#97A3B7;margin:0 0 6px;line-height:1.5;">
                    {{ __('email.common.footer_support_text') }} <a href="mailto:suporte@syncro.chat" style="color:#0085f3;text-decoration:none;">suporte@syncro.chat</a>
                  </p>
                  <p style="font-size:11px;color:#c4c9d2;margin:0;">
                    {{ __('email.common.footer_copyright', ['year' => date('Y')]) }}
                  </p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

      </table>
    </td></tr>
  </table>
</body>
</html>

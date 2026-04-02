@extends('emails._layout')

@section('title', $title)

@section('preheader', $body)

@section('content')
  <h1 style="font-family:'DM Sans',sans-serif;font-size:24px;font-weight:700;color:#1a1d23;margin:0 0 16px;">
    {{ __('email.upsell.greeting', ['name' => $user->name]) }}
  </h1>
  <p style="color:#677489;line-height:1.7;margin:0 0 24px;font-size:15px;">
    {{ $body }}
  </p>

  {{-- CTA --}}
  <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;"><tr><td align="center">
    <a href="{{ $checkoutUrl }}" style="display:inline-block;background:#0085f3;color:#fff;text-decoration:none;font-family:'DM Sans',sans-serif;font-weight:600;font-size:15px;padding:14px 36px;border-radius:100px;">
      {{ $ctaText }}
    </a>
  </td></tr></table>

  {{-- Why upgrade --}}
  <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;"><tr>
    <td style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;padding:20px;">
      <p style="font-size:14px;color:#0085f3;font-weight:600;margin:0 0 8px;">{{ __('email.upsell.why_title') }}</p>
      <table cellpadding="0" cellspacing="0" width="100%">
        @foreach(['why_1','why_2','why_3'] as $key)
        <tr><td style="padding:3px 0;font-size:13px;color:#1e3a8a;">&bull; {{ __('email.upsell.' . $key) }}</td></tr>
        @endforeach
      </table>
    </td>
  </tr></table>

  <hr style="border:none;border-top:1px solid #f0f2f7;margin:0 0 24px;" />

  <table width="100%" cellpadding="0" cellspacing="0"><tr>
    <td style="background:#f8fafc;border-radius:12px;padding:20px;text-align:center;">
      <p style="font-size:14px;color:#677489;margin:0;">
        {{ __('email.upsell.support_question') }}
        <a href="mailto:suporte@syncro.chat" style="color:#0085f3;font-weight:600;text-decoration:none;">suporte@syncro.chat</a>
      </p>
    </td>
  </tr></table>
@endsection

@extends('emails._layout')

@section('title', __('email.payment_failed.subject'))

@section('preheader', __('email.payment_failed.body', ['tenant' => $tenant->name]))

@section('content')
  {{-- Alert badge --}}
  <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;"><tr>
    <td style="background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:16px;text-align:center;">
      <div style="font-size:18px;font-weight:700;color:#991b1b;">{{ __('email.payment_failed.title') }}</div>
    </td>
  </tr></table>

  <h1 style="font-family:'DM Sans',sans-serif;font-size:24px;font-weight:700;color:#1a1d23;margin:0 0 16px;">
    {{ __('email.payment_failed.greeting', ['name' => $user->name]) }}
  </h1>
  <p style="color:#677489;line-height:1.7;margin:0 0 24px;font-size:15px;">
    {{ __('email.payment_failed.body', ['tenant' => $tenant->name]) }}
  </p>

  {{-- CTA --}}
  <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;"><tr><td align="center">
    <a href="{{ $billingUrl }}" style="display:inline-block;background:#0085f3;color:#fff;text-decoration:none;font-family:'DM Sans',sans-serif;font-weight:600;font-size:15px;padding:14px 36px;border-radius:100px;">
      {{ __('email.payment_failed.cta') }}
    </a>
  </td></tr></table>

  {{-- Warning --}}
  <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;"><tr>
    <td style="background:#fffbeb;border:1px solid #fde68a;border-radius:12px;padding:16px 20px;">
      <p style="font-size:13px;color:#92400e;margin:0;line-height:1.6;">
        {{ __('email.payment_failed.warning') }}
      </p>
    </td>
  </tr></table>

  <hr style="border:none;border-top:1px solid #f0f2f7;margin:0 0 24px;" />

  <table width="100%" cellpadding="0" cellspacing="0"><tr>
    <td style="background:#f8fafc;border-radius:12px;padding:20px;text-align:center;">
      <p style="font-size:14px;color:#677489;margin:0;">
        {{ __('email.payment_failed.support_question') }}
        <a href="mailto:suporte@syncro.chat" style="color:#0085f3;font-weight:600;text-decoration:none;">suporte@syncro.chat</a>
      </p>
    </td>
  </tr></table>
@endsection

@extends('emails._layout')

@section('title', __('email.partner_unlinked.subject'))

@section('preheader', __('email.partner_unlinked.title', ['name' => $partnerName]))

@section('content')
  <h1 style="font-family:'DM Sans',sans-serif;font-size:24px;font-weight:700;color:#1a1d23;margin:0 0 16px;">
    {{ __('email.partner_unlinked.title', ['name' => $partnerName]) }}
  </h1>
  <p style="color:#677489;line-height:1.7;margin:0 0 24px;font-size:15px;">
    {{ __('email.partner_unlinked.body') }}
  </p>

  {{-- Client card --}}
  <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;"><tr>
    <td style="background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:20px 22px;">
      <div style="font-size:11px;font-weight:700;color:#dc2626;text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px;">{{ __('email.partner_unlinked.client_label') }}</div>
      <div style="font-size:20px;font-weight:700;color:#1a1d23;margin-bottom:4px;">{{ $clientName }}</div>
      <div style="font-size:13px;color:#677489;">{{ __('email.partner_unlinked.unlinked_at', ['date' => now()->translatedFormat('d \d\e F \d\e Y \à\s H:i')]) }}</div>
    </td>
  </tr></table>

  {{-- Commission info --}}
  <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;"><tr>
    <td style="background:#fffbeb;border:1px solid #fde68a;border-radius:12px;padding:16px 20px;">
      <p style="font-size:13px;color:#92400e;margin:0;line-height:1.6;">
        <strong>{{ __('email.partner_unlinked.commission_title') }}</strong><br/>
        &bull; {{ __('email.partner_unlinked.commission_pending') }}<br/>
        &bull; {{ __('email.partner_unlinked.commission_released') }}
      </p>
    </td>
  </tr></table>

  {{-- CTA --}}
  <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;"><tr><td align="center">
    <a href="{{ url('/parceiro') }}" style="display:inline-block;background:#0085f3;color:#fff;text-decoration:none;font-family:'DM Sans',sans-serif;font-weight:600;font-size:15px;padding:14px 36px;border-radius:100px;">
      {{ __('email.partner_unlinked.cta') }}
    </a>
  </td></tr></table>

  <hr style="border:none;border-top:1px solid #f0f2f7;margin:0 0 20px;" />
  <p style="font-size:13px;color:#97A3B7;line-height:1.6;margin:0;">
    {{ __('email.partner_unlinked.footer_note') }}
  </p>
@endsection

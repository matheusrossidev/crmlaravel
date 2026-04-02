@extends('emails._layout')

@section('title', __('email.agency_referral.subject', ['client' => $newClientTenant->name]))

@section('preheader', __('email.agency_referral.body'))

@section('content')
  <h1 style="font-family:'DM Sans',sans-serif;font-size:24px;font-weight:700;color:#1a1d23;margin:0 0 16px;">
    {{ __('email.agency_referral.title', ['name' => $agencyAdminUser->name]) }}
  </h1>
  <p style="color:#677489;line-height:1.7;margin:0 0 24px;font-size:15px;">
    {{ __('email.agency_referral.body') }}
  </p>

  {{-- Client card --}}
  <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;"><tr>
    <td style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;padding:20px 22px;">
      <div style="font-size:11px;font-weight:700;color:#0085f3;text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px;">{{ __('email.agency_referral.client_label') }}</div>
      <div style="font-size:20px;font-weight:700;color:#1a1d23;margin-bottom:4px;">{{ $newClientTenant->name }}</div>
      <div style="font-size:13px;color:#677489;">{{ __('email.agency_referral.registered_at', ['date' => now()->translatedFormat('d \d\e F \d\e Y')]) }}</div>
    </td>
  </tr></table>

  {{-- Counter --}}
  <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;"><tr>
    <td style="text-align:center;padding:16px;background:#f8fafc;border:1px solid #e8eaf0;border-radius:12px;">
      <div style="font-size:13px;color:#97A3B7;margin-bottom:4px;">{{ __('email.agency_referral.total_clients_label') }}</div>
      <div style="font-size:36px;font-weight:800;color:#0085f3;">{{ $totalClients }}</div>
    </td>
  </tr></table>

  {{-- CTA --}}
  <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;"><tr><td align="center">
    <a href="{{ url('/parceiro') }}" style="display:inline-block;background:#0085f3;color:#fff;text-decoration:none;font-family:'DM Sans',sans-serif;font-weight:600;font-size:15px;padding:14px 36px;border-radius:100px;">
      {{ __('email.agency_referral.cta') }}
    </a>
  </td></tr></table>

  <hr style="border:none;border-top:1px solid #f0f2f7;margin:0 0 20px;" />
  <p style="font-size:13px;color:#97A3B7;line-height:1.6;margin:0;">
    {{ __('email.agency_referral.footer_note') }}
  </p>
@endsection

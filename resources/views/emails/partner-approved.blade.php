@extends('emails._layout')

@section('title', __('email.partner_approved.subject'))

@section('preheader', __('email.partner_approved.body', ['name' => $user->name, 'tenant' => $tenant->name]))

@section('content')
  <div style="border-radius:12px;overflow:hidden;height:220px;background:#f0f2f7;margin:-8px -8px 0;">
    <img src="{{ url('/images/mocks/dashboard.png') }}" alt="Syncro Dashboard" style="width:100%;height:220px;display:block;object-fit:cover;object-position:top;" />
  </div>

  <div style="margin-top:32px;">
    <h1 style="font-family:'DM Sans',sans-serif;font-size:24px;font-weight:700;color:#1a1d23;margin:0 0 16px;">{{ __('email.partner_approved.title') }}</h1>
    <p style="color:#677489;line-height:1.7;margin:0 0 28px;font-size:15px;">
      {{ __('email.partner_approved.body', ['name' => $user->name, 'tenant' => $tenant->name]) }}
    </p>

    {{-- Code box --}}
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;"><tr>
      <td style="background:#f8fafc;border:1px solid #e8eaf0;border-radius:12px;padding:24px;text-align:center;">
        <div style="font-size:11px;font-weight:600;color:#97A3B7;text-transform:uppercase;letter-spacing:1.5px;margin-bottom:10px;">{{ __('email.partner_approved.code_label') }}</div>
        <div style="font-size:32px;font-weight:800;color:#0085f3;letter-spacing:3px;font-family:'Courier New',monospace;">{{ $code }}</div>
        <div style="font-size:13px;color:#97A3B7;margin-top:10px;">{{ __('email.partner_approved.code_hint') }}</div>
      </td>
    </tr></table>

    {{-- CTA --}}
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;"><tr><td align="center">
      <a href="{{ $loginUrl }}" style="display:inline-block;background:#0085f3;color:#fff;text-decoration:none;font-family:'DM Sans',sans-serif;font-weight:600;font-size:15px;padding:14px 36px;border-radius:100px;">{{ __('email.partner_approved.cta') }}</a>
    </td></tr></table>

    <hr style="border:none;border-top:1px solid #f0f2f7;margin:0 0 24px;" />

    <p style="font-size:14px;font-weight:600;color:#1a1d23;margin:0 0 10px;">{{ __('email.partner_approved.includes_title') }}</p>
    <table cellpadding="0" cellspacing="0" width="100%">
      @foreach(['include_1','include_2','include_3','include_4','include_5'] as $key)
      <tr><td style="padding:4px 0;font-size:14px;color:#374151;">&check; &nbsp; {{ __('email.partner_approved.' . $key) }}</td></tr>
      @endforeach
    </table>
  </div>
@endsection

@extends('emails._layout')

@section('title', __('email.verify_agency.subject'))

@section('preheader', __('email.verify_agency.body', ['name' => $user->name, 'tenant' => $tenant->name]))

@section('content')
  <h1 style="font-family:'DM Sans',sans-serif;font-size:24px;font-weight:700;color:#1a1d23;margin:0 0 16px;">{{ __('email.verify_agency.title') }}</h1>
  <p style="color:#677489;line-height:1.7;margin:0 0 20px;font-size:15px;">
    {{ __('email.verify_agency.body', ['name' => $user->name, 'tenant' => $tenant->name]) }}
  </p>

  <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;"><tr>
    <td style="background:#f8fafc;border:1px solid #e8eaf0;border-radius:12px;padding:18px 20px;">
      <p style="font-size:13px;font-weight:600;color:#1a1d23;margin:0 0 6px;">{{ __('email.verify_agency.next_step_title') }}</p>
      <p style="font-size:13px;color:#677489;margin:0;line-height:1.5;">{{ __('email.verify_agency.next_step_body') }}</p>
    </td>
  </tr></table>

  <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;"><tr><td align="center">
    <a href="{{ $verifyUrl }}" style="display:inline-block;background:#0085f3;color:#fff;text-decoration:none;font-family:'DM Sans',sans-serif;font-weight:600;font-size:15px;padding:14px 36px;border-radius:100px;">{{ __('email.verify_agency.cta') }}</a>
  </td></tr></table>

  <div style="background:#f8fafc;border:1px solid #e8eaf0;border-radius:12px;padding:16px;word-break:break-all;font-size:13px;color:#677489;">
    <strong style="color:#374151;">{{ __('email.verify_agency.cant_click') }}</strong><br/><br/>
    <a href="{{ $verifyUrl }}" style="color:#0085f3;text-decoration:none;">{{ $verifyUrl }}</a>
  </div>

  <hr style="border:none;border-top:1px solid #f0f2f7;margin:28px 0 20px;" />
  <p style="font-size:13px;color:#97A3B7;line-height:1.6;margin:0;">{{ __('email.verify_agency.ignore') }}</p>
@endsection

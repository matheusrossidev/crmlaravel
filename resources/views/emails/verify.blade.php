@extends('emails._layout')

@section('title', __('email.verify.subject'))

@section('preheader', __('email.verify.body'))

@section('content')
  <h1 style="font-family:'DM Sans',sans-serif;font-size:24px;font-weight:700;color:#1a1d23;margin:0 0 16px;line-height:1.3;">
    {{ __('email.verify.title') }}
  </h1>

  <p style="color:#677489;line-height:1.7;margin:0 0 28px;font-size:15px;">
    {{ __('email.verify.greeting', ['name' => $user->name]) }}<br/>
    {{ __('email.verify.body') }}
  </p>

  <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">
    <tr><td align="center">
      <a href="{{ $verifyUrl }}" style="display:inline-block;background:#0085f3;color:#fff;text-decoration:none;font-family:'DM Sans',sans-serif;font-weight:600;font-size:15px;padding:14px 36px;border-radius:100px;line-height:1;">
        {{ __('email.verify.cta') }}
      </a>
    </td></tr>
  </table>

  <p style="text-align:center;font-size:13px;color:#97A3B7;margin:0 0 28px;">
    {{ __('email.verify.expire', ['hours' => 48]) }}
  </p>

  <div style="background:#f8fafc;border:1px solid #e8eaf0;border-radius:12px;padding:16px;word-break:break-all;font-size:13px;color:#677489;">
    <strong style="color:#374151;">{{ __('email.verify.cant_click') }}</strong><br/><br/>
    <a href="{{ $verifyUrl }}" style="color:#0085f3;text-decoration:none;">{{ $verifyUrl }}</a>
  </div>

  <hr style="border:none;border-top:1px solid #f0f2f7;margin:28px 0 20px;" />

  <p style="font-size:13px;color:#97A3B7;line-height:1.6;margin:0;">
    {{ __('email.verify.ignore') }}
  </p>
@endsection

@extends('emails._layout')

@section('title', $template->subject ?? 'Syncro')

@section('preheader', __('email.reengagement.title', ['name' => $userName]))

@section('content')
  <h1 style="font-family:'DM Sans',sans-serif;font-size:22px;font-weight:700;color:#1a1d23;margin:0 0 20px;">
    {{ __('email.reengagement.title', ['name' => $userName]) }}
  </h1>

  <div style="color:#677489;line-height:1.8;font-size:15px;margin:0 0 28px;">
    {!! nl2br(e($renderedBody)) !!}
  </div>

  <table width="100%" cellpadding="0" cellspacing="0" style="margin:28px 0 16px;">
    <tr><td align="center">
      <a href="{{ $loginUrl }}" style="display:inline-block;background:#0085f3;color:#fff;text-decoration:none;font-family:'DM Sans',sans-serif;font-weight:600;font-size:15px;padding:14px 36px;border-radius:100px;">
        {{ __('email.reengagement.cta') }}
      </a>
    </td></tr>
  </table>
@endsection

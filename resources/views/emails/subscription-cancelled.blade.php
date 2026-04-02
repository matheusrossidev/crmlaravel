@extends('emails._layout')

@section('title', __('email.subscription_cancelled.subject'))

@section('preheader', __('email.subscription_cancelled.body', ['tenant' => $tenant->name]))

@section('content')
  <h1 style="font-family:'DM Sans',sans-serif;font-size:24px;font-weight:700;color:#1a1d23;margin:0 0 16px;">
    {{ __('email.subscription_cancelled.title', ['name' => $user->name]) }}
  </h1>
  <p style="color:#677489;line-height:1.7;margin:0 0 24px;font-size:15px;">
    {{ __('email.subscription_cancelled.body', ['tenant' => $tenant->name]) }}
    @if($plan)
      {{ __('email.subscription_cancelled.body_with_plan', ['plan' => $plan->display_name]) }}
    @endif
  </p>

  {{-- Reactivate note --}}
  <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;"><tr>
    <td style="background:#f8fafc;border:1px solid #e8eaf0;border-radius:12px;padding:20px;">
      <p style="font-size:14px;color:#677489;margin:0;line-height:1.6;">
        {{ __('email.subscription_cancelled.reactivate_note') }}
      </p>
    </td>
  </tr></table>

  <hr style="border:none;border-top:1px solid #f0f2f7;margin:0 0 24px;" />

  <table width="100%" cellpadding="0" cellspacing="0"><tr>
    <td style="background:#f8fafc;border-radius:12px;padding:20px;text-align:center;">
      <p style="font-size:14px;color:#677489;margin:0;">
        {{ __('email.subscription_cancelled.support_question') }}
        <a href="mailto:suporte@syncro.chat" style="color:#0085f3;font-weight:600;text-decoration:none;">suporte@syncro.chat</a>
      </p>
    </td>
  </tr></table>
@endsection

@extends('emails._layout')

@section('title', __('email.subscription_activated.subject'))

@section('preheader', __('email.subscription_activated.body', ['tenant' => $tenant->name]))

@section('content')
  <h1 style="font-family:'DM Sans',sans-serif;font-size:24px;font-weight:700;color:#1a1d23;margin:0 0 16px;">
    {{ __('email.subscription_activated.title', ['name' => $user->name]) }}
  </h1>
  <p style="color:#677489;line-height:1.7;margin:0 0 24px;font-size:15px;">
    {{ __('email.subscription_activated.body', ['tenant' => $tenant->name]) }}
    @if($plan)
      {{ __('email.subscription_activated.body_with_plan', ['plan' => $plan->display_name, 'price' => number_format($plan->price_monthly, 2, ',', '.')]) }}
    @endif
  </p>

  {{-- Welcome box --}}
  <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;"><tr>
    <td style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;padding:20px;text-align:center;">
      <p style="font-size:15px;color:#0085f3;font-weight:600;margin:0;">{{ __('email.subscription_activated.welcome_message') }}</p>
      <p style="font-size:13px;color:#677489;margin:8px 0 0;">{{ __('email.subscription_activated.billing_note') }}</p>
    </td>
  </tr></table>

  {{-- CTA --}}
  <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;"><tr><td align="center">
    <a href="{{ $dashboardUrl }}" style="display:inline-block;background:#0085f3;color:#fff;text-decoration:none;font-family:'DM Sans',sans-serif;font-weight:600;font-size:15px;padding:14px 36px;border-radius:100px;">
      {{ __('email.subscription_activated.cta') }}
    </a>
  </td></tr></table>
@endsection

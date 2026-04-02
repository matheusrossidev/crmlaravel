@extends('emails._layout')

@section('title', __('email.trial_ending.subject', ['days' => $daysLeft]))

@section('preheader', __('email.trial_ending.subtitle'))

@section('content')
  {{-- Urgency badge --}}
  <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;"><tr>
    <td style="background:#fffbeb;border:1px solid #fde68a;border-radius:12px;padding:20px;text-align:center;">
      <div style="font-size:22px;font-weight:800;color:#92400e;margin-bottom:4px;">
        @if($daysLeft === 1)
          {{ __('email.trial_ending.title_last_day') }}
        @else
          {{ __('email.trial_ending.title_days', ['days' => $daysLeft]) }}
        @endif
      </div>
      <div style="font-size:14px;color:#a16207;">{{ __('email.trial_ending.subtitle') }}</div>
    </td>
  </tr></table>

  <h1 style="font-family:'DM Sans',sans-serif;font-size:24px;font-weight:700;color:#1a1d23;margin:0 0 16px;">
    {{ __('email.trial_ending.greeting', ['name' => $user->name]) }}
  </h1>
  <p style="color:#677489;line-height:1.7;margin:0 0 24px;font-size:15px;">
    @if($daysLeft === 1)
      {{ __('email.trial_ending.body_last_day', ['tenant' => $tenant->name]) }}
    @else
      {{ __('email.trial_ending.body_days', ['tenant' => $tenant->name, 'days' => $daysLeft]) }}
    @endif
  </p>

  {{-- CTA --}}
  <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;"><tr><td align="center">
    <a href="{{ $checkoutUrl }}" style="display:inline-block;background:#0085f3;color:#fff;text-decoration:none;font-family:'DM Sans',sans-serif;font-weight:600;font-size:15px;padding:14px 36px;border-radius:100px;">
      {{ __('email.trial_ending.cta') }}
    </a>
  </td></tr></table>

  {{-- What you lose --}}
  <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;"><tr>
    <td style="background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:20px;">
      <p style="font-size:14px;color:#991b1b;font-weight:600;margin:0 0 8px;">{{ __('email.trial_ending.lose_title') }}</p>
      <table cellpadding="0" cellspacing="0" width="100%">
        @foreach(['lose_1','lose_2','lose_3','lose_4'] as $key)
        <tr><td style="padding:3px 0;font-size:13px;color:#991b1b;">&bull; {{ __('email.trial_ending.' . $key) }}</td></tr>
        @endforeach
      </table>
    </td>
  </tr></table>
@endsection

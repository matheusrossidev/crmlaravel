@extends('emails._layout')

@section('title', __('email.welcome.subject'))

@section('preheader', __('email.welcome.body', ['tenant' => $tenant->name]))

@section('content')
  <div style="border-radius:12px;overflow:hidden;height:220px;background:#f0f2f7;margin:-8px -8px 0;">
    <img src="{{ url('/images/mocks/kanban.png') }}" alt="Syncro CRM" style="width:100%;height:220px;display:block;object-fit:cover;object-position:top;" />
  </div>

  <div style="margin-top:32px;">
    <h1 style="font-family:'DM Sans',sans-serif;font-size:24px;font-weight:700;color:#1a1d23;margin:0 0 16px;">{{ __('email.welcome.title', ['name' => $user->name]) }}</h1>
    <p style="color:#677489;line-height:1.7;margin:0 0 28px;font-size:15px;">{{ __('email.welcome.body', ['tenant' => $tenant->name]) }}</p>

    @php
        $steps = [
            ['1', __('email.welcome.step1_title'), __('email.welcome.step1_desc')],
            ['2', __('email.welcome.step2_title'), __('email.welcome.step2_desc')],
            ['3', __('email.welcome.step3_title'), __('email.welcome.step3_desc')],
        ];
    @endphp

    @foreach($steps as $step)
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:10px;"><tr><td style="background:#f8fafc;border:1px solid #e8eaf0;border-radius:12px;padding:14px 16px;"><table cellpadding="0" cellspacing="0" width="100%"><tr>
      <td width="32" style="vertical-align:top;padding-right:12px;"><div style="background:#0085f3;color:#fff;font-weight:700;font-size:13px;width:26px;height:26px;border-radius:50%;text-align:center;line-height:26px;">{{ $step[0] }}</div></td>
      <td><strong style="font-size:14px;color:#1a1d23;">{{ $step[1] }}</strong><br/><span style="font-size:13px;color:#677489;">{{ $step[2] }}</span></td>
    </tr></table></td></tr></table>
    @endforeach

    <table width="100%" cellpadding="0" cellspacing="0" style="margin:28px 0 16px;"><tr><td align="center">
      <a href="{{ $loginUrl }}" style="display:inline-block;background:#0085f3;color:#fff;text-decoration:none;font-family:'DM Sans',sans-serif;font-weight:600;font-size:15px;padding:14px 36px;border-radius:100px;">{{ __('email.welcome.cta') }}</a>
    </td></tr></table>

    <p style="text-align:center;font-size:13px;color:#10B981;font-weight:600;margin:0;">{{ __('email.welcome.trial_badge') }}</p>
  </div>
@endsection

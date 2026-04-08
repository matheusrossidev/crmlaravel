{{--
    Painel Google Calendar — recebe (do parent):
        $google (OAuthConnection|null)
--}}
@php
    $hasCalendar    = $google && $google->status === 'active'
                      && (in_array('https://www.googleapis.com/auth/calendar.events', (array) ($google->scopes_json ?? []), true)
                       || in_array('https://www.googleapis.com/auth/calendar', (array) ($google->scopes_json ?? []), true));
    $needsReconnect = $google && $google->status === 'active' && !$hasCalendar;
@endphp

<div class="panel-header">
    <div>
        <h3 class="panel-title">{{ __('integrations.gcal_title') }}</h3>
        <p class="panel-subtitle">{{ __('integrations.gcal_subtitle') }}</p>
    </div>
    @if($hasCalendar)
        <span class="conn-badge conn-active">{{ __('integrations.gcal_connected') }}</span>
    @elseif($needsReconnect)
        <span class="conn-badge conn-expired">{{ __('integrations.gcal_reconnect_badge') }}</span>
    @else
        <span class="conn-badge conn-none">{{ __('integrations.gcal_disconnected') }}</span>
    @endif
</div>

<ul class="integration-features">
    <li>{{ __('integrations.gcal_feat_1') }}</li>
    <li>{{ __('integrations.gcal_feat_2') }}</li>
    <li>{{ __('integrations.gcal_feat_3') }}</li>
    <li>{{ __('integrations.gcal_feat_4') }}</li>
</ul>

@if($hasCalendar && $google)
<div class="conn-detail">
    <strong>{{ $google->platform_user_name ?? __('integrations.gcal_default_name') }}</strong><br>
    <span>{{ __('integrations.gcal_ai_hint') }}</span>
</div>
@elseif($needsReconnect)
<div class="conn-detail" style="color:#b45309;">
    <i class="bi bi-exclamation-triangle me-1"></i>
    {{ __('integrations.gcal_needs_reconnect') }}
</div>
@else
<div class="conn-detail" style="color:#9ca3af;">
    {{ __('integrations.gcal_not_connected') }}
</div>
@endif

<div class="integration-actions">
    @if($hasCalendar)
        <a href="{{ route('calendar.index') }}" class="btn-sync" style="text-decoration:none;">
            <i class="bi bi-calendar3"></i> {{ __('integrations.gcal_open') }}
        </a>
        <button class="btn-disconnect" onclick="disconnectPlatform('google', this)">
            <i class="bi bi-x-circle"></i> {{ __('integrations.gcal_disconnect') }}
        </button>
    @else
        <a href="{{ route('settings.integrations.google.redirect') }}" class="btn-connect">
            <i class="bi bi-google"></i> {{ $needsReconnect ? __('integrations.gcal_reconnect') : __('integrations.gcal_connect') }}
        </a>
    @endif
</div>

{{--
    Painel Instagram — recebe (do parent):
        $instagram (InstagramInstance|null)
--}}
<div class="panel-header">
    <div>
        <h3 class="panel-title">{{ __('integrations.ig_title') }}</h3>
        <p class="panel-subtitle">{{ __('integrations.ig_subtitle') }}</p>
    </div>
    @if($instagram && $instagram->status === 'connected')
        <span class="conn-badge conn-active">{{ __('integrations.ig_connected') }}</span>
    @elseif($instagram)
        <span class="conn-badge conn-expired">{{ __('integrations.ig_reconnect') }}</span>
    @else
        <span class="conn-badge conn-none">{{ __('integrations.ig_disconnected') }}</span>
    @endif
</div>

<ul class="integration-features">
    <li>{{ __('integrations.ig_feat_1') }}</li>
    <li>{{ __('integrations.ig_feat_2') }}</li>
    <li>{{ __('integrations.ig_feat_3') }}</li>
    <li>{{ __('integrations.ig_feat_4') }}</li>
</ul>

@if($instagram)
<div class="conn-detail">
    <strong>{{ $instagram->username ?? __('integrations.ig_default_name') }}</strong><br>
    <span>{{ __('integrations.ig_connected') }} {{ $instagram->updated_at?->diffForHumans() ?? '' }}</span>
</div>
@else
<div class="conn-detail" style="color:#9ca3af;">
    {{ __('integrations.ig_not_connected') }}
</div>
@endif

<div class="integration-actions">
    @if($instagram)
        <button class="btn-disconnect" onclick="disconnectInstagram(this)">
            <i class="bi bi-x-circle"></i> {{ __('integrations.ig_disconnect') }}
        </button>
    @else
        <a href="{{ route('settings.integrations.instagram.redirect') }}" class="btn-connect">
            <i class="bi bi-instagram"></i> {{ __('integrations.ig_connect') }}
        </a>
    @endif
</div>

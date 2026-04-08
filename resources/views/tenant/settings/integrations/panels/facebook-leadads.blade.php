{{--
    Painel Facebook Lead Ads — recebe (do parent):
        $facebookLeadAds (OAuthConnection|null)
        $fbLeadConnections (Collection<FacebookLeadFormConnection>)
--}}
<div class="panel-header">
    <div>
        <h3 class="panel-title">{{ __('integrations.fb_lead_title') }}</h3>
        <p class="panel-subtitle">{{ __('integrations.fb_lead_subtitle') }}</p>
    </div>
    @if($facebookLeadAds && $facebookLeadAds->status === 'active')
        <span class="conn-badge conn-active">{{ __('integrations.fb_lead_connected') }}</span>
    @else
        <span class="conn-badge conn-none">{{ __('integrations.fb_lead_disconnected') }}</span>
    @endif
</div>

<ul class="integration-features">
    <li>{{ __('integrations.fb_lead_feat_1') }}</li>
    <li>{{ __('integrations.fb_lead_feat_2') }}</li>
    <li>{{ __('integrations.fb_lead_feat_3') }}</li>
    <li>{{ __('integrations.fb_lead_feat_4') }}</li>
</ul>

@if($facebookLeadAds && $facebookLeadAds->status === 'active')
    <div class="conn-detail">
        <strong>{{ $facebookLeadAds->platform_user_name ?? 'Facebook' }}</strong><br>
        <span>{{ __('integrations.fb_lead_connected_ago', ['time' => $facebookLeadAds->updated_at?->diffForHumans() ?? '']) }}</span>
    </div>
    @if($fbLeadConnections->isNotEmpty())
    <div style="margin:10px 0;padding:10px 14px;background:#f0f4ff;border:1px solid #dbeafe;border-radius:8px;font-size:12.5px;">
        <strong style="color:#1a1d23;">{{ __('integrations.fb_lead_forms_count', ['count' => $fbLeadConnections->count()]) }}</strong>
        @foreach($fbLeadConnections as $fc)
        <div style="margin-top:6px;display:flex;align-items:center;gap:6px;color:#6b7280;">
            <span style="flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $fc->form_name }} &rarr; {{ $fc->pipeline?->name }} / {{ $fc->stage?->name }}</span>
            <button type="button" onclick="editFbLeadConnection({{ $fc->id }})" title="{{ __('integrations.fb_lead_edit') }}" style="background:#eff6ff;border:1px solid #bfdbfe;color:#1877F2;border-radius:6px;padding:3px 7px;cursor:pointer;font-size:11px;">
                <i class="bi bi-pencil"></i>
            </button>
            <button type="button" onclick="deleteFbLeadConnection({{ $fc->id }}, this)" title="{{ __('integrations.fb_lead_delete') }}" style="background:#fef2f2;border:1px solid #fecaca;color:#dc2626;border-radius:6px;padding:3px 7px;cursor:pointer;font-size:11px;">
                <i class="bi bi-trash"></i>
            </button>
        </div>
        @endforeach
    </div>
    @endif
    <div class="integration-actions" style="gap:8px;">
        <button class="btn-connect" onclick="openFbLeadDrawer()" style="background:#1877F2;">
            <i class="bi bi-gear"></i> {{ __('integrations.fb_lead_manage') }}
        </button>
        <button class="btn-disconnect" onclick="disconnectFbLeadAds(this)">
            <i class="bi bi-x-circle"></i> {{ __('integrations.fb_lead_disconnect') }}
        </button>
    </div>
@else
    <div class="conn-detail" style="color:#9ca3af;">{{ __('integrations.fb_lead_not_connected') }}</div>
    <div class="integration-actions">
        <a href="{{ route('settings.integrations.facebook-leadads.redirect') }}" class="btn-connect" style="background:#1877F2;">
            <i class="bi bi-facebook"></i> {{ __('integrations.fb_lead_connect') }}
        </a>
    </div>
@endif

{{--
    Painel WhatsApp WAHA — renderizado no lado direito do modal de detalhes da
    integração. Recebe (do parent) as variáveis:
        $whatsappInstances (Collection)
        $whatsappInstancesRemain (int|null) — null = ilimitado
        $maxWhatsappInstances (int)

    Os metadados (nome, ícone, descrição) ficam na sidebar do modal — aqui só
    o conteúdo funcional (status, lista de instâncias, botão adicionar).
--}}
@php
    $waConnected = $whatsappInstances->where('status', 'connected')->count();
@endphp

<div class="panel-header">
    <div>
        <h3 class="panel-title">{{ __('integrations.wa_title') }}</h3>
        <p class="panel-subtitle">{{ __('integrations.wa_subtitle') }}</p>
    </div>
    @if($waConnected > 0)
        <span class="conn-badge conn-active">{{ __('integrations.wa_active', ['count' => $waConnected]) }}</span>
    @else
        <span class="conn-badge conn-none">{{ __('integrations.wa_disconnected') }}</span>
    @endif
</div>

<ul class="integration-features">
    <li>{{ __('integrations.wa_feat_1') }}</li>
    <li>{{ __('integrations.wa_feat_2') }}</li>
    <li>{{ __('integrations.wa_feat_3') }}</li>
    <li>{{ __('integrations.wa_feat_4') }}</li>
</ul>

{{-- Instâncias conectadas --}}
<div id="waInstancesList" style="margin-bottom:16px;">
@foreach($whatsappInstances as $inst)
    <div class="wa-instance-item" data-instance-id="{{ $inst->id }}">
        <span class="wa-dot {{ $inst->status === 'connected' ? 'connected' : ($inst->status === 'qr' ? 'qr' : 'offline') }}"></span>
        <div class="wa-instance-detail">
            <div class="wa-label-wrap">
                <input type="text" class="wa-label-input" value="{{ $inst->label ?? '' }}"
                       placeholder="{{ __('integrations.wa_label_ph') }}"
                       data-instance-id="{{ $inst->id }}" onblur="saveWaLabel(this)">
                <i class="bi bi-pencil wa-edit-icon"></i>
                @if($inst->is_primary)
                    <span class="wa-primary-badge" title="{{ __('integrations.wa_primary_active') }}"><i class="bi bi-star-fill"></i> {{ __('integrations.wa_primary_label') }}</span>
                @endif
            </div>
            <span class="wa-instance-phone">{{ $inst->phone_number ?? $inst->display_name ?? $inst->session_name }}</span>
            @php
                $assignedUserIds = $inst->users->pluck('id')->all();
                $assignedNames   = $inst->users->pluck('name')->take(3)->all();
            @endphp
            <span class="wa-instance-users-summary">
                <i class="bi bi-people"></i>
                @if(count($assignedUserIds) === 0)
                    {{ __('integrations.wa_users_none') }}
                @else
                    {{ implode(', ', $assignedNames) }}@if(count($assignedUserIds) > 3) +{{ count($assignedUserIds) - 3 }}@endif
                @endif
            </span>
        </div>
        <div class="wa-instance-actions">
            @if($inst->status === 'connected')
                <button class="btn-icon-soft" onclick="toggleWaPrimary({{ $inst->id }}, this)" title="{{ __('integrations.wa_set_primary') }}">
                    <i class="bi {{ $inst->is_primary ? 'bi-star-fill text-warning' : 'bi-star' }}"></i>
                </button>
                <button class="btn-icon-soft" onclick="toggleWaUsers({{ $inst->id }})" title="{{ __('integrations.wa_users_btn') }}">
                    <i class="bi bi-people"></i>
                </button>
                <button class="btn-sync" style="padding:5px 12px;font-size:11.5px;" onclick="openImportModal({{ $inst->id }})" title="{{ __('integrations.wa_import') }}">
                    <i class="bi bi-cloud-download"></i> {{ __('integrations.wa_import') }}
                </button>
                <button class="btn-disconnect" style="padding:5px 12px;font-size:11.5px;" onclick="disconnectWhatsapp(this, {{ $inst->id }})">
                    <i class="bi bi-x-circle"></i> {{ __('integrations.wa_disconnect') }}
                </button>
            @elseif($inst->status === 'qr')
                <button class="btn-connect" style="padding:5px 12px;font-size:11.5px;" onclick="openWaModal({{ $inst->id }})">
                    <i class="bi bi-qr-code"></i> {{ __('integrations.wa_qr') }}
                </button>
                <button class="btn-disconnect" style="padding:5px 8px;font-size:11.5px;" onclick="deleteWhatsappInstance(this, {{ $inst->id }})">
                    <i class="bi bi-trash"></i>
                </button>
            @else
                <button class="btn-connect" style="padding:5px 12px;font-size:11.5px;" onclick="reconnectWhatsapp(this, {{ $inst->id }})">
                    <i class="bi bi-arrow-clockwise"></i> {{ __('integrations.wa_reconnect') }}
                </button>
                <button class="btn-disconnect" style="padding:5px 8px;font-size:11.5px;" onclick="deleteWhatsappInstance(this, {{ $inst->id }})">
                    <i class="bi bi-trash"></i>
                </button>
            @endif
        </div>

        {{-- Sub-painel de usuários (escondido por padrão, abre via toggleWaUsers) --}}
        <div class="wa-users-panel" id="waUsersPanel-{{ $inst->id }}" style="display:none;">
            <div class="wa-users-panel-header">
                <strong>{{ __('integrations.wa_users_panel_title') }}</strong>
                <small>{{ __('integrations.wa_users_panel_desc') }}</small>
            </div>
            <div class="wa-users-panel-list" data-instance-id="{{ $inst->id }}">
                @foreach($tenantUsers as $u)
                    <label class="wa-user-checkbox">
                        <input type="checkbox" value="{{ $u->id }}"
                               {{ in_array($u->id, $assignedUserIds, true) ? 'checked' : '' }}>
                        {{ $u->name }}
                    </label>
                @endforeach
            </div>
            <div class="wa-users-panel-actions">
                <button class="btn-connect" style="padding:6px 14px;font-size:12px;" onclick="saveWaUsers({{ $inst->id }})">
                    <i class="bi bi-check-lg"></i> {{ __('integrations.wa_users_save') }}
                </button>
            </div>
        </div>
    </div>
@endforeach
</div>

<style>
/* Permite que o sub-painel quebre pra baixo em vez de virar coluna lateral */
#waInstancesList .wa-instance-item { flex-wrap: wrap; position: relative; }
.wa-primary-badge { display:inline-flex; align-items:center; gap:3px; background:#fef3c7; color:#92400e; font-size:10px; font-weight:700; padding:2px 7px; border-radius:99px; margin-left:6px; }
.wa-instance-users-summary { display:flex; align-items:center; gap:4px; font-size:11.5px; color:#6b7280; margin-top:3px; }
.btn-icon-soft { background:#f3f4f6; border:1px solid #e5e7eb; border-radius:8px; padding:5px 9px; font-size:12px; cursor:pointer; transition:.15s; color:#374151; line-height:1; }
.btn-icon-soft:hover { background:#e5e7eb; }
.text-warning { color:#f59e0b !important; }
.wa-users-panel {
    flex-basis: 100%;
    order: 99;
    margin-top: 12px;
    padding: 16px;
    background: #fff;
    border: 1px solid #e8eaf0;
    border-radius: 10px;
    animation: waPanelFadeIn .15s ease-out;
}
@keyframes waPanelFadeIn { from { opacity:0; transform:translateY(-4px); } to { opacity:1; transform:translateY(0); } }
.wa-users-panel-header { margin-bottom: 12px; }
.wa-users-panel-header strong { display:block; font-size:13px; color:#1a1d23; margin-bottom:2px; }
.wa-users-panel-header small { font-size:11.5px; color:#6b7280; line-height:1.4; display:block; }
.wa-users-panel-list { display:grid; grid-template-columns:repeat(2, 1fr); gap:8px; max-height:220px; overflow-y:auto; padding:10px; background:#f9fafb; border:1px solid #e8eaf0; border-radius:8px; margin-bottom:12px; }
.wa-user-checkbox { display:flex; align-items:center; gap:8px; font-size:12.5px; color:#374151; cursor:pointer; padding:4px 6px; border-radius:6px; transition:background .12s; }
.wa-user-checkbox:hover { background:#fff; }
.wa-user-checkbox input { cursor:pointer; flex-shrink:0; }
.wa-users-panel-actions { display:flex; justify-content:flex-end; gap:8px; }
@media (max-width: 720px) {
    .wa-users-panel-list { grid-template-columns: 1fr; }
}
</style>

<div class="integration-actions">
    @if($whatsappInstancesRemain === null || $whatsappInstancesRemain > 0)
        <button class="btn-connect" id="btnAddWaNumber" onclick="startWhatsappConnect(this)">
            <i class="bi bi-plus-lg"></i> {{ __('integrations.wa_add_number') }}
        </button>
    @else
        <span class="btn-coming-soon">
            <i class="bi bi-lock"></i> {{ __('integrations.wa_limit', ['max' => $maxWhatsappInstances]) }}
        </span>
    @endif
</div>

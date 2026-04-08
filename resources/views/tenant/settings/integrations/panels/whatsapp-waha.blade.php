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
            </div>
            <span class="wa-instance-phone">{{ $inst->phone_number ?? $inst->display_name ?? $inst->session_name }}</span>
        </div>
        <div class="wa-instance-actions">
            @if($inst->status === 'connected')
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
    </div>
@endforeach
</div>

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

@extends('tenant.layouts.app')
@php
    $title = 'Integrações';
    $pageIcon = 'plugin';
@endphp

@push('styles')
<style>
    .integrations-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
    }

    @media (max-width: 1100px) {
        .integrations-grid { grid-template-columns: repeat(2, 1fr); }
    }

    @media (max-width: 700px) {
        .integrations-grid { grid-template-columns: 1fr; }
    }

    .integration-card {
        background: #fff;
        border-radius: 14px;
        border: 1px solid #e8eaf0;
        overflow: hidden;
    }

    .integration-header {
        padding: 20px 24px;
        display: flex;
        align-items: center;
        gap: 14px;
        border-bottom: 1px solid #f0f2f7;
    }

    .integration-logo {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        font-weight: 800;
        color: #fff;
        flex-shrink: 0;
    }

    .integration-logo.facebook   { background: #1877F2; }
    .integration-logo.google     { background: linear-gradient(135deg, #4285F4 0%, #EA4335 50%, #FBBC04 75%, #34A853 100%); }
    .integration-logo.whatsapp   { background: #25D366; }
    .integration-logo.instagram  { background: linear-gradient(135deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%); }

    .conn-soon { background: #f3f4f6; color: #9ca3af; }

    .integration-features {
        list-style: none;
        padding: 0;
        margin: 0 0 16px;
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .integration-features li {
        font-size: 12.5px;
        color: #4b5563;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .integration-features li::before {
        content: '✓';
        font-size: 11px;
        font-weight: 700;
        color: #9ca3af;
        flex-shrink: 0;
        width: 14px;
        text-align: center;
    }

    .btn-coming-soon {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 9px 18px;
        background: #f3f4f6;
        color: #9ca3af;
        border: 1.5px solid #e8eaf0;
        border-radius: 100px;
        font-size: 13px;
        font-weight: 600;
        cursor: not-allowed;
    }

    /* Modal QR */
    .wa-modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 10000;
        background: rgba(15,23,42,.55);
        align-items: center;
        justify-content: center;
    }
    .wa-modal-overlay.open { display: flex; }

    .wa-modal {
        background: #fff;
        border-radius: 16px;
        padding: 32px;
        width: 100%;
        max-width: 440px;
        margin: 16px;
        text-align: center;
        box-shadow: 0 24px 60px rgba(0,0,0,.18);
    }

    .wa-modal h4 {
        font-size: 18px;
        font-weight: 700;
        color: #1a1d23;
        margin: 0 0 6px;
    }

    .wa-modal p {
        font-size: 13px;
        color: #6b7280;
        margin: 0 0 20px;
    }

    .wa-steps {
        text-align: left;
        font-size: 13px;
        color: #374151;
        line-height: 1.7;
        margin-bottom: 22px;
        padding: 14px 16px;
        background: #f8fafc;
        border-radius: 10px;
        list-style: none;
        counter-reset: step;
    }

    .wa-steps li {
        counter-increment: step;
        display: flex;
        align-items: flex-start;
        gap: 10px;
        margin-bottom: 6px;
    }

    .wa-steps li:last-child { margin-bottom: 0; }

    .wa-steps li::before {
        content: counter(step);
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: #25D366;
        color: #fff;
        font-size: 11px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        margin-top: 2px;
    }

    .wa-qr-area {
        width: 220px;
        height: 220px;
        margin: 0 auto 16px;
        border: 1.5px solid #e5e7eb;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    .wa-qr-area img { width: 100%; height: 100%; object-fit: contain; }

    #waQrStatus {
        font-size: 13px;
        color: #6b7280;
        margin-bottom: 20px;
    }

    #waQrStatus.connected { color: #10B981; font-weight: 600; }
    #waQrStatus.error     { color: #EF4444; }

    .btn-wa-cancel {
        padding: 9px 20px;
        background: #fff;
        color: #374151;
        border: 1.5px solid #e8eaf0;
        border-radius: 100px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all .15s;
    }

    .btn-wa-cancel:hover { background: #f4f6fb; }

    .integration-title {
        flex: 1;
    }

    .integration-title h3 {
        font-size: 15px;
        font-weight: 700;
        color: #1a1d23;
        margin: 0 0 3px;
    }

    .integration-title p {
        font-size: 12px;
        color: #9ca3af;
        margin: 0;
    }

    .conn-badge {
        font-size: 11.5px;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 99px;
        white-space: nowrap;
    }

    .conn-active   { background: #d1fae5; color: #065f46; }
    .conn-expired  { background: #fef3c7; color: #92400e; }
    .conn-revoked,
    .conn-none     { background: #f3f4f6; color: #6b7280; }

    .integration-body {
        padding: 18px 24px;
    }

    .conn-detail {
        font-size: 13px;
        color: #374151;
        margin-bottom: 16px;
    }

    .conn-detail strong { color: #1a1d23; }
    .conn-detail span   { color: #9ca3af; font-size: 12px; }

    .integration-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .btn-connect {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 9px 18px;
        background: #0085f3;
        color: #fff;
        border: none;
        border-radius: 100px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        transition: background .15s;
    }

    .btn-connect:hover { background: #0070d1; color: #fff; }

    .btn-sync {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        background: #fff;
        color: #374151;
        border: 1.5px solid #e8eaf0;
        border-radius: 100px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all .15s;
    }

    .btn-sync:hover { background: #f0f4ff; border-color: #dbeafe; color: #0085f3; }

    .btn-disconnect {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        background: #fff;
        color: #EF4444;
        border: 1.5px solid #fecaca;
        border-radius: 100px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all .15s;
    }

    .btn-disconnect:hover { background: #fef2f2; }

    /* ── WhatsApp Instances (dentro do card) ───────────────────── */
    .wa-instance-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        background: #f8fafc;
        border-radius: 8px;
        margin-bottom: 8px;
    }
    .wa-instance-item:last-child { margin-bottom: 0; }

    .wa-dot {
        width: 9px;
        height: 9px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .wa-dot.connected {
        background: #10b981;
        animation: pulse-green 2s ease-in-out infinite;
    }
    .wa-dot.qr       { background: #f59e0b; animation: pulse-yellow 2s ease-in-out infinite; }
    .wa-dot.offline   { background: #d1d5db; }

    @keyframes pulse-green {
        0%, 100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, .5); }
        50%      { box-shadow: 0 0 0 5px rgba(16, 185, 129, 0); }
    }
    @keyframes pulse-yellow {
        0%, 100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, .5); }
        50%      { box-shadow: 0 0 0 5px rgba(245, 158, 11, 0); }
    }

    .wa-instance-detail {
        flex: 1;
        min-width: 0;
    }

    .wa-label-wrap {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .wa-label-input {
        border: 1px solid #e5e7eb;
        background: #fff;
        font-size: 12.5px;
        font-weight: 600;
        color: #1a1d23;
        padding: 3px 8px;
        border-radius: 6px;
        width: 100%;
        max-width: 160px;
        outline: none;
        transition: border-color .15s;
    }
    .wa-label-input:focus { border-color: #0085f3; }
    .wa-label-input::placeholder { color: #b0b7c3; font-weight: 400; font-style: italic; }

    .wa-edit-icon {
        color: #9ca3af;
        font-size: 11px;
        flex-shrink: 0;
    }

    .wa-instance-phone {
        font-size: 11.5px;
        color: #6b7280;
        display: block;
        margin-top: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .wa-instance-actions {
        display: flex;
        gap: 6px;
        flex-shrink: 0;
    }
</style>
@endpush

@section('content')
<div class="page-container">

    @include('tenant.settings._tabs')

    <div class="integrations-grid">

        {{-- ─── WhatsApp ─────────────────────────────────────────────────── --}}
        @if($enabledIntegrations['whatsapp'])
        <div class="integration-card">
            <div class="integration-header">
                <div class="integration-logo whatsapp">
                    <i class="bi bi-whatsapp" style="font-size:20px;"></i>
                </div>
                <div class="integration-title">
                    <h3>WhatsApp Business</h3>
                    <p>Receba e envie mensagens direto do CRM</p>
                </div>
                @php
                    $waConnected = $whatsappInstances->where('status', 'connected')->count();
                @endphp
                @if($waConnected > 0)
                    <span class="conn-badge conn-active">{{ $waConnected }} ativo(s)</span>
                @else
                    <span class="conn-badge conn-none">Desconectado</span>
                @endif
            </div>
            <div class="integration-body">
                <ul class="integration-features">
                    <li>Receba e envie mensagens direto do CRM</li>
                    <li>Chatbot com fluxo visual de automação</li>
                    <li>Agente de IA para atendimento automático</li>
                    <li>Transcrição de áudios via IA</li>
                </ul>

                {{-- Instâncias conectadas --}}
                <div id="waInstancesList" style="margin-bottom:16px;">
                @foreach($whatsappInstances as $inst)
                    <div class="wa-instance-item" data-instance-id="{{ $inst->id }}">
                        <span class="wa-dot {{ $inst->status === 'connected' ? 'connected' : ($inst->status === 'qr' ? 'qr' : 'offline') }}"></span>
                        <div class="wa-instance-detail">
                            <div class="wa-label-wrap">
                                <input type="text" class="wa-label-input" value="{{ $inst->label ?? '' }}"
                                       placeholder="Dar um nome..."
                                       data-instance-id="{{ $inst->id }}" onblur="saveWaLabel(this)">
                                <i class="bi bi-pencil wa-edit-icon"></i>
                            </div>
                            <span class="wa-instance-phone">{{ $inst->phone_number ?? $inst->display_name ?? $inst->session_name }}</span>
                        </div>
                        <div class="wa-instance-actions">
                            @if($inst->status === 'connected')
                                <button class="btn-sync" style="padding:5px 12px;font-size:11.5px;" onclick="openImportModal({{ $inst->id }})" title="Importar histórico de mensagens">
                                    <i class="bi bi-cloud-download"></i> Importar
                                </button>
                                <button class="btn-disconnect" style="padding:5px 12px;font-size:11.5px;" onclick="disconnectWhatsapp(this, {{ $inst->id }})">
                                    <i class="bi bi-x-circle"></i> Desconectar
                                </button>
                            @elseif($inst->status === 'qr')
                                <button class="btn-connect" style="padding:5px 12px;font-size:11.5px;" onclick="openWaModal({{ $inst->id }})">
                                    <i class="bi bi-qr-code"></i> QR
                                </button>
                                <button class="btn-disconnect" style="padding:5px 8px;font-size:11.5px;" onclick="deleteWhatsappInstance(this, {{ $inst->id }})">
                                    <i class="bi bi-trash"></i>
                                </button>
                            @else
                                <button class="btn-connect" style="padding:5px 12px;font-size:11.5px;" onclick="reconnectWhatsapp(this, {{ $inst->id }})">
                                    <i class="bi bi-arrow-clockwise"></i> Reconectar
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
                            <i class="bi bi-plus-lg"></i> Adicionar número
                        </button>
                    @else
                        <span class="btn-coming-soon">
                            <i class="bi bi-lock"></i> Limite de {{ $maxWhatsappInstances }} número(s)
                        </span>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- ─── Google Calendar ──────────────────────────────────────────── --}}
        @if($enabledIntegrations['google_calendar'])
        @php
            $calendarScope = 'https://www.googleapis.com/auth/calendar';
            $hasCalendar   = $google && $google->status === 'active'
                             && in_array($calendarScope, (array) ($google->scopes_json ?? []), true);
            $needsReconnect = $google && $google->status === 'active' && !$hasCalendar;
        @endphp
        <div class="integration-card">
            <div class="integration-header">
                <div class="integration-logo google">G</div>
                <div class="integration-title">
                    <h3>Google Calendar</h3>
                    <p>Agenda integrada — crie e gerencie eventos direto no CRM</p>
                </div>
                @if($hasCalendar)
                    <span class="conn-badge conn-active">Conectado</span>
                @elseif($needsReconnect)
                    <span class="conn-badge conn-expired">Reconectar</span>
                @else
                    <span class="conn-badge conn-none">Desconectado</span>
                @endif
            </div>
            <div class="integration-body">
                <ul class="integration-features">
                    <li>Visualize e gerencie seus eventos diretamente no CRM</li>
                    <li>Crie, edite e exclua eventos com arrastar e soltar</li>
                    <li>O Agente de IA pode marcar reuniões automaticamente</li>
                    <li>Sincronização bidirecional com o Google Calendar</li>
                </ul>

                @if($hasCalendar && $google)
                <div class="conn-detail">
                    <strong>{{ $google->platform_user_name ?? 'Conta conectada' }}</strong><br>
                    <span>Agenda habilitada para o Agente de IA</span>
                </div>
                @elseif($needsReconnect)
                <div class="conn-detail" style="color:#b45309;">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    Google conectado, mas sem permissão de agenda. Reconecte para habilitar.
                </div>
                @else
                <div class="conn-detail" style="color:#9ca3af;">
                    Conecte sua conta Google para acessar o Google Calendar.
                </div>
                @endif

                <div class="integration-actions">
                    @if($hasCalendar)
                        <a href="{{ route('calendar.index') }}" class="btn-sync" style="text-decoration:none;">
                            <i class="bi bi-calendar3"></i> Abrir Agenda
                        </a>
                        <button class="btn-disconnect" onclick="disconnectPlatform('google', this)">
                            <i class="bi bi-x-circle"></i> Desconectar
                        </button>
                    @else
                        <a href="{{ route('settings.integrations.google.redirect') }}" class="btn-connect">
                            <i class="bi bi-google"></i> {{ $needsReconnect ? 'Reconectar Google' : 'Conectar Google' }}
                        </a>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- ─── Instagram ──────────────────────────────────────────────── --}}
        @if($enabledIntegrations['instagram'])
        <div class="integration-card">
            <div class="integration-header">
                <div class="integration-logo instagram">
                    <i class="bi bi-instagram" style="font-size:20px;"></i>
                </div>
                <div class="integration-title">
                    <h3>Instagram</h3>
                    <p>Chat de mensagens diretas (DMs)</p>
                </div>
                @if($instagram && $instagram->status === 'connected')
                    <span class="conn-badge conn-active">Conectado</span>
                @elseif($instagram)
                    <span class="conn-badge conn-expired">Reconectar</span>
                @else
                    <span class="conn-badge conn-none">Desconectado</span>
                @endif
            </div>
            <div class="integration-body">
                <ul class="integration-features">
                    <li>Chat de mensagens diretas (DMs) no CRM</li>
                    <li>Agente de IA para atender DMs automaticamente</li>
                    <li>Criação automática de leads a partir de DMs</li>
                    <li>Histórico completo de conversas</li>
                </ul>
                @if($instagram)
                <div class="conn-detail">
                    <strong>{{ $instagram->username ?? 'Conta conectada' }}</strong><br>
                    <span>Conectado {{ $instagram->updated_at?->diffForHumans() ?? '' }}</span>
                </div>
                @else
                <div class="conn-detail" style="color:#9ca3af;">
                    Nenhuma conta conectada.
                </div>
                @endif
                <div class="integration-actions">
                    @if($instagram)
                        <button class="btn-disconnect" onclick="disconnectInstagram(this)">
                            <i class="bi bi-x-circle"></i> Desconectar
                        </button>
                    @else
                        <a href="{{ route('settings.integrations.instagram.redirect') }}" class="btn-connect">
                            <i class="bi bi-instagram"></i> Conectar Instagram
                        </a>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- ─── Facebook Ads ───────────────────────────────────────────────── --}}
        @if($enabledIntegrations['facebook_ads'])
        <div class="integration-card">
            <div class="integration-header">
                <div class="integration-logo facebook">f</div>
                <div class="integration-title">
                    <h3>Facebook Ads</h3>
                    <p>Sincroniza campanhas, métricas e gastos</p>
                </div>
                @if($facebook && $facebook->status === 'active')
                    <span class="conn-badge conn-active">Conectado</span>
                @elseif($facebook && $facebook->status === 'expired')
                    <span class="conn-badge conn-expired">Token expirado</span>
                @else
                    <span class="conn-badge conn-none">Desconectado</span>
                @endif
            </div>
            <div class="integration-body">
                <ul class="integration-features">
                    <li>Importa campanhas e conjuntos de anúncios</li>
                    <li>Sincroniza métricas de alcance, cliques e gastos</li>
                    <li>Atribui leads automaticamente às campanhas de origem</li>
                    <li>Atualização automática a cada hora</li>
                </ul>
                @if($facebook)
                <div class="conn-detail">
                    <strong>{{ $facebook->platform_user_name ?? 'Conta conectada' }}</strong><br>
                    <span>
                        Último sync:
                        {{ $facebook->last_sync_at ? $facebook->last_sync_at->diffForHumans() : 'Aguardando primeira sincronização...' }}
                    </span>
                </div>
                @else
                <div class="conn-detail" style="color:#9ca3af;">
                    Nenhuma conta conectada.
                </div>
                @endif
                <div class="integration-actions">
                    @if($facebook && in_array($facebook->status, ['active', 'expired']))
                        <button class="btn-sync" onclick="syncNow('facebook', this)">
                            <i class="bi bi-arrow-clockwise"></i> Sincronizar agora
                        </button>
                        <button class="btn-disconnect" onclick="disconnectPlatform('facebook', this)">
                            <i class="bi bi-x-circle"></i> Desconectar
                        </button>
                    @else
                        <a href="{{ route('settings.integrations.facebook.redirect') }}" class="btn-connect">
                            <i class="bi bi-facebook"></i> Conectar Facebook
                        </a>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- ─── Google Ads ─────────────────────────────────────────────────── --}}
        @if($enabledIntegrations['google_ads'])
        <div class="integration-card">
            <div class="integration-header">
                <div class="integration-logo google">G</div>
                <div class="integration-title">
                    <h3>Google Ads</h3>
                    <p>Sincroniza campanhas, métricas e gastos</p>
                </div>
                @if($google && $google->status === 'active')
                    <span class="conn-badge conn-active">Conectado</span>
                @elseif($google && $google->status === 'expired')
                    <span class="conn-badge conn-expired">Token expirado</span>
                @else
                    <span class="conn-badge conn-none">Desconectado</span>
                @endif
            </div>
            <div class="integration-body">
                <ul class="integration-features">
                    <li>Importa campanhas de Search, Display e Shopping</li>
                    <li>Sincroniza impressões, cliques e custo por conversão</li>
                    <li>Atribui leads automaticamente às campanhas de origem</li>
                    <li>Atualização automática a cada hora</li>
                </ul>
                @if($google)
                <div class="conn-detail">
                    <strong>{{ $google->platform_user_name ?? 'Conta conectada' }}</strong><br>
                    <span>
                        Último sync:
                        {{ $google->last_sync_at ? $google->last_sync_at->diffForHumans() : 'Aguardando primeira sincronização...' }}
                    </span>
                </div>
                @else
                <div class="conn-detail" style="color:#9ca3af;">
                    Nenhuma conta conectada.
                </div>
                @endif
                <div class="integration-actions">
                    @if($google && in_array($google->status, ['active', 'expired']))
                        <button class="btn-sync" onclick="syncNow('google', this)">
                            <i class="bi bi-arrow-clockwise"></i> Sincronizar agora
                        </button>
                        <button class="btn-disconnect" onclick="disconnectPlatform('google', this)">
                            <i class="bi bi-x-circle"></i> Desconectar
                        </button>
                    @else
                        <a href="{{ route('settings.integrations.google.redirect') }}" class="btn-connect">
                            <i class="bi bi-google"></i> Conectar Google
                        </a>
                    @endif
                </div>
            </div>
        </div>
        @endif

    </div>

</div>

{{-- ─── Modal QR WhatsApp ──────────────────────────────────────────── --}}
<div id="waQrModal" class="wa-modal-overlay">
    <div class="wa-modal">
        <h4><i class="bi bi-whatsapp" style="color:#25D366;margin-right:6px;"></i>Conectar WhatsApp</h4>
        <p>Escaneie o código QR com seu celular para vincular o número</p>

        <ol class="wa-steps">
            <li>Abra o <strong>WhatsApp</strong> no seu celular</li>
            <li>Toque em <strong>⋮ Mais opções</strong> (Android) ou <strong>Configurações</strong> (iPhone)</li>
            <li>Selecione <strong>Aparelhos conectados → Conectar um aparelho</strong></li>
            <li>Aponte a câmera para o QR Code abaixo</li>
        </ol>

        <div class="wa-qr-area" id="waQrArea">
            <i class="bi bi-arrow-clockwise spin" style="font-size:36px;color:#9ca3af;"></i>
        </div>

        <p id="waQrStatus">Aguardando QR Code...</p>

        <button class="btn-wa-cancel" onclick="closeWaModal()">Cancelar</button>
    </div>
</div>

{{-- ─── Modal Importar Histórico ──────────────────────────────────── --}}
<div id="waImportModal" class="wa-modal-overlay">
    <div class="wa-modal" style="max-width:420px;">
        {{-- Estado 1: Configuração --}}
        <div id="importConfigState">
            <h4 style="margin:0 0 4px;font-size:16px;font-weight:700;color:#1a1d23;">
                <i class="bi bi-cloud-download" style="color:#0085f3;margin-right:6px;"></i>Importar Mensagens
            </h4>
            <p style="font-size:13px;color:#6b7280;margin:0 0 18px;">Importa o histórico de conversas do WhatsApp para o CRM</p>

            <div style="text-align:left;margin-bottom:20px;">
                <label style="font-size:13px;font-weight:600;color:#1a1d23;display:block;margin-bottom:6px;">Período</label>
                <select id="importDaysSelect" style="width:100%;padding:10px 12px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:13px;color:#374151;background:#fff;outline:none;">
                    <option value="7">Últimos 7 dias</option>
                    <option value="15">Últimos 15 dias</option>
                    <option value="30" selected>Últimos 30 dias</option>
                </select>
                <p style="font-size:11.5px;color:#9ca3af;margin:8px 0 0;">A importação roda em segundo plano. Mensagens já existentes serão ignoradas.</p>
            </div>

            <div style="display:flex;gap:8px;justify-content:center;">
                <button class="btn-wa-cancel" onclick="closeImportModal()">Cancelar</button>
                <button class="btn-connect" id="btnStartImport" onclick="startImport()">
                    <i class="bi bi-cloud-download"></i> Importar
                </button>
            </div>
        </div>

        {{-- Estado 2: Progresso --}}
        <div id="importProgressState" style="display:none;">
            <h4 id="importProgressTitle" style="margin:0 0 4px;font-size:16px;font-weight:700;color:#1a1d23;">
                <i class="bi bi-arrow-clockwise spin" style="color:#0085f3;margin-right:6px;"></i>Importando Mensagens...
            </h4>
            <p id="importProgressSubtitle" style="font-size:13px;color:#6b7280;margin:0 0 18px;">Buscando conversas do WhatsApp...</p>

            {{-- Barra de progresso --}}
            <div style="background:#f3f4f6;border-radius:8px;height:10px;overflow:hidden;margin-bottom:18px;">
                <div id="importProgressBar" style="height:100%;background:#0085f3;border-radius:8px;transition:width .5s ease;width:0%;"></div>
            </div>

            {{-- Contadores --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px;">
                <div style="background:#f0f7ff;border-radius:10px;padding:12px 14px;text-align:center;">
                    <div style="font-size:22px;font-weight:700;color:#0085f3;" id="importCountChats">0</div>
                    <div style="font-size:11px;color:#6b7280;font-weight:600;">Conversas</div>
                </div>
                <div style="background:#ecfdf5;border-radius:10px;padding:12px 14px;text-align:center;">
                    <div style="font-size:22px;font-weight:700;color:#059669;" id="importCountMessages">0</div>
                    <div style="font-size:11px;color:#6b7280;font-weight:600;">Mensagens</div>
                </div>
                <div style="background:#fef3c7;border-radius:10px;padding:12px 14px;text-align:center;">
                    <div style="font-size:22px;font-weight:700;color:#d97706;" id="importCountSkipped">0</div>
                    <div style="font-size:11px;color:#6b7280;font-weight:600;">Duplicadas</div>
                </div>
                <div style="background:#f3f4f6;border-radius:10px;padding:12px 14px;text-align:center;">
                    <div style="font-size:22px;font-weight:700;color:#374151;" id="importCountTime">0:00</div>
                    <div style="font-size:11px;color:#6b7280;font-weight:600;">Tempo</div>
                </div>
            </div>

            {{-- Chat atual --}}
            <div id="importCurrentChat" style="font-size:12px;color:#9ca3af;text-align:center;margin-bottom:16px;min-height:18px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"></div>

            {{-- Botão fechar --}}
            <div style="text-align:center;">
                <button class="btn-wa-cancel" id="importCloseBtn" onclick="closeImportModal()">Fechar</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
const SYNC_URL            = @json(route('settings.integrations.sync',       ['platform' => '__P__']));
const DISCONNECT_URL      = @json(route('settings.integrations.disconnect', ['platform' => '__P__']));
const WA_CONNECT_URL      = @json(route('settings.integrations.whatsapp.connect'));
const WA_BASE_URL         = @json(rtrim(route('settings.integrations.whatsapp.connect'), '/connect'));
const IG_DISCONNECT_URL   = @json(route('settings.integrations.instagram.disconnect'));

let waQrPollInterval = null;
let waQrNullCount    = 0;
let waCurrentInstanceId = null;

// ── WhatsApp ──────────────────────────────────────────────────────────────────

async function startWhatsappConnect(btn) {
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-arrow-clockwise spin"></i> Conectando...';

    try {
        const res  = await fetch(WA_CONNECT_URL, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ label: '' }),
        });
        const data = await res.json();

        if (data.success) {
            waCurrentInstanceId = data.instance_id;
            openWaModal(data.instance_id);
        } else {
            toastr.error(data.message || 'Erro ao iniciar conexão.');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-plus-lg"></i> Adicionar número';
        }
    } catch (e) {
        toastr.error('Erro de conexão.');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-plus-lg"></i> Adicionar número';
    }
}

function openWaModal(instanceId) {
    waCurrentInstanceId = instanceId;
    document.getElementById('waQrModal').classList.add('open');
    document.getElementById('waQrStatus').textContent = 'Aguardando QR Code...';
    document.getElementById('waQrStatus').className = '';
    document.getElementById('waQrArea').innerHTML = '<i class="bi bi-arrow-clockwise spin" style="font-size:36px;color:#9ca3af;"></i>';

    waQrNullCount = 0;
    clearInterval(waQrPollInterval);
    pollWaQr();
    waQrPollInterval = setInterval(pollWaQr, 3000);
}

function closeWaModal() {
    clearInterval(waQrPollInterval);
    document.getElementById('waQrModal').classList.remove('open');
}

async function pollWaQr() {
    if (!waCurrentInstanceId) return;
    try {
        const res  = await fetch(`${WA_BASE_URL}/${waCurrentInstanceId}/qr`, {
            headers: { 'Accept': 'application/json' }
        });
        const data = await res.json();

        if (data.status === 'connected') {
            clearInterval(waQrPollInterval);
            document.getElementById('waQrArea').innerHTML = '<i class="bi bi-check-circle-fill" style="font-size:64px;color:#25D366;"></i>';
            const st = document.getElementById('waQrStatus');
            st.textContent = 'WhatsApp conectado com sucesso!';
            st.className = 'connected';
            setTimeout(() => location.reload(), 1800);
        } else if (data.qr_base64) {
            waQrNullCount = 0;
            document.getElementById('waQrArea').innerHTML = `<img src="data:image/png;base64,${data.qr_base64}" alt="QR Code">`;
            document.getElementById('waQrStatus').textContent = 'Escaneie o código com seu celular';
        } else if (data.status === 'disconnected' || ++waQrNullCount >= 5) {
            clearInterval(waQrPollInterval);
            document.getElementById('waQrArea').innerHTML =
                '<i class="bi bi-x-circle-fill" style="font-size:48px;color:#ef4444;margin-bottom:12px;display:block;"></i>';
            const st = document.getElementById('waQrStatus');
            st.textContent = 'QR Code expirado ou sessão falhou.';
            if (!document.getElementById('btnWaRetry')) {
                st.insertAdjacentHTML('afterend',
                    '<button id="btnWaRetry" style="margin-top:12px;padding:8px 20px;background:#25D366;color:#fff;border:none;border-radius:8px;cursor:pointer;font-weight:600;">'
                    + '<i class="bi bi-arrow-clockwise"></i> Tentar novamente</button>');
                document.getElementById('btnWaRetry').addEventListener('click', async () => {
                    document.getElementById('btnWaRetry').remove();
                    document.getElementById('waQrArea').innerHTML = '<i class="bi bi-arrow-clockwise spin" style="font-size:36px;color:#9ca3af;"></i>';
                    document.getElementById('waQrStatus').textContent = 'Aguardando QR Code...';
                    waQrNullCount = 0;
                    openWaModal(waCurrentInstanceId);
                });
            }
        }
    } catch (e) {
        // Silenciar erros de polling
    }
}

async function reconnectWhatsapp(btn, instanceId) {
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-arrow-clockwise spin"></i>';
    openWaModal(instanceId);
}

async function disconnectWhatsapp(btn, instanceId) {
    confirmAction({
        title: 'Desconectar WhatsApp',
        message: 'Tem certeza que deseja desconectar este número?',
        confirmText: 'Desconectar',
        onConfirm: async () => {
            btn.disabled = true;
            try {
                const res  = await fetch(`${WA_BASE_URL}/${instanceId}/disconnect`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();
                if (data.success) {
                    toastr.success('WhatsApp desconectado.');
                    setTimeout(() => location.reload(), 1200);
                } else {
                    toastr.error('Erro ao desconectar.');
                    btn.disabled = false;
                }
            } catch (e) {
                toastr.error('Erro de conexão.');
                btn.disabled = false;
            }
        },
    });
}

async function deleteWhatsappInstance(btn, instanceId) {
    confirmAction({
        title: 'Remover número',
        message: 'Tem certeza que deseja remover este número? As conversas serão mantidas.',
        confirmText: 'Remover',
        onConfirm: async () => {
            btn.disabled = true;
            try {
                const res  = await fetch(`${WA_BASE_URL}/${instanceId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();
                if (data.success) {
                    toastr.success('Número removido.');
                    setTimeout(() => location.reload(), 1200);
                } else {
                    toastr.error('Erro ao remover.');
                    btn.disabled = false;
                }
            } catch (e) {
                toastr.error('Erro de conexão.');
                btn.disabled = false;
            }
        },
    });
}

async function saveWaLabel(input) {
    const instanceId = input.dataset.instanceId;
    const label = input.value.trim();
    if (!label) return;

    try {
        await fetch(`${WA_BASE_URL}/${instanceId}`, {
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ label }),
        });
    } catch (e) {
        // silenciar
    }
}

// Fechar modal clicando no overlay
document.getElementById('waQrModal').addEventListener('click', function(e) {
    if (e.target === this) closeWaModal();
});

// ── Import histórico ────────────────────────────────────────────────────────

let waImportInstanceId = null;
let importPollTimer    = null;
let importStartedTime  = null;
let importTimeTimer    = null;

function openImportModal(instanceId) {
    waImportInstanceId = instanceId;
    document.getElementById('importDaysSelect').value = '30';
    document.getElementById('importConfigState').style.display = '';
    document.getElementById('importProgressState').style.display = 'none';
    document.getElementById('waImportModal').classList.add('open');

    // Checar se já tem import rodando
    checkExistingImport(instanceId);
}

function closeImportModal() {
    document.getElementById('waImportModal').classList.remove('open');
    if (importPollTimer) { clearInterval(importPollTimer); importPollTimer = null; }
    if (importTimeTimer) { clearInterval(importTimeTimer); importTimeTimer = null; }
    waImportInstanceId = null;
}

document.getElementById('waImportModal').addEventListener('click', function(e) {
    if (e.target === this) closeImportModal();
});

async function checkExistingImport(instanceId) {
    try {
        const res  = await fetch(`${WA_BASE_URL}/${instanceId}/import/progress`, {
            headers: { 'Accept': 'application/json' },
        });
        const data = await res.json();
        if (data.status === 'running') {
            showProgressState();
            updateProgressUI(data);
            startProgressPolling(instanceId);
        }
    } catch (e) {}
}

async function startImport() {
    if (!waImportInstanceId) return;

    const days = document.getElementById('importDaysSelect').value;
    const btn  = document.getElementById('btnStartImport');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-arrow-clockwise spin"></i> Iniciando...';

    try {
        const res  = await fetch(`${WA_BASE_URL}/${waImportInstanceId}/import`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ days: parseInt(days) }),
        });
        const data = await res.json();

        if (data.success) {
            showProgressState();
            startProgressPolling(waImportInstanceId);
        } else {
            toastr.error(data.message || 'Erro ao iniciar importação.');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-cloud-download"></i> Importar';
        }
    } catch (e) {
        toastr.error('Erro de conexão.');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-cloud-download"></i> Importar';
    }
}

function showProgressState() {
    document.getElementById('importConfigState').style.display = 'none';
    document.getElementById('importProgressState').style.display = '';
    document.getElementById('importProgressBar').style.width = '0%';
    document.getElementById('importCountChats').textContent = '0';
    document.getElementById('importCountMessages').textContent = '0';
    document.getElementById('importCountSkipped').textContent = '0';
    document.getElementById('importCountTime').textContent = '0:00';
    document.getElementById('importCurrentChat').textContent = '';
    document.getElementById('importProgressTitle').innerHTML = '<i class="bi bi-arrow-clockwise spin" style="color:#0085f3;margin-right:6px;"></i>Importando Mensagens...';
    document.getElementById('importProgressSubtitle').textContent = 'Buscando conversas do WhatsApp...';

    importStartedTime = Date.now();
    if (importTimeTimer) clearInterval(importTimeTimer);
    importTimeTimer = setInterval(() => {
        const elapsed = Math.floor((Date.now() - importStartedTime) / 1000);
        const min = Math.floor(elapsed / 60);
        const sec = String(elapsed % 60).padStart(2, '0');
        document.getElementById('importCountTime').textContent = `${min}:${sec}`;
    }, 1000);
}

function startProgressPolling(instanceId) {
    if (importPollTimer) clearInterval(importPollTimer);

    importPollTimer = setInterval(async () => {
        try {
            const res  = await fetch(`${WA_BASE_URL}/${instanceId}/import/progress`, {
                headers: { 'Accept': 'application/json' },
            });
            const data = await res.json();
            updateProgressUI(data);

            if (data.status === 'completed' || data.status === 'failed' || data.status === 'idle') {
                clearInterval(importPollTimer);
                importPollTimer = null;
                if (importTimeTimer) { clearInterval(importTimeTimer); importTimeTimer = null; }
            }
        } catch (e) {}
    }, 2000);
}

function updateProgressUI(data) {
    if (!data || data.status === 'idle') return;

    const processed = data.processed || 0;
    const total     = data.total || 0;
    const messages  = data.messages || 0;
    const skipped   = data.skipped || 0;
    const current   = data.current || '';
    const pct       = total > 0 ? Math.min(Math.round((processed / total) * 100), 100) : 0;

    document.getElementById('importProgressBar').style.width = (total > 0 ? pct : 30) + '%';
    document.getElementById('importCountChats').textContent = total > 0 ? `${processed}/${total}` : processed;
    document.getElementById('importCountMessages').textContent = messages.toLocaleString('pt-BR');
    document.getElementById('importCountSkipped').textContent = skipped.toLocaleString('pt-BR');

    if (data.started_at && importStartedTime) {
        // Sincronizar com o tempo real do servidor
        const serverStart = new Date(data.started_at).getTime();
        if (Math.abs(serverStart - importStartedTime) > 5000) {
            importStartedTime = serverStart;
        }
    }

    if (current) {
        document.getElementById('importCurrentChat').textContent = `Processando: ${current}`;
        document.getElementById('importProgressSubtitle').textContent = `Processando conversas...`;
    }

    if (data.status === 'completed') {
        document.getElementById('importProgressTitle').innerHTML = '<i class="bi bi-check-circle-fill" style="color:#059669;margin-right:6px;"></i>Importação Concluída';
        document.getElementById('importProgressSubtitle').textContent = `${processed} conversas processadas com sucesso.`;
        document.getElementById('importProgressBar').style.width = '100%';
        document.getElementById('importProgressBar').style.background = '#059669';
        document.getElementById('importCurrentChat').textContent = '';
        document.getElementById('importCloseBtn').textContent = 'Fechar';
    } else if (data.status === 'failed') {
        document.getElementById('importProgressTitle').innerHTML = '<i class="bi bi-exclamation-triangle-fill" style="color:#dc2626;margin-right:6px;"></i>Erro na Importação';
        document.getElementById('importProgressSubtitle').textContent = data.error || 'Ocorreu um erro durante a importação.';
        document.getElementById('importProgressBar').style.background = '#dc2626';
        document.getElementById('importCurrentChat').textContent = '';
    }
}

async function syncNow(platform, btn) {
    const url = SYNC_URL.replace('__P__', platform);
    btn.disabled = true;
    const icon = btn.querySelector('i');
    icon.className = 'bi bi-arrow-clockwise spin';

    try {
        const res  = await fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
        });
        const data = await res.json();
        if (data.success) {
            toastr.success('Sincronização iniciada em segundo plano.');
        } else {
            toastr.error(data.message || 'Erro ao sincronizar.');
        }
    } catch (e) {
        toastr.error('Erro de conexão.');
    } finally {
        btn.disabled = false;
        icon.className = 'bi bi-arrow-clockwise';
    }
}

function disconnectPlatform(platform, btn) {
    confirmAction({
        title: 'Desconectar integração',
        message: 'Tem certeza que deseja desconectar esta integração?',
        confirmText: 'Desconectar',
        onConfirm: async () => {
            const url = DISCONNECT_URL.replace('__P__', platform);
            btn.disabled = true;
            try {
                const res  = await fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();
                if (data.success) {
                    toastr.success('Integração desconectada.');
                    setTimeout(() => location.reload(), 1200);
                } else {
                    toastr.error('Erro ao desconectar.');
                    btn.disabled = false;
                }
            } catch (e) {
                toastr.error('Erro de conexão.');
                btn.disabled = false;
            }
        },
    });
}

async function disconnectInstagram(btn) {
    confirmAction({
        title: 'Desconectar Instagram',
        message: 'Tem certeza que deseja desconectar a conta do Instagram?',
        confirmText: 'Desconectar',
        onConfirm: async () => {
            btn.disabled = true;
            try {
                const res  = await fetch(IG_DISCONNECT_URL, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();
                if (data.success) {
                    toastr.success('Instagram desconectado.');
                    setTimeout(() => location.reload(), 1200);
                } else {
                    toastr.error('Erro ao desconectar.');
                    btn.disabled = false;
                }
            } catch (e) {
                toastr.error('Erro de conexão.');
                btn.disabled = false;
            }
        },
    });
}
</script>
<style>
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
.spin { animation: spin .8s linear infinite; display: inline-block; }
</style>
@endpush

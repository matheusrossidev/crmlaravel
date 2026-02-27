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
        border-radius: 9px;
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
        border-radius: 9px;
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
        background: #3B82F6;
        color: #fff;
        border: none;
        border-radius: 9px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        transition: background .15s;
    }

    .btn-connect:hover { background: #2563EB; color: #fff; }

    .btn-sync {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        background: #fff;
        color: #374151;
        border: 1.5px solid #e8eaf0;
        border-radius: 9px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all .15s;
    }

    .btn-sync:hover { background: #f0f4ff; border-color: #dbeafe; color: #3B82F6; }

    .btn-disconnect {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        background: #fff;
        color: #EF4444;
        border: 1.5px solid #fecaca;
        border-radius: 9px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all .15s;
    }

    .btn-disconnect:hover { background: #fef2f2; }
</style>
@endpush

@section('content')
<div class="page-container">

    <div class="integrations-grid">

        {{-- ─── Facebook Ads ─────────────────────────────────────────────── --}}
        @if(auth()->user()->isSuperAdmin())
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
                        {{ $facebook->last_sync_at ? $facebook->last_sync_at->diffForHumans() : 'Nunca' }}
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
        @else
        <div class="integration-card" style="opacity:.55;pointer-events:none;">
            <div class="integration-header">
                <div class="integration-logo facebook">f</div>
                <div class="integration-title">
                    <h3>Facebook Ads</h3>
                    <p>Sincroniza campanhas, métricas e gastos</p>
                </div>
                <span class="conn-badge" style="background:#f3f4f6;color:#9ca3af;">Em breve</span>
            </div>
            <div class="integration-body">
                <ul class="integration-features">
                    <li>Importa campanhas e conjuntos de anúncios</li>
                    <li>Sincroniza métricas de alcance, cliques e gastos</li>
                    <li>Atribui leads automaticamente às campanhas de origem</li>
                    <li>Atualização automática a cada hora</li>
                </ul>
                <div class="integration-actions">
                    <button class="btn-connect" disabled style="cursor:not-allowed;">
                        <i class="bi bi-facebook"></i> Em breve
                    </button>
                </div>
            </div>
        </div>
        @endif

        {{-- ─── Google Ads ───────────────────────────────────────────────── --}}
        @if(auth()->user()->isSuperAdmin())
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
                        {{ $google->last_sync_at ? $google->last_sync_at->diffForHumans() : 'Nunca' }}
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
        @else
        <div class="integration-card" style="opacity:.55;pointer-events:none;">
            <div class="integration-header">
                <div class="integration-logo google">G</div>
                <div class="integration-title">
                    <h3>Google Ads</h3>
                    <p>Sincroniza campanhas, métricas e gastos</p>
                </div>
                <span class="conn-badge" style="background:#f3f4f6;color:#9ca3af;">Em breve</span>
            </div>
            <div class="integration-body">
                <ul class="integration-features">
                    <li>Importa campanhas de Search, Display e Shopping</li>
                    <li>Sincroniza impressões, cliques e custo por conversão</li>
                    <li>Atribui leads automaticamente às campanhas de origem</li>
                    <li>Atualização automática a cada hora</li>
                </ul>
                <div class="integration-actions">
                    <button class="btn-connect" disabled style="cursor:not-allowed;">
                        <i class="bi bi-google"></i> Em breve
                    </button>
                </div>
            </div>
        </div>
        @endif

        {{-- ─── Google Calendar ──────────────────────────────────────────── --}}
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

        {{-- ─── WhatsApp ─────────────────────────────────────────────────── --}}
        <div class="integration-card">
            <div class="integration-header">
                <div class="integration-logo whatsapp">
                    <i class="bi bi-whatsapp" style="font-size:20px;"></i>
                </div>
                <div class="integration-title">
                    <h3>WhatsApp Business</h3>
                    <p>Receba e envie mensagens direto do CRM</p>
                </div>
                @if($whatsapp && $whatsapp->status === 'connected')
                    <span class="conn-badge conn-active">Conectado</span>
                @elseif($whatsapp && $whatsapp->status === 'qr')
                    <span class="conn-badge conn-expired">Aguardando QR</span>
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

                @if($whatsapp && $whatsapp->status === 'connected')
                <div class="conn-detail">
                    <strong>{{ $whatsapp->display_name ?? $whatsapp->phone_number ?? 'Número conectado' }}</strong><br>
                    <span>Conectado {{ $whatsapp->updated_at?->diffForHumans() ?? '' }}</span>
                </div>
                @else
                <div class="conn-detail" style="color:#9ca3af;">
                    Nenhum número conectado.
                </div>
                @endif

                <div class="integration-actions">
                    @if($whatsapp && $whatsapp->status === 'connected')
                        <button class="btn-disconnect" onclick="disconnectWhatsapp(this)">
                            <i class="bi bi-x-circle"></i> Desconectar
                        </button>
                        <button id="btnImportHistory" class="btn-connect" style="background:#6366f1;" onclick="importWhatsappHistory(this)">
                            <i class="bi bi-clock-history"></i> Importar histórico
                        </button>
                    @elseif($whatsapp && $whatsapp->status === 'qr')
                        <button class="btn-connect" onclick="openWaModal(true)">
                            <i class="bi bi-qr-code"></i> Ver QR Code
                        </button>
                        <button class="btn-disconnect" onclick="disconnectWhatsapp(this)">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </button>
                    @else
                        <button class="btn-connect" onclick="startWhatsappConnect(this)">
                            <i class="bi bi-whatsapp"></i> Conectar WhatsApp
                        </button>
                    @endif
                </div>
            </div>
        </div>

        {{-- ─── Instagram ────────────────────────────────────────────────── --}}
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

                @if($instagram && $instagram->status === 'connected')
                <div class="conn-detail">
                    @if($instagram->profile_picture_url)
                        <img src="{{ $instagram->profile_picture_url }}" alt="" style="width:28px;height:28px;border-radius:50%;margin-right:8px;vertical-align:middle;">
                    @endif
                    <strong>{{ $instagram->username ?? 'Conta conectada' }}</strong><br>
                    <span>Conectado {{ $instagram->updated_at?->diffForHumans() ?? '' }}</span>
                </div>
                @else
                <div class="conn-detail" style="color:#9ca3af;">
                    Nenhuma conta conectada.
                </div>
                @endif

                <div class="integration-actions">
                    @if($instagram && $instagram->status === 'connected')
                        <button class="btn-disconnect" onclick="disconnectInstagram(this)">
                            <i class="bi bi-x-circle"></i> Desconectar
                        </button>
                    @else
                        <a href="{{ route('settings.integrations.instagram.redirect') }}" class="btn-connect" style="background:#dc2743;">
                            <i class="bi bi-instagram"></i> Conectar Instagram
                        </a>
                    @endif
                </div>
            </div>
        </div>

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

@endsection

@push('scripts')
<script>
const SYNC_URL            = @json(route('settings.integrations.sync',       ['platform' => '__P__']));
const DISCONNECT_URL      = @json(route('settings.integrations.disconnect', ['platform' => '__P__']));
const WA_CONNECT_URL      = @json(route('settings.integrations.whatsapp.connect'));
const WA_QR_URL           = @json(route('settings.integrations.whatsapp.qr'));
const WA_DISCONNECT_URL   = @json(route('settings.integrations.whatsapp.disconnect'));
const WA_IMPORT_URL       = @json(route('settings.integrations.whatsapp.import'));
const IG_DISCONNECT_URL   = @json(route('settings.integrations.instagram.disconnect'));

let waQrPollInterval = null;

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
            },
        });
        const data = await res.json();

        if (data.success) {
            openWaModal(false);
        } else {
            toastr.error('Erro ao iniciar conexão. Tente novamente.');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-whatsapp"></i> Conectar WhatsApp';
        }
    } catch (e) {
        toastr.error('Erro de conexão.');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-whatsapp"></i> Conectar WhatsApp';
    }
}

function openWaModal(skipCreate) {
    document.getElementById('waQrModal').classList.add('open');
    document.getElementById('waQrStatus').textContent = 'Aguardando QR Code...';
    document.getElementById('waQrStatus').className = '';
    document.getElementById('waQrArea').innerHTML = '<i class="bi bi-arrow-clockwise spin" style="font-size:36px;color:#9ca3af;"></i>';

    // Iniciar polling do QR
    clearInterval(waQrPollInterval);
    pollWaQr();
    waQrPollInterval = setInterval(pollWaQr, 3000);
}

function closeWaModal() {
    clearInterval(waQrPollInterval);
    document.getElementById('waQrModal').classList.remove('open');
}

async function pollWaQr() {
    try {
        const res  = await fetch(WA_QR_URL, {
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
            document.getElementById('waQrArea').innerHTML = `<img src="data:image/png;base64,${data.qr_base64}" alt="QR Code">`;
            document.getElementById('waQrStatus').textContent = 'Escaneie o código com seu celular';
        }
    } catch (e) {
        // Silenciar erros de polling
    }
}

async function disconnectWhatsapp(btn) {
    confirmAction({
        title: 'Desconectar WhatsApp',
        message: 'Tem certeza que deseja desconectar o número de WhatsApp?',
        confirmText: 'Desconectar',
        onConfirm: async () => {
            btn.disabled = true;
            try {
                const res  = await fetch(WA_DISCONNECT_URL, {
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

async function importWhatsappHistory(btn) {
    if (! confirm('Isso irá importar todas as conversas e mensagens anteriores do WhatsApp. Pode levar alguns minutos. Continuar?')) {
        return;
    }
    btn.disabled = true;
    const original = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-arrow-clockwise spin"></i> Importando...';

    try {
        const res  = await fetch(WA_IMPORT_URL, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
        });
        const data = await res.json();
        if (data.success) {
            toastr.success(
                `Importação concluída! ${data.imported_chats} conversa(s) e ${data.imported_messages} mensagem(ns) importadas.`,
                'Histórico importado',
                { timeOut: 6000 }
            );
        } else {
            toastr.error(data.message || 'Erro ao importar histórico.');
        }
    } catch (e) {
        toastr.error('Erro de conexão ao importar histórico.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = original;
    }
}

// Fechar modal clicando no overlay
document.getElementById('waQrModal').addEventListener('click', function(e) {
    if (e.target === this) closeWaModal();
});

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

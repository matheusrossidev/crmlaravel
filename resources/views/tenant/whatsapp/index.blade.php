@extends('tenant.layouts.app')
@php
    $title    = 'Chats';
    $pageIcon = 'chat-dots';
@endphp

@push('styles')
<style>
    /* ── Layout geral ── */
    .wa-page {
        display: flex;
        height: calc(100vh - 64px);
        overflow: hidden;
        background: #f4f6fb;
    }

    /* ── Empty state ── */
    .wa-empty-state {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 16px;
        color: #6b7280;
        text-align: center;
        padding: 40px;
    }

    .wa-empty-state .wa-icon-circle {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: #dcfce7;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 36px;
        color: #25D366;
    }

    .wa-empty-state h3 {
        font-size: 20px;
        font-weight: 700;
        color: #1a1d23;
        margin: 0;
    }

    .wa-empty-state p {
        font-size: 14px;
        color: #6b7280;
        margin: 0;
        max-width: 360px;
    }

    .btn-go-integrations {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 10px 22px;
        background: #25D366;
        color: #fff;
        border: none;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        transition: background .15s;
        cursor: pointer;
    }

    .btn-go-integrations:hover { background: #1ebe5a; color: #fff; }

    /* ── Sidebar de conversas ── */
    .wa-sidebar {
        width: 320px;
        flex-shrink: 0;
        background: #fff;
        border-right: 1px solid #e8eaf0;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .wa-sidebar-header {
        padding: 16px 16px 12px;
        border-bottom: 1px solid #f0f2f7;
        flex-shrink: 0;
    }

    .wa-sidebar-title {
        font-size: 15px;
        font-weight: 700;
        color: #1a1d23;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .wa-sidebar-title .wa-badge {
        font-size: 11px;
        font-weight: 700;
        background: #EF4444;
        color: #fff;
        border-radius: 99px;
        padding: 2px 7px;
    }

    .wa-search {
        position: relative;
    }

    .wa-search i {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
        font-size: 14px;
    }

    .wa-search input {
        width: 100%;
        padding: 8px 10px 8px 32px;
        border: 1.5px solid #e8eaf0;
        border-radius: 9px;
        font-size: 13px;
        color: #1a1d23;
        background: #f8fafc;
        outline: none;
        transition: border-color .15s;
    }

    .wa-search input:focus {
        border-color: #25D366;
        background: #fff;
    }

    .wa-filters {
        display: flex;
        gap: 4px;
        padding: 10px 16px 0;
        flex-shrink: 0;
    }

    .wa-filter-btn {
        padding: 5px 12px;
        border-radius: 99px;
        font-size: 12px;
        font-weight: 600;
        border: 1.5px solid #e8eaf0;
        background: #fff;
        color: #6b7280;
        cursor: pointer;
        transition: all .15s;
    }

    .wa-filter-btn.active {
        background: #25D366;
        border-color: #25D366;
        color: #fff;
    }

    .wa-conv-list {
        flex: 1;
        overflow-y: auto;
        padding: 8px 0;
    }

    .wa-conv-item {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 11px 16px;
        cursor: pointer;
        transition: background .12s;
        border-bottom: 1px solid #f9fafb;
    }

    .wa-conv-item:hover { background: #f8fafc; }
    .wa-conv-item.active { background: #f0fdf4; border-left: 3px solid #25D366; }

    .wa-conv-avatar {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: linear-gradient(135deg, #25D366, #128C7E);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 16px;
        font-weight: 700;
        flex-shrink: 0;
        overflow: hidden;
    }

    .wa-conv-avatar img { width: 100%; height: 100%; object-fit: cover; }

    .wa-conv-info { flex: 1; min-width: 0; }

    .wa-conv-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 3px;
    }

    .wa-conv-name {
        font-size: 13.5px;
        font-weight: 600;
        color: #1a1d23;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 160px;
    }

    .wa-conv-time {
        font-size: 11px;
        color: #9ca3af;
        flex-shrink: 0;
    }

    .wa-conv-preview {
        font-size: 12.5px;
        color: #6b7280;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 200px;
    }

    .wa-conv-bottom {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 3px;
    }

    .wa-unread-dot {
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: #25D366;
        color: #fff;
        font-size: 10px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    /* ── Área principal de chat ── */
    .wa-chat-area {
        flex: 1;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        background: #f4f6fb;
    }

    .wa-no-conv {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 12px;
        color: #9ca3af;
    }

    .wa-no-conv i { font-size: 56px; opacity: .3; color: #25D366; }
    .wa-no-conv p { font-size: 14px; }

    /* ── Chat Header ── */
    .wa-chat-header {
        background: #fff;
        border-bottom: 1px solid #e8eaf0;
        padding: 12px 20px;
        display: flex;
        align-items: center;
        gap: 12px;
        flex-shrink: 0;
    }

    .wa-chat-contact-name {
        font-size: 14.5px;
        font-weight: 700;
        color: #1a1d23;
    }

    .wa-chat-contact-phone {
        font-size: 12px;
        color: #9ca3af;
    }

    .wa-status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #25D366;
        flex-shrink: 0;
    }

    .wa-chat-actions {
        margin-left: auto;
        display: flex;
        gap: 8px;
    }

    .wa-action-btn {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        border: 1.5px solid #e8eaf0;
        background: #fff;
        color: #6b7280;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        cursor: pointer;
        transition: all .15s;
    }

    .wa-action-btn:hover { background: #f4f6fb; color: #1a1d23; }
    .wa-action-btn.danger:hover { background: #fef2f2; color: #EF4444; border-color: #fecaca; }

    /* ── Messages area ── */
    .wa-messages {
        flex: 1;
        overflow-y: auto;
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .wa-date-sep {
        text-align: center;
        font-size: 11.5px;
        color: #9ca3af;
        font-weight: 600;
        margin: 12px 0 8px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .wa-date-sep::before,
    .wa-date-sep::after {
        content: '';
        flex: 1;
        height: 1px;
        background: #e8eaf0;
    }

    /* Balões de mensagem */
    .wa-msg {
        display: flex;
        flex-direction: column;
        max-width: 65%;
        margin-bottom: 2px;
        position: relative;
    }

    .wa-msg.inbound  { align-self: flex-start; align-items: flex-start; }
    .wa-msg.outbound { align-self: flex-end;   align-items: flex-end;   }
    .wa-msg.note     { align-self: center; max-width: 80%; }

    .wa-bubble {
        padding: 8px 12px;
        border-radius: 12px;
        font-size: 13.5px;
        line-height: 1.5;
        color: #1a1d23;
        word-break: break-word;
        position: relative;
    }

    .wa-msg.inbound  .wa-bubble { background: #fff; border-radius: 2px 12px 12px 12px; box-shadow: 0 1px 2px rgba(0,0,0,.06); }
    .wa-msg.outbound .wa-bubble { background: #dcfce7; border-radius: 12px 2px 12px 12px; }
    .wa-msg.note     .wa-bubble { background: #fef9c3; border-radius: 10px; width: 100%; border-left: 3px solid #F59E0B; }

    .wa-bubble.deleted { font-style: italic; color: #9ca3af; font-size: 12.5px; }

    /* Metadados da mensagem */
    .wa-msg-meta {
        display: flex;
        align-items: center;
        gap: 4px;
        font-size: 10.5px;
        color: #9ca3af;
        margin-top: 2px;
        padding: 0 4px;
    }

    .wa-ack i { font-size: 12px; }
    .wa-ack.read i { color: #25D366; }

    /* Nota privada label */
    .wa-note-label {
        font-size: 11px;
        color: #92400e;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 4px;
        margin-bottom: 4px;
    }

    /* Reações */
    .wa-reactions {
        margin-top: 2px;
        display: flex;
        gap: 4px;
        flex-wrap: wrap;
    }

    .wa-reaction-pill {
        background: rgba(255,255,255,.9);
        border: 1px solid #e8eaf0;
        border-radius: 99px;
        padding: 1px 6px;
        font-size: 13px;
        display: flex;
        align-items: center;
        gap: 3px;
        cursor: pointer;
    }

    .wa-reaction-pill span { font-size: 10px; color: #6b7280; }

    /* Imagem */
    .wa-img-thumb {
        max-width: 220px;
        max-height: 200px;
        border-radius: 8px;
        cursor: pointer;
        object-fit: cover;
        display: block;
    }

    /* Áudio */
    .wa-audio { max-width: 240px; }
    .wa-audio audio { width: 100%; margin-top: 4px; }

    /* ── Footer de composição ── */
    .wa-compose-area {
        background: #fff;
        border-top: 1px solid #e8eaf0;
        padding: 12px 16px;
        flex-shrink: 0;
    }

    .wa-compose-tabs {
        display: flex;
        gap: 0;
        margin-bottom: 10px;
        border: 1.5px solid #e8eaf0;
        border-radius: 9px;
        overflow: hidden;
        width: fit-content;
    }

    .wa-tab-btn {
        padding: 6px 16px;
        font-size: 12.5px;
        font-weight: 600;
        color: #6b7280;
        background: #fff;
        border: none;
        cursor: pointer;
        transition: all .15s;
    }

    .wa-tab-btn.active { background: #25D366; color: #fff; }
    .wa-tab-btn:not(.active):hover { background: #f4f6fb; }

    .wa-compose-row {
        display: flex;
        align-items: flex-end;
        gap: 8px;
    }

    .wa-textarea-wrap { flex: 1; }

    .wa-textarea {
        width: 100%;
        min-height: 40px;
        max-height: 120px;
        resize: none;
        border: 1.5px solid #e8eaf0;
        border-radius: 10px;
        padding: 9px 12px;
        font-size: 13.5px;
        font-family: 'Inter', sans-serif;
        color: #1a1d23;
        background: #fafafa;
        outline: none;
        line-height: 1.5;
        overflow-y: auto;
        transition: border-color .15s;
    }

    .wa-textarea:focus { border-color: #25D366; background: #fff; }
    .wa-textarea.note-mode { border-color: #F59E0B; background: #fffbeb; }

    .wa-btn-icon {
        width: 38px;
        height: 38px;
        border: 1.5px solid #e8eaf0;
        border-radius: 9px;
        background: #fff;
        color: #6b7280;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        cursor: pointer;
        transition: all .15s;
        flex-shrink: 0;
    }

    .wa-btn-icon:hover { background: #f4f6fb; color: #1a1d23; }

    .wa-btn-send {
        width: 38px;
        height: 38px;
        border: none;
        border-radius: 9px;
        background: #25D366;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        cursor: pointer;
        transition: background .15s;
        flex-shrink: 0;
    }

    .wa-btn-send:hover { background: #1ebe5a; }
    .wa-btn-send:disabled { background: #d1fae5; cursor: not-allowed; }

    /* Gravação de áudio */
    .wa-recording-indicator {
        display: none;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        color: #EF4444;
        font-weight: 600;
        flex: 1;
    }

    .wa-recording-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #EF4444;
        animation: pulse 1s infinite;
    }

    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: .3; }
    }

    /* ── Painel de detalhes ── */
    .wa-details {
        width: 260px;
        flex-shrink: 0;
        background: #fff;
        border-left: 1px solid #e8eaf0;
        overflow-y: auto;
        display: none;
        flex-direction: column;
        gap: 0;
    }

    .wa-details.open { display: flex; }

    .wa-details-section {
        padding: 16px;
        border-bottom: 1px solid #f0f2f7;
    }

    .wa-details-label {
        font-size: 11px;
        font-weight: 700;
        color: #9ca3af;
        text-transform: uppercase;
        letter-spacing: .06em;
        margin-bottom: 8px;
    }

    .wa-details-value {
        font-size: 13px;
        color: #374151;
    }

    /* Emoji picker simples */
    .wa-emoji-picker {
        display: none;
        position: absolute;
        bottom: calc(100% + 6px);
        right: 0;
        background: #fff;
        border: 1px solid #e8eaf0;
        border-radius: 10px;
        padding: 8px;
        gap: 4px;
        flex-wrap: wrap;
        width: 160px;
        box-shadow: 0 6px 20px rgba(0,0,0,.1);
        z-index: 200;
    }

    .wa-emoji-picker.open { display: flex; }
    .wa-emoji-opt { font-size: 20px; cursor: pointer; padding: 2px; border-radius: 4px; }
    .wa-emoji-opt:hover { background: #f4f6fb; }
</style>
@endpush

@section('content')
<div class="wa-page">

@if(! $connected)
{{-- ── Empty State: não conectado ───────────────────────────────────────────── --}}
<div class="wa-empty-state">
    <div class="wa-icon-circle">
        <i class="bi bi-whatsapp"></i>
    </div>
    <h3>WhatsApp não conectado</h3>
    <p>Para usar o chat, você precisa conectar seu número de WhatsApp em Integrações.</p>
    <a href="{{ route('settings.integrations.index') }}" class="btn-go-integrations">
        <i class="bi bi-plugin"></i> Ir para Integrações
    </a>
</div>

@else
{{-- ── Inbox ─────────────────────────────────────────────────────────────────── --}}

{{-- Sidebar de conversas --}}
<div class="wa-sidebar">
    <div class="wa-sidebar-header">
        <div class="wa-sidebar-title">
            <i class="bi bi-whatsapp" style="color:#25D366;"></i>
            Conversas
            <span class="wa-badge" id="totalUnreadBadge" style="display:none;"></span>
        </div>
        <div class="wa-search">
            <i class="bi bi-search"></i>
            <input type="text" id="searchInput" placeholder="Buscar conversa...">
        </div>
    </div>

    <div class="wa-filters">
        <button class="wa-filter-btn active" data-filter="all">Todas</button>
        <button class="wa-filter-btn" data-filter="open">Abertas</button>
        <button class="wa-filter-btn" data-filter="closed">Fechadas</button>
    </div>

    <div class="wa-conv-list" id="convList">
        @forelse($conversations as $conv)
        <div class="wa-conv-item"
             data-conv-id="{{ $conv->id }}"
             data-phone="{{ $conv->phone }}"
             data-status="{{ $conv->status }}"
             onclick="openConversation({{ $conv->id }}, this)">
            <div class="wa-conv-avatar">
                @if($conv->contact_picture_url)
                    <img src="{{ $conv->contact_picture_url }}" alt="">
                @else
                    {{ strtoupper(substr($conv->contact_name ?? $conv->phone, 0, 1)) }}
                @endif
            </div>
            <div class="wa-conv-info">
                <div class="wa-conv-top">
                    <span class="wa-conv-name">{{ $conv->contact_name ?? $conv->phone }}</span>
                    <span class="wa-conv-time">{{ $conv->last_message_at?->diffForHumans(short: true) }}</span>
                </div>
                <div class="wa-conv-bottom">
                    <span class="wa-conv-preview">
                        @if($conv->latestMessage)
                            @if($conv->latestMessage->type === 'image') 📷 Imagem
                            @elseif($conv->latestMessage->type === 'audio') 🎵 Áudio
                            @elseif($conv->latestMessage->type === 'note') 🔒 Nota interna
                            @else {{ Str::limit($conv->latestMessage->body ?? '', 40) }}
                            @endif
                        @endif
                    </span>
                    @if($conv->unread_count > 0)
                    <span class="wa-unread-dot">{{ $conv->unread_count }}</span>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div style="padding:32px 16px;text-align:center;color:#9ca3af;font-size:13px;">
            <i class="bi bi-chat-dots" style="font-size:32px;display:block;margin-bottom:10px;opacity:.4;"></i>
            Nenhuma conversa ainda.<br>As mensagens recebidas aparecerão aqui.
        </div>
        @endforelse
    </div>
</div>

{{-- Área de chat --}}
<div class="wa-chat-area" id="chatArea">
    <div class="wa-no-conv" id="noConvPlaceholder">
        <i class="bi bi-chat-quote"></i>
        <p>Selecione uma conversa para começar</p>
    </div>

    {{-- Header do chat (oculto até abrir conversa) --}}
    <div class="wa-chat-header" id="chatHeader" style="display:none;">
        <div class="wa-conv-avatar" id="chatAvatar" style="width:38px;height:38px;font-size:14px;"></div>
        <div>
            <div class="wa-chat-contact-name" id="chatContactName"></div>
            <div class="wa-chat-contact-phone" id="chatContactPhone"></div>
        </div>
        <div class="wa-chat-actions">
            <button class="wa-action-btn" id="btnToggleDetails" title="Detalhes" onclick="toggleDetails()">
                <i class="bi bi-info-circle"></i>
            </button>
            <div style="position:relative;">
                <button class="wa-action-btn" id="btnCloseConv" title="Fechar conversa" onclick="toggleConvStatus()">
                    <i class="bi bi-check-circle"></i>
                </button>
            </div>
        </div>
    </div>

    {{-- Mensagens --}}
    <div class="wa-messages" id="messagesContainer" style="display:none;"></div>

    {{-- Footer de composição --}}
    <div class="wa-compose-area" id="composeArea" style="display:none;">
        <div class="wa-compose-tabs">
            <button class="wa-tab-btn active" id="tabReply" onclick="setComposeMode('reply')">Responder</button>
            <button class="wa-tab-btn" id="tabNote" onclick="setComposeMode('note')">Nota Privada</button>
        </div>

        <div class="wa-compose-row" id="recordingRow" style="display:none;">
            <div class="wa-recording-indicator" style="display:flex;" id="recordingIndicator">
                <div class="wa-recording-dot"></div>
                Gravando áudio... <span id="recordingTimer">0:00</span>
            </div>
            <button class="wa-btn-icon" onclick="cancelRecording()" title="Cancelar">
                <i class="bi bi-x"></i>
            </button>
            <button class="wa-btn-send" onclick="stopAndSendRecording()" title="Enviar áudio">
                <i class="bi bi-send"></i>
            </button>
        </div>

        <div class="wa-compose-row" id="normalRow">
            <input type="file" id="fileInput" accept="image/*" style="display:none;" onchange="sendImage(this)">
            <button class="wa-btn-icon" onclick="document.getElementById('fileInput').click()" title="Enviar imagem">
                <i class="bi bi-image"></i>
            </button>
            <button class="wa-btn-icon" id="btnMic" onclick="startRecording()" title="Gravar áudio">
                <i class="bi bi-mic"></i>
            </button>
            <div class="wa-textarea-wrap">
                <textarea class="wa-textarea"
                          id="messageInput"
                          placeholder="Digite uma mensagem..."
                          rows="1"
                          oninput="autoResize(this)"></textarea>
            </div>
            <button class="wa-btn-send" id="btnSend" onclick="sendMessage()" title="Enviar">
                <i class="bi bi-send"></i>
            </button>
        </div>
    </div>
</div>

{{-- Painel de detalhes --}}
<div class="wa-details" id="detailsPanel">
    <div class="wa-details-section">
        <div class="wa-details-label">Contato</div>
        <div class="wa-details-value" id="detailsName" style="font-weight:600;margin-bottom:4px;"></div>
        <div class="wa-details-value" id="detailsPhone" style="color:#9ca3af;font-size:12px;"></div>
    </div>
    <div class="wa-details-section">
        <div class="wa-details-label">Atribuído a</div>
        <select class="wa-textarea" style="min-height:unset;height:36px;padding:6px 10px;" id="assignSelect" onchange="assignUser()">
            <option value="">Sem atribuição</option>
            @foreach($users as $u)
            <option value="{{ $u->id }}">{{ $u->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="wa-details-section">
        <div class="wa-details-label">Status</div>
        <div class="wa-details-value" id="detailsStatus"></div>
    </div>
</div>

@endif
</div>
@endsection

@push('scripts')
<script>
// ── Estado global ─────────────────────────────────────────────────────────────
let activeConvId      = null;
let activeConvStatus  = 'open';
let composeMode       = 'reply';
let lastPollAt        = new Date().toISOString();
let pollTimer         = null;
let mediaRecorder     = null;
let audioChunks       = [];
let recordingSeconds  = 0;
let recordingTimer    = null;
let reactionTargetId  = null;

const CSRF = document.querySelector('meta[name="csrf-token"]')?.content;

// ── Inicialização ─────────────────────────────────────────────────────────────
@if($connected)
document.addEventListener('DOMContentLoaded', () => {
    setupSearch();
    setupFilters();
    updateTotalUnread();
    startPolling();

    // Enter para enviar
    document.getElementById('messageInput')?.addEventListener('keydown', e => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });
});
@endif

// ── Filtros e pesquisa ────────────────────────────────────────────────────────
function setupSearch() {
    document.getElementById('searchInput').addEventListener('input', function() {
        const q = this.value.toLowerCase();
        document.querySelectorAll('.wa-conv-item').forEach(item => {
            const name  = item.querySelector('.wa-conv-name').textContent.toLowerCase();
            const phone = item.dataset.phone.toLowerCase();
            item.style.display = (name.includes(q) || phone.includes(q)) ? '' : 'none';
        });
    });
}

function setupFilters() {
    document.querySelectorAll('.wa-filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.wa-filter-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const filter = this.dataset.filter;
            document.querySelectorAll('.wa-conv-item').forEach(item => {
                item.style.display = (filter === 'all' || item.dataset.status === filter) ? '' : 'none';
            });
        });
    });
}

// ── Abrir conversa ────────────────────────────────────────────────────────────
async function openConversation(convId, el) {
    activeConvId = convId;

    document.querySelectorAll('.wa-conv-item').forEach(i => i.classList.remove('active'));
    el.classList.add('active');

    // Remover unread badge do item
    const dot = el.querySelector('.wa-unread-dot');
    if (dot) dot.remove();

    // Atualizar header
    const name  = el.querySelector('.wa-conv-name').textContent;
    const phone = el.dataset.phone;
    activeConvStatus = el.dataset.status;

    document.getElementById('chatHeader').style.display = 'flex';
    document.getElementById('messagesContainer').style.display = 'flex';
    document.getElementById('composeArea').style.display = 'block';
    document.getElementById('noConvPlaceholder').style.display = 'none';

    document.getElementById('chatContactName').textContent = name;
    document.getElementById('chatContactPhone').textContent = phone;
    document.getElementById('chatAvatar').textContent = name.charAt(0).toUpperCase();
    document.getElementById('detailsName').textContent = name;
    document.getElementById('detailsPhone').textContent = phone;
    document.getElementById('detailsStatus').textContent = activeConvStatus === 'open' ? '🟢 Aberta' : '⚫ Fechada';
    document.getElementById('btnCloseConv').title = activeConvStatus === 'open' ? 'Fechar conversa' : 'Reabrir conversa';
    document.getElementById('btnCloseConv').querySelector('i').className = activeConvStatus === 'open'
        ? 'bi bi-check-circle' : 'bi bi-arrow-counterclockwise';

    // Marcar como lida
    await fetch(`/whatsapp/conversations/${convId}/read`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
    });
    updateTotalUnread();

    // Carregar mensagens
    const res  = await fetch(`/whatsapp/conversations/${convId}`, { headers: { 'Accept': 'application/json' } });
    const data = await res.json();
    renderMessages(data.messages, true);
}

// ── Renderizar mensagens ──────────────────────────────────────────────────────
function renderMessages(messages, clear = false) {
    const container = document.getElementById('messagesContainer');
    if (clear) container.innerHTML = '';

    let lastDate = null;

    messages.forEach(msg => {
        const msgDate = msg.sent_at ? new Date(msg.sent_at).toLocaleDateString('pt-BR') : null;

        if (msgDate && msgDate !== lastDate) {
            lastDate = msgDate;
            const sep = document.createElement('div');
            sep.className = 'wa-date-sep';
            const today = new Date().toLocaleDateString('pt-BR');
            const yesterday = new Date(Date.now() - 86400000).toLocaleDateString('pt-BR');
            sep.textContent = msgDate === today ? 'Hoje' : msgDate === yesterday ? 'Ontem' : msgDate;
            container.appendChild(sep);
        }

        container.appendChild(buildMessageEl(msg));
    });

    container.scrollTop = container.scrollHeight;
}

function buildMessageEl(msg) {
    const isNote = msg.type === 'note';
    const isReaction = msg.type === 'reaction';

    if (isReaction) {
        // Reações são exibidas junto à mensagem original — skip aqui
        return document.createComment('reaction');
    }

    const dir  = msg.direction;
    const wrap = document.createElement('div');
    wrap.className = `wa-msg ${isNote ? 'note' : dir}`;
    wrap.dataset.id = msg.id;
    wrap.dataset.wahaId = msg.waha_message_id || '';

    if (isNote) {
        const label = document.createElement('div');
        label.className = 'wa-note-label';
        label.innerHTML = '<i class="bi bi-lock-fill"></i> Nota interna — visível só para o time';
        wrap.appendChild(label);
    }

    const bubble = document.createElement('div');
    bubble.className = `wa-bubble${msg.is_deleted ? ' deleted' : ''}`;

    if (msg.is_deleted) {
        bubble.innerHTML = '<i class="bi bi-slash-circle" style="margin-right:4px;"></i>Esta mensagem foi apagada';
    } else if (msg.type === 'image' && msg.media_url) {
        bubble.innerHTML = `<img src="${msg.media_url}" class="wa-img-thumb" onclick="window.open('${msg.media_url}','_blank')" alt="Imagem">`;
        if (msg.body) bubble.innerHTML += `<div style="margin-top:6px;font-size:13px;">${escHtml(msg.body)}</div>`;
    } else if (msg.type === 'audio' && msg.media_url) {
        bubble.innerHTML = `<div class="wa-audio"><i class="bi bi-mic-fill" style="color:#25D366;margin-right:4px;"></i>Áudio<audio controls src="${msg.media_url}"></audio></div>`;
    } else {
        bubble.textContent = msg.body || '';
    }

    wrap.appendChild(bubble);

    // Meta (hora + ack)
    if (!isNote) {
        const meta = document.createElement('div');
        meta.className = 'wa-msg-meta';
        const time = msg.sent_at ? new Date(msg.sent_at).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' }) : '';
        meta.innerHTML = `<span>${time}</span>`;
        if (dir === 'outbound' && !isNote) {
            const ackIcon = { pending: '🕐', sent: '✓', delivered: '✓✓', read: '✓✓' };
            const ackColor = msg.ack === 'read' ? 'color:#25D366;' : '';
            meta.innerHTML += `<span style="${ackColor}">${ackIcon[msg.ack] || '✓'}</span>`;
        }
        wrap.appendChild(meta);
    }

    // Double-click para reagir (só mensagens recebidas com ID WAHA)
    if (dir === 'inbound' && msg.waha_message_id) {
        bubble.addEventListener('dblclick', () => showEmojiPicker(msg.waha_message_id, wrap));
    }

    return wrap;
}

function appendMessages(messages) {
    if (!messages?.length) return;
    renderMessages(messages, false);
}

// ── Emoji picker simples ──────────────────────────────────────────────────────
const QUICK_EMOJIS = ['👍', '❤️', '😂', '😮', '😢', '🙏'];

function showEmojiPicker(wahaId, wrap) {
    // Remover picker existente
    document.querySelectorAll('.wa-emoji-picker').forEach(e => e.remove());

    reactionTargetId = wahaId;
    const picker = document.createElement('div');
    picker.className = 'wa-emoji-picker open';

    QUICK_EMOJIS.forEach(emoji => {
        const btn = document.createElement('span');
        btn.className = 'wa-emoji-opt';
        btn.textContent = emoji;
        btn.onclick = () => sendReaction(wahaId, emoji);
        picker.appendChild(btn);
    });

    wrap.style.position = 'relative';
    wrap.appendChild(picker);

    setTimeout(() => document.addEventListener('click', closeEmojiPicker, { once: true }), 50);
}

function closeEmojiPicker() {
    document.querySelectorAll('.wa-emoji-picker').forEach(e => e.remove());
}

// ── Envio de mensagens ────────────────────────────────────────────────────────
async function sendMessage() {
    if (!activeConvId) return;
    const input = document.getElementById('messageInput');
    const body  = input.value.trim();
    if (!body) return;

    input.value = '';
    autoResize(input);

    const type = composeMode === 'note' ? 'note' : 'text';

    const formData = new FormData();
    formData.append('type', type);
    formData.append('body', body);

    const res  = await fetch(`/whatsapp/conversations/${activeConvId}/messages`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: formData,
    });

    const data = await res.json();
    if (data.success) {
        appendMessages([data.message]);
    } else {
        toastr.error(data.error || 'Erro ao enviar mensagem');
    }
}

async function sendImage(input) {
    if (!activeConvId || !input.files[0]) return;

    const formData = new FormData();
    formData.append('type', 'image');
    formData.append('file', input.files[0]);

    const res  = await fetch(`/whatsapp/conversations/${activeConvId}/messages`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: formData,
    });

    const data = await res.json();
    if (data.success) {
        appendMessages([data.message]);
    } else {
        toastr.error(data.error || 'Erro ao enviar imagem');
    }

    input.value = '';
}

async function sendReaction(wahaId, emoji) {
    closeEmojiPicker();
    if (!activeConvId) return;

    const formData = new FormData();
    formData.append('waha_message_id', wahaId);
    formData.append('emoji', emoji);

    await fetch(`/whatsapp/conversations/${activeConvId}/react`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: formData,
    });
}

// ── Gravação de áudio ─────────────────────────────────────────────────────────
async function startRecording() {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        mediaRecorder = new MediaRecorder(stream, { mimeType: 'audio/webm;codecs=opus' });
        audioChunks   = [];

        mediaRecorder.ondataavailable = e => audioChunks.push(e.data);
        mediaRecorder.start();

        document.getElementById('normalRow').style.display = 'none';
        document.getElementById('recordingRow').style.display = 'flex';

        recordingSeconds = 0;
        recordingTimer = setInterval(() => {
            recordingSeconds++;
            const m = Math.floor(recordingSeconds / 60);
            const s = recordingSeconds % 60;
            document.getElementById('recordingTimer').textContent = `${m}:${s.toString().padStart(2,'0')}`;
        }, 1000);
    } catch (e) {
        toastr.error('Permissão de microfone negada.');
    }
}

function cancelRecording() {
    if (mediaRecorder) { mediaRecorder.stop(); mediaRecorder.stream.getTracks().forEach(t => t.stop()); }
    clearInterval(recordingTimer);
    document.getElementById('recordingRow').style.display = 'none';
    document.getElementById('normalRow').style.display = 'flex';
}

async function stopAndSendRecording() {
    if (!mediaRecorder) return;

    mediaRecorder.onstop = async () => {
        const blob = new Blob(audioChunks, { type: 'audio/webm' });
        const file = new File([blob], 'audio.webm', { type: 'audio/webm' });

        const formData = new FormData();
        formData.append('type', 'audio');
        formData.append('file', file);

        cancelRecording();

        const res  = await fetch(`/whatsapp/conversations/${activeConvId}/messages`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: formData,
        });

        const data = await res.json();
        if (data.success) {
            appendMessages([data.message]);
        } else {
            toastr.error(data.error || 'Erro ao enviar áudio');
        }
    };

    mediaRecorder.stop();
    mediaRecorder.stream.getTracks().forEach(t => t.stop());
    clearInterval(recordingTimer);
}

// ── Compose mode (reply / note) ───────────────────────────────────────────────
function setComposeMode(mode) {
    composeMode = mode;
    const textarea = document.getElementById('messageInput');
    document.getElementById('tabReply').classList.toggle('active', mode === 'reply');
    document.getElementById('tabNote').classList.toggle('active', mode === 'note');
    textarea.classList.toggle('note-mode', mode === 'note');
    textarea.placeholder = mode === 'note' ? 'Adicionar nota interna...' : 'Digite uma mensagem...';
    // Ocultar botões de mídia na nota
    document.getElementById('fileInput').closest('button')?.parentElement?.querySelectorAll('.wa-btn-icon').forEach(b => {
        b.style.display = mode === 'note' ? 'none' : '';
    });
}

// ── Status da conversa ────────────────────────────────────────────────────────
async function toggleConvStatus() {
    if (!activeConvId) return;
    const newStatus = activeConvStatus === 'open' ? 'closed' : 'open';

    const res  = await fetch(`/whatsapp/conversations/${activeConvId}/status`, {
        method: 'PUT',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ status: newStatus }),
    });

    const data = await res.json();
    if (data.success) {
        activeConvStatus = newStatus;
        const convEl = document.querySelector(`[data-conv-id="${activeConvId}"]`);
        if (convEl) convEl.dataset.status = newStatus;
        document.getElementById('detailsStatus').textContent = newStatus === 'open' ? '🟢 Aberta' : '⚫ Fechada';
        document.getElementById('btnCloseConv').title = newStatus === 'open' ? 'Fechar conversa' : 'Reabrir conversa';
        document.getElementById('btnCloseConv').querySelector('i').className = newStatus === 'open'
            ? 'bi bi-check-circle' : 'bi bi-arrow-counterclockwise';
        toastr.success(newStatus === 'closed' ? 'Conversa fechada.' : 'Conversa reaberta.');
    }
}

// ── Atribuição ────────────────────────────────────────────────────────────────
async function assignUser() {
    if (!activeConvId) return;
    const userId = document.getElementById('assignSelect').value;

    await fetch(`/whatsapp/conversations/${activeConvId}/assign`, {
        method: 'PUT',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ user_id: userId }),
    });
}

// ── Painel de detalhes ────────────────────────────────────────────────────────
function toggleDetails() {
    document.getElementById('detailsPanel').classList.toggle('open');
}

// ── Polling ───────────────────────────────────────────────────────────────────
function startPolling() {
    pollTimer = setInterval(async () => {
        try {
            const url = `/whatsapp/poll?since=${encodeURIComponent(lastPollAt)}${activeConvId ? '&conversation_id=' + activeConvId : ''}`;
            const res  = await fetch(url, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();

            if (data.new_messages?.length) {
                appendMessages(data.new_messages);
            }

            if (data.conversations_updated?.length) {
                data.conversations_updated.forEach(c => updateConvInSidebar(c));
            }

            lastPollAt = data.now;
        } catch (_) {}
    }, 5000);
}

function updateConvInSidebar(conv) {
    let el = document.querySelector(`[data-conv-id="${conv.id}"]`);

    if (! el) {
        // Nova conversa: criar elemento e inserir no topo
        el = document.createElement('div');
        el.className = 'wa-conv-item';
        el.dataset.convId = conv.id;
        el.dataset.phone  = conv.phone;
        el.dataset.status = conv.status;
        el.onclick        = function() { openConversation(conv.id, this); };
        document.getElementById('convList').prepend(el);
    }

    const preview = conv.last_message_type === 'image'  ? '📷 Imagem'  :
                    conv.last_message_type === 'audio'  ? '🎵 Áudio'   :
                    conv.last_message_type === 'note'   ? '🔒 Nota'    :
                    (conv.last_message_body || '').substring(0, 40);

    const initial = (conv.contact_name || conv.phone || '?').charAt(0).toUpperCase();
    const timeAgo = conv.last_message_at ? timeRelative(conv.last_message_at) : '';
    const unread  = conv.unread_count > 0 && conv.id !== activeConvId
        ? `<span class="wa-unread-dot">${conv.unread_count}</span>` : '';

    el.innerHTML = `
        <div class="wa-conv-avatar">${initial}</div>
        <div class="wa-conv-info">
            <div class="wa-conv-top">
                <span class="wa-conv-name">${escHtml(conv.contact_name || conv.phone)}</span>
                <span class="wa-conv-time">${timeAgo}</span>
            </div>
            <div class="wa-conv-bottom">
                <span class="wa-conv-preview">${escHtml(preview)}</span>
                ${unread}
            </div>
        </div>`;

    el.dataset.status = conv.status;

    // Mover para o topo da lista
    document.getElementById('convList').prepend(el);

    updateTotalUnread();
}

function updateTotalUnread() {
    let total = 0;
    document.querySelectorAll('.wa-unread-dot').forEach(d => {
        total += parseInt(d.textContent || '0', 10);
    });
    const badge = document.getElementById('totalUnreadBadge');
    if (badge) {
        badge.textContent = total;
        badge.style.display = total > 0 ? '' : 'none';
    }
}

// ── Utilitários ───────────────────────────────────────────────────────────────
function autoResize(el) {
    el.style.height = 'auto';
    el.style.height = Math.min(el.scrollHeight, 120) + 'px';
}

function escHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function timeRelative(iso) {
    const diff = (Date.now() - new Date(iso)) / 1000;
    if (diff < 60) return 'agora';
    if (diff < 3600) return Math.floor(diff / 60) + 'm';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h';
    return new Date(iso).toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' });
}
</script>
<style>
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
.spin { animation: spin .8s linear infinite; display: inline-block; }
</style>
@endpush

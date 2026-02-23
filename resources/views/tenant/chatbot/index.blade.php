@extends('tenant.layouts.app')

@php
    $title    = 'Chatbot Builder';
    $pageIcon = 'diagram-3';
@endphp

@section('topbar_actions')
<div class="topbar-actions">
    <a href="{{ route('chatbot.flows.create') }}" class="btn-primary-sm" style="text-decoration:none;display:flex;align-items:center;gap:6px;">
        <i class="bi bi-plus-lg"></i> Novo Fluxo
    </a>
</div>
@endsection

@push('styles')
<style>
    .flows-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 16px;
    }

    .flow-card {
        background: #fff;
        border-radius: 14px;
        border: 1px solid #e8eaf0;
        overflow: hidden;
        transition: box-shadow .15s;
    }

    .flow-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,.07); }

    .flow-card-body {
        padding: 18px 20px;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .flow-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: #eff6ff;
        border: 1.5px solid #bfdbfe;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        color: #2563eb;
        flex-shrink: 0;
    }

    .flow-name {
        font-size: 14px;
        font-weight: 700;
        color: #1a1d23;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .flow-desc {
        font-size: 12px;
        color: #9ca3af;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .flow-meta {
        display: flex;
        gap: 14px;
        font-size: 11px;
        color: #6b7280;
    }

    .flow-actions {
        display: flex;
        gap: 6px;
        padding-top: 12px;
        border-top: 1px solid #f0f2f7;
        margin-top: auto;
    }

    .badge-active   { background: #d1fae5; color: #065f46; padding: 2px 9px; border-radius: 99px; font-size: 11px; font-weight: 700; white-space: nowrap; }
    .badge-inactive { background: #f3f4f6; color: #6b7280;  padding: 2px 9px; border-radius: 99px; font-size: 11px; font-weight: 700; white-space: nowrap; }

    .empty-state {
        text-align: center;
        padding: 80px 20px;
        color: #9ca3af;
    }

    .empty-state i      { font-size: 52px; opacity: .2; margin-bottom: 14px; display: block; }
    .empty-state h3     { font-size: 16px; color: #374151; margin: 0 0 6px; }
    .empty-state p      { font-size: 13.5px; margin: 0 0 20px; }

    .btn-delete-flow { display: inline-flex; align-items: center; gap: 5px; font-size: 12px; }
    .btn-delete-flow:hover { background: #fee2e2 !important; border-color: #fca5a5 !important; color: #ef4444 !important; }

    /* ── Test Chat Sidebar ──────────────────────────────────────────────────── */
    .tcm-backdrop {
        position: fixed; inset: 0; background: rgba(0,0,0,.18);
        z-index: 1999; opacity: 0; pointer-events: none;
        transition: opacity .32s ease;
    }
    .tcm-backdrop.open { opacity: 1; pointer-events: all; }

    .tcm-sidebar {
        position: fixed; top: 0; right: 0; width: 380px; max-width: 95vw;
        height: 100vh; background: #fff;
        box-shadow: -6px 0 40px rgba(0,0,0,.1);
        display: flex; flex-direction: column; z-index: 2000;
        transform: translateX(100%);
        transition: transform .35s cubic-bezier(.4,0,.2,1);
        overflow: hidden;
    }
    .tcm-sidebar.open { transform: translateX(0); }

    .tcm-header {
        display: flex; align-items: center; gap: 10px;
        padding: 16px 18px; border-bottom: 1px solid #f0f2f7; flex-shrink: 0;
        background: #fff;
    }
    .tcm-header-icon {
        width: 34px; height: 34px; border-radius: 10px; background: #eff6ff;
        display: flex; align-items: center; justify-content: center;
        font-size: 16px; color: #3B82F6; flex-shrink: 0;
    }
    .tcm-header-info { flex: 1; min-width: 0; }
    .tcm-header-info h3 { font-size: 13px; font-weight: 700; color: #1a1d23; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .tcm-header-info span { font-size: 11px; color: #9ca3af; }
    .tcm-header-btn { background: none; border: none; font-size: 18px; color: #9ca3af; cursor: pointer; padding: 4px 6px; border-radius: 7px; line-height: 1; }
    .tcm-header-btn:hover { background: #f3f4f6; color: #374151; }

    .tcm-messages {
        flex: 1; overflow-y: auto; padding: 16px 14px;
        display: flex; flex-direction: column; gap: 6px;
        background: #f8fafc; scroll-behavior: smooth;
    }
    .tcm-messages::-webkit-scrollbar { width: 4px; }
    .tcm-messages::-webkit-scrollbar-track { background: transparent; }
    .tcm-messages::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 4px; }

    @keyframes tcm-in {
        from { opacity: 0; transform: translateY(8px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .tcm-msg-bot, .tcm-msg-user, .tcm-msg-system {
        max-width: 82%; animation: tcm-in .22s ease forwards;
    }
    .tcm-msg-bot   { align-self: flex-start; }
    .tcm-msg-user  { align-self: flex-end; }
    .tcm-msg-system { align-self: center; max-width: 96%; }

    .tcm-bubble {
        padding: 9px 13px; border-radius: 16px;
        font-size: 13px; line-height: 1.55; word-break: break-word;
    }
    .tcm-msg-bot  .tcm-bubble { background: #fff; border: 1px solid #e8eaf0; border-bottom-left-radius: 4px; color: #1a1d23; box-shadow: 0 1px 2px rgba(0,0,0,.04); }
    .tcm-msg-user .tcm-bubble { background: #3B82F6; color: #fff; border-bottom-right-radius: 4px; }
    .tcm-msg-system .tcm-bubble { background: #f0f9ff; color: #6b7280; font-size: 11.5px; border-radius: 8px; text-align: center; border: 1px dashed #bfdbfe; padding: 5px 12px; }

    /* Typing dots */
    .tcm-typing-bubble { display: flex; gap: 5px; align-items: center; padding: 11px 16px !important; }
    @keyframes tcm-dot { 0%, 80%, 100% { transform: scale(.7); opacity: .4; } 40% { transform: scale(1); opacity: 1; } }
    .tcm-dot { width: 7px; height: 7px; border-radius: 50%; background: #9ca3af; animation: tcm-dot 1.3s infinite; display: inline-block; }
    .tcm-dot:nth-child(2) { animation-delay: .2s; }
    .tcm-dot:nth-child(3) { animation-delay: .4s; }

    .tcm-img { border-radius: 12px; overflow: hidden; max-width: 220px; }
    .tcm-img img { width: 100%; display: block; }
    .tcm-img-caption { font-size: 12px; color: #6b7280; padding: 5px 2px 0; }

    .tcm-footer {
        padding: 12px 14px 16px; border-top: 1px solid #f0f2f7; flex-shrink: 0;
        display: flex; flex-direction: column; gap: 9px; background: #fff;
    }
    .tcm-input-row { display: flex; gap: 7px; }
    .tcm-input {
        flex: 1; border: 1.5px solid #e8eaf0; border-radius: 12px;
        padding: 9px 12px; font-size: 13px; outline: none;
        resize: none; min-height: 38px; max-height: 90px; line-height: 1.45;
        transition: border-color .15s;
    }
    .tcm-input:focus { border-color: #3B82F6; }
    .tcm-send {
        background: #3B82F6; color: #fff; border: none; border-radius: 12px;
        padding: 0 16px; font-size: 14px; cursor: pointer; align-self: flex-end;
        height: 38px; transition: background .15s, transform .1s;
    }
    .tcm-send:hover { background: #2563eb; }
    .tcm-send:active { transform: scale(.95); }
    .tcm-send:disabled { opacity: .45; cursor: default; transform: none; }
    .tcm-bottom-row { display: flex; align-items: center; gap: 10px; }
    .tcm-restart {
        background: none; border: 1.5px solid #e8eaf0; border-radius: 8px;
        padding: 5px 11px; font-size: 12px; color: #6b7280; cursor: pointer;
        display: flex; align-items: center; gap: 5px; transition: all .15s;
    }
    .tcm-restart:hover { background: #f3f4f6; border-color: #d1d5db; }
    .tcm-done-msg { font-size: 12px; color: #059669; font-weight: 600; display: flex; align-items: center; gap: 4px; }
    .tcm-hint { font-size: 11px; color: #9ca3af; }
</style>
@endpush

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

// ── Estado do test chat ───────────────────────────────────────────────────────
let tcmUrl   = '';
let tcmState = { node_id: null, vars: {} };
let tcmBusy  = false;
let _typingCtr = 0;

const tcmSleep = ms => new Promise(r => setTimeout(r, ms));

function openTestChat(flowId, flowName, url) {
    tcmUrl   = url;
    tcmState = { node_id: null, vars: {} };
    document.getElementById('tcmTitle').textContent     = flowName;
    document.getElementById('tcmMessages').innerHTML    = '';
    document.getElementById('tcmDoneMsg').style.display = 'none';
    document.getElementById('tcmInput').disabled        = false;
    document.getElementById('tcmSendBtn').disabled      = false;
    document.getElementById('tcmBackdrop').classList.add('open');
    document.getElementById('tcmSidebar').classList.add('open');
    tcmCall(null);
}

function closeTestChat() {
    document.getElementById('tcmBackdrop').classList.remove('open');
    document.getElementById('tcmSidebar').classList.remove('open');
}

function tcmRestart() {
    tcmState = { node_id: null, vars: {} };
    document.getElementById('tcmMessages').innerHTML    = '';
    document.getElementById('tcmDoneMsg').style.display = 'none';
    document.getElementById('tcmInput').disabled        = false;
    document.getElementById('tcmSendBtn').disabled      = false;
    tcmCall(null);
}

function tcmSend() {
    const input = document.getElementById('tcmInput');
    const text  = input.value.trim();
    if (!text || tcmBusy) return;
    input.value = '';
    tcmAppendMessage('user', text);
    tcmCall(text);
}

async function tcmCall(message) {
    tcmBusy = true;
    document.getElementById('tcmSendBtn').disabled = true;

    // Initial network-loading indicator
    const loadingId = tcmAppendTyping();
    let data;
    try {
        const res = await fetch(tcmUrl, {
            method:  'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json' },
            body:    JSON.stringify({ message, state: tcmState }),
        });
        data = await res.json();
    } catch (e) {
        tcmRemoveTyping(loadingId);
        tcmAppendSystem('❌ Erro ao comunicar com o servidor.');
        document.getElementById('tcmSendBtn').disabled = false;
        tcmBusy = false;
        return;
    }
    tcmRemoveTyping(loadingId);

    await tcmShowMessages(data.messages ?? []);

    tcmState = data.state ?? { node_id: null, vars: {} };

    if (data.done) {
        document.getElementById('tcmDoneMsg').style.display = 'flex';
        document.getElementById('tcmInput').disabled        = true;
        document.getElementById('tcmSendBtn').disabled      = true;
    } else {
        document.getElementById('tcmSendBtn').disabled = false;
        document.getElementById('tcmInput').focus();
    }
    tcmBusy = false;
}

// Displays messages one-by-one with per-message typing animation + natural delays
async function tcmShowMessages(messages) {
    for (let i = 0; i < messages.length; i++) {
        const msg   = messages[i];
        const isBot = msg.type === 'text' || msg.type === 'image';

        if (isBot) {
            const tid = tcmAppendTyping();
            // Typing delay proportional to text length (600ms – 1800ms)
            const ms  = msg.type === 'text'
                ? Math.min(1800, Math.max(600, (msg.content?.length ?? 20) * 25))
                : 900;
            await tcmSleep(ms);
            tcmRemoveTyping(tid);
        }

        if (msg.type === 'text')   tcmAppendMessage('bot', msg.content);
        if (msg.type === 'image')  tcmAppendImage(msg.url, msg.caption);
        if (msg.type === 'system') tcmAppendSystem(msg.content);

        // Short pause between consecutive messages
        if (i < messages.length - 1) await tcmSleep(isBot ? 300 : 80);
    }
}

function tcmAppendMessage(side, text) {
    const box  = document.getElementById('tcmMessages');
    const wrap = document.createElement('div');
    wrap.className = side === 'bot' ? 'tcm-msg-bot' : 'tcm-msg-user';
    const bub  = document.createElement('div');
    bub.className   = 'tcm-bubble';
    bub.textContent = text;
    wrap.appendChild(bub);
    box.appendChild(wrap);
    box.scrollTop = box.scrollHeight;
}

function tcmAppendImage(url, caption) {
    const box  = document.getElementById('tcmMessages');
    const wrap = document.createElement('div');
    wrap.className = 'tcm-msg-bot';
    wrap.innerHTML = `<div class="tcm-img">
        <img src="${escapeHtml(url)}" alt="imagem" onerror="this.style.display='none'">
        ${caption ? `<div class="tcm-img-caption">${escapeHtml(caption)}</div>` : ''}
    </div>`;
    box.appendChild(wrap);
    box.scrollTop = box.scrollHeight;
}

function tcmAppendSystem(text) {
    const box  = document.getElementById('tcmMessages');
    const wrap = document.createElement('div');
    wrap.className = 'tcm-msg-system';
    const bub  = document.createElement('div');
    bub.className   = 'tcm-bubble';
    bub.textContent = text;
    wrap.appendChild(bub);
    box.appendChild(wrap);
    box.scrollTop = box.scrollHeight;
}

function tcmAppendTyping() {
    const id   = 'tcm-t-' + (++_typingCtr);
    const box  = document.getElementById('tcmMessages');
    const wrap = document.createElement('div');
    wrap.id        = id;
    wrap.className = 'tcm-msg-bot';
    wrap.innerHTML = '<div class="tcm-bubble tcm-typing-bubble"><span class="tcm-dot"></span><span class="tcm-dot"></span><span class="tcm-dot"></span></div>';
    box.appendChild(wrap);
    box.scrollTop = box.scrollHeight;
    return id;
}

function tcmRemoveTyping(id) {
    document.getElementById(id)?.remove();
}
</script>
@endpush

@section('content')
<div class="page-container">

    @if($flows->isEmpty())
        <div class="empty-state">
            <i class="bi bi-diagram-3"></i>
            <h3>Nenhum fluxo criado ainda</h3>
            <p>
                Crie um fluxo para atender seus contatos automaticamente no WhatsApp.<br>
                <a href="{{ route('chatbot.flows.create') }}" style="color:#3B82F6;font-weight:600;">
                    Criar primeiro fluxo →
                </a>
            </p>
        </div>
    @else
        <div class="flows-grid">
            @foreach($flows as $flow)
            <div class="flow-card">
                <div class="flow-card-body">

                    {{-- Cabeçalho do card --}}
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div class="flow-icon"><i class="bi bi-diagram-3"></i></div>
                        <div style="flex:1;min-width:0;">
                            <div class="flow-name">{{ $flow->name }}</div>
                            @if($flow->description)
                                <div class="flow-desc">{{ $flow->description }}</div>
                            @endif
                        </div>
                        <span class="{{ $flow->is_active ? 'badge-active' : 'badge-inactive' }}">
                            {{ $flow->is_active ? 'Ativo' : 'Inativo' }}
                        </span>
                    </div>

                    {{-- Meta --}}
                    <div class="flow-meta">
                        @if($flow->trigger_keywords)
                            <span><i class="bi bi-lightning-charge text-warning me-1"></i>{{ implode(', ', array_slice($flow->trigger_keywords, 0, 3)) }}{{ count($flow->trigger_keywords) > 3 ? '…' : '' }}</span>
                        @else
                            <span><i class="bi bi-hand-index me-1"></i>Manual</span>
                        @endif
                        <span><i class="bi bi-diagram-3 me-1"></i>{{ $flow->nodes()->count() }} nós</span>
                        <span title="Conversas atribuídas a este fluxo agora">
                            <i class="bi bi-person-lines-fill me-1" style="color:#8b5cf6;"></i>
                            {{ $flow->conversations_count }} {{ $flow->conversations_count === 1 ? 'lead atendido' : 'leads atendidos' }}
                        </span>
                    </div>

                    {{-- Ações --}}
                    <div class="flow-actions">
                        <a href="{{ route('chatbot.flows.edit', $flow) }}" class="btn-primary-sm" style="text-decoration:none;display:inline-flex;align-items:center;gap:5px;font-size:12px;">
                            <i class="bi bi-pencil-square"></i> Editar fluxo
                        </a>
                        <a href="{{ route('chatbot.flows.edit', $flow) }}?settings=1" class="btn-secondary-sm" style="text-decoration:none;display:inline-flex;align-items:center;gap:5px;font-size:12px;">
                            <i class="bi bi-gear"></i> Config.
                        </a>
                        <button class="btn-secondary-sm" style="display:inline-flex;align-items:center;gap:5px;font-size:12px;"
                                onclick="openTestChat({{ $flow->id }}, '{{ addslashes($flow->name) }}', '{{ route('chatbot.flows.test-step', $flow) }}')">
                            <i class="bi bi-play-circle"></i> Testar
                        </button>
                        <form method="POST" action="{{ route('chatbot.flows.destroy', $flow) }}" style="margin-left:auto;"
                              onsubmit="return confirm('Excluir o fluxo «{{ addslashes($flow->name) }}»? Esta ação não pode ser desfeita.')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-secondary-sm btn-delete-flow">
                                <i class="bi bi-trash3"></i> Excluir
                            </button>
                        </form>
                    </div>

                </div>
            </div>
            @endforeach
        </div>
    @endif

    {{-- ── Test Chat Sidebar ──────────────────────────────────────────────────── --}}
    <div id="tcmBackdrop" class="tcm-backdrop" onclick="closeTestChat()"></div>
    <div id="tcmSidebar" class="tcm-sidebar">
        <div class="tcm-header">
            <div class="tcm-header-icon"><i class="bi bi-robot"></i></div>
            <div class="tcm-header-info">
                <h3 id="tcmTitle">Testando fluxo</h3>
                <span>Simulação · nenhuma mensagem real enviada</span>
            </div>
            <button class="tcm-header-btn" onclick="closeTestChat()" title="Fechar"><i class="bi bi-x-lg"></i></button>
        </div>
        <div id="tcmMessages" class="tcm-messages"></div>
        <div class="tcm-footer">
            <div class="tcm-input-row">
                <textarea id="tcmInput" class="tcm-input" placeholder="Digite sua resposta…" rows="1"
                    onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();tcmSend();}"></textarea>
                <button id="tcmSendBtn" class="tcm-send" onclick="tcmSend()">
                    <i class="bi bi-send-fill"></i>
                </button>
            </div>
            <div class="tcm-bottom-row">
                <button class="tcm-restart" onclick="tcmRestart()">
                    <i class="bi bi-arrow-counterclockwise"></i> Reiniciar
                </button>
                <span id="tcmDoneMsg" class="tcm-done-msg" style="display:none;">
                    <i class="bi bi-check-circle"></i> Fluxo concluído
                </span>
            </div>
            <div class="tcm-hint">Enter para enviar &middot; Shift+Enter para nova linha</div>
        </div>
    </div>

</div>
@endsection

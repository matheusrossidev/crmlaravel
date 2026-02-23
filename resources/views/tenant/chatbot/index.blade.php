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

    /* ── Test Chat Modal ────────────────────────────────────────────────────── */
    .tcm-overlay {
        position: fixed; inset: 0; background: rgba(0,0,0,.45);
        z-index: 2000; display: flex; align-items: center; justify-content: center;
    }
    .tcm-box {
        background: #fff; border-radius: 16px; width: 420px; max-width: 95vw;
        display: flex; flex-direction: column; box-shadow: 0 20px 60px rgba(0,0,0,.18);
        max-height: 85vh; overflow: hidden;
    }
    .tcm-header {
        display: flex; align-items: center; gap: 10px;
        padding: 14px 18px; border-bottom: 1px solid #f0f2f7;
        flex-shrink: 0;
    }
    .tcm-header h3 { font-size: 14px; font-weight: 700; color: #1a1d23; flex: 1; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .tcm-header button { background: none; border: none; font-size: 18px; color: #9ca3af; cursor: pointer; padding: 2px 6px; border-radius: 6px; }
    .tcm-header button:hover { background: #f3f4f6; color: #374151; }

    .tcm-messages {
        flex: 1; overflow-y: auto; padding: 14px 16px;
        display: flex; flex-direction: column; gap: 8px;
        background: #f8fafc;
    }
    .tcm-msg-bot, .tcm-msg-user, .tcm-msg-system { max-width: 80%; }
    .tcm-msg-bot   { align-self: flex-start; }
    .tcm-msg-user  { align-self: flex-end; }
    .tcm-msg-system { align-self: center; max-width: 95%; }

    .tcm-bubble {
        padding: 9px 13px; border-radius: 14px;
        font-size: 13px; line-height: 1.5; word-break: break-word;
    }
    .tcm-msg-bot  .tcm-bubble { background: #fff; border: 1px solid #e8eaf0; border-bottom-left-radius: 4px; color: #1a1d23; }
    .tcm-msg-user .tcm-bubble { background: #3B82F6; color: #fff; border-bottom-right-radius: 4px; }
    .tcm-msg-system .tcm-bubble { background: #f3f4f6; color: #6b7280; font-size: 11.5px; border-radius: 8px; text-align: center; }

    .tcm-img { border-radius: 10px; overflow: hidden; max-width: 240px; }
    .tcm-img img { width: 100%; display: block; }
    .tcm-img-caption { font-size: 12px; color: #6b7280; padding: 5px 2px 0; }

    .tcm-footer {
        padding: 12px 14px; border-top: 1px solid #f0f2f7; flex-shrink: 0;
        display: flex; flex-direction: column; gap: 8px;
    }
    .tcm-input-row { display: flex; gap: 8px; }
    .tcm-input {
        flex: 1; border: 1.5px solid #e8eaf0; border-radius: 10px;
        padding: 8px 12px; font-size: 13px; outline: none;
        resize: none; height: 38px; line-height: 1.4;
    }
    .tcm-input:focus { border-color: #3B82F6; }
    .tcm-send {
        background: #3B82F6; color: #fff; border: none; border-radius: 10px;
        padding: 0 16px; font-size: 13px; font-weight: 600; cursor: pointer;
    }
    .tcm-send:hover { background: #2563eb; }
    .tcm-send:disabled { opacity: .5; cursor: default; }
    .tcm-restart {
        background: none; border: 1.5px solid #e8eaf0; border-radius: 8px;
        padding: 5px 12px; font-size: 12px; color: #6b7280; cursor: pointer;
        display: flex; align-items: center; gap: 5px; align-self: flex-start;
    }
    .tcm-restart:hover { background: #f3f4f6; }
    .tcm-done-msg { font-size: 12px; color: #6b7280; text-align: center; padding: 4px 0 0; }
</style>
@endpush

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

// ── Estado do test chat ───────────────────────────────────────────────────────
let tcmUrl   = '';
let tcmState = { node_id: null, vars: {} };
let tcmBusy  = false;

function openTestChat(flowId, flowName, url) {
    tcmUrl   = url;
    tcmState = { node_id: null, vars: {} };
    document.getElementById('tcmTitle').textContent    = flowName;
    document.getElementById('tcmMessages').innerHTML   = '';
    document.getElementById('tcmDoneMsg').style.display = 'none';
    document.getElementById('tcmInput').disabled       = false;
    document.getElementById('tcmSendBtn').disabled     = false;
    document.getElementById('tcmOverlay').style.display = 'flex';
    tcmCall(null);
}

function closeTestChat() {
    document.getElementById('tcmOverlay').style.display = 'none';
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
    const typingId = tcmAppendTyping();

    try {
        const res  = await fetch(tcmUrl, {
            method:  'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json' },
            body:    JSON.stringify({ message, state: tcmState }),
        });
        const data = await res.json();
        tcmRemoveTyping(typingId);

        for (const msg of data.messages ?? []) {
            if (msg.type === 'text')   tcmAppendMessage('bot', msg.content);
            if (msg.type === 'image')  tcmAppendImage(msg.url, msg.caption);
            if (msg.type === 'system') tcmAppendSystem(msg.content);
        }

        tcmState = data.state ?? { node_id: null, vars: {} };

        if (data.done) {
            document.getElementById('tcmDoneMsg').style.display = 'inline';
            document.getElementById('tcmInput').disabled        = true;
            document.getElementById('tcmSendBtn').disabled      = true;
        } else {
            document.getElementById('tcmSendBtn').disabled = false;
            document.getElementById('tcmInput').focus();
        }
    } catch (e) {
        tcmRemoveTyping(typingId);
        tcmAppendSystem('❌ Erro ao comunicar com o servidor.');
        document.getElementById('tcmSendBtn').disabled = false;
    }
    tcmBusy = false;
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

let _typingCtr = 0;
function tcmAppendTyping() {
    const id   = 'tcm-typing-' + (++_typingCtr);
    const box  = document.getElementById('tcmMessages');
    const wrap = document.createElement('div');
    wrap.id    = id;
    wrap.className = 'tcm-msg-bot';
    wrap.innerHTML = '<div class="tcm-bubble" style="color:#9ca3af;letter-spacing:3px;font-size:18px;">···</div>';
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

    {{-- ── Test Chat Modal ──────────────────────────────────────────────────── --}}
    <div id="tcmOverlay" class="tcm-overlay" style="display:none;" onclick="if(event.target===this)closeTestChat()">
        <div class="tcm-box">
            <div class="tcm-header">
                <i class="bi bi-robot" style="font-size:18px;color:#3B82F6;flex-shrink:0;"></i>
                <h3 id="tcmTitle">Testando fluxo</h3>
                <button onclick="closeTestChat()" title="Fechar"><i class="bi bi-x-lg"></i></button>
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
                <div style="display:flex;align-items:center;gap:10px;">
                    <button class="tcm-restart" onclick="tcmRestart()">
                        <i class="bi bi-arrow-counterclockwise"></i> Reiniciar
                    </button>
                    <span id="tcmDoneMsg" class="tcm-done-msg" style="display:none;">
                        Fluxo concluído.
                    </span>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

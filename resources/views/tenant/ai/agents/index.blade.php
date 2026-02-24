@extends('tenant.layouts.app')

@php
    $title    = 'Agente de IA';
    $pageIcon = 'robot';
@endphp

@section('topbar_actions')
<div class="topbar-actions">
    <a href="{{ route('ai.agents.create') }}" class="btn-primary-sm" style="text-decoration:none;display:flex;align-items:center;gap:6px;">
        <i class="bi bi-plus-lg"></i> Novo Agente
    </a>
</div>
@endsection

@push('styles')
<style>
    .agents-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 12px;
    }

    .agent-card {
        background: #fff;
        border-radius: 14px;
        border: 1px solid #e8eaf0;
        overflow: hidden;
        transition: box-shadow .15s;
    }
    .agent-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,.07); }

    .agent-card-body {
        padding: 18px 20px;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .agent-card-top {
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }

    .agent-icon {
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

    .agent-name {
        font-size: 14px;
        font-weight: 700;
        color: #1a1d23;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .agent-meta { font-size: 12px; color: #9ca3af; }

    .agent-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }
    .badge {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 2px 9px; border-radius: 99px;
        font-size: 11px; font-weight: 700; white-space: nowrap;
    }
    .badge-active   { background: #d1fae5; color: #065f46; }
    .badge-inactive { background: #f3f4f6; color: #6b7280; }

    .agent-desc {
        font-size: 12px;
        color: #9ca3af;
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }

    .agent-meta-row {
        display: flex;
        gap: 14px;
        font-size: 11px;
        color: #6b7280;
    }

    .agent-actions {
        display: flex;
        gap: 6px;
        padding-top: 12px;
        border-top: 1px solid #f0f2f7;
        margin-top: auto;
    }

    .btn-delete-agent:hover { background: #fee2e2 !important; color: #ef4444 !important; }

    .empty-state {
        text-align: center; padding: 80px 20px; color: #9ca3af;
    }
    .empty-state i  { font-size: 52px; opacity: .2; margin-bottom: 14px; display: block; }
    .empty-state h3 { font-size: 16px; color: #374151; margin: 0 0 6px; }
    .empty-state p  { font-size: 13.5px; margin: 0 0 20px; }

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
    .tcm-msg-bot    { align-self: flex-start; }
    .tcm-msg-user   { align-self: flex-end; }
    .tcm-msg-system { align-self: center; max-width: 96%; }

    .tcm-bubble {
        padding: 9px 13px; border-radius: 16px;
        font-size: 13px; line-height: 1.55; word-break: break-word;
        white-space: pre-wrap;
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

    .tcm-footer {
        padding: 12px 14px 16px; border-top: 1px solid #f0f2f7; flex-shrink: 0;
        display: flex; flex-direction: column; gap: 9px; background: #fff;
    }
    .tcm-input-row { display: flex; gap: 7px; }
    .tcm-input {
        flex: 1; border: 1.5px solid #e8eaf0; border-radius: 12px;
        padding: 9px 12px; font-size: 13px; outline: none;
        resize: none; min-height: 38px; max-height: 90px; line-height: 1.45;
        transition: border-color .15s; font-family: inherit;
    }
    .tcm-input:focus { border-color: #3B82F6; }
    .tcm-send {
        background: #3B82F6; color: #fff; border: none; border-radius: 12px;
        width: 40px; height: 38px; display: flex; align-items: center; justify-content: center;
        cursor: pointer; font-size: 16px; flex-shrink: 0; transition: background .15s;
    }
    .tcm-send:hover { background: #2563eb; }
    .tcm-send:disabled { opacity: .5; cursor: not-allowed; }

    .tcm-reset {
        background: none; border: none; color: #9ca3af;
        font-size: 11.5px; cursor: pointer; padding: 0; text-align: left;
        width: fit-content; transition: color .15s;
    }
    .tcm-reset:hover { color: #ef4444; }
    /* ── Delete Confirmation Modal ── */
    .del-modal-overlay {
        display: none; position: fixed; inset: 0;
        background: rgba(0,0,0,.45); z-index: 9999;
        align-items: center; justify-content: center;
    }
    .del-modal-overlay.open { display: flex; }
    .del-modal {
        background: #fff; border-radius: 14px; padding: 28px;
        width: 400px; max-width: 94vw;
        box-shadow: 0 20px 60px rgba(0,0,0,.18);
        text-align: center;
    }
    .del-modal-icon { font-size: 36px; color: #EF4444; margin-bottom: 12px; }
    .del-modal-title { font-size: 16px; font-weight: 700; color: #1a1d23; margin-bottom: 8px; }
    .del-modal-text { font-size: 13.5px; color: #6b7280; margin-bottom: 24px; line-height: 1.5; }
    .del-modal-footer { display: flex; justify-content: center; gap: 10px; }
    .btn-del-cancel {
        padding: 9px 22px; border-radius: 9px; font-size: 13.5px; font-weight: 600;
        border: 1.5px solid #e8eaf0; background: #f4f6fb; color: #4b5563; cursor: pointer;
    }
    .btn-del-cancel:hover { background: #e8eaf0; }
    .btn-del-confirm {
        padding: 9px 22px; border-radius: 9px; font-size: 13.5px; font-weight: 600;
        border: none; background: #EF4444; color: #fff; cursor: pointer;
    }
    .btn-del-confirm:hover { background: #dc2626; }
</style>
@endpush

@section('content')
<div class="page-container">

    @if($agents->isEmpty())
    <div class="empty-state">
        <i class="bi bi-robot"></i>
        <h3>Nenhum agente criado ainda</h3>
        <p>
            Crie um agente de IA para responder automaticamente nos seus chats.<br>
            <a href="{{ route('ai.agents.create') }}" style="color:#3B82F6;font-weight:600;">
                Criar primeiro agente →
            </a>
        </p>
    </div>
    @else
    <div class="agents-grid">
        @foreach($agents as $agent)
        @php
            $objLabel = ['sales' => 'Vendas', 'support' => 'Suporte', 'general' => 'Geral'][$agent->objective] ?? $agent->objective;
            $chLabel  = $agent->channel === 'whatsapp' ? 'WhatsApp' : 'Web Chat';
            $chIcon   = $agent->channel === 'whatsapp' ? 'whatsapp' : 'chat-dots';
        @endphp
        <div class="agent-card">
            <div class="agent-card-body">
                <div class="agent-card-top">
                    <div class="agent-icon">
                        <i class="bi bi-robot"></i>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div class="agent-name">{{ $agent->name }}</div>
                        @if($agent->company_name)
                        <div class="agent-meta">{{ $agent->company_name }}</div>
                        @endif
                    </div>
                </div>

                <div class="agent-badges">
                    <span class="badge {{ $agent->is_active ? 'badge-active' : 'badge-inactive' }}">
                        <i class="bi bi-circle-fill" style="font-size:7px;"></i>
                        {{ $agent->is_active ? 'Ativo' : 'Inativo' }}
                    </span>
                    @if($agent->auto_assign)
                    <span class="badge" style="background:#eff6ff;color:#2563eb;">
                        <i class="bi bi-lightning-fill"></i> Auto-assign
                    </span>
                    @endif
                </div>

                <div class="agent-meta-row">
                    <span><i class="bi bi-{{ $chIcon }}"></i> {{ $chLabel }}</span>
                    <span><i class="bi bi-tag"></i> {{ $objLabel }}</span>
                    <span><i class="bi bi-person-lines-fill"></i> {{ $agent->conversations_count }} {{ $agent->conversations_count === 1 ? 'conversa' : 'conversas' }}</span>
                </div>

                @if($agent->persona_description)
                <div class="agent-desc">{{ $agent->persona_description }}</div>
                @endif

                <div class="agent-actions">
                    <a href="{{ route('ai.agents.edit', $agent) }}"
                       class="btn btn-sm btn-light"
                       style="display:inline-flex;align-items:center;gap:5px;font-size:12px;padding:8px 16px;border-radius:9px;text-decoration:none;color:#374151;">
                        <i class="bi bi-pencil"></i> Editar
                    </a>
                    <button class="btn btn-sm btn-light"
                            onclick="openTestChat({{ $agent->id }}, '{{ addslashes($agent->name) }}')"
                            style="display:inline-flex;align-items:center;gap:5px;font-size:12px;padding:8px 16px;border-radius:9px;">
                        <i class="bi bi-chat-dots"></i> Testar
                    </button>
                    <button class="btn btn-sm btn-light"
                            onclick="toggleActive({{ $agent->id }}, {{ $agent->is_active ? 'true' : 'false' }}, this)"
                            style="display:inline-flex;align-items:center;gap:5px;font-size:12px;padding:8px 16px;border-radius:9px;">
                        <i class="bi bi-{{ $agent->is_active ? 'pause' : 'play' }}"></i>
                        {{ $agent->is_active ? 'Pausar' : 'Ativar' }}
                    </button>
                    <button class="btn btn-sm btn-light btn-delete-agent"
                            onclick="deleteAgent({{ $agent->id }}, this)"
                            style="display:inline-flex;align-items:center;gap:5px;font-size:12px;padding:8px 16px;border-radius:9px;color:#9ca3af;">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

</div>

{{-- Test Chat Sidebar --}}
<div class="tcm-backdrop" id="tcmBackdrop" onclick="closeTestChat()"></div>
<div class="tcm-sidebar" id="tcmSidebar">
    <div class="tcm-header">
        <div class="tcm-header-icon"><i class="bi bi-robot"></i></div>
        <div class="tcm-header-info">
            <h3 id="tcmAgentName">Agente</h3>
            <span>Teste de conversa simulada</span>
        </div>
        <button class="tcm-header-btn" onclick="closeTestChat()" title="Fechar"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="tcm-messages" id="tcmMessages"></div>
    <div class="tcm-footer">
        <div class="tcm-input-row">
            <textarea class="tcm-input" id="tcmInput" rows="1"
                      placeholder="Digite uma mensagem..."
                      onkeydown="tcmKeyDown(event)"
                      oninput="this.style.height='auto';this.style.height=Math.min(this.scrollHeight,90)+'px'"></textarea>
            <button class="tcm-send" id="tcmSendBtn" onclick="tcmSend()" title="Enviar">
                <i class="bi bi-send-fill"></i>
            </button>
        </div>
        <button class="tcm-reset" onclick="tcmReset()"><i class="bi bi-arrow-counterclockwise"></i> Reiniciar conversa</button>
    </div>
</div>

{{-- Modal: confirmar exclusão de agente --}}
<div class="del-modal-overlay" id="delAgentModal">
    <div class="del-modal">
        <div class="del-modal-icon"><i class="bi bi-trash3-fill"></i></div>
        <div class="del-modal-title">Excluir agente?</div>
        <div class="del-modal-text">O agente será removido permanentemente.<br>Esta ação não pode ser desfeita.</div>
        <div class="del-modal-footer">
            <button class="btn-del-cancel" onclick="document.getElementById('delAgentModal').classList.remove('open')">Cancelar</button>
            <button class="btn-del-confirm" onclick="_doDeleteAgent()">Excluir</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
let tcmAgentId = null;
let tcmHistory = [];
let tcmBusy    = false;

/* ── Open / Close ── */
function openTestChat(agentId, agentName) {
    tcmAgentId = agentId;
    document.getElementById('tcmAgentName').textContent = agentName;
    tcmReset();
    document.getElementById('tcmBackdrop').classList.add('open');
    document.getElementById('tcmSidebar').classList.add('open');
    setTimeout(() => document.getElementById('tcmInput').focus(), 350);
}
function closeTestChat() {
    document.getElementById('tcmBackdrop').classList.remove('open');
    document.getElementById('tcmSidebar').classList.remove('open');
}

/* ── Messages ── */
function tcmAddMsg(role, text) {
    const msgs   = document.getElementById('tcmMessages');
    const wrap   = document.createElement('div');
    wrap.className = role === 'user' ? 'tcm-msg-user' : role === 'system' ? 'tcm-msg-system' : 'tcm-msg-bot';
    const bubble = document.createElement('div');
    bubble.className = 'tcm-bubble';
    bubble.textContent = text;
    wrap.appendChild(bubble);
    msgs.appendChild(wrap);
    msgs.scrollTop = msgs.scrollHeight;
    return wrap;
}

function tcmAddTyping() {
    const msgs = document.getElementById('tcmMessages');
    const wrap = document.createElement('div');
    wrap.className = 'tcm-msg-bot';
    wrap.innerHTML = '<div class="tcm-bubble tcm-typing-bubble"><span class="tcm-dot"></span><span class="tcm-dot"></span><span class="tcm-dot"></span></div>';
    msgs.appendChild(wrap);
    msgs.scrollTop = msgs.scrollHeight;
    return wrap;
}

/* ── Send ── */
function tcmKeyDown(e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); tcmSend(); }
}

async function tcmSend() {
    const input = document.getElementById('tcmInput');
    const msg   = input.value.trim();
    if (!msg || !tcmAgentId || tcmBusy) return;

    input.value = '';
    input.style.height = 'auto';
    tcmBusy = true;
    document.getElementById('tcmSendBtn').disabled = true;

    tcmAddMsg('user', msg);
    const typing = tcmAddTyping();

    try {
        const res  = await fetch(`/ia/agentes/${tcmAgentId}/test-chat`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ message: msg, history: tcmHistory }),
        });
        const data = await res.json();
        typing.remove();

        if (data.success) {
            tcmAddMsg('bot', data.reply);
            tcmHistory.push({ role: 'user',  content: msg });
            tcmHistory.push({ role: 'agent', content: data.reply });
            if (tcmHistory.length > 40) tcmHistory = tcmHistory.slice(-40);
        } else {
            tcmAddMsg('system', '⚠ Erro: ' + (data.message || 'Falha ao obter resposta.'));
        }
    } catch (e) {
        typing.remove();
        tcmAddMsg('system', '⚠ Erro de conexão.');
    } finally {
        tcmBusy = false;
        document.getElementById('tcmSendBtn').disabled = false;
        input.focus();
    }
}

function tcmReset() {
    tcmHistory = [];
    document.getElementById('tcmMessages').innerHTML = '';
    tcmAddMsg('system', 'Conversa reiniciada. Digite uma mensagem para começar.');
}

/* ── Card actions ── */
async function toggleActive(id, isActive, btn) {
    const res  = await fetch(`/ia/agentes/${id}/toggle`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json' },
    });
    const data = await res.json();
    if (!data.success) { toastr.error('Erro.'); return; }
    location.reload();
}

let _deleteAgentId  = null;
let _deleteAgentBtn = null;

function deleteAgent(id, btn) {
    _deleteAgentId  = id;
    _deleteAgentBtn = btn;
    document.getElementById('delAgentModal').classList.add('open');
}

async function _doDeleteAgent() {
    document.getElementById('delAgentModal').classList.remove('open');
    if (!_deleteAgentId) return;
    const res  = await fetch(`/ia/agentes/${_deleteAgentId}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF },
    });
    const data = await res.json();
    if (!data.success) { toastr.error('Erro ao excluir.'); return; }
    _deleteAgentBtn?.closest('.agent-card')?.remove();
    toastr.success('Agente excluído.');
}
</script>
@endpush

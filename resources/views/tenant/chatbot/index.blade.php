@extends('tenant.layouts.app')

@php
    $title    = __('chatbot.page_title');
    $pageIcon = 'diagram-3';
@endphp

{{-- topbar_actions removido — botão movido para page header --}}

@push('styles')
<style>
    .flows-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 16px;
    }

    .flow-dropdown.show { display: block !important; }
    .flow-card {
        background: #fff;
        border-radius: 14px;
        border: 1px solid #e8eaf0;
        overflow: visible;
        transition: box-shadow .15s;
    }
    .flow-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,.07); }

    .flow-card-body {
        padding: 18px 20px;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .flow-card-top {
        display: flex;
        align-items: flex-start;
        gap: 12px;
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
    .flow-subtitle { font-size: 12px; color: #9ca3af; }

    .flow-badges {
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

    .flow-desc {
        font-size: 12px;
        color: #9ca3af;
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }

    .flow-meta-row {
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

    .btn-delete-flow:hover { background: #fee2e2 !important; color: #ef4444 !important; }

    .empty-state {
        text-align: center;
        padding: 80px 20px;
        color: #9ca3af;
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

    /* ── FAB mobile ── */
    .chatbot-fab {
        display: none;
        position: fixed;
        bottom: 24px;
        right: 24px;
        width: 52px;
        height: 52px;
        background: #0085f3;
        color: #fff;
        border: none;
        border-radius: 50%;
        font-size: 22px;
        cursor: pointer;
        z-index: 100;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 16px rgba(0,133,243,.35);
        transition: transform .15s, box-shadow .15s;
        text-decoration: none;
    }
    .chatbot-fab:hover { transform: scale(1.06); box-shadow: 0 6px 24px rgba(0,133,243,.45); }

    /* ── Mobile ── */
    @media (max-width: 768px) {
        .topbar-actions .btn-primary-sm { display: none !important; }
        .chatbot-fab { display: flex; }
        .flow-actions {
            overflow-x: auto; -webkit-overflow-scrolling: touch;
            padding-bottom: 4px;
            -webkit-mask-image: linear-gradient(to right, black 90%, transparent 100%);
            mask-image: linear-gradient(to right, black 90%, transparent 100%);
        }
        .flow-actions .btn,
        .flow-actions button,
        .flow-actions a { flex-shrink: 0; white-space: nowrap; }
    }
</style>
@endpush

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
const CBLANG = @json(__('chatbot'));

// ── Fechar dropdowns ao clicar fora ──────────────────────────────────────────
document.addEventListener('click', function(e) {
    if (!e.target.closest('.flow-dropdown') && !e.target.closest('[onclick*="classList.toggle"]')) {
        document.querySelectorAll('.flow-dropdown.show').forEach(function(d) { d.classList.remove('show'); });
    }
});

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
        tcmAppendSystem(CBLANG.test_server_error);
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

/* ── Toggle flow active ── */
function toggleFlowActive(flowId, currentlyActive, btn) {
    btn.disabled = true;
    fetch('{{ url("chatbot/fluxos") }}/' + flowId + '/toggle', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
    })
    .then(r => r.json())
    .then(data => {
        // Reload to reflect updated state
        window.location.reload();
    })
    .catch(() => {
        btn.disabled = false;
        if (typeof toastr !== 'undefined') toastr.error(CBLANG.toast_toggle_error);
    });
}

/* ── Delete flow modal ── */
let _deleteFlowForm = null;

function openFlowDeleteModal(form, name) {
    _deleteFlowForm = form;
    document.getElementById('delFlowText').innerHTML = CBLANG.delete_modal_text.replace(':name', name);
    document.getElementById('delFlowModal').classList.add('open');
}

function _doDeleteFlow() {
    document.getElementById('delFlowModal').classList.remove('open');
    if (_deleteFlowForm) _deleteFlowForm.submit();
}

/* ── Embed modal ── */
function openEmbedModal(scriptUrl, widgetType, publicUrl) {
    var scriptTag = '<script src="' + scriptUrl + '"></' + 'script>';
    var el = document.getElementById('idxEmbedCode');
    var instructions = document.getElementById('idxEmbedInstructions');
    var divHint = document.getElementById('idxEmbedDivHint');
    var linkSection = document.getElementById('idxPublicLinkSection');
    var linkInput = document.getElementById('idxPublicLinkUrl');

    if (publicUrl) {
        linkSection.style.display = 'block';
        linkInput.value = publicUrl;
    } else {
        linkSection.style.display = 'none';
    }

    if (widgetType === 'inline' || widgetType === 'page') {
        var divCode = '<div id="syncro-chat" style="width:100%;height:100vh;"></div>';
        el.value = divCode + '\n' + scriptTag;
        el.rows = 4;
        instructions.innerHTML = widgetType === 'page'
            ? CBLANG.embed_modal_paste_fullpage
            : CBLANG.embed_modal_paste_inline;
        divHint.style.display = 'block';
        divHint.innerHTML = widgetType === 'page'
            ? CBLANG.embed_modal_hint_fullpage
            : CBLANG.embed_modal_hint_inline;
    } else {
        el.value = scriptTag;
        el.rows = 3;
        instructions.innerHTML = CBLANG.embed_modal_bubble_hint;
        divHint.style.display = 'none';
    }

    document.getElementById('idxEmbedModal').style.display = 'flex';
}

function copyFlowLink(url) {
    navigator.clipboard.writeText(url).then(function() {
        if (typeof toastr !== 'undefined') toastr.success(CBLANG.toast_link_copied);
    });
}

function copyPublicLink() {
    var input = document.getElementById('idxPublicLinkUrl');
    navigator.clipboard.writeText(input.value.trim()).then(function() {
        var msg = document.getElementById('idxPublicLinkCopied');
        msg.style.display = 'inline-flex';
        setTimeout(function() { msg.style.display = 'none'; }, 2500);
    });
}

function copyIdxEmbed() {
    var textarea = document.getElementById('idxEmbedCode');
    navigator.clipboard.writeText(textarea.value.trim()).then(function() {
        var msg = document.getElementById('idxEmbedCopied');
        msg.style.display = 'inline-flex';
        setTimeout(function() { msg.style.display = 'none'; }, 2500);
    });
}

/* ── Widget test ── */
var _widgetTestActive = false;

function openWidgetTest(token) {
    if (_widgetTestActive) {
        closeWidgetTest();
        return;
    }
    // Limpar visitor ID para conversa nova
    localStorage.removeItem('syncro_vid_' + token);

    var s = document.createElement('script');
    s.id = 'syncro-test-widget';
    s.src = '{{ config("app.url") }}/api/widget/' + token + '.js?' + Date.now() + '&force_bubble=1';
    document.body.appendChild(s);
    _widgetTestActive = true;
}

function closeWidgetTest() {
    ['syncro-launcher', 'syncro-panel', 'syncro-welcome', 'syncro-test-widget'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) el.remove();
    });
    document.querySelectorAll('style').forEach(function(s) {
        if (s.textContent && s.textContent.indexOf('#syncro-launcher') !== -1) s.remove();
    });
    _widgetTestActive = false;
}
</script>
@endpush

@section('content')
<div class="page-container">

    <div style="margin-bottom:20px;">
        <div style="font-size:11px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:#97A3B7;margin-bottom:4px;">{{ __('nav.automation') ?? 'AUTOMAÇÃO' }}</div>
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
            <div>
                <h1 style="font-family:'Plus Jakarta Sans',sans-serif;font-size:22px;font-weight:700;color:#1a1d23;margin:0 0 4px;">{{ __('chatbot.page_title') }}</h1>
                <p style="font-size:13.5px;color:#677489;margin:0;">{{ __('chatbot.index_subtitle') }}</p>
            </div>
            <a href="{{ route('chatbot.flows.onboarding') }}" class="btn-primary-sm" style="text-decoration:none;">
                <i class="bi bi-plus-lg"></i> {{ __('chatbot.new_flow') }}
            </a>
        </div>
    </div>

    @if($flows->isEmpty())
        <div class="empty-state">
            <i class="bi bi-diagram-3"></i>
            <h3>{{ __('chatbot.empty_title') }}</h3>
            <p>
                {{ __('chatbot.empty_description') }}<br>
                <a href="{{ route('chatbot.flows.onboarding') }}" style="color:#3B82F6;font-weight:600;">
                    {{ __('chatbot.empty_cta') }}
                </a>
            </p>
        </div>
    @else
        <div class="flows-grid">
            @foreach($flows as $flow)
            @php
                $nodesCount = $flow->steps_node_count;
                $leadsCount = $flow->conversations_count + $flow->website_conversations_count;
                $channelIcon = $flow->channel === 'website' ? 'globe2' : ($flow->channel === 'instagram' ? 'instagram' : 'whatsapp');
                $channelLabel = $flow->channel === 'website' ? 'Web' : ($flow->channel === 'instagram' ? 'IG' : 'WA');
            @endphp
            <div class="flow-card" style="padding:18px 22px;display:flex;flex-direction:column;gap:14px;">
                {{-- Header: name + toggle + menu --}}
                <div style="display:flex;align-items:flex-start;gap:10px;">
                    <a href="{{ route('chatbot.flows.edit', $flow) }}" style="flex:1;min-width:0;text-decoration:none;color:inherit;">
                        <div class="flow-name" style="font-size:15px;margin-bottom:2px;">{{ $flow->name }}</div>
                        <div style="font-size:11.5px;color:#9ca3af;">{{ __('chatbot.last_edit') }} {{ $flow->updated_at?->diffForHumans() }}</div>
                    </a>

                    {{-- Toggle --}}
                    <label style="position:relative;display:inline-block;width:40px;height:22px;flex-shrink:0;cursor:pointer;" title="{{ $flow->is_active ? __('chatbot.deactivate') : __('chatbot.activate') }}">
                        <input type="checkbox" {{ $flow->is_active ? 'checked' : '' }}
                               onchange="toggleFlowActive({{ $flow->id }}, {{ $flow->is_active ? 'true' : 'false' }}, this)"
                               style="opacity:0;width:0;height:0;">
                        <span style="position:absolute;inset:0;border-radius:99px;transition:all .2s;{{ $flow->is_active ? 'background:#10b981;' : 'background:#d1d5db;' }}"></span>
                        <span style="position:absolute;top:2px;{{ $flow->is_active ? 'left:20px;' : 'left:2px;' }}width:18px;height:18px;border-radius:50%;background:#fff;transition:all .2s;box-shadow:0 1px 3px rgba(0,0,0,.15);"></span>
                    </label>

                    {{-- Menu 3 pontinhos --}}
                    <div style="position:relative;">
                        <button onclick="this.nextElementSibling.classList.toggle('show')" style="width:32px;height:32px;border-radius:8px;border:1px solid #e8eaf0;background:#fff;color:#6b7280;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:16px;">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <div class="flow-dropdown" style="display:none;position:absolute;right:0;top:36px;background:#fff;border:1px solid #e8eaf0;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.12);min-width:180px;z-index:10;padding:6px 0;">
                            <a href="{{ route('chatbot.flows.edit', $flow) }}" style="display:flex;align-items:center;gap:8px;padding:8px 14px;font-size:13px;color:#374151;text-decoration:none;transition:background .1s;font-weight:500;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background=''">
                                <i class="bi bi-pencil" style="font-size:12px;color:#6b7280;"></i> {{ __('chatbot.dropdown_edit') }}
                            </a>
                            <a href="{{ route('chatbot.flows.results', $flow) }}" style="display:flex;align-items:center;gap:8px;padding:8px 14px;font-size:13px;color:#374151;text-decoration:none;transition:background .1s;font-weight:500;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background=''">
                                <i class="bi bi-bar-chart-line" style="font-size:12px;color:#6b7280;"></i> {{ __('chatbot.dropdown_results') }}
                            </a>
                            @if($flow->website_token)
                            <button onclick="openWidgetTest('{{ $flow->website_token }}');this.closest('.flow-dropdown').classList.remove('show')" style="display:flex;align-items:center;gap:8px;padding:8px 14px;font-size:13px;color:#374151;background:none;border:none;width:100%;text-align:left;cursor:pointer;font-weight:500;font-family:inherit;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background=''">
                                <i class="bi bi-play-circle" style="font-size:12px;color:#6b7280;"></i> {{ __('chatbot.dropdown_test') }}
                            </button>
                            @if($flow->slug)
                            <button onclick="copyFlowLink('{{ config('app.url') }}/chat/{{ auth()->user()->tenant->slug }}/{{ $flow->slug }}');this.closest('.flow-dropdown').classList.remove('show')" style="display:flex;align-items:center;gap:8px;padding:8px 14px;font-size:13px;color:#374151;background:none;border:none;width:100%;text-align:left;cursor:pointer;font-weight:500;font-family:inherit;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background=''">
                                <i class="bi bi-link-45deg" style="font-size:12px;color:#6b7280;"></i> {{ __('chatbot.dropdown_link') }}
                            </button>
                            @endif
                            <button onclick="openEmbedModal('{{ config('app.url') }}/api/widget/{{ $flow->website_token }}.js', '{{ $flow->widget_type ?? 'bubble' }}', '{{ $flow->slug ? config('app.url') . '/chat/' . auth()->user()->tenant->slug . '/' . $flow->slug : '' }}');this.closest('.flow-dropdown').classList.remove('show')" style="display:flex;align-items:center;gap:8px;padding:8px 14px;font-size:13px;color:#374151;background:none;border:none;width:100%;text-align:left;cursor:pointer;font-weight:500;font-family:inherit;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background=''">
                                <i class="bi bi-code-slash" style="font-size:12px;color:#6b7280;"></i> {{ __('chatbot.dropdown_embed') }}
                            </button>
                            @endif
                            <div style="height:1px;background:#f0f2f7;margin:4px 0;"></div>
                            <form method="POST" action="{{ route('chatbot.flows.destroy', $flow) }}">
                                @csrf @method('DELETE')
                                <button type="button" onclick="openFlowDeleteModal(this.closest('form'), '{{ addslashes($flow->name) }}')" style="display:flex;align-items:center;gap:8px;padding:8px 14px;font-size:13px;color:#ef4444;background:none;border:none;width:100%;text-align:left;cursor:pointer;font-weight:500;font-family:inherit;" onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background=''">
                                    <i class="bi bi-trash3" style="font-size:12px;"></i> {{ __('chatbot.dropdown_delete') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Badges --}}
                <div style="display:flex;flex-wrap:wrap;gap:6px;">
                    <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:99px;font-size:11px;font-weight:600;{{ $flow->is_active ? 'background:#d1fae5;color:#065f46;' : 'background:#f3f4f6;color:#6b7280;' }}">
                        <i class="bi bi-circle-fill" style="font-size:6px;"></i> {{ $flow->is_active ? __('chatbot.badge_active') : __('chatbot.badge_inactive') }}
                    </span>
                    <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:99px;font-size:11px;font-weight:600;background:#eff6ff;color:#2563eb;">
                        <i class="bi bi-{{ $channelIcon }}"></i> {{ $flow->channel === 'website' ? 'Website' : ($flow->channel === 'instagram' ? 'Instagram' : 'WhatsApp') }}
                    </span>
                    @if($flow->is_catch_all)
                    <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:99px;font-size:11px;font-weight:600;background:#fef3c7;color:#92400e;">
                        <i class="bi bi-star-fill" style="font-size:8px;"></i> {{ __('chatbot.badge_catch_all') }}
                    </span>
                    @endif
                </div>

                {{-- Métricas --}}
                <div style="display:flex;gap:16px;font-size:12px;color:#6b7280;">
                    <span><i class="bi bi-diagram-3" style="margin-right:3px;"></i> {{ $nodesCount }} {{ $nodesCount === 1 ? __('chatbot.nodes_singular') : __('chatbot.nodes_plural') }}</span>
                    <span><i class="bi bi-people" style="margin-right:3px;"></i> {{ $leadsCount }} {{ __('chatbot.attended') }}</span>
                </div>

                {{-- Footer: criador + data --}}
                <div style="display:flex;justify-content:space-between;align-items:center;padding-top:10px;border-top:1px solid #f0f2f7;font-size:11px;color:#9ca3af;">
                    <span>{{ __('chatbot.created') }} {{ $flow->created_at?->diffForHumans() }}</span>
                    <span>{{ $flow->created_at?->format('d/m/Y') }}</span>
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
                <h3 id="tcmTitle">{{ __('chatbot.test_title') }}</h3>
                <span>{{ __('chatbot.test_subtitle') }}</span>
            </div>
            <button class="tcm-header-btn" onclick="closeTestChat()" title=""><i class="bi bi-x-lg"></i></button>
        </div>
        <div id="tcmMessages" class="tcm-messages"></div>
        <div class="tcm-footer">
            <div class="tcm-input-row">
                <textarea id="tcmInput" class="tcm-input" placeholder="{{ __('chatbot.test_input_placeholder') }}" rows="1"
                    onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();tcmSend();}"></textarea>
                <button id="tcmSendBtn" class="tcm-send" onclick="tcmSend()">
                    <i class="bi bi-send-fill"></i>
                </button>
            </div>
            <div class="tcm-bottom-row">
                <button class="tcm-restart" onclick="tcmRestart()">
                    <i class="bi bi-arrow-counterclockwise"></i> {{ __('chatbot.test_restart') }}
                </button>
                <span id="tcmDoneMsg" class="tcm-done-msg" style="display:none;">
                    <i class="bi bi-check-circle"></i> {{ __('chatbot.test_done') }}
                </span>
            </div>
            <div class="tcm-hint">{{ __('chatbot.test_hint') }}</div>
        </div>
    </div>

</div>

{{-- Modal: Embed code --}}
<div id="idxEmbedModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9999;align-items:center;justify-content:center;" onclick="if(event.target===this)this.style.display='none'">
    <div style="background:#fff;border-radius:14px;padding:28px 32px;width:520px;max-width:94vw;box-shadow:0 20px 60px rgba(0,0,0,.18);">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
            <h3 style="font-size:16px;font-weight:700;color:#1a1d23;margin:0;">{{ __('chatbot.embed_modal_title') }}</h3>
            <button onclick="document.getElementById('idxEmbedModal').style.display='none'" style="background:none;border:none;font-size:20px;color:#9ca3af;cursor:pointer;padding:4px;">&times;</button>
        </div>

        {{-- Link público --}}
        <div id="idxPublicLinkSection" style="display:none;margin-bottom:18px;padding:14px 16px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;">
            <div style="font-size:12px;font-weight:700;color:#16a34a;margin-bottom:8px;text-transform:uppercase;letter-spacing:.05em;">
                <i class="bi bi-link-45deg"></i> {{ __('chatbot.embed_public_link') }}
            </div>
            <div style="display:flex;gap:8px;align-items:center;">
                <input type="text" id="idxPublicLinkUrl" readonly style="flex:1;border:1.5px solid #e8eaf0;border-radius:8px;padding:8px 12px;font-family:monospace;font-size:12px;color:#374151;background:#fff;">
                <button onclick="copyPublicLink()" style="background:#16a34a;color:#fff;border:none;border-radius:8px;padding:8px 14px;font-size:12px;font-weight:600;cursor:pointer;white-space:nowrap;">
                    <i class="bi bi-clipboard"></i> {{ __('chatbot.embed_copy') }}
                </button>
            </div>
            <span id="idxPublicLinkCopied" style="font-size:11px;color:#16a34a;font-weight:600;display:none;margin-top:4px;"><i class="bi bi-check-circle"></i> {{ __('chatbot.embed_public_link_copied') }}</span>
            <div style="font-size:11px;color:#6b7280;margin-top:6px;">{{ __('chatbot.embed_public_link_hint') }}</div>
        </div>

        <div style="font-size:12px;font-weight:700;color:#9ca3af;margin-bottom:8px;text-transform:uppercase;letter-spacing:.05em;">
            <i class="bi bi-code-slash"></i> {{ __('chatbot.embed_code_label') }}
        </div>
        <p id="idxEmbedInstructions" style="font-size:13.5px;color:#6b7280;margin:0 0 14px;">{!! __('chatbot.embed_modal_paste_before') !!}</p>
        <textarea id="idxEmbedCode" readonly rows="3" style="width:100%;border:1.5px solid #e8eaf0;border-radius:9px;padding:12px;font-family:monospace;font-size:12.5px;color:#374151;background:#f8fafc;resize:none;"></textarea>
        <div id="idxEmbedDivHint" style="display:none;margin-top:10px;padding:10px 14px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;font-size:12px;color:#1e40af;line-height:1.5;"></div>
        <div style="display:flex;align-items:center;gap:10px;margin-top:14px;">
            <button onclick="copyIdxEmbed()" style="background:#0085f3;color:#fff;border:none;border-radius:9px;padding:9px 20px;font-size:13px;font-weight:600;cursor:pointer;">
                <i class="bi bi-clipboard"></i> {{ __('chatbot.embed_copy_button') }}
            </button>
            <span id="idxEmbedCopied" style="font-size:12px;color:#16a34a;font-weight:600;display:none;"><i class="bi bi-check-circle"></i> {{ __('chatbot.embed_copied') }}</span>
        </div>
    </div>
</div>

{{-- Modal: confirmar exclusão de fluxo --}}
<div class="del-modal-overlay" id="delFlowModal">
    <div class="del-modal">
        <div class="del-modal-icon"><i class="bi bi-trash3-fill"></i></div>
        <div class="del-modal-title">{{ __('chatbot.delete_modal_title') }}</div>
        <div class="del-modal-text" id="delFlowText"></div>
        <div class="del-modal-footer">
            <button class="btn-del-cancel" onclick="document.getElementById('delFlowModal').classList.remove('open')">{{ __('chatbot.delete_modal_cancel') }}</button>
            <button class="btn-del-confirm" onclick="_doDeleteFlow()">{{ __('chatbot.delete_modal_confirm') }}</button>
        </div>
    </div>
</div>

<a href="{{ route('chatbot.flows.onboarding') }}" class="chatbot-fab" aria-label="{{ __('chatbot.new_flow') }}">
    <i class="bi bi-plus-lg"></i>
</a>
@endsection

@extends('tenant.layouts.app')

@php
    $title    = 'Chatbot';
    $pageIcon = 'robot';

    $pipelinesJs = $pipelines->map(fn($p) => [
        'id'     => $p->id,
        'name'   => $p->name,
        'stages' => $p->stages->map(fn($s) => ['id' => $s->id, 'name' => $s->name])->values(),
    ])->values();
@endphp

@push('styles')
<style>
/* ── Page layout ──────────────────────────────────────────────────── */
.cb-page { display: flex; flex-direction: column; height: calc(100vh - 64px); overflow: hidden; }

.cb-header {
    display: flex; align-items: center; gap: 14px;
    padding: 14px 24px;
    background: #fff;
    border-bottom: 1px solid #e8eaf0;
    flex-shrink: 0;
    z-index: 10;
}
.cb-back {
    width: 32px; height: 32px; border-radius: 8px;
    border: 1.5px solid #e8eaf0; background: #fff; color: #374151;
    display: inline-flex; align-items: center; justify-content: center;
    text-decoration: none; font-size: 15px; flex-shrink: 0;
    transition: all .15s;
}
.cb-back:hover { background: #f3f4f6; border-color: #d1d5db; color: #111827; }
.cb-name-input {
    flex: 1; min-width: 0;
    border: 1.5px solid #e8eaf0; border-radius: 9px;
    padding: 8px 14px; font-size: 14px; font-weight: 600;
    color: #1a1d23; outline: none; transition: border-color .15s;
    font-family: inherit;
}
.cb-name-input:focus { border-color: #3b82f6; }
.cb-name-input::placeholder { font-weight: 400; color: #9ca3af; }
.cb-header-right { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
.cb-status-badge {
    padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;
    background: #f3f4f6; color: #6b7280; cursor: pointer;
}
.cb-status-badge.active { background: #dcfce7; color: #16a34a; }

/* ── Builder body ─────────────────────────────────────────────────── */
.cb-builder { display: flex; flex: 1; overflow: hidden; }

/* ── Left sidebar ─────────────────────────────────────────────────── */
.cb-sidebar {
    width: 230px; flex-shrink: 0;
    background: #fafafa;
    border-right: 1px solid #e8eaf0;
    overflow-y: auto;
    padding: 16px 0 24px;
}
.cb-sidebar-section { margin-bottom: 6px; }
.cb-sidebar-section-title {
    padding: 8px 16px 4px;
    font-size: 10.5px; font-weight: 700; color: #9ca3af;
    text-transform: uppercase; letter-spacing: .07em;
}
.cb-block-item {
    display: flex; align-items: center; gap: 10px;
    padding: 8px 16px; cursor: pointer;
    font-size: 13px; color: #374151; font-weight: 500;
    transition: background .12s, color .12s;
}
.cb-block-item:hover { background: #eff6ff; color: #2563eb; }
.cb-block-item .cb-block-icon {
    width: 26px; height: 26px; border-radius: 7px;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 12px; flex-shrink: 0;
}
.cb-block-item.message  .cb-block-icon { background: #dbeafe; color: #2563eb; }
.cb-block-item.input    .cb-block-icon { background: #e0e7ff; color: #4f46e5; }
.cb-block-item.condition .cb-block-icon { background: #fef9c3; color: #b45309; }
.cb-block-item.action   .cb-block-icon { background: #dcfce7; color: #16a34a; }
.cb-block-item.delay    .cb-block-icon { background: #fce7f3; color: #db2777; }
.cb-block-item.end      .cb-block-icon { background: #f3f4f6; color: #6b7280; }
.cb-sidebar-divider { height: 1px; background: #f0f2f7; margin: 8px 16px; }

/* ── Canvas ───────────────────────────────────────────────────────── */
.cb-canvas {
    flex: 1; overflow: auto;
    background: #f4f6fb;
    padding: 36px 24px 60px;
}
.cb-flow {
    width: fit-content;
    min-width: 520px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    align-items: center;
}

/* ── Node ─────────────────────────────────────────────────────────── */
.cb-node {
    background: #fff;
    border: 1.5px solid #e8eaf0;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,.06);
    overflow: visible;
    position: relative;
    transition: box-shadow .15s;
    width: 420px;
}
.cb-node:hover { box-shadow: 0 4px 16px rgba(0,0,0,.1); }
.cb-node-bar {
    position: absolute; left: 0; top: 0; bottom: 0;
    width: 4px; border-radius: 12px 0 0 12px;
}
.cb-node.start    .cb-node-bar { background: #6366f1; }
.cb-node.message  .cb-node-bar { background: #3b82f6; }
.cb-node.input    .cb-node-bar { background: #8b5cf6; }
.cb-node.condition .cb-node-bar { background: #f59e0b; }
.cb-node.action   .cb-node-bar { background: #10b981; }
.cb-node.delay    .cb-node-bar { background: #ec4899; }
.cb-node.end      .cb-node-bar { background: #6b7280; }

.cb-node-head {
    display: flex; align-items: center; gap: 10px;
    padding: 12px 14px 12px 18px;
    border-bottom: 1px solid #f3f4f6;
}
.cb-node-icon {
    width: 30px; height: 30px; border-radius: 8px;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 13px; flex-shrink: 0;
}
.cb-node.start    .cb-node-icon { background: #e0e7ff; color: #6366f1; }
.cb-node.message  .cb-node-icon { background: #dbeafe; color: #2563eb; }
.cb-node.input    .cb-node-icon { background: #ede9fe; color: #7c3aed; }
.cb-node.condition .cb-node-icon { background: #fef9c3; color: #b45309; }
.cb-node.action   .cb-node-icon { background: #dcfce7; color: #16a34a; }
.cb-node.delay    .cb-node-icon { background: #fce7f3; color: #db2777; }
.cb-node.end      .cb-node-icon { background: #f3f4f6; color: #6b7280; }

.cb-node-label { flex: 1; min-width: 0; }
.cb-node-type {
    font-size: 10.5px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .06em; color: #9ca3af;
}
.cb-node-name { font-size: 13px; font-weight: 600; color: #1a1d23; margin-top: 1px; }

.cb-node-remove {
    width: 26px; height: 26px; border-radius: 6px;
    border: 1px solid #e8eaf0; background: transparent; color: #9ca3af;
    display: inline-flex; align-items: center; justify-content: center;
    cursor: pointer; font-size: 12px; flex-shrink: 0;
    transition: all .15s;
}
.cb-node-remove:hover { background: #fee2e2; color: #ef4444; border-color: #fca5a5; }

.cb-node-body { padding: 14px 18px; }
.cb-node-body label {
    font-size: 12px; font-weight: 600; color: #6b7280;
    display: block; margin-bottom: 5px;
}
.cb-node-body label + label { margin-top: 10px; }

/* ── Form controls ────────────────────────────────────────────────── */
.cb-node-body .form-control,
.cb-node-body .form-select {
    border: 1.5px solid #e8eaf0; border-radius: 8px;
    padding: 7px 11px; font-size: 13px; color: #374151;
    background: #fff; outline: none; width: 100%;
    box-sizing: border-box; font-family: inherit;
    transition: border-color .15s;
}
.cb-node-body .form-control:focus,
.cb-node-body .form-select:focus { border-color: #3b82f6; }
.cb-node-body textarea.form-control { resize: vertical; min-height: 64px; }
.cb-node-body .row-pair { display: flex; gap: 8px; }
.cb-node-body .row-pair > * { flex: 1; min-width: 0; }
.cb-node-body .cb-checkbox {
    display: flex; align-items: center; gap: 8px;
    font-size: 13px; color: #374151; cursor: pointer;
    margin-top: 8px;
}
.cb-node-body .cb-checkbox input { margin: 0; }

/* ── Connector ────────────────────────────────────────────────────── */
.cb-connector {
    width: 2px; height: 30px;
    background: #d1d5db;
    margin: 0 auto;
    position: relative;
}
.cb-connector::after {
    content: '';
    position: absolute; bottom: -7px; left: 50%;
    transform: translateX(-50%);
    border: 7px solid transparent;
    border-top-color: #d1d5db;
}

/* ── Add step button ──────────────────────────────────────────────── */
.cb-add-step {
    display: flex; align-items: center; justify-content: center; gap: 6px;
    width: 100%; padding: 10px;
    border: 2px dashed #d1d5db; border-radius: 12px;
    background: transparent; color: #6b7280;
    font-size: 13px; font-weight: 500;
    cursor: pointer; transition: all .15s;
    margin-top: 4px;
}
.cb-add-step:hover { border-color: #3b82f6; color: #3b82f6; background: #eff6ff; }

/* ── Tree branching ──────────────────────────────────────────────── */
.cb-branches-wrapper {
    margin-top: 4px;
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 100%;
}

/* Vertical line from parent node down to horizontal bar */
.cb-tree-stem {
    width: 2px; height: 20px;
    background: #d1d5db;
}

/* Horizontal bar connecting all branches */
.cb-tree-hbar {
    height: 2px; background: #d1d5db;
    position: relative;
}

/* Container flex das colunas */
.cb-branches {
    display: flex;
    gap: 12px;
    justify-content: center;
    flex-wrap: nowrap;
    position: relative;
}

/* Cada coluna de branch */
.cb-branch-col {
    flex: 0 0 auto;
    width: 260px;
    background: #f8fafc;
    border: 1.5px solid #e8eaf0;
    border-radius: 10px;
    overflow: visible;
    position: relative;
    display: flex;
    flex-direction: column;
}
/* Vertical line from hbar down to each column */
.cb-branch-col::before {
    content: '';
    display: block;
    width: 2px; height: 15px;
    background: #d1d5db;
    margin: -15px auto 0;
    position: relative;
    z-index: 1;
}
/* Branch padrão com borda tracejada */
.cb-branch-col.default {
    border-style: dashed;
    border-color: #c7c7c7;
}

/* Header da branch */
.cb-branch-header {
    padding: 8px 12px;
    background: #f0f2f7;
    border-bottom: 1px solid #e8eaf0;
    display: flex; align-items: center;
    justify-content: space-between;
    gap: 6px;
    border-radius: 10px 10px 0 0;
}
.cb-branch-col.default .cb-branch-header {
    background: #f9fafb;
}
.cb-branch-label-input {
    font-size: 12.5px; font-weight: 700; color: #374151;
    border: none; background: transparent; outline: none;
    flex: 1; min-width: 0; padding: 0;
    font-family: inherit;
}
.cb-branch-label-input:focus { color: #2563eb; }
.cb-branch-remove {
    width: 20px; height: 20px; border-radius: 5px;
    border: none; background: transparent; color: #c7c7c7;
    cursor: pointer; font-size: 11px;
    display: inline-flex; align-items: center; justify-content: center;
    transition: all .15s; flex-shrink: 0;
}
.cb-branch-remove:hover { background: #fee2e2; color: #ef4444; }

/* Config editável (keywords, operador) */
.cb-branch-config {
    padding: 8px 12px;
    border-bottom: 1px solid #f0f2f7;
    background: #fafbfd;
}
.cb-branch-config label {
    font-size: 11px; font-weight: 600; color: #9ca3af;
    display: block; margin-bottom: 3px;
}
.cb-branch-config .form-control,
.cb-branch-config .form-select {
    border: 1.5px solid #e8eaf0; border-radius: 6px;
    padding: 5px 8px; font-size: 12px; color: #374151;
    background: #fff; outline: none; width: 100%;
    font-family: inherit; transition: border-color .15s;
}
.cb-branch-config .form-control:focus,
.cb-branch-config .form-select:focus { border-color: #3b82f6; }
.cb-branch-config .row-pair { display: flex; gap: 6px; }
.cb-branch-config .row-pair > * { flex: 1; min-width: 0; }

/* Body da branch (sub-nós) */
.cb-branch-body {
    padding: 10px;
    flex: 1;
}

/* Nós compactos dentro de branches */
.cb-branch-body .cb-node {
    width: 100%;
    font-size: 12px;
}
.cb-branch-body .cb-node-head { padding: 8px 10px 8px 14px; }
.cb-branch-body .cb-node-body { padding: 10px 14px; }
.cb-branch-body .cb-node-icon { width: 24px; height: 24px; font-size: 11px; }
.cb-branch-body .cb-node-type { font-size: 9.5px; }
.cb-branch-body .cb-node-name { font-size: 12px; }
.cb-branch-body .cb-connector { height: 16px; }
.cb-branch-body .cb-add-step { font-size: 12px; padding: 8px; }
.cb-branch-body .cb-node-body .form-control,
.cb-branch-body .cb-node-body .form-select { font-size: 12px; padding: 5px 9px; }
.cb-branch-body .cb-node-body textarea.form-control { min-height: 48px; }
.cb-branch-body .cb-node-remove { width: 22px; height: 22px; font-size: 10px; }
.cb-branch-body .cb-node-move { width: 18px; height: 18px; font-size: 9px; }

/* Botão adicionar branch */
.cb-add-branch-col {
    flex: 0 0 auto;
    width: 44px;
    min-height: 80px;
    border: 2px dashed #d1d5db;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer;
    color: #9ca3af; font-size: 20px;
    transition: all .15s;
    margin-top: 15px; /* compensate for ::before */
}
.cb-add-branch-col:hover {
    border-color: #3b82f6; color: #3b82f6; background: #eff6ff;
}

/* ── Image upload area ────────────────────────────────────────────── */
.cb-image-area {
    margin-top: 8px; padding: 10px;
    border: 1.5px dashed #e8eaf0; border-radius: 8px;
    text-align: center; cursor: pointer;
    transition: all .15s; font-size: 12px; color: #9ca3af;
}
.cb-image-area:hover { border-color: #3b82f6; color: #3b82f6; background: #eff6ff; }
.cb-image-area img { max-width: 100%; border-radius: 6px; margin-top: 6px; }

/* ── Buttons ──────────────────────────────────────────────────────── */
.btn-primary-sm {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 18px; background: #3b82f6; color: #fff;
    border: none; border-radius: 9px; font-size: 13.5px;
    font-weight: 600; cursor: pointer; transition: background .15s;
    font-family: inherit;
}
.btn-primary-sm:hover { background: #2563eb; color: #fff; }
.btn-cancel-sm {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 16px; background: #fff; color: #374151;
    border: 1.5px solid #e8eaf0; border-radius: 9px; font-size: 13.5px;
    font-weight: 600; cursor: pointer; transition: all .15s;
    font-family: inherit; text-decoration: none;
}
.btn-cancel-sm:hover { background: #f3f4f6; color: #111827; }

/* ── Variables modal ──────────────────────────────────────────────── */
.cb-vars-list { list-style: none; padding: 0; margin: 0; }
.cb-vars-list li {
    display: flex; align-items: center; gap: 8px;
    padding: 6px 0; border-bottom: 1px solid #f3f4f6;
}
.cb-vars-list li:last-child { border-bottom: none; }
.cb-vars-list .var-name { flex: 1; font-size: 13px; font-weight: 600; color: #1a1d23; }
.cb-vars-list .var-remove { color: #d1d5db; cursor: pointer; font-size: 14px; }
.cb-vars-list .var-remove:hover { color: #ef4444; }

/* ── Move buttons ─────────────────────────────────────────────────── */
.cb-node-move {
    width: 22px; height: 22px; border-radius: 5px;
    border: 1px solid #e8eaf0; background: transparent; color: #9ca3af;
    display: inline-flex; align-items: center; justify-content: center;
    cursor: pointer; font-size: 11px; flex-shrink: 0; transition: all .15s;
}
.cb-node-move:hover { background: #eff6ff; color: #3b82f6; border-color: #bfdbfe; }
</style>
@endpush

@section('content')
<div class="cb-page">

    {{-- Header --}}
    <div class="cb-header">
        <a href="{{ route('chatbot.flows.index') }}" class="cb-back" title="Voltar">
            <i class="bi bi-arrow-left"></i>
        </a>
        <input type="text" class="cb-name-input" id="cbName"
            placeholder="Nome do fluxo..."
            value="{{ $flow->name }}">
        <div class="cb-header-right">
            <span class="cb-status-badge {{ $flow->is_active ? 'active' : '' }}" id="cbStatusBadge" onclick="toggleActive()">
                {{ $flow->is_active ? 'Ativo' : 'Inativo' }}
            </span>
            <button class="btn-cancel-sm" onclick="openTestWidget()">
                <i class="bi bi-play-circle"></i> Testar
            </button>
            @if($flow->channel === 'website' && $flow->website_token)
            <button class="btn-cancel-sm" onclick="showEmbedModal()">
                <i class="bi bi-code-slash"></i> Embed
            </button>
            @endif
            <a href="{{ route('chatbot.flows.edit', ['flow' => $flow->id, 'settings' => 1]) }}" class="btn-cancel-sm">
                <i class="bi bi-gear"></i> Config
            </a>
            <button class="btn-primary-sm" onclick="saveFlow()">
                <i class="bi bi-check2"></i> Salvar
            </button>
        </div>
    </div>

    {{-- Builder --}}
    <div class="cb-builder">

        {{-- Sidebar --}}
        <div class="cb-sidebar">
            <div class="cb-sidebar-section">
                <div class="cb-sidebar-section-title">Blocos</div>
                <div class="cb-block-item message" onclick="addStepToRoot('message')">
                    <span class="cb-block-icon"><i class="bi bi-chat-dots"></i></span>Mensagem
                </div>
                <div class="cb-block-item input" onclick="addStepToRoot('input')">
                    <span class="cb-block-icon"><i class="bi bi-input-cursor-text"></i></span>Pergunta
                </div>
                <div class="cb-block-item condition" onclick="addStepToRoot('condition')">
                    <span class="cb-block-icon"><i class="bi bi-question-diamond"></i></span>Condição
                </div>
                <div class="cb-block-item action" onclick="addStepToRoot('action')">
                    <span class="cb-block-icon"><i class="bi bi-lightning"></i></span>Ação
                </div>
                <div class="cb-block-item delay" onclick="addStepToRoot('delay')">
                    <span class="cb-block-icon"><i class="bi bi-hourglass-split"></i></span>Aguardar
                </div>
                <div class="cb-block-item end" onclick="addStepToRoot('end')">
                    <span class="cb-block-icon"><i class="bi bi-stop-circle"></i></span>Fim
                </div>
            </div>

            <div class="cb-sidebar-divider"></div>

            <div class="cb-sidebar-section">
                <div class="cb-sidebar-section-title">Config</div>
                <div class="cb-block-item" onclick="showVarsModal()" style="color:#374151">
                    <span class="cb-block-icon" style="background:#f3f4f6;color:#6b7280"><i class="bi bi-braces"></i></span>Variáveis
                </div>
            </div>
        </div>

        {{-- Canvas --}}
        <div class="cb-canvas">
            <div class="cb-flow" id="cbFlow">
                <!-- Rendered by JS -->
            </div>
        </div>
    </div>
</div>

{{-- Variables Modal --}}
<div class="modal fade" id="varsModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content" style="border-radius:14px;border:1.5px solid #e8eaf0;">
            <div class="modal-header" style="border-bottom:1px solid #f0f2f7;padding:14px 18px;">
                <h6 style="font-size:14px;font-weight:700;color:#1a1d23;margin:0;">Variáveis do fluxo</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="font-size:11px;"></button>
            </div>
            <div class="modal-body" style="padding:14px 18px;">
                <ul class="cb-vars-list" id="varsList"></ul>
                <div style="display:flex;gap:6px;margin-top:10px;">
                    <input type="text" id="newVarName" class="form-control" placeholder="nome_variavel" style="border:1.5px solid #e8eaf0;border-radius:8px;padding:7px 11px;font-size:13px;">
                    <button class="btn-primary-sm" onclick="addVariable()" style="padding:7px 14px;font-size:12px;">+</button>
                </div>
                <div style="margin-top:10px;font-size:11px;color:#9ca3af;">
                    Variáveis de sistema: <code>$contact_name</code>, <code>$contact_email</code>, <code>$contact_phone</code>, <code>$lead_exists</code>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal Embed --}}
@if($flow->website_token)
<div id="embedModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9999;align-items:center;justify-content:center;" onclick="if(event.target===this)this.style.display='none'">
    <div style="background:#fff;border-radius:14px;padding:28px 32px;width:520px;max-width:94vw;box-shadow:0 20px 60px rgba(0,0,0,.18);">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
            <h3 style="font-size:16px;font-weight:700;color:#1a1d23;margin:0;">Código de instalação</h3>
            <button onclick="document.getElementById('embedModal').style.display='none'" style="background:none;border:none;font-size:20px;color:#9ca3af;cursor:pointer;padding:4px;">&times;</button>
        </div>
        <p style="font-size:13.5px;color:#6b7280;margin:0 0 14px;">Cole este código antes do <code>&lt;/body&gt;</code> do seu site:</p>
        <textarea id="embedCode" readonly rows="3" style="width:100%;border:1.5px solid #e8eaf0;border-radius:9px;padding:12px;font-family:monospace;font-size:12.5px;color:#374151;background:#f8fafc;resize:none;">{{ '<script src="' . config('app.url') . '/api/widget/' . $flow->website_token . '.js"></' . 'script>' }}</textarea>
        <div style="display:flex;align-items:center;gap:10px;margin-top:14px;">
            <button onclick="copyEmbed()" class="btn-primary-sm" style="padding:9px 20px;font-size:13px;">
                <i class="bi bi-clipboard"></i> Copiar código
            </button>
            <span id="embedCopied" style="font-size:12px;color:#16a34a;font-weight:600;display:none;"><i class="bi bi-check-circle"></i> Copiado!</span>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
(function() {
    'use strict';

    const FLOW_ID   = {{ $flow->id }};
    const SAVE_URL  = "{{ route('chatbot.flows.graph', $flow) }}";
    const UPLOAD_URL = "{{ route('chatbot.flows.upload-image') }}";
    const TOGGLE_URL = "{{ route('chatbot.flows.toggle', $flow) }}";
    const CSRF      = "{{ csrf_token() }}";
    const PIPELINES = {!! json_encode($pipelinesJs) !!};
    const TAGS      = {!! json_encode($tags) !!};
    const USERS     = {!! json_encode($users) !!};
    const CUSTOM_FIELDS = {!! json_encode($customFieldDefs) !!};

    let flowSteps     = {!! json_encode($flow->steps ?? []) !!} || [];
    let flowVariables = {!! json_encode($flow->variables ?? []) !!} || [];

    const NODE_TYPES = {
        message:   { icon: 'bi-chat-dots',          label: 'Mensagem',  color: 'message' },
        input:     { icon: 'bi-input-cursor-text',   label: 'Pergunta',  color: 'input' },
        condition: { icon: 'bi-question-diamond',    label: 'Condição',  color: 'condition' },
        action:    { icon: 'bi-lightning',            label: 'Ação',      color: 'action' },
        delay:     { icon: 'bi-hourglass-split',      label: 'Aguardar',  color: 'delay' },
        end:       { icon: 'bi-stop-circle',          label: 'Fim',       color: 'end' },
    };

    let idCounter = 1;
    function genId() { return 's' + Date.now().toString(36) + (idCounter++); }

    // ── Resolve steps at a path ──────────────────────────────────────
    function resolveParentSteps(path) {
        if (path.length === 0) return flowSteps;
        let current = flowSteps;
        for (let i = 0; i < path.length; i++) {
            const key = path[i];
            if (typeof key === 'number') {
                current = current[key];
            } else {
                current = current[key];
            }
        }
        return current;
    }

    // ── Default configs ──────────────────────────────────────────────
    function defaultConfig(type) {
        switch (type) {
            case 'message':   return { text: '', image_url: null };
            case 'input':     return { text: '', save_to: '', field_type: 'text', show_buttons: false };
            case 'condition': return { variable: '', conditions: [] };
            case 'action':    return { type: 'create_lead' };
            case 'delay':     return { seconds: 3 };
            case 'end':       return { text: '' };
            default:          return {};
        }
    }

    function createStep(type) {
        const step = { id: genId(), type: type, config: defaultConfig(type) };
        if (type === 'input' || type === 'condition') {
            step.branches = [];
            step.default_branch = { steps: [] };
        }
        return step;
    }

    // ── Add / Remove / Move ──────────────────────────────────────────
    window.addStepToRoot = function(type) {
        flowSteps.push(createStep(type));
        renderFlow();
    };

    function removeStep(path, index) {
        const arr = resolveParentSteps(path);
        arr.splice(index, 1);
        renderFlow();
    }

    function moveStep(path, fromIdx, direction) {
        const arr = resolveParentSteps(path);
        const toIdx = fromIdx + direction;
        if (toIdx < 0 || toIdx >= arr.length) return;
        [arr[fromIdx], arr[toIdx]] = [arr[toIdx], arr[fromIdx]];
        renderFlow();
    }

    // ── Branch management ────────────────────────────────────────────
    function addBranch(path, stepIndex) {
        const step = resolveParentSteps(path)[stepIndex];
        if (!step.branches) step.branches = [];
        const bId = 'b' + Date.now().toString(36) + (idCounter++);
        const branch = {
            id: bId,
            label: 'Opção ' + (step.branches.length + 1),
            keywords: [],
            steps: []
        };
        if (step.type === 'condition') {
            branch.operator = 'equals';
            branch.value = '';
        }
        step.branches.push(branch);
        if (!step.default_branch) step.default_branch = { steps: [] };
        renderFlow();
    }

    function removeBranch(path, stepIndex, branchIndex) {
        const step = resolveParentSteps(path)[stepIndex];
        step.branches.splice(branchIndex, 1);
        renderFlow();
    }

    // ── Escape HTML ──────────────────────────────────────────────────
    function esc(str) {
        const d = document.createElement('div');
        d.textContent = str || '';
        return d.innerHTML;
    }

    // ── Render Flow ──────────────────────────────────────────────────
    function renderFlow() {
        const container = document.getElementById('cbFlow');
        container.innerHTML = '';

        // Start node
        container.innerHTML += `
            <div class="cb-node start" style="align-self:center;">
                <div class="cb-node-bar"></div>
                <div class="cb-node-head">
                    <div class="cb-node-icon"><i class="bi bi-play-fill"></i></div>
                    <div class="cb-node-label">
                        <div class="cb-node-type">Início</div>
                        <div class="cb-node-name">Quando visitante envia mensagem</div>
                    </div>
                </div>
            </div>`;

        if (flowSteps.length > 0) {
            container.innerHTML += '<div class="cb-connector"></div>';
        }

        // Render root steps
        renderStepListInto(flowSteps, container, []);

        // Add step button at the end
        const addBtnWrap = document.createElement('div');
        addBtnWrap.style.cssText = 'margin-top:8px;width:420px;align-self:center;';
        addBtnWrap.innerHTML = `
            <button class="cb-add-step" onclick="showAddMenu(this, [])">
                <i class="bi bi-plus-lg"></i> Adicionar bloco
            </button>`;
        container.appendChild(addBtnWrap);
    }

    function renderStepListInto(steps, container, path) {
        steps.forEach((step, i) => {
            const stepEl = document.createElement('div');
            stepEl.style.cssText = 'display:flex;flex-direction:column;align-items:center;width:100%;';
            stepEl.innerHTML = renderStep(step, path, i, steps.length);
            container.appendChild(stepEl);

            // Branches (tree layout)
            if ((step.type === 'input' || step.type === 'condition') && (step.branches || step.default_branch)) {
                const branchEl = document.createElement('div');
                branchEl.style.cssText = 'display:flex;flex-direction:column;align-items:center;width:100%;';
                branchEl.innerHTML = renderBranches(step, path, i);
                container.appendChild(branchEl);
            }

            if (i < steps.length - 1) {
                const conn = document.createElement('div');
                conn.className = 'cb-connector';
                container.appendChild(conn);
            }
        });
    }

    function renderStep(step, path, index, totalSteps) {
        const info = NODE_TYPES[step.type] || { icon: 'bi-circle', label: step.type, color: '' };
        const pathStr = JSON.stringify(path).replace(/"/g, '&quot;');
        let html = '';

        html += `<div class="cb-node ${step.type}" data-step-id="${step.id}">`;
        html += `<div class="cb-node-bar"></div>`;

        // Head
        html += `<div class="cb-node-head">`;
        html += `<div class="cb-node-icon"><i class="bi ${info.icon}"></i></div>`;
        html += `<div class="cb-node-label">`;
        html += `<div class="cb-node-type">${esc(info.label)}</div>`;
        html += `<div class="cb-node-name">${esc(getStepSummary(step))}</div>`;
        html += `</div>`;

        // Move buttons
        if (index > 0) {
            html += `<button class="cb-node-move" onclick="event.stopPropagation();cbMoveStep(${pathStr}, ${index}, -1)" title="Mover para cima"><i class="bi bi-chevron-up"></i></button>`;
        }
        if (index < totalSteps - 1) {
            html += `<button class="cb-node-move" onclick="event.stopPropagation();cbMoveStep(${pathStr}, ${index}, 1)" title="Mover para baixo"><i class="bi bi-chevron-down"></i></button>`;
        }

        html += `<button class="cb-node-remove" onclick="event.stopPropagation();cbRemoveStep(${pathStr}, ${index})" title="Remover"><i class="bi bi-x-lg"></i></button>`;
        html += `</div>`;

        // Body
        html += renderStepBody(step, path, index);

        html += `</div>`;
        return html;
    }

    function getStepSummary(step) {
        const c = step.config || {};
        switch (step.type) {
            case 'message': return truncate(c.text, 40) || 'Mensagem vazia';
            case 'input':   return truncate(c.text, 40) || 'Pergunta vazia';
            case 'condition': return c.variable ? 'Se ' + c.variable + '...' : 'Condição vazia';
            case 'action':  return getActionLabel(c);
            case 'delay':   return (c.seconds || 3) + ' segundos';
            case 'end':     return truncate(c.text, 40) || 'Finalizar';
            default:        return step.type;
        }
    }

    function truncate(str, len) {
        if (!str) return '';
        return str.length > len ? str.substring(0, len) + '...' : str;
    }

    function getActionLabel(c) {
        switch (c.type || '') {
            case 'create_lead':        return 'Criar lead';
            case 'change_stage':       return 'Mover para etapa';
            case 'add_tag':            return 'Adicionar tag: ' + (c.value || '');
            case 'remove_tag':         return 'Remover tag: ' + (c.value || '');
            case 'save_variable':      return 'Salvar variável: ' + (c.variable || '');
            case 'close_conversation': return 'Encerrar conversa';
            case 'assign_human':       return 'Transferir para humano';
            case 'send_webhook':       return 'Webhook: ' + (c.url || '');
            case 'set_custom_field':   return 'Campo: ' + (c.field_label || c.field_name || '');
            default:                   return c.type || 'Ação';
        }
    }

    // ── Step Body (inline form) ──────────────────────────────────────
    function renderStepBody(step, path, index) {
        const pathStr = JSON.stringify(path).replace(/"/g, '&quot;');
        const c = step.config || {};
        let html = '<div class="cb-node-body">';

        switch (step.type) {
            case 'message':
                html += '<label>Texto da mensagem</label>';
                html += '<textarea class="form-control" onchange="cbUpdateConfig(' + pathStr + ', ' + index + ', \'text\', this.value)">' + esc(c.text || '') + '</textarea>';
                html += renderImageArea(step, path, index);
                break;

            case 'input':
                html += '<label>Pergunta para o visitante</label>';
                html += '<textarea class="form-control" onchange="cbUpdateConfig(' + pathStr + ', ' + index + ', \'text\', this.value)">' + esc(c.text || '') + '</textarea>';
                html += '<div class="row-pair" style="margin-top:8px;">';
                html += '<div><label>Tipo do campo</label>';
                html += '<select class="form-select" onchange="cbUpdateConfig(' + pathStr + ', ' + index + ', \'field_type\', this.value)">';
                ['text', 'email', 'phone', 'number'].forEach(function(t) {
                    html += '<option value="' + t + '" ' + (c.field_type === t ? 'selected' : '') + '>' + t + '</option>';
                });
                html += '</select></div>';
                html += '<div><label>Salvar em</label>';
                html += '<select class="form-select" onchange="cbUpdateConfig(' + pathStr + ', ' + index + ', \'save_to\', this.value)">';
                html += '<option value="">Não salvar</option>';
                flowVariables.forEach(function(v) {
                    var name = v.name || v;
                    html += '<option value="' + esc(name) + '" ' + (c.save_to === name ? 'selected' : '') + '>' + esc(name) + '</option>';
                });
                html += '</select></div></div>';
                html += '<label class="cb-checkbox"><input type="checkbox" ' + (c.show_buttons ? 'checked' : '') + ' onchange="cbUpdateConfig(' + pathStr + ', ' + index + ', \'show_buttons\', this.checked)"> Exibir botões de resposta rápida</label>';
                break;

            case 'condition':
                html += '<label>Variável a verificar</label>';
                html += '<select class="form-select" onchange="cbUpdateConfig(' + pathStr + ', ' + index + ', \'variable\', this.value)">';
                html += '<option value="">Selecione...</option>';
                ['$contact_name', '$contact_email', '$contact_phone', '$lead_exists'].forEach(function(v) {
                    html += '<option value="' + v + '" ' + (c.variable === v ? 'selected' : '') + '>' + v + '</option>';
                });
                flowVariables.forEach(function(v) {
                    var name = v.name || v;
                    html += '<option value="' + esc(name) + '" ' + (c.variable === name ? 'selected' : '') + '>' + esc(name) + '</option>';
                });
                html += '</select>';
                break;

            case 'action':
                html += renderActionBody(step, path, index);
                break;

            case 'delay':
                html += '<label>Segundos de espera</label>';
                html += '<input type="number" class="form-control" min="1" max="300" value="' + (c.seconds || 3) + '" onchange="cbUpdateConfig(' + pathStr + ', ' + index + ', \'seconds\', parseInt(this.value))">';
                break;

            case 'end':
                html += '<label>Mensagem de encerramento (opcional)</label>';
                html += '<textarea class="form-control" onchange="cbUpdateConfig(' + pathStr + ', ' + index + ', \'text\', this.value)">' + esc(c.text || '') + '</textarea>';
                break;
        }

        html += '</div>';
        return html;
    }

    function renderImageArea(step, path, index) {
        const pathStr = JSON.stringify(path).replace(/"/g, '&quot;');
        const c = step.config || {};
        let html = '<div class="cb-image-area" onclick="cbUploadImage(' + pathStr + ', ' + index + ', this)">';
        if (c.image_url) {
            html += '<img src="' + esc(c.image_url) + '" alt="Imagem">';
            html += '<div style="margin-top:6px;font-size:11px;color:#6b7280;">Clique para trocar a imagem</div>';
        } else {
            html += '<i class="bi bi-image" style="font-size:20px;display:block;margin-bottom:4px;"></i>';
            html += 'Clique para adicionar imagem';
        }
        html += '</div>';
        return html;
    }

    function renderActionBody(step, path, index) {
        const pathStr = JSON.stringify(path).replace(/"/g, '&quot;');
        const c = step.config || {};
        let html = '';

        html += '<label>Tipo da ação</label>';
        html += '<select class="form-select" onchange="cbUpdateActionType(' + pathStr + ', ' + index + ', this.value)">';
        var actionTypes = [
            ['create_lead', 'Criar lead'],
            ['change_stage', 'Mover para etapa'],
            ['add_tag', 'Adicionar tag'],
            ['remove_tag', 'Remover tag'],
            ['save_variable', 'Salvar variável'],
            ['close_conversation', 'Encerrar conversa'],
            ['assign_human', 'Transferir para humano'],
            ['send_webhook', 'Enviar webhook'],
            ['set_custom_field', 'Preencher campo personalizado'],
        ];
        actionTypes.forEach(function(at) {
            html += '<option value="' + at[0] + '" ' + (c.type === at[0] ? 'selected' : '') + '>' + at[1] + '</option>';
        });
        html += '</select>';

        switch (c.type) {
            case 'create_lead':
                html += '<div class="row-pair" style="margin-top:8px;">';
                html += '<div><label>Var nome</label><input class="form-control" value="' + esc(c.name_var || '') + '" onchange="cbUpdateConfig(' + pathStr + ', ' + index + ', \'name_var\', this.value)"></div>';
                html += '<div><label>Var email</label><input class="form-control" value="' + esc(c.email_var || '') + '" onchange="cbUpdateConfig(' + pathStr + ', ' + index + ', \'email_var\', this.value)"></div>';
                html += '</div>';
                html += '<div class="row-pair" style="margin-top:8px;">';
                html += '<div><label>Var telefone</label><input class="form-control" value="' + esc(c.phone_var || '') + '" onchange="cbUpdateConfig(' + pathStr + ', ' + index + ', \'phone_var\', this.value)"></div>';
                html += '<div><label>Etapa</label><select class="form-select" onchange="cbUpdateConfig(' + pathStr + ', ' + index + ', \'stage_id\', parseInt(this.value))">';
                html += '<option value="">Selecione...</option>';
                PIPELINES.forEach(function(p) {
                    p.stages.forEach(function(s) {
                        html += '<option value="' + s.id + '" ' + (c.stage_id == s.id ? 'selected' : '') + '>' + esc(p.name) + ' → ' + esc(s.name) + '</option>';
                    });
                });
                html += '</select></div></div>';
                break;
            case 'change_stage':
                html += '<label style="margin-top:8px;">Etapa destino</label>';
                html += '<select class="form-select" onchange="cbUpdateConfig(' + pathStr + ', ' + index + ', \'stage_id\', parseInt(this.value))">';
                html += '<option value="">Selecione...</option>';
                PIPELINES.forEach(function(p) {
                    p.stages.forEach(function(s) {
                        html += '<option value="' + s.id + '" ' + (c.stage_id == s.id ? 'selected' : '') + '>' + esc(p.name) + ' → ' + esc(s.name) + '</option>';
                    });
                });
                html += '</select>';
                break;
            case 'add_tag':
            case 'remove_tag':
                html += '<label style="margin-top:8px;">Tag</label>';
                html += '<select class="form-select" onchange="cbUpdateConfig(' + pathStr + ', ' + index + ', \'value\', this.value)">';
                html += '<option value="">Selecione...</option>';
                TAGS.forEach(function(t) {
                    html += '<option value="' + esc(t) + '" ' + (c.value === t ? 'selected' : '') + '>' + esc(t) + '</option>';
                });
                html += '</select>';
                break;
            case 'save_variable':
                html += '<div class="row-pair" style="margin-top:8px;">';
                html += '<div><label>Variável</label><input class="form-control" value="' + esc(c.variable || '') + '" onchange="cbUpdateConfig(' + pathStr + ', ' + index + ', \'variable\', this.value)"></div>';
                html += '<div><label>Valor</label><input class="form-control" value="' + esc(c.value || '') + '" onchange="cbUpdateConfig(' + pathStr + ', ' + index + ', \'value\', this.value)"></div>';
                html += '</div>';
                break;
            case 'send_webhook':
                html += '<label style="margin-top:8px;">URL</label>';
                html += '<input class="form-control" value="' + esc(c.url || '') + '" onchange="cbUpdateConfig(' + pathStr + ', ' + index + ', \'url\', this.value)" placeholder="https://...">';
                break;
            case 'set_custom_field':
                html += '<label style="margin-top:8px;">Campo</label>';
                html += '<select class="form-select" onchange="cbUpdateCustomField(' + pathStr + ', ' + index + ', this.value)">';
                html += '<option value="">Selecione...</option>';
                CUSTOM_FIELDS.forEach(function(f) {
                    html += '<option value="' + esc(f.name) + '" ' + (c.field_name === f.name ? 'selected' : '') + '>' + esc(f.label) + '</option>';
                });
                html += '</select>';
                html += '<label style="margin-top:8px;">Valor</label>';
                html += '<input class="form-control" value="' + esc(c.field_value || '') + '" onchange="cbUpdateConfig(' + pathStr + ', ' + index + ', \'field_value\', this.value)">';
                break;
        }

        return html;
    }

    // ── Render Branches (TREE layout) ────────────────────────────────
    function renderBranches(step, path, index) {
        const pathStr = JSON.stringify(path).replace(/"/g, '&quot;');
        const branches = step.branches || [];
        const totalCols = branches.length + 1; // +1 for default branch
        const colW = 260, gap = 12;
        const totalWidth = totalCols * colW + (totalCols - 1) * gap;
        const barWidth = (totalCols - 1) * (colW + gap);

        let html = '<div class="cb-branches-wrapper">';

        // Vertical stem from parent node
        html += '<div class="cb-tree-stem"></div>';

        // Horizontal bar
        if (totalCols > 1) {
            html += '<div class="cb-tree-hbar" style="width:' + barWidth + 'px;"></div>';
        }

        // Branch columns
        html += '<div class="cb-branches">';

        // Regular branches
        branches.forEach(function(b, bi) {
            const branchPath = path.concat([index, 'branches', bi, 'steps']);
            const branchPathStr = JSON.stringify(branchPath).replace(/"/g, '&quot;');

            html += '<div class="cb-branch-col">';

            // Header
            html += '<div class="cb-branch-header">';
            html += '<input class="cb-branch-label-input" value="' + esc(b.label || 'Opção ' + (bi + 1)) + '" onchange="cbUpdateBranch(' + pathStr + ', ' + index + ', ' + bi + ', \'label\', this.value)">';
            html += '<button class="cb-branch-remove" onclick="cbRemoveBranch(' + pathStr + ', ' + index + ', ' + bi + ')" title="Remover opção"><i class="bi bi-x-lg"></i></button>';
            html += '</div>';

            // Config
            html += '<div class="cb-branch-config">';
            if (step.type === 'input') {
                html += '<label>Keywords (vírgula)</label>';
                html += '<input class="form-control" value="' + esc((b.keywords || []).join(', ')) + '" onchange="cbUpdateBranchKeywords(' + pathStr + ', ' + index + ', ' + bi + ', this.value)">';
            } else if (step.type === 'condition') {
                html += '<div class="row-pair">';
                html += '<div><label>Operador</label><select class="form-select" onchange="cbUpdateBranch(' + pathStr + ', ' + index + ', ' + bi + ', \'operator\', this.value)">';
                var ops = { equals: 'Igual a', not_equals: 'Diferente', contains: 'Contém', starts_with: 'Começa com', ends_with: 'Termina com', gt: 'Maior que', lt: 'Menor que' };
                Object.keys(ops).forEach(function(op) {
                    html += '<option value="' + op + '" ' + (b.operator === op ? 'selected' : '') + '>' + ops[op] + '</option>';
                });
                html += '</select></div>';
                html += '<div><label>Valor</label><input class="form-control" value="' + esc(b.value || '') + '" onchange="cbUpdateBranch(' + pathStr + ', ' + index + ', ' + bi + ', \'value\', this.value)"></div>';
                html += '</div>';
            }
            html += '</div>';

            // Body (sub-steps)
            html += '<div class="cb-branch-body" id="branch-' + step.id + '-' + bi + '">';
            // Sub-steps rendered via DOM after innerHTML is set
            html += '</div>';

            html += '</div>'; // .cb-branch-col
        });

        // Default branch
        const defPath = path.concat([index, 'default_branch', 'steps']);
        const defPathStr = JSON.stringify(defPath).replace(/"/g, '&quot;');

        html += '<div class="cb-branch-col default">';
        html += '<div class="cb-branch-header"><span class="cb-branch-label-input" style="color:#9ca3af;font-weight:600;">Padrão</span></div>';
        html += '<div class="cb-branch-config"><label style="color:#b0b0b0;font-size:10px;">Quando nenhuma opção corresponder</label></div>';
        html += '<div class="cb-branch-body" id="branch-' + step.id + '-default"></div>';
        html += '</div>';

        // Add branch button
        html += '<div class="cb-add-branch-col" onclick="cbAddBranch(' + pathStr + ', ' + index + ')" title="Adicionar opção">+</div>';

        html += '</div>'; // .cb-branches
        html += '</div>'; // .cb-branches-wrapper

        return html;
    }

    // After renderFlow sets innerHTML, we need to populate branch bodies via DOM
    // We use a post-render pass since innerHTML doesn't support nested renderStepListInto
    function postRenderBranches() {
        flowSteps.forEach(function(step, i) {
            populateBranchBodies(step, [], i);
        });
    }

    function populateBranchBodies(step, path, index) {
        if (step.type !== 'input' && step.type !== 'condition') return;
        if (!step.branches && !step.default_branch) return;

        // Regular branches
        (step.branches || []).forEach(function(b, bi) {
            const containerId = 'branch-' + step.id + '-' + bi;
            const container = document.getElementById(containerId);
            if (!container) return;
            container.innerHTML = '';

            const branchPath = path.concat([index, 'branches', bi, 'steps']);
            const branchPathStr = JSON.stringify(branchPath).replace(/"/g, '&quot;');

            (b.steps || []).forEach(function(bs, bsi) {
                const stepEl = document.createElement('div');
                stepEl.style.cssText = 'display:flex;flex-direction:column;align-items:center;width:100%;';
                stepEl.innerHTML = renderStep(bs, branchPath, bsi, b.steps.length);
                container.appendChild(stepEl);

                // Recursively populate nested branches
                if ((bs.type === 'input' || bs.type === 'condition') && (bs.branches || bs.default_branch)) {
                    const nestedEl = document.createElement('div');
                    nestedEl.style.cssText = 'display:flex;flex-direction:column;align-items:center;width:100%;';
                    nestedEl.innerHTML = renderBranches(bs, branchPath, bsi);
                    container.appendChild(nestedEl);
                }

                if (bsi < b.steps.length - 1) {
                    const conn = document.createElement('div');
                    conn.className = 'cb-connector';
                    container.appendChild(conn);
                }
            });

            // Add step button
            const addBtn = document.createElement('button');
            addBtn.className = 'cb-add-step';
            addBtn.style.marginTop = '8px';
            addBtn.innerHTML = '<i class="bi bi-plus-lg"></i> Adicionar';
            addBtn.onclick = function() { showAddMenu(addBtn, branchPath); };
            container.appendChild(addBtn);

            // Recursive: populate nested branches within this branch
            (b.steps || []).forEach(function(bs, bsi) {
                populateBranchBodies(bs, branchPath, bsi);
            });
        });

        // Default branch
        const defContainerId = 'branch-' + step.id + '-default';
        const defContainer = document.getElementById(defContainerId);
        if (!defContainer) return;
        defContainer.innerHTML = '';

        const defPath = path.concat([index, 'default_branch', 'steps']);
        const defPathStr = JSON.stringify(defPath).replace(/"/g, '&quot;');
        const defSteps = (step.default_branch && step.default_branch.steps) || [];

        defSteps.forEach(function(ds, dsi) {
            const stepEl = document.createElement('div');
            stepEl.style.cssText = 'display:flex;flex-direction:column;align-items:center;width:100%;';
            stepEl.innerHTML = renderStep(ds, defPath, dsi, defSteps.length);
            defContainer.appendChild(stepEl);

            if ((ds.type === 'input' || ds.type === 'condition') && (ds.branches || ds.default_branch)) {
                const nestedEl = document.createElement('div');
                nestedEl.style.cssText = 'display:flex;flex-direction:column;align-items:center;width:100%;';
                nestedEl.innerHTML = renderBranches(ds, defPath, dsi);
                defContainer.appendChild(nestedEl);
            }

            if (dsi < defSteps.length - 1) {
                const conn = document.createElement('div');
                conn.className = 'cb-connector';
                defContainer.appendChild(conn);
            }
        });

        const addBtn = document.createElement('button');
        addBtn.className = 'cb-add-step';
        addBtn.style.marginTop = '8px';
        addBtn.innerHTML = '<i class="bi bi-plus-lg"></i> Adicionar';
        addBtn.onclick = function() { showAddMenu(addBtn, defPath); };
        defContainer.appendChild(addBtn);

        // Recursive for default branch
        defSteps.forEach(function(ds, dsi) {
            populateBranchBodies(ds, defPath, dsi);
        });
    }

    // Override renderFlow to include post-render
    const _origRenderFlow = renderFlow;
    renderFlow = function() {
        _origRenderFlow();
        postRenderBranches();
    };

    // ── Global click handlers ────────────────────────────────────────
    window.cbRemoveStep = function(path, index) {
        removeStep(path, index);
    };

    window.cbMoveStep = function(path, index, direction) {
        moveStep(path, index, direction);
    };

    window.cbUpdateConfig = function(path, index, field, value) {
        const arr = resolveParentSteps(path);
        if (!arr[index].config) arr[index].config = {};
        arr[index].config[field] = value;
    };

    window.cbUpdateActionType = function(path, index, newType) {
        const arr = resolveParentSteps(path);
        arr[index].config = { type: newType };
        renderFlow();
    };

    window.cbUpdateCustomField = function(path, index, fieldName) {
        const arr = resolveParentSteps(path);
        const field = CUSTOM_FIELDS.find(function(f) { return f.name === fieldName; });
        arr[index].config.field_name = fieldName;
        arr[index].config.field_label = field ? field.label : fieldName;
        renderFlow();
    };

    window.cbAddBranch = function(path, stepIndex) {
        addBranch(path, stepIndex);
    };

    window.cbRemoveBranch = function(path, stepIndex, branchIndex) {
        removeBranch(path, stepIndex, branchIndex);
    };

    window.cbUpdateBranch = function(path, stepIndex, branchIndex, field, value) {
        const step = resolveParentSteps(path)[stepIndex];
        step.branches[branchIndex][field] = value;
    };

    window.cbUpdateBranchKeywords = function(path, stepIndex, branchIndex, value) {
        const step = resolveParentSteps(path)[stepIndex];
        step.branches[branchIndex].keywords = value.split(',').map(function(s) { return s.trim(); }).filter(Boolean);
    };

    // ── Add menu (inline dropdown) ───────────────────────────────────
    window.showAddMenu = function(btn, path) {
        document.querySelectorAll('.cb-add-menu').forEach(function(m) { m.remove(); });

        const menu = document.createElement('div');
        menu.className = 'cb-add-menu';
        menu.style.cssText = 'position:absolute;z-index:100;background:#fff;border:1.5px solid #e8eaf0;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.12);padding:6px 0;min-width:180px;';

        var types = [
            ['message', 'bi-chat-dots', 'Mensagem'],
            ['input', 'bi-input-cursor-text', 'Pergunta'],
            ['condition', 'bi-question-diamond', 'Condição'],
            ['action', 'bi-lightning', 'Ação'],
            ['delay', 'bi-hourglass-split', 'Aguardar'],
            ['end', 'bi-stop-circle', 'Fim'],
        ];

        types.forEach(function(t) {
            var item = document.createElement('div');
            item.style.cssText = 'display:flex;align-items:center;gap:10px;padding:8px 14px;cursor:pointer;font-size:13px;color:#374151;font-weight:500;transition:background .12s;';
            item.innerHTML = '<i class="bi ' + t[1] + '" style="font-size:14px;color:#6b7280;"></i>' + t[2];
            item.onmouseenter = function() { item.style.background = '#eff6ff'; };
            item.onmouseleave = function() { item.style.background = ''; };
            item.onclick = function(e) {
                e.stopPropagation();
                var arr = resolveParentSteps(path);
                arr.push(createStep(t[0]));
                renderFlow();
                menu.remove();
            };
            menu.appendChild(item);
        });

        btn.style.position = 'relative';
        btn.appendChild(menu);

        setTimeout(function() {
            document.addEventListener('click', function handler(e) {
                if (!menu.contains(e.target) && e.target !== btn) {
                    menu.remove();
                    document.removeEventListener('click', handler);
                }
            });
        }, 10);
    };

    // ── Image upload ─────────────────────────────────────────────────
    window.cbUploadImage = function(path, index, areaEl) {
        var input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        input.onchange = function() {
            var file = input.files[0];
            if (!file) return;
            var fd = new FormData();
            fd.append('image', file);
            fetch(UPLOAD_URL, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF },
                body: fd
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.url) {
                    cbUpdateConfig(path, index, 'image_url', data.url);
                    renderFlow();
                }
            })
            .catch(function(err) {
                console.error('Upload error:', err);
                toastr.error('Erro ao enviar imagem');
            });
        };
        input.click();
    };

    // ── Toggle active ────────────────────────────────────────────────
    window.toggleActive = function() {
        fetch(TOGGLE_URL, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var badge = document.getElementById('cbStatusBadge');
            if (data.is_active) {
                badge.classList.add('active');
                badge.textContent = 'Ativo';
            } else {
                badge.classList.remove('active');
                badge.textContent = 'Inativo';
            }
        });
    };

    // ── Save flow ────────────────────────────────────────────────────
    window.saveFlow = function() {
        var name = document.getElementById('cbName').value.trim();
        if (!name) {
            toastr.warning('Informe o nome do fluxo');
            return;
        }

        fetch(SAVE_URL, {
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': CSRF,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                steps: JSON.stringify(flowSteps),
                variables: flowVariables,
                name: name,
            }),
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                toastr.success('Fluxo salvo com sucesso!');
            } else {
                toastr.error(data.message || 'Erro ao salvar');
            }
        })
        .catch(function(err) {
            console.error('Save error:', err);
            toastr.error('Erro ao salvar fluxo');
        });
    };

    // ── Variables modal ──────────────────────────────────────────────
    window.showVarsModal = function() {
        renderVarsList();
        new bootstrap.Modal(document.getElementById('varsModal')).show();
    };

    function renderVarsList() {
        var list = document.getElementById('varsList');
        list.innerHTML = '';
        flowVariables.forEach(function(v, i) {
            var name = v.name || v;
            list.innerHTML += '<li><span class="var-name">' + esc(name) + '</span><span class="var-remove" onclick="removeVariable(' + i + ')"><i class="bi bi-x-lg"></i></span></li>';
        });
    }

    window.addVariable = function() {
        var input = document.getElementById('newVarName');
        var name = input.value.trim().replace(/[^a-zA-Z0-9_]/g, '');
        if (!name) return;
        if (flowVariables.some(function(v) { return (v.name || v) === name; })) {
            toastr.warning('Variável já existe');
            return;
        }
        flowVariables.push({ name: name, default: '' });
        input.value = '';
        renderVarsList();
    };

    window.removeVariable = function(index) {
        flowVariables.splice(index, 1);
        renderVarsList();
    };

    // ── Init ─────────────────────────────────────────────────────────
    renderFlow();

    // ── Test Widget ──────────────────────────────────────────────────
    var _testWidgetActive = false;

    window.openTestWidget = async function() {
        // Salva o fluxo antes de testar
        await saveFlow();

        if (_testWidgetActive) {
            closeTestWidget();
            return;
        }

        // Limpar visitor ID para começar conversa nova
        var vkey = 'syncro_vid_' + '{{ $flow->website_token }}';
        localStorage.removeItem(vkey);

        // Injetar script do widget
        var s = document.createElement('script');
        s.id = 'syncro-test-widget';
        s.src = '{{ config("app.url") }}/api/widget/{{ $flow->website_token }}.js?' + Date.now();
        document.body.appendChild(s);
        _testWidgetActive = true;
    };

    window.closeTestWidget = function() {
        ['syncro-launcher', 'syncro-window', 'syncro-welcome', 'syncro-test-widget'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el.remove();
        });
        // Remove style injetado pelo widget
        document.querySelectorAll('style').forEach(function(s) {
            if (s.textContent && s.textContent.indexOf('#syncro-launcher') !== -1) s.remove();
        });
        _testWidgetActive = false;
    };

    // ── Embed Modal ──────────────────────────────────────────────────
    window.showEmbedModal = function() {
        var modal = document.getElementById('embedModal');
        if (modal) modal.style.display = 'flex';
    };

    window.copyEmbed = function() {
        var textarea = document.getElementById('embedCode');
        if (textarea) {
            navigator.clipboard.writeText(textarea.value.trim()).then(function() {
                var msg = document.getElementById('embedCopied');
                if (msg) {
                    msg.style.display = 'inline-flex';
                    setTimeout(function() { msg.style.display = 'none'; }, 2500);
                }
            });
        }
    };

})();
</script>
@endpush

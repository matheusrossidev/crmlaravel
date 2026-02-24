@extends('tenant.layouts.app')
@php
    $title    = 'API / Webhooks';
    $pageIcon = 'key';
@endphp

@push('styles')
<style>
    /* ── Layout ── */
    .api-layout {
        display: grid;
        grid-template-columns: 1fr 280px;
        gap: 22px;
        align-items: start;
    }

    @media (max-width: 900px) { .api-layout { grid-template-columns: 1fr; } }

    .api-card {
        background: #fff;
        border-radius: 14px;
        border: 1px solid #e8eaf0;
        overflow: hidden;
    }

    .api-card-header {
        padding: 16px 22px;
        border-bottom: 1px solid #f0f2f7;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }

    .api-card-header h3 {
        font-size: 14px;
        font-weight: 700;
        color: #1a1d23;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .api-card-body { padding: 20px 22px; }

    /* Key item */
    .key-item {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 14px 0;
        border-bottom: 1px solid #f7f8fa;
    }

    .key-item:last-child { border-bottom: none; }

    .key-icon {
        width: 38px; height: 38px;
        border-radius: 10px;
        background: #eff6ff;
        display: flex; align-items: center; justify-content: center;
        color: #3B82F6; font-size: 16px; flex-shrink: 0;
    }

    .key-info { flex: 1; min-width: 0; }
    .key-name  { font-size: 13.5px; font-weight: 600; color: #1a1d23; margin-bottom: 3px; }
    .key-prefix{ font-size: 12px; color: #9ca3af; font-family: monospace; }
    .key-meta  { font-size: 11px; color: #9ca3af; margin-top: 2px; }

    .badge-active   { background: #d1fae5; color: #065f46; font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 99px; }
    .badge-inactive { background: #f3f4f6; color: #6b7280; font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 99px; }

    .btn-revoke {
        padding: 6px 12px; background: #fff; color: #EF4444;
        border: 1.5px solid #fecaca; border-radius: 8px;
        font-size: 12px; font-weight: 600; cursor: pointer; white-space: nowrap;
    }
    .btn-revoke:hover { background: #fef2f2; }

    .keys-empty { text-align: center; padding: 40px 20px; color: #9ca3af; font-size: 13px; }
    .keys-empty i { font-size: 32px; opacity: .3; display: block; margin-bottom: 8px; }

    /* Endpoint blocks */
    .endpoint-block {
        background: #f8fafc;
        border: 1px solid #e8eaf0;
        border-radius: 10px;
        margin-bottom: 10px;
        overflow: hidden;
    }

    .endpoint-method {
        display: inline-block; font-size: 11px; font-weight: 700;
        padding: 2px 7px; border-radius: 5px; margin-right: 6px;
        font-family: monospace;
    }

    .method-post   { background: #d1fae5; color: #065f46; }
    .method-get    { background: #dbeafe; color: #1d4ed8; }
    .method-put    { background: #fef3c7; color: #92400e; }
    .method-delete { background: #fee2e2; color: #991b1b; }

    .endpoint-path { font-family: monospace; font-size: 12.5px; color: #374151; font-weight: 600; }
    .endpoint-desc { font-size: 12px; color: #6b7280; margin-top: 2px; }

    .endpoint-header {
        padding: 10px 14px;
        display: flex; align-items: center; justify-content: space-between;
        cursor: pointer; user-select: none;
        transition: background .12s;
    }
    .endpoint-header:hover { background: #f0f4fa; }

    .endpoint-block.open .ep-chevron { transform: rotate(180deg); }
    .ep-chevron { transition: transform .2s; }

    pre.code-block {
        margin: 0; padding: 14px 16px;
        background: #1e2130; color: #e2e8f0;
        font-size: 11.5px; font-family: 'Menlo','Monaco','Consolas',monospace;
        overflow-x: auto; border-top: 1px solid #e8eaf0;
        display: none; white-space: pre;
    }
    .endpoint-block.open pre.code-block { display: block; }

    /* ── Builder (POST /leads) ── */
    .builder-wrap {
        display: none;
        grid-template-columns: 1fr 1fr;
        border-top: 1px solid #e8eaf0;
        min-height: 280px;
    }
    .endpoint-block.open .builder-wrap { display: grid; }
    @media (max-width: 700px) { .builder-wrap { grid-template-columns: 1fr; } }

    .builder-form {
        padding: 16px;
        overflow-y: auto;
        max-height: 460px;
        border-right: 1px solid #e8eaf0;
    }

    .builder-preview-pane {
        background: #1e2130;
        display: flex;
        flex-direction: column;
    }

    .builder-preview-header {
        padding: 7px 12px;
        background: #161825;
        display: flex; align-items: center; justify-content: space-between;
        color: #6b7280; font-size: 10.5px; font-weight: 600;
        flex-shrink: 0;
    }

    pre.curl-live {
        margin: 0; padding: 14px;
        flex: 1;
        color: #e2e8f0; font-size: 11px;
        font-family: 'Menlo','Monaco','Consolas',monospace;
        white-space: pre-wrap; word-break: break-all;
        overflow-y: auto;
    }

    /* ── Mini builder (stage/won/lost) ── */
    .mini-builder {
        display: none;
        grid-template-columns: 1fr 1fr;
        border-top: 1px solid #e8eaf0;
        background: #f8fafc;
    }
    .endpoint-block.open .mini-builder { display: grid; }
    @media (max-width: 700px) { .mini-builder { grid-template-columns: 1fr; } }

    .mini-form {
        padding: 14px 16px;
        border-right: 1px solid #e8eaf0;
    }

    .mini-preview {
        background: #1e2130;
        display: flex; flex-direction: column;
    }

    pre.mini-curl {
        margin: 0; padding: 12px;
        color: #e2e8f0; font-size: 11px;
        font-family: 'Menlo','Monaco','Consolas',monospace;
        white-space: pre-wrap; word-break: break-all;
        flex: 1;
    }

    /* ── Builder form elements ── */
    .bsec-title {
        font-size: 10px; font-weight: 700;
        text-transform: uppercase; letter-spacing: .07em;
        color: #9ca3af; margin: 12px 0 7px;
    }
    .bsec-title:first-child { margin-top: 0; }

    .bfield {
        display: grid;
        grid-template-columns: 18px 120px 1fr;
        align-items: center;
        gap: 6px; margin-bottom: 6px;
    }

    .bfield-label {
        font-size: 11.5px; font-family: monospace;
        font-weight: 600; color: #374151;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }

    .bfield-req {
        display: inline-block; font-size: 10px;
        background: #eff6ff; color: #3B82F6;
        padding: 1px 6px; border-radius: 4px; font-weight: 600;
        white-space: nowrap;
    }

    .binput {
        width: 100%; box-sizing: border-box;
        padding: 5px 8px; border: 1.5px solid #e8eaf0;
        border-radius: 7px; font-size: 11.5px;
        font-family: monospace; color: #1a1d23;
        background: #fafafa; outline: none;
    }
    .binput:focus { border-color: #3B82F6; background: #fff; }
    .binput:disabled { opacity: .35; cursor: not-allowed; background: #f3f4f6; }

    .bselect {
        width: 100%; box-sizing: border-box;
        padding: 5px 8px; border: 1.5px solid #e8eaf0;
        border-radius: 7px; font-size: 12px;
        color: #1a1d23; background: #fff;
        outline: none; cursor: pointer;
    }
    .bselect:focus { border-color: #3B82F6; }

    .mfield { margin-bottom: 10px; }
    .mfield-label {
        display: block; font-size: 11px; font-weight: 700;
        color: #6b7280; margin-bottom: 3px;
        text-transform: uppercase; letter-spacing: .04em;
    }

    .btn-copy-curl {
        padding: 2px 10px; background: #3B82F6; color: #fff;
        border: none; border-radius: 5px; font-size: 10.5px;
        font-weight: 600; cursor: pointer;
    }
    .btn-copy-curl:hover { background: #2563eb; }
    .btn-copy-curl.copied { background: #10B981; }

    /* Modal */
    .modal-overlay {
        display: none; position: fixed; inset: 0;
        background: rgba(0,0,0,.45); z-index: 500;
        align-items: center; justify-content: center;
    }
    .modal-overlay.open { display: flex; }

    .modal-box {
        background: #fff; border-radius: 14px; padding: 28px;
        width: 460px; max-width: 94vw;
        box-shadow: 0 20px 60px rgba(0,0,0,.18);
    }

    .modal-title {
        font-size: 15px; font-weight: 700; color: #1a1d23;
        margin-bottom: 16px; display: flex; align-items: center; gap: 8px;
    }

    .key-reveal-box {
        background: #f8fafc; border: 1.5px solid #e8eaf0;
        border-radius: 9px; padding: 12px 14px;
        font-family: monospace; font-size: 13px; color: #1a1d23;
        word-break: break-all; margin-bottom: 12px; position: relative;
    }

    .btn-copy {
        position: absolute; top: 8px; right: 8px;
        padding: 4px 10px; background: #3B82F6; color: #fff;
        border: none; border-radius: 6px; font-size: 11px;
        font-weight: 600; cursor: pointer;
    }
    .btn-copy:hover { background: #2563EB; }
    .btn-copy.copied { background: #10B981; }

    .warning-box {
        background: #fffbeb; border: 1px solid #fde68a;
        border-radius: 8px; padding: 10px 14px;
        font-size: 12.5px; color: #92400e; margin-bottom: 16px;
        display: flex; align-items: flex-start; gap: 8px;
    }

    .drawer-input {
        width: 100%; padding: 9px 12px;
        border: 1.5px solid #e8eaf0; border-radius: 9px;
        font-size: 13.5px; font-family: 'Inter', sans-serif;
        color: #1a1d23; background: #fafafa; outline: none;
        box-sizing: border-box; margin-bottom: 14px;
    }
    .drawer-input:focus { border-color: #3B82F6; background: #fff; }
</style>
@endpush

@section('content')
<div class="page-container">
<div class="api-layout">

    {{-- ── Left: Keys + Docs ──────────────────────────────────────────── --}}
    <div>

        {{-- API Keys --}}
        <div class="api-card" style="margin-bottom:22px;">
            <div class="api-card-header">
                <h3><i class="bi bi-key" style="color:#3B82F6;"></i> Suas API Keys</h3>
                <button class="btn-primary-sm" onclick="openNewKeyModal()">
                    <i class="bi bi-plus-lg"></i> Nova API Key
                </button>
            </div>
            <div class="api-card-body" style="padding:0 22px;">
                @forelse($apiKeys as $key)
                <div class="key-item" id="key-row-{{ $key->id }}">
                    <div class="key-icon"><i class="bi bi-key"></i></div>
                    <div class="key-info">
                        <div class="key-name">{{ $key->name }}</div>
                        <div class="key-prefix">{{ $key->key_prefix }}</div>
                        <div class="key-meta">
                            Criada em {{ $key->created_at?->format('d/m/Y') }}
                            @if($key->last_used_at)
                                · Último uso: {{ $key->last_used_at->diffForHumans() }}
                            @else
                                · Nunca utilizada
                            @endif
                        </div>
                    </div>
                    <span class="{{ $key->is_active ? 'badge-active' : 'badge-inactive' }}">
                        {{ $key->is_active ? 'Ativa' : 'Revogada' }}
                    </span>
                    @if($key->is_active)
                    <button class="btn-revoke" onclick="revokeKey({{ $key->id }}, this)">
                        <i class="bi bi-trash"></i> Revogar
                    </button>
                    @endif
                </div>
                @empty
                <div class="keys-empty">
                    <i class="bi bi-key"></i>
                    Nenhuma API Key criada ainda.<br>
                    Clique em <strong>Nova API Key</strong> para criar.
                </div>
                @endforelse
            </div>
        </div>

        {{-- Endpoints --}}
        <div class="api-card">
            <div class="api-card-header">
                <h3><i class="bi bi-book" style="color:#8B5CF6;"></i> Documentação dos Endpoints</h3>
            </div>
            <div class="api-card-body">
                <p style="font-size:13px;color:#6b7280;margin-bottom:16px;">
                    Inclua o header <code style="background:#f0f4ff;color:#6366f1;padding:2px 6px;border-radius:4px;font-size:12px;">X-API-Key: sua_key</code>
                    em todas as requisições. URL base: <code style="background:#f0f4ff;color:#6366f1;padding:2px 6px;border-radius:4px;font-size:12px;">{{ url('/api/v1') }}</code>
                </p>

                {{-- ── POST /leads (Builder) ── --}}
                <div class="endpoint-block" id="ep-0">
                    <div class="endpoint-header" onclick="toggleEndpoint('ep-0')">
                        <div>
                            <span class="endpoint-method method-post">POST</span>
                            <span class="endpoint-path">/leads</span>
                            <div class="endpoint-desc">Criar novo lead — use o builder para montar o payload</div>
                        </div>
                        <i class="bi bi-chevron-down ep-chevron" style="color:#9ca3af;font-size:12px;"></i>
                    </div>
                    {{-- Builder --}}
                    <div class="builder-wrap">
                        {{-- Form side --}}
                        <div class="builder-form">
                            <div class="bsec-title">Campos Principais</div>

                            {{-- name (required) --}}
                            <div class="bfield">
                                <i class="bi bi-lock-fill" style="color:#d1d5db;font-size:10px;"></i>
                                <span class="bfield-label">name</span>
                                <div style="display:flex;gap:5px;align-items:center;">
                                    <input type="text" class="binput" id="bld-name"
                                           value="João Silva" oninput="updateCreateCurl()">
                                    <span class="bfield-req">req</span>
                                </div>
                            </div>

                            {{-- phone --}}
                            <div class="bfield">
                                <input type="checkbox" id="bld-phone-on" checked
                                       onchange="toggleBldInput('bld-phone'); updateCreateCurl()">
                                <span class="bfield-label">phone</span>
                                <input type="text" class="binput" id="bld-phone"
                                       value="11999999999" oninput="updateCreateCurl()">
                            </div>

                            {{-- email --}}
                            <div class="bfield">
                                <input type="checkbox" id="bld-email-on"
                                       onchange="toggleBldInput('bld-email'); updateCreateCurl()">
                                <span class="bfield-label">email</span>
                                <input type="text" class="binput" id="bld-email"
                                       value="joao@exemplo.com" oninput="updateCreateCurl()" disabled>
                            </div>

                            {{-- source --}}
                            <div class="bfield">
                                <input type="checkbox" id="bld-source-on"
                                       onchange="toggleBldInput('bld-source'); updateCreateCurl()">
                                <span class="bfield-label">source</span>
                                <input type="text" class="binput" id="bld-source"
                                       value="site" oninput="updateCreateCurl()" disabled>
                            </div>

                            {{-- value --}}
                            <div class="bfield">
                                <input type="checkbox" id="bld-value-on"
                                       onchange="toggleBldInput('bld-value'); updateCreateCurl()">
                                <span class="bfield-label">value</span>
                                <input type="number" class="binput" id="bld-value"
                                       value="1500" oninput="updateCreateCurl()" disabled>
                            </div>

                            {{-- notes --}}
                            <div class="bfield">
                                <input type="checkbox" id="bld-notes-on"
                                       onchange="toggleBldInput('bld-notes'); updateCreateCurl()">
                                <span class="bfield-label">notes</span>
                                <input type="text" class="binput" id="bld-notes"
                                       value="Observação do lead" oninput="updateCreateCurl()" disabled>
                            </div>

                            {{-- Pipeline & Stage --}}
                            @if($pipelines->count())
                            <div class="bsec-title">Pipeline & Etapa <span style="color:#EF4444;font-size:9px;">obrigatório</span></div>

                            <div class="bfield">
                                <i class="bi bi-diagram-3" style="color:#d1d5db;font-size:10px;"></i>
                                <span class="bfield-label">pipeline_id</span>
                                <select class="bselect" id="bld-create-pipeline"
                                        onchange="populateStagesFor('bld-create-pipeline','bld-create-stage','all'); updateCreateCurl()">
                                    @foreach($pipelines as $pipeline)
                                    <option value="{{ $pipeline->id }}">{{ $pipeline->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="bfield">
                                <i class="bi bi-arrow-right-circle" style="color:#d1d5db;font-size:10px;"></i>
                                <span class="bfield-label">stage_id</span>
                                <select class="bselect" id="bld-create-stage"
                                        onchange="updateCreateCurl()"></select>
                            </div>
                            @else
                            <div class="bsec-title">Pipeline & Etapa</div>
                            <p style="font-size:12px;color:#9ca3af;margin:0 0 8px;">
                                <i class="bi bi-info-circle"></i>
                                Nenhum pipeline configurado. Crie um em
                                <a href="{{ route('settings.pipelines') }}">Configurações → Funis</a>.
                            </p>
                            @endif

                            {{-- Custom Fields --}}
                            @if($customFields->count())
                            <div class="bsec-title" style="display:flex;align-items:center;gap:6px;">
                                Campos Personalizados
                                <span style="font-size:10px;font-weight:400;color:#9ca3af;text-transform:none;letter-spacing:0;">(custom_fields.*)</span>
                            </div>
                            @foreach($customFields as $cf)
                            <div class="bfield">
                                <input type="checkbox" id="cfon-{{ $cf->name }}"
                                       onchange="toggleBldInput('cfv-{{ $cf->name }}'); updateCreateCurl()">
                                <span class="bfield-label" title="{{ $cf->label }}">{{ $cf->name }}</span>
                                <input type="{{ in_array($cf->field_type, ['number','currency']) ? 'number' : 'text' }}"
                                       class="binput" id="cfv-{{ $cf->name }}"
                                       placeholder="{{ $cf->label }}"
                                       oninput="updateCreateCurl()" disabled>
                            </div>
                            @endforeach
                            @endif
                        </div>

                        {{-- Curl preview --}}
                        <div class="builder-preview-pane">
                            <div class="builder-preview-header">
                                <span><i class="bi bi-terminal" style="margin-right:4px;"></i>cURL gerado</span>
                                <button class="btn-copy-curl" onclick="copyCurl('curl-create', this)">
                                    <i class="bi bi-clipboard"></i> Copiar
                                </button>
                            </div>
                            <pre class="curl-live" id="curl-create"></pre>
                        </div>
                    </div>
                </div>

                {{-- ── GET /leads/{id} ── --}}
                <div class="endpoint-block" id="ep-1">
                    <div class="endpoint-header" onclick="toggleEndpoint('ep-1')">
                        <div>
                            <span class="endpoint-method method-get">GET</span>
                            <span class="endpoint-path">/leads/{id}</span>
                            <div class="endpoint-desc">Buscar lead por ID</div>
                        </div>
                        <i class="bi bi-chevron-down ep-chevron" style="color:#9ca3af;font-size:12px;"></i>
                    </div>
                    <pre class="code-block">curl -X GET {{ url('/api/v1') }}/leads/1 \
  -H 'X-API-Key: YOUR_API_KEY'</pre>
                </div>

                {{-- ── PUT /leads/{id}/stage (Mini builder) ── --}}
                <div class="endpoint-block" id="ep-2">
                    <div class="endpoint-header" onclick="toggleEndpoint('ep-2')">
                        <div>
                            <span class="endpoint-method method-put">PUT</span>
                            <span class="endpoint-path">/leads/{id}/stage</span>
                            <div class="endpoint-desc">Mover lead para outra etapa</div>
                        </div>
                        <i class="bi bi-chevron-down ep-chevron" style="color:#9ca3af;font-size:12px;"></i>
                    </div>
                    <div class="mini-builder">
                        <div class="mini-form">
                            <div class="mfield">
                                <label class="mfield-label">Lead ID</label>
                                <input type="number" class="binput" id="stage-lead-id"
                                       value="1" oninput="updateStageCurl()">
                            </div>
                            @if($pipelines->count())
                            <div class="mfield">
                                <label class="mfield-label">Pipeline</label>
                                <select class="bselect" id="stage-pipeline"
                                        onchange="populateStagesFor('stage-pipeline','stage-stage','all'); updateStageCurl()">
                                    @foreach($pipelines as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mfield">
                                <label class="mfield-label">Etapa</label>
                                <select class="bselect" id="stage-stage" onchange="updateStageCurl()"></select>
                            </div>
                            @endif
                        </div>
                        <div class="mini-preview">
                            <div class="builder-preview-header">
                                <span><i class="bi bi-terminal" style="margin-right:4px;"></i>cURL gerado</span>
                                <button class="btn-copy-curl" onclick="copyCurl('curl-stage', this)">
                                    <i class="bi bi-clipboard"></i> Copiar
                                </button>
                            </div>
                            <pre class="mini-curl" id="curl-stage"></pre>
                        </div>
                    </div>
                </div>

                {{-- ── PUT /leads/{id}/won (Mini builder) ── --}}
                <div class="endpoint-block" id="ep-3">
                    <div class="endpoint-header" onclick="toggleEndpoint('ep-3')">
                        <div>
                            <span class="endpoint-method method-put">PUT</span>
                            <span class="endpoint-path">/leads/{id}/won</span>
                            <div class="endpoint-desc">Marcar lead como ganho</div>
                        </div>
                        <i class="bi bi-chevron-down ep-chevron" style="color:#9ca3af;font-size:12px;"></i>
                    </div>
                    <div class="mini-builder">
                        <div class="mini-form">
                            <div class="mfield">
                                <label class="mfield-label">Lead ID</label>
                                <input type="number" class="binput" id="won-lead-id"
                                       value="1" oninput="updateWonCurl()">
                            </div>
                            @if($pipelines->count())
                            <div class="mfield">
                                <label class="mfield-label">Pipeline <span style="font-weight:400;color:#9ca3af;">(filtro)</span></label>
                                <select class="bselect" id="won-pipeline"
                                        onchange="populateStagesFor('won-pipeline','won-stage','won'); updateWonCurl()">
                                    @foreach($pipelines as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mfield">
                                <label class="mfield-label">Etapa de Ganho</label>
                                <select class="bselect" id="won-stage" onchange="updateWonCurl()"></select>
                            </div>
                            @endif
                            <div class="mfield">
                                <label class="mfield-label">Valor (opcional)</label>
                                <input type="number" class="binput" id="won-value"
                                       placeholder="2000.00" oninput="updateWonCurl()">
                            </div>
                        </div>
                        <div class="mini-preview">
                            <div class="builder-preview-header">
                                <span><i class="bi bi-terminal" style="margin-right:4px;"></i>cURL gerado</span>
                                <button class="btn-copy-curl" onclick="copyCurl('curl-won', this)">
                                    <i class="bi bi-clipboard"></i> Copiar
                                </button>
                            </div>
                            <pre class="mini-curl" id="curl-won"></pre>
                        </div>
                    </div>
                </div>

                {{-- ── PUT /leads/{id}/lost (Mini builder) ── --}}
                <div class="endpoint-block" id="ep-4">
                    <div class="endpoint-header" onclick="toggleEndpoint('ep-4')">
                        <div>
                            <span class="endpoint-method method-put">PUT</span>
                            <span class="endpoint-path">/leads/{id}/lost</span>
                            <div class="endpoint-desc">Marcar lead como perdido</div>
                        </div>
                        <i class="bi bi-chevron-down ep-chevron" style="color:#9ca3af;font-size:12px;"></i>
                    </div>
                    <div class="mini-builder">
                        <div class="mini-form">
                            <div class="mfield">
                                <label class="mfield-label">Lead ID</label>
                                <input type="number" class="binput" id="lost-lead-id"
                                       value="1" oninput="updateLostCurl()">
                            </div>
                            @if($pipelines->count())
                            <div class="mfield">
                                <label class="mfield-label">Pipeline <span style="font-weight:400;color:#9ca3af;">(filtro)</span></label>
                                <select class="bselect" id="lost-pipeline"
                                        onchange="populateStagesFor('lost-pipeline','lost-stage','lost'); updateLostCurl()">
                                    @foreach($pipelines as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mfield">
                                <label class="mfield-label">Etapa de Perda</label>
                                <select class="bselect" id="lost-stage" onchange="updateLostCurl()"></select>
                            </div>
                            @endif
                            <div class="mfield">
                                <label class="mfield-label">Motivo ID (opcional)</label>
                                <input type="number" class="binput" id="lost-reason"
                                       placeholder="ID do motivo" oninput="updateLostCurl()">
                            </div>
                        </div>
                        <div class="mini-preview">
                            <div class="builder-preview-header">
                                <span><i class="bi bi-terminal" style="margin-right:4px;"></i>cURL gerado</span>
                                <button class="btn-copy-curl" onclick="copyCurl('curl-lost', this)">
                                    <i class="bi bi-clipboard"></i> Copiar
                                </button>
                            </div>
                            <pre class="mini-curl" id="curl-lost"></pre>
                        </div>
                    </div>
                </div>

                {{-- ── DELETE /leads/{id} ── --}}
                <div class="endpoint-block" id="ep-5">
                    <div class="endpoint-header" onclick="toggleEndpoint('ep-5')">
                        <div>
                            <span class="endpoint-method method-delete">DELETE</span>
                            <span class="endpoint-path">/leads/{id}</span>
                            <div class="endpoint-desc">Deletar lead</div>
                        </div>
                        <i class="bi bi-chevron-down ep-chevron" style="color:#9ca3af;font-size:12px;"></i>
                    </div>
                    <pre class="code-block">curl -X DELETE {{ url('/api/v1') }}/leads/1 \
  -H 'X-API-Key: YOUR_API_KEY'</pre>
                </div>

                {{-- ── GET /pipelines ── --}}
                <div class="endpoint-block" id="ep-6">
                    <div class="endpoint-header" onclick="toggleEndpoint('ep-6')">
                        <div>
                            <span class="endpoint-method method-get">GET</span>
                            <span class="endpoint-path">/pipelines</span>
                            <div class="endpoint-desc">Listar pipelines e etapas disponíveis</div>
                        </div>
                        <i class="bi bi-chevron-down ep-chevron" style="color:#9ca3af;font-size:12px;"></i>
                    </div>
                    <pre class="code-block">curl -X GET {{ url('/api/v1') }}/pipelines \
  -H 'X-API-Key: YOUR_API_KEY'</pre>
                </div>

            </div>
        </div>

    </div>

    {{-- ── Right: Info ─────────────────────────────────────────────────── --}}
    <div>
        <div class="api-card">
            <div class="api-card-header">
                <h3><i class="bi bi-info-circle" style="color:#F59E0B;"></i> Como usar</h3>
            </div>
            <div class="api-card-body" style="font-size:13px;color:#374151;line-height:1.6;">
                <p><strong>1. Gere uma API Key</strong><br>
                Clique em <em>Nova API Key</em>, dê um nome para identificar onde será usada (ex: "Site", "Automação") e copie a key.</p>

                <p style="margin-top:12px;"><strong>2. Salve com segurança</strong><br>
                A key completa é exibida <strong>apenas uma vez</strong>. Guarde em um local seguro.</p>

                <p style="margin-top:12px;"><strong>3. Inclua no header</strong></p>
                <div style="background:#f8fafc;border:1px solid #e8eaf0;border-radius:8px;padding:10px 12px;font-family:monospace;font-size:12px;margin:8px 0;word-break:break-all;">
                    X-API-Key: crm_sua_key_aqui
                </div>

                <p style="margin-top:12px;"><strong>4. URL base</strong></p>
                <div style="background:#f8fafc;border:1px solid #e8eaf0;border-radius:8px;padding:10px 12px;font-family:monospace;font-size:12px;word-break:break-all;">
                    {{ url('/api/v1') }}
                </div>

                <hr style="border:none;border-top:1px solid #f0f2f7;margin:16px 0;">

                <p style="font-size:12px;color:#374151;"><strong>Builder interativo</strong><br>
                Expanda <span class="endpoint-method method-post" style="font-size:10px;">POST</span> <code style="font-size:11px;">/leads</code> para usar o builder — selecione campos, pipeline e etapa e veja o cURL gerado em tempo real.</p>

                <hr style="border:none;border-top:1px solid #f0f2f7;margin:16px 0;">

                <p style="font-size:12px;color:#9ca3af;">
                    <i class="bi bi-shield-check" style="color:#10B981;"></i>
                    Todas as requisições são isoladas por conta.
                </p>

                @if($customFields->count())
                <hr style="border:none;border-top:1px solid #f0f2f7;margin:16px 0;">
                <p style="font-size:12px;"><strong>Campos personalizados</strong></p>
                <p style="font-size:12px;color:#6b7280;">Passe em <code style="font-size:11px;">custom_fields</code>:</p>
                @foreach($customFields as $cf)
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                    <code style="font-size:11px;background:#f0f4ff;color:#6366f1;padding:1px 6px;border-radius:4px;">{{ $cf->name }}</code>
                    <span style="font-size:11px;color:#9ca3af;">{{ $cf->label }} · {{ $cf->field_type }}</span>
                </div>
                @endforeach
                @endif
            </div>
        </div>
    </div>

</div>
</div>

{{-- Modal: Nova API Key --}}
<div class="modal-overlay" id="modalNewKey">
    <div class="modal-box">
        <div class="modal-title">
            <i class="bi bi-key" style="color:#3B82F6;"></i> Nova API Key
        </div>
        <div id="newKeyForm">
            <label style="font-size:12.5px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Nome da key</label>
            <input type="text" id="newKeyName" class="drawer-input" placeholder="Ex: Site, Landing Page, Automação...">
            <div style="display:flex;gap:8px;justify-content:flex-end;">
                <button onclick="closeNewKeyModal()" style="padding:9px 18px;border:1.5px solid #e8eaf0;border-radius:9px;background:#fff;font-size:13px;font-weight:600;color:#6b7280;cursor:pointer;">Cancelar</button>
                <button onclick="createKey()" id="btnCreateKey" style="padding:9px 22px;border:none;border-radius:9px;background:#3B82F6;color:#fff;font-size:13px;font-weight:600;cursor:pointer;">
                    <i class="bi bi-plus-lg"></i> Criar
                </button>
            </div>
        </div>
        <div id="keyRevealSection" style="display:none;">
            <div class="warning-box">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span>Copie agora! Esta key <strong>não será exibida novamente</strong>.</span>
            </div>
            <div class="key-reveal-box">
                <span id="rawKeyText"></span>
                <button class="btn-copy" onclick="copyKey()" id="btnCopy">Copiar</button>
            </div>
            <button onclick="closeAndReload()" style="width:100%;padding:10px;border:none;border-radius:9px;background:#10B981;color:#fff;font-size:13.5px;font-weight:600;cursor:pointer;">
                <i class="bi bi-check-lg"></i> Feito, já copiei
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const API_KEY_STORE   = @json(route('settings.api-keys.store'));
const API_KEY_DESTROY = @json(route('settings.api-keys.destroy', ['apiKey' => '__ID__']));
const BASE_URL        = @json(url('/api/v1'));
const CSRF            = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

const PIPELINES_DATA  = {!! json_encode($pipelines) !!};
const CUSTOM_FIELDS   = {!! json_encode($customFields) !!};

// ── Modal nova key ──────────────────────────────────────────────────────────
function openNewKeyModal() {
    document.getElementById('newKeyName').value = '';
    document.getElementById('newKeyForm').style.display = '';
    document.getElementById('keyRevealSection').style.display = 'none';
    document.getElementById('modalNewKey').classList.add('open');
    setTimeout(() => document.getElementById('newKeyName').focus(), 100);
}

function closeNewKeyModal() {
    document.getElementById('modalNewKey').classList.remove('open');
}

function closeAndReload() {
    document.getElementById('modalNewKey').classList.remove('open');
    location.reload();
}

async function createKey() {
    const name = document.getElementById('newKeyName').value.trim();
    if (!name) { toastr.warning('Informe um nome para a API Key.'); return; }

    const btn = document.getElementById('btnCreateKey');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Criando...';

    try {
        const res  = await fetch(API_KEY_STORE, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ name }),
        });
        const data = await res.json();

        if (data.success) {
            document.getElementById('rawKeyText').textContent = data.raw_key;
            document.getElementById('newKeyForm').style.display = 'none';
            document.getElementById('keyRevealSection').style.display = '';
        } else {
            toastr.error('Erro ao criar API Key.');
        }
    } catch (e) {
        toastr.error('Erro de conexão.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-plus-lg"></i> Criar';
    }
}

function copyKey() {
    const text = document.getElementById('rawKeyText').textContent;
    navigator.clipboard.writeText(text).then(() => {
        const btn = document.getElementById('btnCopy');
        btn.textContent = 'Copiado!';
        btn.classList.add('copied');
        setTimeout(() => { btn.textContent = 'Copiar'; btn.classList.remove('copied'); }, 2000);
    });
}

// ── Revogar key ─────────────────────────────────────────────────────────────
function revokeKey(id, btn) {
    confirmAction({
        title: 'Revogar API Key',
        message: 'Sistemas que utilizam esta chave perderão acesso imediatamente.',
        confirmText: 'Revogar',
        onConfirm: async () => {
            btn.disabled = true;
            try {
                const res  = await fetch(API_KEY_DESTROY.replace('__ID__', id), {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': CSRF,
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();
                if (data.success) {
                    toastr.success('API Key revogada.');
                    document.getElementById(`key-row-${id}`)?.remove();
                } else {
                    toastr.error('Erro ao revogar.');
                    btn.disabled = false;
                }
            } catch (e) {
                toastr.error('Erro de conexão.');
                btn.disabled = false;
            }
        },
    });
}

// ── Toggle endpoint accordion ────────────────────────────────────────────────
function toggleEndpoint(id) {
    document.getElementById(id)?.classList.toggle('open');
}

// ── Builder helpers ──────────────────────────────────────────────────────────

// Toggle optional field input enabled/disabled
function toggleBldInput(id) {
    const el = document.getElementById(id);
    if (el) el.disabled = !el.disabled;
}

// Populate stage dropdown from pipelines data
function populateStagesFor(pipeSelId, stageSelId, filter) {
    const pId  = parseInt(document.getElementById(pipeSelId)?.value);
    const pipe = PIPELINES_DATA.find(p => p.id === pId);
    const sel  = document.getElementById(stageSelId);
    if (!sel) return;

    sel.innerHTML = '';
    const stages = (pipe?.stages || []).filter(s => {
        if (filter === 'won')  return s.is_won;
        if (filter === 'lost') return s.is_lost;
        return true; // 'all'
    });

    if (!stages.length) {
        const o = document.createElement('option');
        o.value = '';
        o.textContent = filter === 'won'
            ? '— Nenhuma etapa de ganho neste pipeline —'
            : filter === 'lost'
                ? '— Nenhuma etapa de perda neste pipeline —'
                : '— Nenhuma etapa disponível —';
        sel.appendChild(o);
    } else {
        stages.forEach(s => {
            const o = document.createElement('option');
            o.value = s.id;
            o.textContent = s.name + (s.is_won ? ' ✓' : s.is_lost ? ' ✗' : '');
            sel.appendChild(o);
        });
    }
}

// Format a curl command string
function formatCurl(method, path, body) {
    const bodyStr = JSON.stringify(body, null, 2);
    const indented = bodyStr.split('\n').map((l, i) => i === 0 ? l : '  ' + l).join('\n');
    return `curl -X ${method} ${BASE_URL}/${path} \\\n  -H 'X-API-Key: YOUR_API_KEY' \\\n  -H 'Content-Type: application/json' \\\n  -d '${indented}'`;
}

// Copy curl to clipboard
function copyCurl(preId, btn) {
    const text = document.getElementById(preId)?.textContent;
    if (!text) return;
    navigator.clipboard.writeText(text).then(() => {
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check-lg"></i> Copiado!';
        btn.classList.add('copied');
        setTimeout(() => { btn.innerHTML = orig; btn.classList.remove('copied'); }, 2000);
    });
}

// ── POST /leads builder ──────────────────────────────────────────────────────
function buildCreateCurl() {
    const name  = document.getElementById('bld-name')?.value  || 'João Silva';
    const body  = { name };

    // phone (optional toggle, default checked)
    if (document.getElementById('bld-phone-on')?.checked) {
        const v = document.getElementById('bld-phone')?.value;
        if (v) body.phone = v;
    }

    // pipeline_id / stage_id
    const pEl = document.getElementById('bld-create-pipeline');
    const sEl = document.getElementById('bld-create-stage');
    if (pEl?.value) body.pipeline_id = parseInt(pEl.value);
    if (sEl?.value) body.stage_id    = parseInt(sEl.value);

    // optional fields
    for (const f of ['email', 'source', 'notes']) {
        if (document.getElementById(`bld-${f}-on`)?.checked) {
            const v = document.getElementById(`bld-${f}`)?.value;
            if (v) body[f] = v;
        }
    }
    if (document.getElementById('bld-value-on')?.checked) {
        const v = parseFloat(document.getElementById('bld-value')?.value);
        if (!isNaN(v)) body.value = v;
    }

    // custom fields
    const cf = {};
    CUSTOM_FIELDS.forEach(f => {
        if (document.getElementById(`cfon-${f.name}`)?.checked) {
            cf[f.name] = document.getElementById(`cfv-${f.name}`)?.value || '';
        }
    });
    if (Object.keys(cf).length) body.custom_fields = cf;

    return formatCurl('POST', 'leads', body);
}

function updateCreateCurl() {
    const el = document.getElementById('curl-create');
    if (el) el.textContent = buildCreateCurl();
}

// ── Stage / Won / Lost mini builders ────────────────────────────────────────
function updateStageCurl() {
    const leadId = document.getElementById('stage-lead-id')?.value || '1';
    const body   = {};
    const pEl    = document.getElementById('stage-pipeline');
    const sEl    = document.getElementById('stage-stage');
    if (pEl?.value) body.pipeline_id = parseInt(pEl.value);
    if (sEl?.value) body.stage_id    = parseInt(sEl.value);
    const el = document.getElementById('curl-stage');
    if (el) el.textContent = formatCurl('PUT', `leads/${leadId}/stage`, body);
}

function updateWonCurl() {
    const leadId = document.getElementById('won-lead-id')?.value || '1';
    const body   = {};
    const sEl    = document.getElementById('won-stage');
    if (sEl?.value) body.stage_id = parseInt(sEl.value);
    const val = parseFloat(document.getElementById('won-value')?.value);
    if (!isNaN(val) && val > 0) body.value = val;
    const el = document.getElementById('curl-won');
    if (el) el.textContent = formatCurl('PUT', `leads/${leadId}/won`, body);
}

function updateLostCurl() {
    const leadId = document.getElementById('lost-lead-id')?.value || '1';
    const body   = {};
    const sEl    = document.getElementById('lost-stage');
    if (sEl?.value) body.stage_id = parseInt(sEl.value);
    const rid = parseInt(document.getElementById('lost-reason')?.value);
    if (!isNaN(rid) && rid > 0) body.reason_id = rid;
    const el = document.getElementById('curl-lost');
    if (el) el.textContent = formatCurl('PUT', `leads/${leadId}/lost`, body);
}

// ── Init ─────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    if (PIPELINES_DATA.length) {
        populateStagesFor('bld-create-pipeline', 'bld-create-stage', 'all');
        populateStagesFor('stage-pipeline',      'stage-stage',      'all');
        populateStagesFor('won-pipeline',        'won-stage',        'won');
        populateStagesFor('lost-pipeline',       'lost-stage',       'lost');
    }
    updateCreateCurl();
    updateStageCurl();
    updateWonCurl();
    updateLostCurl();
});

// Fechar modal com Esc
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeNewKeyModal();
});
</script>
@endpush

@extends('tenant.layouts.app')

@php
    $title    = 'Automações de Instagram';
    $pageIcon = 'instagram';
@endphp

@push('styles')
<style>
@keyframes spin { to { transform: rotate(360deg); } }

/* ── Banner ──────────────────────────────────────── */
.ig-banner {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 13px 16px;
    border-radius: 10px;
    font-size: 13.5px;
    margin-bottom: 20px;
    line-height: 1.5;
}
.ig-banner.warning {
    background: #fff7ed;
    border: 1px solid #fed7aa;
    color: #9a3412;
}
.ig-banner.info {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    color: #1e3a8a;
}
.ig-banner a { color: inherit; font-weight: 600; text-decoration: underline; }

/* ── Card ────────────────────────────────────────── */
.ig-card {
    background: #fff;
    border: 1px solid #e8eaf0;
    border-radius: 12px;
    overflow: hidden;
}
.ig-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid #f0f2f7;
}
.ig-card-head h2 {
    margin: 0;
    font-size: 14.5px;
    font-weight: 700;
    color: #111827;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* ── Empty state ─────────────────────────────────── */
.ig-empty {
    text-align: center;
    padding: 48px 24px;
    color: #9ca3af;
}
.ig-empty i { font-size: 36px; opacity: .25; display: block; margin-bottom: 12px; }
.ig-empty p { margin: 0; font-size: 13.5px; }

/* ── Automation item ─────────────────────────────── */
.ig-item {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 14px 20px;
    border-bottom: 1px solid #f0f2f7;
}
.ig-item:last-child { border-bottom: none; }

.ig-item-thumb {
    width: 52px;
    height: 52px;
    border-radius: 8px;
    object-fit: cover;
    flex-shrink: 0;
    background: #f3f4f6;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #9ca3af;
    font-size: 20px;
    overflow: hidden;
}
.ig-item-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.ig-item-body { flex: 1; min-width: 0; }
.ig-item-name {
    font-size: 13.5px;
    font-weight: 600;
    color: #111827;
    margin-bottom: 3px;
}
.ig-item-meta {
    font-size: 12px;
    color: #6b7280;
    margin-bottom: 6px;
}

.ig-chips { display: flex; flex-wrap: wrap; gap: 4px; margin-bottom: 6px; }
.ig-chip {
    background: #dbeafe;
    color: #1d4ed8;
    border-radius: 20px;
    padding: 2px 10px;
    font-size: 11.5px;
    font-weight: 500;
}

.ig-action-preview {
    font-size: 12px;
    color: #4b5563;
    display: flex;
    gap: 14px;
    flex-wrap: wrap;
}
.ig-action-preview span {
    display: flex;
    align-items: center;
    gap: 4px;
}

.ig-item-actions {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-shrink: 0;
    padding-top: 2px;
}

.btn-icon {
    width: 28px; height: 28px; border-radius: 7px;
    border: 1px solid #e8eaf0; background: #fff; color: #6b7280;
    display: inline-flex; align-items: center; justify-content: center;
    cursor: pointer; font-size: 13px; transition: all .15s;
}
.btn-icon:hover { background: #f0f4ff; color: #374151; }
.btn-icon.danger:hover { background: #fee2e2; color: #ef4444; border-color: #fca5a5; }

/* ── Toggle switch ───────────────────────────────── */
.ig-toggle {
    position: relative;
    display: inline-block;
    width: 36px;
    height: 20px;
    flex-shrink: 0;
}
.ig-toggle input { opacity: 0; width: 0; height: 0; }
.ig-toggle .slider {
    position: absolute; inset: 0;
    background: #d1d5db;
    border-radius: 20px;
    cursor: pointer;
    transition: .2s;
}
.ig-toggle .slider::before {
    content: '';
    position: absolute;
    width: 14px; height: 14px;
    left: 3px; bottom: 3px;
    background: #fff;
    border-radius: 50%;
    transition: .2s;
}
.ig-toggle input:checked + .slider { background: #2a84ef; }
.ig-toggle input:checked + .slider::before { transform: translateX(16px); }

/* ── Drawer lateral ──────────────────────────────── */
.ig-drawer-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.35);
    z-index: 199;
}
.ig-drawer-overlay.open { display: block; }

.ig-drawer {
    position: fixed;
    top: 0; right: 0;
    width: 480px;
    height: 100vh;
    background: #fff;
    box-shadow: -4px 0 32px rgba(0,0,0,.1);
    z-index: 200;
    display: flex;
    flex-direction: column;
    transform: translateX(100%);
    transition: transform .25s cubic-bezier(.4,0,.2,1);
    overflow: hidden;
}
.ig-drawer.open { transform: translateX(0); }

.ig-drawer-head {
    padding: 18px 22px;
    border-bottom: 1px solid #f0f2f7;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
}
.ig-drawer-head h3 { margin: 0; font-size: 15px; font-weight: 700; color: #1a1d23; }

.ig-drawer-body {
    flex: 1;
    overflow-y: auto;
    padding: 20px 22px;
}

.ig-drawer-foot {
    padding: 14px 22px;
    border-top: 1px solid #f0f2f7;
    background: #fafafa;
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    flex-shrink: 0;
}

/* ── Confirm modal ───────────────────────────────── */
.ig-confirm-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.5);
    z-index: 1100;
    align-items: center;
    justify-content: center;
}
.ig-confirm-overlay.open { display: flex; }
.ig-confirm-box {
    background: #fff;
    border-radius: 14px;
    padding: 26px 28px;
    width: 360px;
    max-width: 95vw;
    box-shadow: 0 20px 60px rgba(0,0,0,.2);
}
.ig-confirm-box h4 { margin: 0 0 8px; font-size: 15px; font-weight: 700; color: #111827; }
.ig-confirm-box p  { margin: 0 0 20px; font-size: 13.5px; color: #6b7280; }
.ig-confirm-actions { display: flex; gap: 8px; justify-content: flex-end; }

/* ── Form elements ───────────────────────────────── */
.form-group { margin-bottom: 14px; }
.form-group label {
    display: block;
    font-size: 11.5px;
    font-weight: 700;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: .05em;
    margin-bottom: 5px;
}
.form-control {
    width: 100%; padding: 8px 11px;
    border: 1.5px solid #e8eaf0; border-radius: 8px;
    font-size: 13.5px; color: #111827; background: #fff;
    font-family: inherit; box-sizing: border-box;
    transition: border-color .15s;
}
.form-control:focus { outline: none; border-color: #2a84ef; box-shadow: 0 0 0 3px rgba(42,132,239,.1); }
textarea.form-control { resize: vertical; min-height: 68px; }

/* ── Post picker ─────────────────────────────────── */
.post-scope-radio { display: flex; gap: 16px; margin-bottom: 10px; }
.post-scope-radio label { display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 13px; font-weight: 400; color: #374151; text-transform: none; letter-spacing: 0; }

.post-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 6px;
    max-height: 200px;
    overflow-y: auto;
}
.post-grid-item {
    position: relative;
    border-radius: 7px;
    overflow: hidden;
    cursor: pointer;
    border: 2px solid transparent;
    aspect-ratio: 1;
    background: #f3f4f6;
    transition: border-color .15s;
}
.post-grid-item img { width: 100%; height: 100%; object-fit: cover; display: block; }
.post-grid-item.selected { border-color: #2a84ef; }
.post-grid-item .post-check {
    position: absolute;
    top: 3px; right: 3px;
    background: #2a84ef;
    color: #fff;
    border-radius: 50%;
    width: 18px; height: 18px;
    display: none;
    align-items: center;
    justify-content: center;
    font-size: 10px;
}
.post-grid-item.selected .post-check { display: flex; }
.post-grid-placeholder {
    aspect-ratio: 1;
    background: #f3f4f6;
    border-radius: 7px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #d1d5db;
    font-size: 20px;
}
.load-more-posts { text-align: center; margin-top: 8px; }

/* ── Keyword chips ───────────────────────────────── */
.keyword-input-wrap {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    padding: 7px 10px;
    border: 1.5px solid #e8eaf0;
    border-radius: 8px;
    min-height: 40px;
    cursor: text;
    transition: border-color .15s;
}
.keyword-input-wrap:focus-within { border-color: #2a84ef; box-shadow: 0 0 0 3px rgba(42,132,239,.1); }
.kw-chip {
    background: #dbeafe;
    color: #1d4ed8;
    border-radius: 20px;
    padding: 2px 8px 2px 10px;
    font-size: 12px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 3px;
    white-space: nowrap;
}
.kw-chip button {
    background: none; border: none; cursor: pointer;
    color: #1d4ed8; padding: 0; font-size: 12px; line-height: 1;
}
.kw-input {
    border: none; outline: none;
    font-size: 13px; flex: 1; min-width: 80px;
    background: transparent; padding: 2px 0;
}

.match-type-radio { display: flex; gap: 16px; }
.match-type-radio label { display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 13px; font-weight: 400; color: #374151; text-transform: none; letter-spacing: 0; }

/* ── Buttons ─────────────────────────────────────── */
.btn-primary-ig {
    background: #2a84ef;
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 8px 18px;
    font-size: 13.5px;
    font-weight: 600;
    cursor: pointer;
    transition: background .15s;
}
.btn-primary-ig:hover { background: #1a6fd4; }
.btn-primary-ig:disabled { opacity: .55; cursor: not-allowed; }

.btn-secondary-ig {
    background: #f3f4f6;
    color: #374151;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 8px 18px;
    font-size: 13.5px;
    font-weight: 500;
    cursor: pointer;
}
.btn-secondary-ig:hover { background: #e5e7eb; }

.btn-new-ig {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #2a84ef;
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 8px 16px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: background .15s;
}
.btn-new-ig:hover { background: #1a6fd4; }
.btn-new-ig:disabled { opacity: .55; cursor: not-allowed; }

.btn-danger-ig {
    background: #ef4444;
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 8px 18px;
    font-size: 13.5px;
    font-weight: 600;
    cursor: pointer;
}
.btn-danger-ig:hover { background: #dc2626; }

.char-count { font-size: 11px; color: #9ca3af; text-align: right; margin-top: 3px; }
.char-count.warn   { color: #f59e0b; }
.char-count.danger { color: #ef4444; }

/* ── Métricas ─────────────────────────────────────── */
.ig-metrics {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 6px;
}
.ig-metric {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 11.5px;
    font-weight: 600;
    color: #2a84ef;
    background: #eff6ff;
    border-radius: 20px;
    padding: 2px 9px;
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 18px;
}
.section-title    { font-size: 15px; font-weight: 700; color: #1a1d23; }
.section-subtitle { font-size: 13px; color: #9ca3af; margin-top: 3px; }
</style>
@endpush

@section('content')
<div class="page-container">

    <div class="section-header">
        <div>
            <div class="section-title">Automações de Instagram</div>
            <div class="section-subtitle">Responda comentários e envie DMs automaticamente com base em palavras-chave.</div>
        </div>
    </div>

    {{-- ── Banners ──────────────────────────────────────────────── --}}
    @if(! $instance || $instance->status !== 'connected')
        <div class="ig-banner info">
            <i class="bi bi-info-circle-fill" style="font-size:17px;flex-shrink:0;margin-top:1px;"></i>
            <span>
                Instagram não está conectado. Para usar Automações,
                <a href="{{ route('settings.integrations.index') }}">vá em Integrações</a> e conecte sua conta.
            </span>
        </div>
    @endif

    {{-- ── Card principal ──────────────────────────────────────── --}}
    <div class="ig-card">
        <div class="ig-card-head">
            <h2>
                <i class="bi bi-chat-square-heart" style="color:#2a84ef;"></i>
                Automações de Comentários
            </h2>
            <button class="btn-new-ig" onclick="openModal()"
                    {{ (! $instance || $instance->status !== 'connected') ? 'disabled' : '' }}>
                <i class="bi bi-plus-lg"></i> Nova Automação
            </button>
        </div>

        {{-- Lista — sempre presente no DOM para que prependItem() funcione --}}
        <div id="automationList">
            @foreach($automations as $auto)
                <div class="ig-item" data-id="{{ $auto->id }}" id="auto-{{ $auto->id }}">
                    <div class="ig-item-thumb">
                        @if($auto->media_thumbnail_url)
                            <img src="{{ $auto->media_thumbnail_url }}" alt=""
                                 onerror="this.parentElement.innerHTML='<i class=\'bi bi-grid-3x3\'></i>'">
                        @else
                            <i class="bi bi-grid-3x3"></i>
                        @endif
                    </div>
                    <div class="ig-item-body">
                        <div class="ig-item-name">
                            {{ $auto->name ?: ($auto->media_id ? 'Publicação específica' : 'Todos os posts') }}
                        </div>
                        <div class="ig-item-meta">
                            {{ $auto->match_type === 'all' ? 'Todas as palavras' : 'Qualquer palavra' }}
                            &bull; {{ count($auto->keywords) }} palavra(s)-chave
                        </div>
                        <div class="ig-chips">
                            @foreach($auto->keywords as $kw)
                                <span class="ig-chip">{{ $kw }}</span>
                            @endforeach
                        </div>
                        <div class="ig-action-preview">
                            @if($auto->reply_comment)
                                <span>
                                    <i class="bi bi-chat-left-text" style="color:#2a84ef;"></i>
                                    {{ Str::limit($auto->reply_comment, 50) }}
                                </span>
                            @endif
                            @if($auto->dm_message)
                                <span>
                                    <i class="bi bi-envelope-fill" style="color:#2a84ef;"></i>
                                    {{ Str::limit($auto->dm_message, 50) }}
                                </span>
                            @endif
                        </div>
                        @if($auto->comments_replied > 0 || $auto->dms_sent > 0)
                            <div class="ig-metrics">
                                @if($auto->comments_replied > 0)
                                    <span class="ig-metric">
                                        <i class="bi bi-chat-left-text"></i>
                                        {{ number_format($auto->comments_replied) }} comentário(s) respondido(s)
                                    </span>
                                @endif
                                @if($auto->dms_sent > 0)
                                    <span class="ig-metric">
                                        <i class="bi bi-send-fill"></i>
                                        {{ number_format($auto->dms_sent) }} DM(s) enviada(s)
                                    </span>
                                @endif
                            </div>
                        @endif
                    </div>
                    <div class="ig-item-actions">
                        <label class="ig-toggle" title="{{ $auto->is_active ? 'Ativa' : 'Inativa' }}">
                            <input type="checkbox" {{ $auto->is_active ? 'checked' : '' }}
                                   onchange="toggleAuto({{ $auto->id }}, this)">
                            <span class="slider"></span>
                        </label>
                        <button class="btn-icon" title="Editar" onclick="editAuto({{ $auto->id }})">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn-icon danger" title="Excluir"
                                onclick="confirmDelete({{ $auto->id }})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            @endforeach

            @if($automations->isEmpty())
                <div class="ig-empty" id="emptyState">
                    <i class="bi bi-robot"></i>
                    <p>Nenhuma automação criada ainda.<br>
                       Clique em <strong>Nova Automação</strong> para começar.</p>
                </div>
            @endif
        </div>
    </div>

</div>

{{-- ── Drawer Nova/Editar Automação ───────────────────────────────── --}}
<div id="igOverlay" class="ig-drawer-overlay" onclick="closeModal()"></div>
<aside id="igDrawer" class="ig-drawer">
    <div class="ig-drawer-head">
        <h3 id="modalTitle">Nova Automação</h3>
        <button class="btn-icon" onclick="closeModal()"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="ig-drawer-body">
        <input type="hidden" id="editingId" value="">

        {{-- Nome --}}
        <div class="form-group">
            <label>Nome <span style="font-weight:400;text-transform:none;letter-spacing:0;color:#9ca3af;">(opcional)</span></label>
            <input type="text" id="autoName" class="form-control"
                   placeholder="Ex: Responder sobre preços" maxlength="100">
        </div>

        {{-- Post --}}
        <div class="form-group">
            <label>Publicação alvo</label>
            <div class="post-scope-radio">
                <label>
                    <input type="radio" name="postScope" value="all" checked onchange="onScopeChange(this.value)">
                    Todos os posts
                </label>
                <label>
                    <input type="radio" name="postScope" value="specific" onchange="onScopeChange(this.value)">
                    Publicação específica
                </label>
            </div>
            <div id="postPickerWrap" style="display:none;">
                <div id="postGrid" class="post-grid">
                    <div class="post-grid-placeholder">
                        <i class="bi bi-arrow-repeat" style="animation:spin 1s linear infinite;"></i>
                    </div>
                </div>
                <div class="load-more-posts" id="loadMoreWrap" style="display:none;">
                    <button class="btn-secondary-ig" style="font-size:12px;padding:5px 14px;" onclick="loadMorePosts()">
                        <i class="bi bi-chevron-down"></i> Carregar mais
                    </button>
                </div>
            </div>
            <input type="hidden" id="selectedMediaId" value="">
            <input type="hidden" id="selectedMediaThumb" value="">
            <input type="hidden" id="selectedMediaCaption" value="">
        </div>

        {{-- Keywords --}}
        <div class="form-group">
            <label>Palavras-chave <span style="font-weight:400;text-transform:none;letter-spacing:0;color:#9ca3af;">(Enter ou vírgula para adicionar)</span></label>
            <div class="keyword-input-wrap" id="kwWrap"
                 onclick="document.getElementById('kwInput').focus()">
                <input type="text" id="kwInput" class="kw-input" placeholder="Digite uma palavra...">
            </div>
        </div>

        {{-- Match type --}}
        <div class="form-group">
            <label>Correspondência</label>
            <div class="match-type-radio">
                <label>
                    <input type="radio" name="matchType" value="any" checked>
                    Qualquer palavra (OU)
                </label>
                <label>
                    <input type="radio" name="matchType" value="all">
                    Todas as palavras (E)
                </label>
            </div>
        </div>

        {{-- Reply comment --}}
        <div class="form-group">
            <label>
                <i class="bi bi-chat-left-text" style="color:#2a84ef;font-size:11px;"></i>
                Responder ao comentário
                <span style="font-weight:400;text-transform:none;letter-spacing:0;color:#9ca3af;">(opcional)</span>
            </label>
            <textarea id="replyComment" class="form-control" maxlength="2200"
                      placeholder="Resposta pública postada no comentário..."
                      oninput="updateCount('replyComment','countReply',2200)"></textarea>
            <div class="char-count" id="countReply">0 / 2200</div>
        </div>

        {{-- DM message --}}
        <div class="form-group" style="margin-bottom:0;">
            <label>
                <i class="bi bi-envelope-fill" style="color:#2a84ef;font-size:11px;"></i>
                Enviar DM
                <span style="font-weight:400;text-transform:none;letter-spacing:0;color:#9ca3af;">(opcional)</span>
            </label>
            <textarea id="dmMessage" class="form-control" maxlength="1000"
                      placeholder="Mensagem privada enviada no inbox..."
                      oninput="updateCount('dmMessage','countDm',1000)"></textarea>
            <div class="char-count" id="countDm">0 / 1000</div>
        </div>
    </div>
    <div class="ig-drawer-foot">
        <button class="btn-secondary-ig" onclick="closeModal()">Cancelar</button>
        <button class="btn-primary-ig" id="saveBtn" onclick="saveAutomation()">Salvar</button>
    </div>
</aside>

{{-- ── Modal Confirmação de Exclusão ─────────────────────────────── --}}
<div id="confirmModal" class="ig-confirm-overlay" onclick="if(event.target===this)closeConfirm()">
    <div class="ig-confirm-box">
        <h4><i class="bi bi-trash" style="color:#ef4444;margin-right:6px;"></i>Excluir Automação</h4>
        <p>Tem certeza que deseja excluir esta automação? Esta ação não pode ser desfeita.</p>
        <div class="ig-confirm-actions">
            <button class="btn-secondary-ig" onclick="closeConfirm()">Cancelar</button>
            <button class="btn-danger-ig" id="confirmDeleteBtn" onclick="executeDelete()">Excluir</button>
        </div>
    </div>
</div>

@php
    $igStoreUrl  = route('settings.ig-automations.store');
    $igUpdUrl    = route('settings.ig-automations.update',  ['automation' => '__ID__']);
    $igDelUrl    = route('settings.ig-automations.destroy', ['automation' => '__ID__']);
    $igToggleUrl = route('settings.ig-automations.toggle',  ['automation' => '__ID__']);
    $igPostsUrl  = route('settings.ig-automations.posts');
@endphp
@endsection

@push('scripts')
<script>
const CSRF       = '{{ csrf_token() }}';
const STORE_URL  = '{{ $igStoreUrl }}';
const UPD_URL    = '{{ $igUpdUrl }}';
const DEL_URL    = '{{ $igDelUrl }}';
const TOGGLE_URL = '{{ $igToggleUrl }}';
const POSTS_URL  = '{{ $igPostsUrl }}';

let automations    = {!! json_encode($automations) !!};
let postNextCursor = null;
let postsLoaded    = false;
let keywords       = [];
let pendingDeleteId = null;

// ── Modal Nova/Editar ─────────────────────────────────────────────────────
function openModal(auto = null) {
    document.getElementById('editingId').value              = auto ? auto.id : '';
    document.getElementById('modalTitle').textContent       = auto ? 'Editar Automação' : 'Nova Automação';
    document.getElementById('autoName').value               = auto?.name ?? '';
    document.getElementById('selectedMediaId').value        = auto?.media_id ?? '';
    document.getElementById('selectedMediaThumb').value     = auto?.media_thumbnail_url ?? '';
    document.getElementById('selectedMediaCaption').value   = auto?.media_caption ?? '';
    document.getElementById('replyComment').value           = auto?.reply_comment ?? '';
    document.getElementById('dmMessage').value              = auto?.dm_message ?? '';

    updateCount('replyComment', 'countReply', 2200);
    updateCount('dmMessage', 'countDm', 1000);

    keywords = auto ? [...(auto.keywords || [])] : [];
    renderKwChips();

    const scope = auto?.media_id ? 'specific' : 'all';
    document.querySelectorAll('[name="postScope"]').forEach(r => r.checked = (r.value === scope));
    onScopeChange(scope, auto?.media_id);

    const mt = auto?.match_type ?? 'any';
    document.querySelectorAll('[name="matchType"]').forEach(r => r.checked = (r.value === mt));

    document.getElementById('igOverlay').classList.add('open');
    document.getElementById('igDrawer').classList.add('open');
}

function closeModal() {
    document.getElementById('igOverlay').classList.remove('open');
    document.getElementById('igDrawer').classList.remove('open');
    postsLoaded    = false;
    postNextCursor = null;
}

function editAuto(id) {
    const auto = automations.find(a => a.id == id);
    if (auto) openModal(auto);
}

// ── Confirm delete modal ──────────────────────────────────────────────────
function confirmDelete(id) {
    pendingDeleteId = id;
    document.getElementById('confirmModal').classList.add('open');
}

function closeConfirm() {
    pendingDeleteId = null;
    document.getElementById('confirmModal').classList.remove('open');
}

async function executeDelete() {
    const id  = pendingDeleteId;
    const btn = document.getElementById('confirmDeleteBtn');
    if (!id) return;

    btn.disabled = true;
    btn.textContent = 'Excluindo…';

    try {
        const res = await fetch(DEL_URL.replace('__ID__', id), {
            method: 'DELETE',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
        });
        if (!res.ok) { alert('Erro ao excluir.'); return; }
        automations = automations.filter(a => a.id != id);
        document.getElementById(`auto-${id}`)?.remove();
        checkEmpty();
        closeConfirm();
    } catch {
        alert('Erro de rede. Tente novamente.');
    } finally {
        btn.disabled    = false;
        btn.textContent = 'Excluir';
    }
}

// ── Post scope ────────────────────────────────────────────────────────────
function onScopeChange(value, preselectMediaId = null) {
    const wrap = document.getElementById('postPickerWrap');
    if (value === 'specific') {
        wrap.style.display = 'block';
        if (!postsLoaded) loadPosts(preselectMediaId);
    } else {
        wrap.style.display = 'none';
        document.getElementById('selectedMediaId').value = '';
    }
}

async function loadPosts(preselectMediaId = null) {
    postsLoaded = true;
    const grid  = document.getElementById('postGrid');
    grid.innerHTML = '<div class="post-grid-placeholder"><i class="bi bi-arrow-repeat" style="animation:spin 1s linear infinite;font-size:20px;"></i></div>';

    try {
        const res  = await fetch(POSTS_URL, { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        if (data.error) {
            grid.innerHTML = `<p style="color:#ef4444;font-size:12px;padding:8px;">${escHtml(data.error)}</p>`;
            return;
        }
        grid.innerHTML = '';
        (data.data || []).forEach(p => appendPostItem(p, preselectMediaId));
        postNextCursor = data.next_cursor;
        document.getElementById('loadMoreWrap').style.display = postNextCursor ? 'block' : 'none';
    } catch {
        grid.innerHTML = '<p style="color:#ef4444;font-size:12px;padding:8px;">Erro ao carregar publicações.</p>';
    }
}

async function loadMorePosts() {
    if (!postNextCursor) return;
    try {
        const res  = await fetch(`${POSTS_URL}?after=${encodeURIComponent(postNextCursor)}`, { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        (data.data || []).forEach(p => appendPostItem(p));
        postNextCursor = data.next_cursor;
        document.getElementById('loadMoreWrap').style.display = postNextCursor ? 'block' : 'none';
    } catch {}
}

function appendPostItem(post, preselectId = null) {
    const grid = document.getElementById('postGrid');
    const div  = document.createElement('div');
    div.className       = 'post-grid-item';
    div.dataset.id      = post.id;
    div.dataset.thumb   = post.thumbnail_url ?? '';
    div.dataset.caption = post.caption ?? '';
    div.title           = post.caption || '';

    const img = post.thumbnail_url
        ? `<img src="${escHtml(post.thumbnail_url)}" alt="" loading="lazy">`
        : `<div style="height:100%;display:flex;align-items:center;justify-content:center;color:#9ca3af;font-size:18px;"><i class="bi bi-image"></i></div>`;

    div.innerHTML = `${img}<div class="post-check"><i class="bi bi-check"></i></div>`;

    if (post.id === preselectId) {
        div.classList.add('selected');
        document.getElementById('selectedMediaId').value      = post.id;
        document.getElementById('selectedMediaThumb').value   = post.thumbnail_url ?? '';
        document.getElementById('selectedMediaCaption').value = post.caption ?? '';
    }

    div.onclick = () => selectPost(div, post);
    grid.appendChild(div);
}

function selectPost(el, post) {
    document.querySelectorAll('.post-grid-item').forEach(d => d.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('selectedMediaId').value      = post.id;
    document.getElementById('selectedMediaThumb').value   = post.thumbnail_url ?? '';
    document.getElementById('selectedMediaCaption').value = post.caption ?? '';
}

// ── Keyword chips ─────────────────────────────────────────────────────────
function renderKwChips() {
    const wrap  = document.getElementById('kwWrap');
    const input = document.getElementById('kwInput');
    wrap.querySelectorAll('.kw-chip').forEach(c => c.remove());
    keywords.forEach((kw, i) => {
        const chip = document.createElement('span');
        chip.className = 'kw-chip';
        chip.innerHTML = `${escHtml(kw)}<button type="button" onclick="removeKw(${i})">×</button>`;
        wrap.insertBefore(chip, input);
    });
}

function addKw(value) {
    const kw = value.trim().replace(/,+$/, '').trim();
    if (kw && !keywords.includes(kw)) { keywords.push(kw); renderKwChips(); }
}

function removeKw(i) { keywords.splice(i, 1); renderKwChips(); }

document.getElementById('kwInput').addEventListener('keydown', function(e) {
    if (e.key === 'Enter' || e.key === ',') {
        e.preventDefault();
        addKw(this.value);
        this.value = '';
    } else if (e.key === 'Backspace' && this.value === '' && keywords.length) {
        keywords.pop();
        renderKwChips();
    }
});
document.getElementById('kwInput').addEventListener('blur', function() {
    if (this.value.trim()) { addKw(this.value); this.value = ''; }
});

// ── Char counter ──────────────────────────────────────────────────────────
function updateCount(inputId, countId, max) {
    const len = document.getElementById(inputId).value.length;
    const el  = document.getElementById(countId);
    el.textContent = `${len} / ${max}`;
    el.className   = 'char-count' + (len >= max ? ' danger' : len > max * .88 ? ' warn' : '');
}

// ── Save ──────────────────────────────────────────────────────────────────
async function saveAutomation() {
    if (keywords.length === 0) { alert('Adicione pelo menos uma palavra-chave.'); return; }
    const reply = document.getElementById('replyComment').value.trim();
    const dm    = document.getElementById('dmMessage').value.trim();
    if (!reply && !dm) { alert('Defina pelo menos uma ação: resposta ao comentário ou DM.'); return; }

    const scope = document.querySelector('[name="postScope"]:checked').value;
    const id    = document.getElementById('editingId').value;

    const body = {
        name:                 document.getElementById('autoName').value.trim() || null,
        media_id:             scope === 'specific' ? (document.getElementById('selectedMediaId').value || null) : null,
        media_thumbnail_url:  scope === 'specific' ? (document.getElementById('selectedMediaThumb').value || null) : null,
        media_caption:        scope === 'specific' ? (document.getElementById('selectedMediaCaption').value || null) : null,
        keywords,
        match_type:           document.querySelector('[name="matchType"]:checked').value,
        reply_comment:        reply || null,
        dm_message:           dm || null,
    };

    const isEdit = !!id;
    const url    = isEdit ? UPD_URL.replace('__ID__', id) : STORE_URL;
    const method = isEdit ? 'PUT' : 'POST';

    const btn = document.getElementById('saveBtn');
    btn.disabled    = true;
    btn.textContent = 'Salvando…';

    try {
        const res  = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                'Accept':       'application/json',
                'X-CSRF-TOKEN': CSRF,
            },
            body: JSON.stringify(body),
        });
        const data = await res.json();

        if (!res.ok) { alert(data.error || data.message || 'Erro ao salvar.'); return; }

        const auto = data.automation;
        if (isEdit) {
            const idx = automations.findIndex(a => a.id == id);
            if (idx >= 0) automations[idx] = auto;
            renderItem(auto, document.getElementById(`auto-${id}`));
        } else {
            automations.unshift(auto);
            prependItem(auto);
        }
        closeModal();
    } catch (e) {
        alert('Erro ao salvar: ' + e.message);
    } finally {
        btn.disabled    = false;
        btn.textContent = 'Salvar';
    }
}

// ── Toggle ────────────────────────────────────────────────────────────────
async function toggleAuto(id, checkbox) {
    try {
        const res  = await fetch(TOGGLE_URL.replace('__ID__', id), {
            method: 'PATCH',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
        });
        if (!res.ok) { checkbox.checked = !checkbox.checked; return; }
        const data = await res.json();
        const auto = automations.find(a => a.id == id);
        if (auto) auto.is_active = data.is_active;
    } catch { checkbox.checked = !checkbox.checked; }
}

// ── Render helpers ────────────────────────────────────────────────────────
function checkEmpty() {
    const list  = document.getElementById('automationList');
    const items = list.querySelectorAll('.ig-item');
    const empty = document.getElementById('emptyState');
    if (items.length === 0) {
        if (!empty) {
            list.innerHTML = '<div class="ig-empty" id="emptyState"><i class="bi bi-robot"></i><p>Nenhuma automação criada ainda.<br>Clique em <strong>Nova Automação</strong> para começar.</p></div>';
        }
    } else {
        empty?.remove();
    }
}

function renderItem(auto, el) {
    if (!el) return;
    const thumbHtml = auto.media_thumbnail_url
        ? `<img src="${escHtml(auto.media_thumbnail_url)}" alt="" onerror="this.parentElement.innerHTML='<i class=&quot;bi bi-grid-3x3&quot;></i>'">`
        : '<i class="bi bi-grid-3x3"></i>';

    const kwChips   = (auto.keywords || []).map(k => `<span class="ig-chip">${escHtml(k)}</span>`).join('');
    const replyPrev = auto.reply_comment
        ? `<span><i class="bi bi-chat-left-text" style="color:#2a84ef;"></i> ${escHtml(auto.reply_comment.substring(0, 50))}${auto.reply_comment.length > 50 ? '…' : ''}</span>` : '';
    const dmPrev    = auto.dm_message
        ? `<span><i class="bi bi-envelope-fill" style="color:#2a84ef;"></i> ${escHtml(auto.dm_message.substring(0, 50))}${auto.dm_message.length > 50 ? '…' : ''}</span>` : '';

    const commentMetric = (auto.comments_replied > 0)
        ? `<span class="ig-metric"><i class="bi bi-chat-left-text"></i> ${auto.comments_replied} comentário(s) respondido(s)</span>` : '';
    const dmMetric = (auto.dms_sent > 0)
        ? `<span class="ig-metric"><i class="bi bi-send-fill"></i> ${auto.dms_sent} DM(s) enviada(s)</span>` : '';
    const metricsHtml = (commentMetric || dmMetric)
        ? `<div class="ig-metrics">${commentMetric}${dmMetric}</div>` : '';

    el.innerHTML = `
        <div class="ig-item-thumb">${thumbHtml}</div>
        <div class="ig-item-body">
            <div class="ig-item-name">${escHtml(auto.name || (auto.media_id ? 'Publicação específica' : 'Todos os posts'))}</div>
            <div class="ig-item-meta">${auto.match_type === 'all' ? 'Todas as palavras' : 'Qualquer palavra'} &bull; ${auto.keywords?.length ?? 0} palavra(s)-chave</div>
            <div class="ig-chips">${kwChips}</div>
            <div class="ig-action-preview">${replyPrev}${dmPrev}</div>
            ${metricsHtml}
        </div>
        <div class="ig-item-actions">
            <label class="ig-toggle">
                <input type="checkbox" ${auto.is_active ? 'checked' : ''} onchange="toggleAuto(${auto.id}, this)">
                <span class="slider"></span>
            </label>
            <button class="btn-icon" onclick="editAuto(${auto.id})"><i class="bi bi-pencil"></i></button>
            <button class="btn-icon danger" onclick="confirmDelete(${auto.id})"><i class="bi bi-trash"></i></button>
        </div>`;
}

function prependItem(auto) {
    const list  = document.getElementById('automationList');
    document.getElementById('emptyState')?.remove();

    const el      = document.createElement('div');
    el.className  = 'ig-item';
    el.dataset.id = auto.id;
    el.id         = `auto-${auto.id}`;
    list.prepend(el);
    renderItem(auto, el);
}

function escHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}
</script>
@endpush

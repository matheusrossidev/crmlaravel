@extends('tenant.layouts.app')

@php
    $title    = 'Automações de Instagram';
    $pageIcon = 'instagram';
@endphp

@push('styles')
<style>
    .ig-auto-wrap {
        max-width: 900px;
    }

    /* ── Banner ────────────────────────────────── */
    .ig-banner {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 18px;
        border-radius: 10px;
        font-size: 13.5px;
        margin-bottom: 20px;
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

    /* ── Card ──────────────────────────────────── */
    .ig-card {
        background: #fff;
        border: 1px solid #e8eaf0;
        border-radius: 14px;
        overflow: hidden;
    }
    .ig-card-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 18px 22px;
        border-bottom: 1px solid #f0f2f7;
    }
    .ig-card-head h2 {
        margin: 0;
        font-size: 15px;
        font-weight: 700;
        color: #111827;
    }

    /* ── Empty state ───────────────────────────── */
    .ig-empty {
        text-align: center;
        padding: 48px 24px;
        color: #9ca3af;
    }
    .ig-empty i { font-size: 40px; margin-bottom: 12px; display: block; }
    .ig-empty p { margin: 0; font-size: 14px; }

    /* ── Automation item ───────────────────────── */
    .ig-item {
        display: flex;
        align-items: flex-start;
        gap: 16px;
        padding: 16px 22px;
        border-bottom: 1px solid #f0f2f7;
    }
    .ig-item:last-child { border-bottom: none; }

    .ig-item-thumb {
        width: 56px;
        height: 56px;
        border-radius: 8px;
        object-fit: cover;
        flex-shrink: 0;
        background: #f3f4f6;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #9ca3af;
        font-size: 22px;
    }
    .ig-item-thumb img {
        width: 100%;
        height: 100%;
        border-radius: 8px;
        object-fit: cover;
    }

    .ig-item-body { flex: 1; min-width: 0; }
    .ig-item-name {
        font-size: 13.5px;
        font-weight: 600;
        color: #111827;
        margin-bottom: 4px;
    }
    .ig-item-meta {
        font-size: 12.5px;
        color: #6b7280;
        margin-bottom: 6px;
    }

    .ig-chips { display: flex; flex-wrap: wrap; gap: 4px; margin-bottom: 6px; }
    .ig-chip {
        background: #ede9fe;
        color: #6d28d9;
        border-radius: 20px;
        padding: 2px 10px;
        font-size: 12px;
        font-weight: 500;
    }

    .ig-action-preview {
        font-size: 12px;
        color: #4b5563;
        display: flex;
        gap: 12px;
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
        gap: 8px;
        flex-shrink: 0;
    }

    .btn-icon {
        background: none;
        border: none;
        cursor: pointer;
        padding: 6px 8px;
        border-radius: 8px;
        color: #6b7280;
        transition: background .15s, color .15s;
        font-size: 15px;
    }
    .btn-icon:hover { background: #f3f4f6; color: #111827; }
    .btn-icon.danger:hover { background: #fef2f2; color: #dc2626; }

    /* ── Toggle switch ─────────────────────────── */
    .ig-toggle {
        position: relative;
        display: inline-block;
        width: 38px;
        height: 22px;
        flex-shrink: 0;
    }
    .ig-toggle input { opacity: 0; width: 0; height: 0; }
    .ig-toggle .slider {
        position: absolute; inset: 0;
        background: #d1d5db;
        border-radius: 22px;
        cursor: pointer;
        transition: .2s;
    }
    .ig-toggle .slider::before {
        content: '';
        position: absolute;
        width: 16px; height: 16px;
        left: 3px; bottom: 3px;
        background: #fff;
        border-radius: 50%;
        transition: .2s;
    }
    .ig-toggle input:checked + .slider { background: #25d366; }
    .ig-toggle input:checked + .slider::before { transform: translateX(16px); }

    /* ── Modal ─────────────────────────────────── */
    .ig-modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,.45);
        z-index: 1050;
        align-items: center;
        justify-content: center;
    }
    .ig-modal-overlay.open { display: flex; }
    .ig-modal {
        background: #fff;
        border-radius: 16px;
        width: 100%;
        max-width: 600px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 20px 60px rgba(0,0,0,.18);
        margin: 16px;
    }
    .ig-modal-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 20px 24px 16px;
        border-bottom: 1px solid #f0f2f7;
        position: sticky;
        top: 0;
        background: #fff;
        z-index: 1;
    }
    .ig-modal-head h3 { margin: 0; font-size: 16px; font-weight: 700; }
    .ig-modal-body { padding: 20px 24px; }
    .ig-modal-foot {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        padding: 16px 24px;
        border-top: 1px solid #f0f2f7;
        background: #fafafa;
        position: sticky;
        bottom: 0;
    }

    .form-group { margin-bottom: 16px; }
    .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #374151; }
    .form-control {
        width: 100%; padding: 8px 12px; border: 1px solid #e5e7eb;
        border-radius: 8px; font-size: 13.5px; color: #111827;
        background: #fff;
    }
    .form-control:focus { outline: none; border-color: #6d28d9; box-shadow: 0 0 0 3px rgba(109,40,217,.1); }
    textarea.form-control { resize: vertical; min-height: 70px; }

    /* ── Post picker ───────────────────────────── */
    .post-scope-radio { display: flex; gap: 16px; margin-bottom: 12px; }
    .post-scope-radio label { display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 13.5px; font-weight: 400; }

    .post-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 8px;
        max-height: 220px;
        overflow-y: auto;
        padding: 4px;
    }
    .post-grid-item {
        position: relative;
        border-radius: 8px;
        overflow: hidden;
        cursor: pointer;
        border: 2px solid transparent;
        aspect-ratio: 1;
        background: #f3f4f6;
    }
    .post-grid-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .post-grid-item.selected { border-color: #6d28d9; }
    .post-grid-item .post-check {
        position: absolute;
        top: 4px; right: 4px;
        background: #6d28d9;
        color: #fff;
        border-radius: 50%;
        width: 20px; height: 20px;
        display: none;
        align-items: center;
        justify-content: center;
        font-size: 11px;
    }
    .post-grid-item.selected .post-check { display: flex; }
    .post-grid-placeholder {
        aspect-ratio: 1;
        background: #f3f4f6;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #d1d5db;
        font-size: 24px;
    }
    .load-more-posts {
        text-align: center;
        margin-top: 8px;
    }

    /* ── Keyword chips ──────────────────────────── */
    .keyword-input-wrap {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        padding: 8px 10px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        min-height: 42px;
        cursor: text;
    }
    .keyword-input-wrap:focus-within { border-color: #6d28d9; box-shadow: 0 0 0 3px rgba(109,40,217,.1); }
    .kw-chip {
        background: #ede9fe;
        color: #6d28d9;
        border-radius: 20px;
        padding: 2px 8px 2px 10px;
        font-size: 12.5px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 4px;
        white-space: nowrap;
    }
    .kw-chip button {
        background: none;
        border: none;
        cursor: pointer;
        color: #6d28d9;
        padding: 0;
        font-size: 12px;
        line-height: 1;
    }
    .kw-input {
        border: none;
        outline: none;
        font-size: 13.5px;
        flex: 1;
        min-width: 80px;
        background: transparent;
        padding: 2px 0;
    }

    .match-type-radio { display: flex; gap: 16px; }
    .match-type-radio label { display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 13px; font-weight: 400; }

    .btn-primary {
        background: #6d28d9;
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 9px 18px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
    }
    .btn-primary:disabled { opacity: .55; cursor: not-allowed; }
    .btn-secondary {
        background: #f3f4f6;
        color: #374151;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 9px 18px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
    }
    .btn-new {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: linear-gradient(135deg, #833ab4, #fd1d1d, #fcb045);
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 8px 16px;
        font-size: 13.5px;
        font-weight: 600;
        cursor: pointer;
    }
    .btn-new:disabled { opacity: .55; cursor: not-allowed; }

    .char-count { font-size: 11.5px; color: #9ca3af; text-align: right; margin-top: 3px; }
    .char-count.warn { color: #f59e0b; }
    .char-count.danger { color: #ef4444; }
</style>
@endpush

@section('content')

<div class="ig-auto-wrap">

    {{-- ── Banners ──────────────────────────────────────────────── --}}
    @if(! $instance || $instance->status !== 'connected')
        <div class="ig-banner info">
            <i class="bi bi-info-circle-fill" style="font-size:18px;flex-shrink:0;"></i>
            <span>
                Instagram não está conectado. Para usar Automações,
                <a href="{{ route('settings.integrations.index') }}">vá em Integrações</a> e conecte sua conta.
            </span>
        </div>
    @else
        <div class="ig-banner warning">
            <i class="bi bi-exclamation-triangle-fill" style="font-size:18px;flex-shrink:0;"></i>
            <div>
                Esta funcionalidade requer a permissão <strong>instagram_business_manage_comments</strong>.<br>
                Se você conectou o Instagram antes de hoje, <strong>desconecte e reconecte</strong> para ativar a permissão.
                <a href="{{ route('settings.integrations.index') }}">Ir para Integrações →</a>
            </div>
        </div>
    @endif

    {{-- ── Card principal ──────────────────────────────────────── --}}
    <div class="ig-card">
        <div class="ig-card-head">
            <h2><i class="bi bi-chat-square-heart" style="color:#833ab4;margin-right:8px;"></i>Automações de Comentários</h2>
            <button class="btn-new" onclick="openModal()" {{ (! $instance || $instance->status !== 'connected') ? 'disabled' : '' }}>
                <i class="bi bi-plus-lg"></i> Nova Automação
            </button>
        </div>

        @if($automations->isEmpty())
            <div class="ig-empty">
                <i class="bi bi-robot"></i>
                <p>Nenhuma automação criada ainda.<br>
                   Clique em <strong>Nova Automação</strong> para começar.</p>
            </div>
        @else
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
                                    <span><i class="bi bi-chat-left-text" style="color:#833ab4;"></i>
                                        {{ Str::limit($auto->reply_comment, 50) }}
                                    </span>
                                @endif
                                @if($auto->dm_message)
                                    <span><i class="bi bi-envelope-fill" style="color:#3b82f6;"></i>
                                        {{ Str::limit($auto->dm_message, 50) }}
                                    </span>
                                @endif
                            </div>
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
                            <button class="btn-icon danger" title="Excluir" onclick="deleteAuto({{ $auto->id }})">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

</div>

{{-- ── Modal ──────────────────────────────────────────────────────── --}}
<div id="igModal" class="ig-modal-overlay" onclick="if(event.target===this)closeModal()">
    <div class="ig-modal">
        <div class="ig-modal-head">
            <h3 id="modalTitle">Nova Automação</h3>
            <button class="btn-icon" onclick="closeModal()"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="ig-modal-body">
            <input type="hidden" id="editingId" value="">

            {{-- Nome --}}
            <div class="form-group">
                <label>Nome (opcional)</label>
                <input type="text" id="autoName" class="form-control" placeholder="Ex: Responder sobre preços" maxlength="100">
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
                        <div class="post-grid-placeholder"><i class="bi bi-arrow-repeat" style="animation:spin 1s linear infinite;"></i></div>
                    </div>
                    <div class="load-more-posts" id="loadMoreWrap" style="display:none;">
                        <button class="btn-secondary" style="font-size:12.5px;padding:6px 14px;" onclick="loadMorePosts()">
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
                <label>Palavras-chave <span style="font-weight:400;color:#9ca3af;">(pressione Enter ou vírgula para adicionar)</span></label>
                <div class="keyword-input-wrap" id="kwWrap" onclick="document.getElementById('kwInput').focus()">
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
                <label><i class="bi bi-chat-left-text" style="color:#833ab4;"></i> Responder ao comentário <span style="font-weight:400;color:#9ca3af;">(opcional)</span></label>
                <textarea id="replyComment" class="form-control" maxlength="2200"
                          placeholder="Digite a resposta pública que será postada no comentário..."
                          oninput="updateCount('replyComment','countReply',2200)"></textarea>
                <div class="char-count" id="countReply">0 / 2200</div>
            </div>

            {{-- DM message --}}
            <div class="form-group" style="margin-bottom:0;">
                <label><i class="bi bi-envelope-fill" style="color:#3b82f6;"></i> Enviar DM <span style="font-weight:400;color:#9ca3af;">(opcional)</span></label>
                <textarea id="dmMessage" class="form-control" maxlength="1000"
                          placeholder="Digite a mensagem privada que será enviada no inbox..."
                          oninput="updateCount('dmMessage','countDm',1000)"></textarea>
                <div class="char-count" id="countDm">0 / 1000</div>
            </div>
        </div>
        <div class="ig-modal-foot">
            <button class="btn-secondary" onclick="closeModal()">Cancelar</button>
            <button class="btn-primary" id="saveBtn" onclick="saveAutomation()">Salvar</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<style>
@keyframes spin { to { transform: rotate(360deg); } }
</style>
<script>
const CSRF              = '{{ csrf_token() }}';
const STORE_URL         = '{{ route('settings.ig-automations.store') }}';
const UPD_URL           = '{{ route('settings.ig-automations.update', ['automation' => '__ID__']) }}';
const DEL_URL           = '{{ route('settings.ig-automations.destroy', ['automation' => '__ID__']) }}';
const TOGGLE_URL        = '{{ route('settings.ig-automations.toggle', ['automation' => '__ID__']) }}';
const POSTS_URL         = '{{ route('settings.ig-automations.posts') }}';
const IG_CONNECTED      = {{ ($instance && $instance->status === 'connected') ? 'true' : 'false' }};

// ── In-memory automations ─────────────────────────────────────────────────
let automations = @json($automations);
let postNextCursor = null;
let postsLoaded   = false;
let keywords      = [];

// ── Modal ─────────────────────────────────────────────────────────────────
function openModal(auto = null) {
    document.getElementById('editingId').value     = auto ? auto.id : '';
    document.getElementById('modalTitle').textContent = auto ? 'Editar Automação' : 'Nova Automação';
    document.getElementById('autoName').value      = auto?.name ?? '';
    document.getElementById('selectedMediaId').value    = auto?.media_id ?? '';
    document.getElementById('selectedMediaThumb').value = auto?.media_thumbnail_url ?? '';
    document.getElementById('selectedMediaCaption').value = auto?.media_caption ?? '';
    document.getElementById('replyComment').value  = auto?.reply_comment ?? '';
    document.getElementById('dmMessage').value     = auto?.dm_message ?? '';

    updateCount('replyComment', 'countReply', 2200);
    updateCount('dmMessage', 'countDm', 1000);

    // Keywords
    keywords = auto ? [...(auto.keywords || [])] : [];
    renderKwChips();

    // Post scope
    const scope = auto?.media_id ? 'specific' : 'all';
    document.querySelectorAll('[name="postScope"]').forEach(r => r.checked = (r.value === scope));
    onScopeChange(scope, auto?.media_id);

    // Match type
    const mt = auto?.match_type ?? 'any';
    document.querySelectorAll('[name="matchType"]').forEach(r => r.checked = (r.value === mt));

    document.getElementById('igModal').classList.add('open');
}

function closeModal() {
    document.getElementById('igModal').classList.remove('open');
    postsLoaded = false;
    postNextCursor = null;
}

function editAuto(id) {
    const auto = automations.find(a => a.id == id);
    if (auto) openModal(auto);
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
    const grid = document.getElementById('postGrid');
    grid.innerHTML = '<div class="post-grid-placeholder"><i class="bi bi-arrow-repeat" style="animation:spin 1s linear infinite;font-size:22px;"></i></div>';

    try {
        const res = await fetch(`${POSTS_URL}?after=`, { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        if (data.error) { grid.innerHTML = `<p style="color:#ef4444;font-size:12px;padding:8px;">${escHtml(data.error)}</p>`; return; }
        grid.innerHTML = '';
        (data.data || []).forEach(p => appendPostItem(p, preselectMediaId));
        postNextCursor = data.next_cursor;
        document.getElementById('loadMoreWrap').style.display = postNextCursor ? 'block' : 'none';
    } catch (e) {
        grid.innerHTML = '<p style="color:#ef4444;font-size:12px;padding:8px;">Erro ao carregar publicações.</p>';
    }
}

async function loadMorePosts() {
    if (!postNextCursor) return;
    try {
        const res = await fetch(`${POSTS_URL}?after=${encodeURIComponent(postNextCursor)}`, { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        (data.data || []).forEach(p => appendPostItem(p));
        postNextCursor = data.next_cursor;
        document.getElementById('loadMoreWrap').style.display = postNextCursor ? 'block' : 'none';
    } catch {}
}

function appendPostItem(post, preselectId = null) {
    const grid = document.getElementById('postGrid');
    const div  = document.createElement('div');
    div.className = 'post-grid-item';
    div.dataset.id      = post.id;
    div.dataset.thumb   = post.thumbnail_url ?? '';
    div.dataset.caption = post.caption ?? '';
    div.title = post.caption || '';

    const img = post.thumbnail_url
        ? `<img src="${escHtml(post.thumbnail_url)}" alt="" loading="lazy">`
        : `<div style="font-size:18px;display:flex;align-items:center;justify-content:center;height:100%;color:#9ca3af;"><i class="bi bi-image"></i></div>`;

    div.innerHTML = `${img}<div class="post-check"><i class="bi bi-check"></i></div>`;

    if (post.id === preselectId) {
        div.classList.add('selected');
        document.getElementById('selectedMediaId').value    = post.id;
        document.getElementById('selectedMediaThumb').value = post.thumbnail_url ?? '';
        document.getElementById('selectedMediaCaption').value = post.caption ?? '';
    }

    div.onclick = () => selectPost(div, post);
    grid.appendChild(div);
}

function selectPost(el, post) {
    document.querySelectorAll('.post-grid-item').forEach(d => d.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('selectedMediaId').value    = post.id;
    document.getElementById('selectedMediaThumb').value = post.thumbnail_url ?? '';
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

function removeKw(i) {
    keywords.splice(i, 1);
    renderKwChips();
}

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
    el.className = 'char-count' + (len > max * .9 ? (len >= max ? ' danger' : ' warn') : '');
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
    btn.disabled = true;
    btn.textContent = 'Salvando…';

    try {
        const res  = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
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
        alert('Erro de rede. Tente novamente.');
    } finally {
        btn.disabled = false;
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
        const data = await res.json();
        if (!res.ok) { checkbox.checked = !checkbox.checked; return; }
        const auto = automations.find(a => a.id == id);
        if (auto) auto.is_active = data.is_active;
    } catch { checkbox.checked = !checkbox.checked; }
}

// ── Delete ────────────────────────────────────────────────────────────────
async function deleteAuto(id) {
    if (!confirm('Excluir esta automação?')) return;
    try {
        const res = await fetch(DEL_URL.replace('__ID__', id), {
            method: 'DELETE',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
        });
        if (!res.ok) { alert('Erro ao excluir.'); return; }
        automations = automations.filter(a => a.id != id);
        document.getElementById(`auto-${id}`)?.remove();
        checkEmpty();
    } catch { alert('Erro de rede.'); }
}

// ── Render helpers ────────────────────────────────────────────────────────
function checkEmpty() {
    const list = document.getElementById('automationList');
    if (list && list.children.length === 0) {
        list.innerHTML = `<div class="ig-empty">
            <i class="bi bi-robot"></i>
            <p>Nenhuma automação criada ainda.<br>Clique em <strong>Nova Automação</strong> para começar.</p>
        </div>`;
    }
}

function renderItem(auto, el) {
    if (!el) return;
    const thumbHtml = auto.media_thumbnail_url
        ? `<img src="${escHtml(auto.media_thumbnail_url)}" alt="" onerror="this.parentElement.innerHTML='<i class=&quot;bi bi-grid-3x3&quot;></i>'">`
        : '<i class="bi bi-grid-3x3"></i>';

    const kwChips = (auto.keywords || []).map(k => `<span class="ig-chip">${escHtml(k)}</span>`).join('');
    const replyPreview = auto.reply_comment
        ? `<span><i class="bi bi-chat-left-text" style="color:#833ab4;"></i> ${escHtml(auto.reply_comment.substring(0,50))}</span>` : '';
    const dmPreview = auto.dm_message
        ? `<span><i class="bi bi-envelope-fill" style="color:#3b82f6;"></i> ${escHtml(auto.dm_message.substring(0,50))}</span>` : '';

    el.innerHTML = `
        <div class="ig-item-thumb">${thumbHtml}</div>
        <div class="ig-item-body">
            <div class="ig-item-name">${escHtml(auto.name || (auto.media_id ? 'Publicação específica' : 'Todos os posts'))}</div>
            <div class="ig-item-meta">${auto.match_type === 'all' ? 'Todas as palavras' : 'Qualquer palavra'} &bull; ${auto.keywords?.length} palavra(s)-chave</div>
            <div class="ig-chips">${kwChips}</div>
            <div class="ig-action-preview">${replyPreview}${dmPreview}</div>
        </div>
        <div class="ig-item-actions">
            <label class="ig-toggle"><input type="checkbox" ${auto.is_active ? 'checked' : ''} onchange="toggleAuto(${auto.id}, this)"><span class="slider"></span></label>
            <button class="btn-icon" onclick="editAuto(${auto.id})"><i class="bi bi-pencil"></i></button>
            <button class="btn-icon danger" onclick="deleteAuto(${auto.id})"><i class="bi bi-trash"></i></button>
        </div>`;
}

function prependItem(auto) {
    const list = document.getElementById('automationList');
    // Remove empty state if present
    list.querySelector('.ig-empty')?.remove();

    const el = document.createElement('div');
    el.className = 'ig-item';
    el.dataset.id = auto.id;
    el.id = `auto-${auto.id}`;
    list.prepend(el);
    renderItem(auto, el);
}

function escHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
@endpush

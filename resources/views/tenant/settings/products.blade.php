@extends('tenant.layouts.app')
@php
    $title    = 'Produtos e Serviços';
    $pageIcon = 'box-seam';
@endphp

@push('styles')
<style>
    .cf-card { background:#fff; border-radius:14px; border:1px solid #e8eaf0; overflow:hidden; }
    .cf-card-header { padding:16px 22px; border-bottom:1px solid #f0f2f7; display:flex; align-items:center; justify-content:space-between; gap:10px; }
    .cf-card-header h3 { font-size:14px; font-weight:700; color:#1a1d23; margin:0; display:flex; align-items:center; gap:8px; }
    .cf-table { width:100%; border-collapse:collapse; }
    .cf-table th { font-size:11px; font-weight:700; color:#9ca3af; text-transform:uppercase; letter-spacing:.06em; padding:10px 22px; text-align:left; border-bottom:1px solid #f0f2f7; background:#fafbfc; }
    .cf-table td { padding:13px 22px; font-size:13.5px; color:#374151; border-bottom:1px solid #f7f8fa; vertical-align:middle; }
    .cf-table tr:last-child td { border-bottom:none; }
    .cf-table tr:hover td { background:#fafbfc; }
    .status-badge { display:inline-flex; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; }
    .status-active { background:#f0fdf4; color:#16a34a; }
    .status-inactive { background:#f1f5f9; color:#64748b; }
    .btn-icon { width:32px; height:32px; border:1px solid #e8eaf0; border-radius:8px; background:#fff; display:inline-flex; align-items:center; justify-content:center; color:#6b7280; font-size:14px; cursor:pointer; transition:all .15s; }
    .btn-icon:hover { background:#f4f6fb; color:#3B82F6; border-color:#dbeafe; }
    .btn-icon.danger:hover { background:#fef2f2; color:#EF4444; border-color:#fecaca; }
    .btn-new { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; background:#0085f3; color:#fff; border:none; border-radius:100px; font-size:13px; font-weight:600; cursor:pointer; transition:background .15s; }
    .btn-new:hover { background:#0070d1; }
    .empty-state { text-align:center; padding:60px 20px; color:#9ca3af; }
    .empty-state i { font-size:36px; margin-bottom:12px; display:block; }
    .cat-badge { display:inline-flex; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; background:#eff6ff; color:#2563EB; }
    .gallery-item { position:relative; border-radius:10px; overflow:hidden; aspect-ratio:1; border:1.5px solid #e8eaf0; }
    .gallery-item img, .gallery-item video { width:100%; height:100%; object-fit:cover; }
    .gallery-item .gallery-delete { position:absolute; top:6px; right:6px; background:rgba(0,0,0,.6); color:#fff; border:none; border-radius:50%; width:26px; height:26px; font-size:12px; cursor:pointer; display:flex; align-items:center; justify-content:center; opacity:0; transition:opacity .2s; }
    .gallery-item:hover .gallery-delete { opacity:1; }

    /* Drawer */
    .drawer-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.35); z-index:300; }
    .drawer-overlay.open { display:block; }
    .drawer { position:fixed; top:0; right:-480px; width:480px; max-width:100vw; height:100vh; background:#fff; z-index:301; box-shadow:-4px 0 24px rgba(0,0,0,.1); transition:right .25s cubic-bezier(.4,0,.2,1); display:flex; flex-direction:column; }
    .drawer.open { right:0; }
    .drawer-header { padding:18px 22px; border-bottom:1px solid #f0f2f7; display:flex; align-items:center; justify-content:space-between; }
    .drawer-header h4 { font-size:15px; font-weight:700; color:#1a1d23; margin:0; }
    .drawer-body { flex:1; overflow-y:auto; padding:20px 22px; }
    .drawer-footer { padding:14px 22px; border-top:1px solid #f0f2f7; display:flex; justify-content:flex-end; gap:10px; }
    .form-label { display:block; font-size:12px; font-weight:600; color:#6b7280; margin-bottom:5px; }
    .form-input { width:100%; padding:9px 13px; border:1.5px solid #e8eaf0; border-radius:9px; font-size:13px; color:#1a1d23; outline:none; transition:border-color .2s; }
    .form-input:focus { border-color:#0085f3; }
    .btn-save { display:inline-flex; align-items:center; gap:6px; padding:9px 20px; background:#0085f3; color:#fff; border:none; border-radius:9px; font-size:13px; font-weight:600; cursor:pointer; }
    .btn-save:hover { background:#0070d1; }
    .btn-cancel { padding:9px 20px; background:#f4f6fb; color:#374151; border:1px solid #e8eaf0; border-radius:9px; font-size:13px; font-weight:600; cursor:pointer; }
    .info-box { margin-top:16px; padding:14px 18px; background:#f0f9ff; border:1px solid #bae6fd; border-radius:10px; font-size:12.5px; color:#0369a1; line-height:1.6; }
    .info-box i { margin-right:4px; }
</style>
@endpush

@section('content')
<div class="page-container">

    <div class="cf-card">
        <div class="cf-card-header">
            <h3><i class="bi bi-box-seam" style="color:#3B82F6;"></i> Produtos e Serviços</h3>
            <button class="btn-new" onclick="openDrawer()">
                <i class="bi bi-plus-lg"></i> Novo produto
            </button>
        </div>

        @if($products->isEmpty())
        <div class="empty-state">
            <i class="bi bi-box-seam"></i>
            <p style="font-weight:600;color:#374151;">Nenhum produto cadastrado</p>
            <p style="font-size:13px;">Cadastre produtos e serviços para vincular a leads e usar com a IA.</p>
        </div>
        @else
        <table class="cf-table" id="productsTable">
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>SKU</th>
                    <th>Categoria</th>
                    <th>Preço</th>
                    <th>Unidade</th>
                    <th>Galeria</th>
                    <th>Status</th>
                    <th style="width:100px;"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($products as $p)
                <tr id="prod-row-{{ $p->id }}">
                    <td>
                        <div style="font-weight:600;color:#1a1d23;">{{ $p->name }}</div>
                        @if($p->description)
                            <div style="font-size:12px;color:#9ca3af;max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $p->description }}</div>
                        @endif
                    </td>
                    <td style="font-family:monospace;font-size:12px;color:#6b7280;">{{ $p->sku ?? '—' }}</td>
                    <td>
                        @if($p->categoryRelation)
                            <span class="cat-badge">
                                @if($p->categoryRelation->parent)
                                    {{ $p->categoryRelation->parent->name }} →
                                @endif
                                {{ $p->categoryRelation->name }}
                            </span>
                        @elseif($p->category)
                            <span class="cat-badge">{{ $p->category }}</span>
                        @else
                            <span style="color:#d1d5db;">—</span>
                        @endif
                    </td>
                    <td style="font-weight:600;color:#1a1d23;">R$ {{ number_format((float)$p->price, 2, ',', '.') }}</td>
                    <td style="color:#6b7280;">{{ $p->unit ?? '—' }}</td>
                    <td>
                        <span style="font-size:12px;color:#6b7280;">
                            <i class="bi bi-images"></i> {{ $p->media->count() }}
                        </span>
                    </td>
                    <td>
                        <span class="status-badge {{ $p->is_active ? 'status-active' : 'status-inactive' }}">
                            {{ $p->is_active ? 'Ativo' : 'Inativo' }}
                        </span>
                    </td>
                    <td>
                        <div style="display:flex;gap:4px;">
                            <button class="btn-icon" onclick="editProduct({{ $p->id }})" title="Editar"><i class="bi bi-pencil"></i></button>
                            <button class="btn-icon" onclick="openGallery({{ $p->id }})" title="Galeria"><i class="bi bi-images"></i></button>
                            <button class="btn-icon danger" onclick="deleteProduct({{ $p->id }})" title="Excluir"><i class="bi bi-trash3"></i></button>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    <div class="info-box">
        <i class="bi bi-info-circle"></i>
        Produtos cadastrados ficam disponíveis para vincular a leads e para o agente de IA consultar preços, enviar fotos e vincular automaticamente durante conversas.
    </div>
</div>

{{-- Drawer --}}
<div class="drawer-overlay" id="drawerOverlay" onclick="closeDrawer()"></div>
<div class="drawer" id="drawer">
    <div class="drawer-header">
        <h4 id="drawerTitle" style="font-size:15px;font-weight:700;color:#1a1d23;margin:0;">Novo Produto</h4>
        <button class="btn-icon" onclick="closeDrawer()"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="drawer-body">
        <input type="hidden" id="productId">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div style="grid-column:1/-1;">
                <label class="form-label">Nome *</label>
                <input type="text" id="productName" class="form-input" placeholder="Ex: Cortina Romana">
            </div>
            <div style="grid-column:1/-1;">
                <label class="form-label">Descricao</label>
                <textarea id="productDescription" class="form-input" rows="3" placeholder="Descricao detalhada..."></textarea>
            </div>
            <div>
                <label class="form-label">Preco *</label>
                <input type="number" id="productPrice" class="form-input" step="0.01" min="0" placeholder="0,00">
            </div>
            <div>
                <label class="form-label">Preco de custo</label>
                <input type="number" id="productCostPrice" class="form-input" step="0.01" min="0" placeholder="0,00">
            </div>
            <div>
                <label class="form-label">SKU</label>
                <input type="text" id="productSku" class="form-input" placeholder="CRT-001">
            </div>
            <div>
                <label class="form-label">Unidade</label>
                <select id="productUnit" class="form-input">
                    <option value="">Selecione</option>
                    <option value="un">Unidade (un)</option>
                    <option value="m2">Metro quadrado (m2)</option>
                    <option value="m">Metro linear (m)</option>
                    <option value="hr">Hora (hr)</option>
                    <option value="mes">Mensal</option>
                    <option value="kg">Quilograma (kg)</option>
                    <option value="L">Litro (L)</option>
                    <option value="pc">Peca (pc)</option>
                </select>
            </div>
            <div>
                <label class="form-label">Categoria</label>
                <select id="productCategoryId" class="form-input" onchange="onParentCatChange()">
                    <option value="">— Selecione —</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" data-has-children="{{ $cat->children->count() > 0 ? '1' : '0' }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div id="subCatWrap" style="display:none;">
                <label class="form-label">Subcategoria</label>
                <select id="productSubCategoryId" class="form-input">
                    <option value="">— Selecione —</option>
                </select>
            </div>
            <div>
                <label class="form-label">Status</label>
                <select id="productActive" class="form-input">
                    <option value="1">Ativo</option>
                    <option value="0">Inativo</option>
                </select>
            </div>
        </div>

        {{-- Galeria (dentro do drawer, aparece ao editar) --}}
        <div id="gallerySection" style="display:none;margin-top:20px;padding-top:18px;border-top:1px solid #f0f2f7;">
            <label class="form-label" style="font-size:13px;margin-bottom:10px;">
                <i class="bi bi-images" style="color:#3B82F6;"></i> Galeria
            </label>
            <div style="border:2px dashed #d1d5db;border-radius:10px;padding:16px;text-align:center;cursor:pointer;transition:border-color .2s;margin-bottom:12px;"
                 onclick="document.getElementById('galleryFileInput').click()"
                 ondragover="this.style.borderColor='#0085f3';event.preventDefault()"
                 ondragleave="this.style.borderColor='#d1d5db'"
                 ondrop="handleDrop(event)">
                <i class="bi bi-cloud-arrow-up" style="font-size:22px;color:#9ca3af;"></i>
                <p style="font-size:12px;color:#6b7280;margin:4px 0 0;">Clique ou arraste fotos/videos</p>
                <p style="font-size:10.5px;color:#9ca3af;margin:2px 0 0;">JPG, PNG, MP4, PDF — Max 10MB</p>
            </div>
            <input type="file" id="galleryFileInput" style="display:none" accept="image/*,video/mp4,video/mov,application/pdf" multiple onchange="uploadFiles(this.files)">
            <div id="galleryGrid" style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;"></div>
        </div>
    </div>
    <div class="drawer-footer">
        <button class="btn-cancel" onclick="closeDrawer()">Cancelar</button>
        <button class="btn-save" onclick="saveProduct()"><i class="bi bi-check-lg"></i> Salvar</button>
    </div>
</div>


@endsection

@push('scripts')
<script>
const PRODUCTS = @json($products);

function openDrawer(id) {
    const isEdit = !!id;
    const p = isEdit ? PRODUCTS.find(x => x.id === id) : null;
    document.getElementById('drawerTitle').textContent = isEdit ? 'Editar Produto' : 'Novo Produto';
    document.getElementById('productId').value = id || '';
    document.getElementById('productName').value = p?.name || '';
    document.getElementById('productDescription').value = p?.description || '';
    document.getElementById('productPrice').value = p?.price || '';
    document.getElementById('productCostPrice').value = p?.cost_price || '';
    document.getElementById('productSku').value = p?.sku || '';
    document.getElementById('productUnit').value = p?.unit || '';
    document.getElementById('productCategoryId').value = p?.category_id || '';
    onParentCatChange(p?.category_id);
    document.getElementById('productActive').value = p ? (p.is_active ? '1' : '0') : '1';

    // Gallery section: show only when editing
    const galSec = document.getElementById('gallerySection');
    if (isEdit) {
        galSec.style.display = '';
        renderGallery(p?.media || []);
    } else {
        galSec.style.display = 'none';
    }

    document.getElementById('drawerOverlay').classList.add('open');
    document.getElementById('drawer').classList.add('open');
}
function editProduct(id) { openDrawer(id); }
function closeDrawer() {
    document.getElementById('drawerOverlay').classList.remove('open');
    document.getElementById('drawer').classList.remove('open');
}

function saveProduct() {
    const id = document.getElementById('productId').value;
    const data = {
        name: document.getElementById('productName').value,
        description: document.getElementById('productDescription').value || null,
        price: parseFloat(document.getElementById('productPrice').value) || 0,
        cost_price: parseFloat(document.getElementById('productCostPrice').value) || null,
        sku: document.getElementById('productSku').value || null,
        unit: document.getElementById('productUnit').value || null,
        category_id: document.getElementById('productSubCategoryId')?.value || document.getElementById('productCategoryId').value || null,
        is_active: document.getElementById('productActive').value === '1',
    };
    if (!data.name) { toastr.error('Nome e obrigatorio.'); return; }
    const url = id ? `{{ url('configuracoes/produtos') }}/${id}` : '{{ route("settings.products.store") }}';
    const method = id ? 'PUT' : 'POST';
    API.call(method, url, data).done(() => {
        closeDrawer();
        toastr.success(id ? 'Produto atualizado.' : 'Produto criado.');
        location.reload();
    });
}

function deleteProduct(id) {
    if (!confirm('Excluir este produto?')) return;
    API.delete(`{{ url('configuracoes/produtos') }}/${id}`).done(() => {
        toastr.success('Produto excluido.');
        location.reload();
    });
}

// Gallery — now opens the drawer in edit mode
function openGallery(id) { openDrawer(id); }

function renderGallery(media) {
    const grid = document.getElementById('galleryGrid');
    const pid = document.getElementById('productId').value;
    if (!media.length) {
        grid.innerHTML = '<p style="grid-column:1/-1;text-align:center;color:#9ca3af;padding:20px;">Nenhuma midia adicionada.</p>';
        return;
    }
    grid.innerHTML = media.map(m => {
        const isImage = m.mime_type && m.mime_type.startsWith('image/');
        const isVideo = m.mime_type && m.mime_type.startsWith('video/');
        const src = '{{ asset("storage") }}/' + m.storage_path;
        let content = '';
        if (isImage) content = `<img src="${src}" alt="${escapeHtml(m.original_name)}">`;
        else if (isVideo) content = `<video src="${src}" muted></video>`;
        else content = `<div style="display:flex;align-items:center;justify-content:center;height:100%;background:#f8f9fa;"><i class="bi bi-file-earmark" style="font-size:28px;color:#9ca3af;"></i></div>`;
        return `<div class="gallery-item">${content}<button class="gallery-delete" onclick="deleteMedia(${pid},${m.id})"><i class="bi bi-x"></i></button><div style="position:absolute;bottom:0;left:0;right:0;background:linear-gradient(transparent,rgba(0,0,0,.5));padding:6px 8px;"><span style="color:#fff;font-size:10.5px;font-weight:500;">${escapeHtml(m.original_name)}</span></div></div>`;
    }).join('');
}

function uploadFiles(files) {
    const pid = document.getElementById('productId').value;
    if (!pid || !files.length) return;
    Array.from(files).forEach(file => {
        const fd = new FormData();
        fd.append('file', file);
        $.ajax({
            url: `{{ url('configuracoes/produtos') }}/${pid}/media`,
            method: 'POST', data: fd, processData: false, contentType: false,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        }).done(res => {
            if (res.success) {
                const p = PRODUCTS.find(x => x.id === parseInt(pid));
                if (p) { if (!p.media) p.media = []; p.media.push(res.media); renderGallery(p.media); }
                toastr.success('Midia adicionada.');
            }
        }).fail(() => toastr.error('Erro ao fazer upload.'));
    });
    document.getElementById('galleryFileInput').value = '';
}

function handleDrop(e) { e.preventDefault(); e.target.closest('[ondrop]').style.borderColor='#d1d5db'; uploadFiles(e.dataTransfer.files); }

function deleteMedia(productId, mediaId) {
    if (!confirm('Remover esta midia?')) return;
    API.delete(`{{ url('configuracoes/produtos') }}/${productId}/media/${mediaId}`).done(() => {
        const p = PRODUCTS.find(x => x.id === productId);
        if (p && p.media) { p.media = p.media.filter(m => m.id !== mediaId); renderGallery(p.media); }
        toastr.success('Midia removida.');
    });
}

// ── Category helpers ──────────────────────────────────────────────
const ALL_CATEGORIES = @json($categories);

function onParentCatChange(preselect) {
    const sel = document.getElementById('productCategoryId');
    const subWrap = document.getElementById('subCatWrap');
    const subSel = document.getElementById('productSubCategoryId');
    const parentId = parseInt(sel.value);
    const cat = ALL_CATEGORIES.find(c => c.id === parentId);

    if (cat && cat.children && cat.children.length > 0) {
        subWrap.style.display = '';
        subSel.innerHTML = '<option value="">— Selecione —</option>' +
            cat.children.map(s => `<option value="${s.id}" ${preselect == s.id ? 'selected' : ''}>${s.name}</option>`).join('');
    } else {
        subWrap.style.display = 'none';
        subSel.innerHTML = '<option value="">— Selecione —</option>';
    }
}

</script>
@endpush

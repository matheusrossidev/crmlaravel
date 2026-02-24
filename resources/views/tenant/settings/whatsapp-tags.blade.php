@extends('tenant.layouts.app')

@php
    $title    = 'Configurações';
    $pageIcon = 'gear';
@endphp

@push('styles')
<style>
    .wt-table-wrap {
        background: #fff;
        border: 1px solid #e8eaf0;
        border-radius: 12px;
        overflow: hidden;
    }
    .wt-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13.5px;
    }
    .wt-table thead th {
        padding: 11px 16px;
        font-size: 11.5px;
        font-weight: 700;
        color: #9ca3af;
        text-transform: uppercase;
        letter-spacing: .06em;
        background: #fafafa;
        border-bottom: 1px solid #f0f2f7;
    }
    .wt-table tbody tr { border-bottom: 1px solid #f7f8fa; }
    .wt-table tbody tr:last-child { border-bottom: none; }
    .wt-table tbody td {
        padding: 12px 16px;
        color: #374151;
        vertical-align: middle;
    }

    .tag-chip {
        display: inline-flex;
        align-items: center;
        padding: 3px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        white-space: nowrap;
    }
    .color-swatch {
        width: 16px; height: 16px;
        border-radius: 4px;
        border: 1px solid rgba(0,0,0,.1);
        flex-shrink: 0;
        display: inline-block;
    }

    .btn-icon {
        width: 28px; height: 28px; border-radius: 7px;
        border: 1px solid #e8eaf0; background: #fff; color: #6b7280;
        display: inline-flex; align-items: center; justify-content: center;
        cursor: pointer; font-size: 13px; transition: all .15s;
    }
    .btn-icon:hover { background: #f0f4ff; color: #374151; }
    .btn-icon.danger:hover { background: #fee2e2; color: #ef4444; border-color: #fca5a5; }

    .section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 18px;
    }
    .section-title { font-size: 15px; font-weight: 700; color: #1a1d23; }
    .section-subtitle { font-size: 13px; color: #9ca3af; margin-top: 3px; }

    /* Modal */
    .wt-modal-overlay {
        display: none;
        position: fixed; inset: 0;
        background: rgba(0,0,0,.45);
        z-index: 1000;
        align-items: center;
        justify-content: center;
    }
    .wt-modal-overlay.open { display: flex; }
    .wt-modal {
        background: #fff;
        border-radius: 14px;
        padding: 28px;
        width: 420px;
        max-width: 95vw;
        box-shadow: 0 20px 60px rgba(0,0,0,.18);
    }
    .wt-modal-title {
        font-size: 16px; font-weight: 700; color: #1a1d23;
        margin-bottom: 20px;
    }
    .form-group { margin-bottom: 14px; }
    .form-label {
        display: block; font-size: 11.5px; font-weight: 700;
        color: #6b7280; margin-bottom: 5px;
        text-transform: uppercase; letter-spacing: .05em;
    }
    .form-control {
        width: 100%; padding: 9px 12px;
        border: 1.5px solid #e8eaf0; border-radius: 9px;
        font-size: 13.5px; outline: none; font-family: inherit;
        transition: border-color .15s; box-sizing: border-box;
    }
    .form-control:focus { border-color: #3B82F6; }

    .color-row { display: flex; gap: 8px; align-items: center; }
    .color-picker-input {
        width: 46px; height: 38px; padding: 3px;
        border: 1.5px solid #e8eaf0; border-radius: 9px;
        cursor: pointer; background: #fff; flex-shrink: 0;
    }

    .preset-colors { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 8px; }
    .preset-btn {
        width: 24px; height: 24px; border-radius: 6px;
        border: 2px solid transparent; cursor: pointer;
        transition: transform .1s, border-color .1s;
    }
    .preset-btn:hover { transform: scale(1.15); }
    .preset-btn.selected { border-color: #1a1d23; }

    .preview-wrap {
        margin-top: 14px; padding: 12px 16px;
        background: #f8fafc; border-radius: 9px;
        display: flex; align-items: center; gap: 10px;
        font-size: 12px; color: #9ca3af;
    }

    .modal-footer {
        display: flex; gap: 8px; justify-content: flex-end;
        margin-top: 22px;
    }
    .btn-cancel {
        padding: 8px 18px; border-radius: 8px;
        border: 1.5px solid #e8eaf0; background: #fff;
        font-size: 13px; font-weight: 600; color: #6b7280;
        cursor: pointer; transition: background .15s;
    }
    .btn-cancel:hover { background: #f0f2f7; }
    .btn-save {
        padding: 8px 22px; border-radius: 8px; border: none;
        background: #3B82F6; color: #fff;
        font-size: 13px; font-weight: 600;
        cursor: pointer; transition: background .15s;
    }
    .btn-save:hover { background: #2563eb; }
    .btn-save:disabled { opacity: .6; cursor: not-allowed; }

    .empty-state {
        text-align: center; padding: 56px 24px; color: #9ca3af;
    }
    .empty-state i { font-size: 36px; opacity: .25; display: block; margin-bottom: 12px; }
</style>
@endpush

@section('content')
<div class="page-container">

    <div class="section-header">
        <div>
            <div class="section-title">Tags</div>
            <div class="section-subtitle">Crie tags com cores para categorizar leads e conversas.</div>
        </div>
        <button class="btn-primary-sm" id="btnNewTag">
            <i class="bi bi-plus-lg"></i> Nova Tag
        </button>
    </div>

    <div class="wt-table-wrap">
        <table class="wt-table">
            <thead>
                <tr>
                    <th>Tag</th>
                    <th style="width:140px;">Cor</th>
                    <th style="width:80px;"></th>
                </tr>
            </thead>
            <tbody id="tagsBody">
                @forelse($tags as $tag)
                @php
                    [$r, $g, $b] = sscanf($tag->color, '#%02x%02x%02x');
                @endphp
                <tr data-id="{{ $tag->id }}">
                    <td>
                        <span class="tag-chip"
                              style="background:rgba({{ $r }},{{ $g }},{{ $b }},.12);color:{{ $tag->color }};border:1px solid rgba({{ $r }},{{ $g }},{{ $b }},.3);">
                            {{ $tag->name }}
                        </span>
                    </td>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <span class="color-swatch" style="background:{{ $tag->color }};"></span>
                            <span style="font-size:12px;color:#6b7280;font-family:monospace;">{{ $tag->color }}</span>
                        </div>
                    </td>
                    <td>
                        <div style="display:flex;gap:5px;justify-content:flex-end;">
                            <button class="btn-icon" title="Editar"
                                    onclick="openEdit({{ $tag->id }},'{{ addslashes($tag->name) }}','{{ $tag->color }}')">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn-icon danger" title="Excluir"
                                    onclick="deleteTag({{ $tag->id }},this)">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr id="emptyRow">
                    <td colspan="3">
                        <div class="empty-state">
                            <i class="bi bi-tag"></i>
                            Nenhuma tag criada. Clique em <strong>Nova Tag</strong> para começar.
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>

{{-- Modal --}}
<div class="wt-modal-overlay" id="tagModal">
    <div class="wt-modal">
        <div class="wt-modal-title" id="modalTitle">Nova Tag</div>
        <input type="hidden" id="tagId">

        <div class="form-group">
            <label class="form-label">Nome</label>
            <input type="text" id="tagName" class="form-control"
                   placeholder="Ex: VIP, Urgente, Suporte..."
                   oninput="updatePreview()">
        </div>

        <div class="form-group">
            <label class="form-label">Cor</label>
            <div class="color-row">
                <input type="color" id="tagColorPicker" class="color-picker-input" value="#3B82F6"
                       oninput="syncFromPicker()">
                <input type="text" id="tagColorText" class="form-control"
                       value="#3B82F6" placeholder="#3B82F6"
                       oninput="syncFromText()" style="flex:1;font-family:monospace;">
            </div>
            <div class="preset-colors">
                @foreach(['#3B82F6','#10B981','#F59E0B','#EF4444','#8B5CF6','#EC4899','#06B6D4','#F97316','#84CC16','#6B7280'] as $c)
                <button type="button" class="preset-btn" style="background:{{ $c }};"
                        data-color="{{ $c }}" onclick="setPreset('{{ $c }}')"></button>
                @endforeach
            </div>
        </div>

        <div class="preview-wrap">
            <span style="font-size:11px;color:#9ca3af;white-space:nowrap;">Preview:</span>
            <span id="previewChip" class="tag-chip">Tag</span>
        </div>

        <div class="modal-footer">
            <button class="btn-cancel" onclick="closeModal()">Cancelar</button>
            <button class="btn-save" id="btnSave" onclick="saveTag()">Salvar</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
const URL_STORE   = '{{ route('settings.tags.store') }}';
const URL_UPDATE  = '{{ route('settings.tags.update', ['tag' => '__ID__']) }}';
const URL_DESTROY = '{{ route('settings.tags.destroy', ['tag' => '__ID__']) }}';

/* ── Helpers ── */
function esc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function hexToRgba(hex, a) {
    const r = parseInt(hex.slice(1,3),16), g = parseInt(hex.slice(3,5),16), b = parseInt(hex.slice(5,7),16);
    return `rgba(${r},${g},${b},${a})`;
}
function isValidHex(h) { return /^#[0-9a-fA-F]{6}$/.test(h); }

/* ── Preview ── */
function updatePreview() {
    const name  = document.getElementById('tagName').value.trim() || 'Tag';
    const color = document.getElementById('tagColorText').value.trim();
    const chip  = document.getElementById('previewChip');
    chip.textContent = name;
    if (isValidHex(color)) {
        chip.style.background = hexToRgba(color, .12);
        chip.style.color      = color;
        chip.style.border     = `1px solid ${hexToRgba(color, .3)}`;
    }
}

function syncFromPicker() {
    const val = document.getElementById('tagColorPicker').value;
    document.getElementById('tagColorText').value = val;
    updatePreview();
    highlightPreset(val);
}
function syncFromText() {
    const val = document.getElementById('tagColorText').value;
    if (isValidHex(val)) {
        document.getElementById('tagColorPicker').value = val;
        highlightPreset(val);
    }
    updatePreview();
}
function setPreset(hex) {
    document.getElementById('tagColorPicker').value = hex;
    document.getElementById('tagColorText').value   = hex;
    updatePreview();
    highlightPreset(hex);
}
function highlightPreset(hex) {
    document.querySelectorAll('.preset-btn').forEach(b => {
        b.classList.toggle('selected', b.dataset.color.toLowerCase() === hex.toLowerCase());
    });
}

/* ── Modal ── */
document.getElementById('btnNewTag').addEventListener('click', () => {
    document.getElementById('modalTitle').textContent = 'Nova Tag';
    document.getElementById('tagId').value   = '';
    document.getElementById('tagName').value = '';
    setPreset('#3B82F6');
    document.getElementById('tagModal').classList.add('open');
    setTimeout(() => document.getElementById('tagName').focus(), 80);
});

function openEdit(id, name, color) {
    document.getElementById('modalTitle').textContent = 'Editar Tag';
    document.getElementById('tagId').value   = id;
    document.getElementById('tagName').value = name;
    setPreset(color);
    document.getElementById('tagModal').classList.add('open');
}

function closeModal() {
    document.getElementById('tagModal').classList.remove('open');
}

document.getElementById('tagModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
document.getElementById('tagName').addEventListener('keydown', e => {
    if (e.key === 'Enter') { e.preventDefault(); saveTag(); }
});

/* ── CRUD ── */
async function saveTag() {
    const id    = document.getElementById('tagId').value;
    const name  = document.getElementById('tagName').value.trim();
    const color = document.getElementById('tagColorText').value.trim()
               || document.getElementById('tagColorPicker').value;

    if (!name)              { document.getElementById('tagName').focus(); return; }
    if (!isValidHex(color)) { toastr.error('Cor inválida. Use formato #RRGGBB.'); return; }

    const btn = document.getElementById('btnSave');
    btn.disabled = true;

    try {
        const url    = id ? URL_UPDATE.replace('__ID__', id) : URL_STORE;
        const method = id ? 'PUT' : 'POST';
        const res    = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ name, color }),
        });
        const data = await res.json();

        if (!data.success) {
            toastr.error(data.message || 'Erro ao salvar.');
            return;
        }

        closeModal();
        const t = data.tag;

        if (id) {
            updateRow(t);
        } else {
            insertRow(t);
        }
        toastr.success(id ? 'Tag atualizada.' : 'Tag criada.');
    } finally {
        btn.disabled = false;
    }
}

function buildChipStyle(color) {
    return `background:${hexToRgba(color,.12)};color:${color};border:1px solid ${hexToRgba(color,.3)};`;
}

function buildRow(t) {
    return `<tr data-id="${t.id}">
        <td><span class="tag-chip" style="${buildChipStyle(t.color)}">${esc(t.name)}</span></td>
        <td>
            <div style="display:flex;align-items:center;gap:8px;">
                <span class="color-swatch" style="background:${t.color};"></span>
                <span style="font-size:12px;color:#6b7280;font-family:monospace;">${t.color}</span>
            </div>
        </td>
        <td>
            <div style="display:flex;gap:5px;justify-content:flex-end;">
                <button class="btn-icon" title="Editar" onclick="openEdit(${t.id},'${t.name.replace(/'/g,"\\'")}','${t.color}')">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn-icon danger" title="Excluir" onclick="deleteTag(${t.id},this)">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </td>
    </tr>`;
}

function insertRow(t) {
    document.getElementById('emptyRow')?.remove();
    document.getElementById('tagsBody').insertAdjacentHTML('beforeend', buildRow(t));
}

function updateRow(t) {
    const row = document.querySelector(`tr[data-id="${t.id}"]`);
    if (!row) return;
    row.querySelector('.tag-chip').textContent   = t.name;
    row.querySelector('.tag-chip').style.cssText = buildChipStyle(t.color);
    row.querySelector('.color-swatch').style.background = t.color;
    row.querySelectorAll('td')[1].querySelector('span:last-child').textContent = t.color;
    row.querySelector('.btn-icon').setAttribute('onclick',
        `openEdit(${t.id},'${t.name.replace(/'/g,"\\'")}','${t.color}')`);
}

async function deleteTag(id, btn) {
    if (!confirm('Excluir esta tag? As conversas que a utilizam manterão o nome, mas perderão a cor.')) return;

    const row = btn.closest('tr');
    const res  = await fetch(URL_DESTROY.replace('__ID__', id), {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF },
    });
    const data = await res.json();
    if (!data.success) { toastr.error('Erro ao excluir.'); return; }

    row.remove();
    toastr.success('Tag excluída.');

    if (!document.querySelector('#tagsBody tr[data-id]')) {
        document.getElementById('tagsBody').innerHTML = `
            <tr id="emptyRow"><td colspan="3">
                <div class="empty-state">
                    <i class="bi bi-tag"></i>
                    Nenhuma tag criada.
                </div>
            </td></tr>`;
    }
}

// init
updatePreview();
</script>
@endpush

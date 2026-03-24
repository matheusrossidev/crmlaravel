@extends('tenant.layouts.app')

@php
    $title    = 'Tags';
    $pageIcon = 'tags';
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

    /* Drawer */
    .drawer-overlay {
        display: none; position: fixed; inset: 0;
        background: rgba(0,0,0,.35); z-index: 300;
    }
    .drawer-overlay.open { display: block; }
    .drawer {
        position: fixed; top: 0; right: -440px;
        width: 440px; height: 100vh; background: #fff;
        z-index: 301; transition: right .25s cubic-bezier(.4,0,.2,1);
        display: flex; flex-direction: column;
        box-shadow: -4px 0 24px rgba(0,0,0,.1);
    }
    .drawer.open { right: 0; }
    .drawer-header {
        padding: 18px 22px; border-bottom: 1px solid #f0f2f7;
        display: flex; align-items: center; justify-content: space-between;
        font-size: 15px; font-weight: 700; color: #1a1d23;
    }
    .drawer-body { padding: 22px; flex: 1; overflow-y: auto; }
    .drawer-footer {
        padding: 16px 22px; border-top: 1px solid #f0f2f7;
        display: flex; gap: 10px; justify-content: flex-end;
    }

    .form-group { margin-bottom: 16px; }
    .form-label {
        display: block; font-size: 12.5px; font-weight: 600;
        color: #374151; margin-bottom: 6px;
    }
    .form-input {
        width: 100%; padding: 9px 12px;
        border: 1px solid #d1d5db; border-radius: 9px;
        font-size: 13.5px; color: #1a1d23;
        outline: none; transition: border-color .15s; background: #fff;
        box-sizing: border-box; font-family: inherit;
    }
    .form-input:focus { border-color: #3B82F6; box-shadow: 0 0 0 3px rgba(59,130,246,.1); }

    .color-row { display: flex; gap: 8px; align-items: center; }
    .color-picker-input {
        width: 46px; height: 38px; padding: 3px;
        border: 1px solid #d1d5db; border-radius: 9px;
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

    .btn-cancel {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 9px 20px; background: #f4f6fb; color: #374151;
        border: 1px solid #e8eaf0; border-radius: 100px;
        font-size: 13px; font-weight: 600; cursor: pointer; transition: background .15s;
    }
    .btn-cancel:hover { background: #e8eaf0; }
    .btn-save {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 9px 20px; background: #0085f3; color: #fff;
        border: none; border-radius: 100px;
        font-size: 13px; font-weight: 600;
        cursor: pointer; transition: background .15s;
    }
    .btn-save:hover { background: #0070d1; }
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

{{-- Drawer --}}
<div class="drawer-overlay" id="drawerOverlay" onclick="closeDrawer()"></div>
<div class="drawer" id="drawer">
    <div class="drawer-header">
        <span id="drawerTitle">Nova Tag</span>
        <button onclick="closeDrawer()" style="background:none;border:none;font-size:18px;color:#6b7280;cursor:pointer;">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <div class="drawer-body">
        <input type="hidden" id="tagId">

        <div class="form-group">
            <label class="form-label">Nome</label>
            <input type="text" id="tagName" class="form-input"
                   placeholder="Ex: VIP, Urgente, Suporte..."
                   oninput="updatePreview()">
        </div>

        <div class="form-group">
            <label class="form-label">Cor</label>
            <div class="color-row">
                <input type="color" id="tagColorPicker" class="color-picker-input" value="#3B82F6"
                       oninput="syncFromPicker()">
                <input type="text" id="tagColorText" class="form-input"
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
    </div>
    <div class="drawer-footer">
        <button class="btn-cancel" onclick="closeDrawer()">Cancelar</button>
        <button class="btn-save" id="btnSave" onclick="saveTag()">
            <i class="bi bi-check2"></i> Salvar
        </button>
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

/* ── Drawer ── */
function openDrawer() {
    document.getElementById('drawerOverlay').classList.add('open');
    document.getElementById('drawer').classList.add('open');
}

function closeDrawer() {
    document.getElementById('drawerOverlay').classList.remove('open');
    document.getElementById('drawer').classList.remove('open');
}

document.getElementById('btnNewTag').addEventListener('click', () => {
    document.getElementById('drawerTitle').textContent = 'Nova Tag';
    document.getElementById('tagId').value   = '';
    document.getElementById('tagName').value = '';
    setPreset('#3B82F6');
    openDrawer();
    setTimeout(() => document.getElementById('tagName').focus(), 200);
});

function openEdit(id, name, color) {
    document.getElementById('drawerTitle').textContent = 'Editar Tag';
    document.getElementById('tagId').value   = id;
    document.getElementById('tagName').value = name;
    setPreset(color);
    openDrawer();
}

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

        closeDrawer();
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

function deleteTag(id, btn) {
    confirmAction({
        title: 'Excluir tag',
        message: 'As conversas que a utilizam manterão o nome, mas perderão a cor.<br>Esta ação não pode ser desfeita.',
        confirmText: 'Excluir',
        onConfirm: async () => {
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
        },
    });
}

// init
updatePreview();
</script>
@endpush

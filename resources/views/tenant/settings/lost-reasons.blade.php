@extends('tenant.layouts.app')

@php
    $title = 'Motivos de Perda';
    $pageIcon = 'x-circle';
@endphp

@push('styles')
<style>
    .reason-table-wrap { background: #fff; border: 1px solid #e8eaf0; border-radius: 12px; overflow: hidden; }
    .reason-table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
    .reason-table thead th {
        padding: 11px 16px; font-size: 11.5px; font-weight: 700; color: #9ca3af;
        text-transform: uppercase; letter-spacing: .06em; background: #fafafa;
        border-bottom: 1px solid #f0f2f7;
    }
    .reason-table tbody tr { border-bottom: 1px solid #f7f8fa; }
    .reason-table tbody tr:last-child { border-bottom: none; }
    .reason-table tbody td { padding: 12px 16px; color: #374151; vertical-align: middle; }

    .toggle { position: relative; display: inline-block; width: 36px; height: 20px; }
    .toggle input { display: none; }
    .toggle-slider {
        position: absolute; inset: 0; background: #d1d5db;
        border-radius: 99px; cursor: pointer; transition: .2s;
    }
    .toggle-slider::before {
        content: ''; position: absolute;
        width: 14px; height: 14px; left: 3px; bottom: 3px;
        background: #fff; border-radius: 50%; transition: .2s;
    }
    .toggle input:checked + .toggle-slider { background: #10B981; }
    .toggle input:checked + .toggle-slider::before { transform: translateX(16px); }

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
        font-family: inherit; box-sizing: border-box;
    }
    .form-input:focus { border-color: #3B82F6; box-shadow: 0 0 0 3px rgba(59,130,246,.1); }

    .btn-icon {
        width: 28px; height: 28px; border-radius: 7px; border: 1px solid #e8eaf0;
        background: #fff; color: #6b7280;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; font-size: 13px; transition: all .15s;
    }
    .btn-icon:hover { background: #f0f4ff; color: #374151; }
    .btn-icon.danger:hover { background: #fee2e2; color: #ef4444; border-color: #fca5a5; }

    .btn-save {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 9px 20px; background: #0085f3; color: #fff;
        border: none; border-radius: 100px; font-size: 13px; font-weight: 600;
        cursor: pointer; transition: background .15s;
    }
    .btn-save:hover { background: #0070d1; }
    .btn-cancel {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 9px 20px; background: #f4f6fb; color: #374151;
        border: 1px solid #e8eaf0; border-radius: 100px;
        font-size: 13px; font-weight: 600; cursor: pointer; transition: background .15s;
    }
    .btn-cancel:hover { background: #e8eaf0; }

    .section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; }
    .section-title  { font-size: 15px; font-weight: 700; color: #1a1d23; }
</style>
@endpush

@section('content')
<div class="page-container">

    <div class="section-header">
        <div class="section-title">Motivos de Perda</div>
        <button class="btn-primary-sm" id="btnNovoMotivo">
            <i class="bi bi-plus-lg"></i> Novo Motivo
        </button>
    </div>

    <div class="reason-table-wrap">
        <table class="reason-table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th style="width:100px;text-align:center;">Ativo</th>
                    <th style="width:80px;"></th>
                </tr>
            </thead>
            <tbody id="reasonsBody">
                @forelse($reasons as $reason)
                <tr data-reason-id="{{ $reason->id }}">
                    <td class="reason-name-cell">{{ $reason->name }}</td>
                    <td style="text-align:center;">
                        <label class="toggle">
                            <input type="checkbox" {{ $reason->is_active ? 'checked' : '' }}
                                   onchange="toggleReason({{ $reason->id }}, '{{ addslashes($reason->name) }}', this.checked)">
                            <span class="toggle-slider"></span>
                        </label>
                    </td>
                    <td>
                        <div style="display:flex;gap:5px;justify-content:flex-end;">
                            <button class="btn-icon" onclick="openEditReason({{ $reason->id }}, '{{ addslashes($reason->name) }}')">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn-icon danger" onclick="deleteReason({{ $reason->id }}, this)">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr id="emptyReasons">
                    <td colspan="3" style="text-align:center;padding:40px;color:#9ca3af;">Nenhum motivo cadastrado.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>

{{-- Drawer: Motivo de Perda --}}
<div class="drawer-overlay" id="drawerOverlay" onclick="closeDrawer()"></div>
<div class="drawer" id="drawer">
    <div class="drawer-header">
        <span id="drawerTitle">Novo Motivo</span>
        <button onclick="closeDrawer()" style="background:none;border:none;font-size:18px;color:#6b7280;cursor:pointer;">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <div class="drawer-body">
        <input type="hidden" id="reasonId">
        <div class="form-group">
            <label class="form-label">Nome do Motivo</label>
            <input type="text" id="reasonName" class="form-input" placeholder="Ex: Sem orçamento">
        </div>
    </div>
    <div class="drawer-footer">
        <button class="btn-cancel" onclick="closeDrawer()">Cancelar</button>
        <button class="btn-save" onclick="saveReason()">
            <i class="bi bi-check2"></i> Salvar
        </button>
    </div>
</div>
@endsection

@push('scripts')
<script>
const REASON_STORE = @json(route('settings.lost-reasons.store'));
const REASON_UPD   = @json(route('settings.lost-reasons.update',  ['reason' => '__ID__']));
const REASON_DEL   = @json(route('settings.lost-reasons.destroy', ['reason' => '__ID__']));
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content;

/* ---- Drawer open/close ---- */
function openDrawer() {
    document.getElementById('drawerOverlay').classList.add('open');
    document.getElementById('drawer').classList.add('open');
    setTimeout(() => document.getElementById('reasonName').focus(), 200);
}

function closeDrawer() {
    document.getElementById('drawerOverlay').classList.remove('open');
    document.getElementById('drawer').classList.remove('open');
}

/* ---- New / Edit ---- */
document.getElementById('btnNovoMotivo').addEventListener('click', () => {
    document.getElementById('drawerTitle').textContent = 'Novo Motivo';
    document.getElementById('reasonId').value = '';
    document.getElementById('reasonName').value = '';
    openDrawer();
});

function openEditReason(id, name) {
    document.getElementById('drawerTitle').textContent = 'Editar Motivo';
    document.getElementById('reasonId').value = id;
    document.getElementById('reasonName').value = name;
    openDrawer();
}

/* ---- Save ---- */
async function saveReason() {
    const id   = document.getElementById('reasonId').value;
    const name = document.getElementById('reasonName').value.trim();
    if (!name) { document.getElementById('reasonName').focus(); return; }

    const url    = id ? REASON_UPD.replace('__ID__', id) : REASON_STORE;
    const method = id ? 'PUT' : 'POST';

    const res  = await fetch(url, {
        method,
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ name, is_active: true })
    });
    const data = await res.json();
    if (!data.success) { alert(data.message || 'Erro.'); return; }

    closeDrawer();
    const r = data.reason;
    const body = document.getElementById('reasonsBody');
    document.getElementById('emptyReasons')?.remove();

    if (id) {
        const row = body.querySelector(`tr[data-reason-id="${id}"]`);
        if (row) row.querySelector('.reason-name-cell').textContent = r.name;
    } else {
        body.insertAdjacentHTML('beforeend', `<tr data-reason-id="${r.id}">
            <td class="reason-name-cell">${escapeHtml(r.name)}</td>
            <td style="text-align:center;">
                <label class="toggle">
                    <input type="checkbox" checked onchange="toggleReason(${r.id},'${escapeJs(r.name)}',this.checked)">
                    <span class="toggle-slider"></span>
                </label>
            </td>
            <td>
                <div style="display:flex;gap:5px;justify-content:flex-end;">
                    <button class="btn-icon" onclick="openEditReason(${r.id},'${escapeJs(r.name)}')"><i class="bi bi-pencil"></i></button>
                    <button class="btn-icon danger" onclick="deleteReason(${r.id},this)"><i class="bi bi-trash"></i></button>
                </div>
            </td>
        </tr>`);
    }
}

/* ---- Toggle active ---- */
async function toggleReason(id, name, active) {
    await fetch(REASON_UPD.replace('__ID__', id), {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ name, is_active: active })
    });
}

/* ---- Delete ---- */
function deleteReason(id, btn) {
    confirmAction({
        title: 'Excluir motivo',
        message: 'Tem certeza que deseja excluir este motivo de perda?',
        confirmText: 'Excluir',
        onConfirm: async () => {
            const res  = await fetch(REASON_DEL.replace('__ID__', id), {
                method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF }
            });
            const data = await res.json();
            if (!data.success) { toastr.error(data.message || 'Erro ao excluir.'); return; }
            if (data.deactivated) {
                toastr.warning('Este motivo possui registros e foi desativado.');
                const row = document.querySelector(`tr[data-reason-id="${id}"]`);
                if (row) row.querySelector('input[type=checkbox]').checked = false;
            } else {
                btn.closest('tr').remove();
            }
        },
    });
}

/* ---- Helpers ---- */
function escapeHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function escapeJs(s) { return String(s).replace(/\\/g,'\\\\').replace(/'/g,"\\'"); }
</script>
@endpush

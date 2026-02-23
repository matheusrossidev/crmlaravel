@extends('tenant.layouts.app')
@php
    $title    = 'Campos Extras';
    $pageIcon = 'sliders';
@endphp

@push('styles')
<style>
    .cf-card {
        background: #fff;
        border-radius: 14px;
        border: 1px solid #e8eaf0;
        overflow: hidden;
    }
    .cf-card-header {
        padding: 16px 22px;
        border-bottom: 1px solid #f0f2f7;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }
    .cf-card-header h3 {
        font-size: 14px; font-weight: 700; color: #1a1d23;
        margin: 0; display: flex; align-items: center; gap: 8px;
    }

    .cf-table { width: 100%; border-collapse: collapse; }
    .cf-table th {
        font-size: 11px; font-weight: 700; color: #9ca3af;
        text-transform: uppercase; letter-spacing: .06em;
        padding: 10px 22px; text-align: left;
        border-bottom: 1px solid #f0f2f7; background: #fafbfc;
    }
    .cf-table td {
        padding: 13px 22px; font-size: 13.5px; color: #374151;
        border-bottom: 1px solid #f7f8fa; vertical-align: middle;
    }
    .cf-table tr:last-child td { border-bottom: none; }
    .cf-table tr:hover td { background: #fafbfc; }

    .type-badge {
        display: inline-flex; align-items: center;
        padding: 3px 10px; border-radius: 20px;
        font-size: 11px; font-weight: 600;
        background: #eff6ff; color: #2563EB;
        text-transform: capitalize;
    }

    .bool-icon { font-size: 16px; }
    .bool-icon.yes { color: #10B981; }
    .bool-icon.no  { color: #d1d5db; }

    .status-badge {
        display: inline-flex; padding: 3px 10px; border-radius: 20px;
        font-size: 11px; font-weight: 600;
    }
    .status-active   { background: #f0fdf4; color: #16a34a; }
    .status-inactive { background: #f1f5f9; color: #64748b; }

    .btn-icon {
        width: 32px; height: 32px;
        border: 1px solid #e8eaf0; border-radius: 8px; background: #fff;
        display: inline-flex; align-items: center; justify-content: center;
        color: #6b7280; font-size: 14px; cursor: pointer; transition: all .15s;
    }
    .btn-icon:hover { background: #f4f6fb; color: #3B82F6; border-color: #dbeafe; }
    .btn-icon.danger:hover { background: #fef2f2; color: #EF4444; border-color: #fecaca; }

    .btn-new {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 8px 16px; background: #3B82F6; color: #fff;
        border: none; border-radius: 9px; font-size: 13px; font-weight: 600;
        cursor: pointer; transition: background .15s;
    }
    .btn-new:hover { background: #2563EB; }

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
    .form-group label {
        display: block; font-size: 12.5px; font-weight: 600;
        color: #374151; margin-bottom: 6px;
    }
    .form-control {
        width: 100%; padding: 9px 12px;
        border: 1px solid #d1d5db; border-radius: 9px;
        font-size: 13.5px; color: #1a1d23;
        outline: none; transition: border-color .15s; background: #fff;
    }
    .form-control:focus { border-color: #3B82F6; box-shadow: 0 0 0 3px rgba(59,130,246,.1); }
    .form-error { font-size: 12px; color: #EF4444; margin-top: 4px; }

    .check-row {
        display: flex; align-items: center; gap: 8px;
        padding: 6px 0; cursor: pointer;
    }
    .check-row input[type=checkbox] { width: 16px; height: 16px; cursor: pointer; }
    .check-row span { font-size: 13.5px; color: #374151; font-weight: 500; }

    .btn-save {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 9px 20px; background: #3B82F6; color: #fff;
        border: none; border-radius: 9px; font-size: 13.5px; font-weight: 600;
        cursor: pointer; transition: background .15s;
    }
    .btn-save:hover { background: #2563EB; }
    .btn-save:disabled { opacity: .6; cursor: not-allowed; }
    .btn-cancel {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 9px 20px; background: #f4f6fb; color: #374151;
        border: 1px solid #e8eaf0; border-radius: 9px;
        font-size: 13.5px; font-weight: 600; cursor: pointer; transition: background .15s;
    }
    .btn-cancel:hover { background: #e8eaf0; }

    .empty-state { text-align: center; padding: 60px 20px; color: #9ca3af; }
    .empty-state i { font-size: 36px; margin-bottom: 12px; display: block; }

    .type-hint {
        font-size: 11.5px; color: #9ca3af; margin-top: 4px;
    }
</style>
@endpush

@section('content')
<div class="page-container">

    <div class="cf-card">
        <div class="cf-card-header">
            <h3><i class="bi bi-layout-text-window-sidebar" style="color:#3B82F6;"></i> Campos Personalizados</h3>
            <button class="btn-new" onclick="openDrawer()">
                <i class="bi bi-plus-lg"></i> Novo campo
            </button>
        </div>

        @if($fields->isEmpty())
        <div class="empty-state">
            <i class="bi bi-layout-text-window-sidebar"></i>
            <p style="font-weight:600;color:#374151;">Nenhum campo personalizado</p>
            <p style="font-size:13px;">Campos extras aparecem no formulário de leads e na API.</p>
        </div>
        @else
        <table class="cf-table" id="cfTable">
            <thead>
                <tr>
                    <th>Label</th>
                    <th>Nome interno</th>
                    <th>Tipo</th>
                    <th>Obrigatório</th>
                    <th>No card</th>
                    <th>Status</th>
                    <th style="width:80px;"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($fields as $f)
                <tr id="cf-row-{{ $f->id }}">
                    <td style="font-weight:600;color:#1a1d23;">{{ $f->label }}</td>
                    <td style="font-family:monospace;font-size:12px;color:#6b7280;">{{ $f->name }}</td>
                    <td><span class="type-badge">{{ $f->field_type }}</span></td>
                    <td>
                        <i class="bi {{ $f->is_required ? 'bi-check-circle-fill yes' : 'bi-circle no' }} bool-icon"></i>
                    </td>
                    <td>
                        <i class="bi {{ $f->show_on_card ? 'bi-check-circle-fill yes' : 'bi-circle no' }} bool-icon"></i>
                    </td>
                    <td>
                        <span class="status-badge {{ $f->is_active ? 'status-active' : 'status-inactive' }}">
                            {{ $f->is_active ? 'Ativo' : 'Inativo' }}
                        </span>
                    </td>
                    <td>
                        <div style="display:flex;gap:4px;">
                            <button class="btn-icon" title="Editar"
                                onclick="editField({{ $f->id }}, {{ json_encode($f->label) }}, {{ json_encode($f->field_type) }}, {{ json_encode($f->options_json ?? []) }}, {{ $f->is_required ? 'true' : 'false' }}, {{ $f->show_on_card ? 'true' : 'false' }}, {{ $f->is_active ? 'true' : 'false' }}, {{ $f->sort_order }})">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn-icon danger" title="Excluir"
                                onclick="deleteField({{ $f->id }}, {{ json_encode($f->label) }})">
                                <i class="bi bi-trash3"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    {{-- Info card --}}
    <div style="max-width:900px;margin-top:20px;padding:16px 20px;background:#eff6ff;border:1px solid #dbeafe;border-radius:12px;font-size:13px;color:#1e40af;">
        <i class="bi bi-info-circle me-2"></i>
        Os campos personalizados aparecem no formulário de leads e são acessíveis via API com a chave <code>custom_fields</code>.
        Use o <strong>nome interno</strong> na API para enviar e receber valores.
    </div>

</div>

{{-- Drawer --}}
<div class="drawer-overlay" id="drawerOverlay" onclick="closeDrawer()"></div>
<div class="drawer" id="drawer">
    <div class="drawer-header">
        <span id="drawerTitle">Novo Campo</span>
        <button onclick="closeDrawer()" style="background:none;border:none;font-size:18px;color:#6b7280;cursor:pointer;">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <div class="drawer-body">
        <input type="hidden" id="editFieldId">

        <div class="form-group">
            <label>Label <span style="color:#EF4444;">*</span></label>
            <input type="text" class="form-control" id="dLabel" placeholder="Ex: CPF, Empresa, Data de nascimento">
            <div class="form-error d-none" id="errLabel"></div>
        </div>

        <div class="form-group" id="typeGroup">
            <label>Tipo de campo <span style="color:#EF4444;">*</span></label>
            <select class="form-control" id="dType" onchange="onTypeChange()">
                <option value="text">Texto curto</option>
                <option value="textarea">Texto longo</option>
                <option value="number">Número</option>
                <option value="currency">Moeda (R$)</option>
                <option value="date">Data</option>
                <option value="select">Seleção única</option>
                <option value="multiselect">Seleção múltipla</option>
                <option value="checkbox">Checkbox (Sim/Não)</option>
                <option value="url">URL / Link</option>
                <option value="phone">Telefone</option>
                <option value="email">E-mail</option>
                <option value="file">Arquivo (upload)</option>
            </select>
            <div class="type-hint" id="typeHint"></div>
        </div>

        <div class="form-group d-none" id="optionsGroup">
            <label>Opções <small style="color:#9ca3af;">(uma por linha)</small></label>
            <textarea class="form-control" id="dOptions" rows="5"
                      placeholder="Opção A&#10;Opção B&#10;Opção C"></textarea>
            <div class="form-error d-none" id="errOptions"></div>
        </div>

        <div style="border-top:1px solid #f0f2f7;padding-top:14px;margin-top:4px;">
            <label class="check-row">
                <input type="checkbox" id="dRequired">
                <span>Campo obrigatório</span>
            </label>
            <label class="check-row">
                <input type="checkbox" id="dShowOnCard">
                <span>Mostrar em destaque no card do lead</span>
            </label>
            <label class="check-row">
                <input type="checkbox" id="dActive" checked>
                <span>Campo ativo</span>
            </label>
        </div>
    </div>
    <div class="drawer-footer">
        <button class="btn-cancel" onclick="closeDrawer()">Cancelar</button>
        <button class="btn-save" id="btnSave" onclick="saveField()">
            <i class="bi bi-check2"></i> Salvar
        </button>
    </div>
</div>
@endsection

@push('scripts')
<script>
const storeUrl  = "{{ route('settings.custom-fields.store') }}";
const updateUrl = (id) => `/configuracoes/campos-extras/${id}`;
const deleteUrl = (id) => `/configuracoes/campos-extras/${id}`;
const csrf      = document.querySelector('meta[name=csrf-token]').content;

const typeHints = {
    text: 'Campo de texto de uma linha.',
    textarea: 'Campo de texto multi-linha.',
    number: 'Aceita valores numéricos.',
    currency: 'Valor monetário (decimal).',
    date: 'Seletor de data.',
    select: 'Menu suspenso, escolha uma opção.',
    multiselect: 'Múltiplas opções podem ser selecionadas.',
    checkbox: 'Verdadeiro ou falso.',
    url: 'Link / endereço web.',
    phone: 'Número de telefone.',
    email: 'Endereço de e-mail.',
};

let editingId = null;

function openDrawer() {
    editingId = null;
    document.getElementById('drawerTitle').textContent = 'Novo Campo';
    document.getElementById('editFieldId').value = '';
    document.getElementById('dLabel').value = '';
    document.getElementById('dType').value = 'text';
    document.getElementById('dOptions').value = '';
    document.getElementById('dRequired').checked = false;
    document.getElementById('dShowOnCard').checked = false;
    document.getElementById('dActive').checked = true;
    document.getElementById('typeGroup').style.display = '';
    clearErrors();
    onTypeChange();
    openDrawerUI();
}

function editField(id, label, type, options, required, showOnCard, active) {
    editingId = id;
    document.getElementById('drawerTitle').textContent = 'Editar Campo';
    document.getElementById('editFieldId').value = id;
    document.getElementById('dLabel').value = label;
    document.getElementById('dType').value = type;
    document.getElementById('dOptions').value = (options && options.length) ? options.join('\n') : '';
    document.getElementById('dRequired').checked = required;
    document.getElementById('dShowOnCard').checked = showOnCard;
    document.getElementById('dActive').checked = active;
    // Após criação, tipo não pode mudar
    document.getElementById('typeGroup').style.display = 'none';
    clearErrors();
    onTypeChange();
    openDrawerUI();
}

function openDrawerUI() {
    document.getElementById('drawerOverlay').classList.add('open');
    document.getElementById('drawer').classList.add('open');
    setTimeout(() => document.getElementById('dLabel').focus(), 200);
}

function closeDrawer() {
    document.getElementById('drawerOverlay').classList.remove('open');
    document.getElementById('drawer').classList.remove('open');
}

function onTypeChange() {
    const type = document.getElementById('dType').value;
    document.getElementById('typeHint').textContent = typeHints[type] ?? '';
    const needsOptions = ['select', 'multiselect'].includes(type);
    document.getElementById('optionsGroup').classList.toggle('d-none', !needsOptions);
}

async function saveField() {
    clearErrors();
    const btn = document.getElementById('btnSave');
    btn.disabled = true;

    const isEdit = editingId !== null;
    const url    = isEdit ? updateUrl(editingId) : storeUrl;
    const method = isEdit ? 'PUT' : 'POST';

    const body = {
        label:        document.getElementById('dLabel').value,
        field_type:   document.getElementById('dType').value,
        options:      document.getElementById('dOptions').value,
        is_required:  document.getElementById('dRequired').checked,
        show_on_card: document.getElementById('dShowOnCard').checked,
        is_active:    document.getElementById('dActive').checked,
    };

    try {
        const res  = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify(body),
        });
        const data = await res.json();

        if ((res.status === 200 || res.status === 201) && data.success) {
            toastr.success(isEdit ? 'Campo atualizado!' : 'Campo criado!');
            closeDrawer();
            setTimeout(() => location.reload(), 700);
        } else if (data.errors) {
            Object.keys(data.errors).forEach(f => {
                const el = document.getElementById('err' + f.charAt(0).toUpperCase() + f.slice(1));
                if (el) { el.textContent = data.errors[f][0]; el.classList.remove('d-none'); }
            });
        } else {
            toastr.error(data.message ?? 'Erro ao salvar campo.');
        }
    } catch { toastr.error('Erro de conexão.'); }
    btn.disabled = false;
}

function deleteField(id, label) {
    confirmAction({
        title: 'Excluir campo personalizado',
        message: `Excluir o campo "${label}"? Todos os valores associados serão perdidos.`,
        confirmText: 'Excluir',
        onConfirm: async () => {
            try {
                const res  = await fetch(deleteUrl(id), {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                });
                const data = await res.json();
                if (data.success) {
                    toastr.success('Campo excluído.');
                    const row = document.getElementById(`cf-row-${id}`);
                    if (row) row.remove();
                } else {
                    toastr.error(data.message ?? 'Erro ao excluir.');
                }
            } catch { toastr.error('Erro de conexão.'); }
        },
    });
}

function clearErrors() {
    document.querySelectorAll('.form-error').forEach(el => {
        el.textContent = ''; el.classList.add('d-none');
    });
}
</script>
@endpush

@extends('tenant.layouts.app')
@php
    $title    = 'Usuários';
    $pageIcon = 'people';
@endphp

@push('styles')
<style>
    .users-card {
        background: #fff;
        border-radius: 14px;
        border: 1px solid #e8eaf0;
        overflow: hidden;
    }
    .users-card-header {
        padding: 16px 22px;
        border-bottom: 1px solid #f0f2f7;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }
    .users-card-header h3 {
        font-size: 14px;
        font-weight: 700;
        color: #1a1d23;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .users-table { width: 100%; border-collapse: collapse; }
    .users-table th {
        font-size: 11px;
        font-weight: 700;
        color: #9ca3af;
        text-transform: uppercase;
        letter-spacing: .06em;
        padding: 10px 22px;
        text-align: left;
        border-bottom: 1px solid #f0f2f7;
        background: #fafbfc;
    }
    .users-table td {
        padding: 13px 22px;
        font-size: 13.5px;
        color: #374151;
        border-bottom: 1px solid #f7f8fa;
        vertical-align: middle;
    }
    .users-table tr:last-child td { border-bottom: none; }
    .users-table tr:hover td { background: #fafbfc; }

    .role-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        text-transform: capitalize;
    }
    .role-admin   { background: #eff6ff; color: #2563EB; }
    .role-manager { background: #f0fdf4; color: #16a34a; }
    .role-viewer  { background: #fef9c3; color: #a16207; }

    .btn-icon {
        width: 32px; height: 32px;
        border: 1px solid #e8eaf0;
        border-radius: 8px;
        background: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #6b7280;
        font-size: 14px;
        cursor: pointer;
        transition: all .15s;
    }
    .btn-icon:hover { background: #f4f6fb; color: #3B82F6; border-color: #dbeafe; }
    .btn-icon.danger:hover { background: #fef2f2; color: #EF4444; border-color: #fecaca; }

    /* Drawer */
    .drawer-overlay {
        display: none;
        position: fixed; inset: 0;
        background: rgba(0,0,0,.35);
        z-index: 300;
    }
    .drawer-overlay.open { display: block; }
    .drawer {
        position: fixed;
        top: 0; right: -420px;
        width: 420px; height: 100vh;
        background: #fff;
        z-index: 301;
        transition: right .25s cubic-bezier(.4,0,.2,1);
        display: flex;
        flex-direction: column;
        box-shadow: -4px 0 24px rgba(0,0,0,.1);
    }
    .drawer.open { right: 0; }
    .drawer-header {
        padding: 18px 22px;
        border-bottom: 1px solid #f0f2f7;
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 15px;
        font-weight: 700;
        color: #1a1d23;
    }
    .drawer-body { padding: 22px; flex: 1; overflow-y: auto; }

    .form-group { margin-bottom: 16px; }
    .form-group label { display: block; font-size: 12.5px; font-weight: 600; color: #374151; margin-bottom: 6px; }
    .form-control {
        width: 100%;
        padding: 9px 12px;
        border: 1px solid #d1d5db;
        border-radius: 9px;
        font-size: 13.5px;
        color: #1a1d23;
        outline: none;
        transition: border-color .15s;
        background: #fff;
    }
    .form-control:focus { border-color: #3B82F6; box-shadow: 0 0 0 3px rgba(59,130,246,.1); }
    .form-error { font-size: 12px; color: #EF4444; margin-top: 4px; }

    .drawer-footer {
        padding: 16px 22px;
        border-top: 1px solid #f0f2f7;
        display: flex;
        gap: 10px;
        justify-content: flex-end;
    }
    .btn-save {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 9px 20px;
        background: #3B82F6; color: #fff;
        border: none; border-radius: 9px;
        font-size: 13.5px; font-weight: 600;
        cursor: pointer; transition: background .15s;
    }
    .btn-save:hover { background: #2563EB; }
    .btn-save:disabled { opacity: .6; cursor: not-allowed; }
    .btn-cancel {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 9px 20px;
        background: #f4f6fb; color: #374151;
        border: 1px solid #e8eaf0; border-radius: 9px;
        font-size: 13.5px; font-weight: 600;
        cursor: pointer; transition: background .15s;
    }
    .btn-cancel:hover { background: #e8eaf0; }

    .btn-new {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 8px 16px;
        background: #3B82F6; color: #fff;
        border: none; border-radius: 9px;
        font-size: 13px; font-weight: 600;
        cursor: pointer; transition: background .15s;
    }
    .btn-new:hover { background: #2563EB; }

    .user-av {
        width: 32px; height: 32px;
        border-radius: 50%;
        background: linear-gradient(135deg,#10B981,#3B82F6);
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 12px; font-weight: 700;
        overflow: hidden; flex-shrink: 0;
    }
    .user-av img { width: 100%; height: 100%; object-fit: cover; }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #9ca3af;
    }
    .empty-state i { font-size: 36px; margin-bottom: 12px; display: block; }
</style>
@endpush

@section('content')
<div class="page-container">

    <div class="users-card">
        <div class="users-card-header">
            <h3><i class="bi bi-people" style="color:#3B82F6;"></i> Usuários da Conta</h3>
            @if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())
            <button class="btn-new" onclick="openDrawer()">
                <i class="bi bi-plus-lg"></i> Novo usuário
            </button>
            @endif
        </div>

        @if($users->isEmpty())
        <div class="empty-state">
            <i class="bi bi-people"></i>
            <p style="font-weight:600;color:#374151;">Nenhum usuário encontrado</p>
        </div>
        @else
        <table class="users-table" id="usersTable">
            <thead>
                <tr>
                    <th>Usuário</th>
                    <th>E-mail</th>
                    <th>Papel</th>
                    <th>Desde</th>
                    <th style="width:80px;"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $u)
                <tr id="user-row-{{ $u->id }}">
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div class="user-av">
                                @if($u->avatar)
                                    <img src="{{ $u->avatar }}" alt="{{ $u->name }}">
                                @else
                                    {{ strtoupper(substr($u->name, 0, 1)) }}
                                @endif
                            </div>
                            <div>
                                <div style="font-weight:600;color:#1a1d23;">{{ $u->name }}</div>
                                @if($u->id === auth()->id())
                                    <div style="font-size:11px;color:#9ca3af;">Você</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td>{{ $u->email }}</td>
                    <td>
                        <span class="role-badge role-{{ $u->role }}">{{ ucfirst($u->role) }}</span>
                    </td>
                    <td>{{ $u->created_at->format('d/m/Y') }}</td>
                    <td>
                        @if($u->id !== auth()->id() && (auth()->user()->isAdmin() || auth()->user()->isSuperAdmin()))
                        <div style="display:flex;gap:4px;">
                            <button class="btn-icon" title="Editar"
                                onclick="editUser({{ $u->id }}, '{{ addslashes($u->name) }}', '{{ $u->email }}', '{{ $u->role }}')">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn-icon danger" title="Excluir"
                                onclick="deleteUser({{ $u->id }}, '{{ addslashes($u->name) }}')">
                                <i class="bi bi-trash3"></i>
                            </button>
                        </div>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        @if($users->hasPages())
        <div style="padding:16px 22px;border-top:1px solid #f0f2f7;">
            {{ $users->links() }}
        </div>
        @endif
        @endif
    </div>

</div>

{{-- Drawer --}}
<div class="drawer-overlay" id="drawerOverlay" onclick="closeDrawer()"></div>
<div class="drawer" id="drawer">
    <div class="drawer-header">
        <span id="drawerTitle">Novo Usuário</span>
        <button onclick="closeDrawer()" style="background:none;border:none;font-size:18px;color:#6b7280;cursor:pointer;">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <div class="drawer-body">
        <input type="hidden" id="editUserId">
        <div class="form-group">
            <label>Nome completo</label>
            <input type="text" class="form-control" id="drawerName" placeholder="Nome do usuário">
            <div class="form-error d-none" id="errDName"></div>
        </div>
        <div class="form-group">
            <label>E-mail</label>
            <input type="email" class="form-control" id="drawerEmail" placeholder="email@empresa.com">
            <div class="form-error d-none" id="errDEmail"></div>
        </div>
        <div class="form-group" id="pwdGroup">
            <label>Senha</label>
            <input type="password" class="form-control" id="drawerPassword" placeholder="Mínimo 8 caracteres">
            <div class="form-error d-none" id="errDPwd"></div>
        </div>
        <div class="form-group">
            <label>Papel</label>
            <select class="form-control" id="drawerRole">
                <option value="viewer">Viewer — somente leitura</option>
                <option value="manager">Manager — gerencia leads</option>
                @if(auth()->user()->isSuperAdmin())
                <option value="admin">Admin — gerencia usuários</option>
                @endif
            </select>
            <div class="form-error d-none" id="errDRole"></div>
        </div>
    </div>
    <div class="drawer-footer">
        <button class="btn-cancel" onclick="closeDrawer()">Cancelar</button>
        <button class="btn-save" id="btnDrawerSave" onclick="saveUser()">
            <i class="bi bi-check2"></i> Salvar
        </button>
    </div>
</div>
@endsection

@push('scripts')
<script>
const storeUrl  = "{{ route('settings.users.store') }}";
const updateUrl = (id) => `{{ url('configuracoes/usuarios') }}/${id}`;
const deleteUrl = (id) => `{{ url('configuracoes/usuarios') }}/${id}`;
const csrf      = document.querySelector('meta[name=csrf-token]').content;

let editingId = null;

function openDrawer(mode = 'create') {
    editingId = null;
    document.getElementById('drawerTitle').textContent = 'Novo Usuário';
    document.getElementById('editUserId').value = '';
    document.getElementById('drawerName').value = '';
    document.getElementById('drawerEmail').value = '';
    document.getElementById('drawerPassword').value = '';
    document.getElementById('drawerRole').value = 'viewer';
    document.getElementById('pwdGroup').style.display = '';
    clearDrawerErrors();
    document.getElementById('drawerOverlay').classList.add('open');
    document.getElementById('drawer').classList.add('open');
}

function editUser(id, name, email, role) {
    editingId = id;
    document.getElementById('drawerTitle').textContent = 'Editar Usuário';
    document.getElementById('editUserId').value = id;
    document.getElementById('drawerName').value = name;
    document.getElementById('drawerEmail').value = email;
    document.getElementById('drawerRole').value = role;
    document.getElementById('pwdGroup').style.display = 'none'; // senha não editada aqui
    clearDrawerErrors();
    document.getElementById('drawerOverlay').classList.add('open');
    document.getElementById('drawer').classList.add('open');
}

function closeDrawer() {
    document.getElementById('drawerOverlay').classList.remove('open');
    document.getElementById('drawer').classList.remove('open');
}

async function saveUser() {
    clearDrawerErrors();
    const btn = document.getElementById('btnDrawerSave');
    btn.disabled = true;

    const isEdit = editingId !== null;
    const url    = isEdit ? updateUrl(editingId) : storeUrl;
    const method = isEdit ? 'PUT' : 'POST';

    const body = {
        name:  document.getElementById('drawerName').value,
        email: document.getElementById('drawerEmail').value,
        role:  document.getElementById('drawerRole').value,
    };
    if (!isEdit) body.password = document.getElementById('drawerPassword').value;

    try {
        const res  = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify(body),
        });
        const data = await res.json();

        if ((res.status === 200 || res.status === 201) && data.success) {
            toastr.success(isEdit ? 'Usuário atualizado!' : 'Usuário criado!');
            closeDrawer();
            setTimeout(() => location.reload(), 800);
        } else if (data.errors) {
            showDrawerErrors(data.errors);
        } else {
            toastr.error(data.message ?? 'Erro ao salvar.');
        }
    } catch { toastr.error('Erro de conexão.'); }
    btn.disabled = false;
}

function deleteUser(id, name) {
    confirmAction({
        title: 'Excluir usuário',
        message: `Tem certeza que deseja excluir o usuário "${name}"?`,
        confirmText: 'Excluir',
        onConfirm: async () => {
            try {
                const res  = await fetch(deleteUrl(id), {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                });
                const data = await res.json();
                if (data.success) {
                    toastr.success('Usuário excluído.');
                    const row = document.getElementById(`user-row-${id}`);
                    if (row) row.remove();
                } else {
                    toastr.error(data.message ?? 'Erro ao excluir.');
                }
            } catch { toastr.error('Erro de conexão.'); }
        },
    });
}

function clearDrawerErrors() {
    ['errDName','errDEmail','errDPwd','errDRole'].forEach(id => {
        const el = document.getElementById(id);
        if (el) { el.textContent = ''; el.classList.add('d-none'); }
    });
}

function showDrawerErrors(errors) {
    const map = { name: 'errDName', email: 'errDEmail', password: 'errDPwd', role: 'errDRole' };
    Object.keys(map).forEach(field => {
        if (errors[field]) {
            const el = document.getElementById(map[field]);
            if (el) { el.textContent = errors[field][0]; el.classList.remove('d-none'); }
        }
    });
}
</script>
@endpush

@extends('master.layouts.app')
@php $title = 'Administradores'; $pageIcon = 'shield-lock'; @endphp

@push('styles')
<style>
.admin-card {
    display: flex; align-items: center; gap: 14px; padding: 14px 18px;
    background: #fff; border: 1.5px solid #e8eaf0; border-radius: 12px;
    margin-bottom: 8px; transition: border-color .15s;
}
.admin-card:hover { border-color: #bfdbfe; }
.admin-card.owner { border-left: 4px solid #f59e0b; }
.admin-card.inactive { opacity: .5; }
.admin-av {
    width: 40px; height: 40px; border-radius: 50%; display: flex;
    align-items: center; justify-content: center; font-size: 14px;
    font-weight: 700; flex-shrink: 0;
}
.admin-info { flex: 1; min-width: 0; }
.admin-name { font-size: 14px; font-weight: 600; color: #1a1d23; }
.admin-email { font-size: 12px; color: #9ca3af; }
.admin-badges { display: flex; flex-wrap: wrap; gap: 4px; margin-top: 4px; }
.admin-badge { font-size: 10px; font-weight: 600; padding: 2px 8px; border-radius: 99px; }
.admin-actions { display: flex; gap: 6px; flex-shrink: 0; }
.admin-actions button {
    padding: 5px 12px; border-radius: 7px; font-size: 11px; font-weight: 600;
    cursor: pointer; border: 1.5px solid; display: flex; align-items: center; gap: 4px;
}
.btn-edit-admin { background: #eff6ff; color: #0085f3; border-color: #bfdbfe; }
.btn-del-admin { background: #fff; color: #dc2626; border-color: #fca5a5; }

/* Drawer */
.adm-drawer-overlay {
    display: none; position: fixed; inset: 0; background: rgba(0,0,0,.35);
    z-index: 1000; justify-content: flex-end;
}
.adm-drawer-overlay.open { display: flex; }
.adm-drawer {
    width: 420px; max-width: 100vw; background: #fff; height: 100%;
    box-shadow: -4px 0 20px rgba(0,0,0,.1); display: flex; flex-direction: column;
}
.adm-drawer-header {
    padding: 18px 22px; border-bottom: 1px solid #f0f2f7;
    display: flex; align-items: center; justify-content: space-between;
}
.adm-drawer-header h3 { font-size: 15px; font-weight: 700; color: #1a1d23; margin: 0; display: flex; align-items: center; gap: 8px; }
.adm-drawer-body { flex: 1; overflow-y: auto; padding: 18px 22px; }
.adm-drawer-footer { padding: 14px 22px; border-top: 1px solid #f0f2f7; }
.adm-field { margin-bottom: 14px; }
.adm-field label { display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 5px; }
.adm-field input { width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px; font-family: inherit; }
.adm-group-title { font-size: 11px; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: .06em; padding: 12px 0 6px; border-top: 1px solid #f0f2f7; margin-top: 6px; }
.adm-group-title:first-of-type { border-top: none; margin-top: 0; }
.adm-module {
    display: flex; align-items: center; gap: 8px; padding: 6px 8px;
    border-radius: 7px; cursor: pointer; transition: background .1s;
}
.adm-module:hover { background: #f8fafc; }
.adm-module input[type=checkbox] { accent-color: #0085f3; width: 15px; height: 15px; }
.adm-module label { font-size: 13px; color: #374151; cursor: pointer; flex: 1; display: flex; align-items: center; gap: 6px; }
.adm-module i { font-size: 13px; color: #9ca3af; width: 18px; text-align: center; }
</style>
@endpush

@section('content')
<div class="m-section-header">
    <div class="m-section-title">Administradores</div>
    <div class="m-section-subtitle">Gerencie os administradores do painel master</div>
</div>

<div class="m-card">
    <div class="m-card-header">
        <h3 class="m-card-title"><i class="bi bi-shield-lock"></i> Administradores Master ({{ $admins->count() }})</h3>
        <button onclick="openDrawer()" class="m-btn m-btn-primary m-btn-sm">
            <i class="bi bi-plus-lg"></i> Novo Administrador
        </button>
    </div>

    <div style="padding:16px 20px;">
        @foreach($admins as $admin)
            @php
                $isOwner = $admin->isOwnerAdmin();
                $modules = $admin->master_permissions['modules'] ?? [];
                $initials = strtoupper(mb_substr($admin->name, 0, 2));
            @endphp
            <div class="admin-card {{ $isOwner ? 'owner' : '' }} {{ !$admin->is_super_admin ? 'inactive' : '' }}">
                <div class="admin-av" style="background:{{ $isOwner ? '#fffbeb' : '#eff6ff' }};color:{{ $isOwner ? '#f59e0b' : '#0085f3' }};">
                    {{ $initials }}
                </div>
                <div class="admin-info">
                    <div class="admin-name">
                        {{ $admin->name }}
                        @if($isOwner)
                            <span class="admin-badge" style="background:#fffbeb;color:#f59e0b;">OWNER</span>
                        @elseif(!$admin->is_super_admin)
                            <span class="admin-badge" style="background:#f3f4f6;color:#6b7280;">DESATIVADO</span>
                        @else
                            <span class="admin-badge" style="background:#eff6ff;color:#0085f3;">SUB-MASTER</span>
                        @endif
                        @if($admin->totp_enabled)
                            <span title="2FA ativo" style="color:#10b981;font-size:12px;"><i class="bi bi-shield-check"></i></span>
                        @else
                            <span title="2FA pendente" style="color:#f59e0b;font-size:12px;"><i class="bi bi-shield-exclamation"></i></span>
                        @endif
                    </div>
                    <div class="admin-email">{{ $admin->email }}</div>
                    @if(!$isOwner && $admin->is_super_admin && count($modules) > 0)
                        <div class="admin-badges">
                            @foreach(array_slice($modules, 0, 5) as $mod)
                                <span class="admin-badge" style="background:#f3f4f6;color:#6b7280;">{{ $availableModules[$mod]['label'] ?? $mod }}</span>
                            @endforeach
                            @if(count($modules) > 5)
                                <span class="admin-badge" style="background:#f3f4f6;color:#6b7280;">+{{ count($modules) - 5 }}</span>
                            @endif
                        </div>
                    @elseif($isOwner)
                        <div class="admin-badges">
                            <span class="admin-badge" style="background:#f0fdf4;color:#059669;">Acesso total</span>
                        </div>
                    @endif
                </div>
                <div class="admin-actions">
                    @if(!$isOwner)
                        <button class="btn-edit-admin" onclick="editAdmin({{ $admin->id }}, {!! htmlspecialchars(json_encode(['name' => $admin->name, 'email' => $admin->email, 'modules' => $modules, 'is_active' => $admin->is_super_admin]), ENT_QUOTES, 'UTF-8') !!})">
                            <i class="bi bi-pencil"></i> Editar
                        </button>
                        <button class="btn-del-admin" onclick="removeAdmin({{ $admin->id }}, '{{ addslashes($admin->name) }}')">
                            <i class="bi bi-trash3"></i>
                        </button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>

{{-- ── Drawer ── --}}
<div class="adm-drawer-overlay" id="admDrawerOverlay" onclick="if(event.target===this)closeDrawer()">
    <div class="adm-drawer">
        <div class="adm-drawer-header">
            <h3><i class="bi bi-shield-lock" style="color:#0085f3;"></i> <span id="drawerTitle">Novo Administrador</span></h3>
            <button onclick="closeDrawer()" style="background:none;border:none;font-size:18px;cursor:pointer;color:#9ca3af;">&times;</button>
        </div>
        <div class="adm-drawer-body">
            <input type="hidden" id="editAdminId" value="">

            <div class="adm-field">
                <label>Nome</label>
                <input id="admName" placeholder="Nome completo">
            </div>
            <div class="adm-field">
                <label>Email</label>
                <input id="admEmail" type="email" placeholder="email@exemplo.com">
            </div>
            <div class="adm-field" id="passwordField">
                <label>Senha <span id="pwdHint" style="font-weight:400;color:#9ca3af;">(mínimo 8 caracteres)</span></label>
                <input id="admPassword" type="password" placeholder="••••••••">
            </div>

            <div style="font-size:13px;font-weight:700;color:#1a1d23;margin:18px 0 10px;display:flex;align-items:center;gap:6px;">
                <i class="bi bi-key" style="color:#0085f3;"></i> Módulos permitidos
            </div>

            <div style="display:flex;gap:6px;margin-bottom:12px;">
                <button onclick="toggleAll(true)" style="background:#eff6ff;color:#0085f3;border:1px solid #bfdbfe;border-radius:6px;padding:4px 10px;font-size:11px;font-weight:600;cursor:pointer;">Marcar todos</button>
                <button onclick="toggleAll(false)" style="background:#f3f4f6;color:#6b7280;border:1px solid #e5e7eb;border-radius:6px;padding:4px 10px;font-size:11px;font-weight:600;cursor:pointer;">Desmarcar todos</button>
            </div>

            @php
                $groupedModules = [];
                foreach ($availableModules as $key => $mod) {
                    $groupedModules[$mod['group']][$key] = $mod;
                }
            @endphp
            @foreach($groupedModules as $groupName => $mods)
                <div class="adm-group-title">{{ $groupName }}</div>
                @foreach($mods as $key => $mod)
                    <div class="adm-module">
                        <input type="checkbox" id="mod_{{ $key }}" value="{{ $key }}" class="module-check"
                            {{ $key === 'dashboard' ? 'checked disabled' : '' }}>
                        <label for="mod_{{ $key }}">
                            <i class="bi bi-{{ $mod['icon'] }}"></i> {{ $mod['label'] }}
                        </label>
                    </div>
                @endforeach
            @endforeach
        </div>
        <div class="adm-drawer-footer">
            <button id="btnSaveAdmin" onclick="saveAdmin()" style="width:100%;background:#0085f3;color:#fff;border:none;border-radius:9px;padding:10px;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;">
                <i class="bi bi-check2"></i> <span id="btnSaveText">Criar administrador</span>
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const csrf = document.querySelector('meta[name=csrf-token]').content;
const baseUrl = "{{ route('master.admins') }}";

function openDrawer(editMode = false) {
    document.getElementById('editAdminId').value = '';
    document.getElementById('admName').value = '';
    document.getElementById('admEmail').value = '';
    document.getElementById('admPassword').value = '';
    document.getElementById('drawerTitle').textContent = 'Novo Administrador';
    document.getElementById('btnSaveText').textContent = 'Criar administrador';
    document.getElementById('passwordField').querySelector('label span').textContent = '(mínimo 8 caracteres)';
    document.getElementById('admPassword').required = true;
    document.querySelectorAll('.module-check').forEach(c => { if (!c.disabled) c.checked = false; });
    document.getElementById('admDrawerOverlay').classList.add('open');
}

function closeDrawer() {
    document.getElementById('admDrawerOverlay').classList.remove('open');
}

function editAdmin(id, data) {
    document.getElementById('editAdminId').value = id;
    document.getElementById('admName').value = data.name;
    document.getElementById('admEmail').value = data.email;
    document.getElementById('admPassword').value = '';
    document.getElementById('drawerTitle').textContent = 'Editar Administrador';
    document.getElementById('btnSaveText').textContent = 'Salvar alterações';
    document.getElementById('passwordField').querySelector('label span').textContent = '(deixe vazio para manter)';
    document.getElementById('admPassword').required = false;

    document.querySelectorAll('.module-check').forEach(c => {
        if (!c.disabled) c.checked = data.modules.includes(c.value);
    });

    document.getElementById('admDrawerOverlay').classList.add('open');
}

function toggleAll(state) {
    document.querySelectorAll('.module-check').forEach(c => { if (!c.disabled) c.checked = state; });
}

async function saveAdmin() {
    const id       = document.getElementById('editAdminId').value;
    const name     = document.getElementById('admName').value.trim();
    const email    = document.getElementById('admEmail').value.trim();
    const password = document.getElementById('admPassword').value;
    const modules  = [...new Set([
        'dashboard',
        ...[...document.querySelectorAll('.module-check:checked')].map(c => c.value)
    ])];

    if (!name || !email) { toastr.warning('Preencha nome e email.'); return; }
    if (!id && !password) { toastr.warning('Defina uma senha.'); return; }
    if (modules.length === 0) { toastr.warning('Selecione pelo menos um módulo.'); return; }

    const body = { name, email, modules };
    if (password) body.password = password;

    const url    = id ? `${baseUrl}/${id}` : baseUrl;
    const method = id ? 'PUT' : 'POST';

    try {
        const res = await fetch(url, {
            method,
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify(body),
        });
        const data = await res.json();
        if (data.success) {
            toastr.success(data.message);
            setTimeout(() => window.location.reload(), 1000);
        } else {
            toastr.error(data.message || 'Erro ao salvar.');
        }
    } catch { toastr.error('Erro de conexão.'); }
}

function removeAdmin(id, name) {
    window.confirmAction({
        title: 'Remover acesso master?',
        message: `O administrador "${name}" perderá o acesso ao painel master.`,
        confirmText: 'Remover',
        onConfirm: async () => {
            try {
                const res = await fetch(`${baseUrl}/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                });
                const data = await res.json();
                if (data.success) {
                    toastr.success(data.message);
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    toastr.error(data.message || 'Erro.');
                }
            } catch { toastr.error('Erro de conexão.'); }
        },
    });
}
</script>
@endpush

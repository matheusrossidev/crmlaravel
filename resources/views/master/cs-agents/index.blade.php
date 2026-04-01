@extends('master.layouts.app')
@php $title = 'Customer Success'; $pageIcon = 'headset'; @endphp

@push('styles')
<style>
.cs-card {
    display: flex; align-items: center; gap: 14px; padding: 14px 18px;
    background: #fff; border: 1.5px solid #e8eaf0; border-radius: 12px;
    margin-bottom: 8px; transition: border-color .15s;
}
.cs-card:hover { border-color: #bfdbfe; }
.cs-av {
    width: 40px; height: 40px; border-radius: 50%; display: flex;
    align-items: center; justify-content: center; font-size: 14px;
    font-weight: 700; flex-shrink: 0; background: #eff6ff; color: #0085f3;
}
.cs-info { flex: 1; min-width: 0; }
.cs-name { font-size: 14px; font-weight: 600; color: #1a1d23; }
.cs-email { font-size: 12px; color: #9ca3af; }
.cs-badges { display: flex; flex-wrap: wrap; gap: 4px; margin-top: 4px; }
.cs-badge { font-size: 10px; font-weight: 600; padding: 2px 8px; border-radius: 99px; }
.cs-actions { display: flex; gap: 6px; flex-shrink: 0; }
.cs-actions button {
    padding: 5px 12px; border-radius: 7px; font-size: 11px; font-weight: 600;
    cursor: pointer; border: 1.5px solid; display: flex; align-items: center; gap: 4px;
}
.btn-edit-cs { background: #eff6ff; color: #0085f3; border-color: #bfdbfe; }
.btn-del-cs { background: #fff; color: #dc2626; border-color: #fca5a5; }
.cs-meta { font-size: 11px; color: #9ca3af; margin-top: 2px; }

/* Drawer */
.cs-drawer-overlay {
    display: none; position: fixed; inset: 0; background: rgba(0,0,0,.35);
    z-index: 1000; justify-content: flex-end;
}
.cs-drawer-overlay.open { display: flex; }
.cs-drawer {
    width: 420px; max-width: 100vw; background: #fff; height: 100%;
    box-shadow: -4px 0 20px rgba(0,0,0,.1); display: flex; flex-direction: column;
}
.cs-drawer-header {
    padding: 18px 22px; border-bottom: 1px solid #f0f2f7;
    display: flex; align-items: center; justify-content: space-between;
}
.cs-drawer-header h3 { font-size: 15px; font-weight: 700; color: #1a1d23; margin: 0; display: flex; align-items: center; gap: 8px; }
.cs-drawer-body { flex: 1; overflow-y: auto; padding: 18px 22px; }
.cs-drawer-footer { padding: 14px 22px; border-top: 1px solid #f0f2f7; }
.cs-field { margin-bottom: 14px; }
.cs-field label { display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 5px; }
.cs-field input, .cs-field select {
    width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px;
    font-size: 13px; font-family: inherit; background: #fff;
}

.cs-empty {
    text-align: center; padding: 48px 20px; color: #9ca3af;
}
.cs-empty i { font-size: 40px; display: block; margin-bottom: 12px; color: #d1d5db; }
.cs-empty p { font-size: 14px; margin: 0; }
</style>
@endpush

@section('content')
<div class="m-section-header">
    <div class="m-section-title">Customer Success</div>
    <div class="m-section-subtitle">Gerencie os agentes de atendimento ao cliente</div>
</div>

<div class="m-card">
    <div class="m-card-header">
        <h3 class="m-card-title"><i class="bi bi-headset"></i> Agentes CS ({{ $agents->count() }})</h3>
        <div style="display:flex;gap:8px;">
            <a href="{{ route('cs.index') }}" target="_blank" class="m-btn m-btn-outline m-btn-sm">
                <i class="bi bi-box-arrow-up-right"></i> Painel CS
            </a>
            <button onclick="openDrawer()" class="m-btn m-btn-primary m-btn-sm">
                <i class="bi bi-plus-lg"></i> Novo Agente CS
            </button>
        </div>
    </div>

    <div style="padding:16px 20px;">
        @if($agents->isEmpty())
            <div class="cs-empty">
                <i class="bi bi-headset"></i>
                <p>Nenhum agente CS cadastrado ainda.</p>
            </div>
        @else
            @foreach($agents as $agent)
                @php
                    $initials = strtoupper(mb_substr($agent->name, 0, 2));
                    $lastLogin = $agent->last_login_at ? \Carbon\Carbon::parse($agent->last_login_at)->diffForHumans() : 'Nunca';
                @endphp
                <div class="cs-card">
                    <div class="cs-av">{{ $initials }}</div>
                    <div class="cs-info">
                        <div class="cs-name">{{ $agent->name }}</div>
                        <div class="cs-email">{{ $agent->email }}</div>
                        <div class="cs-badges">
                            @if($agent->tenant)
                                <span class="cs-badge" style="background:#eff6ff;color:#0085f3;">{{ $agent->tenant->name }}</span>
                            @else
                                <span class="cs-badge" style="background:#fef3c7;color:#d97706;">Sem tenant</span>
                            @endif
                        </div>
                        <div class="cs-meta">
                            <i class="bi bi-clock"></i> Ultimo login: {{ $lastLogin }}
                        </div>
                    </div>
                    <div class="cs-actions">
                        <button class="btn-edit-cs" onclick="editAgent({{ $agent->id }}, {!! htmlspecialchars(json_encode(['name' => $agent->name, 'email' => $agent->email, 'tenant_id' => $agent->tenant_id]), ENT_QUOTES, 'UTF-8') !!})">
                            <i class="bi bi-pencil"></i> Editar
                        </button>
                        <button class="btn-del-cs" onclick="removeAgent({{ $agent->id }}, '{{ addslashes($agent->name) }}')">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</div>

{{-- Drawer --}}
<div class="cs-drawer-overlay" id="csDrawerOverlay" onclick="if(event.target===this)closeDrawer()">
    <div class="cs-drawer">
        <div class="cs-drawer-header">
            <h3><i class="bi bi-headset" style="color:#0085f3;"></i> <span id="drawerTitle">Novo Agente CS</span></h3>
            <button onclick="closeDrawer()" style="background:none;border:none;font-size:18px;cursor:pointer;color:#9ca3af;">&times;</button>
        </div>
        <div class="cs-drawer-body">
            <input type="hidden" id="editAgentId" value="">

            <div class="cs-field">
                <label>Nome</label>
                <input id="csName" placeholder="Nome completo">
            </div>
            <div class="cs-field">
                <label>Email</label>
                <input id="csEmail" type="email" placeholder="email@exemplo.com">
            </div>
            <div class="cs-field" id="passwordField">
                <label>Senha <span id="pwdHint" style="font-weight:400;color:#9ca3af;">(minimo 8 caracteres)</span></label>
                <input id="csPassword" type="password" placeholder="••••••••">
            </div>
            <div class="cs-field">
                <label>Tenant (opcional)</label>
                <select id="csTenant">
                    <option value="">— Sem tenant —</option>
                    @foreach($tenants as $tenant)
                        <option value="{{ $tenant->id }}">{{ $tenant->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="cs-drawer-footer">
            <button id="btnSaveAgent" onclick="saveAgent()" style="width:100%;background:#0085f3;color:#fff;border:none;border-radius:9px;padding:10px;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;">
                <i class="bi bi-check2"></i> <span id="btnSaveText">Criar agente CS</span>
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const csrf = document.querySelector('meta[name=csrf-token]').content;
const baseUrl = "{{ route('master.cs-agents') }}";

function openDrawer() {
    document.getElementById('editAgentId').value = '';
    document.getElementById('csName').value = '';
    document.getElementById('csEmail').value = '';
    document.getElementById('csPassword').value = '';
    document.getElementById('csTenant').value = '';
    document.getElementById('drawerTitle').textContent = 'Novo Agente CS';
    document.getElementById('btnSaveText').textContent = 'Criar agente CS';
    document.getElementById('pwdHint').textContent = '(minimo 8 caracteres)';
    document.getElementById('csPassword').required = true;
    document.getElementById('csDrawerOverlay').classList.add('open');
}

function closeDrawer() {
    document.getElementById('csDrawerOverlay').classList.remove('open');
}

function editAgent(id, data) {
    document.getElementById('editAgentId').value = id;
    document.getElementById('csName').value = data.name;
    document.getElementById('csEmail').value = data.email;
    document.getElementById('csPassword').value = '';
    document.getElementById('csTenant').value = data.tenant_id || '';
    document.getElementById('drawerTitle').textContent = 'Editar Agente CS';
    document.getElementById('btnSaveText').textContent = 'Salvar alteracoes';
    document.getElementById('pwdHint').textContent = '(deixe vazio para manter)';
    document.getElementById('csPassword').required = false;
    document.getElementById('csDrawerOverlay').classList.add('open');
}

async function saveAgent() {
    const id       = document.getElementById('editAgentId').value;
    const name     = document.getElementById('csName').value.trim();
    const email    = document.getElementById('csEmail').value.trim();
    const password = document.getElementById('csPassword').value;
    const tenant_id = document.getElementById('csTenant').value || null;

    if (!name || !email) { toastr.warning('Preencha nome e email.'); return; }
    if (!id && !password) { toastr.warning('Defina uma senha.'); return; }

    const body = { name, email, tenant_id };
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
    } catch { toastr.error('Erro de conexao.'); }
}

function removeAgent(id, name) {
    window.confirmAction({
        title: 'Remover acesso CS?',
        message: `O agente "${name}" perdera o acesso CS.`,
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
            } catch { toastr.error('Erro de conexao.'); }
        },
    });
}
</script>
@endpush

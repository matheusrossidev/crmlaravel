@extends('master.layouts.app')

@php
    $title    = 'Códigos de Parceiros';
    $pageIcon = 'building-check';
@endphp

@section('topbar_actions')
<button class="m-btn m-btn-primary" onclick="openNew()">
    <i class="bi bi-plus-lg"></i> Novo Código
</button>
@endsection

@section('content')

<div class="m-section-header">
    <div class="m-section-title">Códigos de Agência</div>
    <div class="m-section-subtitle">Gerencie os códigos de indicação dos parceiros</div>
</div>

<div class="m-card">
    <div class="m-card-header">
        <div class="m-card-title"><i class="bi bi-building-check"></i> Códigos de Parceiros</div>
        <div style="font-size:12.5px;color:#9ca3af;">
            Cada código é único e serve para cadastrar um parceiro e rastrear os clientes por ele indicados.
        </div>
    </div>
    <div style="overflow-x:auto;">
        <table class="m-table" id="codesTable">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Descrição</th>
                    <th>Parceiro Vinculado</th>
                    <th>Link de Cadastro</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($codes as $code)
                <tr id="code-row-{{ $code->id }}">
                    <td>
                        <code style="background:#f3f4f6;padding:3px 8px;border-radius:5px;font-size:13px;font-weight:700;color:#1e40af;">
                            {{ $code->code }}
                        </code>
                    </td>
                    <td style="color:#6b7280;font-size:13px;">{{ $code->description ?? '—' }}</td>
                    <td>
                        @if($code->tenant)
                            <span style="font-weight:600;color:#111827;">{{ $code->tenant->name }}</span>
                            <span style="font-size:11px;color:#9ca3af;margin-left:4px;">#{{ $code->tenant_id }}</span>
                        @else
                            <span style="color:#9ca3af;font-size:13px;">Não vinculado</span>
                        @endif
                    </td>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <span style="font-size:12px;color:#6b7280;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                /cadastro-agencia?code={{ $code->code }}
                            </span>
                            <button class="m-btn m-btn-ghost m-btn-sm"
                                    onclick="copyLink('{{ url('/cadastro-agencia?code=' . $code->code) }}')"
                                    title="Copiar link">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>
                    </td>
                    <td>
                        @if($code->is_active)
                            <span class="m-badge m-badge-active">Ativo</span>
                        @else
                            <span class="m-badge m-badge-inactive">Inativo</span>
                        @endif
                    </td>
                    <td style="white-space:nowrap;">
                        <button class="m-btn m-btn-ghost m-btn-sm"
                                onclick="editCode({{ $code->id }}, {{ json_encode(['code' => $code->code, 'description' => $code->description, 'is_active' => $code->is_active, 'tenant_id' => $code->tenant_id]) }})">
                            <i class="bi bi-pencil"></i>
                        </button>
                        @if(!$code->tenant_id)
                        <button class="m-btn m-btn-ghost m-btn-sm" style="color:#EF4444;"
                                onclick="deleteCode({{ $code->id }}, '{{ addslashes($code->code) }}')"
                                title="Excluir código">
                            <i class="bi bi-trash3"></i>
                        </button>
                        @endif
                    </td>
                </tr>
                @endforeach
                @if($codes->isEmpty())
                <tr>
                    <td colspan="6" style="text-align:center;padding:32px;color:#9ca3af;font-size:14px;">
                        Nenhum código cadastrado ainda.
                    </td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

{{-- ── Modal Criar/Editar ──────────────────────────────────────────────────── --}}
<div id="codeModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;align-items:center;justify-content:center;" onclick="if(event.target===this)closeModal()">
    <div style="background:#fff;border-radius:16px;padding:28px;width:100%;max-width:480px;box-shadow:0 20px 60px rgba(0,0,0,.2);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h3 id="modalTitle" style="margin:0;font-size:17px;font-weight:700;color:#111827;">Novo Código</h3>
            <button onclick="closeModal()" style="background:none;border:none;font-size:20px;color:#9ca3af;cursor:pointer;">&times;</button>
        </div>

        <form id="codeForm" onsubmit="submitForm(event)">
            <input type="hidden" id="editingId" value="">

            <div style="margin-bottom:16px;" id="codeFieldWrap">
                <label style="display:block;font-size:12.5px;font-weight:600;color:#374151;margin-bottom:6px;">
                    Código <span style="color:#ef4444;">*</span>
                </label>
                <div style="display:flex;gap:8px;">
                    <input type="text" id="codeInput" placeholder="AGC-EXEMPLO"
                           style="flex:1;padding:9px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;font-family:monospace;text-transform:uppercase;"
                           oninput="this.value=this.value.toUpperCase()" maxlength="20">
                    <button type="button" onclick="generateCode()"
                            style="padding:9px 14px;background:#f3f4f6;border:1px solid #d1d5db;border-radius:8px;font-size:13px;cursor:pointer;white-space:nowrap;">
                        <i class="bi bi-shuffle"></i> Gerar
                    </button>
                </div>
                <div style="font-size:11px;color:#9ca3af;margin-top:4px;">Apenas letras maiúsculas, números e hífens. Ex: AGC-STARTUP25</div>
            </div>

            <div style="margin-bottom:16px;">
                <label style="display:block;font-size:12.5px;font-weight:600;color:#374151;margin-bottom:6px;">Descrição (opcional)</label>
                <input type="text" id="descriptionInput" placeholder="Ex: Parceiro Marketing"
                       style="width:100%;padding:9px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;box-sizing:border-box;" maxlength="100">
            </div>

            {{-- Parceiro vinculado (só exibido na edição) --}}
            <div style="margin-bottom:16px;" id="tenantFieldWrap">
                <label style="display:block;font-size:12.5px;font-weight:600;color:#374151;margin-bottom:6px;">
                    Parceiro Vinculado
                </label>
                <select id="tenantIdInput"
                        style="width:100%;padding:9px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;background:#fff;box-sizing:border-box;">
                    <option value="">— Nenhum —</option>
                    @foreach($partners as $partner)
                    <option value="{{ $partner->id }}">{{ $partner->name }} #{{ $partner->id }}</option>
                    @endforeach
                </select>
                <div style="font-size:11px;color:#9ca3af;margin-top:4px;">Atribua este código a um parceiro existente.</div>
            </div>

            <div style="margin-bottom:24px;display:flex;align-items:center;gap:10px;">
                <input type="checkbox" id="isActiveInput" checked style="width:16px;height:16px;accent-color:#0085f3;">
                <label for="isActiveInput" style="font-size:14px;color:#374151;cursor:pointer;">Código ativo</label>
            </div>

            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" onclick="closeModal()" class="m-btn m-btn-ghost">Cancelar</button>
                <button type="submit" id="submitBtn" class="m-btn m-btn-primary">Criar Código</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
const STORE_URL  = '{{ route('master.agency-codes.store') }}';
const UPDATE_URL = (id) => `/master/codigos-agencia/${id}`;
const DELETE_URL = (id) => `/master/codigos-agencia/${id}`;
const GEN_URL    = '{{ route('master.agency-codes.generate') }}';

function openNew() {
    document.getElementById('editingId').value = '';
    document.getElementById('codeInput').value = '';
    document.getElementById('codeInput').disabled = false;
    document.getElementById('descriptionInput').value = '';
    document.getElementById('isActiveInput').checked = true;
    document.getElementById('tenantIdInput').value = '';
    document.getElementById('tenantFieldWrap').style.display = 'none';
    document.getElementById('codeFieldWrap').style.display = '';
    document.getElementById('modalTitle').textContent = 'Novo Código';
    document.getElementById('submitBtn').textContent = 'Criar Código';
    document.getElementById('codeModal').style.display = 'flex';
}

function editCode(id, data) {
    document.getElementById('editingId').value = id;
    document.getElementById('codeInput').value = data.code ?? '';
    document.getElementById('codeInput').disabled = false;
    document.getElementById('descriptionInput').value = data.description ?? '';
    document.getElementById('isActiveInput').checked = data.is_active;
    document.getElementById('tenantIdInput').value = data.tenant_id ?? '';
    document.getElementById('tenantFieldWrap').style.display = '';
    document.getElementById('codeFieldWrap').style.display = '';
    document.getElementById('modalTitle').textContent = 'Editar Código';
    document.getElementById('submitBtn').textContent = 'Salvar';
    document.getElementById('codeModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('codeModal').style.display = 'none';
}

async function generateCode() {
    const btn = document.querySelector('button[onclick="generateCode()"]');
    btn.disabled = true;
    btn.textContent = '...';
    try {
        const res = await window.API.post(GEN_URL, {});
        document.getElementById('codeInput').value = res.code;
    } catch (e) {
        toastr.error('Erro ao gerar código.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-shuffle"></i> Gerar';
    }
}

async function submitForm(e) {
    e.preventDefault();
    const id   = document.getElementById('editingId').value;
    const body = {
        code:        document.getElementById('codeInput').value.trim().toUpperCase(),
        description: document.getElementById('descriptionInput').value.trim() || null,
        is_active:   document.getElementById('isActiveInput').checked,
    };

    if (id) {
        const tenantVal = document.getElementById('tenantIdInput').value;
        body.tenant_id = tenantVal ? parseInt(tenantVal) : null;
    }

    try {
        if (id) {
            await window.API.put(UPDATE_URL(id), body);
            toastr.success('Código atualizado.');
        } else {
            await window.API.post(STORE_URL, body);
            toastr.success('Código criado com sucesso.');
        }
        closeModal();
        setTimeout(() => location.reload(), 600);
    } catch (err) {
        const msg = err?.responseJSON?.errors
            ? Object.values(err.responseJSON.errors).flat().join(' ')
            : (err?.responseJSON?.message ?? 'Erro ao salvar código.');
        toastr.error(msg);
    }
}

async function deleteCode(id, code) {
    if (!confirm(`Excluir o código "${code}"? Esta ação não pode ser desfeita.`)) return;
    try {
        await window.API.delete(DELETE_URL(id));
        document.getElementById(`code-row-${id}`)?.remove();
        toastr.success('Código excluído.');
    } catch (err) {
        toastr.error(err?.responseJSON?.message ?? 'Não foi possível excluir.');
    }
}

function copyLink(url) {
    navigator.clipboard.writeText(url).then(() => toastr.success('Link copiado!'));
}
</script>
@endpush

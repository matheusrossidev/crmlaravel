@extends('master.layouts.app')
@php
    $title    = 'Planos';
    $pageIcon = 'layers';
@endphp

@section('topbar_actions')
<button class="m-btn m-btn-primary" onclick="openNewPlan()">
    <i class="bi bi-plus-lg"></i> Novo Plano
</button>
@endsection

@section('content')

<div class="m-card">
    <div class="m-card-header">
        <div class="m-card-title"><i class="bi bi-layers"></i> Definições de Planos</div>
    </div>
    <div style="overflow-x:auto;">
        <table class="m-table" id="plansTable">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Exibição</th>
                    <th>Preço/mês</th>
                    <th>Trial</th>
                    <th>Usuários</th>
                    <th>Leads</th>
                    <th>Pipelines</th>
                    <th>Campos</th>
                    <th>IA</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($plans as $plan)
                <tr id="plan-row-{{ $plan->id }}">
                    <td><code style="font-size:12px;">{{ $plan->name }}</code></td>
                    <td style="font-weight:600;">{{ $plan->display_name }}</td>
                    <td>R$ {{ number_format($plan->price_monthly, 2, ',', '.') }}</td>
                    <td>
                        @if($plan->trial_days !== null)
                            <span style="background:#fff7ed;color:#c2410c;padding:2px 8px;border-radius:6px;font-size:12px;font-weight:600;">{{ $plan->trial_days }}d</span>
                        @else
                            <span style="color:#9ca3af;font-size:12px;">—</span>
                        @endif
                    </td>
                    <td>{{ $plan->features_json['max_users'] ?? '—' }}</td>
                    <td>{{ $plan->features_json['max_leads'] ?? '—' }}</td>
                    <td>{{ $plan->features_json['max_pipelines'] ?? '—' }}</td>
                    <td>{{ $plan->features_json['max_custom_fields'] ?? '—' }}</td>
                    <td>
                        @if($plan->features_json['ai_agents'] ?? false)
                            <i class="bi bi-check-circle-fill" style="color:#10B981;"></i>
                        @else
                            <i class="bi bi-x-circle" style="color:#D1D5DB;"></i>
                        @endif
                    </td>
                    <td>
                        @if($plan->is_active)
                            <span class="m-badge m-badge-active">Ativo</span>
                        @else
                            <span class="m-badge m-badge-inactive">Inativo</span>
                        @endif
                    </td>
                    <td style="white-space:nowrap;">
                        <button class="m-btn m-btn-ghost m-btn-sm" onclick="editPlan({{ $plan->id }}, {{ json_encode($plan) }})">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="m-btn m-btn-ghost m-btn-sm" style="color:#EF4444;"
                                onclick="deletePlan({{ $plan->id }}, '{{ addslashes($plan->display_name) }}')"
                                title="Excluir plano">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Modal --}}
<div id="planModal" style="display:none;position:fixed;inset:0;z-index:1050;background:rgba(0,0,0,.4);align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;width:520px;max-width:95vw;padding:28px;box-shadow:0 8px 48px rgba(0,0,0,.2);max-height:90vh;overflow-y:auto;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h3 id="planModalTitle" style="font-size:16px;font-weight:700;margin:0;">Plano</h3>
            <button onclick="closePlanModal()" style="background:none;border:none;cursor:pointer;font-size:22px;color:#9ca3af;">×</button>
        </div>

        <input type="hidden" id="planId">

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
            <div>
                <label style="font-size:12.5px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Nome técnico</label>
                <input type="text" id="planName" class="form-control" placeholder="ex: starter" style="border:1px solid #d1d5db;border-radius:8px;padding:8px 11px;width:100%;font-size:13.5px;">
                <div style="font-size:11px;color:#9ca3af;margin-top:3px;">Só para edição (não alterar em planos existentes)</div>
            </div>
            <div>
                <label style="font-size:12.5px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Nome de exibição</label>
                <input type="text" id="planDisplayName" class="form-control" placeholder="ex: Starter" style="border:1px solid #d1d5db;border-radius:8px;padding:8px 11px;width:100%;font-size:13.5px;">
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
            <div>
                <label style="font-size:12.5px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Preço mensal (R$)</label>
                <input type="number" id="planPrice" min="0" step="0.01" class="form-control" placeholder="0.00" style="border:1px solid #d1d5db;border-radius:8px;padding:8px 11px;width:100%;font-size:13.5px;">
            </div>
            <div>
                <label style="font-size:12.5px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Dias de trial gratuito</label>
                <input type="number" id="planTrialDays" min="0" max="365" placeholder="ex: 14" style="border:1px solid #d1d5db;border-radius:8px;padding:8px 11px;width:100%;font-size:13.5px;">
                <div style="font-size:11px;color:#9ca3af;margin-top:3px;">Vazio = sem período trial</div>
            </div>
        </div>

        <div style="font-size:12.5px;font-weight:600;color:#374151;margin-bottom:10px;">Limites e features</div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:14px;">
            <div>
                <label style="font-size:11.5px;color:#6b7280;display:block;margin-bottom:4px;">Max usuários</label>
                <input type="number" id="fMaxUsers" min="0" style="border:1px solid #d1d5db;border-radius:7px;padding:7px 10px;width:100%;font-size:13px;">
            </div>
            <div>
                <label style="font-size:11.5px;color:#6b7280;display:block;margin-bottom:4px;">Max leads</label>
                <input type="number" id="fMaxLeads" min="0" style="border:1px solid #d1d5db;border-radius:7px;padding:7px 10px;width:100%;font-size:13px;">
            </div>
            <div>
                <label style="font-size:11.5px;color:#6b7280;display:block;margin-bottom:4px;">Max pipelines</label>
                <input type="number" id="fMaxPipelines" min="0" style="border:1px solid #d1d5db;border-radius:7px;padding:7px 10px;width:100%;font-size:13px;">
            </div>
            <div>
                <label style="font-size:11.5px;color:#6b7280;display:block;margin-bottom:4px;">Max campos personalizados</label>
                <input type="number" id="fMaxCustomFields" min="0" style="border:1px solid #d1d5db;border-radius:7px;padding:7px 10px;width:100%;font-size:13px;">
            </div>
        </div>
        <div style="margin-bottom:14px;">
            <label style="font-size:11.5px;color:#6b7280;display:block;margin-bottom:4px;">Tokens IA/mês</label>
            <input type="number" id="fAiTokens" min="0" style="border:1px solid #d1d5db;border-radius:7px;padding:7px 10px;width:200px;font-size:13px;">
        </div>
        <div style="display:flex;gap:20px;margin-bottom:20px;flex-wrap:wrap;">
            <label style="display:flex;align-items:center;gap:7px;font-size:13px;cursor:pointer;">
                <input type="checkbox" id="fAiAgents"> Agentes IA
            </label>
            <label style="display:flex;align-items:center;gap:7px;font-size:13px;cursor:pointer;">
                <input type="checkbox" id="fInstagram"> Instagram
            </label>
            <label style="display:flex;align-items:center;gap:7px;font-size:13px;cursor:pointer;">
                <input type="checkbox" id="fChatbot"> Chatbot
            </label>
            <label style="display:flex;align-items:center;gap:7px;font-size:13px;cursor:pointer;">
                <input type="checkbox" id="fIsActive"> Plano ativo
            </label>
        </div>

        <div style="display:flex;gap:10px;justify-content:flex-end;">
            <button onclick="closePlanModal()" class="btn-clear">Cancelar</button>
            <button onclick="savePlan()" class="btn-apply" id="btnSavePlan">Salvar</button>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
.btn-clear { display:inline-flex;align-items:center;padding:8px 18px;background:transparent;color:#6b7280;border:1.5px solid #e5e7eb;border-radius:9px;font-size:13.5px;font-weight:600;cursor:pointer;transition:.15s; }
.btn-clear:hover { background:#f3f4f6; }
.btn-apply { display:inline-flex;align-items:center;padding:8px 22px;background:#3B82F6;color:#fff;border:none;border-radius:9px;font-size:13.5px;font-weight:600;cursor:pointer;transition:.15s; }
.btn-apply:hover { background:#2563EB; }
</style>
@endpush

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name=csrf-token]').content;
const ROUTE_STORE  = "{{ route('master.plans.store') }}";
const ROUTE_UPDATE = (id) => `/master/planos/${id}`;
const ROUTE_DELETE = (id) => `/master/planos/${id}`;

let editingId = null;

function openNewPlan() {
    editingId = null;
    document.getElementById('planModalTitle').textContent = 'Novo Plano';
    document.getElementById('planId').value = '';
    document.getElementById('planName').value = '';
    document.getElementById('planName').disabled = false;
    document.getElementById('planDisplayName').value = '';
    document.getElementById('planPrice').value = '0';
    document.getElementById('planTrialDays').value = '';
    document.getElementById('fMaxUsers').value = '5';
    document.getElementById('fMaxLeads').value = '1000';
    document.getElementById('fMaxPipelines').value = '3';
    document.getElementById('fMaxCustomFields').value = '10';
    document.getElementById('fAiTokens').value = '500000';
    document.getElementById('fAiAgents').checked = true;
    document.getElementById('fInstagram').checked = false;
    document.getElementById('fChatbot').checked = false;
    document.getElementById('fIsActive').checked = true;
    showModal();
}

function editPlan(id, plan) {
    editingId = id;
    document.getElementById('planModalTitle').textContent = 'Editar Plano: ' + plan.display_name;
    document.getElementById('planId').value = id;
    document.getElementById('planName').value = plan.name;
    document.getElementById('planName').disabled = true;
    document.getElementById('planDisplayName').value = plan.display_name;
    document.getElementById('planPrice').value = plan.price_monthly;
    document.getElementById('planTrialDays').value = (plan.trial_days !== null && plan.trial_days !== undefined) ? plan.trial_days : '';
    const f = plan.features_json || {};
    document.getElementById('fMaxUsers').value       = f.max_users ?? 5;
    document.getElementById('fMaxLeads').value       = f.max_leads ?? 1000;
    document.getElementById('fMaxPipelines').value   = f.max_pipelines ?? 3;
    document.getElementById('fMaxCustomFields').value = f.max_custom_fields ?? 10;
    document.getElementById('fAiTokens').value    = f.ai_tokens_monthly ?? 0;
    document.getElementById('fAiAgents').checked   = !!f.ai_agents;
    document.getElementById('fInstagram').checked  = !!f.instagram;
    document.getElementById('fChatbot').checked    = !!f.chatbot;
    document.getElementById('fIsActive').checked   = !!plan.is_active;
    showModal();
}

async function savePlan() {
    const btn = document.getElementById('btnSavePlan');
    btn.disabled = true;

    const trialRaw = document.getElementById('planTrialDays').value;

    const payload = {
        name:           document.getElementById('planName').value,
        display_name:   document.getElementById('planDisplayName').value,
        price_monthly:  parseFloat(document.getElementById('planPrice').value) || 0,
        trial_days:     trialRaw !== '' ? parseInt(trialRaw) : null,
        is_active:      document.getElementById('fIsActive').checked ? 1 : 0,
        features_json: {
            max_users:          parseInt(document.getElementById('fMaxUsers').value) || 0,
            max_leads:          parseInt(document.getElementById('fMaxLeads').value) || 0,
            max_pipelines:      parseInt(document.getElementById('fMaxPipelines').value) || 0,
            max_custom_fields:  parseInt(document.getElementById('fMaxCustomFields').value) || 0,
            ai_tokens_monthly:  parseInt(document.getElementById('fAiTokens').value) || 0,
            ai_agents:          document.getElementById('fAiAgents').checked,
            instagram:          document.getElementById('fInstagram').checked,
            chatbot:            document.getElementById('fChatbot').checked,
        },
    };

    const url    = editingId ? ROUTE_UPDATE(editingId) : ROUTE_STORE;
    const method = editingId ? 'PUT' : 'POST';

    try {
        const res  = await fetch(url, {
            method,
            headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':CSRF, 'Accept':'application/json' },
            body: JSON.stringify(payload),
        });
        const data = await res.json();
        if (res.ok && data.success) {
            toastr.success('Plano salvo!');
            closePlanModal();
            setTimeout(() => location.reload(), 800);
        } else {
            const msg = data.message || Object.values(data.errors || {}).flat().join(', ');
            toastr.error(msg || 'Erro ao salvar.');
        }
    } catch { toastr.error('Erro de conexão.'); }
    btn.disabled = false;
}

async function deletePlan(id, name) {
    if (!confirm(`Excluir o plano "${name}"?\n\nEmpresas existentes com este plano não serão afetadas.`)) return;

    try {
        const res  = await fetch(ROUTE_DELETE(id), {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        });
        const data = await res.json();
        if (res.ok && data.success) {
            toastr.success('Plano excluído.');
            const row = document.getElementById(`plan-row-${id}`);
            if (row) row.remove();
        } else {
            toastr.error(data.message || 'Erro ao excluir.');
        }
    } catch { toastr.error('Erro de conexão.'); }
}

function showModal() {
    const m = document.getElementById('planModal');
    m.style.display = 'flex';
}
function closePlanModal() {
    document.getElementById('planModal').style.display = 'none';
}
</script>
@endpush

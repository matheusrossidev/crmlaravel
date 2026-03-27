@extends('master.layouts.app')
@php
    $title    = 'Pacotes de Tokens';
    $pageIcon = 'coin';
@endphp

@section('topbar_actions')
<button class="m-btn m-btn-primary" onclick="openNewPlan()">
    <i class="bi bi-plus-lg"></i> Novo Pacote
</button>
@endsection

@section('content')

<div class="m-card">
    <div class="m-card-header">
        <div class="m-card-title"><i class="bi bi-coin"></i> Pacotes de Incremento de Tokens</div>
        <div style="font-size:12.5px;color:#9ca3af;">Pacotes que os tenants podem comprar para ampliar a quota mensal de tokens de IA.</div>
    </div>
    <div style="overflow-x:auto;">
        <table class="m-table" id="plansTable">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Tokens</th>
                    <th>Preço (BRL)</th>
                    <th>Preço (USD)</th>
                    <th>Stripe</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($plans as $plan)
                <tr id="plan-row-{{ $plan->id }}">
                    <td style="font-weight:600;">{{ $plan->display_name }}</td>
                    <td>
                        <span style="background:#eff6ff;color:#2563eb;padding:3px 10px;border-radius:6px;font-size:12.5px;font-weight:700;">
                            +{{ number_format($plan->tokens_amount, 0, ',', '.') }} tokens
                        </span>
                    </td>
                    <td style="font-weight:600;">R$ {{ number_format($plan->price, 2, ',', '.') }}</td>
                    <td>
                        @if($plan->price_usd)
                            <span style="font-weight:600;">$ {{ number_format((float)$plan->price_usd, 2, '.', ',') }}</span>
                        @else
                            <span style="color:#9ca3af;font-size:12px;">—</span>
                        @endif
                    </td>
                    <td>
                        @if($plan->stripe_price_id)
                            <code style="font-size:11px;background:#f3f4f6;padding:2px 6px;border-radius:4px;">{{ Str::limit($plan->stripe_price_id, 18) }}</code>
                        @else
                            <span style="color:#9ca3af;font-size:12px;">—</span>
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
                                title="Excluir pacote">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </td>
                </tr>
                @endforeach
                @if($plans->isEmpty())
                <tr>
                    <td colspan="7" style="text-align:center;color:#9ca3af;padding:40px;">
                        Nenhum pacote criado ainda.
                    </td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

{{-- Modal --}}
<div id="planModal" style="display:none;position:fixed;inset:0;z-index:1050;background:rgba(0,0,0,.4);align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;width:460px;max-width:95vw;padding:28px;box-shadow:0 8px 48px rgba(0,0,0,.2);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h3 id="planModalTitle" style="font-size:16px;font-weight:700;margin:0;">Pacote de Tokens</h3>
            <button onclick="closePlanModal()" style="background:none;border:none;cursor:pointer;font-size:22px;color:#9ca3af;">×</button>
        </div>

        <input type="hidden" id="planId">

        <div style="margin-bottom:14px;">
            <label style="font-size:12.5px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Nome do pacote</label>
            <input type="text" id="planName" class="form-control" placeholder="ex: Pack +500k tokens"
                style="border:1px solid #d1d5db;border-radius:8px;padding:8px 11px;width:100%;font-size:13.5px;">
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
            <div>
                <label style="font-size:12.5px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Tokens adicionados</label>
                <input type="number" id="planTokens" min="1" step="1000" class="form-control" placeholder="500000"
                    style="border:1px solid #d1d5db;border-radius:8px;padding:8px 11px;width:100%;font-size:13.5px;">
            </div>
            <div>
                <label style="font-size:12.5px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Preço (R$)</label>
                <input type="number" id="planPrice" min="0" step="0.01" class="form-control" placeholder="49.90"
                    style="border:1px solid #d1d5db;border-radius:8px;padding:8px 11px;width:100%;font-size:13.5px;">
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
            <div>
                <label style="font-size:12.5px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Preço (USD)</label>
                <input type="number" id="planPriceUsd" min="0" step="0.01" class="form-control" placeholder="9.90"
                    style="border:1px solid #d1d5db;border-radius:8px;padding:8px 11px;width:100%;font-size:13.5px;">
                <div style="font-size:11px;color:#9ca3af;margin-top:3px;">Preço em dólar para cobrança via Stripe</div>
            </div>
            <div>
                <label style="font-size:12.5px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Stripe Price ID</label>
                <input type="text" id="planStripePriceId" class="form-control" placeholder="price_1Abc..."
                    style="border:1px solid #d1d5db;border-radius:8px;padding:8px 11px;width:100%;font-size:13.5px;">
                <div style="font-size:11px;color:#9ca3af;margin-top:3px;">ID do preço no Stripe Dashboard</div>
            </div>
        </div>

        <div style="margin-bottom:20px;">
            <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer;">
                <input type="checkbox" id="planIsActive"> Pacote ativo (visível para tenants)
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
const CSRF         = document.querySelector('meta[name=csrf-token]').content;
const ROUTE_STORE  = "{{ route('master.token-increments.store') }}";
const ROUTE_UPDATE = (id) => `/master/token-incrementos/${id}`;
const ROUTE_DELETE = (id) => `/master/token-incrementos/${id}`;

let editingId = null;

function openNewPlan() {
    editingId = null;
    document.getElementById('planModalTitle').textContent = 'Novo Pacote de Tokens';
    document.getElementById('planId').value = '';
    document.getElementById('planName').value = '';
    document.getElementById('planTokens').value = '500000';
    document.getElementById('planPrice').value = '49.90';
    document.getElementById('planPriceUsd').value = '';
    document.getElementById('planStripePriceId').value = '';
    document.getElementById('planIsActive').checked = true;
    showModal();
}

function editPlan(id, plan) {
    editingId = id;
    document.getElementById('planModalTitle').textContent = 'Editar Pacote: ' + plan.display_name;
    document.getElementById('planId').value = id;
    document.getElementById('planName').value = plan.display_name;
    document.getElementById('planTokens').value = plan.tokens_amount;
    document.getElementById('planPrice').value = plan.price;
    document.getElementById('planPriceUsd').value = plan.price_usd || '';
    document.getElementById('planStripePriceId').value = plan.stripe_price_id || '';
    document.getElementById('planIsActive').checked = !!plan.is_active;
    showModal();
}

async function savePlan() {
    const btn = document.getElementById('btnSavePlan');
    btn.disabled = true;

    const priceUsdRaw = document.getElementById('planPriceUsd').value;
    const stripePriceId = document.getElementById('planStripePriceId').value.trim();

    const payload = {
        display_name:    document.getElementById('planName').value,
        tokens_amount:   parseInt(document.getElementById('planTokens').value) || 0,
        price:           parseFloat(document.getElementById('planPrice').value) || 0,
        price_usd:       priceUsdRaw !== '' ? parseFloat(priceUsdRaw) : null,
        stripe_price_id: stripePriceId || null,
        is_active:       document.getElementById('planIsActive').checked ? 1 : 0,
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
            toastr.success('Pacote salvo!');
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
    if (!confirm(`Excluir o pacote "${name}"?\n\nIsso não poderá ser desfeito.`)) return;

    try {
        const res  = await fetch(ROUTE_DELETE(id), {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        });
        const data = await res.json();
        if (res.ok && data.success) {
            toastr.success('Pacote excluído.');
            const row = document.getElementById(`plan-row-${id}`);
            if (row) row.remove();
        } else {
            toastr.error(data.message || 'Erro ao excluir.');
        }
    } catch { toastr.error('Erro de conexão.'); }
}

function showModal() {
    document.getElementById('planModal').style.display = 'flex';
}
function closePlanModal() {
    document.getElementById('planModal').style.display = 'none';
}
</script>
@endpush

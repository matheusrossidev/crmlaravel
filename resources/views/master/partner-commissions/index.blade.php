@extends('master.layouts.app')
@php $title = 'Comissões e Saques'; $pageIcon = 'cash-coin'; @endphp

@push('styles')
<style>
.tab-btns { display: flex; gap: 0; border-bottom: 2px solid #e8eaf0; margin-bottom: 20px; }
.tab-btn { padding: 10px 20px; font-size: 13px; font-weight: 600; color: #6b7280; cursor: pointer; border: none; border-bottom: 2.5px solid transparent; margin-bottom: -2px; background: none; }
.tab-btn:hover { color: #1a1d23; }
.tab-btn.active { color: #0085f3; border-bottom-color: #0085f3; }
.tab-panel { display: none; }
.tab-panel.active { display: block; }
.wd-status { font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 99px; }
.wd-status.pending { background: #fffbeb; color: #92400e; }
.wd-status.approved, .wd-status.processing { background: #eff6ff; color: #1e40af; }
.wd-status.paid { background: #ecfdf5; color: #065f46; }
.wd-status.rejected { background: #fef2f2; color: #991b1b; }
.wd-status.available { background: #ecfdf5; color: #065f46; }
.wd-status.withdrawn { background: #f3f4f6; color: #6b7280; }
.wd-status.cancelled { background: #fef2f2; color: #991b1b; }
</style>
@endpush

@section('content')
<div class="m-section-header">
    <div class="m-section-title">Comissões e Saques</div>
    <div class="m-section-subtitle">Gerencie comissões e solicitações de saque dos parceiros</div>
</div>

<div class="tab-btns">
    <button class="tab-btn active" onclick="switchTab('withdrawals', this)">Saques @if($pendingWithdrawals > 0)<span style="background:#ef4444;color:#fff;padding:1px 7px;border-radius:99px;font-size:10px;margin-left:4px;">{{ $pendingWithdrawals }}</span>@endif</button>
    <button class="tab-btn" onclick="switchTab('commissions', this)">Comissões</button>
</div>

{{-- Withdrawals --}}
<div class="tab-panel active" id="panel-withdrawals">
    <div class="m-card">
        <div class="m-card-header">
            <div class="m-card-title"><i class="bi bi-wallet2"></i> Solicitações de Saque</div>
        </div>
        @if($withdrawals->isEmpty())
            <div style="padding:40px;text-align:center;color:#9ca3af;">Nenhum saque solicitado.</div>
        @else
            <div style="overflow-x:auto;">
                <table class="m-table">
                    <thead><tr><th>Data</th><th>Parceiro</th><th>Valor</th><th>PIX</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @foreach($withdrawals as $wd)
                        <tr>
                            <td style="font-size:12px;color:#6b7280;">{{ $wd->requested_at?->format('d/m/Y H:i') }}</td>
                            <td style="font-weight:600;color:#1a1d23;">{{ $wd->partner?->name ?? '#' . $wd->tenant_id }}</td>
                            <td style="font-weight:700;">R$ {{ number_format((float)$wd->amount, 2, ',', '.') }}</td>
                            <td style="font-size:12px;">
                                <div style="font-weight:600;">{{ $wd->pix_holder_name }}</div>
                                <div style="color:#6b7280;">{{ $wd->pix_key_type }}: {{ $wd->pix_key }}</div>
                                <div style="color:#9ca3af;">{{ $wd->pix_holder_cpf_cnpj }}</div>
                            </td>
                            <td><span class="wd-status {{ $wd->status }}">{{ ucfirst($wd->status) }}</span></td>
                            <td style="text-align:right;white-space:nowrap;">
                                @if($wd->status === 'pending')
                                    <button style="padding:5px 12px;background:#059669;color:#fff;border:none;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;" onclick="approveWd({{ $wd->id }})">Aprovar</button>
                                    <button style="padding:5px 12px;background:#ef4444;color:#fff;border:none;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;margin-left:4px;" onclick="rejectWd({{ $wd->id }})">Rejeitar</button>
                                @elseif(in_array($wd->status, ['approved', 'processing']))
                                    <button style="padding:5px 12px;background:#0085f3;color:#fff;border:none;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;" onclick="markPaid({{ $wd->id }})">Marcar pago</button>
                                @elseif($wd->status === 'paid')
                                    <span style="font-size:11px;color:#059669;">Pago {{ $wd->paid_at?->format('d/m') }}</span>
                                @elseif($wd->status === 'rejected')
                                    <span style="font-size:11px;color:#ef4444;">{{ $wd->rejected_reason ?? 'Rejeitado' }}</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div style="padding:12px 20px;">{{ $withdrawals->links('pagination::bootstrap-5') }}</div>
        @endif
    </div>
</div>

{{-- Commissions --}}
<div class="tab-panel" id="panel-commissions">
    <div class="m-card">
        <div class="m-card-header">
            <div class="m-card-title"><i class="bi bi-cash-stack"></i> Comissões Geradas</div>
        </div>
        @if($commissions->isEmpty())
            <div style="padding:40px;text-align:center;color:#9ca3af;">Nenhuma comissão gerada ainda.</div>
        @else
            <div style="overflow-x:auto;">
                <table class="m-table">
                    <thead><tr><th>Data</th><th>Parceiro</th><th>Cliente</th><th>Valor</th><th>Status</th><th>Disponível em</th></tr></thead>
                    <tbody>
                        @foreach($commissions as $c)
                        <tr>
                            <td style="font-size:12px;color:#6b7280;">{{ $c->created_at?->format('d/m/Y') }}</td>
                            <td style="font-weight:600;color:#1a1d23;">{{ $c->partner?->name ?? '#' . $c->tenant_id }}</td>
                            <td>{{ $c->clientTenant?->name ?? '#' . $c->client_tenant_id }}</td>
                            <td style="font-weight:700;color:#059669;">R$ {{ number_format((float)$c->amount, 2, ',', '.') }}</td>
                            <td><span class="wd-status {{ $c->status }}">{{ ucfirst($c->status) }}</span></td>
                            <td style="font-size:12px;color:#6b7280;">{{ $c->available_at?->format('d/m/Y') ?? '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div style="padding:12px 20px;">{{ $commissions->links('pagination::bootstrap-5') }}</div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
function switchTab(name, el) { document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active')); document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active')); el.classList.add('active'); document.getElementById('panel-' + name).classList.add('active'); }

async function approveWd(id) {
    if (!confirm('Aprovar saque e iniciar transferência PIX?')) return;
    const r = await fetch('{{ route("master.partner-withdrawals.approve", "__ID__") }}'.replace('__ID__', id), { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' } });
    const d = await r.json();
    if (d.success) { toastr.success(d.message || 'Aprovado!'); setTimeout(() => location.reload(), 800); }
    else { toastr.error(d.message || 'Erro'); }
}
function rejectWd(id) {
    const reason = prompt('Motivo da rejeição:');
    if (reason === null) return;
    fetch('{{ route("master.partner-withdrawals.reject", "__ID__") }}'.replace('__ID__', id), { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' }, body: JSON.stringify({ reason }) }).then(r => r.json()).then(d => { if (d.success) { toastr.success('Rejeitado'); setTimeout(() => location.reload(), 800); } });
}
function markPaid(id) {
    if (!confirm('Confirmar que o pagamento foi realizado?')) return;
    fetch('{{ route("master.partner-withdrawals.paid", "__ID__") }}'.replace('__ID__', id), { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' } }).then(r => r.json()).then(d => { if (d.success) { toastr.success('Marcado como pago!'); setTimeout(() => location.reload(), 800); } });
}
</script>
@endpush

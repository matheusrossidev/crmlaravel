@extends('tenant.layouts.app')
@php $title = 'Portal do Parceiro'; $pageIcon = 'building'; @endphp

@push('styles')
<style>
/* ── Dark hero section ── */
.page-container { padding: 0 !important; overflow-x: hidden; }
.ph-dark { background: linear-gradient(135deg, #0a0a1a 0%, #1a1040 40%, #2d1b69 70%, #0085f3 100%); border-radius: 0; padding: 24px 0 60px; margin: 0 0 24px; overflow: visible; position: relative; width: 100%; }
.ph-dark::before { content: ''; position: absolute; top: -50%; right: -20%; width: 60%; height: 200%; background: radial-gradient(circle, rgba(99,102,241,.12) 0%, transparent 65%); pointer-events: none; }
.ph-dark::after { content: ''; position: absolute; bottom: -30%; left: -10%; width: 40%; height: 130%; background: radial-gradient(circle, rgba(0,133,243,.08) 0%, transparent 60%); pointer-events: none; }

/* Rank section inside dark */
.ph-rank-section { padding: 0 36px 12px; position: relative; z-index: 1; display: flex; align-items: center; gap: 20px; flex-wrap: wrap; }
.ph-rank-img { width: 150px; height: 150px; border-radius: 0; object-fit: contain; border: none; flex-shrink: 0; }
@media (max-width: 480px) { .ph-rank-img { width: 80px; height: 80px; } }
.ph-rank-ph { width: 80px; height: 80px; border-radius: 18px; display: flex; align-items: center; justify-content: center; font-size: 36px; border: 3px solid rgba(255,255,255,.15); flex-shrink: 0; }
.ph-rank-info { flex: 1; min-width: 200px; }
.ph-rank-level { display: inline-block; font-size: 10px; font-weight: 700; padding: 2px 10px; border-radius: 99px; margin-bottom: 6px; letter-spacing: .06em; text-transform: uppercase; }
.ph-rank-title { font-size: 18px; font-weight: 700; color: #fff; margin-bottom: 6px; }
.ph-rank-progress { margin-top: 8px; }
.ph-rank-dots { display: flex; gap: 4px; margin-bottom: 6px; }
.ph-rank-dot { width: 12px; height: 4px; border-radius: 2px; }
.ph-rank-dot.filled { background: #fff; }
.ph-rank-dot.empty { background: rgba(255,255,255,.2); }
.ph-rank-until { font-size: 12px; color: rgba(255,255,255,.5); }
.ph-rank-until strong { color: rgba(255,255,255,.8); }
.ph-link-btn { padding: 10px 18px; background: rgba(255,255,255,.1); color: rgba(255,255,255,.8); border: 1px solid rgba(255,255,255,.15); border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; backdrop-filter: blur(4px); transition: background .15s; display: inline-flex; align-items: center; gap: 6px; flex-shrink: 0; }
.ph-link-btn:hover { background: rgba(255,255,255,.18); color: #fff; }

/* Link box */
.ph-link-box { margin: 0 36px 16px; padding: 10px 14px; background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.1); border-radius: 10px; display: none; position: relative; z-index: 1; }
.ph-link-box.show { display: flex; align-items: center; gap: 8px; }
.ph-link-box code { flex: 1; font-size: 12px; color: rgba(255,255,255,.7); word-break: break-all; }
.ph-link-copy { padding: 5px 14px; background: #fff; color: #0085f3; border: none; border-radius: 6px; font-size: 11px; font-weight: 700; cursor: pointer; }

/* KPI cards inside dark — vazados pra baixo */
.ph-kpi-row { display: flex; gap: 14px; padding: 0 36px; position: relative; z-index: 2; overflow-x: auto; -webkit-overflow-scrolling: touch; scrollbar-width: none; margin-bottom: -150px; }
.ph-kpi-row::-webkit-scrollbar { display: none; }
.ph-kpi-dark { background: rgba(15,15,35,.9); border: 1px solid rgba(255,255,255,.1); border-radius: 16px; padding: 20px 22px; min-width: 170px; flex: 1; flex-shrink: 0; }
.ph-kpi-dark-icon { width: 38px; height: 38px; border-radius: 10px; background: rgba(0,133,243,.1); border: 1.5px solid #0085f3; outline: 2px solid rgba(0,133,243,.25); outline-offset: 2px; display: flex; align-items: center; justify-content: center; font-size: 16px; color: #0085f3; margin-bottom: 12px; filter: drop-shadow(0 0 6px rgba(0,133,243,.3)); }
.ph-kpi-dark-val { font-size: 22px; font-weight: 800; color: #fff !important; }
.ph-kpi-dark-label { font-size: 12px; color: rgba(255,255,255,.45); margin-top: 2px; }
.ph-kpi-dark .sacar-btn { display: inline-block; margin-top: 6px; padding: 3px 10px; background: #10B981; color: #fff; border: none; border-radius: 6px; font-size: 10px; font-weight: 700; cursor: pointer; }

/* ── Grid below dark ── */
.ph-grid { display: grid; grid-template-columns: 65% 1fr; gap: 16px; }
@media (max-width: 768px) { .ph-grid { grid-template-columns: 1fr; } }
.ph-card { background: #fff; border: 1.5px solid #e8eaf0; border-radius: 14px; overflow: hidden; }
.ph-card-header { padding: 16px 20px; border-bottom: 1px solid #f0f2f7; display: flex; align-items: center; justify-content: space-between; }
.ph-card-header h3 { font-size: 13.5px; font-weight: 600; color: #1a1d23; margin: 0; display: flex; align-items: center; gap: 6px; }
.ph-card-header h3 i { color: #0085f3; }
.ph-card-body { padding: 18px 20px; }

/* ── Client list ── */
.ph-client { display: flex; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px solid #f3f4f6; }
.ph-client:last-child { border-bottom: none; }
.ph-client-av { width: 32px; height: 32px; border-radius: 50%; background: #eff6ff; color: #0085f3; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; flex-shrink: 0; }
.ph-client-info { flex: 1; min-width: 0; }
.ph-client-name { font-size: 13px; font-weight: 600; color: #1a1d23; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ph-client-meta { font-size: 11px; color: #97A3B7; }
.ph-client-badge { font-size: 10px; font-weight: 600; padding: 2px 8px; border-radius: 99px; flex-shrink: 0; }
.ph-client-badge.active { background: #ecfdf5; color: #065f46; }
.ph-client-badge.trial { background: #eff6ff; color: #1e40af; }
.ph-client-badge.other { background: #f3f4f6; color: #6b7280; }
</style>
@endpush

@section('content')
<div class="page-container">

    {{-- ══ DARK HERO SECTION ══ --}}
    <div class="ph-dark">
        <div style="padding:0 36px 8px;position:relative;z-index:1;">
            <h1 style="font-family:'Plus Jakarta Sans',sans-serif;font-size:22px;font-weight:800;color:#fff;margin:0;">Programa de Parceiros</h1>
        </div>

        {{-- Rank --}}
        <div class="ph-rank-section">
            @if($currentRank?->image_path)
                <img src="{{ asset('storage/' . $currentRank->image_path) }}" class="ph-rank-img">
            @else
                <i class="bi bi-trophy-fill" style="font-size:56px;color:{{ $currentRank?->color ?? '#f59e0b' }};flex-shrink:0;"></i>
            @endif

            <div class="ph-rank-info">
                @if($currentRank)
                    <div class="ph-rank-level" style="background:{{ $currentRank->color }}40;color:{{ $currentRank->color }};">Nível {{ $currentRank->sort_order }}</div>
                @endif
                <div class="ph-rank-title">
                    @if($currentRank)
                        Sua empresa está no nível <strong>{{ $currentRank->name }}</strong>
                    @else
                        Programa de Parceiros
                    @endif
                </div>

                @if($nextRank)
                    @php
                        $pct = $nextRank->min_sales > 0 ? min(round(($activeClients / $nextRank->min_sales) * 100), 100) : 0;
                        $totalDots = 10;
                        $filledDots = (int) round($pct / 10);
                    @endphp
                    <div class="ph-rank-progress">
                        <div class="ph-rank-dots">
                            @for($i = 0; $i < $totalDots; $i++)
                                <div class="ph-rank-dot {{ $i < $filledDots ? 'filled' : 'empty' }}"></div>
                            @endfor
                        </div>
                        <div class="ph-rank-until">
                            <strong>{{ max($nextRank->min_sales - $activeClients, 0) }} vendas</strong> até o próximo nível
                        </div>
                    </div>
                @elseif($currentRank)
                    <div style="font-size:12px;color:#10B981;margin-top:6px;"><i class="bi bi-check-circle-fill"></i> Nível máximo atingido!</div>
                @endif
            </div>

            @if($agencyCode)
                <button class="ph-link-btn" onclick="navigator.clipboard.writeText('{{ url('/register?agency=' . $agencyCode->code) }}');toastr.success('Link copiado!');">
                    <i class="bi bi-link-45deg"></i> Seu link de indicação
                </button>
            @endif
        </div>

        {{-- KPI Cards (dark glass) --}}
        <div class="ph-kpi-row">
            <div class="ph-kpi-dark">
                <div class="ph-kpi-dark-icon"><i class="bi bi-people"></i></div>
                <div class="ph-kpi-dark-val">{{ $activeClients }}</div>
                <div class="ph-kpi-dark-label">Contas ativas</div>
            </div>
            <div class="ph-kpi-dark">
                <div class="ph-kpi-dark-icon"><i class="bi bi-currency-dollar"></i></div>
                <div class="ph-kpi-dark-val">R$ {{ number_format($totalCommission, 2, ',', '.') }}</div>
                <div class="ph-kpi-dark-label">Comissão total</div>
            </div>
            <div class="ph-kpi-dark">
                <div class="ph-kpi-dark-icon"><i class="bi bi-box-arrow-up"></i></div>
                <div class="ph-kpi-dark-val">R$ {{ number_format($totalWithdrawn, 2, ',', '.') }}</div>
                <div class="ph-kpi-dark-label">Saques realizados</div>
            </div>
            <div class="ph-kpi-dark">
                <div class="ph-kpi-dark-icon"><i class="bi bi-hourglass-split"></i></div>
                <div class="ph-kpi-dark-val">R$ {{ number_format(max($totalCommission - $totalWithdrawn - $availableBalance, 0), 2, ',', '.') }}</div>
                <div class="ph-kpi-dark-label">Saldo a liberar</div>
            </div>
            <div class="ph-kpi-dark">
                <div class="ph-kpi-dark-icon" style="background:rgba(16,185,129,.15);color:#10B981;"><i class="bi bi-wallet2"></i></div>
                <div class="ph-kpi-dark-val">R$ {{ number_format($availableBalance, 2, ',', '.') }}</div>
                <div class="ph-kpi-dark-label">Saldo disponível</div>
                <button class="sacar-btn" onclick="document.getElementById('wdOverlay').style.display='flex';document.getElementById('wdDrawer').style.right='0';">Sacar</button>
            </div>
        </div>
    </div>

    {{-- ══ CHART + CLIENTS ══ --}}
    <div class="ph-grid" style="padding:0 24px;margin-top:120px;">
        <div class="ph-card">
            <div class="ph-card-header"><h3><i class="bi bi-graph-up"></i> Evolução</h3></div>
            <div class="ph-card-body"><canvas id="evoChart" height="300" style="max-height:300px;"></canvas></div>
        </div>
        <div class="ph-card">
            <div class="ph-card-header">
                <h3><i class="bi bi-people"></i> Clientes indicados</h3>
                <span style="font-size:12px;color:#97A3B7;">{{ $clients->count() }} total</span>
            </div>
            <div class="ph-card-body" style="max-height:380px;overflow-y:auto;padding-top:8px;">
                @forelse($clients as $client)
                    @php
                        $initials = strtoupper(mb_substr($client->name, 0, 2));
                        $badgeClass = in_array($client->status, ['active', 'partner']) ? 'active' : ($client->status === 'trial' ? 'trial' : 'other');
                        $statusLabel = match($client->status) { 'active', 'partner' => 'Ativo', 'trial' => 'Trial', 'suspended' => 'Suspenso', default => ucfirst($client->status) };
                        $planLabel = match($client->plan) { 'starter' => 'Starter', 'pro' => 'Pro', 'enterprise' => 'Enterprise', default => ucfirst($client->plan ?? 'Free') };
                    @endphp
                    <div class="ph-client">
                        <div class="ph-client-av">{{ $initials }}</div>
                        <div class="ph-client-info">
                            <div class="ph-client-name">{{ $client->name }}</div>
                            <div class="ph-client-meta">{{ $planLabel }} · {{ $client->created_at->format('d/m/Y') }}</div>
                        </div>
                        <span class="ph-client-badge {{ $badgeClass }}">{{ $statusLabel }}</span>
                    </div>
                @empty
                    <div style="text-align:center;padding:30px;color:#97A3B7;">
                        <i class="bi bi-people" style="font-size:24px;display:block;margin-bottom:6px;"></i>
                        <p style="font-size:13px;margin:0;">Nenhum cliente indicado ainda.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- Withdrawal Drawer --}}
<div id="wdOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.3);z-index:5000;align-items:center;justify-content:center;" onclick="if(event.target===this){this.style.display='none';document.getElementById('wdDrawer').style.right='-520px';}"></div>
<div id="wdDrawer" style="position:fixed;top:0;right:-520px;width:440px;height:100vh;background:#fff;z-index:5001;box-shadow:-4px 0 24px rgba(0,0,0,.1);display:flex;flex-direction:column;transition:right .25s cubic-bezier(.4,0,.2,1);">
    <div style="padding:18px 24px;border-bottom:1px solid #f0f2f7;display:flex;align-items:center;justify-content:space-between;">
        <h3 style="margin:0;font-size:16px;font-weight:700;color:#1a1d23;">Solicitar Saque</h3>
        <button onclick="document.getElementById('wdOverlay').style.display='none';document.getElementById('wdDrawer').style.right='-520px';" style="background:none;border:none;font-size:18px;color:#9ca3af;cursor:pointer;"><i class="bi bi-x-lg"></i></button>
    </div>
    <div style="flex:1;overflow-y:auto;padding:24px;">
        <div style="background:#ecfdf5;border:1px solid #d1fae5;border-radius:12px;padding:14px 18px;margin-bottom:20px;">
            <div style="font-size:12px;color:#065f46;font-weight:600;">Saldo disponível</div>
            <div style="font-size:22px;font-weight:800;color:#059669;">R$ {{ number_format($availableBalance, 2, ',', '.') }}</div>
        </div>

        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:12.5px;font-weight:600;color:#374151;margin-bottom:6px;">Valor do saque (R$) *</label>
            <input type="number" id="wdAmount" min="50" step="0.01" placeholder="Mínimo R$ 50,00" style="width:100%;padding:9px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;box-sizing:border-box;">
            <div style="font-size:11px;color:#9ca3af;margin-top:4px;">Mínimo: R$ 50,00</div>
        </div>

        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:12.5px;font-weight:600;color:#374151;margin-bottom:6px;">Tipo de chave PIX *</label>
            <select id="wdPixType" style="width:100%;padding:9px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;box-sizing:border-box;background:#fff;">
                <option value="CPF">CPF</option>
                <option value="CNPJ">CNPJ</option>
                <option value="EMAIL">E-mail</option>
                <option value="PHONE">Telefone</option>
                <option value="EVP">Chave aleatória</option>
            </select>
        </div>

        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:12.5px;font-weight:600;color:#374151;margin-bottom:6px;">Chave PIX *</label>
            <input type="text" id="wdPixKey" placeholder="Digite sua chave PIX" style="width:100%;padding:9px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;box-sizing:border-box;">
        </div>

        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:12.5px;font-weight:600;color:#374151;margin-bottom:6px;">Nome do titular *</label>
            <input type="text" id="wdName" placeholder="Nome completo" style="width:100%;padding:9px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;box-sizing:border-box;">
        </div>

        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:12.5px;font-weight:600;color:#374151;margin-bottom:6px;">CPF/CNPJ do titular *</label>
            <input type="text" id="wdCpf" placeholder="000.000.000-00" style="width:100%;padding:9px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;box-sizing:border-box;">
        </div>
    </div>
    <div style="padding:16px 24px;border-top:1px solid #f0f2f7;display:flex;gap:10px;justify-content:flex-end;">
        <button onclick="document.getElementById('wdOverlay').style.display='none';document.getElementById('wdDrawer').style.right='-520px';" style="padding:9px 18px;background:#f3f4f6;border:1px solid #d1d5db;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;">Cancelar</button>
        <button id="btnWithdraw" onclick="submitWithdrawal()" style="padding:9px 18px;background:#0085f3;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;"><i class="bi bi-wallet2"></i> Solicitar Saque</button>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

async function submitWithdrawal() {
    const btn = document.getElementById('btnWithdraw');
    const amount = parseFloat(document.getElementById('wdAmount').value);
    if (!amount || amount < 50) { toastr.error('Valor mínimo: R$ 50,00'); return; }
    const pixKey = document.getElementById('wdPixKey').value.trim();
    const pixType = document.getElementById('wdPixType').value;
    const name = document.getElementById('wdName').value.trim();
    const cpf = document.getElementById('wdCpf').value.trim();
    if (!pixKey || !name || !cpf) { toastr.error('Preencha todos os campos'); return; }

    btn.disabled = true;
    try {
        const r = await fetch('{{ route("partner.withdrawal.store") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' },
            body: JSON.stringify({ amount, pix_key: pixKey, pix_key_type: pixType, pix_holder_name: name, pix_holder_cpf_cnpj: cpf }),
        });
        const d = await r.json();
        if (d.success) {
            toastr.success('Saque solicitado! Aguarde a aprovação.');
            document.getElementById('wdOverlay').style.display = 'none';
            document.getElementById('wdDrawer').style.right = '-520px';
            setTimeout(() => location.reload(), 1000);
        } else {
            toastr.error(d.message || 'Erro ao solicitar saque');
        }
    } catch { toastr.error('Erro de conexão'); }
    btn.disabled = false;
}

const chartData = @json($chartData);
new Chart(document.getElementById('evoChart').getContext('2d'), {
    type: 'line',
    data: {
        labels: chartData.map(d => d.label),
        datasets: [
            { label: 'Indicações', data: chartData.map(d => d.clients), borderColor: '#0085f3', backgroundColor: 'rgba(0,133,243,.06)', fill: true, tension: .3, pointRadius: 5, pointBackgroundColor: '#0085f3', yAxisID: 'y' },
            { label: 'Comissão (R$)', data: chartData.map(d => d.commission), borderColor: '#8B5CF6', backgroundColor: 'rgba(139,92,246,.06)', fill: true, tension: .3, pointRadius: 5, pointBackgroundColor: '#8B5CF6', yAxisID: 'y1' },
        ],
    },
    options: {
        responsive: true,
        interaction: { mode: 'index', intersect: false },
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } },
        scales: {
            y: { beginAtZero: true, position: 'left', title: { display: true, text: 'Indicações', font: { size: 11 } }, grid: { color: '#f3f4f6' } },
            y1: { beginAtZero: true, position: 'right', title: { display: true, text: 'Comissão R$', font: { size: 11 } }, grid: { display: false } },
            x: { grid: { display: false } },
        },
    },
});
</script>
@endpush

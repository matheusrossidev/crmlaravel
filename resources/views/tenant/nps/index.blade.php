@extends('tenant.layouts.app')
@php $title = 'NPS'; $pageIcon = 'emoji-smile'; @endphp

@push('styles')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
    .nps-big { font-size: 48px; font-weight: 800; line-height: 1; }
    .nps-big.positive { color: #10B981; }
    .nps-big.negative { color: #EF4444; }
    .nps-big.neutral  { color: #F59E0B; }
    .nps-bar { height: 8px; border-radius: 99px; overflow: hidden; display: flex; margin-top: 8px; }
    .nps-bar-p { background: #10B981; }
    .nps-bar-n { background: #F59E0B; }
    .nps-bar-d { background: #EF4444; }
    .comment-item { padding: 14px 0; border-bottom: 1px solid #f7f8fa; }
    .comment-item:last-child { border-bottom: none; }
    .score-dot { width: 28px; height: 28px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; color: #fff; flex-shrink: 0; }
    .score-dot.promoter { background: #10B981; }
    .score-dot.passive { background: #F59E0B; }
    .score-dot.detractor { background: #EF4444; }

    .content-card-header h3 {
        font-size: 13.5px;
        font-weight: 600;
        color: #1a1d23;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
        font-family: 'Plus Jakarta Sans', sans-serif;
    }
    .content-card-header h3 i { color: #007DFF; }

    .nps-kpi-grid {
        display: flex;
        gap: 14px;
        margin-bottom: 20px;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
        padding-bottom: 2px;
    }
    .nps-kpi-grid::-webkit-scrollbar { display: none; }
    .nps-kpi-card {
        background: #fff;
        border-radius: 14px;
        padding: 16px 18px;
        border: 1px solid #e8eaf0;
        min-width: 170px;
        flex: 1;
        flex-shrink: 0;
    }

    .page-drawer-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.3); z-index: 5000; }
    .page-drawer-overlay.open { display: block; }
    .page-drawer {
        position: fixed; top: 0; right: -460px; width: 440px; height: 100vh;
        background: #fff; z-index: 5001; box-shadow: -4px 0 24px rgba(0,0,0,.1);
        display: flex; flex-direction: column; transition: right .25s cubic-bezier(.4,0,.2,1);
    }
    .page-drawer.open { right: 0; }
    @media (max-width: 480px) { .page-drawer { width: 100%; right: -100%; } }
    .nps-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
    @media (max-width: 768px) { .nps-grid-2 { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="page-container">

    <div style="margin-bottom:20px;">
        <div style="font-size:11px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:#97A3B7;margin-bottom:4px;">NPS</div>
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
            <div>
                <h1 style="font-family:'Plus Jakarta Sans',sans-serif;font-size:22px;font-weight:700;color:#1a1d23;margin:0 0 4px;">{{ __('nps.title') }}</h1>
                <p style="font-size:13.5px;color:#677489;margin:0;">{{ __('nps.subtitle') }}</p>
            </div>
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                <form method="GET" style="display:flex;gap:6px;align-items:center;">
                    <input type="date" name="from" value="{{ $dateFrom }}" style="padding:7px 10px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:12px;outline:none;">
                    <input type="date" name="to" value="{{ $dateTo }}" style="padding:7px 10px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:12px;outline:none;">
                    <button type="submit" class="btn-outline-sm" style="padding:7px 12px;"><i class="bi bi-funnel"></i></button>
                </form>
                <button class="btn-primary-sm" onclick="openCreate()">
                    <i class="bi bi-plus-lg"></i> {{ __('nps.new_survey') }}
                </button>
            </div>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="nps-kpi-grid">
        @php
            $kpis = [
                ['icon' => 'speedometer2', 'color' => ($npsScore >= 50 ? 'green' : ($npsScore >= 0 ? 'orange' : 'red')), 'label' => __('nps.nps_score'), 'value' => ($npsScore > 0 ? '+' : '') . $npsScore, 'valueColor' => ($npsScore > 0 ? '#10B981' : ($npsScore < 0 ? '#EF4444' : '#F59E0B')), 'sub' => null],
                ['icon' => 'chat-square-text', 'color' => 'blue', 'label' => __('nps.responses'), 'value' => $totalAnswered, 'valueColor' => '#1a1d23', 'sub' => null],
                ['icon' => 'emoji-laughing', 'color' => 'green', 'label' => __('nps.promoters'), 'value' => ($totalAnswered > 0 ? round($promoters / $totalAnswered * 100) : 0) . '%', 'valueColor' => '#10B981', 'sub' => $promoters . ' ' . strtolower(__('nps.responses'))],
                ['icon' => 'emoji-frown', 'color' => 'red', 'label' => __('nps.detractors'), 'value' => ($totalAnswered > 0 ? round($detractors / $totalAnswered * 100) : 0) . '%', 'valueColor' => '#EF4444', 'sub' => $detractors . ' ' . strtolower(__('nps.responses'))],
                ['icon' => 'star', 'color' => 'purple', 'label' => __('nps.avg_score'), 'value' => number_format((float) $avgScore, 1, ',', '.'), 'valueColor' => '#1a1d23', 'sub' => null],
            ];
            $iconBgs = ['blue' => '#eff6ff', 'green' => '#f0fdf4', 'red' => '#fef2f2', 'orange' => '#fffbeb', 'purple' => '#f5f3ff'];
            $iconColors = ['blue' => '#007DFF', 'green' => '#10B981', 'red' => '#EF4444', 'orange' => '#F59E0B', 'purple' => '#8B5CF6'];
        @endphp
        @foreach($kpis as $k)
        <div class="nps-kpi-card">
            <div style="display:flex;align-items:center;gap:9px;margin-bottom:10px;">
                <div style="width:30px;height:30px;border-radius:8px;background:{{ $iconBgs[$k['color']] }};color:{{ $iconColors[$k['color']] }};display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0;">
                    <i class="bi bi-{{ $k['icon'] }}"></i>
                </div>
                <span style="font-size:12px;color:#97A3B7;font-weight:500;">{{ $k['label'] }}</span>
            </div>
            <div style="font-size:22px;font-weight:700;color:{{ $k['valueColor'] }};line-height:1;">{{ $k['value'] }}</div>
            @if($k['sub'])
                <div style="font-size:11px;color:#97A3B7;margin-top:4px;">{{ $k['sub'] }}</div>
            @endif
        </div>
        @endforeach
    </div>

    {{-- NPS Bar --}}
    @if($totalAnswered > 0)
    <div class="content-card" style="margin-bottom:20px;padding:16px 20px;">
        <div style="display:flex;justify-content:space-between;font-size:12px;font-weight:600;margin-bottom:4px;">
            <span style="color:#10B981;">{{ __('nps.promoters') }} {{ round($promoters/$totalAnswered*100) }}%</span>
            <span style="color:#F59E0B;">{{ __('nps.neutrals') }} {{ round($passives/$totalAnswered*100) }}%</span>
            <span style="color:#EF4444;">{{ __('nps.detractors') }} {{ round($detractors/$totalAnswered*100) }}%</span>
        </div>
        <div class="nps-bar">
            <div class="nps-bar-p" style="width:{{ $promoters/$totalAnswered*100 }}%;"></div>
            <div class="nps-bar-n" style="width:{{ $passives/$totalAnswered*100 }}%;"></div>
            <div class="nps-bar-d" style="width:{{ $detractors/$totalAnswered*100 }}%;"></div>
        </div>
    </div>
    @endif

    <div class="nps-grid-2">
        {{-- Monthly trend --}}
        <div class="content-card">
            <div class="content-card-header"><h3><i class="bi bi-graph-up"></i> {{ __('nps.nps_evolution') }}</h3></div>
            <div style="padding:16px 20px;"><canvas id="npsChart" height="200"></canvas></div>
        </div>
        {{-- Distribution --}}
        <div class="content-card">
            <div class="content-card-header"><h3><i class="bi bi-bar-chart"></i> {{ __('nps.score_distribution') }}</h3></div>
            <div style="padding:16px 20px;"><canvas id="distChart" height="200"></canvas></div>
        </div>
    </div>

    {{-- By vendor + comments side by side --}}
    <div class="nps-grid-2">
        {{-- By vendor --}}
        <div class="content-card">
            <div class="content-card-header"><h3><i class="bi bi-people"></i> Por Vendedor</h3></div>
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:13px;">
                    <thead>
                        <tr>
                            <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:600;color:#677489;border-bottom:1px solid #f0f2f7;">Vendedor</th>
                            <th style="padding:10px 12px;text-align:center;font-size:11px;font-weight:600;color:#677489;border-bottom:1px solid #f0f2f7;">Respostas</th>
                            <th style="padding:10px 12px;text-align:center;font-size:11px;font-weight:600;color:#677489;border-bottom:1px solid #f0f2f7;">NPS</th>
                            <th style="padding:10px 12px;text-align:center;font-size:11px;font-weight:600;color:#677489;border-bottom:1px solid #f0f2f7;">Média</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($byVendor as $v)
                        @php $vNps = $v->total > 0 ? round(($v->promoters - $v->detractors) / $v->total * 100) : 0; @endphp
                        <tr>
                            <td style="padding:10px 16px;border-bottom:1px solid #f7f8fa;font-weight:600;">{{ $v->name }}</td>
                            <td style="padding:10px 12px;text-align:center;border-bottom:1px solid #f7f8fa;">{{ $v->total }}</td>
                            <td style="padding:10px 12px;text-align:center;border-bottom:1px solid #f7f8fa;font-weight:700;color:{{ $vNps > 0 ? '#10B981' : ($vNps < 0 ? '#EF4444' : '#F59E0B') }};">{{ $vNps > 0 ? '+' : '' }}{{ $vNps }}</td>
                            <td style="padding:10px 12px;text-align:center;border-bottom:1px solid #f7f8fa;">{{ $v->avg_score }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" style="padding:24px;text-align:center;color:#9ca3af;">Sem dados no período</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Recent comments --}}
        <div class="content-card">
            <div class="content-card-header"><h3><i class="bi bi-chat-quote"></i> Comentários Recentes</h3></div>
            <div style="padding:0 20px;">
                @forelse($recentComments as $c)
                <div class="comment-item" style="display:flex;gap:12px;align-items:flex-start;">
                    <span class="score-dot {{ $c->npsCategory() }}">{{ $c->score }}</span>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:13px;color:#374151;line-height:1.5;">{{ $c->comment }}</div>
                        <div style="font-size:11px;color:#9ca3af;margin-top:4px;">
                            {{ $c->lead?->name ?? 'Anônimo' }}
                            @if($c->user) · {{ $c->user->name }} @endif
                            · {{ $c->answered_at?->format('d/m/Y') }}
                        </div>
                    </div>
                </div>
                @empty
                <div style="padding:24px;text-align:center;color:#9ca3af;font-size:13px;">Nenhum comentário no período</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Surveys list --}}
    <div class="content-card">
        <div class="content-card-header">
            <h3><i class="bi bi-list-ul"></i> Pesquisas Configuradas</h3>
        </div>
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:13.5px;">
                <thead>
                    <tr>
                        <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:600;color:#677489;border-bottom:1px solid #f0f2f7;">Nome</th>
                        <th style="padding:10px 12px;text-align:left;font-size:11px;font-weight:600;color:#677489;border-bottom:1px solid #f0f2f7;">Trigger</th>
                        <th style="padding:10px 12px;text-align:center;font-size:11px;font-weight:600;color:#677489;border-bottom:1px solid #f0f2f7;">Respostas</th>
                        <th style="padding:10px 12px;text-align:center;font-size:11px;font-weight:600;color:#677489;border-bottom:1px solid #f0f2f7;">Link</th>
                        <th style="padding:10px 12px;text-align:center;font-size:11px;font-weight:600;color:#677489;border-bottom:1px solid #f0f2f7;">Status</th>
                        <th style="padding:10px 12px;border-bottom:1px solid #f0f2f7;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($surveys as $s)
                    <tr>
                        <td style="padding:10px 16px;border-bottom:1px solid #f7f8fa;">
                            <div style="font-weight:600;color:#1a1d23;">{{ $s->name }}</div>
                            <div style="font-size:12px;color:#9ca3af;">{{ \Illuminate\Support\Str::limit($s->question, 50) }}</div>
                        </td>
                        <td style="padding:10px 12px;border-bottom:1px solid #f7f8fa;">
                            @php $triggerLabels = ['lead_won' => 'Venda fechada', 'conversation_closed' => 'Conversa fechada', 'manual' => 'Manual']; @endphp
                            <span style="font-size:12px;font-weight:600;">{{ $triggerLabels[$s->trigger] ?? $s->trigger }}</span>
                            @if($s->delay_hours > 0)
                                <div style="font-size:11px;color:#9ca3af;">+{{ $s->delay_hours }}h de delay</div>
                            @endif
                        </td>
                        <td style="padding:10px 12px;text-align:center;border-bottom:1px solid #f7f8fa;font-weight:700;">{{ $s->answered_count }}/{{ $s->responses_count }}</td>
                        <td style="padding:10px 12px;text-align:center;border-bottom:1px solid #f7f8fa;">
                            <button onclick="copyLink('{{ url('/pesquisa/' . $s->slug) }}')" title="Copiar link"
                                style="background:#eff6ff;color:#0085f3;border:none;border-radius:6px;padding:5px 10px;cursor:pointer;font-size:12px;font-weight:600;">
                                <i class="bi bi-link-45deg"></i> Copiar
                            </button>
                        </td>
                        <td style="padding:10px 12px;text-align:center;border-bottom:1px solid #f7f8fa;">
                            @if($s->is_active)
                                <span style="color:#10B981;font-weight:600;font-size:12px;"><i class="bi bi-check-circle-fill"></i> Ativo</span>
                            @else
                                <span style="color:#9ca3af;font-size:12px;"><i class="bi bi-x-circle"></i> Inativo</span>
                            @endif
                        </td>
                        <td style="padding:10px 12px;border-bottom:1px solid #f7f8fa;text-align:right;">
                            <button onclick="deleteSurvey({{ $s->id }}, '{{ addslashes($s->name) }}')"
                                style="background:#fef2f2;color:#ef4444;border:none;border-radius:6px;padding:4px 8px;cursor:pointer;font-size:12px;">
                                <i class="bi bi-trash3"></i>
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Create Drawer --}}
<div class="page-drawer-overlay" id="createOverlay" onclick="closeCreate()"></div>
<div class="page-drawer" id="createDrawer">
    <div class="notif-drawer-header">
        <h3 style="margin:0;font-size:16px;font-weight:700;display:flex;align-items:center;gap:8px;">
            <i class="bi bi-emoji-smile" style="color:#0085f3;"></i> {{ __('nps.new_survey') }}
        </h3>
        <button onclick="closeCreate()" style="background:none;border:none;font-size:18px;color:#9ca3af;cursor:pointer;"><i class="bi bi-x-lg"></i></button>
    </div>
    <div style="flex:1;overflow-y:auto;padding:24px;">
        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:4px;">Nome *</label>
            <input type="text" id="sName" placeholder="Ex: NPS Pós-Venda" style="width:100%;padding:9px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;">
        </div>
        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:4px;">Pergunta principal *</label>
            <textarea id="sQuestion" rows="2" placeholder="De 0 a 10, quanto você recomendaria nossos serviços?" style="width:100%;padding:9px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;resize:vertical;font-family:inherit;"></textarea>
        </div>
        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:4px;">Pergunta de follow-up (opcional)</label>
            <input type="text" id="sFollowUp" placeholder="O que motivou sua nota?" style="width:100%;padding:9px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;">
        </div>
        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:4px;">Disparar quando</label>
            <select id="sTrigger" style="width:100%;padding:9px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;">
                <option value="manual">Manual (enviar link)</option>
                <option value="lead_won">Automático — Venda fechada</option>
                <option value="conversation_closed">Automático — Conversa fechada</option>
            </select>
        </div>
        <div style="margin-bottom:14px;" id="delayRow">
            <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:4px;">Delay (horas após trigger)</label>
            <input type="number" id="sDelay" value="0" min="0" max="168" style="width:100%;padding:9px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;">
        </div>
        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:4px;">Enviar via</label>
            <select id="sSendVia" style="width:100%;padding:9px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;">
                <option value="whatsapp">WhatsApp</option>
                <option value="link">Apenas link</option>
            </select>
        </div>
        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:4px;">Mensagem de agradecimento (opcional)</label>
            <input type="text" id="sThanks" placeholder="Obrigado pela sua opinião!" style="width:100%;padding:9px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;">
        </div>
    </div>
    <div style="padding:16px 24px;border-top:1px solid #f0f2f7;display:flex;gap:10px;justify-content:flex-end;">
        <button class="btn-outline-sm" onclick="closeCreate()">Cancelar</button>
        <button class="btn-primary-sm" id="btnCreateSurvey" onclick="createSurvey()"><i class="bi bi-check-lg"></i> Criar</button>
    </div>
</div>

@endsection

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

// Charts
new Chart(document.getElementById('npsChart'), {
    type: 'line',
    data: {
        labels: {!! json_encode(collect($monthlyNps)->pluck('label')) !!},
        datasets: [{
            label: 'NPS',
            data: {!! json_encode(collect($monthlyNps)->pluck('nps')) !!},
            borderColor: '#0085f3', backgroundColor: 'rgba(0,133,243,0.1)',
            borderWidth: 2, fill: true, tension: 0.4, pointRadius: 4,
            pointBackgroundColor: '#fff', pointBorderColor: '#0085f3', pointBorderWidth: 2,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { display: false }, ticks: { font: { size: 11 } } },
            y: { grid: { color: '#f3f4f6' }, ticks: { font: { size: 11 } }, border: { display: false } }
        }
    }
});

@php
    $distLabels = []; $distValues = []; $distColors = [];
    for ($i = 0; $i <= 10; $i++) {
        $distLabels[] = (string) $i;
        $distValues[] = $distribution[$i] ?? 0;
        $distColors[] = $i <= 6 ? '#EF4444' : ($i <= 8 ? '#F59E0B' : '#10B981');
    }
@endphp

new Chart(document.getElementById('distChart'), {
    type: 'bar',
    data: {
        labels: {!! json_encode($distLabels) !!},
        datasets: [{ data: {!! json_encode($distValues) !!}, backgroundColor: {!! json_encode($distColors) !!}, borderRadius: 4 }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { display: false }, ticks: { font: { size: 11 } } },
            y: { beginAtZero: true, grid: { color: '#f3f4f6' }, ticks: { stepSize: 1, font: { size: 11 } }, border: { display: false } }
        }
    }
});

function copyLink(url) {
    navigator.clipboard.writeText(url).then(() => toastr.success('Link copiado!'));
}

function openCreate() {
    document.getElementById('createOverlay').classList.add('open');
    document.getElementById('createDrawer').classList.add('open');
}

function closeCreate() {
    document.getElementById('createOverlay').classList.remove('open');
    document.getElementById('createDrawer').classList.remove('open');
}

async function createSurvey() {
    const name = document.getElementById('sName').value.trim();
    const question = document.getElementById('sQuestion').value.trim();
    if (!name || !question) { toastr.error('Preencha nome e pergunta'); return; }

    document.getElementById('btnCreateSurvey').disabled = true;
    const res = await fetch('{{ route("nps.store") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' },
        body: JSON.stringify({
            name, type: 'nps', question,
            follow_up_question: document.getElementById('sFollowUp').value.trim() || null,
            trigger: document.getElementById('sTrigger').value,
            delay_hours: parseInt(document.getElementById('sDelay').value) || 0,
            send_via: document.getElementById('sSendVia').value,
            thank_you_message: document.getElementById('sThanks').value.trim() || null,
        }),
    });
    const data = await res.json();
    if (data.success) { toastr.success('Pesquisa criada!'); setTimeout(() => location.reload(), 800); }
    else { toastr.error(data.message || 'Erro'); document.getElementById('btnCreateSurvey').disabled = false; }
}

function deleteSurvey(id, name) {
    if (!confirm('Excluir "' + name + '"?')) return;
    fetch('{{ route("nps.destroy", "__ID__") }}'.replace('__ID__', id), {
        method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' },
    }).then(r => r.json()).then(d => { if (d.success) location.reload(); });
}
</script>
@endpush

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Relatório de Desempenho — Syncro CRM</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Helvetica', 'Arial', sans-serif; color: #1a1d23; background: #fff; font-size: 11px; }
    .report { padding: 28px 32px; }

    /* Header */
    .report-header { display: flex; align-items: center; justify-content: space-between; padding-bottom: 16px; border-bottom: 2px solid #e8eaf0; margin-bottom: 20px; }
    .report-header td { vertical-align: middle; }
    .report-logo img { height: 32px; }
    .report-title { font-size: 14px; font-weight: 700; color: #1a1d23; text-align: right; }
    .report-period { font-size: 10px; color: #6b7280; text-align: right; }
    .report-filters { font-size: 9px; color: #9ca3af; text-align: right; }

    /* Section */
    .section { margin-bottom: 20px; page-break-inside: avoid; }
    .section-title { font-size: 11px; font-weight: 700; color: #374151; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 8px; padding-bottom: 4px; border-bottom: 1px solid #f0f2f7; }

    /* KPI */
    .kpi-table { width: 100%; margin-bottom: 20px; }
    .kpi-table td { width: 25%; border: 1.5px solid #e8eaf0; border-radius: 8px; padding: 12px 14px; vertical-align: top; }
    .kpi-label { font-size: 9px; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 4px; }
    .kpi-value { font-size: 20px; font-weight: 800; color: #1a1d23; }
    .kpi-value-green { font-size: 20px; font-weight: 800; color: #16a34a; }
    .kpi-value-blue { font-size: 20px; font-weight: 800; color: #0085f3; }
    .kpi-delta { font-size: 10px; font-weight: 600; margin-top: 3px; }
    .kpi-delta-up { color: #16a34a; }
    .kpi-delta-down { color: #ef4444; }
    .kpi-delta-neutral { color: #9ca3af; }
    .kpi-sub { font-size: 10px; color: #6b7280; margin-top: 3px; }

    /* Tables */
    table.data { width: 100%; border-collapse: collapse; font-size: 10.5px; }
    table.data thead th { background: #f8fafc; color: #6b7280; font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; padding: 6px 8px; text-align: left; border-bottom: 1px solid #e8eaf0; }
    table.data thead th.right { text-align: right; }
    table.data tbody td { padding: 7px 8px; border-bottom: 1px solid #f3f4f6; color: #374151; }
    table.data tbody td.right { text-align: right; font-variant-numeric: tabular-nums; }
    table.data tbody td.bold { font-weight: 700; color: #1a1d23; }
    table.data tbody td.green { color: #16a34a; font-weight: 700; }
    table.data tbody td.blue { color: #0085f3; font-weight: 700; }
    table.data tbody tr:last-child td { border-bottom: none; }

    .badge { display: inline-block; padding: 1px 6px; border-radius: 100px; font-size: 9px; font-weight: 700; }
    .badge-high { background: #d1fae5; color: #065f46; }
    .badge-mid { background: #fef3c7; color: #92400e; }
    .badge-low { background: #f3f4f6; color: #6b7280; }

    /* Funnel */
    .funnel-table { width: 100%; margin-bottom: 0; }
    .funnel-table td { width: 25%; text-align: center; padding: 12px 8px; border-radius: 8px; }
    .funnel-count { font-size: 18px; font-weight: 800; }
    .funnel-label { font-size: 8px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; margin-top: 2px; }
    .funnel-pct { font-size: 9px; color: #6b7280; }
    .f-total { background: #eff6ff; color: #1e40af; }
    .f-open { background: #fffbeb; color: #92400e; }
    .f-won { background: #f0fdf4; color: #166534; }
    .f-lost { background: #fef2f2; color: #991b1b; }

    /* Reason bars */
    .reason-label { display: inline-block; width: 120px; font-size: 10px; color: #374151; }
    .reason-bar-bg { display: inline-block; width: 200px; height: 12px; background: #f3f4f6; border-radius: 3px; vertical-align: middle; overflow: hidden; }
    .reason-bar { height: 100%; background: #ef4444; border-radius: 3px; }
    .reason-pct { font-size: 9px; color: #6b7280; margin-left: 6px; }

    /* WA KPIs */
    .wa-kpi-table td { width: 25%; border: 1px solid #e8eaf0; border-radius: 6px; padding: 10px 12px; }
    .wa-val { font-size: 16px; font-weight: 800; color: #1a1d23; }
    .wa-label { font-size: 9px; color: #6b7280; margin-top: 2px; }

    /* Footer */
    .report-footer { margin-top: 24px; padding-top: 10px; border-top: 1px solid #e8eaf0; font-size: 9px; color: #9ca3af; }
    .report-footer table { width: 100%; }
    .report-footer td { padding: 0; }

    .dot { display: inline-block; width: 7px; height: 7px; border-radius: 50%; margin-right: 4px; }
</style>
</head>
<body>
<div class="report">

    {{-- Header --}}
    <table style="width:100%;margin-bottom:20px;border-bottom:2px solid #e8eaf0;padding-bottom:14px;">
        <tr>
            <td style="vertical-align:middle;">
                <img src="{{ public_path('images/logo.png') }}" style="height:32px;" alt="Syncro">
            </td>
            <td style="text-align:right;vertical-align:middle;">
                <div class="report-title">Relatório de Desempenho</div>
                <div class="report-period">{{ $dateFrom->format('d/m/Y') }} — {{ $dateTo->format('d/m/Y') }}</div>
                <div class="report-filters">
                    @if($filterPipeline) Pipeline: {{ $pipelines->firstWhere('id', $filterPipeline)?->name ?? 'N/A' }} @else Pipeline: Todos @endif
                    &bull;
                    @if($filterCampaign) Campanha: {{ $campaigns->firstWhere('id', $filterCampaign)?->name ?? 'N/A' }} @else Campanha: Todas @endif
                </div>
            </td>
        </tr>
    </table>

    {{-- KPIs --}}
    <table class="kpi-table">
        <tr>
            <td>
                <div class="kpi-label">Leads</div>
                <div class="kpi-value">{{ number_format($totalLeads, 0, ',', '.') }}</div>
                <div class="kpi-delta {{ $deltaLeads === null ? 'kpi-delta-neutral' : ($deltaLeads >= 0 ? 'kpi-delta-up' : 'kpi-delta-down') }}">
                    @if($deltaLeads !== null) {{ $deltaLeads >= 0 ? '+' : '-' }}{{ number_format(abs($deltaLeads), 1, ',', '.') }}% vs anterior @else Sem dados anteriores @endif
                </div>
            </td>
            <td>
                <div class="kpi-label">Receita</div>
                <div class="kpi-value-green">{{ __('common.currency') }} {{ number_format($totalRevenue, 0, __('common.decimal_sep'), __('common.thousands_sep')) }}</div>
                <div class="kpi-delta {{ $deltaRevenue === null ? 'kpi-delta-neutral' : ($deltaRevenue >= 0 ? 'kpi-delta-up' : 'kpi-delta-down') }}">
                    @if($deltaRevenue !== null) {{ $deltaRevenue >= 0 ? '+' : '-' }}{{ number_format(abs($deltaRevenue), 1, ',', '.') }}% vs anterior @else Sem dados anteriores @endif
                </div>
            </td>
            <td>
                <div class="kpi-label">Ticket Médio</div>
                <div class="kpi-value-green">{{ __('common.currency') }} {{ number_format($avgTicket, 0, __('common.decimal_sep'), __('common.thousands_sep')) }}</div>
                <div class="kpi-sub">{{ $salesCount }} venda(s) no período</div>
            </td>
            <td>
                <div class="kpi-label">Taxa de Conversão</div>
                <div class="kpi-value-blue">{{ number_format($convRate, 1, ',', '.') }}%</div>
                <div class="kpi-sub">Leads → Vendas</div>
            </td>
        </tr>
    </table>

    {{-- Funil --}}
    <div class="section">
        <div class="section-title">Funil de Conversão</div>
        <table class="funnel-table">
            <tr>
                <td class="f-total"><div class="funnel-count">{{ $totalLeads }}</div><div class="funnel-label">Total</div><div class="funnel-pct">100%</div></td>
                <td class="f-open"><div class="funnel-count">{{ $funnelEmAberto }}</div><div class="funnel-label">Em Aberto</div><div class="funnel-pct">{{ $totalLeads > 0 ? number_format($funnelEmAberto / $totalLeads * 100, 1, ',', '.') : 0 }}%</div></td>
                <td class="f-won"><div class="funnel-count">{{ $salesCount }}</div><div class="funnel-label">Ganhos</div><div class="funnel-pct">{{ $totalLeads > 0 ? number_format($salesCount / $totalLeads * 100, 1, ',', '.') : 0 }}%</div></td>
                <td class="f-lost"><div class="funnel-count">{{ $totalLost }}</div><div class="funnel-label">Perdidos</div><div class="funnel-pct">{{ $totalLeads > 0 ? number_format($totalLost / $totalLeads * 100, 1, ',', '.') : 0 }}%</div></td>
            </tr>
        </table>
    </div>

    {{-- Origem x Conversão --}}
    @if($sourceConversion->isNotEmpty())
    <div class="section">
        <div class="section-title">Origem × Conversão</div>
        <table class="data">
            <thead><tr><th>Origem</th><th class="right">Leads</th><th class="right">Vendas</th><th class="right">Conversão</th><th class="right">Receita</th></tr></thead>
            <tbody>
                @foreach($sourceConversion as $s)
                <tr>
                    <td class="bold">{{ $s['source'] }}</td>
                    <td class="right">{{ $s['leads'] }}</td>
                    <td class="right">{{ $s['vendas'] }}</td>
                    <td class="right"><span class="badge {{ $s['conv'] >= 25 ? 'badge-high' : ($s['conv'] >= 10 ? 'badge-mid' : 'badge-low') }}">{{ number_format($s['conv'], 1, ',', '.') }}%</span></td>
                    <td class="right green">{{ $s['receita'] > 0 ? __('common.currency') . ' ' . number_format($s['receita'], 0, __('common.decimal_sep'), __('common.thousands_sep')) : '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Campanhas --}}
    @if($campaignRows->isNotEmpty())
    <div class="section">
        <div class="section-title">Campanhas</div>
        <table class="data">
            <thead><tr><th>Campanha</th><th class="right">Leads</th><th class="right">Conv.</th><th class="right">Conv.%</th><th class="right">Receita</th></tr></thead>
            <tbody>
                @foreach($campaignRows as $c)
                <tr>
                    <td><strong>{{ $c['name'] }}</strong><br><span style="font-size:9px;color:#9ca3af;">{{ $c['source'] }}</span></td>
                    <td class="right blue">{{ $c['leads_count'] }}</td>
                    <td class="right">{{ $c['sales_count'] }}</td>
                    <td class="right"><span class="badge {{ $c['conv'] >= 25 ? 'badge-high' : ($c['conv'] >= 10 ? 'badge-mid' : 'badge-low') }}">{{ number_format($c['conv'], 1, ',', '.') }}%</span></td>
                    <td class="right green">{{ $c['revenue'] > 0 ? __('common.currency') . ' ' . number_format($c['revenue'], 0, __('common.decimal_sep'), __('common.thousands_sep')) : '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Pipeline --}}
    @foreach($pipelineRows as $pr)
    <div class="section">
        <div class="section-title">Pipeline — {{ $pr['pipeline']->name }}</div>
        <table class="data">
            <thead><tr><th>Etapa</th><th class="right">Leads</th><th class="right">% do Total</th></tr></thead>
            <tbody>
                @foreach($pr['stages'] as $s)
                <tr>
                    <td>
                        <span class="dot" style="background:{{ $s['stage']->color ?? '#6b7280' }};"></span>
                        <strong>{{ $s['stage']->name }}</strong>
                        @if($s['stage']->is_won) <span class="badge badge-high" style="font-size:8px;">GANHO</span> @endif
                        @if($s['stage']->is_lost) <span class="badge" style="background:#fef2f2;color:#991b1b;font-size:8px;">PERDIDO</span> @endif
                    </td>
                    <td class="right bold">{{ $s['count'] }}</td>
                    <td class="right">{{ $pr['total'] > 0 ? number_format($s['count'] / $pr['total'] * 100, 1, ',', '.') : 0 }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endforeach

    {{-- Perdidos --}}
    @if($totalLost > 0)
    <div class="section">
        <div class="section-title">Motivos de Perda — {{ $totalLost }} leads ({{ __('common.currency') }} {{ number_format($lostPotentialValue, 0, __('common.decimal_sep'), __('common.thousands_sep')) }} potencial)</div>
        @foreach($lostByReason as $r)
        <div style="margin-bottom:5px;">
            <span class="reason-label">{{ $r['reason'] }}</span>
            <span class="reason-bar-bg"><span class="reason-bar" style="width:{{ $r['pct'] }}%;"></span></span>
            <span class="reason-pct">{{ number_format($r['pct'], 0) }}%</span>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Vendedores --}}
    @if($vendedores->isNotEmpty())
    <div class="section">
        <div class="section-title">Desempenho por Vendedor</div>
        <table class="data">
            <thead><tr><th>Vendedor</th><th class="right">Leads</th><th class="right">Vendas</th><th class="right">Conversão</th><th class="right">Receita</th></tr></thead>
            <tbody>
                @foreach($vendedores as $v)
                <tr>
                    <td class="bold">{{ $v['user']->name }}</td>
                    <td class="right">{{ $v['leads'] }}</td>
                    <td class="right">{{ $v['vendas'] }}</td>
                    <td class="right"><span class="badge {{ $v['conv'] >= 25 ? 'badge-high' : ($v['conv'] >= 10 ? 'badge-mid' : 'badge-low') }}">{{ number_format($v['conv'], 1, ',', '.') }}%</span></td>
                    <td class="right green">{{ $v['receita'] > 0 ? __('common.currency') . ' ' . number_format($v['receita'], 0, __('common.decimal_sep'), __('common.thousands_sep')) : '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- WhatsApp --}}
    @if(($waTotal ?? 0) > 0)
    <div class="section">
        <div class="section-title">WhatsApp — Atendimento</div>
        <table class="wa-kpi-table" style="width:100%;">
            <tr>
                <td><div class="wa-val">{{ $waTotal }}</div><div class="wa-label">Conversas iniciadas</div></td>
                <td><div class="wa-val">{{ $waFechadas }}</div><div class="wa-label">Fechadas ({{ $waTotal > 0 ? round($waFechadas / $waTotal * 100) : 0 }}%)</div></td>
                <td><div class="wa-val">{{ $waComLead }}</div><div class="wa-label">Viraram lead ({{ $waTotal > 0 ? round($waComLead / $waTotal * 100) : 0 }}%)</div></td>
                <td><div class="wa-val">{{ $waIA }}</div><div class="wa-label">Atendidas por IA ({{ $waTotal > 0 ? round($waIA / $waTotal * 100) : 0 }}%)</div></td>
            </tr>
        </table>
    </div>
    @endif

    {{-- Atividade --}}
    @if($teamActivity->isNotEmpty())
    <div class="section">
        <div class="section-title">Atividade da Equipe</div>
        <table class="data">
            <thead><tr><th>Usuário</th><th class="right">Msgs WhatsApp</th><th class="right">Eventos CRM</th><th class="right">Total</th></tr></thead>
            <tbody>
                @foreach($teamActivity as $t)
                <tr>
                    <td class="bold">{{ $t['user']->name }}</td>
                    <td class="right">{{ number_format($t['msgs'], 0, ',', '.') }}</td>
                    <td class="right">{{ number_format($t['events'], 0, ',', '.') }}</td>
                    <td class="right blue">{{ number_format($t['total'], 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Footer --}}
    <div class="report-footer">
        <table><tr>
            <td>Gerado em {{ now()->format('d/m/Y H:i') }} — Syncro CRM</td>
            <td style="text-align:right;">{{ auth()->user()->name }} &bull; {{ auth()->user()->tenant->name ?? '' }}</td>
        </tr></table>
    </div>

</div>
</body>
</html>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Relatório de Campanhas — Syncro CRM</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Helvetica', 'Arial', sans-serif; color: #1a1d23; background: #fff; font-size: 11px; }
    .report { padding: 28px 32px; }

    .report-title { font-size: 14px; font-weight: 700; color: #1a1d23; text-align: right; }
    .report-period { font-size: 10px; color: #6b7280; text-align: right; }

    .section { margin-bottom: 22px; page-break-inside: avoid; }
    .section-title { font-size: 11px; font-weight: 700; color: #374151; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 8px; padding-bottom: 4px; border-bottom: 1px solid #f0f2f7; }

    /* KPI cards */
    .kpi-table { width: 100%; margin-bottom: 18px; border-collapse: separate; border-spacing: 8px 0; }
    .kpi-table td { border: 1.5px solid #e8eaf0; border-radius: 10px; padding: 14px 16px; vertical-align: top; width: 25%; }
    .kpi-icon { display: inline-block; width: 14px; height: 14px; margin-right: 4px; vertical-align: middle; }
    .kpi-label { font-size: 10px; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 6px; }
    .kpi-value { font-size: 24px; font-weight: 800; color: #1a1d23; line-height: 1; margin-bottom: 4px; }
    .kpi-value-green { font-size: 24px; font-weight: 800; color: #10B981; line-height: 1; margin-bottom: 4px; }
    .kpi-value-blue { font-size: 24px; font-weight: 800; color: #0085f3; line-height: 1; margin-bottom: 4px; }
    .kpi-delta { font-size: 10px; font-weight: 600; }
    .kpi-delta-up { color: #10B981; }
    .kpi-delta-down { color: #ef4444; }
    .kpi-delta-neutral { color: #9ca3af; }

    /* Top performers */
    .top-table { width: 100%; margin-bottom: 18px; border-collapse: separate; border-spacing: 8px 0; }
    .top-table td { border: 1.5px solid #e8eaf0; border-radius: 10px; padding: 14px 16px; vertical-align: top; width: 33.33%; }
    .top-icon-box { display: inline-block; width: 36px; height: 36px; border-radius: 8px; text-align: center; line-height: 36px; font-size: 16px; margin-bottom: 8px; }
    .top-icon-source { background: #eff6ff; color: #2563eb; }
    .top-icon-medium { background: #f0fdf4; color: #16a34a; }
    .top-icon-campaign { background: #fef3c7; color: #d97706; }
    .top-label { font-size: 10px; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 3px; }
    .top-value { font-size: 14px; font-weight: 700; color: #1a1d23; }
    .top-sub { font-size: 10px; color: #6b7280; margin-top: 2px; }

    /* Distribution mini tables */
    .dist-row { margin-bottom: 18px; }
    .dist-row table { width: 100%; }
    .dist-row table > tbody > tr > td { vertical-align: top; padding: 0 8px; }
    .dist-row table > tbody > tr > td:first-child { padding-left: 0; }
    .dist-row table > tbody > tr > td:last-child { padding-right: 0; }

    .dist-title { font-size: 9px; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 6px; }
    .dist-item { margin-bottom: 4px; }
    .dist-label { font-size: 10px; color: #374151; }
    .dist-value { font-size: 10px; font-weight: 700; color: #1a1d23; float: right; }
    .dist-bar { height: 4px; background: #f3f4f6; border-radius: 2px; margin-top: 2px; }
    .dist-bar-fill { height: 4px; background: #0085f3; border-radius: 2px; }

    /* Data tables */
    table.data { width: 100%; border-collapse: collapse; font-size: 10px; }
    table.data thead th { background: #f8fafc; color: #6b7280; font-size: 8.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; padding: 6px 6px; text-align: left; border-bottom: 1px solid #e8eaf0; }
    table.data thead th.right { text-align: right; }
    table.data tbody td { padding: 5px 6px; border-bottom: 1px solid #f3f4f6; color: #374151; }
    table.data tbody td.right { text-align: right; font-variant-numeric: tabular-nums; }
    table.data tbody td.bold { font-weight: 700; color: #1a1d23; }
    table.data tbody td.green { color: #16a34a; font-weight: 700; }
    table.data tbody td.blue { color: #0085f3; font-weight: 700; }
    table.data tbody tr:last-child td { border-bottom: none; }

    .badge { display: inline-block; padding: 1px 6px; border-radius: 100px; font-size: 8.5px; font-weight: 700; }
    .badge-high { background: #d1fae5; color: #065f46; }
    .badge-mid { background: #fef3c7; color: #92400e; }
    .badge-low { background: #f3f4f6; color: #6b7280; }

    .report-footer { margin-top: 24px; padding-top: 10px; border-top: 1px solid #e8eaf0; font-size: 9px; color: #9ca3af; }
    .report-footer table { width: 100%; }
    .report-footer td { padding: 0; }
</style>
</head>
<body>
<div class="report">

    {{-- ═══ Header ═══ --}}
    <table style="width:100%;margin-bottom:20px;border-bottom:2px solid #e8eaf0;padding-bottom:14px;">
        <tr>
            <td style="vertical-align:middle;">
                <img src="{{ public_path('images/logo.png') }}" style="height:32px;" alt="Syncro">
            </td>
            <td style="text-align:right;vertical-align:middle;">
                <div class="report-title">Relatório de Campanhas</div>
                <div class="report-period">Período: últimos {{ $days }} dias ({{ now()->subDays($days)->format('d/m/Y') }} — {{ now()->format('d/m/Y') }})</div>
            </td>
        </tr>
    </table>

    {{-- ═══ KPIs ═══ --}}
    <table class="kpi-table">
        <tr>
            <td>
                <div class="kpi-label"><span style="color:#3B82F6;">&#x1F464;</span> LEADS</div>
                <div class="kpi-value">{{ number_format($totalLeads, 0, ',', '.') }}</div>
                <div class="kpi-delta {{ $deltaLeads === null ? 'kpi-delta-neutral' : ($deltaLeads >= 0 ? 'kpi-delta-up' : 'kpi-delta-down') }}">
                    @if($deltaLeads !== null)
                        {{ $deltaLeads >= 0 ? "\xE2\x86\x91" : "\xE2\x86\x93" }} {{ number_format(abs($deltaLeads), 1, ',', '.') }}% vs anterior
                    @else
                        com UTM no periodo
                    @endif
                </div>
            </td>
            <td>
                <div class="kpi-label"><span style="color:#10B981;">&#10003;</span> CONVERSOES</div>
                <div class="kpi-value">{{ number_format($totalConversions, 0, ',', '.') }}</div>
                <div class="kpi-delta {{ $deltaConv === null ? 'kpi-delta-neutral' : ($deltaConv >= 0 ? 'kpi-delta-up' : 'kpi-delta-down') }}">
                    @if($deltaConv !== null)
                        {{ $deltaConv >= 0 ? "\xE2\x86\x91" : "\xE2\x86\x93" }} {{ number_format(abs($deltaConv), 1, ',', '.') }}% vs anterior
                    @else
                        vendas fechadas
                    @endif
                </div>
            </td>
            <td>
                <div class="kpi-label"><span style="color:#0085f3;">%</span> TAXA DE CONV.</div>
                <div class="kpi-value-blue">{{ number_format($convRate, 1, ',', '.') }}%</div>
                <div class="kpi-delta {{ $convRate >= 5 ? 'kpi-delta-up' : ($convRate >= 2 ? 'kpi-delta-neutral' : 'kpi-delta-down') }}">
                    {{ $convRate >= 5 ? 'excelente' : ($convRate >= 2 ? 'regular' : 'baixa') }}
                </div>
            </td>
            <td>
                <div class="kpi-label"><span style="color:#10B981;">$</span> RECEITA</div>
                <div class="kpi-value-green">{{ __('common.currency') }} {{ number_format($totalRevenue, 0, __('common.decimal_sep'), __('common.thousands_sep')) }}</div>
                <div class="kpi-delta {{ $deltaRev === null ? 'kpi-delta-neutral' : ($deltaRev >= 0 ? 'kpi-delta-up' : 'kpi-delta-down') }}">
                    @if($deltaRev !== null)
                        {{ $deltaRev >= 0 ? "\xE2\x86\x91" : "\xE2\x86\x93" }} {{ number_format(abs($deltaRev), 1, ',', '.') }}% vs anterior
                    @else
                        total gerado
                    @endif
                </div>
            </td>
        </tr>
    </table>

    {{-- ═══ Top Performers ═══ --}}
    @if($utmBreakdown->isNotEmpty())
    <table class="top-table">
        <tr>
            <td>
                <div class="top-icon-box top-icon-source">&#x2192;</div>
                <div class="top-label">MELHOR FONTE</div>
                <div class="top-value">{{ $topSourceName }}</div>
                <div class="top-sub">{{ $topSource ?? 0 }} leads</div>
            </td>
            <td>
                <div class="top-icon-box top-icon-medium">&#x25CE;</div>
                <div class="top-label">MELHOR MIDIA</div>
                <div class="top-value">{{ $topMediumName }}</div>
                <div class="top-sub">{{ $topMedium ?? 0 }} leads</div>
            </td>
            <td>
                <div class="top-icon-box top-icon-campaign">&#x2605;</div>
                <div class="top-label">MELHOR CAMPANHA</div>
                @if($topCampaign)
                <div class="top-value">{{ $topCampaign['utm_campaign'] }}</div>
                <div class="top-sub">{{ number_format($topCampaign['conv_rate'], 1, ',', '.') }}% conversao ({{ $topCampaign['leads'] }} leads)</div>
                @else
                <div class="top-value">—</div>
                <div class="top-sub">sem dados suficientes</div>
                @endif
            </td>
        </tr>
    </table>
    @endif

    {{-- ═══ Distribuição por Source / Medium / Campaign ═══ --}}
    @if($utmBreakdown->isNotEmpty())
    <div class="dist-row">
        <table><tr>
            {{-- Por Source --}}
            <td style="width:33%;vertical-align:top;padding:0 8px 0 0;">
                <div class="dist-title">Por Source</div>
                @php $bySource = $utmBreakdown->groupBy('utm_source')->map(fn($g) => $g->sum('leads'))->sortDesc()->take(8); $srcMax = $bySource->first() ?: 1; @endphp
                @foreach($bySource as $name => $count)
                <div class="dist-item">
                    <div><span class="dist-label">{{ $name }}</span><span class="dist-value">{{ $count }}</span></div>
                    <div class="dist-bar"><div class="dist-bar-fill" style="width:{{ round($count / $srcMax * 100) }}%"></div></div>
                </div>
                @endforeach
            </td>
            {{-- Por Medium --}}
            <td style="width:33%;vertical-align:top;padding:0 8px;">
                <div class="dist-title">Por Medium</div>
                @php $byMedium = $utmBreakdown->groupBy('utm_medium')->map(fn($g) => $g->sum('leads'))->sortDesc()->take(8); $medMax = $byMedium->first() ?: 1; @endphp
                @foreach($byMedium as $name => $count)
                <div class="dist-item">
                    <div><span class="dist-label">{{ $name }}</span><span class="dist-value">{{ $count }}</span></div>
                    <div class="dist-bar"><div class="dist-bar-fill" style="width:{{ round($count / $medMax * 100) }}%;background:#34A853;"></div></div>
                </div>
                @endforeach
            </td>
            {{-- Por Campaign --}}
            <td style="width:33%;vertical-align:top;padding:0 0 0 8px;">
                <div class="dist-title">Por Campaign</div>
                @php $byCamp = $utmBreakdown->groupBy('utm_campaign')->map(fn($g) => $g->sum('leads'))->sortDesc()->take(8); $campMax = $byCamp->first() ?: 1; @endphp
                @foreach($byCamp as $name => $count)
                <div class="dist-item">
                    <div><span class="dist-label">{{ $name }}</span><span class="dist-value">{{ $count }}</span></div>
                    <div class="dist-bar"><div class="dist-bar-fill" style="width:{{ round($count / $campMax * 100) }}%;background:#F59E0B;"></div></div>
                </div>
                @endforeach
            </td>
        </tr></table>
    </div>
    @endif

    {{-- ═══ Detalhamento UTM (tabela completa) ═══ --}}
    @if($utmBreakdown->isNotEmpty())
    <div class="section">
        <div class="section-title">Detalhamento UTM</div>
        <table class="data">
            <thead>
                <tr>
                    <th>Source</th>
                    <th>Medium</th>
                    <th>Campaign</th>
                    <th class="right">Leads</th>
                    <th class="right">Conv.</th>
                    <th class="right">Receita</th>
                    <th class="right">Conv.%</th>
                </tr>
            </thead>
            <tbody>
                @foreach($utmBreakdown as $u)
                <tr>
                    <td class="bold">{{ $u['utm_source'] }}</td>
                    <td>{{ $u['utm_medium'] }}</td>
                    <td>{{ $u['utm_campaign'] }}</td>
                    <td class="right bold">{{ $u['leads'] }}</td>
                    <td class="right">{{ $u['conversions'] }}</td>
                    <td class="right green">{{ $u['revenue'] > 0 ? __('common.currency') . ' ' . number_format($u['revenue'], 0, __('common.decimal_sep'), __('common.thousands_sep')) : '—' }}</td>
                    <td class="right"><span class="badge {{ $u['conv_rate'] >= 10 ? 'badge-high' : ($u['conv_rate'] >= 3 ? 'badge-mid' : 'badge-low') }}">{{ number_format($u['conv_rate'], 1, ',', '.') }}%</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- ═══ Performance por Dimensão (Source) ═══ --}}
    @if($dimensionData->isNotEmpty())
    <div class="section">
        <div class="section-title">Performance por Source</div>
        <table class="data">
            <thead>
                <tr>
                    <th>Source</th>
                    <th class="right">Leads</th>
                    <th class="right">Conv.</th>
                    <th class="right">Receita</th>
                    <th class="right">Conv.%</th>
                </tr>
            </thead>
            <tbody>
                @foreach($dimensionData as $d)
                <tr>
                    <td class="bold">{{ $d['value'] }}</td>
                    <td class="right">{{ $d['leads'] }}</td>
                    <td class="right">{{ $d['conversions'] }}</td>
                    <td class="right green">{{ $d['revenue'] > 0 ? __('common.currency') . ' ' . number_format($d['revenue'], 0, __('common.decimal_sep'), __('common.thousands_sep')) : '—' }}</td>
                    <td class="right"><span class="badge {{ $d['conv_rate'] >= 10 ? 'badge-high' : ($d['conv_rate'] >= 3 ? 'badge-mid' : 'badge-low') }}">{{ number_format($d['conv_rate'], 1, ',', '.') }}%</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- ═══ Funil por Source ═══ --}}
    @if(!empty($funnelStages) && !empty($funnelMatrix))
    <div class="section">
        <div class="section-title">Funil por Source</div>
        <table class="data">
            <thead>
                <tr>
                    <th>Source</th>
                    @foreach($funnelStages as $stage)
                    <th class="right">{{ $stage }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($funnelMatrix as $dimVal => $stages)
                <tr>
                    <td class="bold">{{ $dimVal }}</td>
                    @foreach($funnelStages as $stage)
                    <td class="right">{{ $stages[$stage] ?? 0 }}</td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- ═══ Footer ═══ --}}
    <div class="report-footer">
        <table><tr>
            <td>Gerado em {{ now()->format('d/m/Y H:i') }} — Syncro CRM</td>
            <td style="text-align:right;">{{ auth()->user()->name }} &bull; {{ auth()->user()->tenant->name ?? '' }}</td>
        </tr></table>
    </div>

</div>
</body>
</html>

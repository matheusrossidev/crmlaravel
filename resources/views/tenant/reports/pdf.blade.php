<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Performance</title>
    <style>
        @page { margin: 32px 28px; }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DejaVu Sans', sans-serif; color: #1a1d23; font-size: 11px; line-height: 1.4; }

        /* ═══ Cover ═══ */
        .cover {
            height: 820px;
            position: relative;
            padding: 40px 0;
        }
        .cover-header { text-align: left; }
        .cover-logo { height: 42px; margin-bottom: 48px; }
        .cover-badge {
            display: inline-block;
            background: #eff6ff;
            color: #0070d1;
            font-size: 10px;
            font-weight: bold;
            letter-spacing: 2px;
            padding: 6px 14px;
            border-radius: 4px;
            margin-bottom: 18px;
        }
        .cover-title {
            font-size: 38px;
            font-weight: bold;
            color: #0a0f1a;
            line-height: 1.1;
            margin-bottom: 18px;
        }
        .cover-subtitle {
            font-size: 14px;
            color: #6b7280;
            line-height: 1.6;
            max-width: 480px;
            margin-bottom: 80px;
        }
        .cover-meta {
            border-top: 2px solid #0085f3;
            padding-top: 20px;
            font-size: 11px;
            color: #374151;
        }
        .cover-meta-row { margin-bottom: 6px; display: block; }
        .cover-meta-label {
            color: #9ca3af;
            text-transform: uppercase;
            font-weight: bold;
            font-size: 9px;
            letter-spacing: 1px;
            display: inline-block;
            width: 110px;
        }
        .cover-meta-value { color: #1a1d23; font-weight: bold; }
        .cover-footer {
            font-size: 9px;
            color: #9ca3af;
            text-align: center;
            border-top: 1px solid #e5e7eb;
            padding-top: 14px;
            margin-top: 40px;
        }

        /* ═══ Section headers ═══ */
        .section { page-break-before: always; padding-top: 8px; }
        .section-number {
            font-size: 10px;
            color: #0085f3;
            font-weight: bold;
            letter-spacing: 2px;
            margin-bottom: 4px;
        }
        .section-title {
            font-size: 20px;
            font-weight: bold;
            color: #0a0f1a;
            margin-bottom: 4px;
        }
        .section-desc {
            font-size: 11px;
            color: #6b7280;
            margin-bottom: 20px;
            padding-bottom: 14px;
            border-bottom: 1px solid #e5e7eb;
        }

        /* ═══ KPI cards ═══ */
        .kpi-grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px;
            margin-bottom: 24px;
        }
        .kpi-cell {
            width: 25%;
            padding: 14px 16px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            background: #fff;
            vertical-align: top;
        }
        .kpi-label {
            font-size: 9px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: bold;
            margin-bottom: 8px;
        }
        .kpi-value {
            font-size: 22px;
            font-weight: bold;
            color: #0a0f1a;
            margin-bottom: 4px;
            line-height: 1;
        }
        .kpi-delta {
            font-size: 10px;
            font-weight: bold;
        }
        .kpi-delta.up { color: #059669; }
        .kpi-delta.down { color: #dc2626; }
        .kpi-delta.flat { color: #9ca3af; }

        /* ═══ Chart image wrapper ═══ */
        .chart-box {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 14px;
            background: #fff;
            margin-bottom: 18px;
            text-align: center;
        }
        .chart-box img { max-width: 100%; height: auto; }
        .chart-box-title {
            text-align: left;
            font-size: 11px;
            font-weight: bold;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }

        /* ═══ Tables ═══ */
        table.data {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
            margin-bottom: 18px;
            page-break-inside: avoid;
        }
        table.data th {
            background: #f8fafc;
            color: #374151;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: left;
            padding: 9px 11px;
            border-bottom: 2px solid #e5e7eb;
        }
        table.data td {
            padding: 9px 11px;
            border-bottom: 1px solid #f0f2f7;
            color: #1a1d23;
        }
        table.data tr:last-child td { border-bottom: 0; }
        table.data td.num { text-align: right; font-variant-numeric: tabular-nums; }
        table.data td.pct { font-weight: bold; color: #0070d1; text-align: right; }

        /* ═══ Side-by-side layout (tabela + chart) ═══ */
        .two-col { width: 100%; border-collapse: separate; border-spacing: 10px 0; margin-bottom: 18px; }
        .two-col > tbody > tr > td { vertical-align: top; }

        /* ═══ Badges ═══ */
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
        }
        .badge-green  { background: #d1fae5; color: #047857; }
        .badge-blue   { background: #dbeafe; color: #1d4ed8; }
        .badge-red    { background: #fee2e2; color: #b91c1c; }
        .badge-yellow { background: #fef3c7; color: #92400e; }
        .badge-gray   { background: #f3f4f6; color: #4b5563; }

        /* ═══ Bars (pra heatmap motivos) ═══ */
        .bar-cell { position: relative; height: 14px; background: #f3f4f6; border-radius: 3px; overflow: hidden; }
        .bar-fill { position: absolute; top: 0; left: 0; height: 100%; background: #ef4444; }

        .empty {
            padding: 24px;
            text-align: center;
            color: #9ca3af;
            font-size: 11px;
            font-style: italic;
            border: 1px dashed #e5e7eb;
            border-radius: 10px;
        }
    </style>
</head>
<body>

{{-- ═════════════════════ CAPA ═════════════════════ --}}
<div class="cover">
    <div class="cover-header">
        @if($tenant?->logo)
            <img src="{{ $tenant->logo }}" alt="Logo" class="cover-logo">
        @else
            <img src="{{ public_path('images/logo.png') }}" alt="Syncro" class="cover-logo">
        @endif

        <div class="cover-badge">RELATÓRIO</div>
        <h1 class="cover-title">Performance<br>de Vendas</h1>
        <p class="cover-subtitle">
            Visão consolidada dos seus leads, conversões, equipe e canais de atendimento no período selecionado.
        </p>
    </div>

    <div class="cover-meta">
        <span class="cover-meta-row">
            <span class="cover-meta-label">Empresa</span>
            <span class="cover-meta-value">{{ $tenant?->name ?? '—' }}</span>
        </span>
        <span class="cover-meta-row">
            <span class="cover-meta-label">Período</span>
            <span class="cover-meta-value">{{ $dateFrom->format('d/m/Y') }} até {{ $dateTo->format('d/m/Y') }}</span>
        </span>
        @if($filterPipelineName)
            <span class="cover-meta-row">
                <span class="cover-meta-label">Pipeline</span>
                <span class="cover-meta-value">{{ $filterPipelineName }}</span>
            </span>
        @endif
        @if($filterUserName)
            <span class="cover-meta-row">
                <span class="cover-meta-label">Vendedor</span>
                <span class="cover-meta-value">{{ $filterUserName }}</span>
            </span>
        @endif
        <span class="cover-meta-row">
            <span class="cover-meta-label">Gerado em</span>
            <span class="cover-meta-value">{{ $generatedAt->format('d/m/Y H:i') }} por {{ $generatedBy }}</span>
        </span>
    </div>

    <div class="cover-footer">
        Syncro CRM · Plataforma 360 de Marketing e Vendas
    </div>
</div>

{{-- ═════════════════════ RESUMO EXECUTIVO ═════════════════════ --}}
<div class="section">
    <div class="section-number">01</div>
    <h2 class="section-title">Resumo executivo</h2>
    <p class="section-desc">Indicadores-chave do período com comparação contra o período imediatamente anterior.</p>

    <table class="kpi-grid">
        <tr>
            <td class="kpi-cell">
                <div class="kpi-label">Leads</div>
                <div class="kpi-value">{{ number_format($totalLeads, 0, ',', '.') }}</div>
                @if($deltaLeads !== null)
                    <div class="kpi-delta {{ $deltaLeads > 0 ? 'up' : ($deltaLeads < 0 ? 'down' : 'flat') }}">
                        {{ $deltaLeads > 0 ? '+' : '' }}{{ $deltaLeads }}% vs período ant.
                    </div>
                @else
                    <div class="kpi-delta flat">—</div>
                @endif
            </td>
            <td class="kpi-cell">
                <div class="kpi-label">Receita</div>
                <div class="kpi-value">R$ {{ number_format($totalRevenue, 0, ',', '.') }}</div>
                @if($deltaRevenue !== null)
                    <div class="kpi-delta {{ $deltaRevenue > 0 ? 'up' : ($deltaRevenue < 0 ? 'down' : 'flat') }}">
                        {{ $deltaRevenue > 0 ? '+' : '' }}{{ $deltaRevenue }}% vs período ant.
                    </div>
                @else
                    <div class="kpi-delta flat">—</div>
                @endif
            </td>
            <td class="kpi-cell">
                <div class="kpi-label">Ticket médio</div>
                <div class="kpi-value">R$ {{ number_format($avgTicket, 0, ',', '.') }}</div>
                <div class="kpi-delta flat">{{ $salesCount }} vendas</div>
            </td>
            <td class="kpi-cell">
                <div class="kpi-label">Conversão</div>
                <div class="kpi-value">{{ number_format($convRate, 1, ',', '.') }}%</div>
                <div class="kpi-delta flat">{{ $salesCount }} / {{ $totalLeads }}</div>
            </td>
        </tr>
    </table>

    @if($charts['leadsByDay'])
        <div class="chart-box">
            <div class="chart-box-title">Leads por dia</div>
            <img src="{{ $charts['leadsByDay'] }}" alt="Leads por dia">
        </div>
    @endif
</div>

{{-- ═════════════════════ FUNIL & CONVERSÃO ═════════════════════ --}}
<div class="section">
    <div class="section-number">02</div>
    <h2 class="section-title">Funil de conversão</h2>
    <p class="section-desc">Fluxo dos leads pelos estágios do pipeline e resultado final.</p>

    @if($charts['funnel'])
        <div class="chart-box">
            <div class="chart-box-title">Distribuição de leads</div>
            <img src="{{ $charts['funnel'] }}" alt="Funil">
        </div>
    @endif

    @foreach($pipelineRows as $p)
        <h3 style="font-size:13px;font-weight:bold;color:#374151;margin:18px 0 10px;">
            Pipeline: {{ $p['pipeline']->name }}
        </h3>
        <table class="data">
            <thead>
                <tr>
                    <th>Etapa</th>
                    <th class="num" style="width:80px;">Leads</th>
                    <th class="num" style="width:80px;">%</th>
                    <th class="num" style="width:120px;">Dias médios</th>
                </tr>
            </thead>
            <tbody>
                @foreach($p['stages'] as $s)
                    <tr>
                        <td>
                            @if($s['stage']->is_won)
                                <span class="badge badge-green">GANHO</span>
                            @elseif($s['stage']->is_lost)
                                <span class="badge badge-red">PERDIDO</span>
                            @endif
                            {{ $s['stage']->name }}
                        </td>
                        <td class="num">{{ $s['count'] }}</td>
                        <td class="pct">{{ $p['total'] > 0 ? round($s['count'] / $p['total'] * 100, 1) : 0 }}%</td>
                        <td class="num">{{ $s['avg_days'] !== null ? $s['avg_days'] . ' dias' : '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach
</div>

{{-- ═════════════════════ ORIGEM & CANAIS ═════════════════════ --}}
<div class="section">
    <div class="section-number">03</div>
    <h2 class="section-title">Origem e canais</h2>
    <p class="section-desc">De onde vêm seus leads e como cada canal está performando.</p>

    @if($charts['leadsBySource'] || $sourceConversion->count() > 0)
        <table class="two-col">
            <tr>
                @if($charts['leadsBySource'])
                    <td style="width:38%;">
                        <div class="chart-box">
                            <div class="chart-box-title">Distribuição</div>
                            <img src="{{ $charts['leadsBySource'] }}" alt="Leads por origem">
                        </div>
                    </td>
                @endif
                <td>
                    <table class="data">
                        <thead>
                            <tr>
                                <th>Origem</th>
                                <th class="num">Leads</th>
                                <th class="num">Vendas</th>
                                <th class="num">Conv.</th>
                                <th class="num">Receita</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sourceConversion as $row)
                                <tr>
                                    <td>{{ $row['source'] }}</td>
                                    <td class="num">{{ $row['leads'] }}</td>
                                    <td class="num">{{ $row['vendas'] }}</td>
                                    <td class="pct">{{ $row['conv'] }}%</td>
                                    <td class="num">R$ {{ number_format($row['receita'], 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="empty">Sem dados no período.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>
    @endif

    @if($campaignRows->count() > 0)
        <h3 style="font-size:13px;font-weight:bold;color:#374151;margin:22px 0 10px;">Campanhas (UTM)</h3>
        <table class="data">
            <thead>
                <tr>
                    <th>Campanha</th>
                    <th>Fonte</th>
                    <th class="num">Leads</th>
                    <th class="num">Vendas</th>
                    <th class="num">Conv.</th>
                    <th class="num">Receita</th>
                </tr>
            </thead>
            <tbody>
                @foreach($campaignRows as $c)
                    <tr>
                        <td style="font-weight:bold;">{{ $c['name'] }}</td>
                        <td>{{ $c['source'] }}</td>
                        <td class="num">{{ $c['leads_count'] }}</td>
                        <td class="num">{{ $c['sales_count'] }}</td>
                        <td class="pct">{{ $c['conv'] }}%</td>
                        <td class="num">R$ {{ number_format($c['revenue'], 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if($waClicksTotal > 0)
        <h3 style="font-size:13px;font-weight:bold;color:#374151;margin:22px 0 10px;">
            Botão WhatsApp · {{ $waClicksTotal }} cliques no período
        </h3>

        @if($charts['waClicks'])
            <div class="chart-box">
                <div class="chart-box-title">Cliques por dia</div>
                <img src="{{ $charts['waClicks'] }}" alt="Cliques WA">
            </div>
        @endif

        <table class="two-col">
            <tr>
                <td style="width:50%;">
                    <h4 style="font-size:10px;color:#6b7280;text-transform:uppercase;letter-spacing:1px;font-weight:bold;margin-bottom:8px;">Top fontes (UTM)</h4>
                    <table class="data">
                        <thead><tr><th>Fonte</th><th class="num">Cliques</th></tr></thead>
                        <tbody>
                            @forelse($waClicksBySource as $src => $total)
                                <tr><td>{{ $src }}</td><td class="num">{{ $total }}</td></tr>
                            @empty
                                <tr><td colspan="2" class="empty">—</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </td>
                <td>
                    <h4 style="font-size:10px;color:#6b7280;text-transform:uppercase;letter-spacing:1px;font-weight:bold;margin-bottom:8px;">Top páginas</h4>
                    <table class="data">
                        <thead><tr><th>URL</th><th class="num">Cliques</th></tr></thead>
                        <tbody>
                            @forelse($waClicksByPage as $url => $total)
                                <tr>
                                    <td style="font-size:9.5px;word-break:break-all;">{{ \Illuminate\Support\Str::limit($url, 50) }}</td>
                                    <td class="num">{{ $total }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="empty">—</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>
    @endif
</div>

{{-- ═════════════════════ EQUIPE ═════════════════════ --}}
<div class="section">
    <div class="section-number">04</div>
    <h2 class="section-title">Equipe</h2>
    <p class="section-desc">Performance individual dos vendedores no período.</p>

    <table class="data">
        <thead>
            <tr>
                <th>Vendedor</th>
                <th class="num">Leads</th>
                <th class="num">Vendas</th>
                <th class="num">Conv.</th>
                <th class="num">Receita</th>
            </tr>
        </thead>
        <tbody>
            @forelse($vendedores as $v)
                <tr>
                    <td style="font-weight:bold;">{{ $v['user']->name }}</td>
                    <td class="num">{{ $v['leads'] }}</td>
                    <td class="num">{{ $v['vendas'] }}</td>
                    <td class="pct">{{ $v['conv'] }}%</td>
                    <td class="num">R$ {{ number_format($v['receita'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty">Sem atividade no período.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h3 style="font-size:13px;font-weight:bold;color:#374151;margin:22px 0 10px;">Atendimento WhatsApp</h3>
    <table class="kpi-grid">
        <tr>
            <td class="kpi-cell">
                <div class="kpi-label">Conversas</div>
                <div class="kpi-value">{{ $waTotal }}</div>
                <div class="kpi-delta flat">no período</div>
            </td>
            <td class="kpi-cell">
                <div class="kpi-label">Com lead</div>
                <div class="kpi-value">{{ $waComLead }}</div>
                <div class="kpi-delta flat">{{ $waTotal > 0 ? round($waComLead / $waTotal * 100) : 0 }}% do total</div>
            </td>
            <td class="kpi-cell">
                <div class="kpi-label">Fechadas</div>
                <div class="kpi-value">{{ $waFechadas }}</div>
                <div class="kpi-delta flat">resolvidas</div>
            </td>
            <td class="kpi-cell">
                <div class="kpi-label">1ª resposta</div>
                <div class="kpi-value">
                    @if($avgFirstResponse !== null){{ $avgFirstResponse }}m @else — @endif
                </div>
                <div class="kpi-delta flat">tempo médio humano</div>
            </td>
        </tr>
    </table>
</div>

{{-- ═════════════════════ PRODUTOS ═════════════════════ --}}
@if($topProducts->count() > 0)
    <div class="section">
        <div class="section-number">05</div>
        <h2 class="section-title">Produtos mais vendidos</h2>
        <p class="section-desc">Top 10 produtos por quantidade de vendas no período.</p>

        <table class="data">
            <thead>
                <tr>
                    <th style="width:30px;">#</th>
                    <th>Produto</th>
                    <th class="num">Preço</th>
                    <th class="num">Vendas</th>
                    <th class="num">Receita</th>
                </tr>
            </thead>
            <tbody>
                @foreach($topProducts as $idx => $prod)
                    <tr>
                        <td>{{ $idx + 1 }}{{ $idx === 0 ? ' ★' : '' }}</td>
                        <td style="font-weight:{{ $idx === 0 ? 'bold' : 'normal' }};">{{ $prod->name }}</td>
                        <td class="num">R$ {{ number_format((float) $prod->price, 0, ',', '.') }}</td>
                        <td class="num">{{ $prod->won_count }}</td>
                        <td class="num">R$ {{ number_format((float) $prod->total_value, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

{{-- ═════════════════════ PERDAS ═════════════════════ --}}
@if($totalLost > 0)
    <div class="section">
        <div class="section-number">{{ $topProducts->count() > 0 ? '06' : '05' }}</div>
        <h2 class="section-title">Leads perdidos</h2>
        <p class="section-desc">{{ $totalLost }} leads perdidos · valor potencial de R$ {{ number_format($lostPotentialValue, 0, ',', '.') }}</p>

        <table class="two-col">
            <tr>
                <td style="width:55%;">
                    <h4 style="font-size:10px;color:#6b7280;text-transform:uppercase;letter-spacing:1px;font-weight:bold;margin-bottom:8px;">Por motivo</h4>
                    <table class="data">
                        <thead>
                            <tr>
                                <th>Motivo</th>
                                <th class="num" style="width:60px;">Qtd</th>
                                <th style="width:90px;">%</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lostByReason as $r)
                                <tr>
                                    <td>{{ $r['reason'] }}</td>
                                    <td class="num">{{ $r['total'] }}</td>
                                    <td>
                                        <div class="bar-cell">
                                            <div class="bar-fill" style="width:{{ $r['pct'] }}%;"></div>
                                        </div>
                                        <div style="font-size:9px;color:#6b7280;margin-top:2px;">{{ $r['pct'] }}%</div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </td>
                <td>
                    <h4 style="font-size:10px;color:#6b7280;text-transform:uppercase;letter-spacing:1px;font-weight:bold;margin-bottom:8px;">Por vendedor</h4>
                    <table class="data">
                        <thead>
                            <tr>
                                <th>Vendedor</th>
                                <th class="num">Qtd</th>
                                <th class="num">%</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lostByVendedor as $v)
                                <tr>
                                    <td>{{ $v['user'] }}</td>
                                    <td class="num">{{ $v['total'] }}</td>
                                    <td class="pct">{{ $v['pct'] }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>
    </div>
@endif

</body>
</html>

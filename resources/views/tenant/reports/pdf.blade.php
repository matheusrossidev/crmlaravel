<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Performance</title>
    <style>
        @page { margin: 52px 48px 40px 48px; }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            color: #1a1d23;
            font-size: 11px;
            line-height: 1.5;
        }

        /* ═══════════════════════ CAPA ═══════════════════════ */
        .cover {
            height: 940px;
            position: relative;
        }
        .cover-logo {
            height: 52px;
            width: auto;
            margin-bottom: 44px;
        }
        .cover-badge {
            display: inline-block;
            background: #eff6ff;
            color: #0070d1;
            font-size: 10px;
            font-weight: bold;
            letter-spacing: 2.4px;
            padding: 7px 14px;
            border-radius: 4px;
            margin-bottom: 22px;
        }
        .cover-title {
            font-size: 48px;
            font-weight: bold;
            line-height: 1.05;
            color: #0a0f1a;
            letter-spacing: -0.5px;
            margin-bottom: 16px;
        }
        .cover-subtitle {
            font-size: 15px;
            color: #6b7280;
            line-height: 1.55;
            width: 480px;
            margin-bottom: 56px;
        }

        .cover-meta-wrap {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 40px;
        }
        .cover-meta {
            border-top: 3px solid #0085f3;
            padding-top: 18px;
            margin-bottom: 20px;
        }
        .cover-meta-row {
            padding: 8px 0;
            border-bottom: 1px solid #f0f2f7;
            font-size: 12px;
        }
        .cover-meta-row-last { border-bottom: 0; }
        .cover-meta-label {
            color: #9ca3af;
            text-transform: uppercase;
            font-weight: bold;
            font-size: 9px;
            letter-spacing: 1.2px;
            display: inline-block;
            width: 120px;
        }
        .cover-meta-value {
            color: #0a0f1a;
            font-weight: bold;
        }
        .cover-footer {
            text-align: center;
            font-size: 9px;
            color: #9ca3af;
            letter-spacing: 0.3px;
            padding-top: 14px;
            border-top: 1px solid #e5e7eb;
        }

        /* ═══════════════════════ SEÇÕES ═══════════════════════ */
        .section { page-break-before: always; padding-top: 0; }
        .section-head {
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid #e5e7eb;
        }
        .section-number {
            font-size: 10px;
            color: #0085f3;
            font-weight: bold;
            letter-spacing: 2.5px;
            margin-bottom: 5px;
        }
        .section-title {
            font-size: 24px;
            font-weight: bold;
            color: #0a0f1a;
            margin-bottom: 6px;
            letter-spacing: -0.3px;
        }
        .section-desc {
            font-size: 12px;
            color: #6b7280;
        }

        h3.sub {
            font-size: 13px;
            font-weight: bold;
            color: #1a1d23;
            margin: 24px 0 10px;
        }
        h3.sub span.small {
            color: #6b7280;
            font-weight: normal;
            font-size: 11px;
            margin-left: 6px;
        }

        /* ═══════════════════════ KPI grid ═══════════════════════ */
        /* DomPDF não suporta CSS Grid — usa table */
        table.kpi-grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 10px 0;
            margin-bottom: 24px;
        }
        table.kpi-grid td {
            width: 25%;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 14px 16px;
            vertical-align: top;
        }
        .kpi-top {
            margin-bottom: 8px;
        }
        .kpi-icon {
            display: inline-block;
            width: 22px;
            height: 22px;
            border-radius: 6px;
            background: #eff6ff;
            color: #0085f3;
            text-align: center;
            line-height: 22px;
            font-size: 11px;
            font-weight: bold;
            margin-right: 6px;
            vertical-align: middle;
        }
        .kpi-icon.green  { background: #d1fae5; color: #059669; }
        .kpi-icon.yellow { background: #fef3c7; color: #d97706; }
        .kpi-icon.purple { background: #ede9fe; color: #7c3aed; }
        .kpi-icon.teal   { background: #ccfbf1; color: #0f766e; }
        .kpi-icon.gray   { background: #f3f4f6; color: #4b5563; }
        .kpi-icon.blue   { background: #dbeafe; color: #1d4ed8; }

        .kpi-label {
            display: inline-block;
            font-size: 10px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: bold;
            vertical-align: middle;
        }
        .kpi-value {
            font-size: 24px;
            font-weight: bold;
            color: #0a0f1a;
            line-height: 1.05;
            margin-bottom: 5px;
            letter-spacing: -0.5px;
        }
        .kpi-delta {
            font-size: 10.5px;
            font-weight: bold;
        }
        .kpi-delta.up   { color: #059669; }
        .kpi-delta.down { color: #dc2626; }
        .kpi-delta.flat { color: #9ca3af; }

        /* ═══════════════════════ Chart card ═══════════════════════ */
        .chart-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px 18px;
            margin-bottom: 18px;
            text-align: center;
        }
        .chart-card-title {
            text-align: left;
            font-size: 10px;
            font-weight: bold;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            margin-bottom: 12px;
        }
        .chart-card img {
            max-width: 100%;
            height: auto;
        }

        /* ═══════════════════════ Tables ═══════════════════════ */
        table.data {
            width: 100%;
            border-collapse: collapse;
            font-size: 11.5px;
            margin-bottom: 16px;
            page-break-inside: avoid;
        }
        table.data th {
            background: #f8fafc;
            color: #1a1d23;
            font-size: 9.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            text-align: left;
            padding: 10px 12px;
            border-bottom: 1.5px solid #e5e7eb;
        }
        table.data td {
            padding: 10px 12px;
            border-bottom: 1px solid #f0f2f7;
            color: #1a1d23;
        }
        table.data tr:last-child td { border-bottom: 0; }
        table.data td.num { text-align: right; }
        table.data td.pct { text-align: right; font-weight: bold; color: #0070d1; }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
            letter-spacing: 0.3px;
        }
        .badge-green  { background: #d1fae5; color: #047857; }
        .badge-red    { background: #fee2e2; color: #b91c1c; }
        .badge-blue   { background: #dbeafe; color: #1d4ed8; }

        /* Layout 2 colunas pra chart + tabela */
        table.layout-2col {
            width: 100%;
            border-collapse: separate;
            border-spacing: 12px 0;
            margin-bottom: 16px;
        }
        table.layout-2col > tbody > tr > td { vertical-align: top; }

        .champion { font-weight: bold; color: #0a0f1a; }
        .star { color: #f59e0b; margin-right: 4px; font-weight: bold; }

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

{{-- ═════════════════════════ CAPA ═════════════════════════ --}}
<div class="cover">
    <img src="{{ public_path('images/logo.png') }}" alt="Syncro" class="cover-logo">

    <div class="cover-badge">RELATÓRIO</div>
    <h1 class="cover-title">Performance<br>de Vendas</h1>
    <p class="cover-subtitle">
        Visão consolidada dos seus leads, conversões, equipe e canais de atendimento no período selecionado.
    </p>

    <div class="cover-meta-wrap">
        <div class="cover-meta">
            <div class="cover-meta-row">
                <span class="cover-meta-label">Empresa</span>
                <span class="cover-meta-value">{{ $tenant?->name ?? '—' }}</span>
            </div>
            <div class="cover-meta-row">
                <span class="cover-meta-label">Período</span>
                <span class="cover-meta-value">{{ $dateFrom->format('d/m/Y') }} até {{ $dateTo->format('d/m/Y') }} ({{ $dateFrom->diffInDays($dateTo) + 1 }} dias)</span>
            </div>
            @if($filterPipelineName)
                <div class="cover-meta-row">
                    <span class="cover-meta-label">Pipeline</span>
                    <span class="cover-meta-value">{{ $filterPipelineName }}</span>
                </div>
            @endif
            @if($filterUserName)
                <div class="cover-meta-row">
                    <span class="cover-meta-label">Vendedor</span>
                    <span class="cover-meta-value">{{ $filterUserName }}</span>
                </div>
            @endif
            <div class="cover-meta-row cover-meta-row-last">
                <span class="cover-meta-label">Gerado em</span>
                <span class="cover-meta-value">{{ $generatedAt->format('d/m/Y H:i') }} por {{ $generatedBy }}</span>
            </div>
        </div>

        <div class="cover-footer">
            Syncro CRM · Plataforma 360 de Marketing e Vendas
        </div>
    </div>
</div>

{{-- ═════════════════════════ RESUMO EXECUTIVO ═════════════════════════ --}}
<div class="section">
    <div class="section-head">
        <div class="section-number">01</div>
        <h2 class="section-title">Resumo executivo</h2>
        <p class="section-desc">Indicadores-chave do período com comparação contra o período imediatamente anterior.</p>
    </div>

    <table class="kpi-grid">
        <tr>
            <td>
                <div class="kpi-top">
                    <span class="kpi-icon">◉</span>
                    <span class="kpi-label">Leads</span>
                </div>
                <div class="kpi-value">{{ number_format($totalLeads, 0, ',', '.') }}</div>
                @if($deltaLeads !== null)
                    <div class="kpi-delta {{ $deltaLeads > 0 ? 'up' : ($deltaLeads < 0 ? 'down' : 'flat') }}">
                        {{ $deltaLeads > 0 ? '▲' : ($deltaLeads < 0 ? '▼' : '●') }}
                        {{ $deltaLeads > 0 ? '+' : '' }}{{ $deltaLeads }}% vs período ant.
                    </div>
                @else
                    <div class="kpi-delta flat">—</div>
                @endif
            </td>
            <td>
                <div class="kpi-top">
                    <span class="kpi-icon green">$</span>
                    <span class="kpi-label">Receita</span>
                </div>
                <div class="kpi-value">R$ {{ number_format($totalRevenue, 0, ',', '.') }}</div>
                @if($deltaRevenue !== null)
                    <div class="kpi-delta {{ $deltaRevenue > 0 ? 'up' : ($deltaRevenue < 0 ? 'down' : 'flat') }}">
                        {{ $deltaRevenue > 0 ? '▲' : ($deltaRevenue < 0 ? '▼' : '●') }}
                        {{ $deltaRevenue > 0 ? '+' : '' }}{{ $deltaRevenue }}% vs período ant.
                    </div>
                @else
                    <div class="kpi-delta flat">—</div>
                @endif
            </td>
            <td>
                <div class="kpi-top">
                    <span class="kpi-icon yellow">◈</span>
                    <span class="kpi-label">Ticket médio</span>
                </div>
                <div class="kpi-value">R$ {{ number_format($avgTicket, 0, ',', '.') }}</div>
                <div class="kpi-delta flat">{{ $salesCount }} vendas</div>
            </td>
            <td>
                <div class="kpi-top">
                    <span class="kpi-icon purple">%</span>
                    <span class="kpi-label">Conversão</span>
                </div>
                <div class="kpi-value">{{ number_format($convRate, 1, ',', '.') }}%</div>
                <div class="kpi-delta flat">{{ $salesCount }} / {{ $totalLeads }}</div>
            </td>
        </tr>
    </table>

    @if($charts['leadsByDay'])
        <div class="chart-card">
            <div class="chart-card-title">Leads por dia</div>
            <img src="{{ $charts['leadsByDay'] }}" alt="Leads por dia">
        </div>
    @endif
</div>

{{-- ═════════════════════════ FUNIL ═════════════════════════ --}}
<div class="section">
    <div class="section-head">
        <div class="section-number">02</div>
        <h2 class="section-title">Funil de conversão</h2>
        <p class="section-desc">Fluxo dos leads pelos estágios do pipeline e resultado final.</p>
    </div>

    @if($charts['funnel'])
        <div class="chart-card">
            <div class="chart-card-title">Distribuição geral</div>
            <img src="{{ $charts['funnel'] }}" alt="Funil">
        </div>
    @endif

    @foreach($pipelineRows as $p)
        <h3 class="sub">
            Pipeline: {{ $p['pipeline']->name }}
            <span class="small">{{ $p['total'] }} leads totais</span>
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

{{-- ═════════════════════════ ORIGEM & CANAIS ═════════════════════════ --}}
<div class="section">
    <div class="section-head">
        <div class="section-number">03</div>
        <h2 class="section-title">Origem e canais</h2>
        <p class="section-desc">De onde vêm seus leads e como cada canal está performando.</p>
    </div>

    @if($charts['leadsBySource'] || $sourceConversion->count() > 0)
        <table class="layout-2col">
            <tr>
                @if($charts['leadsBySource'])
                    <td style="width:36%;">
                        <div class="chart-card" style="margin-bottom:0;">
                            <div class="chart-card-title">Distribuição</div>
                            <img src="{{ $charts['leadsBySource'] }}" alt="Leads por origem">
                        </div>
                    </td>
                @endif
                <td>
                    <table class="data" style="margin-bottom:0;">
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

    {{-- Campanhas UTM --}}
    @if($campaignRows->count() > 0)
        <h3 class="sub">Campanhas (UTM)</h3>
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

    {{-- Botão WhatsApp --}}
    @if($waClicksTotal > 0)
        <h3 class="sub">
            Botão WhatsApp
            <span class="small">{{ $waClicksTotal }} cliques no período</span>
        </h3>

        @if($charts['waClicks'])
            <div class="chart-card">
                <div class="chart-card-title">Cliques por dia</div>
                <img src="{{ $charts['waClicks'] }}" alt="Cliques WA">
            </div>
        @endif

        <table class="layout-2col">
            <tr>
                <td style="width:50%;">
                    <h3 class="sub" style="margin-top:0;">Top fontes (UTM)</h3>
                    <table class="data" style="margin-bottom:0;">
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
                    <h3 class="sub" style="margin-top:0;">Top páginas</h3>
                    <table class="data" style="margin-bottom:0;">
                        <thead><tr><th>URL</th><th class="num">Cliques</th></tr></thead>
                        <tbody>
                            @forelse($waClicksByPage as $url => $total)
                                <tr>
                                    <td style="font-size:10px;word-break:break-all;">{{ \Illuminate\Support\Str::limit($url, 50) }}</td>
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

{{-- ═════════════════════════ EQUIPE ═════════════════════════ --}}
<div class="section">
    <div class="section-head">
        <div class="section-number">04</div>
        <h2 class="section-title">Equipe</h2>
        <p class="section-desc">Performance individual dos vendedores no período.</p>
    </div>

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
            @forelse($vendedores as $idx => $v)
                <tr>
                    <td class="champion">
                        @if($idx === 0)<span class="star">★</span>@endif{{ $v['user']->name }}
                    </td>
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

    <h3 class="sub">Atendimento WhatsApp</h3>
    <table class="kpi-grid">
        <tr>
            <td>
                <div class="kpi-top">
                    <span class="kpi-icon green">W</span>
                    <span class="kpi-label">Conversas</span>
                </div>
                <div class="kpi-value">{{ $waTotal }}</div>
                <div class="kpi-delta flat">no período</div>
            </td>
            <td>
                <div class="kpi-top">
                    <span class="kpi-icon">◉</span>
                    <span class="kpi-label">Com lead</span>
                </div>
                <div class="kpi-value">{{ $waComLead }}</div>
                <div class="kpi-delta flat">{{ $waTotal > 0 ? round($waComLead / $waTotal * 100) : 0 }}% do total</div>
            </td>
            <td>
                <div class="kpi-top">
                    <span class="kpi-icon blue">✓</span>
                    <span class="kpi-label">Fechadas</span>
                </div>
                <div class="kpi-value">{{ $waFechadas }}</div>
                <div class="kpi-delta flat">resolvidas</div>
            </td>
            <td>
                <div class="kpi-top">
                    <span class="kpi-icon yellow">⏱</span>
                    <span class="kpi-label">1ª resposta</span>
                </div>
                <div class="kpi-value">
                    @if($avgFirstResponse !== null){{ $avgFirstResponse }}m @else — @endif
                </div>
                <div class="kpi-delta flat">tempo médio humano</div>
            </td>
        </tr>
    </table>
</div>

{{-- ═════════════════════════ PRODUTOS ═════════════════════════ --}}
@if($topProducts->count() > 0)
    <div class="section">
        <div class="section-head">
            <div class="section-number">05</div>
            <h2 class="section-title">Produtos mais vendidos</h2>
            <p class="section-desc">Top 10 produtos por quantidade de vendas no período.</p>
        </div>

        <table class="data">
            <thead>
                <tr>
                    <th style="width:40px;">#</th>
                    <th>Produto</th>
                    <th class="num">Preço</th>
                    <th class="num">Vendas</th>
                    <th class="num">Receita</th>
                </tr>
            </thead>
            <tbody>
                @foreach($topProducts as $idx => $prod)
                    <tr>
                        <td>@if($idx === 0)<span class="star">★</span>@endif{{ $idx + 1 }}</td>
                        <td class="{{ $idx === 0 ? 'champion' : '' }}">{{ $prod->name }}</td>
                        <td class="num">R$ {{ number_format((float) $prod->price, 0, ',', '.') }}</td>
                        <td class="num">{{ $prod->won_count }}</td>
                        <td class="num">R$ {{ number_format((float) $prod->total_value, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

{{-- ═════════════════════════ PERDAS ═════════════════════════ --}}
@if($totalLost > 0)
    <div class="section">
        <div class="section-head">
            <div class="section-number">{{ $topProducts->count() > 0 ? '06' : '05' }}</div>
            <h2 class="section-title">Leads perdidos</h2>
            <p class="section-desc">{{ $totalLost }} leads perdidos · valor potencial de R$ {{ number_format($lostPotentialValue, 0, ',', '.') }}</p>
        </div>

        <table class="layout-2col">
            <tr>
                <td style="width:55%;">
                    <h3 class="sub" style="margin-top:0;">Por motivo</h3>
                    <table class="data" style="margin-bottom:0;">
                        <thead>
                            <tr>
                                <th>Motivo</th>
                                <th class="num" style="width:60px;">Qtd</th>
                                <th class="num" style="width:60px;">%</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lostByReason as $r)
                                <tr>
                                    <td>{{ $r['reason'] }}</td>
                                    <td class="num">{{ $r['total'] }}</td>
                                    <td class="pct">{{ $r['pct'] }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </td>
                <td>
                    <h3 class="sub" style="margin-top:0;">Por vendedor</h3>
                    <table class="data" style="margin-bottom:0;">
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

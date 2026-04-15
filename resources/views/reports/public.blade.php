<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    @include('partials._google-analytics')
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $report->title ?? 'Relatório de Performance' }} — Syncro</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --blue: #0085f3; --blue-dark: #0070d1; --blue-soft: #eff6ff;
            --ink: #0a0f1a; --text: #1a1d23;
            --muted: #6b7280; --faint: #9ca3af;
            --line: #e5e7eb; --line-soft: #f0f2f7;
            --bg: #f4f6fb;
            --green: #10b981; --red: #ef4444; --yellow: #f59e0b;
        }
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
            padding: 24px;
            font-size: 13px;
            line-height: 1.5;
        }
        .page {
            width: 794px;
            min-height: 1123px;
            margin: 0 auto 28px;
            background: #fff;
            box-shadow: 0 4px 24px rgba(0,0,0,0.06);
            padding: 56px 52px;
            position: relative;
        }

        /* ── Print button flutuante ── */
        .print-btn {
            position: fixed;
            top: 20px; right: 20px;
            z-index: 100;
            background: var(--blue);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 10px 18px;
            font-size: 13px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            box-shadow: 0 4px 16px rgba(0,133,243,.25);
            display: flex; align-items: center; gap: 6px;
            transition: transform .15s, background .15s;
        }
        .print-btn:hover { background: var(--blue-dark); transform: translateY(-1px); }

        /* ── Cover ── */
        .cover { display: flex; flex-direction: column; min-height: 1010px; }
        .cover-top { flex: 1; }
        .cover-logo { height: 52px; margin-bottom: 48px; }
        .cover-badge {
            display: inline-block;
            background: var(--blue-soft); color: var(--blue-dark);
            font-size: 11px; font-weight: 700;
            letter-spacing: 2.4px;
            padding: 8px 16px;
            border-radius: 6px;
            margin-bottom: 24px;
        }
        .cover-title {
            font-size: 56px; font-weight: 800;
            line-height: 1.05; color: var(--ink);
            letter-spacing: -0.02em;
            margin-bottom: 20px;
        }
        .cover-subtitle {
            font-size: 17px; color: var(--muted);
            line-height: 1.55;
            max-width: 520px;
            margin-bottom: 60px;
        }
        .cover-meta {
            margin-top: auto;
            border-top: 3px solid var(--blue);
            padding-top: 24px;
        }
        .cover-meta-row {
            display: flex; gap: 24px;
            padding: 10px 0;
            border-bottom: 1px solid var(--line-soft);
            font-size: 13px;
        }
        .cover-meta-row:last-child { border-bottom: 0; }
        .cover-meta-label {
            color: var(--faint);
            text-transform: uppercase;
            font-weight: 700;
            font-size: 10px;
            letter-spacing: 1.2px;
            width: 140px;
            flex-shrink: 0;
        }
        .cover-meta-value { color: var(--ink); font-weight: 600; }
        .cover-footer {
            margin-top: 32px;
            text-align: center;
            font-size: 10px; color: var(--faint);
            letter-spacing: 0.3px;
        }

        /* ── Seções ── */
        .section-head {
            margin-bottom: 28px; padding-bottom: 18px;
            border-bottom: 1px solid var(--line);
        }
        .section-number {
            font-size: 11px; color: var(--blue);
            font-weight: 800; letter-spacing: 2.5px;
            margin-bottom: 6px;
        }
        .section-title {
            font-size: 28px; font-weight: 800;
            color: var(--ink); letter-spacing: -0.015em;
            margin-bottom: 6px;
        }
        .section-desc {
            font-size: 13.5px; color: var(--muted);
            max-width: 560px;
        }
        h3.sub {
            font-size: 14px; font-weight: 700;
            color: var(--text); margin: 28px 0 12px;
            letter-spacing: -0.01em;
        }
        h3.sub small {
            color: var(--muted); font-weight: 500;
            font-size: 12px; margin-left: 6px;
        }

        /* ── KPI cards ── */
        .kpi-grid {
            display: grid; grid-template-columns: repeat(4, 1fr);
            gap: 14px; margin-bottom: 28px;
        }
        .kpi {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 18px 20px;
        }
        .kpi-top {
            display: flex; align-items: center; gap: 8px;
            margin-bottom: 10px;
        }
        .kpi-icon {
            width: 28px; height: 28px;
            border-radius: 8px;
            background: var(--blue-soft); color: var(--blue);
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 14px;
        }
        .kpi-label {
            font-size: 11px; color: var(--muted);
            text-transform: uppercase; letter-spacing: 1px;
            font-weight: 700;
        }
        .kpi-value {
            font-size: 28px; font-weight: 800;
            color: var(--ink); line-height: 1;
            margin-bottom: 6px;
            letter-spacing: -0.02em;
        }
        .kpi-delta {
            font-size: 11.5px; font-weight: 600;
            display: inline-flex; align-items: center; gap: 3px;
        }
        .kpi-delta.up { color: var(--green); }
        .kpi-delta.down { color: var(--red); }
        .kpi-delta.flat { color: var(--faint); }

        /* ── Charts ── */
        .chart-card {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 20px 22px;
            margin-bottom: 22px;
        }
        .chart-card-title {
            font-size: 11px; font-weight: 700;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 1.2px;
            margin-bottom: 14px;
        }
        .chart-wrapper { position: relative; height: 220px; }
        .chart-wrapper.sm { height: 160px; }
        .chart-wrapper.tall { height: 280px; }

        /* ── Tables ── */
        table.data { width: 100%; border-collapse: collapse; font-size: 12.5px; }
        table.data th {
            background: #f8fafc; color: var(--text);
            font-size: 10px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.8px;
            text-align: left;
            padding: 11px 14px;
            border-bottom: 1.5px solid var(--line);
        }
        table.data td {
            padding: 11px 14px;
            border-bottom: 1px solid var(--line-soft);
        }
        table.data tr:last-child td { border-bottom: 0; }
        table.data td.num { text-align: right; font-variant-numeric: tabular-nums; }
        table.data td.pct { text-align: right; font-weight: 700; color: var(--blue-dark); }
        table.data tr:hover td { background: #fafbfd; }

        .badge {
            display: inline-block; padding: 3px 8px;
            border-radius: 4px;
            font-size: 10px; font-weight: 700;
            letter-spacing: 0.3px;
        }
        .badge-green { background: #d1fae5; color: #047857; }
        .badge-red { background: #fee2e2; color: #b91c1c; }

        .row-2col {
            display: grid; grid-template-columns: 1fr 1.4fr;
            gap: 18px; margin-bottom: 20px;
        }

        .champion { font-weight: 700; color: var(--ink); }
        .star { color: var(--yellow); margin-right: 4px; }
        .empty { padding: 20px; text-align: center; color: var(--faint); font-size: 12px; font-style: italic; }

        /* ── Print mode ── */
        @media print {
            body { background: #fff; padding: 0; }
            .print-btn { display: none !important; }
            .page {
                box-shadow: none;
                margin: 0 auto;
                padding: 40px 36px;
                page-break-after: always;
            }
            .page:last-child { page-break-after: auto; }
        }
    </style>
</head>
<body>

<button class="print-btn" onclick="window.print()">
    <i class="bi bi-printer-fill"></i> Imprimir / Salvar PDF
</button>

@php
    $totalLeads  = $data['totalLeads'] ?? 0;
    $salesCount  = $data['salesCount'] ?? 0;
    $totalRev    = $data['totalRevenue'] ?? 0;
    $avgTicket   = $data['avgTicket'] ?? 0;
    $convRate    = $data['convRate'] ?? 0;
    $deltaLeads  = $data['deltaLeads'] ?? null;
    $deltaRev    = $data['deltaRevenue'] ?? null;
    $dateFromStr = \Carbon\Carbon::parse($data['dateFrom'])->format('d/m/Y');
    $dateToStr   = \Carbon\Carbon::parse($data['dateTo'])->format('d/m/Y');
    $daysPeriod  = \Carbon\Carbon::parse($data['dateFrom'])->diffInDays(\Carbon\Carbon::parse($data['dateTo'])) + 1;
    $pipelineRows  = $data['pipelineRows'] ?? [];
    $sourceConv    = $data['sourceConversion'] ?? [];
    $leadsBySource = $data['leadsBySource'] ?? [];
    $campaignRows  = $data['campaignRows'] ?? [];
    $waTotal       = $data['waTotal'] ?? 0;
    $waComLead     = $data['waComLead'] ?? 0;
    $waFechadas    = $data['waFechadas'] ?? 0;
    $avgFirstResp  = $data['avgFirstResponse'] ?? null;
    $vendedores    = $data['vendedores'] ?? [];
    $topProducts   = $data['topProducts'] ?? [];
    $waClicksTotal = $data['waClicksTotal'] ?? 0;
    $waClicksByDay = $data['waClicksByDay'] ?? [];
    $waClicksBySrc = $data['waClicksBySource'] ?? [];
    $waClicksByPg  = $data['waClicksByPage'] ?? [];
    $totalLost     = $data['totalLost'] ?? 0;
    $lostValue     = $data['lostPotentialValue'] ?? 0;
    $lostByReason  = $data['lostByReason'] ?? [];
    $lostByVendor  = $data['lostByVendedor'] ?? [];
@endphp

{{-- ═════════════ CAPA ═════════════ --}}
<div class="page cover">
    <div class="cover-top">
        <img src="{{ asset('images/logo.png') }}" alt="Syncro" class="cover-logo" onerror="this.style.display='none'">
        <div class="cover-badge">RELATÓRIO</div>
        <h1 class="cover-title">{{ $report->title ?: 'Performance de Vendas' }}</h1>
        <p class="cover-subtitle">
            Visão consolidada dos seus leads, conversões, equipe e canais de atendimento no período selecionado.
        </p>
    </div>

    <div class="cover-meta">
        <div class="cover-meta-row">
            <span class="cover-meta-label">Empresa</span>
            <span class="cover-meta-value">{{ $report->tenant?->name ?? '—' }}</span>
        </div>
        <div class="cover-meta-row">
            <span class="cover-meta-label">Período</span>
            <span class="cover-meta-value">{{ $dateFromStr }} até {{ $dateToStr }} ({{ $daysPeriod }} dias)</span>
        </div>
        @if(! empty($data['filterPipelineName']))
        <div class="cover-meta-row">
            <span class="cover-meta-label">Pipeline</span>
            <span class="cover-meta-value">{{ $data['filterPipelineName'] }}</span>
        </div>
        @endif
        @if(! empty($data['filterUserName']))
        <div class="cover-meta-row">
            <span class="cover-meta-label">Vendedor</span>
            <span class="cover-meta-value">{{ $data['filterUserName'] }}</span>
        </div>
        @endif
        <div class="cover-meta-row">
            <span class="cover-meta-label">Gerado em</span>
            <span class="cover-meta-value">{{ $report->created_at->format('d/m/Y H:i') }} por {{ $report->user?->name ?? '—' }}</span>
        </div>
    </div>
    <div class="cover-footer">Syncro CRM · Plataforma 360 de Marketing e Vendas</div>
</div>

{{-- ═════════════ 01 RESUMO ═════════════ --}}
<div class="page">
    <div class="section-head">
        <div class="section-number">01</div>
        <h2 class="section-title">Resumo executivo</h2>
        <p class="section-desc">Indicadores-chave do período com comparação contra o período imediatamente anterior.</p>
    </div>

    <div class="kpi-grid">
        <div class="kpi">
            <div class="kpi-top">
                <span class="kpi-icon"><i class="bi bi-people-fill"></i></span>
                <span class="kpi-label">Leads</span>
            </div>
            <div class="kpi-value">{{ number_format($totalLeads, 0, ',', '.') }}</div>
            @if($deltaLeads !== null)
                <div class="kpi-delta {{ $deltaLeads > 0 ? 'up' : ($deltaLeads < 0 ? 'down' : 'flat') }}">
                    <i class="bi bi-arrow-{{ $deltaLeads > 0 ? 'up' : ($deltaLeads < 0 ? 'down' : 'right') }}"></i>
                    {{ $deltaLeads > 0 ? '+' : '' }}{{ $deltaLeads }}% vs período ant.
                </div>
            @else
                <div class="kpi-delta flat">—</div>
            @endif
        </div>
        <div class="kpi">
            <div class="kpi-top">
                <span class="kpi-icon" style="background:#d1fae5;color:#059669;"><i class="bi bi-currency-dollar"></i></span>
                <span class="kpi-label">Receita</span>
            </div>
            <div class="kpi-value">R$ {{ number_format($totalRev, 0, ',', '.') }}</div>
            @if($deltaRev !== null)
                <div class="kpi-delta {{ $deltaRev > 0 ? 'up' : ($deltaRev < 0 ? 'down' : 'flat') }}">
                    <i class="bi bi-arrow-{{ $deltaRev > 0 ? 'up' : ($deltaRev < 0 ? 'down' : 'right') }}"></i>
                    {{ $deltaRev > 0 ? '+' : '' }}{{ $deltaRev }}% vs período ant.
                </div>
            @else
                <div class="kpi-delta flat">—</div>
            @endif
        </div>
        <div class="kpi">
            <div class="kpi-top">
                <span class="kpi-icon" style="background:#fef3c7;color:#d97706;"><i class="bi bi-receipt"></i></span>
                <span class="kpi-label">Ticket médio</span>
            </div>
            <div class="kpi-value">R$ {{ number_format($avgTicket, 0, ',', '.') }}</div>
            <div class="kpi-delta flat">{{ $salesCount }} vendas</div>
        </div>
        <div class="kpi">
            <div class="kpi-top">
                <span class="kpi-icon" style="background:#ede9fe;color:#7c3aed;"><i class="bi bi-graph-up-arrow"></i></span>
                <span class="kpi-label">Conversão</span>
            </div>
            <div class="kpi-value">{{ number_format($convRate, 1, ',', '.') }}%</div>
            <div class="kpi-delta flat">{{ $salesCount }} / {{ $totalLeads }}</div>
        </div>
    </div>

    <div class="chart-card">
        <div class="chart-card-title">Leads por dia</div>
        <div class="chart-wrapper tall"><canvas id="chartLeads"></canvas></div>
    </div>
</div>

{{-- ═════════════ 02 FUNIL ═════════════ --}}
<div class="page">
    <div class="section-head">
        <div class="section-number">02</div>
        <h2 class="section-title">Funil de conversão</h2>
        <p class="section-desc">Fluxo dos leads pelos estágios do pipeline e resultado final.</p>
    </div>

    <div class="chart-card">
        <div class="chart-card-title">Distribuição geral</div>
        <div class="chart-wrapper tall"><canvas id="chartFunnel"></canvas></div>
    </div>

    @foreach($pipelineRows as $p)
        <h3 class="sub">
            Pipeline: {{ $p['pipeline']['name'] ?? '—' }}
            <small>{{ $p['total'] ?? 0 }} leads totais</small>
        </h3>
        <table class="data">
            <thead>
                <tr><th>Etapa</th><th class="num">Leads</th><th class="num">%</th><th class="num">Dias médios</th></tr>
            </thead>
            <tbody>
                @foreach($p['stages'] ?? [] as $s)
                    @php
                        $stageData = $s['stage'] ?? [];
                        $isWon  = $stageData['is_won']  ?? false;
                        $isLost = $stageData['is_lost'] ?? false;
                        $stageName = $stageData['name'] ?? '—';
                    @endphp
                    <tr>
                        <td>
                            @if($isWon) <span class="badge badge-green">GANHO</span> @endif
                            @if($isLost) <span class="badge badge-red">PERDIDO</span> @endif
                            {{ $stageName }}
                        </td>
                        <td class="num">{{ $s['count'] ?? 0 }}</td>
                        <td class="pct">{{ ($p['total'] ?? 0) > 0 ? round(($s['count'] ?? 0) / $p['total'] * 100, 1) : 0 }}%</td>
                        <td class="num">{{ isset($s['avg_days']) ? $s['avg_days'] . ' dias' : '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach
</div>

{{-- ═════════════ 03 ORIGEM & CANAIS ═════════════ --}}
<div class="page">
    <div class="section-head">
        <div class="section-number">03</div>
        <h2 class="section-title">Origem e canais</h2>
        <p class="section-desc">De onde vêm seus leads e como cada canal está performando.</p>
    </div>

    <div class="row-2col">
        <div class="chart-card" style="margin-bottom:0;">
            <div class="chart-card-title">Distribuição</div>
            <div class="chart-wrapper"><canvas id="chartSources"></canvas></div>
        </div>
        <div>
            <table class="data">
                <thead>
                    <tr><th>Origem</th><th class="num">Leads</th><th class="num">Vendas</th><th class="num">Conv.</th><th class="num">Receita</th></tr>
                </thead>
                <tbody>
                @forelse($sourceConv as $row)
                    <tr>
                        <td>{{ $row['source'] ?? '—' }}</td>
                        <td class="num">{{ $row['leads'] ?? 0 }}</td>
                        <td class="num">{{ $row['vendas'] ?? 0 }}</td>
                        <td class="pct">{{ $row['conv'] ?? 0 }}%</td>
                        <td class="num">R$ {{ number_format($row['receita'] ?? 0, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty">Sem dados no período.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if(count($campaignRows))
        <h3 class="sub">Campanhas (UTM)</h3>
        <table class="data">
            <thead>
                <tr><th>Campanha</th><th>Fonte</th><th class="num">Leads</th><th class="num">Vendas</th><th class="num">Conv.</th><th class="num">Receita</th></tr>
            </thead>
            <tbody>
                @foreach($campaignRows as $c)
                    <tr>
                        <td style="font-weight:700;">{{ $c['name'] ?? '—' }}</td>
                        <td>{{ $c['source'] ?? '—' }}</td>
                        <td class="num">{{ $c['leads_count'] ?? 0 }}</td>
                        <td class="num">{{ $c['sales_count'] ?? 0 }}</td>
                        <td class="pct">{{ $c['conv'] ?? 0 }}%</td>
                        <td class="num">R$ {{ number_format($c['revenue'] ?? 0, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if($waClicksTotal > 0)
        <h3 class="sub">Botão WhatsApp <small>{{ $waClicksTotal }} cliques no período</small></h3>
        <div class="chart-card">
            <div class="chart-card-title">Cliques por dia</div>
            <div class="chart-wrapper sm"><canvas id="chartWaClicks"></canvas></div>
        </div>
        <div class="row-2col">
            <div>
                <h3 class="sub" style="margin-top:0;">Top fontes (UTM)</h3>
                <table class="data">
                    <thead><tr><th>Fonte</th><th class="num">Cliques</th></tr></thead>
                    <tbody>
                    @forelse($waClicksBySrc as $src => $total)
                        <tr><td>{{ $src }}</td><td class="num">{{ $total }}</td></tr>
                    @empty
                        <tr><td colspan="2" class="empty">—</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div>
                <h3 class="sub" style="margin-top:0;">Top páginas</h3>
                <table class="data">
                    <thead><tr><th>URL</th><th class="num">Cliques</th></tr></thead>
                    <tbody>
                    @forelse($waClicksByPg as $url => $total)
                        <tr><td style="font-size:11px;word-break:break-all;">{{ \Illuminate\Support\Str::limit($url, 60) }}</td><td class="num">{{ $total }}</td></tr>
                    @empty
                        <tr><td colspan="2" class="empty">—</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

{{-- ═════════════ 04 EQUIPE ═════════════ --}}
<div class="page">
    <div class="section-head">
        <div class="section-number">04</div>
        <h2 class="section-title">Equipe</h2>
        <p class="section-desc">Performance individual dos vendedores no período.</p>
    </div>

    <table class="data">
        <thead>
            <tr><th>Vendedor</th><th class="num">Leads</th><th class="num">Vendas</th><th class="num">Conv.</th><th class="num">Receita</th></tr>
        </thead>
        <tbody>
        @forelse($vendedores as $idx => $v)
            <tr>
                <td class="champion">
                    @if($idx === 0)<span class="star">★</span>@endif
                    {{ $v['user']['name'] ?? '—' }}
                </td>
                <td class="num">{{ $v['leads'] ?? 0 }}</td>
                <td class="num">{{ $v['vendas'] ?? 0 }}</td>
                <td class="pct">{{ $v['conv'] ?? 0 }}%</td>
                <td class="num">R$ {{ number_format($v['receita'] ?? 0, 0, ',', '.') }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="empty">Sem atividade no período.</td></tr>
        @endforelse
        </tbody>
    </table>

    <h3 class="sub">Atendimento WhatsApp</h3>
    <div class="kpi-grid">
        <div class="kpi">
            <div class="kpi-top"><span class="kpi-icon" style="background:#d1fae5;color:#059669;"><i class="bi bi-whatsapp"></i></span><span class="kpi-label">Conversas</span></div>
            <div class="kpi-value">{{ $waTotal }}</div>
            <div class="kpi-delta flat">no período</div>
        </div>
        <div class="kpi">
            <div class="kpi-top"><span class="kpi-icon"><i class="bi bi-person-check"></i></span><span class="kpi-label">Com lead</span></div>
            <div class="kpi-value">{{ $waComLead }}</div>
            <div class="kpi-delta flat">{{ $waTotal > 0 ? round($waComLead / $waTotal * 100) : 0 }}% do total</div>
        </div>
        <div class="kpi">
            <div class="kpi-top"><span class="kpi-icon" style="background:#dbeafe;color:#1d4ed8;"><i class="bi bi-check2-circle"></i></span><span class="kpi-label">Fechadas</span></div>
            <div class="kpi-value">{{ $waFechadas }}</div>
            <div class="kpi-delta flat">resolvidas</div>
        </div>
        <div class="kpi">
            <div class="kpi-top"><span class="kpi-icon" style="background:#fef3c7;color:#d97706;"><i class="bi bi-clock-history"></i></span><span class="kpi-label">1ª resposta</span></div>
            <div class="kpi-value">@if($avgFirstResp !== null){{ $avgFirstResp }}m @else — @endif</div>
            <div class="kpi-delta flat">tempo médio humano</div>
        </div>
    </div>
</div>

{{-- ═════════════ 05 PRODUTOS (condicional) ═════════════ --}}
@if(count($topProducts))
<div class="page">
    <div class="section-head">
        <div class="section-number">05</div>
        <h2 class="section-title">Produtos mais vendidos</h2>
        <p class="section-desc">Top 10 produtos por quantidade de vendas no período.</p>
    </div>

    <table class="data">
        <thead>
            <tr><th style="width:40px;">#</th><th>Produto</th><th class="num">Preço</th><th class="num">Vendas</th><th class="num">Receita</th></tr>
        </thead>
        <tbody>
            @foreach($topProducts as $idx => $prod)
                <tr>
                    <td>@if($idx === 0)<span class="star">★</span>@endif{{ $idx + 1 }}</td>
                    <td class="{{ $idx === 0 ? 'champion' : '' }}">{{ $prod['name'] ?? '—' }}</td>
                    <td class="num">R$ {{ number_format((float) ($prod['price'] ?? 0), 0, ',', '.') }}</td>
                    <td class="num">{{ $prod['won_count'] ?? 0 }}</td>
                    <td class="num">R$ {{ number_format((float) ($prod['total_value'] ?? 0), 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- ═════════════ 06 PERDAS (condicional) ═════════════ --}}
@if($totalLost > 0)
<div class="page">
    <div class="section-head">
        <div class="section-number">{{ count($topProducts) > 0 ? '06' : '05' }}</div>
        <h2 class="section-title">Leads perdidos</h2>
        <p class="section-desc">{{ $totalLost }} leads perdidos · valor potencial de R$ {{ number_format($lostValue, 0, ',', '.') }}</p>
    </div>

    <div class="row-2col">
        <div>
            <h3 class="sub" style="margin-top:0;">Por motivo</h3>
            <table class="data">
                <thead><tr><th>Motivo</th><th class="num">Qtd</th><th class="num">%</th></tr></thead>
                <tbody>
                @foreach($lostByReason as $r)
                    <tr><td>{{ $r['reason'] ?? '—' }}</td><td class="num">{{ $r['total'] ?? 0 }}</td><td class="pct">{{ $r['pct'] ?? 0 }}%</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div>
            <h3 class="sub" style="margin-top:0;">Por vendedor</h3>
            <table class="data">
                <thead><tr><th>Vendedor</th><th class="num">Qtd</th><th class="num">%</th></tr></thead>
                <tbody>
                @foreach($lostByVendor as $v)
                    <tr><td>{{ $v['user'] ?? '—' }}</td><td class="num">{{ $v['total'] ?? 0 }}</td><td class="pct">{{ $v['pct'] ?? 0 }}%</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

<script>
// Leads por dia
new Chart(document.getElementById('chartLeads'), {
    type: 'line',
    data: {
        labels: @json($data['chartDates'] ?? []),
        datasets: [{
            label: 'Leads',
            data: @json($data['chartLeads'] ?? []),
            fill: true,
            backgroundColor: 'rgba(0, 133, 243, 0.1)',
            borderColor: '#0085f3',
            borderWidth: 2.5,
            tension: 0.35,
            pointRadius: 3,
            pointBackgroundColor: '#0085f3',
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { precision: 0, font: { size: 11 } } },
            x: { ticks: { font: { size: 10 }, maxRotation: 0, autoSkip: true, maxTicksLimit: 10 } }
        }
    }
});

// Funil
new Chart(document.getElementById('chartFunnel'), {
    type: 'bar',
    data: {
        labels: ['Novos leads', 'Em aberto', 'Ganhos', 'Perdidos'],
        datasets: [{
            data: [{{ $totalLeads }}, {{ $data['funnelEmAberto'] ?? 0 }}, {{ $salesCount }}, {{ $totalLost }}],
            backgroundColor: ['#0085f3', '#f59e0b', '#10b981', '#ef4444'],
            borderRadius: 6,
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { x: { beginAtZero: true, ticks: { font: { size: 11 } } }, y: { ticks: { font: { size: 12, weight: 600 } } } }
    }
});

// Origem
@if(count($leadsBySource) > 0)
new Chart(document.getElementById('chartSources'), {
    type: 'doughnut',
    data: {
        labels: @json(collect($leadsBySource)->map(fn($r) => ucfirst($r['source'] ?? 'manual'))->values()),
        datasets: [{
            data: @json(collect($leadsBySource)->map(fn($r) => (int) ($r['total'] ?? 0))->values()),
            backgroundColor: ['#0085f3','#10b981','#ec4899','#6b7280','#f59e0b','#25d366','#8b5cf6','#14b8a6'],
            borderWidth: 2, borderColor: '#fff'
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false, cutout: '65%',
        plugins: { legend: { position: 'bottom', labels: { font: { size: 11 }, boxWidth: 12, padding: 10 } } }
    }
});
@endif

// Cliques WA
@if($waClicksTotal > 0)
new Chart(document.getElementById('chartWaClicks'), {
    type: 'bar',
    data: {
        labels: @json(array_map(fn($d) => \Carbon\Carbon::parse($d)->format('d/m'), array_keys($waClicksByDay))),
        datasets: [{
            data: @json(array_values($waClicksByDay)),
            backgroundColor: '#25d366',
            borderRadius: 4,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0, font: { size: 10 } } }, x: { ticks: { font: { size: 10 } } } }
    }
});
@endif
</script>
</body>
</html>

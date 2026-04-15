<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use App\Support\ChartUrlBuilder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reports)
    {
    }

    public function index(Request $request): View
    {
        $data = $this->reports->generate($request->only(['date_from', 'date_to', 'pipeline_id', 'user_id']));

        return view('tenant.reports.index', $data);
    }

    public function exportPdf(Request $request)
    {
        $data = $this->reports->generate($request->only(['date_from', 'date_to', 'pipeline_id', 'user_id']));

        // Gera URLs QuickChart pros gráficos do PDF (DomPDF não roda JS)
        $data['charts'] = $this->buildChartUrls($data);

        // Info de quem gerou + quando + tenant
        $data['generatedAt'] = now();
        $data['generatedBy'] = auth()->user()?->name ?? '—';
        $data['tenant']      = auth()->user()?->tenant;

        // Resolve nomes dos filtros aplicados (pipeline + user)
        $data['filterPipelineName'] = $data['filterPipeline']
            ? ($data['pipelines']->firstWhere('id', (int) $data['filterPipeline'])?->name ?? '—')
            : null;
        $data['filterUserName'] = $data['filterUser']
            ? (\App\Models\User::find($data['filterUser'])?->name ?? '—')
            : null;

        $pdf = Pdf::loadView('tenant.reports.pdf', $data)
            ->setPaper('a4', 'portrait')
            ->setOption('isRemoteEnabled', true)
            ->setOption('defaultFont', 'DejaVu Sans');

        $filename = 'relatorio-' . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Monta as URLs QuickChart com dados agregados do relatório.
     */
    private function buildChartUrls(array $d): array
    {
        // Leads por dia — line full width
        $leadsByDayUrl = ChartUrlBuilder::line(
            $d['chartLeads'],
            $d['chartDates'],
            'Leads',
            700,
            240,
        );

        // Leads por origem — doughnut
        $sourceLabels = [];
        $sourceData   = [];
        foreach ($d['leadsBySource'] as $row) {
            $sourceLabels[] = ucfirst((string) ($row->source ?? 'manual'));
            $sourceData[]   = (int) $row->total;
        }
        $leadsBySourceUrl = count($sourceData) > 0
            ? ChartUrlBuilder::doughnut($sourceData, $sourceLabels, null, 320, 260)
            : null;

        // WA cliques por dia — bar
        $waClicksUrl = null;
        if (! empty($d['waClicksByDay'])) {
            $labels = [];
            $values = [];
            foreach ($d['waClicksByDay'] as $day => $total) {
                $labels[] = \Carbon\Carbon::parse($day)->format('d/m');
                $values[] = (int) $total;
            }
            $waClicksUrl = ChartUrlBuilder::bar($values, $labels, 'Cliques', null, 700, 220);
        }

        // Funil de conversão — horizontal bar
        $funnelLabels = ['Novos leads', 'Em aberto', 'Vendas', 'Perdidos'];
        $funnelValues = [
            (int) $d['totalLeads'],
            (int) $d['funnelEmAberto'],
            (int) $d['salesCount'],
            (int) $d['totalLost'],
        ];
        $funnelUrl = ChartUrlBuilder::barHorizontal($funnelValues, $funnelLabels, 700, 200);

        return [
            'leadsByDay'     => $leadsByDayUrl,
            'leadsBySource'  => $leadsBySourceUrl,
            'waClicks'       => $waClicksUrl,
            'funnel'         => $funnelUrl,
        ];
    }
}

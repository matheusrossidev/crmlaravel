<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\GeneratedReport;
use App\Services\ReportService;
use App\Support\ChartUrlBuilder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reports)
    {
    }

    public function index(Request $request): View
    {
        $data = $this->reports->generate($request->only(['date_from', 'date_to', 'pipeline_id', 'user_id']));

        // Lista histórico (paginado) — carrega junto na 1ª render pra UX mais rápida
        $data['generatedReports'] = GeneratedReport::with('user:id,name')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return view('tenant.reports.index', $data);
    }

    /**
     * Cria um snapshot de relatório público com os filtros atuais.
     * Retorna o link público + flag de senha.
     */
    public function generate(Request $request): JsonResponse
    {
        $input = $request->validate([
            'date_from'   => 'nullable|date',
            'date_to'     => 'nullable|date',
            'pipeline_id' => 'nullable|integer|exists:pipelines,id',
            'user_id'     => 'nullable|integer|exists:users,id',
            'title'       => 'nullable|string|max:150',
            'password'    => 'nullable|string|min:4|max:100',
            'expires_in'  => 'nullable|in:7,30,90',
        ]);

        $filters = array_filter([
            'date_from'   => $input['date_from']   ?? null,
            'date_to'     => $input['date_to']     ?? null,
            'pipeline_id' => $input['pipeline_id'] ?? null,
            'user_id'     => $input['user_id']     ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        // Gera snapshot completo via service
        $snapshot = $this->reports->generate($filters);

        // Resolve nomes dos filtros pra exibir no relatório
        $snapshot['filterPipelineName'] = ! empty($filters['pipeline_id'])
            ? ($snapshot['pipelines']->firstWhere('id', (int) $filters['pipeline_id'])?->name ?? '—')
            : null;
        $snapshot['filterUserName'] = ! empty($filters['user_id'])
            ? (\App\Models\User::find($filters['user_id'])?->name ?? '—')
            : null;

        // Prepara snapshot pra serialização — converte Eloquent Collections em arrays simples
        $snapshot = $this->serializeSnapshot($snapshot);

        $expiresAt = null;
        if (! empty($input['expires_in'])) {
            $expiresAt = now()->addDays((int) $input['expires_in']);
        }

        $report = GeneratedReport::create([
            'tenant_id'      => activeTenantId(),
            'user_id'        => auth()->id(),
            'hash'           => GeneratedReport::generateUniqueHash(),
            'title'          => $input['title'] ?? null,
            'snapshot_json'  => $snapshot,
            'filters_json'   => $filters,
            'password_hash'  => ! empty($input['password']) ? $input['password'] : null, // cast 'hashed'
            'expires_at'     => $expiresAt,
        ]);

        return response()->json([
            'success'      => true,
            'hash'         => $report->hash,
            'url'          => $report->publicUrl(),
            'has_password' => $report->hasPassword(),
            'title'        => $report->title,
        ]);
    }

    /**
     * Lista histórico de relatórios gerados (AJAX pra refresh sem reload).
     */
    public function history(): JsonResponse
    {
        $reports = GeneratedReport::with('user:id,name')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn ($r) => [
                'id'             => $r->id,
                'hash'           => $r->hash,
                'title'          => $r->title,
                'url'            => $r->publicUrl(),
                'user_name'      => $r->user?->name ?? '—',
                'created_at'     => $r->created_at?->format('d/m/Y H:i'),
                'views_count'    => $r->views_count,
                'last_viewed_at' => $r->last_viewed_at?->format('d/m/Y H:i'),
                'has_password'   => $r->hasPassword(),
                'expires_at'     => $r->expires_at?->format('d/m/Y'),
                'is_expired'     => $r->isExpired(),
                'period_from'    => $r->filters_json['date_from'] ?? null,
                'period_to'      => $r->filters_json['date_to'] ?? null,
            ]);

        return response()->json(['reports' => $reports]);
    }

    public function deleteHistory(GeneratedReport $report): JsonResponse
    {
        // BelongsToTenant trait já filtra — se não era do tenant, 404
        $report->delete();
        return response()->json(['success' => true]);
    }

    /**
     * Atualiza/remove senha de um relatório existente.
     */
    public function updatePassword(Request $request, GeneratedReport $report): JsonResponse
    {
        $data = $request->validate([
            'password' => 'nullable|string|min:4|max:100',
        ]);

        $report->password_hash = ! empty($data['password']) ? $data['password'] : null;
        $report->save();

        return response()->json([
            'success'      => true,
            'has_password' => $report->hasPassword(),
        ]);
    }

    /**
     * Normaliza o snapshot pra serialização JSON estável.
     * Converte Eloquent Models/Collections em arrays puros.
     */
    private function serializeSnapshot(array $data): array
    {
        // Remove objetos Eloquent pesados — guardamos só os dados necessários pra render
        unset($data['pipelines']); // já resolvemos filterPipelineName

        $data['dateFrom'] = $data['dateFrom']?->toIso8601String();
        $data['dateTo']   = $data['dateTo']?->toIso8601String();

        // Normaliza collections pra arrays
        foreach (['leadsBySource', 'campaignRows', 'pipelineRows', 'lostByReason', 'lostByVendedor',
                  'vendedores', 'waMsgByUser', 'sourceConversion', 'teamActivity', 'topProducts'] as $key) {
            if (isset($data[$key]) && is_object($data[$key]) && method_exists($data[$key], 'toArray')) {
                $data[$key] = $data[$key]->toArray();
            }
        }

        return $data;
    }

    // ══ PDF legacy (mantido como fallback — será removido em cleanup) ══════

    public function exportPdf(Request $request)
    {
        $data = $this->reports->generate($request->only(['date_from', 'date_to', 'pipeline_id', 'user_id']));
        $data['charts']      = $this->buildChartUrls($data);
        $data['generatedAt'] = now();
        $user = \Illuminate\Support\Facades\Auth::user();
        $data['generatedBy'] = $user?->name ?? '—';
        $data['tenant']      = $user?->tenant;
        $data['filterPipelineName'] = $data['filterPipeline']
            ? ($data['pipelines']->firstWhere('id', (int) $data['filterPipeline'])?->name ?? '—')
            : null;
        $data['filterUserName'] = $data['filterUser']
            ? (\App\Models\User::find($data['filterUser'])?->name ?? '—')
            : null;

        $pdf = Pdf::loadView('tenant.reports.pdf', $data)->setPaper('a4', 'portrait');
        return $pdf->download('relatorio-' . now()->format('Y-m-d') . '.pdf');
    }

    private function buildChartUrls(array $d): array
    {
        $leadsByDayUrl = ChartUrlBuilder::line($d['chartLeads'], $d['chartDates'], 'Leads', 700, 240);

        $sourceLabels = [];
        $sourceData   = [];
        foreach ($d['leadsBySource'] as $row) {
            $sourceLabels[] = ucfirst((string) ($row->source ?? 'manual'));
            $sourceData[]   = (int) $row->total;
        }
        $leadsBySourceUrl = count($sourceData) > 0
            ? ChartUrlBuilder::doughnut($sourceData, $sourceLabels, null, 320, 260)
            : null;

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

        $funnelUrl = ChartUrlBuilder::barHorizontal(
            [(int) $d['totalLeads'], (int) $d['funnelEmAberto'], (int) $d['salesCount'], (int) $d['totalLost']],
            ['Novos leads', 'Em aberto', 'Vendas', 'Perdidos'],
            700,
            200,
        );

        return [
            'leadsByDay'    => $leadsByDayUrl,
            'leadsBySource' => $leadsBySourceUrl,
            'waClicks'      => $waClicksUrl,
            'funnel'        => $funnelUrl,
        ];
    }
}

@extends('tenant.layouts.app')

@php
    $title = __('forms.title');
    $pageIcon = 'ui-checks-grid';
@endphp

@push('styles')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
    .content-card-header h3 {
        font-size: 13.5px;font-weight: 600;color: #1a1d23;margin: 0;
        display: flex;align-items: center;gap: 8px;
        font-family: 'Plus Jakarta Sans', sans-serif;
    }
    .content-card-header h3 i { color: #007DFF; }

    .fx-kpi-grid {
        display: flex;gap: 14px;margin-bottom: 20px;overflow-x: auto;
        -webkit-overflow-scrolling: touch;scrollbar-width: none;padding-bottom: 2px;
    }
    .fx-kpi-grid::-webkit-scrollbar { display: none; }
    .fx-kpi-card {
        background: #fff;border-radius: 14px;padding: 16px 18px;
        border: 1px solid #e8eaf0;min-width: 170px;flex: 1;flex-shrink: 0;
    }
    .fx-grid-2 { display: grid;grid-template-columns: 1fr 1fr;gap: 20px;margin-bottom: 20px; }
    .fx-grid-3 { display: grid;grid-template-columns: 1.2fr 1.2fr 1fr;gap: 20px;margin-bottom: 20px; }
    @media (max-width: 1024px) { .fx-grid-3 { grid-template-columns: 1fr 1fr; } }
    @media (max-width: 768px) { .fx-grid-2, .fx-grid-3 { grid-template-columns: 1fr; } }

    .fx-type-badge { font-size:10px;font-weight:600;padding:2px 8px;border-radius:99px;display:inline-block; }
</style>
@endpush

@section('content')
<div class="page-container">

    <div style="margin-bottom:20px;">
        <div style="font-size:11px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:#97A3B7;margin-bottom:4px;">CRM</div>
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
            <div>
                <h1 style="font-family:'Plus Jakarta Sans',sans-serif;font-size:22px;font-weight:700;color:#1a1d23;margin:0 0 4px;">{{ __('forms.title') }}</h1>
                <p style="font-size:13.5px;color:#677489;margin:0;">{{ __('forms.subtitle') }}</p>
            </div>
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                <form method="GET" style="display:flex;gap:6px;align-items:center;">
                    <input type="date" name="from" value="{{ $dateFrom }}" style="padding:7px 10px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:12px;outline:none;">
                    <input type="date" name="to" value="{{ $dateTo }}" style="padding:7px 10px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:12px;outline:none;">
                    <button type="submit" class="btn-outline-sm" style="padding:7px 12px;"><i class="bi bi-funnel"></i></button>
                </form>
                <a href="{{ route('forms.create') }}" class="btn-primary-sm" style="text-decoration:none;">
                    <i class="bi bi-plus-lg"></i> {{ __('forms.new_form') }}
                </a>
            </div>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="fx-kpi-grid">
        @php
            $kpis = [
                ['icon' => 'ui-checks-grid', 'color' => 'blue',   'label' => __('forms.active_forms'),      'value' => $activeCount,      'valueColor' => '#1a1d23', 'sub' => $forms->count() . ' total'],
                ['icon' => 'inbox',          'color' => 'green',  'label' => __('forms.submissions_month'), 'value' => $totalSubmissions, 'valueColor' => '#10B981', 'sub' => null],
                ['icon' => 'eye',            'color' => 'purple', 'label' => __('forms.views'),             'value' => $totalViews,       'valueColor' => '#8B5CF6', 'sub' => null],
                ['icon' => 'graph-up-arrow', 'color' => 'orange', 'label' => __('forms.avg_conversion'),    'value' => $conversionRate . '%', 'valueColor' => '#F59E0B', 'sub' => null],
                ['icon' => 'person-check',   'color' => 'blue',   'label' => __('forms.leads_created'),     'value' => $leadsCreated,     'valueColor' => '#1a1d23', 'sub' => null],
            ];
            $iconBgs = ['blue' => '#eff6ff', 'green' => '#f0fdf4', 'red' => '#fef2f2', 'orange' => '#fffbeb', 'purple' => '#f5f3ff'];
            $iconColors = ['blue' => '#007DFF', 'green' => '#10B981', 'red' => '#EF4444', 'orange' => '#F59E0B', 'purple' => '#8B5CF6'];
        @endphp
        @foreach($kpis as $k)
        <div class="fx-kpi-card">
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

    {{-- Charts --}}
    <div class="fx-grid-3">
        <div class="content-card">
            <div class="content-card-header"><h3><i class="bi bi-graph-up"></i> {{ __('forms.submissions_trend') }}</h3></div>
            <div style="padding:16px 20px;"><canvas id="trendChart" height="200"></canvas></div>
        </div>
        <div class="content-card">
            <div class="content-card-header"><h3><i class="bi bi-bar-chart"></i> {{ __('forms.top_forms') }}</h3></div>
            <div style="padding:16px 20px;"><canvas id="topChart" height="200"></canvas></div>
        </div>
        <div class="content-card">
            <div class="content-card-header"><h3><i class="bi bi-pie-chart"></i> {{ __('forms.chart_by_mode') }}</h3></div>
            <div style="padding:16px 20px;"><canvas id="modeChart" height="200"></canvas></div>
        </div>
    </div>

    {{-- Forms list --}}
    <div class="content-card">
        <div class="content-card-header">
            <h3><i class="bi bi-list-ul"></i> {{ __('forms.configured_forms') }}</h3>
        </div>
        @if($forms->isEmpty())
            <div style="text-align:center;padding:60px 20px;color:#9ca3af;">
                <i class="bi bi-ui-checks-grid" style="font-size:48px;display:block;margin-bottom:12px;"></i>
                <p style="font-size:14px;margin-bottom:14px;">{{ __('forms.no_forms') }}</p>
                <a href="{{ route('forms.create') }}" class="btn-primary-sm" style="text-decoration:none;"><i class="bi bi-plus-lg"></i> {{ __('forms.new_form') }}</a>
            </div>
        @else
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:13.5px;">
                <thead>
                    <tr>
                        <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:600;color:#677489;border-bottom:1px solid #f0f2f7;">{{ __('forms.form_name') }}</th>
                        <th style="padding:10px 12px;text-align:left;font-size:11px;font-weight:600;color:#677489;border-bottom:1px solid #f0f2f7;">{{ __('forms.form_type') }}</th>
                        <th style="padding:10px 12px;text-align:center;font-size:11px;font-weight:600;color:#677489;border-bottom:1px solid #f0f2f7;">{{ __('forms.submissions') }}</th>
                        <th style="padding:10px 12px;text-align:center;font-size:11px;font-weight:600;color:#677489;border-bottom:1px solid #f0f2f7;">{{ __('forms.views') }}</th>
                        <th style="padding:10px 12px;text-align:center;font-size:11px;font-weight:600;color:#677489;border-bottom:1px solid #f0f2f7;">{{ __('forms.conversion') }}</th>
                        <th style="padding:10px 12px;text-align:center;font-size:11px;font-weight:600;color:#677489;border-bottom:1px solid #f0f2f7;">{{ __('forms.link_label') ?? 'Link' }}</th>
                        <th style="padding:10px 12px;text-align:center;font-size:11px;font-weight:600;color:#677489;border-bottom:1px solid #f0f2f7;">{{ __('forms.status_label') ?? 'Status' }}</th>
                        <th style="padding:10px 12px;border-bottom:1px solid #f0f2f7;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($forms as $form)
                    @php
                        $typeBg = match($form->type) {
                            'conversational' => 'background:#fef3c7;color:#92400e;',
                            'multistep' => 'background:#ede9fe;color:#5b21b6;',
                            default => 'background:#eff6ff;color:#3b82f6;',
                        };
                        $rate = $form->getConversionRate();
                    @endphp
                    <tr>
                        <td style="padding:12px 16px;border-bottom:1px solid #f7f8fa;">
                            <a href="{{ route('forms.edit', $form) }}" style="text-decoration:none;color:inherit;">
                                <div style="font-weight:600;color:#1a1d23;">{{ $form->name }}</div>
                                <div style="font-size:12px;color:#9ca3af;">{{ $form->created_at->format('d/m/Y') }}</div>
                            </a>
                        </td>
                        <td style="padding:12px 12px;border-bottom:1px solid #f7f8fa;">
                            <span class="fx-type-badge" style="{{ $typeBg }}">{{ __('forms.type_' . $form->type) }}</span>
                            @if($form->pipeline)
                                <div style="font-size:11px;color:#9ca3af;margin-top:3px;">{{ $form->pipeline->name }}</div>
                            @endif
                        </td>
                        <td style="padding:12px 12px;text-align:center;border-bottom:1px solid #f7f8fa;font-weight:700;">{{ $form->submissions_count }}</td>
                        <td style="padding:12px 12px;text-align:center;border-bottom:1px solid #f7f8fa;">{{ $form->views_count }}</td>
                        <td style="padding:12px 12px;text-align:center;border-bottom:1px solid #f7f8fa;font-weight:700;color:{{ $rate >= 20 ? '#10B981' : ($rate >= 5 ? '#F59E0B' : '#9ca3af') }};">{{ $rate }}%</td>
                        <td style="padding:12px 12px;text-align:center;border-bottom:1px solid #f7f8fa;">
                            <button onclick="copyLink('{{ $form->getPublicUrl() }}')" title="{{ __('forms.copy_link') }}"
                                style="background:#eff6ff;color:#0085f3;border:none;border-radius:6px;padding:5px 10px;cursor:pointer;font-size:12px;font-weight:600;">
                                <i class="bi bi-link-45deg"></i> {{ __('forms.copy_label') ?? 'Copiar' }}
                            </button>
                        </td>
                        <td style="padding:12px 12px;text-align:center;border-bottom:1px solid #f7f8fa;">
                            @if($form->is_active)
                                <span style="color:#10B981;font-weight:600;font-size:12px;"><i class="bi bi-check-circle-fill"></i> {{ __('forms.active') }}</span>
                            @else
                                <span style="color:#9ca3af;font-size:12px;"><i class="bi bi-x-circle"></i> {{ __('forms.inactive') }}</span>
                            @endif
                        </td>
                        <td style="padding:12px 12px;border-bottom:1px solid #f7f8fa;text-align:right;white-space:nowrap;">
                            <a href="{{ route('forms.builder', $form) }}" title="Builder" style="color:#0085f3;font-size:14px;padding:4px 6px;text-decoration:none;"><i class="bi bi-grid-3x3-gap"></i></a>
                            <a href="{{ route('forms.submissions', $form) }}" title="Envios" style="color:#0085f3;font-size:14px;padding:4px 6px;text-decoration:none;"><i class="bi bi-inbox"></i></a>
                            <a href="{{ route('forms.edit', $form) }}" title="Editar" style="color:#0085f3;font-size:14px;padding:4px 6px;text-decoration:none;"><i class="bi bi-pencil"></i></a>
                            <button onclick="deleteForm({{ $form->id }}, '{{ addslashes($form->name) }}')"
                                style="background:#fef2f2;color:#ef4444;border:none;border-radius:6px;padding:4px 8px;cursor:pointer;font-size:12px;">
                                <i class="bi bi-trash3"></i>
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

// Trend chart (line)
new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: {
        labels: {!! json_encode($trendLabels) !!},
        datasets: [{
            label: 'Envios',
            data: {!! json_encode($trendValues) !!},
            borderColor: '#0085f3', backgroundColor: 'rgba(0,133,243,0.1)',
            borderWidth: 2, fill: true, tension: 0.4, pointRadius: 3,
            pointBackgroundColor: '#fff', pointBorderColor: '#0085f3', pointBorderWidth: 2,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { display: false }, ticks: { font: { size: 10 }, maxTicksLimit: 10 } },
            y: { beginAtZero: true, grid: { color: '#f3f4f6' }, ticks: { stepSize: 1, font: { size: 11 } }, border: { display: false } }
        }
    }
});

// Top forms chart (bar)
new Chart(document.getElementById('topChart'), {
    type: 'bar',
    data: {
        labels: {!! json_encode($topForms->pluck('name')) !!},
        datasets: [{
            data: {!! json_encode($topForms->pluck('submissions_count')) !!},
            backgroundColor: '#0085f3', borderRadius: 6,
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { beginAtZero: true, grid: { color: '#f3f4f6' }, ticks: { stepSize: 1, font: { size: 11 } }, border: { display: false } },
            y: { grid: { display: false }, ticks: { font: { size: 11 } } }
        }
    }
});

// By-mode chart (doughnut)
new Chart(document.getElementById('modeChart'), {
    type: 'doughnut',
    data: {
        labels: {!! json_encode($byModeLabels) !!},
        datasets: [{
            data: {!! json_encode($byModeValues) !!},
            backgroundColor: ['#0085f3', '#10B981', '#8B5CF6'],
            borderWidth: 0,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false, cutout: '62%',
        plugins: {
            legend: { position: 'bottom', labels: { font: { size: 11 }, boxWidth: 12, padding: 10 } }
        }
    }
});

function copyLink(url) {
    navigator.clipboard.writeText(url).then(() => toastr.success('{{ __("forms.link_copied") }}'));
}

function deleteForm(id, name) {
    if (!confirm('Excluir "' + name + '"?')) return;
    fetch('{{ url("formularios") }}/' + id, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' },
    }).then(r => r.json()).then(d => { if (d.success) location.reload(); });
}
</script>
@endpush

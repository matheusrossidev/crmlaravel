@extends('tenant.layouts.app')

@php
    $title = __('forms.title');
    $pageIcon = 'bi-ui-checks-grid';
@endphp

@section('content')
<div class="page-container">
    <div style="margin-bottom:20px;">
        <div style="font-size:11px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:#97A3B7;margin-bottom:4px;">CRM</div>
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
            <div>
                <h1 style="font-family:'Plus Jakarta Sans',sans-serif;font-size:22px;font-weight:700;color:#1a1d23;margin:0 0 4px;">{{ __('forms.title') }}</h1>
                <p style="font-size:13.5px;color:#677489;margin:0;">{{ __('forms.subtitle') }}</p>
            </div>
            <a href="{{ route('forms.create') }}" class="btn-primary-sm" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
                <i class="bi bi-plus-lg"></i> {{ __('forms.new_form') }}
            </a>
        </div>
    </div>

    {{-- Métricas --}}
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px;">
        <div style="background:#fff;border:1.5px solid #e8eaf0;border-radius:14px;padding:20px;">
            <div style="font-size:12px;color:#6b7280;font-weight:600;">{{ __('forms.active_forms') }}</div>
            <div style="font-size:28px;font-weight:800;color:#1a1d23;">{{ $activeCount }}</div>
        </div>
        <div style="background:#fff;border:1.5px solid #e8eaf0;border-radius:14px;padding:20px;">
            <div style="font-size:12px;color:#6b7280;font-weight:600;">{{ __('forms.submissions_month') }}</div>
            <div style="font-size:28px;font-weight:800;color:#1a1d23;">{{ $submissionsMonth }}</div>
        </div>
        <div style="background:#fff;border:1.5px solid #e8eaf0;border-radius:14px;padding:20px;">
            <div style="font-size:12px;color:#6b7280;font-weight:600;">{{ __('forms.avg_conversion') }}</div>
            <div style="font-size:28px;font-weight:800;color:#1a1d23;">
                @php
                    $totalViews = $forms->sum('views_count');
                    $totalSubs = $forms->sum('submissions_count');
                    $avgRate = $totalViews > 0 ? round(($totalSubs / $totalViews) * 100, 1) : 0;
                @endphp
                {{ $avgRate }}%
            </div>
        </div>
    </div>

    {{-- Cards --}}
    @if($forms->isEmpty())
        <div style="text-align:center;padding:60px 20px;color:#9ca3af;">
            <i class="bi bi-ui-checks-grid" style="font-size:48px;display:block;margin-bottom:12px;"></i>
            <p style="font-size:14px;">{{ __('forms.no_forms') }}</p>
        </div>
    @else
        <div style="display:grid;gap:12px;">
            @foreach($forms as $form)
            <div style="background:#fff;border:1.5px solid #e8eaf0;border-radius:14px;padding:18px 22px;display:flex;align-items:center;gap:16px;">
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px;">
                        <span style="font-size:14px;font-weight:700;color:#1a1d23;">{{ $form->name }}</span>
                        <span style="font-size:10px;font-weight:600;padding:2px 8px;border-radius:99px;{{ $form->is_active ? 'background:#ecfdf5;color:#059669;' : 'background:#f3f4f6;color:#6b7280;' }}">
                            {{ $form->is_active ? __('forms.active') : __('forms.inactive') }}
                        </span>
                        <span style="font-size:10px;font-weight:600;padding:2px 8px;border-radius:99px;background:#eff6ff;color:#3b82f6;">
                            {{ __('forms.type_' . $form->type) }}
                        </span>
                    </div>
                    <div style="font-size:12px;color:#6b7280;">
                        {{ $form->submissions_count }} {{ __('forms.submissions') }}
                        · {{ $form->views_count }} {{ __('forms.views') }}
                        · {{ $form->getConversionRate() }}% {{ __('forms.conversion') }}
                        @if($form->pipeline)
                            · {{ $form->pipeline->name }}
                        @endif
                        · {{ __('forms.created_at') }} {{ $form->created_at->format('d/m/Y') }}
                    </div>
                </div>
                <div style="display:flex;gap:6px;align-items:center;">
                    <button onclick="navigator.clipboard.writeText('{{ $form->getPublicUrl() }}');toastr.success('{{ __('forms.link_copied') }}')" style="background:none;border:none;color:#0085f3;cursor:pointer;font-size:14px;padding:6px;" title="{{ __('forms.copy_link') }}">
                        <i class="bi bi-link-45deg"></i>
                    </button>
                    <a href="{{ route('forms.builder', $form) }}" style="color:#0085f3;font-size:14px;padding:6px;" title="Builder">
                        <i class="bi bi-grid-3x3-gap"></i>
                    </a>
                    <a href="{{ route('forms.submissions', $form) }}" style="color:#6b7280;font-size:14px;padding:6px;" title="{{ __('forms.submissions') }}">
                        <i class="bi bi-list-ul"></i>
                    </a>
                    <a href="{{ route('forms.edit', $form) }}" style="color:#6b7280;font-size:14px;padding:6px;" title="{{ __('common.edit') }}">
                        <i class="bi bi-pencil"></i>
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>
@endsection

@extends('tenant.layouts.app')

@php
    $title = __('forms.submissions_title', ['name' => $form->name]);
    $pageIcon = 'bi-list-ul';
@endphp

@section('content')
<div class="page-container">
    <div style="margin-bottom:20px;">
        <div style="font-size:11px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:#97A3B7;margin-bottom:4px;">CRM</div>
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
            <div>
                <h1 style="font-family:'Plus Jakarta Sans',sans-serif;font-size:22px;font-weight:700;color:#1a1d23;margin:0 0 4px;">{{ __('forms.submissions_title', ['name' => $form->name]) }}</h1>
                <p style="font-size:13.5px;color:#677489;margin:0;">{{ $submissions->total() }} {{ __('forms.submissions') }}</p>
            </div>
            <div style="display:flex;gap:8px;">
                <a href="{{ route('forms.edit', $form) }}" class="btn-primary-sm" style="background:#f3f4f6;color:#374151;border:1px solid #e5e7eb;text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
                    <i class="bi bi-arrow-left"></i> {{ __('common.edit') }}
                </a>
                <a href="{{ route('forms.submissions.export', $form) }}" class="btn-primary-sm" style="background:#eff6ff;color:#0085f3;border:1.5px solid #bfdbfe;text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
                    <i class="bi bi-download"></i> {{ __('forms.export_csv') }}
                </a>
        </div>
    </div>

    @if($submissions->isEmpty())
        <div style="text-align:center;padding:60px 20px;color:#9ca3af;">
            <i class="bi bi-inbox" style="font-size:48px;display:block;margin-bottom:12px;"></i>
            <p>{{ __('forms.no_submissions') }}</p>
        </div>
    @else
        <div style="background:#fff;border:1.5px solid #e8eaf0;border-radius:14px;overflow:hidden;">
            <table style="width:100%;border-collapse:collapse;font-size:13px;">
                <thead>
                    <tr style="background:#f9fafb;border-bottom:1px solid #e8eaf0;">
                        <th style="padding:12px 16px;text-align:left;font-weight:600;color:#6b7280;">#</th>
                        <th style="padding:12px 16px;text-align:left;font-weight:600;color:#6b7280;">{{ __('forms.lead') }}</th>
                        @foreach(($form->fields ?? []) as $field)
                            @if(!in_array($field['type'], ['heading', 'divider']))
                            <th style="padding:12px 16px;text-align:left;font-weight:600;color:#6b7280;">{{ $field['label'] ?? $field['id'] }}</th>
                            @endif
                        @endforeach
                        <th style="padding:12px 16px;text-align:left;font-weight:600;color:#6b7280;">{{ __('forms.date') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($submissions as $sub)
                    <tr style="border-bottom:1px solid #f0f2f7;">
                        <td style="padding:10px 16px;color:#9ca3af;">{{ $sub->id }}</td>
                        <td style="padding:10px 16px;">
                            @if($sub->lead)
                                <a href="{{ route('leads.show', $sub->lead_id) }}" style="color:#0085f3;text-decoration:none;font-weight:600;">{{ $sub->lead->name }}</a>
                            @else
                                <span style="color:#9ca3af;">—</span>
                            @endif
                        </td>
                        @foreach(($form->fields ?? []) as $field)
                            @if(!in_array($field['type'], ['heading', 'divider']))
                            <td style="padding:10px 16px;color:#374151;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                {{ $sub->data[$field['id']] ?? '—' }}
                            </td>
                            @endif
                        @endforeach
                        <td style="padding:10px 16px;color:#6b7280;">{{ $sub->submitted_at?->format('d/m/Y H:i') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div style="margin-top:16px;">
            {{ $submissions->links() }}
        </div>
    @endif
</div>
@endsection

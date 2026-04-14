@extends('tenant.layouts.app')

@php
    $title    = $template->name;
    $pageIcon = 'chat-dots';

    $body   = '';
    $header = null;
    $footer = '';
    $buttons = [];
    foreach ((array) $template->components as $c) {
        $type = strtoupper($c['type'] ?? '');
        if ($type === 'BODY')   $body   = $c['text'] ?? '';
        if ($type === 'HEADER') $header = $c;
        if ($type === 'FOOTER') $footer = $c['text'] ?? '';
        if ($type === 'BUTTONS') $buttons = $c['buttons'] ?? [];
    }
@endphp

@push('styles')
<style>
    .show-grid { display: grid; grid-template-columns: 1.2fr 1fr; gap: 20px; align-items: start; }
    @media (max-width: 900px) { .show-grid { grid-template-columns: 1fr; } }

    .card {
        background: #fff; border: 1px solid #e8eaf0;
        border-radius: 14px; overflow: hidden;
    }
    .card-head {
        padding: 14px 20px;
        border-bottom: 1px solid #f0f2f7;
        font-size: 14px; font-weight: 700; color: #1a1d23;
    }
    .card-body { padding: 18px 20px; }

    .meta-row {
        display: flex; justify-content: space-between; align-items: center;
        padding: 10px 0;
        border-bottom: 1px dashed #f0f2f7;
        font-size: 13px;
    }
    .meta-row:last-child { border-bottom: none; }
    .meta-row .label { color: #6b7280; font-weight: 500; }
    .meta-row .value { color: #1a1d23; font-weight: 600; }

    .badge-pill {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 3px 10px; border-radius: 20px;
        font-size: 11.5px; font-weight: 600;
    }
    .badge-st-APPROVED { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }
    .badge-st-PENDING  { background: #fefce8; color: #ca8a04; border: 1px solid #fde68a; }
    .badge-st-REJECTED { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
    .badge-st-PAUSED,
    .badge-st-DISABLED { background: #f3f4f6; color: #6b7280; border: 1px solid #e5e7eb; }

    .wa-bubble-wrap {
        background: #e5ddd5;
        padding: 24px 18px;
        border-radius: 12px;
        min-height: 360px;
    }
    .wa-bubble {
        background: #dcf8c6;
        border-radius: 12px;
        padding: 10px 13px;
        font-size: 14px;
        line-height: 1.45;
        max-width: 86%;
        box-shadow: 0 1px 2px rgba(0,0,0,.12);
        position: relative;
        color: #1a1d23;
    }
    .wa-bubble .wa-header {
        font-weight: 700;
        margin-bottom: 6px;
        color: #0f172a;
    }
    .wa-bubble .wa-body { white-space: pre-wrap; word-wrap: break-word; }
    .wa-bubble .wa-footer {
        font-size: 12px;
        color: #64748b;
        margin-top: 6px;
    }
    .wa-bubble .wa-buttons {
        display: flex; flex-direction: column; gap: 3px;
        margin-top: 8px;
        border-top: 1px solid rgba(0,0,0,.07);
        padding-top: 6px;
    }
    .wa-bubble .wa-btn {
        text-align: center;
        color: #0085f3;
        font-size: 14px;
        padding: 6px 0;
        cursor: pointer;
    }

    .rejected-box {
        background: #fef2f2;
        border: 1px solid #fecaca;
        border-radius: 10px;
        padding: 12px 14px;
        color: #991b1b;
        font-size: 13px;
        margin-bottom: 18px;
    }

    .btn-danger-outline {
        background: #fff; color: #dc2626;
        border: 1.5px solid #fecaca;
        padding: 9px 16px; border-radius: 9px;
        font-size: 13px; font-weight: 600;
        display: inline-flex; align-items: center; gap: 7px;
        cursor: pointer; transition: all .15s;
    }
    .btn-danger-outline:hover { background: #fee2e2; }
</style>
@endpush

@section('content')
<div class="page-container">

    @include('tenant.settings._tabs')

    <div style="margin-bottom:20px;display:flex;align-items:center;gap:12px;">
        <a href="{{ route('settings.whatsapp-templates.index') }}" style="color:#6b7280;text-decoration:none;">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h1 style="font-family:'Plus Jakarta Sans',sans-serif;font-size:22px;font-weight:700;color:#1a1d23;margin:0;">
            {{ $template->name }}
        </h1>
        <span class="badge-pill badge-st-{{ $template->status }}">
            @switch($template->status)
                @case('APPROVED')  {{ __('wa_templates.status_approved') }} @break
                @case('PENDING')   {{ __('wa_templates.status_pending') }} @break
                @case('REJECTED')  {{ __('wa_templates.status_rejected') }} @break
                @case('PAUSED')    {{ __('wa_templates.status_paused') }} @break
                @case('DISABLED')  {{ __('wa_templates.status_disabled') }} @break
                @default           {{ $template->status }}
            @endswitch
        </span>
    </div>

    @if($template->isRejected() && $template->rejected_reason)
        <div class="rejected-box">
            <strong>{{ __('wa_templates.show_rejected_reason') }}:</strong> {{ $template->rejected_reason }}
        </div>
    @endif

    <div class="show-grid">
        <div>
            <div class="card">
                <div class="card-head">{{ __('wa_templates.preview_title') }}</div>
                <div class="card-body" style="padding: 0;">
                    <div class="wa-bubble-wrap">
                        <div class="wa-bubble">
                            @if($header)
                                @if(strtoupper($header['format'] ?? 'TEXT') === 'TEXT')
                                    <div class="wa-header">{{ $header['text'] ?? '' }}</div>
                                @else
                                    <div style="background:rgba(0,0,0,.06);border-radius:8px;padding:10px;text-align:center;color:#64748b;font-size:12px;margin-bottom:6px;">
                                        <i class="bi bi-image"></i> {{ strtolower($header['format']) }}
                                    </div>
                                @endif
                            @endif
                            <div class="wa-body">{{ $body }}</div>
                            @if($footer)
                                <div class="wa-footer">{{ $footer }}</div>
                            @endif
                            @if(count($buttons))
                                <div class="wa-buttons">
                                    @foreach($buttons as $b)
                                        <div class="wa-btn">{{ $b['text'] ?? '' }}</div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="card">
                <div class="card-head">Informações</div>
                <div class="card-body">
                    <div class="meta-row">
                        <span class="label">{{ __('wa_templates.col_language') }}</span>
                        <span class="value">{{ $template->language }}</span>
                    </div>
                    <div class="meta-row">
                        <span class="label">{{ __('wa_templates.col_category') }}</span>
                        <span class="value">
                            @switch($template->category)
                                @case('UTILITY')        {{ __('wa_templates.cat_utility') }} @break
                                @case('MARKETING')      {{ __('wa_templates.cat_marketing') }} @break
                                @case('AUTHENTICATION') {{ __('wa_templates.cat_authentication') }} @break
                            @endswitch
                        </span>
                    </div>
                    <div class="meta-row">
                        <span class="label">{{ __('wa_templates.col_instance') }}</span>
                        <span class="value">{{ $template->instance?->label ?: $template->instance?->phone_number ?: '—' }}</span>
                    </div>
                    @if($template->quality_rating)
                        <div class="meta-row">
                            <span class="label">{{ __('wa_templates.show_quality') }}</span>
                            <span class="value">{{ $template->quality_rating }}</span>
                        </div>
                    @endif
                    @if($template->meta_template_id)
                        <div class="meta-row">
                            <span class="label">{{ __('wa_templates.show_meta_id') }}</span>
                            <span class="value" style="font-family:monospace;font-size:11.5px;">{{ $template->meta_template_id }}</span>
                        </div>
                    @endif
                    <div class="meta-row">
                        <span class="label">{{ __('wa_templates.show_last_sync') }}</span>
                        <span class="value">{{ $template->last_synced_at?->diffForHumans() ?? '—' }}</span>
                    </div>
                </div>
            </div>

            <div style="margin-top:18px;text-align:right;">
                <form method="POST" action="{{ route('settings.whatsapp-templates.destroy', $template) }}"
                      onsubmit="return confirm('{{ __('wa_templates.show_delete_confirm') }}');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-danger-outline">
                        <i class="bi bi-trash"></i> {{ __('wa_templates.show_delete') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

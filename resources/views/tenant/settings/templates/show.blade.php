@extends('tenant.layouts.app')

@php
    $title    = $template->name;
    $pageIcon = 'chat-dots';

    $body    = '';
    $header  = null;
    $footer  = '';
    $buttons = [];
    foreach ((array) $template->components as $c) {
        $type = strtoupper($c['type'] ?? '');
        if ($type === 'BODY')    $body    = $c['text'] ?? '';
        if ($type === 'HEADER')  $header  = $c;
        if ($type === 'FOOTER')  $footer  = $c['text'] ?? '';
        if ($type === 'BUTTONS') $buttons = $c['buttons'] ?? [];
    }
    $headerFormat = $header ? strtoupper($header['format'] ?? 'TEXT') : null;
    $headerSample = $header['example']['header_handle'][0] ?? null;
@endphp

@push('styles')
<style>
    .show-grid { display: grid; grid-template-columns: 70% 30%; gap: 24px; }
    @media (max-width: 900px) { .show-grid { grid-template-columns: 1fr; } }

    .card {
        background: #fff; border: 1px solid #e8eaf0;
        border-radius: 14px; overflow: hidden;
        margin-bottom: 16px;
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

    /* iPhone clay — mesma estética do create.blade.php */
    .preview-sticky { position: sticky; top: 80px; align-self: start; }
    .iphone-frame {
        width: 100%;
        max-width: 280px;
        margin: 0 auto;
        aspect-ratio: 9 / 18.5;
        background: linear-gradient(145deg, #e6e9f0 0%, #d7dce8 100%);
        border-radius: 38px;
        padding: 10px;
        box-shadow:
            0 30px 50px -20px rgba(15, 23, 42, .25),
            0 12px 24px -8px rgba(15, 23, 42, .15),
            inset 0 2px 4px rgba(255, 255, 255, .6),
            inset 0 -2px 4px rgba(15, 23, 42, .08);
        position: relative;
    }
    .iphone-frame::before {
        content: '';
        position: absolute;
        top: 14px; left: 50%; transform: translateX(-50%);
        width: 72px; height: 20px;
        background: #1a1d23;
        border-radius: 14px;
        z-index: 3;
    }
    .iphone-screen {
        width: 100%; height: 100%;
        border-radius: 30px;
        overflow: hidden;
        background: #f0f2f5;
        position: relative;
        display: flex;
        flex-direction: column;
    }
    .iphone-topbar {
        flex-shrink: 0;
        background: #075e54;
        padding: 36px 14px 8px;
        display: flex; align-items: center; gap: 9px;
        color: #fff;
    }
    .iphone-topbar .avatar {
        width: 28px; height: 28px; border-radius: 50%;
        background: rgba(255, 255, 255, .25);
        display: flex; align-items: center; justify-content: center;
        font-size: 12px; font-weight: 700;
    }
    .iphone-topbar .contact-name { font-size: 11.5px; font-weight: 600; line-height: 1.2; }
    .iphone-topbar .contact-status { font-size: 9px; opacity: .8; }

    .wa-bubble-wrap {
        flex: 1;
        background-image: url('{{ asset('images/mocks/whatsapp-background.png') }}');
        background-size: cover;
        background-position: center;
        padding: 14px 10px;
        overflow-y: auto;
    }
    .wa-bubble {
        background: #dcf8c6;
        border-radius: 8px;
        padding: 7px 9px 5px;
        font-size: 11.5px;
        line-height: 1.38;
        max-width: 88%;
        box-shadow: 0 1px 1px rgba(0, 0, 0, .12);
        color: #1a1d23;
        position: relative;
        margin-left: auto;
    }
    .wa-bubble::after {
        content: '';
        position: absolute;
        right: -5px; top: 0;
        width: 0; height: 0;
        border-top: 8px solid #dcf8c6;
        border-right: 6px solid transparent;
    }
    .wa-header { font-weight: 700; margin-bottom: 4px; color: #0f172a; font-size: 11.5px; }
    .wa-body   { white-space: pre-wrap; word-wrap: break-word; }
    .wa-footer { font-size: 9.5px; color: #64748b; margin-top: 4px; }
    .wa-buttons {
        display: flex; flex-direction: column; gap: 2px;
        margin-top: 6px;
        border-top: 1px solid rgba(0,0,0,.07);
        padding-top: 4px;
    }
    .wa-btn { text-align: center; color: #0085f3; font-size: 11px; padding: 4px 0; font-weight: 500; }

    .rejected-box {
        background: #fef2f2;
        border: 1px solid #fecaca;
        border-radius: 10px;
        padding: 12px 14px;
        color: #991b1b;
        font-size: 13px;
        margin-bottom: 18px;
    }

    .info-box {
        background: #fff7ed;
        border: 1px solid #fed7aa;
        border-radius: 10px;
        padding: 10px 12px;
        color: #92400e;
        font-size: 12px;
        margin-bottom: 14px;
        line-height: 1.45;
    }
    .info-box i { margin-right: 4px; }

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

    <div style="margin-bottom:20px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
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
                                @default                {{ $template->category }}
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

            {{-- Aviso sobre re-classificação automática da Meta --}}
            <div class="info-box">
                <i class="bi bi-info-circle"></i>
                <strong>Categoria mudou sozinha?</strong> A Meta pode re-classificar templates automaticamente com base no conteúdo
                (ex: UTILITY → MARKETING se o corpo parecer promocional). Isso protege você de cobrança errada —
                o status real é sempre o que aparece aqui após sincronizar.
                <a href="https://developers.facebook.com/docs/whatsapp/updates-to-pricing/new-template-guidelines#template-category-changes"
                   target="_blank" style="color:#0085f3;text-decoration:none;">Doc Meta</a>.
            </div>

            <div style="text-align:right;">
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

        <div>
            <div class="preview-sticky">
                <div style="font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#97A3B7;text-align:center;margin-bottom:14px;">
                    {{ __('wa_templates.preview_title') }}
                </div>
                <div class="iphone-frame">
                    <div class="iphone-screen">
                        <div class="iphone-topbar">
                            <div class="avatar"><i class="bi bi-person-fill"></i></div>
                            <div>
                                <div class="contact-name">Cliente</div>
                                <div class="contact-status">online</div>
                            </div>
                        </div>
                        <div class="wa-bubble-wrap">
                            <div class="wa-bubble">
                                @if($header)
                                    @if($headerFormat === 'TEXT')
                                        <div class="wa-header">{{ $header['text'] ?? '' }}</div>
                                    @elseif($headerSample && $headerFormat === 'IMAGE')
                                        <img src="{{ $headerSample }}" style="width:100%;border-radius:4px;display:block;margin-bottom:4px;" alt="">
                                    @else
                                        @php
                                            $ico = $headerFormat === 'VIDEO' ? 'bi-camera-video'
                                                 : ($headerFormat === 'DOCUMENT' ? 'bi-file-earmark-text' : 'bi-image');
                                        @endphp
                                        <div style="background:rgba(0,0,0,.06);border-radius:6px;padding:10px;text-align:center;color:#64748b;font-size:10px;margin-bottom:4px;">
                                            <i class="bi {{ $ico }}" style="font-size:18px;"></i>
                                            <div>{{ strtolower($headerFormat) }}</div>
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
                <div style="font-size:11.5px;color:#9ca3af;text-align:center;margin-top:14px;line-height:1.4;">
                    {{ __('wa_templates.preview_hint') }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

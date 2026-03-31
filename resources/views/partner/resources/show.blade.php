@extends('tenant.layouts.app')
@php $title = $resource->title; $pageIcon = 'file-earmark-richtext'; @endphp

@push('styles')
<style>
.res-page { max-width: 820px; margin: 0 auto; padding: 0 24px; }
.res-back { font-size: 13px; color: #0085f3; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; margin-bottom: 24px; }
.res-back:hover { text-decoration: underline; }

.res-cover { width: 100%; height: 280px; object-fit: cover; border-radius: 16px; margin-bottom: 28px; }
.res-cover-ph { width: 100%; height: 200px; background: linear-gradient(135deg, #eff6ff, #f5f3ff); border-radius: 16px; margin-bottom: 28px; display: flex; align-items: center; justify-content: center; color: #0085f3; font-size: 56px; }

.res-header { margin-bottom: 28px; }
.res-cat { font-size: 11px; font-weight: 700; color: #0085f3; text-transform: uppercase; letter-spacing: .06em; margin-bottom: 8px; }
.res-title { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 26px; font-weight: 800; color: #1a1d23; margin: 0 0 10px; line-height: 1.3; }
.res-desc { font-size: 15px; color: #6b7280; line-height: 1.6; margin: 0; }

.res-content { background: #fff; border: 1.5px solid #e8eaf0; border-radius: 16px; padding: 32px 36px; line-height: 1.85; font-size: 15px; color: #374151; margin-bottom: 24px; }
.res-content h2 { font-size: 20px; font-weight: 700; color: #1a1d23; margin: 28px 0 12px; }
.res-content h3 { font-size: 17px; font-weight: 700; color: #1a1d23; margin: 24px 0 10px; }
.res-content p { margin: 0 0 14px; }
.res-content ul, .res-content ol { margin: 0 0 14px; padding-left: 20px; }
.res-content li { margin-bottom: 6px; }
.res-content strong { color: #1a1d23; }
.res-content a { color: #0085f3; }
.res-content img { max-width: 100%; border-radius: 10px; margin: 16px 0; }

/* Downloads */
.res-downloads { background: #fff; border: 1.5px solid #e8eaf0; border-radius: 16px; overflow: hidden; }
.res-downloads-header { padding: 16px 24px; border-bottom: 1px solid #f0f2f7; display: flex; align-items: center; gap: 8px; }
.res-downloads-header i { color: #0085f3; font-size: 16px; }
.res-downloads-header h3 { font-size: 14px; font-weight: 700; color: #1a1d23; margin: 0; }
.res-downloads-header span { font-size: 12px; color: #97A3B7; margin-left: auto; }
.res-dl-list { padding: 8px 12px; }
.res-dl-item { display: flex; align-items: center; gap: 14px; padding: 14px 12px; border-radius: 10px; text-decoration: none; color: #374151; transition: background .12s; }
.res-dl-item:hover { background: #f0f6ff; }
.res-dl-icon { width: 40px; height: 40px; border-radius: 10px; background: #eff6ff; color: #0085f3; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
.res-dl-info { flex: 1; min-width: 0; }
.res-dl-name { font-size: 14px; font-weight: 600; color: #1a1d23; }
.res-dl-meta { font-size: 12px; color: #97A3B7; margin-top: 2px; }
.res-dl-btn { padding: 7px 16px; background: #0085f3; color: #fff; border: none; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 5px; flex-shrink: 0; transition: background .12s; }
.res-dl-btn:hover { background: #0070d1; }

/* Empty downloads placeholder */
.res-dl-empty { padding: 24px; text-align: center; }
.res-dl-empty-items { display: flex; flex-direction: column; gap: 10px; margin-top: 16px; }
.res-dl-placeholder { display: flex; align-items: center; gap: 12px; padding: 12px 14px; background: #f8fafc; border: 1.5px dashed #e5e7eb; border-radius: 10px; }
.res-dl-placeholder .icon { width: 36px; height: 36px; border-radius: 8px; background: #f3f4f6; display: flex; align-items: center; justify-content: center; color: #d1d5db; font-size: 16px; flex-shrink: 0; }
.res-dl-placeholder .text { flex: 1; }
.res-dl-placeholder .name { font-size: 13px; font-weight: 600; color: #9ca3af; }
.res-dl-placeholder .size { font-size: 11px; color: #d1d5db; }
.res-dl-placeholder .btn-ph { padding: 5px 12px; background: #f3f4f6; color: #9ca3af; border: none; border-radius: 6px; font-size: 11px; font-weight: 600; cursor: default; }
</style>
@endpush

@section('content')
<div class="res-page">
    <a href="{{ route('partner.resources.index') }}" class="res-back">
        <i class="bi bi-arrow-left"></i> {{ __('partner.back_to_resources') }}
    </a>

    {{-- Cover --}}
    @if($resource->cover_image)
        <img src="{{ asset('storage/' . $resource->cover_image) }}" class="res-cover" alt="{{ $resource->title }}">
    @else
        <div class="res-cover-ph"><i class="bi bi-file-earmark-richtext"></i></div>
    @endif

    {{-- Header --}}
    <div class="res-header">
        @if($resource->category) <div class="res-cat">{{ $resource->category }}</div> @endif
        <h1 class="res-title">{{ $resource->title }}</h1>
        @if($resource->description) <p class="res-desc">{{ $resource->description }}</p> @endif
    </div>

    {{-- Content --}}
    @if($resource->content)
        <div class="res-content">{!! $resource->content !!}</div>
    @endif

    {{-- Downloads --}}
    <div class="res-downloads">
        <div class="res-downloads-header">
            <i class="bi bi-download"></i>
            <h3>Downloads</h3>
            @if($resource->attachments && count($resource->attachments) > 0)
                <span>{{ count($resource->attachments) }} arquivo(s)</span>
            @endif
        </div>

        @if($resource->attachments && count($resource->attachments) > 0)
            <div class="res-dl-list">
                @foreach($resource->attachments as $att)
                    @php
                        $ext = pathinfo($att['name'] ?? '', PATHINFO_EXTENSION);
                        $icon = match(strtolower($ext)) {
                            'pdf' => 'bi-file-earmark-pdf-fill',
                            'xls', 'xlsx' => 'bi-file-earmark-excel-fill',
                            'doc', 'docx' => 'bi-file-earmark-word-fill',
                            'ppt', 'pptx' => 'bi-file-earmark-ppt-fill',
                            'zip', 'rar' => 'bi-file-earmark-zip-fill',
                            'png', 'jpg', 'jpeg', 'gif', 'webp' => 'bi-file-earmark-image-fill',
                            default => 'bi-file-earmark-fill',
                        };
                    @endphp
                    <a href="{{ asset('storage/' . ($att['path'] ?? '')) }}" class="res-dl-item" download>
                        <div class="res-dl-icon"><i class="bi {{ $icon }}"></i></div>
                        <div class="res-dl-info">
                            <div class="res-dl-name">{{ $att['name'] ?? 'Arquivo' }}</div>
                            <div class="res-dl-meta">{{ strtoupper($ext) }} @if(isset($att['size'])) · {{ $att['size'] }} @endif</div>
                        </div>
                        <button class="res-dl-btn"><i class="bi bi-download"></i> Baixar</button>
                    </a>
                @endforeach
            </div>
        @else
            <div class="res-dl-empty">
                <div style="font-size:13px;color:#97A3B7;margin-bottom:4px;">{{ __('partner.download_files') }}</div>
                <div style="font-size:12px;color:#d1d5db;">{{ __('partner.download_soon') }}</div>
                <div class="res-dl-empty-items">
                    <div class="res-dl-placeholder">
                        <div class="icon"><i class="bi bi-file-earmark-pdf"></i></div>
                        <div class="text"><div class="name">Guia completo.pdf</div><div class="size">PDF · 2.4 MB</div></div>
                        <button class="btn-ph">Baixar</button>
                    </div>
                    <div class="res-dl-placeholder">
                        <div class="icon"><i class="bi bi-file-earmark-excel"></i></div>
                        <div class="text"><div class="name">Planilha de projeção.xlsx</div><div class="size">XLSX · 156 KB</div></div>
                        <button class="btn-ph">Baixar</button>
                    </div>
                    <div class="res-dl-placeholder">
                        <div class="icon"><i class="bi bi-file-earmark-ppt"></i></div>
                        <div class="text"><div class="name">Apresentação comercial.pptx</div><div class="size">PPTX · 5.1 MB</div></div>
                        <button class="btn-ph">Baixar</button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

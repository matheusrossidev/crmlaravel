@extends('tenant.layouts.app')
@php $title = 'Cursos'; $pageIcon = 'mortarboard'; @endphp

@push('styles')
<style>
.course-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px; }
.course-card { background: #fff; border: 1.5px solid #e8eaf0; border-radius: 14px; overflow: hidden; transition: box-shadow .15s, transform .15s; text-decoration: none; color: inherit; display: block; }
.course-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.06); transform: translateY(-2px); }
.course-cover { width: 100%; height: 170px; object-fit: cover; background: #f3f4f6; }
.course-cover-ph { width: 100%; height: 170px; background: linear-gradient(135deg, #eff6ff, #f5f3ff); display: flex; align-items: center; justify-content: center; color: #0085f3; font-size: 44px; }
.course-body { padding: 18px 20px; }
.course-title { font-size: 16px; font-weight: 700; color: #1a1d23; margin-bottom: 6px; }
.course-desc { font-size: 13px; color: #6b7280; line-height: 1.5; margin-bottom: 14px; }
.course-progress { display: flex; align-items: center; gap: 10px; }
.course-bar { flex: 1; height: 6px; background: #f3f4f6; border-radius: 99px; overflow: hidden; }
.course-bar-fill { height: 100%; border-radius: 99px; background: #0085f3; transition: width .3s; }
.course-pct { font-size: 12px; font-weight: 700; color: #0085f3; }
.course-lessons-count { font-size: 12px; color: #97A3B7; margin-top: 6px; }
.course-cert-badge { display: inline-flex; align-items: center; gap: 4px; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 99px; background: #ecfdf5; color: #059669; margin-top: 8px; }
</style>
@endpush

@section('content')
<div class="page-container">
    <div style="margin-bottom:24px;">
        <div style="font-size:11px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:#97A3B7;margin-bottom:4px;">{{ __('partner.portal_label') }}</div>
        <h1 style="font-family:'Plus Jakarta Sans',sans-serif;font-size:22px;font-weight:700;color:#1a1d23;margin:0 0 4px;">Cursos</h1>
        <p style="font-size:13.5px;color:#677489;margin:0;">{{ __('partner.courses_desc') }}</p>
    </div>

    @if($courses->isEmpty())
        <div style="padding:60px;text-align:center;background:#fff;border:1.5px solid #e8eaf0;border-radius:14px;color:#97A3B7;">
            <i class="bi bi-mortarboard" style="font-size:40px;display:block;margin-bottom:10px;color:#d1d5db;"></i>
            <p style="font-size:14px;font-weight:600;color:#374151;">{{ __('partner.no_courses') }}</p>
            <p style="font-size:13px;">{{ __('partner.courses_soon') }}</p>
        </div>
    @else
        <div class="course-grid">
            @foreach($courses as $c)
                <a href="{{ route('partner.courses.show', $c->slug) }}" class="course-card">
                    @if($c->cover_image)
                        <img src="{{ asset('storage/' . $c->cover_image) }}" class="course-cover" alt="{{ $c->title }}">
                    @else
                        <div class="course-cover-ph"><i class="bi bi-play-circle"></i></div>
                    @endif
                    <div class="course-body">
                        <div class="course-title">{{ $c->title }}</div>
                        @if($c->description) <div class="course-desc">{{ Str::limit($c->description, 100) }}</div> @endif
                        <div class="course-progress">
                            <div class="course-bar"><div class="course-bar-fill" style="width:{{ $c->progress_pct }}%;"></div></div>
                            <span class="course-pct">{{ $c->progress_pct }}%</span>
                        </div>
                        <div class="course-lessons-count">{{ $c->completed_lessons }}/{{ $c->lessons_count }} aulas concluídas</div>
                        @if($c->has_certificate)
                            <div class="course-cert-badge"><i class="bi bi-patch-check-fill"></i> Certificado emitido</div>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection

@extends('tenant.layouts.app')
@php $title = $course->title; $pageIcon = 'play-circle'; @endphp

@push('styles')
<style>
.cp-layout { display: grid; grid-template-columns: 1fr 320px; gap: 20px; }
@media (max-width: 768px) { .cp-layout { grid-template-columns: 1fr; } }

.cp-player { background: #fff; border: 1.5px solid #e8eaf0; border-radius: 14px; overflow: hidden; }
.cp-video { width: 100%; aspect-ratio: 16/9; background: #000; display: flex; align-items: center; justify-content: center; color: #6b7280; font-size: 48px; }
.cp-video iframe { width: 100%; height: 100%; border: none; }
.cp-lesson-info { padding: 20px 24px; }
.cp-lesson-title { font-size: 18px; font-weight: 700; color: #1a1d23; margin-bottom: 6px; }
.cp-lesson-desc { font-size: 13px; color: #6b7280; line-height: 1.6; }

.cp-sidebar { background: #fff; border: 1.5px solid #e8eaf0; border-radius: 14px; overflow: hidden; display: flex; flex-direction: column; }
.cp-sidebar-header { padding: 16px 18px; border-bottom: 1px solid #f0f2f7; }
.cp-sidebar-header h3 { font-size: 14px; font-weight: 700; color: #1a1d23; margin: 0 0 4px; }
.cp-sidebar-header .sub { font-size: 12px; color: #97A3B7; }
.cp-sidebar-progress { padding: 0 18px; margin: 12px 0; }
.cp-sidebar-bar { height: 6px; background: #f3f4f6; border-radius: 99px; overflow: hidden; }
.cp-sidebar-bar-fill { height: 100%; border-radius: 99px; background: #0085f3; }

.cp-lessons { flex: 1; overflow-y: auto; max-height: 500px; }
.cp-lesson { display: flex; align-items: center; gap: 10px; padding: 12px 18px; border-bottom: 1px solid #f3f4f6; cursor: pointer; transition: background .1s; text-decoration: none; color: inherit; }
.cp-lesson:hover { background: #f8fafc; }
.cp-lesson.active { background: #eff6ff; border-left: 3px solid #0085f3; }
.cp-lesson-num { width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; flex-shrink: 0; }
.cp-lesson-num.done { background: #ecfdf5; color: #059669; }
.cp-lesson-num.pending { background: #f3f4f6; color: #6b7280; }
.cp-lesson-name { font-size: 13px; font-weight: 600; color: #1a1d23; flex: 1; }
.cp-lesson-dur { font-size: 11px; color: #97A3B7; flex-shrink: 0; }

.cp-cert-card { background: #fff; border: 1.5px solid #e8eaf0; border-radius: 14px; padding: 24px; text-align: center; margin-top: 20px; }
.cp-cert-card.issued { border-color: #10B981; background: linear-gradient(180deg, #ecfdf5, #fff 50%); }
</style>
@endpush

@section('content')
<div class="page-container">
    <div style="margin-bottom:16px;">
        <a href="{{ route('partner.courses.index') }}" style="font-size:13px;color:#0085f3;text-decoration:none;display:inline-flex;align-items:center;gap:4px;">
            <i class="bi bi-arrow-left"></i> Voltar aos cursos
        </a>
    </div>

    <div class="cp-layout">
        {{-- Player --}}
        <div>
            <div class="cp-player">
                <div class="cp-video" id="videoContainer">
                    @if($course->lessons->isNotEmpty())
                        @php $firstLesson = $course->lessons->first(); @endphp
                        @if($firstLesson->video_url)
                            <iframe src="{{ $firstLesson->video_url }}" allowfullscreen id="videoFrame"></iframe>
                        @else
                            <i class="bi bi-play-circle" id="videoPlaceholder"></i>
                        @endif
                    @else
                        <i class="bi bi-play-circle"></i>
                    @endif
                </div>
                <div class="cp-lesson-info">
                    <div class="cp-lesson-title" id="activeTitle">{{ $course->lessons->first()?->title ?? $course->title }}</div>
                    <div class="cp-lesson-desc" id="activeDesc">{{ $course->lessons->first()?->description ?? $course->description }}</div>
                    @if($course->lessons->isNotEmpty())
                        <button id="btnComplete" onclick="completeLesson()" style="margin-top:14px;padding:8px 18px;background:#0085f3;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px;">
                            <i class="bi bi-check-lg"></i> Marcar como concluída
                        </button>
                    @endif
                </div>
            </div>

            {{-- Certificate --}}
            @if($allCompleted)
                <div class="cp-cert-card {{ $certificate ? 'issued' : '' }}">
                    @if($certificate)
                        <i class="bi bi-patch-check-fill" style="font-size:36px;color:#10B981;display:block;margin-bottom:10px;"></i>
                        <div style="font-size:16px;font-weight:700;color:#1a1d23;margin-bottom:4px;">Certificado emitido!</div>
                        <div style="font-size:13px;color:#6b7280;margin-bottom:12px;">Código: <strong>{{ $certificate->certificate_code }}</strong></div>
                        <a href="{{ url('/certificado/' . $certificate->certificate_code) }}" target="_blank" style="font-size:13px;color:#0085f3;font-weight:600;text-decoration:none;">
                            <i class="bi bi-box-arrow-up-right"></i> Ver certificado
                        </a>
                    @else
                        <i class="bi bi-award" style="font-size:36px;color:#f59e0b;display:block;margin-bottom:10px;"></i>
                        <div style="font-size:16px;font-weight:700;color:#1a1d23;margin-bottom:4px;">Parabéns! Curso concluído!</div>
                        <div style="font-size:13px;color:#6b7280;margin-bottom:14px;">Emita seu certificado agora.</div>
                        <button onclick="issueCert()" style="padding:10px 24px;background:#0085f3;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;">
                            <i class="bi bi-patch-check"></i> Emitir Certificado
                        </button>
                    @endif
                </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="cp-sidebar">
            <div class="cp-sidebar-header">
                <h3>{{ $course->title }}</h3>
                <div class="sub">{{ $course->lessons->count() }} aulas</div>
            </div>
            <div class="cp-sidebar-progress">
                @php $pct = $course->lessons->count() > 0 ? round((count($completedIds) / $course->lessons->count()) * 100) : 0; @endphp
                <div class="cp-sidebar-bar"><div class="cp-sidebar-bar-fill" style="width:{{ $pct }}%;"></div></div>
                <div style="font-size:11px;color:#97A3B7;margin-top:4px;">{{ count($completedIds) }}/{{ $course->lessons->count() }} concluídas · {{ $pct }}%</div>
            </div>
            <div class="cp-lessons">
                @foreach($course->lessons as $i => $lesson)
                    @php $done = in_array($lesson->id, $completedIds); @endphp
                    <div class="cp-lesson {{ $i === 0 ? 'active' : '' }}" data-id="{{ $lesson->id }}" data-video="{{ $lesson->video_url }}" data-title="{{ $lesson->title }}" data-desc="{{ $lesson->description }}" onclick="selectLesson(this)">
                        <div class="cp-lesson-num {{ $done ? 'done' : 'pending' }}">
                            @if($done) <i class="bi bi-check"></i> @else {{ $i + 1 }} @endif
                        </div>
                        <div class="cp-lesson-name">{{ $lesson->title }}</div>
                        @if($lesson->duration_minutes) <div class="cp-lesson-dur">{{ $lesson->duration_minutes }}min</div> @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
let activeLessonId = {{ $course->lessons->first()?->id ?? 'null' }};

function selectLesson(el) {
    document.querySelectorAll('.cp-lesson').forEach(l => l.classList.remove('active'));
    el.classList.add('active');
    activeLessonId = el.dataset.id;
    document.getElementById('activeTitle').textContent = el.dataset.title;
    document.getElementById('activeDesc').textContent = el.dataset.desc || '';

    const container = document.getElementById('videoContainer');
    if (el.dataset.video) {
        container.innerHTML = '<iframe src="' + el.dataset.video + '" allowfullscreen style="width:100%;height:100%;border:none;" id="videoFrame"></iframe>';
    } else {
        container.innerHTML = '<i class="bi bi-play-circle" style="font-size:48px;color:#6b7280;"></i>';
    }
}

async function completeLesson() {
    if (!activeLessonId) return;
    const url = '{{ route("partner.lessons.complete", "__ID__") }}'.replace('__ID__', activeLessonId);
    const r = await fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' } });
    const d = await r.json();
    if (d.success) {
        toastr.success('Aula concluída!');
        // Mark as done visually
        const el = document.querySelector('.cp-lesson[data-id="' + activeLessonId + '"]');
        if (el) {
            const num = el.querySelector('.cp-lesson-num');
            num.classList.remove('pending');
            num.classList.add('done');
            num.innerHTML = '<i class="bi bi-check"></i>';
        }
        setTimeout(() => location.reload(), 800);
    }
}

async function issueCert() {
    const url = '{{ route("partner.courses.certificate", $course->id) }}';
    const r = await fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' } });
    const d = await r.json();
    if (d.success) { toastr.success('Certificado emitido!'); setTimeout(() => location.reload(), 800); }
    else { toastr.error(d.message || 'Erro'); }
}
</script>
@endpush

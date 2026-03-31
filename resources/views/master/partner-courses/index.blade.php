@extends('master.layouts.app')
@php $title = 'Cursos para Parceiros'; $pageIcon = 'mortarboard'; @endphp

@section('topbar_actions')
<button class="m-btn m-btn-primary" onclick="openCourseModal()"><i class="bi bi-plus-lg"></i> Novo Curso</button>
@endsection

@section('content')
@foreach($courses as $course)
<div class="m-card" style="margin-bottom:16px;">
    <div class="m-card-header" style="display:flex;align-items:center;justify-content:space-between;">
        <div style="display:flex;align-items:center;gap:12px;">
            @if($course->cover_image)
                <img src="{{ asset('storage/' . $course->cover_image) }}" style="width:48px;height:48px;border-radius:10px;object-fit:cover;">
            @else
                <div style="width:48px;height:48px;border-radius:10px;background:#eff6ff;display:flex;align-items:center;justify-content:center;"><i class="bi bi-play-circle" style="color:#0085f3;font-size:20px;"></i></div>
            @endif
            <div>
                <div class="m-card-title" style="margin:0;">{{ $course->title }}</div>
                <div style="font-size:12px;color:#97A3B7;">
                    {{ $course->lessons_count }} aula{{ $course->lessons_count !== 1 ? 's' : '' }}
                    · {{ $course->is_published ? '✅ Publicado' : '📝 Rascunho' }}
                </div>
            </div>
        </div>
        <div style="display:flex;gap:6px;">
            <button style="background:#eff6ff;border:none;color:#0085f3;cursor:pointer;padding:6px 10px;border-radius:6px;font-size:13px;font-weight:600;" onclick="toggleLessons({{ $course->id }})"><i class="bi bi-list-ul"></i> Aulas</button>
            <button style="background:none;border:none;color:#0085f3;cursor:pointer;font-size:15px;padding:4px 6px;" onclick="editCourse({{ $course->id }}, {{ json_encode($course) }})"><i class="bi bi-pencil"></i></button>
            <button style="background:none;border:none;color:#ef4444;cursor:pointer;font-size:15px;padding:4px 6px;" onclick="deleteCourse({{ $course->id }})"><i class="bi bi-trash3"></i></button>
        </div>
    </div>
    <div id="lessons-{{ $course->id }}" style="display:none;padding:16px 20px;border-top:1px solid #f0f2f7;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
            <span style="font-size:13px;font-weight:700;color:#374151;">Aulas</span>
            <button style="padding:5px 12px;background:#0085f3;color:#fff;border:none;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;" onclick="openLessonModal({{ $course->id }})"><i class="bi bi-plus"></i> Nova Aula</button>
        </div>
        @forelse($course->lessons ?? collect() as $lesson)
            <div style="display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid #f3f4f6;">
                <span style="width:24px;height:24px;border-radius:50%;background:#eff6ff;color:#0085f3;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0;">{{ $lesson->sort_order + 1 }}</span>
                <span style="font-size:13px;font-weight:600;color:#1a1d23;flex:1;">{{ $lesson->title }}</span>
                @if($lesson->duration_minutes) <span style="font-size:11px;color:#97A3B7;">{{ $lesson->duration_minutes }}min</span> @endif
                <button style="background:none;border:none;color:#0085f3;cursor:pointer;font-size:13px;" onclick="editLesson({{ $lesson->id }}, {{ json_encode($lesson) }})"><i class="bi bi-pencil"></i></button>
                <button style="background:none;border:none;color:#ef4444;cursor:pointer;font-size:13px;" onclick="deleteLesson({{ $lesson->id }})"><i class="bi bi-trash3"></i></button>
            </div>
        @empty
            <div style="text-align:center;padding:20px;color:#97A3B7;font-size:13px;">Nenhuma aula adicionada.</div>
        @endforelse
    </div>
</div>
@endforeach

@if($courses->isEmpty())
    <div class="m-card" style="padding:60px;text-align:center;color:#9ca3af;">
        <i class="bi bi-mortarboard" style="font-size:36px;display:block;margin-bottom:10px;color:#d1d5db;"></i>
        <p style="font-size:14px;font-weight:600;color:#374151;">Nenhum curso criado</p>
    </div>
@endif

{{-- Course Modal --}}
<div id="courseModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;align-items:center;justify-content:center;" onclick="if(event.target===this)closeCourseModal()">
    <div style="background:#fff;border-radius:16px;padding:28px;width:100%;max-width:480px;box-shadow:0 20px 60px rgba(0,0,0,.2);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h3 id="courseTitle" style="margin:0;font-size:17px;font-weight:700;">Novo Curso</h3>
            <button onclick="closeCourseModal()" style="background:none;border:none;font-size:20px;color:#9ca3af;cursor:pointer;">&times;</button>
        </div>
        <input type="hidden" id="cEditId" value="">
        <div style="margin-bottom:14px;"><label style="display:block;font-size:12.5px;font-weight:600;color:#374151;margin-bottom:6px;">Título *</label><input type="text" id="cTitle" required style="width:100%;padding:9px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;box-sizing:border-box;"></div>
        <div style="margin-bottom:14px;"><label style="display:block;font-size:12.5px;font-weight:600;color:#374151;margin-bottom:6px;">Descrição</label><textarea id="cDesc" rows="3" style="width:100%;padding:9px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;font-family:inherit;resize:vertical;box-sizing:border-box;"></textarea></div>
        <div style="display:flex;gap:12px;margin-bottom:14px;">
            <div style="width:80px;"><label style="display:block;font-size:12.5px;font-weight:600;color:#374151;margin-bottom:6px;">Ordem</label><input type="number" id="cOrder" min="0" value="0" style="width:100%;padding:9px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;box-sizing:border-box;"></div>
            <div style="flex:1;"><label style="display:block;font-size:12.5px;font-weight:600;color:#374151;margin-bottom:6px;">Capa</label><input type="file" id="cCover" accept="image/*" style="font-size:13px;"></div>
        </div>
        <div style="margin-bottom:20px;display:flex;align-items:center;gap:10px;"><input type="checkbox" id="cPublished" style="width:16px;height:16px;accent-color:#0085f3;"><label for="cPublished" style="font-size:14px;color:#374151;cursor:pointer;">Publicado</label></div>
        <div style="display:flex;gap:10px;justify-content:flex-end;">
            <button type="button" onclick="closeCourseModal()" style="padding:9px 18px;background:#f3f4f6;border:1px solid #d1d5db;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;">Cancelar</button>
            <button type="button" onclick="saveCourse()" style="padding:9px 18px;background:#0085f3;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;"><i class="bi bi-check-lg"></i> Salvar</button>
        </div>
    </div>
</div>

{{-- Lesson Modal --}}
<div id="lessonModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1001;align-items:center;justify-content:center;" onclick="if(event.target===this)closeLessonModal()">
    <div style="background:#fff;border-radius:16px;padding:28px;width:100%;max-width:480px;box-shadow:0 20px 60px rgba(0,0,0,.2);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h3 id="lessonTitle" style="margin:0;font-size:17px;font-weight:700;">Nova Aula</h3>
            <button onclick="closeLessonModal()" style="background:none;border:none;font-size:20px;color:#9ca3af;cursor:pointer;">&times;</button>
        </div>
        <input type="hidden" id="lEditId" value="">
        <input type="hidden" id="lCourseId" value="">
        <div style="margin-bottom:14px;"><label style="display:block;font-size:12.5px;font-weight:600;color:#374151;margin-bottom:6px;">Título *</label><input type="text" id="lTitle" required style="width:100%;padding:9px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;box-sizing:border-box;"></div>
        <div style="margin-bottom:14px;"><label style="display:block;font-size:12.5px;font-weight:600;color:#374151;margin-bottom:6px;">Descrição</label><textarea id="lDesc" rows="2" style="width:100%;padding:9px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;font-family:inherit;resize:vertical;box-sizing:border-box;"></textarea></div>
        <div style="margin-bottom:14px;"><label style="display:block;font-size:12.5px;font-weight:600;color:#374151;margin-bottom:6px;">URL do vídeo</label><input type="text" id="lVideo" placeholder="https://youtube.com/embed/..." style="width:100%;padding:9px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;box-sizing:border-box;"></div>
        <div style="display:flex;gap:12px;margin-bottom:20px;">
            <div style="flex:1;"><label style="display:block;font-size:12.5px;font-weight:600;color:#374151;margin-bottom:6px;">Duração (min)</label><input type="number" id="lDuration" min="0" value="0" style="width:100%;padding:9px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;box-sizing:border-box;"></div>
            <div style="flex:1;"><label style="display:block;font-size:12.5px;font-weight:600;color:#374151;margin-bottom:6px;">Ordem</label><input type="number" id="lOrder" min="0" value="0" style="width:100%;padding:9px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;box-sizing:border-box;"></div>
        </div>
        <div style="display:flex;gap:10px;justify-content:flex-end;">
            <button type="button" onclick="closeLessonModal()" style="padding:9px 18px;background:#f3f4f6;border:1px solid #d1d5db;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;">Cancelar</button>
            <button type="button" onclick="saveLesson()" style="padding:9px 18px;background:#0085f3;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;"><i class="bi bi-check-lg"></i> Salvar</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
function toggleLessons(id){const el=document.getElementById('lessons-'+id);el.style.display=el.style.display==='none'?'block':'none';}

function openCourseModal(e){document.getElementById('courseModal').style.display='flex';if(!e){document.getElementById('courseTitle').textContent='Novo Curso';document.getElementById('cEditId').value='';document.getElementById('cTitle').value='';document.getElementById('cDesc').value='';document.getElementById('cOrder').value='0';document.getElementById('cPublished').checked=false;}}
function closeCourseModal(){document.getElementById('courseModal').style.display='none';}
function editCourse(id,d){document.getElementById('cEditId').value=id;document.getElementById('courseTitle').textContent='Editar Curso';document.getElementById('cTitle').value=d.title;document.getElementById('cDesc').value=d.description||'';document.getElementById('cOrder').value=d.sort_order||0;document.getElementById('cPublished').checked=!!d.is_published;openCourseModal(true);}
async function saveCourse(){const id=document.getElementById('cEditId').value;const fd=new FormData();fd.append('title',document.getElementById('cTitle').value);fd.append('description',document.getElementById('cDesc').value);fd.append('sort_order',document.getElementById('cOrder').value);fd.append('is_published',document.getElementById('cPublished').checked?'1':'0');const f=document.getElementById('cCover').files[0];if(f)fd.append('cover',f);if(id)fd.append('_method','PUT');const url=id?'{{ route("master.partner-courses.update","__ID__") }}'.replace('__ID__',id):'{{ route("master.partner-courses.store") }}';const r=await fetch(url,{method:'POST',headers:{'X-CSRF-TOKEN':CSRF,Accept:'application/json'},body:fd});const d=await r.json();if(d.success){toastr.success('Salvo!');setTimeout(()=>location.reload(),600);}else toastr.error(d.message||'Erro');}
function deleteCourse(id){if(!confirm('Excluir curso e todas as aulas?'))return;fetch('{{ route("master.partner-courses.destroy","__ID__") }}'.replace('__ID__',id),{method:'DELETE',headers:{'X-CSRF-TOKEN':CSRF,Accept:'application/json'}}).then(r=>r.json()).then(d=>{if(d.success)location.reload();});}

function openLessonModal(courseId,e){document.getElementById('lessonModal').style.display='flex';document.getElementById('lCourseId').value=courseId;if(!e){document.getElementById('lessonTitle').textContent='Nova Aula';document.getElementById('lEditId').value='';document.getElementById('lTitle').value='';document.getElementById('lDesc').value='';document.getElementById('lVideo').value='';document.getElementById('lDuration').value='0';document.getElementById('lOrder').value='0';}}
function closeLessonModal(){document.getElementById('lessonModal').style.display='none';}
function editLesson(id,d){document.getElementById('lEditId').value=id;document.getElementById('lCourseId').value=d.course_id;document.getElementById('lessonTitle').textContent='Editar Aula';document.getElementById('lTitle').value=d.title;document.getElementById('lDesc').value=d.description||'';document.getElementById('lVideo').value=d.video_url||'';document.getElementById('lDuration').value=d.duration_minutes||0;document.getElementById('lOrder').value=d.sort_order||0;openLessonModal(d.course_id,true);}
async function saveLesson(){const id=document.getElementById('lEditId').value;const courseId=document.getElementById('lCourseId').value;const body={title:document.getElementById('lTitle').value,description:document.getElementById('lDesc').value,video_url:document.getElementById('lVideo').value,duration_minutes:parseInt(document.getElementById('lDuration').value)||0,sort_order:parseInt(document.getElementById('lOrder').value)||0};const url=id?'{{ route("master.partner-lessons.update","__ID__") }}'.replace('__ID__',id):'{{ route("master.partner-lessons.store","__ID__") }}'.replace('__ID__',courseId);const r=await fetch(url,{method:id?'PUT':'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,Accept:'application/json'},body:JSON.stringify(body)});const d=await r.json();if(d.success){toastr.success('Salvo!');setTimeout(()=>location.reload(),600);}else toastr.error(d.message||'Erro');}
function deleteLesson(id){if(!confirm('Excluir aula?'))return;fetch('{{ route("master.partner-lessons.destroy","__ID__") }}'.replace('__ID__',id),{method:'DELETE',headers:{'X-CSRF-TOKEN':CSRF,Accept:'application/json'}}).then(r=>r.json()).then(d=>{if(d.success)location.reload();});}
</script>
@endpush

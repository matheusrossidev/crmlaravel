@extends('tenant.layouts.app')

@php
    $title    = 'Agenda';
    $pageIcon = 'calendar3';
@endphp

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css">
<style>
.calendar-wrapper {
    background: #fff;
    border: 1px solid #e8eaf0;
    border-radius: 14px;
    padding: 24px;
}

/* FullCalendar overrides */
.fc .fc-toolbar-title       { font-size: 16px; font-weight: 700; color: #1a1d23; }
.fc .fc-button              { background: #f3f4f6; border: 1.5px solid #e5e7eb; color: #374151; font-size: 13px; font-weight: 600; border-radius: 8px !important; padding: 6px 12px; }
.fc .fc-button:hover        { background: #e5e7eb; }
.fc .fc-button-primary:not(:disabled).fc-button-active,
.fc .fc-button-primary:not(:disabled):active { background: #0085f3; border-color: #0085f3; color: #fff; }
.fc .fc-button-primary      { background: #f3f4f6; border-color: #e5e7eb; color: #374151; }
.fc .fc-button-primary:hover { background: #e5e7eb; border-color: #e5e7eb; }
.fc-today-button            { text-transform: capitalize !important; }
.fc .fc-daygrid-day.fc-day-today { background: #eff6ff; }
.fc .fc-event               { border-radius: 6px; border: none; padding: 2px 5px; font-size: 12px; cursor: pointer; }
.fc .fc-event-title         { font-weight: 600; }
.fc-daygrid-event-dot       { border-color: #0085f3; }
.fc .fc-col-header-cell-cushion { font-size: 12px; font-weight: 700; color: #6b7280; text-transform: uppercase; text-decoration: none; }
.fc .fc-daygrid-day-number  { font-size: 13px; color: #374151; text-decoration: none; }
.fc a:hover { color: inherit; }
.fc .fc-timegrid-slot       { height: 40px; }

/* Modal */
.cal-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 1050;
    background: rgba(0,0,0,.45);
    align-items: center;
    justify-content: center;
}
.cal-modal-overlay.open { display: flex; }
.cal-modal {
    background: #fff;
    border-radius: 16px;
    width: 480px;
    max-width: 95vw;
    padding: 28px;
    box-shadow: 0 8px 48px rgba(0,0,0,.2);
    max-height: 90vh;
    overflow-y: auto;
}
.cal-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}
.cal-modal-title { font-size: 16px; font-weight: 700; color: #1a1d23; margin: 0; }
.cal-modal-close {
    background: none; border: none; cursor: pointer; font-size: 22px; color: #9ca3af; padding: 0;
}
.cal-form-group { margin-bottom: 14px; }
.cal-form-group label {
    display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px;
}
.cal-input {
    width: 100%; padding: 9px 12px; border: 1.5px solid #e5e7eb; border-radius: 9px;
    font-size: 13.5px; font-family: inherit; color: #1a1d23; outline: none;
    transition: border-color .15s, box-shadow .15s; background: #fafafa;
}
.cal-input:focus { border-color: #0085f3; background: #fff; box-shadow: 0 0 0 3px rgba(0,133,243,.1); }
.cal-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.cal-footer { display: flex; gap: 10px; justify-content: flex-end; margin-top: 4px; }
.btn-cal-cancel {
    padding: 8px 18px; background: transparent; color: #6b7280; border: 1.5px solid #e5e7eb;
    border-radius: 9px; font-size: 13px; font-weight: 600; cursor: pointer;
}
.btn-cal-cancel:hover { background: #f3f4f6; }
.btn-cal-save {
    padding: 8px 22px; background: #0085f3; color: #fff; border: none; border-radius: 9px;
    font-size: 13px; font-weight: 600; cursor: pointer; transition: background .15s;
}
.btn-cal-save:hover { background: #0070d1; }
.btn-cal-delete {
    padding: 8px 16px; background: transparent; color: #ef4444; border: 1.5px solid #fecaca;
    border-radius: 9px; font-size: 13px; font-weight: 600; cursor: pointer; margin-right: auto;
}
.btn-cal-delete:hover { background: #fef2f2; }
.section-title { font-size: 15px; font-weight: 700; color: #1a1d23; }
.section-subtitle { font-size: 13px; color: #9ca3af; margin-top: 3px; }
.section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
</style>
@endpush

@section('content')
<div class="page-container">

    <div class="section-header">
        <div>
            <div class="section-title">Agenda</div>
            <div class="section-subtitle">Sincronizado com Google Calendar.</div>
        </div>
        <button class="btn-primary-sm" onclick="openCreateModal()">
            <i class="bi bi-plus-lg"></i> Novo evento
        </button>
    </div>

    <div class="calendar-wrapper">
        <div id="calendar"></div>
    </div>

</div>

{{-- Modal de Evento --}}
<div class="cal-modal-overlay" id="calModal">
    <div class="cal-modal">
        <div class="cal-modal-header">
            <h3 class="cal-modal-title" id="calModalTitle">Novo Evento</h3>
            <button class="cal-modal-close" onclick="closeModal()">×</button>
        </div>

        <input type="hidden" id="calEventId">

        <div class="cal-form-group">
            <label>Título <span style="color:#ef4444;">*</span></label>
            <input type="text" class="cal-input" id="calTitle" placeholder="Ex: Reunião com cliente">
        </div>

        <div class="cal-row">
            <div class="cal-form-group">
                <label>Início</label>
                <input type="datetime-local" class="cal-input" id="calStart">
            </div>
            <div class="cal-form-group">
                <label>Fim</label>
                <input type="datetime-local" class="cal-input" id="calEnd">
            </div>
        </div>

        <div class="cal-form-group">
            <label>Local</label>
            <input type="text" class="cal-input" id="calLocation" placeholder="Ex: Google Meet, Escritório...">
        </div>

        <div class="cal-form-group">
            <label>Descrição</label>
            <textarea class="cal-input" id="calDescription" rows="3" placeholder="Observações sobre o evento..."></textarea>
        </div>

        <div id="calError" style="display:none;background:#fef2f2;color:#991b1b;border:1px solid #fecaca;border-radius:8px;padding:10px 14px;font-size:13px;margin-bottom:12px;"></div>

        <div class="cal-footer">
            <button class="btn-cal-delete" id="btnCalDelete" onclick="deleteEvent()" style="display:none;">
                <i class="bi bi-trash3"></i> Excluir
            </button>
            <button class="btn-cal-cancel" onclick="closeModal()">Cancelar</button>
            <button class="btn-cal-save" id="btnCalSave" onclick="saveEvent()">Salvar</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/locales/pt-br.global.min.js"></script>
<script>
const CSRF = document.querySelector('meta[name=csrf-token]')?.content;
const ROUTES = {
    events:  '{{ route('calendar.events') }}',
    store:   '{{ route('calendar.store') }}',
    update:  (id) => '{{ url('agenda/eventos') }}/' + id,
    destroy: (id) => '{{ url('agenda/eventos') }}/' + id,
};

let calendar;
let currentEventId = null;

// ── FullCalendar Init ────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    const el = document.getElementById('calendar');
    calendar = new FullCalendar.Calendar(el, {
        locale:          'pt-br',
        initialView:     'dayGridMonth',
        headerToolbar: {
            left:   'prev,next today',
            center: 'title',
            right:  'dayGridMonth,timeGridWeek,timeGridDay',
        },
        editable:        true,
        selectable:      true,
        height:          'auto',
        nowIndicator:    true,
        eventColor:      '#0085f3',

        // Carrega eventos da API
        events: function (info, successCallback, failureCallback) {
            fetch(ROUTES.events + '?start=' + info.startStr + '&end=' + info.endStr, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            })
            .then(r => r.json())
            .then(data => {
                if (data.error) { failureCallback(data.error); return; }
                successCallback(data);
            })
            .catch(failureCallback);
        },

        // Clique em slot vazio → criar
        dateClick: function (info) {
            openCreateModal(info.dateStr);
        },

        // Selecionar range → criar com datas preenchidas
        select: function (info) {
            openCreateModal(info.startStr, info.endStr);
        },

        // Clique em evento → editar
        eventClick: function (info) {
            openEditModal(info.event);
        },

        // Drag & drop → atualiza no Google
        eventDrop: function (info) {
            updateEventDates(info.event);
        },

        // Resize → atualiza no Google
        eventResize: function (info) {
            updateEventDates(info.event);
        },
    });

    calendar.render();
});

// ── Modal helpers ────────────────────────────────────────────────────────
function openCreateModal(start, end) {
    currentEventId = null;
    document.getElementById('calModalTitle').textContent = 'Novo Evento';
    document.getElementById('calEventId').value = '';
    document.getElementById('calTitle').value = '';
    document.getElementById('calStart').value = start ? toDatetimeLocal(start) : '';
    document.getElementById('calEnd').value   = end   ? toDatetimeLocal(end)   : (start ? toDatetimeLocal(addHour(start)) : '');
    document.getElementById('calLocation').value = '';
    document.getElementById('calDescription').value = '';
    document.getElementById('btnCalDelete').style.display = 'none';
    hideError();
    document.getElementById('calModal').classList.add('open');
    setTimeout(() => document.getElementById('calTitle').focus(), 100);
}

function openEditModal(event) {
    currentEventId = event.id;
    document.getElementById('calModalTitle').textContent = 'Editar Evento';
    document.getElementById('calEventId').value = event.id;
    document.getElementById('calTitle').value = event.title;
    document.getElementById('calStart').value = event.start ? toDatetimeLocal(event.start.toISOString()) : '';
    document.getElementById('calEnd').value   = event.end   ? toDatetimeLocal(event.end.toISOString())   : '';
    document.getElementById('calLocation').value = event.extendedProps?.location || '';
    document.getElementById('calDescription').value = event.extendedProps?.description || '';
    document.getElementById('btnCalDelete').style.display = 'inline-flex';
    hideError();
    document.getElementById('calModal').classList.add('open');
}

function closeModal() {
    document.getElementById('calModal').classList.remove('open');
}

function hideError() {
    const el = document.getElementById('calError');
    el.style.display = 'none';
    el.textContent = '';
}

function showError(msg) {
    const el = document.getElementById('calError');
    el.textContent = msg;
    el.style.display = 'block';
}

// ── Save event ────────────────────────────────────────────────────────────
async function saveEvent() {
    const title = document.getElementById('calTitle').value.trim();
    if (!title) { showError('O título é obrigatório.'); return; }

    const start = document.getElementById('calStart').value;
    const end   = document.getElementById('calEnd').value;

    if (!start) { showError('Informe a data/hora de início.'); return; }
    if (!end)   { showError('Informe a data/hora de fim.'); return; }
    if (start >= end) { showError('O fim deve ser após o início.'); return; }

    const btn = document.getElementById('btnCalSave');
    btn.disabled = true;
    btn.textContent = 'Salvando...';
    hideError();

    const payload = {
        title,
        start:       toIso(start),
        end:         toIso(end),
        location:    document.getElementById('calLocation').value.trim(),
        description: document.getElementById('calDescription').value.trim(),
    };

    const isEdit = !!currentEventId;
    const url    = isEdit ? ROUTES.update(currentEventId) : ROUTES.store;
    const method = isEdit ? 'PUT' : 'POST';

    try {
        const res  = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify(payload),
        });
        const data = await res.json();

        if (data.success) {
            closeModal();
            calendar.refetchEvents();
        } else {
            showError(data.message || 'Erro ao salvar evento.');
        }
    } catch (e) {
        showError('Erro de conexão. Tente novamente.');
    }

    btn.disabled = false;
    btn.textContent = 'Salvar';
}

// ── Delete event ───────────────────────────────────────────────────────────
async function deleteEvent() {
    if (!currentEventId) return;
    if (!confirm('Excluir este evento do Google Calendar?')) return;

    try {
        const res  = await fetch(ROUTES.destroy(currentEventId), {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        });
        const data = await res.json();
        if (data.success) {
            closeModal();
            calendar.refetchEvents();
        } else {
            showError(data.message || 'Erro ao excluir.');
        }
    } catch (e) {
        showError('Erro de conexão.');
    }
}

// ── Drag & drop update ─────────────────────────────────────────────────────
async function updateEventDates(event) {
    const payload = {
        start: event.start?.toISOString(),
        end:   event.end?.toISOString(),
    };
    try {
        await fetch(ROUTES.update(event.id), {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify(payload),
        });
    } catch (e) {
        console.error('Erro ao atualizar evento:', e);
        calendar.refetchEvents();
    }
}

// ── Date helpers ──────────────────────────────────────────────────────────
function toDatetimeLocal(isoStr) {
    if (!isoStr) return '';
    const d = new Date(isoStr);
    const pad = (n) => String(n).padStart(2, '0');
    return d.getFullYear() + '-' + pad(d.getMonth()+1) + '-' + pad(d.getDate())
        + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
}

function toIso(datetimeLocalStr) {
    return new Date(datetimeLocalStr).toISOString();
}

function addHour(isoStr) {
    const d = new Date(isoStr);
    d.setHours(d.getHours() + 1);
    return d.toISOString();
}

// Fechar modal ao clicar fora
document.getElementById('calModal').addEventListener('click', function (e) {
    if (e.target === this) closeModal();
});
</script>
@endpush

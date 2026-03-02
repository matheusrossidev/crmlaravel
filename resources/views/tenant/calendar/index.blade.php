@extends('tenant.layouts.app')

@php
    $title    = 'Agenda';
    $pageIcon = 'calendar3';
@endphp

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css">
<style>
/* ── Layout ──────────────────────────────────────────────────────────────── */
.cal-page {
    display: grid;
    grid-template-columns: 260px 1fr;
    gap: 20px;
    align-items: start;
}
@media (max-width: 960px) {
    .cal-page { grid-template-columns: 1fr; }
    .cal-sidebar { display: none; }
}

/* ── Sidebar ─────────────────────────────────────────────────────────────── */
.cal-sidebar {
    background: #fff;
    border: 1px solid #e8eaf0;
    border-radius: 14px;
    padding: 18px 16px;
    position: sticky;
    top: 20px;
}

.btn-new-event {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    width: 100%;
    padding: 10px 18px;
    background: #0085f3;
    color: #fff;
    border: none;
    border-radius: 24px;
    font-size: 13.5px;
    font-weight: 600;
    cursor: pointer;
    margin-bottom: 20px;
    transition: background .15s, box-shadow .15s;
    box-shadow: 0 2px 10px rgba(0,133,243,.3);
}
.btn-new-event:hover { background: #0070d1; box-shadow: 0 4px 16px rgba(0,133,243,.4); }

/* ── Mini Calendar ───────────────────────────────────────────────────────── */
.mini-cal { margin-bottom: 4px; }
.mini-cal-nav {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
}
.mini-cal-nav .mc-title {
    font-size: 12.5px;
    font-weight: 700;
    color: #374151;
}
.mini-cal-nav button {
    background: none;
    border: none;
    cursor: pointer;
    color: #9ca3af;
    font-size: 18px;
    padding: 0 4px;
    border-radius: 6px;
    line-height: 1;
    transition: color .1s, background .1s;
}
.mini-cal-nav button:hover { background: #f3f4f6; color: #374151; }
.mc-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 1px;
    text-align: center;
}
.mc-head {
    font-size: 10px;
    font-weight: 700;
    color: #9ca3af;
    padding: 3px 0 5px;
}
.mc-day {
    position: relative;
    font-size: 11.5px;
    color: #374151;
    cursor: pointer;
    width: 28px;
    height: 28px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    border-radius: 50%;
    transition: background .1s;
    line-height: 1;
    font-weight: 500;
}
.mc-day:hover:not(.mc-empty) { background: #f3f4f6; }
.mc-day.mc-today { background: #0085f3 !important; color: #fff; font-weight: 700; }
.mc-day.mc-today:hover { background: #0070d1 !important; }
.mc-day.mc-has-event::after {
    content: '';
    position: absolute;
    bottom: 2px;
    width: 4px;
    height: 4px;
    background: #0085f3;
    border-radius: 50%;
}
.mc-day.mc-today.mc-has-event::after { background: rgba(255,255,255,.7); }
.mc-day.mc-empty { cursor: default; color: #d1d5db !important; }
.mc-day.mc-other { color: #d1d5db; }

/* ── Upcoming ────────────────────────────────────────────────────────────── */
.up-section-title {
    font-size: 11px;
    font-weight: 700;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: .05em;
    padding: 14px 0 10px;
    border-top: 1px solid #f3f4f6;
    margin-top: 12px;
}
.up-item {
    display: flex;
    align-items: flex-start;
    gap: 9px;
    padding: 7px 0;
    border-bottom: 1px solid #f9fafb;
    cursor: pointer;
    transition: opacity .1s;
}
.up-item:last-child { border-bottom: none; }
.up-item:hover .up-title { color: #0085f3; }
.up-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; margin-top: 3px; }
.up-body { flex: 1; min-width: 0; }
.up-title {
    font-size: 12px;
    font-weight: 600;
    color: #1a1d23;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    transition: color .1s;
}
.up-date { font-size: 11px; color: #9ca3af; margin-top: 1px; }
.up-empty { font-size: 12px; color: #9ca3af; text-align: center; padding: 14px 0; }

/* ── Main calendar wrapper ───────────────────────────────────────────────── */
.cal-main {
    background: #fff;
    border: 1px solid #e8eaf0;
    border-radius: 14px;
    padding: 20px 24px 24px;
    min-height: 600px;
}

/* ── FullCalendar overrides ──────────────────────────────────────────────── */
.fc .fc-toolbar { margin-bottom: 16px; }
.fc .fc-toolbar-title { font-size: 17px; font-weight: 700; color: #1a1d23; }
.fc .fc-button {
    background: #f3f4f6 !important;
    border: 1.5px solid #e5e7eb !important;
    color: #374151 !important;
    font-size: 12.5px !important;
    font-weight: 600 !important;
    border-radius: 8px !important;
    padding: 6px 13px !important;
    box-shadow: none !important;
    transition: background .12s !important;
}
.fc .fc-button:hover { background: #e5e7eb !important; }
.fc .fc-button-primary:not(:disabled).fc-button-active,
.fc .fc-button-primary:not(:disabled):active {
    background: #0085f3 !important;
    border-color: #0085f3 !important;
    color: #fff !important;
}
.fc .fc-button-group .fc-button { border-radius: 0 !important; }
.fc .fc-button-group .fc-button:first-child { border-radius: 8px 0 0 8px !important; }
.fc .fc-button-group .fc-button:last-child  { border-radius: 0 8px 8px 0 !important; }

/* Column headers */
.fc .fc-col-header-cell-cushion {
    font-size: 11px;
    font-weight: 700;
    color: #9ca3af;
    text-transform: uppercase;
    text-decoration: none;
    padding: 8px 4px;
}

/* Day numbers */
.fc .fc-daygrid-day-number {
    font-size: 12.5px;
    font-weight: 500;
    color: #374151;
    text-decoration: none;
    padding: 6px 8px;
}

/* Today cell */
.fc .fc-daygrid-day.fc-day-today { background: #eff6ff !important; }
.fc .fc-daygrid-day.fc-day-today .fc-daygrid-day-number {
    background: #0085f3;
    color: #fff !important;
    border-radius: 50%;
    width: 26px;
    height: 26px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    font-weight: 700;
}

/* Events */
.fc .fc-event {
    border: none !important;
    border-radius: 5px !important;
    padding: 2px 6px !important;
    font-size: 11.5px !important;
    font-weight: 600 !important;
    cursor: pointer;
}
.fc .fc-daygrid-event-harness { margin-top: 2px; }
.fc a { text-decoration: none !important; }
.fc a:hover { color: inherit; }

/* Time grid */
.fc .fc-timegrid-slot { height: 44px; }
.fc .fc-timegrid-event {
    border-radius: 6px !important;
}
.fc .fc-timegrid-event .fc-event-main { padding: 4px 8px; }

/* Now indicator */
.fc .fc-timegrid-now-indicator-line { border-color: #ef4444; }
.fc .fc-timegrid-now-indicator-arrow { border-top-color: #ef4444; border-bottom-color: #ef4444; }

/* Scrollgrid */
.fc .fc-scrollgrid { border-color: #f3f4f6 !important; }
.fc .fc-scrollgrid td, .fc .fc-scrollgrid th { border-color: #f3f4f6 !important; }

/* ── Event Popup ─────────────────────────────────────────────────────────── */
.ev-popup {
    position: fixed;
    z-index: 1060;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 8px 40px rgba(0,0,0,.18);
    width: 300px;
    padding: 16px;
    display: none;
    animation: popIn .12s ease;
}
@keyframes popIn {
    from { opacity: 0; transform: scale(.95); }
    to   { opacity: 1; transform: scale(1); }
}
.ev-popup.open { display: block; }
.ev-popup-header {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    margin-bottom: 10px;
}
.ev-popup-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; margin-top: 4px; }
.ev-popup-title { font-size: 14px; font-weight: 700; color: #1a1d23; flex: 1; line-height: 1.35; }
.ev-popup-btns { display: flex; gap: 4px; flex-shrink: 0; }
.ev-popup-btn {
    background: none; border: none; cursor: pointer; color: #9ca3af; padding: 4px 5px;
    border-radius: 6px; font-size: 14px; line-height: 1;
}
.ev-popup-btn:hover { background: #f3f4f6; color: #374151; }
.ev-popup-row {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    font-size: 12px;
    color: #4b5563;
    margin-top: 7px;
    line-height: 1.5;
}
.ev-popup-row i { color: #9ca3af; margin-top: 1px; flex-shrink: 0; font-size: 13px; }

/* ── Drawer inputs ───────────────────────────────────────────────────────── */
.cal-inp {
    width: 100%;
    padding: 9px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
    color: #1a1d23;
    outline: none;
    box-sizing: border-box;
    transition: border-color .15s, box-shadow .15s;
    background: #fff;
}
.cal-inp:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.15); }
</style>
@endpush

@section('content')
<div class="page-container">
<div class="cal-page">

    {{-- ── Sidebar ─────────────────────────────────────────────────────── --}}
    <aside class="cal-sidebar">

        <button class="btn-new-event" onclick="openCreateModal()">
            <i class="bi bi-plus-lg"></i> Novo evento
        </button>

        {{-- Mini Calendar --}}
        <div id="miniCal"></div>

        {{-- Upcoming Events --}}
        <div class="up-section-title">Próximos eventos</div>
        <div id="upcomingList"><div class="up-empty">Carregando…</div></div>

    </aside>

    {{-- ── Main Calendar ───────────────────────────────────────────────── --}}
    <div class="cal-main">
        <div id="calendar"></div>
    </div>

</div>
</div>

{{-- ── Event Popup ──────────────────────────────────────────────────────── --}}
<div class="ev-popup" id="evPopup">
    <div class="ev-popup-header">
        <div class="ev-popup-dot" id="popupDot"></div>
        <div class="ev-popup-title" id="popupTitle"></div>
        <div class="ev-popup-btns">
            <button class="ev-popup-btn" title="Editar"  onclick="editFromPopup()"><i class="bi bi-pencil"></i></button>
            <button class="ev-popup-btn" title="Excluir" onclick="deleteFromPopup()"><i class="bi bi-trash3"></i></button>
            <button class="ev-popup-btn" title="Fechar"  onclick="closePopup()"><i class="bi bi-x-lg"></i></button>
        </div>
    </div>
    <div id="popupBody"></div>
</div>

{{-- ── Overlay do drawer ────────────────────────────────────────────────── --}}
<div id="calDrawerOverlay" onclick="closeCalDrawer()" style="
    display:none; position:fixed; inset:0;
    background:rgba(0,0,0,.35); z-index:1049;
    transition:opacity .25s;
"></div>

{{-- ── Drawer de evento ─────────────────────────────────────────────────── --}}
<aside id="calDrawer" style="
    position:fixed; top:0; right:0;
    width:480px; max-width:100vw; height:100vh;
    background:#fff;
    box-shadow:-4px 0 32px rgba(0,0,0,.12);
    z-index:1050;
    display:flex; flex-direction:column;
    transform:translateX(100%);
    transition:transform .25s cubic-bezier(.4,0,.2,1);
    overflow:hidden;
">
    {{-- Header --}}
    <div style="display:flex;align-items:center;justify-content:space-between;
                padding:20px 24px;border-bottom:1px solid #e2e8f0;flex-shrink:0">
        <h3 id="calDrawerTitle" style="font-size:16px;font-weight:600;color:#0f172a;margin:0">Novo Evento</h3>
        <button onclick="closeCalDrawer()" style="
            background:none;border:none;cursor:pointer;
            color:#94a3b8;font-size:22px;line-height:1;padding:4px;
        ">×</button>
    </div>

    {{-- Body (scrollable) --}}
    <div style="flex:1;overflow-y:auto;padding:22px 24px">
        <input type="hidden" id="calEventId">

        {{-- Título --}}
        <div style="margin-bottom:18px">
            <label style="display:block;font-size:13px;font-weight:500;color:#374151;margin-bottom:6px">
                Título <span style="color:#ef4444">*</span>
            </label>
            <input type="text" class="cal-inp" id="calTitle" placeholder="Ex: Reunião com cliente">
        </div>

        {{-- Data/Hora --}}
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:18px">
            <div>
                <label style="display:block;font-size:13px;font-weight:500;color:#374151;margin-bottom:6px">Início</label>
                <input type="datetime-local" class="cal-inp" id="calStart">
            </div>
            <div>
                <label style="display:block;font-size:13px;font-weight:500;color:#374151;margin-bottom:6px">Fim</label>
                <input type="datetime-local" class="cal-inp" id="calEnd">
            </div>
        </div>

        {{-- Local --}}
        <div style="margin-bottom:18px">
            <label style="display:block;font-size:13px;font-weight:500;color:#374151;margin-bottom:6px">
                <i class="bi bi-geo-alt" style="margin-right:4px;color:#9ca3af"></i>Local
            </label>
            <input type="text" class="cal-inp" id="calLocation" placeholder="Ex: Google Meet, Sala de reunião…">
        </div>

        {{-- Convidados --}}
        <div style="margin-bottom:18px">
            <label style="display:block;font-size:13px;font-weight:500;color:#374151;margin-bottom:6px">
                <i class="bi bi-person-plus" style="margin-right:4px;color:#9ca3af"></i>Convidados
            </label>
            <div id="calAttendeeTags" style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:8px"></div>
            <div style="display:flex;gap:8px">
                <input type="email" class="cal-inp" id="calAttendeeInput" placeholder="email@exemplo.com"
                       style="flex:1"
                       onkeydown="if(event.key==='Enter'){event.preventDefault();addCalAttendee()}">
                <button type="button" onclick="addCalAttendee()" style="
                    padding:9px 14px;background:#f1f5f9;border:1px solid #e2e8f0;
                    border-radius:8px;font-size:13px;font-weight:500;color:#374151;
                    cursor:pointer;white-space:nowrap;flex-shrink:0;
                "><i class="bi bi-plus"></i> Adicionar</button>
            </div>
            <p style="margin:6px 0 0;font-size:12px;color:#94a3b8">
                Os convidados receberão um convite por e-mail do Google Calendar.
            </p>
        </div>

        {{-- Descrição --}}
        <div style="margin-bottom:18px">
            <label style="display:block;font-size:13px;font-weight:500;color:#374151;margin-bottom:6px">
                <i class="bi bi-card-text" style="margin-right:4px;color:#9ca3af"></i>Descrição
            </label>
            <textarea class="cal-inp" id="calDescription" rows="4"
                      placeholder="Observações sobre o evento…"
                      style="resize:vertical"></textarea>
        </div>

        <div id="calError" style="display:none;background:#fef2f2;border:1px solid #fecaca;
             color:#dc2626;border-radius:8px;padding:10px 14px;font-size:13px;margin-bottom:12px"></div>
    </div>

    {{-- Footer --}}
    <div style="display:flex;align-items:center;gap:10px;
                padding:16px 24px;border-top:1px solid #e2e8f0;flex-shrink:0">
        <button id="btnCalDelete" onclick="deleteEvent()" style="
            display:none;margin-right:auto;
            padding:8px 14px;background:#fff;border:1px solid #fecaca;
            color:#dc2626;border-radius:8px;font-size:13px;font-weight:500;cursor:pointer;
        "><i class="bi bi-trash3" style="margin-right:4px"></i>Excluir</button>
        <button onclick="closeCalDrawer()" style="
            padding:8px 20px;background:#f1f5f9;border:1px solid #e2e8f0;
            color:#374151;border-radius:8px;font-size:14px;font-weight:500;cursor:pointer;
        ">Cancelar</button>
        <button id="btnCalSave" onclick="saveEvent()" style="
            padding:8px 24px;background:#0085f3;border:none;
            color:#fff;border-radius:8px;font-size:14px;font-weight:500;cursor:pointer;
            transition:background .15s;
        ">Salvar</button>
    </div>
</aside>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script>
'use strict';

const CSRF   = document.querySelector('meta[name=csrf-token]')?.content;
const ROUTES = {
    events:  '{{ route('calendar.events') }}',
    store:   '{{ route('calendar.store') }}',
    update:  id => '{{ url('agenda/eventos') }}/' + id,
    destroy: id => '{{ url('agenda/eventos') }}/' + id,
};

// ── Color palette ─────────────────────────────────────────────────────────
const PALETTE = ['#4285f4','#0f9d58','#f4b400','#db4437','#ab47bc','#00bcd4','#ff7043','#795548','#607d8b','#e91e63'];
function eventColor(str) {
    let h = 0;
    for (let i = 0; i < (str || '').length; i++) h = (Math.imul(31, h) + str.charCodeAt(i)) | 0;
    return PALETTE[Math.abs(h) % PALETTE.length];
}

// ── State ─────────────────────────────────────────────────────────────────
let calendar;
let currentEventId  = null;
let popupEvent      = null;
let cachedEvents    = [];
let eventDateSet    = new Set();
let calAttendees    = [];

// ── PT-BR helpers ─────────────────────────────────────────────────────────
const MONTHS_LONG  = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
const MONTHS_SHORT = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
const WDAYS_SHORT  = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];
const WDAYS_MIN    = ['D','S','T','Q','Q','S','S'];

// ── Mini Calendar ─────────────────────────────────────────────────────────
let miniDate = new Date();

function renderMiniCal() {
    const el     = document.getElementById('miniCal');
    const year   = miniDate.getFullYear();
    const month  = miniDate.getMonth();
    const today  = new Date();
    const first  = new Date(year, month, 1).getDay();
    const days   = new Date(year, month + 1, 0).getDate();
    const prevD  = new Date(year, month, 0).getDate();

    let html = `<div class="mini-cal">
        <div class="mini-cal-nav">
            <button onclick="mcPrev()">‹</button>
            <span class="mc-title">${MONTHS_LONG[month]} ${year}</span>
            <button onclick="mcNext()">›</button>
        </div>
        <div class="mc-grid">`;

    WDAYS_MIN.forEach(d => { html += `<div class="mc-head">${d}</div>`; });

    for (let i = first - 1; i >= 0; i--)
        html += `<div class="mc-day mc-empty mc-other">${prevD - i}</div>`;

    for (let d = 1; d <= days; d++) {
        const ds   = `${year}-${String(month+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
        const isTd = today.getFullYear()===year && today.getMonth()===month && today.getDate()===d;
        const hasE = eventDateSet.has(ds);
        const cls  = (isTd ? ' mc-today' : '') + (hasE ? ' mc-has-event' : '');
        html += `<div class="mc-day${cls}" onclick="mcGoto('${ds}')" title="${ds}">${d}</div>`;
    }

    const total = first + days;
    const fill  = total % 7 === 0 ? 0 : 7 - (total % 7);
    for (let i = 1; i <= fill; i++)
        html += `<div class="mc-day mc-empty mc-other">${i}</div>`;

    html += '</div></div>';
    el.innerHTML = html;
}

function mcPrev() { miniDate.setMonth(miniDate.getMonth() - 1); renderMiniCal(); }
function mcNext() { miniDate.setMonth(miniDate.getMonth() + 1); renderMiniCal(); }
function mcGoto(ds) { closePopup(); calendar.gotoDate(ds); calendar.changeView('timeGridDay'); }

// ── Upcoming Events List ──────────────────────────────────────────────────
function renderUpcoming(events) {
    const el  = document.getElementById('upcomingList');
    const now = new Date();
    const list = [...events]
        .filter(e => new Date(e.start) >= now)
        .sort((a, b) => new Date(a.start) - new Date(b.start))
        .slice(0, 7);

    if (!list.length) {
        el.innerHTML = '<div class="up-empty">Sem eventos próximos</div>';
        return;
    }

    el.innerHTML = list.map(e => {
        const color  = eventColor(e.id || e.title || '');
        const d      = new Date(e.start);
        const dLabel = WDAYS_SHORT[d.getDay()] + ', ' + d.getDate() + ' ' + MONTHS_SHORT[d.getMonth()];
        const tLabel = (d.getHours() || d.getMinutes())
            ? ' · ' + String(d.getHours()).padStart(2,'0') + ':' + String(d.getMinutes()).padStart(2,'0')
            : '';
        return `<div class="up-item" onclick="mcGoto('${e.start.substr(0,10)}')">
            <div class="up-dot" style="background:${color}"></div>
            <div class="up-body">
                <div class="up-title">${esc(e.title || 'Sem título')}</div>
                <div class="up-date">${dLabel}${tLabel}</div>
            </div>
        </div>`;
    }).join('');
}

// ── FullCalendar Init ─────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    renderMiniCal();

    calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
        initialView:  'dayGridMonth',
        headerToolbar: {
            left:   'prev,next today',
            center: 'title',
            right:  'dayGridMonth,timeGridWeek,timeGridDay',
        },
        buttonText: {
            today:  'Hoje',
            month:  'Mês',
            week:   'Semana',
            day:    'Dia',
        },
        locale: 'pt-br',
        firstDay: 0,

        editable:    true,
        selectable:  true,
        height:      'auto',
        nowIndicator: true,

        events(info, ok, fail) {
            fetch(`${ROUTES.events}?start=${info.startStr}&end=${info.endStr}`, {
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': CSRF },
            })
            .then(r => r.json())
            .then(data => {
                if (data.error) {
                    toastr.error('Erro ao carregar agenda: ' + data.error, 'Calendário');
                    fail(data.error);
                    return;
                }
                const colored = data.map(e => ({
                    ...e,
                    backgroundColor: eventColor(e.id || e.title || ''),
                    borderColor:     eventColor(e.id || e.title || ''),
                    textColor:       '#fff',
                }));
                cachedEvents = data;
                eventDateSet = new Set(data.map(e => (e.start||'').substr(0,10)).filter(Boolean));
                renderMiniCal();
                renderUpcoming(data);
                ok(colored);
            })
            .catch(err => {
                toastr.error('Não foi possível conectar ao Google Calendar. Verifique a integração nas configurações.', 'Calendário');
                fail(err);
            });
        },

        dateClick(info) {
            closePopup();
            openCreateModal(info.dateStr);
        },
        select(info) {
            closePopup();
            openCreateModal(info.startStr, info.endStr);
        },
        eventClick(info) {
            info.jsEvent.stopPropagation();
            showPopup(info.event, info.jsEvent);
        },
        eventDrop(info)   { closePopup(); patchDates(info.event); },
        eventResize(info) { patchDates(info.event); },
    });

    calendar.render();

    document.addEventListener('click', e => {
        if (!document.getElementById('evPopup').contains(e.target)) closePopup();
    });

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') { closeCalDrawer(); closePopup(); }
    });
});

// ── Event Popup ───────────────────────────────────────────────────────────
function showPopup(event, jsEvent) {
    popupEvent = event;
    const color = eventColor(event.id || event.title || '');
    document.getElementById('popupDot').style.background = color;
    document.getElementById('popupTitle').textContent = event.title || 'Sem título';

    let body = '';
    if (event.start) {
        const s = fmtDT(event.start);
        const e = event.end ? fmtDT(event.end) : null;
        body += row('bi-clock', s + (e ? ' → ' + e : ''));
    }
    const loc       = event.extendedProps?.location    || '';
    const desc      = event.extendedProps?.description || '';
    const attendees = event.extendedProps?.attendees   || [];
    if (loc)  body += row('bi-geo-alt', esc(loc));
    if (attendees.length) {
        const emails = attendees.map(a => esc(a.email || a)).join(', ');
        body += row('bi-people', emails);
    }
    if (desc) body += row('bi-card-text', `<span style="white-space:pre-line">${esc(desc.substring(0,250))}${desc.length>250?'…':''}</span>`);

    document.getElementById('popupBody').innerHTML = body;

    const popup = document.getElementById('evPopup');
    popup.classList.add('open');
    popup.style.display = 'block';

    const rr  = jsEvent.target.getBoundingClientRect();
    const wW  = window.innerWidth;
    const wH  = window.innerHeight;
    let left  = rr.right + 10;
    if (left + 310 > wW) left = rr.left - 315;
    if (left < 8) left = 8;
    let top = rr.top;
    popup.style.left = left + 'px';
    popup.style.top  = top  + 'px';
    const pH = popup.offsetHeight;
    if (top + pH > wH - 8) popup.style.top = Math.max(8, wH - pH - 8) + 'px';
}

function closePopup() {
    const p = document.getElementById('evPopup');
    p.classList.remove('open');
    p.style.display = 'none';
    popupEvent = null;
}

function editFromPopup()   { if (popupEvent) { const ev = popupEvent; closePopup(); openEditModal(ev); } }
function deleteFromPopup() { if (popupEvent) { currentEventId = popupEvent.id; closePopup(); deleteEvent(); } }

function row(icon, content) {
    return `<div class="ev-popup-row"><i class="bi ${icon}"></i><span>${content}</span></div>`;
}

function fmtDT(d) {
    if (!(d instanceof Date)) d = new Date(d);
    const p = n => String(n).padStart(2,'0');
    return `${WDAYS_SHORT[d.getDay()]}, ${p(d.getDate())}/${p(d.getMonth()+1)} ${p(d.getHours())}:${p(d.getMinutes())}`;
}

function esc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// ── Drawer helpers ────────────────────────────────────────────────────────
function openCalDrawer() {
    document.getElementById('calDrawerOverlay').style.display = 'block';
    document.getElementById('calDrawer').style.transform = 'translateX(0)';
    document.body.style.overflow = 'hidden';
}

function closeCalDrawer() {
    document.getElementById('calDrawerOverlay').style.display = 'none';
    document.getElementById('calDrawer').style.transform = 'translateX(100%)';
    document.body.style.overflow = '';
}

// Alias para compatibilidade com chamadas existentes
function closeModal() { closeCalDrawer(); }

// ── Create / Edit ─────────────────────────────────────────────────────────
function openCreateModal(start, end) {
    currentEventId = null;
    calAttendees   = [];
    document.getElementById('calDrawerTitle').textContent    = 'Novo Evento';
    document.getElementById('calEventId').value              = '';
    document.getElementById('calTitle').value                = '';
    document.getElementById('calStart').value                = start ? toDTL(start) : '';
    document.getElementById('calEnd').value                  = end   ? toDTL(end)   : (start ? toDTL(addHour(start)) : '');
    document.getElementById('calLocation').value             = '';
    document.getElementById('calDescription').value          = '';
    document.getElementById('calAttendeeInput').value        = '';
    document.getElementById('btnCalDelete').style.display    = 'none';
    renderAttendeeTags();
    hideErr();
    openCalDrawer();
    setTimeout(() => document.getElementById('calTitle').focus(), 300);
}

function openEditModal(event) {
    currentEventId = event.id;
    const rawAttendees = event.extendedProps?.attendees || [];
    calAttendees = rawAttendees.map(a => (typeof a === 'string' ? a : a.email)).filter(Boolean);

    document.getElementById('calDrawerTitle').textContent    = 'Editar Evento';
    document.getElementById('calEventId').value              = event.id;
    document.getElementById('calTitle').value                = event.title || '';
    document.getElementById('calStart').value                = event.start ? toDTL(event.start.toISOString()) : '';
    document.getElementById('calEnd').value                  = event.end   ? toDTL(event.end.toISOString())   : '';
    document.getElementById('calLocation').value             = event.extendedProps?.location    || '';
    document.getElementById('calDescription').value          = event.extendedProps?.description || '';
    document.getElementById('calAttendeeInput').value        = '';
    document.getElementById('btnCalDelete').style.display    = 'inline-flex';
    renderAttendeeTags();
    hideErr();
    openCalDrawer();
}

// ── Attendees ─────────────────────────────────────────────────────────────
function addCalAttendee() {
    const input = document.getElementById('calAttendeeInput');
    const email = input.value.trim();
    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return;
    if (calAttendees.includes(email)) { input.value = ''; return; }
    calAttendees.push(email);
    input.value = '';
    renderAttendeeTags();
}

function removeCalAttendee(email) {
    calAttendees = calAttendees.filter(e => e !== email);
    renderAttendeeTags();
}

function renderAttendeeTags() {
    const container = document.getElementById('calAttendeeTags');
    if (!calAttendees.length) { container.innerHTML = ''; return; }
    container.innerHTML = calAttendees.map(email => `
        <span style="display:inline-flex;align-items:center;gap:4px;
                     background:#eff6ff;border:1px solid #bfdbfe;
                     color:#1d4ed8;border-radius:20px;padding:3px 10px;font-size:12px;
                     font-family:inherit">
            <i class="bi bi-envelope" style="font-size:11px;opacity:.7"></i>
            ${esc(email)}
            <button type="button" onclick="removeCalAttendee(${JSON.stringify(email)})"
                    style="background:none;border:none;cursor:pointer;color:#93c5fd;
                           font-size:15px;line-height:1;padding:0;margin-left:2px">×</button>
        </span>
    `).join('');
}

// ── Error helpers ─────────────────────────────────────────────────────────
function hideErr()    { const e = document.getElementById('calError'); e.style.display = 'none'; e.textContent = ''; }
function showErr(msg) { const e = document.getElementById('calError'); e.textContent = msg; e.style.display = 'block'; }

// ── Save ──────────────────────────────────────────────────────────────────
async function saveEvent() {
    const title = document.getElementById('calTitle').value.trim();
    const start = document.getElementById('calStart').value;
    const end   = document.getElementById('calEnd').value;

    if (!title)        { showErr('O título é obrigatório.'); return; }
    if (!start)        { showErr('Informe a data/hora de início.'); return; }
    if (!end)          { showErr('Informe a data/hora de fim.'); return; }
    if (start >= end)  { showErr('O fim deve ser após o início.'); return; }

    const btn = document.getElementById('btnCalSave');
    btn.disabled = true; btn.textContent = 'Salvando…';
    hideErr();

    const isEdit = !!currentEventId;
    try {
        const res  = await fetch(isEdit ? ROUTES.update(currentEventId) : ROUTES.store, {
            method:  isEdit ? 'PUT' : 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' },
            body: JSON.stringify({
                title,
                start:       toISO(start),
                end:         toISO(end),
                location:    document.getElementById('calLocation').value.trim(),
                description: document.getElementById('calDescription').value.trim(),
                attendees:   calAttendees.join(','),
            }),
        });
        const data = await res.json();
        if (data.success) { closeCalDrawer(); calendar.refetchEvents(); }
        else showErr(data.message || 'Erro ao salvar evento.');
    } catch { showErr('Erro de conexão. Tente novamente.'); }

    btn.disabled = false; btn.textContent = 'Salvar';
}

// ── Delete ────────────────────────────────────────────────────────────────
async function deleteEvent() {
    if (!currentEventId) return;
    if (!confirm('Excluir este evento do Google Calendar?')) return;
    try {
        const res  = await fetch(ROUTES.destroy(currentEventId), {
            method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' },
        });
        const data = await res.json();
        if (data.success) { closeCalDrawer(); calendar.refetchEvents(); }
        else showErr(data.message || 'Erro ao excluir.');
    } catch { showErr('Erro de conexão.'); }
}

// ── Drag/resize update ────────────────────────────────────────────────────
async function patchDates(event) {
    try {
        await fetch(ROUTES.update(event.id), {
            method:  'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ start: event.start?.toISOString(), end: event.end?.toISOString() }),
        });
    } catch { calendar.refetchEvents(); }
}

// ── Date utils ────────────────────────────────────────────────────────────
function toDTL(iso) {
    if (!iso) return '';
    const d = new Date(iso);
    const p = n => String(n).padStart(2,'0');
    return `${d.getFullYear()}-${p(d.getMonth()+1)}-${p(d.getDate())}T${p(d.getHours())}:${p(d.getMinutes())}`;
}
function toISO(dtl) { return new Date(dtl).toISOString(); }
function addHour(iso) { const d = new Date(iso); d.setHours(d.getHours()+1); return d.toISOString(); }
</script>
@endpush

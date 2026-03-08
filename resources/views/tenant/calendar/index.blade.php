@extends('tenant.layouts.app')

@php
    $title    = 'Agenda';
    $pageIcon = 'calendar3';
@endphp

@section('topbar_actions')
<div class="topbar-actions" style="gap:8px;">
    <button class="btn-primary-sm" onclick="openCreateModal()">
        <i class="bi bi-plus-lg"></i> Novo evento
    </button>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css">
<style>
/* ── Main calendar wrapper ───────────────────────────────────────────────── */
.cal-main {
    background: #fff;
    border: 1.5px solid #e8eaf0;
    border-radius: 16px;
    padding: 24px 28px 28px;
    min-height: 600px;
}

/* ══════════════════════════════════════════════════════════════════════════
   FullCalendar — Slothui Overrides
   ══════════════════════════════════════════════════════════════════════════ */

.fc {
    --fc-border-color: #f0f1f4;
    --fc-today-bg-color: transparent;
    --fc-neutral-bg-color: transparent;
}

/* ── Custom Toolbar (replaces FC toolbar) ────────────────────────────────── */
.fc .fc-toolbar.fc-header-toolbar { display: none !important; }

.sloth-toolbar {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 24px;
    flex-wrap: wrap;
}
.sloth-title {
    font-size: 26px;
    font-weight: 800;
    color: #1a1d23;
    letter-spacing: -.03em;
    line-height: 1.2;
    white-space: nowrap;
}
.sloth-today-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: none;
    border: 1.5px solid #e2e6ed;
    border-radius: 20px;
    padding: 6px 16px;
    font-size: 13px;
    font-weight: 600;
    color: #374151;
    cursor: pointer;
    transition: all .15s;
    white-space: nowrap;
}
.sloth-today-btn:hover { background: #f7f9fc; border-color: #d1d5db; }
.sloth-today-btn .dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #22c55e;
    flex-shrink: 0;
}
.sloth-nav-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: 1.5px solid #e2e6ed;
    background: #fff;
    color: #6b7280;
    font-size: 16px;
    cursor: pointer;
    transition: all .15s;
    flex-shrink: 0;
}
.sloth-nav-btn:hover { background: #f3f4f6; color: #374151; border-color: #d1d5db; }
.sloth-spacer { flex: 1; }
.sloth-view-group {
    display: inline-flex;
    background: #f3f5f8;
    border-radius: 12px;
    padding: 4px;
    gap: 2px;
}
.sloth-view-btn {
    padding: 7px 18px;
    border: none;
    border-radius: 9px;
    font-size: 13px;
    font-weight: 600;
    color: #6b7280;
    background: transparent;
    cursor: pointer;
    transition: all .15s;
    white-space: nowrap;
}
.sloth-view-btn:hover { color: #374151; background: rgba(0,0,0,.03); }
.sloth-view-btn.active {
    background: #fff;
    color: #1a1d23;
    box-shadow: 0 1px 4px rgba(0,0,0,.08);
}

/* ── Column headers ──────────────────────────────────────────────────────── */
.fc .fc-col-header-cell {
    background: transparent;
    border: none !important;
    padding-bottom: 4px;
}
.fc .fc-col-header-cell-cushion {
    font-size: 13px;
    font-weight: 700;
    color: #1a1d23;
    text-transform: capitalize;
    text-decoration: none;
    padding: 10px 8px;
    letter-spacing: .01em;
}

/* ── Day cells ───────────────────────────────────────────────────────────── */
.fc .fc-daygrid-day {
    transition: background .12s;
    border-color: #f0f1f4 !important;
}
.fc .fc-daygrid-day:hover { background: #fafbfe; }
.fc .fc-daygrid-day-frame {
    min-height: 100px;
    padding: 4px;
}

/* ── Day numbers ─────────────────────────────────────────────────────────── */
.fc .fc-daygrid-day-top {
    flex-direction: row;
    padding: 6px 8px 2px;
}
.fc .fc-daygrid-day-number {
    font-size: 14px;
    font-weight: 600;
    color: #374151;
    text-decoration: none;
    padding: 0;
}
.fc .fc-daygrid-day.fc-day-other .fc-daygrid-day-number {
    color: #d1d5db;
    font-weight: 400;
}

/* ── Today ───────────────────────────────────────────────────────────────── */
.fc .fc-daygrid-day.fc-day-today {
    background: transparent !important;
}
.fc .fc-daygrid-day.fc-day-today .fc-daygrid-day-number {
    background: #3b82f6;
    color: #fff !important;
    border-radius: 50%;
    width: 30px;
    height: 30px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 14px;
}

/* ══════════════════════════════════════════════════════════════════════════
   Events — Slothui colored pill blocks
   ══════════════════════════════════════════════════════════════════════════ */
.fc .fc-event {
    border: none !important;
    border-radius: 8px !important;
    padding: 4px 10px !important;
    font-size: 12px !important;
    font-weight: 600 !important;
    cursor: pointer;
    transition: opacity .12s, transform .12s;
    line-height: 1.4 !important;
    margin-bottom: 2px;
}
.fc .fc-event:hover {
    opacity: .88;
    transform: scale(1.015);
}
.fc .fc-daygrid-event-harness {
    margin: 1px 3px;
}
.fc .fc-daygrid-block-event .fc-event-time {
    font-weight: 500 !important;
    opacity: .9;
    padding-right: 3px;
    font-size: 11px !important;
}
.fc .fc-daygrid-block-event .fc-event-title {
    font-weight: 600 !important;
}
/* Force block display — hide dot style */
.fc .fc-daygrid-dot-event { display: none !important; }

.fc a { text-decoration: none !important; }
.fc a:hover { color: inherit; }

/* ── More events link ────────────────────────────────────────────────────── */
.fc .fc-daygrid-more-link {
    font-size: 11.5px;
    font-weight: 700;
    color: #6366f1;
    padding: 3px 6px;
    border-radius: 6px;
}
.fc .fc-daygrid-more-link:hover { background: #eef2ff; }

/* ── Time grid ───────────────────────────────────────────────────────────── */
.fc .fc-timegrid-slot { height: 52px; }
.fc .fc-timegrid-slot-label-cushion {
    font-size: 11.5px;
    font-weight: 500;
    color: #9ca3af;
}
.fc .fc-timegrid-event {
    border-radius: 10px !important;
    border-left: 4px solid rgba(0,0,0,.12) !important;
}
.fc .fc-timegrid-event .fc-event-main {
    padding: 6px 10px;
    font-size: 12.5px;
}
.fc .fc-timegrid-event .fc-event-time {
    font-size: 11px;
    font-weight: 500;
    opacity: .85;
}

/* ── Now indicator ───────────────────────────────────────────────────────── */
.fc .fc-timegrid-now-indicator-line {
    border-color: #ef4444;
    border-width: 2px;
}
.fc .fc-timegrid-now-indicator-arrow {
    border-top-color: #ef4444;
    border-bottom-color: #ef4444;
}

/* ── Grid borders ────────────────────────────────────────────────────────── */
.fc .fc-scrollgrid { border: none !important; }
.fc .fc-scrollgrid td,
.fc .fc-scrollgrid th {
    border-color: #f0f1f4 !important;
}
.fc table { border-collapse: collapse; }
.fc .fc-scrollgrid-section-header > * {
    border-bottom: 2px solid #eef0f4;
}

/* ── Event Popup ─────────────────────────────────────────────────────────── */
.ev-popup {
    position: fixed;
    z-index: 1060;
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 12px 48px rgba(0,0,0,.15), 0 2px 8px rgba(0,0,0,.06);
    width: 310px;
    padding: 18px;
    display: none;
    animation: popIn .15s cubic-bezier(.4,0,.2,1);
}
@keyframes popIn {
    from { opacity: 0; transform: scale(.92) translateY(4px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
}
.ev-popup.open { display: block; }
.ev-popup-header {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-bottom: 12px;
}
.ev-popup-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; margin-top: 5px; }
.ev-popup-title { font-size: 14.5px; font-weight: 700; color: #1a1d23; flex: 1; line-height: 1.4; }
.ev-popup-btns { display: flex; gap: 2px; flex-shrink: 0; }
.ev-popup-btn {
    background: none; border: none; cursor: pointer; color: #9ca3af; padding: 5px 6px;
    border-radius: 8px; font-size: 14px; line-height: 1; transition: all .12s;
}
.ev-popup-btn:hover { background: #f3f4f6; color: #374151; }
.ev-popup-row {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    font-size: 12.5px;
    color: #4b5563;
    margin-top: 8px;
    line-height: 1.5;
}
.ev-popup-row i { color: #9ca3af; margin-top: 2px; flex-shrink: 0; font-size: 14px; }

/* ── Drawer inputs ───────────────────────────────────────────────────────── */
.cal-inp {
    width: 100%;
    padding: 10px 14px;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    font-size: 14px;
    font-family: inherit;
    color: #1a1d23;
    outline: none;
    box-sizing: border-box;
    transition: border-color .15s, box-shadow .15s;
    background: #fff;
}
.cal-inp:focus { border-color: #0085f3; box-shadow: 0 0 0 3px rgba(0,133,243,.1); }

/* ── FAB for mobile ── */
.cal-fab {
    display: none;
    position: fixed;
    bottom: 24px;
    right: 24px;
    width: 52px;
    height: 52px;
    background: #0085f3;
    color: #fff;
    border: none;
    border-radius: 50%;
    font-size: 22px;
    cursor: pointer;
    z-index: 100;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 16px rgba(0,133,243,.35);
    transition: transform .15s, box-shadow .15s;
}
.cal-fab:hover { transform: scale(1.06); box-shadow: 0 6px 24px rgba(0,133,243,.45); }

/* ── Mobile ── */
@media (max-width: 768px) {
    .cal-main {
        padding: 16px 14px 18px;
        border-radius: 14px;
    }
    .sloth-toolbar {
        gap: 10px;
        margin-bottom: 16px;
    }
    .sloth-title { font-size: 20px; }
    .sloth-today-btn { padding: 5px 12px; font-size: 12px; }
    .sloth-nav-btn { width: 32px; height: 32px; font-size: 14px; }
    .sloth-view-btn { padding: 5px 12px; font-size: 12px; }
    .fc .fc-daygrid-day-frame { min-height: 65px; }
    .fc .fc-col-header-cell-cushion { font-size: 11px; padding: 6px 4px; }
    .cal-fab { display: flex; }
    .ev-popup { width: calc(100vw - 32px); left: 16px !important; right: 16px; }
}
@media (max-width: 480px) {
    .cal-main { padding: 12px 10px 14px; }
    #calDrawer { width: 100vw !important; }
    .sloth-title { font-size: 18px; }
    .sloth-view-group { display: none; }
    .sloth-view-mobile {
        display: flex !important;
        width: 100%;
        order: 10;
    }
    .fc .fc-col-header-cell-cushion { font-size: 10px; padding: 4px 2px; }
    .fc .fc-daygrid-day-number { font-size: 12px; }
    .fc .fc-daygrid-day-frame { min-height: 50px; }
}
/* Mobile view selector — hidden on desktop */
.sloth-view-mobile {
    display: none;
    background: #f3f5f8;
    border-radius: 12px;
    padding: 4px;
    gap: 2px;
}
.sloth-view-mobile .sloth-view-btn {
    flex: 1;
    text-align: center;
}
</style>
@endpush

@section('content')
<div class="page-container">
    {{-- ── Main Calendar ───────────────────────────────────────────────── --}}
    <div class="cal-main">

        {{-- Custom Slothui Toolbar --}}
        <div class="sloth-toolbar">
            <span class="sloth-title" id="slothTitle"></span>
            <button class="sloth-today-btn" onclick="goToday()">
                <span class="dot"></span> Hoje
            </button>
            <button class="sloth-nav-btn" onclick="goPrev()"><i class="bi bi-chevron-left"></i></button>
            <button class="sloth-nav-btn" onclick="goNext()"><i class="bi bi-chevron-right"></i></button>
            <span class="sloth-spacer"></span>
            <div class="sloth-view-group" id="viewGroup">
                <button class="sloth-view-btn active" data-view="dayGridMonth" onclick="switchView(this)">Mes</button>
                <button class="sloth-view-btn" data-view="timeGridWeek" onclick="switchView(this)">Semana</button>
                <button class="sloth-view-btn" data-view="timeGridDay" onclick="switchView(this)">Dia</button>
            </div>
        </div>

        {{-- Mobile view selector --}}
        <div class="sloth-view-mobile" id="viewGroupMobile">
            <button class="sloth-view-btn active" data-view="dayGridMonth" onclick="switchView(this)">Mes</button>
            <button class="sloth-view-btn" data-view="timeGridWeek" onclick="switchView(this)">Semana</button>
            <button class="sloth-view-btn" data-view="timeGridDay" onclick="switchView(this)">Dia</button>
        </div>

        <div id="calendar"></div>
    </div>
</div>

{{-- ── FAB mobile ─────────────────────────────────────────────────────── --}}
<button class="cal-fab" onclick="openCreateModal()">
    <i class="bi bi-plus-lg"></i>
</button>

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
        ">x</button>
    </div>

    {{-- Body (scrollable) --}}
    <div style="flex:1;overflow-y:auto;padding:22px 24px">
        <input type="hidden" id="calEventId">

        {{-- Titulo --}}
        <div style="margin-bottom:18px">
            <label style="display:block;font-size:13px;font-weight:500;color:#374151;margin-bottom:6px">
                Titulo <span style="color:#ef4444">*</span>
            </label>
            <input type="text" class="cal-inp" id="calTitle" placeholder="Ex: Reuniao com cliente">
        </div>

        {{-- Data/Hora --}}
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:18px">
            <div>
                <label style="display:block;font-size:13px;font-weight:500;color:#374151;margin-bottom:6px">Inicio</label>
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
            <input type="text" class="cal-inp" id="calLocation" placeholder="Ex: Google Meet, Sala de reuniao...">
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
                Os convidados receberao um convite por e-mail do Google Calendar.
            </p>
        </div>

        {{-- Descricao --}}
        <div style="margin-bottom:18px">
            <label style="display:block;font-size:13px;font-weight:500;color:#374151;margin-bottom:6px">
                <i class="bi bi-card-text" style="margin-right:4px;color:#9ca3af"></i>Descricao
            </label>
            <textarea class="cal-inp" id="calDescription" rows="4"
                      placeholder="Observacoes sobre o evento..."
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
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/locales/pt-br.global.min.js"></script>
<script>
'use strict';

const CSRF   = document.querySelector('meta[name=csrf-token]')?.content;
const ROUTES = {
    events:  '{{ route('calendar.events') }}',
    store:   '{{ route('calendar.store') }}',
    update:  id => '{{ url('agenda/eventos') }}/' + id,
    destroy: id => '{{ url('agenda/eventos') }}/' + id,
};

// ── Slothui Color palette (vibrant) ──────────────────────────────────────
const PALETTE = ['#6366f1','#22c55e','#f59e0b','#3b82f6','#ef4444','#8b5cf6','#14b8a6','#f97316','#ec4899','#06b6d4'];
function eventColor(str) {
    let h = 0;
    for (let i = 0; i < (str || '').length; i++) h = (Math.imul(31, h) + str.charCodeAt(i)) | 0;
    return PALETTE[Math.abs(h) % PALETTE.length];
}

// ── State ─────────────────────────────────────────────────────────────────
let calendar;
let currentEventId  = null;
let popupEvent      = null;
let calAttendees    = [];

// ── PT-BR helpers ─────────────────────────────────────────────────────────
const MONTHS_LONG  = ['Janeiro','Fevereiro','Marco','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
const WDAYS_SHORT  = ['Dom','Seg','Ter','Qua','Qui','Sex','Sab'];
// ── Custom toolbar controls ──────────────────────────────────────────────
function updateTitle() {
    const d = calendar.getDate();
    document.getElementById('slothTitle').textContent =
        MONTHS_LONG[d.getMonth()] + ' ' + d.getFullYear();
}

function goToday() { calendar.today(); updateTitle(); }
function goPrev()  { calendar.prev();  updateTitle(); }
function goNext()  { calendar.next();  updateTitle(); }

function switchView(btn) {
    const view = btn.dataset.view;
    calendar.changeView(view);
    // Update both desktop & mobile button groups
    document.querySelectorAll('.sloth-view-btn').forEach(b => {
        b.classList.toggle('active', b.dataset.view === view);
    });
    updateTitle();
}

// ── FullCalendar Init ─────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
        initialView:  'dayGridMonth',
        headerToolbar: false,
        locale:     'pt-br',
        allDayText: 'Dia inteiro',
        firstDay:   0,

        editable:    true,
        selectable:  true,
        height:      'auto',
        nowIndicator: true,
        eventDisplay: 'block',
        dayMaxEvents: 3,

        events(info, ok, fail) {
            fetch(`${ROUTES.events}?start=${info.startStr}&end=${info.endStr}`, {
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': CSRF },
            })
            .then(r => r.json())
            .then(data => {
                if (data.error) {
                    toastr.error('Erro ao carregar agenda: ' + data.error, 'Calendario');
                    fail(data.error);
                    return;
                }
                const colored = data.map(e => ({
                    ...e,
                    backgroundColor: eventColor(e.id || e.title || ''),
                    borderColor:     eventColor(e.id || e.title || ''),
                    textColor:       '#fff',
                }));
                ok(colored);
            })
            .catch(err => {
                toastr.error('Nao foi possivel conectar ao Google Calendar. Verifique a integracao nas configuracoes.', 'Calendario');
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
    updateTitle();

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
    document.getElementById('popupTitle').textContent = event.title || 'Sem titulo';

    let body = '';
    if (event.start) {
        const s = fmtDT(event.start);
        const e = event.end ? fmtDT(event.end) : null;
        body += row('bi-clock', s + (e ? ' &rarr; ' + e : ''));
    }
    const loc       = event.extendedProps?.location    || '';
    const desc      = event.extendedProps?.description || '';
    const attendees = event.extendedProps?.attendees   || [];
    if (loc)  body += row('bi-geo-alt', esc(loc));
    if (attendees.length) {
        const emails = attendees.map(a => esc(a.email || a)).join(', ');
        body += row('bi-people', emails);
    }
    if (desc) body += row('bi-card-text', `<span style="white-space:pre-line">${esc(desc.substring(0,250))}${desc.length>250?'...':''}</span>`);

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
                           font-size:15px;line-height:1;padding:0;margin-left:2px">x</button>
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

    if (!title)        { showErr('O titulo e obrigatorio.'); return; }
    if (!start)        { showErr('Informe a data/hora de inicio.'); return; }
    if (!end)          { showErr('Informe a data/hora de fim.'); return; }
    if (start >= end)  { showErr('O fim deve ser apos o inicio.'); return; }

    const btn = document.getElementById('btnCalSave');
    btn.disabled = true; btn.textContent = 'Salvando...';
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
    } catch { showErr('Erro de conexao. Tente novamente.'); }

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
    } catch { showErr('Erro de conexao.'); }
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

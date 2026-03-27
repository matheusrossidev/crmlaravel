@extends('tenant.layouts.app')

@php
    $title    = __('calendar.title');
    $pageIcon = 'calendar3';
@endphp

@if($calendarConnected)
@section('topbar_actions')
<div class="topbar-actions" style="gap:8px;">
    <button class="btn-primary-sm cal-hide-mobile" onclick="openCreateModal()">
        <i class="bi bi-plus-lg"></i> {{ __('calendar.new_event') }}
    </button>
</div>
@endsection
@endif

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css">
<style>
/* ══════════════════════════════════════════════════════════════════════════
   2-Column Layout: Sidebar + Main
   ══════════════════════════════════════════════════════════════════════════ */
.page-container { padding: 0 !important; max-width: none !important; }
.cal-layout {
    display: flex;
    height: calc(100vh - 56px);
    overflow: hidden;
}

/* ── Left Sidebar ─────────────────────────────────────────────────────── */
.cal-sidebar {
    width: 280px;
    flex-shrink: 0;
    height: 100%;
    overflow-y: auto;
    padding: 0;
    background: #fff;
    border-right: 1px solid #e8eaf0;
    display: flex;
    flex-direction: column;
    gap: 0;
}
.cal-sidebar-card {
    background: #fff;
    border: none;
    border-bottom: 1px solid #f0f1f4;
    border-radius: 0;
    padding: 16px 16px;
}
.cal-sidebar-card:last-child { border-bottom: none; }

/* ── Mini Calendar ────────────────────────────────────────────────────── */
.mini-cal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
}
.mini-cal-title {
    font-size: 14px;
    font-weight: 700;
    color: #1a1d23;
    text-transform: capitalize;
}
.mini-cal-nav {
    display: flex;
    gap: 4px;
}
.mini-cal-nav-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    border: 1px solid #e2e6ed;
    background: #fff;
    color: #6b7280;
    font-size: 13px;
    cursor: pointer;
    transition: all .15s;
}
.mini-cal-nav-btn:hover { background: #f3f4f6; color: #374151; }

/* Mini FC overrides */
#miniCalendar {
    font-size: 12px;
}
#miniCalendar .fc-toolbar.fc-header-toolbar { display: none !important; }
#miniCalendar .fc-scrollgrid { border: none !important; }
#miniCalendar .fc-scrollgrid td,
#miniCalendar .fc-scrollgrid th { border: none !important; }
#miniCalendar .fc-scrollgrid-section-header > * { border-bottom: none; }
#miniCalendar .fc-col-header-cell {
    background: transparent;
    padding: 0;
}
#miniCalendar .fc-col-header-cell-cushion {
    font-size: 11px;
    font-weight: 600;
    color: #9ca3af;
    text-transform: uppercase;
    text-decoration: none;
    padding: 4px 0;
}
#miniCalendar .fc-daygrid-day {
    border: none !important;
    cursor: pointer;
}
#miniCalendar .fc-daygrid-day:hover {
    background: #f3f4f6;
    border-radius: 50%;
}
#miniCalendar .fc-daygrid-day-frame {
    min-height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    padding: 0;
}
#miniCalendar .fc-daygrid-day-top {
    flex-direction: row;
    justify-content: center;
    padding: 0;
}
#miniCalendar .fc-daygrid-day-number {
    font-size: 12px;
    font-weight: 500;
    color: #374151;
    text-decoration: none;
    padding: 0;
    width: 28px;
    height: 28px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}
#miniCalendar .fc-daygrid-day.fc-day-other .fc-daygrid-day-number {
    color: #d1d5db;
    font-weight: 400;
}
#miniCalendar .fc-daygrid-day.fc-day-today {
    background: transparent !important;
}
#miniCalendar .fc-daygrid-day.fc-day-today .fc-daygrid-day-number {
    background: #0085f3;
    color: #fff !important;
    font-weight: 700;
}
#miniCalendar .fc-daygrid-day.mini-cal-selected .fc-daygrid-day-number {
    background: #eff6ff;
    color: #0085f3 !important;
    font-weight: 700;
}
#miniCalendar .fc-daygrid-event-harness { display: none !important; }
#miniCalendar .fc-daygrid-more-link { display: none !important; }
/* Event dot indicator */
#miniCalendar .fc-daygrid-day.has-events .fc-daygrid-day-number::after {
    content: '';
    position: absolute;
    bottom: 2px;
    left: 50%;
    transform: translateX(-50%);
    width: 4px;
    height: 4px;
    border-radius: 50%;
    background: #0085f3;
}
#miniCalendar .fc-daygrid-day-frame {
    position: relative;
}
#miniCalendar .fc {
    --fc-border-color: transparent;
    --fc-today-bg-color: transparent;
    --fc-neutral-bg-color: transparent;
}

/* ── Sidebar Filters ──────────────────────────────────────────────────── */
.cal-filter-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 14px;
}
.cal-filter-title {
    font-size: 13px;
    font-weight: 700;
    color: #1a1d23;
}
.cal-filter-clear {
    font-size: 12px;
    font-weight: 600;
    color: #0085f3;
    cursor: pointer;
    background: none;
    border: none;
    padding: 0;
    transition: color .15s;
}
.cal-filter-clear:hover { color: #0070d1; }

.cal-filter-label {
    font-size: 12px;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: .03em;
    margin-bottom: 8px;
}

.cal-filter-calendars {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin-bottom: 16px;
    max-height: 200px;
    overflow-y: auto;
}
.cal-filter-cal-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 4px 0;
    cursor: pointer;
    font-size: 13px;
    color: #374151;
    font-weight: 500;
}
.cal-filter-cal-item input[type="checkbox"] {
    width: 16px;
    height: 16px;
    border-radius: 4px;
    cursor: pointer;
    accent-color: #0085f3;
    flex-shrink: 0;
}
.cal-filter-cal-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    flex-shrink: 0;
}
.cal-filter-cal-name {
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.cal-filter-search {
    width: 100%;
    padding: 8px 12px 8px 32px;
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    font-size: 13px;
    font-family: inherit;
    color: #1a1d23;
    outline: none;
    box-sizing: border-box;
    transition: border-color .15s;
    background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' fill='%239ca3af' viewBox='0 0 16 16'%3E%3Cpath d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85zm-5.44 1.158a5.5 5.5 0 1 1 0-11 5.5 5.5 0 0 1 0 11z'/%3E%3C/svg%3E") 10px center no-repeat;
}
.cal-filter-search:focus { border-color: #0085f3; }
.cal-filter-search::placeholder { color: #9ca3af; }

/* Loading spinner for sidebar calendars */
.cal-filter-loading {
    text-align: center;
    padding: 12px 0;
    color: #9ca3af;
    font-size: 12px;
}

/* ── Right Main Area ──────────────────────────────────────────────────── */
.cal-main {
    flex: 1;
    min-width: 0;
    background: #fff;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    border-left: 1px solid #e8eaf0;
}
#calendar { flex: 1; min-height: 0; }

/* ══════════════════════════════════════════════════════════════════════════
   FullCalendar — Overrides
   ══════════════════════════════════════════════════════════════════════════ */
.fc {
    --fc-border-color: #f0f1f4;
    --fc-today-bg-color: transparent;
    --fc-neutral-bg-color: transparent;
}
.fc .fc-toolbar.fc-header-toolbar { display: none !important; }

/* ── Custom Toolbar ───────────────────────────────────────────────────── */
.cal-toolbar {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    border-bottom: 1px solid #e8eaf0;
    flex-shrink: 0;
    flex-wrap: wrap;
}
.cal-today-btn {
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
.cal-today-btn:hover { background: #f7f9fc; border-color: #d1d5db; }
.cal-today-btn .dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #22c55e;
    flex-shrink: 0;
}
.cal-nav-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    border-radius: 50%;
    border: 1.5px solid #e2e6ed;
    background: #fff;
    color: #6b7280;
    font-size: 15px;
    cursor: pointer;
    transition: all .15s;
    flex-shrink: 0;
}
.cal-nav-btn:hover { background: #f3f4f6; color: #374151; border-color: #d1d5db; }
.cal-toolbar-title {
    font-size: 16px;
    font-weight: 700;
    color: #1a1d23;
    white-space: nowrap;
}
.cal-toolbar-spacer { flex: 1; }
.cal-view-dropdown {
    position: relative;
    display: inline-block;
}
.cal-view-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 16px;
    border: 1.5px solid #e2e6ed;
    border-radius: 9px;
    font-size: 13px;
    font-weight: 600;
    color: #374151;
    background: #fff;
    cursor: pointer;
    transition: all .15s;
    white-space: nowrap;
}
.cal-view-btn:hover { background: #f7f9fc; border-color: #d1d5db; }
.cal-view-btn i { font-size: 15px; color: #0085f3; }
.cal-view-menu {
    display: none;
    position: absolute;
    top: calc(100% + 4px);
    right: 0;
    background: #fff;
    border: 1.5px solid #e8eaf0;
    border-radius: 10px;
    box-shadow: 0 8px 32px rgba(0,0,0,.1);
    min-width: 140px;
    z-index: 20;
    padding: 4px;
    animation: popIn .12s ease;
}
.cal-view-menu.open { display: block; }
.cal-view-option {
    display: block;
    width: 100%;
    padding: 8px 14px;
    border: none;
    background: none;
    font-size: 13px;
    font-weight: 500;
    color: #374151;
    cursor: pointer;
    border-radius: 7px;
    text-align: left;
    transition: background .12s;
}
.cal-view-option:hover { background: #f3f4f6; }
.cal-view-option.active { color: #0085f3; font-weight: 600; background: #eff6ff; }

/* Mobile sidebar toggle */
.cal-sidebar-toggle {
    display: none;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    border-radius: 8px;
    border: 1.5px solid #e2e6ed;
    background: #fff;
    color: #6b7280;
    font-size: 16px;
    cursor: pointer;
    transition: all .15s;
    flex-shrink: 0;
}
.cal-sidebar-toggle:hover { background: #f3f4f6; color: #374151; }

/* ── Column headers ──────────────────────────────────────────────────── */
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

/* Custom day header: number + weekday */
.cal-day-header {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
    padding: 6px 0;
    text-decoration: none;
}
.cal-day-header-num {
    font-size: 22px;
    font-weight: 700;
    color: #1a1d23;
    width: 36px;
    height: 36px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    line-height: 1;
}
.cal-day-header-num.is-today {
    background: #0085f3;
    color: #fff !important;
}
.cal-day-header-weekday {
    font-size: 11px;
    font-weight: 600;
    color: #9ca3af;
    text-transform: uppercase;
}

/* ── Day cells ────────────────────────────────────────────────────────── */
.fc .fc-daygrid-day {
    transition: background .12s;
    border-color: #f0f1f4 !important;
}
.fc .fc-daygrid-day:hover { background: #fafbfe; }
.fc .fc-daygrid-day-frame {
    min-height: 100px;
    padding: 4px;
}
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
.fc .fc-daygrid-day.fc-day-today {
    background: transparent !important;
}
.fc .fc-daygrid-day.fc-day-today .fc-daygrid-day-number {
    background: #0085f3;
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
   Events — colored pill blocks
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
.fc .fc-daygrid-event-harness { margin: 1px 3px; }
.fc .fc-daygrid-block-event .fc-event-time {
    font-weight: 500 !important;
    opacity: .9;
    padding-right: 3px;
    font-size: 11px !important;
}
.fc .fc-daygrid-block-event .fc-event-title { font-weight: 600 !important; }
.fc .fc-daygrid-dot-event { display: none !important; }

/* All-day events */
.fc .fc-daygrid-event.fc-allday-custom {
    padding: 1px 8px !important;
    font-size: 11px !important;
    font-weight: 600 !important;
    border-radius: 4px !important;
    opacity: .85;
    min-height: auto !important;
    line-height: 1.4 !important;
    margin-bottom: 0;
}
.fc .fc-daygrid-event.fc-allday-custom .fc-event-title {
    font-weight: 600 !important;
    font-size: 11px !important;
}
.fc .fc-daygrid-event.fc-allday-custom .fc-event-time { display: none !important; }

.fc a { text-decoration: none !important; }
.fc a:hover { color: inherit; }

/* ── More events link ─────────────────────────────────────────────────── */
.fc .fc-daygrid-more-link {
    font-size: 11.5px;
    font-weight: 700;
    color: #6366f1;
    padding: 3px 6px;
    border-radius: 6px;
}
.fc .fc-daygrid-more-link:hover { background: #eef2ff; }

/* ── Time grid ────────────────────────────────────────────────────────── */
.fc .fc-timegrid-slot { height: 48px; }
.fc .fc-timegrid-slot-label-cushion {
    font-size: 11px;
    font-weight: 500;
    color: #9ca3af;
}
.fc .fc-timegrid-event {
    border: none !important;
    border-left: 4px solid var(--ev-color, #0085f3) !important;
    border-radius: 6px !important;
    box-shadow: none !important;
    overflow: hidden;
}
.fc .fc-timegrid-event .fc-event-main {
    color: #1a1d23 !important;
    padding: 4px 8px !important;
    font-size: 12px !important;
}
.fc .fc-timegrid-event .ev-card-name {
    font-weight: 600;
    font-size: 12px;
    color: #1a1d23;
    line-height: 1.3;
    margin-bottom: 1px;
}
.fc .fc-timegrid-event .ev-card-desc {
    font-size: 11px;
    color: #4b5563;
    line-height: 1.3;
}
.fc .fc-timegrid-event .ev-card-time {
    font-size: 10px;
    color: #6b7280;
    margin-top: 2px;
}
.fc .fc-timegrid-event .fc-event-time {
    font-size: 11px;
    font-weight: 500;
    opacity: .85;
}

/* Custom event content */
.cal-event-content {
    display: flex;
    flex-direction: column;
    gap: 1px;
    line-height: 1.3;
    overflow: hidden;
}
.cal-event-title {
    font-size: 12px;
    font-weight: 700;
    color: #fff;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.fc-timegrid-event .cal-event-title {
    color: #1a1d23;
    font-weight: 600;
}
.cal-event-desc {
    font-size: 11px;
    font-weight: 400;
    color: rgba(255,255,255,.85);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.fc-timegrid-event .cal-event-desc {
    color: #4b5563;
}
.cal-event-time {
    font-size: 10px;
    font-weight: 500;
    color: rgba(255,255,255,.75);
    margin-top: 2px;
}
.fc-timegrid-event .cal-event-time {
    color: #6b7280;
}

/* ── Now indicator ────────────────────────────────────────────────────── */
.fc .fc-timegrid-now-indicator-line {
    border-color: #ef4444;
    border-width: 2px;
}
.fc .fc-timegrid-now-indicator-arrow {
    border-top-color: #ef4444;
    border-bottom-color: #ef4444;
}

/* ── Grid borders ─────────────────────────────────────────────────────── */
.fc .fc-scrollgrid { border: none !important; }
.fc .fc-scrollgrid td,
.fc .fc-scrollgrid th { border-color: #f0f1f4 !important; }
.fc table { border-collapse: collapse; }
.fc .fc-scrollgrid-section-header > * { border-bottom: 2px solid #eef0f4; }

/* Hide allDay slot in timeGrid views */
.fc .fc-timegrid .fc-daygrid-body { display: none !important; }
.fc .fc-timegrid-divider { display: none !important; }

/* ── Event Popup ──────────────────────────────────────────────────────── */
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

/* ── Drawer inputs ────────────────────────────────────────────────────── */
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

/* ── FAB ── */
.cal-fab {
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
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 16px rgba(0,133,243,.35);
    transition: transform .15s, box-shadow .15s;
}
.cal-fab:hover { transform: scale(1.06); box-shadow: 0 6px 24px rgba(0,133,243,.45); }

/* ── Mobile overlay for sidebar ── */
.cal-sidebar-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.35);
    z-index: 1039;
}
.cal-sidebar-overlay.open { display: block; }

/* ── Responsive ───────────────────────────────────────────────────────── */
@media (max-width: 768px) {
    .cal-layout { flex-direction: column; gap: 0; height: 100vh; }
    .cal-sidebar {
        position: fixed;
        top: 0; left: 0;
        width: 280px;
        height: 100vh;
        z-index: 1040;
        background: #fff;
        padding: 16px;
        gap: 12px;
        overflow-y: auto;
        transform: translateX(-100%);
        transition: transform .25s cubic-bezier(.4,0,.2,1);
        box-shadow: 4px 0 32px rgba(0,0,0,.12);
        border-right: none;
    }
    .cal-sidebar.open { transform: translateX(0); }
    .cal-sidebar-card {
        border: none;
        padding: 0;
        border-radius: 0;
    }
    .cal-sidebar-toggle { display: flex; }
    .cal-main {
        border-left: none;
    }
    .cal-toolbar { gap: 8px; padding: 10px 14px; }
    .cal-toolbar-title { font-size: 14px; }
    .cal-today-btn { padding: 5px 12px; font-size: 12px; }
    .cal-nav-btn { width: 30px; height: 30px; font-size: 13px; }
    .cal-hide-mobile { display: none !important; }
    .ev-popup { width: calc(100vw - 32px); left: 16px !important; right: 16px; }
    .fc .fc-daygrid-day-frame { min-height: 65px; }
    .fc .fc-col-header-cell-cushion { font-size: 11px; padding: 6px 4px; }
}
@media (max-width: 480px) {
    .cal-main { }
    #calDrawer { width: 100vw !important; }
    .cal-toolbar-title { font-size: 13px; }
    .cal-datetime-grid { grid-template-columns: 1fr !important; }
    .cal-inp[type="datetime-local"] { font-size: 13px; }
    .fc .fc-col-header-cell-cushion { font-size: 10px; padding: 4px 2px; }
    .fc .fc-daygrid-day-number { font-size: 12px; }
    .fc .fc-daygrid-day-frame { min-height: 50px; }
    .cal-day-header-num { font-size: 16px; width: 28px; height: 28px; }
    .cal-day-header-weekday { font-size: 9px; }
}
</style>
@endpush

@section('content')
<div class="page-container">

    @if(! $calendarConnected)
    {{-- ── Empty State — Google Calendar not connected ────────────────────── --}}
    <div style="background:#fff;border:1.5px solid #e8eaf0;border-radius:14px;display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:500px;text-align:center;padding:60px 24px;">
        <div style="width:80px;height:80px;border-radius:20px;background:#eff6ff;display:flex;align-items:center;justify-content:center;margin-bottom:24px;">
            <i class="bi bi-calendar3" style="font-size:36px;color:#0085f3;"></i>
        </div>
        <h2 style="font-size:22px;font-weight:700;color:#1a1d23;margin:0 0 8px;">{{ __('calendar.almost_ready') }}</h2>
        <p style="font-size:14px;color:#6b7280;max-width:420px;margin:0 0 28px;line-height:1.6;">
            {{ __('calendar.connect_desc') }}
        </p>
        <a href="{{ route('settings.integrations.index') }}"
           style="display:inline-flex;align-items:center;gap:8px;background:#0085f3;color:#fff;border:none;border-radius:9px;padding:10px 24px;font-size:13px;font-weight:600;text-decoration:none;transition:background .15s;">
            <i class="bi bi-google"></i> {{ __('calendar.connect_google') }}
        </a>
        <p style="font-size:12px;color:#9ca3af;margin-top:16px;">
            {{ __('calendar.redirect_note') }}
        </p>
    </div>
    @else
    {{-- ── 2-Column Layout ────────────────────────────────────────────────── --}}
    <div class="cal-layout">

        {{-- ── Left Sidebar ───────────────────────────────────────────────── --}}
        <div class="cal-sidebar" id="calSidebar">

            {{-- Mini Calendar --}}
            <div class="cal-sidebar-card">
                <div class="mini-cal-header">
                    <span class="mini-cal-title" id="miniCalTitle"></span>
                    <div class="mini-cal-nav">
                        <button class="mini-cal-nav-btn" onclick="miniCalPrev()"><i class="bi bi-chevron-left"></i></button>
                        <button class="mini-cal-nav-btn" onclick="miniCalNext()"><i class="bi bi-chevron-right"></i></button>
                    </div>
                </div>
                <div id="miniCalendar"></div>
            </div>

            {{-- Filters --}}
            <div class="cal-sidebar-card">
                <div class="cal-filter-header">
                    <span class="cal-filter-title" id="lblFilters">{{ __('calendar.filters') }}</span>
                    <button class="cal-filter-clear" onclick="clearFilters()">{{ __('calendar.clear_filters') }}</button>
                </div>

                {{-- Calendar checkboxes --}}
                <div class="cal-filter-label" id="lblCalFilter">{{ __('calendar.calendar_label_filter') }}</div>
                <div class="cal-filter-calendars" id="sidebarCalList">
                    <div class="cal-filter-loading"><i class="bi bi-arrow-repeat"></i> {{ __('calendar.loading_calendars') }}</div>
                </div>

                {{-- Search --}}
                <div class="cal-filter-label">{{ __('calendar.search_ph') }}</div>
                <input type="text" class="cal-filter-search" id="calSearchInput"
                       placeholder="{{ __('calendar.search_ph') }}"
                       oninput="filterEventsBySearch()">
            </div>
        </div>

        {{-- Mobile sidebar overlay --}}
        <div class="cal-sidebar-overlay" id="calSidebarOverlay" onclick="closeSidebar()"></div>

        {{-- ── Right Main Area ────────────────────────────────────────────── --}}
        <div class="cal-main">

            {{-- Toolbar --}}
            <div class="cal-toolbar">
                <button class="cal-sidebar-toggle" onclick="toggleSidebar()" title="Menu">
                    <i class="bi bi-list"></i>
                </button>
                <button class="cal-today-btn" onclick="goToday()">
                    <span class="dot"></span> {{ __('calendar.today') }}
                </button>
                <button class="cal-nav-btn" onclick="goPrev()"><i class="bi bi-chevron-left"></i></button>
                <button class="cal-nav-btn" onclick="goNext()"><i class="bi bi-chevron-right"></i></button>
                <span class="cal-toolbar-title" id="calToolbarTitle"></span>
                <span class="cal-toolbar-spacer"></span>
                <div class="cal-view-dropdown" id="viewDropdown">
                    <button class="cal-view-btn" onclick="toggleViewMenu()">
                        <i class="bi bi-calendar3"></i>
                        <span id="viewBtnLabel">{{ __('calendar.week') }}</span>
                        <i class="bi bi-chevron-down" style="font-size:11px;"></i>
                    </button>
                    <div class="cal-view-menu" id="viewMenu">
                        <button class="cal-view-option" data-view="timeGridDay" onclick="switchView(this)">{{ __('calendar.day') }}</button>
                        <button class="cal-view-option active" data-view="timeGridWeek" onclick="switchView(this)">{{ __('calendar.week') }}</button>
                        <button class="cal-view-option" data-view="dayGridMonth" onclick="switchView(this)">{{ __('calendar.month') }}</button>
                    </div>
                </div>
            </div>

            <div id="calendar"></div>
        </div>
    </div>
    @endif
</div>

@if($calendarConnected)
{{-- ── FAB ─────────────────────────────────────────────────────────────── --}}
<button class="cal-fab" onclick="openCreateModal()">
    <i class="bi bi-plus-lg"></i>
</button>

{{-- ── Event Popup ──────────────────────────────────────────────────────── --}}
<div class="ev-popup" id="evPopup">
    <div class="ev-popup-header">
        <div class="ev-popup-dot" id="popupDot"></div>
        <div class="ev-popup-title" id="popupTitle"></div>
        <div class="ev-popup-btns">
            <button class="ev-popup-btn" title="{{ __('calendar.edit') }}"   onclick="editFromPopup()"><i class="bi bi-pencil"></i></button>
            <button class="ev-popup-btn" title="{{ __('calendar.delete') }}" onclick="deleteFromPopup()"><i class="bi bi-trash3"></i></button>
            <button class="ev-popup-btn" title="{{ __('calendar.close') }}"  onclick="closePopup()"><i class="bi bi-x-lg"></i></button>
        </div>
    </div>
    <div id="popupBody"></div>
</div>

{{-- ── Overlay do drawer ──────────────────────────────────────────────── --}}
<div id="calDrawerOverlay" onclick="closeCalDrawer()" style="
    display:none; position:fixed; inset:0;
    background:rgba(0,0,0,.35); z-index:1049;
    transition:opacity .25s;
"></div>

{{-- ── Drawer de evento ───────────────────────────────────────────────── --}}
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
        <h3 id="calDrawerTitle" style="font-size:16px;font-weight:600;color:#0f172a;margin:0">{{ __('calendar.new_event_title') }}</h3>
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
                {{ __('calendar.event_title') }} <span style="color:#ef4444">*</span>
            </label>
            <input type="text" class="cal-inp" id="calTitle" placeholder="{{ __('calendar.event_title_ph') }}">
        </div>

        {{-- Data/Hora --}}
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:18px" class="cal-datetime-grid">
            <div>
                <label style="display:block;font-size:13px;font-weight:500;color:#374151;margin-bottom:6px">{{ __('calendar.start') }}</label>
                <input type="datetime-local" class="cal-inp" id="calStart" style="max-width:100%">
            </div>
            <div>
                <label style="display:block;font-size:13px;font-weight:500;color:#374151;margin-bottom:6px">{{ __('calendar.end') }}</label>
                <input type="datetime-local" class="cal-inp" id="calEnd" style="max-width:100%">
            </div>
        </div>

        {{-- Local --}}
        <div style="margin-bottom:18px">
            <label style="display:block;font-size:13px;font-weight:500;color:#374151;margin-bottom:6px">
                <i class="bi bi-geo-alt" style="margin-right:4px;color:#9ca3af"></i>{{ __('calendar.location') }}
            </label>
            <input type="text" class="cal-inp" id="calLocation" placeholder="{{ __('calendar.location_ph') }}">
        </div>

        {{-- Convidados --}}
        <div style="margin-bottom:18px">
            <label style="display:block;font-size:13px;font-weight:500;color:#374151;margin-bottom:6px">
                <i class="bi bi-person-plus" style="margin-right:4px;color:#9ca3af"></i>{{ __('calendar.attendees') }}
            </label>
            <div id="calAttendeeTags" style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:8px"></div>
            <div style="display:flex;gap:8px">
                <input type="email" class="cal-inp" id="calAttendeeInput" placeholder="{{ __('calendar.attendees_ph') }}"
                       style="flex:1"
                       onkeydown="if(event.key==='Enter'){event.preventDefault();addCalAttendee()}">
                <button type="button" onclick="addCalAttendee()" style="
                    padding:9px 14px;background:#f1f5f9;border:1px solid #e2e8f0;
                    border-radius:8px;font-size:13px;font-weight:500;color:#374151;
                    cursor:pointer;white-space:nowrap;flex-shrink:0;
                "><i class="bi bi-plus"></i> {{ __('calendar.add_attendee') }}</button>
            </div>
            <p style="margin:6px 0 0;font-size:12px;color:#94a3b8">
                {{ __('calendar.attendees_note') }}
            </p>
        </div>

        {{-- Descricao --}}
        <div style="margin-bottom:18px">
            <label style="display:block;font-size:13px;font-weight:500;color:#374151;margin-bottom:6px">
                <i class="bi bi-card-text" style="margin-right:4px;color:#9ca3af"></i>{{ __('calendar.description') }}
            </label>
            <textarea class="cal-inp" id="calDescription" rows="4"
                      placeholder="{{ __('calendar.description_ph') }}"
                      style="resize:vertical"></textarea>
        </div>

        <div id="calError" style="display:none;background:#fef2f2;border:1px solid #fecaca;
             color:#dc2626;border-radius:8px;padding:10px 14px;font-size:13px;margin-bottom:12px"></div>
    </div>

    {{-- Footer --}}
    <div style="display:flex;align-items:center;gap:10px;
                padding:16px 24px;padding-bottom:calc(16px + env(safe-area-inset-bottom, 0px));border-top:1px solid #e2e8f0;flex-shrink:0">
        <button id="btnCalDelete" onclick="deleteEvent()" style="
            display:none;margin-right:auto;
            padding:8px 14px;background:#fff;border:1px solid #fecaca;
            color:#dc2626;border-radius:8px;font-size:13px;font-weight:500;cursor:pointer;
        "><i class="bi bi-trash3" style="margin-right:4px"></i>{{ __('calendar.delete') }}</button>
        <button onclick="closeCalDrawer()" style="
            padding:8px 20px;background:#f1f5f9;border:1px solid #e2e8f0;
            color:#374151;border-radius:8px;font-size:14px;font-weight:500;cursor:pointer;
        ">{{ __('calendar.cancel') }}</button>
        <button id="btnCalSave" onclick="saveEvent()" style="
            padding:8px 24px;background:#0085f3;border:none;
            color:#fff;border-radius:8px;font-size:14px;font-weight:500;cursor:pointer;
            transition:background .15s;
        ">{{ __('calendar.save') }}</button>
    </div>
</aside>
@endif

@endsection

@if($calendarConnected)
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/locales/pt-br.global.min.js"></script>
<script>
'use strict';

const LANG = @json(__('calendar'));
// Add new keys if missing from lang files
if (!LANG.filters)              LANG.filters = '{{ __("calendar.filters") }}';
if (!LANG.clear_filters)        LANG.clear_filters = '{{ __("calendar.clear_filters") }}';
if (!LANG.search_ph)            LANG.search_ph = '{{ __("calendar.search_ph") }}';
if (!LANG.calendar_label_filter) LANG.calendar_label_filter = '{{ __("calendar.calendar_label_filter") }}';

const CSRF = document.querySelector('meta[name=csrf-token]')?.content;
const ROUTES = {
    events:      '{{ route('calendar.events') }}',
    store:       '{{ route('calendar.store') }}',
    update:      id => '{{ url('agenda/eventos') }}/' + id,
    destroy:     id => '{{ url('agenda/eventos') }}/' + id,
    calendars:   '{{ route('calendar.calendars') }}',
    preferences: '{{ route('calendar.preferences') }}',
};

// ── Color palette ────────────────────────────────────────────────────────
const PALETTE = ['#6366f1','#22c55e','#f59e0b','#3b82f6','#ef4444','#8b5cf6','#14b8a6','#f97316','#ec4899','#06b6d4'];
const calendarColorMap = {};
function eventColor(str) {
    if (str && calendarColorMap[str]) return calendarColorMap[str];
    let h = 0;
    for (let i = 0; i < (str || '').length; i++) h = (Math.imul(31, h) + str.charCodeAt(i)) | 0;
    return PALETTE[Math.abs(h) % PALETTE.length];
}

// ── State ────────────────────────────────────────────────────────────────
let calendar;
let miniCal;
let currentEventId  = null;
let popupEvent      = null;
let calAttendees    = [];
let searchFilter    = '';
let allFetchedEvents = []; // cache for search filtering

// ── PT-BR helpers ────────────────────────────────────────────────────────
const MONTHS_LONG  = LANG.months;
const WDAYS_SHORT  = LANG.weekdays_short;

// ── Toolbar title ────────────────────────────────────────────────────────
function updateTitle() {
    const view = calendar.view;
    const d    = calendar.getDate();
    let title  = '';

    if (view.type === 'timeGridWeek') {
        const start = view.activeStart;
        const end   = new Date(view.activeEnd);
        end.setDate(end.getDate() - 1);
        const p = n => String(n).padStart(2, '0');
        if (start.getMonth() === end.getMonth()) {
            title = `${p(start.getDate())} - ${p(end.getDate())} ${MONTHS_LONG[start.getMonth()].substring(0,3)} ${start.getFullYear()}`;
        } else {
            title = `${p(start.getDate())} ${MONTHS_LONG[start.getMonth()].substring(0,3)} - ${p(end.getDate())} ${MONTHS_LONG[end.getMonth()].substring(0,3)} ${start.getFullYear()}`;
        }
    } else if (view.type === 'timeGridDay') {
        const p = n => String(n).padStart(2, '0');
        title = `${p(d.getDate())} ${MONTHS_LONG[d.getMonth()]} ${d.getFullYear()}`;
    } else {
        title = MONTHS_LONG[d.getMonth()] + ' ' + d.getFullYear();
    }

    document.getElementById('calToolbarTitle').textContent = title;
}

function goToday() {
    calendar.today();
    updateTitle();
    syncMiniCal();
}
function goPrev() {
    calendar.prev();
    updateTitle();
    syncMiniCal();
}
function goNext() {
    calendar.next();
    updateTitle();
    syncMiniCal();
}

function switchView(btn) {
    const view = btn.dataset.view;
    calendar.changeView(view);
    // Update dropdown
    document.querySelectorAll('.cal-view-option').forEach(b => {
        b.classList.toggle('active', b.dataset.view === view);
    });
    // Update label
    document.getElementById('viewBtnLabel').textContent = btn.textContent.trim();
    closeViewMenu();
    updateTitle();
}

function toggleViewMenu() {
    document.getElementById('viewMenu').classList.toggle('open');
}
function closeViewMenu() {
    document.getElementById('viewMenu').classList.remove('open');
}

// ── Sidebar toggle (mobile) ─────────────────────────────────────────────
function toggleSidebar() {
    document.getElementById('calSidebar').classList.toggle('open');
    document.getElementById('calSidebarOverlay').classList.toggle('open');
    document.body.style.overflow = document.getElementById('calSidebar').classList.contains('open') ? 'hidden' : '';
}
function closeSidebar() {
    document.getElementById('calSidebar').classList.remove('open');
    document.getElementById('calSidebarOverlay').classList.remove('open');
    document.body.style.overflow = '';
}

// ── Mini Calendar ────────────────────────────────────────────────────────
function initMiniCal() {
    miniCal = new FullCalendar.Calendar(document.getElementById('miniCalendar'), {
        initialView: 'dayGridMonth',
        headerToolbar: false,
        locale: 'pt-br',
        firstDay: 0,
        height: 'auto',
        fixedWeekCount: false,
        dayMaxEvents: 0,
        dayCellDidMount(info) {
            // We mark days with events later via updateMiniCalDots()
        },
        dateClick(info) {
            // Navigate main calendar to that day's week
            calendar.gotoDate(info.dateStr);
            if (window.innerWidth <= 480) {
                calendar.changeView('timeGridDay');
                document.querySelectorAll('.cal-view-option').forEach(b => {
                    b.classList.toggle('active', b.dataset.view === 'timeGridDay');
                });
                document.getElementById('viewBtnLabel').textContent = LANG.day;
            } else if (calendar.view.type === 'dayGridMonth') {
                calendar.changeView('timeGridWeek');
                document.querySelectorAll('.cal-view-option').forEach(b => {
                    b.classList.toggle('active', b.dataset.view === 'timeGridWeek');
                });
                document.getElementById('viewBtnLabel').textContent = LANG.week;
            }
            updateTitle();
            // Highlight selected day
            document.querySelectorAll('#miniCalendar .mini-cal-selected').forEach(el => el.classList.remove('mini-cal-selected'));
            info.dayEl.classList.add('mini-cal-selected');
            closeSidebar();
        },
    });
    miniCal.render();
    updateMiniCalTitle();
}

function updateMiniCalTitle() {
    if (!miniCal) return;
    const d = miniCal.getDate();
    const el = document.getElementById('miniCalTitle');
    if (el) el.textContent = MONTHS_LONG[d.getMonth()] + ' ' + d.getFullYear();
}

function miniCalPrev() {
    miniCal.prev();
    updateMiniCalTitle();
}
function miniCalNext() {
    miniCal.next();
    updateMiniCalTitle();
}

function syncMiniCal() {
    if (!miniCal) return;
    miniCal.gotoDate(calendar.getDate());
    updateMiniCalTitle();
}

function updateMiniCalDots() {
    // Mark days that have events
    if (!miniCal) return;
    document.querySelectorAll('#miniCalendar .fc-daygrid-day').forEach(dayEl => {
        dayEl.classList.remove('has-events');
    });
    allFetchedEvents.forEach(ev => {
        const start = new Date(ev.start);
        const dateStr = start.getFullYear() + '-' +
            String(start.getMonth()+1).padStart(2,'0') + '-' +
            String(start.getDate()).padStart(2,'0');
        const dayEl = document.querySelector(`#miniCalendar .fc-daygrid-day[data-date="${dateStr}"]`);
        if (dayEl) dayEl.classList.add('has-events');
    });
}

// ── Search filter ────────────────────────────────────────────────────────
function filterEventsBySearch() {
    searchFilter = (document.getElementById('calSearchInput')?.value || '').trim().toLowerCase();
    calendar.refetchEvents();
}

function clearFilters() {
    // Reset search
    const searchEl = document.getElementById('calSearchInput');
    if (searchEl) searchEl.value = '';
    searchFilter = '';

    // Check all calendars
    document.querySelectorAll('#sidebarCalList input[type="checkbox"]').forEach(cb => {
        if (!cb.checked) {
            cb.checked = true;
            toggleCalVisible(cb);
        }
    });

    calendar.refetchEvents();
}

// ── Custom day header renderer ───────────────────────────────────────────
function renderDayHeader(arg) {
    const date = arg.date;
    const isToday = isSameDay(date, new Date());
    const num = date.getDate();
    const weekday = WDAYS_SHORT[date.getDay()];
    const el = document.createElement('div');
    el.className = 'cal-day-header';
    el.innerHTML = `
        <span class="cal-day-header-num ${isToday ? 'is-today' : ''}">${num}</span>
        <span class="cal-day-header-weekday">${weekday}</span>
    `;
    return { domNodes: [el] };
}

function isSameDay(a, b) {
    return a.getFullYear() === b.getFullYear() &&
           a.getMonth() === b.getMonth() &&
           a.getDate() === b.getDate();
}

// ── Custom event content renderer ────────────────────────────────────────
function renderEventContent(arg) {
    const ev = arg.event;
    const p = n => String(n).padStart(2, '0');
    let timeStr = '';
    if (ev.start) {
        timeStr = `${p(ev.start.getHours())}:${p(ev.start.getMinutes())}`;
        if (ev.end) timeStr += ` - ${p(ev.end.getHours())}:${p(ev.end.getMinutes())}`;
    }
    const desc = ev.extendedProps?.description || '';

    const el = document.createElement('div');
    el.className = 'cal-event-content';
    el.innerHTML = `
        <span class="cal-event-title">${esc(ev.title || LANG.no_title)}</span>
        ${desc ? `<span class="cal-event-desc">${esc(desc.substring(0, 60))}</span>` : ''}
        ${timeStr ? `<span class="cal-event-time">${timeStr}</span>` : ''}
    `;
    return { domNodes: [el] };
}

// ── FullCalendar Init ────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    // Default to timeGridDay on very small screens
    const defaultView = window.innerWidth <= 480 ? 'timeGridDay' : 'timeGridWeek';

    calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
        initialView:  defaultView,
        headerToolbar: false,
        locale:     'pt-br',
        allDayText: LANG.all_day,
        firstDay:   0,

        editable:    true,
        selectable:  true,
        height:      '100%',
        nowIndicator: true,
        eventDisplay: 'block',
        dayMaxEvents: 3,
        moreLinkText: LANG.more,

        slotMinTime: '07:00:00',
        slotMaxTime: '22:00:00',
        slotDuration: '00:30:00',

        dayHeaderContent: function(arg) {
            const viewType = calendar ? calendar.view.type : defaultView;
            if (viewType === 'timeGridWeek' || viewType === 'timeGridDay') {
                return renderDayHeader(arg);
            }
            // For month view, return default text
            return arg.text;
        },

        eventContent: function(arg) {
            const viewType = calendar ? calendar.view.type : defaultView;
            if (viewType === 'timeGridWeek' || viewType === 'timeGridDay') {
                return renderEventContent(arg);
            }
            return undefined; // default rendering for month
        },

        eventClassNames(arg) {
            return arg.event.allDay ? ['fc-allday-custom'] : [];
        },

        eventDidMount(info) {
            const color = info.event.backgroundColor || '#0085f3';
            info.el.style.setProperty('--ev-color', color);
            info.el.style.background = color + '1A';
            info.el.style.borderLeft = '4px solid ' + color;
        },

        events(info, ok, fail) {
            fetch(`${ROUTES.events}?start=${info.startStr}&end=${info.endStr}`, {
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': CSRF },
            })
            .then(r => r.json())
            .then(data => {
                if (data.error) {
                    toastr.error(LANG.error_load_events + data.error, LANG.calendar_label);
                    fail(data.error);
                    return;
                }

                allFetchedEvents = data;
                updateMiniCalDots();

                let filtered = data;
                // Filter by visible calendars (sidebar checkboxes)
                if (selectedVisibleIds && selectedVisibleIds.length > 0) {
                    filtered = filtered.filter(e =>
                        !e.calendarId || selectedVisibleIds.includes(e.calendarId)
                    );
                }
                // Apply search filter
                if (searchFilter) {
                    filtered = filtered.filter(e =>
                        (e.title || '').toLowerCase().includes(searchFilter) ||
                        (e.description || '').toLowerCase().includes(searchFilter)
                    );
                }

                const colored = filtered.map(e => {
                    const color = eventColor(e.calendarId || e.id || e.title || '');
                    return {
                        ...e,
                        backgroundColor: color,
                        borderColor:     color,
                        textColor:       '#fff',
                    };
                });

                // Dedup: remove duplicate events (same title + same start time)
                const seen = new Set();
                const deduped = colored.filter(e => {
                    const key = (e.title || '') + '|' + (e.start || '');
                    if (seen.has(key)) return false;
                    seen.add(key);
                    return true;
                });
                ok(deduped);
            })
            .catch(err => {
                toastr.error(LANG.error_google_connect, LANG.calendar_label);
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
            info.jsEvent.preventDefault();
            info.jsEvent.stopPropagation();
            showPopup(info.event, info.jsEvent);
        },
        eventDrop(info)   { closePopup(); patchDates(info.event); },
        eventResize(info) { patchDates(info.event); },
    });

    calendar.render();
    updateTitle();

    // Set initial active view option
    document.querySelectorAll('.cal-view-option').forEach(b => {
        b.classList.toggle('active', b.dataset.view === defaultView);
        if (b.dataset.view === defaultView) {
            document.getElementById('viewBtnLabel').textContent = b.textContent.trim();
        }
    });

    // Init mini calendar
    initMiniCal();

    // Load sidebar calendars
    loadSidebarCalendars();

    // Close view dropdown on outside click
    document.addEventListener('click', e => {
        if (!document.getElementById('evPopup').contains(e.target)) closePopup();
        if (!document.getElementById('viewDropdown').contains(e.target)) closeViewMenu();
    });

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') { closeCalDrawer(); closePopup(); closeViewMenu(); closeSidebar(); }
    });
});

// ── Event Popup ──────────────────────────────────────────────────────────
function showPopup(event, jsEvent) {
    popupEvent = event;
    const color = eventColor(event.extendedProps?.calendarId || event.id || event.title || '');
    document.getElementById('popupDot').style.background = color;
    document.getElementById('popupTitle').textContent = event.title || LANG.no_title;

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

    const gUrl = event.extendedProps?.googleUrl;
    if (gUrl) body += row('bi-box-arrow-up-right', `<a href="${gUrl}" target="_blank" rel="noopener" style="color:#3B82F6;text-decoration:none;font-size:12px;">${esc(LANG.open_google)}</a>`);

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

// ── Drawer helpers ───────────────────────────────────────────────────────
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

function closeModal() { closeCalDrawer(); }

// ── Create / Edit ────────────────────────────────────────────────────────
function openCreateModal(start, end) {
    currentEventId = null;
    calAttendees   = [];
    document.getElementById('calDrawerTitle').textContent    = LANG.new_event_title;
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

    document.getElementById('calDrawerTitle').textContent    = LANG.edit_event_title;
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

// ── Attendees ────────────────────────────────────────────────────────────
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

// ── Error helpers ────────────────────────────────────────────────────────
function hideErr()    { const e = document.getElementById('calError'); e.style.display = 'none'; e.textContent = ''; }
function showErr(msg) { const e = document.getElementById('calError'); e.textContent = msg; e.style.display = 'block'; }

// ── Save ─────────────────────────────────────────────────────────────────
async function saveEvent() {
    const title = document.getElementById('calTitle').value.trim();
    const start = document.getElementById('calStart').value;
    const end   = document.getElementById('calEnd').value;

    if (!title)        { showErr(LANG.title_required); return; }
    if (!start)        { showErr(LANG.start_required); return; }
    if (!end)          { showErr(LANG.end_required); return; }
    if (start >= end)  { showErr(LANG.end_after_start); return; }

    const btn = document.getElementById('btnCalSave');
    btn.disabled = true; btn.textContent = LANG.saving;
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
        else showErr(data.message || LANG.error_save_event);
    } catch { showErr(LANG.error_connection); }

    btn.disabled = false; btn.textContent = LANG.save;
}

// ── Delete ───────────────────────────────────────────────────────────────
function deleteEvent() {
    if (!currentEventId) return;
    confirmAction({
        title: LANG.delete_title,
        message: LANG.delete_confirm,
        confirmText: LANG.delete,
        onConfirm: async () => {
            try {
                const res  = await fetch(ROUTES.destroy(currentEventId), {
                    method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' },
                });
                const data = await res.json();
                if (data.success) { closeCalDrawer(); calendar.refetchEvents(); }
                else showErr(data.message || LANG.error_delete);
            } catch { showErr(LANG.error_connection); }
        },
    });
}

// ── Drag/resize update ───────────────────────────────────────────────────
async function patchDates(event) {
    try {
        await fetch(ROUTES.update(event.id), {
            method:  'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ start: event.start?.toISOString(), end: event.end?.toISOString() }),
        });
    } catch { calendar.refetchEvents(); }
}

// ── Date utils ───────────────────────────────────────────────────────────
function toDTL(iso) {
    if (!iso) return '';
    const d = new Date(iso);
    const p = n => String(n).padStart(2,'0');
    return `${d.getFullYear()}-${p(d.getMonth()+1)}-${p(d.getDate())}T${p(d.getHours())}:${p(d.getMinutes())}`;
}
function toISO(dtl) { return new Date(dtl).toISOString(); }
function addHour(iso) { const d = new Date(iso); d.setHours(d.getHours()+1); return d.toISOString(); }

// ══════════════════════════════════════════════════════════════════════════
// Calendar Selector (sidebar checkboxes)
// ══════════════════════════════════════════════════════════════════════════
let availableCalendars = [];
let selectedVisibleIds = {!! json_encode($calendarVisibleIds) !!};
let selectedDefaultId  = {!! json_encode($calendarDefaultId) !!};

async function loadSidebarCalendars() {
    const container = document.getElementById('sidebarCalList');
    if (!container) return;
    container.innerHTML = `<div class="cal-filter-loading"><i class="bi bi-arrow-repeat"></i> ${esc(LANG.loading_calendars)}</div>`;

    try {
        const res  = await fetch(ROUTES.calendars, { headers: { Accept: 'application/json', 'X-CSRF-TOKEN': CSRF } });
        const data = await res.json();
        if (data.error) {
            container.innerHTML = `<div class="cal-filter-loading" style="color:#ef4444">${esc(data.error)}</div>`;
            return;
        }

        availableCalendars = data;
        data.forEach(c => { calendarColorMap[c.id] = c.backgroundColor || eventColor(c.id); });

        renderSidebarCalendars();
    } catch {
        container.innerHTML = `<div class="cal-filter-loading" style="color:#ef4444">${esc(LANG.error_load_calendars || 'Error')}</div>`;
    }
}

function renderSidebarCalendars() {
    const container = document.getElementById('sidebarCalList');
    if (!availableCalendars.length) {
        container.innerHTML = `<div class="cal-filter-loading">${esc(LANG.no_calendars || 'No calendars')}</div>`;
        return;
    }

    container.innerHTML = availableCalendars.map(c => {
        const checked = selectedVisibleIds.includes(c.id) ? 'checked' : '';
        const color   = calendarColorMap[c.id] || eventColor(c.id);
        const label   = c.summary + (c.primary ? ` (${LANG.primary || 'primary'})` : '');
        return `
        <label class="cal-filter-cal-item">
            <input type="checkbox" value="${esc(c.id)}" ${checked}
                   onchange="toggleCalVisible(this)">
            <span class="cal-filter-cal-dot" style="background:${color}"></span>
            <span class="cal-filter-cal-name">${esc(label)}</span>
        </label>`;
    }).join('');
}

function toggleCalVisible(checkbox) {
    const id = checkbox.value;
    if (checkbox.checked) {
        if (!selectedVisibleIds.includes(id)) selectedVisibleIds.push(id);
    } else {
        selectedVisibleIds = selectedVisibleIds.filter(v => v !== id);
        if (selectedDefaultId === id && selectedVisibleIds.length) {
            selectedDefaultId = selectedVisibleIds[0];
        }
    }
    // Refetch immediately with new filter, then save to server
    if (calendar) calendar.refetchEvents();
    saveCalendarPrefs();
}

function setDefaultCalendar(id) {
    selectedDefaultId = id;
    if (!selectedVisibleIds.includes(id)) {
        selectedVisibleIds.push(id);
    }
    saveCalendarPrefs();
}

async function saveCalendarPrefs() {
    if (!selectedVisibleIds.length) {
        toastr.warning(LANG.select_at_least_one);
        return;
    }

    try {
        const res = await fetch(ROUTES.preferences, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' },
            body: JSON.stringify({ visible_ids: selectedVisibleIds, default_id: selectedDefaultId }),
        });
        const data = await res.json();
        if (data.success) {
            calendar.refetchEvents();
        }
    } catch {
        // silent fail for auto-save
    }
}

// Legacy alias for any code that may call these
function openCalendarSelector() { toggleSidebar(); }
function closeCalendarSelector() { closeSidebar(); }
</script>
@endpush
@endif

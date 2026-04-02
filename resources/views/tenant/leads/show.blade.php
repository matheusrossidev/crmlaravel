@extends('tenant.layouts.app')
@php
$title   = $lead->name;
$pageIcon = 'person-badge';
@endphp

@section('topbar_actions')
<div class="topbar-actions" style="gap:6px;">
    <a href="{{ route('leads.index') }}" class="btn-secondary-sm" style="padding:8px 10px;text-decoration:none;" title="{{ __('leads.back') }}">
        <i class="bi bi-arrow-left"></i>
    </a>
    <button class="btn-primary-sm" id="btnEditLead" onclick="openLeadDrawer({{ $lead->id }})" style="padding:8px 14px;" title="{{ __('leads.edit_lead') }}">
        <i class="bi bi-pencil"></i>
        <span class="d-none d-md-inline" style="margin-left:4px;">{{ __('leads.edit') }}</span>
    </button>
</div>
@endsection

@push('styles')
<style>
/* ── Hero ── */
.lp-hero {
    background: #fff;
    border: 1px solid #e8eaf0;
    border-radius: 14px;
    padding: 24px 28px;
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 20px;
}
.lp-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: #eff6ff;
    color: #3b82f6;
    font-size: 24px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.lp-hero-info { flex: 1; min-width: 0; }
.lp-hero-name {
    font-size: 20px;
    font-weight: 700;
    color: #1a1d23;
    margin: 0 0 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.lp-hero-meta {
    font-size: 13px;
    color: #6b7280;
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.lp-hero-score {
    flex-shrink: 0;
    margin-left: auto;
}

/* ── Sequence Banner ── */
.lp-seq-banner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #f0fdf4;
    border: 1.5px solid #bbf7d0;
    border-radius: 12px;
    padding: 14px 20px;
    margin-bottom: 16px;
}
.lp-seq-banner-left {
    display: flex;
    align-items: center;
    gap: 12px;
}
.lp-seq-banner-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: #dcfce7;
    color: #16a34a;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
}
.lp-seq-banner-name {
    font-size: 13.5px;
    font-weight: 700;
    color: #1a1d23;
}
.lp-seq-banner-step {
    font-size: 12px;
    color: #16a34a;
    font-weight: 500;
}
.lp-seq-banner-link {
    font-size: 12.5px;
    font-weight: 600;
    color: #16a34a;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 4px;
    transition: color .15s;
    white-space: nowrap;
}
.lp-seq-banner-link:hover { color: #059669; }

/* Collapsible UTM */
.lp-utm-body { overflow: hidden; transition: max-height .25s ease; max-height: 500px; }
.lp-utm-body.collapsed { max-height: 0; }

/* Sidebar multi-card spacing */
.lp-info-card + .lp-info-card { margin-top: 12px; }

/* ── Step indicator ── */
.lp-steps-wrap {
    background: #fff;
    border: 1px solid #e8eaf0;
    border-radius: 14px;
    padding: 22px 28px;
    margin-bottom: 20px;
    overflow-x: auto;
}
.lp-steps {
    display: flex;
    align-items: center;
    min-width: max-content;
}
.lp-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    position: relative;
}
.lp-step-dot {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    border: 2.5px solid #d1d5db;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    color: #9ca3af;
    transition: all .2s;
    position: relative;
    z-index: 1;
}
.lp-step.past .lp-step-dot {
    border-color: transparent;
    color: #fff;
}
.lp-step.current .lp-step-dot {
    border-width: 3px;
    color: #fff;
    box-shadow: 0 0 0 4px rgba(0,133,243,.2);
    animation: stagePulse 2s ease-in-out infinite;
}
@keyframes stagePulse {
    0%, 100% { box-shadow: 0 0 0 4px rgba(0,133,243,.15); }
    50%      { box-shadow: 0 0 0 8px rgba(0,133,243,.08); }
}
.lp-step-dot.clickable { cursor: pointer; }
.lp-step-dot.clickable:hover { transform: scale(1.15); }
.lp-step-label {
    font-size: 11.5px;
    font-weight: 600;
    color: #9ca3af;
    text-align: center;
    max-width: 80px;
    line-height: 1.3;
}
.lp-step.past .lp-step-label  { color: #6b7280; }
.lp-step.current .lp-step-label { color: #1a1d23; font-weight: 700; }
.lp-step-line {
    flex: 1;
    height: 2px;
    background: #e5e7eb;
    min-width: 32px;
    margin-bottom: 18px; /* align with dots */
}
.lp-step-line.filled { background: #0085f3; }

/* ── Main grid ── */
.lp-grid {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 20px;
    align-items: start;
}
@media (max-width: 900px) {
    .lp-grid { grid-template-columns: 1fr; }
}
@media (max-width: 768px) {
    /* Activities split view: stack vertically on mobile */
    #tab-activities > div { flex-direction: column !important; }
    #tab-activities > div > div { width: 100% !important; border-right: none !important; border-bottom: 1px solid #f0f2f7; max-height: 300px !important; }
    /* Tabs scroll */
    .lp-tabs-nav { padding: 0 10px; }
    .lp-tab-btn { padding: 10px 12px; font-size: 12px; white-space: nowrap; }
}

/* ── Tabs ── */
.lp-card {
    background: #fff;
    border: 1px solid #e8eaf0;
    border-radius: 14px;
    overflow: hidden;
}
.lp-tabs-nav {
    display: flex;
    border-bottom: 1px solid #f0f2f7;
    padding: 0 20px;
    gap: 0;
    overflow-x: auto;
}
.lp-tab-btn {
    padding: 13px 16px;
    font-size: 13px;
    font-weight: 600;
    color: #6b7280;
    border: none;
    background: none;
    cursor: pointer;
    border-bottom: 2.5px solid transparent;
    white-space: nowrap;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: color .15s, border-color .15s;
}
.lp-tab-btn:hover { color: #374151; }
.lp-tab-btn.active { color: #3b82f6; border-bottom-color: #3b82f6; }
.lp-tab-panel { display: none; padding: 22px; }
.lp-tab-panel.active { display: block; }

/* ── Notes ── */
.lp-note-card {
    border: 1px solid #f0f2f7;
    border-radius: 10px;
    padding: 14px 16px;
    margin-bottom: 12px;
    background: #fafafa;
}
.lp-note-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 8px;
}
.lp-note-avatar {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: #eff6ff;
    color: #3b82f6;
    font-size: 12px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.lp-note-meta { flex: 1; min-width: 0; }
.lp-note-author { font-size: 13px; font-weight: 600; color: #1a1d23; }
.lp-note-date   { font-size: 11px; color: #9ca3af; }
.lp-note-body   { font-size: 13.5px; color: #374151; line-height: 1.6; white-space: pre-wrap; word-break: break-word; }
.lp-note-del {
    background: none;
    border: none;
    cursor: pointer;
    color: #d1d5db;
    font-size: 14px;
    padding: 2px 4px;
    border-radius: 4px;
    transition: color .15s;
}
.lp-note-del:hover { color: #ef4444; }

/* Note form */
.lp-note-textarea {
    width: 100%;
    border: 1.5px solid #e8eaf0;
    border-radius: 9px;
    padding: 10px 14px;
    font-size: 13.5px;
    font-family: inherit;
    resize: vertical;
    min-height: 80px;
    outline: none;
    transition: border-color .15s;
    box-sizing: border-box;
    color: #1a1d23;
}
.lp-note-textarea:focus { border-color: #3b82f6; }
.lp-btn-add-note {
    margin-top: 8px;
    padding: 8px 18px;
    background: #3b82f6;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: background .15s;
}
.lp-btn-add-note:hover { background: #2563eb; }
.lp-btn-add-note:disabled { background: #93c5fd; cursor: not-allowed; }

/* ── Timeline ── */
.lp-timeline { list-style: none; padding: 0; margin: 0; }
.lp-timeline-item {
    display: flex;
    gap: 14px;
    padding-bottom: 18px;
    position: relative;
}
.lp-timeline-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: 14px;
    top: 28px;
    bottom: 0;
    width: 2px;
    background: #f0f2f7;
}
.lp-tl-icon {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    flex-shrink: 0;
    position: relative;
    z-index: 1;
}
.lp-tl-body { flex: 1; min-width: 0; padding-top: 4px; }
.lp-tl-desc { font-size: 13.5px; color: #1a1d23; font-weight: 500; }
.lp-tl-meta { font-size: 11.5px; color: #9ca3af; margin-top: 2px; }

/* ── Chat bubbles ── */
.lp-chat-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 600;
    color: #3b82f6;
    text-decoration: none;
    padding: 8px 14px;
    border: 1.5px solid #dbeafe;
    border-radius: 8px;
    background: #eff6ff;
    margin-bottom: 16px;
    transition: background .15s;
}
.lp-chat-link:hover { background: #dbeafe; color: #2563eb; }
.lp-messages { display: flex; flex-direction: column; gap: 4px; }
.lp-date-sep {
    text-align: center;
    font-size: 11px;
    font-weight: 600;
    color: #9ca3af;
    background: #f3f4f6;
    border-radius: 99px;
    padding: 3px 12px;
    display: inline-block;
    align-self: center;
    margin: 8px 0;
}
.lp-bubble-wrap {
    display: flex;
    flex-direction: column;
}
.lp-bubble-wrap.out { align-items: flex-end; }
.lp-bubble-wrap.in  { align-items: flex-start; }
.lp-bubble {
    max-width: 78%;
    padding: 8px 12px;
    border-radius: 12px;
    font-size: 13.5px;
    line-height: 1.5;
    word-break: break-word;
    position: relative;
}
.lp-bubble.out { background: #d1fae5; border-bottom-right-radius: 3px; color: #065f46; }
.lp-bubble.in  { background: #f3f4f6; border-bottom-left-radius: 3px; color: #1a1d23; }
.lp-bubble.ig-out { background: #dbeafe; color: #1e3a8a; border-bottom-right-radius: 3px; }
.lp-note-internal {
    margin: 6px 0;
    background: #fefce8;
    border-left: 3px solid #f59e0b;
    border-radius: 0 8px 8px 0;
    padding: 8px 12px;
}
.lp-note-internal-header {
    font-size: 11px; font-weight: 600; color: #92400e;
    margin-bottom: 4px; display: flex; align-items: center; gap: 4px;
}
.lp-note-internal-body {
    font-size: 13.5px; color: #1a1d23; line-height: 1.5; white-space: pre-wrap; word-break: break-word;
}
.lp-note-internal-time {
    font-size: 10.5px; color: #a16207; text-align: right; margin-top: 4px;
}
.lp-bubble-time {
    font-size: 10.5px;
    color: #9ca3af;
    margin-top: 2px;
    padding: 0 2px;
}
.lp-bubble img {
    max-width: 200px;
    max-height: 200px;
    border-radius: 8px;
    display: block;
    cursor: pointer;
}
.lp-bubble audio { max-width: 240px; }
.lp-empty-conv {
    text-align: center;
    padding: 40px 20px;
    color: #9ca3af;
}
.lp-empty-conv i { font-size: 36px; opacity: .3; display: block; margin-bottom: 10px; }

/* ── Info card ── */
.lp-info-card {
    background: #fff;
    border: 1px solid #e8eaf0;
    border-radius: 14px;
    overflow: hidden;
}
.lp-info-section {
    padding: 18px 20px;
    border-bottom: 1px solid #f0f2f7;
}
.lp-info-section:last-child { border-bottom: none; }
.lp-info-section-title {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: #9ca3af;
    margin-bottom: 12px;
}
.lp-info-row {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-bottom: 10px;
    font-size: 13.5px;
    color: #374151;
}
.lp-info-row:last-child { margin-bottom: 0; }
.lp-info-icon {
    width: 20px;
    text-align: center;
    color: #9ca3af;
    flex-shrink: 0;
    margin-top: 1px;
}
.lp-info-val { flex: 1; min-width: 0; word-break: break-word; }
.lp-info-val a { color: #3b82f6; text-decoration: none; }
.lp-info-val a:hover { text-decoration: underline; }
.lp-info-empty { color: #d1d5db; }

.lp-copy-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 26px;
    height: 26px;
    border: none;
    background: #f1f5f9;
    color: #6b7280;
    border-radius: 6px;
    cursor: pointer;
    font-size: 12px;
    flex-shrink: 0;
    transition: all .15s;
}
.lp-copy-btn:hover { background: #e2e8f0; color: #374151; }
.lp-copy-btn.copied { background: #dcfce7; color: #16a34a; }

.lp-tag-chip {
    display: inline-flex;
    align-items: center;
    padding: 2px 10px;
    border-radius: 99px;
    font-size: 11.5px;
    font-weight: 600;
    background: #f0f2f7;
    color: #374151;
    margin: 2px 3px 2px 0;
}

/* ── Contacts section ── */
.lp-add-contact-btn { background:none; border:none; color:#0085f3; cursor:pointer; font-size:16px; padding:0; line-height:1; }
.lp-add-contact-btn:hover { color:#0070d1; }
.lp-contact-card { padding:10px 0; border-bottom:1px solid #f0f2f7; }
.lp-contact-card:last-child { border-bottom:none; }
.lp-contact-name { font-size:13px; font-weight:600; color:#1a1d23; }
.lp-contact-role { font-size:11px; color:#6b7280; margin-left:6px; font-weight:500; }
.lp-contact-detail { font-size:12px; color:#374151; display:flex; align-items:center; gap:4px; margin-top:3px; }
.lp-contact-detail i { font-size:11px; color:#9ca3af; }
.lp-contact-actions { display:flex; gap:4px; margin-top:4px; }
.lp-contact-actions button { background:none; border:none; cursor:pointer; font-size:12px; color:#9ca3af; padding:2px 4px; border-radius:4px; }
.lp-contact-actions button:hover { color:#374151; background:#f3f4f6; }
.lp-contact-actions button.lp-contact-del:hover { color:#ef4444; background:#fef2f2; }
.lp-contact-form { display:flex; flex-direction:column; gap:8px; margin-bottom:10px; }
.lp-contact-form input { font-size:13px; padding:7px 10px; border:1.5px solid #e8eaf0; border-radius:8px; outline:none; color:#1a1d23; }
.lp-contact-form input:focus { border-color:#0085f3; }
.lp-contact-form-btns { display:flex; gap:6px; }
.lp-contact-form-btns button { font-size:12px; font-weight:600; padding:6px 14px; border-radius:8px; border:none; cursor:pointer; }
.lp-contact-form-btns .btn-save-contact { background:#0085f3; color:#fff; }
.lp-contact-form-btns .btn-save-contact:hover { background:#0070d1; }
.lp-contact-form-btns .btn-cancel-contact { background:#f3f4f6; color:#6b7280; }
.lp-contact-form-btns .btn-cancel-contact:hover { background:#e5e7eb; }

.stage-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 11.5px;
    font-weight: 600;
    padding: 3px 9px;
    border-radius: 99px;
}
.stage-badge .dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    flex-shrink: 0;
}
.source-pill {
    font-size: 11px;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 99px;
    background: #f0f2f7;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: .04em;
}

/* ── Scheduled Messages ── */
.lp-sm-card {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px 14px;
    border: 1px solid #f0f2f7;
    border-radius: 10px;
    margin-bottom: 10px;
    background: #fafafa;
}
.lp-sm-icon {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    background: #eff6ff;
    color: #3b82f6;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    flex-shrink: 0;
}
.lp-sm-body { flex: 1; min-width: 0; }
.lp-sm-preview {
    font-size: 13px;
    color: #374151;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 5px;
}
.lp-sm-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    font-size: 11.5px;
    color: #9ca3af;
}
.lp-sm-badge {
    padding: 1px 7px;
    border-radius: 99px;
    font-size: 11px;
    font-weight: 700;
}
.lp-sm-cancel {
    background: none;
    border: none;
    cursor: pointer;
    color: #d1d5db;
    font-size: 13px;
    padding: 4px;
    border-radius: 4px;
    flex-shrink: 0;
    transition: color .15s;
}
.lp-sm-cancel:hover { color: #ef4444; }

/* ── Attachments ── */
.lp-attach-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    border: 1px solid #f0f2f7;
    border-radius: 10px;
    margin-bottom: 8px;
    transition: background .1s;
}
.lp-attach-item:hover { background: #fafbfc; }
.lp-attach-icon { font-size: 22px; flex-shrink: 0; line-height: 1; }
.lp-attach-info { flex: 1; min-width: 0; }
.lp-attach-name {
    font-size: 13px; font-weight: 600; color: #1a1d23;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.lp-attach-meta { font-size: 11.5px; color: #9ca3af; margin-top: 2px; }
.lp-attach-actions { display: flex; gap: 4px; flex-shrink: 0; }
.lp-attach-btn {
    width: 30px; height: 30px; border-radius: 7px;
    border: 1px solid #e8eaf0; background: #fff; color: #6b7280;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; cursor: pointer; transition: all .15s;
    text-decoration: none;
}
.lp-attach-btn:hover { background: #f3f4f6; color: #374151; }
.lp-attach-del:hover { background: #fee2e2; color: #ef4444; border-color: #fca5a5; }
.lp-attach-uploading {
    display: flex; align-items: center; gap: 8px;
    padding: 10px 14px; border: 1px dashed #93c5fd;
    border-radius: 10px; margin-bottom: 8px;
    background: #eff6ff; font-size: 12.5px; color: #0085f3;
}

/* ── Schedule Modal ── */
.sched-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.45);
    z-index: 1050;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}
.sched-modal {
    background: #fff;
    border-radius: 16px;
    width: 100%;
    max-width: 520px;
    box-shadow: 0 20px 60px rgba(0,0,0,.18);
    overflow: hidden;
}
.sched-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 22px;
    border-bottom: 1px solid #f0f2f7;
}
.sched-modal-title { font-size: 15px; font-weight: 700; color: #1a1d23; }
.sched-modal-close {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 18px;
    color: #9ca3af;
    padding: 4px;
    line-height: 1;
    border-radius: 4px;
    transition: color .15s;
}
.sched-modal-close:hover { color: #374151; }
.sched-modal-body { padding: 22px; max-height: 65vh; overflow-y: auto; }
.sched-form-group { margin-bottom: 16px; }
.sched-form-label {
    display: block;
    font-size: 12.5px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 6px;
}
.sched-form-select,
.sched-form-input,
.sched-form-textarea {
    width: 100%;
    border: 1.5px solid #e8eaf0;
    border-radius: 8px;
    padding: 9px 12px;
    font-size: 13.5px;
    font-family: inherit;
    color: #1a1d23;
    outline: none;
    transition: border-color .15s;
    box-sizing: border-box;
    background: #fff;
}
.sched-form-select:focus,
.sched-form-input:focus,
.sched-form-textarea:focus { border-color: #3b82f6; }
.sched-form-textarea { resize: vertical; min-height: 90px; }
.sched-type-radios { display: flex; gap: 10px; }
.sched-type-radio {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 8px 12px;
    border: 1.5px solid #e8eaf0;
    border-radius: 8px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
    color: #6b7280;
    transition: all .15s;
    user-select: none;
}
.sched-type-radio:has(input:checked) {
    border-color: #3b82f6;
    background: #eff6ff;
    color: #3b82f6;
}
.sched-type-radio input { display: none; }
.sched-modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 14px 22px;
    border-top: 1px solid #f0f2f7;
}
.sched-btn-cancel {
    padding: 9px 18px;
    border: 1.5px solid #e8eaf0;
    border-radius: 8px;
    background: #fff;
    font-size: 13px;
    font-weight: 600;
    color: #6b7280;
    cursor: pointer;
    transition: all .15s;
}
.sched-btn-cancel:hover { background: #f9fafb; }
.sched-btn-submit {
    padding: 9px 22px;
    background: #3b82f6;
    border: none;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    color: #fff;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: background .15s;
}
.sched-btn-submit:hover { background: #2563eb; }
.sched-btn-submit:disabled { background: #93c5fd; cursor: not-allowed; }
</style>
@endpush

@section('content')
<div class="page-container">

{{-- ── Hero ── --}}
<div class="lp-hero">
    <div class="lp-avatar">{{ strtoupper(substr($lead->name, 0, 1)) }}</div>
    <div class="lp-hero-info" style="flex:1;">
        {{-- Name + quick action icons --}}
        <div style="display:flex;align-items:center;gap:8px;">
            <h1 class="lp-hero-name">{{ $lead->name }}</h1>
            @if($lead->phone)
            <a href="https://wa.me/{{ preg_replace('/\D/', '', $lead->phone) }}" target="_blank" title="{{ __('leads.action_call_whatsapp') }}" style="color:#0085f3;font-size:13px;text-decoration:none;padding:2px;"><i class="bi bi-telephone"></i></a>
            @endif
            @if($lead->phone)
            <a href="https://wa.me/{{ preg_replace('/\D/', '', $lead->phone) }}" target="_blank" title="{{ __('leads.action_whatsapp') }}" style="color:#0085f3;font-size:13px;text-decoration:none;padding:2px;"><i class="bi bi-chat-dots"></i></a>
            @endif
            @if($lead->email)
            <a href="mailto:{{ $lead->email }}" title="{{ __('leads.action_email') }}" style="color:#0085f3;font-size:13px;text-decoration:none;padding:2px;"><i class="bi bi-envelope"></i></a>
            @endif
            <button onclick="openLeadTaskDrawer()" title="{{ __('leads.action_schedule') }}" style="background:none;border:none;color:#0085f3;font-size:13px;cursor:pointer;padding:2px;"><i class="bi bi-calendar-plus"></i></button>
            <button onclick="openNewNote()" title="{{ __('leads.action_new_note') }}" style="background:none;border:none;color:#0085f3;font-size:13px;cursor:pointer;padding:2px;"><i class="bi bi-flag"></i></button>
        </div>
        {{-- Tags --}}
        <div style="display:flex;align-items:center;gap:5px;margin-top:6px;flex-wrap:wrap;">
            @foreach(($lead->tags ?? []) as $tag)
            <span style="display:inline-flex;align-items:center;gap:3px;padding:2px 9px;border-radius:99px;font-size:11px;font-weight:600;background:#eff6ff;color:#0085f3;border:1px solid #bfdbfe;">
                {{ $tag }}
                <button onclick="removeTag('{{ addslashes($tag) }}')" style="background:none;border:none;color:#93c5fd;cursor:pointer;font-size:12px;line-height:1;padding:0 0 0 2px;">&times;</button>
            </span>
            @endforeach
            <div style="position:relative;display:inline-block;">
                <button onclick="document.getElementById('tagDropdown').style.display=document.getElementById('tagDropdown').style.display==='block'?'none':'block'" style="width:22px;height:22px;border-radius:50%;background:#f3f4f6;border:1px dashed #d1d5db;color:#9ca3af;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;font-size:12px;" title="{{ __('leads.action_add_tag') }}">+</button>
                @php $allTags = \App\Models\WhatsappTag::orderBy('name')->get(['name','color']); @endphp
                <div id="tagDropdown" style="display:none;position:absolute;top:calc(100% + 4px);left:0;background:#fff;border:1px solid #e8eaf0;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,.1);z-index:50;min-width:140px;max-height:200px;overflow-y:auto;">
                    @foreach($allTags as $t)
                        @if(!in_array($t->name, $lead->tags ?? []))
                        <button onclick="addExistingTag('{{ addslashes($t->name) }}')" style="display:flex;align-items:center;gap:6px;padding:7px 12px;width:100%;border:none;background:none;cursor:pointer;font-size:12px;color:#374151;font-weight:500;" onmouseenter="this.style.background='#f8fafc'" onmouseleave="this.style.background=''">
                            @if($t->color)<span style="width:8px;height:8px;border-radius:50%;background:{{ $t->color }};flex-shrink:0;"></span>@endif
                            {{ $t->name }}
                        </button>
                        @endif
                    @endforeach
                    @if($allTags->count() === 0 || $allTags->whereNotIn('name', $lead->tags ?? [])->isEmpty())
                    <div style="padding:10px 12px;font-size:11px;color:#9ca3af;text-align:center;">{{ __('leads.no_tags_available') }}</div>
                    @endif
                </div>
            </div>
        </div>
        {{-- Meta row --}}
        <div class="lp-hero-meta" style="margin-top:4px;">
            @if($lead->stage)
            <span class="stage-badge" style="background:{{ $lead->stage->color }}22;color:{{ $lead->stage->color }};">
                <span class="dot" style="background:{{ $lead->stage->color }};"></span>
                {{ $lead->stage->name }}
            </span>
            @endif
            @if($lead->source)
            <span class="source-pill">{{ $lead->source }}</span>
            @endif
            <span style="color:#d1d5db;">|</span>
            <span>{{ __('leads.created_ago', ['time' => $lead->created_at->diffForHumans()]) }}</span>
        </div>
    </div>

    {{-- Right side: score + outcome dropdown + assignee avatar --}}
    <div style="display:flex;align-items:center;gap:12px;flex-shrink:0;">
        @if($lead->score > 0)
        @php
            $sCls = $lead->score >= 70 ? 'hot' : ($lead->score >= 30 ? 'warm' : 'cold');
            $sColorMap = ['hot' => '#059669', 'warm' => '#d97706', 'cold' => '#9ca3af'];
            $sLabelMap = ['hot' => __('scoring.score_high') . ' 🔥', 'warm' => __('scoring.score_medium'), 'cold' => __('scoring.score_low')];
            $sBgMap = ['hot' => '#ecfdf5', 'warm' => '#fffbeb', 'cold' => '#f3f4f6'];
        @endphp
        <span style="display:inline-flex;align-items:center;gap:4px;padding:4px 12px;border-radius:100px;font-size:13px;font-weight:700;background:{{ $sBgMap[$sCls] }};color:{{ $sColorMap[$sCls] }};white-space:nowrap;">
            <i class="bi bi-lightning-fill" style="font-size:11px;"></i> {{ $lead->score }}
        </span>
        @endif

        {{-- Outcome dropdown (just icon) --}}
        @php
            $wonStage = $lead->pipeline?->stages->firstWhere('is_won', true);
            $lostStage = $lead->pipeline?->stages->firstWhere('is_lost', true);
        @endphp
        @if($wonStage || $lostStage)
        <div style="position:relative;" id="outcomeWrap">
            <button onclick="document.getElementById('outcomeMenu').style.display=document.getElementById('outcomeMenu').style.display==='block'?'none':'block'" title="Marcar resultado" style="background:none;border:none;color:#6b7280;font-size:18px;cursor:pointer;padding:4px;display:flex;align-items:center;">
                <i class="bi bi-emoji-neutral"></i> <i class="bi bi-chevron-down" style="font-size:10px;margin-left:2px;"></i>
            </button>
            <div id="outcomeMenu" style="display:none;position:absolute;top:calc(100% + 4px);right:0;background:#fff;border:1px solid #e8eaf0;border-radius:10px;box-shadow:0 4px 16px rgba(0,0,0,.1);z-index:50;min-width:170px;overflow:hidden;">
                @if($wonStage)
                <button onclick="moveToStage({{ $wonStage->id }},'{{ addslashes($wonStage->name) }}')" style="display:flex;align-items:center;gap:8px;padding:10px 16px;width:100%;border:none;background:none;cursor:pointer;font-size:13px;color:#059669;font-weight:600;" onmouseenter="this.style.background='#f0fdf4'" onmouseleave="this.style.background=''">
                    <i class="bi bi-trophy-fill"></i> {{ __('leads.outcome_won') }}
                </button>
                @endif
                @if($lostStage)
                <button onclick="moveToStage({{ $lostStage->id }},'{{ addslashes($lostStage->name) }}')" style="display:flex;align-items:center;gap:8px;padding:10px 16px;width:100%;border:none;background:none;cursor:pointer;font-size:13px;color:#dc2626;font-weight:600;" onmouseenter="this.style.background='#fef2f2'" onmouseleave="this.style.background=''">
                    <i class="bi bi-x-circle-fill"></i> {{ __('leads.outcome_lost') }}
                </button>
                @endif
            </div>
        </div>
        @endif

        {{-- Assignee avatar --}}
        @if($lead->assignedTo)
        <div title="{{ $lead->assignedTo->name }}" style="width:36px;height:36px;border-radius:50%;background:#eff6ff;color:#0085f3;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;border:2px solid #bfdbfe;flex-shrink:0;">
            {{ strtoupper(substr($lead->assignedTo->name, 0, 2)) }}
        </div>
        @endif
    </div>
</div>

{{-- ── Step Indicator ── --}}
@if($lead->pipeline && $lead->pipeline->stages->count() > 0)
@php
    $stages      = $lead->pipeline->stages->sortBy('position');
    $currentPos  = $lead->stage?->position ?? 0;
@endphp
<div class="lp-steps-wrap">
    <div class="lp-steps">
        @foreach($stages as $stage)
        @php
            $isPast    = $stage->position < $currentPos;
            $isCurrent = $stage->id === $lead->stage_id;
            $cls       = $isPast ? 'past' : ($isCurrent ? 'current' : 'future');
            $dotBg     = ($isPast || $isCurrent) ? '#0085f3' : '#e5e7eb';
            $dotColor  = ($isPast || $isCurrent) ? '#fff' : '#9ca3af';
        @endphp
        <div class="lp-step {{ $cls }}">
            <div class="lp-step-dot clickable" style="background:{{ $dotBg }};border-color:{{ $dotBg }};color:{{ $dotColor }};"
                 onclick="moveToStage({{ $stage->id }}, '{{ addslashes($stage->name) }}')" title="Mover para {{ $stage->name }}">
                @if($isPast)
                    <i class="bi bi-check-lg"></i>
                @elseif($isCurrent)
                    @if($stage->is_won)  <i class="bi bi-trophy-fill"></i>
                    @elseif($stage->is_lost) <i class="bi bi-x-lg"></i>
                    @else <span style="font-size:12px;font-weight:800;">{{ $stage->position }}</span>
                    @endif
                @else
                    <span style="font-size:11px;font-weight:700;">{{ $stage->position }}</span>
                @endif
            </div>
            <div class="lp-step-label">{{ $stage->name }}</div>
        </div>
        @if(!$loop->last)
        <div class="lp-step-line {{ ($isPast || $isCurrent) ? 'filled' : '' }}"></div>
        @endif
        @endforeach
    </div>
</div>
@endif

{{-- ── Sequence Banner ── --}}
@php
    $activeSeq = $lead->activeSequence;
@endphp
@if($activeSeq && $activeSeq->sequence)
<div class="lp-seq-banner">
    <div class="lp-seq-banner-left">
        <div class="lp-seq-banner-icon"><i class="bi bi-arrow-repeat"></i></div>
        <div>
            <div class="lp-seq-banner-name">{{ $activeSeq->sequence->name }}</div>
            <div class="lp-seq-banner-step">{{ __('sequences.badge_step', ['current' => $activeSeq->current_step_position, 'total' => $activeSeq->sequence->steps->count()]) }}</div>
        </div>
    </div>
    <a href="{{ route('settings.sequences.edit', $activeSeq->sequence) }}" class="lp-seq-banner-link">
        {{ __('leads.view_sequence') }} <i class="bi bi-arrow-right"></i>
    </a>
</div>
@endif

{{-- ── Main grid ── --}}
<div class="lp-grid">

    {{-- ── Left: Tabs ── --}}
    <div class="lp-card">
        <div class="lp-tabs-nav">
            <button class="lp-tab-btn active" data-tab="activities">
                <i class="bi bi-list-task"></i> {{ __('leads.tab_activities') }}
                @if(isset($pendingTasksCount) && $pendingTasksCount > 0)
                <span style="background:#fef2f2;color:#ef4444;font-size:10px;font-weight:700;padding:1px 6px;border-radius:99px;">{{ $pendingTasksCount }}</span>
                @endif
            </button>
            <button class="lp-tab-btn" data-tab="timeline">
                <i class="bi bi-clock-history"></i> {{ __('leads.history') }}
            </button>
            <button class="lp-tab-btn" data-tab="notes">
                <i class="bi bi-journal-text"></i> {{ __('leads.notes') }}
                @if($lead->leadNotes->count() > 0)
                <span style="background:#eff6ff;color:#3b82f6;font-size:10px;font-weight:700;padding:1px 6px;border-radius:99px;">{{ $lead->leadNotes->count() }}</span>
                @endif
            </button>
            @if($waConversation)
            <button class="lp-tab-btn" data-tab="whatsapp">
                <i class="bi bi-whatsapp" style="color:#25d366;"></i> WhatsApp
                <span style="background:#f0fdf4;color:#10b981;font-size:10px;font-weight:700;padding:1px 6px;border-radius:99px;">{{ $waConversation->messages->count() }}</span>
            </button>
            @endif
            @if($igConversation || $lead->instagram_username)
            <button class="lp-tab-btn" data-tab="instagram">
                <i class="bi bi-instagram" style="color:#e1306c;"></i> Instagram
                @if($igConversation)
                <span style="background:#fdf2f8;color:#9d174d;font-size:10px;font-weight:700;padding:1px 6px;border-radius:99px;">{{ $igConversation->messages->count() }}</span>
                @endif
            </button>
            @endif
            <button class="lp-tab-btn" data-tab="attachments">
                <i class="bi bi-paperclip"></i> {{ __('leads.attachments') }}
                @if($lead->attachments->count() > 0)
                <span style="background:#eff6ff;color:#0085f3;font-size:10px;font-weight:700;padding:1px 6px;border-radius:99px;">{{ $lead->attachments->count() }}</span>
                @endif
            </button>
            <button class="lp-tab-btn" data-tab="scheduled">
                <i class="bi bi-clock"></i> {{ __('leads.scheduled_messages_tab') }}
                @if($scheduledMessages->where('status', 'pending')->count() > 0)
                <span style="background:#fff7ed;color:#f59e0b;font-size:10px;font-weight:700;padding:1px 6px;border-radius:99px;">{{ $scheduledMessages->where('status', 'pending')->count() }}</span>
                @endif
            </button>
            <button class="lp-tab-btn" data-tab="products">
                <i class="bi bi-box-seam"></i> {{ __('leads.tab_products') }}
                @if($lead->products->count() > 0)
                <span style="background:#f0fdf4;color:#059669;font-size:10px;font-weight:700;padding:1px 6px;border-radius:99px;">{{ $lead->products->count() }}</span>
                @endif
            </button>
        </div>

        {{-- ── Tab: Atividades (split view — tasks) ── --}}
        <div class="lp-tab-panel active" id="tab-activities">
            <div style="display:flex;min-height:300px;">
                {{-- Left: task list --}}
                <div style="width:50%;border-right:1px solid #f0f2f7;overflow-y:auto;max-height:500px;" id="actTaskList">
                    <div style="padding:12px 16px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #f0f2f7;">
                        <span style="font-size:13px;font-weight:600;color:#1a1d23;">{{ __('leads.upcoming_activities') }}</span>
                        <button onclick="openLeadTaskDrawer()" style="background:#0085f3;color:#fff;border:none;border-radius:100px;padding:4px 12px;font-size:11px;font-weight:600;cursor:pointer;">
                            {{ __('leads.new_activity') }}
                        </button>
                    </div>
                    @php
                        $sortedTasks = $tasks->sortBy(fn($t) => $t->status === 'completed' ? 1 : 0)->sortBy('due_date');
                        $taskIcons = ['call' => 'telephone', 'email' => 'envelope', 'task' => 'check2-square', 'visit' => 'geo-alt', 'whatsapp' => 'whatsapp', 'meeting' => 'camera-video'];
                    @endphp
                    @forelse($sortedTasks as $task)
                        @php
                            $isCompleted = $task->status === 'completed';
                            $daysUntil = $task->due_date ? (int) now()->startOfDay()->diffInDays($task->due_date, false) : null;
                            $isOverdue = !$isCompleted && $daysUntil !== null && $daysUntil < 0;
                            $icon = $taskIcons[$task->type] ?? 'check2-square';
                        @endphp
                        <div class="act-task-row {{ $isCompleted ? 'completed' : '' }} {{ $isOverdue ? 'overdue' : '' }}" data-task-id="{{ $task->id }}" onclick="showTaskDetail({{ $task->id }})">
                            <div style="display:flex;align-items:center;gap:10px;padding:10px 16px;cursor:pointer;border-bottom:1px solid #f7f8fa;transition:background .1s;"
                                 onmouseenter="this.style.background='#f8fafc'" onmouseleave="this.style.background=''">
                                @if($daysUntil !== null && !$isCompleted)
                                    <div style="min-width:32px;text-align:center;">
                                        <div style="font-size:15px;font-weight:800;color:{{ $isOverdue ? '#ef4444' : ($daysUntil <= 1 ? '#f59e0b' : '#0085f3') }};">{{ abs($daysUntil) }}</div>
                                        <div style="font-size:9px;font-weight:600;color:#9ca3af;">{{ $isOverdue ? __('leads.overdue_label') : 'D' }}</div>
                                    </div>
                                @endif
                                <i class="bi bi-{{ $icon }}" style="font-size:14px;color:{{ $isCompleted ? '#10b981' : '#9ca3af' }};"></i>
                                <div style="flex:1;min-width:0;">
                                    <div style="font-size:13px;font-weight:500;color:{{ $isCompleted ? '#9ca3af' : '#1a1d23' }};{{ $isCompleted ? 'text-decoration:line-through;' : '' }}overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                        {{ $task->subject }}
                                    </div>
                                    <div style="font-size:11px;color:#9ca3af;">
                                        {{ $task->due_date?->format('d/m') }}
                                        @if($task->assignedTo) · {{ $task->assignedTo->name }} @endif
                                    </div>
                                </div>
                                @if($isCompleted)
                                    <i class="bi bi-check-circle-fill" style="color:#10b981;font-size:16px;"></i>
                                @else
                                    <div style="width:18px;height:18px;border:2px solid #d1d5db;border-radius:50%;flex-shrink:0;cursor:pointer;"
                                         onclick="event.stopPropagation();toggleTaskComplete({{ $task->id }},this)"
                                         onmouseenter="this.style.borderColor='#10b981'" onmouseleave="this.style.borderColor='#d1d5db'"></div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div style="text-align:center;padding:40px 20px;color:#9ca3af;">
                            <i class="bi bi-check2-all" style="font-size:28px;display:block;margin-bottom:8px;"></i>
                            {{ __('leads.no_pending_activities') }}
                        </div>
                    @endforelse
                </div>

                {{-- Right: task detail --}}
                <div style="width:50%;overflow-y:auto;max-height:500px;" id="actTaskDetail">
                    <div style="display:flex;align-items:center;justify-content:center;height:100%;color:#d1d5db;flex-direction:column;gap:8px;padding:40px;">
                        <i class="bi bi-hand-index" style="font-size:28px;"></i>
                        <span style="font-size:13px;">{{ __('leads.select_activity') }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Tab: Histórico (unified feed) ── --}}
        <div class="lp-tab-panel" id="tab-timeline">
            {{-- Timeline feed --}}
            <div class="lp-timeline" id="timelineFeed" style="padding:16px 20px;">
                @php
                    // Merge events + notes + score logs into unified timeline
                    $timelineItems = collect();

                    // Events
                    foreach ($lead->events as $evt) {
                        $evtIcons = [
                            'created' => ['bi-plus-circle-fill', '#10b981'],
                            'updated' => ['bi-pencil-fill', '#6b7280'],
                            'stage_changed' => ['bi-arrow-right-circle-fill', '#8b5cf6'],
                            'assigned' => ['bi-person-check', '#3b82f6'],
                            'note_added' => ['bi-journal-text', '#f59e0b'],
                            'sale_won' => ['bi-trophy-fill', '#10b981'],
                            'sale_lost' => ['bi-x-circle-fill', '#ef4444'],
                            'score_updated' => ['bi-lightning-fill', '#d97706'],
                        ];
                        $iconData = $evtIcons[$evt->event_type] ?? ['bi-circle-fill', '#9ca3af'];
                        $timelineItems->push([
                            'date' => $evt->created_at,
                            'icon' => $iconData[0],
                            'color' => $iconData[1],
                            'text' => $evt->description,
                            'meta' => ($evt->performedBy?->name ?? 'Sistema') . ' · ' . $evt->created_at?->diffForHumans(),
                            'type' => 'event',
                        ]);
                    }

                    // Notes
                    foreach ($lead->leadNotes as $note) {
                        $timelineItems->push([
                            'date' => $note->created_at,
                            'icon' => 'bi-sticky-fill',
                            'color' => '#f59e0b',
                            'text' => Str::limit($note->body, 120),
                            'meta' => ($note->author?->name ?? 'Sistema') . ' · ' . $note->created_at?->diffForHumans(),
                            'type' => 'note',
                        ]);
                    }

                    // Score logs
                    foreach ($lead->scoreLogs->take(10) as $sl) {
                        $pts = $sl->points >= 0 ? '+' . $sl->points : (string) $sl->points;
                        $timelineItems->push([
                            'date' => $sl->created_at,
                            'icon' => 'bi-lightning-fill',
                            'color' => $sl->points >= 0 ? '#059669' : '#ef4444',
                            'text' => $pts . ' pts — ' . $sl->reason,
                            'meta' => $sl->created_at?->diffForHumans(),
                            'type' => 'score',
                        ]);
                    }

                    $timelineItems = $timelineItems->sortByDesc('date')->take(30);
                @endphp

                @forelse($timelineItems as $item)
                <div class="lp-timeline-item">
                    <div class="lp-tl-icon" style="color:{{ $item['color'] }};background:{{ $item['color'] }}15;">
                        <i class="bi {{ $item['icon'] }}"></i>
                    </div>
                    <div class="lp-tl-body">
                        <div class="lp-tl-desc">{{ $item['text'] }}</div>
                        <div class="lp-tl-meta">{{ $item['meta'] }}</div>
                    </div>
                </div>
                @empty
                <div style="text-align:center;padding:32px;color:#9ca3af;font-size:13px;">
                    <i class="bi bi-activity" style="font-size:28px;display:block;margin-bottom:8px;color:#d1d5db;"></i>
                    {{ __('leads.no_activities_recorded') }}
                </div>
                @endforelse
            </div>
        </div>

        {{-- ── Tab: Notas ── --}}
        <div class="lp-tab-panel" id="tab-notes">
            <div id="notesContainer">
                @forelse($lead->leadNotes as $note)
                <div class="lp-note-card" id="note-{{ $note->id }}">
                    <div class="lp-note-header">
                        <div class="lp-note-avatar">{{ strtoupper(substr($note->author?->name ?? '?', 0, 1)) }}</div>
                        <div class="lp-note-meta">
                            <div class="lp-note-author">{{ $note->author?->name ?? __('leads.unknown_author') }}</div>
                            <div class="lp-note-date">{{ $note->created_at->diffForHumans() }}</div>
                        </div>
                        @if($note->created_by === auth()->id())
                        <button class="lp-note-del" onclick="deletePageNote({{ $note->id }})" title="{{ __('leads.delete_note_title') }}">
                            <i class="bi bi-trash3"></i>
                        </button>
                        @endif
                    </div>
                    <div class="lp-note-body">{{ $note->body }}</div>
                </div>
                @empty
                <div id="notesEmpty" style="text-align:center;padding:30px 20px;color:#9ca3af;">
                    <i class="bi bi-journal-x" style="font-size:32px;opacity:.3;display:block;margin-bottom:8px;"></i>
                    {{ __('leads.no_notes') }}
                </div>
                @endforelse
            </div>

            {{-- Form nova nota --}}
            <div style="margin-top:16px;padding-top:16px;border-top:1px solid #f0f2f7;">
                <textarea id="newNoteBody" class="lp-note-textarea" placeholder="{{ __('leads.note_placeholder') }}"></textarea>
                <button class="lp-btn-add-note" id="btnAddNote" onclick="addPageNote()">
                    <i class="bi bi-plus-lg"></i> {{ __('leads.add_note') }}
                </button>
            </div>
        </div>

        {{-- ── Tab: Histórico ── --}}
        <div class="lp-tab-panel" id="tab-history">
            @if($lead->events->count() === 0)
            <div style="text-align:center;padding:40px 20px;color:#9ca3af;">
                <i class="bi bi-clock" style="font-size:32px;opacity:.3;display:block;margin-bottom:8px;"></i>
                {{ __('leads.no_events') }}
            </div>
            @else
            <ul class="lp-timeline">
                @foreach($lead->events as $event)
                @php
                    [$iconClass, $iconBg, $iconColor] = match($event->event_type) {
                        'created'         => ['bi-star-fill',               '#f0fdf4', '#10b981'],
                        'stage_changed'   => ['bi-arrow-right-circle-fill', '#eff6ff', '#3b82f6'],
                        'note_added'      => ['bi-chat-left-text-fill',     '#faf5ff', '#8b5cf6'],
                        'ai_tag_added'    => ['bi-robot',                   '#f0fdf4', '#10b981'],
                        'ai_note'         => ['bi-robot',                   '#faf5ff', '#8b5cf6'],
                        'ai_field_filled' => ['bi-robot',                   '#f0fdf4', '#10b981'],
                        'ai_data_updated' => ['bi-robot',                   '#eff6ff', '#3b82f6'],
                        default           => ['bi-pencil-fill',              '#fff7ed', '#f59e0b'],
                    };
                @endphp
                <li class="lp-timeline-item">
                    <div class="lp-tl-icon" style="background:{{ $iconBg }};color:{{ $iconColor }};">
                        <i class="bi {{ $iconClass }}"></i>
                    </div>
                    <div class="lp-tl-body">
                        <div class="lp-tl-desc">{{ $event->description }}</div>
                        <div class="lp-tl-meta">
                            @if($event->performedBy)
                                {{ __('leads.by_performer', ['name' => $event->performedBy->name]) }}
                            @elseif(($event->data_json['source'] ?? '') === 'ai_agent')
                                {{ __('leads.by_ai_agent') }}
                            @else
                                {{ __('leads.by_system') }}
                            @endif
                            · {{ $event->created_at?->format('d/m/Y H:i') }}
                        </div>
                    </div>
                </li>
                @endforeach
            </ul>
            @endif
        </div>

        {{-- ── Tab: WhatsApp ── --}}
        @if($waConversation)
        <div class="lp-tab-panel" id="tab-whatsapp">
            <a href="{{ route('chats.index') }}" target="_blank" class="lp-chat-link">
                <i class="bi bi-whatsapp" style="color:#25d366;"></i>
                {{ __('leads.view_full_chat') }}
                <i class="bi bi-box-arrow-up-right" style="font-size:11px;"></i>
            </a>

            @php
                $waMessages  = $waConversation->messages;
                $lastWaDate  = null;
            @endphp
            <div class="lp-messages">
                @foreach($waMessages as $msg)
                @php
                    $msgDay = $msg->sent_at?->format('d/m/Y');
                @endphp
                @if($msgDay && $msgDay !== $lastWaDate)
                @php $lastWaDate = $msgDay; @endphp
                <div class="lp-date-sep">
                    @if($msgDay === now()->format('d/m/Y')) {{ __('leads.today') }}
                    @elseif($msgDay === now()->subDay()->format('d/m/Y')) {{ __('leads.yesterday') }}
                    @else {{ $msgDay }}
                    @endif
                </div>
                @endif

                @php
                    $isOut = $msg->direction === 'outbound';
                    $isNote = $msg->type === 'note';
                @endphp
                @if($isNote)
                    <div class="lp-note-internal">
                        <div class="lp-note-internal-header">
                            <i class="bi bi-lock-fill"></i> {{ __('leads.internal_note') }}
                        </div>
                        <div class="lp-note-internal-body">{{ $msg->body }}</div>
                        <div class="lp-note-internal-time">{{ $msg->sent_at?->format('H:i') }}</div>
                    </div>
                @else
                <div class="lp-bubble-wrap {{ $isOut ? 'out' : 'in' }}">
                    <div class="lp-bubble {{ $isOut ? 'out' : 'in' }}">
                        @if($msg->type === 'image' && $msg->media_url)
                            <img src="{{ $msg->media_url }}" alt="{{ __('leads.image_alt') }}" onclick="window.open(this.src,'_blank')">
                        @elseif($msg->type === 'video' && $msg->media_url)
                            <video src="{{ $msg->media_url }}" controls preload="metadata" style="max-width:100%;border-radius:8px;"></video>
                        @elseif($msg->type === 'audio' && $msg->media_url)
                            <audio controls src="{{ $msg->media_url }}"></audio>
                        @elseif($msg->body)
                            {{ $msg->body }}
                        @else
                            <em style="opacity:.5;">{{ ucfirst($msg->type ?? __('leads.message_fallback')) }}</em>
                        @endif
                    </div>
                    <div class="lp-bubble-time">{{ $msg->sent_at?->format('H:i') }}</div>
                </div>
                @endif
                @endforeach
            </div>
        </div>
        @endif

        {{-- ── Tab: Instagram ── --}}
        @if($igConversation || $lead->instagram_username)
        <div class="lp-tab-panel" id="tab-instagram">
            @if(!$igConversation)
            <div class="lp-empty-conv">
                <i class="bi bi-instagram"></i>
                <p>{{ __('leads.no_ig_conversation') }}</p>
                @if($lead->instagram_username)
                <p style="font-size:12px;margin-top:4px;">{{ __('leads.username_label') }}: <strong>{{ $lead->instagram_username }}</strong></p>
                @endif
            </div>
            @else
            <a href="{{ route('chats.index') }}" target="_blank" class="lp-chat-link">
                <i class="bi bi-instagram" style="color:#e1306c;"></i>
                {{ __('leads.view_full_chat') }}
                <i class="bi bi-box-arrow-up-right" style="font-size:11px;"></i>
            </a>

            @php
                $igMessages = $igConversation->messages;
                $lastIgDate = null;
            @endphp
            <div class="lp-messages">
                @foreach($igMessages as $msg)
                @php
                    $msgDay = $msg->sent_at?->format('d/m/Y');
                @endphp
                @if($msgDay && $msgDay !== $lastIgDate)
                @php $lastIgDate = $msgDay; @endphp
                <div class="lp-date-sep">
                    @if($msgDay === now()->format('d/m/Y')) {{ __('leads.today') }}
                    @elseif($msgDay === now()->subDay()->format('d/m/Y')) {{ __('leads.yesterday') }}
                    @else {{ $msgDay }}
                    @endif
                </div>
                @endif

                @php $isOut = $msg->direction === 'outbound'; @endphp
                <div class="lp-bubble-wrap {{ $isOut ? 'out' : 'in' }}">
                    <div class="lp-bubble {{ $isOut ? 'ig-out' : 'in' }}">
                        @if($msg->type === 'image' && $msg->media_url)
                            <img src="{{ $msg->media_url }}" alt="{{ __('leads.image_alt') }}" onclick="window.open(this.src,'_blank')">
                        @elseif($msg->type === 'video' && $msg->media_url)
                            <video src="{{ $msg->media_url }}" controls preload="metadata" style="max-width:100%;border-radius:8px;"></video>
                        @elseif($msg->type === 'audio' && $msg->media_url)
                            <audio controls src="{{ $msg->media_url }}"></audio>
                        @elseif($msg->body)
                            {{ $msg->body }}
                        @else
                            <em style="opacity:.5;">{{ ucfirst($msg->type ?? __('leads.message_fallback')) }}</em>
                        @endif
                    </div>
                    <div class="lp-bubble-time">{{ $msg->sent_at?->format('H:i') }}</div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
        @endif

        {{-- ── Tab: Anexos ── --}}
        <div class="lp-tab-panel" id="tab-attachments">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                <span style="font-size:13px;color:#6b7280;">
                    @if($lead->attachments->count() === 0) {{ __('leads.no_attachments') }}
                    @elseif($lead->attachments->count() === 1) {{ __('leads.one_attachment') }}
                    @else {{ __('leads.n_attachments', ['count' => $lead->attachments->count()]) }}
                    @endif
                </span>
                <label for="attachFileInput" style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;background:#eff6ff;color:#0085f3;border:none;border-radius:100px;font-size:13px;font-weight:600;cursor:pointer;">
                    <i class="bi bi-plus-lg"></i> {{ __('leads.send_file') }}
                </label>
                <input type="file" id="attachFileInput" style="display:none;" onchange="uploadAttachment(this.files[0])">
            </div>

            <div id="attachmentsList">
                @forelse($lead->attachments as $att)
                <div class="lp-attach-item" id="attach-{{ $att->id }}">
                    <div class="lp-attach-icon">
                        @if(str_starts_with($att->mime_type, 'image/'))
                            <i class="bi bi-file-earmark-image" style="color:#8b5cf6;"></i>
                        @elseif($att->mime_type === 'application/pdf')
                            <i class="bi bi-file-earmark-pdf" style="color:#ef4444;"></i>
                        @elseif(str_contains($att->mime_type, 'spreadsheet') || str_contains($att->mime_type, 'excel'))
                            <i class="bi bi-file-earmark-spreadsheet" style="color:#10b981;"></i>
                        @elseif(str_contains($att->mime_type, 'word') || str_contains($att->mime_type, 'document'))
                            <i class="bi bi-file-earmark-word" style="color:#0085f3;"></i>
                        @else
                            <i class="bi bi-file-earmark" style="color:#6b7280;"></i>
                        @endif
                    </div>
                    <div class="lp-attach-info">
                        <div class="lp-attach-name">{{ $att->original_name }}</div>
                        <div class="lp-attach-meta">
                            {{ $att->uploader?->name ?? __('leads.system') }} · {{ $att->created_at->format('d/m/Y H:i') }}
                            · {{ number_format($att->file_size / 1024, 0) }} KB
                        </div>
                    </div>
                    <div class="lp-attach-actions">
                        <a href="{{ Storage::disk('public')->url($att->storage_path) }}" target="_blank" class="lp-attach-btn" title="{{ __('leads.open_title') }}">
                            <i class="bi bi-download"></i>
                        </a>
                        <button class="lp-attach-btn lp-attach-del" onclick="deleteAttachment({{ $att->id }})" title="{{ __('leads.delete_title') }}">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </div>
                </div>
                @empty
                <div id="attachEmpty" style="text-align:center;padding:40px 20px;color:#9ca3af;">
                    <i class="bi bi-paperclip" style="font-size:32px;opacity:.3;display:block;margin-bottom:8px;"></i>
                    {{ __('leads.no_attachments_added') }}
                </div>
                @endforelse
            </div>
        </div>

        {{-- ── Tab: Agendamentos ── --}}
        <div class="lp-tab-panel" id="tab-scheduled">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                <span style="font-size:13px;color:#6b7280;">
                    @if($scheduledMessages->count() === 0)
                        {{ __('leads.no_scheduled_messages') }}
                    @elseif($scheduledMessages->count() === 1)
                        {{ __('leads.one_scheduled_message') }}
                    @else
                        {{ __('leads.n_scheduled_messages', ['count' => $scheduledMessages->count()]) }}
                    @endif
                </span>
                <button onclick="openScheduleModal()" style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;background:#eff6ff;color:#3b82f6;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;">
                    <i class="bi bi-plus-lg"></i> {{ __('leads.schedule_message') }}
                </button>
            </div>
            <div id="scheduledList">
                @php
                    $smStatusLabels = ['pending'=>__('leads.status_pending'),'sent'=>__('leads.status_sent'),'failed'=>__('leads.status_failed'),'cancelled'=>__('leads.status_cancelled')];
                    $smStatusStyles = [
                        'pending'   => 'background:#fff7ed;color:#f59e0b;',
                        'sent'      => 'background:#f0fdf4;color:#10b981;',
                        'failed'    => 'background:#fef2f2;color:#ef4444;',
                        'cancelled' => 'background:#f9fafb;color:#6b7280;',
                    ];
                    $smTypeIcons = ['text'=>'bi-chat-text','image'=>'bi-image','document'=>'bi-file-earmark'];
                @endphp
                @forelse($scheduledMessages as $sm)
                <div class="lp-sm-card" id="sm-{{ $sm->id }}">
                    <div class="lp-sm-icon">
                        <i class="bi {{ $smTypeIcons[$sm->type] ?? 'bi-chat' }}"></i>
                    </div>
                    <div class="lp-sm-body">
                        <div class="lp-sm-preview">
                            @if($sm->body) {{ \Illuminate\Support\Str::limit($sm->body, 80) }}
                            @elseif($sm->media_filename) {{ $sm->media_filename }}
                            @else <em style="opacity:.5;">{{ __('leads.no_content') }}</em>
                            @endif
                        </div>
                        <div class="lp-sm-meta">
                            <span class="lp-sm-badge" style="{{ $smStatusStyles[$sm->status] ?? '' }}">
                                {{ $smStatusLabels[$sm->status] ?? $sm->status }}
                            </span>
                            <span>{{ $sm->send_at->translatedFormat('d/m/Y \à\s H:i') }}</span>
                            @if($sm->error)
                            <span style="color:#ef4444;" title="{{ $sm->error }}">
                                <i class="bi bi-exclamation-circle"></i> {{ \Illuminate\Support\Str::limit($sm->error, 50) }}
                            </span>
                            @endif
                        </div>
                    </div>
                    @if($sm->status === 'pending')
                    <button class="lp-sm-cancel" onclick="cancelScheduled({{ $sm->id }})" title="{{ __('leads.cancel_schedule') }}">
                        <i class="bi bi-x-lg"></i>
                    </button>
                    @endif
                </div>
                @empty
                <div id="scheduledEmpty" style="text-align:center;padding:40px 20px;color:#9ca3af;">
                    <i class="bi bi-clock" style="font-size:32px;opacity:.3;display:block;margin-bottom:8px;"></i>
                    {{ __('leads.no_scheduled_empty') }}
                </div>
                @endforelse
            </div>
        </div>

        {{-- ── Tab: Tarefas ── --}}
        <div class="lp-tab-panel" id="tab-tasks">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                <span style="font-size:13px;color:#6b7280;">
                    @if(isset($tasks))
                        @if($tasks->count() === 0) {{ __('leads.no_tasks') }}
                        @elseif($tasks->count() === 1) {{ __('leads.one_task') }}
                        @else {{ __('leads.n_tasks', ['count' => $tasks->count()]) }}
                        @endif
                    @else {{ __('leads.no_tasks') }}
                    @endif
                </span>
                <button onclick="openLeadTaskDrawer()" style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;background:#eff6ff;color:#3b82f6;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;">
                    <i class="bi bi-plus-lg"></i> {{ __('leads.new_task') }}
                </button>
            </div>
            <div id="leadTaskList" style="display:flex;flex-direction:column;gap:8px;">
                @php
                    $taskTypeIcons = ['call'=>'bi-telephone','email'=>'bi-envelope','task'=>'bi-check2-square','visit'=>'bi-geo-alt','whatsapp'=>'bi-whatsapp','meeting'=>'bi-camera-video'];
                    $taskTypeLabels = ['call'=>__('leads.task_type_call'),'email'=>__('leads.task_type_email'),'task'=>__('leads.task_type_task'),'visit'=>__('leads.task_type_visit'),'whatsapp'=>__('leads.task_type_whatsapp'),'meeting'=>__('leads.task_type_meeting')];
                @endphp
                @forelse(($tasks ?? collect()) as $tk)
                @php
                    $tkDays = $tk->due_date ? (int) today()->diffInDays($tk->due_date, false) : 999;
                    $tkColor = $tk->status === 'completed' ? '#10b981' : ($tkDays <= 1 ? '#ef4444' : ($tkDays <= 3 ? '#f59e0b' : '#10b981'));
                    $tkIcon = $taskTypeIcons[$tk->type] ?? 'bi-check2-square';
                    $tkDone = $tk->status === 'completed';
                @endphp
                <div class="lp-task-card" id="lead-task-{{ $tk->id }}" style="display:flex;align-items:center;gap:10px;padding:10px 14px;border:1px solid #e8eaf0;border-radius:10px;{{ $tkDone ? 'opacity:.5;' : '' }}">
                    <div onclick="toggleLeadTask({{ $tk->id }})" style="width:18px;height:18px;border-radius:50%;border:2px solid {{ $tkDone ? '#10b981' : '#d1d5db' }};{{ $tkDone ? 'background:#10b981;' : '' }}cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        @if($tkDone)<span style="color:#fff;font-size:10px;font-weight:700;">&#10003;</span>@endif
                    </div>
                    <div style="width:28px;height:28px;border-radius:7px;background:{{ $tkColor }}15;color:{{ $tkColor }};display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0;">
                        <i class="bi {{ $tkIcon }}"></i>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:13px;font-weight:600;color:#1a1d23;{{ $tkDone ? 'text-decoration:line-through;' : '' }}">{{ $tk->subject }}</div>
                        <div style="font-size:11px;color:#9ca3af;margin-top:1px;">
                            {{ $taskTypeLabels[$tk->type] ?? $tk->type }}
                            @if($tk->assignedTo) &middot; {{ $tk->assignedTo->name }} @endif
                        </div>
                    </div>
                    <span style="font-size:11px;font-weight:600;padding:3px 8px;border-radius:6px;background:{{ $tkColor }}20;color:{{ $tkColor }};white-space:nowrap;">
                        {{ $tk->due_time ? substr($tk->due_time, 0, 5) . ' ' : '' }}{{ $tk->due_date?->format('d/m/Y') }}
                    </span>
                </div>
                @empty
                <div style="text-align:center;padding:40px 20px;color:#9ca3af;">
                    <i class="bi bi-check2-square" style="font-size:32px;opacity:.3;display:block;margin-bottom:8px;"></i>
                    {{ __('leads.no_tasks_linked') }}
                </div>
                @endforelse
            </div>
        </div>

        {{-- ── Tab: Produtos ── --}}
        <div class="lp-tab-panel" id="tab-products">
            <div style="padding:16px 20px;">
                @if($lead->products->count() > 0)
                    <table style="width:100%;border-collapse:collapse;font-size:13px;">
                        <thead>
                            <tr style="border-bottom:1px solid #f0f2f7;">
                                <th style="text-align:left;padding:8px 10px;font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;">{{ __('leads.product_col') }}</th>
                                <th style="text-align:center;padding:8px 10px;font-size:11px;font-weight:700;color:#9ca3af;">{{ __('leads.qty_col') }}</th>
                                <th style="text-align:right;padding:8px 10px;font-size:11px;font-weight:700;color:#9ca3af;">{{ __('leads.unit_price_col') }}</th>
                                <th style="text-align:right;padding:8px 10px;font-size:11px;font-weight:700;color:#9ca3af;">{{ __('leads.total_col') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lead->products as $lp)
                            <tr style="border-bottom:1px solid #f7f8fa;">
                                <td style="padding:10px;">
                                    <div style="font-weight:600;color:#1a1d23;">{{ $lp->product?->name ?? '—' }}</div>
                                    @if($lp->product?->sku)<div style="font-size:11px;color:#9ca3af;">SKU: {{ $lp->product->sku }}</div>@endif
                                </td>
                                <td style="text-align:center;padding:10px;color:#374151;">{{ number_format($lp->quantity, 0) }}</td>
                                <td style="text-align:right;padding:10px;color:#374151;">R$ {{ number_format($lp->unit_price, 2, ',', '.') }}</td>
                                <td style="text-align:right;padding:10px;font-weight:600;color:#1a1d23;">R$ {{ number_format($lp->total, 2, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr style="border-top:2px solid #e8eaf0;">
                                <td colspan="3" style="padding:10px;text-align:right;font-size:13px;font-weight:700;color:#374151;">{{ __('leads.total_col') }}</td>
                                <td style="padding:10px;text-align:right;font-size:15px;font-weight:800;color:#059669;">R$ {{ number_format($lead->products->sum('total'), 2, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                @else
                    <div style="text-align:center;padding:40px 20px;color:#9ca3af;">
                        <i class="bi bi-box-seam" style="font-size:32px;opacity:.3;display:block;margin-bottom:8px;"></i>
                        {{ __('leads.no_products_linked') }}
                    </div>
                @endif
            </div>
        </div>

    </div>{{-- end left col --}}

    {{-- ── Right: Info card ── --}}
    {{-- Card 1 — Contato & Negócio --}}
    <div class="lp-info-card">

        {{-- Contato --}}
        <div class="lp-info-section">
            <div class="lp-info-section-title">{{ __('leads.contact_section') }}</div>

            <div class="lp-info-row">
                <div class="lp-info-icon"><i class="bi bi-telephone"></i></div>
                <div class="lp-info-val" style="display:flex;align-items:center;gap:6px;">
                    @if($lead->phone)
                    <a href="https://wa.me/{{ preg_replace('/\D/', '', $lead->phone) }}" target="_blank">
                        {{ $lead->phone }}
                    </a>
                    <button class="lp-copy-btn" onclick="copyToClipboard('{{ $lead->phone }}', this)" title="Copiar">
                        <i class="bi bi-clipboard"></i>
                    </button>
                    @else
                    <span class="lp-info-empty">—</span>
                    @endif
                </div>
            </div>

            <div class="lp-info-row">
                <div class="lp-info-icon"><i class="bi bi-envelope"></i></div>
                <div class="lp-info-val" style="display:flex;align-items:center;gap:6px;">
                    @if($lead->email)
                    <a href="mailto:{{ $lead->email }}">{{ $lead->email }}</a>
                    <button class="lp-copy-btn" onclick="copyToClipboard('{{ $lead->email }}', this)" title="Copiar">
                        <i class="bi bi-clipboard"></i>
                    </button>
                    @else
                    <span class="lp-info-empty">—</span>
                    @endif
                </div>
            </div>

            @if($lead->company)
            <div class="lp-info-row">
                <div class="lp-info-icon"><i class="bi bi-building"></i></div>
                <div class="lp-info-val">{{ $lead->company }}</div>
            </div>
            @endif

            @if($lead->instagram_username)
            <div class="lp-info-row">
                <div class="lp-info-icon"><i class="bi bi-instagram"></i></div>
                <div class="lp-info-val">
                    <a href="https://instagram.com/{{ ltrim($lead->instagram_username,'@') }}" target="_blank">
                        {{ ltrim($lead->instagram_username,'@') }}
                    </a>
                </div>
            </div>
            @endif
        </div>

        {{-- Negócio --}}
        <div class="lp-info-section">
            <div class="lp-info-section-title">{{ __('leads.deal') }}</div>

            <div class="lp-info-row">
                <div class="lp-info-icon"><i class="bi bi-currency-dollar"></i></div>
                <div class="lp-info-val" style="font-weight:700;color:#10b981;">
                    @if($lead->value)
                    {{ __('common.currency') }} {{ number_format((float)$lead->value, 2, __('common.decimal_sep'), __('common.thousands_sep')) }}
                    @else
                    <span class="lp-info-empty">—</span>
                    @endif
                </div>
            </div>

            <div class="lp-info-row">
                <div class="lp-info-icon"><i class="bi bi-tag"></i></div>
                <div class="lp-info-val">
                    @if($lead->source)
                    <span class="source-pill">{{ $lead->source }}</span>
                    @else
                    <span class="lp-info-empty">—</span>
                    @endif
                </div>
            </div>

            <div class="lp-info-row">
                <div class="lp-info-icon"><i class="bi bi-megaphone"></i></div>
                <div class="lp-info-val">
                    {{ $lead->campaign?->name ?? '—' }}
                </div>
            </div>

            <div class="lp-info-row">
                <div class="lp-info-icon"><i class="bi bi-person-check"></i></div>
                <div class="lp-info-val" style="display:flex;align-items:center;gap:8px;">
                    @if($lead->assignedTo)
                    @if($lead->assignedTo->avatar)
                    <img src="{{ asset($lead->assignedTo->avatar) }}" alt="" style="width:22px;height:22px;border-radius:50%;object-fit:cover;flex-shrink:0;">
                    @endif
                    {{ $lead->assignedTo->name }}
                    @else
                    <span class="lp-info-empty">{{ __('leads.not_assigned') }}</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Tags --}}
        @if(!empty($lead->tags))
        <div class="lp-info-section">
            <div class="lp-info-section-title">{{ __('leads.tags') }}</div>
            <div>
                @foreach($lead->tags as $tag)
                <span class="lp-tag-chip">{{ $tag }}</span>
                @endforeach
            </div>
        </div>
        @endif

        {{-- UTM & Rastreamento (colapsável) --}}
    @if($lead->utm_source || $lead->utm_medium || $lead->utm_campaign || $lead->utm_term || $lead->utm_content || $lead->fbclid || $lead->gclid)
        <div class="lp-info-section" style="cursor:pointer;" onclick="this.nextElementSibling.classList.toggle('collapsed');">
            <div class="lp-info-section-title" style="display:flex;justify-content:space-between;align-items:center;">
                {{ __('leads.utm_section') }}
                <i class="bi bi-chevron-down" style="font-size:12px;color:#9ca3af;transition:transform .2s;"></i>
            </div>
        </div>
        <div class="lp-utm-body collapsed">
            @if($lead->utm_source)
            <div class="lp-info-row" style="padding:4px 18px;">
                <span class="source-pill" style="font-size:11px;">utm_source: {{ $lead->utm_source }}</span>
            </div>
            @endif
            @if($lead->utm_medium)
            <div class="lp-info-row" style="padding:4px 18px;">
                <span class="source-pill" style="font-size:11px;background:#f3f4f6;color:#374151;">utm_medium: {{ $lead->utm_medium }}</span>
            </div>
            @endif
            @if($lead->utm_campaign)
            <div class="lp-info-row" style="padding:4px 18px;">
                <span class="source-pill" style="font-size:11px;background:#eff6ff;color:#2563eb;">utm_campaign: {{ $lead->utm_campaign }}</span>
            </div>
            @endif
            @if($lead->utm_term)
            <div class="lp-info-row" style="padding:4px 18px;">
                <span class="source-pill" style="font-size:11px;background:#f3f4f6;color:#374151;">utm_term: {{ $lead->utm_term }}</span>
            </div>
            @endif
            @if($lead->utm_content)
            <div class="lp-info-row" style="padding:4px 18px;">
                <span class="source-pill" style="font-size:11px;background:#f3f4f6;color:#374151;">utm_content: {{ $lead->utm_content }}</span>
            </div>
            @endif
            @if($lead->fbclid)
            <div class="lp-info-row" style="padding:4px 18px;">
                <span style="font-size:10.5px;color:#9ca3af;">fbclid: {{ Str::limit($lead->fbclid, 30) }}</span>
            </div>
            @endif
            @if($lead->gclid)
            <div class="lp-info-row" style="padding:4px 18px;">
                <span style="font-size:10.5px;color:#9ca3af;">gclid: {{ Str::limit($lead->gclid, 30) }}</span>
            </div>
            @endif
            <div style="height:8px;"></div>
        </div>
    @endif

        {{-- Contatos da Empresa --}}
        <div class="lp-info-section">
            <div class="lp-info-section-title" style="display:flex;justify-content:space-between;align-items:center;">
                {{ __('leads.contacts_section') }}
                <button class="lp-add-contact-btn" onclick="toggleContactForm()" title="{{ __('leads.add_contact') }}">
                    <i class="bi bi-plus-circle"></i>
                </button>
            </div>

            {{-- Add / Edit contact form (hidden by default) --}}
            <div id="addContactForm" class="lp-contact-form" style="display:none;">
                <input type="hidden" id="editContactId" value="">
                <input type="text" id="contactName" placeholder="{{ __('leads.contact_name') }}">
                <input type="text" id="contactRole" placeholder="{{ __('leads.role_placeholder') }}">
                <input type="text" id="contactPhone" placeholder="{{ __('leads.contact_phone') }}">
                <input type="text" id="contactEmail" placeholder="{{ __('leads.contact_email') }}">
                <div class="lp-contact-form-btns">
                    <button class="btn-save-contact" onclick="saveContact()">{{ __('leads.save') }}</button>
                    <button class="btn-cancel-contact" onclick="toggleContactForm(false)">{{ __('leads.cancel') }}</button>
                </div>
            </div>

            {{-- Contact list --}}
            <div id="contactsList">
                <div style="text-align:center;padding:8px 0;color:#d1d5db;font-size:12px;">
                    <i class="bi bi-hourglass-split"></i>
                </div>
            </div>
        </div>

        {{-- Campos personalizados --}}
        @php $customFields = $lead->custom_fields; @endphp
        @if(!empty($customFields))
        <div class="lp-info-section">
            <div class="lp-info-section-title">{{ __('leads.custom_fields') }}</div>
            @foreach($customFields as $field)
            <div class="lp-info-row" style="align-items:flex-start;">
                <div class="lp-info-icon"><i class="bi bi-input-cursor-text"></i></div>
                <div class="lp-info-val">
                    <div style="font-size:11px;color:#9ca3af;font-weight:600;margin-bottom:2px;">{{ $field['label'] }}</div>
                    @if($field['value'] !== null && $field['value'] !== '')
                        @if($field['type'] === 'checkbox')
                            {{ $field['value'] ? __('leads.yes') : __('leads.no') }}
                        @elseif($field['type'] === 'currency')
                            {{ __('common.currency') }} {{ number_format((float)$field['value'], 2, __('common.decimal_sep'), __('common.thousands_sep')) }}
                        @elseif($field['type'] === 'multiselect' && is_array($field['value']))
                            {{ implode(', ', $field['value']) }}
                        @elseif($field['type'] === 'url')
                            <a href="{{ $field['value'] }}" target="_blank">{{ $field['value'] }}</a>
                        @else
                            {{ $field['value'] }}
                        @endif
                    @else
                        <span class="lp-info-empty">—</span>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @endif

        {{-- Opt-out --}}
        @if($lead->opted_out)
        <div class="lp-info-section">
            <div style="display:flex;align-items:center;gap:8px;padding:8px 12px;background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;">
                <i class="bi bi-exclamation-triangle-fill" style="color:#dc2626;"></i>
                <div>
                    <div style="font-size:12px;font-weight:600;color:#dc2626;">Opted-out</div>
                    @if($lead->opted_out_reason)<div style="font-size:11px;color:#9ca3af;">{{ $lead->opted_out_reason }}</div>@endif
                </div>
            </div>
        </div>
        @endif

        {{-- Vendas --}}
        @if($lead->sales->count() > 0 || $lead->lostSales->count() > 0)
        <div class="lp-info-section">
            <div class="lp-info-section-title">Vendas</div>
            @foreach($lead->sales as $sale)
            <div style="display:flex;align-items:center;gap:8px;padding:6px 0;">
                <div style="width:24px;height:24px;border-radius:50%;background:#f0fdf4;color:#10b981;display:flex;align-items:center;justify-content:center;font-size:11px;flex-shrink:0;">
                    <i class="bi bi-check-lg"></i>
                </div>
                <div style="flex:1;">
                    <div style="font-size:12.5px;font-weight:600;color:#059669;">R$ {{ number_format((float) $sale->value, 2, ',', '.') }}</div>
                    <div style="font-size:11px;color:#9ca3af;">{{ $sale->closed_at?->format('d/m/Y') }} · {{ $sale->closedBy?->name }}</div>
                </div>
            </div>
            @endforeach
            @foreach($lead->lostSales as $lost)
            <div style="display:flex;align-items:center;gap:8px;padding:6px 0;">
                <div style="width:24px;height:24px;border-radius:50%;background:#fef2f2;color:#dc2626;display:flex;align-items:center;justify-content:center;font-size:11px;flex-shrink:0;">
                    <i class="bi bi-x-lg"></i>
                </div>
                <div style="flex:1;">
                    <div style="font-size:12.5px;font-weight:600;color:#dc2626;">Perdida</div>
                    <div style="font-size:11px;color:#9ca3af;">{{ $lost->lost_at?->format('d/m/Y') }} · {{ $lost->reason?->name }}</div>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        {{-- Datas --}}
        <div class="lp-info-section">
            <div class="lp-info-section-title">{{ __('leads.dates_section') }}</div>
            <div class="lp-info-row">
                <div class="lp-info-icon"><i class="bi bi-calendar-plus"></i></div>
                <div class="lp-info-val" style="font-size:12.5px;">
                    {{ __('leads.created_on_date', ['date' => $lead->created_at->format('d/m/Y H:i')]) }}
                </div>
            </div>
            <div class="lp-info-row">
                <div class="lp-info-icon"><i class="bi bi-calendar-check"></i></div>
                <div class="lp-info-val" style="font-size:12.5px;">
                    {{ __('leads.updated_ago', ['time' => $lead->updated_at->diffForHumans()]) }}
                </div>
            </div>
        </div>

    </div>{{-- end info card --}}

</div>{{-- end grid --}}

</div>{{-- end page-container --}}

{{-- Drawer compartilhado (para edição) --}}
@include('tenant.leads._drawer', ['pipelines' => $pipelines, 'customFieldDefs' => $cfDefs])

{{-- Drawer para criar tarefa inline --}}
<div id="newTaskOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.35);z-index:1000;" onclick="closeNewTaskDrawer()"></div>
<div id="newTaskDrawer" style="position:fixed;top:0;right:-440px;width:420px;max-width:100vw;height:100%;background:#fff;z-index:1001;box-shadow:-4px 0 20px rgba(0,0,0,.1);transition:right .3s;display:flex;flex-direction:column;">
    <div style="padding:18px 22px;border-bottom:1px solid #f0f2f7;display:flex;align-items:center;justify-content:space-between;">
        <h4 style="margin:0;font-size:15px;font-weight:700;color:#1a1d23;display:flex;align-items:center;gap:8px;">
            <i class="bi bi-plus-circle" style="color:#0085f3;"></i> {{ __('leads.new_activity_title') }}
        </h4>
        <button onclick="closeNewTaskDrawer()" style="background:none;border:none;font-size:18px;cursor:pointer;color:#9ca3af;">&times;</button>
    </div>
    <div style="flex:1;overflow-y:auto;padding:18px 22px;">
        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('leads.subject_label') }} *</label>
            <input id="ntSubject" type="text" style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;font-family:inherit;" placeholder="{{ __('leads.subject_placeholder') }}">
        </div>
        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('leads.description_label') }}</label>
            <textarea id="ntDescription" style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;font-family:inherit;min-height:60px;resize:vertical;" placeholder="{{ __('leads.description_placeholder') }}"></textarea>
        </div>
        <div style="display:flex;gap:10px;margin-bottom:14px;">
            <div style="flex:1;">
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('leads.type_label') }} *</label>
                <select id="ntType" style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;font-family:inherit;">
                    <option value="call">{{ __('leads.type_call') }}</option>
                    <option value="email">{{ __('leads.type_email_activity') }}</option>
                    <option value="task">{{ __('leads.type_task') }}</option>
                    <option value="visit">{{ __('leads.type_visit') }}</option>
                    <option value="whatsapp">{{ __('leads.type_whatsapp') }}</option>
                    <option value="meeting">{{ __('leads.type_meeting') }}</option>
                </select>
            </div>
            <div style="flex:1;">
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('leads.priority_label') }}</label>
                <select id="ntPriority" style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;font-family:inherit;">
                    <option value="low">{{ __('leads.priority_low') }}</option>
                    <option value="medium" selected>{{ __('leads.priority_medium') }}</option>
                    <option value="high">{{ __('leads.priority_high') }}</option>
                </select>
            </div>
        </div>
        <div style="display:flex;gap:10px;margin-bottom:14px;">
            <div style="flex:1;">
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('leads.date_label') }} *</label>
                <input id="ntDueDate" type="date" style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;font-family:inherit;">
            </div>
            <div style="flex:1;">
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('leads.time_label') }}</label>
                <input id="ntDueTime" type="time" style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;font-family:inherit;">
            </div>
        </div>
        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('leads.responsible_label') }}</label>
            <select id="ntAssignedTo" style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;font-family:inherit;">
                <option value="">{{ __('leads.sidebar_not_assigned') }}</option>
                @foreach($users as $u)
                <option value="{{ $u->id }}" {{ $u->id === auth()->id() ? 'selected' : '' }}>{{ $u->name }}</option>
                @endforeach
            </select>
        </div>
        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('leads.observations_label') }}</label>
            <textarea id="ntNotes" style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;font-family:inherit;min-height:50px;resize:vertical;" placeholder="{{ __('leads.observations_placeholder') }}"></textarea>
        </div>
    </div>
    <div style="padding:14px 22px;border-top:1px solid #f0f2f7;">
        <button onclick="saveNewTask()" style="width:100%;background:#0085f3;color:#fff;border:none;border-radius:100px;padding:10px;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;">
            <i class="bi bi-check2"></i> {{ __('leads.create_activity_btn') }}
        </button>
    </div>
</div>

{{-- ── Modal: Agendar Mensagem ── --}}
<div id="schedModal" class="sched-overlay" style="display:none;" onclick="if(event.target===this)closeScheduleModal()">
    <div class="sched-modal">
        <div class="sched-modal-header">
            <span class="sched-modal-title"><i class="bi bi-clock" style="margin-right:6px;"></i>{{ __('leads.schedule_message') }}</span>
            <button class="sched-modal-close" onclick="closeScheduleModal()"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="sched-modal-body">
            @if($quickMessages->isNotEmpty())
            <div class="sched-form-group">
                <label class="sched-form-label">{{ __('leads.quick_message_template') }}</label>
                <select id="schedQuickMsg" class="sched-form-select" onchange="applyQuickMessage(this.value)">
                    <option value="">{{ __('leads.select_template') }}</option>
                    @foreach($quickMessages as $qm)
                    <option value="{{ $qm->id }}" data-body="{{ e($qm->body) }}">{{ $qm->title }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="sched-form-group">
                <label class="sched-form-label">{{ __('leads.message_type') }}</label>
                <div class="sched-type-radios">
                    <label class="sched-type-radio">
                        <input type="radio" name="schedType" value="text" checked onchange="onSchedTypeChange()">
                        <i class="bi bi-chat-text"></i> {{ __('leads.type_text') }}
                    </label>
                    <label class="sched-type-radio">
                        <input type="radio" name="schedType" value="image" onchange="onSchedTypeChange()">
                        <i class="bi bi-image"></i> {{ __('leads.type_image') }}
                    </label>
                    <label class="sched-type-radio">
                        <input type="radio" name="schedType" value="document" onchange="onSchedTypeChange()">
                        <i class="bi bi-file-earmark"></i> {{ __('leads.type_document') }}
                    </label>
                </div>
            </div>
            <div class="sched-form-group" id="schedBodyGroup">
                <label class="sched-form-label">{{ __('leads.message_label') }}</label>
                <textarea id="schedBody" class="sched-form-textarea" placeholder="{{ __('leads.message_placeholder') }}"></textarea>
            </div>
            <div class="sched-form-group" id="schedFileGroup" style="display:none;">
                <label class="sched-form-label">{{ __('leads.file_label') }}</label>
                <input type="file" id="schedFile" class="sched-form-input" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt">
            </div>
            <div class="sched-form-group" id="schedCaptionGroup" style="display:none;">
                <label class="sched-form-label">{{ __('leads.caption_label') }}</label>
                <input type="text" id="schedCaption" class="sched-form-input" placeholder="{{ __('leads.caption_placeholder') }}">
            </div>
            <div class="sched-form-group">
                <label class="sched-form-label">{{ __('leads.send_datetime') }}</label>
                <input type="datetime-local" id="schedSendAt" class="sched-form-input">
            </div>
        </div>
        <div class="sched-modal-footer">
            <button class="sched-btn-cancel" onclick="closeScheduleModal()">{{ __('leads.cancel') }}</button>
            <button class="sched-btn-submit" id="schedSubmitBtn" onclick="submitSchedule()">
                <i class="bi bi-clock"></i> {{ __('leads.schedule_send') }}
            </button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
const LLANG = @json(__('leads'));

function copyToClipboard(text, btn) {
    navigator.clipboard.writeText(text).then(function() {
        btn.classList.add('copied');
        btn.innerHTML = '<i class="bi bi-check-lg"></i>';
        setTimeout(function() {
            btn.classList.remove('copied');
            btn.innerHTML = '<i class="bi bi-clipboard"></i>';
        }, 1500);
    });
}

// Constantes que o drawer precisa (PIPELINES_DATA, CF_DEFS, LEAD_TAGS, LEAD_NOTE_STORE, LEAD_NOTE_DEL são definidas pelo drawer)
const LEAD_SHOW  = '{{ route('leads.show',    ['lead' => '__ID__']) }}';
const LEAD_STORE = '{{ route('leads.store') }}';
const LEAD_UPD   = '{{ route('leads.update',  ['lead' => '__ID__']) }}';
const LEAD_DEL   = '{{ route('leads.destroy', ['lead' => '__ID__']) }}';

// Após salvar no drawer, recarregar a página
window.onLeadSaved = function(lead, isNew) {
    if (!isNew) {
        location.reload();
    } else {
        window.location.href = '{{ route('leads.index') }}';
    }
};

window.onLeadDeleted = function() {
    window.location.href = '{{ route('leads.index') }}';
};

// ── Scheduled Messages ────────────────────────────────────────────────────
const SCHED_STORE   = '{{ route('leads.scheduled.store',   ['lead' => $lead->id]) }}';
const SCHED_INDEX   = '{{ route('leads.scheduled.index',   ['lead' => $lead->id]) }}';
const SCHED_DESTROY = '{{ route('leads.scheduled.destroy', ['lead' => $lead->id, 'scheduled' => '__ID__']) }}';

function openScheduleModal() {
    const now = new Date();
    now.setMinutes(now.getMinutes() + 2);
    const pad = n => String(n).padStart(2, '0');
    const min = `${now.getFullYear()}-${pad(now.getMonth()+1)}-${pad(now.getDate())}T${pad(now.getHours())}:${pad(now.getMinutes())}`;
    document.getElementById('schedSendAt').min = min;
    document.getElementById('schedSendAt').value = '';
    document.getElementById('schedModal').style.display = 'flex';
}

function closeScheduleModal() {
    document.getElementById('schedModal').style.display = 'none';
    document.getElementById('schedBody').value = '';
    const fi = document.getElementById('schedFile');
    if (fi) fi.value = '';
    document.getElementById('schedCaption').value = '';
    const qm = document.getElementById('schedQuickMsg');
    if (qm) qm.value = '';
    document.querySelector('input[name="schedType"][value="text"]').checked = true;
    onSchedTypeChange();
}

function onSchedTypeChange() {
    const type = document.querySelector('input[name="schedType"]:checked')?.value || 'text';
    document.getElementById('schedBodyGroup').style.display    = type === 'text' ? '' : 'none';
    document.getElementById('schedFileGroup').style.display    = type !== 'text' ? '' : 'none';
    document.getElementById('schedCaptionGroup').style.display = type !== 'text' ? '' : 'none';
}

function applyQuickMessage(id) {
    const sel = document.getElementById('schedQuickMsg');
    const opt = sel ? sel.options[sel.selectedIndex] : null;
    if (id && opt) {
        document.getElementById('schedBody').value = opt.dataset.body || '';
        document.querySelector('input[name="schedType"][value="text"]').checked = true;
        onSchedTypeChange();
    }
}

async function submitSchedule() {
    const type    = document.querySelector('input[name="schedType"]:checked')?.value || 'text';
    const body    = document.getElementById('schedBody').value.trim();
    const sendAt  = document.getElementById('schedSendAt').value;
    const fileEl  = document.getElementById('schedFile');
    const caption = document.getElementById('schedCaption').value.trim();

    if (!sendAt) { alert(LLANG.inform_datetime); return; }
    if (type === 'text' && !body) { alert(LLANG.type_the_message); return; }
    if (type !== 'text' && (!fileEl || !fileEl.files.length)) { alert(LLANG.select_a_file); return; }

    const fd = new FormData();
    fd.append('type', type);
    fd.append('send_at', sendAt);
    if (type === 'text') {
        fd.append('body', body);
    } else {
        fd.append('file', fileEl.files[0]);
        if (caption) fd.append('body', caption);
    }

    const btn = document.getElementById('schedSubmitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> ' + LLANG.scheduling;

    try {
        const res  = await fetch(SCHED_STORE, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: fd,
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.error || data.message || LLANG.error_scheduling);
        closeScheduleModal();
        await reloadScheduledList();
        if (typeof toastr !== 'undefined') toastr.success(LLANG.scheduled_success);
    } catch (e) {
        alert(LLANG.error_prefix + e.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-clock"></i> ' + LLANG.schedule_send;
    }
}

async function cancelScheduled(id) {
    if (!confirm(LLANG.confirm_cancel_schedule)) return;
    const url = SCHED_DESTROY.replace('__ID__', id);
    try {
        const res  = await fetch(url, {
            method: 'DELETE',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.error || LLANG.error_cancel);
        document.getElementById('sm-' + id)?.remove();
        if (!document.querySelector('#scheduledList .lp-sm-card')) {
            document.getElementById('scheduledList').innerHTML =
                `<div id="scheduledEmpty" style="text-align:center;padding:40px 20px;color:#9ca3af;">
                    <i class="bi bi-clock" style="font-size:32px;opacity:.3;display:block;margin-bottom:8px;"></i>
                    ${LLANG.no_scheduled_empty}
                </div>`;
        }
    } catch (e) {
        alert(LLANG.error_prefix + e.message);
    }
}

async function reloadScheduledList() {
    const res  = await fetch(SCHED_INDEX, { headers: { 'Accept': 'application/json' } });
    const data = await res.json();
    const items = data.data || [];
    const list  = document.getElementById('scheduledList');
    const statusLabels = { pending: LLANG.status_pending, sent: LLANG.status_sent, failed: LLANG.status_failed, cancelled: LLANG.status_cancelled };
    const statusStyles = {
        pending:   'background:#fff7ed;color:#f59e0b;',
        sent:      'background:#f0fdf4;color:#10b981;',
        failed:    'background:#fef2f2;color:#ef4444;',
        cancelled: 'background:#f9fafb;color:#6b7280;',
    };
    const typeIcons = { text: 'bi-chat-text', image: 'bi-image', document: 'bi-file-earmark' };

    if (!items.length) {
        list.innerHTML = `<div id="scheduledEmpty" style="text-align:center;padding:40px 20px;color:#9ca3af;">
            <i class="bi bi-clock" style="font-size:32px;opacity:.3;display:block;margin-bottom:8px;"></i>
            ${LLANG.no_scheduled_empty}</div>`;
    } else {
        list.innerHTML = items.map(s => `
            <div class="lp-sm-card" id="sm-${s.id}">
                <div class="lp-sm-icon"><i class="bi ${typeIcons[s.type] || 'bi-chat'}"></i></div>
                <div class="lp-sm-body">
                    <div class="lp-sm-preview">${escapeHtml(s.body || s.media_filename || LLANG.no_content)}</div>
                    <div class="lp-sm-meta">
                        <span class="lp-sm-badge" style="${statusStyles[s.status] || ''}">${statusLabels[s.status] || s.status}</span>
                        <span>${escapeHtml(s.send_at_human || '')}</span>
                        ${s.error ? `<span style="color:#ef4444;" title="${escapeHtml(s.error)}"><i class="bi bi-exclamation-circle"></i> ${escapeHtml(s.error.substring(0, 50))}</span>` : ''}
                    </div>
                </div>
                ${s.status === 'pending' ? `<button class="lp-sm-cancel" onclick="cancelScheduled(${s.id})" title="${LLANG.cancel}"><i class="bi bi-x-lg"></i></button>` : ''}
            </div>`).join('');
    }

    // Atualiza badge da aba
    const pending = items.filter(s => s.status === 'pending').length;
    const tabBtn  = document.querySelector('[data-tab="scheduled"]');
    const badge   = tabBtn?.querySelector('span');
    if (pending > 0) {
        const html = `<span style="background:#fff7ed;color:#f59e0b;font-size:10px;font-weight:700;padding:1px 6px;border-radius:99px;">${pending}</span>`;
        if (badge) badge.outerHTML = html;
        else if (tabBtn) tabBtn.insertAdjacentHTML('beforeend', html);
    } else if (badge) {
        badge.remove();
    }
}

// ── Tabs ──────────────────────────────────────────────────────────────────
document.querySelectorAll('.lp-tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const tab = this.dataset.tab;
        document.querySelectorAll('.lp-tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.lp-tab-panel').forEach(p => p.classList.remove('active'));
        this.classList.add('active');
        const panel = document.getElementById('tab-' + tab);
        if (panel) panel.classList.add('active');
    });
});

// ── Notas ─────────────────────────────────────────────────────────────────
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content;

async function addPageNote() {
    const textarea = document.getElementById('newNoteBody');
    const body     = textarea.value.trim();
    if (!body) return;

    const btn = document.getElementById('btnAddNote');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> ' + LLANG.saving;

    try {
        const res = await fetch(LEAD_NOTE_STORE.replace('__ID__', {{ $lead->id }}), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF,
            },
            body: JSON.stringify({ body }),
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.message || LLANG.error_add_note);

        const note = data.note;
        // Remove empty state if present
        document.getElementById('notesEmpty')?.remove();

        // Prepend card
        const container = document.getElementById('notesContainer');
        const card = document.createElement('div');
        card.className = 'lp-note-card';
        card.id = 'note-' + note.id;
        card.innerHTML = `
            <div class="lp-note-header">
                <div class="lp-note-avatar">${escapeHtml((note.author || '?').charAt(0).toUpperCase())}</div>
                <div class="lp-note-meta">
                    <div class="lp-note-author">${escapeHtml(note.author || LLANG.me)}</div>
                    <div class="lp-note-date">${LLANG.just_now}</div>
                </div>
                <button class="lp-note-del" onclick="deletePageNote(${note.id})" title="${LLANG.delete_note_title}">
                    <i class="bi bi-trash3"></i>
                </button>
            </div>
            <div class="lp-note-body">${escapeHtml(note.body)}</div>
        `;
        container.prepend(card);
        textarea.value = '';

        // Update tab counter
        const tabBtn = document.querySelector('[data-tab="notes"]');
        const counterEl = tabBtn?.querySelector('span');
        if (counterEl) {
            const count = document.querySelectorAll('.lp-note-card').length;
            counterEl.textContent = count;
        } else if (tabBtn) {
            const span = document.createElement('span');
            span.style.cssText = 'background:#eff6ff;color:#3b82f6;font-size:10px;font-weight:700;padding:1px 6px;border-radius:99px;';
            span.textContent = document.querySelectorAll('.lp-note-card').length;
            tabBtn.appendChild(span);
        }
    } catch(e) {
        alert(LLANG.error_saving_note + e.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-plus-lg"></i> ' + LLANG.add_note;
    }
}

async function deletePageNote(noteId) {
    if (!confirm(LLANG.confirm_delete_note)) return;
    const url = LEAD_NOTE_DEL
        .replace('__LEAD__', {{ $lead->id }})
        .replace('__NOTE__', noteId);
    try {
        const res = await fetch(url, {
            method: 'DELETE',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
        });
        if (!res.ok) throw new Error(LLANG.error_deleting);
        document.getElementById('note-' + noteId)?.remove();

        if (document.querySelectorAll('.lp-note-card').length === 0) {
            const container = document.getElementById('notesContainer');
            container.innerHTML = `<div id="notesEmpty" style="text-align:center;padding:30px 20px;color:#9ca3af;">
                <i class="bi bi-journal-x" style="font-size:32px;opacity:.3;display:block;margin-bottom:8px;"></i>
                ${LLANG.no_notes}
            </div>`;
        }
    } catch(e) {
        alert(LLANG.error_prefix + e.message);
    }
}

// ── Anexos ─────────────────────────────────────────────────────────────────
async function uploadAttachment(file) {
    if (!file) return;

    const list = document.getElementById('attachmentsList');
    const emptyEl = document.getElementById('attachEmpty');
    if (emptyEl) emptyEl.remove();

    const tempId = 'att-temp-' + Date.now();
    list.insertAdjacentHTML('afterbegin', `
        <div class="lp-attach-uploading" id="${tempId}">
            <i class="bi bi-arrow-repeat spin"></i> ${LLANG.uploading_file.replace(':name', file.name)}
        </div>
    `);

    const fd = new FormData();
    fd.append('file', file);

    try {
        const res = await fetch('{{ route("leads.attachments.store", $lead->id) }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content },
            body: fd,
        });
        const data = await res.json();
        document.getElementById(tempId)?.remove();

        if (data.success) {
            const a = data.attachment;
            list.insertAdjacentHTML('afterbegin', buildAttachHtml(a));
            toastr.success(LLANG.attach_sent);
        } else {
            toastr.error(data.message || LLANG.error_sending_attach);
        }
    } catch (e) {
        document.getElementById(tempId)?.remove();
        toastr.error(LLANG.error_conn_attach);
    }

    document.getElementById('attachFileInput').value = '';
}

async function deleteAttachment(id) {
    if (!confirm(LLANG.confirm_delete_attachment)) return;
    try {
        const base = '{{ route("leads.attachments.destroy", [$lead->id, "__ID__"]) }}'.replace('__ID__', id);
        const res = await fetch(base, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content,
                'Content-Type': 'application/json',
            },
        });
        const data = await res.json();
        if (data.success) {
            document.getElementById('attach-' + id)?.remove();
            toastr.success(LLANG.attach_deleted);
            if (!document.querySelector('#attachmentsList .lp-attach-item')) {
                document.getElementById('attachmentsList').innerHTML = `
                    <div id="attachEmpty" style="text-align:center;padding:40px 20px;color:#9ca3af;">
                        <i class="bi bi-paperclip" style="font-size:32px;opacity:.3;display:block;margin-bottom:8px;"></i>
                        ${LLANG.no_attachments_added}
                    </div>`;
            }
        } else {
            toastr.error(data.message || LLANG.error_deleting);
        }
    } catch (e) {
        toastr.error(LLANG.error_conn);
    }
}

function buildAttachHtml(a) {
    let icon = '<i class="bi bi-file-earmark" style="color:#6b7280;"></i>';
    if (a.mime_type?.startsWith('image/')) icon = '<i class="bi bi-file-earmark-image" style="color:#8b5cf6;"></i>';
    else if (a.mime_type === 'application/pdf') icon = '<i class="bi bi-file-earmark-pdf" style="color:#ef4444;"></i>';
    else if (a.mime_type?.includes('spreadsheet') || a.mime_type?.includes('excel')) icon = '<i class="bi bi-file-earmark-spreadsheet" style="color:#10b981;"></i>';
    else if (a.mime_type?.includes('word') || a.mime_type?.includes('document')) icon = '<i class="bi bi-file-earmark-word" style="color:#0085f3;"></i>';

    const sizeKb = a.file_size ? Math.round(a.file_size / 1024) : 0;

    return `<div class="lp-attach-item" id="attach-${a.id}">
        <div class="lp-attach-icon">${icon}</div>
        <div class="lp-attach-info">
            <div class="lp-attach-name">${a.original_name}</div>
            <div class="lp-attach-meta">${a.uploaded_by || LLANG.you} · ${a.created_at} · ${sizeKb} KB</div>
        </div>
        <div class="lp-attach-actions">
            <a href="${a.url}" target="_blank" class="lp-attach-btn" title="${LLANG.open_title}"><i class="bi bi-download"></i></a>
            <button class="lp-attach-btn lp-attach-del" onclick="deleteAttachment(${a.id})" title="${LLANG.delete_title}"><i class="bi bi-trash3"></i></button>
        </div>
    </div>`;
}

// ── Tarefas do Lead ──────────────────────────────────────────────────
// ── Move lead to stage (pipeline click) ────────────────────────────────
async function moveToStage(stageId, stageName) {
    try {
        const res = await fetch('{{ route("crm.lead.stage", $lead->id) }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ stage_id: stageId, pipeline_id: {{ $lead->pipeline_id }} }),
        });
        const data = await res.json();
        if (data.success !== false && res.ok) {
            toastr.success(@json(__('leads.moved_to', ['stage' => ''])) + stageName);
            setTimeout(() => location.reload(), 800);
        } else {
            toastr.error(data.message || data.error || @json(__('leads.error_move_stage')));
        }
    } catch { toastr.error(@json(__('leads.error_conn'))); }
    document.getElementById('outcomeMenu')?.style && (document.getElementById('outcomeMenu').style.display = 'none');
}

// ── Tags from hero ─────────────────────────────────────────────────────
async function addExistingTag(tagName) {
    document.getElementById('tagDropdown').style.display = 'none';
    const currentTags = @json($lead->tags ?? []);
    if (currentTags.includes(tagName)) return;
    currentTags.push(tagName);
    await updateLeadTags(currentTags);
}
async function removeTag(tag) {
    const currentTags = @json($lead->tags ?? []).filter(t => t !== tag);
    await updateLeadTags(currentTags);
}
async function updateLeadTags(tags) {
    try {
        const res = await fetch('{{ route("leads.update", $lead->id) }}', {
            method: 'PUT',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ name: {!! json_encode($lead->name) !!}, tags: tags }),
        });
        if (res.ok) { location.reload(); }
        else { toastr.error('Erro ao atualizar tags.'); }
    } catch { toastr.error('Erro de conexão.'); }
}

// Close dropdowns on outside click
document.addEventListener('click', e => {
    const outcomeWrap = document.getElementById('outcomeWrap');
    const outcomeMenu = document.getElementById('outcomeMenu');
    if (outcomeWrap && outcomeMenu && !outcomeWrap.contains(e.target)) outcomeMenu.style.display = 'none';

    const tagDd = document.getElementById('tagDropdown');
    if (tagDd && !e.target.closest('[title="Adicionar tag"]') && !tagDd.contains(e.target)) tagDd.style.display = 'none';
});

function toggleLeadTask(id) {
    $.ajax({ url: '/crm/public/tarefas/' + id + '/toggle', method: 'PATCH', headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' } })
        .done(function() { location.reload(); });
}

// ── Activities split view ──────────────────────────────────────────────
@php
    $taskDataMap = [];
    foreach ($tasks as $t) {
        $taskDataMap[$t->id] = [
            'id' => $t->id,
            'subject' => $t->subject,
            'description' => $t->description,
            'type' => $t->type,
            'status' => $t->status,
            'priority' => $t->priority,
            'due_date' => $t->due_date?->format('d/m/Y'),
            'due_time' => $t->due_time ? substr($t->due_time, 0, 5) : null,
            'completed_at' => $t->completed_at?->format('d/m/Y H:i'),
            'assigned_to_name' => $t->assignedTo?->name,
            'notes' => $t->notes,
        ];
    }
@endphp
const _taskData = {!! json_encode($taskDataMap) !!};

const _taskTypeLabels = { call: @json(__('leads.type_call')), email: @json(__('leads.type_email_activity')), task: @json(__('leads.type_task')), visit: @json(__('leads.type_visit')), whatsapp: @json(__('leads.type_whatsapp')), meeting: @json(__('leads.type_meeting')) };
const _taskPriorityLabels = { low: @json(__('leads.priority_low')), medium: @json(__('leads.priority_medium')), high: @json(__('leads.priority_high')) };
const _taskPriorityColors = { low: '#10b981', medium: '#f59e0b', high: '#ef4444' };

function showTaskDetail(taskId) {
    const t = _taskData[taskId];
    if (!t) return;

    // Highlight selected row
    document.querySelectorAll('.act-task-row').forEach(r => r.style.background = '');
    document.querySelector(`.act-task-row[data-task-id="${taskId}"]`)?.querySelectorAll('div')[0]?.closest('.act-task-row')?.querySelector('div')?.style && (
        document.querySelector(`.act-task-row[data-task-id="${taskId}"] > div`).style.background = '#f0f4ff'
    );

    const panel = document.getElementById('actTaskDetail');
    const priColor = _taskPriorityColors[t.priority] || '#6b7280';
    panel.innerHTML = `
        <div style="padding:20px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                <h4 style="font-size:16px;font-weight:700;color:#1a1d23;margin:0;">${escapeHtml(t.subject)}</h4>
                <i class="bi bi-question-circle" style="color:#9ca3af;font-size:14px;cursor:help;" title="Clique na tarefa para ver detalhes"></i>
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:16px;">
                <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:99px;font-size:11px;font-weight:600;background:#eff6ff;color:#0085f3;">
                    ${_taskTypeLabels[t.type] || t.type}
                </span>
                <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:99px;font-size:11px;font-weight:600;background:${priColor}15;color:${priColor};">
                    ${_taskPriorityLabels[t.priority] || t.priority}
                </span>
                ${t.status === 'completed' ? '<span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:99px;font-size:11px;font-weight:600;background:#f0fdf4;color:#10b981;"><i class="bi bi-check-circle-fill"></i> ' + @json(__('leads.completed_label')) + '</span>' : ''}
            </div>
            ${t.description ? `<div style="margin-bottom:16px;padding:12px;background:#f8fafc;border-radius:8px;font-size:13px;color:#374151;line-height:1.6;">${escapeHtml(t.description)}</div>` : ''}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:13px;">
                <div><span style="color:#9ca3af;font-size:11px;display:block;margin-bottom:2px;">${@json(__('leads.date_label'))}</span><strong>${t.due_date || '—'}${t.due_time ? ' ' + t.due_time : ''}</strong></div>
                <div><span style="color:#9ca3af;font-size:11px;display:block;margin-bottom:2px;">${@json(__('leads.responsible_label'))}</span><strong>${t.assigned_to_name || @json(__('leads.sidebar_not_assigned'))}</strong></div>
                ${t.completed_at ? `<div><span style="color:#9ca3af;font-size:11px;display:block;margin-bottom:2px;">${@json(__('leads.completed_at_label'))}</span><strong>${t.completed_at}</strong></div>` : ''}
                ${t.notes ? `<div style="grid-column:span 2;"><span style="color:#9ca3af;font-size:11px;display:block;margin-bottom:2px;">${@json(__('leads.observations_label'))}</span><div style="color:#374151;">${escapeHtml(t.notes)}</div></div>` : ''}
            </div>
        </div>
    `;
}

function toggleTaskComplete(taskId, el) {
    fetch('{{ url("/tarefas") }}/' + taskId + '/toggle', {
        method: 'PATCH',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
    }).then(() => location.reload());
}

function escapeHtml(s) {
    if (!s) return '';
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function openLeadTaskModal() {
    window.location.href = @json(route('tasks.index')) + '?open_modal=1&lead_id={{ $lead->id }}&lead_name=' + encodeURIComponent(@json($lead->name));
}

function openLeadTaskDrawer() {
    document.getElementById('ntSubject').value = '';
    document.getElementById('ntDescription').value = '';
    document.getElementById('ntDueDate').value = new Date(Date.now() + 86400000).toISOString().slice(0, 10);
    document.getElementById('ntDueTime').value = '';
    document.getElementById('ntNotes').value = '';
    document.getElementById('newTaskOverlay').style.display = 'block';
    setTimeout(() => { document.getElementById('newTaskDrawer').style.right = '0'; }, 10);
}

function closeNewTaskDrawer() {
    document.getElementById('newTaskDrawer').style.right = '-440px';
    setTimeout(() => { document.getElementById('newTaskOverlay').style.display = 'none'; }, 300);
}

async function saveNewTask() {
    const subject = document.getElementById('ntSubject').value.trim();
    const dueDate = document.getElementById('ntDueDate').value;
    if (!subject) { toastr.warning(@json(__('leads.subject_required'))); return; }
    if (!dueDate) { toastr.warning(@json(__('leads.date_required'))); return; }

    try {
        const res = await fetch('{{ route("tasks.store") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({
                subject: subject,
                description: document.getElementById('ntDescription').value,
                type: document.getElementById('ntType').value,
                priority: document.getElementById('ntPriority').value,
                due_date: dueDate,
                due_time: document.getElementById('ntDueTime').value || null,
                assigned_to: document.getElementById('ntAssignedTo').value || null,
                notes: document.getElementById('ntNotes').value || null,
                lead_id: {{ $lead->id }},
            }),
        });
        const data = await res.json();
        if (data.success || res.ok) {
            toastr.success(@json(__('leads.activity_created')));
            closeNewTaskDrawer();
            setTimeout(() => location.reload(), 800);
        } else {
            toastr.error(data.message || @json(__('leads.error_create_activity')));
        }
    } catch { toastr.error(@json(__('leads.error_conn'))); }
}

// ── Contacts ─────────────────────────────────────────────────────────
const CONTACTS_URL = '{{ route("leads.contacts.index", $lead->id) }}';
let editingContactId = null;

function loadContacts() {
    fetch(CONTACTS_URL, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } })
        .then(r => r.json())
        .then(contacts => {
            const el = document.getElementById('contactsList');
            if (!contacts.length) {
                el.innerHTML = `<div style="text-align:center;padding:8px 0;color:#d1d5db;font-size:12px;">${LLANG.no_contacts}</div>`;
                return;
            }
            el.innerHTML = contacts.map(c => {
                let html = `<div class="lp-contact-card" data-contact-id="${c.id}">`;
                html += `<div><span class="lp-contact-name">${escapeHtml(c.name)}</span>`;
                if (c.role) html += `<span class="lp-contact-role">${escapeHtml(c.role)}</span>`;
                html += `</div>`;
                if (c.phone) {
                    html += `<div class="lp-contact-detail">
                        <i class="bi bi-telephone"></i> ${escapeHtml(c.phone)}
                        <button class="lp-copy-btn" onclick="copyToClipboard('${escapeHtml(c.phone)}', this)" title="Copy" style="width:20px;height:20px;font-size:10px;">
                            <i class="bi bi-clipboard"></i>
                        </button>
                    </div>`;
                }
                if (c.email) {
                    html += `<div class="lp-contact-detail">
                        <i class="bi bi-envelope"></i> ${escapeHtml(c.email)}
                        <button class="lp-copy-btn" onclick="copyToClipboard('${escapeHtml(c.email)}', this)" title="Copy" style="width:20px;height:20px;font-size:10px;">
                            <i class="bi bi-clipboard"></i>
                        </button>
                    </div>`;
                }
                html += `<div class="lp-contact-actions">
                    <button onclick="editContact(${c.id})" title="${LLANG.edit || 'Edit'}"><i class="bi bi-pencil"></i></button>
                    <button class="lp-contact-del" onclick="deleteContact(${c.id})" title="${LLANG.delete_contact_title}"><i class="bi bi-trash3"></i></button>
                </div>`;
                html += `</div>`;
                return html;
            }).join('');
        })
        .catch(() => {});
}

function toggleContactForm(show) {
    const form = document.getElementById('addContactForm');
    if (typeof show === 'undefined') show = form.style.display === 'none';
    if (show) {
        form.style.display = 'flex';
    } else {
        form.style.display = 'none';
        clearContactForm();
    }
}

function clearContactForm() {
    document.getElementById('editContactId').value = '';
    document.getElementById('contactName').value = '';
    document.getElementById('contactRole').value = '';
    document.getElementById('contactPhone').value = '';
    document.getElementById('contactEmail').value = '';
    editingContactId = null;
}

function saveContact() {
    const name  = document.getElementById('contactName').value.trim();
    const role  = document.getElementById('contactRole').value.trim();
    const phone = document.getElementById('contactPhone').value.trim();
    const email = document.getElementById('contactEmail').value.trim();

    if (!name) { document.getElementById('contactName').focus(); return; }

    const payload = { name, role, phone, email };
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    if (editingContactId) {
        fetch(CONTACTS_URL + '/' + editingContactId, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(data => {
            if (typeof toastr !== 'undefined') toastr.success(LLANG.contact_updated);
            toggleContactForm(false);
            loadContacts();
        })
        .catch(() => { if (typeof toastr !== 'undefined') toastr.error(LLANG.error_prefix + 'update'); });
    } else {
        fetch(CONTACTS_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(data => {
            if (typeof toastr !== 'undefined') toastr.success(LLANG.contact_added);
            toggleContactForm(false);
            loadContacts();
        })
        .catch(() => { if (typeof toastr !== 'undefined') toastr.error(LLANG.error_prefix + 'create'); });
    }
}

function editContact(id) {
    const card = document.querySelector(`.lp-contact-card[data-contact-id="${id}"]`);
    if (!card) return;

    fetch(CONTACTS_URL, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } })
        .then(r => r.json())
        .then(contacts => {
            const c = contacts.find(x => x.id === id);
            if (!c) return;
            editingContactId = id;
            document.getElementById('editContactId').value = id;
            document.getElementById('contactName').value = c.name || '';
            document.getElementById('contactRole').value = c.role || '';
            document.getElementById('contactPhone').value = c.phone || '';
            document.getElementById('contactEmail').value = c.email || '';
            toggleContactForm(true);
        });
}

function deleteContact(id) {
    if (!confirm(LLANG.delete_contact_title)) return;
    fetch(CONTACTS_URL + '/' + id, {
        method: 'DELETE',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
    })
    .then(r => r.json())
    .then(() => {
        if (typeof toastr !== 'undefined') toastr.success(LLANG.contact_deleted);
        loadContacts();
    })
    .catch(() => { if (typeof toastr !== 'undefined') toastr.error(LLANG.error_prefix + 'delete'); });
}

// Load contacts on page ready
document.addEventListener('DOMContentLoaded', loadContacts);
</script>
@endpush

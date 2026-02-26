@extends('tenant.layouts.app')
@php
$title   = $lead->name;
$pageIcon = 'person-badge';
@endphp

@section('topbar_actions')
<div class="topbar-actions" style="gap:8px;">
    <a href="{{ route('leads.index') }}" class="btn-secondary-sm" style="display:flex;align-items:center;gap:5px;text-decoration:none;">
        <i class="bi bi-arrow-left"></i> Contatos
    </a>
    <button class="btn-primary-sm" id="btnEditLead" onclick="openLeadDrawer({{ $lead->id }})">
        <i class="bi bi-pencil"></i> Editar Lead
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
    box-shadow: 0 0 0 4px rgba(37,211,102,.2);
}
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
.lp-step-line.filled { background: #25d366; }

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
.lp-bubble.note-bubble { background: #fef3c7; color: #78350f; font-style: italic; }
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
    position: sticky;
    top: 80px;
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
</style>
@endpush

@section('content')
<div class="page-container">

{{-- ── Hero ── --}}
<div class="lp-hero">
    <div class="lp-avatar">{{ strtoupper(substr($lead->name, 0, 1)) }}</div>
    <div class="lp-hero-info">
        <h1 class="lp-hero-name">{{ $lead->name }}</h1>
        <div class="lp-hero-meta">
            @if($lead->stage)
            <span class="stage-badge" style="background:{{ $lead->stage->color }}22;color:{{ $lead->stage->color }};">
                <span class="dot" style="background:{{ $lead->stage->color }};"></span>
                {{ $lead->stage->name }}
            </span>
            @endif
            @if($lead->source)
            <span class="source-pill">{{ $lead->source }}</span>
            @endif
            @if($lead->pipeline)
            <span><i class="bi bi-diagram-3" style="margin-right:3px;"></i>{{ $lead->pipeline->name }}</span>
            @endif
            <span style="color:#d1d5db;">|</span>
            <span>Criado {{ $lead->created_at->diffForHumans() }}</span>
        </div>
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
            $dotBg     = ($isPast || $isCurrent) ? '#25d366' : '#e5e7eb';
            $dotColor  = ($isPast || $isCurrent) ? '#fff' : '#9ca3af';
        @endphp
        <div class="lp-step {{ $cls }}">
            <div class="lp-step-dot" style="background:{{ $dotBg }};border-color:{{ $dotBg }};color:{{ $dotColor }};">
                @if($isPast)
                    <i class="bi bi-check-lg"></i>
                @elseif($isCurrent)
                    @if($stage->is_won)  <i class="bi bi-trophy-fill"></i>
                    @elseif($stage->is_lost) <i class="bi bi-x-lg"></i>
                    @else <i class="bi bi-record-fill" style="font-size:8px;"></i>
                    @endif
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

{{-- ── Main grid ── --}}
<div class="lp-grid">

    {{-- ── Left: Tabs ── --}}
    <div class="lp-card">
        <div class="lp-tabs-nav">
            <button class="lp-tab-btn active" data-tab="notes">
                <i class="bi bi-journal-text"></i> Notas
                @if($lead->leadNotes->count() > 0)
                <span style="background:#eff6ff;color:#3b82f6;font-size:10px;font-weight:700;padding:1px 6px;border-radius:99px;">{{ $lead->leadNotes->count() }}</span>
                @endif
            </button>
            <button class="lp-tab-btn" data-tab="history">
                <i class="bi bi-clock-history"></i> Histórico
                @if($lead->events->count() > 0)
                <span style="background:#f0fdf4;color:#10b981;font-size:10px;font-weight:700;padding:1px 6px;border-radius:99px;">{{ $lead->events->count() }}</span>
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
        </div>

        {{-- ── Tab: Notas ── --}}
        <div class="lp-tab-panel active" id="tab-notes">
            <div id="notesContainer">
                @forelse($lead->leadNotes as $note)
                <div class="lp-note-card" id="note-{{ $note->id }}">
                    <div class="lp-note-header">
                        <div class="lp-note-avatar">{{ strtoupper(substr($note->author?->name ?? '?', 0, 1)) }}</div>
                        <div class="lp-note-meta">
                            <div class="lp-note-author">{{ $note->author?->name ?? 'Desconhecido' }}</div>
                            <div class="lp-note-date">{{ $note->created_at->diffForHumans() }}</div>
                        </div>
                        @if($note->created_by === auth()->id())
                        <button class="lp-note-del" onclick="deletePageNote({{ $note->id }})" title="Excluir nota">
                            <i class="bi bi-trash3"></i>
                        </button>
                        @endif
                    </div>
                    <div class="lp-note-body">{{ $note->body }}</div>
                </div>
                @empty
                <div id="notesEmpty" style="text-align:center;padding:30px 20px;color:#9ca3af;">
                    <i class="bi bi-journal-x" style="font-size:32px;opacity:.3;display:block;margin-bottom:8px;"></i>
                    Nenhuma nota ainda.
                </div>
                @endforelse
            </div>

            {{-- Form nova nota --}}
            <div style="margin-top:16px;padding-top:16px;border-top:1px solid #f0f2f7;">
                <textarea id="newNoteBody" class="lp-note-textarea" placeholder="Escreva uma nota..."></textarea>
                <button class="lp-btn-add-note" id="btnAddNote" onclick="addPageNote()">
                    <i class="bi bi-plus-lg"></i> Adicionar Nota
                </button>
            </div>
        </div>

        {{-- ── Tab: Histórico ── --}}
        <div class="lp-tab-panel" id="tab-history">
            @if($lead->events->count() === 0)
            <div style="text-align:center;padding:40px 20px;color:#9ca3af;">
                <i class="bi bi-clock" style="font-size:32px;opacity:.3;display:block;margin-bottom:8px;"></i>
                Nenhum evento registrado.
            </div>
            @else
            <ul class="lp-timeline">
                @foreach($lead->events as $event)
                @php
                    [$iconClass, $iconBg, $iconColor] = match($event->event_type) {
                        'created'      => ['bi-star-fill',              '#f0fdf4', '#10b981'],
                        'stage_changed'=> ['bi-arrow-right-circle-fill','#eff6ff', '#3b82f6'],
                        'note_added'   => ['bi-chat-left-text-fill',    '#faf5ff', '#8b5cf6'],
                        default        => ['bi-pencil-fill',             '#fff7ed', '#f59e0b'],
                    };
                @endphp
                <li class="lp-timeline-item">
                    <div class="lp-tl-icon" style="background:{{ $iconBg }};color:{{ $iconColor }};">
                        <i class="bi {{ $iconClass }}"></i>
                    </div>
                    <div class="lp-tl-body">
                        <div class="lp-tl-desc">{{ $event->description }}</div>
                        <div class="lp-tl-meta">
                            por {{ $event->performedBy?->name ?? 'Sistema' }}
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
                Ver conversa completa no chat
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
                    @if($msgDay === now()->format('d/m/Y')) Hoje
                    @elseif($msgDay === now()->subDay()->format('d/m/Y')) Ontem
                    @else {{ $msgDay }}
                    @endif
                </div>
                @endif

                @php
                    $isOut = $msg->direction === 'outbound';
                    $isNote = $msg->type === 'note';
                @endphp
                <div class="lp-bubble-wrap {{ $isOut ? 'out' : 'in' }}">
                    <div class="lp-bubble {{ $isNote ? 'note-bubble' : ($isOut ? 'out' : 'in') }}">
                        @if($isNote)
                            <span style="font-size:11px;margin-bottom:4px;display:block;opacity:.7;"><i class="bi bi-lock-fill"></i> Nota interna</span>
                        @endif
                        @if($msg->type === 'image' && $msg->media_url)
                            <img src="{{ $msg->media_url }}" alt="Imagem" onclick="window.open(this.src,'_blank')">
                        @elseif($msg->type === 'audio' && $msg->media_url)
                            <audio controls src="{{ $msg->media_url }}"></audio>
                        @elseif($msg->body)
                            {{ $msg->body }}
                        @else
                            <em style="opacity:.5;">{{ ucfirst($msg->type ?? 'mensagem') }}</em>
                        @endif
                    </div>
                    <div class="lp-bubble-time">{{ $msg->sent_at?->format('H:i') }}</div>
                </div>
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
                <p>Nenhuma conversa Instagram vinculada a este lead.</p>
                @if($lead->instagram_username)
                <p style="font-size:12px;margin-top:4px;">Username: <strong>{{ $lead->instagram_username }}</strong></p>
                @endif
            </div>
            @else
            <a href="{{ route('chats.index') }}" target="_blank" class="lp-chat-link">
                <i class="bi bi-instagram" style="color:#e1306c;"></i>
                Ver conversa completa no chat
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
                    @if($msgDay === now()->format('d/m/Y')) Hoje
                    @elseif($msgDay === now()->subDay()->format('d/m/Y')) Ontem
                    @else {{ $msgDay }}
                    @endif
                </div>
                @endif

                @php $isOut = $msg->direction === 'outbound'; @endphp
                <div class="lp-bubble-wrap {{ $isOut ? 'out' : 'in' }}">
                    <div class="lp-bubble {{ $isOut ? 'ig-out' : 'in' }}">
                        @if($msg->type === 'image' && $msg->media_url)
                            <img src="{{ $msg->media_url }}" alt="Imagem" onclick="window.open(this.src,'_blank')">
                        @elseif($msg->type === 'audio' && $msg->media_url)
                            <audio controls src="{{ $msg->media_url }}"></audio>
                        @elseif($msg->body)
                            {{ $msg->body }}
                        @else
                            <em style="opacity:.5;">{{ ucfirst($msg->type ?? 'mensagem') }}</em>
                        @endif
                    </div>
                    <div class="lp-bubble-time">{{ $msg->sent_at?->format('H:i') }}</div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
        @endif

    </div>{{-- end left col --}}

    {{-- ── Right: Info card ── --}}
    <div class="lp-info-card">

        {{-- Contato --}}
        <div class="lp-info-section">
            <div class="lp-info-section-title">Contato</div>

            <div class="lp-info-row">
                <div class="lp-info-icon"><i class="bi bi-telephone"></i></div>
                <div class="lp-info-val">
                    @if($lead->phone)
                    <a href="https://wa.me/{{ preg_replace('/\D/', '', $lead->phone) }}" target="_blank">
                        {{ $lead->phone }}
                    </a>
                    @else
                    <span class="lp-info-empty">—</span>
                    @endif
                </div>
            </div>

            <div class="lp-info-row">
                <div class="lp-info-icon"><i class="bi bi-envelope"></i></div>
                <div class="lp-info-val">
                    @if($lead->email)
                    <a href="mailto:{{ $lead->email }}">{{ $lead->email }}</a>
                    @else
                    <span class="lp-info-empty">—</span>
                    @endif
                </div>
            </div>

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
            <div class="lp-info-section-title">Negócio</div>

            <div class="lp-info-row">
                <div class="lp-info-icon"><i class="bi bi-currency-dollar"></i></div>
                <div class="lp-info-val" style="font-weight:700;color:#10b981;">
                    @if($lead->value)
                    R$ {{ number_format((float)$lead->value, 2, ',', '.') }}
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
                <div class="lp-info-val">
                    {{ $lead->assignedTo?->name ?? 'Não atribuído' }}
                </div>
            </div>
        </div>

        {{-- Tags --}}
        @if(!empty($lead->tags))
        <div class="lp-info-section">
            <div class="lp-info-section-title">Tags</div>
            <div>
                @foreach($lead->tags as $tag)
                <span class="lp-tag-chip">{{ $tag }}</span>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Campos personalizados --}}
        @php $customFields = $lead->custom_fields; @endphp
        @if(!empty($customFields))
        <div class="lp-info-section">
            <div class="lp-info-section-title">Campos Personalizados</div>
            @foreach($customFields as $field)
            <div class="lp-info-row" style="align-items:flex-start;">
                <div class="lp-info-icon"><i class="bi bi-input-cursor-text"></i></div>
                <div class="lp-info-val">
                    <div style="font-size:11px;color:#9ca3af;font-weight:600;margin-bottom:2px;">{{ $field['label'] }}</div>
                    @if($field['value'] !== null && $field['value'] !== '')
                        @if($field['type'] === 'checkbox')
                            {{ $field['value'] ? 'Sim' : 'Não' }}
                        @elseif($field['type'] === 'currency')
                            R$ {{ number_format((float)$field['value'], 2, ',', '.') }}
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

        {{-- Datas --}}
        <div class="lp-info-section">
            <div class="lp-info-section-title">Datas</div>
            <div class="lp-info-row">
                <div class="lp-info-icon"><i class="bi bi-calendar-plus"></i></div>
                <div class="lp-info-val" style="font-size:12.5px;">
                    Criado em {{ $lead->created_at->format('d/m/Y H:i') }}
                </div>
            </div>
            <div class="lp-info-row">
                <div class="lp-info-icon"><i class="bi bi-calendar-check"></i></div>
                <div class="lp-info-val" style="font-size:12.5px;">
                    Atualizado {{ $lead->updated_at->diffForHumans() }}
                </div>
            </div>
        </div>

    </div>{{-- end info card --}}

</div>{{-- end grid --}}

</div>{{-- end page-container --}}

{{-- Drawer compartilhado (para edição) --}}
@include('tenant.leads._drawer', ['pipelines' => $pipelines, 'customFieldDefs' => $cfDefs])

@endsection

@push('scripts')
<script>
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
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Salvando...';

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
        if (!res.ok) throw new Error(data.message || 'Erro');

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
                    <div class="lp-note-author">${escapeHtml(note.author || 'Eu')}</div>
                    <div class="lp-note-date">agora mesmo</div>
                </div>
                <button class="lp-note-del" onclick="deletePageNote(${note.id})" title="Excluir nota">
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
        alert('Erro ao salvar nota: ' + e.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-plus-lg"></i> Adicionar Nota';
    }
}

async function deletePageNote(noteId) {
    if (!confirm('Excluir esta nota?')) return;
    const url = LEAD_NOTE_DEL
        .replace('__LEAD__', {{ $lead->id }})
        .replace('__NOTE__', noteId);
    try {
        const res = await fetch(url, {
            method: 'DELETE',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
        });
        if (!res.ok) throw new Error('Erro ao excluir');
        document.getElementById('note-' + noteId)?.remove();

        if (document.querySelectorAll('.lp-note-card').length === 0) {
            const container = document.getElementById('notesContainer');
            container.innerHTML = `<div id="notesEmpty" style="text-align:center;padding:30px 20px;color:#9ca3af;">
                <i class="bi bi-journal-x" style="font-size:32px;opacity:.3;display:block;margin-bottom:8px;"></i>
                Nenhuma nota ainda.
            </div>`;
        }
    } catch(e) {
        alert('Erro: ' + e.message);
    }
}
</script>
@endpush

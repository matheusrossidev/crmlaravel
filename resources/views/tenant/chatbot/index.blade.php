@extends('tenant.layouts.app')

@php
    $title    = 'Chatbot Builder';
    $pageIcon = 'diagram-3';
@endphp

@section('topbar_actions')
<div class="topbar-actions">
    <a href="{{ route('chatbot.flows.create') }}" class="btn-primary-sm" style="text-decoration:none;display:flex;align-items:center;gap:6px;">
        <i class="bi bi-plus-lg"></i> Novo Fluxo
    </a>
</div>
@endsection

@push('styles')
<style>
    .flows-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 16px;
    }

    .flow-card {
        background: #fff;
        border-radius: 14px;
        border: 1px solid #e8eaf0;
        overflow: hidden;
        transition: box-shadow .15s;
    }

    .flow-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,.07); }

    .flow-card-body {
        padding: 18px 20px;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .flow-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: #eff6ff;
        border: 1.5px solid #bfdbfe;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        color: #2563eb;
        flex-shrink: 0;
    }

    .flow-name {
        font-size: 14px;
        font-weight: 700;
        color: #1a1d23;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .flow-desc {
        font-size: 12px;
        color: #9ca3af;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .flow-meta {
        display: flex;
        gap: 14px;
        font-size: 11px;
        color: #6b7280;
    }

    .flow-actions {
        display: flex;
        gap: 6px;
        padding-top: 12px;
        border-top: 1px solid #f0f2f7;
        margin-top: auto;
    }

    .badge-active   { background: #d1fae5; color: #065f46; padding: 2px 9px; border-radius: 99px; font-size: 11px; font-weight: 700; white-space: nowrap; }
    .badge-inactive { background: #f3f4f6; color: #6b7280;  padding: 2px 9px; border-radius: 99px; font-size: 11px; font-weight: 700; white-space: nowrap; }

    .empty-state {
        text-align: center;
        padding: 80px 20px;
        color: #9ca3af;
    }

    .empty-state i      { font-size: 52px; opacity: .2; margin-bottom: 14px; display: block; }
    .empty-state h3     { font-size: 16px; color: #374151; margin: 0 0 6px; }
    .empty-state p      { font-size: 13.5px; margin: 0 0 20px; }
</style>
@endpush

@section('content')
<div class="page-container">

    @if($flows->isEmpty())
        <div class="empty-state">
            <i class="bi bi-diagram-3"></i>
            <h3>Nenhum fluxo criado ainda</h3>
            <p>
                Crie um fluxo para atender seus contatos automaticamente no WhatsApp.<br>
                <a href="{{ route('chatbot.flows.create') }}" style="color:#3B82F6;font-weight:600;">
                    Criar primeiro fluxo →
                </a>
            </p>
        </div>
    @else
        <div class="flows-grid">
            @foreach($flows as $flow)
            <div class="flow-card">
                <div class="flow-card-body">

                    {{-- Cabeçalho do card --}}
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div class="flow-icon"><i class="bi bi-diagram-3"></i></div>
                        <div style="flex:1;min-width:0;">
                            <div class="flow-name">{{ $flow->name }}</div>
                            @if($flow->description)
                                <div class="flow-desc">{{ $flow->description }}</div>
                            @endif
                        </div>
                        <span class="{{ $flow->is_active ? 'badge-active' : 'badge-inactive' }}">
                            {{ $flow->is_active ? 'Ativo' : 'Inativo' }}
                        </span>
                    </div>

                    {{-- Meta --}}
                    <div class="flow-meta">
                        @if($flow->trigger_keywords)
                            <span><i class="bi bi-lightning-charge text-warning me-1"></i>{{ implode(', ', array_slice($flow->trigger_keywords, 0, 3)) }}{{ count($flow->trigger_keywords) > 3 ? '…' : '' }}</span>
                        @else
                            <span><i class="bi bi-hand-index me-1"></i>Manual</span>
                        @endif
                        <span><i class="bi bi-diagram-3 me-1"></i>{{ $flow->nodes()->count() }} nós</span>
                    </div>

                    {{-- Ações --}}
                    <div class="flow-actions">
                        <a href="{{ route('chatbot.flows.edit', $flow) }}" class="btn-primary-sm" style="text-decoration:none;display:inline-flex;align-items:center;gap:5px;font-size:12px;">
                            <i class="bi bi-pencil-square"></i> Editar fluxo
                        </a>
                        <a href="{{ route('chatbot.flows.edit', $flow) }}?settings=1" class="btn-secondary-sm" style="text-decoration:none;display:inline-flex;align-items:center;gap:5px;font-size:12px;">
                            <i class="bi bi-gear"></i> Config.
                        </a>
                        <form method="POST" action="{{ route('chatbot.flows.destroy', $flow) }}" style="margin-left:auto;"
                              onsubmit="return confirm('Excluir o fluxo «{{ addslashes($flow->name) }}»? Esta ação não pode ser desfeita.')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-secondary-sm" style="color:#dc2626;border-color:#fca5a5;background:#fff0f0;display:inline-flex;align-items:center;">
                                <i class="bi bi-trash3"></i>
                            </button>
                        </form>
                    </div>

                </div>
            </div>
            @endforeach
        </div>
    @endif

</div>
@endsection

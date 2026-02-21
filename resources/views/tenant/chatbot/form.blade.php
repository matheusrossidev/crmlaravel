@extends('tenant.layouts.app')

@php
    $isEdit   = $flow->exists;
    $title    = $isEdit ? 'Editar Fluxo' : 'Novo Fluxo';
    $pageIcon = 'diagram-3';
@endphp

@section('topbar_actions')
<div class="topbar-actions">
    <a href="{{ route('chatbot.flows.index') }}" style="color:#2563eb;font-size:13.5px;font-weight:500;text-decoration:underline;display:inline-flex;align-items:center;gap:5px;">
        <i class="bi bi-arrow-left" style="font-size:12px;"></i> Voltar
    </a>
</div>
@endsection

@push('styles')
<style>
    .flow-form-wrap {
        max-width: 640px;
        margin: 0 auto;
    }

    .flow-form-card {
        background: #fff;
        border-radius: 14px;
        border: 1px solid #e8eaf0;
        padding: 28px 32px;
    }

    .form-section-label {
        font-size: 10.5px;
        font-weight: 700;
        color: #9ca3af;
        text-transform: uppercase;
        letter-spacing: .08em;
        margin-bottom: 12px;
        margin-top: 24px;
        padding-bottom: 8px;
        border-bottom: 1px solid #f0f2f7;
    }

    .form-section-label:first-child { margin-top: 0; }

    .form-group {
        margin-bottom: 14px;
    }

    .form-group label {
        display: block;
        font-size: 12.5px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 5px;
        font-family: 'Inter', sans-serif;
    }

    .form-group .hint {
        font-size: 11.5px;
        color: #9ca3af;
        margin-top: 4px;
        line-height: 1.5;
        font-family: 'Inter', sans-serif;
    }

    .form-group .hint code {
        background: #f3f4f6;
        padding: 1px 5px;
        border-radius: 4px;
        font-size: 11px;
        color: #6366f1;
    }

    .field-input {
        width: 100%;
        padding: 9px 12px;
        border: 1.5px solid #e8eaf0;
        border-radius: 9px;
        font-size: 13.5px;
        font-family: 'Inter', sans-serif;
        color: #1a1d23;
        background: #fafafa;
        outline: none;
        transition: border-color .15s, box-shadow .15s;
        box-sizing: border-box;
        appearance: none;
    }

    .field-input:focus {
        border-color: #3B82F6;
        background: #fff;
        box-shadow: 0 0 0 3px rgba(59,130,246,.1);
    }

    .field-input.is-invalid {
        border-color: #EF4444;
        box-shadow: 0 0 0 3px rgba(239,68,68,.08);
    }

    .field-error {
        font-size: 11.5px;
        color: #EF4444;
        margin-top: 3px;
    }

    .switch-row {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 14px;
        background: #f9fafb;
        border-radius: 9px;
        border: 1.5px solid #e8eaf0;
    }

    .switch-row label {
        font-size: 13px;
        font-weight: 600;
        color: #374151;
        margin: 0;
        cursor: pointer;
        font-family: 'Inter', sans-serif;
    }

    .switch-row .switch-desc {
        font-size: 11.5px;
        color: #9ca3af;
        font-family: 'Inter', sans-serif;
    }

    .form-actions {
        display: flex;
        gap: 10px;
        align-items: center;
        margin-top: 28px;
        padding-top: 20px;
        border-top: 1px solid #f0f2f7;
    }

    .btn-form-primary {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 9px 20px;
        background: #2563eb;
        color: #fff;
        border: none;
        border-radius: 9px;
        font-size: 13.5px;
        font-weight: 600;
        font-family: 'Inter', sans-serif;
        cursor: pointer;
        transition: background .15s;
        text-decoration: none;
    }

    .btn-form-primary:hover { background: #1d4ed8; color: #fff; }

    .btn-form-secondary {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 9px 18px;
        background: #fff;
        color: #374151;
        border: 1.5px solid #e8eaf0;
        border-radius: 9px;
        font-size: 13.5px;
        font-weight: 600;
        font-family: 'Inter', sans-serif;
        cursor: pointer;
        transition: border-color .15s, background .15s;
        text-decoration: none;
    }

    .btn-form-secondary:hover { border-color: #d1d5db; background: #f9fafb; color: #374151; }

    .open-builder-row {
        margin-top: 16px;
        display: flex;
        justify-content: center;
    }

    .btn-open-builder {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 22px;
        background: #f0fdf4;
        color: #16a34a;
        border: 1.5px solid #bbf7d0;
        border-radius: 9px;
        font-size: 13.5px;
        font-weight: 600;
        font-family: 'Inter', sans-serif;
        text-decoration: none;
        transition: background .15s, border-color .15s;
    }

    .btn-open-builder:hover { background: #dcfce7; border-color: #86efac; color: #15803d; }
</style>
@endpush

@section('content')
<div class="page-container">
<div class="flow-form-wrap">

    @if($errors->any())
        <div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:10px;padding:12px 16px;margin-bottom:18px;font-size:13px;color:#dc2626;font-family:'Inter',sans-serif;">
            <i class="bi bi-exclamation-circle me-1"></i>
            {{ $errors->first() }}
        </div>
    @endif

    <div class="flow-form-card">
        <form method="POST" action="{{ $isEdit ? route('chatbot.flows.update', $flow) : route('chatbot.flows.store') }}">
            @csrf
            @if($isEdit) @method('PUT') @endif

            {{-- Identificação --}}
            <div class="form-section-label">Identificação</div>

            <div class="form-group">
                <label>Nome do fluxo <span style="color:#EF4444;">*</span></label>
                <input type="text" name="name"
                    class="field-input {{ $errors->has('name') ? 'is-invalid' : '' }}"
                    value="{{ old('name', $flow->name) }}"
                    placeholder="Ex: Qualificação de Lead"
                    required>
                @error('name')
                    <div class="field-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label>Descrição</label>
                <textarea name="description" class="field-input" rows="2"
                    placeholder="Para que serve este fluxo?">{{ old('description', $flow->description) }}</textarea>
            </div>

            {{-- Disparo --}}
            <div class="form-section-label">Disparo Automático</div>

            <div class="form-group">
                <label>Keywords de disparo</label>
                <input type="text" name="trigger_keywords" class="field-input"
                    value="{{ old('trigger_keywords', $flow->trigger_keywords ? implode(', ', $flow->trigger_keywords) : '') }}"
                    placeholder="oi, olá, bom dia">
                <div class="hint">Separadas por vírgula. Deixe vazio para atribuição apenas manual. Quando um contato enviar uma mensagem contendo uma dessas palavras, o fluxo será iniciado automaticamente.</div>
            </div>

            {{-- Variáveis --}}
            <div class="form-section-label">Variáveis de Sessão</div>

            <div class="form-group">
                <label>Variáveis</label>
                <input type="text" name="variables" class="field-input"
                    value="{{ old('variables', $flow->variables ? implode(', ', array_column($flow->variables, 'name')) : '') }}"
                    placeholder="nome, email, interesse">
                <div class="hint">
                    Nomes das variáveis que o fluxo irá coletar. Ex: <code>nome</code> → use <code>&#123;&#123;nome&#125;&#125;</code> em mensagens.
                </div>
            </div>

            @if($isEdit)
            {{-- Status --}}
            <div class="form-section-label">Status</div>

            <div class="form-group">
                <div class="switch-row">
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1"
                            id="isActive" style="width:38px;height:20px;cursor:pointer;"
                            {{ old('is_active', $flow->is_active) ? 'checked' : '' }}>
                    </div>
                    <div>
                        <label for="isActive">Fluxo ativo</label>
                        <div class="switch-desc">Quando ativo, o fluxo responde automaticamente às mensagens.</div>
                    </div>
                </div>
            </div>
            @endif

            <div class="form-actions">
                <button type="submit" class="btn-form-primary">
                    @if($isEdit)
                        <i class="bi bi-check-lg"></i> Salvar alterações
                    @else
                        <i class="bi bi-arrow-right-circle"></i> Criar e editar nós
                    @endif
                </button>
                <a href="{{ route('chatbot.flows.index') }}" class="btn-form-secondary">
                    Cancelar
                </a>
            </div>
        </form>
    </div>

    @if($isEdit)
    <div class="open-builder-row">
        <a href="{{ route('chatbot.flows.edit', $flow) }}" class="btn-open-builder">
            <i class="bi bi-diagram-3"></i> Abrir Builder de Nós
        </a>
    </div>
    @endif

</div>
</div>
@endsection

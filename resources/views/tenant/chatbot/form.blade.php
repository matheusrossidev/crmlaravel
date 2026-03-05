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
        max-width: 100%;
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

    .chatbot-channel-card:hover { border-color: #93c5fd !important; background: #f0f8ff !important; color: #2563eb !important; }
    .chatbot-channel-card.selected { border-color: #3B82F6 !important; background: #eff6ff !important; color: #2563eb !important; }
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

            {{-- Canal --}}
            <div class="form-section-label">Canal</div>

            @php $currentChannel = old('channel', $flow->channel ?? 'whatsapp'); @endphp
            <div class="form-group">
                <div style="display:flex;gap:10px;">
                    @foreach([['whatsapp','WhatsApp','whatsapp'],['instagram','Instagram','instagram'],['website','Website','globe']] as [$val,$label,$icon])
                    <label style="flex:1;cursor:pointer;">
                        <input type="radio" name="channel" value="{{ $val }}" {{ $currentChannel === $val ? 'checked' : '' }}
                               style="display:none;" onchange="updateChatbotChannelCards()">
                        <div class="chatbot-channel-card {{ $currentChannel === $val ? 'selected' : '' }}" data-channel="{{ $val }}"
                             style="display:flex;flex-direction:column;align-items:center;gap:5px;padding:14px 8px;border:2px solid #e8eaf0;border-radius:10px;background:#fafafa;color:#6b7280;font-size:12.5px;font-weight:600;transition:all .15s;text-align:center;cursor:pointer;">
                            <i class="bi bi-{{ $icon }}" style="font-size:20px;"></i>
                            <span>{{ $label }}</span>
                        </div>
                    </label>
                    @endforeach
                </div>
            </div>

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

            {{-- Widget Website --}}
            <div id="website-settings" style="{{ ($currentChannel === 'website') ? '' : 'display:none;' }}">
                <div class="form-section-label">Aparência do Widget</div>

                <div class="form-group">
                    <label>Nome do bot</label>
                    <input type="text" name="bot_name" class="field-input"
                        value="{{ old('bot_name', $flow->bot_name) }}"
                        placeholder="Ex: Ana, Sofia, Assistente...">
                    <div class="hint">Aparece no cabeçalho do chat.</div>
                </div>

                <div class="form-group">
                    <label>Avatar do bot</label>
                    <input type="hidden" name="bot_avatar" id="bot_avatar_value" value="{{ old('bot_avatar', $flow->bot_avatar) }}">

                    {{-- Grade de avatares pré-definidos + upload --}}
                    <div style="display:flex;gap:10px;flex-wrap:wrap;" id="avatar-grid">
                        @php
                            $predefinedAvatars = [
                                '/images/avatars/agent-1.png',
                                '/images/avatars/agent-2.png',
                                '/images/avatars/agent-3.png',
                                '/images/avatars/agent-4.png',
                                '/images/avatars/agent-5.png',
                            ];
                            $currentAvatar = old('bot_avatar', $flow->bot_avatar ?? '');
                        @endphp
                        @foreach($predefinedAvatars as $av)
                        <div class="avatar-option {{ $currentAvatar === $av ? 'selected' : '' }}"
                             data-url="{{ $av }}"
                             onclick="selectAvatar('{{ $av }}')"
                             style="width:52px;height:52px;border-radius:50%;overflow:hidden;cursor:pointer;border:2.5px solid {{ $currentAvatar === $av ? '#0085f3' : '#e8eaf0' }};transition:border-color .15s;flex-shrink:0;">
                            <img src="{{ asset($av) }}" alt="Avatar" style="width:100%;height:100%;object-fit:cover;"
                                 onerror="this.parentElement.style.display='none'">
                        </div>
                        @endforeach

                        {{-- Upload personalizado --}}
                        @php
                            $isCustom = $currentAvatar && !in_array($currentAvatar, $predefinedAvatars);
                        @endphp
                        <div id="avatar-upload-card"
                             onclick="document.getElementById('avatar-upload-input').click()"
                             style="width:52px;height:52px;border-radius:50%;overflow:hidden;cursor:pointer;border:2.5px solid {{ $isCustom ? '#0085f3' : '#e8eaf0' }};transition:border-color .15s;flex-shrink:0;background:#f8fafc;display:flex;align-items:center;justify-content:center;position:relative;">
                            @if($isCustom)
                                <img id="avatar-upload-preview" src="{{ $currentAvatar }}" alt="Avatar" style="width:100%;height:100%;object-fit:cover;position:absolute;inset:0;">
                            @else
                                <img id="avatar-upload-preview" src="" alt="" style="width:100%;height:100%;object-fit:cover;position:absolute;inset:0;display:none;">
                                <svg id="avatar-upload-icon" viewBox="0 0 24 24" style="width:20px;height:20px;fill:#9ca3af;"><path d="M19 3H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2V5a2 2 0 00-2-2zm-7 13l-4-4h3V9h2v3h3l-4 4z"/></svg>
                            @endif
                        </div>
                        <input type="file" id="avatar-upload-input" accept="image/*" style="display:none;">
                    </div>
                    <div class="hint" style="margin-top:8px;">Escolha um avatar ou clique no último ícone para fazer upload de uma imagem personalizada.</div>
                </div>

                <div class="form-group">
                    <label>Mensagem de entrada</label>
                    <textarea name="welcome_message" class="field-input" rows="2"
                        placeholder="Olá! 👋 Posso te ajudar?">{{ old('welcome_message', $flow->welcome_message) }}</textarea>
                    <div class="hint">Aparece como bolinha flutuante acima do botão do chat após 3 segundos. Deixe vazio para desativar. (Apenas no modo Bubble)</div>
                </div>

                <div class="form-group">
                    <label>Tipo de Widget</label>
                    @php $currentType = old('widget_type', $flow->widget_type ?? 'bubble'); @endphp
                    <div style="display:flex;gap:12px;margin-top:4px;" id="widget-type-cards">
                        <label class="widget-type-card {{ $currentType === 'bubble' ? 'selected' : '' }}" data-type="bubble" style="flex:1;cursor:pointer;border:1.5px solid {{ $currentType === 'bubble' ? '#0085f3' : '#e8eaf0' }};border-radius:10px;padding:12px 10px;text-align:center;background:{{ $currentType === 'bubble' ? '#eff6ff' : '#fff' }};transition:all .15s;">
                            <input type="radio" name="widget_type" value="bubble" {{ $currentType === 'bubble' ? 'checked' : '' }} style="display:none;">
                            <div style="margin-bottom:6px;display:flex;justify-content:center;">
                                <svg viewBox="0 0 24 24" style="width:28px;height:28px;fill:{{ $currentType === 'bubble' ? '#0085f3' : '#9ca3af' }};"><path d="M20 2H4C2.9 2 2 2.9 2 4v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>
                            </div>
                            <div style="font-size:13px;font-weight:700;color:#1a1d23;">Bubble</div>
                            <div style="font-size:11px;color:#6b7280;margin-top:2px;">Botão flutuante no canto da tela</div>
                        </label>
                        <label class="widget-type-card {{ $currentType === 'inline' ? 'selected' : '' }}" data-type="inline" style="flex:1;cursor:pointer;border:1.5px solid {{ $currentType === 'inline' ? '#0085f3' : '#e8eaf0' }};border-radius:10px;padding:12px 10px;text-align:center;background:{{ $currentType === 'inline' ? '#eff6ff' : '#fff' }};transition:all .15s;">
                            <input type="radio" name="widget_type" value="inline" {{ $currentType === 'inline' ? 'checked' : '' }} style="display:none;">
                            <div style="margin-bottom:6px;display:flex;justify-content:center;">
                                <svg viewBox="0 0 24 24" style="width:28px;height:28px;fill:{{ $currentType === 'inline' ? '#0085f3' : '#9ca3af' }};"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 14H4V6h16v12zM6 10h4v2H6zm0-3h12v2H6zm0 6h8v2H6z"/></svg>
                            </div>
                            <div style="font-size:13px;font-weight:700;color:#1a1d23;">Inline / Página</div>
                            <div style="font-size:11px;color:#6b7280;margin-top:2px;">Embebido em elemento da página</div>
                        </label>
                    </div>
                    <div id="bubble-type-hint" class="hint" style="{{ $currentType !== 'inline' ? '' : 'display:none;' }}">
                        Adicione <code>&lt;script src="..." data-token="..."&gt;&lt;/script&gt;</code> antes de <code>&lt;/body&gt;</code>.
                    </div>
                    <div id="inline-type-hint" class="hint" style="{{ $currentType === 'inline' ? '' : 'display:none;' }}">
                        Adicione <code>&lt;div id="syncro-chat"&gt;&lt;/div&gt;</code> onde deseja o chat, e o <code>&lt;script&gt;</code> no final do body.
                    </div>
                </div>
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

@push('scripts')
<script>
function updateChatbotChannelCards() {
    const selected = document.querySelector('input[name="channel"]:checked')?.value;
    document.querySelectorAll('.chatbot-channel-card').forEach(card => {
        card.classList.toggle('selected', card.dataset.channel === selected);
    });
    const ws = document.getElementById('website-settings');
    if (ws) ws.style.display = selected === 'website' ? 'block' : 'none';
}

function selectAvatar(url) {
    document.getElementById('bot_avatar_value').value = url;

    // Update predefined grid highlight
    const predefined = Array.from(document.querySelectorAll('.avatar-option')).map(el => el.dataset.url);
    document.querySelectorAll('.avatar-option').forEach(el => {
        el.style.borderColor = el.dataset.url === url ? '#0085f3' : '#e8eaf0';
    });

    // Update upload card highlight + preview
    const uploadCard = document.getElementById('avatar-upload-card');
    const uploadPreview = document.getElementById('avatar-upload-preview');
    const uploadIcon = document.getElementById('avatar-upload-icon');
    const isCustom = url && !predefined.includes(url);
    if (uploadCard) uploadCard.style.borderColor = isCustom ? '#0085f3' : '#e8eaf0';
    if (uploadPreview) {
        if (isCustom) {
            uploadPreview.src = url;
            uploadPreview.style.display = 'block';
            if (uploadIcon) uploadIcon.style.display = 'none';
        } else {
            uploadPreview.style.display = 'none';
            if (uploadIcon) uploadIcon.style.display = '';
        }
    }
}

// Avatar upload via file input
(function() {
    var input = document.getElementById('avatar-upload-input');
    if (!input) return;
    input.addEventListener('change', function() {
        var file = input.files[0];
        if (!file) return;
        var formData = new FormData();
        formData.append('image', file);
        var card = document.getElementById('avatar-upload-card');
        if (card) card.style.opacity = '0.5';
        fetch('{{ route("chatbot.flows.upload-image") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: formData,
        }).then(function(res) { return res.json(); }).then(function(data) {
            if (card) card.style.opacity = '1';
            if (data.url) selectAvatar(data.url);
        }).catch(function() {
            if (card) card.style.opacity = '1';
        });
        input.value = '';
    });
})();

// Widget type card selection
document.querySelectorAll('.widget-type-card').forEach(function(card) {
    card.addEventListener('click', function() {
        var type = card.dataset.type;
        var radio = card.querySelector('input[type="radio"]');
        if (radio) radio.checked = true;
        document.querySelectorAll('.widget-type-card').forEach(function(c) {
            var isSel = c.dataset.type === type;
            c.style.borderColor = isSel ? '#0085f3' : '#e8eaf0';
            c.style.background  = isSel ? '#eff6ff' : '#fff';
            // Update icon color
            var svg = c.querySelector('svg');
            if (svg) svg.style.fill = isSel ? '#0085f3' : '#9ca3af';
        });
        var bubbleHint = document.getElementById('bubble-type-hint');
        var inlineHint = document.getElementById('inline-type-hint');
        if (bubbleHint) bubbleHint.style.display = type === 'bubble' ? '' : 'none';
        if (inlineHint) inlineHint.style.display = type === 'inline' ? '' : 'none';
    });
});
</script>
@endpush
@endsection

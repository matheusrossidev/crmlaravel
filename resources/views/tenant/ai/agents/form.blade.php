@extends('tenant.layouts.app')

@php
    $title    = 'Inteligência Artificial';
    $pageIcon = 'robot';
    $isEdit   = $agent->exists;
@endphp

@push('styles')
<style>
    .ai-form-wrap { width: 100%; }

    .section-card {
        background: #fff; border: 1px solid #e8eaf0;
        border-radius: 14px; margin-bottom: 18px; overflow: hidden;
    }
    .section-card-header {
        display: flex; align-items: center; gap: 10px;
        padding: 16px 20px; border-bottom: 1px solid #f0f2f7;
        cursor: pointer; user-select: none; background: #fafafa;
        transition: background .1s;
    }
    .section-card-header:hover { background: #f5f7fb; }
    .section-card-header .section-icon {
        width: 32px; height: 32px; border-radius: 8px;
        background: #eff6ff; color: #3B82F6;
        display: flex; align-items: center; justify-content: center;
        font-size: 15px;
    }
    .section-card-title { font-size: 13.5px; font-weight: 700; color: #1a1d23; flex: 1; }
    .section-card-body { padding: 20px; display: block; }
    .section-card-body.collapsed { display: none; }
    .chevron { transition: transform .2s; color: #9ca3af; }
    .chevron.open { transform: rotate(180deg); }

    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .form-row.three { grid-template-columns: 1fr 1fr 1fr; }
    .form-group { margin-bottom: 14px; }
    .form-label {
        display: block; font-size: 11.5px; font-weight: 700;
        color: #6b7280; margin-bottom: 5px;
        text-transform: uppercase; letter-spacing: .05em;
    }
    .form-control {
        width: 100%; padding: 9px 12px;
        border: 1.5px solid #e8eaf0; border-radius: 9px;
        font-size: 13.5px; outline: none; font-family: inherit;
        transition: border-color .15s; box-sizing: border-box;
    }
    .form-control:focus { border-color: #3B82F6; }
    textarea.form-control { resize: vertical; min-height: 90px; }
    select.form-control { background: #fff; }

    /* Etapas dinâmicas */
    .stages-list { display: flex; flex-direction: column; gap: 8px; }
    .stage-item {
        display: flex; gap: 8px; align-items: flex-start;
        padding: 10px; background: #f8fafc;
        border: 1px solid #e8eaf0; border-radius: 9px;
    }
    .stage-num {
        width: 24px; height: 24px; border-radius: 6px;
        background: #eff6ff; color: #3B82F6;
        display: flex; align-items: center; justify-content: center;
        font-size: 11px; font-weight: 700; flex-shrink: 0; margin-top: 8px;
    }
    .stage-inputs { flex: 1; display: flex; flex-direction: column; gap: 6px; }
    .stage-del {
        width: 28px; height: 28px; border-radius: 7px;
        border: 1px solid #e8eaf0; background: #fff; color: #9ca3af;
        cursor: pointer; display: flex; align-items: center; justify-content: center;
        font-size: 13px; flex-shrink: 0; margin-top: 5px; transition: all .15s;
    }
    .stage-del:hover { background: #fee2e2; color: #ef4444; border-color: #fca5a5; }
    .btn-add-stage {
        padding: 8px 16px; border-radius: 8px;
        border: 1.5px dashed #d1d5db; background: transparent;
        font-size: 12.5px; font-weight: 600; color: #6b7280;
        cursor: pointer; transition: all .15s; margin-top: 8px;
    }
    .btn-add-stage:hover { border-color: #3B82F6; color: #3B82F6; background: #f0f8ff; }

    /* Toggle ativo */
    .toggle-wrap {
        display: flex; align-items: center; gap: 10px;
        padding: 12px 16px; background: #f8fafc;
        border: 1px solid #e8eaf0; border-radius: 10px;
        margin-bottom: 18px;
    }
    .toggle-switch {
        width: 44px; height: 24px; border-radius: 12px;
        background: #e5e7eb; position: relative; cursor: pointer;
        transition: background .2s; flex-shrink: 0;
    }
    .toggle-switch.on { background: #3B82F6; }
    .toggle-switch::after {
        content: ''; position: absolute; top: 3px; left: 3px;
        width: 18px; height: 18px; border-radius: 50%; background: #fff;
        transition: left .2s; box-shadow: 0 1px 3px rgba(0,0,0,.2);
    }
    .toggle-switch.on::after { left: 23px; }

    .form-footer {
        display: flex; gap: 10px; align-items: center;
        padding: 20px 0;
    }
    .btn-primary {
        padding: 10px 28px; border-radius: 9px; border: none;
        background: #3B82F6; color: #fff;
        font-size: 13.5px; font-weight: 600; cursor: pointer;
        transition: background .15s;
    }
    .btn-primary:hover { background: #2563eb; }
    .btn-cancel {
        padding: 10px 20px; border-radius: 9px;
        border: 1.5px solid #e8eaf0; background: #fff;
        font-size: 13.5px; font-weight: 600; color: #6b7280;
        cursor: pointer; transition: all .15s; text-decoration: none;
        display: inline-flex; align-items: center;
    }
    .btn-cancel:hover { background: #f0f2f7; }

    /* Widget de chat de teste */
    .test-chat-panel {
        position: fixed; bottom: 24px; right: 24px;
        width: 360px; border-radius: 16px;
        background: #fff; border: 1px solid #e8eaf0;
        box-shadow: 0 12px 48px rgba(0,0,0,.15);
        z-index: 500; display: flex; flex-direction: column;
        overflow: hidden;
        @if(!$isEdit) display: none; @endif
    }
    .test-chat-header {
        padding: 13px 16px; background: #3B82F6;
        display: flex; align-items: center; justify-content: space-between;
        cursor: pointer;
    }
    .test-chat-title { color: #fff; font-size: 13.5px; font-weight: 700; }
    .test-chat-toggle { color: rgba(255,255,255,.8); font-size: 16px; }
    .test-chat-body { height: 320px; overflow-y: auto; padding: 14px; display: flex; flex-direction: column; gap: 8px; }
    .test-chat-body.collapsed { display: none; }
    .test-chat-input-wrap {
        padding: 10px 12px; border-top: 1px solid #f0f2f7;
        display: flex; gap: 7px;
    }
    .test-chat-input-wrap.collapsed { display: none; }
    .test-chat-input {
        flex: 1; padding: 8px 10px; border: 1.5px solid #e8eaf0;
        border-radius: 9px; font-size: 13px; outline: none; font-family: inherit;
    }
    .test-chat-input:focus { border-color: #3B82F6; }
    .test-send-btn {
        width: 36px; height: 36px; border-radius: 9px;
        background: #3B82F6; border: none; color: #fff;
        cursor: pointer; display: flex; align-items: center; justify-content: center;
        font-size: 15px; flex-shrink: 0; transition: background .15s;
    }
    .test-send-btn:hover { background: #2563eb; }
    .test-send-btn:disabled { opacity: .6; cursor: not-allowed; }

    .chat-bubble {
        max-width: 80%; padding: 8px 12px;
        border-radius: 12px; font-size: 13px; line-height: 1.45;
        white-space: pre-wrap; word-break: break-word;
    }
    .chat-bubble.user {
        align-self: flex-end; background: #3B82F6; color: #fff;
        border-bottom-right-radius: 4px;
    }
    .chat-bubble.agent {
        align-self: flex-start; background: #f0f2f7; color: #1a1d23;
        border-bottom-left-radius: 4px;
    }
    .chat-bubble.typing { color: #9ca3af; font-style: italic; }

    @if(!$isEdit)
    .test-chat-panel { display: none; }
    @endif

    /* Knowledge file list */
    .kb-file-item {
        display: flex; align-items: flex-start; gap: 10px;
        padding: 10px 12px; border: 1px solid #e8eaf0;
        border-radius: 9px; margin-bottom: 7px; background: #fafafa;
    }
    .kb-file-icon { font-size: 22px; flex-shrink: 0; line-height: 1; padding-top: 2px; }
    .kb-file-info { flex: 1; min-width: 0; }
    .kb-file-name { font-size: 13px; font-weight: 600; color: #1a1d23; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .kb-status-badge {
        display: inline-block; font-size: 10.5px; font-weight: 700;
        padding: 1px 7px; border-radius: 20px; margin-top: 3px;
    }
    .kb-status-badge.done    { background: #dcfce7; color: #16a34a; }
    .kb-status-badge.failed  { background: #fee2e2; color: #dc2626; }
    .kb-status-badge.pending { background: #fef9c3; color: #ca8a04; }
    .kb-preview-btn {
        font-size: 11px; color: #3B82F6; border: none; background: none;
        padding: 0; cursor: pointer; margin-left: 6px;
    }
    .kb-del-btn {
        flex-shrink: 0; width: 28px; height: 28px;
        border: 1px solid #e8eaf0; border-radius: 7px;
        background: #fff; color: #9ca3af; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        font-size: 13px; transition: all .15s;
    }
    .kb-del-btn:hover { background: #fee2e2; color: #ef4444; border-color: #fca5a5; }
    .kb-file-preview {
        font-size: 11.5px; color: #6b7280; background: #f8fafc;
        border: 1px solid #e8eaf0; border-radius: 7px;
        padding: 8px 10px; margin-bottom: 7px; white-space: pre-wrap;
        line-height: 1.5;
    }
    .kb-uploading {
        display: flex; align-items: center; gap: 8px;
        padding: 10px 12px; border: 1px dashed #93c5fd;
        border-radius: 9px; margin-bottom: 7px; background: #eff6ff;
        font-size: 12.5px; color: #3B82F6;
    }

    .channel-card {
        display: flex; flex-direction: column; align-items: center; gap: 5px;
        padding: 12px 8px; border: 2px solid #e8eaf0; border-radius: 10px;
        background: #fafafa; color: #6b7280; font-size: 12px; font-weight: 600;
        transition: all .15s; text-align: center;
    }
    .channel-card:hover { border-color: #93c5fd; background: #f0f8ff; color: #2563eb; }
    .channel-card.selected { border-color: #3B82F6; background: #eff6ff; color: #2563eb; }
</style>
@endpush

@section('content')
<div class="page-container">

    <div style="margin-bottom:22px;display:flex;align-items:center;gap:10px;">
        <a href="{{ route('ai.agents.index') }}" style="color:#9ca3af;font-size:14px;text-decoration:none;">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <div style="font-size:15px;font-weight:700;color:#1a1d23;">
                {{ $isEdit ? 'Editar Agente' : 'Novo Agente' }}
            </div>
        </div>
    </div>

    <form method="POST"
          action="{{ $isEdit ? route('ai.agents.update', $agent) : route('ai.agents.store') }}"
          id="agentForm"
          class="ai-form-wrap">
        @csrf
        @if($isEdit) @method('PUT') @endif

        {{-- Seletor de Canal --}}
        <div style="margin-bottom:16px;">
            <div style="font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px;">Canal de atuação</div>
            <div style="display:flex;gap:10px;">
                @php $currentChannel = old('channel', $agent->channel ?? 'whatsapp'); @endphp
                @foreach([['whatsapp','WhatsApp','whatsapp'],['instagram','Instagram','instagram'],['web_chat','Web Chat','globe']] as [$val,$label,$icon])
                <label style="flex:1;cursor:pointer;">
                    <input type="radio" name="channel" value="{{ $val }}" {{ $currentChannel === $val ? 'checked' : '' }}
                           style="display:none;" onchange="updateChannelCards()">
                    <div class="channel-card {{ $currentChannel === $val ? 'selected' : '' }}" data-channel="{{ $val }}">
                        <i class="bi bi-{{ $icon }}" style="font-size:18px;"></i>
                        <span>{{ $label }}</span>
                    </div>
                </label>
                @endforeach
            </div>
        </div>

        {{-- Toggle ativo --}}
        <div class="toggle-wrap" onclick="toggleActive()">
            <div class="toggle-switch {{ $agent->is_active ? 'on' : '' }}" id="toggleSwitch"></div>
            <div>
                <div style="font-size:13px;font-weight:700;color:#1a1d23;" id="toggleLabel">
                    {{ $agent->is_active ? 'Agente Ativo' : 'Agente Inativo' }}
                </div>
                <div style="font-size:11.5px;color:#9ca3af;">Ativar para que responda automaticamente</div>
            </div>
        </div>
        <input type="hidden" name="is_active" id="isActiveInput" value="{{ $agent->is_active ? '1' : '0' }}">

        {{-- Toggle auto-assign --}}
        <div class="toggle-wrap" onclick="toggleAutoAssign()" style="margin-bottom:10px;">
            <div class="toggle-switch {{ ($agent->auto_assign ?? false) ? 'on' : '' }}" id="autoAssignSwitch"></div>
            <div>
                <div style="font-size:13px;font-weight:700;color:#1a1d23;" id="autoAssignLabel">
                    {{ ($agent->auto_assign ?? false) ? 'Auto-assign Ativado' : 'Auto-assign Desativado' }}
                </div>
                <div style="font-size:11.5px;color:#9ca3af;">Atribuir automaticamente a novas conversas WhatsApp</div>
            </div>
        </div>
        <input type="hidden" name="auto_assign" id="autoAssignInput" value="{{ ($agent->auto_assign ?? false) ? '1' : '0' }}">

        {{-- 1. Identidade --}}
        <div class="section-card">
            <div class="section-card-header" onclick="toggleSection('identity')">
                <div class="section-icon"><i class="bi bi-person-badge"></i></div>
                <div class="section-card-title">1. Identidade</div>
                <i class="bi bi-chevron-down chevron open" id="chevron-identity"></i>
            </div>
            <div class="section-card-body" id="body-identity">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Nome do Agente *</label>
                        <input type="text" name="name" class="form-control" required
                               value="{{ old('name', $agent->name) }}" placeholder="Ex: Assistente de Vendas">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nome da Empresa</label>
                        <input type="text" name="company_name" class="form-control"
                               value="{{ old('company_name', $agent->company_name) }}" placeholder="Sua Empresa Ltda.">
                    </div>
                </div>
                <div class="form-row three">
                    <div class="form-group">
                        <label class="form-label">Objetivo *</label>
                        <select name="objective" class="form-control">
                            @foreach(['sales' => 'Vendas', 'support' => 'Suporte', 'general' => 'Geral'] as $v => $l)
                            <option value="{{ $v }}" {{ old('objective', $agent->objective) === $v ? 'selected' : '' }}>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Comunicação *</label>
                        <select name="communication_style" class="form-control">
                            @foreach(['formal' => 'Formal', 'normal' => 'Normal', 'casual' => 'Descontraído'] as $v => $l)
                            <option value="{{ $v }}" {{ old('communication_style', $agent->communication_style) === $v ? 'selected' : '' }}>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Idioma *</label>
                        <select name="language" class="form-control">
                            @foreach(['pt-BR' => 'Português (BR)', 'en-US' => 'English', 'es-ES' => 'Español'] as $v => $l)
                            <option value="{{ $v }}" {{ old('language', $agent->language ?? 'pt-BR') === $v ? 'selected' : '' }}>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Setor / Indústria</label>
                    <input type="text" name="industry" class="form-control"
                           value="{{ old('industry', $agent->industry) }}" placeholder="Ex: E-commerce, SaaS, Saúde...">
                </div>
            </div>
        </div>

        {{-- 2. Persona e Comportamento --}}
        <div class="section-card">
            <div class="section-card-header" onclick="toggleSection('persona')">
                <div class="section-icon"><i class="bi bi-chat-quote"></i></div>
                <div class="section-card-title">2. Persona e Comportamento</div>
                <i class="bi bi-chevron-down chevron open" id="chevron-persona"></i>
            </div>
            <div class="section-card-body" id="body-persona">
                <div class="form-group">
                    <label class="form-label">Descrição da Persona</label>
                    <textarea name="persona_description" class="form-control" rows="4"
                              placeholder="Ex: Você é Maria, uma consultora de vendas simpática e proativa que adora ajudar clientes a encontrar a solução certa...">{{ old('persona_description', $agent->persona_description) }}</textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Comportamento</label>
                    <textarea name="behavior" class="form-control" rows="4"
                              placeholder="Ex: Sempre pergunte o nome do cliente. Nunca ofereça descontos sem consultar primeiro. Priorize resolver o problema antes de vender...">{{ old('behavior', $agent->behavior) }}</textarea>
                </div>
            </div>
        </div>

        {{-- 3. Fluxo --}}
        <div class="section-card">
            <div class="section-card-header" onclick="toggleSection('flow')">
                <div class="section-icon"><i class="bi bi-signpost-split"></i></div>
                <div class="section-card-title">3. Fluxo do Atendimento</div>
                <i class="bi bi-chevron-down chevron open" id="chevron-flow"></i>
            </div>
            <div class="section-card-body" id="body-flow">
                <div class="form-group">
                    <label class="form-label">Ao Finalizar o Atendimento</label>
                    <textarea name="on_finish_action" class="form-control" rows="3"
                              placeholder="Ex: Agradeça o contato, ofereça avaliação de 1-5 estrelas e encerre com uma mensagem positiva.">{{ old('on_finish_action', $agent->on_finish_action) }}</textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Quando Transferir para Humano</label>
                    <textarea name="on_transfer_message" class="form-control" rows="3"
                              placeholder="Ex: Se o cliente solicitar falar com atendente, peça desculpas pela demora e informe que um humano vai assumir em breve.">{{ old('on_transfer_message', $agent->on_transfer_message) }}</textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Ao Receber Mensagem Inválida / Tentativa de Jailbreak</label>
                    <textarea name="on_invalid_response" class="form-control" rows="3"
                              placeholder="Ex: Informe que só pode ajudar com assuntos relacionados ao nosso serviço e ofereça opções válidas.">{{ old('on_invalid_response', $agent->on_invalid_response) }}</textarea>
                </div>
            </div>
        </div>

        {{-- 4. Etapas da Conversa --}}
        <div class="section-card">
            <div class="section-card-header" onclick="toggleSection('stages')">
                <div class="section-icon"><i class="bi bi-list-ol"></i></div>
                <div class="section-card-title">4. Etapas da Conversa</div>
                <i class="bi bi-chevron-down chevron" id="chevron-stages"></i>
            </div>
            <div class="section-card-body collapsed" id="body-stages">
                <div style="font-size:12.5px;color:#9ca3af;margin-bottom:12px;">
                    Defina as etapas que o agente deve seguir durante a conversa (opcional).
                </div>
                <div class="stages-list" id="stagesList">
                    @foreach(old('conversation_stages', $agent->conversation_stages ?? []) as $i => $stage)
                    <div class="stage-item" data-index="{{ $i }}">
                        <div class="stage-num">{{ $i + 1 }}</div>
                        <div class="stage-inputs">
                            <input type="text" name="conversation_stages[{{ $i }}][name]"
                                   class="form-control" style="min-height:unset;"
                                   value="{{ $stage['name'] ?? '' }}"
                                   placeholder="Nome da etapa">
                            <input type="text" name="conversation_stages[{{ $i }}][description]"
                                   class="form-control" style="min-height:unset;"
                                   value="{{ $stage['description'] ?? '' }}"
                                   placeholder="Descrição (opcional)">
                        </div>
                        <button type="button" class="stage-del" onclick="removeStage(this)">×</button>
                    </div>
                    @endforeach
                </div>
                <button type="button" class="btn-add-stage" onclick="addStage()">
                    <i class="bi bi-plus"></i> Adicionar etapa
                </button>
            </div>
        </div>

        {{-- 5. Base de Conhecimento --}}
        <div class="section-card">
            <div class="section-card-header" onclick="toggleSection('kb')">
                <div class="section-icon"><i class="bi bi-database"></i></div>
                <div class="section-card-title">5. Base de Conhecimento</div>
                <i class="bi bi-chevron-down chevron" id="chevron-kb"></i>
            </div>
            <div class="section-card-body collapsed" id="body-kb">
                <div style="font-size:12.5px;color:#9ca3af;margin-bottom:10px;">
                    Inclua informações sobre sua empresa, produtos, preços, FAQs, políticas, etc.
                    O agente usará estas informações para responder.
                </div>
                <textarea name="knowledge_base" class="form-control" rows="8"
                          placeholder="Empresa: XYZ Tecnologia&#10;Produtos: Plano Básico R$49/mês, Plano Pro R$99/mês&#10;Horário: seg-sex 9h-18h&#10;Telefone: (11) 1234-5678&#10;...">{{ old('knowledge_base', $agent->knowledge_base) }}</textarea>

                @if($isEdit)
                {{-- Upload de arquivos --}}
                <div style="margin-top:20px;">
                    <div style="font-size:12px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:10px;">
                        <i class="bi bi-paperclip" style="margin-right:4px;"></i>Arquivos de Conhecimento
                    </div>
                    <div style="font-size:12px;color:#9ca3af;margin-bottom:10px;">
                        Faça upload de PDFs, imagens ou arquivos de texto. O conteúdo será extraído automaticamente e usado pelo agente.
                    </div>

                    {{-- Dropzone --}}
                    <div id="kbDropzone" style="border:2px dashed #d1d5db;border-radius:10px;padding:20px 16px;text-align:center;cursor:pointer;transition:all .2s;margin-bottom:14px;"
                         onclick="document.getElementById('kbFileInput').click()"
                         ondragover="event.preventDefault();this.style.borderColor='#3B82F6';this.style.background='#eff6ff';"
                         ondragleave="this.style.borderColor='#d1d5db';this.style.background='';"
                         ondrop="handleKbDrop(event)">
                        <i class="bi bi-cloud-arrow-up" style="font-size:26px;color:#9ca3af;display:block;margin-bottom:6px;"></i>
                        <div style="font-size:13px;color:#6b7280;font-weight:600;">Clique ou arraste arquivos aqui</div>
                        <div style="font-size:11.5px;color:#9ca3af;margin-top:3px;">PDF, TXT, CSV, PNG, JPG, WEBP — máx. 20 MB</div>
                    </div>
                    <input type="file" id="kbFileInput" style="display:none;"
                           accept=".pdf,.txt,.csv,.png,.jpg,.jpeg,.webp,.gif"
                           onchange="uploadKbFile(this.files[0])">

                    {{-- Lista de arquivos --}}
                    <div id="kbFileList">
                        @foreach($knowledgeFiles as $kbFile)
                        <div class="kb-file-item" id="kb-file-{{ $kbFile->id }}">
                            <div class="kb-file-icon">
                                @if(str_starts_with($kbFile->mime_type, 'image/'))
                                    <i class="bi bi-file-earmark-image" style="color:#8b5cf6;"></i>
                                @elseif($kbFile->mime_type === 'application/pdf')
                                    <i class="bi bi-file-earmark-pdf" style="color:#ef4444;"></i>
                                @else
                                    <i class="bi bi-file-earmark-text" style="color:#3B82F6;"></i>
                                @endif
                            </div>
                            <div class="kb-file-info">
                                <div class="kb-file-name">{{ $kbFile->original_name }}</div>
                                @if($kbFile->status === 'done')
                                    <span class="kb-status-badge done">Extraído</span>
                                    @if($kbFile->extracted_text)
                                    <button type="button" class="kb-preview-btn" onclick="toggleKbPreview({{ $kbFile->id }})">
                                        <i class="bi bi-eye"></i> Ver prévia
                                    </button>
                                    @endif
                                @elseif($kbFile->status === 'failed')
                                    <span class="kb-status-badge failed">Falhou</span>
                                    @if($kbFile->error_message)
                                    <span style="font-size:11px;color:#ef4444;display:block;margin-top:2px;">{{ $kbFile->error_message }}</span>
                                    @endif
                                @else
                                    <span class="kb-status-badge pending">Pendente</span>
                                @endif
                            </div>
                            <button type="button" class="kb-del-btn" onclick="deleteKbFile({{ $kbFile->id }}, '{{ e($kbFile->original_name) }}')" title="Remover">
                                <i class="bi bi-trash3"></i>
                            </button>
                        </div>
                        @if($kbFile->extracted_text)
                        <div class="kb-file-preview" id="kb-preview-{{ $kbFile->id }}" style="display:none;">
                            {{ mb_substr($kbFile->extracted_text, 0, 600) }}{{ mb_strlen($kbFile->extracted_text) > 600 ? '…' : '' }}
                        </div>
                        @endif
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- 6. Ferramentas do Agente --}}
        <div class="section-card">
            <div class="section-card-header" onclick="toggleSection('tools')">
                <div class="section-icon"><i class="bi bi-tools"></i></div>
                <div class="section-card-title">6. Ferramentas do Agente</div>
                <i class="bi bi-chevron-down chevron" id="chevron-tools"></i>
            </div>
            <div class="section-card-body collapsed" id="body-tools">
                {{-- Toggle enable_pipeline_tool --}}
                <div class="toggle-wrap" onclick="togglePipelineTool()" style="margin-bottom:16px;">
                    <div class="toggle-switch {{ ($agent->enable_pipeline_tool ?? false) ? 'on' : '' }}" id="pipelineToolSwitch"></div>
                    <div style="margin-left:10px;">
                        <div style="font-size:13px;font-weight:700;color:#1a1d23;" id="pipelineToolLabel">
                            {{ ($agent->enable_pipeline_tool ?? false) ? 'Controle de Funil Ativado' : 'Controle de Funil Desativado' }}
                        </div>
                        <div style="font-size:11px;color:#9ca3af;">O agente pode mover o lead entre as etapas do funil automaticamente durante o atendimento</div>
                    </div>
                </div>
                <input type="hidden" name="enable_pipeline_tool" id="pipelineToolInput" value="{{ ($agent->enable_pipeline_tool ?? false) ? '1' : '0' }}">

                {{-- Toggle enable_tags_tool --}}
                <div class="toggle-wrap" onclick="toggleTagsTool()">
                    <div class="toggle-switch {{ ($agent->enable_tags_tool ?? false) ? 'on' : '' }}" id="tagsToolSwitch"></div>
                    <div style="margin-left:10px;">
                        <div style="font-size:13px;font-weight:700;color:#1a1d23;" id="tagsToolLabel">
                            {{ ($agent->enable_tags_tool ?? false) ? 'Atribuição de Tags Ativada' : 'Atribuição de Tags Desativada' }}
                        </div>
                        <div style="font-size:11px;color:#9ca3af;">O agente pode adicionar tags à conversa automaticamente conforme o contexto</div>
                    </div>
                </div>
                <input type="hidden" name="enable_tags_tool" id="tagsToolInput" value="{{ ($agent->enable_tags_tool ?? false) ? '1' : '0' }}">

                {{-- Toggle enable_intent_notify --}}
                <div class="toggle-wrap" style="margin-top:12px;" onclick="toggleIntentNotify()">
                    <div class="toggle-switch {{ ($agent->enable_intent_notify ?? false) ? 'on' : '' }}" id="intentNotifySwitch"></div>
                    <div style="margin-left:10px;">
                        <div style="font-size:13px;font-weight:700;color:#1a1d23;" id="intentNotifyLabel">
                            {{ ($agent->enable_intent_notify ?? false) ? 'Detecção de Intenção Ativada' : 'Detecção de Intenção Desativada' }}
                        </div>
                        <div style="font-size:11px;color:#9ca3af;">Notifica quando o agente identificar sinais claros de intenção de compra, agendamento ou fechamento</div>
                    </div>
                </div>
                <input type="hidden" name="enable_intent_notify" id="intentNotifyInput" value="{{ ($agent->enable_intent_notify ?? false) ? '1' : '0' }}">

                {{-- Toggle enable_calendar_tool --}}
                <div class="toggle-wrap" style="margin-top:12px;" onclick="toggleCalendarTool()">
                    <div class="toggle-switch {{ ($agent->enable_calendar_tool ?? false) ? 'on' : '' }}" id="calendarToolSwitch"></div>
                    <div style="margin-left:10px;">
                        <div style="font-size:13px;font-weight:700;color:#1a1d23;" id="calendarToolLabel">
                            {{ ($agent->enable_calendar_tool ?? false) ? 'Agenda Google Calendar Ativada' : 'Agenda Google Calendar Desativada' }}
                        </div>
                        <div style="font-size:11px;color:#9ca3af;">O agente pode criar, reagendar e cancelar eventos no Google Calendar conforme a conversa</div>
                    </div>
                </div>
                <input type="hidden" name="enable_calendar_tool" id="calendarToolInput" value="{{ ($agent->enable_calendar_tool ?? false) ? '1' : '0' }}">

                {{-- Instruções de agenda (visível só quando habilitado) --}}
                <div id="calendarToolOptions" style="{{ ($agent->enable_calendar_tool ?? false) ? '' : 'display:none' }}">
                    <div style="margin-top:12px;">
                        <label class="form-label fw-semibold" style="font-size:13px;">Como o agente deve usar a agenda</label>
                        <textarea name="calendar_tool_instructions"
                                  class="form-control"
                                  rows="4"
                                  maxlength="2000"
                                  placeholder="Ex: Quando o usuário pedir para marcar uma reunião, verifique os eventos já agendados e crie o evento. Reuniões têm 1 hora de duração por padrão. Sempre confirme o horário com o usuário antes de criar."
                                  style="font-size:13px;resize:vertical;">{{ old('calendar_tool_instructions', $agent->calendar_tool_instructions ?? '') }}</textarea>
                        <div class="form-text" style="font-size:11px;color:#9ca3af;margin-top:4px;">
                            O agente receberá estas instruções no prompt. Certifique-se de ter conectado o Google Calendar em
                            <a href="{{ route('settings.integrations.index') }}" target="_blank" style="color:#0085f3;">Configurações → Integrações</a>.
                        </div>
                    </div>
                </div>

                {{-- Usuário de transferência --}}
                <div style="margin-top:16px;">
                    <label class="form-label fw-semibold" style="font-size:13px;">Atribuir conversa a (ao transferir)</label>
                    <select name="transfer_to_user_id" class="form-select form-select-sm" style="max-width:320px;">
                        <option value="">— Nenhum (sem atribuição automática) —</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}"
                                {{ old('transfer_to_user_id', $agent->transfer_to_user_id ?? '') == $u->id ? 'selected' : '' }}>
                                {{ $u->name }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text" style="font-size:11px;color:#9ca3af;">Quando o agente usar "assign_human", a conversa será atribuída a este usuário e o IA desativado.</div>
                </div>
            </div>
        </div>

        {{-- 7. Configurações Avançadas --}}
        <div class="section-card">
            <div class="section-card-header" onclick="toggleSection('advanced')">
                <div class="section-icon"><i class="bi bi-sliders"></i></div>
                <div class="section-card-title">7. Configurações Avançadas</div>
                <i class="bi bi-chevron-down chevron" id="chevron-advanced"></i>
            </div>
            <div class="section-card-body collapsed" id="body-advanced">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Tamanho Máx. de Mensagem (caracteres)</label>
                        <input type="number" name="max_message_length" class="form-control"
                               value="{{ old('max_message_length', $agent->max_message_length ?? 500) }}"
                               min="50" max="4000" step="50">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Delay entre mensagens (segundos)</label>
                        <input type="number" name="response_delay_seconds" class="form-control"
                               value="{{ old('response_delay_seconds', $agent->response_delay_seconds ?? 2) }}"
                               min="0" max="30"
                               title="Pausa entre cada parte da resposta (quando dividida em múltiplas mensagens)">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Tempo de espera para batching (segundos)</label>
                        <input type="number" name="response_wait_seconds" class="form-control"
                               value="{{ old('response_wait_seconds', $agent->response_wait_seconds ?? 0) }}"
                               min="0" max="30"
                               title="Aguardar X segundos antes de processar, para agrupar mensagens enviadas em sequência. 0 = sem espera.">
                        <div style="font-size:11px;color:#9ca3af;margin-top:4px;">
                            Quando o usuário manda várias mensagens seguidas, o agente aguarda este tempo antes de responder, processando todas juntas.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 8. Follow-up Automático --}}
        <div class="section-card">
            <div class="section-card-header" onclick="toggleSection('followup')">
                <div class="section-icon"><i class="bi bi-arrow-repeat"></i></div>
                <div class="section-card-title">8. Follow-up Automático</div>
                <i class="bi bi-chevron-down chevron" id="chevron-followup"></i>
            </div>
            <div class="section-card-body collapsed" id="body-followup">
                {{-- Toggle followup_enabled --}}
                <div class="toggle-wrap" onclick="toggleFollowup()" style="margin-bottom:16px;">
                    <div class="toggle-switch {{ ($agent->followup_enabled ?? false) ? 'on' : '' }}" id="followupSwitch"></div>
                    <div style="margin-left:10px;">
                        <div style="font-size:13px;font-weight:700;color:#1a1d23;" id="followupLabel">
                            {{ ($agent->followup_enabled ?? false) ? 'Follow-up Ativado' : 'Follow-up Desativado' }}
                        </div>
                        <div style="font-size:11px;color:#9ca3af;">Quando o cliente para de responder, o agente retoma o contato automaticamente</div>
                    </div>
                </div>
                <input type="hidden" name="followup_enabled" id="followupInput" value="{{ ($agent->followup_enabled ?? false) ? '1' : '0' }}">

                {{-- Opções (visíveis só quando habilitado) --}}
                <div id="followupOptions" style="{{ ($agent->followup_enabled ?? false) ? '' : 'display:none' }}">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Intervalo entre tentativas (minutos)</label>
                            <input type="number" name="followup_delay_minutes" class="form-control"
                                   value="{{ old('followup_delay_minutes', $agent->followup_delay_minutes ?? 40) }}"
                                   min="5" max="1440">
                            <div style="font-size:11px;color:#9ca3af;margin-top:4px;">Padrão: 40 minutos</div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Máximo de tentativas por conversa</label>
                            <input type="number" name="followup_max_count" class="form-control"
                                   value="{{ old('followup_max_count', $agent->followup_max_count ?? 3) }}"
                                   min="1" max="10">
                            <div style="font-size:11px;color:#9ca3af;margin-top:4px;">Após este limite a conversa é ignorada</div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Horário comercial — início (hora)</label>
                            <input type="number" name="followup_hour_start" class="form-control"
                                   value="{{ old('followup_hour_start', $agent->followup_hour_start ?? 8) }}"
                                   min="0" max="23">
                            <div style="font-size:11px;color:#9ca3af;margin-top:4px;">Ex: 8 = a partir das 08:00</div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Horário comercial — fim (hora)</label>
                            <input type="number" name="followup_hour_end" class="form-control"
                                   value="{{ old('followup_hour_end', $agent->followup_hour_end ?? 18) }}"
                                   min="1" max="23">
                            <div style="font-size:11px;color:#9ca3af;margin-top:4px;">Ex: 18 = até as 18:59</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-footer">
            <button type="submit" class="btn-primary">
                <i class="bi bi-floppy"></i> {{ $isEdit ? 'Salvar alterações' : 'Criar Agente' }}
            </button>
            <a href="{{ route('ai.agents.index') }}" class="btn-cancel">Cancelar</a>
            @if($isEdit)
            <button type="button" class="btn-cancel" style="margin-left:auto;" onclick="toggleTestChat()">
                <i class="bi bi-chat-dots"></i> Testar Agente
            </button>
            @endif
        </div>

    </form>

    @if($isEdit)
    {{-- Widget de teste --}}
    <div class="test-chat-panel" id="testChatPanel" style="display:none;">
        <div class="test-chat-header" onclick="toggleTestChat()">
            <span class="test-chat-title"><i class="bi bi-robot"></i> Testar: {{ $agent->name }}</span>
            <i class="bi bi-chevron-down test-chat-toggle" id="testChatChevron"></i>
        </div>
        <div class="test-chat-body" id="testChatBody">
            <div class="chat-bubble agent">Olá! Sou {{ $agent->name }}. Como posso ajudar?</div>
        </div>
        <div class="test-chat-input-wrap" id="testInputWrap">
            <input type="text" class="test-chat-input" id="testInput"
                   placeholder="Digite uma mensagem..."
                   onkeydown="if(event.key==='Enter'){event.preventDefault();sendTest();}">
            <button class="test-send-btn" id="testSendBtn" onclick="sendTest()">
                <i class="bi bi-send"></i>
            </button>
        </div>
    </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
const AGENT_ID  = {{ $agent->id ?? 'null' }};
const CSRF      = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
const KB_UPLOAD = '{{ $isEdit ? route('ai.agents.knowledge-files.store', $agent) : '' }}';
const KB_DELETE = '{{ $isEdit ? url('/ia/agentes/' . $agent->id . '/knowledge-files') : '' }}';

/* ── Knowledge Files ── */
function fileIcon(mime) {
    if (mime.startsWith('image/')) return '<i class="bi bi-file-earmark-image" style="color:#8b5cf6;font-size:22px;"></i>';
    if (mime === 'application/pdf') return '<i class="bi bi-file-earmark-pdf" style="color:#ef4444;font-size:22px;"></i>';
    return '<i class="bi bi-file-earmark-text" style="color:#3B82F6;font-size:22px;"></i>';
}

function handleKbDrop(e) {
    e.preventDefault();
    const dz = document.getElementById('kbDropzone');
    dz.style.borderColor = '#d1d5db';
    dz.style.background  = '';
    const file = e.dataTransfer.files[0];
    if (file) uploadKbFile(file);
}

async function uploadKbFile(file) {
    if (!file || !AGENT_ID) return;

    const list = document.getElementById('kbFileList');
    const tmpId = 'tmp-' + Date.now();

    // Placeholder carregando
    const tmpEl = document.createElement('div');
    tmpEl.className = 'kb-uploading';
    tmpEl.id = tmpId;
    tmpEl.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Fazendo upload e extraindo conteúdo de <strong>' + escapeHtml(file.name) + '</strong>…';
    list.prepend(tmpEl);

    const fd = new FormData();
    fd.append('file', file);
    fd.append('_token', CSRF);

    try {
        const res  = await fetch(KB_UPLOAD, { method: 'POST', body: fd });
        const data = await res.json();
        tmpEl.remove();

        if (!res.ok) {
            toastr.error(data.message ?? 'Erro ao fazer upload.', 'Erro');
            return;
        }

        // Montar HTML do novo arquivo
        let badgeHtml = '';
        if (data.status === 'done') {
            badgeHtml = '<span class="kb-status-badge done">Extraído</span>';
            if (data.preview) {
                badgeHtml += ' <button type="button" class="kb-preview-btn" onclick="toggleKbPreview(' + data.id + ')"><i class="bi bi-eye"></i> Ver prévia</button>';
            }
        } else if (data.status === 'failed') {
            badgeHtml = '<span class="kb-status-badge failed">Falhou</span>';
            if (data.error_message) badgeHtml += '<span style="font-size:11px;color:#ef4444;display:block;margin-top:2px;">' + escapeHtml(data.error_message) + '</span>';
        } else {
            badgeHtml = '<span class="kb-status-badge pending">Pendente</span>';
        }

        const itemEl = document.createElement('div');
        itemEl.className = 'kb-file-item';
        itemEl.id = 'kb-file-' + data.id;
        itemEl.innerHTML = `
            <div class="kb-file-icon">${fileIcon(data.mime_type ?? '')}</div>
            <div class="kb-file-info">
                <div class="kb-file-name">${escapeHtml(data.original_name)}</div>
                ${badgeHtml}
            </div>
            <button type="button" class="kb-del-btn" onclick="deleteKbFile(${data.id}, '${escapeHtml(data.original_name)}')" title="Remover">
                <i class="bi bi-trash3"></i>
            </button>`;
        list.prepend(itemEl);

        if (data.preview) {
            const prevEl = document.createElement('div');
            prevEl.className = 'kb-file-preview';
            prevEl.id = 'kb-preview-' + data.id;
            prevEl.style.display = 'none';
            prevEl.textContent = data.preview;
            itemEl.insertAdjacentElement('afterend', prevEl);
        }

        if (data.status === 'done') toastr.success('Arquivo processado com sucesso!', 'OK');
        else if (data.status === 'failed') toastr.warning('Extração falhou. Veja o motivo na lista.', 'Atenção');
    } catch (err) {
        tmpEl.remove();
        toastr.error('Erro de rede. Tente novamente.', 'Erro');
    }

    // Reset input
    document.getElementById('kbFileInput').value = '';
}

function toggleKbPreview(id) {
    const el = document.getElementById('kb-preview-' + id);
    if (el) el.style.display = el.style.display === 'none' ? 'block' : 'none';
}

async function deleteKbFile(id, name) {
    if (!confirm('Remover "' + name + '" da base de conhecimento?')) return;

    try {
        const res = await fetch(KB_DELETE + '/' + id, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        });
        if (!res.ok) { toastr.error('Erro ao remover arquivo.'); return; }

        document.getElementById('kb-file-' + id)?.remove();
        document.getElementById('kb-preview-' + id)?.remove();
        toastr.success('Arquivo removido.', 'OK');
    } catch {
        toastr.error('Erro de rede.', 'Erro');
    }
}
let testHistory = [];
let testChatOpen = false;

/* ── Canal ── */
function updateChannelCards() {
    const selected = document.querySelector('input[name="channel"]:checked')?.value;
    document.querySelectorAll('.channel-card').forEach(card => {
        card.classList.toggle('selected', card.dataset.channel === selected);
    });
}

/* ── Toggle ativo ── */
function toggleActive() {
    const sw    = document.getElementById('toggleSwitch');
    const input = document.getElementById('isActiveInput');
    const label = document.getElementById('toggleLabel');
    const isOn  = input.value === '1';
    input.value = isOn ? '0' : '1';
    sw.classList.toggle('on', !isOn);
    label.textContent = isOn ? 'Agente Inativo' : 'Agente Ativo';
}

/* ── Toggle auto-assign ── */
function toggleAutoAssign() {
    const sw    = document.getElementById('autoAssignSwitch');
    const input = document.getElementById('autoAssignInput');
    const label = document.getElementById('autoAssignLabel');
    const isOn  = input.value === '1';
    input.value = isOn ? '0' : '1';
    sw.classList.toggle('on', !isOn);
    label.textContent = isOn ? 'Auto-assign Desativado' : 'Auto-assign Ativado';
}

function togglePipelineTool() {
    const sw    = document.getElementById('pipelineToolSwitch');
    const input = document.getElementById('pipelineToolInput');
    const label = document.getElementById('pipelineToolLabel');
    const isOn  = input.value === '1';
    input.value = isOn ? '0' : '1';
    sw.classList.toggle('on', !isOn);
    label.textContent = isOn ? 'Controle de Funil Desativado' : 'Controle de Funil Ativado';
}

function toggleTagsTool() {
    const sw    = document.getElementById('tagsToolSwitch');
    const input = document.getElementById('tagsToolInput');
    const label = document.getElementById('tagsToolLabel');
    const isOn  = input.value === '1';
    input.value = isOn ? '0' : '1';
    sw.classList.toggle('on', !isOn);
    label.textContent = isOn ? 'Atribuição de Tags Desativada' : 'Atribuição de Tags Ativada';
}

function toggleIntentNotify() {
    const sw    = document.getElementById('intentNotifySwitch');
    const input = document.getElementById('intentNotifyInput');
    const label = document.getElementById('intentNotifyLabel');
    const isOn  = input.value === '1';
    input.value = isOn ? '0' : '1';
    sw.classList.toggle('on', !isOn);
    label.textContent = isOn ? 'Detecção de Intenção Desativada' : 'Detecção de Intenção Ativada';
}

function toggleCalendarTool() {
    const sw      = document.getElementById('calendarToolSwitch');
    const input   = document.getElementById('calendarToolInput');
    const label   = document.getElementById('calendarToolLabel');
    const options = document.getElementById('calendarToolOptions');
    const isOn    = input.value === '1';
    input.value = isOn ? '0' : '1';
    sw.classList.toggle('on', !isOn);
    label.textContent = isOn ? 'Agenda Google Calendar Desativada' : 'Agenda Google Calendar Ativada';
    options.style.display = isOn ? 'none' : '';
}

function toggleFollowup() {
    const sw      = document.getElementById('followupSwitch');
    const input   = document.getElementById('followupInput');
    const label   = document.getElementById('followupLabel');
    const options = document.getElementById('followupOptions');
    const isOn    = input.value === '1';
    input.value = isOn ? '0' : '1';
    sw.classList.toggle('on', !isOn);
    label.textContent = isOn ? 'Follow-up Desativado' : 'Follow-up Ativado';
    options.style.display = isOn ? 'none' : '';
}

/* ── Sections ── */
function toggleSection(id) {
    const body    = document.getElementById('body-' + id);
    const chevron = document.getElementById('chevron-' + id);
    const isOpen  = !body.classList.contains('collapsed');
    body.classList.toggle('collapsed', isOpen);
    chevron.classList.toggle('open', !isOpen);
}

/* ── Etapas dinâmicas ── */
let stageCount = {{ count(old('conversation_stages', $agent->conversation_stages ?? [])) }};

function addStage() {
    const i    = stageCount++;
    const list = document.getElementById('stagesList');
    list.insertAdjacentHTML('beforeend', `
        <div class="stage-item" data-index="${i}">
            <div class="stage-num">${list.children.length + 1}</div>
            <div class="stage-inputs">
                <input type="text" name="conversation_stages[${i}][name]"
                       class="form-control" style="min-height:unset;"
                       placeholder="Nome da etapa">
                <input type="text" name="conversation_stages[${i}][description]"
                       class="form-control" style="min-height:unset;"
                       placeholder="Descrição (opcional)">
            </div>
            <button type="button" class="stage-del" onclick="removeStage(this)">×</button>
        </div>
    `);
    renumberStages();
}

function removeStage(btn) {
    btn.closest('.stage-item').remove();
    renumberStages();
}

function renumberStages() {
    document.querySelectorAll('#stagesList .stage-item').forEach((el, i) => {
        el.querySelector('.stage-num').textContent = i + 1;
    });
}

/* ── Chat de Teste ── */
function toggleTestChat() {
    const panel = document.getElementById('testChatPanel');
    testChatOpen = !testChatOpen;
    panel.style.display = testChatOpen ? 'flex' : 'none';
    if (testChatOpen) {
        setTimeout(() => document.getElementById('testInput').focus(), 100);
    }
}

function appendBubble(role, text) {
    const body   = document.getElementById('testChatBody');
    const bubble = document.createElement('div');
    bubble.className = 'chat-bubble ' + role;
    bubble.textContent = text;
    body.appendChild(bubble);
    body.scrollTop = body.scrollHeight;
    return bubble;
}

async function sendTest() {
    const input = document.getElementById('testInput');
    const msg   = input.value.trim();
    if (!msg || !AGENT_ID) return;
    input.value = '';

    appendBubble('user', msg);
    const typingBubble = appendBubble('agent typing', '…');

    document.getElementById('testSendBtn').disabled = true;

    try {
        const res  = await fetch(`/ia/agentes/${AGENT_ID}/test-chat`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ message: msg, history: testHistory }),
        });
        const data = await res.json();

        typingBubble.remove();

        if (data.success) {
            appendBubble('agent', data.reply);
            testHistory.push({ role: 'user',  content: msg });
            testHistory.push({ role: 'agent', content: data.reply });
            // Mantém histórico máximo de 20 trocas
            if (testHistory.length > 40) testHistory = testHistory.slice(-40);
        } else {
            appendBubble('agent', '⚠️ Erro: ' + (data.message || 'Falha ao obter resposta.'));
        }
    } catch (e) {
        typingBubble.remove();
        appendBubble('agent', '⚠️ Erro de conexão.');
    } finally {
        document.getElementById('testSendBtn').disabled = false;
        input.focus();
    }
}
</script>
@endpush

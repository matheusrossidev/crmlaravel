@extends('tenant.layouts.app')

@php
    $isEdit   = $flow->exists;
    $title    = $isEdit ? __('chatbot.form_title_edit') : __('chatbot.form_title_new');
    $pageIcon = 'diagram-3';
@endphp

@section('topbar_actions')
<div class="topbar-actions">
    <a href="{{ route('chatbot.flows.index') }}" style="color:#2563eb;font-size:13.5px;font-weight:500;text-decoration:underline;display:inline-flex;align-items:center;gap:5px;">
        <i class="bi bi-arrow-left" style="font-size:12px;"></i> {{ __('chatbot.form_back') }}
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
            <div class="form-section-label">{{ __('chatbot.form_section_channel') }}</div>

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

            {{-- Instância WhatsApp (só visível se channel=whatsapp) --}}
            @php
                $waInstances = \App\Models\WhatsappInstance::query()
                    ->where('status', 'connected')
                    ->orderByDesc('is_primary')
                    ->orderBy('label')
                    ->get();
                $currentInstanceId = old('whatsapp_instance_id', $flow->whatsapp_instance_id ?? '');
            @endphp
            <div id="waInstanceRow" class="form-group" style="{{ $currentChannel === 'whatsapp' ? '' : 'display:none;' }}">
                <label>Aplicar em qual número?</label>
                <select name="whatsapp_instance_id" class="field-input">
                    <option value="">Todas as instâncias WhatsApp do tenant</option>
                    @foreach($waInstances as $inst)
                        <option value="{{ $inst->id }}" {{ (string) $currentInstanceId === (string) $inst->id ? 'selected' : '' }}>
                            {{ $inst->label ?: $inst->phone_number }}
                            @if($inst->isCloudApi()) · Cloud API Oficial @else · WAHA @endif
                            @if($inst->supportsTemplates()) · templates @endif
                        </option>
                    @endforeach
                </select>
                <div style="font-size:11.5px;color:#9ca3af;margin-top:4px;line-height:1.4;">
                    Deixe "Todas" se esse flow vale pra qualquer número. Escolha específico se você quer flows diferentes por número (ex: comercial vs suporte).
                </div>
            </div>

            {{-- Identificação --}}
            <div class="form-section-label">{{ __('chatbot.form_section_identification') }}</div>

            <div class="form-group">
                <label>{{ __('chatbot.form_flow_name') }} <span style="color:#EF4444;">*</span></label>
                <input type="text" name="name"
                    class="field-input {{ $errors->has('name') ? 'is-invalid' : '' }}"
                    value="{{ old('name', $flow->name) }}"
                    placeholder="{{ __('chatbot.form_flow_name_placeholder') }}"
                    required>
                @error('name')
                    <div class="field-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label>{{ __('chatbot.form_description') }}</label>
                <textarea name="description" class="field-input" rows="2"
                    placeholder="{{ __('chatbot.form_description_placeholder') }}">{{ old('description', $flow->description) }}</textarea>
            </div>

            {{-- Slug (URL pública) — só para website --}}
            <div id="slug-field" style="{{ ($currentChannel === 'website') ? '' : 'display:none;' }}">
                <div class="form-group" style="margin-top:14px;">
                    <label>{{ __('chatbot.form_slug') }}</label>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <input type="text" name="slug" class="field-input" style="flex:1;"
                            value="{{ old('slug', $flow->slug) }}"
                            placeholder="{{ __('chatbot.form_slug_placeholder') }}"
                            oninput="updateSlugPreview(this.value)">
                    </div>
                    <div class="hint" style="margin-top:6px;">
                        <span id="slug-preview-url" style="color:#6366f1;">
                            @php
                                $tenantSlug = auth()->user()->tenant->slug ?? 'empresa';
                                $currentSlug = old('slug', $flow->slug ?? 'meu-chatbot');
                            @endphp
                            {{ config('app.url') }}/chat/{{ $tenantSlug }}/{{ $currentSlug }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Widget Website --}}
            <div id="website-settings" style="{{ ($currentChannel === 'website') ? '' : 'display:none;' }}">
                <div class="form-section-label">{{ __('chatbot.form_section_widget') }}</div>

                <div class="form-group">
                    <label>{{ __('chatbot.form_bot_name') }}</label>
                    <input type="text" name="bot_name" class="field-input"
                        value="{{ old('bot_name', $flow->bot_name) }}"
                        placeholder="{{ __('chatbot.form_bot_name_placeholder') }}">
                    <div class="hint">{{ __('chatbot.form_bot_name_hint') }}</div>
                </div>

                <div class="form-group">
                    <label>{{ __('chatbot.form_bot_avatar') }}</label>
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
                    <div class="hint" style="margin-top:8px;">{{ __('chatbot.form_bot_avatar_hint') }}</div>
                </div>

                <div class="form-group">
                    <label>{{ __('chatbot.form_welcome_message') }}</label>
                    <textarea name="welcome_message" class="field-input" rows="2"
                        placeholder="{{ __('chatbot.form_welcome_placeholder') }}">{{ old('welcome_message', $flow->welcome_message) }}</textarea>
                    <div class="hint">{!! __('chatbot.form_welcome_hint') !!}</div>
                </div>

                <div class="form-group">
                    <label>{{ __('chatbot.form_widget_type') }}</label>
                    @php $currentType = old('widget_type', $flow->widget_type ?? 'bubble'); @endphp
                    <div style="display:flex;gap:12px;margin-top:4px;" id="widget-type-cards">
                        <label class="widget-type-card {{ $currentType === 'bubble' ? 'selected' : '' }}" data-type="bubble" style="flex:1;cursor:pointer;border:1.5px solid {{ $currentType === 'bubble' ? '#0085f3' : '#e8eaf0' }};border-radius:10px;padding:12px 10px;text-align:center;background:{{ $currentType === 'bubble' ? '#eff6ff' : '#fff' }};transition:all .15s;">
                            <input type="radio" name="widget_type" value="bubble" {{ $currentType === 'bubble' ? 'checked' : '' }} style="display:none;">
                            <div style="margin-bottom:6px;display:flex;justify-content:center;">
                                <svg viewBox="0 0 24 24" style="width:28px;height:28px;fill:{{ $currentType === 'bubble' ? '#0085f3' : '#9ca3af' }};"><path d="M20 2H4C2.9 2 2 2.9 2 4v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>
                            </div>
                            <div style="font-size:13px;font-weight:700;color:#1a1d23;">{{ __('chatbot.form_widget_bubble') }}</div>
                            <div style="font-size:11px;color:#6b7280;margin-top:2px;">{{ __('chatbot.form_widget_bubble_desc') }}</div>
                        </label>
                        <label class="widget-type-card {{ $currentType === 'inline' ? 'selected' : '' }}" data-type="inline" style="flex:1;cursor:pointer;border:1.5px solid {{ $currentType === 'inline' ? '#0085f3' : '#e8eaf0' }};border-radius:10px;padding:12px 10px;text-align:center;background:{{ $currentType === 'inline' ? '#eff6ff' : '#fff' }};transition:all .15s;">
                            <input type="radio" name="widget_type" value="inline" {{ $currentType === 'inline' ? 'checked' : '' }} style="display:none;">
                            <div style="margin-bottom:6px;display:flex;justify-content:center;">
                                <svg viewBox="0 0 24 24" style="width:28px;height:28px;fill:{{ $currentType === 'inline' ? '#0085f3' : '#9ca3af' }};"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 14H4V6h16v12zM6 10h4v2H6zm0-3h12v2H6zm0 6h8v2H6z"/></svg>
                            </div>
                            <div style="font-size:13px;font-weight:700;color:#1a1d23;">{{ __('chatbot.form_widget_inline') }}</div>
                            <div style="font-size:11px;color:#6b7280;margin-top:2px;">{{ __('chatbot.form_widget_inline_desc') }}</div>
                        </label>
                    </div>
                    <div id="bubble-type-hint" class="hint" style="{{ $currentType !== 'inline' ? '' : 'display:none;' }}">
                        {!! __('chatbot.form_bubble_hint') !!}
                    </div>
                    <div id="inline-type-hint" class="hint" style="{{ $currentType === 'inline' ? '' : 'display:none;' }}">
                        {!! __('chatbot.form_inline_hint') !!}
                    </div>
                </div>

                <div class="form-group">
                    <label>{{ __('chatbot.form_button_color') }}</label>
                    @php $currentColor = old('widget_color', $flow->widget_color ?? '#0085f3'); @endphp
                    <div style="display:flex;align-items:center;gap:10px;">
                        <input type="color" name="widget_color" id="widget-color-picker"
                            value="{{ $currentColor }}"
                            style="width:42px;height:36px;padding:2px;border:1.5px solid #e8eaf0;border-radius:8px;cursor:pointer;background:#fafafa;"
                            oninput="document.getElementById('widget-color-hex').value = this.value">
                        <input type="text" id="widget-color-hex" class="field-input" style="width:100px;text-align:center;font-family:monospace;"
                            value="{{ $currentColor }}" maxlength="7"
                            oninput="if(/^#[0-9a-fA-F]{6}$/.test(this.value)) document.getElementById('widget-color-picker').value = this.value">
                    </div>
                    <div class="hint">{{ __('chatbot.form_button_color_hint') }}</div>
                </div>
            </div>

            {{-- Disparo --}}
            <div class="form-section-label">{{ __('chatbot.form_section_trigger') }}</div>

            {{-- Trigger type (Instagram only) --}}
            @php
                $currentTriggerType = old('trigger_type', $flow->trigger_type ?? 'keyword');
            @endphp
            <div id="triggerTypeWrap" style="{{ $currentChannel === 'instagram' ? '' : 'display:none;' }}">
                <div class="form-group">
                    <label style="font-weight:600;margin-bottom:8px;display:block;">{{ __('chatbot.trigger_type_label') }}</label>
                    <div style="display:flex;gap:10px;">
                        <label style="flex:1;cursor:pointer;">
                            <input type="radio" name="trigger_type" value="keyword" {{ $currentTriggerType === 'keyword' ? 'checked' : '' }}
                                   style="display:none;" onchange="toggleTriggerType()">
                            <div class="trigger-type-card {{ $currentTriggerType === 'keyword' ? 'selected' : '' }}"
                                 style="display:flex;align-items:center;gap:8px;padding:12px 14px;border:2px solid #e8eaf0;border-radius:10px;background:#fafafa;color:#6b7280;font-size:12.5px;font-weight:600;transition:all .15s;cursor:pointer;">
                                <i class="bi bi-chat-dots" style="font-size:16px;"></i>
                                <div>
                                    <div style="color:#1a1d23;">{{ __('chatbot.trigger_type_keyword') }}</div>
                                    <div style="font-weight:400;font-size:11px;margin-top:2px;">{{ __('chatbot.trigger_type_keyword_desc') }}</div>
                                </div>
                            </div>
                        </label>
                        <label style="flex:1;cursor:pointer;">
                            <input type="radio" name="trigger_type" value="instagram_comment" {{ $currentTriggerType === 'instagram_comment' ? 'checked' : '' }}
                                   style="display:none;" onchange="toggleTriggerType()">
                            <div class="trigger-type-card {{ $currentTriggerType === 'instagram_comment' ? 'selected' : '' }}"
                                 style="display:flex;align-items:center;gap:8px;padding:12px 14px;border:2px solid #e8eaf0;border-radius:10px;background:#fafafa;color:#6b7280;font-size:12.5px;font-weight:600;transition:all .15s;cursor:pointer;">
                                <i class="bi bi-chat-left-heart" style="font-size:16px;"></i>
                                <div>
                                    <div style="color:#1a1d23;">{{ __('chatbot.trigger_type_comment') }}</div>
                                    <div style="font-weight:400;font-size:11px;margin-top:2px;">{{ __('chatbot.trigger_type_comment_desc') }}</div>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            {{-- Comment trigger settings (Instagram comment only) --}}
            <div id="commentTriggerWrap" style="{{ ($currentChannel === 'instagram' && $currentTriggerType === 'instagram_comment') ? '' : 'display:none;' }}">
                {{-- Post/Reel selector --}}
                <div class="form-group">
                    <label style="font-weight:600;">{{ __('chatbot.trigger_post_label') }}</label>
                    <div style="display:flex;gap:10px;margin-bottom:10px;">
                        <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;">
                            <input type="radio" name="comment_scope" value="all" {{ empty($flow->trigger_media_id) ? 'checked' : '' }}
                                   onchange="toggleCommentScope()" style="accent-color:#0085f3;">
                            {{ __('chatbot.trigger_post_any') }}
                        </label>
                        <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;">
                            <input type="radio" name="comment_scope" value="specific" {{ !empty($flow->trigger_media_id) ? 'checked' : '' }}
                                   onchange="toggleCommentScope()" style="accent-color:#0085f3;">
                            {{ __('chatbot.trigger_post_specific') }}
                        </label>
                    </div>
                    <div id="commentPostPicker" style="{{ !empty($flow->trigger_media_id) ? '' : 'display:none;' }}">
                        <div id="commentPostGrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(80px,1fr));gap:8px;max-height:240px;overflow-y:auto;margin-bottom:10px;"></div>
                        <button type="button" onclick="loadCommentPosts()" style="padding:6px 14px;background:#eff6ff;color:#0085f3;border:1.5px solid #bfdbfe;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;">
                            <i class="bi bi-arrow-clockwise"></i> {{ __('chatbot.trigger_load_posts') }}
                        </button>
                    </div>
                    <input type="hidden" name="trigger_media_id" id="triggerMediaId" value="{{ old('trigger_media_id', $flow->trigger_media_id ?? '') }}">
                    <input type="hidden" name="trigger_media_thumbnail" id="triggerMediaThumb" value="{{ old('trigger_media_thumbnail', $flow->trigger_media_thumbnail ?? '') }}">
                    <input type="hidden" name="trigger_media_caption" id="triggerMediaCaption" value="{{ old('trigger_media_caption', $flow->trigger_media_caption ?? '') }}">
                </div>

                {{-- Reply on comment --}}
                <div class="form-group">
                    <label>{{ __('chatbot.trigger_reply_comment') }}</label>
                    <textarea name="trigger_reply_comment" class="field-input" rows="2" maxlength="2200"
                        placeholder="{{ __('chatbot.trigger_reply_comment_ph') }}">{{ old('trigger_reply_comment', $flow->trigger_reply_comment ?? '') }}</textarea>
                    <div class="hint">{{ __('chatbot.trigger_reply_comment_hint') }}</div>
                </div>
            </div>

            <div class="form-group">
                <label>{{ __('chatbot.form_trigger_keywords') }}</label>
                <input type="text" name="trigger_keywords" class="field-input"
                    value="{{ old('trigger_keywords', $flow->trigger_keywords ? implode(', ', $flow->trigger_keywords) : '') }}"
                    placeholder="{{ __('chatbot.form_trigger_placeholder') }}"
                    id="triggerKeywordsInput">
                <div class="hint" id="triggerKeywordsHint">{{ __('chatbot.form_trigger_hint') }}</div>
            </div>

            <div class="form-group">
                <div class="switch-row">
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" name="is_catch_all" value="1"
                            id="isCatchAll" style="width:38px;height:20px;cursor:pointer;"
                            {{ old('is_catch_all', $flow->is_catch_all ?? false) ? 'checked' : '' }}
                            onchange="document.getElementById('triggerKeywordsInput').disabled = this.checked;">
                    </div>
                    <div>
                        <label for="isCatchAll" style="font-weight:600;">Responder qualquer mensagem</label>
                        <div class="switch-desc" style="font-size:12px;color:#9ca3af;">
                            Quando ativo, o fluxo dispara para qualquer mensagem recebida (não precisa de palavra-chave).
                            Funciona como fallback: só dispara se nenhum outro fluxo com keyword específica bateu.
                        </div>
                    </div>
                </div>
            </div>

            {{-- Variáveis --}}
            <div class="form-section-label">{{ __('chatbot.form_section_variables') }}</div>

            <div class="form-group">
                <label>{{ __('chatbot.form_variables') }}</label>
                <input type="text" name="variables" class="field-input"
                    value="{{ old('variables', $flow->variables ? implode(', ', array_column($flow->variables, 'name')) : '') }}"
                    placeholder="{{ __('chatbot.form_variables_placeholder') }}">
                <div class="hint">
                    {!! __('chatbot.form_variables_hint') !!}
                </div>
            </div>

            @if($isEdit)
            {{-- Status --}}
            <div class="form-section-label">{{ __('chatbot.form_section_status') }}</div>

            <div class="form-group">
                <div class="switch-row">
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1"
                            id="isActive" style="width:38px;height:20px;cursor:pointer;"
                            {{ old('is_active', $flow->is_active) ? 'checked' : '' }}>
                    </div>
                    <div>
                        <label for="isActive">{{ __('chatbot.form_active_label') }}</label>
                        <div class="switch-desc">{{ __('chatbot.form_active_hint') }}</div>
                    </div>
                </div>
            </div>
            @endif

            <div class="form-actions">
                <button type="submit" class="btn-form-primary">
                    @if($isEdit)
                        <i class="bi bi-check-lg"></i> {{ __('chatbot.form_save_changes') }}
                    @else
                        <i class="bi bi-arrow-right-circle"></i> {{ __('chatbot.form_create_and_edit') }}
                    @endif
                </button>
                <a href="{{ route('chatbot.flows.index') }}" class="btn-form-secondary">
                    {{ __('chatbot.form_cancel') }}
                </a>
            </div>
        </form>
    </div>

    @if($isEdit)
    <div class="open-builder-row">
        <a href="{{ route('chatbot.flows.edit', $flow) }}" class="btn-open-builder">
            <i class="bi bi-diagram-3"></i> {{ __('chatbot.form_open_builder') }}
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
    const isWebsite = selected === 'website';
    const isInstagram = selected === 'instagram';
    const isWhatsapp = selected === 'whatsapp';
    const ws = document.getElementById('website-settings');
    const sf = document.getElementById('slug-field');
    const wir = document.getElementById('waInstanceRow');
    if (ws) ws.style.display = isWebsite ? 'block' : 'none';
    if (sf) sf.style.display = isWebsite ? 'block' : 'none';
    if (wir) wir.style.display = isWhatsapp ? '' : 'none';

    // Show/hide trigger type for Instagram
    const ttw = document.getElementById('triggerTypeWrap');
    if (ttw) ttw.style.display = isInstagram ? '' : 'none';
    if (!isInstagram) {
        // Reset to keyword when switching away from Instagram
        const kwRadio = document.querySelector('input[name="trigger_type"][value="keyword"]');
        if (kwRadio) kwRadio.checked = true;
        const ctw = document.getElementById('commentTriggerWrap');
        if (ctw) ctw.style.display = 'none';
    }
    toggleTriggerType();
}

function toggleTriggerType() {
    const type = document.querySelector('input[name="trigger_type"]:checked')?.value || 'keyword';
    document.querySelectorAll('.trigger-type-card').forEach(card => {
        const radio = card.closest('label').querySelector('input');
        card.style.borderColor = radio?.checked ? '#0085f3' : '#e8eaf0';
        card.style.background = radio?.checked ? '#eff6ff' : '#fafafa';
        card.style.color = radio?.checked ? '#0085f3' : '#6b7280';
    });
    const ctw = document.getElementById('commentTriggerWrap');
    if (ctw) ctw.style.display = type === 'instagram_comment' ? '' : 'none';

    // Update hint text
    const hint = document.getElementById('triggerKeywordsHint');
    if (hint) {
        hint.textContent = type === 'instagram_comment'
            ? 'Palavras no comentário que disparam o fluxo. Ex: quero, preço, link. Deixe vazio para disparar em qualquer comentário.'
            : '{{ __("chatbot.form_trigger_hint") }}';
    }
}

function toggleCommentScope() {
    const scope = document.querySelector('input[name="comment_scope"]:checked')?.value;
    const picker = document.getElementById('commentPostPicker');
    if (picker) picker.style.display = scope === 'specific' ? '' : 'none';
    if (scope === 'all') {
        document.getElementById('triggerMediaId').value = '';
        document.getElementById('triggerMediaThumb').value = '';
        document.getElementById('triggerMediaCaption').value = '';
    }
}

let _commentPostsLoaded = false;
function loadCommentPosts(after) {
    const grid = document.getElementById('commentPostGrid');
    if (!after) { grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:16px;color:#9ca3af;font-size:12px;">Carregando...</div>'; }

    const url = '{{ route("settings.ig-automations.posts") }}' + (after ? '?after=' + after : '');
    window.API.get(url).then(res => {
        if (!after) grid.innerHTML = '';
        const preselectId = document.getElementById('triggerMediaId').value;
        (res.data || []).forEach(post => {
            const div = document.createElement('div');
            div.style.cssText = 'position:relative;aspect-ratio:1;border-radius:8px;overflow:hidden;border:2px solid ' + (post.id === preselectId ? '#0085f3' : '#e8eaf0') + ';cursor:pointer;';
            const img = post.thumbnail_url ? '<img src="' + post.thumbnail_url + '" style="width:100%;height:100%;object-fit:cover;" loading="lazy">' : '<div style="height:100%;display:flex;align-items:center;justify-content:center;background:#f3f4f6;color:#9ca3af;"><i class="bi bi-image"></i></div>';
            const badge = post.media_type === 'REEL' ? '<span style="position:absolute;top:3px;left:3px;background:rgba(124,58,237,.85);color:#fff;font-size:8px;font-weight:700;padding:1px 5px;border-radius:3px;">Reel</span>' : '';
            div.innerHTML = img + badge;
            div.onclick = () => {
                grid.querySelectorAll('div').forEach(d => d.style.borderColor = '#e8eaf0');
                div.style.borderColor = '#0085f3';
                document.getElementById('triggerMediaId').value = post.id;
                document.getElementById('triggerMediaThumb').value = post.thumbnail_url || '';
                document.getElementById('triggerMediaCaption').value = post.caption || '';
            };
            grid.appendChild(div);
        });
        if (res.next_cursor) {
            const more = document.createElement('div');
            more.style.cssText = 'grid-column:1/-1;text-align:center;';
            more.innerHTML = '<button type="button" onclick="loadCommentPosts(\'' + res.next_cursor + '\');this.parentElement.remove();" style="padding:5px 14px;background:#eff6ff;color:#0085f3;border:1px solid #bfdbfe;border-radius:6px;font-size:11px;font-weight:600;cursor:pointer;">Carregar mais</button>';
            grid.appendChild(more);
        }
        _commentPostsLoaded = true;
    }).catch(() => {
        grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:16px;color:#ef4444;font-size:12px;">Erro ao carregar publicações. Verifique a conexão com Instagram.</div>';
    });
}

function updateSlugPreview(val) {
    var slug = val.toLowerCase().replace(/[^a-z0-9-]/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '');
    var el = document.getElementById('slug-preview-url');
    if (el) el.textContent = '{{ config("app.url") }}/chat/{{ auth()->user()->tenant->slug ?? "empresa" }}/' + (slug || 'meu-chatbot');
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

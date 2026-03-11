@extends('tenant.layouts.app')
@php
    $title    = 'Notificações';
    $pageIcon = 'bell';
@endphp

@push('styles')
<style>
    .notif-card {
        background: #fff;
        border-radius: 14px;
        border: 1px solid #e8eaf0;
        overflow: hidden;
        margin-bottom: 20px;
    }
    .notif-card-header {
        padding: 16px 22px;
        border-bottom: 1px solid #f0f2f7;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 14px;
        font-weight: 700;
        color: #1a1d23;
    }
    .notif-card-header i { color: #0085f3; font-size: 16px; }
    .notif-card-body { padding: 22px; }

    /* Permission status */
    .perm-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 12.5px;
        font-weight: 600;
        padding: 4px 12px;
        border-radius: 20px;
    }
    .perm-status.granted { background: #ecfdf5; color: #059669; }
    .perm-status.denied { background: #fef2f2; color: #dc2626; }
    .perm-status.default { background: #eff6ff; color: #0085f3; }
    .perm-status.unsupported { background: #f3f4f6; color: #6b7280; }

    .perm-actions { margin-top: 14px; display: flex; gap: 10px; flex-wrap: wrap; }

    /* Toggle switch */
    .toggle-switch {
        position: relative;
        display: inline-block;
        width: 38px;
        height: 22px;
        flex-shrink: 0;
    }
    .toggle-switch input { opacity: 0; width: 0; height: 0; }
    .toggle-slider {
        position: absolute;
        cursor: pointer;
        inset: 0;
        background: #d1d5db;
        border-radius: 22px;
        transition: .2s;
    }
    .toggle-slider::before {
        content: '';
        position: absolute;
        width: 16px; height: 16px;
        left: 3px; bottom: 3px;
        background: #fff;
        border-radius: 50%;
        transition: .2s;
    }
    .toggle-switch input:checked + .toggle-slider { background: #0085f3; }
    .toggle-switch input:checked + .toggle-slider::before { transform: translateX(16px); }

    /* Preferences table */
    .pref-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }
    .pref-table th {
        text-align: center;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #6b7280;
        padding: 0 8px 10px;
        white-space: nowrap;
    }
    .pref-table th:first-child { text-align: left; }
    .pref-table td {
        padding: 10px 8px;
        border-top: 1px solid #f0f2f7;
        vertical-align: middle;
    }
    .pref-table td:not(:first-child) { text-align: center; }
    .pref-table .type-label {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 500;
        color: #374151;
    }
    .pref-table .type-label i { color: #9ca3af; font-size: 15px; width: 18px; text-align: center; }
    .pref-table .type-desc { font-size: 11px; color: #9ca3af; font-weight: 400; }

    /* Sound select */
    .sound-select {
        padding: 5px 10px;
        border: 1.5px solid #e8eaf0;
        border-radius: 8px;
        font-size: 12px;
        font-family: inherit;
        background: #fff;
        color: #374151;
        outline: none;
    }
    .sound-select:focus { border-color: #0085f3; }

    .sound-preview-btn {
        background: none;
        border: none;
        color: #0085f3;
        cursor: pointer;
        font-size: 14px;
        padding: 2px;
    }
    .sound-preview-btn:hover { color: #0070d1; }

    /* Quiet hours */
    .quiet-row {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        margin-top: 14px;
    }
    .quiet-row input[type="time"] {
        padding: 6px 10px;
        border: 1.5px solid #e8eaf0;
        border-radius: 8px;
        font-size: 12.5px;
        font-family: inherit;
        color: #374151;
        outline: none;
    }
    .quiet-row input[type="time"]:focus { border-color: #0085f3; }
    .quiet-row span { font-size: 12.5px; color: #6b7280; }

    .btn-save-prefs {
        background: #0085f3;
        color: #fff;
        border: none;
        border-radius: 9px;
        padding: 10px 24px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: background .15s;
    }
    .btn-save-prefs:hover { background: #0070d1; }
    .btn-save-prefs:disabled { opacity: .6; cursor: not-allowed; }

    @media (max-width: 600px) {
        .pref-table th, .pref-table td { padding: 8px 4px; }
        .pref-table { font-size: 12px; }
    }
</style>
@endpush

@section('content')
<div class="page-container">

@include('tenant.settings._tabs')

<div style="margin-top:20px;">

    {{-- Card 1: Permissões do Navegador --}}
    <div class="notif-card">
        <div class="notif-card-header">
            <i class="bi bi-globe2"></i> Permissões do Navegador
        </div>
        <div class="notif-card-body">
            <p style="font-size:13px; color:#6b7280; margin-bottom:12px;">
                Para receber notificações, é necessário permitir que o navegador exiba alertas.
            </p>
            <div id="permStatusWrap">
                <span class="perm-status default" id="permBadge">
                    <i class="bi bi-question-circle"></i> Verificando...
                </span>
            </div>
            <div id="iosHint" style="display:none; margin-top:12px; padding:12px 16px; background:#eff6ff; border-radius:10px; border:1px solid #bfdbfe; font-size:12.5px; color:#1e40af; line-height:1.5;">
                <i class="bi bi-phone" style="margin-right:4px;"></i>
                <strong>iPhone/iPad:</strong> Para receber notificações, adicione o Syncro à tela inicial.
                Toque em <strong>Compartilhar</strong> <i class="bi bi-box-arrow-up"></i> &rarr; <strong>Adicionar à Tela de Início</strong>.
            </div>
            <div class="perm-actions">
                <button class="btn-save-prefs" id="btnRequestPerm" onclick="requestNotifPermission()" style="padding:8px 18px; font-size:12.5px;">
                    <i class="bi bi-bell"></i> Permitir Notificações
                </button>
                <button class="btn-save-prefs" id="btnSubscribePush" onclick="togglePushSubscription()" style="padding:8px 18px; font-size:12.5px; background:#eff6ff; color:#0085f3; border:1.5px solid #bfdbfe;">
                    <i class="bi bi-phone"></i> <span id="pushBtnLabel">Ativar Push</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Card 2: Preferências por Tipo --}}
    <div class="notif-card">
        <div class="notif-card-header">
            <i class="bi bi-sliders"></i> Preferências por Tipo
        </div>
        <div class="notif-card-body">
            <table class="pref-table">
                <thead>
                    <tr>
                        <th>Evento</th>
                        <th>Browser</th>
                        <th>Push</th>
                        <th>Som</th>
                    </tr>
                </thead>
                <tbody id="prefTableBody">
                </tbody>
            </table>
        </div>
    </div>

    {{-- Card 3: Sons --}}
    <div class="notif-card">
        <div class="notif-card-header">
            <i class="bi bi-volume-up"></i> Sons
        </div>
        <div class="notif-card-body">
            <div style="display:flex; align-items:center; gap:12px; margin-bottom:16px;">
                <label class="toggle-switch">
                    <input type="checkbox" id="soundMasterToggle" onchange="updateSoundMaster()">
                    <span class="toggle-slider"></span>
                </label>
                <span style="font-size:13px; font-weight:500; color:#374151;">Som de notificações ativado</span>
            </div>
            <div id="soundPerType" style="display:grid; grid-template-columns:1fr 1fr; gap:10px 20px;">
            </div>
        </div>
    </div>

    {{-- Card 4: Horário Silencioso --}}
    <div class="notif-card">
        <div class="notif-card-header">
            <i class="bi bi-moon"></i> Horário Silencioso
        </div>
        <div class="notif-card-body">
            <div style="display:flex; align-items:center; gap:12px;">
                <label class="toggle-switch">
                    <input type="checkbox" id="quietToggle" onchange="markDirty()">
                    <span class="toggle-slider"></span>
                </label>
                <span style="font-size:13px; font-weight:500; color:#374151;">Ativar horário silencioso</span>
            </div>
            <div class="quiet-row" id="quietTimeRow" style="display:none;">
                <span>De</span>
                <input type="time" id="quietStart" value="22:00" onchange="markDirty()">
                <span>até</span>
                <input type="time" id="quietEnd" value="07:00" onchange="markDirty()">
            </div>
            <p style="font-size:11.5px; color:#9ca3af; margin-top:10px;">
                Durante o horário silencioso, nenhuma notificação será emitida (browser, push ou som).
            </p>
        </div>
    </div>

    {{-- Botão salvar --}}
    <div style="text-align:right; margin-top:4px;">
        <button class="btn-save-prefs" id="btnSavePrefs" onclick="savePreferences()" disabled>
            <i class="bi bi-check2"></i> Salvar Preferências
        </button>
    </div>
</div>

</div>
@endsection

@push('scripts')
<script>
(function() {
    var prefs = @json($preferences ?? []);
    var dirty = false;

    // Notification types config
    var notifTypes = [
        { key: 'new_lead',            icon: 'bi-person-plus',    label: 'Novo lead criado',               sound: 'notification-chime' },
        { key: 'lead_assigned',       icon: 'bi-person-check',   label: 'Lead atribuído a mim',           sound: 'notification-chime' },
        { key: 'lead_stage_changed',  icon: 'bi-arrow-right',    label: 'Lead movido de etapa',           sound: 'notification-chime' },
        { key: 'whatsapp_message',    icon: 'bi-whatsapp',       label: 'Nova mensagem WhatsApp',         sound: 'message-received' },
        { key: 'whatsapp_assigned',   icon: 'bi-chat-dots',      label: 'Conversa atribuída',             sound: 'notification-chime' },
        { key: 'ai_intent',           icon: 'bi-robot',          label: 'Sinal de intenção (IA)',         sound: 'notification-chime' },
        { key: 'ai_analyst',          icon: 'bi-lightbulb',      label: 'Sugestão do analista (IA)',      sound: 'notification-chime' },
        { key: 'campaign_completed',  icon: 'bi-megaphone',      label: 'Campanha finalizada',            sound: 'alert' },
        { key: 'master_notification', icon: 'bi-info-circle',    label: 'Notificação do sistema',         sound: 'alert' },
    ];

    var soundOptions = [
        { value: 'notification-chime', label: 'Chime' },
        { value: 'message-received',   label: 'Mensagem' },
        { value: 'alert',              label: 'Alerta' },
    ];

    window.markDirty = function() {
        dirty = true;
        document.getElementById('btnSavePrefs').disabled = false;
    };

    // ── Build preferences table ─────────────────────────
    function buildPrefTable() {
        var tbody = document.getElementById('prefTableBody');
        var html = '';

        notifTypes.forEach(function(t) {
            var browserChecked = (prefs.browser && prefs.browser[t.key] === false) ? '' : 'checked';
            var pushChecked    = (prefs.push && prefs.push[t.key] === false) ? '' : 'checked';
            var soundChecked   = (prefs.sound && prefs.sound[t.key] === false) ? '' : 'checked';

            html += '<tr>';
            html += '<td><div class="type-label"><i class="bi ' + t.icon + '"></i> ' + t.label + '</div></td>';
            html += '<td><label class="toggle-switch"><input type="checkbox" data-channel="browser" data-type="' + t.key + '" ' + browserChecked + ' onchange="markDirty()"><span class="toggle-slider"></span></label></td>';
            html += '<td><label class="toggle-switch"><input type="checkbox" data-channel="push" data-type="' + t.key + '" ' + pushChecked + ' onchange="markDirty()"><span class="toggle-slider"></span></label></td>';
            html += '<td><label class="toggle-switch"><input type="checkbox" data-channel="sound_type" data-type="' + t.key + '" ' + soundChecked + ' onchange="markDirty()"><span class="toggle-slider"></span></label></td>';
            html += '</tr>';
        });

        tbody.innerHTML = html;
    }

    // ── Build sound per type selects ────────────────────
    function buildSoundPerType() {
        var wrap = document.getElementById('soundPerType');
        var html = '';

        notifTypes.forEach(function(t) {
            var currentSound = (prefs.sound && prefs.sound[t.key]) || t.sound;
            if (typeof currentSound === 'boolean') currentSound = t.sound;

            html += '<div style="display:flex; align-items:center; gap:6px;">';
            html += '<span style="font-size:12px; color:#6b7280; min-width:120px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="' + t.label + '">' + t.label + '</span>';
            html += '<select class="sound-select" data-sound-type="' + t.key + '" onchange="markDirty()">';
            soundOptions.forEach(function(s) {
                html += '<option value="' + s.value + '"' + (currentSound === s.value ? ' selected' : '') + '>' + s.label + '</option>';
            });
            html += '</select>';
            html += '<button class="sound-preview-btn" onclick="previewSound(\'' + t.key + '\')" title="Ouvir"><i class="bi bi-play-circle"></i></button>';
            html += '</div>';
        });

        wrap.innerHTML = html;
    }

    // ── Permission status ───────────────────────────────
    function updatePermStatus() {
        var status = window.NotifManager ? window.NotifManager.getPermissionStatus() : 'unsupported';
        var badge = document.getElementById('permBadge');
        var btnReq = document.getElementById('btnRequestPerm');

        var map = {
            granted:     { cls: 'granted',     icon: 'bi-check-circle', text: 'Permitido' },
            denied:      { cls: 'denied',      icon: 'bi-x-circle',    text: 'Bloqueado' },
            default:     { cls: 'default',     icon: 'bi-dash-circle', text: 'Não solicitado' },
            unsupported: { cls: 'unsupported', icon: 'bi-slash-circle', text: 'Não suportado' },
        };

        var s = map[status] || map.unsupported;
        badge.className = 'perm-status ' + s.cls;
        badge.innerHTML = '<i class="bi ' + s.icon + '"></i> ' + s.text;

        btnReq.style.display = (status === 'granted' || status === 'unsupported') ? 'none' : '';

        // Show iOS hint when unsupported on Apple devices
        var iosHint = document.getElementById('iosHint');
        var isIOS = /iPhone|iPad|iPod/.test(navigator.userAgent);
        if (iosHint) {
            iosHint.style.display = (status === 'unsupported' && isIOS) ? 'block' : 'none';
        }
    }

    function updatePushStatus() {
        if (!window.NotifManager) return;
        window.NotifManager.isPushSubscribed().then(function(subscribed) {
            var label = document.getElementById('pushBtnLabel');
            var btn = document.getElementById('btnSubscribePush');
            if (subscribed) {
                label.textContent = 'Desativar Push';
                btn.style.background = '#fef2f2';
                btn.style.color = '#dc2626';
                btn.style.borderColor = '#fecaca';
            } else {
                label.textContent = 'Ativar Push';
                btn.style.background = '#eff6ff';
                btn.style.color = '#0085f3';
                btn.style.borderColor = '#bfdbfe';
            }
        });
    }

    window.requestNotifPermission = function() {
        if (!window.NotifManager) return;
        window.NotifManager.requestPermission().then(function() {
            updatePermStatus();
        });
    };

    window.togglePushSubscription = function() {
        if (!window.NotifManager) return;
        window.NotifManager.isPushSubscribed().then(function(subscribed) {
            if (subscribed) {
                window.NotifManager.unsubscribePush().then(function() {
                    toastr.info('Push desativado.');
                    updatePushStatus();
                }).catch(function() {
                    toastr.error('Erro ao desativar push.');
                });
            } else {
                window.NotifManager.subscribePush().then(function() {
                    toastr.success('Push ativado!');
                    updatePushStatus();
                    updatePermStatus();
                }).catch(function(err) {
                    if (err.message && err.message.indexOf('denied') !== -1) {
                        toastr.error('Permissão negada pelo navegador.');
                    } else {
                        toastr.error('Erro ao ativar push.');
                    }
                    updatePermStatus();
                });
            }
        });
    };

    window.previewSound = function(type) {
        var select = document.querySelector('[data-sound-type="' + type + '"]');
        var soundName = select ? select.value : 'notification-chime';
        if (window.NotifManager) window.NotifManager.playSound(soundName);
    };

    window.updateSoundMaster = function() {
        markDirty();
    };

    // ── Collect & save ──────────────────────────────────
    window.savePreferences = function() {
        var data = { browser: {}, push: {}, sound: {}, quiet_hours: {} };

        // Browser & push toggles
        document.querySelectorAll('[data-channel="browser"]').forEach(function(el) {
            data.browser[el.dataset.type] = el.checked;
        });
        document.querySelectorAll('[data-channel="push"]').forEach(function(el) {
            data.push[el.dataset.type] = el.checked;
        });

        // Sound master toggle
        data.sound.enabled = document.getElementById('soundMasterToggle').checked;

        // Sound per type (either true/false from table toggle, and sound name from selects)
        document.querySelectorAll('[data-channel="sound_type"]').forEach(function(el) {
            if (!el.checked) {
                data.sound[el.dataset.type] = false;
            }
        });
        document.querySelectorAll('[data-sound-type]').forEach(function(el) {
            var type = el.dataset.soundType;
            // Only set sound name if sound is enabled for this type
            var toggle = document.querySelector('[data-channel="sound_type"][data-type="' + type + '"]');
            if (toggle && toggle.checked) {
                data.sound[type] = el.value;
            }
        });

        // Quiet hours
        data.quiet_hours.enabled = document.getElementById('quietToggle').checked;
        data.quiet_hours.start = document.getElementById('quietStart').value;
        data.quiet_hours.end = document.getElementById('quietEnd').value;

        var btn = document.getElementById('btnSavePrefs');
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Salvando...';

        $.ajax({
            url: '{{ route("settings.notifications.update") }}',
            method: 'PUT',
            contentType: 'application/json',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json',
            },
            data: JSON.stringify(data),
            success: function(resp) {
                toastr.success(resp.message || 'Preferências salvas!');
                window.notificationPrefs = resp.preferences || data;
                dirty = false;
                btn.innerHTML = '<i class="bi bi-check2"></i> Salvar Preferências';
            },
            error: function() {
                toastr.error('Erro ao salvar preferências.');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check2"></i> Salvar Preferências';
            }
        });
    };

    // ── Init ────────────────────────────────────────────
    function init() {
        buildPrefTable();
        buildSoundPerType();

        // Sound master toggle
        var soundEnabled = prefs.sound ? (prefs.sound.enabled !== false) : true;
        document.getElementById('soundMasterToggle').checked = soundEnabled;

        // Quiet hours
        var qh = prefs.quiet_hours || {};
        document.getElementById('quietToggle').checked = !!qh.enabled;
        if (qh.start) document.getElementById('quietStart').value = qh.start;
        if (qh.end) document.getElementById('quietEnd').value = qh.end;
        document.getElementById('quietTimeRow').style.display = qh.enabled ? 'flex' : 'none';

        document.getElementById('quietToggle').addEventListener('change', function() {
            document.getElementById('quietTimeRow').style.display = this.checked ? 'flex' : 'none';
        });

        // Update permission status after NotifManager loads
        setTimeout(function() {
            updatePermStatus();
            updatePushStatus();
        }, 500);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
@endpush

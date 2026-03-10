@extends('master.layouts.app')
@php
    $title    = 'Upsell Triggers';
    $pageIcon = 'rocket-takeoff';
@endphp

@section('topbar_actions')
<button class="m-btn m-btn-primary" onclick="openNew()">
    <i class="bi bi-plus-lg"></i> Novo Trigger
</button>
@endsection

@section('content')

{{-- Stats Cards --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:24px;">
    <div class="m-card" style="padding:20px;">
        <div style="font-size:12px;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:.5px;">Triggers Ativos</div>
        <div style="font-size:28px;font-weight:800;color:#1a1d23;margin-top:4px;">{{ $totalActive }}</div>
    </div>
    <div class="m-card" style="padding:20px;">
        <div style="font-size:12px;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:.5px;">Disparos (mês)</div>
        <div style="font-size:28px;font-weight:800;color:#0085f3;margin-top:4px;">{{ $firesThisMonth }}</div>
    </div>
    <div class="m-card" style="padding:20px;">
        <div style="font-size:12px;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:.5px;">Cliques (mês)</div>
        <div style="font-size:28px;font-weight:800;color:#059669;margin-top:4px;">{{ $clicksThisMonth }}</div>
    </div>
    <div class="m-card" style="padding:20px;">
        <div style="font-size:12px;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:.5px;">Conversões (mês)</div>
        <div style="font-size:28px;font-weight:800;color:#7c3aed;margin-top:4px;">{{ $conversionsThisMonth }}</div>
    </div>
</div>

{{-- Triggers Table --}}
<div class="m-card">
    <div class="m-card-header">
        <div class="m-card-title"><i class="bi bi-rocket-takeoff"></i> Gatilhos de Upsell</div>
        <div style="font-size:12.5px;color:#9ca3af;">Configure gatilhos automáticos para incentivar upgrades de plano.</div>
    </div>
    <div style="overflow-x:auto;">
        <table class="m-table" id="triggersTable">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Plano Origem</th>
                    <th>Plano Destino</th>
                    <th>Métrica</th>
                    <th>Threshold</th>
                    <th>Ação</th>
                    <th>Cooldown</th>
                    <th>Prioridade</th>
                    <th>Status</th>
                    <th>Disparos</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($triggers as $trigger)
                <tr id="trigger-row-{{ $trigger->id }}">
                    <td style="font-weight:600;">{{ $trigger->name }}</td>
                    <td>
                        @if($trigger->source_plan)
                            <span class="m-badge m-badge-info">{{ $trigger->source_plan }}</span>
                        @else
                            <span style="color:#9ca3af;font-size:12.5px;">Todos</span>
                        @endif
                    </td>
                    <td><span class="m-badge m-badge-active">{{ $trigger->target_plan }}</span></td>
                    <td>
                        @php
                            $metricLabels = [
                                'leads' => 'Leads', 'users' => 'Usuários', 'pipelines' => 'Pipelines',
                                'ai_agents' => 'Agentes IA', 'ai_tokens' => 'Tokens IA',
                                'chatbot_flows' => 'Chatbot Flows', 'automations' => 'Automações',
                            ];
                        @endphp
                        {{ $metricLabels[$trigger->metric] ?? $trigger->metric }}
                    </td>
                    <td style="font-weight:600;">
                        {{ number_format((float)$trigger->threshold_value, 0, ',', '.') }}{{ $trigger->threshold_type === 'percentage' ? '%' : '' }}
                    </td>
                    <td>
                        @php
                            $actionLabels = ['banner' => 'Banner', 'notification' => 'Notificação', 'email' => 'Email', 'all' => 'Todos'];
                            $actionColors = ['banner' => '#2563eb', 'notification' => '#d97706', 'email' => '#059669', 'all' => '#7c3aed'];
                        @endphp
                        <span style="background:{{ $actionColors[$trigger->action_type] ?? '#6b7280' }}15;color:{{ $actionColors[$trigger->action_type] ?? '#6b7280' }};padding:3px 10px;border-radius:6px;font-size:12px;font-weight:600;">
                            {{ $actionLabels[$trigger->action_type] ?? $trigger->action_type }}
                        </span>
                    </td>
                    <td style="font-size:12.5px;color:#6b7280;">{{ $trigger->cooldown_hours }}h</td>
                    <td style="font-size:12.5px;font-weight:600;">{{ $trigger->priority }}</td>
                    <td>
                        @if($trigger->is_active)
                            <span class="m-badge m-badge-active">Ativo</span>
                        @else
                            <span class="m-badge m-badge-inactive">Inativo</span>
                        @endif
                    </td>
                    <td style="font-weight:600;color:#0085f3;">{{ $trigger->logs_count }}</td>
                    <td style="white-space:nowrap;">
                        <button class="m-btn m-btn-ghost m-btn-sm" onclick="showPreview({{ json_encode($trigger) }})" title="Preview">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button class="m-btn m-btn-ghost m-btn-sm" onclick="viewLogs({{ $trigger->id }}, '{{ addslashes($trigger->name) }}')" title="Ver logs">
                            <i class="bi bi-list-ul"></i>
                        </button>
                        <button class="m-btn m-btn-ghost m-btn-sm" onclick="editTrigger({{ $trigger->id }}, {{ json_encode($trigger) }})">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="m-btn m-btn-ghost m-btn-sm" style="color:#EF4444;"
                                onclick="deleteTrigger({{ $trigger->id }}, '{{ addslashes($trigger->name) }}')" title="Excluir">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </td>
                </tr>
                @endforeach
                @if($triggers->isEmpty())
                <tr>
                    <td colspan="11" style="text-align:center;color:#9ca3af;padding:40px;">
                        Nenhum trigger criado ainda. Clique em "Novo Trigger" para começar.
                    </td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════ --}}
{{-- CRUD Modal                                          --}}
{{-- ═══════════════════════════════════════════════════ --}}
<div id="triggerModal" style="display:none;position:fixed;inset:0;z-index:1050;background:rgba(0,0,0,.4);align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;width:620px;max-width:95vw;max-height:90vh;overflow-y:auto;padding:28px;box-shadow:0 8px 48px rgba(0,0,0,.2);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h3 id="modalTitle" style="font-size:16px;font-weight:700;margin:0;">Novo Trigger</h3>
            <button onclick="closeModal()" style="background:none;border:none;cursor:pointer;font-size:22px;color:#9ca3af;">&times;</button>
        </div>

        <input type="hidden" id="fId">

        {{-- Nome --}}
        <div style="margin-bottom:14px;">
            <label class="flabel">Nome do trigger</label>
            <input type="text" id="fName" class="form-control finput" placeholder='ex: "Starter → Pro: 80% leads"'>
        </div>

        {{-- Planos --}}
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
            <div>
                <label class="flabel">Plano origem</label>
                <select id="fSourcePlan" class="form-control finput">
                    <option value="">Todos os planos</option>
                    @foreach($plans as $p)
                        <option value="{{ $p->name }}">{{ $p->display_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="flabel">Plano destino (recomendado)</label>
                <select id="fTargetPlan" class="form-control finput" required>
                    <option value="">Selecione...</option>
                    @foreach($plans as $p)
                        <option value="{{ $p->name }}">{{ $p->display_name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Métrica + Threshold --}}
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
            <div>
                <label class="flabel">Métrica</label>
                <select id="fMetric" class="form-control finput">
                    <option value="leads">Leads</option>
                    <option value="users">Usuários</option>
                    <option value="pipelines">Pipelines</option>
                    <option value="ai_agents">Agentes IA</option>
                    <option value="ai_tokens">Tokens IA (mês)</option>
                    <option value="chatbot_flows">Chatbot Flows</option>
                    <option value="automations">Automações</option>
                </select>
            </div>
            <div>
                <label class="flabel">Tipo de threshold</label>
                <select id="fThresholdType" class="form-control finput">
                    <option value="percentage">Percentual (%)</option>
                    <option value="absolute">Valor absoluto</option>
                </select>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;margin-bottom:14px;">
            <div>
                <label class="flabel">Valor do threshold</label>
                <input type="number" id="fThresholdValue" class="form-control finput" min="1" step="1" placeholder="80">
            </div>
            <div>
                <label class="flabel">Cooldown (horas)</label>
                <input type="number" id="fCooldown" class="form-control finput" min="1" max="8760" value="72">
            </div>
            <div>
                <label class="flabel">Prioridade</label>
                <input type="number" id="fPriority" class="form-control finput" min="0" value="0">
            </div>
        </div>

        {{-- ── Tipo de Ação (seletor visual) ── --}}
        <div style="margin-bottom:16px;">
            <label class="flabel">Tipo de ação</label>
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;" id="actionTypeSelector">
                <button type="button" class="action-type-btn" data-value="banner" onclick="selectActionType('banner')">
                    <i class="bi bi-megaphone"></i>
                    <span>Banner</span>
                    <small>Barra no topo da página</small>
                </button>
                <button type="button" class="action-type-btn" data-value="notification" onclick="selectActionType('notification')">
                    <i class="bi bi-bell"></i>
                    <span>Notificação</span>
                    <small>Toast na tela</small>
                </button>
                <button type="button" class="action-type-btn" data-value="email" onclick="selectActionType('email')">
                    <i class="bi bi-envelope"></i>
                    <span>Email</span>
                    <small>Template padrão</small>
                </button>
                <button type="button" class="action-type-btn" data-value="all" onclick="selectActionType('all')">
                    <i class="bi bi-layers"></i>
                    <span>Todos</span>
                    <small>Banner + Email</small>
                </button>
            </div>
            <input type="hidden" id="fActionType" value="banner">
        </div>

        {{-- ── Construtor: BANNER ── --}}
        <div id="cfgBanner" class="action-cfg-section" style="border:1px solid #bfdbfe;border-radius:10px;padding:16px;margin-bottom:14px;background:#f8fbff;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
                <i class="bi bi-megaphone" style="color:#2563eb;font-size:15px;"></i>
                <span style="font-size:13px;font-weight:700;color:#1e40af;">Configurar Banner In-App</span>
                <button type="button" onclick="togglePreviewInline('banner')" class="btn-preview-inline">
                    <i class="bi bi-eye"></i> Preview
                </button>
            </div>
            <div style="margin-bottom:10px;">
                <label class="flabel">Título do banner</label>
                <input type="text" id="fBannerTitle" class="form-control finput" placeholder="Desbloqueie WhatsApp + IA" oninput="updateLivePreview()">
            </div>
            <div style="margin-bottom:10px;">
                <label class="flabel">Mensagem</label>
                <input type="text" id="fBannerBody" class="form-control finput" placeholder="Você está perto do limite. Faça upgrade agora!" oninput="updateLivePreview()">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                <div>
                    <label class="flabel">Texto do botão</label>
                    <input type="text" id="fBannerCta" class="form-control finput" placeholder="Ver plano Pro" oninput="updateLivePreview()">
                </div>
                <div>
                    <label class="flabel">URL do botão (opcional)</label>
                    <input type="text" id="fBannerUrl" class="form-control finput" placeholder="/cobranca/checkout?plan=pro">
                </div>
            </div>
            {{-- Live preview do banner --}}
            <div id="bannerPreviewBox" style="display:none;margin-top:14px;">
                <div style="font-size:11px;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">Preview ao vivo</div>
                <div id="bannerPreviewLive" style="background:#0085f3;color:#fff;padding:12px 20px;border-radius:8px;display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;">
                    <div style="display:flex;align-items:center;gap:10px;flex:1;min-width:0;">
                        <i class="bi bi-rocket-takeoff" style="font-size:16px;flex-shrink:0;"></i>
                        <div>
                            <strong id="pvBannerTitle" style="font-size:13px;">Título</strong>
                            <span id="pvBannerBody" style="font-size:12px;opacity:.9;margin-left:6px;">Mensagem</span>
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
                        <span style="background:#fff;color:#0085f3;padding:5px 14px;border-radius:6px;font-size:12px;font-weight:700;" id="pvBannerCta">Ver plano</span>
                        <span style="opacity:.6;font-size:16px;cursor:default;">&times;</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Construtor: NOTIFICAÇÃO ── --}}
        <div id="cfgNotification" class="action-cfg-section" style="display:none;border:1px solid #fde68a;border-radius:10px;padding:16px;margin-bottom:14px;background:#fffef5;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
                <i class="bi bi-bell" style="color:#d97706;font-size:15px;"></i>
                <span style="font-size:13px;font-weight:700;color:#92400e;">Configurar Notificação (Toast)</span>
                <button type="button" onclick="togglePreviewInline('notification')" class="btn-preview-inline">
                    <i class="bi bi-eye"></i> Preview
                </button>
            </div>
            <div style="margin-bottom:10px;">
                <label class="flabel">Título</label>
                <input type="text" id="fNotifTitle" class="form-control finput" placeholder="Você está no limite!" oninput="updateLivePreview()">
            </div>
            <div style="margin-bottom:10px;">
                <label class="flabel">Mensagem</label>
                <textarea id="fNotifBody" class="form-control finput" rows="2" placeholder="Você atingiu 80% dos leads do seu plano. Considere fazer upgrade." oninput="updateLivePreview()"></textarea>
            </div>
            {{-- Live preview da notificação --}}
            <div id="notifPreviewBox" style="display:none;margin-top:14px;">
                <div style="font-size:11px;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">Preview ao vivo</div>
                <div style="display:flex;justify-content:flex-end;">
                    <div id="notifPreviewLive" style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:14px 18px;width:340px;box-shadow:0 4px 20px rgba(0,0,0,.1);">
                        <div style="display:flex;align-items:flex-start;gap:10px;">
                            <div style="width:32px;height:32px;border-radius:8px;background:#eff6ff;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="bi bi-rocket-takeoff" style="color:#0085f3;font-size:14px;"></i>
                            </div>
                            <div style="flex:1;min-width:0;">
                                <div style="font-size:13px;font-weight:700;color:#1a1d23;" id="pvNotifTitle">Título</div>
                                <div style="font-size:12px;color:#6b7280;margin-top:2px;line-height:1.4;" id="pvNotifBody">Mensagem</div>
                                <div style="font-size:11px;color:#9ca3af;margin-top:6px;">agora mesmo</div>
                            </div>
                            <span style="color:#9ca3af;font-size:16px;cursor:default;line-height:1;">&times;</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Construtor: EMAIL ── --}}
        <div id="cfgEmail" class="action-cfg-section" style="display:none;border:1px solid #bbf7d0;border-radius:10px;padding:16px;margin-bottom:14px;background:#f8fdf9;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
                <i class="bi bi-envelope" style="color:#059669;font-size:15px;"></i>
                <span style="font-size:13px;font-weight:700;color:#065f46;">Configurar Email</span>
                <button type="button" onclick="togglePreviewInline('email')" class="btn-preview-inline">
                    <i class="bi bi-eye"></i> Preview
                </button>
            </div>
            <div style="background:#ecfdf5;border:1px solid #a7f3d0;border-radius:8px;padding:12px;margin-bottom:12px;">
                <div style="display:flex;align-items:center;gap:6px;font-size:12px;color:#065f46;">
                    <i class="bi bi-info-circle"></i>
                    O email usa o template padrão da plataforma (mesmo visual dos emails de trial, pagamento, etc). Você configura apenas o conteúdo.
                </div>
            </div>
            <div style="margin-bottom:10px;">
                <label class="flabel">Assunto do email</label>
                <input type="text" id="fEmailSubject" class="form-control finput" placeholder="Hora de crescer — conheça o plano Pro" oninput="updateLivePreview()">
            </div>
            <div style="margin-bottom:10px;">
                <label class="flabel">Título (cabeçalho do email)</label>
                <input type="text" id="fEmailTitle" class="form-control finput" placeholder="Hora de crescer!" oninput="updateLivePreview()">
            </div>
            <div style="margin-bottom:10px;">
                <label class="flabel">Corpo da mensagem</label>
                <textarea id="fEmailBody" class="form-control finput" rows="3" placeholder="Você está chegando no limite do seu plano atual. Conheça opções maiores para continuar crescendo." oninput="updateLivePreview()"></textarea>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                <div>
                    <label class="flabel">Texto do botão CTA</label>
                    <input type="text" id="fEmailCta" class="form-control finput" placeholder="Ver plano Pro" oninput="updateLivePreview()">
                </div>
                <div>
                    <label class="flabel">URL do CTA (opcional)</label>
                    <input type="text" id="fEmailUrl" class="form-control finput" placeholder="/cobranca/checkout?plan=pro">
                </div>
            </div>
            {{-- Preview inline do email --}}
            <div id="emailPreviewBox" style="display:none;margin-top:14px;">
                <div style="font-size:11px;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">Preview do email</div>
                <div style="background:#f4f4f5;border-radius:10px;padding:16px;max-height:320px;overflow-y:auto;">
                    <div style="max-width:400px;margin:0 auto;background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.06);">
                        {{-- Email header --}}
                        <div style="background:#0085f3;padding:24px 20px;text-align:center;">
                            <div style="font-size:14px;font-weight:800;color:#fff;" id="pvEmailHeaderTitle">Hora de crescer!</div>
                            <div style="color:#bfdbfe;font-size:11px;margin-top:4px;" id="pvEmailHeaderSub">Conheça o plano Pro</div>
                        </div>
                        {{-- Email body --}}
                        <div style="padding:20px;">
                            <p style="font-size:13px;font-weight:700;color:#111827;margin:0 0 6px;">Olá, João!</p>
                            <p style="color:#6b7280;font-size:12px;line-height:1.5;margin:0 0 16px;" id="pvEmailBody">Mensagem do email...</p>
                            <div style="text-align:center;margin-bottom:16px;">
                                <span style="display:inline-block;background:#0085f3;color:#fff;font-weight:700;font-size:12px;padding:8px 24px;border-radius:6px;" id="pvEmailCta">Ver plano</span>
                            </div>
                            <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:6px;padding:12px;">
                                <p style="font-size:11px;color:#1e40af;font-weight:600;margin:0 0 4px;">Por que fazer upgrade?</p>
                                <ul style="font-size:10px;color:#1e3a8a;line-height:1.7;margin:0;padding-left:16px;">
                                    <li>Mais leads, usuários e pipelines</li>
                                    <li>Recursos avançados de IA e automação</li>
                                </ul>
                            </div>
                        </div>
                        <div style="padding:10px 20px;text-align:center;border-top:1px solid #f3f4f6;">
                            <span style="font-size:10px;color:#9ca3af;">Syncro Plataforma · app.syncro.chat</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Construtor: TODOS ── --}}
        <div id="cfgAll" class="action-cfg-section" style="display:none;border:1px solid #c4b5fd;border-radius:10px;padding:16px;margin-bottom:14px;background:#faf8ff;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
                <i class="bi bi-layers" style="color:#7c3aed;font-size:15px;"></i>
                <span style="font-size:13px;font-weight:700;color:#5b21b6;">Configurar Todos (Banner + Email)</span>
            </div>
            <div style="background:#ede9fe;border:1px solid #c4b5fd;border-radius:8px;padding:12px;margin-bottom:12px;">
                <div style="font-size:12px;color:#5b21b6;">
                    <i class="bi bi-info-circle"></i>
                    Ao selecionar "Todos", o tenant recebe um <strong>banner in-app</strong> e um <strong>email</strong>. Configure o conteúdo compartilhado abaixo.
                </div>
            </div>
            <div style="margin-bottom:10px;">
                <label class="flabel">Título (banner + cabeçalho email)</label>
                <input type="text" id="fAllTitle" class="form-control finput" placeholder="Hora de crescer!" oninput="updateLivePreview()">
            </div>
            <div style="margin-bottom:10px;">
                <label class="flabel">Mensagem (banner + corpo email)</label>
                <textarea id="fAllBody" class="form-control finput" rows="2" placeholder="Você está chegando no limite do plano." oninput="updateLivePreview()"></textarea>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
                <div>
                    <label class="flabel">Texto do botão CTA</label>
                    <input type="text" id="fAllCta" class="form-control finput" placeholder="Ver plano Pro" oninput="updateLivePreview()">
                </div>
                <div>
                    <label class="flabel">URL do CTA (opcional)</label>
                    <input type="text" id="fAllUrl" class="form-control finput" placeholder="/cobranca/checkout?plan=pro">
                </div>
            </div>
            <div>
                <label class="flabel">Assunto do email</label>
                <input type="text" id="fAllEmailSubject" class="form-control finput" placeholder="Hora de crescer — conheça o plano Pro">
            </div>
        </div>

        <div style="margin-bottom:20px;">
            <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer;">
                <input type="checkbox" id="fIsActive"> Trigger ativo
            </label>
        </div>

        <div style="display:flex;gap:10px;justify-content:flex-end;">
            <button onclick="closeModal()" class="btn-clear">Cancelar</button>
            <button onclick="saveTrigger()" class="btn-apply" id="btnSave">Salvar</button>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════ --}}
{{-- Preview Modal (da tabela)                           --}}
{{-- ═══════════════════════════════════════════════════ --}}
<div id="previewModal" style="display:none;position:fixed;inset:0;z-index:1060;background:rgba(0,0,0,.5);align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;width:680px;max-width:95vw;max-height:90vh;overflow-y:auto;padding:28px;box-shadow:0 8px 48px rgba(0,0,0,.2);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h3 id="previewModalTitle" style="font-size:16px;font-weight:700;margin:0;">Preview</h3>
            <button onclick="closePreviewModal()" style="background:none;border:none;cursor:pointer;font-size:22px;color:#9ca3af;">&times;</button>
        </div>
        <div id="previewModalContent"></div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════ --}}
{{-- Logs Modal                                          --}}
{{-- ═══════════════════════════════════════════════════ --}}
<div id="logsModal" style="display:none;position:fixed;inset:0;z-index:1050;background:rgba(0,0,0,.4);align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;width:700px;max-width:95vw;max-height:90vh;overflow-y:auto;padding:28px;box-shadow:0 8px 48px rgba(0,0,0,.2);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h3 id="logsModalTitle" style="font-size:16px;font-weight:700;margin:0;">Logs</h3>
            <button onclick="closeLogsModal()" style="background:none;border:none;cursor:pointer;font-size:22px;color:#9ca3af;">&times;</button>
        </div>
        <div style="overflow-x:auto;">
            <table class="m-table">
                <thead>
                    <tr>
                        <th>Tenant</th>
                        <th>Plano</th>
                        <th>Valor</th>
                        <th>Limite</th>
                        <th>Ação</th>
                        <th>Disparado em</th>
                        <th>Clique</th>
                        <th>Conversão</th>
                    </tr>
                </thead>
                <tbody id="logsTableBody">
                    <tr><td colspan="8" style="text-align:center;color:#9ca3af;padding:20px;">Carregando...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
.flabel { font-size:12.5px;font-weight:600;color:#374151;display:block;margin-bottom:5px; }
.finput { border:1px solid #d1d5db;border-radius:8px;padding:8px 11px;width:100%;font-size:13.5px; }
textarea.finput { resize:vertical; }
.btn-clear { display:inline-flex;align-items:center;padding:8px 18px;background:transparent;color:#6b7280;border:1.5px solid #e5e7eb;border-radius:9px;font-size:13.5px;font-weight:600;cursor:pointer;transition:.15s; }
.btn-clear:hover { background:#f3f4f6; }
.btn-apply { display:inline-flex;align-items:center;padding:8px 22px;background:#0085f3;color:#fff;border:none;border-radius:9px;font-size:13.5px;font-weight:600;cursor:pointer;transition:.15s; }
.btn-apply:hover { background:#0070d1; }

/* Action type selector buttons */
.action-type-btn {
    display:flex;flex-direction:column;align-items:center;gap:4px;
    padding:12px 8px;border:2px solid #e5e7eb;border-radius:10px;background:#fff;
    cursor:pointer;transition:.15s;text-align:center;
}
.action-type-btn:hover { border-color:#93c5fd;background:#f8fbff; }
.action-type-btn.selected { border-color:#0085f3;background:#eff6ff; }
.action-type-btn i { font-size:20px;color:#6b7280; }
.action-type-btn.selected i { color:#0085f3; }
.action-type-btn span { font-size:12px;font-weight:700;color:#374151; }
.action-type-btn small { font-size:10px;color:#9ca3af;line-height:1.2; }

.btn-preview-inline {
    margin-left:auto;display:inline-flex;align-items:center;gap:4px;
    background:#fff;border:1px solid #d1d5db;border-radius:6px;padding:3px 10px;
    font-size:11px;font-weight:600;color:#6b7280;cursor:pointer;transition:.15s;
}
.btn-preview-inline:hover { border-color:#0085f3;color:#0085f3; }
</style>
@endpush

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name=csrf-token]').content;
const ROUTE_STORE  = "{{ route('master.upsell.store') }}";
const BASE_UPSELL  = "{{ url('master/upsell') }}";
const ROUTE_UPDATE = (id) => `${BASE_UPSELL}/${id}`;
const ROUTE_DELETE = (id) => `${BASE_UPSELL}/${id}`;
const ROUTE_LOGS   = (id) => `${BASE_UPSELL}/${id}/logs`;

let editingId = null;

/* ═══ Action Type Selector ═══ */
function selectActionType(type) {
    document.getElementById('fActionType').value = type;

    // Highlight selected button
    document.querySelectorAll('.action-type-btn').forEach(btn => {
        btn.classList.toggle('selected', btn.dataset.value === type);
    });

    // Show/hide config sections
    document.querySelectorAll('.action-cfg-section').forEach(s => s.style.display = 'none');
    const sectionMap = { banner:'cfgBanner', notification:'cfgNotification', email:'cfgEmail', all:'cfgAll' };
    const section = document.getElementById(sectionMap[type]);
    if (section) section.style.display = 'block';
}

/* ═══ Live Preview Updates ═══ */
function updateLivePreview() {
    const type = document.getElementById('fActionType').value;

    if (type === 'banner' || type === 'all') {
        const title = (type === 'banner' ? document.getElementById('fBannerTitle') : document.getElementById('fAllTitle')).value || 'Título';
        const body  = (type === 'banner' ? document.getElementById('fBannerBody') : document.getElementById('fAllBody')).value || 'Mensagem';
        const cta   = (type === 'banner' ? document.getElementById('fBannerCta') : document.getElementById('fAllCta')).value || 'Ver plano';
        document.getElementById('pvBannerTitle').textContent = title;
        document.getElementById('pvBannerBody').textContent = body;
        document.getElementById('pvBannerCta').textContent = cta;
    }

    if (type === 'notification') {
        document.getElementById('pvNotifTitle').textContent = document.getElementById('fNotifTitle').value || 'Título';
        document.getElementById('pvNotifBody').textContent = document.getElementById('fNotifBody').value || 'Mensagem';
    }

    if (type === 'email' || type === 'all') {
        const title = (type === 'email' ? document.getElementById('fEmailTitle') : document.getElementById('fAllTitle')).value || 'Hora de crescer!';
        const body  = (type === 'email' ? document.getElementById('fEmailBody') : document.getElementById('fAllBody')).value || 'Mensagem do email...';
        const cta   = (type === 'email' ? document.getElementById('fEmailCta') : document.getElementById('fAllCta')).value || 'Ver plano';
        const sub   = (type === 'email' ? document.getElementById('fEmailSubject') : document.getElementById('fAllEmailSubject')).value || '';
        document.getElementById('pvEmailHeaderTitle').textContent = title;
        document.getElementById('pvEmailHeaderSub').textContent = sub ? 'Assunto: ' + sub : 'Conheça o novo plano';
        document.getElementById('pvEmailBody').textContent = body;
        document.getElementById('pvEmailCta').textContent = cta;
    }
}

function togglePreviewInline(type) {
    const boxMap = { banner:'bannerPreviewBox', notification:'notifPreviewBox', email:'emailPreviewBox' };
    const box = document.getElementById(boxMap[type]);
    if (!box) return;
    const isHidden = box.style.display === 'none';
    box.style.display = isHidden ? 'block' : 'none';
    if (isHidden) updateLivePreview();
}

/* ═══ Preview Modal (from table row) ═══ */
function showPreview(trigger) {
    const cfg = trigger.action_config || {};
    const title = cfg.title || 'Hora de crescer!';
    const body  = cfg.body || 'Você está chegando no limite do seu plano atual.';
    const cta   = cfg.cta_text || 'Ver plano';
    const type  = trigger.action_type;

    document.getElementById('previewModalTitle').textContent = 'Preview: ' + trigger.name;
    let html = '';

    // Banner preview
    if (type === 'banner' || type === 'all') {
        html += `
        <div style="margin-bottom:20px;">
            <div style="font-size:11px;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">
                <i class="bi bi-megaphone"></i> Banner In-App (aparece no topo da página do tenant)
            </div>
            <div style="background:#0085f3;color:#fff;padding:12px 20px;border-radius:8px;display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;">
                <div style="display:flex;align-items:center;gap:10px;flex:1;min-width:0;">
                    <i class="bi bi-rocket-takeoff" style="font-size:16px;flex-shrink:0;"></i>
                    <div>
                        <strong style="font-size:13px;">${escapeHtml(title)}</strong>
                        <span style="font-size:12px;opacity:.9;margin-left:6px;">${escapeHtml(body)}</span>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
                    <span style="background:#fff;color:#0085f3;padding:5px 14px;border-radius:6px;font-size:12px;font-weight:700;">${escapeHtml(cta)}</span>
                    <span style="opacity:.6;font-size:16px;">&times;</span>
                </div>
            </div>
        </div>`;
    }

    // Notification preview
    if (type === 'notification' || type === 'all') {
        html += `
        <div style="margin-bottom:20px;">
            <div style="font-size:11px;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">
                <i class="bi bi-bell"></i> Notificação Toast
            </div>
            <div style="display:flex;justify-content:flex-end;">
                <div style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:14px 18px;width:340px;box-shadow:0 4px 20px rgba(0,0,0,.1);">
                    <div style="display:flex;align-items:flex-start;gap:10px;">
                        <div style="width:32px;height:32px;border-radius:8px;background:#eff6ff;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="bi bi-rocket-takeoff" style="color:#0085f3;font-size:14px;"></i>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div style="font-size:13px;font-weight:700;color:#1a1d23;">${escapeHtml(title)}</div>
                            <div style="font-size:12px;color:#6b7280;margin-top:2px;line-height:1.4;">${escapeHtml(body)}</div>
                            <div style="font-size:11px;color:#9ca3af;margin-top:6px;">agora mesmo</div>
                        </div>
                        <span style="color:#9ca3af;font-size:16px;line-height:1;">&times;</span>
                    </div>
                </div>
            </div>
        </div>`;
    }

    // Email preview
    if (type === 'email' || type === 'all') {
        const subj = cfg.email_subject || 'Hora de crescer — conheça o novo plano';
        html += `
        <div style="margin-bottom:20px;">
            <div style="font-size:11px;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">
                <i class="bi bi-envelope"></i> Email (template padrão da plataforma)
            </div>
            <div style="font-size:12px;color:#6b7280;margin-bottom:6px;">Assunto: <strong>${escapeHtml(subj)}</strong></div>
            <div style="background:#f4f4f5;border-radius:10px;padding:16px;">
                <div style="max-width:420px;margin:0 auto;background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.06);">
                    <div style="background:#0085f3;padding:28px 24px;text-align:center;">
                        <div style="font-size:16px;font-weight:800;color:#fff;">${escapeHtml(title)}</div>
                        <div style="color:#bfdbfe;font-size:12px;margin-top:4px;">Conheça o plano ${escapeHtml(trigger.target_plan)}</div>
                    </div>
                    <div style="padding:24px;">
                        <p style="font-size:14px;font-weight:700;color:#111827;margin:0 0 8px;">Olá, João!</p>
                        <p style="color:#6b7280;font-size:13px;line-height:1.5;margin:0 0 20px;">${escapeHtml(body)}</p>
                        <div style="text-align:center;margin-bottom:20px;">
                            <span style="display:inline-block;background:#0085f3;color:#fff;font-weight:700;font-size:13px;padding:10px 28px;border-radius:6px;">${escapeHtml(cta)}</span>
                        </div>
                        <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:6px;padding:14px;">
                            <p style="font-size:12px;color:#1e40af;font-weight:600;margin:0 0 4px;">Por que fazer upgrade?</p>
                            <ul style="font-size:11px;color:#1e3a8a;line-height:1.7;margin:0;padding-left:16px;">
                                <li>Mais leads, usuários e pipelines</li>
                                <li>Recursos avançados de IA e automação</li>
                                <li>Maior capacidade para crescer seu negócio</li>
                            </ul>
                        </div>
                    </div>
                    <div style="padding:12px 24px;text-align:center;border-top:1px solid #f3f4f6;">
                        <span style="font-size:11px;color:#9ca3af;">Syncro Plataforma · app.syncro.chat</span>
                    </div>
                </div>
            </div>
        </div>`;
    }

    document.getElementById('previewModalContent').innerHTML = html;
    document.getElementById('previewModal').style.display = 'flex';
}

function closePreviewModal() {
    document.getElementById('previewModal').style.display = 'none';
}

function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

/* ═══ CRUD ═══ */
function openNew() {
    editingId = null;
    document.getElementById('modalTitle').textContent = 'Novo Trigger de Upsell';
    document.getElementById('fId').value = '';
    document.getElementById('fName').value = '';
    document.getElementById('fSourcePlan').value = '';
    document.getElementById('fTargetPlan').value = '';
    document.getElementById('fMetric').value = 'leads';
    document.getElementById('fThresholdType').value = 'percentage';
    document.getElementById('fThresholdValue').value = '80';
    document.getElementById('fCooldown').value = '72';
    document.getElementById('fPriority').value = '0';
    document.getElementById('fIsActive').checked = true;

    // Clear all action configs
    document.querySelectorAll('.action-cfg-section input, .action-cfg-section textarea').forEach(el => el.value = '');

    // Hide all inline previews
    ['bannerPreviewBox','notifPreviewBox','emailPreviewBox'].forEach(id => {
        document.getElementById(id).style.display = 'none';
    });

    selectActionType('banner');
    document.getElementById('triggerModal').style.display = 'flex';
}

function editTrigger(id, trigger) {
    editingId = id;
    document.getElementById('modalTitle').textContent = 'Editar: ' + trigger.name;
    document.getElementById('fId').value = id;
    document.getElementById('fName').value = trigger.name;
    document.getElementById('fSourcePlan').value = trigger.source_plan || '';
    document.getElementById('fTargetPlan').value = trigger.target_plan;
    document.getElementById('fMetric').value = trigger.metric;
    document.getElementById('fThresholdType').value = trigger.threshold_type;
    document.getElementById('fThresholdValue').value = trigger.threshold_value;
    document.getElementById('fCooldown').value = trigger.cooldown_hours;
    document.getElementById('fPriority').value = trigger.priority;
    document.getElementById('fIsActive').checked = !!trigger.is_active;

    // Clear all fields first
    document.querySelectorAll('.action-cfg-section input, .action-cfg-section textarea').forEach(el => el.value = '');

    // Populate the right section based on action_type
    const cfg = trigger.action_config || {};
    const type = trigger.action_type;

    if (type === 'banner') {
        document.getElementById('fBannerTitle').value = cfg.title || '';
        document.getElementById('fBannerBody').value = cfg.body || '';
        document.getElementById('fBannerCta').value = cfg.cta_text || '';
        document.getElementById('fBannerUrl').value = cfg.cta_url || '';
    } else if (type === 'notification') {
        document.getElementById('fNotifTitle').value = cfg.title || '';
        document.getElementById('fNotifBody').value = cfg.body || '';
    } else if (type === 'email') {
        document.getElementById('fEmailSubject').value = cfg.email_subject || '';
        document.getElementById('fEmailTitle').value = cfg.title || '';
        document.getElementById('fEmailBody').value = cfg.body || '';
        document.getElementById('fEmailCta').value = cfg.cta_text || '';
        document.getElementById('fEmailUrl').value = cfg.cta_url || '';
    } else if (type === 'all') {
        document.getElementById('fAllTitle').value = cfg.title || '';
        document.getElementById('fAllBody').value = cfg.body || '';
        document.getElementById('fAllCta').value = cfg.cta_text || '';
        document.getElementById('fAllUrl').value = cfg.cta_url || '';
        document.getElementById('fAllEmailSubject').value = cfg.email_subject || '';
    }

    // Hide inline previews
    ['bannerPreviewBox','notifPreviewBox','emailPreviewBox'].forEach(id => {
        document.getElementById(id).style.display = 'none';
    });

    selectActionType(type);
    document.getElementById('triggerModal').style.display = 'flex';
}

function collectActionConfig() {
    const type = document.getElementById('fActionType').value;
    const config = {};

    if (type === 'banner') {
        const t = document.getElementById('fBannerTitle').value.trim();
        const b = document.getElementById('fBannerBody').value.trim();
        const c = document.getElementById('fBannerCta').value.trim();
        const u = document.getElementById('fBannerUrl').value.trim();
        if (t) config.title = t;
        if (b) config.body = b;
        if (c) config.cta_text = c;
        if (u) config.cta_url = u;
    } else if (type === 'notification') {
        const t = document.getElementById('fNotifTitle').value.trim();
        const b = document.getElementById('fNotifBody').value.trim();
        if (t) config.title = t;
        if (b) config.body = b;
    } else if (type === 'email') {
        const s = document.getElementById('fEmailSubject').value.trim();
        const t = document.getElementById('fEmailTitle').value.trim();
        const b = document.getElementById('fEmailBody').value.trim();
        const c = document.getElementById('fEmailCta').value.trim();
        const u = document.getElementById('fEmailUrl').value.trim();
        if (s) config.email_subject = s;
        if (t) config.title = t;
        if (b) config.body = b;
        if (c) config.cta_text = c;
        if (u) config.cta_url = u;
    } else if (type === 'all') {
        const t = document.getElementById('fAllTitle').value.trim();
        const b = document.getElementById('fAllBody').value.trim();
        const c = document.getElementById('fAllCta').value.trim();
        const u = document.getElementById('fAllUrl').value.trim();
        const s = document.getElementById('fAllEmailSubject').value.trim();
        if (t) config.title = t;
        if (b) config.body = b;
        if (c) config.cta_text = c;
        if (u) config.cta_url = u;
        if (s) config.email_subject = s;
    }

    return Object.keys(config).length ? config : null;
}

async function saveTrigger() {
    const btn = document.getElementById('btnSave');
    btn.disabled = true;

    const payload = {
        name:            document.getElementById('fName').value,
        source_plan:     document.getElementById('fSourcePlan').value || null,
        target_plan:     document.getElementById('fTargetPlan').value,
        metric:          document.getElementById('fMetric').value,
        threshold_type:  document.getElementById('fThresholdType').value,
        threshold_value: parseFloat(document.getElementById('fThresholdValue').value) || 0,
        action_type:     document.getElementById('fActionType').value,
        action_config:   collectActionConfig(),
        cooldown_hours:  parseInt(document.getElementById('fCooldown').value) || 72,
        priority:        parseInt(document.getElementById('fPriority').value) || 0,
        is_active:       document.getElementById('fIsActive').checked ? 1 : 0,
    };

    const apiUrl = editingId ? ROUTE_UPDATE(editingId) : ROUTE_STORE;
    const method = editingId ? 'PUT' : 'POST';

    try {
        const res  = await fetch(apiUrl, {
            method,
            headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':CSRF, 'Accept':'application/json' },
            body: JSON.stringify(payload),
        });
        const data = await res.json();
        if (res.ok && data.success) {
            toastr.success('Trigger salvo!');
            closeModal();
            setTimeout(() => location.reload(), 800);
        } else {
            const msg = data.message || Object.values(data.errors || {}).flat().join(', ');
            toastr.error(msg || 'Erro ao salvar.');
        }
    } catch { toastr.error('Erro de conexão.'); }
    btn.disabled = false;
}

async function deleteTrigger(id, name) {
    if (!confirm(`Excluir o trigger "${name}"?\n\nTodos os logs serão removidos.`)) return;

    try {
        const res  = await fetch(ROUTE_DELETE(id), {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        });
        const data = await res.json();
        if (res.ok && data.success) {
            toastr.success('Trigger excluído.');
            const row = document.getElementById(`trigger-row-${id}`);
            if (row) row.remove();
        } else {
            toastr.error(data.message || 'Erro ao excluir.');
        }
    } catch { toastr.error('Erro de conexão.'); }
}

function closeModal() {
    document.getElementById('triggerModal').style.display = 'none';
}

/* ═══ Logs Modal ═══ */
async function viewLogs(triggerId, name) {
    document.getElementById('logsModalTitle').textContent = 'Logs: ' + name;
    document.getElementById('logsTableBody').innerHTML = '<tr><td colspan="8" style="text-align:center;color:#9ca3af;padding:20px;">Carregando...</td></tr>';
    document.getElementById('logsModal').style.display = 'flex';

    try {
        const res  = await fetch(ROUTE_LOGS(triggerId), {
            headers: { 'Accept':'application/json', 'X-CSRF-TOKEN':CSRF },
        });
        const data = await res.json();
        if (!data.success || !data.logs.length) {
            document.getElementById('logsTableBody').innerHTML = '<tr><td colspan="8" style="text-align:center;color:#9ca3af;padding:20px;">Nenhum log encontrado.</td></tr>';
            return;
        }

        let html = '';
        data.logs.forEach(log => {
            const tenant = log.tenant || {};
            const fired  = log.fired_at ? new Date(log.fired_at).toLocaleString('pt-BR') : '-';
            const click  = log.clicked_at ? new Date(log.clicked_at).toLocaleString('pt-BR') : '-';
            const conv   = log.converted_at ? new Date(log.converted_at).toLocaleString('pt-BR') : '-';
            html += `<tr>
                <td style="font-weight:600;">${escapeHtml(tenant.name) || '-'}</td>
                <td><span class="m-badge m-badge-info">${escapeHtml(tenant.plan) || '-'}</span></td>
                <td style="font-weight:600;">${Number(log.metric_value).toLocaleString('pt-BR')}</td>
                <td>${Number(log.metric_limit).toLocaleString('pt-BR')}</td>
                <td>${escapeHtml(log.action_type)}</td>
                <td style="font-size:12.5px;">${fired}</td>
                <td style="font-size:12.5px;">${click}</td>
                <td style="font-size:12.5px;">${conv}</td>
            </tr>`;
        });
        document.getElementById('logsTableBody').innerHTML = html;
    } catch {
        document.getElementById('logsTableBody').innerHTML = '<tr><td colspan="8" style="text-align:center;color:#EF4444;padding:20px;">Erro ao carregar logs.</td></tr>';
    }
}

function closeLogsModal() {
    document.getElementById('logsModal').style.display = 'none';
}
</script>
@endpush

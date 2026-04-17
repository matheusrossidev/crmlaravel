# Syncro CRM — Guia Completo da Plataforma

> Este documento é a referência definitiva para qualquer dev ou IA que trabalhe neste codebase.
> Última atualização: **2026-04-17** — atualização geral: billing Stripe (principal) + Asaas (legacy subs + token PIX + partner transfers), Foundation SOLID WhatsApp (ChatIdResolver/InstanceSelector/WindowChecker/MessagePersister), Templates HSM Cloud API, Follow-up Strategy (smart/template/off), Chatbot multi-instância, Actions Cloud-only em automação, phone mask internacional em Forms, fix split duplo nas respostas do Agente IA, **Tenant delete via auto-discovery** (commit 7bafec1), **Deploy section re-verificada contra `portainer-stack.yml` real** (WAHA é `waha.matheusrossi.com.br`, não `waha.syncro.chat`; Sentry + Web Push + Resend documentados).

---

## 1. Visão Geral

**Syncro** é uma plataforma 360 de marketing e CRM multi-tenant com:
- Pipeline de vendas (Kanban)
- Chat inbox para WhatsApp WAHA + WhatsApp Cloud API oficial Meta + Instagram + Website (UI unificada via `tenant/whatsapp/index.blade.php`; backend ainda fragmentado em 3 models, mas há `ConversationContract` + `ConversationResolver` pra abstração polimórfica)
- Agentes de IA com memória, tools, follow-up smart/template/off (via microsserviço Agno)
- Templates HSM WhatsApp Cloud API — criação, aprovação Meta, envio manual (chat) e automático (automação/follow-up)
- Sophia — assistente IA interna com execução de actions no CRM
- Chatbot builder visual multi-canal (React Flow), com suporte a instância WhatsApp específica
- Automações por trigger + send_webhook + extract_lead_data via IA + actions Cloud-only (send_template, send_buttons, send_list)
- Formulários nativos (classic/conversational/multistep) com SDK sem iframe + phone mask internacional (intl-tel-input)
- Relatórios UTM (não há módulo Campanhas — removido abr/2026)
- Facebook Lead Ads — captura automática via webhook + form mapping
- **Billing: Stripe (novo default, BRL/USD) + Asaas (subscriptions legadas forever-locked, Token Increments PIX, Partner Transfers PIX)**
- Programa de parceiros com comissões e cursos
- Tasks, produtos, lead scoring, nurture sequences, NPS, metas de vendas
- Feature Flags — gating de features por tenant via painel master
- Reengagement — emails/WA automáticos pra usuários inativos
- Global search (Cmd+K) + tour interativo (Driver.js)

### Stack

| Camada | Tecnologia |
|--------|-----------|
| Backend | Laravel 11, PHP 8.2 (dev) / 8.3 (prod) |
| Banco | MySQL 8.0 |
| Cache/Queue/Session | Redis 7 |
| Frontend | AdminLTE 4.0.0-rc6, Bootstrap 5, jQuery, Chart.js, Toastr, DataTables, React (chatbot builder only), intl-tel-input 25 (CDN jsDelivr) |
| Build | Vite |
| Real-time | Laravel Reverb (WebSocket) |
| WhatsApp | WAHA Plus (GOWS engine) **+** WhatsApp Cloud API oficial Meta (Coexistence via Embedded Signup) |
| Lead Ads | Facebook Lead Ads (webhook + Business Login + form mapping) |
| Pagamentos | **Stripe (principal: subscriptions BRL+USD, recurring invoices), Asaas (legacy subs forever-locked, PIX pros Token Increments, PIX Transfers pros Partner Withdrawals)** |
| IA | Agno (FastAPI + pgvector), OpenAI/Anthropic/Gemini |
| Tour | Driver.js v1 (onboarding interativo) |
| Email | Laravel Mail + layout shared bilingual (pt_BR / en) |
| Deploy | Docker Swarm, Portainer, Traefik SSL |
| CI/CD | GitHub Actions → Docker Hub → Portainer |

### Stats
~93 models (+Tag, WhatsappTemplate), ~46 services (+ChatIdResolver/InstanceSelector/ConversationWindowChecker/OutboundMessagePersister/WhatsappTemplateService em `app/Services/Whatsapp/`), ~13 jobs, ~31 commands (+BackfillTags, ReconfigureAgnoAgents, ReindexAgnoKnowledge, CheckWhatsappCloudTokens, SyncWhatsappTemplates, GoalAlerts, ProcessGoalRecurrence, BackfillMessageAuthorship), 6 events, 11 notifications, ~89 controllers (Tenant=53, Master=23, Api=4, Auth=2, Webhooks=6, Cs=1), 110+ migrations, 4 contracts (WhatsappServiceContract, ConversationContract, SupportsMessageTemplates, SupportsInteractiveMessages)

### URLs
- **Dev**: `http://localhost/crm/public`
- **Prod**: `https://app.syncro.chat`

### Usuários de Teste (seed)

| Email | Senha | Role |
|-------|-------|------|
| admin@plataforma360.com | password | super_admin |
| admin@demo.com | password | admin (tenant: demo) |
| gestor@demo.com | password | manager (tenant: demo) |

---

## 2. Arquitetura Multi-Tenant

### Trait `BelongsToTenant`
- Localização: `app/Models/Traits/BelongsToTenant.php`
- Aplicado em ~70 models
- Adiciona Global Scope automático: filtra queries por `tenant_id` do usuário logado
- Auto-preenche `tenant_id` ao criar registros
- Suporta impersonação de agências via `session('impersonating_tenant_id')`

### Models SEM tenant (globais)
`Tenant`, `User`, `PipelineStage`, `AiConfiguration`, `PlanDefinition`, `TokenIncrementPlan`, `UpsellTrigger`, `WebhookLog`, `AuditLog`, `PartnerRank`, `PartnerResource`, `PartnerCourse`, `PartnerLesson`, `MasterNotification`, `FeatureFlag`, `ReengagementTemplate`

### Feature Flags
- Modelo `FeatureFlag` (slug, label, description, is_enabled_globally, sort_order) + pivot `feature_tenant`
- Helper: `\App\Models\FeatureFlag::isEnabled('whatsapp_cloud_api', $tenantId)` retorna bool
- Painel: `/master/features` (super_admin) — toggle global ou per-tenant
- Auto-seed via `FeatureFlagSeeder` (rodado no entrypoint do Docker)
- Usado pra rollout gradual de features novas (ex: WhatsApp Cloud API saiu primeiro só pro tenant 12)

### Middleware Chain
```
web → auth → tenant → role:admin → plan.limit:leads
```

| Middleware | Arquivo | Função |
|-----------|---------|--------|
| `tenant` | `TenantMiddleware` | Seta tenant ativo, verifica trial/suspensão |
| `super_admin` | `SuperAdminMiddleware` | Exige `is_super_admin=true` |
| `role:X` | `RoleMiddleware` | Exige role específica (admin, manager, viewer) |
| `plan.limit:X` | `CheckPlanLimit` | Verifica quota do plano |
| `api_key` | `ApiKeyMiddleware` | Valida X-API-Key header (SHA256) |
| `agno_internal` | `AgnoInternalMiddleware` | Valida X-Agno-Token para chamadas internas |

---

## 3. Modelos e Banco de Dados (~88 models)

### Core
- **Tenant** — Empresa com plano, status, subscription, ai_tokens_exhausted, referred_by_agency_id
- **User** — role (super_admin/admin/manager/viewer), tenant_id, dashboard_config, notification_preferences
- **Department** — Setores com assignment_strategy (round_robin/least_busy), default_ai_agent_id

### Leads e Pipeline
- **Lead** — phone, email, company, value, tags (coluna JSON legada **+** relação polimórfica via trait `HasTags` → tabela `taggables`; ver §3 abaixo), custom fields, UTM tracking, pipeline_id, stage_id, status (active/archived/merged), merged_into, merged_at. Trait `HasTags` em uso.
- **Pipeline** — auto_create_from_whatsapp/instagram flags
- **PipelineStage** — position, is_won, is_lost (sem timestamps)
- **Sale** — Imutável (sem updated_at). value, closed_by, closed_at
- **LostSale** — Imutável. reason_id, lost_by, lost_at
- **LeadEvent** — Audit trail. **$timestamps = false** → sempre passar `'created_at' => now()`
- **LeadDuplicate** — lead_id_a, lead_id_b, score (0-100), status (pending/merged/ignored), detected_by (realtime/import/scheduled_job), reviewed_by, reviewed_at
- **LeadNote** — Notas por lead
- **LeadAttachment** — Uploads
- **CustomFieldDefinition** — 10 field_types: text/textarea/number/currency/date/select/multiselect/checkbox/url/phone/email
- **CustomFieldValue** — 5 colunas de valor: value_text, value_number, value_date, value_boolean, value_json

### Tasks e Atividades
- **Task** — subject, description, type (call/email/task/visit/whatsapp/meeting), status (pending/completed), priority (low/medium/high), due_date, due_time, completed_at, lead_id, whatsapp_conversation_id, instagram_conversation_id, assigned_to, created_by, stage_requirement_id
- **StageRequiredTask** — pipeline_stage_id, subject, description, task_type, priority, due_date_offset, sort_order

### Produtos e Catálogo
- **Product** — name, description, sku, price, cost_price, category_id, unit, is_active, sort_order
- **ProductCategory** — parent_id (self-referencing), name, sort_order
- **ProductMedia** — product_id, original_name, storage_path, mime_type, file_size
- **LeadProduct** — lead_id, product_id, quantity, unit_price, discount_percent, total (auto-calculated)
- **SaleItem** — sale_id, product_id, description, quantity, unit_price, total

### Contatos e Listas
- **LeadContact** — lead_id, name, role, phone, email, is_primary
- **LeadList** — name, description, type (static/dynamic), filters (JSON), lead_count. BelongsToMany: leads

### Lead Scoring
- **ScoringRule** — name, category, event_type, conditions (JSON), points, is_active, cooldown_hours
- **LeadScoreLog** — lead_id, scoring_rule_id, points, reason, data_json

### Nurture Sequences
- **NurtureSequence** — name, description, is_active, channel, exit_on_reply, exit_on_stage_change
- **NurtureSequenceStep** — sequence_id, position, delay_minutes, type, config (JSON)
- **LeadSequence** — lead_id, sequence_id, current_step_position, status, next_step_at

### NPS e Pesquisas
- **NpsSurvey** — name, type, question, follow_up_question, trigger, delay_hours, send_via, is_active, slug
- **SurveyResponse** — uuid, survey_id, lead_id, score, comment, status, sent_at, answered_at, expires_at

### Metas de Vendas
- **SalesGoal** — user_id, type (sales_count/sales_value/leads_created/conversion_rate), period, target_value, start_date, end_date, is_recurring, growth_rate, bonus_tiers (JSON)
- **SalesGoalSnapshot** — goal_id, user_id, type, period, target_value, achieved_value, percentage

### WhatsApp (WAHA + Cloud API dual-provider)
- **WhatsappInstance** — `provider` ('waha' ou 'cloud_api'), `session_name` (WAHA), `phone_number`, `phone_number_id` (Cloud API), `waba_id`, `business_account_id`, `access_token` (cast `encrypted`, Cloud API), `system_user_token` (cast encrypted, permanente), `token_expires_at`, `token_status` (valid/expiring/expired/invalid), `token_last_checked_at`, `history_imported`, `display_name`, `label`, `is_primary`. Helpers: `isWaha()`, `isCloudApi()`, `supportsTemplates()`, `supportsInteractiveButtons()`, `supportsInteractiveList()`, `hasWindowRestriction()`, `resolvePrimary($tenantId)`.
- **WhatsappConversation** — `instance_id` (FK!), phone, lid (interno), status (open/closed/expired), tags (coluna JSON legada + trait `HasTags`), assigned_user_id, department_id, ai_agent_id, chatbot_flow_id/node_id/variables, followup counters, response_time_seconds. Implementa `ConversationContract` (`getChannelName(): 'whatsapp'`).
- **WhatsappMessage** — `waha_message_id` (UNIQUE) **OU** `cloud_message_id` (índice) — provider determina qual coluna popular. direction, type, body, media_url, ack, sent_at, `sent_by` (human/human_phone/chatbot/ai_agent/automation/scheduled/followup/event), `sent_by_agent_id` (FK ai_agents).
- **WhatsappTemplate** — `tenant_id`, `whatsapp_instance_id`, `name` (snake_case), `language`, `category` (MARKETING/UTILITY/AUTHENTICATION), `components` (JSON formato Meta), `sample_variables` (exemplos), `status` (PENDING/APPROVED/REJECTED/PAUSED/DISABLED), `meta_template_id`, `rejected_reason`, `quality_rating`, `last_synced_at`. Único por WABA (name+language). Ver seção 11.7.

### Instagram
- **InstagramInstance** — ig_business_account_id, username, access_token (encrypted), status
- **InstagramConversation** — igsid, contact_name, contact_username, ai_agent_id, chatbot_flow_id, tags (coluna JSON legada + trait `HasTags`). Implementa `ConversationContract` (`getChannelName(): 'instagram'`).
- **InstagramMessage** — ig_message_id (UNIQUE), direction, type, body, media
- **InstagramAutomation** — Regras de auto-reply por post (keywords, reply_comment, dm_message arrays), media_type

### Website
- **WebsiteConversation** — visitor_id, flow_id, ai_agent_id, UTM/fbclid/gclid tracking, tags (coluna JSON adicionada em 2026-04-08 + trait `HasTags`). Implementa `ConversationContract` (`getChannelName(): 'website'`).
- **WebsiteMessage** — direction, type, body

### Tags polimórficas (refactor 2026-04-08, em coexistência)
- **Tag** — `tenant_id`, `name`, `color`, `sort_order`, `applies_to` enum (`lead`/`conversation`/`both`). Catálogo único por tenant. Substitui o velho `WhatsappTag`.
- **Tabela `taggables`** — pivot polimórfica (`tag_id`, `taggable_type`, `taggable_id`, `tenant_id`). FK cascade em `tag_id` E `tenant_id` (deletar tag remove atribuições, deletar tenant remove tudo).
- **WhatsappTag** — model legacy ainda existe e a tabela `whatsapp_tags` continua sendo lida em alguns spots de UI catalog (Fase 4 do refactor vai migrar). **NÃO use mais ele em código novo** — use `Tag::` direto.
- A tag pode ser anexada a Lead E a qualquer Conversation (WhatsApp/Instagram/Website) **simultaneamente** — uma única row em `tags`, várias rows em `taggables`. É o desejo "tags omnichannel".
- **Coexistência atual (Fase 3 do plano):** colunas JSON `tags` em leads/whatsapp_conversations/instagram_conversations/website_conversations **continuam sendo escritas em paralelo** com a pivot. JSON ainda é fonte autoritativa pras leituras (filtros, automation conditions, scoring, exports, webhooks). Pivot é dual-write.
- **Comando manual:** `php artisan tags:backfill [--dry-run] [--tenant=N]` — idempotente. Migra `whatsapp_tags` + JSONs pra estrutura nova. Pode rodar várias vezes como reconciliador.
- **Plano completo das 5 fases:** `~/.claude/plans/eager-seeking-corbato.md`. Hoje estamos no fim da Fase 3. Fase 4 = trocar leituras pra pivot. Fase 5 = drop colunas JSON + drop `whatsapp_tags` + rename `WhatsappController`→`InboxController`.

### Chatbot
- **ChatbotFlow** — channel (whatsapp/instagram/website), steps (JSON), variables, trigger_keywords, trigger_type (keyword/instagram_comment), widget config, completions_count
- **ChatbotFlowNode** — type, config (JSON), canvas_x/canvas_y, is_start
- **ChatbotFlowEdge** — source/target node, handles, conditions

### IA
- **AiAgent** — Config completa: objective, communication_style, persona, knowledge_base, response_delay/wait, followup config, enable_pipeline_tool/tags_tool/intent_notify/calendar_tool/voice_reply, use_agno flag
- **AiAgentKnowledgeFile** — Uploads de conhecimento
- **AiAgentMedia** — Mídia por agente
- **AiConfiguration** — Global LLM config (provider, api_key, model)
- **AiIntentSignal** — Alertas de intenção detectados pela IA
- **AiAnalystSuggestion** — Insights gerados pela IA
- **AiUsageLog** — Tokens consumidos: prompt, completion, total

### Lembretes de Eventos
- **EventReminder** — lead_id, conversation_id, ai_agent_id, google_event_id, event_starts_at, offset_minutes, send_at, body, status (pending/sent/failed/cancelled)

### WhatsApp Buttons
- **WhatsappButton** — phone_number, default_message, button_label, website_token (UUID), show_floating, is_active
- **WhatsappButtonClick** — button_id, visitor_id, utm_source/medium/campaign/content/term, fbclid, gclid, page_url, tracking_code

### Billing
- **PlanDefinition** — Planos com `billing_cycle` (monthly/yearly), `group_slug` (vincula mensal↔anual do mesmo tier), `is_recommended` (1 por ciclo), `features_json`, `features_en_json`, `stripe_price_id_brl/usd`. Cada ciclo é uma row separada (espelha Product/Price do Stripe). Helpers: `yearlyVariant()`, `monthlyVariant()`, `yearlyDiscountPctVs($monthly, $currency)`
- **TokenIncrementPlan** — Pacotes de tokens para compra
- **TenantTokenIncrement** — Tokens comprados (asaas_payment_id, status, paid_at)
- **PaymentLog** — tenant_id, type, description, amount, asaas_payment_id, status, paid_at

### Relatórios UTM (NÃO existe módulo Campanhas — só relatórios read-only)
- **NÃO existe** model `Campaign` nem tabela `campaigns`. Foi removido em abril/2026 (commit "clean Campanhas").
- **NÃO existe** integração com Meta Ads ou Google Ads. NÃO existe `FacebookAdsService`, `GoogleAdsService`, `SyncCampaignsJob`, `AdSpend`.
- **OAuthConnection** continua existindo, mas é usado APENAS pelo Google Calendar (escopo `https://www.googleapis.com/auth/calendar`).
- A página `/campanhas` é puramente relatório agregando UTMs (`utm_source`, `utm_medium`, `utm_campaign`, `utm_term`, `utm_content`, `fbclid`, `gclid`) capturados na tabela `leads` pelos widgets de chatbot.
- Não há `Lead.campaign_id`, `Sale.campaign_id`, `LostSale.campaign_id`, nem `WhatsappConversation.referral_campaign_id`.

### Automações
- **Automation** — trigger_type, conditions, actions (JSON), run_count

### Programa de Parceiros
- **PartnerAgencyCode** — code, description, tenant_id, is_active
- **PartnerRank** — name, image_path, min_sales, commission_pct, sort_order, color
- **PartnerCommission** — tenant_id, client_tenant_id, asaas_payment_id, amount, status, available_at
- **PartnerWithdrawal** — tenant_id, amount, status, pix_key, pix_key_type, pix_holder_name, pix_holder_cpf_cnpj, asaas_transfer_id
- **PartnerResource** — title, slug, description, content, cover_image, category, attachments (JSON)
- **PartnerCourse** — title, slug, description, cover_image, is_published
- **PartnerLesson** — course_id, title, description, video_url, duration_minutes
- **PartnerLessonProgress** — tenant_id, lesson_id, completed_at
- **PartnerCertificate** — tenant_id, course_id, certificate_code, issued_at

### Facebook Lead Ads
- **FacebookLeadFormConnection** — tenant_id, oauth_connection_id, page_id, page_name, page_access_token (encrypted), form_id, form_name, form_fields_json (cache de questions), pipeline_id, stage_id, field_mapping (JSON {meta_field → crm_field}), default_tags (JSON), auto_assign_to, allow_duplicates, is_active
- **FacebookLeadFormEntry** — tenant_id, connection_id, meta_lead_id, lead_id (nullable), platform (fb/ig), ad_id, campaign_name_meta, raw_data (JSON), status (processed/failed/duplicate/skipped), error_message

### Feature Flags & Reengajamento
- **FeatureFlag** — slug (whatsapp_cloud_api, facebook_leadads, etc), label, description, is_enabled_globally, sort_order. Pivot `feature_tenant` (feature_id, tenant_id, is_enabled)
- **ReengagementTemplate** — stage (7d/14d/30d), channel (email/whatsapp), subject, body com `{{variables}}`, locale (pt_BR/en), is_active

### Outros
- **ScheduledMessage** — Mensagens agendadas
- **ApiKey** — Chaves API com permissions_json
- **WebhookConfig** — Webhooks de saída
- **UpsellTrigger** / **UpsellTriggerLog** — Triggers de upsell
- **Feedback** — user_id, type, area, title, description, impact, priority, status
- **ElevenlabsUsageLog** — tenant_id, agent_id, conversation_id, characters_used
- **UserConsent** — user_id, consent_type, policy_version, accepted_at
- **MasterNotification** — tenant_id, title, body, type
- **User** — fields novos: `phone`, `last_reengagement_sent_at`, `reengagement_stage` (pra reengagement system)

---

## 4. Rotas e Controllers

### Autenticação (`guest` middleware)
| Método | URI | Controller |
|--------|-----|-----------|
| GET/POST | `/login` | Auth\AuthController |
| GET/POST | `/register` | Auth\AuthController |
| GET/POST | `/forgot-password` | Auth\AuthController |
| GET/POST | `/reset-password/{token}` | Auth\AuthController |
| GET/POST | `/cadastro-agencia` | Auth\AgencyRegisterController |

**Registro — funcionalidades adicionais (abr/2026):**
- **intl-tel-input v25** no campo de telefone (bandeiras, DDI, mascara, validacao E.164). Suporta numeros internacionais (EUA, Portugal, etc).
- **WhatsApp de boas-vindas** via `MasterWhatsappNotifier::welcomeUser()` — envia msg WAHA direto no phone do user apos registro. `PhoneNormalizer::toWahaChatId()` resolve qualquer pais (strip nono digito BR automatico).
- **Validacao DNS** no email (`email:rfc,dns`) — rejeita dominios sem MX.

### Dashboard (`auth`, `tenant`)
| Método | URI | Nome |
|--------|-----|------|
| GET | `/` | dashboard |
| POST | `/dashboard/config` | dashboard.config |
| GET | `/dashboard/leads-chart` | dashboard.leads-chart |
| POST | `/help-chat` | help.chat |
| POST | `/help-chat/execute` | help.execute |
| GET | `/busca` | global.search (Cmd+K global search) |
| POST | `/tour/complete` | tour.complete |
| POST | `/tour/reset` | tour.reset |

### CRM Kanban (`/crm`)
| Método | URI | Nome |
|--------|-----|------|
| GET | `/crm` | crm.index |
| GET | `/crm/poll` | crm.poll |
| GET | `/crm/exportar` | crm.export |
| POST | `/crm/importar/preview` | crm.import.preview (step1: headers + step2: preview com mapping) |
| POST | `/crm/importar` | crm.import (confirma com token + overrides) |
| POST | `/crm/lead/{lead}/stage` | crm.updateStage |
| GET | `/contatos/duplicatas` | leads.duplicates |
| POST | `/contatos/{primary}/merge/{secondary}` | leads.merge |
| POST | `/contatos/detectar-duplicatas` | leads.detect-duplicates |

### Leads/Contatos (`/contatos`)
| Método | URI | Nome |
|--------|-----|------|
| GET | `/contatos` | leads.index |
| POST | `/contatos` | leads.store |
| GET | `/contatos/{lead}` | leads.show |
| PUT | `/contatos/{lead}` | leads.update |
| DELETE | `/contatos/{lead}` | leads.destroy |
| GET | `/contatos/exportar` | leads.export |
| POST | `/contatos/importar` | leads.import |
| POST | `/contatos/{lead}/notas` | leads.addNote |
| POST | `/contatos/{lead}/anexos` | leads.uploadAttachment |

### Tasks (`/tarefas`)
- CRUD completo + toggle complete (POST `/tarefas/{task}/toggle`)
- Filtros por status, tipo, prioridade, data, responsável
- Tasks vinculadas a leads, conversas WhatsApp/Instagram

### Listas de Contatos (`/contatos/listas`)
- CRUD listas (estáticas e dinâmicas) + gerenciar membros

### Chat Inbox (`/chats`)
- **WhatsApp**: CRUD conversations, send messages, assign AI/chatbot/department, link lead
- **Instagram**: Show/send/delete conversations, link lead
- **Website**: Show/status/link lead
- **Quick Messages**: CRUD mensagens rápidas
- **AI Analyst**: Suggestions, approve/reject, trigger analysis
- **Endpoint genérico de contato (NOVO 2026-04-08):** `PUT /chats/inbox/{channel}/{conversation}/contact` — funciona pros 3 canais via `ConversationResolver`. Atualiza `name`/`phone`/`tags` (com dual write JSON+pivot). É o que o front (`saveContact`/`saveTags` em `tenant/whatsapp/index.blade.php`) chama via helper `inboxContactUrl(id)`. Resolveu o bug latente do Instagram (endpoint específico nunca existiu) e habilita tags em Website pela primeira vez.
- Rotas legacy `PUT /chats/conversations/{id}/contact` (só WhatsApp) ainda ativas pra coexistência. Serão removidas na Fase 5 do refactor.

> ⚠️ **`WhatsappController` é o controller dos 3 canais.** Apesar do nome, ele responde rotas WhatsApp + Instagram + Website (métodos `show`, `showInstagram`, `showWebsite`, `updateConversationContact`, etc). Na Fase 5 do refactor de tags/inbox vai ser renomeado pra `InboxController` + pasta de views renomeada pra `tenant/inbox/`. Por enquanto vive em `app/Http/Controllers/Tenant/WhatsappController.php`. **`WhatsappMessageController` continua sendo WhatsApp-specific** (envio outbound) e fica.

### Chatbot (`/chatbot/fluxos`)
| Método | URI | Nome |
|--------|-----|------|
| GET | `/chatbot/fluxos` | chatbot.flows.index |
| POST | `/chatbot/fluxos` | chatbot.flows.store |
| GET | `/chatbot/fluxos/{flow}/editar` | chatbot.flows.edit |
| PUT | `/chatbot/fluxos/{flow}` | chatbot.flows.update |
| DELETE | `/chatbot/fluxos/{flow}` | chatbot.flows.destroy |
| PUT | `/chatbot/fluxos/{flow}/graph` | chatbot.flows.saveGraph |

### IA Agentes (`/ia/agentes`)
| Método | URI | Nome |
|--------|-----|------|
| GET | `/ia/agentes` | ai.agents.index |
| POST | `/ia/agentes` | ai.agents.store |
| GET | `/ia/agentes/{agent}/editar` | ai.agents.edit |
| PUT | `/ia/agentes/{agent}` | ai.agents.update |
| DELETE | `/ia/agentes/{agent}` | ai.agents.destroy |
| POST | `/ia/agentes/{agent}/test-chat` | ai.agents.testChat |

### Relatórios UTM (`/campanhas`) — somente leitura
- `GET /campanhas` — relatório UTM agregado (KPIs + breakdown por source/medium/campaign)
- `GET /campanhas/drill-down` (AJAX) — leads de uma combinação UTM específica
- `GET /campanhas/analytics` (AJAX) — analytics por dimensão/comparação/funil/tendência
- **NÃO há POST/PUT/DELETE.** Não há CRUD. Não há integração com Meta/Google Ads.

### Metas de Vendas (`/metas`)
- CRUD metas + snapshots + alertas de performance

### Configurações (`/configuracoes`)
- **Perfil**: `/configuracoes/perfil`
- **Pipelines**: `/configuracoes/pipelines` + stages (com modal de criação + biblioteca de templates de `app/Support/PipelineTemplates.php`)
- **Motivos de perda**: `/configuracoes/motivos-perda`
- **Usuários**: `/configuracoes/usuarios`
- **Departamentos**: `/configuracoes/departamentos`
- **Tags**: `/configuracoes/tags` — atualmente ainda servido pelo `WhatsappTagController` em cima da tabela legacy `whatsapp_tags`. Será trocado pra `TagController` + `Tag` model na Fase 4 do refactor (URLs ficam idênticas).
- **Campos extras**: `/configuracoes/campos-extras`
- **Produtos**: `/configuracoes/produtos` (CRUD + categorias)
- **Scoring**: `/configuracoes/scoring` (CRUD regras de pontuação)
- **Sequências Nurture**: `/configuracoes/sequencias` (CRUD + enroll leads)
- **Pesquisas NPS**: `/configuracoes/pesquisas` (CRUD + envio)
- **Botões WhatsApp**: `/configuracoes/botoes-whatsapp` (CRUD + tracking)
- **API Keys**: `/configuracoes/api-keys`
- **Integrações**: `/configuracoes/integracoes` (Facebook, Google, WhatsApp WAHA, **WhatsApp Cloud API BETA**, Instagram OAuth, **Facebook Lead Ads**)
- **Automações IG**: `/configuracoes/instagram-automacoes`
- **Automações**: `/configuracoes/automacoes` (com novas actions: `extract_lead_data`, `send_webhook`)
- **Notificações**: `/configuracoes/notificacoes`
- **Cobrança**: `/configuracoes/cobranca` (redesenhado abr/2026: não-assinado mostra tabs Mensal/Anual + grid cards; assinado mostra hero card horizontal + histórico)

### Integrações — sub-rotas (`/configuracoes/integracoes`)
**WhatsApp Cloud API** (`whatsapp-cloud.*`, gated por feature flag `whatsapp_cloud_api`):
- `GET whatsapp-cloud/redirect` — OAuth redirect (fallback velho)
- `GET whatsapp-cloud/callback` — OAuth callback (fallback velho, popup)
- `POST whatsapp-cloud/exchange` — AJAX endpoint do FB Embedded Signup (Coexistence)
- `DELETE whatsapp-cloud/{instance}` — desconecta

**Facebook Lead Ads** (`facebook-leadads.*`):
- `GET/POST facebook-leadads/redirect|callback` — OAuth Business Login
- `GET facebook-leadads/pages|forms|search-page` — listagem de páginas e forms via Graph API
- CRUD `facebook-leadads/connections` — vincula página/form ao pipeline+stage com field mapping
- `DELETE facebook-leadads` — desconecta

### Parceiros (`/parceiro`)
- **Dashboard**: `/parceiro` (stats, rank, comissões)
- **Comissões**: `/parceiro/comissoes` (histórico, disponíveis)
- **Saques**: `/parceiro/saques` (solicitar, histórico)
- **Recursos**: `/parceiro/recursos` (materiais de apoio)
- **Cursos**: `/parceiro/cursos` (cursos + lições + certificados)

### Feedback (`/feedback`)
- CRUD feedbacks dos usuários

### Master (`/master`, `super_admin`)
- Dashboard, Empresas (tenants), Planos, Usuários, Token Increments, Upsell Triggers, Uso, Logs, Sistema, Ferramentas, Notificações, **Features** (`/master/features`), **Reengajamento** (`/master/reengajamento` — CRUD templates + envio de teste + preview)

### API (`/api`)
- **Widget** (público): `/api/widget/{token}/*`
- **v1** (api_key): `/api/v1/leads/*`, `/api/v1/pipelines`, `/api/v1/campaigns/*`
- **Internal Agno**: `/api/internal/agno/*`

### Webhooks (público)
| URI | Handler | HMAC |
|-----|---------|------|
| `POST /api/webhook/waha` | WhatsappWebhookController | `WAHA_WEBHOOK_SECRET` |
| `GET/POST /api/webhook/whatsapp-cloud` | WhatsappCloudWebhookController | `WHATSAPP_CLOUD_APP_SECRET` (X-Hub-Signature-256) |
| `GET/POST /api/webhook/instagram` | InstagramWebhookController | `INSTAGRAM_APP_SECRET` (X-Hub-Signature-256) |
| `GET/POST /api/webhook/facebook/leadgen` | FacebookLeadgenWebhookController | `FACEBOOK_APP_SECRET` (X-Hub-Signature-256) |
| `POST /api/webhook/asaas` | AsaasWebhookController | token na URL |
| `POST /api/webhook/stripe` | StripeWebhookController | Stripe signature header |

---

## 5. WhatsApp (WAHA)

> **Doc completa**: [`obsidian-vault/70 Integrations/WAHA/README.md`](obsidian-vault/70%20Integrations/WAHA/README.md) — 19 notas temáticas cobrindo todos os 80 endpoints OpenAPI, 25 webhook events, gotchas, source code. Esta seção é resumo executivo.
>
> **Doc técnica local**: [`docs/waha-api-docs.md`](docs/waha-api-docs.md)

### URL + auth (produção)
- Endpoint WAHA: `https://waha.matheusrossi.com.br` (**não** `waha.syncro.chat`) — stack Swarm **separado** do CRM
- API auth: header `X-Api-Key` = `WAHA_API_KEY`
- Webhook HMAC: header `X-Webhook-Hmac` com **SHA-512** do raw body, secret = `WAHA_WEBHOOK_SECRET`
- Engine em prod: **GOWS** (Golang, sem browser)

### Fluxo de Mensagem Inbound
```
WAHA → POST /api/webhook/waha
  → WhatsappWebhookController::handle() [valida HMAC SHA-512]
    → ProcessWahaWebhook::dispatchSync($payload)
      → Cache::add("waha:processing:{msgId}", 1, 10) — dedup atômico (race message vs message.any)
      → Resolve phone do JID (limpa @c.us/@lid/@s.whatsapp.net)
      → Se @lid: tenta resolver via /lids/{lid} → batch /lids → contacts
      → Se LID não resolvido: BLOQUEIA (não salva conversa inútil)
      → Cria/atualiza WhatsappConversation com contact_name (PushName em 3 variantes)
      → Auto-assign AI agent (se auto_assign ativo)
      → Salva WhatsappMessage (UNIQUE waha_message_id)
      → Dispara chatbot OU IA OU AutomationEngine
      → Broadcast via Reverb
```

### Regras críticas (fails silenciosos)

1. **`WahaService::parse()` converte 4xx/5xx em `['error' => true, ...]`** — NÃO throw. Método novo DEVE checar `$result['error']` antes de acessar campos. ([19-gotchas-producao](obsidian-vault/70%20Integrations/WAHA/19-gotchas-producao.md))
2. **HMAC é SHA-512** (não SHA-256).
3. **`getChatPicture` sempre retorna HTTP 200** com `{"url": null}` quando sem foto. **Nunca 404**.
4. **URLs CDN Meta expiram em horas** — sempre baixar local via `ProfilePictureDownloader::download()`.
5. **PushName checar 3 variantes**: `name || pushName || pushname` (última **lowercase**).
6. **Timestamp inválido no import**: SKIP, não usar `now()` fallback (embaralha ordem cronológica).
7. **LID**: usar flag `str_ends_with($from, '@lid')`, NUNCA `strlen($phone) > 13`.
8. **`WhatsappInstance::first()` é ARMADILHA**: usar `InstanceSelector::selectFor($tenantId, $ctx)`.
9. **ChatId**: sempre usar `ChatIdResolver::for($instance, $phone, $isGroup, $conv)` — nunca hardcode `$phone . '@c.us'`.

### Autoria de mensagens (`sent_by` tracking)

Toda `WhatsappMessage` (e `InstagramMessage`, `WebsiteMessage`) tem coluna `sent_by` (varchar 20, nullable) + `sent_by_agent_id` (FK pra `ai_agents`, nullable). Valores possíveis:

- `human` — atendente clicou enviar pelo CRM (`user_id` também populado)
- `human_phone` — mensagem mandada do celular do dono (echo do WAHA, sem intent registrado)
- `ai_agent` — Camila/Sophia/qualquer agente IA respondendo (`sent_by_agent_id` populado)
- `chatbot` — fluxo do chatbot builder
- `automation` — `AutomationEngine` disparou
- `scheduled` — comando `whatsapp:send-scheduled`
- `followup` — IA reativando lead inativo
- `event` — eventos de sistema gerados pela IA (stage changed, transferred, tags added)

Como o `ProcessChatbotStep` para WhatsApp **não cria** `WhatsappMessage` direto (a mensagem nasce no banco quando o webhook do WAHA volta com `fromMe=true` via echo), foi implementado um **cache de intent**:

```php
// Antes de cada sendText do chatbot:
Cache::put("outbound_intent:{$convId}:" . md5(trim($body)), [
    'sent_by' => 'chatbot',
    'sent_by_agent_id' => null,
], 120);
```

E o `ProcessWahaWebhook`, ao salvar mensagem outbound do echo, faz `Cache::pull` da mesma chave. Se achar, usa. Se não achar, fallback `human_phone`. TTL 120s é suficiente pro echo voltar (1-3s normalmente). A chave inclui `conversation_id` pra evitar colisão entre conversas com mesmo `body`.

Frontend renderiza um badge na bolha de cada mensagem outbound: pra IA mostra avatar + nome do agent (com animação `msg-author-pulse` no primeiro render); pra outros mostra label texto colorido. CSS em `tenant/whatsapp/index.blade.php` (`.msg-author-badge`, `.msg-author-{tipo}`).

**Backfill** de mensagens antigas: `php artisan messages:backfill-authorship [--dry-run] [--tenant=N]`. Heurística: `outbound + user_id != null` → `human`; `outbound + type='event' + media_mime LIKE 'ai_%'` → `event`; resto fica null (sem badge).

### WahaService — 46 métodos
Ver [[WAHA/18-nossa-implementacao]] pra lista completa com file:line. Grupos principais:
- **Sessions**: `createSession`, `patchSession`, `startSession`, `stopSession`, `deleteSession`, `getQrResponse`
- **Send**: `sendText`, `sendImage`, `sendImageBase64`, `sendVoice`, `sendVoiceBase64`, `sendFileBase64`, `sendList`, `sendReaction`
- **Fetch**: `getChats`, `getChatMessages`, `getChatPicture`, `getContactInfo`, `getGroupInfo`
- **LID**: `getPhoneByLid`, `getAllLids`
- **Config**: `setWebhook`, `setPresence`

### Import de Histórico
Job `ImportWhatsappHistory`: busca conversas e mensagens via WAHA API, cria WhatsappConversation + WhatsappMessage. Flag `history_imported` na instance. Timeout 900s.

Fluxo (após fix commit `379a452`): fetch de mensagens ANTES de criar conv → extrai PushName das msgs → cria conv com nome correto. Skip msgs com timestamp inválido (preserva ordem cronológica).

### Provider Abstraction (WAHA + Cloud API)
A partir de 2026-04-06, WhatsApp suporta 2 providers em paralelo via abstração:

```php
$service = \App\Services\WhatsappServiceFactory::for($instance);
// retorna WhatsappCloudService se $instance->provider === 'cloud_api'
// retorna WahaService caso contrário (default 'waha' ou NULL pra rows legadas)
$service->sendText($chatId, $body);
```

- **`app/Contracts/WhatsappServiceContract.php`** — interface comum: `sendText`, `sendImage`, `sendImageBase64`, `sendVoice`, `sendVoiceBase64`, `sendFileBase64`, `sendList`, `sendReaction`, `getProviderName`
- **`app/Services/WhatsappServiceFactory.php`** — match no `$instance->provider`
- **`app/Services/WahaService.php`** — implementa o contrato (zero behavior change)
- **`app/Services/WhatsappCloudService.php`** — Graph API v22.0, upload via `/media`, lista interativa, download de mídia inbound, `subscribeApp()`

**Pattern crítico** (regression-tested em commit `9daa89d`): SEMPRE resolver instance via `conversation.instance_id`, NÃO via `WhatsappInstance::first()`. Helper de referência: `WhatsappMessageController::resolveInstance($conversation)`.

### WhatsApp Cloud API (Meta Oficial)
Modo **Coexistence** — cliente conecta o WhatsApp Business do celular via QR scan no Embedded Signup, e o número fica vinculado à Cloud API mantendo o app do celular funcionando (mensagens espelhadas via echoes).

**Fluxo**:
```
Frontend FB JS SDK → FB.login({config_id, featureType:'whatsapp_business_app_onboarding'})
  → janelinha Embedded Signup com QR scan
  → postMessage WA_EMBEDDED_SIGNUP {phone_number_id, waba_id, business_id}
  → POST /configuracoes/integracoes/whatsapp-cloud/exchange (AJAX)
    → IntegrationController::exchangeWhatsappCloud()
      → troca code → access_token (oauth/access_token, sem redirect_uri)
      → cria WhatsappInstance(provider='cloud_api')
      → POST /{phone_number_id}/register
      → POST /{waba_id}/subscribed_apps (registra webhook)
```

**Webhook inbound**:
```
Meta → POST /api/webhook/whatsapp-cloud
  → WhatsappCloudWebhookController::handle()
    → Valida HMAC SHA256 com app_secret (X-Hub-Signature-256)
    → ProcessWhatsappCloudWebhook::dispatchSync($payload)
      → entry → changes → value → messages|statuses
      → Dedup via cache + cloud_message_id
      → Cria WhatsappConversation + WhatsappMessage (mesmas tabelas do WAHA)
      → Download de mídia inbound via Graph API → storage local
      → Dispara automações conversation_created/message_received
```

**Pré-requisitos pro cliente final**:
- WhatsApp Business app v2.24.17+ no celular
- Número ativo no WhatsApp Business há 7+ dias
- País suportado (Brasil ✅, EUA, México, Índia, Indonésia, HK, Singapura)
- Throughput: 20 mps (limite específico de números Coexistence)

**Pré-requisitos do app no Meta Developer Portal**:
- WhatsApp product adicionado ✅
- Business Verification ✅
- **`whatsapp_business_messaging` aprovado em App Review** (necessário pra criar Embedded Signup Configuration com Solution Type = Coexistence)
- **`config_id`** copiado pra `WHATSAPP_CLOUD_CONFIG_ID` no Portainer

**Env vars**:
```
WHATSAPP_CLOUD_APP_ID=<app_id>
WHATSAPP_CLOUD_APP_SECRET=<app_secret>
WHATSAPP_CLOUD_CONFIG_ID=<embedded_signup_config_id>  # vazio = fallback OAuth velho
WHATSAPP_CLOUD_VERIFY_TOKEN=<webhook_verify_token>
WHATSAPP_CLOUD_API_VERSION=v22.0
WHATSAPP_CLOUD_REDIRECT=<callback URL pro fallback velho>
```

**Feature flag**: `FeatureFlag::isEnabled('whatsapp_cloud_api', $tenantId)` controla se o card aparece no `/configuracoes/integracoes`. Lançado primeiro só pro tenant 12 (Plataforma 360) via `/master/features`.

---

## 6. Instagram

### Fluxo
```
Meta → POST /api/webhook/instagram
  → InstagramWebhookController::handle()
    → Valida HMAC (X-Hub-Signature-256)
    → ProcessInstagramWebhook::dispatchSync()
      → Encontra InstagramInstance por account_id
      → Se messaging: processa DM (cria conversa, salva mensagem)
        → Suporta tipos: text, share, story_mention, postback, web_url
      → Se comment: verifica InstagramAutomation
        → Reply no comentário
        → Private Reply como 1ª DM (abre janela)
        → DMs subsequentes via recipient.id
        → Pode disparar chatbot flow (trigger_type: instagram_comment)
```

### InstagramAutomation — Campos
- `media_type` — Filtra por tipo de mídia (post, reel, story)
- `keywords` — Array de palavras-chave para match
- `reply_comment` — Array de respostas públicas no comentário
- `dm_message` — Array de mensagens DM
- `chatbot_flow_id` — Fluxo de chatbot a disparar (opcional)

### InstagramService
- `sendMessage($igsid, $text)` — DM regular
- `sendPrivateReply($commentId, $text)` — Private Reply (abre janela de DM)
- `replyToComment($commentId, $text)` — Resposta pública no comentário
- `getProfile($igsid)` — `GET /{IGSID}?fields=name,username,profile_pic` (data completa)
- `listConversations($limit, $after)` — `GET /me/conversations?platform=instagram` (fallback)
- `getConversationParticipants($convId)` — `GET /{conv}?fields=participants` (fallback)

### Contact fetch — IMPORTANTÍSSIMO (mudança silenciosa Meta 28/03/2026)

A Meta mudou o comportamento do `GET /{IGSID}?fields=name,username,profile_pic`
em algum momento entre ~27/03 e 01/04/2026 SEM AVISO em changelog/doc.

**A mudança é POR IGSID, não por instance** (descoberto em 08/04/2026 ao rodar
`instagram:repair-contacts` na instance #34 raulcanal):

- **IGSIDs já cadastrados antes de ~28/03**: endpoint retorna `{ name, username, profile_pic }` completo
- **IGSIDs criados depois**: retornam erro `100/33 "does not support this operation"`,
  mesmo em instances velhas

Ou seja, uma instance pode ter um mix: as conversations antigas continuam recebendo
foto+nome real via getProfile, as conversations novas só conseguem username via fallback.

**535 conversations no banco populadas pelo commit `7cd6d38` (Feb 26)** com fotos
cdninstagram válidas são prova histórica de que getProfile funcionou normalmente
até a mudança.

**Estratégia certa = HYBRID** (implementada em `ProcessInstagramWebhook::fetchContactInfo`):

1. Tenta `getProfile($igsid)` primeiro → se voltar dados, usa name + username + foto
   (download via `ProfilePictureDownloader` porque CDN do Meta expira em horas)
2. Se voltar 100/33, fallback para `listConversations()` + `getConversationParticipants()` →
   pega só `username` (sem name real, sem foto), pelo menos algo

**Endpoints que NÃO funcionam (não re-introduzir):**
- `GET /{message_id}?fields=from` — também retorna 100/33 nas instances novas
- "Auto-discovery" no webhook que pegava primeira instance com `ig_business_account_id` NULL
  e atribuía o `entry.id` do webhook nela — bug histórico de cross-tenant contamination,
  removido em commit `fb32695`. Pra fixar instances com IDs nulos, rodar
  `php artisan instagram:repair-instances` que usa o token DA própria instance pra
  chamar `/me` e popular `ig_business_account_id` corretamente.

**Comandos de manutenção:**
```bash
# Re-valida instances contra /me e corrige IDs nulos/errados
php artisan instagram:repair-instances [--tenant=N] [--force] [--dry-run]

# Re-busca name+username+foto pra conversas com dados faltando (probe per-instance)
php artisan instagram:repair-contacts [--tenant=N] [--instance=N] [--dry-run]
```

**Regra de conduta pro próximo dev/IA**: NUNCA declare "endpoint X não funciona no fluxo
Y" sem testar contra dado real do banco em pelo menos 2 instances de datas/tenants
diferentes. Documentação oficial da Meta as vezes está desatualizada ou contradiz o que
a API realmente retorna. Dado real do banco > doc oficial. Se uma instance funciona e
outra falha, hipótese padrão é "Meta mudou silenciosamente" ou "scope diferente" — NÃO
"endpoint não existe nesse fluxo". Sempre preferir hybrid (try A, fallback B) em vez de
remover o caminho A.

---

## 7. Agentes de IA

### Fluxo
```
Mensagem chega → ProcessWahaWebhook verifica ai_agent_id
  → (new ProcessAiResponse($conversationId, $version))->process()
    → Debounce: Cache versioning (novas msgs incrementam versão)
    → Lock: Cache::add("ai:lock:{id}", 1, 120)
    → Verifica quota de tokens (base + incrementos do mês)
    → Espera response_wait_seconds (batching)
    → Monta contexto: stages, tags, lead, custom fields, notes, history
    → Chama AgnoService::chat() ou OpenAI direto
    → Processa reply_blocks → envia mensagens
    → Processa actions: set_stage, add_tags, update_lead, create_note, assign_human
    → Loga tokens em AiUsageLog
```

### Microsserviço Agno (`agno-service/`)
- **FastAPI** rodando em `http://agno:8000`
- `main.py` — Endpoints: `/chat`, `/agents/{id}/configure`, `/agents/{id}/index-file`, `/agents/{id}/knowledge/search`, `DELETE /agents/{id}/knowledge/{file_id}`, `/agents/{id}/memories/*`
- `agent_factory.py` — Cria/cacheia agentes por `tenant_id:agent_id`, monta instructions com contexto. Aceita kwargs `knowledge_chunks` (RAG) e `current_datetime/period_of_day/greeting` (contexto temporal) — esses contam como contextual e bypassam o cache
- `memory_store.py` — PostgreSQL + pgvector para memória de conversas (resumos)
- `knowledge_store.py` — **NOVO**: tabela `agent_knowledge_chunks` (RAG real). Funções `init_knowledge_tables()`, `index_knowledge_file()`, `search_knowledge()`, `delete_chunks_by_file()`. Reusa `generate_embedding` e engine SQLAlchemy do `memory_store.py`. Chunkifica em ~500 chars com overlap 50, índice ivfflat cosine
- `schemas.py` — ChatRequest, AgentResponse, IndexFileRequest, KnowledgeSearchRequest. ChatRequest aceita `knowledge_chunks`, `current_datetime`, `period_of_day`, `greeting`
- `formatter.py` — Second-pass LLM call que humaniza/quebra resposta. **`max_block` agora é parâmetro** (vem do `max_message_length` do agent), não mais `MAX_BLOCK = 150` hardcoded. Cada agente respeita o próprio limite (Camila clínica usa ~700, Sophia comercial usa ~200)
- `tools/` — Tools disponíveis para function calling

### RAG (Knowledge files) — fluxo completo

1. **Upload** (PHP `AiAgentController::uploadKnowledgeFile`): aceita PDF/DOCX/DOC/TXT/CSV/imagens. Extrai texto via `Smalot\PdfParser` (PDF), `PhpOffice\PhpWord` (DOCX/DOC), leitura direta (TXT/CSV) ou descrição via LLM Vision (imagens). Salva texto extraído em `ai_agent_knowledge_files.extracted_text` e dispara `AgnoService::indexFile($agentId, $tenantId, $fileId, $text, $filename)`.

2. **Indexação no Agno** (`POST /agents/{id}/index-file`): chama `index_knowledge_file()` que:
   - Apaga chunks antigos do mesmo `(agent_id, file_id)` (re-index idempotente)
   - Chunkifica via splitter recursivo (~500 chars, overlap 50, respeita parágrafos→sentenças→espaços)
   - Gera embedding pra cada chunk via `generate_embedding()` (`text-embedding-3-small`, 1536 dim)
   - Insere em `agent_knowledge_chunks` (pgvector + ivfflat cosine index)
   - Retorna `{ok, chunks_count, tokens_used}`

3. **Tracking** — PHP recebe `tokens_used` e cria `AiUsageLog` com `type='knowledge_indexing'`, `model='text-embedding-3-small'`. Custo OpenAI: $0.02/1M tokens (irrisório).

4. **Retrieval** (no `ProcessAiResponse`, antes do `Agno::chat`): chama `AgnoService::searchKnowledge($agentId, $tenantId, $messageBody, top_k=5)`. Agno embeda a query e faz cosine similarity com filtro `tenant_id + agent_id`, threshold 0.25. Retorna top-K chunks `[{file_id, filename, content, similarity}]`.

5. **Injeção** — PHP envia os chunks no payload do `/chat` como `knowledge_chunks`. `agent_factory._build_instructions` monta um bloco "CONTEXTO RELEVANTE DA BASE DE CONHECIMENTO" no system prompt com instrução explícita: "use como FONTE DE VERDADE, se não cobre a pergunta diga que não tem essa info ao invés de inventar".

6. **Delete cascade** — `AiAgentController::deleteKnowledgeFile` chama `AgnoService::deleteKnowledgeFile()` antes de remover o arquivo. Agno faz `DELETE /agents/{id}/knowledge/{file_id}` que apaga todos os chunks vinculados.

7. **Backfill / re-index** — comando `php artisan agno:reindex-knowledge {--agent= --file= --missing}`. Idempotente. `--missing` reindexa apenas arquivos sem `indexed_at`. Roda em background no entrypoint do app pra cobrir arquivos uploaded antes do RAG existir.

### Reconfigure on boot — fix do cache in-memory

O Agno guarda o config dos agents num `dict` Python in-memory (`_agent_configs` em `agent_factory.py`). **Quando o container `syncro_agno` reinicia**, perde tudo. A próxima `/chat` cai num fallback genérico (`{tenant_id, openai, gpt-4o-mini}`), monta um prompt vazio ("Você é Assistente, assistente de nossa empresa") e o LLM completa os buracos puxando contexto da memória vetorial — alucina identidade.

**Fix permanente** (`docker/entrypoint.sh`): no boot do container `app`, roda em background:
```bash
php artisan agno:reconfigure-all --wait=60
php artisan agno:reindex-knowledge --missing
```

`agno:reconfigure-all` itera todos os agents `use_agno=true AND is_active=true` e chama `AgnoService::configureFromAgent($agent)` (que faz o `POST /agents/{id}/configure` com o payload completo). Bug histórico: 2026-04-09, Camila e Sophia respondendo como "Syncro CRM" depois de um deploy.

`AgnoService::configureFromAgent(AiAgent)` é o método único que monta o payload de config — não duplica lógica entre `AiAgentController::syncToAgno`, command de reconfigure, ou qualquer outro spot futuro.

### Contexto temporal injetado a cada chat

O Agno não sabia que horas eram (container roda em UTC, e config é estático). Resultado: Camila dizia "tenha um ótimo dia" às 19h. Fix: PHP (`ProcessAiResponse`) calcula no fuso do app:

```php
$now         = now();
$hour        = (int) $now->format('H');
$periodOfDay = $hour < 5 ? 'madrugada' : ($hour < 12 ? 'manha' : ($hour < 18 ? 'tarde' : 'noite'));
$greeting    = $hour < 5 ? 'ola' : ($hour < 12 ? 'bom dia' : ($hour < 18 ? 'boa tarde' : 'boa noite'));
$currentDt   = $now->locale('pt_BR')->isoFormat('DD/MM/YYYY (dddd) — HH:mm');
```

E envia no payload do `/chat`. `agent_factory._build_instructions` injeta um bloco "DATA E HORA ATUAL (CRÍTICO)" no system prompt com regras: NUNCA "bom dia" se não for manhã, NUNCA "tenha um ótimo dia" à noite (usa "tenha uma ótima noite" ou "até amanhã"), etc.

### Actions da IA
| Action | O que faz |
|--------|----------|
| `set_stage` | Move lead para etapa do funil |
| `add_tags` | Adiciona tags na conversa |
| `update_lead` | Atualiza nome, email, company, birthday, value |
| `create_note` | Cria nota no lead |
| `update_custom_field` | Atualiza campo personalizado |
| `assign_human` | Transfere para humano (limpa ai_agent_id) |
| `send_media` | Envia mídia configurada do agente |

### Sophia — Assistente IA Interna
- Widget flutuante em todas as páginas (exceto chat/chatbot/parceiro)
- **Thinking steps animados** (estilo Kodee): 5 steps sequenciais com checkmarks
- **Action execution**: Sophia pode criar entidades no CRM do tenant
- Actions disponíveis: `create_scoring_rule`, `create_sequence`, `create_pipeline`, `create_automation`, `create_custom_field`, `create_task`, `create_lead`, `query_leads`, `query_performance`
- Segurança: whitelist hardcoded, tenant-scoped, rate limit 10/min, confirmação do usuário
- Service: `SophiaActionExecutor` — valida payload, executa, loga
- Controller: `HelpChatController` — system prompt com docs + actions, forceJson
- Frontend: card de confirmação com lista de ações + botões Confirmar/Cancelar

### Follow-up Strategy (smart/template/off)
Regra Meta pro Cloud API: texto livre fora da janela 24h é rejeitado — só template HSM (paga por envio).

Coluna `ai_agents.followup_strategy` (ENUM) define o comportamento do `AiFollowUpCommand`:
- **`smart`** (default, grátis) — Se janela 24h aberta, envia texto livre normal. Se fechou:
  - Com `ai_agents.followup_template_id` preenchido → envia template fallback (cobra).
  - Sem template → **skip** (poupa custo Meta; registra `skip_reasons.window_closed_no_template`).
- **`template`** — Sempre via template HSM, mesmo dentro da janela. Garante formato pré-aprovado.
- **`off`** — Sem follow-up.

WAHA não tem janela 24h → `ConversationWindowChecker::isOpen` sempre true → fluxo antigo (texto livre) vale.

UI: aba "Follow-up" do form do agente (`resources/views/tenant/ai/agents/form.blade.php`) tem radio cards + dropdown de template APPROVED + alerta laranja quando `template` selecionado.

Mesmo padrão em `NurtureSequenceService::executeMessage`: step com `fallback_template_id` usa template quando janela fecha; sem → skip + log.

### Fix da formatação "picotada" das respostas (2026-04-15)
Bug histórico: Agno retornava `reply_blocks` estruturados, PHP juntava com `\n\n` e re-splittava via `splitIntoMessages` com heurística diferente → lista numerada 1-11 vinha quebrada em 2 bolhas desordenadas.

Fix em `ProcessAiResponse.php`: quando `use_agno=true` e `count($replyBlocks) >= 2` OR `1 bloco ≤ max_length`, usa os blocks **direto** como array de mensagens (sem re-splitar).

`AiAgentService::cleanFormatting` agora **preserva** `- item` e `1. item` (WA renderiza como bullet/numeração visual). Só remove markdown pesado (`**bold**`, `__underline__`, headers `#`, code blocks).

`sendWhatsappReplies` respeita `$agent->response_delay_seconds` (antes era `sleep(3)` hardcoded).

Prompt Agno em `agno-service/agent_factory.py` tem regra explícita: lista curta (≤5) num bloco só; lista longa em blocos de 5 com mini-cabeçalho recontextualizando ("Continuando:").

---

## 8. Chatbot

### Estrutura
`ChatbotFlow` → `ChatbotFlowNode[]` → `ChatbotFlowEdge[]`

### Builder Visual (React Flow)
- Arquivo principal: `resources/js/chatbot-builder.jsx`
- Drag-and-drop visual com React Flow
- Nodes com cores por canal: WhatsApp (verde), Instagram (rosa), Website (azul)
- Suporte a áudio em message nodes (WhatsApp only)
- Cards node (website only) com carrossel de imagens
- `trigger_type`: keyword ou instagram_comment
- `completions_count`: tracking de fluxos completados

### Node Types
| Tipo | Função |
|------|--------|
| `message` | Envia texto/imagem/áudio |
| `input` | Pergunta + branches (WhatsApp: lista interativa, Instagram: quick replies) |
| `cards` | Carrossel de cards com imagem (website only) |
| `condition` | Avalia variável (equals, contains, gt, lt) |
| `action` | Executa: change_stage, add_tag, assign_human, **assign_ai_agent**, send_webhook, set_custom_field |
| `delay` | Pausa N segundos |
| `end` | Mensagem final, limpa fluxo |

### Execução (`ProcessChatbotStep`)
- Max 30 iterações por mensagem (previne loops infinitos)
- 3 segundos de delay entre mensagens (simula digitação)
- Variáveis de sessão em `conversation.chatbot_variables` (JSON)
- Interpolação: `{{nome}}` no texto
- Multi-canal: WhatsApp usa `sendList()`, Instagram usa quick replies, Website usa texto/cards
- **Invariante**: chatbot e agente IA são **mutuamente exclusivos**. Trait `EnforcesExclusiveHandler` nos 3 conversation models dispara `DomainException` se ambos estiverem setados via Eloquent `save()`
- **`assign_ai_agent`**: nova action que limpa chatbot, seta `ai_agent_id`, incrementa `completions_count`, cria evento no chat ("Bot atribuiu conversa ao agente X"), e dispara `ProcessAiResponse` imediatamente pra IA dar boas-vindas contextualizada. Loop do bot para (return) porque `chatbot_flow_id` ficou null
- **`chatbot_node_id`**: ao atribuir flow (dropdown do chat ou auto-trigger por keyword), o node de start (`is_start=true`) é resolvido e setado junto. Bug histórico: antes só setava `chatbot_flow_id` e o bot nunca disparava porque `ProcessWahaWebhook` linha 915 exige ambos
- **Nó de start não-input**: quando `chatbot_node_id` aponta pra um nó message (não input), o `ProcessChatbotStep` agora executa ele direto (antes tentava pular pro edge e silenciava)
- **Lista interativa sem texto**: se o nó input tem branches mas `text` vazio, gera default "Escolha uma opção:" pra não silenciar
- **`is_catch_all`**: checkbox no form de configurações do fluxo. Funciona como fallback: dispara pra qualquer mensagem se nenhum outro flow com keyword bateu
- **Cache de chatbotFlows**: invalidado automaticamente ao salvar/criar flow (`TenantCache::forget`). TTL reduzido de 30min pra 10min
- **`chatbot_flows.whatsapp_instance_id`** (FK nullable, 2026-04-16): quando preenchido, flow só dispara pra mensagens vindas daquela instância específica. `NULL` = roda em todas as instâncias do tenant (backward compat). Permite "flow comercial no número A" + "flow suporte no número B" com triggers conflitantes sem colisão. UI: form do chatbot com `channel=whatsapp` mostra select "Aplicar em qual número?" (inclui badges de capability: WAHA / Cloud API Oficial / templates / buttons).

---

## 9. UTM Tracking (sem módulo Campanhas)

> ⚠️ **NOTA HISTÓRICA**: Anteriormente esse módulo tinha aspirações de integração com Meta Ads e Google Ads (services, sync jobs, OAuth flows, tabela `campaigns`). Tudo isso foi removido em abril/2026 porque nunca foi finalizado nem usado em produção. O que sobrou é puramente relatório agregando UTMs capturados na tabela `leads`. Se quiser reintroduzir integração com plataformas de Ads, precisa começar do zero — não confie em código antigo no histórico do git.

### Captura
Campos no Lead: `utm_source`, `utm_medium`, `utm_campaign`, `utm_term`, `utm_content`, `fbclid`, `gclid`

O chatbot do site captura UTMs automaticamente do `window.location.search`.

### Relatórios
A página `/campanhas` agrega esses UTMs por dimensão (source/medium/campaign) e mostra leads/conversões/receita. Sem integração com plataformas externas. Sem CRUD. A action de automação `set_utm_params` permite sobrescrever UTMs no lead manualmente.

### Atribuição
Não há relacionamento `Lead → Campaign` (a tabela e a coluna foram dropadas). A "atribuição" é puramente baseada em UTM string matching nas queries de relatório.

---

## 10. Pagamentos (Stripe principal + Asaas legacy/Token/Transfers)

### ⚠️ IMPORTANTE — Realidade atual do billing

**Stripe é o principal** pra subscriptions novas. **Asaas é LEGACY** (tenants antigos forever-locked) + papéis específicos (Token Increments PIX + Partner Transfers PIX).

Novos tenants criados hoje caem em Stripe por default (`billing_provider='stripe'`). Tenants antigos que já tinham `asaas_subscription_id` continuam com Asaas até alguém cancelar manualmente — aí a próxima assinatura vai pro Stripe.

### Stripe (principal — subscriptions BRL + USD, mensal + anual)

**Planos anuais vinculados ao mensal (abr/2026):** Cada ciclo (monthly/yearly) é uma row separada em `plan_definitions`, vinculada pelo `group_slug`. Ex: `starter` (monthly) + `starter_anual` (yearly) compartilham `group_slug='starter'`. Admin cria cada variante em `/master/planos` com seu próprio `stripe_price_id`. `is_recommended` marca 1 plano por ciclo como "Mais popular" no checkout.

**Tenant** guarda `billing_cycle` (monthly/yearly) — preenchido pelo webhook ao ativar subscription.

**Checkout redesenhado (abr/2026):** `/cobranca/checkout` e `/configuracoes/cobranca` usam layout centralizado com tabs Mensal/Anual + grid de cards agrupados por `group_slug`. Controller agrupa via `BillingController::buildPlanGroups()`. Plano recomendado posicionado no meio. Badge "Economize X%" calculado via `PlanDefinition::yearlyDiscountPctVs()`.

**Fluxo de assinatura** (`BillingController::stripeSubscribe` → `StripeService::createSubscriptionCheckout`):
- User escolhe plano (cada card já tem o `plan_name` do ciclo selecionado)
- Sistema resolve `price_id` via `PlanDefinition::stripePriceIdFor($currency)` — cada row tem seu price_id
- Metadata inclui `billing_cycle` pra o webhook gravar no tenant
- Redirect pro Stripe Checkout
- Tenant ganha `stripe_customer_id` + `stripe_subscription_id` + `billing_cycle`

**Webhooks Stripe** (`StripeWebhookController`):
| Evento | Ação |
|--------|------|
| `checkout.session.completed` | Ativa subscription, popula `stripe_subscription_id` + `billing_cycle`, cria `PaymentLog`, dispara comissão de parceiro |
| `invoice.payment_succeeded` | Registra pagamento recorrente em `PaymentLog`, comissão |
| `invoice.payment_failed` | Marca `subscription_status=overdue`, notifica tenant |
| `customer.subscription.deleted` | Limpa `stripe_subscription_id`, marca `subscription_status=cancelled` |

Stripe Portal: `BillingController::stripePortal` gera link pro Customer Portal (user muda cartão, cancela, etc).

**Alterar preço de plano existente**: Stripe Prices são imutáveis. Fluxo correto é criar Price novo no Stripe Dashboard + colar o ID novo em `plan_definitions.stripe_price_id_brl/usd`. Quem já paga **continua no Price antigo** (forever).

**Downgrade anual → mensal mid-cycle**: Bloqueado. User termina o ciclo atual e troca na renovação via Stripe Customer Portal. Asaas legado NÃO tem plano anual (forever-locked MONTHLY).

### Asaas (3 papéis específicos — NÃO é principal)

**1. Subscriptions legadas** (`BillingController::subscribe`):
- Tenants que JÁ tinham `asaas_subscription_id` ficam **forever-locked** em Asaas (comentário em código)
- Checkout direto com cartão (sem Checkout redirect, coleta dados no form) — [BillingController.php:179-330](app/Http/Controllers/Tenant/BillingController.php#L179)
- Novos tenants NÃO passam por aqui (default é Stripe)

**2. Token Increments** (compra de tokens IA):
- `TokenIncrementController::purchase` cria Payment PIX via Asaas (sem alternativa Stripe no controller)
- `externalReference = "token_increment:{id}"` identifica no webhook
- Webhook Asaas confirma → `TenantTokenIncrement.status='paid'`, `tenant.ai_tokens_exhausted=false`

**3. Partner Withdrawals** (saque de comissões):
- `PartnerWithdrawalController` cria Transfer PIX via Asaas Transfers API
- Webhook `TRANSFER_DONE` marca `PartnerWithdrawal.status='paid'`
- Ver `obsidian-vault/reference_asaas_transfers.md` pra setup de permissões

**Webhooks Asaas** (`AsaasWebhookController`):
| Evento | Ação |
|--------|------|
| `PAYMENT_RECEIVED` / `PAYMENT_CONFIRMED` | Ativa subscription LEGACY (se tenant é Asaas), confirma token increment, gera `PaymentLog` |
| `PAYMENT_OVERDUE` | Marca subscription overdue (só tenants legacy) |
| `SUBSCRIPTION_INACTIVATED` | Cancela subscription legacy |
| `TRANSFER_DONE` / `TRANSFER_FAILED` | Marca `PartnerWithdrawal` como paid/failed |
| `PAYMENT_REFUNDED` / `PAYMENT_CHARGEBACK_REQUESTED` | Estorna pagamento + comissão do parceiro |

### PaymentLog — fonte única

`PaymentLog` registra TODOS os pagamentos (Asaas + Stripe) — cada row tem `asaas_payment_id` OU `stripe_session_id`/`stripe_invoice_id`. Page `/configuracoes/cobranca` unifica histórico buscando de ambos.

### Partner Commissions — agnóstico

Ambos webhooks (Asaas + Stripe) chamam `PartnerCommissionService::generateCommission()` quando `PAYMENT_RECEIVED`/`invoice.payment_succeeded` rola. 30 dias de carência → comando `partners:release-commissions` marca como `available` → parceiro saca via PIX Asaas.

---

## 11. Real-time (Reverb)

- Reverb escuta em `0.0.0.0:8080` (interno)
- nginx faz proxy de `/app/` e `/apps/` para `reverb:8080`
- Frontend: `window.reverbConfig` injetado via Blade (NUNCA usar VITE_* em prod)
- Eventos broadcasted: `WhatsappMessageCreated`, `WhatsappConversationUpdated`, `InstagramMessageCreated`, `InstagramConversationUpdated`

---

## 11.1 Facebook Lead Ads

### Fluxo
```
Meta Ads → form submetido
  → POST /api/webhook/facebook/leadgen
    → FacebookLeadgenWebhookController::handle()
      → Valida HMAC SHA256 (X-Hub-Signature-256 com FACEBOOK_APP_SECRET)
      → ProcessFacebookLeadgenWebhook::dispatch($payload)
        → Encontra FacebookLeadFormConnection por (page_id, form_id)
        → Busca form fields no Meta Graph API com page_access_token
        → Mapeia field_mapping (JSON) → Lead.{name, email, phone, custom_fields}
        → Sanitiza phone/name/email pra respeitar column lengths
        → Cria Lead com tenant_id + pipeline_id + stage_id da connection
        → Aplica default_tags
        → Auto-assign user (assign_to)
        → Se allow_duplicates=false, dedup por phone/email no tenant
        → Cria FacebookLeadFormEntry (audit log)
```

### Componentes
- **`app/Http/Controllers/FacebookLeadgenWebhookController.php`** — webhook entry
- **`app/Jobs/ProcessFacebookLeadgenWebhook.php`** — processamento
- **`app/Services/FacebookLeadAdsService.php`** — Graph API client (subscribed_apps, get pages, get forms, get lead by ID)
- **`app/Models/FacebookLeadFormConnection.php`** + **`FacebookLeadFormEntry.php`**

### Setup pelo usuário
1. `/configuracoes/integracoes` → "Facebook Lead Ads" → Conectar (OAuth Business Login)
2. Lista páginas autorizadas via `/me/accounts` (com fallback `business_management` scope + busca direta por page ID/URL)
3. Lista forms da página via Graph API
4. Mapeia cada `meta_field` → `crm_field` (name, email, phone, custom field, etc.)
5. Define pipeline + stage de destino + default_tags + auto_assign
6. Salva como `FacebookLeadFormConnection` (page_access_token encrypted)

### Pré-requisitos no Meta Dashboard
- App Facebook com produtos "Webhooks" + "Facebook Login for Business"
- Permissões: `pages_show_list`, `pages_manage_metadata`, `pages_read_engagement`, `leads_retrieval`, `business_management`
- Subscribed app no webhook `leadgen`

---

## 11.2 Sistema de Reengajamento

### O quê
Emails (e/ou WhatsApp) automáticos pra usuários que não fazem login há X dias, separados em estágios 7d / 14d / 30d.

### Componentes
- **`app/Models/ReengagementTemplate.php`** — stage, channel, subject, body com `{{variables}}`, locale, is_active
- **`app/Mail/ReengagementEmail.php`** + **`resources/views/emails/reengagement.blade.php`** (estende `_layout.blade.php` shared bilingual)
- **`app/Console/Commands/SendReengagement.php`** — comando que escaneia users com `last_login_at < threshold` e dispara
- **`app/Http/Controllers/Master/ReengagementController.php`** — `/master/reengajamento` (CRUD templates + preview + sendTest)

### Campos novos em `users`
- `phone` — pra envio via WhatsApp se preferir
- `last_reengagement_sent_at` — pra evitar reenvio no mesmo período
- `reengagement_stage` — qual estágio o user está atualmente

### Variáveis disponíveis no template
- `{{name}}`, `{{email}}`, `{{tenant_name}}`, `{{days_inactive}}`, `{{login_url}}`

### Locale
Templates têm coluna `locale` (`pt_BR` ou `en`) — `SendReengagement` escolhe baseado em `users.locale`.

---

## 11.3 Sophia AI Assistant

(Já documentado na Seção 7 — Agentes de IA, sub-seção "Sophia". Resumo aqui pra discoverability.)

Widget flutuante de IA interna que executa actions no CRM do tenant via whitelist + rate limit + confirmação. Pode criar scoring rules, sequences, pipelines, automações, custom fields, tasks, leads, e fazer queries de leads/performance.

---

## 11.4 Global Search (Cmd+K)

### Componentes
- **`app/Http/Controllers/Tenant/GlobalSearchController.php`** — endpoint `GET /busca?q=...`
- Frontend: shortcut Cmd+K (Mac) / Ctrl+K (Win) abre overlay
- Indexa: leads, conversas WhatsApp, conversas Instagram, tasks, products, automations, chatbot flows
- Limit 10 por categoria, ordenação por relevância
- Resultados clicáveis levam direto pra entidade

---

## 11.5 Tour Interativo (Driver.js)

### Componentes
- **Driver.js v1** carregado via CDN no layout principal
- **`app/Http/Controllers/Tenant/TourController.php`** — `POST /tour/complete` + `POST /tour/reset`
- **`resources/views/tenant/layouts/_tour.blade.php`** — definição dos passos
- **`lang/{pt_BR,en}/tour.php`** — strings traduzidas
- Coluna `users.tour_completed_at` (booleano de "viu o tour")
- Reset disponível em `/configuracoes/perfil` (botão "Refazer tour")

---

## 11.6 Formulários (módulo nativo de captura de leads)

Módulo isolado seguindo SOLID. Três fases completas em abril/2026.

### Arquitetura
- `app/Http/Controllers/Tenant/Forms/{FormController, FormBuilderController, FormMappingController, FormSubmissionController}.php` — admin (CRUD + builder + mapping + submissions)
- `app/Http/Controllers/FormPublicController.php` — público (`show`, `submit`, `config`, `trackView`, `cors`, `script`) — SEM auth
- `app/Services/Forms/{FormSubmissionService, FormLeadCreator, FormNotifier}.php`
- `app/Models/{Form, FormSubmission}.php`
- `routes/forms.php` — carregado via `then` callback em `bootstrap/app.php`

### Tabela `forms` (campos relevantes)
```
name, slug (unique), type ENUM('classic','conversational','multistep','popup','embed')
fields JSON              — array de field objects do builder
mappings JSON            — field_id => 'name'|'phone'|'email'|'company'|'value'|'tags'|'source'|'notes'|'custom:N'
conditional_logic JSON   — [{target_field_id, field_id, operator, value}] — 5 operators: equals/not_equals/contains/not_empty/is_empty
steps JSON               — [{id, title}] para multistep; fields.step_id aponta pra cada
pipeline_id, stage_id, assigned_user_id, source_utm, confirmation_type, confirmation_value, notify_emails JSON
default_country VARCHAR(2) DEFAULT 'BR'     — bandeira inicial dos campos type=tel (intl-tel-input v25)
allowed_countries JSON NULLABLE              — ISO-2 permitidos; NULL = todos ~250 países
logo_url, logo_alignment, background_image_url, enable_logo, enable_background_image, color_preset
brand_color, background_color, card_color, button_color, button_text_color, label_color,
input_border_color, input_bg_color, input_text_color, font_family, border_radius, layout
widget_trigger ENUM('immediate','time','scroll','exit'), widget_delay INT, widget_scroll_pct INT,
widget_show_once BOOL, widget_position ENUM('center','bottom-right','bottom-left')
views_count, views_count_hosted, views_count_inline, views_count_popup
```

### Tabela `form_submissions`
```
form_id, tenant_id, lead_id (nullable), data JSON, embed_mode ENUM('hosted','inline','popup'),
referrer_url, ip_address, user_agent, submitted_at
-- Index: (tenant_id, embed_mode)
```

### 3 tipos de formulário (renderização pública)
- **classic** — `resources/views/forms/public.blade.php` — todos campos numa página só, com layout variants (left/centered/right) e bg image
- **conversational** — `public-conversational.blade.php` — um campo por vez, fullscreen (`100dvh`), progress bar, auto-advance em select/radio, Typeform-style
- **multistep** — `public-multistep.blade.php` — campos agrupados em steps, progress dots, navegação Voltar/Próximo

Todas as 3 views ouvem `postMessage({type: 'form-preview-update', styles})` quando `?preview=1` — usado pelo iframe de preview do edit.

### Admin — criar e editar
- **Criar** (`resources/views/tenant/forms/create.blade.php`): wizard fullpage 5-step (padrão `wizard.blade.php` do AI Agent — dots no canto sup direito, back btn no canto sup esquerdo, múltiplos campos por step, card de branco com `#f6f9fd` gradient bg). Steps: Nome+Tipo+Layout / Cores+Fonte / Branding+Upload / Destino / Envio.
- **Editar** (`edit.blade.php`): layout grid `240px 1fr 1fr` — sidebar tabs + settings + **iframe de preview ao vivo** (postMessage sync). Tabs: General / Layout / Cores / Branding / Destino / Envio / **Distribuição** / Avançado.
- **Cores per-row**: cada cor (brand, bg, card, button, buttonText, label, inputBorder, inputBg, inputText) tem linha própria com ~7 presets contextuais em círculos 30x30 + botão `+` com `<input type="color">` embutido pra custom. HEX atual mostrado à direita do label.
- **Mobile (≤900px)**: sidebar vira header compacto, quick links viram ícones horizontais, tabs em scroll-x, preview em `<details open>` (desktop sempre aberto via CSS, mobile colapsável).

### Builder
- `resources/views/tenant/forms/builder.blade.php` — sidebar (tipos de campo) + canvas (cards) + config panel (right)
- Multistep: mostra grupos com step_id, permite adicionar/remover/reordenar steps, campos movem entre steps via select
- Lógica condicional: checkbox "Conditional logic" no painel de config → escolhe field_id + operator + value; campos condicionais recebem badge roxo
- Cards node-less (JS puro, sem React) — salva JSON via `PUT /formularios/{form}/builder`

### Fase 3 — SDK nativo (sem iframe!)
User rejeitou iframe explicitamente. Padrão RD Station/HubSpot: cola `<script>` e o form renderiza **nativamente no DOM do cliente**.

- `FormPublicController@script` retorna ~400 linhas de JS self-contained (IIFE)
- Lê `data-*` do próprio `<script>`: `data-mode` (`inline`/`popup`), `data-trigger`, `data-delay`, `data-scroll`, `data-position`, `data-show-once`
- Fetcha `GET /api/form/{slug}/config.json` (JSON sanitizado — NÃO expõe mappings/notify_emails/assigned_user_id/pipeline_id/sequence_id/logo_url)
- Injeta CSS scoped com prefixo `#syncro-form-{id}` (style isolation — não vaza pro host)
- Renderiza **SEM chrome**: zero logo, zero título, zero "Criado com Syncro", só campos + botão submit
- Suporta todos tipos de campo classic + lógica condicional
- Modo popup: 4 triggers (`immediate`/`time`/`scroll`/`exit`), 3 posições (`center`/`bottom-right`/`bottom-left`), show-once via `localStorage.syncro_form_shown_{id}`
- Submete via `POST /api/form/{slug}/submit` (JSON, sem CSRF — rota fora do middleware `web`)
- Track view: `POST /api/form/{slug}/track-view` (1x por session via `sessionStorage`)

**Rotas SDK (públicas, CORS `*`, fora do middleware web)**:
```
GET  /api/form/{slug}.js          → script    (SDK IIFE)
GET  /api/form/{slug}/config.json → config    (JSON sanitizado)
POST /api/form/{slug}/submit      → submit    (alias de /f/{slug} com CORS + embed_mode)
POST /api/form/{slug}/track-view  → trackView (incrementa views_count_{mode})
OPTIONS /api/form/{slug}/{any?}   → cors      (preflight)
```

SDK suporta **só `type=classic`** por ora. Conversational/multistep só via link hospedado (complexidade de slides não compensa).

### Serviço de submissão
`FormSubmissionService::process(Form, array data, string ip, ?string ua, string embedMode='hosted', ?string referrerUrl=null)`:
1. Valida honeypot `_website_url` + required fields (pulando campos ocultos por condicional)
2. Cria lead via `FormLeadCreator` (com checagem de `PlanLimitChecker`, captura UTMs, tags, custom fields)
3. Salva `FormSubmission` com `embed_mode` + `referrer_url`
4. Executa post-actions (nurture sequence enroll, static list, create_task)
5. Dispara `FormNotifier` (email + WhatsApp welcome)

Todo o server-side funciona igual pros 3 caminhos (hosted/inline/popup) — só o `embed_mode` muda.

### Index (listagem)
- Header com filtro de datas (default últimos 30 dias)
- 5 KPI cards: Formulários ativos / Envios no período / Visualizações / Taxa de conversão / Leads criados
- **3 gráficos** (Chart.js): line (tendência diária) + bar horizontal (top 8 forms) + **doughnut (envios por modo hosted/inline/popup)**
- Tabela de forms configurados com: nome+data, tipo+pipeline, envios, views, conversion%, copy-link, status, ações (builder/envios/editar/delete)
- Padrão visual do `/nps` (mesmas classes `.content-card`, `.nps-kpi-grid` renomeado pra `.fx-kpi-grid`)

### Convenções
- **"Submissões" → "Envios"** em toda a UI (decisão do user)
- Rotas admin: `/formularios/*` (português); rotas SDK: `/api/form/*` (sem 's' no final — é pra ficar curto)
- Slug gerado no store: `Str::slug($name) . '-' . Str::random(6)`
- `FormFactory` não existe (fora de escopo)

### Como embutir num site externo
```html
<!-- Inline: renderiza no lugar do script -->
<script src="https://app.syncro.chat/api/form/SLUG.js" data-mode="inline" async></script>

<!-- Popup com exit-intent -->
<script src="https://app.syncro.chat/api/form/SLUG.js"
        data-mode="popup"
        data-trigger="exit"
        data-show-once="true"
        async></script>
```

### Phone mask internacional (intl-tel-input v25)
Fields do tipo `tel` nos formulários usam [intl-tel-input v25](https://github.com/jackocnr/intl-tel-input) via CDN jsDelivr (bundle `intlTelInputWithUtils` inclui libphonenumber).
- Bandeiras via emoji nativo do SO (sem sprite/PNG)
- `formatAsYouType: true` — máscara adapta ao país automaticamente
- `strictMode: true` — bloqueia caractere inválido enquanto digita
- Validação client-side: bloqueia submit se número inválido
- Valor enviado ao backend em E.164 (+5511912345678) — `PhoneNormalizer::toE164` normaliza

**Config por formulário** (em `/formularios/{id}/editar` → Avançado):
- `forms.default_country` (VARCHAR(2), default BR) — qual bandeira abre selecionada
- `forms.allowed_countries` (JSON nullable) — ISO-2 de países permitidos; `null` = todos (~250 países)
- UI com radio "Todas / Só os que eu marcar" + checkboxes pra 18 países populares (BR, US, PT, AR, ES, MX, GB, FR, DE, IT, CL, CO, PE, UY, PY, CA, AU, JP)

**Cobertura**: tanto no SDK embed (`FormPublicController::buildSdkJs`) quanto nas views hospedadas via partial shared [resources/views/forms/_phone-lib.blade.php](resources/views/forms/_phone-lib.blade.php).

---

## 11.7 WhatsApp Templates HSM (Cloud API oficial)

Regra Meta: fora da janela 24h **só Message Template HSM** (pré-aprovado). Syncro suporta criação, aprovação Meta, envio manual (chat) e automático (automação/follow-up).

### Tabela `whatsapp_templates`
- `name` (snake_case, único por WABA), `language` (pt_BR, en_US, es_ES...), `category` (MARKETING/UTILITY/AUTHENTICATION)
- `components` (JSON — formato Meta: header+body+footer+buttons), `sample_variables` (exemplos pra revisão Meta)
- `status` ENUM (PENDING/APPROVED/REJECTED/PAUSED/DISABLED), `meta_template_id`, `rejected_reason`, `quality_rating`

### Criação (admin)
`/configuracoes/whatsapp-templates/criar` — wizard 70/30 com preview iPhone clay:
- Botões de variáveis (Nome do cliente / Data / Hora / Empresa / Valor / Código / Link / Outro) inserem `{{N}}` no cursor + registram label amigável
- Upload de mídia de exemplo via dropzone (padrão da plataforma), sobe em `storage/app/public/whatsapp-templates/samples/`
- Ao submeter: [WhatsappTemplateService::create](app/Services/Whatsapp/WhatsappTemplateService.php) valida + chama [WhatsappCloudService::uploadToMetaResumable](app/Services/WhatsappCloudService.php) (Meta exige handle `h:4:...` não URL) + cria template via Graph API

**IMPORTANTE**: Meta pode **reclassificar categoria automaticamente** (UTILITY→MARKETING) se conteúdo tiver cara promocional. Isso é documentado ([doc Meta](https://developers.facebook.com/docs/whatsapp/updates-to-pricing/new-template-guidelines#template-category-changes)) e o `syncFromMeta` loga a mudança. Info box no `/show` explica pro user que é comportamento Meta, não bug nosso.

### Envio
- **Manual** — Modal no chat Cloud (`/chats`) ao clicar "+" → "Template". Detecta janela 24h: se fechada, input de texto desabilita + notice "Use um template pra retomar".
- **Automático** — Actions de automação `send_whatsapp_template`, `send_whatsapp_buttons`, `send_whatsapp_list` (só aparecem na UI se tenant tem instância Cloud).
- **Follow-up IA** — Agent com `followup_strategy='template'` ou `smart` (ver seção 7).

### Sync
`whatsapp:sync-templates` (cron diário 04:00) puxa status atual do Meta e atualiza status local (APPROVED / REJECTED / etc).

---

## 11.8 Foundation SOLID WhatsApp (`app/Services/Whatsapp/`)

Consolidada em 2026-04-14 pra compatibilidade Cloud API sem espalhar `if provider='cloud_api'` pela codebase.

### Shared services (cada um com SRP)
- **`ChatIdResolver::for($instance, $phone, $isGroup, $conv)`** — formato de chatId por provider. Cloud → número puro; WAHA → `@c.us`/`@g.us`/`@lid` preservando LID do histórico GOWS. Usado por chatbot, agente IA, automação, scheduled, event-reminders, nurture.
- **`InstanceSelector::selectFor($tenantId, $ctx)`** — resolve instance priorizando: explicit `instance_id` do config → conversation.instance → entity.instance (agent/flow/sequence) → `WhatsappInstance::resolvePrimary`.
- **`ConversationWindowChecker`** — janela 24h Meta. `isOpen()`, `hoursUntilClose()`, `isCloudApi()`. Única fonte de verdade. WAHA sempre retorna true.
- **`OutboundMessagePersister::persist(...)`** — cria `WhatsappMessage` sync + `broadcast(WhatsappMessageCreated)` via Reverb. Popula `waha_message_id` OU `cloud_message_id` conforme provider. Usado por chatbot/agente IA/automação/nurture/scheduled.

### Contratos segregados (ISP)
- **`App\Contracts\WhatsappServiceContract`** — base (sendText, sendImage, sendList, sendReaction, etc). Tanto `WahaService` quanto `WhatsappCloudService` implementam.
- **`App\Contracts\SupportsMessageTemplates`** — só `WhatsappCloudService` implementa (`sendTemplate`). WAHA não suporta HSM.
- **`App\Contracts\SupportsInteractiveMessages`** — só `WhatsappCloudService` (`sendInteractiveButtons` até 3 reply buttons).

Caller faz `if ($service instanceof SupportsMessageTemplates)` antes de chamar.

### Capabilities no model `WhatsappInstance`
- `supportsTemplates()` → isCloudApi
- `supportsInteractiveButtons()` → isCloudApi
- `supportsInteractiveList()` → true (ambos)
- `hasWindowRestriction()` → isCloudApi

UI consulta esses helpers — zero `if provider === 'cloud_api'` espalhado em Blade/JS.

### Fix chatbot Cloud API
Antes, chatbot no Cloud perdia mensagem — dependia do "echo" webhook do WAHA (`fromMe=true`) pra salvar em `WhatsappMessage`. Cloud não manda echo de outbound. **Fix** (2026-04-14): `ProcessChatbotStep` persiste **sync** via `OutboundMessagePersister` logo após `sendText/Image/List` retornar OK no Cloud. No WAHA o echo ainda chega e é deduped via `waha_message_id` unique.

---

## 12. Deploy e CI/CD

### Docker Images (3 imagens separadas, taguadas por commit SHA — sem `latest` em prod)
- `matolado/crm:{commit_sha}` — PHP-FPM 8.3 + Laravel (usado por `app`/`queue`/`scheduler`/`reverb`)
- `matolado/crm-nginx:{commit_sha}` — nginx + static files
- `matolado/agno-service:{commit_sha}` — Python FastAPI Agno

**Dockerfile**: Node 20 (Vite build) → PHP 8.3-FPM → Composer → `docker/entrypoint.sh` (migrate + cache + permissions setgid + agno reconfigure + agno reindex knowledge).

### GitHub Actions
Push ao `main` → build 3 imagens → push Docker Hub. Deploy é **manual** no Portainer: edita `portainer-stack.yml` apontando pro SHA novo + "Update the stack". NÃO tem auto-pull webhook. Rollback = editar stack apontando pro SHA anterior.

### Stack Swarm (Portainer) — `portainer-stack.yml`
| Service | Image | Replicas | Função |
|---------|-------|----------|--------|
| `nginx` | `crm-nginx:{sha}` | 1 | Reverse proxy + static files (redes `serverossi`+`crm_private`) |
| `app` | `crm:{sha}` | 1 | PHP-FPM 8.3 (web requests) |
| `queue` | `crm:{sha}` | 1 | Worker `--queue=ai,whatsapp,default --timeout=900 --memory=512` |
| `scheduler` | `crm:{sha}` | 1 | Loop `schedule:run` a cada 60s (memory 128M) |
| `reverb` | `crm:{sha}` | 1 | WebSocket server porta 8080 (memory 256M) |
| `mysql` | `mysql:8.0` | 1 | MySQL 8.0 (pinned `node.role=manager`) |
| `pgvector` | `pgvector/pgvector:pg16` | 1 | PostgreSQL 16 + pgvector (memória/RAG Agno, pinned manager) |
| `agno` | `agno-service:{sha}` | 1 | Python FastAPI (memory 128M-512M) |
| `redis` | `redis:7-alpine` | 1 | Cache + Queue + Session (pinned manager) |

- **Redes**: `crm_private` (overlay interno, todos services) + `serverossi` (overlay externo Traefik, SÓ nginx)
- **Domínio**: `https://app.syncro.chat` (Traefik + Let's Encrypt SSL automático, label `letsencryptresolver`)
- **Volumes persistentes**: `mysql_data`, `redis_data`, `pgvector_data`, `storage_data`, `logs_data`, `cache_data`, `public_shared`

> ⚠️ **WAHA roda em stack Swarm SEPARADO** — em `https://waha.matheusrossi.com.br` (não faz parte do `portainer-stack.yml` do CRM). O CRM conecta via env `WAHA_BASE_URL`. O WAHA **NÃO** é `waha.syncro.chat` — é `waha.matheusrossi.com.br` por razão histórica. Qualquer doc ou código que referencie "waha.syncro.chat" está ERRADO e deve ser corrigido.

### Env vars críticas (fonte única: `portainer-stack.yml` — sem `.env.example` tracked)

| Categoria | Env vars |
|-----------|----------|
| **App** | `APP_URL=https://app.syncro.chat`, `APP_KEY`, `APP_ENV=production`, `APP_DEBUG=false`, `APP_TIMEZONE=America/Sao_Paulo`, `APP_LOCALE=pt_BR`, `LOG_CHANNEL=stack`, `LOG_LEVEL=warning`, `TRUSTED_PROXIES=10.0.0.0/8,172.16.0.0/12` |
| **DB / Redis** | `DB_HOST=mysql`, `DB_DATABASE=plataforma360`, `DB_USERNAME=crm`, `DB_PASSWORD`, `REDIS_HOST=redis`, `REDIS_PORT=6379` |
| **Queue / Cache / Session** | Todos em `redis`; `BROADCAST_CONNECTION=reverb` |
| **Reverb** | `REVERB_APP_ID/KEY/SECRET`, `REVERB_HOST=app.syncro.chat`, `REVERB_PORT=443`, `REVERB_SCHEME=https`, `REVERB_SERVER_HOST=reverb`, `REVERB_SERVER_PORT=8080`, `REVERB_ALLOWED_ORIGINS=https://app.syncro.chat`, `VITE_REVERB_*` (injetado via blade, não via build) |
| **Mail (Resend, não SMTP)** | `MAIL_MAILER=resend`, `MAIL_FROM_ADDRESS=noreply@syncro.chat`, `MAIL_FROM_NAME=Syncro`, `RESEND_API_KEY` |
| **LLM / Agno** | `LLM_PROVIDER=openai`, `LLM_MODEL=gpt-4o-mini`, `LLM_API_KEY`, `AGNO_SERVICE_URL=http://agno:8000`, `AGNO_ENABLED=true`, `AGNO_INTERNAL_TOKEN` (== `LARAVEL_INTERNAL_TOKEN` do service agno), `PGVECTOR_URL` (no agno, aponta pra `pgvector:5432`) |
| **WAHA** | `WAHA_BASE_URL=https://waha.matheusrossi.com.br`, `WAHA_API_KEY`, `WAHA_WEBHOOK_SECRET` |
| **WhatsApp Cloud** | `WHATSAPP_CLOUD_APP_ID`, `WHATSAPP_CLOUD_APP_SECRET`, `WHATSAPP_CLOUD_CONFIG_ID` (Embedded Signup Coexistence), `WHATSAPP_CLOUD_VERIFY_TOKEN`, `WHATSAPP_CLOUD_API_VERSION=v22.0`, `WHATSAPP_CLOUD_REDIRECT`, `WHATSAPP_CLOUD_SYSTEM_USER_TOKEN` (permanente Graph API), `WHATSAPP_CLOUD_SYNCRO_BUSINESS_ID` |
| **Facebook / Lead Ads** | `FACEBOOK_CLIENT_ID`, `FACEBOOK_CLIENT_SECRET` (mesmo App do WA Cloud), `FACEBOOK_REDIRECT_URI`, `FACEBOOK_LEADGEN_REDIRECT_URI`, `FACEBOOK_LEADGEN_WEBHOOK_VERIFY_TOKEN`, `FACEBOOK_API_VERSION=v21.0` (⚠️ diferente do WA Cloud v22.0) |
| **Instagram** | `INSTAGRAM_CLIENT_ID`, `INSTAGRAM_CLIENT_SECRET`, `INSTAGRAM_REDIRECT_URI`, `INSTAGRAM_WEBHOOK_VERIFY_TOKEN` |
| **Stripe** | `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET` (prod — principal) |
| **Asaas** | `ASAAS_API_URL`, `ASAAS_API_KEY`, `ASAAS_WEBHOOK_TOKEN` (prod — legacy subs + token increments PIX + partner transfers) |
| **Google** | `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`. `GOOGLE_DEVELOPER_TOKEN`/`GOOGLE_ADS_API_VERSION=v16` existem mas NÃO são usados (legacy Campanhas removido abr/2026) |
| **ElevenLabs** | `ELEVENLABS_API_KEY`, `ELEVENLABS_VOICE_ID`, `ELEVENLABS_MODEL_ID=eleven_multilingual_v2` |
| **Web Push** | `VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY`, `VAPID_SUBJECT=mailto:admin@syncro.chat` |
| **Sentry (APM + errors)** | `SENTRY_LARAVEL_DSN`, `SENTRY_ENVIRONMENT=production`, `SENTRY_TRACES_SAMPLE_RATE=0.1`, `SENTRY_PROFILES_SAMPLE_RATE=0.1`, `SENTRY_SEND_DEFAULT_PII=false` |

Ao adicionar nova integração: **sempre acrescentar env var no `portainer-stack.yml`** + colar no Portainer UI.

### IMPORTANTE: VITE_* no Docker
`npm run build` roda SEM build args. `VITE_*` do Portainer são RUNTIME only.
**Padrão correto**: Injetar config no servidor via Blade (`window.reverbConfig`).

### Permissão de storage/logs (fix permanente 2026-04-14)
Bug histórico: arquivo de log criado por um container (scheduler ou queue) com umask restritivo travava os outros que tentavam escrever no mesmo arquivo → webhooks WAHA retornavam 500 → mensagens não chegavam no CRM. Antes era "correção" manual toda semana:
```bash
docker exec -u root $(docker ps -q -f name=syncro_app) chmod -R 775 /var/www/storage/logs
```

**Fix definitivo no `docker/entrypoint.sh`** (rodando em todo boot):
```bash
umask 002
export UMASK=002
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
find /var/www/storage /var/www/bootstrap/cache -type d -exec chmod 2775 {} +
find /var/www/storage /var/www/bootstrap/cache -type f -exec chmod 664 {} +
```

- **`2775` nos diretórios** — o `2` é **setgid**: arquivos novos criados dentro do dir **herdam o grupo** (`www-data`), independentemente do usuário que criou
- **`664` nos arquivos** + **`umask 002`** — arquivos novos nascem `rw-rw-r--`, todos os processos do grupo `www-data` conseguem escrever
- Não precisa mais chmod manual, **NUNCA**. Se ainda der problema, é caso novo pra investigar (não aplicar o workaround antigo — quebra o fix permanente).

---

## 13. Convenções de Código

### PHP
- `declare(strict_types=1)` em todo arquivo
- PSR-12, typed properties, return types
- Tokens OAuth: sempre `encrypt()` / `decrypt()`
- `LeadEvent::create()` → sempre passar `'created_at' => now()` ($timestamps = false)
- `Sale` e `LostSale` são imutáveis (sem updated_at)

### Blade
- **NUNCA** usar `@php($var = value)` inline → compila para `<?php($var = value)` sem `?>` → quebra toda a view
- **SEMPRE** usar bloco:
  ```blade
  @php
      $var = value;
  @endphp
  ```
- `@json()` com closures/arrays multi-linha falha → usar `{!! json_encode($var) !!}`

### Banco
- `Schema::defaultStringLength(191)` no AppServiceProvider (MySQL do WAMP tem key limit de 1000 bytes)
- Evitar índices compostos com 3+ colunas varchar longas
- `contact_picture_url` deve ser `TEXT` (URLs do WhatsApp excedem 191 chars)
- `phone` é `VARCHAR(30)` (LIDs podem ter 14+ dígitos)

### Frontend
- API helper global: `window.API.get()`, `.post()`, `.put()`, `.delete()`
- `window.escapeHtml()` para sanitizar
- Drawer compartilhado: definir `LEAD_SHOW`, `LEAD_STORE`, `LEAD_UPD`, `LEAD_DEL` por página
- **Drawer→Modal pattern**: páginas grandes (12+ páginas) trocaram drawer lateral por modal centrado via `partials/_drawer-as-modal.blade.php`
- **Cmd+K**: shortcut global pra busca via `GET /busca?q=...` (controller `GlobalSearchController`)
- **Tour interativo**: Driver.js v1, definir steps em `_tour.blade.php`, marca completion via `POST /tour/complete`

### WhatsApp (WAHA + Cloud API) — SOLID enforcement

**Regra de ouro**: use a Foundation SOLID (`app/Services/Whatsapp/`) em qualquer código novo. Nunca espalhe `if ($instance->provider === 'cloud_api') {...}` por Blade/controller/job — delegue pros services compartilhados.

- **NUNCA** usar `WhatsappInstance::first()` pra resolver instance. Use `InstanceSelector::selectFor($tenantId, $context)` que prioriza explicit → conversation → entity → primary.
- **NUNCA** instanciar `new WahaService(...)` direto em código novo. Use `\App\Services\WhatsappServiceFactory::for($instance)` — devolve o service correto por provider.
- **NUNCA** construir chatId com `$phone . '@c.us'` hardcoded. Use `ChatIdResolver::for($instance, $phone, $isGroup, $conv)` — WAHA ganha sufixo apropriado (`@c.us`/`@g.us`/`@lid` do histórico GOWS), Cloud recebe número puro.
- **NUNCA** chamar `WhatsappMessage::create(...)` direto pra mensagem outbound. Use `OutboundMessagePersister::persist($conv, $type, $body, $sendResult, $sentBy, ...)` — popula `waha_message_id`/`cloud_message_id` conforme provider, atualiza `last_message_at`, broadcasta Reverb.
- **NUNCA** checar janela 24h com `diffInHours` inline. Use `ConversationWindowChecker::isOpen($conv)` — single source of truth.
- **Capabilities** (UI + actions): use `$instance->supportsTemplates()`, `->supportsInteractiveButtons()`, `->supportsInteractiveList()`, `->hasWindowRestriction()` em vez de `provider === 'cloud_api'` em tudo.
- **Contratos segregados** (ISP): antes de chamar método exclusivo Cloud, type-check:
  ```php
  if ($service instanceof \App\Contracts\SupportsMessageTemplates) {
      $service->sendTemplate(...);
  }
  ```
- Operações **WAHA-specific** (createSession, QR, history import, group ops, master toolbox) podem chamar `WahaService` direto — sem equivalente no Cloud.
- Listagens da página de Integrações: filtrar por `provider='waha'` OR `NULL` no card WAHA, e `provider='cloud_api'` no card Cloud API. Bug histórico: commit `2535d46`.

### Mensagens outbound (sent_by)
- **TODA** criação direta de `WhatsappMessage::create(['direction' => 'outbound', ...])` (e equivalentes IG/Website) DEVE setar `sent_by` (e `sent_by_agent_id` quando aplicável). Spots já cobertos: ver seção 5 → "Autoria de mensagens".
- Se a fonte automática **não cria** a mensagem direto (ex: chatbot WhatsApp manda via WAHA e o webhook salva via echo), DEVE registrar intent no cache antes do `sendText`:
  ```php
  Cache::put("outbound_intent:{$conv->id}:" . md5(trim($body)), [
      'sent_by' => 'chatbot',
      'sent_by_agent_id' => null,
  ], 120);
  ```
  O `ProcessWahaWebhook` lê via `Cache::pull` quando salva mensagem outbound. Sem intent = `human_phone` (mandado do celular do dono). TTL 120s.

### Agentes IA (Agno)
- **NUNCA** instanciar config de agent in-memory expecting it to persist. O `_agent_configs` do `agno-service/agent_factory.py` é dict Python — perde tudo no restart do container. Pra adicionar novos agents, sempre via `AgnoService::configureFromAgent($agent)` (que faz POST `/agents/{id}/configure`). O `entrypoint.sh` reconfigura todos no boot via `agno:reconfigure-all`.
- **NUNCA** duplicar a lógica de mapping AiAgent → payload Agno em novos comandos. Use `AgnoService::configureFromAgent(AiAgent $agent)` — método único centralizado.
- **Knowledge files / RAG**: ao subir arquivo via `AiAgentController::uploadKnowledgeFile`, o controller já chama `AgnoService::indexFile($agent->id, $tenantId, $fileId, $text, $filename)` que indexa no pgvector. Pra forçar re-index: `php artisan agno:reindex-knowledge --file=N`. Pra apagar arquivo + chunks: `AiAgentController::deleteKnowledgeFile` (já chama `AgnoService::deleteKnowledgeFile()` em cascade).
- **Custo de embeddings** é tracked via `AiUsageLog` com `type='knowledge_indexing'`, `model='text-embedding-3-small'`. Não esqueça de logar se criar novo spot que indexa.

### Feature Flags
- Pra esconder UI condicional por tenant: `@if(\App\Models\FeatureFlag::isEnabled('slug', $tenantId)) ... @endif` no Blade
- Pra bloquear backend: same helper no controller. Não usar permissões nem roles pra isso — feature flag é a fonte da verdade.
- Toggle no painel master `/master/features`. NÃO hardcode flags em código.

### Tags (refactor em coexistência — Fase 3)
- Models que têm tags hoje: `Lead`, `WhatsappConversation`, `InstagramConversation`, `WebsiteConversation`. Todos usam o trait `App\Models\Traits\HasTags`.
- **Pra LER tags em código novo:** prefira `$model->tagModels` (Eloquent collection) ou o accessor `$model->tag_names` (array de strings). Mas a coluna JSON `$model->tags` ainda funciona porque Fase 3 escreve nos dois lugares.
- **Pra ESCREVER tags em código novo:** SEMPRE use os métodos do trait — `attachTagsByName(array $names)` (adiciona, mantém os existentes), `syncTagsByName(array $names)` (substitui o set inteiro), `detachTagsByName(array $names)`. **Adicionalmente** escreva também na coluna JSON pra dual write (dispatch é feito automaticamente nos pontos atuais — `LeadController`, `AutomationEngine`, `NurtureSequenceService`, `ConversationAnalystService`, `AiAgentWebChatService`, `ProcessFacebookLeadgenWebhook`, `KanbanImport`, `WhatsappController::updateContact` e `updateConversationContact`).
- **NUNCA** criar tag manualmente via `WhatsappTag::create(...)` em código novo. Use `Tag::firstOrCreate(['tenant_id' => $t, 'name' => $n], ['color' => '#3B82F6', 'sort_order' => 0, 'applies_to' => 'both'])` — ou melhor, deixe o trait `HasTags` auto-criar via `attachTagsByName()`.
- **Endpoint genérico do inbox:** `PUT /chats/inbox/{channel}/{conversation}/contact` (route name `chats.inbox.conversations.contact`) é o padrão pra atualizar nome/telefone/tags em qualquer canal. Não invente endpoint canal-específico novo.
- **Conversation polimórfica:** se você precisa receber "uma conversa de qualquer canal", aceite `App\Contracts\ConversationContract` (interface) — não `WhatsappConversation` específico. Use `app(App\Services\ConversationResolver::class)->resolve($channel, $id)` quando precisar resolver por string de canal + ID.
- Plano completo do refactor: `~/.claude/plans/eager-seeking-corbato.md`. Não pule fases sem ler o plano.

### Formulários
- **NUNCA reintroduza iframe** pra embed de formulário em site externo. User rejeitou explicitamente em 2026-04-14. O SDK nativo (`FormPublicController@script`) é a única via suportada — renderiza direto no DOM do cliente com CSS scoped `#syncro-form-{id}`.
- **SDK SEMPRE sem chrome**: zero logo, zero título, zero "Criado com Syncro", zero card externo. Logo/footer só nas views hospedadas (`public*.blade.php`).
- **Submissões via SDK** NÃO passam pelo middleware `web` (sem CSRF). Usam rotas `/api/form/{slug}/*` com CORS `*`. Confirmar que qualquer nova rota pro SDK seja pública, idempotente ao CSRF e retorne headers CORS.
- **Config JSON do SDK** DEVE sanitizar: nunca expor `mappings`, `notify_emails`, `assigned_user_id`, `pipeline_id`, `stage_id`, `sequence_id`, `list_id` — esses são internos do CRM.
- **Novos tipos de campo**: adicionar renderização em 4 lugares simultaneamente: `public.blade.php` (classic), `public-conversational.blade.php`, `public-multistep.blade.php` E no JS do SDK em `FormPublicController@buildSdkJs`. Se esquecer de 1, o tipo só funciona em alguns modos.
- **UI do admin — "Submissões" NÃO, "Envios" SIM** — convenção do user desde abril/2026.
- **Cores do form**: NÃO use preset único com 9 cores via um card só. Cada cor tem sua própria linha de bolinhas (7 presets contextuais) + botão `+` custom. Padrão em `edit.blade.php::buildColorRows()`. Se precisar de novo campo de cor, adicione em `COLOR_OPTIONS`.

---

## 14. Toolbox Master (super_admin)

14 tools disponíveis em `/master/ferramentas`:

| Tool | Função |
|------|--------|
| `sync-group-names` | Sincroniza nomes de grupos via WAHA |
| `clear-leads` | Apaga todos os leads do tenant |
| `clear-cache` | Limpa cache Redis |
| `fix-unread-counts` | Recalcula contadores de não-lidas |
| `reset-password` | Reset senha de usuário |
| `wa-status` | Verifica status da instância WhatsApp |
| `close-conversations` | Fecha conversas em batch |
| `cleanup-lid-conversations` | Remove conversas com LID sem phone |
| `resolve-lid-conversations` | Tenta resolver LID→phone |
| `reimport-wa-history` | Reimporta histórico do WhatsApp |
| `reimport-empty-conversations` | Reimporta conversas sem mensagens |
| `sync-profile-pictures` | Sincroniza fotos de perfil |
| `export-tenant-stats` | Exporta estatísticas do tenant |
| `check-user-account` | Valida dados do usuário |

---

## 15. UI / Design System

### Regras Absolutas
- **SEM GRADIENTE** — usar azul sólido `#0085f3` (hover: `#0070d1`)
- Cards: `background:#fff; border:1.5px solid #e8eaf0; border-radius:14px;`
- Botões primários: `background:#0085f3; color:#fff; border-radius:9px; font-size:13px; font-weight:600;`
- Botões secundários: `background:#eff6ff; color:#0085f3; border:1.5px solid #bfdbfe; border-radius:8-10px;`
- Status badges: `.status-badge.active/trial/inactive`
- Cabeçalho de cards: `padding:14-16px 20-22px; border-bottom:1px solid #f0f2f7; font-size:14px; font-weight:700; color:#1a1d23;`
- Cores de texto: primário `#1a1d23`, secundário `#374151`, muted `#6b7280`, placeholder `#9ca3af`

---

## 16. Scheduled Tasks (Cron)

**IMPORTANTE**: comandos que rodam a cada minuto usam `withoutOverlapping(5)` — se o processo crashar, o mutex no Redis expira em **5 minutos** (não 24h do default Laravel). Bug histórico: 2026-04-10, mensagens agendadas ficaram pending por horas porque o mutex de `whatsapp:send-scheduled` travou. Se suspeitar que o cron parou, rodar `php artisan schedule:clear-cache`.

| Comando | Frequência | Função |
|---------|-----------|--------|
| `billing:check-trials` | Diário 06:00 | Verifica trials expirados |
| `whatsapp:send-scheduled` | A cada minuto | Envia mensagens agendadas |
| `whatsapp:send-event-reminders` | A cada minuto | Envia lembretes pendentes de eventos |
| `automations:process-date-triggers` | Diário 08:00 | Automações por data |
| `ai:followup` | A cada 10 min | Follow-up automático de IA |
| `scoring:decay` | Diário 09:00 | Aplica decay de score para leads inativos |
| `sequences:process` | A cada minuto | Processa steps de nurture sequences |
| `goals:process-recurrence` | Diário 00:05 | Snapshots e renovação de metas recorrentes |
| `goals:check-alerts` | Diário 09:00 | Alertas de performance de metas |
| `partners:release-commissions` | Diário 06:30 | Libera comissões após período de carência |
| `master:weekly-report` | Semanal (sexta 12:00) | Relatório semanal para grupo WhatsApp master |
| `upsell:evaluate` | A cada 6 horas | Avalia triggers de upsell por tenant |
| `leads:detect-duplicates` | Diário 03:30 | Detecta duplicatas de leads por phone/email |
| `users:send-reengagement` | Diário 10:00 | Envia emails/WA de reengajamento (7d/14d/30d) pra usuários inativos |
| `whatsapp:cloud-token-health` | Diário 09:30 | Checa debug_token Cloud API, atualiza `whatsapp_instances.token_status`, dispara notification pro admin se expirando/expirado/invalid |
| `whatsapp:sync-templates` | Diário 04:00 | Sync Message Templates HSM da Meta pra `whatsapp_templates` (status, quality_rating, rejected_reason). Log quando Meta reclassifica categoria (UTILITY→MARKETING). |

---

## 17. Estrutura de Arquivos Chave

```
app/
  Http/Controllers/
    Tenant/          — ~53 controllers (dashboard, CRM, leads, chats, chatbot, IA, tasks, products, scoring, sequences, NPS, goals, settings, forms)
    Tenant/Forms/    — subdir (FormController, FormBuilderController, FormMappingController, FormSubmissionController)
    Tenant/LeadMergeController.php
    Tenant/GlobalSearchController.php   — Busca global Cmd+K
    Tenant/TourController.php           — Tour interativo (complete/reset)
    Tenant/WhatsappTemplateController.php — CRUD Templates HSM + sync + upload sample + envio pelo chat
    Master/          — ~23 controllers (tenants, plans, toolbox, logs, system, partners, features, reengagement, etc)
    Master/FeatureController.php        — Painel de feature flags
    Master/ReengagementController.php   — Templates de reengajamento
    Auth/            — 2 controllers (login, register, agency register)
    Api/             — 4 controllers (leads API, widget, agno tools, stripe webhook)
    Cs/              — 1 controller (Customer Success)
    WhatsappWebhookController.php       — Webhook WAHA
    WhatsappCloudWebhookController.php  — Webhook WhatsApp Cloud API (Meta)
    InstagramWebhookController.php
    FacebookLeadgenWebhookController.php — Webhook Facebook Lead Ads
    AsaasWebhookController.php          — Asaas: token increments + partner transfers + legacy subs
    StripeWebhookController.php         — Stripe: subscriptions + recurring invoices
    FormPublicController.php            — Público: render formulário, submit, SDK JS embed (inline/popup)
  Console/Commands/  — 31 commands (billing, whatsapp, ai, scoring, sequences, goals, partners, upsell, master, reengagement, tags backfill, cloud token health, template sync)
    DetectDuplicateLeads.php        — Scan diário de duplicatas
    SendReengagement.php            — Envio de emails/WA de reengajamento
    BackfillTags.php                — Migra whatsapp_tags + colunas JSON `tags` pra estrutura polimórfica `tags`+`taggables`. Idempotente. `--dry-run` e `--tenant=N`.
    ReconfigureAgnoAgents.php       — `agno:reconfigure-all`: itera todos agents `use_agno=true is_active=true` e reconfigura no Agno (POST /configure). Roda no entrypoint do app pra repopular cache in-memory perdido em restart.
    ReindexAgnoKnowledge.php        — `agno:reindex-knowledge --agent= --file= --missing`: reindexa knowledge files no Agno.
    BackfillMessageAuthorship.php   — `messages:backfill-authorship --dry-run --tenant=N`: preenche `sent_by` retroativo via heurística.
    CheckWhatsappCloudTokens.php    — `whatsapp:cloud-token-health`: verifica debug_token de cada WABA via Graph API, atualiza `token_status`, dispara notification.
    SyncWhatsappTemplates.php       — `whatsapp:sync-templates`: sync status dos templates HSM com a Meta.
    GoalAlerts.php                  — `goals:check-alerts`: dispara notifs de alerta de performance.
    ProcessGoalRecurrence.php       — `goals:process-recurrence`: snapshots diários + renovação de metas recorrentes.
    AiFollowUpCommand.php           — `ai:followup`: respeita `followup_strategy` (smart/template/off) + `ConversationWindowChecker` pra Cloud API.
  Jobs/
    ProcessWahaWebhook.php             — Webhook WhatsApp WAHA (core)
    ProcessWhatsappCloudWebhook.php    — Webhook WhatsApp Cloud API (Meta)
    ProcessInstagramWebhook.php        — Webhook Instagram
    ProcessFacebookLeadgenWebhook.php  — Webhook Facebook Lead Ads
    ProcessAiResponse.php              — Resposta IA com debounce
    ProcessChatbotStep.php             — Execução de fluxo chatbot
    ImportWhatsappHistory.php          — Import de histórico WA
    ProcessNurtureStep.php             — Execução de nurture sequence step
    ProcessScoringEvent.php            — Cálculo de lead score
    SendEventReminder.php              — Envio de lembretes de eventos
    ProcessGoalRecurrence.php          — Snapshots e renovação de metas
    DispatchAutomationWebhookJob.php   — Action `send_webhook` das automações
    ExtractLeadDataJob.php             — Action `extract_lead_data` (IA extrai campos da conversa)
  Services/
    WahaService.php                 — API client WAHA (implements WhatsappServiceContract)
    WhatsappCloudService.php        — API client Meta Graph v22.0 (implements WhatsappServiceContract)
    WhatsappServiceFactory.php      — Factory: retorna service correto por $instance->provider
    FacebookLeadAdsService.php      — Graph API client pra Lead Ads (pages, forms, lead retrieval)
    InstagramService.php            — API client Meta/Instagram
    AgnoService.php                 — API client Agno (IA): chat, configureAgent, configureFromAgent, indexFile, searchKnowledge, deleteKnowledgeFile, storeMemory
    AiAgentService.php              — Builder de system prompt + buildHistory + sendWhatsappReply + sendMediaReply
    LeadDataExtractorService.php    — IA extrai campos do lead a partir do histórico de conversa
    AutomationEngine.php            — Motor de automações
    WebhookDispatcherService.php    — Dispatcher de webhooks de saída (HMAC + retry)
    TokenQuotaService.php           — Controle de quota de tokens IA por tenant
    ChatbotVariableService.php      — Variáveis de chatbot
    AsaasService.php                — Gateway Asaas
    StripeService.php               — Gateway Stripe
    ScoringService.php              — Motor de lead scoring
    NurtureService.php              — Motor de nurture sequences
    NpsService.php                  — Envio e processamento NPS
    SalesGoalService.php            — Cálculo de metas de vendas
    TaskService.php                 — CRUD e lógica de tasks
    ProductService.php              — CRUD de produtos e catálogo
    PartnerService.php              — Comissões e saques de parceiros
    LeadListService.php             — Listas estáticas e dinâmicas
    ElevenLabsService.php           — Text-to-speech via ElevenLabs
    EventReminderService.php        — Lembretes de eventos Google Calendar
    WhatsappButtonService.php       — Botões WhatsApp para sites
    ExportService.php               — Exportação de dados
    ReportService.php               — Geração de relatórios
    DashboardService.php            — Dados do dashboard
    NotificationService.php         — Envio de notificações
    WebhookDeliveryService.php      — Entrega de webhooks de saída
    DuplicateLeadDetector.php       — Detecção fuzzy de leads duplicados (phone/email/name)
    LeadMergeService.php            — Merge atômico de leads (21 relações)
    SophiaActionExecutor.php        — Executor de ações da Sophia (whitelist + rate limit)
    ConversationResolver.php        — Mapeia channel string ('whatsapp'|'instagram'|'website') + ID -> ConversationContract concreto. Usado pelo endpoint genérico do inbox.
    Forms/                          — subdir: FormSubmissionService, FormLeadCreator, FormNotifier
    Whatsapp/                       — subdir: Foundation SOLID pra compatibilidade Cloud API (ver seção 11.8)
      ChatIdResolver.php            — chat_id por provider (SRP)
      InstanceSelector.php          — resolução de WhatsappInstance (SRP)
      ConversationWindowChecker.php — janela 24h Meta (single source of truth)
      OutboundMessagePersister.php  — persiste WhatsappMessage sync + broadcast Reverb (usado por chatbot/agente/automação/nurture/scheduled)
      WhatsappTemplateService.php   — CRUD local + sync Meta + send de Message Templates HSM
  Contracts/
    WhatsappServiceContract.php      — Interface comum WAHA + Cloud API (sendText, sendImage, sendList, sendReaction, sendVoice, etc)
    SupportsMessageTemplates.php     — ISP: só Cloud implementa (sendTemplate)
    SupportsInteractiveMessages.php  — ISP: só Cloud implementa (sendInteractiveButtons até 3)
    ConversationContract.php         — Interface comum dos 3 conversation models
  Mail/
    ReengagementEmail.php           — Email de reengajamento (usa _layout shared)
  Rules/
    SafeFile.php                    — Validação de upload sem MIME malicioso
    SafeImage.php                   — Validação de imagem
  Support/
    PipelineTemplates.php           — Biblioteca de templates de pipeline (i18n via lang/pipeline_templates.php)
  Events/           — 6 eventos broadcasted (WhatsApp/Instagram message/conversation created/updated)
  Notifications/    — 11 notifications (goal alerts, NPS, partner, billing, system)
  Models/
    Traits/BelongsToTenant.php      — Global Scope multi-tenant
    Traits/HasTags.php              — Trait polimórfico de tags (tagModels(), attachTagsByName, syncTagsByName, detachTagsByName, accessor tag_names). Em uso por Lead + 3 conversation models.
    Tag.php                         — Catálogo único de tags por tenant. 4 morphedByMany pros taggables. Substitui WhatsappTag.
    WhatsappTag.php                 — LEGACY. Ainda existe, ainda usado pelo CRUD em /configuracoes/tags. Em código novo use Tag::.
    Lead.php, WhatsappConversation.php, WhatsappInstance.php (provider+cloud_api fields),
    AiAgent.php, ChatbotFlow.php, Task.php, Product.php, ScoringRule.php,
    NurtureSequence.php, NpsSurvey.php, SalesGoal.php, PartnerCommission.php,
    WhatsappButton.php, EventReminder.php, LeadDuplicate.php,
    FeatureFlag.php, ReengagementTemplate.php,
    FacebookLeadFormConnection.php, FacebookLeadFormEntry.php, etc.
  Providers/
    AppServiceProvider.php          — defaultStringLength(191)

agno-service/
  main.py              — FastAPI endpoints (chat, configure, index-file, knowledge/search, knowledge/{id} delete, memories/*)
  agent_factory.py     — Criação/cache de agentes (in-memory, repopulado via agno:reconfigure-all no boot)
  memory_store.py      — pgvector: agent_memories (resumos de conversa) + generate_embedding helper compartilhado
  knowledge_store.py   — pgvector: agent_knowledge_chunks (RAG real). chunk_text, index_knowledge_file, search_knowledge, delete_chunks_by_file
  schemas.py           — Request/Response schemas (ChatRequest agora aceita knowledge_chunks, current_datetime, period_of_day, greeting)
  formatter.py         — Humanização de respostas. max_block agora é parâmetro (vem do max_message_length de cada agent), não constante
  tools/               — Function calling tools

resources/
  js/
    app.js                          — API helper global + escapeHtml + Cmd+K global search
    chatbot-builder.jsx             — React Flow chatbot builder visual
  views/
    tenant/layouts/app.blade.php    — Layout principal
    tenant/layouts/_tour.blade.php  — Tour Driver.js (definição dos passos)
    tenant/crm/kanban.blade.php     — Kanban board
    tenant/crm/_import-modal.blade.php — Import multistep (partial isolado com IIFE — Upload > Mapping > Review com acoes bulk/single)
    tenant/whatsapp/index.blade.php — Chat inbox
    tenant/chatbot/builder.blade.php — Chatbot builder (host React)
    tenant/tasks/index.blade.php    — Lista de tarefas
    tenant/goals/index.blade.php    — Metas de vendas
    tenant/leads/duplicates.blade.php — Fila de duplicatas para revisão
    tenant/settings/integrations.blade.php — Cards de integração (WhatsApp WAHA + Cloud API + Lead Ads + etc.)
    tenant/settings/_wacloud-callback.blade.php — View do popup callback OAuth (fallback velho)
    partials/_drawer-as-modal.blade.php — Partial reusável: drawer responsivo que vira modal centrado em desktop
    emails/_layout.blade.php        — Layout email shared (header/footer bilingual)
    emails/reengagement.blade.php   — Template de reengajamento
    master/features/index.blade.php — Painel master de feature flags (toggle global/per-tenant)
    master/reengagement/index.blade.php — Painel master de templates de reengajamento

public/widget.js         — Widget de chat para sites
bootstrap/app.php        — Middleware + Schedule
routes/web.php           — Rotas web
routes/api.php           — Rotas API
```

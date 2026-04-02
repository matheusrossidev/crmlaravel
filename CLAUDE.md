# Syncro CRM — Guia Completo da Plataforma

> Este documento é a referência definitiva para qualquer dev ou IA que trabalhe neste codebase.
> Última atualização: 2026-04-01

---

## 1. Visão Geral

**Syncro** é uma plataforma 360 de marketing e CRM multi-tenant com:
- Pipeline de vendas (Kanban)
- Chat inbox unificado (WhatsApp + Instagram + Website)
- Agentes de IA com memória e tools (via microsserviço Agno)
- Chatbot builder visual multi-canal (React Flow)
- Automações por trigger
- Campanhas com rastreamento UTM
- Billing via Asaas (PIX, cartão) e Stripe (internacional)
- Programa de parceiros com comissões e cursos
- Tasks, produtos, lead scoring, nurture sequences, NPS, metas de vendas

### Stack

| Camada | Tecnologia |
|--------|-----------|
| Backend | Laravel 11, PHP 8.2 (dev) / 8.3 (prod) |
| Banco | MySQL 8.0 |
| Cache/Queue/Session | Redis 7 |
| Frontend | AdminLTE 4.0.0-rc6, Bootstrap 5, jQuery, Chart.js, Toastr, DataTables, React (chatbot builder only) |
| Build | Vite |
| Real-time | Laravel Reverb (WebSocket) |
| WhatsApp | WAHA Plus (GOWS engine) |
| Pagamentos | Asaas (Brasil), Stripe (internacional) |
| IA | Agno (FastAPI + pgvector), OpenAI/Anthropic/Gemini |
| Deploy | Docker Swarm, Portainer, Traefik SSL |
| CI/CD | GitHub Actions → Docker Hub → Portainer |

### Stats
~89 models, 36 services, 10 jobs, 20 commands, 6 events, 11 notifications

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
`Tenant`, `User`, `PipelineStage`, `AiConfiguration`, `PlanDefinition`, `TokenIncrementPlan`, `UpsellTrigger`, `WebhookLog`, `AuditLog`, `PartnerRank`, `PartnerResource`, `PartnerCourse`, `PartnerLesson`, `MasterNotification`

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
- **Lead** — phone, email, company, value, tags (JSON), custom fields, UTM tracking, pipeline_id, stage_id, status (active/archived/merged), merged_into, merged_at
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

### WhatsApp
- **WhatsappInstance** — session_name (WAHA), phone_number, history_imported flag
- **WhatsappConversation** — phone, lid (interno), status (open/closed/expired), tags (JSON), assigned_user_id, department_id, ai_agent_id, chatbot_flow_id/node_id/variables, followup counters
- **WhatsappMessage** — waha_message_id (UNIQUE), direction, type, body, media_url, ack, sent_at

### Instagram
- **InstagramInstance** — ig_business_account_id, username, access_token (encrypted), status
- **InstagramConversation** — igsid, contact_name, contact_username, ai_agent_id, chatbot_flow_id
- **InstagramMessage** — ig_message_id (UNIQUE), direction, type, body, media
- **InstagramAutomation** — Regras de auto-reply por post (keywords, reply_comment, dm_message arrays), media_type

### Website
- **WebsiteConversation** — visitor_id, flow_id, ai_agent_id, UTM/fbclid/gclid tracking
- **WebsiteMessage** — direction, type, body

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
- **PlanDefinition** — Planos disponíveis com features_json
- **TokenIncrementPlan** — Pacotes de tokens para compra
- **TenantTokenIncrement** — Tokens comprados (asaas_payment_id, status, paid_at)
- **PaymentLog** — tenant_id, type, description, amount, asaas_payment_id, status, paid_at

### Campanhas
- **Campaign** — platform, external_id, utm_*, metrics_json, budget
- **AdSpend** — Gasto por campanha/dia
- **OAuthConnection** — Tokens OAuth (encrypted) para Facebook/Google/Instagram

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

### Outros
- **ScheduledMessage** — Mensagens agendadas
- **ApiKey** — Chaves API com permissions_json
- **WebhookConfig** — Webhooks de saída
- **UpsellTrigger** / **UpsellTriggerLog** — Triggers de upsell
- **Feedback** — user_id, type, area, title, description, impact, priority, status
- **ElevenlabsUsageLog** — tenant_id, agent_id, conversation_id, characters_used
- **UserConsent** — user_id, consent_type, policy_version, accepted_at
- **MasterNotification** — tenant_id, title, body, type

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

### Dashboard (`auth`, `tenant`)
| Método | URI | Nome |
|--------|-----|------|
| GET | `/` | dashboard |
| POST | `/dashboard/config` | dashboard.config |
| GET | `/dashboard/leads-chart` | dashboard.leads-chart |
| POST | `/help-chat` | help.chat |
| POST | `/help-chat/execute` | help.execute |

### CRM Kanban (`/crm`)
| Método | URI | Nome |
|--------|-----|------|
| GET | `/crm` | crm.index |
| GET | `/crm/poll` | crm.poll |
| GET | `/crm/exportar` | crm.export |
| POST | `/crm/importar` | crm.import |
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

### Campanhas (`/campanhas`)
- CRUD + reports + drill-down + analytics + PDF export

### Metas de Vendas (`/metas`)
- CRUD metas + snapshots + alertas de performance

### Configurações (`/configuracoes`)
- **Perfil**: `/configuracoes/perfil`
- **Pipelines**: `/configuracoes/pipelines` + stages
- **Motivos de perda**: `/configuracoes/motivos-perda`
- **Usuários**: `/configuracoes/usuarios`
- **Departamentos**: `/configuracoes/departamentos`
- **Tags**: `/configuracoes/tags`
- **Campos extras**: `/configuracoes/campos-extras`
- **Produtos**: `/configuracoes/produtos` (CRUD + categorias)
- **Scoring**: `/configuracoes/scoring` (CRUD regras de pontuação)
- **Sequências Nurture**: `/configuracoes/sequencias` (CRUD + enroll leads)
- **Pesquisas NPS**: `/configuracoes/pesquisas` (CRUD + envio)
- **Botões WhatsApp**: `/configuracoes/botoes-whatsapp` (CRUD + tracking)
- **API Keys**: `/configuracoes/api-keys`
- **Integrações**: `/configuracoes/integracoes` (Facebook, Google, WhatsApp, Instagram OAuth)
- **Automações IG**: `/configuracoes/instagram-automacoes`
- **Automações**: `/configuracoes/automacoes`
- **Notificações**: `/configuracoes/notificacoes`
- **Cobrança**: `/configuracoes/cobranca`

### Parceiros (`/parceiro`)
- **Dashboard**: `/parceiro` (stats, rank, comissões)
- **Comissões**: `/parceiro/comissoes` (histórico, disponíveis)
- **Saques**: `/parceiro/saques` (solicitar, histórico)
- **Recursos**: `/parceiro/recursos` (materiais de apoio)
- **Cursos**: `/parceiro/cursos` (cursos + lições + certificados)

### Feedback (`/feedback`)
- CRUD feedbacks dos usuários

### Master (`/master`, `super_admin`)
- Dashboard, Empresas (tenants), Planos, Usuários, Token Increments, Upsell Triggers, Uso, Logs, Sistema, Ferramentas, Notificações

### API (`/api`)
- **Widget** (público): `/api/widget/{token}/*`
- **v1** (api_key): `/api/v1/leads/*`, `/api/v1/pipelines`, `/api/v1/campaigns/*`
- **Internal Agno**: `/api/internal/agno/*`

### Webhooks (público)
| URI | Handler |
|-----|---------|
| `POST /api/webhook/waha` | WhatsappWebhookController |
| `GET/POST /api/webhook/instagram` | InstagramWebhookController |
| `POST /api/webhook/asaas` | AsaasWebhookController |
| `POST /api/webhook/stripe` | StripeWebhookController |

---

## 5. WhatsApp (WAHA)

### Fluxo de Mensagem Inbound
```
WAHA → POST /api/webhook/waha
  → WhatsappWebhookController::handle()
    → Valida HMAC (WAHA_WEBHOOK_SECRET)
    → ProcessWahaWebhook::dispatchSync($payload)
      → Cache::add("waha:processing:{msgId}", 1, 10) — dedup atômico
      → Resolve phone do JID (limpa @c.us/@lid/@s.whatsapp.net)
      → Se @lid: tenta resolver via WAHA /lids/{lid} → contacts API
      → Se LID não resolvido: BLOQUEIA (não salva conversa)
      → Cria/atualiza WhatsappConversation
      → Auto-assign AI agent (se auto_assign ativo)
      → Salva WhatsappMessage (UNIQUE waha_message_id)
      → Dispara chatbot OU IA (se atribuído)
      → Broadcast via Reverb
```

### Resolução de LID
O WAHA GOWS engine pode enviar `from: XXX@lid` em vez de `@c.us`. O LID é um identificador interno do WhatsApp/Meta.

**Regras:**
1. Se `from` termina com `@lid` → `$fromIsLid = true`
2. Tenta resolver via `WahaService::getPhoneByLid($lid)` → `GET /api/{session}/lids/{lid}`
3. Se falhar: tenta `getAllLids()` para batch mapping
4. Se o phone NÃO foi resolvido E `$fromIsLid = true` → **BLOQUEIA** a mensagem
5. Se resolvido: salva phone resolvido + armazena lid original na coluna `lid`

**IMPORTANTE**: Nunca usar `strlen($phone) > 13` para detectar LID — usar o flag `$fromIsLid` do sufixo `@lid`.

### WahaService — Métodos principais
- `sendText($session, $chatId, $text)` — Envia texto
- `sendImage($session, $chatId, $url, $caption)` — Envia imagem por URL
- `sendList($session, $chatId, $title, $desc, $button, $sections, $footer)` — Lista interativa
- `sendVoice($session, $chatId, $audioBase64)` — Envia áudio
- `getChatPicture($session, $chatId)` — Foto de perfil (com fallback @lid)
- `getPhoneByLid($session, $lid)` — Resolve LID→phone
- `getAllLids($session)` — Batch mapping LID→phone
- `getChatMessages($session, $chatId, $limit, $offset)` — Histórico

### Import de Histórico
Job `ImportWhatsappHistory`: busca conversas e mensagens via WAHA API, cria WhatsappConversation + WhatsappMessage. Flag `history_imported` na instance.

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
- `getProfile($igsid)` — Dados do contato

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
- `main.py` — Endpoints: `/chat`, `/agents/{id}/configure`, `/agents/{id}/memories/*`
- `agent_factory.py` — Cria/cacheia agentes por `tenant_id:agent_id`, monta instructions com contexto
- `memory_store.py` — PostgreSQL + pgvector para memória de conversas
- `schemas.py` — ChatRequest, AgentResponse (reply_blocks[], actions[])
- `formatter.py` — Humaniza respostas, quebra em blocos de 150 chars
- `tools/` — Tools disponíveis para function calling

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
| `input` | Pergunta + branches (WhatsApp: lista, Instagram: quick replies) |
| `cards` | Carrossel de cards com imagem (website only) |
| `condition` | Avalia variável (equals, contains, gt, lt) |
| `action` | Executa: change_stage, add_tag, assign_human, send_webhook, set_custom_field |
| `delay` | Pausa N segundos |
| `end` | Mensagem final, limpa fluxo |

### Execução (`ProcessChatbotStep`)
- Max 30 iterações por mensagem (previne loops infinitos)
- 3 segundos de delay entre mensagens (simula digitação)
- Variáveis de sessão em `conversation.chatbot_variables` (JSON)
- Interpolação: `{{nome}}` no texto
- Multi-canal: WhatsApp usa `sendList()`, Instagram usa quick replies, Website usa texto/cards

---

## 9. Campanhas e UTM

### Captura
Campos no Lead: `utm_source`, `utm_medium`, `utm_campaign`, `utm_term`, `utm_content`, `fbclid`, `gclid`

O chatbot do site captura UTMs automaticamente do `window.location.search`.

### Sync
`SyncCampaignsJob` roda via scheduler, puxa campanhas de `OAuthConnection` (Facebook/Google).

### Atribuição
Lead.campaign_id → quando fecha venda, Sale herda a campanha → relatórios de ROI.

---

## 10. Pagamentos (Asaas + Stripe)

### Asaas (Brasil — PIX, boleto, cartão)

#### Webhooks Asaas
| Evento | Ação |
|--------|------|
| `PAYMENT_RECEIVED` / `PAYMENT_CONFIRMED` | Ativa subscription, limpa ai_tokens_exhausted |
| `PAYMENT_OVERDUE` | Marca overdue, envia email |
| `SUBSCRIPTION_INACTIVATED` | Suspende tenant |

#### Token Increments
- `externalReference = "token_increment:{id}"` identifica pagamento de tokens
- Ao pagar: TenantTokenIncrement.status = 'paid', tenant.ai_tokens_exhausted = false

### Stripe (Internacional — cartão)

#### Webhooks Stripe
| Evento | Ação |
|--------|------|
| `checkout.session.completed` | Ativa subscription |
| `invoice.paid` | Confirma pagamento recorrente |
| `invoice.payment_failed` | Marca falha, notifica tenant |
| `customer.subscription.deleted` | Suspende tenant |

### Dual Billing
- Asaas para clientes Brasil (PIX, boleto, cartão)
- Stripe para clientes internacionais (cartão)
- `PaymentLog` registra todos os pagamentos independente do gateway

---

## 11. Real-time (Reverb)

- Reverb escuta em `0.0.0.0:8080` (interno)
- nginx faz proxy de `/app/` e `/apps/` para `reverb:8080`
- Frontend: `window.reverbConfig` injetado via Blade (NUNCA usar VITE_* em prod)
- Eventos broadcasted: `WhatsappMessageCreated`, `WhatsappConversationUpdated`, `InstagramMessageCreated`, `InstagramConversationUpdated`

---

## 12. Deploy e CI/CD

### Docker Image
```
Dockerfile: Node 20 (build) → PHP 8.3-FPM → Composer → entrypoint.sh
Tag: matolado/crm:{commit_sha}
```

### GitHub Actions
Push ao `main` → build image → push Docker Hub → Portainer puxa

### Stack Swarm (Portainer)
| Service | Replicas | Função |
|---------|----------|--------|
| nginx | 1 | Reverse proxy + static files |
| app | 1 | PHP-FPM (web requests) |
| queue | 1 | Worker: `--queue=ai,whatsapp,default` |
| scheduler | 1 | `schedule:run` a cada 60s |
| reverb | 1 | WebSocket server |
| mysql | 1 | MySQL 8.0 |
| redis | 1 | Cache + Queue + Session |
| pgvector | 1 | PostgreSQL + pgvector (memória IA) |
| agno | 1 | Python FastAPI (IA) |

### IMPORTANTE: VITE_* no Docker
`npm run build` roda SEM build args. `VITE_*` do Portainer são RUNTIME only.
**Padrão correto**: Injetar config no servidor via Blade (`window.reverbConfig`).

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

| Comando | Frequência | Função |
|---------|-----------|--------|
| `billing:check-trials` | Diário 06:00 | Verifica trials expirados |
| `whatsapp:send-scheduled` | A cada minuto | Envia mensagens agendadas |
| `whatsapp:send-event-reminders` | A cada minuto | Envia lembretes pendentes de eventos |
| `automations:process-date-triggers` | Diário 08:00 | Automações por data |
| `ai:followup` | A cada 10 min | Follow-up automático de IA |
| `campaigns:sync` | Hourly | Sync Facebook/Google Ads |
| `scoring:decay` | Diário 02:00 | Aplica decay de score para leads inativos |
| `sequences:process` | A cada 5 min | Processa steps de nurture sequences |
| `goals:process-recurrence` | Diário 00:30 | Snapshots e renovação de metas recorrentes |
| `goals:check-alerts` | Diário 09:00 | Alertas de performance de metas |
| `partners:release-commissions` | Diário 06:00 | Libera comissões após período de carência |
| `master:weekly-report` | Semanal (segunda 09:00) | Relatório semanal para grupo WhatsApp master |
| `upsell:evaluate` | A cada 6 horas | Avalia triggers de upsell por tenant |
| `leads:detect-duplicates` | Diário 03:30 | Detecta duplicatas de leads por phone/email |

---

## 17. Estrutura de Arquivos Chave

```
app/
  Http/Controllers/
    Tenant/          — ~45 controllers (dashboard, CRM, leads, chats, chatbot, IA, tasks, products, scoring, sequences, NPS, goals, settings)
    Tenant/LeadMergeController.php
    Master/          — ~15 controllers (tenants, plans, toolbox, logs, system, partners)
    Auth/            — 2 controllers (login, register, agency register)
    Api/             — ~7 controllers (leads API, widget, agno tools, stripe webhook)
    WhatsappWebhookController.php
    InstagramWebhookController.php
    AsaasWebhookController.php
    StripeWebhookController.php
  Console/Commands/  — 20 commands (billing, whatsapp, ai, scoring, sequences, goals, partners, upsell, master)
    DetectDuplicateLeads.php        — Scan diário de duplicatas
  Jobs/
    ProcessWahaWebhook.php      — Webhook WhatsApp (core)
    ProcessInstagramWebhook.php — Webhook Instagram
    ProcessAiResponse.php       — Resposta IA com debounce
    ProcessChatbotStep.php      — Execução de fluxo chatbot
    ImportWhatsappHistory.php   — Import de histórico WA
    SyncCampaignsJob.php        — Sync campanhas
    ProcessNurtureStep.php      — Execução de nurture sequence step
    ProcessScoringEvent.php     — Cálculo de lead score
    SendEventReminder.php       — Envio de lembretes de eventos
    ProcessGoalRecurrence.php   — Snapshots e renovação de metas
  Services/
    WahaService.php             — API client WAHA
    InstagramService.php        — API client Meta/Instagram
    AgnoService.php             — API client Agno (IA)
    AiAgentService.php          — Builder de system prompt
    AutomationEngine.php        — Motor de automações
    ChatbotVariableService.php  — Variáveis de chatbot
    AsaasService.php            — Gateway Asaas
    StripeService.php           — Gateway Stripe
    ScoringService.php          — Motor de lead scoring
    NurtureService.php          — Motor de nurture sequences
    NpsService.php              — Envio e processamento NPS
    SalesGoalService.php        — Cálculo de metas de vendas
    TaskService.php             — CRUD e lógica de tasks
    ProductService.php          — CRUD de produtos e catálogo
    PartnerService.php          — Comissões e saques de parceiros
    LeadListService.php         — Listas estáticas e dinâmicas
    ElevenLabsService.php       — Text-to-speech via ElevenLabs
    EventReminderService.php    — Lembretes de eventos Google Calendar
    WhatsappButtonService.php   — Botões WhatsApp para sites
    ExportService.php           — Exportação de dados
    ReportService.php           — Geração de relatórios
    DashboardService.php        — Dados do dashboard
    CampaignReportService.php   — Relatórios de campanhas
    NotificationService.php     — Envio de notificações
    WebhookDeliveryService.php  — Entrega de webhooks de saída
    DuplicateLeadDetector.php       — Detecção fuzzy de leads duplicados (phone/email/name)
    LeadMergeService.php            — Merge atômico de leads (21 relações)
    SophiaActionExecutor.php        — Executor de ações da Sophia (whitelist + rate limit)
  Events/           — 6 eventos broadcasted (WhatsApp/Instagram message/conversation created/updated)
  Notifications/    — 11 notifications (goal alerts, NPS, partner, billing, system)
  Models/
    Traits/BelongsToTenant.php  — Global Scope multi-tenant
    Lead.php, WhatsappConversation.php, AiAgent.php, ChatbotFlow.php, Task.php,
    Product.php, ScoringRule.php, NurtureSequence.php, NpsSurvey.php, SalesGoal.php,
    PartnerCommission.php, WhatsappButton.php, EventReminder.php, LeadDuplicate.php, etc.
  Providers/
    AppServiceProvider.php      — defaultStringLength(191)

agno-service/
  main.py              — FastAPI endpoints
  agent_factory.py     — Criação/cache de agentes
  memory_store.py      — pgvector memory
  schemas.py           — Request/Response schemas
  formatter.py         — Humanização de respostas
  tools/               — Function calling tools

resources/
  js/
    app.js                      — API helper global + escapeHtml
    chatbot-builder.jsx         — React Flow chatbot builder visual
  views/
    tenant/layouts/app.blade.php  — Layout principal
    tenant/crm/kanban.blade.php   — Kanban board
    tenant/whatsapp/index.blade.php — Chat inbox
    tenant/chatbot/builder.blade.php — Chatbot builder (host React)
    tenant/tasks/index.blade.php  — Lista de tarefas
    tenant/goals/index.blade.php  — Metas de vendas
    tenant/leads/duplicates.blade.php — Fila de duplicatas para revisão

public/widget.js         — Widget de chat para sites
bootstrap/app.php        — Middleware + Schedule
routes/web.php           — Rotas web
routes/api.php           — Rotas API
```

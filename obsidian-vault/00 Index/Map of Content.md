---
type: moc
tags: [moc, index]
---

# Map of Content

> Índice manual de tudo. Curado, não auto-gerado.

## 📦 Módulos da plataforma

### Sales / CRM
- [[Leads & CRM]]
- [[Lead Scoring]]
- [[Nurture Sequences]]
- [[Lead Duplicates]]
- [[Sales Goals]]
- [[Tasks]]

### Inbox & Conversas
- [[Chat Inbox]]
- [[WhatsApp WAHA]]
- [[WhatsApp Cloud API]]
- [[WhatsApp Templates (HSM)]]
- [[Instagram]]
- [[Website Chat]]

### IA
- [[AI Agents]]
- [[Sophia AI Assistant]]
- [[Chatbot Builder]]
- [[Calendar & Reminders]]

### Automação
- [[Automations]]
- [[Facebook Lead Ads]]

### Comercial
- [[Billing (Stripe + Asaas)]] — Stripe principal, Asaas = legacy subs + PIX token + PIX transfers
- [[Partner Program]]
- [[Feature Flags]]
- [[Reengagement]]

### Forms
- [[Forms module]] — classic/conversational/multistep + SDK embed + phone mask internacional

## 🏗️ Arquitetura

- [[Multi-tenant]]
- [[Real-time (Reverb)]]
- [[Webhook Pipeline]]
- [[Queue Workers]]
- [[Database Schema]]
- [[Tags polimorficas (refactor)]]
- [[Conversation Resolver (refactor)]]
- [[WhatsApp Foundation SOLID]] — ChatIdResolver, InstanceSelector, WindowChecker, MessagePersister + contratos ISP (2026-04-14)

## 🗂 Modelos chave

- [[Lead]] · [[WhatsappConversation]] · [[WhatsappMessage]] · [[WhatsappInstance]]
- [[InstagramConversation]] · [[InstagramInstance]] · [[WebsiteConversation]]
- [[AiAgent]] · [[ChatbotFlow]] · [[EventReminder]]
- [[Automation]] · [[ScoringRule]] · [[NurtureSequence]]
- [[Tag]] · [[Tenant]] · [[User]]

## 🔧 Services chave

- [[AutomationEngine]] · [[EventReminderService]]
- [[WhatsappServiceFactory]] · [[WahaService]] · [[WhatsappCloudService]]
- [[InstagramService]] · [[FacebookLeadAdsService]]
- [[AiAgentService]] · [[AgnoService]]
- [[GoogleCalendarService]] · [[ProfilePictureDownloader]]
- [[DuplicateLeadDetector]] · [[LeadMergeService]]
- [[ConversationResolver]] · [[NotificationDispatcher]]

## 🐛 Bugs & Decisions recentes

- [[2026-04-08 Instagram getProfile mudanca silenciosa Meta]]
- [[2026-04-09 CSS important matava filtro de templates]]
- [[2026-04-09 Idempotencia de actions de automacao]]
- [[2026-04-09 Telefone obrigatorio no description do calendar]]
- [[2026-04-09 Auditoria leads — Onda 1 + Onda 2]]
- [[ADR — Refactor de tags polimorficas (5 fases)]]
- [[ADR — Hybrid Instagram contact fetch]]
- [[ADR — WhatsApp dual provider via factory]]

## 💡 Lessons learned

- [[Verificar empiricamente antes de declarar limitacao]]
- [[CSS important sempre ganha de inline style sem important]]
- [[Sempre preferir hybrid em vez de remover caminho]]
- [[Cross-tenant contamination — auto-discovery e sempre suspeita]]

## 🔌 Integrações externas

- [[Meta Graph API]] (WhatsApp Cloud + Instagram + Facebook Lead Ads)
- [[WAHA]] (WhatsApp não-oficial)
- [[Google Calendar]]
- [[Asaas]] (pagamentos Brasil)
- [[Stripe]] (pagamentos internacional)
- [[Agno]] (microsserviço IA)
- [[ElevenLabs]] (TTS opcional)

## 🚀 Operações

- [[Deploy & CI-CD]]
- [[Comandos VPS]]
- [[Toolbox Master]]
- [[Tenants em producao]]
- [[Logs & monitoramento]]

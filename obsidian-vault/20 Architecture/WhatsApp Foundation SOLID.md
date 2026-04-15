---
type: architecture
status: active
related: ["[[WhatsApp Cloud API]]", "[[WhatsApp WAHA]]", "[[AI Agents]]", "[[Automations]]", "[[Chatbot Builder]]"]
files:
  - app/Services/Whatsapp/ChatIdResolver.php
  - app/Services/Whatsapp/InstanceSelector.php
  - app/Services/Whatsapp/ConversationWindowChecker.php
  - app/Services/Whatsapp/OutboundMessagePersister.php
  - app/Contracts/WhatsappServiceContract.php
  - app/Contracts/SupportsMessageTemplates.php
  - app/Contracts/SupportsInteractiveMessages.php
last_review: 2026-04-17
tags: [architecture, whatsapp, solid, foundation]
---

# WhatsApp Foundation SOLID

## Contexto

Consolidada em 2026-04-14 pra eliminar o pattern `if ($instance->provider === 'cloud_api') {...}` espalhado por Blade/controller/job. Todo módulo que manda mensagem (chatbot, agente IA, automação, nurture, scheduled, event-reminders) usa as mesmas building blocks.

## Serviços compartilhados (SRP)

### `ChatIdResolver`
`for($instance, $phone, $isGroup, $conv): string`

Formata chatId conforme provider:
- **WAHA** → `@c.us` / `@g.us` / `@lid` (preservando LID do histórico GOWS via `waha_message_id`)
- **Cloud API** → número puro (E.164 sem `+`)

Reusa `PhoneNormalizer::toE164` (libphonenumber).

### `InstanceSelector`
`selectFor($tenantId, $ctx): ?WhatsappInstance`

Prioridade única:
1. `$ctx['instance_id']` (escolha explícita do config de automação)
2. `$ctx['conversation']->instance` (herdado da conversa)
3. `$ctx['entity']->instance` (relação da entidade — agent/flow/sequence)
4. `WhatsappInstance::resolvePrimary($tenantId)` (is_primary ou primeira connected)
5. `null` → caller deve logar erro e abortar

### `ConversationWindowChecker`
`isOpen($conv): bool` / `hoursUntilClose($conv): ?float` / `isCloudApi($conv): bool`

Single source of truth pra regra Meta de janela de 24h. WAHA sempre retorna true (sem janela). Cloud consulta última inbound.

### `OutboundMessagePersister`
`persist($conv, $type, $body, $sendResult, $sentBy, $sentByAgentId, $userId, $extras): WhatsappMessage`

- Popula `waha_message_id` OU `cloud_message_id` conforme provider
- Atualiza `conversation.last_message_at` via `updateQuietly`
- Dispara `WhatsappMessageCreated` broadcast (tenant_id obrigatório)
- Usado por chatbot/agente/automação/nurture/scheduled/follow-up

## Contratos segregados (ISP)

### `WhatsappServiceContract` (base)
Ambos `WahaService` e `WhatsappCloudService` implementam:
- `sendText`, `sendImage`, `sendImageBase64`, `sendVoice`, `sendVoiceBase64`
- `sendFileBase64`, `sendList`, `sendReaction`, `getProviderName`

### `SupportsMessageTemplates` (Cloud-only)
```php
public function sendTemplate(string $chatId, string $name, string $lang, array $components): array;
```

### `SupportsInteractiveMessages` (Cloud-only)
```php
public function sendInteractiveButtons(string $chatId, string $body, array $buttons, ?string $footer, ?string $header): array;
```

Caller faz type-check antes:
```php
$service = WhatsappServiceFactory::for($instance);
if ($service instanceof SupportsMessageTemplates) {
    $service->sendTemplate(...);
}
```

## Capabilities no `WhatsappInstance` (OCP)

- `supportsTemplates()` → isCloudApi
- `supportsInteractiveButtons()` → isCloudApi
- `supportsInteractiveList()` → true (ambos)
- `hasWindowRestriction()` → isCloudApi

UI consulta esses helpers — zero `if provider === 'cloud_api'` em Blade/JS.

## Regras de ouro (enforcement)

- ❌ `new WahaService(...)` direto — use `WhatsappServiceFactory::for($instance)`
- ❌ `$phone . '@c.us'` hardcoded — use `ChatIdResolver::for(...)`
- ❌ `WhatsappInstance::first()` — use `InstanceSelector::selectFor(...)`
- ❌ `WhatsappMessage::create([...])` inline pra outbound — use `OutboundMessagePersister::persist(...)`
- ❌ `diffInHours(now())` inline pra janela 24h — use `ConversationWindowChecker::isOpen(...)`

## Bug histórico que isso resolve

**Chatbot no Cloud API perdia mensagens** — `ProcessChatbotStep` dependia do echo webhook do WAHA (`fromMe=true`) pra salvar em `WhatsappMessage`. Cloud não manda echo de outbound, apenas `statuses`. Fix: `OutboundMessagePersister` persiste sync logo após `sendX` retornar OK (no Cloud). WAHA mantém fluxo antigo com dedup por `waha_message_id` unique.

## Migração completa (histórico)

- **2026-04-14 commit `d2434ab`** — Foundation criada (4 services + 2 contratos ISP + capabilities no model)
- **Commit `392a623`** — Chatbot persist sync no Cloud
- **Commit `c73edd6`** — Follow-up smart/template/off
- **Commit `f0896e8`** — Chatbot com `whatsapp_instance_id`
- **Commit `16aebda`** — Automation actions Cloud-only (send_template, send_buttons, send_list)
- **Commit `c5fab6a`** — Refactor 145 linhas de `@c.us` hardcoded eliminadas (SendScheduledMessages, SendEventReminders, AiAgentService, ProcessAiResponse)

## Decisões
- [[ADR — SOLID obrigatório em novas features WhatsApp]]
- [[ADR — Capabilities no model em vez de `if provider`]]

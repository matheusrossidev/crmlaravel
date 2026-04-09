---
type: decision
status: implemented
date: 2026-04-09
modules: ["[[Chat Inbox]]", "[[AI Agents]]"]
files:
  - database/migrations/2026_04_09_190000_add_sent_by_to_messages.php
  - app/Models/WhatsappMessage.php
  - app/Models/InstagramMessage.php
  - app/Models/WebsiteMessage.php
  - app/Http/Controllers/Tenant/WhatsappController.php
  - app/Http/Controllers/Tenant/WhatsappMessageController.php
  - app/Services/AiAgentService.php
  - app/Jobs/ProcessAiResponse.php
  - app/Jobs/ProcessChatbotStep.php
  - app/Jobs/ProcessWahaWebhook.php
  - app/Services/AutomationEngine.php
  - app/Console/Commands/SendScheduledMessages.php
  - app/Console/Commands/BackfillMessageAuthorship.php
  - resources/views/tenant/whatsapp/index.blade.php
commits: ["3f0f816"]
related: ["[[Chat Inbox]]", "[[WhatsappMessage]]", "[[Cache in-memory perde tudo no restart]]"]
tags: [decision, chat, ux, sent-by]
---

# 2026-04-09 — Marcacao de autoria nas mensagens (sent_by)

## Contexto

No chat inbox do Syncro, **toda mensagem outbound** parecia igual: bolha azul direita, sem nenhuma indicacao de quem mandou. Pode ter sido humano (CRM web), Camila (IA), chatbot, automacao, mensagem agendada, follow-up, ou ate o celular do dono. Tudo virava visualmente a mesma coisa.

Problemas:
- "Foi eu que mandei isso ou foi a IA?"
- "Por que essa mensagem foi enviada as 3h da manha?"
- "Como sei que a Camila esta respondendo direito?"
- Impossibilita metricas tipo "% respondidas pela IA vs humano" (ROI da plataforma)

Causa: tabela `whatsapp_messages` so guardava `direction='outbound'` sem dizer **quem**. O `user_id` era heuristica fraca (null podia ser qualquer fonte automatica).

## Decisao

Adicionar coluna `sent_by` (varchar 20) + `sent_by_agent_id` (FK pra `ai_agents`) nas 3 tabelas de mensagens. Marcar todos os spots que criam mensagens outbound. Pra fontes que nao criam direto (chatbot WhatsApp), usar **cache de intent** lido pelo webhook.

### Valores do enum

| sent_by | Quem |
|---|---|
| `human` | Atendente clicou enviar pelo CRM (`user_id` populado) |
| `human_phone` | Mandado do celular do dono (echo do WAHA, sem intent) |
| `ai_agent` | Camila/Sophia/qualquer AiAgent (`sent_by_agent_id` populado) |
| `chatbot` | Fluxo do chatbot builder |
| `automation` | `AutomationEngine::actionSendWhatsappMessage` |
| `scheduled` | Comando `whatsapp:send-scheduled` (cron) |
| `followup` | IA reativando lead inativo |
| `event` | Eventos de sistema gerados pela IA (stage, transfer, tags) |

NULL = mensagem antiga pre-feature, sem badge no chat.

### Cache de intent — pattern reusavel

Problema: `ProcessChatbotStep` para WhatsApp NAO cria `WhatsappMessage` direto. Manda via `WhatsappServiceFactory::for($instance)->sendText($chatId, $text)`. A mensagem nasce no banco quando o webhook do WAHA volta com `fromMe=true` (echo) e o `ProcessWahaWebhook` salva.

Solucao:

```php
// Antes de cada sendText do chatbot:
Cache::put(
    "outbound_intent:{$conv->id}:" . md5(trim($body)),
    ['sent_by' => 'chatbot', 'sent_by_agent_id' => null],
    120  // 2min e mais que suficiente pro echo voltar (1-3s normalmente)
);
$waha->sendText($chatId, $text);
```

E no `ProcessWahaWebhook`, ao salvar mensagem outbound do echo:

```php
$intent = Cache::pull("outbound_intent:{$conv->id}:" . md5(trim($body)));
$sentBy = $intent['sent_by'] ?? 'human_phone';
$sentByAgentId = $intent['sent_by_agent_id'] ?? null;
```

**Por que isso e elegante**:
- Mensagens do celular do dono caem no fallback `human_phone` automaticamente (cache miss)
- Mensagens do chatbot levam o tag certo
- Nenhuma duplicacao no banco — webhook continua sendo a unica fonte canonica
- TTL de 2min limpa Redis sozinho
- Chave inclui `conversation_id` pra evitar colisao entre conversas com mesmo body
- Pattern aplicavel pra qualquer outra fonte futura (basta `Cache::put` antes do `sendText`)

### Frontend

`resources/views/tenant/whatsapp/index.blade.php`:
- 8 estilos CSS (`.msg-author-{tipo}`) com cores diferentes
- Animacao `msg-author-pulse` so no primeiro render de badges de IA (pulsacao roxa por 1.6s)
- Funcao `buildAuthorBadge(msg)`:
  - Pra IA (`ai_agent` ou `followup`): avatar circular 16px do agent + nome + animacao fresh
  - Pra humano: nome do user (do `user.name` via eager load)
  - Pra outros: label texto colorido
- `renderMessages` injeta o badge antes de cada bolha outbound com `sent_by` populado
- Backend (`WhatsappController::formatMessage` + `showInstagram`): eager load `with(['user:id,name', 'sentByAgent:id,name,display_avatar'])` e devolve `sent_by` + `sent_by_agent` no JSON

### Backfill

Comando `messages:backfill-authorship`:
```bash
php artisan messages:backfill-authorship              # tudo
php artisan messages:backfill-authorship --dry-run    # simula
php artisan messages:backfill-authorship --tenant=12  # 1 tenant
```

Heuristica:
- `outbound + user_id != null` → `sent_by='human'`
- `outbound + type='event' + media_mime LIKE 'ai_%'` → `sent_by='event'`
- Resto: deixa NULL (sem badge — nao tem como adivinhar)

Idempotente: so atualiza linhas com `sent_by IS NULL`.

## Pattern reusavel: cache de intent

Esse pattern resolve o caso geral "preciso atribuir metadata a um evento que sera salvo por outra parte do sistema". Aplicavel sempre que:
- Source (chatbot) nao cria a entidade direto
- Sink (webhook) e quem cria
- Source e sink se comunicam via terceiro (WAHA, fila, etc)

A chave do cache deve incluir tudo que **identifica unicamente** o evento (no nosso caso: `conv_id + md5(body)`). TTL pequeno (~120s) pra nao acumular lixo no Redis.

## Bonus: dashboard de ROI da IA

Com essa coluna no lugar, da pra fazer metricas tipo:
```sql
SELECT
  sent_by,
  COUNT(*) AS msgs,
  ROUND(COUNT(*) * 100.0 / SUM(COUNT(*)) OVER (), 1) AS pct
FROM whatsapp_messages
WHERE direction = 'outbound'
  AND tenant_id = ?
  AND sent_at >= NOW() - INTERVAL 7 DAY
GROUP BY sent_by;
```

Resultado tipo:
> Esta semana: IA respondeu 698 mensagens (56%), equipe respondeu 312 (25%), chatbot 187 (15%), automacoes 50 (4%)

Esse e o numero que vende a plataforma — "minha IA respondeu 700 mensagens essa semana sem eu precisar abrir o WhatsApp".

## Links
- Commit: `3f0f816`
- Plano: `~/.claude/plans/eager-seeking-corbato.md`

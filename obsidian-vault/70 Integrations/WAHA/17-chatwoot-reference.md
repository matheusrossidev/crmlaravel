---
type: integration-reference
topic: chatwoot-reference
last_review: 2026-04-17
related: ["[[README]]", "[[07-chatting-receive]]", "[[18-nossa-implementacao]]"]
tags: [waha, chatwoot, reference, source]
---

# 17 — Chatwoot Integration (Reference)

O próprio WAHA tem uma **integração nativa com Chatwoot** open-source no repo. É uma ótima referência de "como fazer" porque foi escrita pelos devs do WAHA — padrões que eles validaram.

**Source:** [github.com/devlikeapro/waha/tree/core/src/apps/chatwoot](https://github.com/devlikeapro/waha/tree/core/src/apps/chatwoot)
**Stack:** TypeScript + NestJS
**Licença:** Apache-2.0 (livre pra adaptar)

## Estrutura do código

```
src/apps/chatwoot/
├── api/              # Client Chatwoot
├── cache/
├── cli/
├── client/
├── consumers/        # Consumers de eventos por tipo
│   ├── inbox/
│   ├── scheduled/
│   ├── task/
│   └── waha/         # ← 13 arquivos — handlers por evento WAHA
│       ├── base.ts
│       ├── message.ack.ts
│       ├── message.any.ts
│       ├── message.edited.ts
│       ├── message.reaction.ts
│       ├── message.revoked.ts
│       ├── session.status.ts
│       ├── call.*.ts
│       └── ...
├── contacts/         # Extração de info de contato
│   ├── InboxContactInfo.ts
│   └── WhatsAppContactInfo.ts   # ← LEITURA OBRIGATÓRIA
├── di/
├── dto/
├── error/
├── i18n/
├── messages/
│   └── to/
│       ├── chatwoot/             # ← converters por tipo de mensagem
│       │   ├── TextMessage.ts
│       │   ├── AlbumMessage.ts
│       │   ├── LocationMessage.ts
│       │   ├── PollMessage.ts
│       │   ├── ListMessage.ts
│       │   ├── ShareContactMessage.ts
│       │   ├── PixMessage.ts
│       │   └── utils/
│       └── whatsapp/
├── migrations/
├── services/
│   ├── ChatWootAppService.ts
│   ├── ChatWootQueueService.ts
│   ├── ChatWootWAHAQueueService.ts
│   └── ConversationSelector.ts
└── waha/
```

## Padrões que aplicamos no Syncro

### 1. Extração de nome — 3 variantes

De [`WhatsAppContactInfo.ts`](https://github.com/devlikeapro/waha/blob/core/src/apps/chatwoot/contacts/WhatsAppContactInfo.ts):

```typescript
const name = contact?.name || contact?.pushName || contact?.pushname || this.chatId;
```

**Três chaves checadas**:
1. `name` — nome do contato na agenda do número conectado
2. `pushName` (camelCase) — push name reportado pelo WhatsApp
3. `pushname` (lowercase) — variante em algumas engines

Nossa [ImportWhatsappHistory:321](app/Jobs/ImportWhatsappHistory.php#L321) replica essa lógica:

```php
$contactName = $info['name']
    ?? $info['pushName']
    ?? $info['pushname']
    ?? null;
```

**Bug histórico nosso**: o código antigo só checava `name` e `pushName`. Faltava `pushname` lowercase — causando `contact_name = phone` em 30%+ das conversas importadas. Fix em commit `379a452`.

### 2. Avatar — mesma função

```typescript
async AvatarUrl(): Promise<string | null> {
    return await this.session.getChatPicture(this.chatId);
}
```

Envia URL remota direto pro Chatwoot. Simples.

**Nossa diferença**: usamos [ProfilePictureDownloader](app/Support/ProfilePictureDownloader.php) pra baixar local. Motivo: URLs do CDN Meta expiram em horas. Se passar só URL pro storage, foto quebra quando usuário abre o chat horas depois.

### 3. Timestamp histórico → NÃO preservar na API

Chatwoot API **não aceita** `created_at` custom em messages. WAHA resolve isso inserindo a data NO CONTENT da mensagem como prefixo visual:

```
[15/03/2024 14:30] Oi, tudo bem?
```

Nossa abordagem é diferente/melhor — usamos coluna `sent_at` no banco. Mas a **filosofia** é a mesma: **não usar `now()` como fallback** pra timestamp inválido. Skip ou preservar ordem sequencial da fila.

Fix em commit `379a452`:
```php
if ($ts <= 1577836800 || $ts >= time() + 86400) {
    Log::info('msg com timestamp invalido, pulando');
    continue;
}
```

### 4. Queue sequencial

[`ChatWootWAHAQueueService.ts`](https://github.com/devlikeapro/waha/blob/core/src/apps/chatwoot/services/ChatWootWAHAQueueService.ts) — processa mensagens **sequencialmente por session**. Não paraleliza dentro de 1 tenant.

Razão: ordem cronológica é crítica pro chat. Processar em paralelo pode gerar ordem errada.

Nosso [ProcessWahaWebhook](app/Jobs/ProcessWahaWebhook.php) roda em queue Laravel — similar. Um worker por tempo, `--queue=ai,whatsapp,default`.

### 5. Consumers por tipo

Cada tipo de evento tem um consumer dedicado em `src/apps/chatwoot/consumers/waha/`:

- `message.any.ts` → orquestrador: chama o converter certo
- `message.ack.ts` → atualiza status
- `message.reaction.ts` → adiciona reação como nota
- `message.edited.ts` → atualiza conteúdo
- `message.revoked.ts` → marca como deletada
- `session.status.ts` → notifica admin se session caiu

**Nossa diferença**: temos 1 job grande [ProcessWahaWebhook](app/Jobs/ProcessWahaWebhook.php) com switch interno. Seguindo padrão Chatwoot, poderíamos quebrar em handlers menores. Refactor futuro.

### 6. Converters por tipo de mensagem

Em `src/apps/chatwoot/messages/to/chatwoot/`:
- `TextMessage.ts` — texto simples, markdown conversion
- `AlbumMessage.ts` — múltiplas mídias agrupadas
- `LocationMessage.ts` — renderiza mapa
- `PollMessage.ts` — converte poll pra texto + opções
- `ListMessage.ts` — lista interativa como texto
- `ShareContactMessage.ts` — vCard → card
- `PixMessage.ts` — pagamento PIX

Cada converter transforma payload WAHA → formato Chatwoot. **Padrão factory + interface** — boa arquitetura.

**No Syncro**: fazemos tipo único `text/image/audio/video/document` em `WhatsappMessage.type`. Perdemos granularidade (poll/location/contact viram "text" com body genérico). Refactor futuro: criar converters específicos.

## App config

O app nativo é ativado via env:

```bash
WAHA_APPS_ENABLED=True
WAHA_APPS_ON=chatwoot          # comma-separated
REDIS_URL=redis://redis:6379
WAHA_API_KEY_PLAIN=<secret>
WHATSAPP_DEFAULT_ENGINE=GOWS
```

Config via API:
```
POST /api/apps
{
  "name": "chatwoot",
  "config": {
    "url": "https://chatwoot.example.com",
    "accountId": 1,
    "accountToken": "abc123",
    "inboxId": 2,
    "inboxIdentifier": "xyz"
  }
}
```

## Custom attributes que eles criam no Chatwoot

Ao sincronizar contato WhatsApp pro Chatwoot, o WAHA popula:

- `WA_CHAT_ID` — o JID completo (`5511999999999@c.us`)
- `WA_JID` — mesmo que WA_CHAT_ID
- `WA_LID` — LID interno quando aplicável

No Syncro, nossa equivalência:
- `whatsapp_conversations.phone` = número
- `whatsapp_conversations.lid` = LID quando aplicável

## Diferenças vs nossa implementação

| Aspecto | WAHA → Chatwoot | Syncro |
|---------|-----------------|--------|
| Linguagem | TypeScript/NestJS | PHP/Laravel |
| Name extraction | `name \|\| pushName \|\| pushname` | Igual (após fix 379a452) |
| Avatar | URL remota direto | Download local (CDN expira) |
| Timestamp | Prefix no content (API limita) | Coluna `sent_at` no banco |
| Ordem | Queue sequencial | Queue Laravel sequencial |
| Consumers | 1 por tipo de evento | 1 job monolítico (refactor pendente) |
| Converters | 1 por tipo de mensagem | Tipo único (refactor pendente) |
| Label sync | Não documentado | Nossa tags próprias |

## Gotchas

- **`pushname` vs `pushName`** — variantes CRÍTICAS. Código antigo que ignora a lowercase vai ter 30%+ de conversas sem nome.
- **URL de avatar expira** — a integração deles aceita; a nossa baixa pra evitar quebra futura.
- **Não copiar lógica de timestamp** — Chatwoot API tem limitação diferente da nossa. Filosofia igual (não fallback `now()`), implementação diferente (coluna `sent_at` vs prefix no texto).
- **Queue sequencial é OBRIGATÓRIO** — paralelizar dentro de uma session embaralha ordem.

## Uso na Syncro

- Validamos contra este source em [commit 379a452](https://github.com/matheusrossidev/crmlaravel/commit/379a452) — pushname fix alinhado.
- Futuro: adotar **padrão consumers por tipo** (refactor do ProcessWahaWebhook).
- Futuro: adotar **converters por message type** (expand WhatsappMessage.type).
- Nosso [ConversationResolver](app/Services/ConversationResolver.php) já é inspirado no `ConversationSelector.ts` deles.

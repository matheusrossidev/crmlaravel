---
type: integration-reference
topic: polls-reactions-labels
last_review: 2026-04-17
related: ["[[README]]", "[[06-chatting-send]]", "[[13-webhooks-events]]"]
tags: [waha, polls, reactions, labels]
---

# 16 — Polls, Reactions & Labels

Features secundárias que podem agregar valor no CRM (pesquisas rápidas, engajamento, organização).

## Polls

### Enviar

```
POST /api/sendPoll
{
  "session": "tenant_12",
  "chatId": "5511999999999@c.us",
  "question": "Qual sua preferência?",
  "options": ["Opção A", "Opção B", "Opção C"],
  "multiselect": false
}
```

Schema: `MessagePollRequest`.

**Limites**:
- `question` max ~200 chars (WhatsApp)
- `options` — min 2, max 12 opções
- `multiselect: true` permite múltipla resposta

### Votar (como remetente da poll)

```
POST /api/sendPollVote
{
  "session": "tenant_12",
  "chatId": "5511999999999@c.us",
  "messageId": "<id_da_poll_original>",
  "selectedOptions": ["Opção A"]
}
```

### Receber votos (webhook)

```json
{
  "event": "poll.vote",
  "payload": {
    "pollMessageId": "true_...",
    "votedBy": "5511888888888@c.us",
    "selectedOptions": ["Opção A"],
    "timestamp": 1745123456
  }
}
```

Event `poll.vote.failed` quando WhatsApp não consegue descriptografar o voto (raro).

### Uso possível no Syncro

- Pesquisa pós-atendimento ("Você ficou satisfeito? Sim / Não")
- NPS interativo via WhatsApp
- Agendamento rápido ("Qual melhor horário?")

Schema retornado como mensagem especial com `type: "poll"` ou `"poll_vote"`.

## Reactions

### Enviar

```
PUT /api/reaction
{
  "session": "tenant_12",
  "chatId": "5511999999999@c.us",
  "messageId": "false_5511999999999@c.us_AAA",
  "emoji": "👍"
}
```

Schema: `MessageReactionRequest`.

Emoji vazio (`""`) remove reação anterior.

### Receber (webhook)

```json
{
  "event": "message.reaction",
  "payload": {
    "id": "false_...",
    "from": "5511999999999@c.us",
    "reaction": {
      "text": "🙏",
      "messageId": "true_..."
    }
  }
}
```

`reaction.text: ""` = reação removida.

### Uso possível no Syncro

- Acknowledgment leve (reage com ✅ em vez de mandar "ok")
- Feedback sem quebrar fluxo (emoji rápido)
- Registro no chat de que "IA viu a mensagem" (UX signal)

## Labels

WAHA suporta endpoints de labels (etiquetas do WhatsApp Business), mas a doc oficial é menos completa.

### Events (webhook)

- `label.upsert` — label criado/atualizado
- `label.deleted` — label removido
- `label.chat.added` — label aplicado a um chat
- `label.chat.deleted` — label removido de um chat

Payloads não 100% documentados. Descobrir via Swagger ao implementar.

### Uso possível no Syncro

- Sincronizar labels WhatsApp Business ↔ tags do CRM
- Categorização automática (novo_lead, em_negociação, fechado)

Hoje usamos [[Tags polimorficas (refactor)|nossa estrutura de tags]] própria. Se quiser two-way sync com labels WhatsApp, implementar bridge no `ProcessWahaWebhook`.

## Star / Unstar

Bônus — não é label, mas relacionado à organização.

```
PUT /api/star
{
  "session": "tenant_12",
  "chatId": "5511999999999@c.us",
  "messageId": "false_...",
  "star": true
}
```

Marca mensagem como favorita (mostra estrela no chat). Tipo bookmark.

Não há evento webhook específico pra star — só reflete no próximo fetch.

## Gotchas

- **Poll events podem chegar out-of-order** em relação ao `poll.vote` vs `message.ack` — tratar separadamente.
- **Reactions não aparecem como `message` event** — só `message.reaction`. Se filtrar só `message`, perde.
- **Label API incompleto em algumas engines** — testar com GOWS antes de depender.
- **Emoji em `reaction.text` vem como caracter Unicode bruto** — cuidado com encoding no banco (`utf8mb4` obrigatório).
- **`PUT /reaction` requer messageId que a outra parte veja** — se ela já deletou, falha silenciosa.

## Uso na Syncro

- **Polls**: não implementado hoje. Feature possível pra NPS/satisfação.
- **Reactions**: não enviamos hoje. Poderia receber e registrar no chat.
- **Labels**: não usamos. Nosso sistema de tags cobre.
- Ver [[06-chatting-send]] pros endpoints de envio correspondentes

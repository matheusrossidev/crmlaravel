---
type: integration-reference
topic: status-stories
status: not-in-use
last_review: 2026-04-17
related: ["[[README]]", "[[10-channels]]"]
tags: [waha, status, stories]
---

# 11 — Status (Stories)

WhatsApp Status = **stories** (conteúdo efêmero que some em 24h). Visto pelos contatos que têm você salvo.

⚠️ **Não usamos no Syncro hoje.** Documentação de referência.

## Endpoints (5 total)

Todos em `/api/{session}/status/*`:

| Método | Path | Descrição |
|--------|------|-----------|
| POST | `/status/text` | Status de texto |
| POST | `/status/image` | Status de imagem |
| POST | `/status/voice` | Status de voz |
| POST | `/status/video` | Status de vídeo |
| POST | `/status/delete` | Apagar status |

## Send text status

```
POST /api/{session}/status/text
{
  "text": "Novidade no ar!",
  "backgroundColor": "#25D366",
  "font": 0
}
```

Schema: `TextStatus`. Font codes do WhatsApp (0-5).

## Send image status

```
POST /api/{session}/status/image
{
  "file": {
    "mimetype": "image/jpeg",
    "url": "https://example.com/img.jpg"
  },
  "caption": "Legenda opcional"
}
```

Schema: `ImageStatus`. Aceita URL ou base64 (ver [[14-media-files]]).

## Send voice status

```
POST /api/{session}/status/voice
{
  "file": {
    "mimetype": "audio/ogg; codecs=opus",
    "url": "https://example.com/voice.opus"
  }
}
```

## Send video status

```
POST /api/{session}/status/video
{
  "file": {
    "mimetype": "video/mp4",
    "url": "https://example.com/video.mp4"
  },
  "caption": "Legenda"
}
```

## Delete status

```
POST /api/{session}/status/delete
{
  "messageId": "status_AAA..."
}
```

Schema: `DeleteStatusRequest`.

## Gotchas

- **Status expira em 24h** automaticamente — sem precisar apagar manualmente.
- **Privacidade**: status é visto apenas por contatos salvos do número conectado (regra WhatsApp).
- **`chatId` = `status@broadcast`** se quiser mandar via endpoints gerais (send-messages).
- **Video status tem limite de 30 segundos** — mais que isso o WhatsApp corta.
- **Broadcast limit**: respeitam limite de rate do WhatsApp — cuidado com spam.

## Uso futuro possível no Syncro

- Status automatizados pro atendimento (ex: "Atendendo hoje 9h-18h").
- Campanhas de marketing (mas usuários podem marcar como spam).

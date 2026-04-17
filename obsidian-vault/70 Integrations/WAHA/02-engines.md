---
type: integration-reference
topic: engines
last_review: 2026-04-17
related: ["[[README]]", "[[01-setup-deploy]]", "[[07-chatting-receive]]"]
tags: [waha, engines, gows, noweb, webjs]
---

# 02 — Engines do WAHA

WAHA suporta **5 engines** — cada um é uma maneira diferente de conectar ao WhatsApp. A escolha afeta feature support, payload structure, performance e estabilidade.

## Tabela comparativa

| Engine | Linguagem | Browser? | Memória | Payload stable? | Uso |
|--------|-----------|----------|---------|-----------------|-----|
| **GOWS** ⭐ | Golang | Não (WebSocket direto) | Baixa | Sim | **Usamos em prod** |
| NOWEB | Node.js/TS | Não (WebSocket direto) | Baixa | Sim | Alternativa a GOWS |
| WEBJS | Chromium | Sim (headless browser) | Alta | Frágil (breaks em updates WA) | Legacy |
| WPP | Chromium | Sim | Alta | Frágil | Legacy |
| VENOM | Chromium | Sim | Alta | Frágil | Legacy |

Doc oficial avisa explicitamente: **"test your system before changing the engine"** — respostas de API e webhook payloads podem diferir significativamente entre engines.

## GOWS (nossa escolha)

**O que é:** glue layer do WAHA sobre a biblioteca Go [github.com/devlikeapro/gows](https://github.com/devlikeapro/gows). Comunica com WhatsApp Web via WebSocket direto, sem browser headless.

**Vantagens:**
- Memória ~50-100MB vs ~500MB+ de Chromium-based
- Estável contra updates do WhatsApp Web (não depende de DOM scraping)
- Suporta LID handling nativo (ver [[15-lid-handling]])
- Funciona com multi-session em um único container

**Configuração:**

```bash
WHATSAPP_DEFAULT_ENGINE=GOWS
```

DNS override recomendado (alguns hosts têm problemas com libc DNS resolver):
```bash
GODEBUG=netdns=go
```

### Device props / History sync

GOWS sincroniza histórico completo do WhatsApp por default — pode trazer anos de mensagens e **estourar storage + bandwidth do proxy**. Limitar com:

```bash
WAHA_GOWS_DEVICE_REQUIRE_FULL_SYNC=false
WAHA_GOWS_DEVICE_HISTORY_SYNC_FULL_SYNC_DAYS_LIMIT=365
WAHA_GOWS_DEVICE_HISTORY_SYNC_RECENT_SYNC_DAYS_LIMIT=30
WAHA_GOWS_DEVICE_HISTORY_SYNC_INITIAL_SYNC_MAX_MESSAGES_PER_CHAT=100

# Bandwidth / storage
WAHA_GOWS_DEVICE_HISTORY_SYNC_FULL_SYNC_SIZE_MB_LIMIT=512
WAHA_GOWS_DEVICE_HISTORY_SYNC_STORAGE_QUOTA_MB=1024
```

**Feature flags** (tudo `false` por default, ativar uma por uma):
- `WAHA_GOWS_DEVICE_HISTORY_SYNC_SUPPORT_CALL_LOG_HISTORY`
- `WAHA_GOWS_DEVICE_HISTORY_SYNC_SUPPORT_CAG_REACTIONS`
- (~15 flags no total — ver [doc oficial GOWS](https://waha.devlike.pro/docs/engines/gows/))

### Após mudar device props

**OBRIGATÓRIO**: reiniciar o container + repair da session (logout + scan QR novo). Props não aplicam retroativamente.

## NOWEB

Similar em filosofia ao GOWS mas em Node.js/TypeScript. Mesma classe de performance, funciona sem browser. Pode ser fallback se GOWS tiver bug específico.

## WEBJS / WPP / VENOM (Chromium-based)

Rodam Chromium headless pra falar com WhatsApp Web. São **legacy** — quebram com updates do WhatsApp, consomem muito mais memória e são mais lentos. Alguma funcionalidade experimental/beta do WhatsApp pode aparecer primeiro nessas engines antes de GOWS/NOWEB.

**Evitar em produção.**

## Payload differences

**A estrutura do `_data` (metadata interno na message payload) varia por engine.**

Exemplo GOWS — PushName do contato:
```json
{
  "_data": {
    "Info": {
      "PushName": "João Silva",
      "Chat": "5511999999999@s.whatsapp.net"
    },
    "notifyName": "João Silva"
  }
}
```

Exemplo WEBJS — estrutura diferente (não detalhada na doc, mas tem seu próprio shape).

Nosso código trata isso em [ProcessWahaWebhook](app/Jobs/ProcessWahaWebhook.php#L456-L464) checando várias variantes:
```php
$contactName = $msg['_data']['Info']['PushName']
    ?? $msg['_data']['notifyName']
    ?? $msg['notifyName']
    ?? null;
```

Ver [[07-chatting-receive]] pra detalhes completos.

## Qual usar?

**Regra prática**: **GOWS** pra qualquer caso novo hoje. Estável, econômico, battle-tested pelo WAHA core.

Se tiver feature específica que só funciona em outra engine (raro), documentar o motivo antes de trocar.

## Gotchas

- **Payloads diferem entre engines** — código que usa `_data.Info.PushName` (GOWS) pode quebrar em NOWEB/WEBJS. Nosso código tem fallback triplo.
- **History sync default trazia anos de mensagens** — até aplicarmos os limits de device props, a base crescia desnecessariamente. Limitar pra 365 dias full + 30 recent + 100 msg/chat.
- **Mudar engine exige re-pareamento**: não é só trocar env var, precisa logout + scan QR novo pra cada tenant.
- **WAHA Plus licença** — grande parte das features avançadas (multi-session em 1 container, alguns endpoints Channels) requerem license Plus. Confirmar na variável `WAHA_PLUS_LICENSE` antes de usar feature nova.

## Uso na Syncro

- [[18-nossa-implementacao|WahaService.php]] — abstrai engine (sempre fala HTTP com WAHA, não com engine)
- [[15-lid-handling]] — GOWS-specific
- [[19-gotchas-producao]] — problemas reais que pegamos

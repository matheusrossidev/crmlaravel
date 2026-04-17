---
type: integration-reference
topic: pairing
last_review: 2026-04-17
related: ["[[README]]", "[[03-sessions]]"]
tags: [waha, pairing, qr, auth]
---

# 04 — Pairing (QR Code + Pairing Code)

Autenticação do WAHA com WhatsApp exige pareamento do número. Dois métodos:
1. **QR Code** (padrão)
2. **Pairing Code** (alfanumérico, alternativa pra quem não consegue escanear QR)

## Endpoints

2 endpoints na tag `Pairing`:

| Método | Path | Descrição |
|--------|------|-----------|
| GET | `/api/{session}/auth/qr` | Retorna QR code |
| POST | `/api/{session}/auth/request-code` | Request pairing code |

## QR Code — `GET /auth/qr`

```
GET /api/tenant_12/auth/qr
```

Query params:
- `format=image` (default) — retorna PNG binário
- `format=raw` — retorna string base64 do QR

Response default:
```
Content-Type: image/png
Body: <binary PNG>
```

Response com `format=raw`:
```json
{
  "mimetype": "image/png",
  "data": "iVBORw0KGgoAAAANSUhEUgAA..."
}
```

**QR refresh a cada 20 segundos.** Max 6 tentativas antes de session ir pra `FAILED`. Client deve poll o endpoint a cada 20s e renderizar pra user escanear.

## Pairing Code — `POST /auth/request-code`

Alternativa pra quem não pode escanear QR (telefone sem câmera, UI acessibilidade, etc).

```
POST /api/tenant_12/auth/request-code
{
  "phoneNumber": "+5511999999999"
}
```

Response:
```json
{
  "code": "ABCD-EFGH"
}
```

Usuário entra no app WhatsApp → Configurações → Dispositivos Conectados → "Conectar com código" → digita o pairing code de 8 chars.

**Validade do code**: tipicamente 60 segundos. Se expirar, fazer nova request.

## Fluxo completo

```
1. POST /api/sessions (cria session, status = STARTING)
2. Poll GET /api/sessions/{name} até status = SCAN_QR_CODE
3a. Opção QR:  GET /api/{session}/auth/qr → mostrar PNG pro user
3b. Opção Code: POST /api/{session}/auth/request-code → exibir code
4. User escaneia/digita no WhatsApp Web
5. WAHA emite event session.status com status = WORKING
6. Ready pra enviar/receber mensagens
```

## Erros comuns

| Erro | Causa |
|------|-------|
| `QR expirou` | 20s passaram — fetch novo QR |
| `Session em FAILED` | 6 tentativas falharam — restart a session |
| `Code não chega no celular` | Número errado ou conta bloqueada pelo WhatsApp |
| `QR não renderiza no CRM` | base64 corrompido ou Content-Type HTML em vez de image/png |

## Gotchas

- **WhatsApp Business app v2.24.17+ é requerido** pro pairing funcionar com WhatsApp Cloud API Coexistence. Pra WAHA padrão (não Coexistence) a versão mínima é bem mais flexível.
- **Número precisa ter WhatsApp ativo há 7+ dias** (regra WhatsApp, não WAHA) pra aceitar pareamento via API.
- **QR escaneado por app errado (WhatsApp pessoal ao invés de Business)** funciona, mas às vezes a session vai pra FAILED rapidamente porque o WhatsApp detecta uso suspeito.

## Uso na Syncro

- [IntegrationController::connectWhatsapp](app/Http/Controllers/Tenant/IntegrationController.php) — fluxo de conectar
- [WahaService::getQrResponse](app/Services/WahaService.php) — fetch QR
- UI: [settings/integrations.blade.php](resources/views/tenant/settings/integrations.blade.php) — modal de QR

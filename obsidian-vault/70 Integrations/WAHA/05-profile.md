---
type: integration-reference
topic: profile
last_review: 2026-04-17
related: ["[[README]]", "[[03-sessions]]"]
tags: [waha, profile]
---

# 05 — Profile

Gerenciamento do perfil do número conectado (nome exibido, status "about", foto).

## Endpoints

5 endpoints na tag `Profile`:

| Método | Path | Descrição |
|--------|------|-----------|
| GET | `/api/{session}/profile` | Perfil completo |
| PUT | `/api/{session}/profile/name` | Alterar nome exibido |
| PUT | `/api/{session}/profile/status` | Alterar status "about" |
| PUT | `/api/{session}/profile/picture` | Alterar foto de perfil |
| DELETE | `/api/{session}/profile/picture` | Remover foto |

## GET /profile — Response

```json
{
  "id": "5511999999999@c.us",
  "name": "Syncro Atendimento",
  "status": "Suporte pelo WhatsApp. Responda ao atendente.",
  "picture": "https://pps.whatsapp.net/..."
}
```

Campos:
- `id` — JID do número autenticado
- `name` — nome exibido pro outro lado do chat (push name)
- `status` — texto "about" (biografia curta do WhatsApp)
- `picture` — URL da foto de perfil atual

## PUT /profile/name

```json
{
  "name": "Syncro Atendimento 24/7"
}
```

Response: 200 OK com novo perfil.

**Limite Meta**: 25 caracteres. Textos maiores são truncados ou rejeitados.

## PUT /profile/status (about)

```json
{
  "status": "Olá! Sou o atendente virtual."
}
```

**Limite Meta**: 139 caracteres.

## PUT /profile/picture

Duas formas:

```json
{
  "file": {
    "url": "https://example.com/logo.jpg"
  }
}
```

ou:

```json
{
  "file": {
    "mimetype": "image/jpeg",
    "filename": "logo.jpg",
    "data": "/9j/4AAQSkZJ..."
  }
}
```

**Restrições Meta:**
- Formato JPEG apenas (PNG é convertido)
- Proporção quadrada (1:1) — WhatsApp recorta se não for
- Tamanho mínimo 192×192, recomendado 640×640
- Max ~1MB

## DELETE /profile/picture

Remove foto atual. Exibe inicial cinza do nome como fallback.

## Gotchas

- **Alterações demoram ~30s pra propagar** pros outros contatos devido a cache do WhatsApp.
- **Mudar foto ou nome MUITO frequente pode ser sinal de spam** pro antispam do WhatsApp — evitar alterações automatizadas a cada pouco tempo.
- **Nome de profile ≠ push name visto pelo outro lado** — o push name que o contato vê pode vir do contato salvo na agenda dele, não do que você definiu. Nome do profile só aparece quando o contato não tem você salvo.
- Não usamos estes endpoints em prod hoje. Config é feita pelo WhatsApp Business app diretamente.

## Uso na Syncro

- Não usamos em código hoje. Se quisermos permitir usuário do CRM alterar foto/nome do número conectado, é o endpoint certo.
- Pra checar se a session tá viva, prefira [[03-sessions|`GET /sessions/{name}/me`]] (mais leve que profile completo).

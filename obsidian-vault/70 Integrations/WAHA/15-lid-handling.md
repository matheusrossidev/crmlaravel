---
type: integration-reference
topic: lid
last_review: 2026-04-17
related: ["[[README]]", "[[02-engines]]", "[[07-chatting-receive]]"]
tags: [waha, lid, gows, resolution]
---

# 15 — LID Handling (`@lid`)

`@lid` = **Linked Identifier** — ID interno do WhatsApp/Meta que engines newer (GOWS principalmente) às vezes usam no lugar do número real (`@c.us`).

## Por que existe

Meta introduziu LIDs pra suporte de **Communities** (comunidades de grupos) e pra mascarar número pessoal em contextos semi-públicos. O JID do remetente pode vir como `36576092528787@lid` em vez de `5511999999999@c.us`.

**Engines afetados:**
- **GOWS** (usamos) — LID aparece com frequência
- **NOWEB** — também pode aparecer
- **WEBJS/WPP/VENOM** — raramente (DOM scraping normalmente traz `@c.us`)

## Formatos

- `36576092528787@lid` — LID puro (14-15 dígitos)
- `5511999999999@c.us` — número real
- `5511999999999@s.whatsapp.net` — formato interno GOWS legacy (converter pra `@c.us`)

## Endpoints de resolução (WAHA)

### Resolver 1 LID → phone

```
GET /api/{session}/lids/{lid}
```

Ex: `GET /api/tenant_12/lids/36576092528787@lid`

Response:
```json
{
  "lid": "36576092528787@lid",
  "phoneNumber": "5511999999999@c.us"
}
```

Se não resolve: retorna null ou 404.

### Batch mapping (todos os LIDs conhecidos)

```
GET /api/{session}/lids
```

Response:
```json
[
  { "lid": "36576092528787", "phone": "5511999999999" },
  { "lid": "82936489904300", "phone": "5511988888888" }
]
```

Mais eficiente pra bulk import — cacheia o mapa inteiro.

## Nossa estratégia de resolução

No [ProcessWahaWebhook](app/Jobs/ProcessWahaWebhook.php):

```php
$fromIsLid = str_ends_with($from, '@lid');

if ($fromIsLid) {
    // 1) Tentar resolver via /lids/{lid}
    $lidResult = $waha->getPhoneByLid($from);
    $phone = $lidResult['phoneNumber'] ?? null;
    
    // 2) Fallback: batch map
    if (!$phone) {
        $lidMap = $waha->getAllLids();
        $phone = $lidMap[$lidWithoutSuffix] ?? null;
    }
    
    // 3) Fallback: getContactInfo (legacy)
    if (!$phone) {
        $info = $waha->getContactInfo($from);
        $phone = normalize($info['id']);
    }
    
    // 4) Se nenhum resolveu → BLOQUEAR (não salvar conversa)
    if (!$phone) {
        Log::info('LID bloqueado, sem phone real');
        return;
    }
}
```

**Regra crítica**: se LID não resolve, **NÃO cria conversa** — evita poluir banco com IDs inúteis.

## ImportWhatsappHistory: mesma lógica

Ver [ImportWhatsappHistory:260-305](app/Jobs/ImportWhatsappHistory.php#L260-L305):

```php
if (! $isGroup && ctype_digit($phone) && ($chatIsLid || strlen($phone) > 13)) {
    // 1) Batch lookup
    if (isset($lidMap[$phone])) {
        $phone = $lidMap[$phone];
    } else {
        // 2) /lids/{lid}
        $lidResult = $waha->getPhoneByLid($phone . '@lid');
        // ...
        // 3) Fallback getContactInfo
    }
}

// BLOQUEAR se LID não resolveu
if (!$isGroup && ctype_digit($phone) && ($chatIsLid || strlen($phone) > 13)) {
    return ['chats' => 0, 'messages' => 0, 'skipped' => 0];
}
```

## Sinalização no banco

Quando resolvemos um LID, salvamos ambos:
- `whatsapp_conversations.phone` = número real resolvido
- `whatsapp_conversations.lid` = LID original (pra cruzar mapping futuro)

## Gotchas

- **NÃO usar `strlen($phone) > 13` como único sinal de LID** — alguns números longos internacionais (Argentina `549...`) podem ter 13+ dígitos legítimos. **Usar o sufixo `@lid` do chatId** como fonte primária.
- **LID pode mudar** — em raríssimas ocasiões Meta rotaciona LIDs. Mapping cached pode ficar stale.
- **Grupos nunca usam LID** — só 1:1 contacts afetados. Nosso código já faz `! isGroup` check.
- **Quando WAHA retorna 404 em `/lids/{lid}`** significa "não sabemos" — não erro. Fallback pra batch.
- **Alguns LIDs aparecem em `@s.whatsapp.net`** (formato interno GOWS) ao invés de `@lid` — tratamos como LID também.
- **Conversa com LID não resolvido = dado inútil** — bloquear é melhor que salvar com `phone: "36576092528787"` (não dá pra mandar mensagem pro LID, só pro `@c.us` real).

## Foto de perfil via LID

Se `GET /chats/{phone}@c.us/picture` retorna `url: null`, ÀS VEZES funciona tentar com o LID:

```
GET /chats/36576092528787@lid/picture → {"url": "https://..."}
```

Por isso o [ToolboxController::syncProfilePictures](app/Http/Controllers/Master/ToolboxController.php#L585-L595) tem fallback com `@lid`:

```php
$pic = $waha->getChatPicture($phone . '@c.us');
if (!$pic && strlen($phone) >= 13) {
    $pic = $waha->getChatPicture($phone . '@lid');
}
```

## Uso na Syncro

- [WahaService::getPhoneByLid](app/Services/WahaService.php) — resolve 1 LID
- [WahaService::getAllLids](app/Services/WahaService.php) — batch mapping
- [ProcessWahaWebhook](app/Jobs/ProcessWahaWebhook.php) — lógica de resolução inbound
- [ImportWhatsappHistory](app/Jobs/ImportWhatsappHistory.php) — lógica de resolução pro import
- Coluna `lid` em `whatsapp_conversations` — armazena LID original mesmo após resolve
- [ToolboxController::syncProfilePictures](app/Http/Controllers/Master/ToolboxController.php#L559-L620) — retry foto via `@lid`

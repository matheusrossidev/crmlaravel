---
type: integration-reference
topic: gotchas
last_review: 2026-04-17
related: ["[[README]]", "[[18-nossa-implementacao]]"]
tags: [waha, gotchas, bugs, production, learnings]
---

# 19 — Gotchas em Produção

Tudo que pegamos na prática com WAHA que NÃO está óbvio na doc oficial. Ordem aproximada por severidade.

## 1. `parse()` converte HTTP 4xx/5xx em array `error`, não throw

### Problema
[WahaService::parse](app/Services/WahaService.php#L426) faz:

```php
if ($response->failed()) {
    return ['error' => true, 'status' => $response->status(), 'body' => $response->body()];
}
```

Ou seja: **401, 500, 503 — nada throw**. Métodos que usam `parse()` internamente precisam checar `$result['error']` explicitamente.

### Impacto
Código como [`getChatPicture` antigo](app/Services/WahaService.php#L152):

```php
try {
    $result = $this->get($path);
    return $result['url'] ?? null;    // ← 401 retorna ['error' => true] → null silenciosamente
} catch (\Throwable) {
    return null;    // ← nunca dispara
}
```

→ 100% das fotos "falhavam" sem log. Bug que custou semanas.

### Fix
Commit `379a452` em [getChatPicture](app/Services/WahaService.php#L152):

```php
if (($result['error'] ?? false) === true) {
    Log::channel('whatsapp')->warning('WAHA retornou erro HTTP', [
        'status' => $result['status'],
        'body' => substr($result['body'] ?? '', 0, 300),
    ]);
    return null;
}
```

### Regra
**Qualquer método novo da WahaService DEVE checar `$result['error']`** antes de acessar campos de sucesso.

## 2. Race condition `message` vs `message.any`

### Problema
WAHA dispara AMBOS os eventos pra cada mensagem inbound. Sem dedup, cria conversação 2x.

### Fix
Dedup atômico via Redis no início de [handleInbound](app/Jobs/ProcessWahaWebhook.php#L64):

```php
if (! Cache::add("waha:processing:{$msgId}", 1, 10)) {
    return; // Já está sendo processado
}
```

Combinado com UNIQUE constraint em `whatsapp_messages.waha_message_id`.

### Complemento
Filtrar por tipo no webhook config evitaria, mas perdemos `message.any` que é valioso pra detectar "celular enviou" vs "API enviou" (source field).

## 3. HMAC é SHA-512 (não SHA-256)

### Problema
Doc antiga minha dizia SHA-256. Na real o header `X-Webhook-Hmac` usa **SHA-512**. Se implementar validação errada, 100% dos webhooks rejeitados.

### Fix
Validar com `hash_hmac('sha512', $rawBody, $secret)`. Ver [[13-webhooks-events#hmac]].

## 4. `contact_picture_url` precisa ser TEXT, não VARCHAR(191)

### Problema
URLs do CDN Meta frequentemente passam 191 caracteres. Migration inicial usava `VARCHAR(191)` (default Laravel) → truncava silenciosamente → foto quebrava no render.

### Fix
Migration [`2026_02_21_000002_fix_column_lengths_whatsapp_conversations.php`](database/migrations/2026_02_21_000002_fix_column_lengths_whatsapp_conversations.php) muda pra `TEXT`.

**Verificar em prod**: `SHOW CREATE TABLE whatsapp_conversations` → tipo deve ser `text`, não `varchar`.

## 5. URLs do CDN Meta expiram em horas

### Problema
A URL retornada por `getChatPicture` aponta pro CDN do Meta (`pps.whatsapp.net/...`) e expira em algumas horas. Se salvar URL direto no banco, usuário abre chat no dia seguinte → foto 403.

### Fix
Baixar pra storage local via [ProfilePictureDownloader::download](app/Support/ProfilePictureDownloader.php):

```php
$localPic = \App\Support\ProfilePictureDownloader::download(
    $remotePic,
    'whatsapp',
    $tenantId,
    $phone
);
// Salva em storage/app/public/profile-pics/whatsapp/{tenant_id}/{phone}.jpg
```

Fallback: se download falhar (SSRF guard, timeout), retorna URL original.

## 6. `pushName` vs `pushname` (camelCase vs lowercase)

### Problema
Contact endpoint (`GET /api/contacts?contactId=...`) retorna as duas variantes dependendo da engine/versão:
- `pushName` (camelCase)
- `pushname` (lowercase)

Código que só checa `pushName` falha em 30%+ das conversas importadas.

### Fix
Commit `379a452` checa as 3 variantes (igual a integração WAHA→Chatwoot nativa):

```php
$contactName = $info['name']
    ?? $info['pushName']
    ?? $info['pushname']
    ?? null;
```

### Fonte
[`WhatsAppContactInfo.ts`](https://github.com/devlikeapro/waha/blob/core/src/apps/chatwoot/contacts/WhatsAppContactInfo.ts) no source oficial.

## 7. Timestamp fallback `now()` embaralha ordem cronológica

### Problema
No import de histórico, algumas mensagens vêm com `timestamp: 0` ou negativo. Código antigo:

```php
$sentAt = ($ts > 1577836800 && $ts < time() + 86400)
    ? Carbon::createFromTimestamp($ts)
    : now();   // ← fallback perigoso
```

Mensagens antigas com timestamp ruim recebiam `now()` → iam pro **topo** do chat junto com as recentes. Resultado: histórico bagunçado.

### Fix
Commit `379a452` skipa em vez de fallback:

```php
if ($ts <= 1577836800 || $ts >= time() + 86400) {
    Log::info('msg timestamp invalido, pulando');
    $skipped++;
    continue;
}
```

Alinhado com a filosofia da integração WAHA→Chatwoot (que nem preserva timestamp histórico na API Chatwoot).

## 8. `strlen > 13` NÃO é bom sinal de LID

### Problema
LIDs têm 14-15 dígitos, mas alguns fones internacionais legítimos também (Argentina `549...`).

### Fix
Usar o **sufixo `@lid`** do chatId como sinal primário:

```php
$fromIsLid = str_ends_with($from, '@lid');
```

Ver [[15-lid-handling]].

## 9. LID não resolvido = DADO INÚTIL → bloquear

### Problema
Se `getPhoneByLid` falha, poderíamos salvar conversa com `phone: "36576092528787"` (o LID). Mas essa conversa é inútil — não dá pra enviar mensagem pra um LID direto.

### Fix
[ProcessWahaWebhook](app/Jobs/ProcessWahaWebhook.php) e [ImportWhatsappHistory](app/Jobs/ImportWhatsappHistory.php) **retornam sem salvar** se LID não resolveu. Logam `Log::info('LID bloqueado')`.

## 10. `WhatsappInstance::first()` é ARMADILHA

### Problema
Tenants com múltiplas instances (rare mas acontece) → `first()` retorna uma aleatória. Quando usuário envia pela instance B, pode ir pela A.

### Fix
Sempre resolver via `conversation.instance_id`. Helper: [WhatsappMessageController::resolveInstance](app/Http/Controllers/Tenant/WhatsappMessageController.php). Ou usar [InstanceSelector::selectFor](app/Services/Whatsapp/InstanceSelector.php) — respeita hierarquia explicit → conversation → entity → primary.

## 11. `new WahaService(...)` direto em código novo é ARMADILHA

### Problema
Código hardcoded com `WahaService` não funciona se a instance for Cloud API (`provider='cloud_api'`).

### Fix
Usar factory:

```php
$service = \App\Services\WhatsappServiceFactory::for($instance);
$service->sendText($chatId, $body);
```

Factory retorna [`WhatsappCloudService`](app/Services/WhatsappCloudService.php) ou [`WahaService`](app/Services/WahaService.php) automaticamente.

## 12. ChatId NUNCA hardcoded como `$phone . '@c.us'`

### Problema
Grupos são `@g.us`, LIDs preservados como `@lid`, Cloud API é número puro. Hardcode quebra em todos esses casos.

### Fix
Usar [ChatIdResolver::for](app/Services/Whatsapp/ChatIdResolver.php):

```php
$chatId = ChatIdResolver::for($instance, $phone, $isGroup, $conversation);
```

## 13. Permissões de storage/logs no Docker

### Problema
Containers rodando com umask diferente criavam arquivos que outros containers do mesmo grupo não conseguiam escrever → webhook WAHA 500 silencioso → mensagens não chegavam.

### Fix permanente
[docker/entrypoint.sh](docker/entrypoint.sh):
```bash
umask 002
export UMASK=002
find /var/www/storage /var/www/bootstrap/cache -type d -exec chmod 2775 {} +
find /var/www/storage /var/www/bootstrap/cache -type f -exec chmod 664 {} +
```

**`2` no início** = setgid (arquivos herdam grupo). Aplicado no commit `fc27ac5` em 2026-04-14.

**Nunca mais** aplicar `chmod 775` manual — quebra o fix permanente.

## 14. Import timeout com tenants grandes

### Problema
Queue worker tem `timeout=900s`. Tenant com 500+ chats, cada um levando 1-2s, estoura os 15 min. Job falha com `TimeoutExceededException`.

### Solução parcial
Settings `WAHA_GOWS_DEVICE_HISTORY_SYNC_INITIAL_SYNC_MAX_MESSAGES_PER_CHAT=100` reduz tempo por chat.

### TODO futuro
Chunking: quebrar em sub-jobs por batch de N chats.

## 15. `scheduler` vs `queue` cron overlap

### Problema histórico
Comandos com `withoutOverlapping(5)` no Laravel 11 têm mutex de 5 MIN (não 24h default). Se crash entre renovações, lock trava e comandos de 1 minuto param.

### Fix
Se suspeitar que `whatsapp:send-scheduled` parou, rodar `php artisan schedule:clear-cache`.

## 16. `history_imported` flag impede re-import

### Problema
Após import inicial, `WhatsappInstance.history_imported = true`. ToolboxController `reimport-wa-history` reseta pra `false` antes de dispatchar.

Se dispatchar sem resetar → early-return no job.

## 17. `@c.us` urlencode

### Problema
Path params com `@` são problemáticos em alguns parsers. [WahaService::getChatPicture](app/Services/WahaService.php#L152) faz `rawurlencode($chatId)`:

```php
$encodedChatId = rawurlencode($chatId);
// "5511999999999@c.us" → "5511999999999%40c.us"
```

Essencial pra evitar 400 Bad Request em rotas como `/chats/{id}/picture` e `/messages/{id}`.

## 18. Outbound intent cache pra autoria

### Problema
Quando o chatbot/IA manda via `sendText`, a mensagem aparece no webhook como `message.any` com `fromMe: true`. Sem contexto extra, parece que "dono mandou pelo celular".

### Fix
Antes de cada send, registra intent no cache:

```php
Cache::put("outbound_intent:{$convId}:" . md5(trim($body)), [
    'sent_by' => 'chatbot',
    'sent_by_agent_id' => null,
], 120);
```

[ProcessWahaWebhook](app/Jobs/ProcessWahaWebhook.php) faz `Cache::pull` ao salvar o echo outbound. Preserva autoria real.

TTL 120s é suficiente pro echo voltar (~1-3s normal).

## 19. Migrations precisam rodar em prod

### Problema
Migration nova em desenvolvimento não aparece em prod até `php artisan migrate`. Esqueci de aplicar `2026_02_21_000002_fix_column_lengths_whatsapp_conversations.php` em prod → URLs truncadas por meses.

### Verificar
```bash
docker exec $(docker ps -q -f name=syncro_app) php artisan migrate:status | grep -i contact_picture
```

## 20. Entrypoint reconfigura Agno no boot

### Problema adjacente (não é WAHA, mas relacionado)
Agno cache in-memory perde config ao restart. Camila (IA) começa respondendo errado.

### Fix
Entrypoint roda `php artisan agno:reconfigure-all --wait=60` no boot. Ver [[Agno]].

## Regra geral

**Se algo falha silenciosamente no WAHA: 90% das vezes é `parse()` escondendo HTTP error ou `catch (\Throwable) {}` sem log.** Sempre adicionar logging estruturado antes de debugar algo do WAHA.

## Uso na Syncro

- [[18-nossa-implementacao]] — detalhes técnicos de cada arquivo
- [[13-webhooks-events]] — HMAC + events
- [[15-lid-handling]] — LID resolution
- Logs: `storage/logs/whatsapp-YYYY-MM-DD.log` (canal `whatsapp` dedicado)

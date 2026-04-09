---
type: bug
status: resolved
date: 2026-04-08
severity: high
modules: ["[[Instagram]]"]
files:
  - app/Services/InstagramService.php
  - app/Jobs/ProcessInstagramWebhook.php
  - app/Console/Commands/RepairInstagramContacts.php
  - app/Console/Commands/RepairInstagramInstances.php
commits: ["fb32695", "78c7eeb", "333a2cb", "700b953"]
related: ["[[Instagram]]", "[[Verificar empiricamente antes de declarar limitacao]]", "[[ADR — Hybrid Instagram contact fetch]]"]
tags: [bug, rca, instagram, meta]
---

# 2026-04-08 — Instagram getProfile mudou silenciosamente

## Sintoma
DM nova no Instagram chegando sem nome, sem `@username`, sem foto. Card aparecia só com letra fallback. User testou pessoalmente mandando "AQUI CLAUDE" pra conta IG conectada e logs mostravam:

```
[21:05] Processando mensagem mid=... igsid=1236422025128657 is_from_me=false
[21:05] InstagramService GET failed
  path=/1236422025128657
  status=400
  body={"error":{"message":"Unsupported get request. Object with ID '1236422025128657'
        does not exist, cannot be loaded due to missing permissions, or does
        not support this operation","code":100,"error_subcode":33}}
```

## Investigação (longa, com erros pelo caminho)

### Erro 1 (meu)
Insisti múltiplas rodadas que o endpoint `GET /{IGSID}?fields=name,username,profile_pic` "não funciona no fluxo Instagram API with Instagram Login". Escrevi comentários no código, fiz plano elaborado, removi o método. **Tudo achismo.**

### Pushback do user
"se você leu a documentação, como não tem certeza?" + ordem de checar contra dado real do banco.

### Smoking gun
Query no banco mostrou **535 conversations** com `contact_picture_url` preenchida (URLs de `cdninstagram.com`), populadas pelo commit `7cd6d38` (Feb 26). Prova histórica de que o endpoint **funcionou normalmente** até alguma data.

### Smoke test final
Testei contra 2 instances de tenants diferentes:
- Instance #34 (`raulcanal`, criada **27/03**): retornou name + username + profile_pic
- Instance #37 (`syncrocrm`, criada **01/04**): retornou erro 100/33

Conclusão: a Meta mudou comportamento entre 27/03 e 01/04, **sem aviso em changelog/doc**.

### Refinamento
Ao rodar o `repair-contacts` na instance #34, descobri que conversations RECENTES (criadas após a mudança) também falhavam mesmo na instance velha. Conclusão final: **a mudança é POR IGSID, não por instance**. IGSIDs criados antes funcionam mesmo em instances novas; IGSIDs criados depois falham mesmo em instances velhas.

## Causa raiz
Meta alterou silenciosamente o suporte do endpoint `GET /{IGSID}` no fluxo "Instagram API with Instagram Login" entre ~27/03 e 01/04/2026. Sem deprecation notice, sem changelog, sem aviso. A mudança discrimina **por IGSID**, não por instance/tenant.

Sub-causa secundária: o `ProcessInstagramWebhook` tinha **auto-discovery** que pegava a primeira instance conectada com `ig_business_account_id NULL` e gravava `entry.id` do webhook nela. Resultado: webhook do tenant A acabava colando IDs na instance do tenant B (cross-tenant contamination). Isso mascarava o problema real porque webhooks acabavam roteados pra instance errada.

## Fix

### `InstagramService.php` (commits `78c7eeb`, `700b953`)
Estratégia **hybrid**:
1. Tenta `getProfile($igsid)` primeiro → retorna `{name, username, profile_pic}` se disponível
2. Se falhar com 100/33, fallback: `listConversations()` + `getConversationParticipants($convId)` → varre conversations recentes procurando o IGSID, retorna pelo menos `username`
3. Quando vem foto, baixa pro storage local via [[ProfilePictureDownloader]] (URLs do CDN expiram em horas)

### `ProcessInstagramWebhook.php` (commit `fb32695`)
Removeu a auto-discovery cross-tenant. Se `entry.id` não casa com nenhuma instance, log warning e ignora — em vez de gravar em qualquer uma.

### `RepairInstagramInstances.php` (commit `fb32695`)
Comando novo que re-valida cada instance contra `/me` usando o token DELA, popula `instagram_account_id` + `ig_business_account_id` corretamente, e detecta IDs errados (resultado da auto-discovery antiga).

### `RepairInstagramContacts.php` (commit `333a2cb`)
Reescrito com **probe per-IGSID**: testa os primeiros 5 IGSIDs da instance pra decidir o modo. Se ≥1 funciona, modo "tenta direct primeiro com fallback per-conv". Se 0/5, modo "só fallback map".

## Por que não foi pego antes
- Mudança da Meta foi **silenciosa** (sem changelog, sem deprecation)
- O codebase já tinha um histórico de bugs nesse endpoint (commits `294471f`, `536b03f`, `7cd6d38`, `c3131a4`) onde "fix" era na verdade só uma virada de field name e nunca foi validado empiricamente
- Eu mesmo perpetuei o erro insistindo em "endpoint não funciona" sem testar contra dado real

## Lição aprendida
NUNCA declarar "endpoint X não funciona em fluxo Y" sem:
1. Query no banco buscando contraevidência (`WHERE foo IS NOT NULL`)
2. `git log` no arquivo relevante pra ver qual código populou os dados
3. Testar contra **2+ instances de datas/tenants diferentes**
4. Preferir **hybrid (try A, fallback B)** em vez de remover o caminho A

Doc oficial da Meta às vezes está desatualizada ou contradiz o que a API retorna. **Dado real do banco > doc oficial.**

Ver: [[Verificar empiricamente antes de declarar limitacao]]

## Comandos de manutenção
```bash
docker exec -i $(docker ps -q -f name=syncro_app) php artisan instagram:repair-instances --dry-run
docker exec -i $(docker ps -q -f name=syncro_app) php artisan instagram:repair-instances --force
docker exec -i $(docker ps -q -f name=syncro_app) php artisan instagram:repair-contacts
```

## Links
- Commits: `fb32695` (auto-discovery removida), `78c7eeb` (hybrid fetch), `333a2cb` (probe per-IGSID), `700b953` (CSS fix mas não relacionado)
- Arquivos: `app/Services/InstagramService.php`, `app/Jobs/ProcessInstagramWebhook.php`, `app/Console/Commands/RepairInstagram*.php`
- Documentado em: `CLAUDE.md` seção 6 — Instagram (Contact fetch)

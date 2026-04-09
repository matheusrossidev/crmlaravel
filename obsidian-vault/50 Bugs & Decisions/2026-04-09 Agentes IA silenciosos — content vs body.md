---
type: bug
status: resolved
date: 2026-04-09
severity: critical
modules: ["[[AI Agents]]"]
files:
  - app/Jobs/ProcessAiResponse.php
  - app/Services/AgnoService.php
  - app/Models/WhatsappMessage.php
commits: ["ee89e23"]
related: ["[[AI Agents]]", "[[AgnoService]]", "[[WhatsappMessage]]", "[[Verificar empiricamente antes de declarar limitacao]]"]
tags: [bug, rca, ai, critical]
---

# 2026-04-09 — Agentes IA silenciosos: `content` vs `body`

## Sintoma
- User criou agente "Camila" (#12) hoje 16:19
- Mandou 2 mensagens pro WhatsApp conectado, agente NÃO respondeu
- Investigação revelou que **Sophia (#3) também parou** (funcionava até pouco antes)
- User frustrado: "desde que mexeu nos follow up e lembrança da agenda fudeu tudo"

## Investigação (com erro de hipótese pelo caminho)

### Hipótese inicial errada
Pensei que era `configureAgent` falhando silenciosamente quando Camila foi criada — Agno não tendo o agent em cache. Mandei o user testar `/agents/12` no Agno.

### Smoking gun real
User rodou `/chat` direto contra o Agno via tinker, **passando `message=body`**:
```
status: 200
body: {"reply_blocks":["Olá! Como posso ajudar você hoje?..."]}
```

**Camila funcionava perfeitamente.** O Agno gerou resposta, o tenant tinha tokens, tudo OK.

### Bug real encontrado
[`ProcessAiResponse.php:833`](app/Jobs/ProcessAiResponse.php) e `:976`:
```php
'query'   => $lastMessage->content ?? '',  // ❌
'message' => $lastMessage->content ?? '',  // ❌
```

`WhatsappMessage` **não tem campo `content`**. O campo é `body`. `->content` retorna `null`, vira `''`, manda `message=""` pro Agno.

### Inconsistência estrutural descoberta
| Model | Campo de texto |
|---|---|
| `WhatsappMessage` | `body` ✅ |
| `InstagramMessage` | `body` ✅ |
| `WebsiteMessage` | `content` ⚠️ |

`WebsiteMessage` é o único do trio que usa `content`. Quem expandiu o `ProcessAiResponse` provavelmente copiou-colou de algum spot de website chat sem perceber que estava lendo `WhatsappMessage`.

### Por que Sophia funcionou ATÉ HOJE
- `ProcessAiResponse.process()` linhas 313-352 tem caminho LLM direto como **fallback** quando `use_agno=true` mas Agno retorna vazio
- `buildHistory()` do `AiAgentService` usa `$msg->body` corretamente — então o caminho LLM direto sempre funcionou
- Mecânica: Agno recebia `message=""` → retornava erro 422 OU resposta vazia → `$reply` ficava vazio → fallback LLM kicka → Sophia respondia
- O que mudou: Agno passou a aceitar `message=""` e retornar `reply_blocks=[]` (HTTP 200) em vez de erro. Resultado: `$reply` não fica mais vazio (é `[]` que vira string), fallback LLM **não kicka mais**, Sophia silencia

## Causa raiz
**Bug de schema mismatch** entre 3 models de Message + falta de typing forte / validação de field name. Existia há tempo (não introduzido por commit recente meu), mas estava silencioso até o Agno mudar comportamento de validação.

## Fix (commit `ee89e23`)

### Fix 1 — As 2 linhas críticas
`content` → `body` em `ProcessAiResponse.php:833` e `:976`. Comentado no código com aviso.

### Fix 2 — Logging estruturado em `AgnoService::chat`
Antes:
```php
throw new \RuntimeException("Agno service error [{$response->status()}]: {$response->body()}");
```

Depois: log estruturado COM body, agent_id, msg_len, has_phone, history_len ANTES de jogar exception. Se eu tivesse esse log no canal `whatsapp`, teria visto o erro 422 do Pydantic e diagnosticado em 2 minutos em vez de 2 horas.

### Fix 3 — Early abort se message vazio
Antes de chamar Agno, valida `messageBody === ''` e aborta com warning. Cobre casos futuros onde algum tipo de mensagem (sticker, audio sem transcrição) chegue sem body.

### Fix 4 — Accessor deprecated `getContentAttribute()` em `WhatsappMessage`
**Garante que esse bug nunca mais aconteça.** Se algum dev/IA futuro escrever `$msg->content` em `WhatsappMessage`, o accessor:
1. Loga warning no canal `whatsapp` com stack trace do caller
2. Retorna `$this->body` como fallback (não quebra silenciosamente)

```php
public function getContentAttribute(): ?string
{
    $bt = debug_backtrace(...);
    Log::channel('whatsapp')->warning(
        'WhatsappMessage->content acessado (DEPRECATED, use ->body)',
        ['msg_id' => $this->id, 'caller' => $bt[1]['file'] . ':' . $bt[1]['line']]
    );
    return $this->body;
}
```

## Por que não foi pego antes
- Caminho LLM direto cobria silenciosamente (era um fallback, mas funcionava sempre)
- Sem typing forte de field names em PHP — `$msg->content` em vez de `$msg->body` não dá erro de syntax
- 0 testes E2E que validem que Agno recebe `message != ""`
- Auditoria de leads (commit `9624215`) e auditoria CLI (commit `65092d1`) **não cobriram** `ProcessAiResponse` porque estavam focadas em outros módulos

## Lições aprendidas

### Lição 1: schema inconsistente entre modelos relacionados é um relógio bombando
3 models de Message (`Whatsapp`, `Instagram`, `Website`) com schemas diferentes (`body` vs `content`) é convite pra bug. Eventualmente alguém copia-cola código entre eles e o erro fica silencioso até que o comportamento upstream mude.

**Padrão a seguir**: padronizar schemas entre models polimorficamente relacionados. Próximo PR deveria renomear `WebsiteMessage.content` → `body` (migration + atualizar ~10 spots).

### Lição 2: fallback silencioso mascara bug
O caminho LLM direto cobrindo o erro do Agno por meses fez com que o bug ficasse invisível até o comportamento do Agno mudar. **Fallback nunca deve ser silencioso** — deveria ter logado warning toda vez que kicka.

### Lição 3: errors devem incluir todo contexto do payload, não só status
`AgnoService::chat()` jogava só status no exception. Pra diagnosticar bug do payload, tive que reproduzir manualmente via tinker. **Toda exception de HTTP call deve logar body do response + payload size + identificadores** antes de jogar.

### Lição 4: accessor deprecated com warning é defesa preventiva barata
Fix 4 (accessor) é 10 linhas de código que **garantem** que esse exato bug nunca mais aconteça. Vale aplicar o mesmo pattern em qualquer model com schema legacy/inconsistente.

## Links
- Commit: `ee89e23`
- Arquivos: [app/Jobs/ProcessAiResponse.php](app/Jobs/ProcessAiResponse.php), [app/Services/AgnoService.php](app/Services/AgnoService.php), [app/Models/WhatsappMessage.php](app/Models/WhatsappMessage.php)
- Lessons: [[Verificar empiricamente antes de declarar limitacao]]
- Dívida técnica: padronizar `WebsiteMessage.content` → `body`

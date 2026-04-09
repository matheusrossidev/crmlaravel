---
type: bug
status: resolved
date: 2026-04-09
severity: critical
modules: ["[[AI Agents]]"]
files:
  - app/Jobs/ProcessAiResponse.php
  - app/Services/AgnoService.php
  - app/Services/AiAgentService.php
  - app/Models/WhatsappMessage.php
  - app/Console/Commands/ReconfigureAgnoAgents.php
  - agno-service/agent_factory.py
  - agno-service/main.py
  - agno-service/formatter.py
  - agno-service/schemas.py
  - docker/entrypoint.sh
commits: ["ee89e23", "331e649", "609aad4", "bd32135"]
related: ["[[AI Agents]]", "[[AgnoService]]", "[[Agno]]", "[[Verificar empiricamente antes de declarar limitacao]]", "[[Cache in-memory perde tudo no restart]]"]
tags: [bug, rca, ai, critical, multi-layer]
---

# 2026-04-09 — Camila e Sophia silenciosas: 5 bugs encadeados no Agno

## Sintoma
- User criou agente "Camila" (#12) hoje 16:19, configurou identidade clinica medica.
- Mandou varias mensagens pro WhatsApp conectado, agente NAO respondeu.
- Investigacao revelou que **Sophia (#3) tambem parou** (funcionava ate antes).
- User frustrado: "desde que mexeu nos follow up e lembranca da agenda fudeu tudo, realmente e complicado confiar em tu pra fazer alteracao".
- Apos o primeiro fix, voltou a responder mas com identidade errada ("Nos somos da Syncro CRM" em vez da clinica).
- Apos o segundo fix, identidade certa mas mensagens "tenha um otimo dia" as 19h e mensagens picotadas em 150 chars apesar de configurada pra 700.

## Investigacao

### Bug 1: schema mismatch — `content` vs `body`
[`ProcessAiResponse.php:833`](app/Jobs/ProcessAiResponse.php) e `:976`:
```php
'query'   => $lastMessage->content ?? '',  // ERRADO
'message' => $lastMessage->content ?? '',  // ERRADO
```

`WhatsappMessage` nao tem campo `content` — o campo e `body`. `->content` retornava `null`, virava `''`, mandava `message=""` pro Agno.

| Model | Campo de texto |
|---|---|
| `WhatsappMessage` | `body` |
| `InstagramMessage` | `body` |
| `WebsiteMessage` | `content` (inconsistente — origem da confusao) |

Quem expandiu o `ProcessAiResponse` provavelmente copiou-colou de algum spot de website chat sem perceber que estava lendo `WhatsappMessage`.

**Por que Sophia funcionou ATE HOJE**: o caminho LLM direto rodava como fallback silencioso quando Agno retornava vazio. O Agno passou a aceitar `message=""` retornando 200 OK + `reply_blocks=[]`, e o fallback parou de kickar. Sophia silenciou.

### Bug 2: history multimodal nao serializa pra Agno
Apos fix do bug 1, Camila comecou a responder mas Pydantic do Agno rejeitava com 422:
```
"loc": ["body", "history", 3, "content"],
"msg": "Input should be a valid string",
"input": [{"type": "image_url", "image_url": {"url": "data:image/png;base64,..."}}]
```

`AiAgentService::buildHistory()` linha 691 monta `content` como **array multimodal OpenAI vision** quando a mensagem tem imagem. Funciona pro caminho LLM direto, mas Agno espera `content` como **string**.

Conversas com qualquer imagem no historico recente paravam de funcionar — a primeira mensagem da Camila era imagem entao ela "ja nasceu morta".

### Bug 3: cache in-memory do Agno perde tudo no restart
Apos fix do bug 2, Camila respondia mas dizia "Nos somos da Syncro CRM" em vez de identidade da clinica. O `objective="general"` e `persona=vazio` no banco indicavam config pobre — mas tinha campos novos (`persona_description`, `behavior`, `company_name`) preenchidos.

O `AiAgentController::syncToAgno` mandava todos os campos certos pro Agno via POST `/configure`. Mas o `_agent_configs` em `agent_factory.py` e um **dict Python in-memory**:

```python
config = _agent_configs.get(agent_id, {})
if not config:
    config = {"tenant_id": tenant_id, "llm_provider": "openai"}
```

Quando o container `syncro_agno` reinicia (deploy, scale, crash), perde tudo. A proxima `/chat` cai num fallback generico, monta um prompt vazio ("Voce e Assistente, assistente de nossa empresa") e o LLM puxa contexto da memoria vetorial — alucina identidade. Como o tenant 12 tem historico antigo da Sophia (Syncro CRM), Camila herda essa identidade.

### Bug 4: formatter ignora `max_message_length`
Apos identidade corrigida, user setou `max_message_length=700` no painel pra Camila explicar procedimentos clinicos com calma. Mas continuou recebendo mensagens picotadas em ~150 chars.

[`agno-service/formatter.py:6`](agno-service/formatter.py):
```python
MAX_BLOCK = 150  # HARDCODED
```

O second-pass formatter (LLM call que humaniza/quebra a resposta em blocos) ignorava completamente o `max_message_length` do agent.

### Bug 5: contexto temporal inexistente
Camila se despedia "tenha um otimo dia!" as 19h. Causa: o Agno nao recebia nenhuma informacao de data/hora. O config armazenado e estatico. O fuso do container `syncro_agno` e UTC, entao mesmo se ele tentasse calcular sozinho ia errar o fuso brasileiro.

## Causa raiz

5 bugs **encadeados** mascarando uns aos outros, todos relacionados a falhas de contrato entre PHP e Agno:
1. Schema mismatch entre 3 models de Message + dev/IA copiando-colando codigo errado
2. Falta de serializacao consciente entre formato OpenAI vision (PHP/LLM direto) e formato Agno (string pura)
3. State em RAM sem persistencia + sem mecanismo de boot que repopula
4. Constante hardcoded em vez de receber config por agent
5. Ausencia de injecao de contexto temporal por chat

## Fix

### Fix 1 — Bug content/body (commit `ee89e23`)
- `ProcessAiResponse.php`: `content` → `body` nos 2 spots
- Logging estruturado em `AgnoService::chat` quando falha (status + body do erro + payload meta)
- Early abort se `lastMessage->body === ''` antes de chamar Agno
- Accessor deprecated em `WhatsappMessage::getContentAttribute()` que retorna `body` mas loga warning com stack trace pra qualquer codigo futuro que tente usar `->content`

### Fix 2 — Bug history multimodal (commit `331e649`)
[`ProcessAiResponse.php:859`](app/Jobs/ProcessAiResponse.php) — `array_map` que copia `content` agora **achata** quando e array:

```php
$history = array_map(function ($m) {
    $content = $m['content'];
    if (is_array($content)) {
        $parts = [];
        foreach ($content as $block) {
            $type = $block['type'] ?? null;
            if ($type === 'text' && isset($block['text'])) {
                $parts[] = (string) $block['text'];
            } elseif ($type === 'image_url') {
                $parts[] = '[imagem]';
            } elseif ($type === 'audio') {
                $parts[] = '[audio]';
            }
        }
        $content = trim(implode(' ', $parts)) ?: '[midia]';
    }
    return ['role' => $m['role'], 'content' => (string) $content];
}, $rawHistory);
```

Caminho LLM direto continua usando `$rawHistory` original (multimodal preservado).

### Fix 3 — Bug cache in-memory (commit `609aad4`)
- `AgnoService::configureFromAgent(AiAgent $agent)` — metodo unico que centraliza o mapping AiAgent → payload Agno
- `app/Console/Commands/ReconfigureAgnoAgents.php` — novo comando `agno:reconfigure-all` que itera todos os agents `use_agno=true is_active=true` e reconfigura
- `docker/entrypoint.sh` roda em background apos startup do app:
  ```bash
  php artisan agno:reconfigure-all --wait=60 &
  ```
- Toda vez que `syncro_app` inicia, repopula o cache do Agno. Cobre deploy, scale, crash do `syncro_agno`.

### Fix 4 — Bug formatter hardcoded (commit `bd32135`)
[`agno-service/formatter.py`](agno-service/formatter.py):
```python
async def format_as_whatsapp_blocks(
    text: str, provider: str, model: str, api_key: str,
    max_block: int = DEFAULT_MAX_BLOCK,  # NOVO parametro
) -> list[str] | None:
```

`main.py` passa `config.get("max_message_length")` na chamada. Cada agente respeita o proprio limite. Camila pode ter 700, Sophia 200.

### Fix 5 — Bug contexto temporal (commit `bd32135`)
PHP (`ProcessAiResponse`) calcula no fuso do app:
```php
$now = now();
$hour = (int) $now->format('H');
$periodOfDay = $hour < 5 ? 'madrugada' : ($hour < 12 ? 'manha' : ($hour < 18 ? 'tarde' : 'noite'));
$greeting = $hour < 5 ? 'ola' : ($hour < 12 ? 'bom dia' : ($hour < 18 ? 'boa tarde' : 'boa noite'));
$currentDt = $now->locale('pt_BR')->isoFormat('DD/MM/YYYY (dddd) — HH:mm');
```

Envia no payload do `/chat` como `current_datetime`, `period_of_day`, `greeting`. Agno schema (`ChatRequest`) recebe os 3 campos novos. `agent_factory._build_instructions` injeta no system prompt um bloco "DATA E HORA ATUAL (CRITICO)" com regras explicitas: NUNCA "bom dia" se nao for manha, NUNCA "tenha um otimo dia" a noite, etc.

## Por que nao foi pego antes

- **Bug 1 (content/body)**: caminho LLM direto cobria silenciosamente. Funcionava ate o Agno mudar comportamento de validacao (passou a aceitar `message=""`).
- **Bug 2 (multimodal)**: a maioria das conversas nao tem imagem no historico recente. So aparecia em conversas especificas.
- **Bug 3 (cache in-memory)**: so se manifestava apos restart do `syncro_agno`. Era intermitente, dificil de correlacionar com deploy.
- **Bug 4 (formatter)**: o `max_message_length` parecia aleatorio porque o user nem sabia que existia second-pass formatter — pensava que era o LLM principal nao respeitando.
- **Bug 5 (temporal)**: assumimos que o LLM "sabe" a hora porque tem treinamento ate uma data — mas nao tem nenhum mecanismo de runtime pra hora atual sem injecao explicita.
- **Geral**: ausencia de logging estruturado em `AgnoService::chat` impedia ver o body do 422 do Pydantic. Ate o Fix 1 ser aplicado, todas as chamadas falhavam silenciosamente (so status code).

## Licoes aprendidas

### Licao 1: schemas inconsistentes entre models relacionados sao bombas-relogio
3 models de Message com schemas diferentes (`body` vs `content`) e convite pra bug. Eventualmente alguem copia-cola codigo entre eles e o erro fica silencioso ate o comportamento upstream mudar. **Padrao**: padronizar schemas entre models polimorficamente relacionados.

### Licao 2: state in-memory em servico distribuido perde tudo no restart
Sempre que vejo um dict/Map/array global em modulo Python/Node/Go, pergunto: o que acontece num restart? Se a resposta for "perde tudo", ou eu (a) persisto em disco/db, ou (b) repopulo via fonte de verdade externa no boot. Aplicado em [[Cache in-memory perde tudo no restart]] como padrao geral.

### Licao 3: contratos entre servicos precisam validar formato, nao so existencia
PHP mandando `message=""` pro Agno passou despercebido por meses porque nao tinha validacao de "string nao vazia" no contrato. Adicionar **early aborts defensivos** e **logging estruturado de erros HTTP com body** sao baratos e salvam horas de debug.

### Licao 4: configuracoes que afetam comportamento devem ser por-instancia, nao constantes globais
`MAX_BLOCK = 150` num modulo compartilhado matava qualquer customizacao por agent. Padrao: aceitar como parametro com default. Se o caller quer customizar, passa.

### Licao 5: contexto temporal nunca e gratis em LLMs
LLMs nao sabem a hora atual. Se o agente precisa saber, INJETA explicitamente no system prompt a cada chamada. Container UTC + fuso BR e gambiarra automatica — calcule no PHP no fuso correto e envie como string formatada.

### Licao 6: bugs encadeados precisam de fixes encadeados — nao tem atalho
A vontade de "achar a causa raiz unica" me fez perder tempo nesse caso. Eram 5 causas independentes. O processo certo: **arruma o primeiro, deploya, ve qual o proximo**. Tentar achar tudo de uma vez = perder o sinal das logs.

## Links
- Commits: `ee89e23` (content/body), `331e649` (multimodal), `609aad4` (reconfigure-all), `9c1b7fb` (RAG), `bd32135` (formatter + temporal)
- Arquivos: ver lista no frontmatter
- RCAs relacionados: [[2026-04-09 RAG real implementado]], [[Cache in-memory perde tudo no restart]]

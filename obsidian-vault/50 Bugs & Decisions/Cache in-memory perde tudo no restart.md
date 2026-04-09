---
type: lesson
status: active
related: ["[[2026-04-09 Camila e Sophia silenciosas — 5 bugs do Agno]]", "[[Agno]]"]
tags: [lesson, pattern, distributed-systems]
---

# Cache in-memory perde tudo no restart — sempre tenha plano de boot

## A licao

Sempre que vejo um `dict`/`Map`/`array` global em modulo Python/Node/Go que armazena estado importante (config de cliente, sessao, cache, etc), pergunto:

> **O que acontece num restart desse processo?**

Se a resposta for "perde tudo e o sistema fica num estado degradado ate alguem fazer X", isso e uma **bomba-relogio**. Ou:
1. Persisto em disco/db, ou
2. Repopulo via fonte de verdade externa no boot, ou
3. Faco lazy load sob demanda no proximo acesso (mas isso so funciona se "primeiro acesso" e detectavel/recuperavel)

## O caso real

`agno-service/agent_factory.py`:
```python
_agent_configs: dict[int, dict[str, Any]] = {}

def get_or_create_agent(agent_id, tenant_id, ...):
    config = _agent_configs.get(agent_id, {})
    if not config:
        config = {"tenant_id": tenant_id, "llm_provider": "openai"}  # FALLBACK
    # ...
```

Quando o container `syncro_agno` reinicia (deploy, scale, crash, OOM), `_agent_configs` volta a `{}`. A proxima `/chat` cai no fallback generico e o agent fica sem identidade — o LLM puxa contexto da memoria vetorial e **alucina** quem ele e. Bug observado em [[2026-04-09 Camila e Sophia silenciosas — 5 bugs do Agno]]: Camila e Sophia respondendo como "Syncro CRM" depois de um deploy.

## Solucao aplicada (Opcao 2: repopulacao no boot)

`docker/entrypoint.sh` do app PHP roda em background no startup:
```bash
php artisan agno:reconfigure-all --wait=60 &
```

`agno:reconfigure-all` itera todos `AiAgent` com `use_agno=true is_active=true` e chama `AgnoService::configureFromAgent($agent)` (que faz POST `/agents/{id}/configure`). Toda vez que o `syncro_app` inicia, repopula o cache do Agno. Cobre deploy, scale, crash do `syncro_agno`.

**Por que opcao 2 e nao opcao 1**: persistir `_agent_configs` em disco exigiria mexer no codigo Python do Agno (escrever em json file ou pgvector), riscos de corrupcao, sincronizacao com PHP. Opcao 2 e mais simples e ja temos a fonte de verdade no MySQL.

## Outros lugares pra checar nesse codebase

Quando aparecer dict/Map global em outros services Python ou state holder em jobs, fazer a mesma pergunta:

- `agno-service/agent_factory.py` `_agent_cache` — agent objects do framework Agno. Provavelmente OK porque sao recriados sob demanda quando faltam (graceful), mas vale checar
- Qualquer cron command que mantem estado entre execucoes via variaveis de classe — em Laravel isso e raro porque cada `php artisan x` e processo novo

## Padrao a seguir

1. **Identifique** estado in-memory crítico
2. **Decida** entre persistir ou repopular
3. **Documente** a decisao no codigo (comentario explicando o "porque" do pattern)
4. **Teste** o cenario de restart explicitamente: `docker service restart syncro_X` e ver se o sistema continua funcionando

## Anti-padrao

Confiar que "o servico nunca vai reiniciar" ou "se reiniciar, o user vai perceber e me avisar". Em prod com auto-scale, restart pode acontecer a qualquer momento (memoria, CPU, deploy zero-downtime, no-host migration). Se o sistema entra em estado degradado silencioso apos restart, voce **vai** perder horas debugando 6 semanas depois quando alguem reclamar.

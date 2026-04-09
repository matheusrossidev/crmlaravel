---
type: bug
status: resolved
date: 2026-04-09
severity: low
modules: ["[[AI Agents]]", "[[Calendar & Reminders]]"]
files:
  - app/Services/AiAgentService.php
  - app/Jobs/ProcessAiResponse.php
commits: ["f8e6513"]
related: ["[[AI Agents]]", "[[Calendar & Reminders]]"]
tags: [bug, rca, ai, calendar]
---

# 2026-04-09 — Telefone obrigatório no description do calendar

## Sintoma
User pediu pra reforçar que **o agente IA sempre coloque o telefone do contato** no `description` do evento que cria no Google Calendar — pra que o vendedor abra o evento e tenha o número à mão pra ligar.

Estado anterior: a instrução do system prompt dizia "Telefone: \[se disponível\]" — opcional. O LLM às vezes esquecia. O fallback PHP em [[ProcessAiResponse]] (linhas 1437-1451) só injetava o bloco se o LLM não tivesse mencionado nada — detecção frágil baseada em substring match (`str_contains($agentDesc, 'Cliente:')`), o que escapava casos comuns.

## Causa raiz
Instrução pro LLM era ambígua + detecção do fallback PHP era frágil. Não era exatamente "bug" — era falta de garantia.

## Fix (commit `f8e6513`)

### `AiAgentService::buildSystemPrompt`
1. **Novo parâmetro `?WhatsappConversation $conv`** pra acessar `$conv->phone` quando não há `$lead`
2. **"Dados do contato desta conversa"** agora pre-puxa o telefone explicitamente:
```
Nome: João | Telefone: 11912345678 ← USE ESTE no campo description (obrigatório) | Email: joao@x.com
```
3. **Seção "DESCRIÇÃO DO EVENTO"** reforçada com regras absolutas:
   - "Se Dados do contato mostra Telefone X, você OBRIGATORIAMENTE inclui essa linha no description"
   - "Copie tal e qual, sem reformatar"
   - "Só pode omitir Telefone se Dados do contato disser 'não disponível'" (caso Instagram/Web)

### `ProcessAiResponse:316`
Caller atualizado pra passar `$conv` no `buildSystemPrompt`.

## Suspensórios + cinto
Duas camadas atuam em paralelo:
1. **System prompt** instrui o LLM explicitamente
2. **Fallback PHP** ([ProcessAiResponse.php:1437-1451](app/Jobs/ProcessAiResponse.php#L1437)) continua injetando bloco "Cliente: ..." se o LLM esquecer

## Caso Instagram
Instagram não tem `phone` — só `igsid`. Pra esses casos, "Dados do contato" mostra `Telefone: não disponível` e a instrução permite omitir a linha.

## Por que não foi pego antes
- Eventos com telefone faltando não geram erro — só passam despercebidos até alguém abrir
- Dependia de comportamento estatístico do LLM (às vezes lembrava, às vezes não)
- Fallback PHP existia mas tinha brecha lógica não-óbvia

## Links
- Commit: `f8e6513`
- Arquivos: [`app/Services/AiAgentService.php`](app/Services/AiAgentService.php), [`app/Jobs/ProcessAiResponse.php`](app/Jobs/ProcessAiResponse.php)
- Documentado em: [[Calendar & Reminders]]

---
type: module
status: active
related: ["[[SophiaActionExecutor]]", "[[HelpChatController]]"]
files:
  - app/Services/SophiaActionExecutor.php
  - app/Http/Controllers/Tenant/HelpChatController.php
last_review: 2026-04-09
tags: [module, ai, sophia, internal]
---

# Sophia AI Assistant

## O que é
Widget flutuante de IA **interna** que executa actions no CRM do tenant via whitelist + rate limit + confirmação. Roda em todas as páginas exceto chat/chatbot/parceiro. Pode criar entidades (scoring rules, sequences, pipelines, automations, custom fields, tasks, leads) e fazer queries (leads, performance).

## Status
- ✅ Thinking steps animados (estilo Kodee)
- ✅ Action execution com whitelist hardcoded
- ✅ Card de confirmação antes de executar (UX explícita)
- ✅ Rate limit 10 actions/min por user
- ✅ Tenant-scoped (não vaza entre tenants)

## Actions disponíveis (whitelist)
- `create_scoring_rule`
- `create_sequence`
- `create_pipeline`
- `create_automation`
- `create_custom_field`
- `create_task`
- `create_lead`
- `query_leads`
- `query_performance`

## Segurança
- Whitelist hardcoded em [[SophiaActionExecutor]] — adicionar action exige PR
- `tenant_id` injetado no contexto de toda action (impossível vazar)
- Rate limit por user
- Confirmação obrigatória antes de executar (UX bloqueia)

## Fluxo
```
User digita pergunta no widget
  → POST /help-chat
    → HelpChatController com system prompt (docs + actions disponíveis)
    → LLM responde com JSON (reply + actions[])
    → Frontend renderiza thinking steps + card de actions pra confirmar
    → User confirma → POST /help-chat/execute
      → SophiaActionExecutor::execute(action) — valida whitelist + payload + executa
      → Retorna resultado pro frontend
```

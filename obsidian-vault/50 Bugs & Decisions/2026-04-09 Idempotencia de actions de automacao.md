---
type: bug
status: resolved
date: 2026-04-09
severity: medium
modules: ["[[Automations]]"]
files:
  - app/Services/AutomationEngine.php
commits: ["41cc967"]
related: ["[[Automations]]", "[[AutomationEngine]]"]
tags: [bug, rca, automations, idempotency]
---

# 2026-04-09 — Idempotência de actions de automação

## Sintoma
User tem automação:
```
Trigger: Nova conversa
Ação: Atribuir a usuário (Matheus Rossi)
```
Reportou que **"toda mensagem que recebo, roda a automação"** — recebia notificações "Lead atribuído a você" repetidas mesmo quando o lead já estava atribuído ao mesmo usuário Matheus.

## Investigação
1. Verifiquei `AutomationEngine::run($triggerType, $context)` — filtra estritamente por `where('trigger_type', $triggerType)`. Automação `conversation_created` **NÃO roda** em `message_received`. Então a queixa literal "toda mensagem dispara nova conversa" não pode ser verdade.

2. Verifiquei `conversation_created` — disparado em apenas 3 lugares (`ProcessWahaWebhook`, `ProcessWhatsappCloudWebhook`, `ProcessInstagramWebhook`), todos dentro de `if (! $conversation)` que cria conversa nova. **Cada conversa nova só dispara uma vez.**

3. Verifiquei `actionAssignToUser` ([AutomationEngine.php:404-421](app/Services/AutomationEngine.php#L404-L421)):
```php
$userId = (int) $config['user_id'];
Lead::withoutGlobalScope('tenant')
    ->where('id', $lead->id)
    ->update(['assigned_to' => $userId]);

try {
    (new NotificationDispatcher())->dispatch('lead_assigned', [...]);
} catch (\Throwable) {}
```

**Sem nenhum check de idempotência.** Mesmo se `lead.assigned_to` JÁ for `$userId`, faz UPDATE e dispara notificação.

4. Audit de outras 13 actions: **5 sofrem do mesmo problema** (state changes sem check).

## Causa raiz
`actionAssignToUser` (e mais 4) não checavam se o valor já era o esperado antes de fazer UPDATE + side effects. Resultado: cada execução da automação disparava notificação de assignment — mesmo quando nada mudava.

A queixa do user provavelmente refletia algum cenário de re-criação de conversa por edge case (LID resolution, dedup race), mas mesmo sem isso o código deveria ser idempotente.

## Fix (commit `41cc967`)
Early-return em 5 actions:

| Action | Check |
|---|---|
| `actionAssignToUser` | Skip se `lead.assigned_to == userId` |
| `actionSetLeadSource` | Skip se `lead.source == config.source` |
| `actionMoveToStage` | Skip se `lead.stage_id == stage.id` (previne LeadEvent + StageRequiredTasks duplicados) |
| `actionAssignAiAgent` | Skip se `conv.ai_agent_id == agentId` E `conv.chatbot_flow_id == null` |
| `actionAssignChatbotFlow` | Skip se `conv.chatbot_flow_id == flowId` E `conv.ai_agent_id == null` |

## NÃO mexido (deliberadamente)
- `add_note` — cria nota nova a cada disparo (pode ser intencional)
- `send_whatsapp_message` — manda toda vez
- `create_task` — cria task a cada disparo
- `enroll_sequence` — comportamento existente
- `send_webhook` / `ai_extract_fields` — Jobs assíncronos (atomicidade externa)

Tornar essas idempotentes exigiria tabela `automation_executions` rastreando (automation_id, lead/conv_id, executed_at). Fora do escopo desse PR.

## Por que não foi pego antes
- Notificações repetidas são "ruído" mais que "erro" — fácil ignorar até começar a incomodar
- Sem teste E2E pra "executar automação 2x e verificar nenhum side-effect duplicado"
- A queixa "toda mensagem dispara nova conversa" levou tempo pra ser reformulada como "automation trigger é idempotente, action não é"

## Lição aprendida
- **State changes devem ser idempotentes por padrão** — comparar antes de UPDATE
- **Notificações são UX-visíveis** — qualquer notificação repetida é percebida pelo user mesmo que o DB esteja "consistente"
- **Quando o user descreve um bug "inexplicável", investigar TODOS os actions/triggers da feature**, não só o que parece direto

## Links
- Commit: `41cc967`
- Arquivo: [`app/Services/AutomationEngine.php`](app/Services/AutomationEngine.php)
- Documentado em: [[Automations]]

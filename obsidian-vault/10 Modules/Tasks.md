---
type: module
status: active
related: ["[[Task]]", "[[StageRequiredTask]]"]
files:
  - app/Models/Task.php
  - app/Services/TaskService.php
last_review: 2026-04-09
tags: [module, tasks]
---

# Tasks

## O que é
Sistema de tarefas vinculadas a leads/conversas. Tipos: call, email, task, visit, whatsapp, meeting. CRUD + toggle complete + filtros.

## Status
- ✅ CRUD com modal + filtros
- ✅ Tasks vinculadas a `Lead`, `WhatsappConversation`, `InstagramConversation`
- ✅ **Stage required tasks**: ao mover lead pra etapa X, sistema cria tasks obrigatórias automaticamente
- ✅ Pode ser criada via Sophia ou via action `create_task` de automação

## Models
- [[Task]] (subject, type, status, priority, due_date, due_time, assigned_to, created_by, stage_requirement_id)
- [[StageRequiredTask]] (template de task obrigatória por etapa)

## Service
- [[StageRequirementService]] — cria as tasks obrigatórias quando stage muda

## Notas
- `actionMoveToStage` chama `StageRequirementService::createRequiredTasks` — agora idempotente após commit `41cc967`

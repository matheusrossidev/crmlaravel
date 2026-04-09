---
type: module
status: active
related: ["[[NurtureSequence]]", "[[NurtureSequenceStep]]", "[[LeadSequence]]", "[[Automations]]"]
files:
  - app/Models/NurtureSequence.php
  - app/Services/NurtureService.php
  - app/Jobs/ProcessNurtureStep.php
last_review: 2026-04-09
tags: [module, sequences, nurture]
---

# Nurture Sequences

## O que é
Sequências automatizadas de mensagens/ações com delays configuráveis. Lead é "enrolled" e progride pelos steps via cron a cada 5 min.

## Status
- ✅ CRUD sequences + steps + enroll manual + via automação
- ✅ Exit conditions: reply do contato (`exit_on_reply`), mudança de etapa (`exit_on_stage_change`)
- ✅ Multi-canal: WhatsApp, Instagram, email
- ✅ Biblioteca de templates por nicho

## Models
- [[NurtureSequence]] (sequence config)
- [[NurtureSequenceStep]] (steps com `delay_minutes`, `type`, `config`)
- [[LeadSequence]] (enrollment ativo: `current_step_position`, `next_step_at`, `status`)

## Cron
`sequences:process` a cada 5 min — processa steps cujo `next_step_at <= now()`.

## Decisões / RCAs
- [[2026-04-09 CSS important matava filtro de templates]] (compartilha modal)

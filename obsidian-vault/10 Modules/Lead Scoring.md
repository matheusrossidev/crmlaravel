---
type: module
status: active
related: ["[[ScoringRule]]", "[[LeadScoreLog]]", "[[AutomationEngine]]"]
files:
  - app/Models/ScoringRule.php
  - app/Services/LeadScoringService.php
last_review: 2026-04-09
tags: [module, scoring]
---

# Lead Scoring

## O que é
Atribui pontos automaticamente a leads baseado em eventos (mensagem recebida, etapa avançada, perfil completo, etc). Pontuação total fica em `leads.score`. Tem cooldown por regra pra evitar spam de pontos.

## Status
- ✅ Regras configuráveis (event_type, conditions JSON, points, cooldown_hours)
- ✅ Decay diário (cron `scoring:decay` 02:00) — pontos diminuem em leads inativos
- ✅ Biblioteca de templates por nicho (~45 templates × 9 nichos)
- ✅ Avaliação dentro do `AutomationEngine::run()` — linhas 83-85

## Models
- [[ScoringRule]] (regras configuradas)
- [[LeadScoreLog]] (audit de cada pontuação atribuída)

## Eventos suportados
- `message_received`
- `message_sent_media`
- `stage_advanced` / `stage_regressed`
- `lead_created`
- `lead_won` / `lead_lost`
- `profile_completed`
- (custom — extensível)

## Templates
[[ScoringRuleTemplates]] em `app/Support/ScoringRuleTemplates.php`. 9 nichos × 5 regras = 45 templates. Configurável via `/configuracoes/scoring` → "Modelos".

## Decisões / RCAs
- [[2026-04-09 CSS important matava filtro de templates]] (bug do filtro de nicho na biblioteca)
- [[ADR — Templates de scoring por nicho]]

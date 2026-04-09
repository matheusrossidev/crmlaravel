---
type: bug
status: resolved
date: 2026-04-09
severity: low
modules: ["[[Leads & CRM]]"]
files:
  - app/Http/Controllers/Tenant/LeadController.php
  - app/Http/Controllers/Tenant/LeadMergeController.php
  - app/Http/Controllers/Tenant/KanbanController.php
  - app/Http/Requests/LeadRequest.php
  - app/Models/Lead.php
  - app/Exports/LeadsExport.php
  - app/Providers/AppServiceProvider.php
  - resources/views/tenant/leads/_drawer.blade.php
  - resources/views/tenant/leads/show.blade.php
  - database/migrations/2026_04_08_230000_drop_converted_at_from_leads_table.php
commits: ["9624215"]
related: ["[[Leads & CRM]]"]
tags: [refactor, clean-code, leads]
---

# 2026-04-09 — Auditoria clean code Leads (Onda 1 + Onda 2)

## Contexto
Auditoria pediu pelo user pra começar uma série de cleanups por módulo. Objetivo: arquitetura mais limpa, dead code removido, redundância eliminada. Onda 1 = quick wins (baixo risco). Onda 2 = refactors pequenos (risco baixo). Onda 3 (god partials) ficou pra outro PR.

## O que mudou (Onda 1 + Onda 2 — commit `9624215`)

### Onda 1 — Quick wins
1. **Drop coluna `leads.converted_at`** + index — coluna criada em fev/26 mas zero usos no codebase. Migration reversa criada.
2. **Query inline `Product` no `_drawer`** — antes executava em toda inclusão (index/show/kanban). Movido pro `View::composer` em `AppServiceProvider`.
3. **4 catch blocks vazios** em `LeadController` agora logam o erro: `AutomationEngine`, `NotificationDispatcher` (×2), `StageRequirementService`.
4. **`formatLead()`** lê tags via `$lead->tag_names` (accessor do trait `HasTags`) em vez da coluna JSON legada — alinha com Fase 4 do refactor de tags.
5. **DI de `DuplicateLeadDetector`** — `app(DuplicateLeadDetector::class)` em vez de `new`.

### Onda 2 — Refactors pequenos
6. **Novo `LeadRequest`** (FormRequest único) substitui validação duplicada em `store()` e `update()`. Usa `isMethod('POST')` pra decidir entre `required` vs `sometimes|required` (preserva semântica de update parcial).
7. **Novo `Lead::scopeFilterByTag()`** centraliza filtro de tag em 1 lugar. 3 callers (`LeadController::index`, `KanbanController`, `LeadsExport`) usam o scope agora — quando Fase 4 trocar JSON pela pivot, é 1 linha mudada no scope em vez de 3.
8. **CSS Quill duplicado** entre `show.blade.php` e `_drawer.blade.php` unificado num bloco compartilhado dentro do `_drawer` (que é incluído em ambos).

## Onda 3 (NÃO incluída — pra PR futuro)
- Quebrar `_drawer.blade.php` (1915 linhas) em partials por seção (form/products/scheduled/tags)
- Quebrar `show.blade.php` (3132 linhas) em partials por tab
- Inline styles repetidos (38 instâncias de `display:flex;align-items:center;...`)

Risco alto de regressão visual — requer testing manual em todas as páginas que incluem o drawer. Não fazer junto com outras coisas.

## Verificação pós-aplicação
- ✅ Lint clean nos 10 arquivos modificados
- ✅ `php artisan migrate` aplica drop sem erro
- ⏳ Smoke test manual de CRUD de lead (pendente do user)

## Links
- Commit: `9624215`
- Plano original: `~/.claude/plans/eager-seeking-corbato.md` (sobrescrito por planos posteriores)
- Documentado em: [[Leads & CRM]]

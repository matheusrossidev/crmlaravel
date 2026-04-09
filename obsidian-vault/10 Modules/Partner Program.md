---
type: module
status: active
related: ["[[PartnerCommission]]", "[[PartnerWithdrawal]]", "[[PartnerRank]]", "[[Asaas]]"]
files:
  - app/Services/PartnerService.php
last_review: 2026-04-09
tags: [module, partners, commissions]
---

# Partner Program

## O que é
Programa de parceiros (agências) com comissões recorrentes sobre clientes referenciados, ranks gamificados, recursos de marketing e cursos com certificados.

## Status
- ✅ Comissões automáticas via Asaas (carência configurável)
- ✅ Saques via Asaas Transfer (PIX) — ativação manual no Asaas
- ✅ Ranks (Bronze/Silver/Gold/etc) baseados em min_sales
- ✅ Cursos + lições + tracking de progresso + certificados PDF
- ✅ Materiais de apoio (recursos)

## Models
- [[PartnerAgencyCode]] (cupom de referência)
- [[PartnerRank]] (níveis com `min_sales`, `commission_pct`, cor)
- [[PartnerCommission]] (comissão por cliente, status, available_at)
- [[PartnerWithdrawal]] (saque via Asaas Transfer)
- [[PartnerResource]] (materiais)
- [[PartnerCourse]] / [[PartnerLesson]] / [[PartnerLessonProgress]]
- [[PartnerCertificate]] (PDF emitido após completar curso)

## Cron
`partners:release-commissions` diário 06:00 — libera comissões após período de carência.

## Decisões / Notas
- Asaas Transfer requer ativação manual de transferências PIX (ver [[Asaas Transfer Setup]])

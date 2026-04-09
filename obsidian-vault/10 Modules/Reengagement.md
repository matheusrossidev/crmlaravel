---
type: module
status: active
related: ["[[ReengagementTemplate]]", "[[User]]"]
files:
  - app/Models/ReengagementTemplate.php
  - app/Mail/ReengagementEmail.php
  - app/Console/Commands/SendReengagement.php
  - app/Http/Controllers/Master/ReengagementController.php
last_review: 2026-04-09
tags: [module, reengagement, email]
---

# Reengagement

## O que é
Emails (e/ou WhatsApp) automáticos pra usuários que não fazem login há X dias, separados em estágios **7d / 14d / 30d**. Templates configurados no painel master.

## Componentes
- [[ReengagementTemplate]] — `stage`, `channel`, `subject`, `body` com `{{vars}}`, `locale`, `is_active`
- `app/Mail/ReengagementEmail.php` + view `emails/reengagement.blade.php` (estende `_layout` shared bilingual)
- `app/Console/Commands/SendReengagement.php` — escaneia users com `last_login_at < threshold`
- `/master/reengajamento` — CRUD templates + preview + sendTest

## Campos novos em `users`
- `phone` — pra envio via WhatsApp se preferir
- `last_reengagement_sent_at` — evita reenvio no mesmo período
- `reengagement_stage` — qual estágio o user está

## Variáveis disponíveis no template
`{{name}}`, `{{email}}`, `{{tenant_name}}`, `{{days_inactive}}`, `{{login_url}}`

## Locale
Templates têm coluna `locale` (`pt_BR` ou `en`) — `SendReengagement` escolhe baseado em `users.locale`.

## Cron
`users:send-reengagement` diário 10:00.

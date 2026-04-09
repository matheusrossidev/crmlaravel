---
auto_generated: true
type: model
class: App\Models\User
table: users
file: app/Models/User.php
tags: [model, auto]
---

# User

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/User.php`

## Tabela
`users`

## Traits
- `HasFactory`
- `Notifiable`
- `HasPushSubscriptions`
- `LogsActivity`

## Fillable
- `tenant_id`
- `name`
- `email`
- `phone`
- `password`
- `role`
- `is_super_admin`
- `is_cs_agent`
- `master_permissions`
- `avatar`
- `last_login_at`
- `dashboard_config`
- `notification_preferences`
- `email_verified_at`
- `verification_token`
- `can_see_all_conversations`
- `totp_secret`
- `totp_enabled`
- `totp_backup_codes`
- `last_reengagement_sent_at`
- `reengagement_stage`

## Relações
- `tenant()` — BelongsTo
- `leads()` — HasMany
- `departments()` — BelongsToMany
- `pipelines()` — BelongsToMany
- `whatsappInstances()` — BelongsToMany
- `pushSubscriptions()` — MorphMany

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[User]]`

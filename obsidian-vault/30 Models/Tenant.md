---
auto_generated: true
type: model
class: App\Models\Tenant
table: tenants
file: app/Models/Tenant.php
tags: [model, auto]
---

# Tenant

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/Tenant.php`

## Tabela
`tenants`

## Fillable
- `name`
- `slug`
- `phone`
- `cnpj`
- `website`
- `city`
- `state`
- `segment`
- `logo`
- `plan`
- `status`
- `trial_ends_at`
- `settings_json`
- `max_users`
- `max_leads`
- `max_pipelines`
- `max_custom_fields`
- `max_chatbot_flows`
- `max_ai_agents`
- `max_departments`
- `max_whatsapp_instances`
- `api_rate_limit`
- `asaas_customer_id`
- `asaas_subscription_id`
- `subscription_status`
- `subscription_ends_at`
- `onboarding_completed_at`
- `ai_tokens_exhausted`
- `referred_by_agency_id`
- `partner_commission_pct`
- `partner_billing_starts_at`
- `locale`
- `billing_provider`
- `billing_country`
- `billing_currency`
- `stripe_customer_id`
- `stripe_subscription_id`

## Casts
| Coluna | Cast |
|---|---|
| `settings_json` | `array` |
| `trial_ends_at` | `datetime` |
| `subscription_ends_at` | `datetime` |
| `onboarding_completed_at` | `datetime` |
| `partner_billing_starts_at` | `datetime` |
| `max_users` | `integer` |
| `max_leads` | `integer` |
| `max_pipelines` | `integer` |
| `max_custom_fields` | `integer` |
| `max_chatbot_flows` | `integer` |
| `max_ai_agents` | `integer` |
| `max_departments` | `integer` |
| `max_whatsapp_instances` | `integer` |
| `api_rate_limit` | `integer` |
| `ai_tokens_exhausted` | `boolean` |
| `referred_by_agency_id` | `integer` |

## Relações
- `users()` — HasMany
- `pipelines()` — HasMany
- `leads()` — HasMany
- `apiKeys()` — HasMany
- `partnerAgencyCode()` — HasOne
- `referringAgency()` — BelongsTo
- `referredClients()` — HasMany

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[Tenant]]`

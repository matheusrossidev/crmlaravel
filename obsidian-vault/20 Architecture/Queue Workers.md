---
type: architecture
status: active
related: ["[[Webhook Pipeline]]", "[[AI Agents]]"]
files:
  - bootstrap/app.php
last_review: 2026-04-09
tags: [architecture, queue, redis]
---

# Queue Workers

## Driver
**Redis** (via Predis). Mesmo Redis usado pra cache + session.

## Filas
| Fila | Workers | Função |
|---|---|---|
| `ai` | 1 | `ProcessAiResponse` (LLM calls — pesado, isolado) |
| `whatsapp` | 1 | `ProcessWahaWebhook`, `ProcessWhatsappCloudWebhook`, `ImportWhatsappHistory` |
| `default` | 1 | Tudo o resto (notifications, mail, scoring) |

## Worker em prod
Stack Docker Swarm (Portainer):
```yaml
queue:
  command: php artisan queue:work --queue=ai,whatsapp,default --tries=3 --max-time=600
```

## Padrões críticos
- **`dispatchSync`** em webhooks (não `dispatch`) — minimiza latency, evita queue lag
- **`tries = 3`** padrão — falhas vão pra `failed_jobs` table
- **`max-time = 600`** — worker reinicia a cada 10min pra liberar memória PHP
- **`unique` jobs** quando aplicável (ex: `ProcessAiResponse` por conversation_id)

## Scheduled tasks ([[Cron Schedule]])
Schedule definido em `bootstrap/app.php` + `routes/console.php`. Service `scheduler` roda `php artisan schedule:run` a cada 60s.

Ver lista completa em [[Cron Schedule]].

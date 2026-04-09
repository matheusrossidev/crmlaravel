---
type: ops
status: active
related: ["[[Real-time (Reverb)]]", "[[Comandos VPS]]"]
files:
  - Dockerfile
  - portainer-stack.yml
  - .github/workflows
last_review: 2026-04-09
tags: [ops, deploy, ci-cd, docker]
---

# Deploy & CI/CD

## Stack Docker Swarm (prod)
| Service | Replicas | Função |
|---|---|---|
| `nginx` | 1 | Reverse proxy + static files + WebSocket proxy |
| `app` | 1 | PHP-FPM (web requests) |
| `queue` | 1 | Worker `--queue=ai,whatsapp,default` |
| `scheduler` | 1 | `php artisan schedule:run` a cada 60s |
| `reverb` | 1 | WebSocket server (porta interna 8080) |
| `mysql` | 1 | MySQL 8.0 |
| `redis` | 1 | Cache + Queue + Session |
| `pgvector` | 1 | PostgreSQL + pgvector (memória IA) |
| `agno` | 1 | Python FastAPI (microsserviço IA) |

Network overlay: `crm_private`.
Domínio: `app.syncro.chat` (Traefik SSL automático).

## CI/CD
1. Push pra `main` no GitHub
2. GitHub Actions builda imagem Docker
3. Tagueia `matolado/crm:latest` + `matolado/crm:{commit_sha}`
4. Push pro Docker Hub
5. Portainer (configurado) puxa imagem nova e redeploya `app`/`queue`/`scheduler`/`reverb`

## Dockerfile pipeline
```
Node 20 (build assets via Vite)
  → PHP 8.3-FPM
    → Composer install
      → entrypoint.sh (migrate + cache + supervisor)
```

## ⚠️ Gotcha crítico: VITE_*
`npm run build` roda **sem build args**. Vars `VITE_*` do Portainer são RUNTIME only.

**NUNCA** usar `import.meta.env.VITE_*` em JS bundlado pelo Vite — vai dar `undefined` em prod.

**Padrão correto**: injetar config no servidor via Blade (`window.reverbConfig`). Ver [[Real-time (Reverb)]].

## Env vars críticas no Portainer
| Var | Notas |
|---|---|
| `APP_KEY` | Laravel encryption key |
| `DB_PASSWORD` | MySQL root password |
| `REDIS_PASSWORD` | Redis auth |
| `WAHA_BASE_URL` / `WAHA_API_KEY` / `WAHA_WEBHOOK_SECRET` | WAHA |
| `WHATSAPP_CLOUD_*` | Meta WhatsApp Cloud (ver [[WhatsApp Cloud API]]) |
| `INSTAGRAM_*` | Meta Instagram |
| `FACEBOOK_*` | Meta Facebook Lead Ads |
| `ASAAS_API_URL` / `ASAAS_API_KEY` / `ASAAS_WEBHOOK_TOKEN` | Asaas |
| `STRIPE_*` | Stripe |
| `GOOGLE_*` | OAuth Google Calendar |
| `OPENAI_API_KEY` / `ANTHROPIC_API_KEY` / `GEMINI_API_KEY` | LLM providers |
| `LLM_API_KEY` | Pra Agno (definir no Portainer UI) |

Ao adicionar nova integração: **sempre acrescentar env var no `portainer-stack.yml`** (e UI do Portainer).

## Rollback
- Tags imutáveis no Docker Hub: `matolado/crm:{commit_sha}`
- Pra rollback: editar stack no Portainer apontando pro SHA anterior + redeploy

## Health checks
- `app` health: `GET /up` (Laravel default)
- `reverb` health: TCP check porta 8080
- `mysql`/`redis`/`pgvector`: `pg_isready` / `mysqladmin ping` / `redis-cli ping`

## Decisões
- [[ADR — Docker Swarm em vez de Kubernetes]]
- [[ADR — Reverb em vez de Pusher]]

---
type: ops
status: active
related: ["[[Real-time (Reverb)]]", "[[Comandos VPS]]"]
files:
  - Dockerfile
  - portainer-stack.yml
  - .github/workflows
last_review: 2026-04-17
tags: [ops, deploy, ci-cd, docker]
---

# Deploy & CI/CD

## Stack Docker Swarm (prod) — `portainer-stack.yml`
| Service | Image | Replicas | Função |
|---|---|---|---|
| `nginx` | `matolado/crm-nginx:{commit_sha}` | 1 | Reverse proxy + static files + WebSocket proxy |
| `app` | `matolado/crm:{commit_sha}` | 1 | PHP-FPM 8.3 (web requests) |
| `queue` | `matolado/crm:{commit_sha}` | 1 | Worker `--queue=ai,whatsapp,default --timeout=900 --memory=512` |
| `scheduler` | `matolado/crm:{commit_sha}` | 1 | `schedule:run` em loop `while true; sleep 60` (memory limit 128M) |
| `reverb` | `matolado/crm:{commit_sha}` | 1 | WebSocket server porta 8080 (memory limit 256M) |
| `mysql` | `mysql:8.0` | 1 | MySQL 8.0 (pinned `node.role=manager`) |
| `pgvector` | `pgvector/pgvector:pg16` | 1 | PostgreSQL 16 + pgvector (memória/RAG Agno, pinned manager) |
| `agno` | `matolado/agno-service:{commit_sha}` | 1 | Python FastAPI (memory 128M-512M) |
| `redis` | `redis:7-alpine` | 1 | Cache + Queue + Session (pinned manager) |

**Redes**:
- `crm_private` — overlay interno (todos os services)
- `serverossi` — overlay externo do Traefik (só `nginx` exposto via `Host(\`app.syncro.chat\`)` + letsencryptresolver)

**Volumes persistentes**: `mysql_data`, `redis_data`, `pgvector_data`, `storage_data`, `logs_data`, `cache_data`, `public_shared`.

**Domínio**: `https://app.syncro.chat` (Traefik + Let's Encrypt SSL).

> ⚠️ **WAHA roda FORA deste stack** — em `https://waha.matheusrossi.com.br` (stack Swarm separado). O CRM conecta via `WAHA_BASE_URL`.

## CI/CD
1. Push pra `main` no GitHub
2. GitHub Actions builda 3 imagens: `matolado/crm:{sha}`, `matolado/crm-nginx:{sha}`, `matolado/agno-service:{sha}`
3. Push pro Docker Hub (tags imutáveis por commit SHA — **sem `latest` em prod**)
4. Portainer puxa via "redeploy" apontando o SHA novo no `portainer-stack.yml`
5. Update manual (edit stack + update) — NÃO tem webhook auto-pull no Portainer hoje

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

## Env vars do `portainer-stack.yml` (fonte única de verdade)

### App / Infra
| Var | Notas |
|---|---|
| `APP_URL` / `ASSET_URL` | `https://app.syncro.chat` |
| `APP_KEY` | Laravel encryption key |
| `APP_ENV` / `APP_DEBUG` / `APP_TIMEZONE` | `production` / `false` / `America/Sao_Paulo` |
| `APP_LOCALE` / `APP_FALLBACK_LOCALE` | `pt_BR` |
| `LOG_CHANNEL` / `LOG_LEVEL` | `stack` / `warning` |
| `DB_*` | `mysql` / `3306` / `plataforma360` / `crm` / senha |
| `REDIS_*` | `redis` / `6379` (sem senha) |
| `QUEUE_CONNECTION` / `CACHE_STORE` / `SESSION_DRIVER` | `redis` / `redis` / `redis` |
| `BROADCAST_CONNECTION` | `reverb` |
| `TRUSTED_PROXIES` | `10.0.0.0/8,172.16.0.0/12` (Traefik overlay) |

### Reverb (WebSocket)
| Var | Notas |
|---|---|
| `REVERB_APP_ID` / `REVERB_APP_KEY` / `REVERB_APP_SECRET` | Auth do Reverb |
| `REVERB_HOST` / `REVERB_PORT` / `REVERB_SCHEME` | `app.syncro.chat` / `443` / `https` (público, browser→nginx→reverb) |
| `REVERB_SERVER_HOST` / `REVERB_SERVER_PORT` | `reverb` / `8080` (interno Docker) |
| `REVERB_ALLOWED_ORIGINS` | `https://app.syncro.chat` |
| `VITE_REVERB_*` | Usadas no blade (não no build — ver gotcha acima) |

### Mail (Resend, não SMTP)
| Var | Notas |
|---|---|
| `MAIL_MAILER` | `resend` |
| `MAIL_FROM_ADDRESS` / `MAIL_FROM_NAME` | `noreply@syncro.chat` / `Syncro` |
| `RESEND_API_KEY` | Chave do Resend |

### LLM / Agno
| Var | Notas |
|---|---|
| `LLM_PROVIDER` / `LLM_MODEL` | `openai` / `gpt-4o-mini` |
| `LLM_API_KEY` | Key OpenAI (mesmo valor usado no service `agno`) |
| `AGNO_SERVICE_URL` | `http://agno:8000` (rede `crm_private`) |
| `AGNO_ENABLED` | `true` |
| `AGNO_INTERNAL_TOKEN` | Token shared Laravel↔Agno (DEVE casar com `LARAVEL_INTERNAL_TOKEN` do service `agno`) |
| `PGVECTOR_URL` (no agno) | `postgresql://agno:...@pgvector:5432/agno` |

### WhatsApp
| Var | Notas |
|---|---|
| `WAHA_BASE_URL` | **`https://waha.matheusrossi.com.br`** (stack Swarm separado) |
| `WAHA_API_KEY` / `WAHA_WEBHOOK_SECRET` | Auth + HMAC webhook |
| `WHATSAPP_CLOUD_APP_ID` / `WHATSAPP_CLOUD_APP_SECRET` | Meta App (mesmo App ID do Facebook) |
| `WHATSAPP_CLOUD_CONFIG_ID` | Embedded Signup Coexistence config |
| `WHATSAPP_CLOUD_VERIFY_TOKEN` / `WHATSAPP_CLOUD_API_VERSION` | Webhook + `v22.0` |
| `WHATSAPP_CLOUD_REDIRECT` | Callback OAuth fallback |
| `WHATSAPP_CLOUD_SYSTEM_USER_TOKEN` | Token System User permanente (Graph API) |
| `WHATSAPP_CLOUD_SYNCRO_BUSINESS_ID` | Business ID da Syncro pra Coexistence |

### Instagram / Facebook / Lead Ads
| Var | Notas |
|---|---|
| `INSTAGRAM_CLIENT_ID` / `INSTAGRAM_CLIENT_SECRET` | OAuth Instagram |
| `INSTAGRAM_REDIRECT_URI` / `INSTAGRAM_WEBHOOK_VERIFY_TOKEN` | Callback + webhook |
| `FACEBOOK_CLIENT_ID` / `FACEBOOK_CLIENT_SECRET` | Mesmo App do WhatsApp Cloud |
| `FACEBOOK_REDIRECT_URI` / `FACEBOOK_LEADGEN_REDIRECT_URI` | Callbacks |
| `FACEBOOK_LEADGEN_WEBHOOK_VERIFY_TOKEN` | Webhook leadgen |
| `FACEBOOK_API_VERSION` | `v21.0` (diferente do WhatsApp Cloud que é v22.0) |

### Pagamentos
| Var | Notas |
|---|---|
| `STRIPE_KEY` / `STRIPE_SECRET` / `STRIPE_WEBHOOK_SECRET` | Stripe prod (principal) |
| `ASAAS_API_URL` / `ASAAS_API_KEY` / `ASAAS_WEBHOOK_TOKEN` | Asaas prod (legacy + tokens + partner PIX) |

### Google / ElevenLabs / Extras
| Var | Notas |
|---|---|
| `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` / `GOOGLE_REDIRECT_URI` | OAuth Google Calendar |
| `GOOGLE_DEVELOPER_TOKEN` / `GOOGLE_ADS_API_VERSION` | Vazio + `v16` (legacy, não usado) |
| `ELEVENLABS_API_KEY` / `ELEVENLABS_VOICE_ID` / `ELEVENLABS_MODEL_ID` | TTS (`eleven_multilingual_v2`) |
| `VAPID_PUBLIC_KEY` / `VAPID_PRIVATE_KEY` / `VAPID_SUBJECT` | Web Push (browser notifications) |
| `SENTRY_LARAVEL_DSN` / `SENTRY_ENVIRONMENT` | Error tracking + APM |
| `SENTRY_TRACES_SAMPLE_RATE` / `SENTRY_PROFILES_SAMPLE_RATE` | `0.1` (10% sample) |
| `SENTRY_SEND_DEFAULT_PII` | `false` |

Ao adicionar nova integração: **sempre acrescentar env var no `portainer-stack.yml`** (é o fonte único — não tem `.env.example` tracked no git).

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

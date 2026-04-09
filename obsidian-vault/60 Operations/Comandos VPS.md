---
type: ops
status: active
related: ["[[Deploy & CI-CD]]"]
tags: [ops, vps, commands]
---

# Comandos VPS

> Comandos úteis pra rodar via SSH na VPS de produção (`syncroserver`).

## Logs em tempo real
```bash
# nginx
docker service logs syncro_nginx --tail 50 -f

# app (PHP-FPM)
docker service logs syncro_app --tail 100 -f

# queue worker
docker service logs syncro_queue --tail 100 -f

# scheduler (cron Laravel)
docker service logs syncro_scheduler --tail 50 -f

# reverb (WebSocket)
docker service logs syncro_reverb --tail 50 -f
```

## Logs arquivos dentro do container
```bash
# WhatsApp (WAHA + Cloud API + automation)
docker exec $(docker ps -q -f name=syncro_app) tail -f /var/www/storage/logs/whatsapp-$(date +%Y-%m-%d).log

# Instagram
docker exec $(docker ps -q -f name=syncro_app) tail -f /var/www/storage/logs/instagram-$(date +%Y-%m-%d).log

# Laravel geral
docker exec $(docker ps -q -f name=syncro_app) tail -f /var/www/storage/logs/laravel.log
```

## Artisan
```bash
# Tinker (REPL)
docker exec -it $(docker ps -q -f name=syncro_app) php artisan tinker

# Tinker com script inline
docker exec -i $(docker ps -q -f name=syncro_app) php artisan tinker --execute='
echo \App\Models\Tenant::count();
'

# Commands de manutenção
docker exec -i $(docker ps -q -f name=syncro_app) php artisan instagram:repair-instances --dry-run
docker exec -i $(docker ps -q -f name=syncro_app) php artisan instagram:repair-contacts
docker exec -i $(docker ps -q -f name=syncro_app) php artisan whatsapp:repair-pictures
docker exec -i $(docker ps -q -f name=syncro_app) php artisan tags:backfill --dry-run
```

## Banco
```bash
# Acessa MySQL CLI
docker exec -it syncro_mysql mysql -uroot -p

# Backup rapido
docker exec syncro_mysql mysqldump -uroot -p"$DB_PASSWORD" syncro > backup-$(date +%Y%m%d).sql
```

## Cache & queue
```bash
# Limpar cache
docker exec $(docker ps -q -f name=syncro_app) php artisan cache:clear
docker exec $(docker ps -q -f name=syncro_app) php artisan view:clear
docker exec $(docker ps -q -f name=syncro_app) php artisan config:clear

# Restart queue worker (pra carregar codigo novo)
docker exec $(docker ps -q -f name=syncro_app) php artisan queue:restart

# Failed jobs
docker exec $(docker ps -q -f name=syncro_app) php artisan queue:failed
docker exec $(docker ps -q -f name=syncro_app) php artisan queue:retry all
```

## Health checks
```bash
# Status dos services Swarm
docker service ls | grep syncro

# Restart hard de um service
docker service update --force syncro_app

# Inspecionar config de um service
docker service inspect syncro_app --pretty
```

## Notas
- **Sempre prefixe com `docker exec`** — não rode `php artisan` direto na VPS
- **Use `-i` (não `-it`)** quando rodar via heredoc ou pipe (sem TTY)
- **`tinker --execute`** permite rodar script PHP inline sem abrir REPL interativo

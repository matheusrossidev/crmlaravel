#!/bin/bash
set -e

echo "=============================="
echo "  CRM — Starting up"
echo "=============================="

# Detectar se este container é o app principal (php-fpm) ou um worker
CMD_ARG="${1:-}"
IS_APP=false
if echo "${CMD_ARG}" | grep -q "php-fpm\|php artisan serve"; then
    IS_APP=true
fi

# ── Sync public/ apenas no container app ─────────────────────────────────────
if [ "${IS_APP}" = "true" ] && [ -d "/var/www-public" ]; then
    echo "[entrypoint] Syncing public/ to shared volume..."
    cp -rn /var/www/public/. /var/www-public/ 2>/dev/null || true
fi

# ── Aguardar MySQL (app e queue — reverb não precisa) ─────────────────────────
if [ "${IS_APP}" = "true" ] || echo "${CMD_ARG}" | grep -q "queue:work"; then
    echo "[entrypoint] Waiting for MySQL..."
    until php -r "
        \$pdo = new PDO(
            'mysql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT'),
            getenv('DB_USERNAME'),
            getenv('DB_PASSWORD')
        );
        echo 'ok';
    " 2>/dev/null; do
        echo "[entrypoint] MySQL not ready, retrying in 3s..."
        sleep 3
    done
    echo "[entrypoint] MySQL ready."
fi

# ── Aguardar Redis (todos os containers usam Redis) ────────────────────────────
echo "[entrypoint] Waiting for Redis..."
until php -r "
    \$r = new Redis();
    \$r->connect(getenv('REDIS_HOST'), (int)getenv('REDIS_PORT', '6379'));
    echo 'ok';
" 2>/dev/null; do
    echo "[entrypoint] Redis not ready, retrying in 2s..."
    sleep 2
done
echo "[entrypoint] Redis ready."

# ── Redescobrir pacotes (garante que bootstrap/cache/packages.php tem Reverb) ──
# Força remoção do cache antigo ANTES de redescobrir — sem isso, o artisan boota
# usando o packages.php obsoleto do volume e ignora o laravel/reverb instalado.
rm -f /var/www/bootstrap/cache/packages.php
php artisan package:discover --ansi 2>/dev/null || true

# ── Migrations + Seed + Cache (apenas container app) ─────────────────────────
if [ "${IS_APP}" = "true" ]; then
    echo "[entrypoint] Running migrations..."
    php artisan migrate --force --no-interaction 2>&1 && echo "[entrypoint] Migrations OK." || {
        echo "[entrypoint] WARNING: migration reported errors (normal em re-deploy)."
        php artisan migrate:status --no-interaction 2>/dev/null || true
        echo "[entrypoint] Continuing startup..."
    }

    USER_COUNT=$(php artisan tinker --no-interaction --execute="echo \App\Models\User::count();" 2>/dev/null | tail -1)
    if [ "${USER_COUNT}" = "0" ] || [ -z "${USER_COUNT}" ]; then
        echo "[entrypoint] No users found — running seeders..."
        php artisan db:seed --force --no-interaction 2>&1 && echo "[entrypoint] Seeding OK." || \
            echo "[entrypoint] WARNING: seeding failed (ignorando)."
    else
        echo "[entrypoint] Users already exist (${USER_COUNT}) — skipping seed."
    fi

    echo "[entrypoint] Caching config/routes/views..."
    php artisan config:cache  2>/dev/null || true
    php artisan route:cache   2>/dev/null || true
    php artisan view:cache    2>/dev/null || true
    php artisan storage:link --force 2>/dev/null || true
fi

echo "[entrypoint] Setup complete. Starting: $@"
exec "$@"

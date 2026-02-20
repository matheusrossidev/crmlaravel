#!/bin/bash
set -e

echo "=============================="
echo "  CRM — Starting up"
echo "=============================="

# Copiar public/ para volume compartilhado (nginx serve os assets)
if [ -d "/var/www-public" ]; then
    echo "[entrypoint] Syncing public/ to shared volume..."
    cp -rn /var/www/public/. /var/www-public/ 2>/dev/null || true
fi

# Aguardar MySQL
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

# Aguardar Redis
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

# Migrations — não falha se tabelas já existirem (re-deploy seguro)
echo "[entrypoint] Running migrations..."
php artisan migrate --force --no-interaction 2>&1 && echo "[entrypoint] Migrations OK." || {
    echo "[entrypoint] WARNING: migration reported errors (tabelas podem já existir — normal em re-deploy)."
    echo "[entrypoint] Checking migration status..."
    php artisan migrate:status --no-interaction 2>/dev/null || true
    echo "[entrypoint] Continuing startup..."
}

# Seed inicial — só roda se não existe nenhum usuário (seguro para re-deploy)
USER_COUNT=$(php artisan tinker --no-interaction --execute="echo \App\Models\User::count();" 2>/dev/null | tail -1)
if [ "${USER_COUNT}" = "0" ] || [ -z "${USER_COUNT}" ]; then
    echo "[entrypoint] No users found — running seeders..."
    php artisan db:seed --force --no-interaction 2>&1 && echo "[entrypoint] Seeding OK." || \
        echo "[entrypoint] WARNING: seeding failed (ignorando)."
else
    echo "[entrypoint] Users already exist (${USER_COUNT}) — skipping seed."
fi

# Cache de configurações
echo "[entrypoint] Caching config/routes/views..."
php artisan config:cache  2>/dev/null || true
php artisan route:cache   2>/dev/null || true
php artisan view:cache    2>/dev/null || true
php artisan storage:link --force 2>/dev/null || true

echo "[entrypoint] Setup complete. Starting: $@"
exec "$@"

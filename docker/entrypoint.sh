#!/bin/bash
set -e

echo "=============================="
echo "  CRM — Starting up"
echo "=============================="

# Copy public files to shared volume (for nginx to serve static assets)
if [ -d "/var/www-public" ]; then
    echo "[entrypoint] Syncing public/ to shared volume..."
    cp -rn /var/www/public/. /var/www-public/ 2>/dev/null || true
fi

# Wait for MySQL to be ready
echo "[entrypoint] Waiting for MySQL..."
until php -r "
    \$pdo = new PDO(
        'mysql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT'),
        getenv('DB_USERNAME'),
        getenv('DB_PASSWORD')
    );
    echo 'MySQL ready';
" 2>/dev/null; do
    echo "[entrypoint] MySQL not ready yet, retrying in 3s..."
    sleep 3
done

# Wait for Redis
echo "[entrypoint] Waiting for Redis..."
until php -r "
    \$redis = new Redis();
    \$redis->connect(getenv('REDIS_HOST'), (int)getenv('REDIS_PORT', 6379));
    echo 'Redis ready';
" 2>/dev/null; do
    echo "[entrypoint] Redis not ready yet, retrying in 2s..."
    sleep 2
done

# Laravel bootstrap
echo "[entrypoint] Running Laravel setup..."
php artisan migrate --force --no-interaction
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link --force 2>/dev/null || true

echo "[entrypoint] Setup complete. Starting: $@"
exec "$@"

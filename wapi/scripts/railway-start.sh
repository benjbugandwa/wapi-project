#!/usr/bin/env bash
set -euo pipefail

echo "==> [Railway Start] Preparing storage & cache directories..."
mkdir -p storage/framework/{sessions,views,cache} storage/logs bootstrap/cache

echo "==> [Railway Start] Running database migrations..."
php artisan migrate --force

echo "==> [Railway Start] Linking public storage..."
php artisan storage:link || true

echo "==> [Railway Start] Caching configuration, routes, events, and views..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan event:cache
php artisan view:cache

echo "==> [Railway Start] Starting web server on 0.0.0.0:${PORT:-8080}..."
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"

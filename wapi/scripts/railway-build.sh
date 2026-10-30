#!/usr/bin/env bash
set -euo pipefail

composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

npm ci
npm run build

php artisan package:discover --ansi

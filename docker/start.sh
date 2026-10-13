#!/bin/bash
set -euo pipefail
cd /var/www/html

# Keep Laravel's encryption key stable across deployments.
if [ -z "${APP_KEY:-}" ]; then
  echo "ERROR: APP_KEY is required. Configure a persistent value in your hosting dashboard." >&2
  exit 1
fi

mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
  mkdir -p database
  touch database/database.sqlite
fi

php artisan config:clear
php artisan package:discover --ansi
# Safe upgrade: never destroy existing users, products or orders.
php artisan migrate --force --no-interaction

if [ "${MARKETHUB_SEED_DEMO:-false}" = "true" ]; then
  if [ "${DB_CONNECTION:-sqlite}" != "sqlite" ]; then
    echo "ERROR: Demo accounts cannot be seeded on a persistent database." >&2
    exit 1
  fi
  # The existing demo seeder also inserts categories, so run it first.
  php artisan markethub:seed-demo --no-interaction
fi
php artisan markethub:seed-categories --no-interaction

if [ -n "${MARKETHUB_ADMIN_EMAIL:-}" ] || [ -n "${MARKETHUB_ADMIN_PASSWORD:-}" ]; then
  php artisan markethub:bootstrap-admin --no-interaction
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache
chown -R www-data:www-data storage bootstrap/cache
if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
  chown -R www-data:www-data database
fi

# For production load, move this worker into a separate service.
(while true; do php artisan queue:work --tries=3 --sleep=3 --max-time=3000 || true; sleep 2; done) &

PORT="${PORT:-10000}"
sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

exec apache2-foreground

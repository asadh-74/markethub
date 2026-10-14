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

# A PostgreSQL deployment must use a deliberately private schema.
# Supabase public schema is exposed through the Data API, which is not
# appropriate for Laravel's users, sessions and order/payment metadata.
if [ "${DB_CONNECTION:-sqlite}" = "pgsql" ]; then
  if [ -z "${DB_SEARCH_PATH:-}" ] || [ "${DB_SEARCH_PATH}" = "public" ]; then
    echo "ERROR: Set DB_SEARCH_PATH to a private PostgreSQL schema (e.g. markethub) first." >&2
    exit 1
  fi
fi

# Aiven's private CA is provided only through a hosting secret. Never save
# certificates or DB passwords in source control.
if [ "${DB_CONNECTION:-sqlite}" = "mysql" ]; then
  if [ -n "${MARKETHUB_MYSQL_CA_PEM:-}" ]; then
    install -d -m 700 storage/certs
    (umask 077; printf '%s\n' "${MARKETHUB_MYSQL_CA_PEM}" > storage/certs/aiven-ca.pem)
    export MYSQL_ATTR_SSL_CA="/var/www/html/storage/certs/aiven-ca.pem"
  fi
  if [ "${APP_ENV:-local}" = "production" ] && [ -z "${MYSQL_ATTR_SSL_CA:-}" ]; then
    echo "ERROR: Production MySQL requires MARKETHUB_MYSQL_CA_PEM (or MYSQL_ATTR_SSL_CA path) for verified TLS." >&2
    exit 1
  fi
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

# A freshly initialized MySQL marketplace has no products. Bootstrap a
# shared, public sample catalogue once, without creating known demo passwords.
# This skips existing products and never resets customer/order data.
if [ "${DB_CONNECTION:-sqlite}" = "mysql" ] && [ "${MARKETHUB_SEED_CATALOG:-true}" = "true" ]; then
  php artisan markethub:seed-shared-catalog --no-interaction
fi

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

#!/bin/sh
set -e

cd /app

DB_DATABASE="${DB_DATABASE:-/app/database-data/database.sqlite}"
export DB_DATABASE

# The image ships without a .env file, so it is created on the first run from the
# baked defaults. Anything set through env_file still wins over this file.
if [ ! -f .env ]; then
    cp /usr/local/etc/navspace/env.defaults .env
fi

# Laravel needs a key to encrypt sessions, cookies and the two factor secrets.
# It is kept next to the database so it survives recreating the container: a new
# key would log everyone out and leave the stored 2FA secrets unreadable.
KEY_FILE=/app/database-data/app_key

if [ -n "${APP_KEY}" ]; then
    : # Supplied through .env.docker, nothing to generate.
elif [ -f "${KEY_FILE}" ]; then
    APP_KEY=$(cat "${KEY_FILE}")
    export APP_KEY
else
    php artisan key:generate --force
    grep '^APP_KEY=' .env | cut -d '=' -f2- >"${KEY_FILE}"
fi

# The SQLite file lives on a volume so the data survives container restarts.
if [ ! -f "${DB_DATABASE}" ]; then
    touch "${DB_DATABASE}"
fi

# Exposes the uploaded models and images through public/storage.
php artisan storage:link --force

php artisan migrate --force

# Loads the DICI demo data the first time the database is created.
if [ "${DB_SEED}" = "true" ] && [ ! -f /app/database-data/.seeded ]; then
    php artisan db:seed --force
    touch /app/database-data/.seeded
fi

# Routes are not cached on purpose: the application declares closure based
# routes, which Laravel cannot serialize.
php artisan config:cache
php artisan view:cache

chown -R www-data:www-data storage bootstrap/cache "$(dirname "${DB_DATABASE}")"

exec "$@"

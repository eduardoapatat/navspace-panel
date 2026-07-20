#!/bin/sh
set -e

cd /app

DB_DATABASE="${DB_DATABASE:-/app/database-data/database.sqlite}"
export DB_DATABASE

# The container is configured entirely through environment variables, which
# Compose fills from the .env of the host, so it keeps no .env file of its own.
#
# APP_KEY is the exception: Laravel needs one to encrypt sessions, cookies and
# the two factor secrets, and it has to stay the same forever. Rotating it logs
# everyone out and leaves the stored 2FA secrets unreadable, so when the deploy
# does not provide one it is generated once and kept next to the database.
KEY_FILE=/app/database-data/app_key

if [ -z "${APP_KEY}" ]; then
    if [ ! -f "${KEY_FILE}" ]; then
        php artisan key:generate --show >"${KEY_FILE}"
    fi

    APP_KEY=$(cat "${KEY_FILE}")
    export APP_KEY
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

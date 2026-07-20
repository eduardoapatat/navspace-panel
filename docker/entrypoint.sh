#!/bin/sh
set -e

cd /app

DB_DATABASE="${DB_DATABASE:-/app/database-data/database.sqlite}"
export DB_DATABASE

# The image ships without a .env file, so it is created on the first run.
if [ ! -f .env ]; then
    cp .env.example .env
fi

# Generate the application key only when one has not been set yet.
if ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --force
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

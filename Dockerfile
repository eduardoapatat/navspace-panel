# syntax=docker/dockerfile:1

# -----------------------------------------------------------------------------
# Base image shared by the build and runtime stages.
#
# FrankenPHP bundles PHP 8.4 and a Caddy web server, so the panel is served from
# a single container without an extra Nginx service. The Debian variant is used
# on purpose: the frontend toolchain (lightningcss, tailwind oxide) ships glibc
# binaries that do not run on Alpine's musl libc.
# -----------------------------------------------------------------------------
FROM dunglas/frankenphp:php8.4 AS base

WORKDIR /app

# curl is kept in the final image because the Compose health check relies on it.
RUN apt-get update \
    && apt-get install -y --no-install-recommends curl ca-certificates \
    && rm -rf /var/lib/apt/lists/* \
    && install-php-extensions \
        pdo_sqlite \
        intl \
        zip \
        gd \
        exif \
        opcache \
        pcntl

COPY docker/php.ini /usr/local/etc/php/conf.d/navspace.ini

# -----------------------------------------------------------------------------
# Build stage.
#
# Installs the PHP and JavaScript dependencies and compiles the Vue assets. Node
# is required in this stage because the Vite build runs Wayfinder, which calls
# `php artisan` to generate the typed route helpers, so both runtimes must be
# available at the same time.
# -----------------------------------------------------------------------------
FROM base AS build

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && corepack enable \
    && rm -rf /var/lib/apt/lists/*

# Dependencies are copied before the source so Docker can reuse these layers
# while only the application code changes.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY package.json pnpm-lock.yaml pnpm-workspace.yaml .npmrc ./
RUN pnpm install --frozen-lockfile

COPY . .

# Wayfinder boots the framework during the asset build, so a valid application
# key must exist before Vite runs. Node modules are dropped afterwards to keep
# them out of the runtime image.
RUN cp .env.example .env \
    && composer dump-autoload --optimize \
    && php artisan key:generate \
    && pnpm run build \
    && rm -rf node_modules .env

# -----------------------------------------------------------------------------
# Runtime stage.
#
# Ships only the application and its compiled assets, without Node, Composer or
# the development dependencies.
# -----------------------------------------------------------------------------
FROM base AS runtime

COPY docker/Caddyfile /etc/frankenphp/Caddyfile

COPY --from=build /app /app

# `database-data` holds the SQLite file and is mounted as a volume so the data
# survives container restarts.
RUN mkdir -p /app/database-data \
    && chown -R www-data:www-data /app/storage /app/bootstrap/cache /app/database-data

COPY docker/env.defaults /usr/local/etc/navspace/env.defaults
COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

EXPOSE 80

ENTRYPOINT ["entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]

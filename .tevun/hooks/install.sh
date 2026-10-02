#!/usr/bin/env bash
set -euo pipefail
cd "$1"
export COMPOSE_PROJECT_NAME=phpcomrapadura
mkdir -p storage/app/public storage/app/private storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
docker compose up -d --wait mysql redis
docker compose run --rm --no-deps --user root app chown -R application:application storage bootstrap/cache
docker compose run --rm --no-deps app composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
docker compose run --rm --no-deps app php artisan migrate --force
docker compose run --rm --no-deps app php artisan optimize
docker compose run --rm --no-deps app php artisan storage:link
docker compose up -d --remove-orphans app

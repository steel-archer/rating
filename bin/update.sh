#!/bin/bash

set -e

git pull

# COMPOSE_FILE in .env (e.g. the Windows override) selects the compose files;
# passing -f here would silently drop it.
if grep -q '^COMPOSE_FILE=' .env 2>/dev/null; then
    docker compose up -d
else
    docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d
fi

docker compose exec app composer install
docker compose exec app npm install
docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
docker compose exec app php bin/console cache:pool:clear cache.app

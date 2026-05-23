#!/bin/bash

set -e

git pull

if git diff HEAD@{1} --name-only 2>/dev/null | grep -qE '^(docker/|docker-compose\.yml)'; then
    docker compose up -d --build
else
    docker compose up -d
fi

docker compose exec app composer install
docker compose exec app npm install
docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
docker compose exec app php bin/console cache:pool:clear cache.app

#!/bin/bash

set -e

git pull

docker compose up -d --build

docker compose exec app composer install
docker compose exec app npm install
docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
docker compose exec app php bin/console cache:pool:clear cache.app

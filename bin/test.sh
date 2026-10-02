#!/bin/bash

set -eo pipefail

# The host shell does not load .env, so take the password from the running
# app container (Compose injects it there from .env).
MYSQL_PASSWORD="$(docker compose exec -T app printenv MYSQL_PASSWORD | tr -d '\r')"
if [ -z "$MYSQL_PASSWORD" ]; then
    echo "MYSQL_PASSWORD is not set in the app container. Is the stack running?" >&2
    exit 1
fi

TEST_ENV=(-e APP_ENV=test -e "DATABASE_URL=mysql://rating_user:${MYSQL_PASSWORD}@db:3306/rating_test?charset=utf8mb4")

# Run migrations on test database
docker compose exec "${TEST_ENV[@]}" app php bin/console doctrine:migrations:migrate --no-interaction

# Run tests (all arguments are forwarded to PHPUnit)
docker compose exec -T "${TEST_ENV[@]}" app php bin/phpunit "$@"

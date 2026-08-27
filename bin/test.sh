#!/bin/bash

set -e

TEST_ENV="-e APP_ENV=test -e DATABASE_URL=mysql://rating_user:${MYSQL_PASSWORD:-rating_pass}@db:3306/rating_test?charset=utf8mb4"

# Run migrations on test database
docker compose exec $TEST_ENV app php bin/console doctrine:migrations:migrate --no-interaction

# Run tests (all arguments are forwarded to PHPUnit)
docker compose exec -T $TEST_ENV app php bin/phpunit "$@"

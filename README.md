# Rating

Rating site

## Setup

```bash
cp .env.dist .env.local   # fill in your values
ln -sf .env.local .env     # symlink for docker compose
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
docker compose exec -T db sh -c 'mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"' < docker/sql/seed.sql
```

App: http://localhost:8080
Mailpit: http://localhost:8025

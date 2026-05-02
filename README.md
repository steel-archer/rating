# Rating

Rating site

## Setup

```bash
cp .env.dist .env.local   # fill in your values
ln -sf .env.local .env     # symlink for docker compose
docker compose up -d --build
docker compose exec app composer install
```

App: http://localhost:8080
Mailpit: http://localhost:8025

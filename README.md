# Сайт рейтингу українського «Що? Де? Коли?»

[![CI](https://github.com/steel-archer/rating/actions/workflows/ci.yml/badge.svg?branch=master)](https://github.com/steel-archer/rating/actions/workflows/ci.yml)
![Coverage](https://raw.githubusercontent.com/steel-archer/rating/master/.github/badges/coverage.svg)

Вебсайт рейтингової системи інтелектуальних ігор: турніри, команди, гравці, майданчики.

## Що потрібно встановити

Перед початком переконайтесь, що на вашому комп'ютері встановлено:

1. **Git** — для завантаження коду проєкту
   - macOS: `brew install git` або завантажте з https://git-scm.com
   - Windows: завантажте з https://git-scm.com
   - Linux: `sudo apt install git` (Ubuntu/Debian) або `sudo dnf install git` (Fedora)

2. **Docker Desktop** — для запуску серверів (застосунок, база даних, пошта)
   - macOS: `brew install --cask docker` або завантажте з https://www.docker.com/products/docker-desktop
   - Windows: `winget install Docker.DockerDesktop` або завантажте з https://www.docker.com/products/docker-desktop
   - Linux: `sudo apt install docker.io docker-compose-v2` (Ubuntu/Debian) або `sudo dnf install docker docker-compose` (Fedora)
   - Після встановлення запустіть Docker Desktop і дочекайтесь, поки він повністю завантажиться

## Встановлення

### 1. Завантажте проєкт

Відкрийте термінал (Terminal на macOS/Linux, PowerShell на Windows) і виконайте:

```bash
git clone https://github.com/steel-archer/rating
cd rating
```

### 2. Налаштуйте змінні середовища

Скопіюйте файл з прикладом налаштувань і заповніть його:

```bash
cp .env.dist .env.local
```

Відкрийте файл `.env.local` у будь-якому текстовому редакторі та заповніть порожні значення:

```
APP_SECRET=будь-який-довгий-випадковий-рядок
MYSQL_ROOT_PASSWORD=ваш_пароль_root
MYSQL_DATABASE=rating
MYSQL_USER=rating_user
MYSQL_PASSWORD=ваш_пароль_користувача
```

Також оновіть рядок `DATABASE_URL`, підставивши ваші значення:

```
DATABASE_URL="mysql://rating_user:ваш_пароль_користувача@db:3306/rating?serverVersion=8.0&charset=utf8mb4"
```

Після цього створіть символічне посилання (потрібно для Docker Compose):

```bash
ln -sf .env.local .env
```

> **Windows:** замість `ln -sf` скопіюйте файл: `copy .env.local .env`

### 3. Запустіть проєкт

```bash
docker compose up -d --build
```

Перший запуск може зайняти кілька хвилин — Docker завантажує та збирає образи.

### 4. Встановіть залежності PHP

```bash
docker compose exec app composer install
```

### 5. Створіть таблиці в базі даних

```bash
docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
```

### 6. Завантажте початкові дані

```bash
docker compose exec -T db sh -c 'mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"' < docker/sql/seed.sql
```

### 7. (Опціонально) Завантажте тестові дані

Якщо хочете наповнити базу тестовими турнірами, командами та гравцями:

```bash
docker compose exec app php bin/console doctrine:fixtures:load --append --no-interaction
```

## Використання

Після успішного запуску відкрийте у браузері:

- **Сайт:** http://localhost:8080

## Зупинка та перезапуск

Зупинити всі сервіси:

```bash
docker compose down
```

Запустити знову (без перезбирання):

```bash
docker compose up -d
```

## Якість коду

Перевірка стилю коду (PSR-12):

```bash
docker compose exec app vendor/bin/phpcs
```

Автоматичне виправлення стилю:

```bash
docker compose exec app vendor/bin/phpcbf
```

Статичний аналіз (PHPStan, рівень 6):

```bash
docker compose exec app vendor/bin/phpstan analyse --memory-limit=512M
```

Тести з покриттям коду:

```bash
docker compose exec app php bin/phpunit --coverage-text
```

Перевірка безпеки залежностей:

```bash
docker compose exec app composer audit
docker compose exec app symfony security:check
```

## Вирішення проблем

**Docker не запускається:**
Переконайтесь, що Docker Desktop запущений і повністю завантажився.

**Помилка з базою даних:**
Перевірте, що значення в `.env.local` збігаються з `DATABASE_URL`.

**Порт 8080 зайнятий:**
Зупиніть інший сервіс на цьому порту або змініть порт у `docker-compose.yml`.

**Сторінка не завантажується після `docker compose up`:**
Зачекайте 10–15 секунд — серверу потрібен час на старт. Якщо не допомогло, перевірте логи:

```bash
docker compose logs app
```

# Сайт рейтингу українського «Що? Де? Коли?»

[![CI](https://github.com/steel-archer/rating/actions/workflows/ci.yml/badge.svg?branch=master)](https://github.com/steel-archer/rating/actions/workflows/ci.yml)
![Coverage](https://raw.githubusercontent.com/steel-archer/rating/badges/.github/badges/coverage.svg)
![PHPCS](https://raw.githubusercontent.com/steel-archer/rating/badges/.github/badges/phpcs.svg)
![PHPStan](https://raw.githubusercontent.com/steel-archer/rating/badges/.github/badges/phpstan.svg)
![ESLint](https://raw.githubusercontent.com/steel-archer/rating/badges/.github/badges/eslint.svg)
![Stylelint](https://raw.githubusercontent.com/steel-archer/rating/badges/.github/badges/stylelint.svg)
![TwigCS](https://raw.githubusercontent.com/steel-archer/rating/badges/.github/badges/twigcs.svg)

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

3. **Тільки для Windows: Bash** — скрипти `bin/*.sh` працюють лише в Bash. Підійде Git Bash (встановлюється разом із Git) або WSL2.
   - **Рекомендовано:** працювати у WSL2 (`wsl --install -d Ubuntu`) і клонувати проєкт у файлову систему Linux (наприклад, `~/rating`), а не на диск `C:`. Docker Desktop монтує файли з диска Windows дуже повільно: повний прогін тестів триває ~40 хв замість ~1 хв, сторінки в dev-режимі відкриваються по кілька секунд, а інколи трапляється випадкова помилка `Input/output error`.

## Встановлення

### 1. Завантажте проєкт

Відкрийте термінал (Terminal на macOS/Linux; на Windows — термінал WSL2 або Git Bash, бо далі використовуються Bash-скрипти) і виконайте:

```bash
git clone https://github.com/steel-archer/rating
cd rating
```

### 2. Налаштуйте змінні середовища

Скопіюйте файл з прикладом налаштувань:

```bash
cp .env.dist .env
```

Файл `.env.dist` вже містить робочі значення для локального середовища. Єдине, що потрібно заповнити — це Google OAuth (якщо потрібна автентифікація). Відкрийте `.env` і вкажіть:

```
GOOGLE_CLIENT_ID=отримайте_від_розробника
GOOGLE_CLIENT_SECRET=отримайте_від_розробника
```

> **Google OAuth:** значення `GOOGLE_CLIENT_ID` та `GOOGLE_CLIENT_SECRET` потрібно отримати від розробника проєкту; також попросіть його додати ваш Google-email до списку дозволених в OAuth-клієнті. Інший варіант — створити власний OAuth-клієнт у Google Cloud Console (Credentials → OAuth client ID → Web application) з дозволеним redirect URI `http://localhost:8080/connect/google/check`.

Без Google OAuth сайт запуститься, але відкриються лише головна сторінка, ліцензія та політика конфіденційності: решта сторінок потребує входу, а іншого способу увійти (пароль, dev-логін) немає.

> **Звідки беруться налаштування:** `.env` читає лише Docker Compose і передає значення в контейнер. `symfony/dotenv` не встановлено, тому `.env.local` **не** використовується. Після зміни `.env` повторіть команду з кроку 3 — контейнер застосунку буде перестворено з новими значеннями.

### 3. Запустіть проєкт

```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d
```

Перший запуск може зайняти кілька хвилин — Docker завантажує образи та створює контейнери. Образ застосунку `ghcr.io/steel-archer/php-dev:latest` публічний, входити в GHCR не потрібно.

### 4. Встановіть залежності PHP

```bash
docker compose exec app composer install
```

### 5. Встановіть залежності Node.js (для лінтерів)

```bash
docker compose exec app npm install
```

### 6. Створіть таблиці в базі даних

```bash
docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
```

### 7. (Опціонально) Завантажте тестові дані

Якщо хочете наповнити базу тестовими турнірами, командами та гравцями:

```bash
docker compose exec app php -d memory_limit=512M bin/console doctrine:fixtures:load --append --no-interaction
```

Фікстури створюють гравців, команди, майданчики, сезони й опубліковані турніри, але не користувачів: увійти все одно можна лише через Google. Запускайте команду один раз — повторний запуск додасть ще один набір даних.

### 8. Налаштуйте адміністратора

Адміністратори та модератори — це гравці з додатковими правами. Щоб створити першого адміна:

1. Напишіть розробнику і попросіть додати ваш Google-email до списку дозволених.
2. Відкрийте сайт (http://localhost:8080) і увійдіть через Google.
3. Подайте заявку на прив'язку до гравця (сайт запропонує це автоматично).
4. Затвердіть заявку та надайте права адміністратора (поміняйте в команді імейл на ваш):

```bash
docker compose exec app php bin/console app:promote-admin your-email@gmail.com
```

5. Перелогіньтеся на сайті (хоча, скоріше за все, вас вилогінить автоматично).

Команда спрацює лише для користувача, який уже входив через Google і має заявку на прив'язку (крок 3) або вже прив'язаного гравця.

Після цього ви зможете затверджувати заявки інших користувачів через інтерфейс модератора.

## Використання

Після успішного запуску відкрийте у браузері:

- **Сайт:** http://localhost:8080
- **Mailpit (листи, які надсилає застосунок):** http://localhost:8025
- **MySQL:** `localhost:3306`, база `rating`, користувач `rating_user` з паролем `MYSQL_PASSWORD` з `.env` (тестова база — `rating_test`)
- **Redis:** `localhost:6379`

## Зупинка та перезапуск

Зупинити всі сервіси:

```bash
docker compose down
```

Запустити знову (без перезбирання):

```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d
```

## Оновлення після git pull

Для отримання нових змін з репозиторію і оновлення локального середовища запустіть скрипт оновлення:

```bash
./bin/update.sh
```

Він виконає `git pull`, запустить стек, встановить залежності, застосує міграції та очистить кеш. Образи скрипт не перезбирає і не оновлює: щоб отримати свіжий `php-dev` (CI публікує його з кожним комітом у `master`), виконайте:

```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml pull app
docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d
```

## Продакшн

Продакшн-стек живе в `docker-compose.prod.yml`: Caddy, застосунок, MySQL і
Redis. Збірка образу, конфігурація та розгортання описані окремо:
[docs/DEPLOYMENT.md](docs/DEPLOYMENT.md).

## Для розробників

- Предметна область, терміни й повний цикл турніру: [docs/DOMAIN.md](docs/DOMAIN.md)
- Архітектура й стек: [docs/PROJECT_OVERVIEW.md](docs/PROJECT_OVERVIEW.md)
- Конвенції коду, перелік фіч, сутностей і команд: [`.kiro/steering/`](.kiro/steering/)
- Інструкції для AI-агентів (Codex, Claude Code): [AGENTS.md](AGENTS.md), [CLAUDE.md](CLAUDE.md)

### Встановлення залежностей PHP (наприклад, після зміни composer.json)

```bash
docker compose exec app composer install
```

### Встановлення залежностей Node.js (наприклад, після зміни package.json)

```bash
docker compose exec app npm install
```

### Генерування перекладів

Після зміни файлу перекладів `translations/messages.uk.yaml` потрібно перегенерувати JS-переклади та закомітити результат:

```bash
docker compose exec app php bin/console app:generate-translations
```

### Локальне створення образів
Локальне створення образу для розробки з dev- і test-залежностями
(директорії з сайтом рейтингу повинні монтуватися як зовнішні директорії):
```bash
docker build -f docker/Dockerfile --target php-dev --tag php-dev .
```
Щоб стек використовував цей образ замість опублікованого, вкажіть у `.env` `APP_IMAGE=php-dev` і перезапустіть стек.

Локальне створення образу з тестовою версією сайту рейтингу з dev- і test-залежностями:
```bash
docker build -f docker/Dockerfile --target rating-app-test --tag rating-app-test .
```

Локальне створення образу з prod-версією сайту рейтингу без dev- і test-залежностей:
```bash
docker build -f docker/Dockerfile --target rating-app --tag rating-app .
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

Лінтинг JavaScript (ESLint):

```bash
docker compose exec app npx eslint assets/
```

Лінтинг CSS (Stylelint):

```bash
docker compose exec app npx stylelint 'assets/styles/**/*.css'
```

Лінтинг Twig-шаблонів (TwigCS Fixer):

```bash
docker compose exec app vendor/bin/twig-cs-fixer lint
```

Усі тести. Запускайте їх **лише** через `./bin/test.sh`: скрипт підставляє тестову базу `rating_test`, а прямий виклик PHPUnit очистить робочу базу `rating`.

```bash
./bin/test.sh
```

Тести з покриттям коду:

```bash
./bin/test.sh --coverage-text
```

Запуск конкретного тесту:

```bash
./bin/test.sh --filter=testBlockedUser
```

Перевірка безпеки залежностей:

```bash
docker compose exec app composer audit
docker compose exec app symfony security:check
```

Усі перевірки якості (окрім тестів) одним скриптом:

```bash
./bin/lint.sh
```

## Вирішення проблем

**Docker не запускається:**
Переконайтесь, що Docker Desktop запущений і повністю завантажився.

**Помилка з базою даних (`Access denied for user 'rating_user'`):**
Паролі MySQL задаються лише під час першої ініціалізації тому `db_data`. Якщо після цього змінити `MYSQL_PASSWORD` чи `MYSQL_ROOT_PASSWORD` у `.env`, застосунок не підключиться. Поверніть попередні значення або, якщо локальні дані не потрібні, видаліть томи (`docker compose down -v`) і пройдіть встановлення заново.

**Тестової бази `rating_test` немає:**
Її створює `docker/mysql/init-test-db.sql`, але лише під час першої ініціалізації тому `db_data` і лише якщо стек запущено з `docker-compose.dev.yml`. Створити її вручну (стек має бути запущений з `docker-compose.dev.yml`):

```bash
docker compose exec db sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" < /docker-entrypoint-initdb.d/init-test-db.sql'
```

**PHPCS видає сотні `End of line character is invalid` (Windows):**
Файли отримано із закінченнями рядків CRLF. Репозиторій примусово використовує LF (`.gitattributes`); якщо клон зроблено до цього, на чистому робочому дереві (без незакомічених змін!) виконайте `git rm -r --cached -q . && git reset --hard`.

**`Input/output error` під час `composer install` або тестів (Windows):**
Випадковий збій bind-mount Docker Desktop — повторіть команду. Щоб позбутися проблеми й повільної роботи, тримайте проєкт у файловій системі WSL2 (див. «Що потрібно встановити»).

**Git Bash спотворює шляхи в `docker compose exec` (Windows):**
Git Bash перетворює аргументи на кшталт `/var/www/html` на `C:/Program Files/Git/var/www/html`. Додайте перед командою `MSYS_NO_PATHCONV=1`.

**Порт 8080 зайнятий:**
Зупиніть інший сервіс на цьому порту або змініть порт у `docker-compose.yml`.

**Сторінка не завантажується після `docker compose up`:**
Зачекайте 10–15 секунд — серверу потрібен час на старт. Якщо не допомогло, перевірте логи:

```bash
docker compose logs app
```

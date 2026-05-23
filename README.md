# Сайт рейтингу українського «Що? Де? Коли?»

[![CI](https://github.com/steel-archer/rating/actions/workflows/ci.yml/badge.svg?branch=master)](https://github.com/steel-archer/rating/actions/workflows/ci.yml)
![Coverage](https://raw.githubusercontent.com/steel-archer/rating/master/.github/badges/coverage.svg)
![PHPCS](https://raw.githubusercontent.com/steel-archer/rating/master/.github/badges/phpcs.svg)
![PHPStan](https://raw.githubusercontent.com/steel-archer/rating/master/.github/badges/phpstan.svg)
![ESLint](https://raw.githubusercontent.com/steel-archer/rating/master/.github/badges/eslint.svg)
![Stylelint](https://raw.githubusercontent.com/steel-archer/rating/master/.github/badges/stylelint.svg)
![TwigCS](https://raw.githubusercontent.com/steel-archer/rating/master/.github/badges/twigcs.svg)

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

Скопіюйте файл з прикладом налаштувань:

```bash
cp .env.dist .env.local
```

Файл `.env.dist` вже містить робочі значення для локального середовища. Єдине, що потрібно заповнити — це Google OAuth (якщо потрібна автентифікація). Відкрийте `.env.local` і вкажіть:

```
GOOGLE_CLIENT_ID=отримайте_від_розробника
GOOGLE_CLIENT_SECRET=отримайте_від_розробника
```

> **Google OAuth:** значення `GOOGLE_CLIENT_ID` та `GOOGLE_CLIENT_SECRET` потрібно отримати від розробника проєкту. Без них автентифікація через Google не працюватиме.

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

### 8. Налаштуйте адміністратора

Адміністратори та модератори — це гравці з додатковими правами. Щоб створити першого адміна:

1. Напишіть розробнику і попросіть додати ваш Google-імейл до списку дозволених.
2. Відкрийте сайт (http://localhost:8080) і увійдіть через Google.
3. Подайте заявку на прив'язку до гравця (сайт запропонує це автоматично).
4. Затвердіть заявку та надайте права адміністратора (поміняйте в команді імейл на ваш):

```bash
docker compose exec app php bin/console app:promote-admin your-email@gmail.com
```

5. Перелогіньтеся на сайті (хоча, скоріше за все, вас вилогінить автоматично).

Після цього ви зможете затверджувати заявки інших користувачів через інтерфейс модератора.

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

## Оновлення після git pull

Після отримання нових змін з репозиторію запустіть скрипт оновлення:

```bash
./bin/update.sh
```

Він встановить залежності, застосує міграції та очистить кеш.

## Для розробників

Після зміни файлу перекладів `translations/messages.uk.yaml` потрібно перегенерувати JS-переклади та закомітити результат:

```bash
docker compose exec app php bin/console app:generate-translations
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

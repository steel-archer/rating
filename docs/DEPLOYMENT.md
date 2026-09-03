# Розгортання в продакшні

## Зі чого складається стек

| Компонент | Призначення |
|-----------|-------------|
| `docker/Dockerfile`, ціль `rating-app` | Продакшн-образ: PHP 8.5 з Apache, зібрані ассети, прогрітий кеш |
| `docker-compose.prod.yml` | Caddy, застосунок, MySQL, Redis і їхні томи |
| `docker/caddy/Caddyfile` | HTTPS, стиснення, заголовки безпеки, кешування ассетів |
| `docker/mysql/prod.cnf` | Продакшн-налаштування MySQL |
| `.env.prod.dist` | Шаблон для `.env`, який читає стек |

Продакшн-файл Compose самодостатній. Не об'єднуйте його з
`docker-compose.yml` і `docker-compose.dev.yml`: Compose зливає опубліковані
порти додаванням, тому override-файл ніколи не зміг би прибрати порти MySQL і
Redis, які відкриває стек розробки — на публічній IP-адресі це виставило б базу
даних у відкритий інтернет.

Порти публікує лише Caddy (80, 443/TCP, 443/UDP). Застосунок, MySQL і Redis
доступні тільки через внутрішню мережу.

## Що має надати хост

- x86-64 Linux з Docker Engine і плагіном Compose. Опублікований образ лише під amd64.
- Щонайменше 2 vCPU і 4 ГБ RAM.
- Вхідні 80, 443/TCP і **443/UDP**. Без UDP HTTP/3 тихо деградує.
- Більше нічого опублікованого: ані MySQL, ані Redis, ані застосунок не повинні отримати `ports:`.
- DNS-записи `A` (і `AAAA`, якщо використовується), які вже вказують на хост. Caddy запитує сертифікат при першому старті.

## Збірка й публікація образу

Зазвичай це робить CI, тегуючи образ за SHA коміту:

```bash
TAG="$(git rev-parse HEAD)"
docker build -f docker/Dockerfile --target rating-app \
  -t ghcr.io/steel-archer/rating-app:"$TAG" .
docker push ghcr.io/steel-archer/rating-app:"$TAG"
```

Завжди розгортайте конкретний тег. З `latest` неможливо сказати, яка саме
збірка працює, і немає незмінного тега, на який відкотитися.

## Конфігурація

```bash
cp .env.prod.dist .env
```

| Змінна | Примітки |
|--------|----------|
| `APP_IMAGE` | Повне посилання на образ разом з тегом-SHA |
| `DOMAIN` | Публічне доменне ім'я; Caddy отримує для нього сертифікат |
| `ACME_EMAIL` | Адреса, на яку Let's Encrypt пише про закінчення терміну |
| `DEFAULT_URI` | `https://<DOMAIN>`; для URL, що генеруються поза HTTP-запитом |
| `APP_SECRET` | `openssl rand -hex 32` |
| `MYSQL_ROOT_PASSWORD`, `MYSQL_PASSWORD` | `openssl rand -hex 32` |
| `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` | Google Cloud Console → Credentials → OAuth client |

Дозволений redirect URI в OAuth-клієнті має бути
`https://<DOMAIN>/connect/google/check`.

`TRUSTED_PROXIES` задається у Compose-файлі, а не в `.env`: застосунок завжди
відповідає лише через Caddy у приватній підмережі Docker. Без цієї змінної
Symfony вважає клієнтом адресу Caddy (ламається rate limiting) і будує
`http://`-посилання (ламається OAuth-редірект).

## Запуск

```bash
docker compose --project-name rating --env-file .env \
  -f docker-compose.prod.yml up -d
```

Entrypoint образу виконує `doctrine:migrations:migrate` до старту Apache, тому
міграції застосовує саме перестворення контейнера застосунку. Розраховуйте, що
до успішного healthcheck може минути близько хвилини; увесь цей час Caddy
віддає 502.

## Розгортання нової версії

Вкажіть новий тег в `APP_IMAGE`, потім:

```bash
docker compose --project-name rating --env-file .env \
  -f docker-compose.prod.yml pull app
docker compose --project-name rating --env-file .env \
  -f docker-compose.prod.yml up -d --remove-orphans
```

Робіть резервну копію перед кожним розгортанням. Відкат образу — розгортання
попереднього SHA тим самим способом — повертає код, але **міграції не
відкочуються**, тому зміну схеми можна скасувати лише з резервної копії.

Розгортання зберігає всі томи. `var/cache` вбудований в образ і навмисно не
зберігається: він має лишатися незмінним у межах релізу.

## Стан, який треба резервувати

| Том | Вміст | Втрата означає |
|-----|-------|----------------|
| `db_data` | Дані MySQL | Втрачено все |
| `app_uploads` | Документи турнірів (`var/uploads`) | Завантажені файли втрачено |
| `caddy_data` | ACME-акаунт і сертифікати | Перевипускаються автоматично, з урахуванням лімітів |
| `redis_data` | Кеш і сесії | Користувачів розлогінить, більше нічого |

Незамінний стан несуть лише перші два. Розклад, шифрування і зберігання цих
копій — справа хоста, і в репозиторії їх немає; але що б це не робило, воно
має знімати дамп MySQL і копію `app_uploads` як один узгоджений знімок, а це
на практиці означає паузу застосунку на час копіювання.

## Щоденні операції

```bash
docker compose --project-name rating --env-file .env \
  -f docker-compose.prod.yml ps
docker compose --project-name rating --env-file .env \
  -f docker-compose.prod.yml logs --tail=100 app
```

Призначити першого адміністратора після того, як він увійшов через Google:

```bash
docker compose --project-name rating --env-file .env \
  -f docker-compose.prod.yml exec app \
  php bin/console app:promote-admin your-email@example.org
```

Не змінюйте паролі MySQL після того, як том бази даних уже ініціалізовано:
змінні оточення контейнера не змінюють паролі наявних облікових записів MySQL.
Ротація вимагає одночасної зміни облікового запису всередині MySQL і значення
в `.env` як однієї операції обслуговування.

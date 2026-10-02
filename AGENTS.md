# AGENTS.md

Інструкції для AI-агентів (Codex, Claude Code та інших), які працюють із цим
репозиторієм. Людям варто почати з `README.md`.

## Проєкт

Сайт рейтингу українського «Що? Де? Коли?» — український аналог
rating.chgk.info: турніри (очні та синхрони), відіграші на майданчиках, команди
й базові склади, гравці, спірні відповіді, апеляції, модерація. Інтерфейс і всі тексти —
українською. Розрахунку рейтингу поки немає.

- Предметна область, терміни, повний цикл турніру: `docs/DOMAIN.md`
- Огляд архітектури й стеку: `docs/PROJECT_OVERVIEW.md`
- Продакшн: `docs/DEPLOYMENT.md`

**Стек:** PHP 8.5, Symfony 8, Doctrine ORM 3, MySQL, Redis, Twig + AssetMapper
(без бандлера) + Stimulus/Turbo, Docker Compose. Вхід — лише через Google OAuth.

## Обов'язкові правила

1. **Тести — тільки `./bin/test.sh [аргументи PHPUnit]`.** Ніколи не викликати
   `bin/phpunit` чи `vendor/bin/phpunit` напряму: лише скрипт підставляє тестову
   БД `rating_test`, а прямий запуск очищає фікстурами dev-БД `rating`.
2. **Усі команди застосунку — в контейнері:** `docker compose exec app ...`.
   На хості запускаються лише `bin/*.sh`, які самі звертаються до контейнера.
3. **`App\Common` не залежить від `App\Classic`.** Взаємодія — лише через
   інтерфейси в `src/Common/Contract`. Правило перевіряє
   `tests/Architecture/ModuleDependencyTest.php`; не послаблювати його.
4. **Згенеровані файли руками не редагувати:** `assets/translations.js`
   (`php bin/console app:generate-translations` після зміни
   `translations/messages.uk.yaml`), `assets/vendor/`, `public/assets/`.
5. **Мова:** відповіді в чаті — мовою запиту користувача; UI-тексти,
   переклади й документація — українською; коментарі в коді та повідомлення
   комітів — англійською.
   Терміни: «ЩДК» (не «ЧГК»), «спірна відповідь» / «спірні» (не «спірка»).
6. **Документація — частина зміни.** Нова чи змінена фіча → оновити
   `.kiro/steering/features.md`; сутність → `entities.md`; команда `app:*` →
   `commands.md`.

## Звідки брати правила

Детальні конвенції живуть у `.kiro/steering/` (їх також використовує Kiro IDE).
Перед зміною файлів прочитати відповідний документ:

| Документ | Коли читати |
|----------|-------------|
| `.kiro/steering/conventions.md` | **Завжди**: стиль PHP, DTO, безпека, тести, міграції, кеш, шаблони |
| `.kiro/steering/architecture.md` | Будь-яка зміна в `src/` |
| `.kiro/steering/entities.md` | `src/**/Entity/**`, `src/**/Repository/**`, `migrations/**` |
| `.kiro/steering/features.md` | Контролери; перелік фіч, ролей і бізнес-процесів |
| `.kiro/steering/commands.md` | `src/**/Command/**` |
| `.kiro/steering/frontend.md` | `assets/**`, `templates/**` |
| `.kiro/steering/testing.md` | `tests/**` |

`.kiro/steering/context.md` — персональні налаштування AI-асистента власника
проєкту в Kiro (мова відповідей, суб-агенти, робота з виводом). Це не правила
проєкту: Codex і Claude їх не застосовують.

## Запуск і перевірка

```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d
docker compose exec app composer install
docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
./bin/lint.sh                      # PHPCS, PHPStan, ESLint, Stylelint, TwigCS
./bin/test.sh --filter=SomeTest    # під час роботи
./bin/test.sh                      # повний прогін перед PR
```

**Готово** означає: `./bin/lint.sh` і `./bin/test.sh` зелені, нова поведінка
покрита e2e-тестом контролера, документація з п. 6 оновлена, JS-переклади
перегенеровані.

## Підводні камені

- **Локального входу немає.** Публічні лише головна, ліцензія й політика
  конфіденційності; після Google-входу без прив'язаного гравця доступна тільки
  заявка на прив'язку (`/player-claim`), решта сторінок вимагає гравця.
  Поведінку перевіряти e2e-тестами (`WebTestCase` + `loginUser`, див.
  `testing.md`). Не додавати обхід автентифікації без явного погодження
  власника проєкту.
- **`.env` читає лише Docker Compose.** `symfony/dotenv` не встановлено, тож
  `.env.local` ігнорується. Нова змінна для застосунку має бути прокинута в
  `environment:` у `docker-compose.yml`; після зміни `.env` перестворити
  контейнер (`up -d`).
- **Адмін не має обходу прав за зв'язками.** Організатор, представник
  майданчика, ведучий і журі визначаються зв'язками, а не роллю.
- **Rate limit вимкнено в dev/test**, тому ліміти не проявляються локально.
- **Windows:** `bin/*.sh` запускати з Git Bash або WSL. Bind-mount Docker
  Desktop повільний і зрідка дає `Input/output error` — просто перезапустити
  команду. У Git Bash абсолютні шляхи в аргументах `docker compose exec`
  спотворюються; допомагає `MSYS_NO_PATHCONV=1`. Якщо в `.env` увімкнено
  `docker-compose.windows.yml` (через `COMPOSE_FILE`), `vendor/` і
  `node_modules/` є лише в контейнері — читати їх через
  `docker compose exec app ...`, а стек запускати `docker compose up -d` без
  `-f`.

## Git

- Гілка від `master`, PR у `master`. Повідомлення комітів — англійською,
  коротко, по суті.
- Не комітити `.env`, `.env.local`, секрети, `var/`, `vendor/`, `node_modules/`.

## Кілька агентів одночасно

Коли над задачею працюють кілька агентів (наприклад, Claude і Codex),
розподіляти роботу **за файлами**, щоб двоє не редагували один файл, і давати
зміни одного агента на рев'ю іншому.

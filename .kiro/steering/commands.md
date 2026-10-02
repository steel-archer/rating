---
inclusion: fileMatch
fileMatchPattern: 'src/**/Command/**'
---

# Консольні команди застосунку

Довідник прикладних `app:*`-команд проєкту. Службові команди експлуатації (docker compose, міграції, лінтери, тести) описані в `context.md` — тут лише доменні команди застосунку.

Усі команди запускаються в docker-контейнері:

```bash
docker compose exec app php bin/console <назва-команди>
```

## Common

### app:generate-translations

Генерує файл JS-перекладів `assets/translations.js` із `translations/messages.uk.yaml` (пласка структура ключів через крапку).

**Файл:** `src/Common/Command/GenerateTranslationsCommand.php`

**Параметри:** немає.

**Коли запускати:** після будь-якої зміни YAML-перекладів.

```bash
docker compose exec app php bin/console app:generate-translations
```

### app:promote-admin

Затверджує наявну заявку користувача зі статусом `pending` (якщо є) та надає йому `ROLE_ADMIN`. Користувач має вже увійти через Google і мати або таку заявку, або привʼязаного гравця; інакше команда завершується помилкою. Команда не створює обліковий запис і не замінює вхід через OAuth.

**Файл:** `src/Common/Command/PromoteAdminCommand.php`

**Аргументи:**

| Аргумент | Обов'язковий | Опис |
|----------|--------------|------|
| email | ✓ | Email користувача (має вже залогінитися через Google) |

```bash
docker compose exec app php bin/console app:promote-admin your-email@gmail.com
```

### app:maintenance:enable

Вмикає режим технічних робіт. Сайт стає доступним лише для модераторів та адмінів; решта бачить сторінку техробіт (503). Стан зберігається в Redis із TTL 24 год як страховка. Винятки: початок і callback Google OAuth, вихід із системи та шляхи `/_wdt` і `/_profiler`.

**Файл:** `src/Common/Command/MaintenanceEnableCommand.php`

**Параметри:** немає.

```bash
docker compose exec app php bin/console app:maintenance:enable
```

### app:maintenance:disable

Вимикає режим технічних робіт і відновлює звичайний доступ.

**Файл:** `src/Common/Command/MaintenanceDisableCommand.php`

**Параметри:** немає.

```bash
docker compose exec app php bin/console app:maintenance:disable
```

### app:maintenance:status

Показує поточний стан режиму технічних робіт (увімкнено/вимкнено).

**Файл:** `src/Common/Command/MaintenanceStatusCommand.php`

**Параметри:** немає.

```bash
docker compose exec app php bin/console app:maintenance:status
```

## Classic

### app:season:rollover

Створює наступний сезон (якщо його ще немає) і вибірково переносить склади команд із сезону-джерела в новий. Потрібні дати початку й завершення джерела, але фактичне завершення сезону команда не перевіряє.

**Файл:** `src/Classic/Command/SeasonRolloverCommand.php`

**Опції:**

| Опція | Значення | Опис |
|-------|----------|------|
| --from | id сезону | Сезон-джерело (за замовчуванням — найновіший сезон) |
| --dry-run | прапорець | Показати, що буде зроблено, без запису змін |

```bash
docker compose exec app php bin/console app:season:rollover --dry-run
docker compose exec app php bin/console app:season:rollover --from=5
```

**Поведінка перенесення:**

- Використовує наявний наступний сезон або створює новий: початок — через рік від початку джерела, завершення — за секунду до наступної річниці.
- Переносить лише гравців базового складу, які хоча б раз грали за цю команду в сезоні-джерелі; команди без таких гравців пропускає.
- Зберігає капітана, якщо він переноситься; інакше обирає гравця з найбільшою кількістю ігор, за рівності — з найменшим ID.
- Пропускає команду, яка вже має склад у цільовому сезоні; створює записи приєднання `joined` для перенесених гравців.
- Реальний запуск виконується в транзакції; `--dry-run` не записує змін.
- Після успішного перенесення запуск без `--from` може обрати вже новостворений сезон. Для повторення тієї самої операції явно вказуй `--from`.

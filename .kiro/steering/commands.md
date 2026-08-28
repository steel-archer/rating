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

Затверджує наявну заявку користувача (якщо є) та надає йому `ROLE_ADMIN`.

**Файл:** `src/Common/Command/PromoteAdminCommand.php`

**Аргументи:**

| Аргумент | Обов'язковий | Опис |
|----------|--------------|------|
| email | ✓ | Email користувача (має вже залогінитися через Google) |

```bash
docker compose exec app php bin/console app:promote-admin your-email@gmail.com
```

### app:maintenance:enable

Вмикає режим технічних робіт. Сайт стає доступним лише для модераторів та адмінів; решта бачить сторінку техробіт (503). Стан зберігається в Redis із TTL 24 год як страховка.

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

Створює наступний сезон (якщо його ще немає) і переносить склади команд із завершеного сезону в новий.

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

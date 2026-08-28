---
inclusion: fileMatch
fileMatchPattern: 'assets/**|templates/**|*.css|*.twig|eslint.config.js|.stylelintrc.json|importmap.php'
---

# Фронтенд: конвенції JS / CSS / Twig

Стек — Symfony AssetMapper + importmap (без webpack/build-кроку), Stimulus + Turbo, vanilla-JS модулі. Загальне правило (єдиний візуальний стиль, українською) — у `context.md`.

## Структура assets

- `assets/app.js` — єдиний entrypoint (`importmap.php`). Підключає `stimulus_bootstrap.js`, `styles/app.css` і всі доменні JS-модулі через `import './x.js'`. Додаючи новий модуль — імпортуй його тут.
- Доменні модулі — плаский набір `assets/*.js` (`captain-claim.js`, `session-results.js`, ...), по одному на фічу/сторінку.
- Спільні утиліти: `api.js` (`apiPost`, `showError`, `transError`), `trans.js` (`trans`), `debounce.js`.
- `assets/translations.js` — **згенерований** файл (не редагувати вручну, не лінтується). Стилі — `assets/styles/*.css`, по файлу на компонент/сторінку.

## JavaScript

- **Кожен файл починається з `// @ts-check`** — увімкнений TypeScript-аналіз у JS. Типізуй через JSDoc (`@param`, `@returns`, `@template`) і уточнюй типи DOM через `/** @type {HTMLButtonElement} */ (...)`.
- **ESM-модулі** (`type: module`): іменовані `export function`, імпорти зверху.
- Патерн доменного модуля: `document.addEventListener('DOMContentLoaded', ...)`, дістати елементи за id, **рано вийти якщо їх немає** (`if (!toggle || !form) { return; }`), навісити обробники.
- **Запити до бекенду — через `apiPost(url, data)`** з `api.js` (сам ставить `Content-Type: application/json`, `X-Requested-With: XMLHttpRequest`, повертає `{ok, body}`). Не дублюй `fetch` вручну.
- **Помилки — через `showError(statusEl, body.error)`**: бекенд повертає `{error: 'translation.key'}`, а `transError` перекладає ключ (або показує backend-текст, якщо це не схоже на ключ). Fallback — `showError(status, null)` у `.catch`.
- **Trim** користувацький ввід перед відправкою (`comment.value.trim()`), блокуй кнопку на час запиту.
- Правила ESLint (`eslint.config.js`): 4 пробіли, одинарні лапки, `semi always`, `eqeqeq`, `curly all`, `comma-dangle always-multiline`, `no-var`, `prefer-const`, `no-console: warn`. Невикористані аргументи — префікс `_`.
- Лінт: `docker compose exec app npx eslint assets/` (повністю, без `| tail`).

## Переклади

- Джерело — `translations/messages.uk.yaml`. Після будь-якої зміни YAML-перекладів **обов'язково** згенеруй JS-версію:
  `docker compose exec app php bin/console app:generate-translations`.
- У JS: `trans('some.key')` або `trans('key', { '%name%': value })` (підстановка через `replaceAll`). Ключі — плаский dot-notation (`squad.hide`, `captain_claim.success`).
- Бекенд у DTO/винятках повертає **ключі** перекладів, не готовий текст (див. `architecture.md`).

## CSS

- Один файл на компонент/сторінку в `assets/styles/`, підключення — в `styles/app.css`.
- Stylelint: `stylelint-config-standard`; вимкнено `selector-class-pattern`, `custom-property-pattern`, `no-descending-specificity` (`.stylelintrc.json`).
- Лінт: `docker compose exec app npx stylelint 'assets/styles/**/*.css'` (повністю).
- Сторінки мають бути візуально в одному стилі — переви­користовуй наявні класи/компоненти (`card`, `table`, `pagination`, `search`, `breadcrumbs`) замість нових.

## Twig

- Шаблони в `templates/`, дзеркалять домени контролерів (`tournament/`, `team/`, `my/`, `moderator/`, `venue/`, `player/`). Спільні партіали — з префіксом `_` (`_pagination.html.twig`, `_player_link.html.twig`, `_contacts_button.html.twig`). Базовий — `base.html.twig`.
- У шаблон передавай **лише DTO**, ніколи Entity чи сирий масив (див. `architecture.md`).
- Інлайн-скрипти потребують CSP-nonce (є Twig-розширення `CspExtension`).
- Лінт: `docker compose exec app vendor/bin/twig-cs-fixer lint` (повністю).

## Stimulus / Turbo

- `controllers.json` — UX-пакети (turbo-core увімкнено). Stimulus-контролери (за потреби) — у `assets/controllers/`, реєструються автоматично через `stimulus_bootstrap.js`. Наявний код переважно на vanilla-модулях; Stimulus додавай лише коли потрібна перевикористовувана поведінка з прив'язкою до розмітки.

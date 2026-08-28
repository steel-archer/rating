---
inclusion: fileMatch
fileMatchPattern: 'tests/**'
---

# Тести: механіка та патерни

Принципи (FIRST, e2e, по тесту на контролер, моки з очікуваннями) — у `context.md`. Тут — конкретна механіка проєкту.

## Запуск

- Усі тести: `./bin/test.sh` (скрипт сам ганяє міграції на тестовій БД `rating_test`, потім `bin/phpunit`; усі аргументи проксуються в PHPUnit).
- Конкретний: `./bin/test.sh --filter=testUpdate`.
- З покриттям: `./bin/test.sh --coverage-html coverage --coverage-clover var/coverage/clover.xml`.
- ⚠️ Ніколи не пропускай вивід через `| tail`/`| head`/`| grep` — читай повністю (див. `context.md`).

## Структура

- Тести дзеркалять `src/`: `tests/TestCase/{Common,Classic}/Controller/...`, `.../Command/...`, `.../Helper/...`. По тестовому класу на контролер, у тій самій підтеці.
- `tests/Architecture/ModuleDependencyTest.php` — PHPat-правило, що забороняє `App\Common` залежати від `App\Classic`. Порушення провалює перевірку. При доданні міжмодульної залежності — використовуй Contract-інтерфейс (див. `architecture.md`), не послаблюй тест.
- `tests/Fixtures/` — alice-фікстури (`Entity/*.yaml`, `Files/`, а також `Controller/`, `Mapping/` — тестові фікстур-класи).
- `tests/DataFixtures/` — реєструються в DI лише в `dev`/`test` (`config/services.yaml`).

## Патерн контролер-тесту (e2e)

Тести розширюють `WebTestCase` і використовують `FixturesTrait`:

```php
class UpdateControllerTest extends WebTestCase
{
    use FixturesTrait;

    private const array FIXTURES = ['Entity/base.yaml', 'Entity/session_claims.yaml'];
    // ...
}
```

- **Фікстури:** `self::loadFixtures([...])` завантажує YAML-файли з `tests/Fixtures/` у режимі purge-delete і повертає `array<string, object>` — мапу референсів (ключ = ім'я з YAML, напр. `'session_pending'`) на створені Entity. `Entity/base.yaml` — спільна база (країна, міста, сезон, гравці, команди, майданчики, представники); підключай її першою, а поверх — сценарний файл.
- **Логін:** `$client->loginUser($objects['user_representative'])`.
- **Один `dataProvider` на клас** — кожен кейс `yield 'опис' => [...]`. Додавай нові кейси в наявний provider, не плоди класи. Типова форма кейсу: `fixtures`, `loginAs`, `action(callable)`, `expectedStatus`, `afterCallback(callable)`, опційно `mockSetup(callable)`.
- **Запит:** `$client->request('POST', '/url/' . $objects['x']->getId(), [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([...], JSON_THROW_ON_ERROR))`.
- **Асерти — змістовні, не лише код відповіді:**
  - статус: `static::assertResponseStatusCodeSame($expectedStatus)`;
  - стан БД: перечитай Entity через `getContainer()->get('doctrine')->getRepository(...)->find(...)` і перевір поля;
  - тіло помилки: `json_decode(...)` і звір ключ перекладу, напр. `assertSame('session_claim.error.registration_closed', $body['error'])`.
- **Покривай happy path + unhappy path:** валідація (422), відмова в доступі / не власник (403), закритий стан (422 з конкретним ключем), непередбачений виняток (500 з `common.error`).

## Моки (з обов'язковими очікуваннями)

Для симуляції збою сервісу — підмінь його в контейнері:

```php
$client->disableReboot();
$stub = $test->createStub(SessionClaimService::class);
$stub->method('update')->willThrowException(new RuntimeException('unexpected'));
static::getContainer()->set(SessionClaimService::class, $stub);
```

Перевіряй, що контролер перетворює це на очікувану відповідь (напр. 500 `common.error`). Якщо використовуєш мок — завжди задавай очікування на його методи.

## Кеш у тестах

У тестовому оточенні кеш — `cache.adapter.array` (не Redis), тож стан не тече між тестами. Кешуй лише DTO, ніколи Entity (див. `context.md`).

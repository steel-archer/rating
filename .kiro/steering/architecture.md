---
inclusion: fileMatch
fileMatchPattern: 'src/**'
---

# Архітектура: шари та їх взаємодія

Довідник про те, **де що лежить** і **як шари з'єднані**. Загальні принципи (DTO, безпека, БД) — у `context.md`; тут — конкретна механіка проєкту з прикладами. Модульний поділ Common/Classic описаний нижче та в `context.md`.

## Модулі

- **`src/Common`** — загальний код: `User`, `Player`, `Venue`, `Town`, `Country`, `Season`, автентифікація, інфраструктура (rate-limit, санітизація, обробка помилок).
- **`src/Classic`** — специфіка «Що? Де? Коли?»: турніри, сесії, спірки, апеляції, команди.
- **Правило залежностей:** `Common` **не** посилається на `Classic`. Кодифіковано архітектурним тестом `tests/Architecture/ModuleDependencyTest.php` (PHPat). Взаємодія — лише через інтерфейси в `src/Common/Contract` (див. «Provider + Contract»).

## Конвеєр запиту

```
Route → Controller → (Request-DTO) → Service → Repository / Provider
                          ↑                          │
                       Mapper ←──── Entity ──────────┘
                          │
                    Response-DTO → Twig / JSON
```

## Controller

- Переважно **single-action invokable**-класи (`__invoke`), що розширюють `AbstractController`. Маршрут — атрибут `#[Route(...)]` на класі. Згруповані по доменах/ролях: `Controller/{Tournament,Team,My,Moderator,Api}`.
- **Вхід — DTO, не `Request`:**
  - POST-тіло: `#[MapRequestPayload] SomeRequestDTO $dto`
  - Query string: `#[MapQueryString] PageRequestDTO $dto = new PageRequestDTO()`
- **Прив'язка Entity з URL** через Symfony EntityValueResolver: типізуй параметр як Entity (`TournamentSession $session`), Doctrine підвантажить за `{id}`. Маршрут завжди обмежує `requirements: ['id' => '\d+']` (гарантує додатність). Якщо потрібна кастомна логіка/кеш — прийми `int $id` і делегуй сервісу.
- **Rate-limiting** — атрибут `#[RateLimited('mutation')]` на класі контролера (див. нижче).
- **Права** — `$this->denyAccessUnlessGranted(SomeVoter::ACTION, $subject)`.
- **Помилки** — лови специфічний виняток і прокидуй його текст: `catch (LogicException $ex) { return $this->json(['error' => $ex->getMessage()], 422); }`. Загальні винятки → generic-текст (обробляє `ExceptionSubscriber`).
- Еталон: `src/Classic/Controller/My/SessionClaim/UpdateController.php`.

## DTO

- `final readonly class`, іменовані параметри через constructor property promotion, без сеттерів. Ніколи не створюй DTO в контролері вручну — тільки через `Mapper` (або в сервісі, якщо DTO агрегує кілька джерел).
- **Request-DTO** (`DTO/Request/...`) — вбудована валідація через `Symfony\Component\Validator\Constraints as Assert` на властивостях (`#[Assert\Date]`, `#[Assert\Positive]`). Кастомні: `#[NoHtml]`, `#[Phone]`, `#[UkrainianName]`, `#[UkrainianTownName]`.
- **Response-DTO** (`DTO/Response/...`) — вкладені DTO у підтеках, типізуються phpdoc `@var list<SomeDTO>`. Не віддавай Entity чи сирий масив у шаблон/JSON.

## Mapping (Entity → Response-DTO)

- Кожен мапер — `implements MappingInterface` + атрибут `#[AsMapper(source: Entity::class, destination: Dto::class)]` (`src/Common/Mapping/`).
- Реєстрація автоматична: `_instanceof: MappingInterface → tag app.mapping`, а `Mapper` отримує `!tagged_iterator app.mapping` (`config/services.yaml`) і будує реєстр `"source::destination"`.
- Виклик: `$this->mapper->map($entity, SomeDTO::class, [...context])` або `mapMultiple(...)`. Додаткові дані (лічильники, контакти) передавай через `$context`, а **не** читай з Entity в мапері — це тримає запити керованими.
- Вкладені мапери викликай через `$context['mapper']` (Mapper сам його підкладає).
- Мапери структуровані по підтеках за доменом/роллю: `Mapping/{Tournament,Team,Player,My,Moderator,Venue}`.
- Еталон: `src/Classic/Mapping/TeamMapping.php`.

## Service

- Інкапсулюють бізнес-логіку й транзакції. **Приймають** Entity + Request-DTO, **повертають** Response-DTO (ніколи Entity).
- Бізнес-помилки — `throw new LogicException('some.translation.key')`; контролер перетворює на 422. Ключ — це переклад, не текст для людини.
- Інжектять репозиторії + `EntityManagerInterface` для `persist`/`flush`.
- Після мутацій — каскадна інвалідація кешу через `CacheInvalidator` (`src/Classic/Service/Cache/`), напр. `invalidateTournament(...)`.
- Уникнення N+1: збирай id і роби один batch-запит (напр. `countPlayedByVenueIds`), а не запит у циклі.
- Еталон: `src/Classic/Service/SessionClaimService.php`.

## Repository

- Розширюють `ServiceEntityRepository` з phpdoc `@extends ServiceEntityRepository<Entity>`.
- **Проти N+1:** явні `->join(...)->addSelect(...)` для фетч-джойну графа одним запитом.
- **Легкі проєкції:** `->select('IDENTITY(...)', 'x.name')->getArrayResult()` замість гідрації Entity, коли потрібні лише кілька полів.
- **Existence-перевірки:** `->select('1')->setMaxResults(1)->getOneOrNullResult()`.
- **Пошук LIKE:** екрануй ввід через `LikeEscape` (`src/Common/Helper/`), щоб уникнути wildcard-ін'єкцій.

## Provider + Contract (міжмодульна взаємодія Common ↔ Classic)

Головний механізм розв'язання залежностей між модулями:

1. `Common` оголошує потребу як інтерфейс у `src/Common/Contract` (напр. `PlayerTeamProviderInterface`), залежний лише від Common-сутностей.
2. `Classic` реалізує його у `src/Classic/Provider` (напр. `ClassicPlayerTeamProvider`), знаючи про Classic-сутності.
3. Споживач у Common інжектить **інтерфейс**, не реалізацію.
4. DI: явного біндингу немає — працює single-implementer autowiring (єдиний імплементер прив'язується автоматично через `App\: resource: '../src/'`).

Наявні контракти: `PlayerTeamProviderInterface`, `PlayerDetailProviderInterface`, `PlayerTournamentProviderInterface`, `VenueTournamentProviderInterface`.

## Security (Voters)

Права понад ієрархію ролей Symfony перевіряються Voter'ами (`extends Voter`, generic phpdoc `@extends Voter<string, Subject>`, дія — публічна константа):

- `SessionRepresentativeVoter` (`SESSION_MANAGE`) — представник сесії (`session.representative === player`).
- `TournamentOrganizerVoter` (`TOURNAMENT_EDIT`) — делегує в `TournamentOfficialRepository::isOrganizer`.
- `PlayerVoter` (`ROLE_PLAYER`) — роль обчислюється динамічно: `User` має прив'язаного `Player`. `ROLE_PLAYER` **не** зберігається в БД.
- Капітан визначається через `TeamPlayer.isCaptain`, журі — через `TournamentOfficial.role` (перевіряється в репозиторіях/сервісах, не окремими Voter'ами).

Інше в `Common/Security`: `GoogleAuthenticator` (OAuth), `AccessDeniedHandler`, `CspNonceGenerator`.

## Інфраструктура (Common/EventSubscriber)

- `RequestSanitizingSubscriber` (`REQUEST`, пріоритет 128) — рекурсивний `trim` + видалення HTML-тегів з query/request/JSON-тіла. Кастомний `stripTags` не ламає легітимний текст на кшталт `a < b`.
- `RateLimitSubscriber` (`CONTROLLER`) — читає `#[RateLimited]`; для GET-запитів до `App\`-контролерів без атрибута — fallback-лімітер `read`. Лімітери (`auth`, `claim`, `api_suggest`, `mutation`, `upload`, `moderator`, `read`) інжектяться `ServiceLocator`'ом у `config/services.yaml`. Ідентичність: userIdentifier або IP. Перевищення → 429 + `Retry-After`.
- `ExceptionSubscriber` (`EXCEPTION`, -10) — для JSON/XHR: `ValidationFailedException` → 422 з текстом першого порушення; інше логується → generic `common.error` 500.
- `BlockedUserSubscriber`, `MaintenanceSubscriber`, `SecurityHeadersSubscriber`.

## Twig

Розділення Extension (декларація функцій) + Runtime (лінива реалізація): `Classic/{AppExtension,AppRuntime}`, `Common/{CspExtension,CspRuntime}`, `Common/{MaintenanceExtension,MaintenanceRuntime}`.

## Шпаргалка: як додати нову сторінку/дію

1. **Route + Controller** — invokable-клас у відповідній підтеці `Controller/...`, `#[Route]`, `#[RateLimited(...)]`, Entity через URL або `int $id`.
2. **Request-DTO** (для запису) з `Assert`-валідацією; приймай через `#[MapRequestPayload]`/`#[MapQueryString]`.
3. **Service** — бізнес-логіка, `LogicException` на бізнес-помилки, `flush`, інвалідація кешу.
4. **Repository** — запити з фетч-джойном/проєкціями проти N+1.
5. **Response-DTO** + **Mapping** (`#[AsMapper]`) для віддачі назовні.
6. **Voter** — якщо потрібна перевірка прав понад роль.
7. **Тест** — e2e на контролер (див. `testing.md`).
8. Якщо `Common` має звертатися до `Classic` — через **Contract**-інтерфейс, не напряму.

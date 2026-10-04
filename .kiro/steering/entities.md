---
inclusion: fileMatch
fileMatchPattern: 'src/**/Entity/**|src/**/Repository/**|migrations/**'
---

# Карта Doctrine-сутностей

Довідкова карта всіх Doctrine-сутностей проєкту, згрупованих за модулями. Використовується як контекст для розуміння доменної моделі.

Позначка «початкове значення PHP» описує ініціалізацію властивості сутності, а не `DEFAULT` у БД. Окремо зазначено значення, для яких ORM явно задає `options: ['default' => ...]`.

## Common

### Country

Довідник країн.

**Файл:** `src/Common/Entity/Country.php`

#### Поля

| Поле | Тип | Nullable | Примітка |
|------|-----|----------|----------|
| id | int | — | PK, auto |
| name | string(255) | — | |

#### Обмеження

- **UQ_country_name**: (name)

### Player

Гравець інтелектуальних ігор. Базова сутність, на яку посилаються турніри, команди та заявки.

**Файл:** `src/Common/Entity/Player.php`

#### Поля

| Поле | Тип | Nullable | Примітка |
|------|-----|----------|----------|
| id | int | — | PK, auto |
| lastName | string(255) | — | |
| firstName | string(255) | — | |
| patronymic | string(255) | ✓ | |
| createdAt | DateTimeImmutable | — | |
| updatedAt | DateTimeImmutable | — | |

#### Зв'язки

| Поле | Тип | Ціль | Nullable | Примітка |
|------|-----|------|----------|----------|
| town | ManyToOne | Town | ✓ | |
| user | OneToOne | User | ✓ | mappedBy: player |

### PlayerClaim

Заявка користувача на прив'язку до існуючого або створення нового гравця в системі.

**Файл:** `src/Common/Entity/PlayerClaim.php`

#### Поля

| Поле | Тип | Nullable | Примітка |
|------|-----|----------|----------|
| id | int | — | PK, auto |
| firstName | string(255) | ✓ | |
| lastName | string(255) | — | |
| patronymic | string(255) | ✓ | |
| townName | string(255) | ✓ | назва нового міста, якого ще немає в довіднику |
| status | enum | — | `App\Common\Enum\PlayerClaimStatus` |

#### Зв'язки

| Поле | Тип | Ціль | Nullable | Примітка |
|------|-----|------|----------|----------|
| user | ManyToOne | User | — | |
| player | ManyToOne | Player | ✓ | |
| town | ManyToOne | Town | ✓ | |
| country | ManyToOne | Country | ✓ | країна (з довідника) для нового міста, введеного вручну |

### Season

Ігровий сезон із визначеними датами початку та завершення.

**Файл:** `src/Common/Entity/Season.php`

#### Поля

| Поле | Тип | Nullable | Примітка |
|------|-----|----------|----------|
| id | int | — | PK, auto |
| name | string(255) | — | |
| startedAt | DateTimeImmutable | ✓ | |
| endedAt | DateTimeImmutable | ✓ | |

### Town

Довідник міст.

**Файл:** `src/Common/Entity/Town.php`

#### Поля

| Поле | Тип | Nullable | Примітка |
|------|-----|----------|----------|
| id | int | — | PK, auto |
| name | string(255) | — | |

#### Зв'язки

| Поле | Тип | Ціль | Nullable | Примітка |
|------|-----|------|----------|----------|
| country | ManyToOne | Country | — | |

#### Обмеження

- **UQ_town_name_country**: (name, country_id)

### User

Користувач системи. Авторизується через Google, може бути прив'язаний до гравця.

**Файл:** `src/Common/Entity/User.php`

**Таблиця:** `common_user`

#### Поля

| Поле | Тип | Nullable | Примітка |
|------|-----|----------|----------|
| id | int | — | PK, auto |
| email | string(255) | — | |
| googleId | string(255) | — | |
| firstName | string(255) | ✓ | |
| lastName | string(255) | ✓ | |
| roles | json | — | |
| telegram | string(32) | ✓ | |
| facebook | string(50) | ✓ | |
| phone | string(20) | ✓ | |
| blockedReason | string(500) | ✓ | |
| termsAcceptedAt | DateTimeImmutable | ✓ | |
| createdAt | DateTimeImmutable | — | |
| updatedAt | DateTimeImmutable | — | |

#### Зв'язки

| Поле | Тип | Ціль | Nullable | Примітка |
|------|-----|------|----------|----------|
| player | OneToOne | Player | ✓ | inversedBy: user; cascade: persist |

#### Обмеження

- **UNIQ_user_email**: (email)
- **UNIQ_user_google_id**: (google_id)
- **UNIQ_user_player**: (player_id)

### Venue

Місце проведення ігор (бар, клуб, зал або онлайн-майданчик). Прив'язане до міста, потребує підтвердження. Схвалення зберігається як `isApproved`; відхилення видаляє несхвалений майданчик і його представників, окремого статусу відмови немає.

**Файл:** `src/Common/Entity/Venue.php`

#### Поля

| Поле | Тип | Nullable | Примітка |
|------|-----|----------|----------|
| id | int | — | PK, auto |
| name | string(255) | — | |
| description | text | ✓ | |
| url | string(255) | ✓ | |
| isOnline | bool | — | початкове значення PHP: false |
| isApproved | bool | — | початкове значення PHP: false |
| createdAt | DateTimeImmutable | — | |

#### Зв'язки

| Поле | Тип | Ціль | Nullable | Примітка |
|------|-----|------|----------|----------|
| town | ManyToOne | Town | — | |
| createdBy | ManyToOne | Player | ✓ | фіксує автора; права на керування майданчиком має як творець, так і будь-який представник (VenueRepresentative) |

#### Обмеження

- **UQ_venue_name_town**: (name, town_id)

### VenueRepresentative

Представник майданчика — зв'язок між гравцем і конкретним майданчиком, де він виконує роль представника.

**Файл:** `src/Common/Entity/VenueRepresentative.php`

#### Поля

| Поле | Тип | Nullable | Примітка |
|------|-----|----------|----------|
| id | int | — | PK, auto |
| createdAt | DateTimeImmutable | — | |

#### Зв'язки

| Поле | Тип | Ціль | Nullable | Примітка |
|------|-----|------|----------|----------|
| venue | ManyToOne | Venue | — | |
| player | ManyToOne | Player | — | |

#### Обмеження

- **UQ_venue_player**: (venue_id, player_id)

## Classic

### CaptainClaim

Заявка гравця на капітанство в команді. Розглядається модератором.

**Файл:** `src/Classic/Entity/CaptainClaim.php`

#### Поля

| Поле | Тип | Nullable | Примітка |
|------|-----|----------|----------|
| id | int | — | PK, auto |
| status | enum | — | `App\Classic\Enum\CaptainClaimStatus` |
| comment | text | — | коментар гравця |
| moderatorComment | text | ✓ | коментар модератора (обов'язковий при відмові) |
| createdAt | DateTimeImmutable | — | |
| resolvedAt | DateTimeImmutable | ✓ | |

#### Зв'язки

| Поле | Тип | Ціль | Nullable | Примітка |
|------|-----|------|----------|----------|
| player | ManyToOne | Player | — | 🔗 Common |
| team | ManyToOne | Team | — | |

### Appeal

Апеляція на відповідь команди в турнірній сесії. Може бути на зарахування відповіді або зняття запитання. Прийняття типу `remove` знімає запитання в усьому турнірі й перераховує бали всіх команд.

**Файл:** `src/Classic/Entity/Appeal.php`

#### Поля

| Поле | Тип | Nullable | Примітка |
|------|-----|----------|----------|
| id | int | — | PK, auto |
| type | enum | — | `App\Classic\Enum\AppealType` |
| text | text | — | |
| status | enum | — | `App\Classic\Enum\AppealStatus` |
| verdict | text | ✓ | |
| createdAt | DateTimeImmutable | — | |

#### Зв'язки

| Поле | Тип | Ціль | Nullable | Примітка |
|------|-----|------|----------|----------|
| tournamentSessionTeamAnswer | OneToOne | TournamentSessionTeamAnswer | — | |

#### Обмеження

- **UQ_appeal_answer**: (tournament_session_team_answer_id)

### SessionClaim

Заявка на проведення ігрової сесії. Подання одночасно створює сесію та заявку зі статусом `pending`.

**Файл:** `src/Classic/Entity/SessionClaim.php`

#### Поля

| Поле | Тип | Nullable | Примітка |
|------|-----|----------|----------|
| id | int | — | PK, auto |
| status | enum | — | `App\Classic\Enum\SessionClaimStatus` |
| comment | text | ✓ | |
| createdAt | DateTimeImmutable | — | |
| resolvedAt | DateTimeImmutable | ✓ | |

#### Зв'язки

| Поле | Тип | Ціль | Nullable | Примітка |
|------|-----|------|----------|----------|
| session | OneToOne | TournamentSession | — | |
| player | ManyToOne | Player | — | 🔗 Common |

#### Обмеження

- **UNIQ_sc_session**: (session_id)

### Team

Команда гравців, прив'язана до міста.

**Файл:** `src/Classic/Entity/Team.php`

#### Поля

| Поле | Тип | Nullable | Примітка |
|------|-----|----------|----------|
| id | int | — | PK, auto |
| name | string(255) | — | |

#### Зв'язки

| Поле | Тип | Ціль | Nullable | Примітка |
|------|-----|------|----------|----------|
| town | ManyToOne | Town | — | 🔗 Common |

### TeamPlayer

Зв'язок гравця з командою в конкретному сезоні. Визначає склад команди та капітана.

**Файл:** `src/Classic/Entity/TeamPlayer.php`

#### Поля

| Поле | Тип | Nullable | Примітка |
|------|-----|----------|----------|
| id | int | — | PK, auto |
| isCaptain | bool | — | початкове значення PHP та DEFAULT БД (ORM): false |

#### Зв'язки

| Поле | Тип | Ціль | Nullable | Примітка |
|------|-----|------|----------|----------|
| team | ManyToOne | Team | — | |
| player | ManyToOne | Player | — | 🔗 Common |
| season | ManyToOne | Season | — | 🔗 Common |

#### Обмеження

- **UQ_player_season**: (player_id, season_id)

### TeamPlayerTransfer

Запис історії переходів гравця між командами. Фіксує кожне приєднання та вихід зі складу.

**Файл:** `src/Classic/Entity/TeamPlayerTransfer.php`

#### Поля

| Поле | Тип | Nullable | Примітка |
|------|-----|----------|----------|
| id | int | — | PK, auto |
| type | enum | — | `App\Classic\Enum\TeamPlayerTransferType` (joined/left) |
| date | DateTimeImmutable (date) | — | дата переходу |

#### Зв'язки

| Поле | Тип | Ціль | Nullable | Примітка |
|------|-----|------|----------|----------|
| player | ManyToOne | Player | — | 🔗 Common |
| team | ManyToOne | Team | — | |
| season | ManyToOne | Season | — | 🔗 Common |

#### Індекси

- **IDX_tpt_player_season_date**: (player_id, season_id, date)

### Tournament

Турнір із запитань «Що? Де? Коли?». Центральна сутність модуля Classic — об'єднує сесії, команди та результати.

**Файл:** `src/Classic/Entity/Tournament.php`

#### Поля

| Поле | Тип | Nullable | Примітка |
|------|-----|----------|----------|
| id | int | — | PK, auto |
| name | string(255) | — | |
| status | enum | — | `App\Classic\Enum\TournamentStatus` |
| format | enum | — | `App\Classic\Enum\TournamentFormat` |
| onlineMode | enum | — | `App\Classic\Enum\TournamentOnlineMode` |
| startedAt | DateTimeImmutable | ✓ | |
| endedAt | DateTimeImmutable | ✓ | |
| resultsHiddenUntil | DateTimeImmutable | ✓ | |
| toursCount | int | ✓ | |
| questionsPerTourMap | json | ✓ | list<int>, кількість запитань по турах |
| difficulty | float | ✓ | |
| trueDl | float | ✓ | |
| registrationDeadline | DateTimeImmutable | ✓ | |
| detailsHiddenUntil | DateTimeImmutable | ✓ | |
| submissionDeadline | DateTimeImmutable | ✓ | |
| appealDeadline | DateTimeImmutable | ✓ | |
| discussionLink | string(512) | ✓ | |
| createdAt | DateTimeImmutable | — | |
| updatedAt | DateTimeImmutable | — | |

#### Зв'язки

| Поле | Тип | Ціль | Nullable | Примітка |
|------|-----|------|----------|----------|
| createdBy | ManyToOne | Player | ✓ | 🔗 Common |
| season | ManyToOne | Season | ✓ | 🔗 Common |

### TournamentDocument

Документ (файл), прикріплений до турніру. Зберігає метадані завантаженого файлу.

**Файл:** `src/Classic/Entity/TournamentDocument.php`

#### Поля

| Поле | Тип | Nullable | Примітка |
|------|-----|----------|----------|
| id | int | — | PK, auto |
| originalName | string(255) | — | |
| storedName | string(255) | — | |
| mimeType | string(100) | — | |
| size | int | — | |
| createdAt | DateTimeImmutable | — | |

#### Зв'язки

| Поле | Тип | Ціль | Nullable | Примітка |
|------|-----|------|----------|----------|
| tournament | ManyToOne | Tournament | — | |

### TournamentDocumentDownload

Аудит-запис про завантаження пакета запитань. Один рядок на кожне завантаження документа ведучим.

**Файл:** `src/Classic/Entity/TournamentDocumentDownload.php`

#### Поля

| Поле | Тип | Nullable | Примітка |
|------|-----|----------|----------|
| id | int | — | PK, auto |
| downloadedAt | DateTimeImmutable | — | |

#### Зв'язки

| Поле | Тип | Ціль | Nullable | Примітка |
|------|-----|------|----------|----------|
| document | ManyToOne | TournamentDocument | — | |
| player | ManyToOne | Player | — | 🔗 Common, ведучий, що завантажив |

### TournamentModerationClaim

Заявка на модерацію турніру. Визначає статус перевірки турніру модератором.

**Файл:** `src/Classic/Entity/TournamentModerationClaim.php`

#### Поля

| Поле | Тип | Nullable | Примітка |
|------|-----|----------|----------|
| id | int | — | PK, auto |
| status | enum | — | `App\Classic\Enum\TournamentModerationStatus` |
| comment | text | ✓ | |
| createdAt | DateTimeImmutable | — | |
| resolvedAt | DateTimeImmutable | ✓ | |

#### Зв'язки

| Поле | Тип | Ціль | Nullable | Примітка |
|------|-----|------|----------|----------|
| tournament | OneToOne | Tournament | — | |

#### Обмеження

- **UNIQ_tmc_tournament**: (tournament_id)

### TournamentOfficial

Офіційна особа турніру — зв'язок гравця з турніром у певній ролі (редактор, член ігрового журі тощо).

**Файл:** `src/Classic/Entity/TournamentOfficial.php`

#### Поля

| Поле | Тип | Nullable | Примітка |
|------|-----|----------|----------|
| id | int | — | PK, auto |
| role | enum | — | `App\Classic\Enum\TournamentOfficialRole` |

#### Зв'язки

| Поле | Тип | Ціль | Nullable | Примітка |
|------|-----|------|----------|----------|
| tournament | ManyToOne | Tournament | — | |
| player | ManyToOne | Player | — | 🔗 Common |

#### Обмеження

- **UQ_tournament_player_role**: (tournament_id, player_id, role)

### TournamentSession

Ігрова сесія турніру — конкретне проведення гри на певному майданчику з представником та ведучим.

**Файл:** `src/Classic/Entity/TournamentSession.php`

#### Поля

| Поле | Тип | Nullable | Примітка |
|------|-----|----------|----------|
| id | int | — | PK, auto |
| playedAt | DateTimeImmutable | ✓ | |
| estimatedTeams | int | ✓ | |
| announcementUrl | string(255) | ✓ | |
| isOnline | bool | — | початкове значення PHP: false |
| createdAt | DateTimeImmutable | — | |
| updatedAt | DateTimeImmutable | — | |

#### Зв'язки

| Поле | Тип | Ціль | Nullable | Примітка |
|------|-----|------|----------|----------|
| tournament | ManyToOne | Tournament | — | |
| venue | ManyToOne | Venue | — | 🔗 Common |
| representative | ManyToOne | Player | — | 🔗 Common, фіксує лише автора заявки; права на керування сесією має будь-який представник майданчика (VenueRepresentative) |
| host | ManyToOne | Player | — | 🔗 Common, гравець з акаунтом |

### TournamentSessionHostHistory

Історія ведучих сесії. Сервіс записує ведучого під час схвалення заявки та його заміни, поки заявка схвалена; призначення лише в очікуванні не записуються. Поточний ведучий зберігається в `TournamentSession.host`.

**Файл:** `src/Classic/Entity/TournamentSessionHostHistory.php`

**Таблиця:** `classic_tournament_session_host_history`

#### Поля

| Поле | Тип | Nullable | Примітка |
|------|-----|----------|----------|
| id | int | — | PK, auto |
| createdAt | DateTimeImmutable | — | задається конструктором PHP |

#### Зв'язки

| Поле | Тип | Ціль | Nullable | Примітка |
|------|-----|------|----------|----------|
| session | ManyToOne | TournamentSession | — | |
| player | ManyToOne | Player | — | 🔗 Common |

#### Обмеження

- **UNIQ_tshh_session_player**: (session_id, player_id)

#### Індекси

- **IDX_tshh_session**: (session_id)
- **IDX_tshh_player**: (player_id)

### TournamentSessionTeam

Участь команди в конкретній ігровій сесії турніру. Зберігає рахунок команди та статус подання результатів.

**Файл:** `src/Classic/Entity/TournamentSessionTeam.php`

#### Поля

| Поле | Тип | Nullable | Примітка |
|------|-----|----------|----------|
| id | int | — | PK, auto |
| score | int | — | початкове значення PHP: 0 |
| resultsSubmitted | bool | — | початкове значення PHP: false |
| oneTimeName | string(255) | ✓ | |

#### Зв'язки

| Поле | Тип | Ціль | Nullable | Примітка |
|------|-----|------|----------|----------|
| tournamentSession | ManyToOne | TournamentSession | — | |
| team | ManyToOne | Team | — | |
| answers | OneToMany | TournamentSessionTeamAnswer | — | mappedBy: tournamentSessionTeam |

#### Обмеження

- **UQ_session_team**: (tournament_session_id, team_id)

### TournamentSessionTeamAnswer

Відповідь команди на конкретне запитання в ігровій сесії. Зберігає результат, дані спірної відповіді та статус зняття запитання. Окремої сутності `Dispute` немає: текст, статус і коментар спірної зберігаються в цій сутності.

**Файл:** `src/Classic/Entity/TournamentSessionTeamAnswer.php`

#### Поля

| Поле | Тип | Nullable | Примітка |
|------|-----|----------|----------|
| id | int | — | PK, auto |
| questionNumber | int | — | |
| isCorrect | bool | — | |
| disputeText | string(500) | ✓ | |
| disputeStatus | enum | ✓ | `App\Classic\Enum\DisputeStatus` |
| disputeComment | string(500) | ✓ | |
| isQuestionRemoved | bool | — | початкове значення PHP: false |

#### Зв'язки

| Поле | Тип | Ціль | Nullable | Примітка |
|------|-----|------|----------|----------|
| tournamentSessionTeam | ManyToOne | TournamentSessionTeam | — | inversedBy: answers |

#### Обмеження

- **UQ_session_team_question**: (tournament_session_team_id, question_number)

### TournamentSessionTeamPlayer

Участь конкретного гравця в команді на ігровій сесії. Фіксує роль гравця (капітан, легіонер).

**Файл:** `src/Classic/Entity/TournamentSessionTeamPlayer.php`

#### Поля

| Поле | Тип | Nullable | Примітка |
|------|-----|----------|----------|
| id | int | — | PK, auto |
| isLegionary | bool | — | початкове значення PHP та DEFAULT БД (ORM): false |
| isCaptain | bool | — | початкове значення PHP та DEFAULT БД (ORM): false |

#### Зв'язки

| Поле | Тип | Ціль | Nullable | Примітка |
|------|-----|------|----------|----------|
| tournamentSessionTeam | ManyToOne | TournamentSessionTeam | — | |
| player | ManyToOne | Player | — | 🔗 Common |

#### Обмеження

- **UQ_session_team_player**: (tournament_session_team_id, player_id)

## Enum-и

Значення нижче — рядки, що зберігаються в полях сутностей. Файли розташовані в `src/Common/Enum` та `src/Classic/Enum` відповідно до модуля.

| Модуль | Enum | Значення | Початкове значення PHP у сутності |
|--------|------|----------|----------------------------------|
| Common | PlayerClaimStatus | `pending`, `approved`, `rejected` | `pending` |
| Classic | CaptainClaimStatus | `pending`, `approved`, `rejected` | `pending` |
| Classic | SessionClaimStatus | `pending`, `approved`, `rejected`, `revoked` | `pending` |
| Classic | TournamentModerationStatus | `pending`, `approved`, `rejected` | `pending` |
| Classic | TournamentStatus | `draft`, `published` | `draft` |
| Classic | DisputeStatus | `created`, `submitted`, `accepted`, `rejected` | `null` (немає спірної) |
| Classic | AppealStatus | `pending`, `accepted`, `rejected` | `pending` |
| Classic | AppealType | `accept`, `remove` | не задано |
| Classic | TeamPlayerTransferType | `joined`, `left` | не задано |
| Classic | TournamentOfficialRole | `organizer`, `editor`, `game_jury`, `appeal_jury` | не задано; співорганізатор також має `organizer` |
| Classic | TournamentFormat | `centralized`, `distributed` | `distributed` |
| Classic | TournamentOnlineMode | `online`, `offline`, `mixed` | `mixed` |

Службові enum-и не є станами, збереженими в полях турніру:

| Модуль | Enum | Значення | Призначення |
|--------|------|----------|-------------|
| Classic | TournamentPeriod | `past`, `active`, `future` | фільтр списку турнірів |
| Classic | ResolveAction | `accept`, `reject` | вхідна дія розгляду |
| Common | CacheTag | `countries`, `towns`, `tournament_list`, `venues`, `moderation_counts` | інфраструктурні теги кешу |

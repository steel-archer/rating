# Карта Doctrine-сутностей

Довідкова карта всіх Doctrine-сутностей проєкту, згрупованих за модулями. Використовується як контекст для розуміння доменної моделі.

## Common

### Country

Довідник країн.

**Файл:** `src/Common/Entity/Country.php`

#### Поля

| Поле | Тип | Nullable | Примітка |
|------|-----|----------|----------|
| id | int | — | PK, auto |
| name | string(255) | — | |

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
| townName | string(255) | ✓ | |
| status | enum | — | `App\Common\Enum\PlayerClaimStatus` |

#### Зв'язки

| Поле | Тип | Ціль | Nullable | Примітка |
|------|-----|------|----------|----------|
| user | ManyToOne | User | — | |
| player | ManyToOne | Player | ✓ | |
| town | ManyToOne | Town | ✓ | |

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

**Таблиця:** `user`

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
| player | OneToOne | Player | ✓ | inversedBy: user |

#### Обмеження

- **UNIQ_user_email**: (email)
- **UNIQ_user_google_id**: (google_id)
- **UNIQ_user_player**: (player_id)

### Venue

Місце проведення ігор (бар, клуб, зал). Прив'язане до міста, потребує підтвердження.

**Файл:** `src/Common/Entity/Venue.php`

#### Поля

| Поле | Тип | Nullable | Примітка |
|------|-----|----------|----------|
| id | int | — | PK, auto |
| name | string(255) | — | |
| isApproved | bool | — | default: false |
| createdAt | DateTimeImmutable | — | |

#### Зв'язки

| Поле | Тип | Ціль | Nullable | Примітка |
|------|-----|------|----------|----------|
| town | ManyToOne | Town | — | |
| createdBy | ManyToOne | Player | ✓ | |

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

### Appeal

Апеляція на відповідь команди в турнірній сесії. Може бути на зарахування або зняття відповіді.

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

Заявка на проведення ігрової сесії. Гравець подає заявку на конкретну турнірну сесію.

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
| isCaptain | bool | — | default: false |

#### Зв'язки

| Поле | Тип | Ціль | Nullable | Примітка |
|------|-----|------|----------|----------|
| team | ManyToOne | Team | — | |
| player | ManyToOne | Player | — | 🔗 Common |
| season | ManyToOne | Season | — | 🔗 Common |

#### Обмеження

- **UQ_player_season**: (player_id, season_id)

### Tournament

Турнір із запитань «Що? Де? Коли?». Центральна сутність модуля Classic — об'єднує сесії, команди та результати.

**Файл:** `src/Classic/Entity/Tournament.php`

#### Поля

| Поле | Тип | Nullable | Примітка |
|------|-----|----------|----------|
| id | int | — | PK, auto |
| name | string(255) | — | |
| status | enum | — | `App\Classic\Enum\TournamentStatus` |
| startedAt | DateTimeImmutable | ✓ | |
| endedAt | DateTimeImmutable | ✓ | |
| resultsHiddenUntil | DateTimeImmutable | ✓ | |
| toursCount | int | ✓ | |
| questionsPerTour | int | ✓ | |
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
| createdAt | DateTimeImmutable | — | |
| updatedAt | DateTimeImmutable | — | |

#### Зв'язки

| Поле | Тип | Ціль | Nullable | Примітка |
|------|-----|------|----------|----------|
| tournament | ManyToOne | Tournament | — | |
| venue | ManyToOne | Venue | — | 🔗 Common |
| representative | ManyToOne | Player | — | 🔗 Common |
| host | ManyToOne | Player | ✓ | 🔗 Common |

### TournamentSessionTeam

Участь команди в конкретній ігровій сесії турніру. Зберігає рахунок команди та статус подання результатів.

**Файл:** `src/Classic/Entity/TournamentSessionTeam.php`

#### Поля

| Поле | Тип | Nullable | Примітка |
|------|-----|----------|----------|
| id | int | — | PK, auto |
| score | int | — | default: 0 |
| resultsSubmitted | bool | — | default: false |
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

Відповідь команди на конкретне запитання в ігровій сесії. Зберігає результат, дані спірки та статус зняття запитання.

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
| isQuestionRemoved | bool | — | default: false |

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
| isLegionary | bool | — | default: false |
| isCaptain | bool | — | default: false |

#### Зв'язки

| Поле | Тип | Ціль | Nullable | Примітка |
|------|-----|------|----------|----------|
| tournamentSessionTeam | ManyToOne | TournamentSessionTeam | — | |
| player | ManyToOne | Player | — | 🔗 Common |

#### Обмеження

- **UQ_session_team_player**: (tournament_session_team_id, player_id)

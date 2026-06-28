# Implementation Plan: Steering-файл карти Doctrine-сутностей

## Overview

Створення файлу `.kiro/steering/entities.md` з описом усіх 20 Doctrine-сутностей проєкту. Кожна сутність описується за єдиним шаблоном: заголовок H3, короткий опис, шлях до файлу, таблиця полів, таблиця зв'язків, обмеження (якщо є). Реалізація розбита на етапи: створення структури файлу, опис Common-сутностей, опис Classic-сутностей, фінальна перевірка.

## Tasks

- [x] 1. Створити файл зі структурою та заголовками
  - [x] 1.1 Створити файл `.kiro/steering/entities.md` із заголовком H1 та секціями H2 для модулів
    - Створити файл за шляхом `.kiro/steering/entities.md`
    - Додати заголовок першого рівня з описом призначення файлу
    - Додати заголовок другого рівня `## Common` для першого модуля
    - Додати заголовок другого рівня `## Classic` для другого модуля
    - Файл не повинен містити front-matter
    - Файл повинен мати новий рядок в кінці
    - _Requirements: 1.1, 1.2, 1.3, 7.1, 7.2, 7.3_

- [x] 2. Описати сутності модуля Common
  - [x] 2.1 Описати сутність Country
    - Прочитати файл `src/Common/Entity/Country.php`
    - Додати H3 заголовок, опис, шлях до файлу
    - Створити таблицю полів (Поле, Тип, Nullable, Примітка)
    - Додати обмеження якщо є
    - _Requirements: 1.4, 2.1, 2.2, 3.1, 3.2, 3.3, 5.1, 8.1_

  - [x] 2.2 Описати сутність Player
    - Прочитати файл `src/Common/Entity/Player.php`
    - Додати H3 заголовок, опис, шлях до файлу
    - Створити таблицю полів та таблицю зв'язків
    - _Requirements: 1.4, 2.1, 2.2, 3.1, 3.2, 3.3, 4.1, 4.2, 4.3, 8.1_

  - [x] 2.3 Описати сутність PlayerClaim
    - Прочитати файл `src/Common/Entity/PlayerClaim.php`
    - Додати H3 заголовок, опис, шлях до файлу
    - Створити таблицю полів (включно з enum PlayerClaimStatus) та таблицю зв'язків
    - Для enum-поля вказати повний FQCN класу
    - _Requirements: 1.4, 2.1, 2.2, 3.1, 3.2, 3.3, 3.4, 4.1, 4.2, 4.3, 8.1_

  - [x] 2.4 Описати сутність Season
    - Прочитати файл `src/Common/Entity/Season.php`
    - Додати H3 заголовок, опис, шлях до файлу
    - Створити таблицю полів
    - _Requirements: 1.4, 2.1, 2.2, 3.1, 3.2, 3.3, 8.1_

  - [x] 2.5 Описати сутність Town
    - Прочитати файл `src/Common/Entity/Town.php`
    - Додати H3 заголовок, опис, шлях до файлу
    - Створити таблицю полів та таблицю зв'язків
    - Додати UniqueConstraint UQ_town_name_country
    - _Requirements: 1.4, 2.1, 2.2, 3.1, 3.2, 3.3, 4.1, 4.2, 4.3, 5.1, 8.1_

  - [x] 2.6 Описати сутність User
    - Прочитати файл `src/Common/Entity/User.php`
    - Додати H3 заголовок, опис, шлях до файлу
    - Додати рядок `**Таблиця:** \`user\`` (зарезервоване слово)
    - Створити таблицю полів (включно з json-полем roles) та таблицю зв'язків
    - Додати 3 UniqueConstraints (email, google_id, player)
    - _Requirements: 1.4, 2.1, 2.2, 2.3, 3.1, 3.2, 3.3, 4.1, 4.2, 4.3, 5.1, 8.1_

  - [x] 2.7 Описати сутність Venue
    - Прочитати файл `src/Common/Entity/Venue.php`
    - Додати H3 заголовок, опис, шлях до файлу
    - Створити таблицю полів та таблицю зв'язків
    - Додати UniqueConstraint UQ_venue_name_town
    - _Requirements: 1.4, 2.1, 2.2, 3.1, 3.2, 3.3, 4.1, 4.2, 4.3, 5.1, 8.1_

  - [x] 2.8 Описати сутність VenueRepresentative
    - Прочитати файл `src/Common/Entity/VenueRepresentative.php`
    - Додати H3 заголовок, опис, шлях до файлу
    - Створити таблицю полів та таблицю зв'язків
    - Додати UniqueConstraint UQ_venue_player
    - _Requirements: 1.4, 2.1, 2.2, 3.1, 3.2, 3.3, 4.1, 4.2, 4.3, 5.1, 8.1_

- [x] 3. Checkpoint — Перевірити Common-модуль
  - Ensure all tests pass, ask the user if questions arise.

- [x] 4. Описати сутності модуля Classic
  - [x] 4.1 Описати сутність Appeal
    - Прочитати файл `src/Classic/Entity/Appeal.php`
    - Додати H3 заголовок, опис, шлях до файлу
    - Створити таблицю полів (включно з enum AppealType, AppealStatus) та таблицю зв'язків
    - Для enum-полів вказати повні FQCN класів
    - Додати UniqueConstraint UQ_appeal_answer
    - _Requirements: 1.4, 2.1, 2.2, 3.1, 3.2, 3.3, 3.4, 4.1, 4.2, 4.3, 5.1, 8.2_

  - [x] 4.2 Описати сутність SessionClaim
    - Прочитати файл `src/Classic/Entity/SessionClaim.php`
    - Додати H3 заголовок, опис, шлях до файлу
    - Створити таблицю полів (включно з enum SessionClaimStatus) та таблицю зв'язків
    - Позначити крос-модульний зв'язок з Player (🔗 Common)
    - Додати UniqueConstraint UNIQ_sc_session
    - _Requirements: 1.4, 2.1, 2.2, 3.1, 3.2, 3.3, 3.4, 4.1, 4.2, 4.3, 4.4, 5.1, 8.2_

  - [x] 4.3 Описати сутність Team
    - Прочитати файл `src/Classic/Entity/Team.php`
    - Додати H3 заголовок, опис, шлях до файлу
    - Створити таблицю полів та таблицю зв'язків
    - Позначити крос-модульний зв'язок з Town (🔗 Common)
    - _Requirements: 1.4, 2.1, 2.2, 3.1, 3.2, 3.3, 4.1, 4.2, 4.3, 4.4, 8.2_

  - [x] 4.4 Описати сутність TeamPlayer
    - Прочитати файл `src/Classic/Entity/TeamPlayer.php`
    - Додати H3 заголовок, опис, шлях до файлу
    - Створити таблицю полів та таблицю зв'язків
    - Позначити крос-модульні зв'язки з Player та Season (🔗 Common)
    - Додати UniqueConstraint UQ_player_season
    - _Requirements: 1.4, 2.1, 2.2, 3.1, 3.2, 3.3, 4.1, 4.2, 4.3, 4.4, 5.1, 8.2_

  - [x] 4.5 Описати сутність Tournament
    - Прочитати файл `src/Classic/Entity/Tournament.php`
    - Додати H3 заголовок, опис, шлях до файлу
    - Створити таблицю полів (включно з enum TournamentStatus) та таблицю зв'язків
    - Позначити крос-модульні зв'язки з Player та Season (🔗 Common)
    - _Requirements: 1.4, 2.1, 2.2, 3.1, 3.2, 3.3, 3.4, 4.1, 4.2, 4.3, 4.4, 8.2_

  - [x] 4.6 Описати сутність TournamentDocument
    - Прочитати файл `src/Classic/Entity/TournamentDocument.php`
    - Додати H3 заголовок, опис, шлях до файлу
    - Створити таблицю полів та таблицю зв'язків
    - _Requirements: 1.4, 2.1, 2.2, 3.1, 3.2, 3.3, 4.1, 4.2, 4.3, 8.2_

  - [x] 4.7 Описати сутність TournamentModerationClaim
    - Прочитати файл `src/Classic/Entity/TournamentModerationClaim.php`
    - Додати H3 заголовок, опис, шлях до файлу
    - Створити таблицю полів (включно з enum TournamentModerationStatus) та таблицю зв'язків
    - Додати UniqueConstraint UNIQ_tmc_tournament
    - _Requirements: 1.4, 2.1, 2.2, 3.1, 3.2, 3.3, 3.4, 4.1, 4.2, 4.3, 5.1, 8.2_

  - [x] 4.8 Описати сутність TournamentOfficial
    - Прочитати файл `src/Classic/Entity/TournamentOfficial.php`
    - Додати H3 заголовок, опис, шлях до файлу
    - Створити таблицю полів (включно з enum TournamentOfficialRole) та таблицю зв'язків
    - Позначити крос-модульний зв'язок з Player (🔗 Common)
    - Додати UniqueConstraint UQ_tournament_player_role
    - _Requirements: 1.4, 2.1, 2.2, 3.1, 3.2, 3.3, 3.4, 4.1, 4.2, 4.3, 4.4, 5.1, 8.2_

  - [x] 4.9 Описати сутність TournamentSession
    - Прочитати файл `src/Classic/Entity/TournamentSession.php`
    - Додати H3 заголовок, опис, шлях до файлу
    - Створити таблицю полів та таблицю зв'язків
    - Позначити крос-модульні зв'язки з Venue, Player (🔗 Common)
    - _Requirements: 1.4, 2.1, 2.2, 3.1, 3.2, 3.3, 4.1, 4.2, 4.3, 4.4, 8.2_

  - [x] 4.10 Описати сутність TournamentSessionTeam
    - Прочитати файл `src/Classic/Entity/TournamentSessionTeam.php`
    - Додати H3 заголовок, опис, шлях до файлу
    - Створити таблицю полів та таблицю зв'язків
    - Додати UniqueConstraint UQ_session_team
    - _Requirements: 1.4, 2.1, 2.2, 3.1, 3.2, 3.3, 4.1, 4.2, 4.3, 5.1, 8.2_

  - [x] 4.11 Описати сутність TournamentSessionTeamAnswer
    - Прочитати файл `src/Classic/Entity/TournamentSessionTeamAnswer.php`
    - Додати H3 заголовок, опис, шлях до файлу
    - Створити таблицю полів (включно з enum DisputeStatus) та таблицю зв'язків
    - Додати UniqueConstraint UQ_session_team_question
    - _Requirements: 1.4, 2.1, 2.2, 3.1, 3.2, 3.3, 3.4, 4.1, 4.2, 4.3, 5.1, 8.2_

  - [x] 4.12 Описати сутність TournamentSessionTeamPlayer
    - Прочитати файл `src/Classic/Entity/TournamentSessionTeamPlayer.php`
    - Додати H3 заголовок, опис, шлях до файлу
    - Створити таблицю полів та таблицю зв'язків
    - Позначити крос-модульний зв'язок з Player (🔗 Common)
    - Додати UniqueConstraint UQ_session_team_player
    - _Requirements: 1.4, 2.1, 2.2, 3.1, 3.2, 3.3, 4.1, 4.2, 4.3, 4.4, 5.1, 8.2_

- [x] 5. Фінальна перевірка та валідація
  - [x] 5.1 Перевірити повноту та коректність файлу
    - Переконатися, що файл містить усі 20 сутностей (8 Common + 12 Classic)
    - Перевірити що файл не містить front-matter
    - Перевірити що файл починається з H1 заголовка
    - Перевірити що файл має новий рядок в кінці
    - Перевірити що описи українською, а назви класів/полів/типів — англійською
    - Оцінити розмір файлу (має бути ~4–6 KB)
    - _Requirements: 6.2, 6.3, 6.4, 7.1, 7.2, 7.3, 8.1, 8.2_

- [x] 6. Фінальний checkpoint
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Кожна задача передбачає читання відповідного Entity-файлу перед написанням опису
- Таблиці полів використовують формат: Поле | Тип | Nullable | Примітка
- Таблиці зв'язків використовують формат: Поле | Тип | Ціль | Nullable | Примітка
- Крос-модульні зв'язки (Classic → Common) позначаються як 🔗 Common
- Nullable позначається як ✓, not-nullable — як —
- Enum-поля містять повний FQCN класу в колонці Примітка
- Checkpoints забезпечують інкрементальну валідацію

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1"] },
    { "id": 1, "tasks": ["2.1", "2.2", "2.3", "2.4", "2.5", "2.6", "2.7", "2.8"] },
    { "id": 2, "tasks": ["4.1", "4.2", "4.3", "4.4", "4.5", "4.6", "4.7", "4.8", "4.9", "4.10", "4.11", "4.12"] },
    { "id": 3, "tasks": ["5.1"] }
  ]
}
```

# Вимоги: Steering-файл карти Doctrine-сутностей

## Вступ

Створення steering-файлу `.kiro/steering/entities.md`, який слугує довідковою картою всіх Doctrine-сутностей проєкту. Файл автоматично включається в контекст Kiro при кожній взаємодії, забезпечуючи розуміння доменної моделі без необхідності читати кожен Entity-файл окремо.

## Глосарій

- **Steering_File**: Markdown-файл у директорії `.kiro/steering/`, який автоматично включається в контекст Kiro при всіх взаємодіях
- **Entity_Map**: Структурований опис усіх Doctrine-сутностей проєкту з полями, зв'язками та призначенням
- **Module**: Один із двох модулів проєкту — Common або Classic
- **Entity**: Doctrine ORM клас, що відображає таблицю бази даних
- **Relationship**: Зв'язок між сутностями (ManyToOne, OneToOne, OneToMany, ManyToMany)

## Вимоги

### Вимога 1: Структура файлу

**User Story:** Як розробник, я хочу мати чітко структурований steering-файл, щоб Kiro швидко знаходив інформацію про потрібну сутність.

#### Критерії приймання

1. THE Steering_File SHALL містити заголовок першого рівня з описом призначення файлу
2. THE Steering_File SHALL групувати сутності за модулями (Common, Classic)
3. THE Steering_File SHALL містити для кожного модуля заголовок другого рівня з назвою модуля
4. THE Steering_File SHALL містити для кожної сутності заголовок третього рівня з назвою класу

### Вимога 2: Опис кожної сутності

**User Story:** Як розробник, я хочу бачити опис кожної сутності, щоб розуміти її роль у системі.

#### Критерії приймання

1. THE Entity_Map SHALL містити для кожної сутності короткий опис її призначення (1–2 речення)
2. THE Entity_Map SHALL вказувати шлях до файлу сутності відносно кореня проєкту
3. THE Entity_Map SHALL вказувати назву таблиці в базі даних, якщо вона відрізняється від стандартної Doctrine-конвенції

### Вимога 3: Опис ключових полів

**User Story:** Як розробник, я хочу бачити ключові поля сутності, щоб розуміти структуру даних без відкриття файлу.

#### Критерії приймання

1. THE Entity_Map SHALL перелічувати всі поля кожної сутності
2. THE Entity_Map SHALL вказувати тип кожного поля (string, int, bool, DateTimeImmutable, enum тощо)
3. THE Entity_Map SHALL вказувати nullable-статус поля, якщо поле є nullable
4. THE Entity_Map SHALL вказувати конкретний enum-клас для полів типу enum

### Вимога 4: Опис зв'язків між сутностями

**User Story:** Як розробник, я хочу бачити зв'язки між сутностями, щоб розуміти доменну модель та уникати помилок при написанні запитів.

#### Критерії приймання

1. THE Entity_Map SHALL описувати всі зв'язки сутності (ManyToOne, OneToOne, OneToMany)
2. THE Entity_Map SHALL вказувати цільову сутність кожного зв'язку
3. THE Entity_Map SHALL вказувати nullable-статус зв'язку
4. THE Entity_Map SHALL позначати зв'язки, що перетинають межі модулів (Classic → Common)

### Вимога 5: Унікальні обмеження та індекси

**User Story:** Як розробник, я хочу знати про унікальні обмеження сутностей, щоб уникати помилок при створенні записів.

#### Критерії приймання

1. THE Entity_Map SHALL перелічувати всі унікальні обмеження (UniqueConstraint) кожної сутності
2. THE Entity_Map SHALL перелічувати business-значущі індекси, що впливають на запити

### Вимога 6: Формат представлення

**User Story:** Як розробник, я хочу мати компактний але інформативний формат, щоб файл не був надмірно великим для контексту Kiro.

#### Критерії приймання

1. THE Steering_File SHALL використовувати markdown-таблиці для полів та зв'язків кожної сутності
2. THE Steering_File SHALL бути написаний українською мовою (описи, заголовки)
3. THE Steering_File SHALL зберігати назви класів, полів, типів англійською мовою (як у коді)
4. THE Steering_File SHALL мати новий рядок в кінці файлу

### Вимога 7: Розміщення та включення

**User Story:** Як розробник, я хочу щоб файл автоматично включався в контекст Kiro, щоб не потрібно було його активувати вручну.

#### Критерії приймання

1. THE Steering_File SHALL розміщуватися за шляхом `.kiro/steering/entities.md`
2. THE Steering_File SHALL не містити front-matter (для автоматичного включення за замовчуванням)
3. THE Steering_File SHALL починатися із заголовка першого рівня

### Вимога 8: Повнота покриття

**User Story:** Як розробник, я хочу мати опис УСІХ сутностей проєкту, щоб не було прогалин у контексті.

#### Критерії приймання

1. THE Entity_Map SHALL містити всі 8 сутностей модуля Common (Country, Player, PlayerClaim, Season, Town, User, Venue, VenueRepresentative)
2. THE Entity_Map SHALL містити всі 12 сутностей модуля Classic (Appeal, SessionClaim, Team, TeamPlayer, Tournament, TournamentDocument, TournamentModerationClaim, TournamentOfficial, TournamentSession, TournamentSessionTeam, TournamentSessionTeamAnswer, TournamentSessionTeamPlayer)

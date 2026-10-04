@AGENTS.md
@.kiro/steering/conventions.md

## Claude Code

- Перед зміною файлів прочитати відповідний документ із таблиці «Звідки брати
  правила» в `AGENTS.md`; `conventions.md` уже підключений вище.
- `.claude/settings.json` блокує прямий виклик PHPUnit — це навмисно (див.
  правило 1 в `AGENTS.md`). Тести запускати через `./bin/test.sh`.

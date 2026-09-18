# CURRENT-SCHEMA-TEST-CONTRACT-001 — актуальная schema frontier в PHP-тестах

## Простыми словами

Предметные тесты не должны копировать номер последней migration лишь ради подготовки актуальной БД. Один независимый тестовый контракт сообщает ожидаемую актуальную границу, а профильные migration/recovery проверки продолжают хранить точные исторические ожидания.

## Область

Актор — автор и reviewer PHP regression tests. Публичный seam — результаты `CanonicalMigrationApplication::run` и CLI migration runner на изолированной MariaDB. Production-поведение не меняется.

## Требования

### A1 — независимый current frontier

Test contract MUST задавать ожидаемую текущую версию и полный непрерывный список версий literal-значением, не вычисляя их из production catalogue, runner output или фактической БД. Профильный frontier test MUST отвергать каталог без последней migration и каталог с пропущенной промежуточной migration.

### A2 — current setup и replay

Выбранные предметные PHP-тесты, где актуальная версия нужна только для setup, MUST получать expected clean/current result из общего контракта. Replay MUST по-прежнему ожидать пустой `appliedVersions`; предметные rows, permissions, conflict, no-mutation, cleanup, prefix/database isolation и concurrency assertions MUST сохраниться.

### A3 — исторические контракты

Historical version, upgrade suffix, recovery bundle compatibility, exact current catalog/table inventory и migration-specific assertions MUST оставаться независимыми точными проверками. Общий current helper MUST NOT заменять их безусловным latest.

### A4 — границы изменения

Изменения MUST оставаться в PHP tests/helpers и минимальных process artifacts. `app/**`, DDL, production migrations/recovery, Python/Compose fixtures, verification classifier/lane/admission rules MUST NOT изменяться.

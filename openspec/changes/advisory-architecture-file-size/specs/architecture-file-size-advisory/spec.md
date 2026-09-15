## Purpose

Определяет публичный результат architecture checker, в котором размер файла остаётся наблюдаемым review-сигналом, но только содержательные нарушения блокируют delivery.

## ADDED Requirements

### Requirement: Size observations are advisory
Публичный architecture checker SHALL сообщать новый production-файл размером не менее 150 физических строк и рост baselined hotspot как `advisories`, включая пересечение 149 → 150 из-за комментария/пустой строки и rename/move большого файла. Одни size advisories MUST давать `ok=true` и exit `0`.

#### Scenario: Новый или выросший hotspot
- **WHEN** production-файл пересекает 150 строк, появляется большим после move/rename или растёт выше baselined size без meaningful violation
- **THEN** human output показывает size advisory, JSON содержит отдельный `advisories` array, `errors` пуст, а command завершается `0`

### Requirement: Meaningful architecture violations remain blocking
Checker MUST сохранять fail-closed SQL ownership, DDL/runtime-migration ownership, dependency direction, rapid-pilot boundary, session/workforce ownership и public-seam controls независимо от размера файла.

#### Scenario: Большой файл нарушает ownership
- **WHEN** большой production-файл содержит новый forbidden SQL, DDL/runtime migration или forbidden dependency
- **THEN** соответствующий finding находится в `errors`, size finding может находиться в `advisories`, `ok=false`, а exit ненулевой

### Requirement: Size metadata updates cannot accept architecture debt
Публичная операция обновления size metadata MUST изменять только size inventory и MUST NOT добавлять, удалять или заменять meaningful exception fingerprints либо public seams.

#### Scenario: Size update рядом с unrelated violation
- **WHEN** вызывается size metadata update, а current scan содержит новый unrelated architecture violation
- **THEN** записывается только актуальная size metadata, а meaningful baseline sections остаются byte-for-byte эквивалентны прежним значениям

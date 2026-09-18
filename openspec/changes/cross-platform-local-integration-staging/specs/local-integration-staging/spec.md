## Purpose

Обеспечивает безопасную и переносимую передачу локальной integration-конфигурации из `.env` штатным non-root импортёрам без зависимости от совпадения host/container UID.

## ADDED Requirements

### Requirement: Accessible cross-platform host staging
Local integration staging SHALL принимать доступные обычные input, destination и staging directory независимо от exact POSIX mode bits. Symlink, non-regular input/destination, non-directory staging path, невалидный формат и фактическая недоступность MUST отклоняться до запуска downstream consumer. Публикация destination MUST быть атомарной и не менять owner или permissions исходного `.env`.

#### Scenario: Non-exact modes accepted
- **WHEN** валидный `.env` имеет mode `0644`, а существующий staging directory — `0755`
- **THEN** snapshot успешно и атомарно публикуется
- **THEN** исходный `.env` сохраняет прежние owner и mode

#### Scenario: Unsafe input rejected
- **WHEN** input или destination является symlink/non-regular path либо документ имеет запрещённый формат
- **THEN** staging завершается ошибкой до запуска импортёра
- **THEN** прежний полный destination не заменяется и временные файлы отсутствуют

### Requirement: Private container delivery across UID boundary
`make import-legacy` и `make sync-workforce` MUST передавать новый подготовленный snapshot в обычный private container file, читаемый штатным non-root runtime UID, даже когда host snapshot имеет mode `0600` и принадлежит другому UID. Секреты MUST NOT передаваться в argv, stdout/stderr, Compose rendering, image layers или application logs.

#### Scenario: Different host and runtime UID
- **WHEN** host snapshot принадлежит UID, отличному от UID 10001 runtime container, и имеет mode `0600`
- **THEN** соответствующий Make-target запускает importer как UID 10001
- **THEN** importer реально читает подготовленные значения из container-side private regular file

#### Scenario: Both public targets use delivery
- **WHEN** оператор запускает `make import-legacy` или `make sync-workforce`
- **THEN** каждый target проходит `.env` → host staging → Compose delivery → штатный PHP loader → importer
- **THEN** target не использует ambient integration secrets или тестовый обходной loader

### Requirement: Replay and failure-safe cleanup
Каждый запуск MUST доставлять текущий полный snapshot, не использовать старые значения и удалять container-side временный secret после успеха или ошибки. Cleanup MUST сохранять исходный ненулевой exit status importer. Конкурентная/прерванная публикация MUST NOT оставлять частичный config.

#### Scenario: Updated configuration replaces old snapshot
- **WHEN** после успешного запуска оператор меняет synthetic configuration и повторяет тот же Make-target
- **THEN** importer наблюдает только новые значения
- **THEN** старое значение и лишние temporary secrets отсутствуют

#### Scenario: Importer failure remains failure
- **WHEN** importer возвращает ненулевой exit status после чтения config
- **THEN** Make-target возвращает ненулевой status
- **THEN** cleanup не заменяет его success и container-side temporary secret удаляется

### Requirement: Synthetic end-to-end acceptance
Проверка SHALL использовать изолированную MariaDB и локальный synthetic Bitrix endpoint, MUST подтверждать наблюдаемые legacy/workforce facts через действующие public Make seams и MUST NOT обращаться к production systems.

#### Scenario: Isolated import and sync
- **WHEN** оба Make-target запускаются с synthetic `.env`, isolated MariaDB и local endpoint
- **THEN** legacy importer сохраняет ожидаемые synthetic facts, workforce sync использует обновлённый local endpoint response
- **THEN** production адреса и credentials не используются

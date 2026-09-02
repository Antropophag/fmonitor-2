## Purpose

> **Target disposition, 2026-09-02:** prepared/registered card language below
> is implemented predecessor. Target card/download amendment belongs to
> `expose-assignment-order-original-http` and requires fresh Gate 1 approval.

Обеспечивает golden E2E для единственного versioned combined PDF распоряжения и приложения, сохраняя authorization, integrity, fault isolation и history contracts.

## ADDED Requirements

### Requirement: Version имеет один combined PDF artifact
Подготовленная версия распоряжения SHALL публиковать ровно один downloadable
artifact type `order` с media `application/pdf`, содержащий распоряжение и
приложение. Отдельный `appendix` HTML/PDF artifact MUST NOT требоваться или
создаваться как параллельный public contract.

#### Scenario: Successful combined download
- **WHEN** authorized actor скачивает artifact конкретных object/version
- **THEN** он получает status 200, `Content-Type: application/pdf`, filename
  `Распоряжение о закреплении монтажников.pdf`, bytes prefix `%PDF-`, exact
  stored Content-Length/SHA-256/bytes и три PDF page objects
- **AND** independent decoded streams содержат order title/object/team и appendix
  title/installer rows в утверждённом порядке; expected semantic markers не
  выводятся из download metadata/production output

#### Scenario: Missing legacy appendix не является ошибкой
- **WHEN** projection не содержит отдельный `appendix`
- **THEN** combined order download и fresh process projection остаются успешными

#### Scenario: Card содержит одну artifact link
- **WHEN** prepared/registered card показывает current version
- **THEN** она содержит ровно одну ссылку `Скачать распоряжение` на type `order`,
  не содержит `Скачать приложение`/type `appendix`, а GET и HEAD используют exact version URL

### Requirement: Authorization и integrity fail closed
Authorization SHALL выполняться до projection/store access. Missing/ambiguous
metadata, invalid filename/media/size/hash, absent/corrupt/inaccessible bytes или
filesystem identity drift MUST давать generic unavailable/not-found outcome без
раскрытия path, hash, SQL, object ID или exception и без process mutation.

#### Scenario: Unauthorized actor не наблюдает availability
- **WHEN** actor не имеет exact artifact read authority
- **THEN** GET/HEAD возвращает exact 403 `Access denied.\n`, no-store и base
  security headers, а store/projection не читаются

#### Scenario: Unknown или invalid metadata
- **WHEN** structurally valid version/type lookup отсутствует либо metadata/hash/size/filename/media integrity несовместимы
- **THEN** GET/HEAD возвращает exact 404 `Not found.\n` без availability details и partial bytes

#### Scenario: Inaccessible combined blob
- **WHEN** owned digest file или shard становится недоступным во время download
- **THEN** GET/HEAD возвращает exact 503 `Service unavailable.\n`, `Retry-After: 60`,
  no-store/base security headers без path/hash/object/exception; process
  projection/history и artifact bytes после восстановления неизменны

#### Scenario: HEAD parity
- **WHEN** authorized actor выполняет HEAD exact combined URL
- **THEN** status/application headers/Content-Length совпадают с GET, body empty,
  и выполняются те же authorization/metadata/store integrity reads

### Requirement: Repeat, reload и history воспроизводимы
Exact repeated download SHALL быть byte-identical и read-only. Fresh service/
connection после fault SHALL наблюдать ту же versioned metadata/history.
Concurrent reads MUST NOT создавать duplicate artifacts или audit/domain facts.

#### Scenario: Fresh reload after failed read
- **WHEN** transient read fault завершён и новый service загружает ту же version
- **THEN** combined PDF metadata/bytes совпадают с pre-fault snapshot и новых process events нет

#### Scenario: Two concurrent reads
- **WHEN** два authorized process выполняют GET одного exact artifact одновременно
- **THEN** оба получают byte-identical 200 PDF; before/after public process
  projection, full artifact metadata list, event list, DB counters и owned
  storage file identity/count неизменны

### Requirement: Fixture cleanup и dependency order точны
Main journey SHALL сначала доказать required RBAC/process-capability admissions,
затем combined-PDF contract. Каждый isolated fault/concurrency fixture SHALL
владеть explicit DB name/user, server process, session root и artifact root;
cleanup MUST остановить/reap process, restore fault, revoke/drop user/database и
удалить только task-owned roots даже при assertion failure.

#### Scenario: Failure before artifact assertion
- **WHEN** RBAC/setup prerequisite не пройден
- **THEN** result классифицируется prerequisite failure, а не combined-PDF RED,
  затем полный owned cleanup выполняется

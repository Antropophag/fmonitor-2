## Purpose

Фиксировать выполненный пункт инспекционного checklist как идемпотентный append-only факт с неизменяемой атрибуцией исполнителей и воспроизводимым аудитом.

## ADDED Requirements

### Requirement: HTTP adapter preserves deterministic result classes

The checklist HTTP adapter SHALL delegate only `item_completed` to the public
inspection-recording seam and SHALL map `ACCEPTED` to `accepted`, `DUPLICATE`
to `duplicate`, `STALE_REVISION` and `OPERATION_PAYLOAD_CONFLICT` to
`conflict`, `INSPECTION_SCHEMA_UNAVAILABLE` to the retryable infrastructure
path, and all other deterministic domain failures to `rejected`. It SHALL NOT
fall back to legacy item-completion SQL. Other checklist operation branches
remain unchanged.

The adapter SHALL use an explicit resolver seam to translate external
`objectId` to canonical `installationCaseId`; it SHALL NOT assume identifier
equality. Zero current cases maps to `CASE_NOT_FOUND` and `{status: rejected,
revision: 0}`. Multiple current cases or resolver/database failure throws
`PilotHttpInfrastructureUnavailable`. Schema unavailability throws that same
exception. The existing outer HTTP adapter renders it as HTTP 503 with
`status=retryable`. All non-infrastructure result shapes are exactly
`{status, revision}`.

#### Scenario: Application result is translated once

- **WHEN** the public inspection-recording seam returns a completion result
- **THEN** the adapter returns the corresponding HTTP result class and revision
- **AND** no legacy item-completion mutation executes

#### Scenario: External object identity is resolved explicitly

- **WHEN** `objectId` and `installationCaseId` differ
- **THEN** the resolver supplies the canonical case id to the command
- **AND** zero cases is rejected deterministically
- **AND** ambiguity or resolver failure uses the retryable infrastructure path

### Requirement: Authorized item completion is accepted once
Система SHALL принимать команду завершения одного пункта только от активного пользователя с exact capability `inspection.item.complete`, для открытого монтажного дела и пункта его неизменяемого template snapshot. Capability SHALL разрешать завершение пункта на любом объекте независимо от current control-engineer assignment. Назначенный инженер и фактический actor SHALL оставаться раздельными audit facts. Команда MUST содержать уникальный client operation id, ожидаемую revision, item id, actor id и непустой набор допустимых installer tab ids.

#### Scenario: First accepted completion
- **WHEN** инженер с capability `inspection.item.complete` отправляет операцию `11111111-1111-4111-8111-111111111111` для открытого дела 4512, item 28, expected revision 0 и installer 1042 из связанного snapshot
- **THEN** система возвращает accepted revision 1 и наблюдаемый projection показывает item выполненным с installer 1042

#### Scenario: Engineer completes an item for another engineer's object
- **GIVEN** открытое дело назначено инженеру 7302
- **WHEN** другой активный инженер 7301 с exact capability `inspection.item.complete` отправляет валидную команду
- **THEN** система принимает команду независимо от назначения
- **AND** audit сохраняет 7301 как фактического actor и 7302 как назначенного инженера без подмены одного факта другим

### Requirement: Offline authority is checked at server receipt
Система MUST при каждом server receipt, включая replay ранее поставленной в очередь offline-команды, повторно проверять active-user status и exact capability `inspection.item.complete`. Client `deviceTime` SHALL быть audit fact и MUST NOT определять authority. Смена current engineer assignment сама по себе SHALL NOT отклонять команду пользователя, который остаётся активным и сохраняет capability.

#### Scenario: Reassignment does not revoke broad engineer authority
- **GIVEN** offline-команда создана инженером 7301, после чего ответственным за объект назначен инженер 7302
- **WHEN** сервер получает команду и 7301 всё ещё активен и имеет `inspection.item.complete`
- **THEN** система принимает команду при выполнении остальных инвариантов
- **AND** сохраняет фактического actor, current assigned engineer, device time и server receipt time раздельно

#### Scenario: Revoked authority is not restored by earlier device time
- **GIVEN** offline-команда содержит `deviceTime` до блокировки пользователя или отзыва capability
- **WHEN** сервер получает её после блокировки пользователя или отзыва `inspection.item.complete`
- **THEN** система детерминированно отклоняет команду как unauthorized без mutation
- **AND** более ранний `deviceTime` не изменяет решение

### Requirement: Completion history and attribution are append-only
Система SHALL сохранять факт завершения, actor, client/server time, template identity/hash и snapshot каждого указанного монтажника без изменения прежних операций. Последующая смена текущего состава распоряжением MUST NOT менять атрибуцию уже принятого завершения.

#### Scenario: Crew changes after completion
- **WHEN** item был принят с installer 1042, а позднее актуальный состав изменён на installer 2048
- **THEN** исторический item остаётся атрибутирован installer 1042, а current-crew projection показывает новый состав отдельно

### Requirement: Exact replay is idempotent and conflicts fail closed
После повторной проверки current active status/exact capability, command syntax и v8 deployment precondition повтор команды с тем же client operation id и тем же normalized payload SHALL вернуть исходный accepted result без нового факта или revision до проверки изменяемых case/template/current-crew preconditions. Повтор id с иным payload SHALL вернуть operation conflict. Для нового operation id stale expected revision, неизвестный item/template, недопустимый installer или неоткрытое дело SHALL быть отклонены предметной причиной без mutation.

#### Scenario: Exact offline replay
- **WHEN** ранее принятая операция `11111111-1111-4111-8111-111111111111` повторена с тем же payload
- **THEN** система возвращает тот же accepted revision 1 и не добавляет operation или audit fact

#### Scenario: Exact replay survives later case and crew changes
- **GIVEN** операция `11111111-1111-4111-8111-111111111111` принята, после чего дело закрыто, template association изменена и installer 1042 больше не входит в current order
- **WHEN** всё ещё активный actor с exact capability повторяет exact normalized command
- **THEN** система возвращает исходный accepted revision 1 без mutation
- **AND** current mutable facts не заменяют historical replay result

#### Scenario: Operation id reused with different attribution
- **WHEN** `11111111-1111-4111-8111-111111111111` повторён с installer 2048 вместо 1042
- **THEN** система отклоняет команду как operation conflict и не меняет revision или history

#### Scenario: Concurrent commands share expected revision
- **WHEN** две разные операции одновременно предъявляют expected revision 0 для одного дела
- **THEN** ровно одна может получить revision 1, а другая отклоняется как stale revision без частичной записи

### Requirement: Canonical persistence ownership
Production schema для item completion SHALL принадлежать landed canonical migration v8. Этот behavior slice SHALL NOT добавлять migration version. HTTP/UI/rapid-pilot MUST вызывать один public application seam и MUST NOT выполнять business persistence SQL или schema-on-demand DDL для этой capability.

#### Scenario: Fresh migrated environment
- **WHEN** canonical migrations применены к чистой test database и HTTP adapter принимает item completion
- **THEN** команда проходит через application seam без runtime DDL, а `make architecture-check` не фиксирует нового DDL/SQL/dependency violation

#### Scenario: Missing v8 schema fails closed
- **WHEN** HTTP adapter принимает item completion без полной совместимой v8 inspection-evidence family
- **THEN** application boundary возвращает deployment/infrastructure failure
- **AND** не создаёт, не исправляет и не изменяет schema или business facts

### Requirement: Accepted evidence is observable through a public query
Application module SHALL предоставлять public read seam `InspectionEvidenceView::getItemCompletion` для наблюдения accepted immutable operation, actual actor, assigned engineer at receipt, device/server times, revisions, template identity и installer snapshots без repository/SQL side channel. Query SHALL быть read-only и SHALL NOT backfill исторические facts.

#### Scenario: Accepted audit is read through the application boundary
- **WHEN** операция завершения принята и вызывается query по её case id и client operation id
- **THEN** query возвращает actual actor и assigned engineer как разные значения, immutable template identity, accepted revision и ordered installer snapshots
- **AND** повторный query не изменяет operation, revision или attribution facts

### Requirement: Production composition is explicit and connection-safe
Production SHALL собирать command/query interfaces только через `ProductionInspectionEvidenceFactory::create(mysqli, ProductionInspectionEvidenceConfig, ?InspectionEvidenceClock)`, возвращающий `InspectionEvidenceApplication`. `InspectionEvidenceClock` SHALL иметь exact method `now(): DateTimeImmutable`; application SHALL форматировать instant как RFC3339 seconds `Y-m-d\TH:i:sP` с numeric offset, default clock SHALL использовать `Europe/Moscow`, а injected clock SHALL вызываться ровно один раз для first-time receipt и не вызываться для replay. Caller SHALL владеть lifecycle соединения, а application SHALL владеть command transaction на одном непередаваемом между workers connection. Factory MUST валидировать canonical ASCII prefix 0..25 bytes до DB access, устанавливать `utf8mb4` и MUST NOT выполнять DDL, schema repair или business mutation.

#### Scenario: Two workers compose independent application instances
- **WHEN** два concurrent worker создают application через factory с отдельными caller-owned connections, одинаковым valid prefix и fixed clock
- **THEN** оба вызывают те же public command/query interfaces
- **AND** ни один worker не закрывает чужое соединение и не делит transaction state

#### Scenario: Invalid prefix is rejected before database access
- **WHEN** config содержит 26-byte, non-ASCII или invalid-character prefix
- **THEN** factory возвращает configuration failure до connection access
- **AND** schema, ledger и business facts не изменяются

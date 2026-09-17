## Context

См. `proposal.md`. Текущий `PilotCaseImporter` создаёт только `fm2_installation_cases` и намеренно не переносит `responsstroicontrol`. Standalone assignment уже имеет один append-only application owner, а Yii2 IdentityAccess уже умеет приглашать пользователя, назначать local роли и безопасно активировать первый вход. Нужны связующий identity fact и отдельная migration orchestration; legacy `../fmonitor` и его БД остаются read-only.

## Goals / Non-Goals

**Goals:**

- доказуемое exact-ID сопоставление legacy user → local identity;
- preview/digest/apply/reconcile для уже импортированных объектов;
- повторное использование существующих IdentityAccess и ControlEngineerAssignment owners;
- отсутствие частичных фактов, скрытых replacement и legacy writes.

**Non-Goals:**

- общий импорт пользователей, passwords, legacy roles/rights или всех объектов;
- перенос распоряжений, applications, originals, opening/checklist/history;
- изменение `responsstroicontrol` или использование ФИО/email как authority;
- новая generic migration/event framework или логика в `rapid-pilot`.

## Decisions

### IdentityAccess владеет explicit legacy link

Добавляется минимальная append-only identity-link history с unique current projection: local user ID, legacy user ID, request/actor/time и bounded source snapshot. Public command принадлежит IdentityAccess и использует существующую `access.administer` admission. Для исправления создаётся новый fact с обязательной причиной; прямой UPDATE из UI запрещён.

Альтернатива хранить legacy ID в email/имени отвергнута как неоднозначная. Повторное использование одного numeric user ID в обеих системах отвергнуто: local identity namespace независим.

### InstallationProcess владеет migration orchestration, но не обходит assignment owner

Новый offline CLI читает legacy/object/link state и формирует canonical JSON preview с digest. Apply принимает файл preview и digest, повторно строит модель под transaction/locks и вызывает batch-capable public migration method того же ControlEngineerAssignment owner. Owner сохраняет migration provenance вместе с append-only lineage.

Альтернатива direct INSERT в assignment table отвергнута: она дублировала бы invariants и создала второй state-changing seam. Последовательные независимые commits отвергнуты из-за частичного переноса.

### Контур определяется target cases

Режим `all-imported` выбирает только IDs из canonical `fm2_installation_cases`; явный allowlist является их подмножеством. Legacy-only объекты никогда не создаются этим importer и получают `OBJECT_NOT_IMPORTED`. Это связывает #20 с уже существующим object import и не расширяет pilot eligibility.

### Preview является immutable operation input

Canonical ordering, schema version, selected IDs, normalized source identities и SHA-256 digest делают preview проверяемым. Apply не доверяет display fields и подтверждает digest плюс текущие DB facts. Изменение любого authority fact даёт stale/conflict вместо best-effort.

### Пользовательский путь остаётся существующим

Администратор предварительно приглашает user в `/pilot/admin/users`, назначает local role/permissions и подтверждает legacy link. Raw invitation показывается однократно существующим flow; legacy password никогда не читается. UI добавляет bounded link/status controls, а migration остаётся offline.

### Dependencies и architecture impact

Owning modules: IdentityAccess для link; InstallationProcess для preview/apply; ControlEngineerAssignment для mutation. Разрешены зависимости на canonical schema inspector, local user directory и legacy read adapters. `rapid-pilot` не получает adapter или domain logic. Architecture inventory/check обновляется только для новых CLI/owners и запрещает SQL mutation из controllers.

## Risks / Trade-offs

- [Legacy `responsstroicontrol` содержит устаревший user ID] → preview показывает inactive/missing и не применяет строку.
- [Связь подтверждена ошибочно] → correction остаётся отдельным audited fact; apply stale-check не допускает незаметной подмены.
- [Большой batch удерживает locks] → bounded batch size и детерминированный lock order; большее множество делится оператором на независимые previews.
- [Commit outcome неизвестен] → reconciliation по operation ID; UNKNOWN не считается успехом и не запускает автоматический повтор mutation.
- [Текущий assignment создан после preview] → whole batch conflict, существующий факт сохраняется.

## Migration Plan

1. Additive production migration добавляет identity-link и необходимые operation/provenance поля штатным migration runner; grants не выдаются автоматически.
2. Развернуть IdentityAccess link command/UI и создать/активировать нужных инженеров существующими invitation/role flows.
3. Оператор запускает preview для `all-imported` или явного allowlist, разбирает skipped/conflicts и фиксирует exact artifact вне checkout.
4. Apply выполняется только для утверждённого digest; reconciliation подтверждает каждый выбранный объект.
5. Rollback application code не удаляет таблицы или историю; уже применённые assignments остаются canonical и меняются только штатной assignment-командой.

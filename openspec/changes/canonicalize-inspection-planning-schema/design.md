## Context

См. `proposal.md` и
`docs/operations/inspection-planning-schema-evidence.md`. Две planning tables
сейчас создаются `RapidPilotInspectionSchedule::ensureSchema()` из scheduling
POST до security checks, Calendar, queue/control projections и disposable
bootstrap. DDL состоит из отдельных auto-committed statements. Longest basename
имеет 36 ASCII bytes и family-local ceiling 28; composed catalogue уже требует
более строгую границу 25/26 из-за 39-byte classification-provenance basename.

Change зависит от фактически landed canonical catalogue through inspection
evidence. Catalogue v1–v8 landed; planning получает literal v9. Gate 2/RED
остаётся запрещён до fresh independent Gate 1 review и explicit owner approval.

## Goals / Non-Goals

**Goals:**

- Один canonical persistence owner для exact two-table family.
- Restartable exact-compatible partial recovery и atomic read-only conflict
  preflight.
- Populated preservation, explicit database-default collation и deterministic
  prefix/version/output contracts.
- Удаление planning-family DDL из всех runtime/read/bootstrap consumers.

**Non-Goals:**

- Cadence, first/next inspection date, reschedule/cancel/status lifecycle.
- Изменение authorization, assignment race, calendar visibility или event
  payload semantics.
- Добавление FK/CHECK, redesign event store или physical table rename.
- Implementation до approved executable spec и mandatory SSD/TDD gates.

## Decisions

### 1. Владение находится в deployment migration layer

Новый migration object входит в canonical runner как literal v9 после landed
inspection-evidence v8. Runtime scheduling остаётся временным rapid-pilot adapter и
consumer; application behavior не зависит от concrete MariaDB migration class.

Альтернатива — оставить `ensureSchema` — сохраняет request/read DDL и нарушает
fresh-deployment/security boundary.

### 2. Preflight всей family предшествует любой DDL

Состояния каждого member: absent или exact. Если все existing members exact,
missing members создаются в dependency-neutral deterministic order. Любое иное
состояние conflict с zero mutation. Это делает повтор после interrupted
per-statement DDL безопасным без destructive rollback.

Canonical runner не имеет lock или ledger. Change требует single-runner
deployment orchestration и не расширяет scope до cross-runner serialization.

Альтернатива all-or-none fail-closed требует ручного repair после transient
interrupt; `CREATE IF NOT EXISTS` без fingerprint скрывает несовместимую schema.

### 3. Exact fingerprint нормализует environment metadata

Сравниваются ordinal column metadata, SQL defaults/generated state, indexes,
constraints, engine, charset и exact target database-default utf8mb4 collation.
Database default читается и allowlist/membership-валидируется до DDL, затем явно
emitted с safe quoting. Auto-increment next values и generated presentation text
не являются schema conflict и сохраняются.

### 4. Composed prefix ceiling равен 25; family-local остаётся 28

Physical basename не переименовывается: rename увеличил бы scope и потребовал
compatibility adapter. Composed runner принимает 0..25 ASCII bytes; 26+
отклоняются до DB connection/access. Production empty prefix не затронут.
Earlier standalone migration tests могут доказывать family-local 28/29, но
full-catalogue configuration contract определяется 39-byte provenance basename.

### 5. Существующие rows не мигрируют в новую semantics

Schedules и append-only events сохраняются byte-for-byte. Migration не
backfill-ит cadence/status/cancel facts и не выводит product meaning из текущего
unique key или `INSERT IGNORE`.

### 6. Architecture ratchet удаляет только owned-family DDL debt

Guard получает exact prohibition для planning-family runtime DDL. Общий debt
baseline уменьшается только на реально удалённые statements и не обновляется для
сокрытия нового DDL. `rapid-pilot` разрешены characterization и wiring к
canonical precondition, но не новая domain logic.

## Risks / Trade-offs

- [26–32 byte test prefixes перестанут работать в full runner] → reject до DB
  connection/access, документировать composed 25/26 и сохранить local 28/29
  только в direct-family evidence.
- [Два runner запущены одновременно] → topology не поддерживается этой change;
  orchestration запускает один runner, а отдельный lock/ledger требует своего
  approved slice.
- [MariaDB DDL interrupt оставит partial family] → exact-compatible restartable
  completion после full preflight.
- [Environment collation drift] → validated explicit database-default collation;
  differing existing collation conflict без conversion.
- [Удаление read-path DDL выявит неподготовленное deployment] → fail-closed
  infrastructure result и fresh canonical migration verification до startup.
- [Ownership inadvertently freezes pilot behavior] → behavioral clauses
  ограничены preservation characterization; cadence/auth остаются GRILL-001.

## Migration Plan

1. После landing predecessors зафиксировать exact version/catalogue и executable
   schema spec; получить owner approval.
2. Создать independent RED matrix для clean/repeat/partial/conflict/prefix,
   populated preservation и DDL-denied runtime.
3. После test review реализовать migration и register in canonical runner.
4. Перевести runtime consumers на exact schema precondition и удалить
   `ensureSchema` call sites/owned DDL.
5. Выполнить clean image/fresh lifecycle, characterization, architecture/full
   verification и independent code review.

Rollback не удаляет tables/data. До deployment schema version можно откатить
code. После применения version rollback — forward fix; destructive down
отсутствует.

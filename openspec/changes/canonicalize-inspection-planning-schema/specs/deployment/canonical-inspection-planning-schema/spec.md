## Purpose

Гарантирует, что fresh deployment создаёт совместимую inspection-planning
schema только canonical migrations до scheduling/calendar запросов и без
runtime DDL.

## ADDED Requirements

### Requirement: Canonical migration owns the inspection-planning family

Canonical production migration v9 SHALL создать exact schedules и
schedule-events tables после фактически landed catalogue v1–v8 и до первого
runtime consumer. Migration SHALL не seed-ить schedule rows или events.

#### Scenario: Clean deployment creates the exact empty family
- **WHEN** canonical runner применяется к clean compatible database
- **THEN** обе planning tables создаются с утверждёнными columns, defaults,
  indexes, constraints, engine, charset и database-default collation
- **AND** обе таблицы остаются пустыми
- **AND** runner сообщает literal schema version `9`

#### Scenario: Exact populated repeat is a no-op
- **WHEN** обе planning tables имеют exact fingerprint и содержат schedules и
  events
- **THEN** migration сообщает no-op
- **AND** не изменяет schema, rows, event payloads или auto-increment state

### Requirement: Compatible interrupted creation is restartable

Migration SHALL до DDL проверить всю существующую family и SHALL создать только
отсутствующий member, если каждый существующий member exact-compatible.

#### Scenario: Exact schedules table exists and events table is absent
- **WHEN** schedules table exact-compatible, а events table отсутствует
- **THEN** migration создаёт только events table
- **AND** сохраняет все schedules rows и auto-increment state

#### Scenario: Exact events table exists and schedules table is absent
- **WHEN** events table exact-compatible, а schedules table отсутствует
- **THEN** migration создаёт только schedules table
- **AND** сохраняет все event rows и auto-increment state

### Requirement: Incompatible planning schema fails closed before mutation

Migration MUST выполнить family-wide read-only preflight. Несовместимый column,
default/generated metadata, index, constraint, engine, charset или collation
SHALL вернуть `SCHEMA_MIGRATION_CONFLICT` до первой mutation.

#### Scenario: Conflict coexists with an absent sibling
- **WHEN** один existing member несовместим, а второй отсутствует
- **THEN** migration детерминированно сообщает conflicting exact prefixed name
- **AND** отсутствующая table не создаётся и existing schema/data не меняются

#### Scenario: Another prefix contains incompatible decoys
- **WHEN** configured family compatible, а unprefixed или иначе prefixed names
  несовместимы
- **THEN** migration инспектирует и изменяет только configured namespace
- **AND** decoy schema, rows и counters неизменны

### Requirement: Canonical prefix remains valid for every planning identifier

После регистрации planning migration canonical runner MUST принимать только
ASCII `tablePrefix` длиной не более 25 bytes и MUST отклонять 26-byte, invalid
или non-ASCII prefix до database connection/access. Ограничение относится ко
всему composed runner, а не только к planning step. Собственная граница planning
family равна 28 bytes при 36-byte basename и остаётся explanatory arithmetic.

#### Scenario: Boundary prefix is accepted
- **WHEN** operator запускает composed runner с valid 25-byte prefix
- **THEN** каждое derived table/index identifier укладывается в MariaDB 64-byte
  limit
- **AND** planning family может быть создана в этом namespace

#### Scenario: Next-byte prefix is rejected
- **WHEN** operator передаёт valid-character prefix длиной 26 bytes
- **THEN** runner возвращает configuration failure до database connection
- **AND** не выполняет database access или schema mutation
- **AND** не раскрывает prefix или derived identifier в error output

### Requirement: Runtime planning paths perform no schema mutation

После canonical migration scheduling POST, Calendar, object queue,
construction-control и bootstrap consumers MUST NOT выполнять `CREATE`,
`ALTER`, `DROP` или schema repair для planning family.

#### Scenario: Existing scheduling behavior runs without DDL privilege
- **WHEN** migrated runtime principal имеет требуемые SELECT/INSERT права, но не
  имеет schema-mutation privilege
- **THEN** approved scheduling characterization и projections сохраняют текущие
  observable outcomes
- **AND** schema fingerprint остаётся неизменным

#### Scenario: Required planning schema is absent or incompatible
- **WHEN** runtime consumer встречает отсутствующую или несовместимую family
- **THEN** он fail closed через deployment/infrastructure boundary
- **AND** не создаёт, не исправляет и не seed-ит schema

#### Scenario: Public runtime outcomes are deterministic
- **WHEN** read-only readiness precondition отклоняет family
- **THEN** scheduling POST, Calendar и construction-control возвращают exact
  Gate 1 `503` UTF-8 responses без redirect/partial HTML/mutation
- **AND** object queue возвращает `503 text/plain` с fresh opaque 12-hex
  reference по exact Gate 1 regex без partial queue HTML/mutation
- **AND** Compose bootstrap завершается non-zero до ready publication и до
  fixture/import/product DML, не раскрывая sensitive configuration

### Requirement: Recovery does not invent runner concurrency or ledger

Migration SHALL быть restartable после прерывания между двумя auto-committed
CREATE, но SHALL NOT заявлять migration ledger или concurrent-runner guarantee,
которых нет в canonical runner. Deployment orchestration MUST не запускать два
public runner одновременно; cross-runner serialization является отдельным
change.

#### Scenario: Interrupted family resumes
- **WHEN** предыдущий single runner оставил один exact member и завершился
- **THEN** следующий single runner создаёт только отсутствующий member

### Requirement: Ownership migration does not approve scheduling semantics

Migration SHALL сохранять существующие schedule/event rows и consumer
compatibility, но SHALL не утверждать inspection cadence, reschedule,
cancellation, assignment-race, visibility или authorization policy.

#### Scenario: Product semantics remain in NEEDS_GRILL
- **WHEN** ownership change готов к review
- **THEN** `INSPECTION-SCHEDULE-001` и связанные behavior slices остаются
  блокированы GRILL-001 до отдельного owner decision
- **AND** ownership acceptance tests не используют UNKNOWN pilot observations
  как normative expected values

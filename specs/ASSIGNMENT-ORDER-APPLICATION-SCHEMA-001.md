# ASSIGNMENT-ORDER-APPLICATION-SCHEMA-001

Версия0.1,2026-09-07. Gate1 candidate.

## Простыми словами

Добавляем хранилище неизменяемых применений состава и попыток команды. Само
создание таблиц никого не назначает, не применяет PDF и не открывает работы.
Хранилище нужно уже одобренному ASSIGNMENT-ORDER-COMPOSITION-APPLY-001 v0.2.

## Public seam and configuration

Actor — deployment operator с synthetic/native DDL connection. Namespace
FMonitor2\InstallationProcess:
`AssignmentOrderApplicationSchemaMigration::apply(mysqli $db,string $prefix='',?AssignmentOrderApplicationSchemaObserver $observer=null):array`;
`AssignmentOrderApplicationSchemaMigration::isReady(mysqli $db,string $prefix=''):bool`.
apply returns exactly `['applied'=>true]` when creates missing family,
`['applied'=>false]` when complete compatible family already exists,
`['applied'=>false,'reason'=>'SCHEMA_MIGRATION_CONFLICT']` on incompatible state.
Runtime/lock/SQL/metadata/release failure throws fixed
`AssignmentOrderApplicationSchemaUnavailable`, message
`Assignment order application schema unavailable.`, code0/previousnull.
Invalid scalar configuration throws that same fixed exception before SQL.

PHP64bit, prefix `[A-Za-z0-9_]{0,25}`, selected database, native utf8mb4 connection,
utf8mb4 database default collation, idle autocommit1 connection. Не выполнять
implicit commit caller transaction; ambient transaction → unavailable без DDL/DML/
commit/rollback, с сохранением caller sentinel. Не менять database/charset/isolation/
session wait-policy. No FS/env/network/log/audit/console side effects.

Dependencies — compatible canonical predecessor schemas through actual frontier15,
including case/event tables, registry and selection family. apply/isReady use existing
read-only readiness contracts; не вызывают predecessor migrations/repairs. Этот engine
пока НЕ регистрируется в canonical runner и не получает version number: registration
после application/opening integration проходит explicit frontier/consumer gates.
Это ограничение engine slice, не исключение migration registration из launch.

## Family and exact columns

Две таблицы, имена с literal prefix, InnoDB, database default utf8mb4 collation.
Ни дополнительных колонок, generated columns, triggers, defaults, secondary
indexes или foreign keys сверх нижеуказанных. Nullable columns имеют NULL default;
остальные defaults отсутствуют. Только указанные primary IDs AUTO_INCREMENT.
Text columns с пометкой ASCII используют CHARACTER SET ascii COLLATE ascii_bin.
Snapshot JSON — LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin.

### fm2_assignment_order_applications

Колонки в точном порядке:

| name | type | nullable |
|---|---|---|
| application_id | BIGINT UNSIGNED AUTO_INCREMENT | no |
| installation_case_id | BIGINT UNSIGNED | no |
| object_id | BIGINT UNSIGNED | no |
| application_sequence | INT UNSIGNED | no |
| assignment_order_id | BIGINT UNSIGNED | no |
| order_version | SMALLINT UNSIGNED | no |
| original_revision_id | VARCHAR(80) ASCII | no |
| original_revision_number | INT UNSIGNED | no |
| document_date | DATE | no |
| composition_identity | VARCHAR(160) ASCII | no |
| composition_sha256 | CHAR(64) ASCII | no |
| control_engineer_user_id | BIGINT UNSIGNED | no |
| previous_application_id | BIGINT UNSIGNED | yes |
| kind | VARCHAR(20) ASCII | no |
| applied_at_utc | DATETIME(6) | no |
| applied_by_user_id | BIGINT UNSIGNED | no |
| request_id | CHAR(36) ASCII | no |
| request_fingerprint | CHAR(64) ASCII | no |
| expected_application_sequence | INT UNSIGNED | no |
| process_event_id | BIGINT UNSIGNED | no |
| selected_snapshot_json | LONGTEXT utf8mb4_bin | no |
| eligibility_snapshot_json | LONGTEXT utf8mb4_bin | no |

Indexes (literal logical symbol name, prefix prepended):
PRIMARY(application_id);
UNIQUE fm2_aoa_uq_case_seq(installation_case_id,application_sequence);
UNIQUE fm2_aoa_uq_id_case(application_id,installation_case_id);
UNIQUE fm2_aoa_uq_request(request_id);
UNIQUE fm2_aoa_uq_event(process_event_id);
KEY fm2_aoa_ix_order_case(assignment_order_id,installation_case_id);
KEY fm2_aoa_ix_previous_case(previous_application_id,installation_case_id).
All full-column ASC BTREE, active/nonignored.

Foreign keys, names prefixed, all ON UPDATE/DELETE RESTRICT, current schema only:
- fm2_aoa_fk_case: installation_case_id → fm2_installation_cases.id.
- fm2_aoa_fk_order: (assignment_order_id,installation_case_id) →
  fm2_assignment_order_identities.(assignment_order_id,installation_case_id).
- fm2_aoa_fk_previous: (previous_application_id,installation_case_id) →
  same family applications.(application_id,installation_case_id).
- fm2_aoa_fk_event: process_event_id → fm2_process_events.id.

CHECK names prefixed, exact semantic expressions:
- fm2_aoa_ck_ids: application_id,installation_case_id,object_id,assignment_order_id,
  control_engineer_user_id,applied_by_user_id,process_event_id each BETWEEN1AND9223372036854775807;
  previous_application_id IS NULL OR BETWEEN1AND9223372036854775807.
- fm2_aoa_ck_seq: application_sequence BETWEEN1AND2147483647 AND
  expected_application_sequence=application_sequence-1.
- fm2_aoa_ck_version: order_version BETWEEN1AND65535 AND original_revision_number>=1.
- fm2_aoa_ck_kind: kind IN('initial','new_order','reapplication') AND
  ((kind='initial' AND previous_application_id IS NULL AND application_sequence=1)
  OR (kind IN('new_order','reapplication') AND previous_application_id IS NOT NULL AND application_sequence>1)).
- fm2_aoa_ck_hash: composition_sha256 and request_fingerprint each REGEXP '^[0-9a-f]{64}$'.
- fm2_aoa_ck_request: request_id REGEXP '^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$'.
- fm2_aoa_ck_revision: original_revision_id REGEXP '^[A-Za-z0-9][A-Za-z0-9._:-]{0,79}$'.
- fm2_aoa_ck_composition: composition_identity REGEXP '^composition-[1-9][0-9]*-v[1-9][0-9]*$'.
- fm2_aoa_ck_selected: JSON_VALID(selected_snapshot_json) AND OCTET_LENGTH(selected_snapshot_json) BETWEEN2AND2097152.
- fm2_aoa_ck_eligibility: JSON_VALID(eligibility_snapshot_json) AND OCTET_LENGTH(eligibility_snapshot_json) BETWEEN2AND2097152.

JSON schema/coherence, exact selected crew/eligibility, original reference and current
authority/lifecycle are application checks, not migration-time business writers.
Migration never synthesizes applications to fill missing rows. Stable application
snapshot format is defined in APPLY-001; physical JSON carries selectedInstallers/
selectedEngineer separately from eligibility and header fields.

### fm2_assignment_order_application_attempts

| name | type | nullable |
|---|---|---|
| attempt_id | BIGINT UNSIGNED AUTO_INCREMENT | no |
| request_id | CHAR(36) ASCII | no |
| actor_user_id | BIGINT UNSIGNED | no |
| object_id | BIGINT UNSIGNED | no |
| assignment_order_id | BIGINT UNSIGNED | no |
| status | VARCHAR(20) ASCII | no |
| reason_code | VARCHAR(80) ASCII | yes |
| application_id | BIGINT UNSIGNED | yes |
| attempted_at_utc | DATETIME(6) | no |

Indexes PRIMARY(attempt_id), KEY fm2_aoaa_ix_request(request_id,attempt_id),
KEY fm2_aoaa_ix_application(application_id); full-column ASC BTREE/nonignored.
FK fm2_aoaa_fk_application: application_id → applications.application_id,
current schema, ON UPDATE/DELETE RESTRICT. No actor/object/order FK: audit also
records healthy denials for absent entities without fabricating domain rows.

CHECK:
- fm2_aoaa_ck_ids: attempt_id,actor_user_id,object_id,assignment_order_id each
  BETWEEN1AND9223372036854775807; application_id null or same positive range.
- fm2_aoaa_ck_request: same UUIDv4 pattern as application request_id.
- fm2_aoaa_ck_status: status IN('applied','replayed','rejected','conflict','failed').
- fm2_aoaa_ck_result: ((status IN('applied','replayed') AND reason_code IS NULL AND
  application_id IS NOT NULL) OR (status IN('rejected','conflict','failed') AND
  reason_code IS NOT NULL AND application_id IS NULL)).
- fm2_aoaa_ck_reason: reason_code IS NULL OR reason_code IN(all literal reasons
  of APPLY-001 v0.2 section7; copied without additional/unknown values).

## Migration lifecycle and preservation

Named lock `fm2_aoas_` + first48 lowercase hex SHA256 of database name + NUL +prefix,
GET_LOCK timeout5s. Always release only an acquired owned lock; failed release
makes outcome unavailable, never success. Caller pre-existing ownership of that
same named lock is rejected before acquisition (no recursion/leak). Lock contention
is unavailable, not schema conflict. Nullable observer is a trusted deployment/test instrumentation port:
`interface AssignmentOrderApplicationSchemaObserver { public function observe(AssignmentOrderApplicationSchemaPhase $phase):void; }`.
Phase backed enum values exactly lock_acquired, applications_created, attempts_created,
family_verified; calls occur after the named native event, never before. Defaultnull
имеет no-op behavior. No callbacks for invalid scalar/caller transaction/held lock.
Exception callback → unavailable с ordinary owned lock release; он не считается
успешным DDL acknowledgement. Observer не меняет production outcome/SQL по умолчанию;
synthetic tests используют его только для bounded coordination настоящих native
DDL/lock workers, не для подмены SQL/metadata/results.

isReady uses read-only inspection and takes
no named locks; false for missing/conflicting family/runtime failure, scalar invalid
configuration still throws fixed exception. It performs no DDL/DML/repair.

Under lock preflight ALL dependencies and both target shapes before any DDL:
- Both absent → create applications then attempts, verify exact complete shape.
- Applications exact+empty, attempts absent → supported interrupted-prefix resume;
  create attempts only, preserve header DDL and AUTO_INCREMENT state.
- Both exact, including populated state → appliedfalse, no DDL/DML/data rewrite.
  Shape readiness does not claim validity of arbitrary application-domain row content.
- Attempts without applications; partial nonempty applications; view/wrong engine/
  collation/column/order/nullability/index/FK/CHECK/trigger/dependency → conflict,
  zero DDL/DML on either existing target or predecessor tables.

Native DDL failure after first table may leave that empty exact table; return unavailable.
A subsequent call follows the supported empty-prefix resume rule. No DROP/ALTER of
existing tables, no data INSERT/UPDATE/DELETE, no version-row write, no grant changes.
All existing rows/DDL and AUTO_INCREMENT values of present tables remain exact;
new empty table counters begin1. isReady true iff both shapes and predecessors ready.
This slice's Done does not claim canonical runner registration or runtime application.

## Independent executable examples and gates

Synthetic native MariaDB fixture prepared via approved canonical1..15 path, prefixes
empty and25chars. Expected ordered family exactly two tables as above; primary tests
invoke public apply/isReady, not private SQL helpers. Independently literal DDL
inspection, indexes/FKs/CHECK metadata and constraint sensitivity cover whole contract.
Seeded complete exact family proves no-op preservation; malformed compatible-looking
families prove all-family preflight with zero changes. Real two-process named-lock
contention/release, caller-held lock, ambient transaction/sentinel and bounded native
partial-creation failure/resume are required. Never permission/OS-denial probes;
synthetic DDL-claim races may be used with explicit barriers/cleanup.

Gate1 → native intended RED after healthy predecessor setup → independent Gate3 →
minimal migration GREEN → native regressions/architecture/lint/diff → independent Gate5.
No actual Bitrix, production import, preview mutation, remote mutation or CI publication.

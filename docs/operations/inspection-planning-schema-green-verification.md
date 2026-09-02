# INSPECTION-PLANNING-SCHEMA-001 — Gate 4 GREEN verification

Дата: 2026-09-02  
Роль: implementation author  
Статус: **PARTIAL_GREEN; verification blocked by approved-test setup and unrelated concurrent changes**

## Реализовано

- Добавлен canonical owner `InspectionPlanningSchemaMigration` literal v9 с
  exact manifests, explicit validated database-default collation, read-only
  family-wide preflight, binary-sorted conflicts/results и restartable partial
  creation.
- v9 зарегистрирована сразу после v8; public runner сохраняет pre-DB prefix
  validation `0..25` ASCII bytes.
- Один read-only readiness seam используется scheduling POST, Calendar,
  ObjectQueue, construction-control и bootstrap.
- Runtime `ensureSchema` и два planning-family `CREATE TABLE` удалены.
  Bootstrap проверяет readiness до fixture/import/product DML и ready manifest.
- Architecture baseline уменьшен ровно на два удалённых DDL fingerprints и два
  соответствующих rapid-pilot mutation fingerprints; изменён fingerprint одного
  сохранённого planning SELECT после замены wiring.

## Выполненные команды

```text
$ php tests/InstallationProcess/inspection_planning_schema_001_test.php
PASS: INSPECTION-PLANNING-SCHEMA-001 migration matrix

$ make lint
exit 0

$ php tests/Verification/characterize_inspection_schedule_duplicate_001_test.php
INSPECTION_SCHEDULE created responses=1 schedules=1 events=1 history=exact
INSPECTION_SCHEDULE sequential-duplicate responses=2 schedules=1 events=1 mutations=0
INSPECTION_SCHEDULE rejections csrf=403 capability=403 invalid-date=422 ineligible-case=409 mutations=0
CHARACTERIZATION_OK CHARACTERIZE-INSPECTION-SCHEDULE-DUPLICATE-001
```

## Блокеры, не являющиеся production regression

`inspection_planning_runtime_ddl_001_test.php` не доходит до runtime assertions:
его `iprMigrate()` теперь корректно запускает public v1–v9 runner и создаёт
planning family, после чего test helper `iprCreatePlanning()` повторно выполняет
безусловный `CREATE TABLE`. Результат:

```text
SETUP_FAILURE: MariaDB runtime fixture:
Table 'healthy_fm2_pilot_inspection_schedules' already exists
```

Approved test/expectations не изменялись. Нужен отдельный test-author/reviewer
цикл для исправления setup так, чтобы fixture либо использовал созданную v9
family, либо подготавливал predecessors без повторного DDL.

Текущий `make architecture-check` дополнительно видит два unrelated concurrent
SQL findings в `app/IdentityAccess/MariaDbLocalAuthorizationFacts.php`. Planning
изменения сами удаляют owned DDL debt; baseline не расширялся этими findings.

`make characterization-test` также выявил два ожидающих harness-update места:
старый calendar verifier не применяет v9 до read-only consumer, а OTIZ harness
жёстко ожидает terminal `schemaVersion=8`. Эти проверки не изменялись этим
implementation author.

Поэтому tasks 3.3, 3.4 и 4.1 остаются незакрытыми до исправления test setup,
повторного независимого test review и устранения/landing concurrent blockers.

## Final integration after prerequisite landing

Предыдущие blockers устранены отдельными reviewed fixture/ownership changes:

- runtime fixture больше не выполняет duplicate planning/completion CREATE;
- calendar и OTIZ harness используют terminal v10 catalogue и independent
  schema prerequisites;
- local authorization architecture findings landed и architecture 7/7 GREEN.

Повторно воспроизведено:

```text
PASS: INSPECTION-PLANNING-SCHEMA-001 migration matrix
PASS: INSPECTION-PLANNING-SCHEMA-001 real HTTP/Compose DML-only contract
PASS calendar bounded projections, deterministic DOM and fail-closed overflow
CHARACTERIZATION_OK CHARACTERIZE-INSPECTION-SCHEDULE-DUPLICATE-001
PASS: PRODUCTION-MIGRATION-RUNNER-001 CLI contract
ARCHITECTURE CHECK PASSED (7 rules)
make lint: exit 0
git diff --check: exit 0
HARNESS-IMAGE-CANONICAL-RUNNER-001 passed
```

Repeated `make verify` проходит reset/migrate v10, architecture, lint, unit,
planning schema/runtime, characterization и diff-check. Remaining DB/E2E
failures принадлежат отдельно planned local-RBAC/login/combined-PDF slices;
planning-owned regression отсутствует. `make fresh-test-verify` воспроизводит
тот же classified result и завершает teardown с `teardown_status=0`.

Статус final: **GREEN / awaiting independent Gate 5 code review**.

## Final Gate 5 maintainability correction

Первый Gate 5 review вернул `CHANGES_REQUESTED` из-за 149-line compressed
migration с duplicated DDL/manifest. Final design устраняет это:

- public orchestration/readiness/preflight — 71 строка;
- one structured schema definition + exact DDL renderer — 80 строк;
- MariaDB fingerprint + semantic JSON CHECK normalization — 102 строки;
- максимальная длина строк 101/127/111, schema facts не дублируются.

Focused planning schema/runtime, runner, calendar, scheduling characterization,
architecture и lint повторно GREEN. Final independent code rereview —
`APPROVED` (`reviews/code/INSPECTION-PLANNING-SCHEMA-001.md`).

```text
9a4723ecda2964f13ec49c329ad9057aa9ea8d3f924d459fd28e8d4f85bdf01a  app/InstallationProcess/InspectionPlanningSchemaMigration.php
280f9d49526a5d972c131c30263eea19930c8fd70ea5146d388b982b8604bea1  app/InstallationProcess/InspectionPlanningDefinitionSchemaMigration.php
7970c5d31083f62e8c83b1d2d7b6098e17a3897f36bc6868d42219a7f163803c  app/InstallationProcess/MariaDbInspectionPlanningSchemaFingerprint.php
```

Статус: **DONE**.

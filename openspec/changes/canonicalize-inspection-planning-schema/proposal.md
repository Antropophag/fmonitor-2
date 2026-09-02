## Why

Планирование инспекций сейчас создаёт две production tables из request/read
paths, включая POST до проверки CSRF/authorization и календарные projections.
Fresh test deployment поэтому не имеет canonical schema ownership и может
изменить schema при первом пользовательском чтении или действии.

## What Changes

- Добавить последовательную canonical migration, единолично владеющую exact
  inspection schedules и schedule events family.
- Принимать clean, exact populated и exact-compatible partial family после
  read-only preflight; несовместимое состояние отклонять до mutation.
- Удалить runtime `CREATE` из scheduling POST, Calendar, construction-control,
  queue и bootstrap call paths; при отсутствии canonical schema fail closed.
- Наследовать composed canonical table-prefix ceiling 25 ASCII bytes: 25
  принимается, а 26-byte/invalid input отклоняется до DB connection/access.
  Собственная family-local граница остаётся 28 bytes: basename
  `fm2_pilot_inspection_schedule_events` имеет 36 bytes.
- Сохранить текущие rows, events, identifiers и projection compatibility без
  утверждения cadence, reschedule, cancellation или assignment-race semantics.
- **NEEDS_GRILL:** target behavior `INSPECTION-SCHEDULE-001` остаётся блокирован
  GRILL-001; этот ownership slice не превращает pilot observations в product
  requirements.

## Capabilities

### New Capabilities

- `deployment/canonical-inspection-planning-schema`: canonical ownership,
  compatibility, prefix safety, preservation и runtime-no-DDL contract для двух
  inspection-planning tables.

### Modified Capabilities

Нет.

## Impact

- Canonical runner literal v9 после фактически landed v1–v8, включая
  workforce v5, identity/access v6, checklist-template v7 и
  inspection-evidence v8.
- `rapid-pilot/InspectionSchedule.php`, Calendar, ObjectQueue,
  construction-control wiring и disposable bootstrap становятся только
  consumers canonical schema.
- Общий runner/configuration process-prefix limit равен 25 bytes; production
  empty prefix не меняется, а 26–32 byte test namespaces должны fail до DB
  connection/access. Family-local 28-byte arithmetic не является composed
  configuration support.
- Exact evidence: `docs/operations/inspection-planning-schema-evidence.md`.
- Product scheduling cadence, authorization scope, reschedule/cancel и stale
  engineer race остаются вне change и в GRILL-001.

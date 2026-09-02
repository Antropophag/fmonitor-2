## Why

Object-detail snapshot и quarantine tables сейчас создаются importer-скриптом
после чтения внешней production-like source. Fresh deployment не имеет
canonical ownership, а первый `--apply` смешивает schema mutation с импортом
персонально/операционно чувствительных данных.

## What Changes

- Добавить canonical migration, единолично создающую exact object-detail и
  quarantine family до запуска importer или consumers.
- Принимать clean, populated exact и exact-compatible partial family после
  read-only preflight; несовместимую schema отклонять до mutation.
- Удалить `CREATE TABLE IF NOT EXISTS` из importer; absent/incompatible target
  schema должна fail closed, без repair или data writes.
- Сохранить immutable first-write/hash-conflict import behavior и existing rows,
  не добавляя FK/CHECK/exclusivity/content redesign в ownership slice.
- Не seed-ить object details и quarantine rows canonical migration.
- **OWNER DECISION:** GRILL-004 закрыт решением
  `docs/operations/test-user-data-reset-decision.md`: первый TEST-USER contour
  использует только deterministic synthetic/native data, без real personal data
  и sanitised legacy cutover. Эта change остаётся data-free; population
  object-detail family возможно только отдельным утверждённым fixture seed.

## Capabilities

### New Capabilities

- `deployment/canonical-object-detail-snapshot-schema`: canonical ownership,
  compatibility, preservation и importer/runtime-no-DDL contract для object
  details и quarantine tables.

### Modified Capabilities

Нет.

## Impact

- Canonical runner получает следующий фактически свободный version после всех
  landed release prerequisites; literal version не резервируется proposal.
- `rapid-pilot/import-production-object-details.php` становится data-only
  adapter, требующим pre-migrated exact schema.
- Consumers `ObjectDetails`, native premium inputs и OTIZ projections продолжают
  читать существующий payload contract без domain ownership.
- Общий composed process-prefix ceiling равен 25 ASCII bytes из-за
  release-supporting 39-byte classification-provenance basename; 26 bytes
  отклоняются до DB connection/access. Собственная граница этой family остаётся
  30 bytes при longest basename 34 bytes и служит только локальной арифметикой.
- Approved synthetic/native source policy и запрет personal data берутся из
  `docs/operations/test-user-data-reset-decision.md`; literal fixture contents,
  object-detail population и premium preview остаются в отдельных
  `seed-test-user-fixtures`/behavior slices.
- Evidence: `docs/operations/object-detail-schema-evidence.md` и
  `docs/operations/object-detail-import-behavior-evidence.md`.

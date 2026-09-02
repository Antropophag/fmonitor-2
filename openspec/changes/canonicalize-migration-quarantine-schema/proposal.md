## Why

Migration-quarantine registration and decisions currently create three tables
from runtime paths, including OTIZ construction before command validation. This
prevents a clean canonical install and schema compatibility from being proven
before migration-control behavior runs.

## What Changes

- Introduce one additive canonical migration for the registry, observations and
  decision-ledger tables, sequenced only after its actual landed predecessors.
- Preflight the entire family before the first DDL, recover every compatible
  partial state, and fail closed without mutation on incompatible schema.
- Preserve populated rows, auto-increment counters and ambient objects while
  moving schema ownership out of runtime registry/ledger paths.
- Enforce the composed catalogue-wide process-prefix boundary: 25 ASCII bytes
  accepted and 26-byte/invalid/non-ASCII input rejected before DB
  connection/access. Preserve the family's own 27-byte arithmetic as local
  evidence only.
- Add exact schema, runner, partial-family, conflict and DDL-denied runtime
  verification before removing runtime ownership.
- Keep registration, classification and decision behavior unchanged and
  PILOT_ONLY.
- **NEEDS_GRILL:** quarantine taxonomy, allowed outcomes, correction/retention,
  cutover and financial use remain outside this ownership slice and continue to
  block their respective behavior/release decisions.

Behavior slice: `CANONICALIZE-MIGRATION-QUARANTINE-SCHEMA-001`. Actor —
production migration operator/runner. Source oracle — три точных runtime DDL
manifest из `MigrationQuarantineRegistry` и
`MigrationQuarantineDecisionLedger`, подтверждённых disposable MariaDB
fingerprint. Target public seam — `bin/fmonitor2-migrate.php`, делегирующий
canonical persistence migration в `app/InstallationProcess`. Release value —
воспроизводимая schema-ready PILOT_ONLY migration-control среда без DDL на
registration/OTIZ runtime paths. Non-goals — любая новая quarantine/decision
domain semantics, financial authority, persistence redesign или data backfill.

## Capabilities

### New Capabilities

- `persistence/migration-quarantine-schema`: Каноническое аддитивное владение
  трёхтабличной схемой migration-quarantine с полной preflight-проверкой,
  восстановлением совместимых частичных состояний и fail-closed конфликтами.

### Modified Capabilities

Нет.

## Impact

Затрагиваются `bin/fmonitor2-migrate.php`, canonical migration catalogue и
новый owner в `app/InstallationProcess`,
`MigrationQuarantineRegistry`, `MigrationQuarantineDecisionLedger`, OTIZ
construction, batch registration, schema verifiers и architecture ratchet.
Публичные actor/source-oracle контракты остаются прежними: migration tooling и
широко авторизованный `otiz.manage` decision path используют текущий
rapid-pilot oracle; новой domain logic или target financial semantics нет.

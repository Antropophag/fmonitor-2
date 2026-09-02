## Why

Шесть таблиц migrated history/reconciliation создаются тремя перекрывающимися
runtime owners, включая OTIZ request path до CSRF. Это делает fresh deployment,
partial recovery и data backfill зависимыми от случайного первого consumer и
скрывает split collation/JSON semantics от canonical runner.

## What Changes

- Добавить ownership-only canonical migration для exact six-table family:
  source snapshots, import quarantine, projection, conflicts, decisions и
  latest decision state.
- Выполнить family-wide semantic preflight до первого DDL, разрешить все 64
  absent/exact-compatible состояния и создавать только missing members.
- Сохранить populated rows, auto-increment counters, derived/decision state и
  ambient decoys; любой incompatible member отклонять с family-wide zero mutation.
- Зафиксировать split collation policy: inherited database default для двух
  tables и MariaDB default utf8mb4 collation для четырёх explicit-charset tables,
  включая JSON alias/binary collation/CHECK.
- Зарегистрировать migration после реально landed predecessors и убрать schema
  creation из importer, projection store, decision ledger и OTIZ constructor
  chain, заменив его fail-closed exact schema precondition.
- Не запускать import, projection backfill, conflict replacement или
  decision-state rebuild из migration.
- Наследовать composed process-prefix boundary: 25 ASCII bytes принимаются,
  26-byte/invalid/non-ASCII input отклоняется до DB connection/access. Собственная
  family-local граница 28 остаётся только identifier arithmetic.

Behavior slice: operator разворачивает/обновляет migrated-evidence persistence
до запуска import/OTIZ tools. Source oracle — `LegacyHistoryMySqlTarget`,
`MigratedEvidenceProjectionStore`, `MigratedEvidenceDecisionLedger` и isolated
MariaDB 11.4.7 fingerprint. Target public seam — canonical migration runner;
runtime tools становятся data-only consumers exact schema.

Release value: clean/restartable deployment и устранение шести runtime-DDL debts
без изменения migrated evidence content или reconciliation behavior.

NEEDS_GRILL: admission migrated evidence в target premium, reconciliation
authority/outcomes, correction/retention/cutover/privacy остаются отдельными
решениями. Они не блокируют ownership planning, но implementation остаётся
`BLOCKED_PREDECESSORS` до landing earlier canonical families и refresh exact
catalogue/version.

Явные non-goals:

- импорт production/legacy data или synthetic population;
- изменение projection algorithm, classifications, hashes или outcomes;
- backfill/rebuild atomicity hardening;
- добавление FKs, redesign JSON/storage, cleanup/retention;
- target OTIZ authorization/premium semantics;
- destructive down migration либо renumbering guessed schema version.

## Capabilities

### New Capabilities

- `persistence/migrated-evidence-schema`: canonical exact ownership,
  compatibility/recovery и runtime-no-DDL contract для six-table family.

### Modified Capabilities

Нет.

## Impact

- Canonical migration catalogue/runner после landed predecessors.
- Legacy importer, projection/backfill store, decision ledger and OTIZ
  constructor schema checks become DDL-free exact preconditions.
- Architecture runtime-DDL baseline decreases by six owner statements/sites.
- No production data population, public response or domain behavior change.
- Full runner/configuration uses composed 25/26 pre-DB-access prefix contract;
  direct family evidence may retain its own 28/29 identifier boundary.

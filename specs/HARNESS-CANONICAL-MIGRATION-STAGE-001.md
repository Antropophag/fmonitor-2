# HARNESS-CANONICAL-MIGRATION-STAGE-001 v0.1

Status: approved by the TEST-USER-READY clean-checkout verification mission. This is a delivery-harness contract and changes no product behavior.

## Actor and intent

A developer, CI runner, or autonomous agent runs the public verification seam and needs proof that the same clean test database is prepared by the canonical production migration runner before DB-dependent verification begins.

## Public seams

- `make migrate` applies canonical migrations to an already-created database and never creates, drops, or resets that database.
- `make verify` owns the clean reset followed by canonical migration and the remaining verification stages.

## Preconditions and input

- Repository test tooling and PHP dependencies are available.
- For a real run, disposable test MariaDB is reachable through the documented `FMONITOR_TEST_DB_*` settings.
- Fixture-based harness tests may replace stage commands but must exercise the public Make seams.

## Contract

1. `make verify` SHALL execute these stages exactly once in stable order: `test-db-reset`, `migrate`, `architecture-check`, `lint`, `unit-test`, `db-test`, `characterization-test`, `e2e-test`, `diff-check`.
2. `make migrate` SHALL NOT depend on or invoke `test-db-reset`; repeating it against the same database SHALL exercise canonical-runner idempotency rather than erase state.
3. A successful migration SHALL preserve the canonical runner output and emit exactly `VERIFY_STAGE migrate PASS` before DB-dependent stages execute.
4. A migration failure SHALL remain visible, be classified exactly once as `SETUP_FAILURE stage=migrate`, and emit `VERIFY_STAGE migrate FAIL`.
5. If reset fails, migration SHALL not execute. It SHALL emit `SETUP_FAILURE stage=migrate cause=test-db-reset outcome=SKIP` and `VERIFY_STAGE migrate FAIL`.
6. If reset or migration fails, `db-test` and `e2e-test` SHALL not execute. Each SHALL emit one setup-caused `SKIP` line naming the immediate setup cause and one `VERIFY_STAGE ... FAIL` result.
7. Architecture, lint, unit, characterization, and diff stages SHALL continue after reset or migration failure.
8. Terminal aggregation SHALL count and name every failed or setup-blocked stage in protocol order and SHALL exit nonzero without `VERIFY_OK`.
9. This slice SHALL NOT silently tear down a developer-owned environment; lifecycle teardown remains the explicit `make test-env-down` seam.

## Acceptance scenarios

### Clean reset is followed by canonical migration

- **GIVEN** all nine fixture stages succeed
- **WHEN** `make verify` runs
- **THEN** reset occurs once and migration runs immediately afterward
- **AND** all nine ordered stage results are `PASS`
- **AND** exactly one terminal `VERIFY_OK` is printed

### Migration failure blocks only DB-dependent work

- **GIVEN** reset succeeds and the canonical migration command fails without classifying itself
- **WHEN** `make verify` runs
- **THEN** the runner emits `SETUP_FAILURE stage=migrate`
- **AND** neither DB nor E2E command executes
- **AND** both are explicitly skipped because `migrate` failed
- **AND** independent stages continue
- **AND** the terminal failure is exactly `count=3 stages=migrate,db-test,e2e-test`

### Reset failure skips migration and DB-dependent work

- **GIVEN** reset fails
- **WHEN** `make verify` runs
- **THEN** migration, DB, and E2E commands do not execute
- **AND** their protocol results are explicit setup-blocked failures
- **AND** the terminal failure is exactly `count=4 stages=test-db-reset,migrate,db-test,e2e-test`

### Public migration is non-destructive

- **GIVEN** a fixture database marker exists
- **WHEN** `make migrate` runs twice without an explicit reset
- **THEN** no reset command is invoked
- **AND** the marker remains available to the second canonical-runner invocation

## Done definition

The reviewed RED proves the missing migration stage and destructive prerequisite; implementation is minimal; focused harness, migration-runner regression, architecture check and diff check pass; a fresh independent code review is approved.

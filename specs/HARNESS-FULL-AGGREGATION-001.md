# HARNESS-FULL-AGGREGATION-001 v0.2

Status: approved by the TEST-USER-READY clean-checkout verification mission. This is a delivery-harness contract and changes no product behavior.

## Public seam

`make verify` from the repository root.

## Required stages

In this stable order:

1. clean test database reset;
2. canonical migration;
3. architecture check;
4. lint;
5. unit tests;
6. DB tests;
7. characterization tests;
8. E2E/golden journey;
9. worktree whitespace/diff check.

The migration-stage setup dependency and skip protocol are defined by `HARNESS-CANONICAL-MIGRATION-STAGE-001`; this v0.2 supersedes the former eight-stage ordering.

## Contract

1. Once environment setup succeeds, a regression in one verification stage MUST NOT prevent later independent stages from running.
2. Every stage MUST emit one final machine-readable result containing its stable stage name and `PASS` or `FAIL`.
3. The command MUST exit nonzero when any required stage fails, report the exact failed-stage count and names, and MUST NOT print `VERIFY_OK`.
4. The command SHALL print `VERIFY_OK` and exit zero only when all required stages pass.
5. A database/container setup failure SHALL be classified `SETUP_FAILURE`. Regression stages that can still run without that resource SHALL continue; DB-dependent stages SHALL report explicit failure/skip caused by setup rather than masquerading as assertion regressions.
6. Output from each underlying stage SHALL remain visible so `SETUP_FAILURE`, `RED_ASSERTION`, and `REGRESSION_FAILURE` evidence is not collapsed into the summary.
7. The full runner SHALL not reset the database between DB tests and the E2E stage unless the stage contract explicitly requests it; one initial clean reset defines the full-run environment.

## Acceptance scenarios

### Middle regression does not hide later coverage

- **GIVEN** setup, architecture, lint and unit stages pass
- **AND** the DB stage fails
- **WHEN** `make verify` runs
- **THEN** characterization, E2E and diff stages still execute
- **AND** the final summary names DB as failed and exits nonzero without `VERIFY_OK`

### Multiple failures are aggregated

- **GIVEN** two non-adjacent stages fail
- **WHEN** `make verify` runs
- **THEN** every required stage is attempted in order
- **AND** the final summary reports exactly both failed stage names

### Fully green checkout

- **GIVEN** every required stage passes
- **WHEN** `make verify` runs
- **THEN** every stage reports `PASS`
- **AND** the command exits zero and prints exactly one terminal `VERIFY_OK`

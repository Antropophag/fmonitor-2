# HARNESS-FRESH-TEST-LIFECYCLE-001 v0.1

Status: approved by the TEST-USER-READY clean-checkout deployment mission. This is a delivery-harness contract and changes no product behavior.

## Actor and intent

A developer, CI runner, or autonomous agent needs one command that executes the authoritative full verification lifecycle and returns the workspace to a stopped disposable-test-environment state on both success and failure.

## Public seam

`make fresh-test-verify` from the repository root.

`make verify` remains the lower-level developer seam that may leave an explicitly requested test environment running for investigation.

## Contract

1. `fresh-test-verify` SHALL invoke the public `verify` target exactly once and preserve all of its stdout/stderr.
2. After `verify` terminates for any reason, it SHALL invoke the public `test-env-down` target exactly once.
3. Teardown SHALL execute after verification success, regression failure, or setup failure. Signal/process-group recovery is outside this minimal release slice and remains deployment hardening work.
4. If verification fails and teardown succeeds, the command SHALL return nonzero and preserve the child `make verify` status in terminal `FRESH_TEST_VERIFY_FAILURE verify_status=<n> teardown_status=0`. GNU Make may normalize a failed recipe to its public status `2`; deeper recipe exit codes are not part of this seam.
5. If verification succeeds and teardown fails, the command SHALL return nonzero and emit `SETUP_FAILURE stage=test-env-down`.
6. If both verification and teardown fail, both underlying outputs SHALL remain visible; the command SHALL emit `SETUP_FAILURE stage=test-env-down` and terminal `FRESH_TEST_VERIFY_FAILURE verify_status=<n> teardown_status=<n>` using the two child Make statuses.
7. On complete success it SHALL exit zero and emit exactly one terminal `FRESH_TEST_VERIFY_OK` after teardown.
8. On any failure it SHALL NOT emit `FRESH_TEST_VERIFY_OK`.
9. The lifecycle wrapper SHALL not duplicate reset, migration, suite, or Docker logic; ownership remains in `verify` and `test-env-down`.

## Acceptance scenarios

### Green verification tears down

- **GIVEN** `verify` succeeds and `test-env-down` succeeds
- **WHEN** `make fresh-test-verify` runs
- **THEN** the two targets execute once in that order
- **AND** their output remains visible
- **AND** the wrapper exits zero with terminal `FRESH_TEST_VERIFY_OK`

### Regression preserves status and tears down

- **GIVEN** child `make verify` returns nonzero after emitting regression evidence
- **AND** teardown succeeds
- **WHEN** the lifecycle runs
- **THEN** teardown still executes exactly once
- **AND** the wrapper exits nonzero
- **AND** terminal failure marker records the child verify status and teardown status `0`
- **AND** no success marker is printed

### Teardown failure is classified

- **GIVEN** `verify` succeeds and teardown fails
- **WHEN** the lifecycle runs
- **THEN** output includes exactly one `SETUP_FAILURE stage=test-env-down`
- **AND** the wrapper exits nonzero without a success marker
- **AND** terminal failure marker records verify status `0` and the nonzero teardown status

### Dual failure preserves primary result

- **GIVEN** child `make verify` and child `make test-env-down` both return nonzero
- **WHEN** the lifecycle runs
- **THEN** both failure streams remain visible
- **AND** teardown failure is classified
- **AND** wrapper exits nonzero
- **AND** the terminal failure marker records both child Make statuses

## Done definition

A reviewed no-Docker RED proves the seam is missing; implementation adds only orchestration; focused lifecycle tests, existing aggregation/canonical-migration harness tests, architecture and diff checks pass; a fresh independent code review is approved; one real failing `fresh-test-verify` run proves the known GRILL regressions are preserved and the Compose environment is down afterward.

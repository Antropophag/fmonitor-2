# Test review: HARNESS-CANONICAL-MIGRATION-STAGE-001 v0.1

- Gate: 3 — independent test review
- Reviewer: separately tasked fresh agent `/root/canonical_migrate_stage_test_review_v3`
- Independence: this reviewer did not author the specification, test, or implementation
- Reviewed ancestry: HEAD `932662938837b28309fef2bf0fe3cadb2ce86e41`; dirty-tree bytes pinned below
- Public seams: `make verify` and `make migrate`
- Date: `2026-09-01`
- Verdict: `APPROVED`

## Findings

No blocking findings.

Traceability and behavior coverage pass. Fixture-backed `make verify` scenarios require exactly nine ordered stage commands and results for success, migration failure, and reset failure. A raw failed migration emits unique stdout/stderr but no setup protocol; the test requires production to preserve both streams, classify `SETUP_FAILURE stage=migrate` exactly once, skip rather than execute DB/E2E, continue every independent stage, and publish the exact `migrate,db-test,e2e-test` terminal aggregate. Reset failure separately proves migration, DB, and E2E do not execute and requires their immediate `test-db-reset` cause and exact four-stage summary.

The public migration scenario does not override `migrate`. It invokes the real root target twice with a PATH-local PHP probe. The probe independently requires the single canonical runner argument `bin/fmonitor2-migrate.php`, exact documented default DB environment, and an explicitly empty `FMONITOR_PROCESS_TABLE_PREFIX`. A marker must exist and remain readable on both invocations. Overlay `test-env-up` and `test-db-reset` targets write forbidden evidence, so any implicit environment bring-up or reset changes the expected two-line log and fails. This rejects both the current destructive prerequisite and a substitute runner/incomplete environment.

The fixtures do not print `VERIFY_STAGE`, `SETUP_FAILURE`, summaries, or `VERIFY_OK`; those must come from the public Make seam. Expected protocol and environment values are specification literals rather than production-derived values. Random temporary isolation and `finally` cleanup are adequate; neither Docker nor MariaDB participates in the RED.

## Reproduced RED

Command:

```text
php tests/Verification/harness_canonical_migration_stage_001_test.php
```

Result: exit `255` at the intended first `RED_ASSERTION`. Current `make verify` ran and published eight passing stages but omitted `migrate`; the expected sequence inserts it directly after `test-db-reset`. This is a behavior RED against the public runner, not an environment failure.

The later public-migration scenario is structurally sensitive to the second known gap: the current `migrate: test-db-reset` prerequisite would append `FORBIDDEN test-db-reset` before each canonical runner invocation, contradicting the exact two-line marker log.

## Reviewed hashes

```text
4d07510c7b504595e6a15f8be234d1b71c0e8969d02e7a1d1edcc317bb990d24  specs/HARNESS-CANONICAL-MIGRATION-STAGE-001.md
7b07b6979a6f6ec73b4b8198d1b5929524ce3fa160577b78b72b497a6e09c8b7  tests/Verification/harness_canonical_migration_stage_001_test.php
40d3205574ffa122e6cfcc03fcf4a0f32407aa91002631d0fe6cd95e09079e0b  Makefile
800d135a043633260ce59440579f35f4dcf16553c61f7e149d82825c9e6c3509  tests/bootstrap.php
```

Gate 3 is approved. Gate 4 may remove the destructive `migrate` prerequisite and add canonical migration orchestration/setup causality without altering the reviewed tests.

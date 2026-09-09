# OTIZ-SETTLEMENT-001 — canonical settlement schema Gate 3 v1

- Date: `2026-09-09`
- Reviewer: separately tasked agent `/root/settlement_review`
- Test author: executor `/root/settlement`; this reviewer did not author the specification, test, production migration, or catalogue
- Reviewed exact candidate: `52d7e2fd578c60230184d09aeaaf7ea8f0c10cf1`
- Reviewed test: `tests/InstallationProcess/otiz_settlement_schema_001_test.php`
- Public seam: `CanonicalMigrationApplication::run()` with `ProductionPilotMigrationCatalogue::migrations()`
- Verdict: **CHANGES_REQUESTED**

This is a separate review of the canonical schema lifecycle increment. It does
not replace the concurrent replay review and does not approve production code.

## T1 — BLOCKING: predecessor upgrade is not exercised

OpenSpec task `openspec/changes/otiz-settlement-owner/tasks.md:8` requires the
canonical migration to be verified for `clean/predecessor/repeat/conflict`.
The reviewed test covers:

- an empty-database clean run through the whole v1-v24 catalogue at line 18;
- a populated repeat at lines 19-22;
- an incompatible-table conflict at line 23.

It does not establish a predecessor database at canonical v23 and then run the
v24 successor. The clean run is not an equivalent oracle: it cannot detect a
migration that succeeds only when it creates its tables alongside all earlier
migrations but fails when upgrading an existing populated v23 deployment.

Add an isolated predecessor-v23 case. It must first establish the exact
canonical predecessor, preserve representative predecessor data, apply the
v24 catalogue, and assert only the additive settlement schema is introduced
without altering predecessor data. Keep clean, populated repeat, and conflict
cases.

## RED reproduction

The exact candidate was checked out at HEAD while the executor had unrelated
uncommitted changes only in the concurrent replay test and its evidence file;
neither changes the schema test, catalogue, migration application, or schema
migration. The reviewed schema test bytes are those committed at `52d7e2fd`.

```text
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    FMONITOR_TEST_DB_PORT=23306 \
    php tests/InstallationProcess/otiz_settlement_schema_001_test.php

Fatal error: Uncaught TestFailure: INTENDED_RED: OTIZ settlement schema is canonical successor v24
Expected: 'FMonitor2\\InstallationProcess\\OtizSettlementSchemaMigration'
Actual: NULL
... tests/InstallationProcess/otiz_settlement_schema_001_test.php(17)
exit 255
```

The disposable database was created successfully and the `finally` cleanup
completed. The failure is the absent canonical v24 registration, not broken
setup. The expected successor is independently derived from the current
contiguous v1-v23 production catalogue and the approved additive migration
plan.

The downstream clean/repeat/conflict assertions are statically traceable and
appropriately exercise the canonical application seam, but they are not
claimed as dynamically reached by this RED run.

## Process state

The executor confirmed that the ignored local plan available at this exact
candidate had been generated before the schema-test commit and that the run
transcript had not yet been preserved. This record preserves an independently
reproduced actual RED, but it cannot make that pre-commit plan current. A
regenerated exact-candidate plan must pass the required `check` before the next
Gate 3 review.

## Gate consequence

Gate 3 is **CHANGES_REQUESTED**. Correct the predecessor oracle, regenerate and
validate the plan against the frozen corrected candidate, capture fresh RED for
the exact corrected test bytes, and request independent rereview. Gate 4 for
the schema increment is not approved by this record.

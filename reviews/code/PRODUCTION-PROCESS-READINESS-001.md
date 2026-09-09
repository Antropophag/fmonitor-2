# Independent Gate 5 review — PRODUCTION-PROCESS-READINESS-001

- Date: `2026-09-09`
- Reviewer: separately tasked agent `/root/runtime_review`
- Test/implementation author: separately tasked agent `/root/runtime_plan`
- Gate 3 reviewer: `/root/runtime_review`
- Verdict: **APPROVED**

## Reviewed identities

```text
d4c221317c684461e547c2e831afb9f2689b0118653704814d5a22789643c168  reviews/tests/PRODUCTION-PROCESS-READINESS-001.md
b7c042ec6d787220b6cf049e01968578d23a4cc9e601f0129eb9a9657ce244a2  tests/Runtime/production_process_readiness_001_test.php
6210a842d6b665727d2282bbeee3dd69bf2c2b22aa31fe38584119bab4786e8e  app/InstallationProcess/ProductionProcessSchemaMigration.php
8a51c9510b5c116896a42addbab5a8c41c6452206ab57b2b19c6ec02ebd57a25  app/InstallationProcess/MariaDbProductionProcessSchemaReadiness.php
f3f4f7f83635fa32744b346ddac866e8a8645d3bc30a71c9b042e1010aac5f12  app/Runtime/MariaDbRuntimeReadiness.php
```

## Review

The change extracts the existing six-table process metadata into a read-only
`MariaDbProductionProcessSchemaReadiness` adapter. Migration creation SQL is
unchanged and now consumes the same table list and per-table validator, eliminating
the earlier installation-case-only runtime shortcut without creating a second
schema definition.

The shared validator checks all process tables, engine/utf8mb4 character storage,
ordered column shape, primary/unique/secondary indexes, foreign keys and the event
JSON check. Runtime readiness calls only `isCompleteCompatible`; it cannot reach
DDL. The migration retains prefix validation, conflict-before-create behavior and
the prior result contract.

The public CLI test proves healthy canonical v22 first, then catches both a missing
process table and a one-character column-width drift, with exact schema and every
table row unchanged after rejection.

## Verification

```text
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/Runtime/production_process_readiness_001_test.php
PASS: PRODUCTION-HTTP-RUNTIME-001 readiness covers all canonical process tables

$ git diff --check
# exit 0, no output
```

The broader production migration runner was attempted by the author but encountered
an environmental database access setup failure before assertions during a concurrent
DB contour restart. The focused test itself successfully runs the full v22 catalogue
twice and verifies the extracted metadata. Broader regression remains required for
the eventual full-runtime verdict.

## Verdict

**APPROVED.** The shared read-only process readiness adapter conforms to the
reviewed contract. This does not approve the full production runtime.

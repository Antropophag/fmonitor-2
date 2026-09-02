# Installation completion v10 — integration fixture review

Date: 2026-09-02  
Reviewer: fresh separately tasked agent `/root/completion_test_review`  
Verdict: **APPROVED**

The reviewer did not author or edit the reviewed tests, characterizations,
production implementation or tasks. This record excludes the concurrent
local-RBAC/CSP route changes authored by `completion_red`; those have separate
reviews.

## Reviewed artifacts and hashes

```text
c1352d23ff1b02385d4d8be969492796a4eccc2d96f1b400ab71268a2e8fbc09  tests/Support/ProductionMigrationRunnerCatalogContract.php
9f8b15a991c7ac1bc69ebc56383270e33addfd2b6b604d04f19b28c5ee82a4e1  tests/InstallationProcess/production_migration_runner_001_test.php
c5182cabe54ae5951eb3d121f509c3e54decab9fd9099d0b87891ebb8c6c7362  tests/InstallationProcess/identity_access_schema_001_test.php
1ee1f7c86eb7c30056f4b4079975199b6e1fe7c72361c010fc1660ee71e292d1  tests/InstallationProcess/inspection_evidence_schema_001_test.php
0a3d0026b77cc39262e125e03042b6f4ef0af528e7b52a4dbc3b146a8941205d  tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php
74ada0f93de0fc3f598b1dbfc4ff05243c2c69ead8ea94225baa74d7ce50144a  tests/InstallationProcess/inspection_planning_schema_001_test.php
385673c077d00017c1fe81ee7d362b37712ee9dbb7ec09b301e0c24bfb3d1493  tests/InstallationProcess/inspection_planning_runtime_ddl_001_test.php
730d554b8b7065dd37deaf8bfacb0f3dccc0e015c0becac98dc05bf26101ee90  tests/Support/inspection_planning_runtime_router.php
4019a09194012bb2afca7bf966de055e4dd8e6024ae0b7d3d74ff00159d586db  tests/InstallationProcess/checklist_template_schema_001_test.php
1fec000df5dd597b521eafc87b46b0048039f1fdb97910a88a1712f786035c6e  tests/InstallationProcess/pilot_case_import_001_test.php
283f10b121afbd4a0978990d9995302f5ccbbdb85d3161d45a5915f1571bce8c  tests/InstallationProcess/workforce_canonical_runner_001_test.php
55872ed19af9224ecc068fba3812ae9e954d657be8c589b8a473ea7cb4b373d7  rapid-pilot/verify-calendar-projections.php
e7be936ea21bf3c0d3030e59c0caf73bf00b02b49badceb0b0fa03e5bc7a1e78  rapid-pilot/verify-completion-flow.php
354350948bfe4a318fd99a225743045092c4cc315bfe6dc7b1c14073d535e381  rapid-pilot/verify-otiz-workflow.php
e2f24888b35bd07e1322191dfb5ee8d7b73f0d2348245d1e60a8dfc99c62ba9e  tests/Verification/harness_otiz_canonical_compat_001_test.php
```

## Exact terminal catalogue

The shared contract now contains exactly 30 v1-v10 tables. Its v10 additions
match the approved root and correction column order/types/nullability/extras,
the eight root/correction primary/unique/secondary index tuples, four ordered
foreign-key column mappings and the three normalized correction CHECK
expressions. Together with v9 it yields exactly 15 CHECK tuples and 17 FK column
mappings. The composed runner test asserts these counts with no extras and
requires clean output `schemaVersion=10`, applied versions `[1..10]`, repeat
`appliedVersions=[]`, exact 25-byte prefix support and existing configuration,
failure, redaction and recovery behavior.

The catalogue-level representation intentionally retains the shared runner's
historical abstraction over integer display widths and index names. Exact v10
defaults, character metadata, generated state, full named index/FK metadata,
semantic CHECK identity, engine and database-default collation remain covered
by the independently approved
`installation_completion_schema_001_test.php`; integration fixtures do not
replace or weaken that direct test.

## Preservation of prior direct behavior

- **v5 workforce:** clean and compatible partial cases now compose through
  v6-v10, but `wcrAssertExactV5`, fresh-row assertions, populated repeat state,
  partial preservation and exact v5 conflict remain unchanged and GREEN.
- **v6 identity/access:** all nine direct manifests, populated/generated-symbol
  compatibility, dependency-safe partial recovery, metadata mutations,
  namespace isolation and runtime-no-DDL checks remain. Terminal successors are
  compared only after the exact v6 family preservation snapshot.
- **v7 checklist template:** direct migration, UCA/non-utf8mb4 preflight,
  metadata/conflict/runtime behavior remain unchanged; only composed terminal
  runner results advance through v10.
- **v8 inspection evidence:** direct exact manifests, predecessor upgrades,
  malformed/metadata conflicts, ordering, database-default and DML-only runtime
  behavior remain. The completed composed repeat compares rows, allocators and
  metadata before terminal cleanup.
- **v9 inspection planning:** the direct clean/repeat/partial/conflict,
  prefix/collation and real HTTP/Compose DML-only matrices remain unchanged and
  GREEN. Runtime uses public terminal v10 fixtures without weakening its v9
  readiness assertions.

The inspection-item MariaDB concurrency/persistence test also remains GREEN
against terminal v10 prerequisites; its worker, runtime user, artifact and
database cleanup includes explicit residue verification.

## Prefixes and schema-global FK symbols

MariaDB requires the two normative v10 FK names to be unique within one schema,
even across table prefixes. Updated multi-prefix tests therefore release only
the terminal completion correction table followed by its root after the
relevant composed-success, repeat and preservation assertions have completed.
They do not remove the older family under test.

Examples reviewed include identity clean/populated/partial/dependency and
blue/green prefixes, workforce clean/partial prefixes, inspection-evidence
terminal repeat, and case-import setup prefixes. Cleanup occurs before the next
compatible prefix can reuse the two v10 symbols. Conflict cases stop at their
originating earlier version and perform no v10 cleanup because no v10 family was
created. The final prefix in each isolated random database may remain until the
outer database drop; this cannot collide with another test run.

No preservation assertion is made vacuous: full or family-filtered before/after
comparison occurs before terminal v10 removal. Case import first requires exact
public v1-v10 JSON, then removes only the unrelated terminal completion family
so its many independent import/race/reconciliation prefixes can coexist; its
process-fact assertions do not treat completion tables as import state.

## Non-utf8mb4 precedence

The identity composed-runner fixture now expects exit `70`, exact
`MIGRATION_FAILED`, empty stderr and zero mutation for a latin1 database. This
matches approved completion §2: terminal v10 validates the database default
before any catalogue mutation and its public mapping supersedes the former
full-runner `DATABASE_UNAVAILABLE` result. The direct v6 identity preflight,
direct v7 checklist-template, direct v8 evidence and direct v9 planning
non-utf8mb4 rejection assertions remain unchanged, so no earlier migration
behavior was rewritten to fit v10.

## Characterization and harness isolation

Calendar and completion characterizations now create unique temporary
databases, run canonical migration/schema seams, use explicit current columns
and drop the entire database afterward. Completion creates its family through
the production migration seam and includes corrections in cleanup; it does not
copy a production DDL literal.

The OTIZ characterization uses test-owned current OTIZ schema literals followed
by the production read-only readiness seam. It no longer relies on runtime
schema creation. Its authorization, deterministic calculation, acceptance,
allocation, payment, reversal, history and export assertions remain GREEN. The
canonical-compat harness advances only its migration contract/table inventory
to v10 and continues to prove two stable invocations plus cleanup after a
controlled failure without leaking or mutating canonical tables.

Temporary databases use admin setup credentials only for creation/grants and
are dropped in `finally`. Runtime/behavior connections retain their intended
privileges. Environment changes such as database name are restored, subprocesses
are reaped, temporary artifacts are removed, and no production-owned schema
creator is used as a test oracle for its own expected DDL.

## Independent verification

The following passed with the local test MariaDB administrator password
provided explicitly where required:

```text
PASS: PRODUCTION-MIGRATION-RUNNER-001 CLI contract
PASS: IDENTITY-ACCESS-SCHEMA-001 canonical runner and runtime ownership
INSPECTION-EVIDENCE-SCHEMA-001 tests passed.
PASS: INSPECTION-PLANNING-SCHEMA-001 migration matrix
CHECKLIST-TEMPLATE-SCHEMA-001 migration runner test passed
PASS: WORKFORCE-CANONICAL-RUNNER-001 complete public-runner matrix.
PASS: PILOT-CASE-IMPORT-001 CLI contract
PASS: INSPECTION-ITEM-COMPLETE-001 MariaDB concurrency and persistence
PASS: INSPECTION-PLANNING-SCHEMA-001 real HTTP/Compose DML-only contract
PASS calendar bounded projections, deterministic DOM and fail-closed overflow
PASS rapid completion flow 85% -> PTO -> declaration -> 100%
ok - authorization rejects a user outside OTIZ
... all OTIZ workflow assertions passed ...
ok - HARNESS-OTIZ-CANONICAL-COMPAT-001 preserves canonical v1-v10 across repeated isolated OTIZ characterization
```

All 15 reviewed PHP artifacts pass `php -l`; scoped `git diff --check` is clean.
The initial OTIZ characterization attempt used its container-default host and
failed before behavior because `mariadb` was not resolvable on the host. Running
the same test with its documented `FMONITOR_VERIFY_DB_*` host credentials passed
fully; this was environment setup, not a regression.

## Verdict

**APPROVED.** The v10 integration edits accurately advance terminal catalogue
fixtures while retaining independently observable v5/v6/v7/v8/v9 behavior,
conflict and preservation sensitivity. No circular production-owned expected
schema, privilege broadening, premature cleanup or hidden mutation was found.
This review does not approve the excluded RBAC/CSP route changes and does not by
itself declare full repository verification complete.

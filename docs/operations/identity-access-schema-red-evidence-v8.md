# IDENTITY-ACCESS-SCHEMA-001 RED evidence v8 — diagnostic-seam amendment

- Date: `2026-09-01`
- Role: fresh Gate 2 test author `identity_access_red_amendment_20260901j`
- Supersedes: `identity-access-schema-red-evidence-v7.md` for Gate 2 test scope
  and task accounting
- Outcome: `QUALIFYING RED`; fresh independent Gate 3 review required
- Production code changed: no

## Owner-approved seam reconciliation

The operator CLI remains the public redacted boundary. Exact internal
`conflictingTables`, `missingTables` and `tablesCreated` are now asserted through
the public `IdentityAccessSchemaMigration` application result before CLI
redaction. Because that v6 object does not exist during RED, those assertions
are syntactically present and become reachable automatically when minimal GREEN
adds the public class; the CLI assertions independently require exact redacted
stdout, empty stderr and exit status.

The v6-only unexpected-failure and post-v6 short-circuit expectations are
authored in
`tests/InstallationProcess/identity_access_schema_001_green_application_contract.php`.
The helper accepts adapters to the eventual public application orchestration,
requires exact `MIGRATION_FAILED` output/exit and proves a test-owned later
migration callback was invoked zero times. Per the approved amendment it first
executes at minimal GREEN; no expectation may be weakened then.

## Corrections after Gate 3 rereview v2

1. Clean output is compared with test-owned literal semantic tuples for all
   nine tables: every ordered column type/nullability/default/extra/charset/
   collation, every ordered BTREE index, all five FK tuples, engine and exact
   deterministic symbols.
2. Independent zero-mutation conflict fixtures now add FK target, local column,
   referenced column and update-rule defects, plus a distinct latin1 charset
   defect. The existing delete-rule and alternate-utf8mb4-collation defects
   remain separate.
3. The application diagnostic assertion requires exact normative conflict,
   missing and created lists; the multi-family CLI assertion separately proves
   that none of those names leak.
4. Unexpected v6 failure and later-migration short-circuit assertions are
   reviewably precise and preserved for their approved first GREEN execution.

## Reproduced qualifying RED

Canonical runner:

```text
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/identity_access_schema_001_test.php
Expected: schemaVersion=6, appliedVersions=[1,2,3,4,5,6]
Actual:   schemaVersion=5, appliedVersions=[1,2,3,4,5]
exit: 255
```

Exclusive runtime observer:

```text
$ tools/verification/run-identity-access-isolated-red.sh
- migrated block/unblock emitted two lazy CREATE TABLE statements
- missing family returned HTTP 303 instead of safe HTTP 400
- missing family mutated user state
- missing family emitted lazy CREATE TABLE
exit: 255
```

The incompatible-family branch remains absent from failures: it produced safe
HTTP 400, zero state mutation and zero DDL. Both RED contours therefore fail on
missing approved behavior rather than setup.

## Validation and hygiene

- `php -l` passed for both executed tests and the GREEN application contract;
- `bash -n tools/verification/run-identity-access-isolated-red.sh` passed;
- `openspec validate canonicalize-identity-access-schema --strict` passed;
- focused `git diff --check` passed;
- isolated runner trap removed its random `fm2-ia-red-*` container; post-run
  query returned no matching container names.

Test SHA-256:

- canonical/application test:
  `c9b12eac2f52782f2574fc3e17fb580bacc3263c77af4b33654c68de353020c5`;
- runtime observer:
  `b93d761a3b3530da2cf9c954527e151e16102e552fb1860159ad48a6a97548e2`;
- first-GREEN failure/short-circuit contract:
  `9a255b2d3d1df6e1a4fb56ab7f63aade58f5dc137637c6ce5525f219cc50919b`.

OpenSpec tasks 2.1–2.3 are checked because their test authorship and qualifying
RED evidence are complete under the amendment. Task 2.4 remains unchecked until
a fresh separately tasked independent reviewer returns `APPROVED` in a new
append-only review record.

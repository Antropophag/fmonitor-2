# Test rereview: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 schema migration v2

- Reviewer: separately tasked agent `/root/original_upload_migration_gate3_v2`
- Test author: separately tasked agent `/root/original_upload_migration_red`
- Reviewed commit: `8e6f71f6254dc7ba68bca0c473c6fc7efc00a161`
- Specification: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v4, owner-approved hash `97a2527db60750089a53311856756b7db7b4682baf5c426a45503639ebde5479`; OpenSpec delta hash `127eddc8a0e7b3ce270b5c704ddf6a55022de22cd3d3447592402b426256cee2`
- Public seams: `CanonicalMigrationApplication::run(...)` and `bin/fmonitor2-migrate.php`
- Corrected test SHA-256: `dadf18a2376751c52a73a8a03dec247f782830f7feebb52925eba96039dd6b41`
- Verdict: `APPROVED`

## Prior finding disposition

The append-only v1 review remains `CHANGES_REQUESTED`. The corrected test closes
its blocking sensitivity gap. A capability-only v12 can no longer pass: the
clean and populated paths require all seven original-evidence tables and compare
independently stated ordered column/type/nullability, index, foreign-key/delete,
CHECK, engine and table-collation semantics.

The manifest covers immutable root identity and composition snapshot, revision
ancestry and the unique nullable current marker, terminal request results,
globally unique accepted fingerprints, one event per accepted revision, one safe
attempt audit per rejected/conflict request, and bounded maintenance results.
The constraints reject missing lineage, multiple current leaves, invalid byte
bounds, incomplete accepted evidence, non-terminal audit shapes and inconsistent
maintenance counters/outcomes.

The populated fixture proves both predecessor preservation and compatible v12
repeat preservation. It retains legacy manual-registration columns and existing
capability grants byte-for-byte, then persists representative root, revision,
accepted request, fingerprint, event, rejected request/audit and maintenance
facts. The complete `aoosState(...)` comparison includes every owned table's
`SHOW CREATE TABLE`, every row and `AUTO_INCREMENT`, so repeat mutation cannot be
hidden behind the semantic manifest.

Both partial-family directions requested by v1 are now exercised. One fixture
pre-creates a malformed populated root; the other pre-creates a hostile populated
revision. Each requires the public v12 application to fail closed before
capability alteration, repair, sibling creation, row/counter mutation or decoy
mutation. The earlier hostile capability-CHECK fixture remains as an independent
preflight-conflict case.

## Review checks

Traceability and seam choice pass. The executable and OpenSpec hashes match the
owner-approved v4 records, and task 3.1 names the additive capability/schema
migration. The deployment CLI and canonical migration application are the public
migration boundaries; the verifier does not call a planned private migration
helper.

Expected-value independence passes. The seven-table manifest is transcribed from
the approved typed persistence/evidence contract and uses repository migration
conventions rather than future production definitions. The test never imports a
production manifest or expected digest. Exact relational constraints and seeded
facts defeat an empty schema, a constant fingerprint and a capability-only
implementation.

Sensitivity passes for the reviewed Gate 2 scope. Omitting any of the seven
tables or required columns, weakening the current-leaf or identity constraints,
redirecting a named lineage relation, changing result/audit outcome constraints,
mutating compatible facts, or attempting repair after either partial conflict
changes an asserted observation. Random database names isolate every fixture,
safe identifier validation limits cleanup ownership, and `finally` drops only
the databases created by this run.

The intended RED remains honest: the verifier pins both approved specification
hashes and stops at the missing public `AssignmentOrderOriginalSchemaMigration`
class. A fresh predecessor run on the healthy repository-owned MariaDB contour
passes immediately before the RED, excluding broken database setup or a damaged
v1-v11 runner as the cause.

## Independent reproduction

At `2026-09-04T01:49:15+03:00` on exact reviewed commit
`8e6f71f6254dc7ba68bca0c473c6fc7efc00a161`:

```text
$ php -l tests/InstallationProcess/assignment_order_original_upload_001_schema_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_upload_001_schema_test.php

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ FMONITOR_TEST_DB_HOST=127.0.0.1 FMONITOR_TEST_DB_PORT=23306 \
  FMONITOR_TEST_DB_ADMIN_USER=root \
  FMONITOR_TEST_DB_ADMIN_PASSWORD=<redacted-local-test-secret> \
  php tests/InstallationProcess/production_migration_runner_001_test.php
PASS: PRODUCTION-MIGRATION-RUNNER-001 CLI contract

$ FMONITOR_TEST_DB_HOST=127.0.0.1 FMONITOR_TEST_DB_PORT=23306 \
  FMONITOR_TEST_DB_ADMIN_USER=root \
  FMONITOR_TEST_DB_ADMIN_PASSWORD=<redacted-local-test-secret> \
  php tests/InstallationProcess/assignment_order_original_upload_001_schema_test.php
PHP Fatal error: Uncaught TestFailure: INTENDED_RED: canonical additive
assignment-order-original schema migration v12 is missing.

$ git diff --check
PASS (no output before this review record)
```

`make test-db-up` is not a repository target. The already-running
`fmonitor2-test-test-db-1` contour reported healthy; the first probe used the
pilot fallback password and was correctly rejected, after which the explicit
test-contour credential reproduced the predecessor GREEN and intended RED above.
No production or test artifact was edited by this review.

## Gate decision

Fresh Gate 3 is `APPROVED`. Gate 4 may implement the minimal additive v12
migration against these reviewed expectations. Any change to the test or its
expected manifest restarts Gate 2 and requires another independent Gate 3.

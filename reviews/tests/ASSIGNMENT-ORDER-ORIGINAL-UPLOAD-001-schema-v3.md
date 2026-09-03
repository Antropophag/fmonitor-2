# Test rereview: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 schema migration v3

- Reviewer: separately tasked agent `/root/original_upload_migration_gate3_v3`
- Test author: separately tasked agent `/root/original_upload_migration_red`
- Reviewed commit: `0fd773c18eb7658493d89310d91079200f821c86`
- Specification: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v4, owner-approved hash `97a2527db60750089a53311856756b7db7b4682baf5c426a45503639ebde5479`; OpenSpec delta hash `127eddc8a0e7b3ce270b5c704ddf6a55022de22cd3d3447592402b426256cee2`
- Corrected test SHA-256: `9ae50ca790fc413e5c3cf1598ce066143558f5a9ef73712eeecd7591007d44ce`
- Verdict: `APPROVED`

## Prior finding disposition

The append-only v1 `CHANGES_REQUESTED` and v2 `APPROVED` records remain intact.
This v3 review addresses only the later Gate 4 discovery that the approved
column serializer emitted a trailing delimiter for MariaDB's empty `EXTRA`
value. The correction is valid: an ordinary column now serializes exactly as
`root_original_id:varchar(160):NO`, while a column with non-empty metadata still
serializes as `id:bigint unsigned:NO:auto_increment`.

The two direct pre-RED assertions independently fix both sensitivity examples.
They execute before the missing-v12 sentinel, so the reproduced intended RED
also proves that the corrected serializer examples pass. Exact comparison is
used: an implementation that drops `auto_increment`, invents it on an ordinary
column, or returns any other non-empty `EXTRA` remains observable.

## Regression and scope review

The test change does not alter any expected table, column type/nullability,
index, foreign key, CHECK, engine, collation or capability literal. The seven
original-evidence table manifest and its root/revision lineage, single-current
identity, terminal request shapes, accepted fingerprint/event identity,
rejected/conflict audit and bounded maintenance-result constraints remain
unchanged from the independently approved v2 verifier.

The clean, repeat, populated-v11, hostile-capability, malformed-root and
hostile-revision fixtures are likewise unchanged. Their assertions still cover
the exact canonical fingerprint, preservation of historical registration and
capability facts, preservation of representative original evidence, and exact
preservation of rows, definitions and `AUTO_INCREMENT` counters on repeat or
conflict. The serializer correction makes those existing assertions reachable
by a conforming future v12 implementation without weakening them.

Only the test oracle and append-only RED evidence changed after the v2 review;
no production or OpenSpec artifact changed. The public application/CLI seam,
approved hashes and explicit missing `AssignmentOrderOriginalSchemaMigration`
sentinel remain intact.

## Independent reproduction

At `2026-09-04T01:55:13+03:00` on exact reviewed commit
`0fd773c18eb7658493d89310d91079200f821c86`:

```text
$ sha256sum tests/InstallationProcess/assignment_order_original_upload_001_schema_test.php
9ae50ca790fc413e5c3cf1598ce066143558f5a9ef73712eeecd7591007d44ce  tests/InstallationProcess/assignment_order_original_upload_001_schema_test.php

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

The healthy v1-v11 predecessor passed immediately before the intended RED using
the explicitly supplied test-contour credential. The failure is therefore the
absent production v12 migration, not database setup, syntax, hash drift or the
corrected serializer.

## Gate decision

Fresh Gate 3 is `APPROVED`. Gate 4 may resume the minimal additive v12
implementation against this exact reviewed test. Any subsequent test or
expected-manifest change restarts Gate 2 and requires another independent Gate 3.

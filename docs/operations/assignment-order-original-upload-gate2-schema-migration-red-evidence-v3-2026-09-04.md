# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — schema oracle correction RED v3

Date: 2026-09-04 01:53:24 MSK (`2026-09-03T22:53:24Z`)
Gate: 2 oracle correction after the Gate 4 MariaDB finding
RED author: separately tasked agent `/root/original_upload_migration_red`
Base/reviewed test head: `b2addbf6f75564265519f30498c36b31769f1f65`

## Corrected defect

The approved v2 verifier serialized every observed MariaDB column as
`name:type:nullability:EXTRA`. For ordinary columns MariaDB returns an empty
`EXTRA`, producing a trailing colon absent from the independently fixed manifest.
A conforming schema therefore failed before its substantive lineage, index, FK
and CHECK assertions. This was a test-oracle defect, not a product failure.

The corrected `aoosColumnSignature()` omits the `:EXTRA` component only when
MariaDB returns the exact empty string. A non-empty value remains mandatory and
observable. Two pre-RED sensitivity assertions fix both examples:

```text
root_original_id:varchar(160):NO
id:bigint unsigned:NO:auto_increment
```

No expected column/type/nullability, table, index, FK, CHECK, capability,
preservation or conflict semantic changed. Production and OpenSpec artifacts
remain untouched; prior evidence and reviews remain append-only.

## Reproduced intended RED

Corrected test SHA-256:

```text
9ae50ca790fc413e5c3cf1598ce066143558f5a9ef73712eeecd7591007d44ce  tests/InstallationProcess/assignment_order_original_upload_001_schema_test.php
```

Commands and relevant output:

```text
$ php -l tests/InstallationProcess/assignment_order_original_upload_001_schema_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_upload_001_schema_test.php

$ git diff --check
PASS (no output)

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ FMONITOR_TEST_DB_HOST=127.0.0.1 FMONITOR_TEST_DB_PORT=23306 \
  FMONITOR_TEST_DB_ADMIN_USER=root \
  FMONITOR_TEST_DB_ADMIN_PASSWORD=<redacted-local-test-secret> \
  php tests/InstallationProcess/assignment_order_original_upload_001_schema_test.php
PHP Fatal error: Uncaught TestFailure: INTENDED_RED: canonical additive
assignment-order-original schema migration v12 is missing.
```

The two corrected serializer assertions pass before the unchanged missing-class
sentinel. The intended RED therefore remains the absent public v12 migration,
not the repaired oracle.

## Gate status

The schema migration verifier has returned to Gate 2 and is demonstrably RED for
the intended reason. Fresh independent Gate 3 approval is required before Gate
4 resumes. Task 3.1 remains open.

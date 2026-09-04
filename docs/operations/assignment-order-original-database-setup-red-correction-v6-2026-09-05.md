# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — database setup RED correction v6

Date: `2026-09-05`

RED author: separately tasked agent `/root/assignment_original_red2`

GREEN-attempt gap base: `ebc6cd0f351fea8efce75a7f9e7134d38ecb3eeb`

Outcome: **INTENDED RED — public migration seam absent**

The CHECK normalizer now narrowly maps only the exact MariaDB doubled
SQL-literal representation of the approved backslash in
`[[:cntrl:]/\\]` to the single approved regex representation used by
`Contract::checks()`. Operand, operator and all other pattern bytes remain
untouched; the existing `NOT REGEXP`/`!(... REGEXP ...)` equivalence remains.

Before the missing production class guard, the verifier now creates the exact
test-owned roots DDL in real MariaDB, reads it back through the table-scoped
CHECK observer and requires exact equality with all three roots CHECK oracles.
A second same-count roots table differs only by removing the backslash from the
control-character pattern and MUST remain unequal after the same round trip.
Both probe tables remain inside the already bounded-owned database and are removed
only by the bounded database cleanup.

Task 2.2 is rechecked. No production, spec, support or other OpenSpec artifact
changed.

## Reproduced evidence

```text
$ php -l tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_database_setup_001_test.php

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
Fatal error: Uncaught TestFailure: INTENDED_RED: approved AssignmentOrderOriginalSchemaMigration production seam is absent.
RED_ASSERTION: expected failing behavior observed in tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
exit 0

$ independent information_schema query for SCHEMA_NAME LIKE 't_aoou_%'
ASSIGNMENT_ORDER_ORIGINAL_DATABASE_SETUP_CLEANUP_OK

$ git diff --check
PASS (no output)
```

## Exact hashes

```text
e0d423537434ea0e63bcfb25d3907d67b3de8a2634f1d2ed12980dee7717b819  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
460c1aeb99cb13575e4a8870bee5afd502de75023987bae20ee1600c5361f297  tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php
30476c480d9ca9bdee836934ae70ffa89d075562c0c217382762b518b2ff1a48  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
7153fdafcb1f265211df18570242b3374c5f3e0b9c9f7284fe175a79dc7efca8  docs/operations/assignment-order-original-database-setup-green-attempt-regex-gap-2026-09-05.md
97292b3eae449c70586c91fcba825dbb7eb17df223bebbadd0b892275b5cc4af  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
```

This append-only record omits its own circular hash. Fresh independent Gate 3
review is required before setup implementation resumes.

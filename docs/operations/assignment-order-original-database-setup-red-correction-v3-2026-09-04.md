# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — database setup RED correction v3

Date: `2026-09-04`

RED author: separately tasked agent `/root/assignment_original_red2`

Prior Gate 3 v2: `9eb5019e619a5441f1137dec97ff863778804e26`,
verdict **CHANGES_REQUESTED**

Outcome: **INTENDED RED — public migration seam absent**

## Corrections

The deterministic schema snapshot used by every repeat/conflict/rollback
comparison now contains, for every binary-ordered table:

- engine and table collation;
- every ordered column including type, nullability, charset, collation, default
  and extra;
- normalized PK/unique/secondary keys and their column order;
- FK local/target columns plus update/delete actions;
- every normalized CHECK expression.

Therefore wrong-CHECK, opaque collation/default and multi-table conflict paths
cannot mutate or partially repair any normative schema property while returning
`CONFLICT`.

The contention probe now uses a duplex bounded protocol. A child first opens
its independent MariaDB connection and emits exact `READY <mode>`. Only after
the parent replies `ENTER <mode>` does it emit `ENTERED <mode>` immediately
before invoking the public seed/cleanup seam. The parent receives both bounded
handshakes before asserting that no terminal line is readable while its exact
identity lock is held. It then commits, requires exact `OK <mode>`, and reaps
the child. Every exceptional path rolls back, closes both descriptors and
terminates/reaps a remaining child.

No production, spec, OpenSpec or support bytes changed in this correction.

## Reproduced evidence

```text
$ php -l tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php
No syntax errors detected in tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php

$ php -l tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_database_setup_001_test.php

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
97292b3eae449c70586c91fcba825dbb7eb17df223bebbadd0b892275b5cc4af  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
30476c480d9ca9bdee836934ae70ffa89d075562c0c217382762b518b2ff1a48  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
460c1aeb99cb13575e4a8870bee5afd502de75023987bae20ee1600c5361f297  tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php
927b30034540da0195c38b12f7d1c0a0c82bff5dc97f95ee540a17c180269dc9  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
0399ea3dd131a70bb1b814e2d03fcdc133f73448ae18efb4877255ef8412af6f  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v2.md
f123a5938488573a934cbb3edbdf06519e5c1f81b43107e908094fa878cabadb  docs/operations/assignment-order-original-database-setup-red-correction-v2-2026-09-04.md
```

The record omits its own circular hash. Corrected bytes require a fresh
independent Gate 3 review.

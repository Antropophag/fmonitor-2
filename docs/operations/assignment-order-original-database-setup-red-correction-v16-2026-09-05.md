# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — database setup RED correction v16

Date: `2026-09-05`

RED author: separately tasked agent `/root/assignment_original_red2`

Gate 3 v15 base: `cf30a0421c32dbe982e16f368d2e6db3d80efa0c`,
verdict **CHANGES_REQUESTED**

Outcome: **INTENDED RED — public migration seam absent**

The TERM-ignoring hang control no longer performs inline cleanup. It captures
its live exact worker PID and throws `CONTROL_TRANSFER_LIVE_WORKER:<pid>` while
all process descriptors and the process handle are still owned by the shared
`finally`.

That actual `finally` records a deterministic PID-bound trace while it closes
descriptors, sends TERM, bounded-polls the worker still live, sends KILL,
bounded-polls stopped, and calls `proc_close`. After catching the intentional
transfer, the verifier requires the exact six-step trace
`FINALLY, TERM, TERM_LIVE, KILL, STOPPED, REAP` for the same PID. Removing or
breaking generic finally cleanup therefore fails the executable assertion.

Both already-open parent connections must answer after exceptional cleanup;
the final independent observer requires no bounded database or connected worker
to remain. Existing fixed stderr/exit-70 failure control and normal contention
paths remain unchanged.

Task 2.2 remains checked. No helper, support, production, spec or OpenSpec bytes
changed.

## Reproduced evidence

```text
$ php -l tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_database_setup_001_test.php

$ php -l tests/Support/assignment_order_original_fixture_worker.php
No syntax errors detected in tests/Support/assignment_order_original_fixture_worker.php

$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
Fatal error: Uncaught TestFailure: INTENDED_RED: approved AssignmentOrderOriginalSchemaMigration production seam is absent.
RED_ASSERTION: expected failing behavior observed in tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
exit 0

$ independent information_schema SCHEMATA/PROCESSLIST query for t_aoou_%
NO_AOOU_DATABASE_OR_CONNECTION_LEAKS

$ git diff --check
PASS (no output)
```

## Exact hashes

```text
b3f14655d3c31ca372966d4a8231fbf5511646554a77c7f06e688922ec1becd3  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
3480ea78ee679fd7ba9cd667bebaf5ddb7a71925eac318b42dbb22984753cd37  tests/Support/assignment_order_original_fixture_worker.php
2d03ad4d6d5d950e4a92592922193e683ee2b7f96451e3634c50efccd0a65a0d  tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php
2edbb932cbf6a4f00625eabf0832bf63f77eff4c7bb0926803f217f72c315795  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v15.md
0666f72cdfa874921c4daea2ea5ed54e286060d54b93deb732366c994bf08ad4  docs/operations/assignment-order-original-database-setup-red-correction-v15-2026-09-05.md
97292b3eae449c70586c91fcba825dbb7eb17df223bebbadd0b892275b5cc4af  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
30476c480d9ca9bdee836934ae70ffa89d075562c0c217382762b518b2ff1a48  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
```

This append-only record omits its own circular hash. Fresh independent Gate 3
review is required before implementation resumes.

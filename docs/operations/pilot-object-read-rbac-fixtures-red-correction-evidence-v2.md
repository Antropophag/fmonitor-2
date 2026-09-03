# PILOT-OBJECT-READ-RBAC-FIXTURES-001 — Gate 2 RED correction evidence v2

Date: 2026-09-03

Author: separately tasked Gate 2 agent `/root/object_list_red_correction`

Status: **RED_CAPTURED / READY_FOR_FRESH_INDEPENDENT_GATE_3**

## Exact scope and ordering

The corrected verifier reaches the production HTTP entrypoint at exact
`GET /pilot/objects`. Its first acceptance tracer committed-deletes the exact
canonical grant `(role_id=5101, permission=objects.read)` and requires the next
public request to return the exact 403 contract. Current pre-GREEN fixture code
delegates to the generic fixture and grants the actor through role `900018`, so
the public route incorrectly remains authorized. This is missing canonical
fixture behavior, not setup, routing, database, CSS, or navigation behavior.

The existing approved navigation-removal assertion remains unchanged and runs
after this own-slice tracer. The own-slice RED therefore occurs first without
discarding predecessor sensitivity.

## Blocking-review corrections represented in the test

- 503 now forbids `Retry-After` and checks the exact singleton application
  header inventory, security values, exact 12-hex correlation ID, one safe log
  category, response/log correlation equality, cardinality, and redaction for
  schema-invalid and read-failed branches.
- Fresh server processes explicitly cover unset, empty, `0`, `-1`, `abc`,
  leading-space ` 18`, trailing-space `18 `, missing and inactive actors,
  inactive/unassigned roles and all four near-match permissions. A valid trusted
  key is no longer invalidated by changing only `REMOTE_USER`.
- Negative cases use a separate reader granted SELECT only on the four canonical
  authorization tables. Any object, process, or legacy handler read therefore
  fails observably instead of satisfying an expected denial.
- Database snapshots include every table's exact `SHOW CREATE`, complete sorted
  rows and `AUTO_INCREMENT`. Protected filesystem snapshots include bytes and
  metadata. Foreign database and filesystem decoys are compared after the
  attempt-all, exactly-once cleanup inventory for server pipes/PID, DB resource,
  two DB principals, exact task database and mutable root.

## Exact commands and intended RED

```text
php -l tests/Support/PilotObjectReadRbacFixture.php
No syntax errors detected in tests/Support/PilotObjectReadRbacFixture.php

php -l tests/InstallationProcess/pilot_object_list_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_object_list_001_test.php

FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
  php tests/InstallationProcess/pilot_object_list_001_test.php
PHP Fatal error:  Uncaught TestFailure: canonical fixture revoke controls public list before navigation status
Expected: 403
Actual: 200
```

Exit status is `255`. Cleanup completed without masking or appending another
failure, and the command left no task database, test principal, or owned root.

## Reviewed-input hashes

```text
e3858f094c1f5c4411887b7a242122f714ec6d488febca87bd591553f5b05828  specs/PILOT-OBJECT-READ-RBAC-FIXTURES-001.md
6ab9b7fcc4e65e7f87fb8a46a39ef4c5c2ee7aec4ca98fefd14d25fd0a1d0616  tests/Support/PilotObjectReadRbacFixture.php
42e8c066638f41de4ca0486f489273d0e58ed45fa0467fcd56cfd7809d238c4c  tests/InstallationProcess/pilot_object_list_001_test.php
```

No production file or test review record was changed. Gate 3 remains open for
a fresh separately tasked reviewer of these exact blobs.

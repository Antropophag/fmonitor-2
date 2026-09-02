# PILOT-OBJECT-READ-RBAC-FIXTURES-001 — Gate 2 RED attempt

Date: 2026-09-02  
Author: separately tasked RED agent `/root/object_list_rbac_red`  
Status: **RED_CAPTURED / TASK 2.1 NOT COMPLETE**

## Scope

The test changes exercise the production HTTP entrypoint for exact
`GET /pilot/objects`. They add explicit process environments for the approved
positive actor, legacy-only identity, missing/inactive/unassigned actors,
case/space/wildcard/suffix near-match permissions, committed revoke and unknown
suffixes. Existing object-list representation and security assertions remain.

Negative cases use a dangling imported case plus complete DB/filesystem
snapshots: an authorization bypass would reach the list read and return 503,
instead of the expected generic 401/403, while mutation changes the snapshot.

## Command and intended RED

```text
php -l tests/Support/PilotObjectReadRbacFixture.php
No syntax errors detected in tests/Support/PilotObjectReadRbacFixture.php

php -l tests/InstallationProcess/pilot_object_list_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_object_list_001_test.php

FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
  php tests/InstallationProcess/pilot_object_list_001_test.php
PHP Fatal error: Uncaught TestFailure: authorized public list renders the exact canonical fixture actor
Expected: true
Actual: false
```

The route returned 200 before this assertion, proving that server, database,
CSS, authorization and list-handler setup were available. The failure is the
intended missing canonical fixture behavior: the temporary test boundary still
delegates to the generic `LocalRbacFixture`, which renders `Fixture 18` rather
than the independently approved `Сотрудник ФКР (тест)` manifest.

## Predecessor blocker retained

A diagnostic run before placing the new acceptance tracer first reached an
unchanged predecessor assertion and failed:

```text
PHP Fatal error: Uncaught TestFailure: shell navigation
Expected: 1
Actual: 0
```

Current `PilotView` renders `Моя работа` as a disabled span, while the inherited
`PILOT-OBJECT-LIST-001` test still requires one `/pilot/` link. The assertion
was not weakened or removed. Consequently the full new negative matrix cannot
yet be demonstrated in one run, and OpenSpec task 2.1 remains unchecked until
that predecessor regression is resolved or independently reclassified.

The direct test default also uses the stale admin password
`fmonitor2_demo_local`; the reproducible test container requires the explicit
command environment above (`fmonitor2_test_root_local`).

## Hashes

```text
7fb8bcb665bf673fc4d83b1fdc0ae345e47a4cca1c573e079b37203fa91079e7  tests/InstallationProcess/pilot_object_list_001_test.php
2b07fdc6c3d4ee04cce9150ef9b0dbb20aec40417011393cb5a82ac16e61345f  tests/Support/PilotObjectReadRbacFixture.php
e3858f094c1f5c4411887b7a242122f714ec6d488febca87bd591553f5b05828  specs/PILOT-OBJECT-READ-RBAC-FIXTURES-001.md
```

Gate 3 review is not requested by this record. No production code or review
record was authored.

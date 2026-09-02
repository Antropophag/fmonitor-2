# Independent Gate 3 test review — PILOT-E2E-RBAC-FIXTURES-001

Date: 2026-09-02  
Reviewer: fresh independent test agent `/root/e2e_rbac_test_review`  
Verdict: **CHANGES_REQUESTED**

The reviewer did not edit tests or production code. OpenSpec task `2.2` remains
open.

## Reproduced RED

Reviewed test SHA-256:
`086db8a5d2014346e97af0c10190df5582be1850a9d9fe6d51c104fc81d85067`.

The direct command without the harness admin override fails during environment
setup because `pefDb()` defaults to the demo root password rather than the
password from `compose.test.yaml`:

```text
php tests/InstallationProcess/pilot_e2e_flow_001_test.php
mysqli_sql_exception: Access denied for user 'root'@'172.29.0.1'
```

With the canonical test DB admin password supplied explicitly, the intended
authorization RED is reached before any combined-PDF assertion:

```text
FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
  php tests/InstallationProcess/pilot_e2e_flow_001_test.php

TestFailure: isolated actor18 exact grant admits first list
Expected: 200
Actual: 403
```

This is an RBAC RED, but it is not sufficient evidence for approval because the
test does not yet make all approved requirements falsifiable.

## Blocking findings

1. **The isolated branch does not clone the approved exact actor-18 authority.**
   `LocalRbacFixture::install()` creates role `900018`, code `fixture_18`, while
   the approved spec requires role `5301`, code `objects_reader`, and the test
   itself expects that tuple only later in the main branch. The isolated revoke
   then explicitly deletes role `900018`. A future implementation could satisfy
   this branch with a second, non-canonical authority model. Seed and assert the
   exact `5301/objects_reader/objects.read` tuple before its DELETE.

2. **Actor-19 denial has no required pre-handler read sentinel.** The negative
   server reuses the main DB principal with `SELECT, INSERT, UPDATE` across the
   whole schema. Checking that the generic body lacks literal `4512` cannot
   detect a handler read whose result is discarded or redacted. The approved
   contract requires a negative principal with SELECT only on the four exact
   local-RBAC tables, so any legacy/object/process read becomes a DB failure.

3. **Revoke/repeat snapshots are materially narrower than the approved
   contract.** `pefRbacRows()` snapshots only ordered rows in four RBAC tables.
   It does not compare `SHOW CREATE`, all task-DB tables, `AUTO_INCREMENT`, the
   public process projection/artifact metadata, or owned storage path/type/dev/
   inode/mode/uid/gid/size/hash. Consequently unnoticed schema, counter,
   process, artifact, or storage mutation can still pass.

4. **Main-grant preservation is not tested at the downstream artifact
   boundary.** `$mainGrantBefore` is checked once before the main journey and is
   never compared with a full snapshot immediately before the combined-PDF
   assertions. This does not prove that the isolated branch and intervening
   authorization work left the main fixture byte-equivalent when the boundary
   is reached.

5. **Cleanup sensitivity is incomplete and is not attempt-all.** The isolated
   branch owns only a DB/user in the test, has no explicit session/mutable/
   artifact roots or foreign decoys, and sequential cleanup queries can prevent
   later cleanup after the first exception. There is also no postcondition
   proving that the exact DB/user/process/roots are gone while foreign decoys
   remain.

6. **The mandatory authority/ambient-input matrix is absent from this RED.**
   Missing/empty/invalid actor 401, unknown/inactive user, inactive role,
   near-match permission, schema/read-unavailable 503, and request-header/
   cookie/`REMOTE_USER` spoof resistance are not asserted for the list seam.
   Existing predecessor checks on other routes cannot approve this fixture
   contract.

## Downstream combined-PDF boundary

The current run stops at the isolated RBAC assertion, so it correctly does not
misclassify the result as combined-PDF RED. The combined-PDF assertions later in
the same large test belong to the separately approved
`PILOT-E2E-COMBINED-PDF-001`; this review neither approves nor rejects those
assertions. RBAC Gate 3 can be approved only after the missing boundary snapshot
proves RBAC prerequisites and then leaves the PDF result visibly downstream.

## Reviewed hashes

```text
83dee68e5df98c3a51d895e4d8c0d2f712cfc4e3bd3ce0f2af3d6217510f0217  specs/PILOT-E2E-RBAC-FIXTURES-001.md
086db8a5d2014346e97af0c10190df5582be1850a9d9fe6d51c104fc81d85067  tests/InstallationProcess/pilot_e2e_flow_001_test.php
1099a646367239acd2a662e7833ee63eaf35c87985f907f2f41cb5e622fa763f  openspec/changes/pilot-e2e-rbac-fixtures/tasks.md
```

## Gate decision

**CHANGES_REQUESTED.** Do not mark task `2.2`. Correct the test and provide a
fresh independent review over new exact hashes. The current RBAC RED is real,
but the suite can still accept implementations that violate the approved exact
authority, sentinel, mutation-isolation, cleanup, and downstream-boundary
requirements.

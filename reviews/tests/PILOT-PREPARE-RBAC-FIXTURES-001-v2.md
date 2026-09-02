# Independent Gate 3 test rereview — PILOT-PREPARE-RBAC-FIXTURES-001 v2

Date: 2026-09-02  
Reviewer: fresh independent agent `/root/prepare_rbac_test_rereview`  
Test author: `/root/prepare_rbac_red`  
Prior verdict: `CHANGES_REQUESTED`  
Verdict: **CHANGES_REQUESTED**

Reviewer did not edit tests or production code. Task 2.2 remains open.

## Reproduction

The owner-approved executable contract and corrected verifier have the recorded
hashes. OpenSpec is strict-valid, PHP syntax is valid, MariaDB is healthy, and
the canonical public-seam run reproduces the intended RED:

```text
openspec validate pilot-prepare-rbac-fixtures --strict
Change 'pilot-prepare-rbac-fixtures' is valid

php -l tests/InstallationProcess/pilot_prepare_form_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_prepare_form_001_test.php

make test-env-up
Container fmonitor2-test-test-db-1 Healthy

FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
FMONITOR_TEST_DB_PORT=23306 \
php tests/InstallationProcess/pilot_prepare_form_001_test.php

TestFailure: GET 503 safe correlation /pilot/objects/4512/assignment-order/prepare
Expected: 1
Actual: 0
```

The failure is an intended successor-contract assertion at the raw HTTP seam,
not environment/setup failure.

## Prior blockers

The correction closes four prior findings:

- committed local and process-capability revokes each exercise GET and HEAD and
  restore actor 18's main fixture;
- `ppfParity()` validates a safe correlation independently for GET and HEAD on
  every prepare-route 503, including local-auth and inherited DB/object/catalog
  classes;
- isolated missing, invalid `018`, and explicit replacement identities are
  present, and foreign DB/user/root decoys are verified after owned cleanup;
- PUT/PATCH/DELETE use a bounded incomplete 1 MiB request-body probe, retain
  exact `Allow: GET, HEAD, POST`, and the reproduced run passes these probes
  before reaching the later RED.

The local/process/object ordering is also materially stronger: an unavailable
process table plus corrupt object protects every local denial, and a corrupt
object protects local-exact/process-missing and both revoke denials.

## Remaining blocking finding

**The approved renderer sentinel is still not an observation of renderer
invocation.** Section 4 requires test-owned sentinels which *separately observe*
the local query, process query, object/form reader, and renderer, with every
denial proving downstream sentinels untouched. Lines 72, 74, 78 and 79 merely
assert that successful-render text (`77-000123`, installer name, and `Состав
распоряжения`) is absent from a generic 403 body. An implementation may invoke
the renderer before authorization and discard its result, yet all these
assertions still pass. The corrupt-object fixture observes/prevents the object
reader, but it does not independently observe whether the renderer was called.

Add a test-owned renderer spy/counter (or an equivalent externally observable
render-only probe) at the public composition seam and assert zero invocations
for each denial class. This must not weaken the raw HTTP response assertions or
replace the existing object/process/local sentinels. Because this changes the
test, Gate 2 must retain a qualifying RED and return to a fresh Gate 3 review.

## Gate decision

The corrected test is not yet APPROVED against the exact owner-approved spec.
Task 2.2 was not marked complete and Gate 4 remains unauthorized.

## Reviewed hashes

```text
565804719e95171fa82523f6f883b8abebc9d8f0e36ca9746612fb8f7daab01e  specs/PILOT-PREPARE-RBAC-FIXTURES-001.md
62409b6a18bba29992c464fbbe60ff69744b3f8eeb5a4d1187dbbb2cfcb7cd4f  openspec/changes/pilot-prepare-rbac-fixtures/proposal.md
85189e1f36123b119806b3af55ca23312dd2787eeebcbe81f27164c52a034d95  openspec/changes/pilot-prepare-rbac-fixtures/design.md
412e496cd1240b26e6d694dc52e729f860a2a3830afced9cfe7f32806960c160  openspec/changes/pilot-prepare-rbac-fixtures/tasks.md
4c5423ca15dadc2d6bfd18ec683b3bafc5f9178d9a54ddc220d3139aab2b6f00  openspec/changes/pilot-prepare-rbac-fixtures/specs/verification/pilot-prepare-rbac-fixtures/spec.md
3ee482ff2368ff4e2d8118913a2082bfa538ba67aefc22db9e86a6b6705e7c20  tests/InstallationProcess/pilot_prepare_form_001_test.php
19f3cee8cba95bfb83d232876ac3b1241c6b6e5f439de40d8c87a00f0ca54cc2  docs/operations/pilot-prepare-rbac-fixtures-red-evidence.md
485a1140343e4f7922e0682ba338e87942bf0a3a38b9ac612ac92c5ed21e40c1  docs/operations/morning-owner-approval-decision-2026-09-02.md
```

# Independent Gate 3 test rereview — PILOT-PREPARE-RBAC-FIXTURES-001 v4

Date: 2026-09-02  
Reviewer: fresh independent agent `/root/prepare_test_review`  
Test author: `/root/prepare_rbac_red`  
Gate: replacement Gate 2 RED v4 after owner-approved exact factory API  
Verdict: **CHANGES_REQUESTED**

The reviewer authored neither the reviewed tests nor production code and did
not edit either during this review. Task 2.2 remains open and Gate 4 is not
authorized.

## Reproduction

The owner-approved executable/OpenSpec hashes match the exact API approval
record (apart from the expected task-state checkbox update), OpenSpec is
strict-valid, both PHP files are syntactically valid, and diff hygiene passes.
The canonical raw-HTTP run reaches the intended current-production failure:

```text
openspec validate pilot-prepare-rbac-fixtures --strict
Change 'pilot-prepare-rbac-fixtures' is valid

php -l tests/InstallationProcess/pilot_prepare_form_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_prepare_form_001_test.php

php -l tests/Support/pilot_prepare_renderer_spy_router.php
No syntax errors detected in tests/Support/pilot_prepare_renderer_spy_router.php

FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
FMONITOR_TEST_DB_PORT=23306 \
php tests/InstallationProcess/pilot_prepare_form_001_test.php

TestFailure: GET 503 safe correlation /pilot/objects/4512/assignment-order/prepare
Expected: 1
Actual: 0
```

MariaDB setup, fixture creation, raw HTTP execution and cleanup complete. This
is a product-contract RED rather than a setup failure.

The isolated predecessor API probe also reproduces the evidence record:

```text
parameters=1 decorator_interface=no identity_decorator=no
```

## Corrected canonical composition

The v4 router no longer reconstructs the application graph. Its only
composition call is
`ProductionPilotHttpEntrypointFactory::create($environment, $decorator)`. The
spy accepts a `PrepareFormRenderer`, wraps it, and delegates both `render()` and
`renderCompatibility()` to that exact object. It does not instantiate a
renderer, inspect the graph, use reflection/eval/shadowing, or replace the real
renderer with test behavior. When the approved interface exists PHP will
enforce the exact decorator interface. These points close the manual-graph
finding from v3.

The GET/HEAD authority matrix, separate local/process revokes, method-before-
body sensitivity, identity isolation, object/state/DB outcomes, read-only DB
and filesystem guards, foreign decoys, redaction and finally cleanup remain
substantial and correctly scoped.

## Blocking findings

1. **There is still no executed positive spy sensitivity control.** On the
   predecessor, PHP accepts the extra argument to the current one-parameter
   user-defined factory method, so the router starts while the decorator is
   silently unused. The run then observes a zero counter for all denial cases
   and fails at the DB-correlation RED before reaching the first allowed form
   request. Thus the passing zero assertion cannot distinguish correct
   canonical instrumentation from an entirely disconnected spy. The evidence
   record's structural `parameters=1` probe documents the absent API, but the
   verifier does not assert it and it does not establish that the same HTTP
   composition can increment the counter. Gate 2 needs a bounded positive
   sensitivity path that executes before the selected qualifying RED, or a
   captured assertion set which reports the API/positive-spy RED only after the
   denial sentinels have executed.

2. **The verifier never asserts the approved positive invocation count.** The
   sole `ppfRenderCount()` assertion requires `0` after rejections. There is no
   later assertion requiring one invocation for an allowed GET and one for an
   allowed HEAD (nor any per-request reset/delta assertion). Even after
   production implements the decorator seam and authority behavior, a factory
   which decorates correctly for denials but bypasses the returned renderer on
   success can pass all renderer-counter assertions. This directly misses the
   owner-approved requirement that every allowed request invoke the canonical
   real renderer exactly once through the spy and return its delegated bytes.

3. **The exact-once `decorate()` factory contract is not observed.** The
   approved API requires the factory to call `decorate()` exactly once with its
   one real `ProductionPrepareFormRenderer`. The test decorator records only
   renderer method calls. A factory that calls `decorate()` multiple times but
   happens to use one returned wrapper can satisfy the current test. Add a
   separate decorate-call counter/type assertion (without giving the decorator
   environment or graph access) so production alone must satisfy the exact
   factory contract.

These are verifier completeness defects, not requests to weaken the RED or to
reintroduce a test-owned composition graph. The existing denial matrix and
read-only guards should remain intact.

## Gate decision

Gate 3 is not approved. Correct the positive sensitivity and exact call-count
observability, preserve the canonical-factory-only router and full rejection
matrix, reproduce a qualifying RED after the relevant sentinels actually run,
then obtain another fresh independent Gate 3 review. Production GREEN remains
unauthorized.

## Reviewed hashes

```text
d591fd30f356ac59cfea34623a8311d07eb39cf41442892bbe42ef7d9d2e6062  specs/PILOT-PREPARE-RBAC-FIXTURES-001.md
d791104bf14b17911b4e23e90d0eef7a3e0f7f41cb12960c4ca4e9eec3fc9e97  openspec/changes/pilot-prepare-rbac-fixtures/proposal.md
eb386f4b40c976dcd9d371eda5f081763404ce8193017ff004fb147a825c9b60  openspec/changes/pilot-prepare-rbac-fixtures/design.md
6829cb04ccf50a03cd68f3bbd3ce09aa9a8208185a74a906f55e7c913fe3b1d5  openspec/changes/pilot-prepare-rbac-fixtures/tasks.md
494e3b3cb77e20c5448fd0f3265c4dcf9420da72316f126bd59b92509c4a1c39  openspec/changes/pilot-prepare-rbac-fixtures/specs/verification/pilot-prepare-rbac-fixtures/spec.md
2657f1f760e9bef056e8b462b5f313087569889fecdab6a9d01e006c4671c2c5  docs/operations/pilot-prepare-rbac-v2-api-exact-hash-approval-2026-09-02.md
1aad947df36728a9e152dfe21738a2ae2fb620d4b16537efdd939dbbbe58b5b3  tests/InstallationProcess/pilot_prepare_form_001_test.php
e7596aeceb7d0ec70d8c3efefe62c231d9b4f871c1cbaf6d3d4764734f02f128  tests/Support/pilot_prepare_renderer_spy_router.php
587acdf5c1ab6fe6b63fc18c91e3f054019f6661cb9b2630dbe5c7e3dfc39bdb  docs/operations/pilot-prepare-rbac-fixtures-red-evidence.md
```

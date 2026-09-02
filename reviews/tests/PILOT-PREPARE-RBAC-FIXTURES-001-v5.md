# Independent Gate 3 test rereview — PILOT-PREPARE-RBAC-FIXTURES-001 v5

Date: 2026-09-02  
Reviewer: fresh independent agent `/root/prepare_test_rereview`  
Test author: `/root/prepare_rbac_red`  
Gate: corrected Gate 2 RED v5 after v4 `CHANGES_REQUESTED`  
Verdict: **APPROVED**

The reviewer authored neither the reviewed tests nor production code and did
not edit either during this review.

## Reproduction

The owner-approved executable/OpenSpec hashes still match the exact API
approval record, apart from the expected task-state checkbox update. OpenSpec
is strict-valid, all three reviewed PHP files are syntactically valid, and diff
hygiene passes.

```text
openspec validate pilot-prepare-rbac-fixtures --strict
Change 'pilot-prepare-rbac-fixtures' is valid

php -l tests/InstallationProcess/pilot_prepare_form_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_prepare_form_001_test.php

php -l tests/Support/PrepareRendererInvocationSpy.php
No syntax errors detected in tests/Support/PrepareRendererInvocationSpy.php

php -l tests/Support/pilot_prepare_renderer_spy_router.php
No syntax errors detected in tests/Support/pilot_prepare_renderer_spy_router.php

git diff --check
# no output
```

The canonical raw-HTTP run completes fixture setup, the positive spy control,
CSS sensitivity, the complete authority/predecessor matrix and cleanup, then
reaches the intended current-production failure:

```text
FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
FMONITOR_TEST_DB_PORT=23306 \
php tests/InstallationProcess/pilot_prepare_form_001_test.php

TestFailure: GET 503 safe correlation /pilot/objects/4512/assignment-order/prepare
Expected: 1
Actual: 0
```

No `t_ppf_*`/`foreign_ppf_*` database, `ppf_*`/`foreign_*` user, or matching
task/foreign temporary root remained after the failing run. This is a
qualifying product-contract RED, not a setup or cleanup failure.

## Review findings

The v4 blockers are closed:

1. Before any selected route RED, the exact shared spy implementation executes
   a bounded positive sensitivity control. It delegates independently fixed
   `render()` and `renderCompatibility()` bytes unchanged and proves exact
   decorate/render counter increments before resetting both counters.
2. Allowed raw-HTTP GET and HEAD have explicit per-request counter deltas:
   each server composition must add exactly one `decorate()` call and each
   request must add exactly one delegated real-renderer invocation. GET retains
   the complete byte/body oracle; HEAD requires matching status, application
   headers and GET `Content-Length` with an empty body.
3. The router passes a decorator with the production-renderer type guard to
   `ProductionPilotHttpEntrypointFactory::create($environment, $decorator)`.
   It has no manual graph, reflection, eval, shadow factory or test-owned
   replacement renderer. The decorator receives only the factory-created
   renderer and the wrapper delegates the exact arguments/results.

The rejection matrix remains complete and executes before its combined zero
render assertion: absent/near-match/inactive local authority, process-only and
legacy-only actors, local-only authority, committed local and process revokes,
isolated local-table fault, unsupported methods with an incomplete body,
missing/invalid/replacement identity, unknown object, wrong state and DB fault.
Corrupt object/process-table sentinels preserve the specified ordering.
GET/HEAD parity, read-only DB/filesystem guards, redaction, task ownership,
foreign decoys and `finally` cleanup remain intact.

No blocker or non-blocking finding was identified. Gate 3 is approved for the
exact hashes below. Any test/support change after these hashes restarts Gate 2;
this approval authorizes minimal Gate 4 only and is not a GREEN or Done verdict.

## Reviewed hashes

```text
d591fd30f356ac59cfea34623a8311d07eb39cf41442892bbe42ef7d9d2e6062  specs/PILOT-PREPARE-RBAC-FIXTURES-001.md
d791104bf14b17911b4e23e90d0eef7a3e0f7f41cb12960c4ca4e9eec3fc9e97  openspec/changes/pilot-prepare-rbac-fixtures/proposal.md
eb386f4b40c976dcd9d371eda5f081763404ce8193017ff004fb147a825c9b60  openspec/changes/pilot-prepare-rbac-fixtures/design.md
6829cb04ccf50a03cd68f3bbd3ce09aa9a8208185a74a906f55e7c913fe3b1d5  openspec/changes/pilot-prepare-rbac-fixtures/tasks.md
494e3b3cb77e20c5448fd0f3265c4dcf9420da72316f126bd59b92509c4a1c39  openspec/changes/pilot-prepare-rbac-fixtures/specs/verification/pilot-prepare-rbac-fixtures/spec.md
2657f1f760e9bef056e8b462b5f313087569889fecdab6a9d01e006c4671c2c5  docs/operations/pilot-prepare-rbac-v2-api-exact-hash-approval-2026-09-02.md
7e9129b218e917f8bebdf945d2b893da2aa38922686c163845dc63a1518e45f9  tests/InstallationProcess/pilot_prepare_form_001_test.php
046e0ccac03b9ccfd94bf1c41fb476d5cc65db5914eb7024838ba1c1b26d3d70  tests/Support/PrepareRendererInvocationSpy.php
7bef82320c08b1f21f3316a3f5872f2c74e7cfc8471a0fa9ff95b5329f9521c6  tests/Support/pilot_prepare_renderer_spy_router.php
4ff286f1c0f3b9a764b217dd2dc0338f55b34ea76244acbba6ceec353e79d6c0  docs/operations/pilot-prepare-rbac-fixtures-red-evidence.md
```

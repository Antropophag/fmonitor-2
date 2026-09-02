# Independent Gate 3 test rereview — PILOT-PREPARE-RBAC-FIXTURES-001 v7

Date: 2026-09-02  
Reviewer: fresh independent agent `/root/prepare_v3_test_review`  
Test author: `/root/prepare_rbac_red`  
Gate: replacement Gate 2 RED v7 against owner-approved v3 transport boundary  
Verdict: **APPROVED**

The reviewer authored neither the reviewed tests nor production code and did
not edit either during this review. This append-only review record is the
reviewer's only change.

## Reproduction

The owner-approved executable/OpenSpec hashes match the approval record. The
only current task-file hash difference is the expected post-approval completion
of tasks 1.5 and 2.1; no normative contract text changed. OpenSpec is
strict-valid, all reviewed PHP files are syntactically valid, and scoped diff
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

git diff --check -- <reviewed paths>
# no output
```

The canonical raw-HTTP run reaches the application for all three fully
delivered unsupported methods and reproduces the intended successor-aware RED:

```text
FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
FMONITOR_TEST_DB_PORT=23306 \
php tests/InstallationProcess/pilot_prepare_form_001_test.php

TestFailure: PUT before authority/DB allow
Expected: 'GET, HEAD, POST'
Actual: 'GET, HEAD'
```

This is a product RED at the public HTTP seam, not a setup failure.

## Review findings

- The v7 verifier sends complete bounded 258-byte bodies for `PUT`, `PATCH`
  and `DELETE`, half-closes the client write side, and requires each request to
  reach the application promptly. It does not claim or attempt to observe
  whether the PHP transport buffered or consumed those bodies.
- Each method response is captured before assertions. The database and guarded
  filesystem snapshots are checked around every request; the payload sentinel
  is absent from every response. With missing identity and deliberately invalid
  DB credentials, exact method admission is isolated from authorization,
  database, domain and form behavior.
- Before the selected `Allow` failure, the verifier proves an exact aggregate
  delta of three factory `decorate()` calls for the three requests and zero
  wrapped-renderer calls. The subsequent exact checks require `405`, body
  `Method not allowed.\n`, `Allow: GET, HEAD, POST`, content length, no retry
  header and redaction for every method.
- The router calls only
  `ProductionPilotHttpEntrypointFactory::create($environment, $decorator)`.
  The decorator receives the factory-created real
  `ProductionPrepareFormRenderer`, wraps it once, and delegates exact renderer
  inputs and bytes; it has no environment, authorization or graph access.
- The executed isolated sensitivity control proves exact decoration/render/
  compatibility counts and byte delegation, then resets both counters. The
  allowed canonical GET and HEAD checks separately require per-request
  `decorate()` and real-renderer deltas, exact GET body assertions, status and
  application-header parity, GET content length, and empty HEAD body.
- The prior local/process one-sided grants, inactive and near-match chains,
  committed revokes, missing/invalid/replacement identity, unknown object,
  wrong state, DB/local faults, non-enumeration, read-only snapshots and cleanup
  matrix remains present and executes before the intended RED. No ambient-email
  or legacy/process fallback can satisfy the local grant.

The v6 blocking finding is closed because the owner-approved v3 contract no
longer asserts the unobservable transport-level no-read property. The verifier
retains all observable security and state guarantees without introducing a
body-observation seam.

## Gate decision

Gate 3 is **APPROVED** for the exact v7 hashes below. Task 2.2 may be marked
complete and minimal Gate 4 implementation may begin. Any change to the test or
test-support hashes restarts Gate 2 and requires a fresh independent review.

## Reviewed hashes

```text
2736c142c2c4535b6541b08764ef5cfea034434291657935b718945b67b55818  specs/PILOT-PREPARE-RBAC-FIXTURES-001.md
d7a3c0255c7d81432be2e69918449dcd0d8280556e4ce2fde0af5c2f4cfae1b9  openspec/changes/pilot-prepare-rbac-fixtures/proposal.md
816dd64e27d505ef12a6c5660739ef21c4c44874ea45b690e02f910c2838d768  openspec/changes/pilot-prepare-rbac-fixtures/design.md
a6effe83c1163a55a0e9719c3858721814c9df1052ca180def21c87bf3fd9270  openspec/changes/pilot-prepare-rbac-fixtures/tasks.md
3e51342ed87eddecb1c30bc4cd4218cf3d6f704d340b2431423b582c9e918beb  openspec/changes/pilot-prepare-rbac-fixtures/specs/verification/pilot-prepare-rbac-fixtures/spec.md
edda5307311eb395e104e34f407a02f01f2bbf255d17476a1901b6e99ada2886  tests/InstallationProcess/pilot_prepare_form_001_test.php
046e0ccac03b9ccfd94bf1c41fb476d5cc65db5914eb7024838ba1c1b26d3d70  tests/Support/PrepareRendererInvocationSpy.php
7bef82320c08b1f21f3316a3f5872f2c74e7cfc8471a0fa9ff95b5329f9521c6  tests/Support/pilot_prepare_renderer_spy_router.php
4aef8ec7fd076a8319693b236e839c2449bff305cba6af5b02a5d9e1d4ebce56  docs/operations/pilot-prepare-rbac-fixtures-red-evidence.md
```

# Independent Gate 3 test rereview — PILOT-PREPARE-RBAC-FIXTURES-001 v3

Date: 2026-09-02  
Reviewer: fresh independent agent `/root/prepare_rbac_test_rereview_v3`  
Test author: `/root/prepare_rbac_red`  
Prior verdict: `CHANGES_REQUESTED`  
Verdict: **CHANGES_REQUESTED**

Reviewer did not edit tests or production code. Task 2.2 remains open.

## Reproduction

The owner-approved executable contract is unchanged, OpenSpec is strict-valid,
both PHP files are syntactically valid, MariaDB is healthy, and the canonical
raw-HTTP run reproduces the intended successor RED:

```text
openspec validate pilot-prepare-rbac-fixtures --strict
Change 'pilot-prepare-rbac-fixtures' is valid

php -l tests/InstallationProcess/pilot_prepare_form_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_prepare_form_001_test.php

php -l tests/Support/pilot_prepare_renderer_spy_router.php
No syntax errors detected in tests/Support/pilot_prepare_renderer_spy_router.php

make test-env-up
Container fmonitor2-test-test-db-1 Healthy

FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
FMONITOR_TEST_DB_PORT=23306 \
php tests/InstallationProcess/pilot_prepare_form_001_test.php

TestFailure: GET 503 safe correlation /pilot/objects/4512/assignment-order/prepare
Expected: 1
Actual: 0
```

This remains a qualifying RED in the public response contract, not an
environment/setup failure.

## Prior renderer blocker: partially corrected

`PrepareRendererInvocationSpy` is not a renderer behavioral substitute: it
implements the existing renderer contracts, increments a task-owned counter,
and delegates both `render()` and `renderCompatibility()` to the real
`ProductionPrepareFormRenderer`. The denial requests use the raw HTTP entrypoint
and the same production application/coordinator/dependency classes.

However, the current evidence still does not prove the required sentinel.

## Blocking findings

1. **The renderer-counter assertion is unreachable in the canonical RED.** The
   reproduced failure is thrown by `ppfParity()` during `$precedenceDb` on line
   90. The only `ppfRenderCount()` assertion is the following statement on line
   91, so it never executes. Contrary to the v3 RED record, the canonical v3 run
   does not pass the renderer assertion. A premature renderer call in any of the
   preceding local/process/revoke/method/identity/object/state cases therefore
   would not be reported by this Gate 2 run. Assert the counter before entering
   the intended failing correlation probe, or capture that response without an
   early assertion and check all accumulated sentinel results before emitting
   the qualifying RED.

2. **The spy has no executed positive sensitivity control.** The counter is
   initialized to `0` and only compared with `0`; there is no assertion proving
   that a successful GET and HEAD through this composition actually invoke the
   spy/real renderer and increment it. The nominal successful render appears
   after the intended RED and is unreachable in Gate 2. A broken counter, an
   unselected spy, or composition drift could therefore make every denial check
   vacuously pass. Add a bounded, independently executed composition probe that
   proves the same HTTP route reaches the spy and delegates to the real renderer,
   while keeping the denial counter isolated at zero.

3. **The HTTP router reconstructs rather than uses the canonical production
   factory.** `pilot_prepare_renderer_spy_router.php` manually duplicates the
   object graph from `ProductionPilotHttpEntrypointFactory::create()`. It uses
   production classes and the real renderer, but it does not execute the
   production factory/entrypoint composition used by `public/router.php`.
   Consequently a production-factory wiring regression can be hidden by the
   test-owned graph. Introduce a narrow test decoration seam in the canonical
   factory (or an equivalent factory-owned composition path) so the spy wraps
   only the renderer while the actual production composition remains under
   test. This is composition instrumentation, not permission to replace route,
   authorization, reader, or renderer behavior in the test.

The v2 corrections for committed revokes, GET/HEAD correlation expectations,
identity isolation, foreign decoys, incomplete-body method sensitivity, and
local/process/object sentinels remain present. They do not close the three
renderer-evidence defects above.

## Gate decision

Gate 3 is not APPROVED. Task 2.2 was not marked complete and Gate 4 remains
unauthorized. Correct the test/evidence, retain a qualifying RED after all
sentinel assertions have actually executed, and return to a fresh independent
Gate 3 review.

## Reviewed hashes

```text
565804719e95171fa82523f6f883b8abebc9d8f0e36ca9746612fb8f7daab01e  specs/PILOT-PREPARE-RBAC-FIXTURES-001.md
62409b6a18bba29992c464fbbe60ff69744b3f8eeb5a4d1187dbbb2cfcb7cd4f  openspec/changes/pilot-prepare-rbac-fixtures/proposal.md
85189e1f36123b119806b3af55ca23312dd2787eeebcbe81f27164c52a034d95  openspec/changes/pilot-prepare-rbac-fixtures/design.md
412e496cd1240b26e6d694dc52e729f860a2a3830afced9cfe7f32806960c160  openspec/changes/pilot-prepare-rbac-fixtures/tasks.md
4c5423ca15dadc2d6bfd18ec683b3bafc5f9178d9a54ddc220d3139aab2b6f00  openspec/changes/pilot-prepare-rbac-fixtures/specs/verification/pilot-prepare-rbac-fixtures/spec.md
66827a9829011b1c4edaa0b2004d61f89e6656ac22af80b51d4d782238232d1e  tests/InstallationProcess/pilot_prepare_form_001_test.php
69ddd0ff1f88c929ffd9247a77c2e066173a34f57fb41165a500dad8bd8a2d3f  tests/Support/pilot_prepare_renderer_spy_router.php
48e87cd87e70236e77d67db60b94c4cb43cab92f35f70a3ef19f3a0f134451d9  docs/operations/pilot-prepare-rbac-fixtures-red-evidence.md
485a1140343e4f7922e0682ba338e87942bf0a3a38b9ac612ac92c5ed21e40c1  docs/operations/morning-owner-approval-decision-2026-09-02.md
```

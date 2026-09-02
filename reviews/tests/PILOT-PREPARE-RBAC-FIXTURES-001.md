# Independent Gate 3 test review — PILOT-PREPARE-RBAC-FIXTURES-001

Date: 2026-09-02  
Reviewer: fresh independent agent `/root/prepare_rbac_test_review`  
Test author: `/root/prepare_rbac_red`  
Verdict: **CHANGES_REQUESTED**

Reviewer did not edit tests or production code. Task 2.2 remains open.

## Gate 2 reproduction

The owner-approved executable contract matches the approval record. The public
verifier is syntactically valid, the OpenSpec package is strict-valid, and the
canonical run reaches the intended public HTTP seam after a healthy MariaDB
setup:

```text
make test-env-up
FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
FMONITOR_TEST_DB_PORT=23306 \
php tests/InstallationProcess/pilot_prepare_form_001_test.php

TestFailure: local authorization schema fault status
Expected: 503
Actual: 403
```

This is a qualifying RED for missing local-RBAC ownership, not an environment
or fixture failure. The current successor-aware wrong-method expectation also
correctly remains `Allow: GET, HEAD, POST`, although execution stops at the
earlier qualifying authority assertion.

## Blocking findings

1. **The required separate admission sentinels are not present.** Section 4 of
   the approved spec requires test-owned sentinels which separately observe the
   local authorization query, process-capability query, object/form reader and
   renderer, and requires every denial to prove all downstream sentinels
   untouched. Lines 53–69 use one corrupt object as an indirect object-reader
   sentinel. That can show only that a denial did not reach object integrity; it
   cannot show whether a local denial queried process capability, and it does
   not observe the renderer. The local-exact/process-missing case at line 71 is
   run against a valid object, so an implementation that reads the object before
   denying can pass. This leaves the central dual-gate precedence contract
   insensitive to plausible regressions.

2. **The required committed process-capability revoke branch is absent.** The
   test covers committed deletion/restoration of local permission (lines 72–75),
   but never deletes actor 18's `assignment_order.prepare` process capability
   while keeping the local grant intact. Section 4 requires subsequent GET and
   HEAD to return 403 after the local sentinel and before object read, with the
   main positive fixture restored. Static actor 20 is not equivalent: it proves
   an initially absent fact, not current-snapshot revocation, and has no ordering
   sentinel.

3. **503 correlation coverage is incomplete for GET/HEAD.** The isolated local
   schema fault is exercised through `ppfParity`, but that helper compares only
   a selected header list which omits `X-Correlation-ID` and returns only the GET
   response. Line 84 validates a safe 12-hex correlation only on GET. It does not
   establish that HEAD's local-auth 503 has a safe correlation header. The same
   generic helper asserts no correlation contract for the inherited DB/object/
   catalog 503 cases, despite the new spec explicitly inheriting correlation/
   reporting and requiring GET/HEAD parity of application-controlled headers.

4. **The environment manifest is not fully exercised.** `ppfStart()` correctly
   launches with `/usr/bin/env -i`, and most new cases explicitly replace both
   actor variables. However, the approved contract also requires explicit
   invalid/replacement actor cases; the new matrix checks only a fully missing
   actor. The later broad-reader probes at lines 124 and 148 replace
   `REMOTE_USER` while retaining positive `FMONITOR_AUTH_USER_ID=18`, so they do
   not provide a clean canonical negative identity fixture for the new local
   seam. No foreign DB/user/root decoy is planted and verified after cleanup,
   although section 5 requires foreign decoys to be preserved.

5. **Wrong-method no-body-read is asserted only by outcome, not made
   sensitive.** The PUT/PATCH/DELETE branch correctly uses missing authority,
   broken DB credentials, exact 405 body and successor `Allow: GET, HEAD, POST`,
   which proves method precedence over identity/DB. But it sends the complete
   declared body, so an implementation that reads it before returning 405 would
   still pass. The approved contract explicitly requires no body read. A
   bounded hanging/incomplete-body probe or equivalent public-seam sentinel is
   needed.

## What is already sound

- Exact public GET/HEAD route and unsupported methods are used; no private
  production method or persistence result is treated as the response.
- Positive, `objects.read`-only, local-only, process-only, inactive actor,
  inactive role, near-match, legacy-only and absent-both outcomes are present.
- Missing actor, local schema fault, unknown object, coherent wrong state and DB
  failure outcomes retain the intended status/redaction precedence.
- Every executed request is wrapped by a full database fingerprint and protected
  filesystem snapshot; the HTTP DB account is SELECT-only.
- Task-owned randomized DB/user/artifact names, `env -i`, and `finally` cleanup
  provide a good isolation base. The findings above concern missing sensitivity,
  not evidence that the current run mutated state.

## Required Gate 2 correction

Add public-seam instrumentation/fixtures that independently distinguish local,
process, object/form and render reads; add the isolated committed process revoke
for GET and HEAD; validate correlation on both methods for local and inherited
503 classes; add explicit invalid/replacement identity plus preserved foreign
decoys; and make wrong-method no-body-read observable. Re-run and retain a
qualifying RED. Any test change requires a fresh independent Gate 3 review.

## Reviewed hashes

```text
565804719e95171fa82523f6f883b8abebc9d8f0e36ca9746612fb8f7daab01e  specs/PILOT-PREPARE-RBAC-FIXTURES-001.md
62409b6a18bba29992c464fbbe60ff69744b3f8eeb5a4d1187dbbb2cfcb7cd4f  openspec/changes/pilot-prepare-rbac-fixtures/proposal.md
85189e1f36123b119806b3af55ca23312dd2787eeebcbe81f27164c52a034d95  openspec/changes/pilot-prepare-rbac-fixtures/design.md
412e496cd1240b26e6d694dc52e729f860a2a3830afced9cfe7f32806960c160  openspec/changes/pilot-prepare-rbac-fixtures/tasks.md
4c5423ca15dadc2d6bfd18ec683b3bafc5f9178d9a54ddc220d3139aab2b6f00  openspec/changes/pilot-prepare-rbac-fixtures/specs/verification/pilot-prepare-rbac-fixtures/spec.md
4866119c27596a5450ad442466eb994637d97e1dd9675c62473af5875fbe70ee  tests/InstallationProcess/pilot_prepare_form_001_test.php
c3862d4960bd1be9715e25566bc6b404e51d35fbbad8a6661f63271bc03548f3  docs/operations/pilot-prepare-rbac-fixtures-red-evidence.md
485a1140343e4f7922e0682ba338e87942bf0a3a38b9ac612ac92c5ed21e40c1  docs/operations/morning-owner-approval-decision-2026-09-02.md
```


# Independent Gate 3 test rereview v3 — PILOT-E2E-RBAC-FIXTURES-001

Date: 2026-09-02  
Reviewer: fresh independent test agent `/root/e2e_rbac_test_rereview_v3`  
Verdict: **CHANGES_REQUESTED**

The reviewer did not edit tests or production code. OpenSpec task `2.2` remains
open.

## Reproduced intended RED

The direct command reaches the intended missing local-RBAC admission without an
environment override and before any combined-PDF assertion:

```text
php tests/InstallationProcess/pilot_e2e_flow_001_test.php

TestFailure: isolated actor18 exact grant admits first list
Expected: 200
Actual: 403
```

Exit status was `255`. This is an authorization RED at the configured production
raw-HTTP seam, not a DB/setup failure and not artifact behavior.

## Closed prior findings

- The revoke expectation now derives `after = before - exactly
  (5301, objects.read)` and retains the `5302` and `5303` rows plus the complete
  schema/metadata/allocator snapshot.
- The isolated manifest covers all task-DB tables, the process projection and
  the owned mutable/session/artifact tree. Each owned sub-root has an observable
  sentinel; foreign DB, DB-user and filesystem decoys are asserted preserved.
- The isolated branch captures the primary exception, attempts every held
  server/connection/owned SQL/root cleanup step, and performs each postcondition
  independently. The main actor-19 check now uses a four-local-RBAC-table-only
  principal, so a handler/object/process read cannot masquerade as generic 403.
- Missing/empty/invalid, unknown/inactive/near-match and unavailable-read cases
  now assert exact response representations and exact application-header
  inventories. The 503 case correlates an opaque external ID with a safe logger
  category and checks representative SQL/RBAC/path secrets are absent.
- RBAC setup and admission remain operationally before the separately labelled
  combined-PDF assertions; this review makes no Gate 3 decision for
  `PILOT-E2E-COMBINED-PDF-001`.

## Blocking findings

1. **The complete main manifest is not compared from the approved boundary.**
   The test captures `$mainGrantBefore` before the first actor-18 list and stores
   it as `$GLOBALS['pefMainBoundary']['snapshot']`, but that `snapshot` key is
   never read. `pef303()` instead captures a new `afterPrepare` manifest after
   the state-changing prepare POST, and `pefXpath()` compares only that later
   manifest immediately before the PDF assertions. Thus mutation between the
   first list and prepare—including mutation caused by actor-19/authority reads
   or other intervening requests—can pass as long as the post-prepare state is
   stable for one response. This does not make the section-3 requirement
   (“main branch snapshots both before first list and at artifact boundary ...
   full-equal”) falsifiable. The approved wording also appears to conflict with
   the legitimate prepare state change; Gate 1 must clarify the exact
   authorization-owned manifest/delta if literal full-DB equality is not
   intended, then the test must compare that approved value at one explicit
   pre-PDF boundary.

2. **The no-leak/logger matrix is still incomplete for 401/403.**
   `pefAuthorityMatrixV3()` discards server diagnostics via `pefStop()` for
   every 401/403 case. Only the 503 process is stopped through
   `pefStopDiagnostics()`. Therefore a regression that logs actor IDs, role or
   permission facts, SQL/schema names, credentials or paths on an
   absent/invalid/unknown/inactive/near-match denial would pass. Section 4 says
   no response/log leaks those facts for the full authority matrix. Capture and
   assert diagnostics for every denial category (while allowing only the
   approved safe diagnostic shape, if any).

3. **Cleanup reporting is attempt-all but not complete or universally
   verdict-preserving.** The isolated V3 branch collects all cleanup failures
   but reports only `$cleanup[0]`, so simultaneous residue/decoy failures are
   hidden rather than reported alongside the primary result. In addition,
   `pefMainActor19Sentinel()` and the outer main fixture still use sequential
   `finally` cleanup whose first failure replaces the primary verdict and skips
   later cleanup. Section 5 says every fixture is attempt-all after success or
   failure. Preserve the original verdict and aggregate all cleanup failures for
   the isolated, main actor-19 and main fixture ownership introduced/exercised
   by this test.

## Sensitivity and separation assessment

The corrected test is genuinely sensitive to the current missing actor-18
admission, the exact revoke delta, the restricted actor-19 read boundary, and a
large part of the response contract. It can still accept unauthorized main
fixture mutation before prepare and diagnostic leakage on 401/403, and cleanup
can still obscure the first failure outside the isolated V3 helper. Those are
normative approved requirements, so Gate 4 cannot start on this exact artifact.

## Reviewed hashes

```text
83dee68e5df98c3a51d895e4d8c0d2f712cfc4e3bd3ce0f2af3d6217510f0217  specs/PILOT-E2E-RBAC-FIXTURES-001.md
558f6359aad5d3b307ccd5e71df61550bfedec0cb59333c31bede92335be96e3  tests/InstallationProcess/pilot_e2e_flow_001_test.php
1099a646367239acd2a662e7833ee63eaf35c87985f907f2f41cb5e622fa763f  openspec/changes/pilot-e2e-rbac-fixtures/tasks.md
f57bbea09e331d0459e2320a64ef2a59ed73bd71c5cfc186b961df12896aaafb  openspec/changes/pilot-e2e-rbac-fixtures/specs/verification/pilot-e2e-rbac-fixtures/spec.md
485a1140343e4f7922e0682ba338e87942bf0a3a38b9ac612ac92c5ed21e40c1  docs/operations/morning-owner-approval-decision-2026-09-02.md
```

## Gate decision

**CHANGES_REQUESTED.** Do not mark OpenSpec task `2.2`. Clarify and enforce the
main pre-list → pre-PDF manifest contract, capture denial diagnostics across the
401/403 matrix, and make all fixture cleanup verdict-preserving with complete
attempt-all reporting. A different fresh independent reviewer must review the
corrected exact test hash.

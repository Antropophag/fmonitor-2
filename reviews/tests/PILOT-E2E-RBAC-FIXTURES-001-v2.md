# Independent Gate 3 test rereview v2 — PILOT-E2E-RBAC-FIXTURES-001

Date: 2026-09-02  
Reviewer: fresh independent test agent `/root/e2e_rbac_test_rereview`  
Verdict: **CHANGES_REQUESTED**

The reviewer did not edit tests or production code. OpenSpec task `2.2` remains
open.

## Reproduced focused RED without override

Reviewed test SHA-256:
`294d4b87573f04050565b69edc30fddd4e9879d31a75db563af8bfd225a03315`.

The exact focused command now reaches the intended authorization failure with
the canonical test-harness defaults; no DB password override is needed:

```text
php tests/InstallationProcess/pilot_e2e_flow_001_test.php

TestFailure: isolated actor18 exact grant admits first list
Expected: 200
Actual: 403
```

This is a genuine RBAC-before-artifact RED, not setup or combined-PDF failure.
The earlier role/grant mismatch and actor-19 SELECT-sentinel omissions are
substantially corrected, but the corrected test still cannot receive Gate 3
approval.

## Closed prior findings

- The active isolated branch seeds and independently asserts exact actor 18,
  role `5301`, code `objects_reader`, and sole joined permission
  `objects.read`; its DELETE names exact `(5301, objects.read)`.
- Actor 19 receives trusted ID `19` with legacy identity but no local identity,
  while its DB principal has SELECT only on the four local-RBAC tables. Header
  and cookie attempts to select actor 18 are present, and the wider authority
  matrix supplies ambient `REMOTE_USER=sidorov@shlz.ru` without rescuing the
  denial.
- The isolated before snapshot now includes every task-DB table's metadata,
  `SHOW CREATE`, rows and allocator, plus the public process projection and
  owned storage identity. Repeat compares the complete snapshot.
- RBAC assertions execute before the separately named combined-PDF assertions,
  and the reproduced RED stops before that dependency.

## Blocking findings

1. **The revoke expected delta is constructed incorrectly.**
   `pefInstallCanonicalRbac()` seeds three permission rows: exact `5301`,
   inactive-role `5302`, and near-match `5303`. After deleting only `5301`, the
   test sets the complete permission-table row snapshot to `[]`:
   `$expected['fm2_pilot_role_permissions'][2] = []`. A correct implementation
   will retain the `5302` and `5303` matrix rows, so this assertion will fail for
   the wrong reason once the first admission becomes GREEN. Compute `after =
   before - exactly (5301, objects.read)` and preserve both other rows, schema,
   metadata and allocator byte-for-byte.

2. **The main artifact-boundary snapshot is still materially incomplete.**
   The callback in `pefXpath()` compares only `pefRbacGrant()` and the four RBAC
   tables. It does not compare the complete main DB snapshot, public process
   projection/artifact metadata, or owned mutable/session/artifact storage tree.
   The isolated branch's complete snapshot cannot prove that the separate main
   fixture stayed unchanged through the intervening requests. Capture the
   approved complete main manifest before its first list and compare it at one
   explicit boundary immediately before the first combined-PDF assertion. Keep
   the exact grant assertion within that manifest.

3. **Cleanup can replace the primary verdict and is not fully attempt-all.**
   `pefIsolatedRbacRevokeV2()` does not capture a primary exception before its
   `finally`; any cleanup/postcondition `TestFailure` replaces the RBAC failure.
   The foreign-decoy verification and its unlink/rmdir operations share one
   `try`, so the first failure skips later attempts. The negative server is also
   assigned by `pefStart()` before entering its local `try/finally`, leaving no
   handle for cleanup if startup fails part-way. Capture the primary result,
   attempt every server/connection/user/DB/root/decoy cleanup independently,
   verify all postconditions, and report cleanup failures alongside rather than
   instead of the primary result.

4. **Cleanup sensitivity does not cover every declared owned resource or
   foreign namespace.** The fixture creates mutable/session/artifact
   directories but only checks disappearance of their common root; it does not
   make session/artifact residue individually observable, does not configure
   the server to use the owned session root, and has no foreign DB principal or
   database decoy proving exact-target SQL cleanup. Add observable owned
   sentinels for each root, configure the relevant roots, and preserve foreign
   DB/user as well as filesystem decoys in postconditions.

5. **The authority/error matrix does not assert the approved exact response and
   diagnostic contract.** The 401/403 cases check status/body plus only four
   security headers. They omit content type/length, CSP, Permissions-Policy,
   COOP, forbidden headers, exact application-header inventory and redaction.
   The 503 case adds several headers but does not verify UTF-8 Content-Type,
   uniqueness/exact header inventory, or the required internal logger record
   containing only the same correlation ID and safe category. No assertion
   proves that actor/RBAC/SQL/path facts are absent from captured diagnostics.
   These are normative section-4 requirements and must be falsifiable in this
   RED, not inherited by comment.

6. **The main actor-19 proof is not the approved restricted-read sentinel.**
   The main journey's actor-19 server reuses the broad main DB user and merely
   checks that body text omits `4512`. The restricted principal exists only in
   the isolated branch after revoke. That branch is useful, but it does not
   prove the mandated main negative step's handler/read sentinel remains
   untouched. Run the main actor-19 list step with the four-table-only principal
   (or provide an equally sensitive explicit main sentinel) and distinguish an
   accidental handler DB failure from the expected generic 403.

## Sensitivity and boundary assessment

The test is now sensitive to missing actor-18 local authority at the public raw
HTTP seam and to several actor-value variants. It is not yet sensitive to all
approved mutation, cleanup, main-boundary and diagnostic regressions. In
particular, the currently malformed permission delta guarantees a false failure
after the production authorization gap is fixed, so the captured RED cannot yet
serve as the reviewed executable expectation for Gate 4.

The combined-PDF checks remain textually and operationally downstream and were
not weakened. This review makes no verdict on
`PILOT-E2E-COMBINED-PDF-001`.

## Reviewed hashes

```text
83dee68e5df98c3a51d895e4d8c0d2f712cfc4e3bd3ce0f2af3d6217510f0217  specs/PILOT-E2E-RBAC-FIXTURES-001.md
294d4b87573f04050565b69edc30fddd4e9879d31a75db563af8bfd225a03315  tests/InstallationProcess/pilot_e2e_flow_001_test.php
1099a646367239acd2a662e7833ee63eaf35c87985f907f2f41cb5e622fa763f  openspec/changes/pilot-e2e-rbac-fixtures/tasks.md
f57bbea09e331d0459e2320a64ef2a59ed73bd71c5cfc186b961df12896aaafb  openspec/changes/pilot-e2e-rbac-fixtures/specs/verification/pilot-e2e-rbac-fixtures/spec.md
```

## Gate decision

**CHANGES_REQUESTED.** Do not mark task `2.2`. Correct the expected revoke
delta, complete the main artifact-boundary manifest, make cleanup genuinely
attempt-all and verdict-preserving, and finish the exact authority/diagnostic
matrix and main actor-19 sentinel. Then obtain a fresh independent review over
the new exact test hash.

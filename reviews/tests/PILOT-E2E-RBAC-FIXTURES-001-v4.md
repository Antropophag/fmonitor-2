# Independent Gate 3 test review v4 — PILOT-E2E-RBAC-FIXTURES-001 v2

Date: 2026-09-02  
Reviewer: fresh independent agent `/root/e2e_rbac_test_review`  
Gate: Gate 3 review of amended RED verifier  
Verdict: **CHANGES_REQUIRED**

The reviewer authored neither the executable contract nor the reviewed test or
production implementation and did not edit tests or production during this
review.

## Exact reviewed inputs

The executable and OpenSpec specification hashes exactly match the
owner-approved E2E RBAC v2 hashes recorded in
`docs/operations/grill-009-rbac-exact-hash-approval-2026-09-02.md`:

```text
147227bde8b9afe126ee374417a9c7f5a3bac84c5e13b10d7dc1b1d9a525ee1f  specs/PILOT-E2E-RBAC-FIXTURES-001.md
78fbdd1b453009ab1e9a85a59e2a382dd2c2b5bfc5c0c405c6d53c41ef404c96  openspec/changes/pilot-e2e-rbac-fixtures/proposal.md
848e238b73b9120cfca4884e54ee827e725f130326207d711662a527574777a1  openspec/changes/pilot-e2e-rbac-fixtures/design.md
fdb2db734e01ea292504ae76d18f9e503e83c34bee352c1503905dadaef3e4b6  openspec/changes/pilot-e2e-rbac-fixtures/specs/verification/pilot-e2e-rbac-fixtures/spec.md
```

`tasks.md` is now `db0e3ad82402ceca9fa878a834ff74d7593ed7b2e29b6fac46300b1c3d9ed877`
because Gate progress was recorded after approval; this does not change the
approved behavior. The reviewed verifier is:

```text
8d80324dbd48fee0632ddd616b1a367a92056ccefd0db852b73d0b4af6a3369a  tests/InstallationProcess/pilot_e2e_flow_001_test.php
```

## Blocking findings

### HIGH — prepare whitelist does not independently constrain the approved row deltas

`pefAssertApprovedPrepareDelta()` excludes five tables from full equality, but
then checks mostly row counts. It permits arbitrary changes to existing or new
rows in `fm2_assignment_orders`, `fm2_installation_cases`,
`fm2_order_installers`, `fm2_process_events` and `fm2_order_artifacts`, provided
the loose counts and two discriminator values still match. In particular it
does not independently pin the assignment-order values, installer identity,
event actor/time/payload, artifact filename/media type/size/hash, or the exact
allowed installation-case transition. The storage oracle similarly requires
only four extra tree entries while preserving old entries; it does not prove
that the four new entries are the expected content-addressed artifact path and
bytes corresponding to independently specified metadata.

This is not the owner-approved “only exact approved prepare delta” boundary. A
faulty implementation could corrupt multiple approved-table facts or write an
unrelated owned file and still pass this helper. Replace the broad mutable-table
exception with an independently constructed exact expected post-prepare
manifest (or exact per-table/per-path deltas) whose values are fixed by fixture
inputs and the public contract, not copied from production output.

### MEDIUM — not every main authorization invocation has an immediate equality boundary

Main actor 18 admission and repeat, plus the isolated branch invocations, use
`pefAssertAuthorizationReadOnly()`. Main actor 19 in
`pefMainActor19Sentinel()` and all calls in `pefAuthorityMatrixV3()` do not.
They are covered only by one aggregate comparison after the entire group.
That detects retained net mutation but allows an invocation to write and a
later invocation to restore the state. The approved v2 contract requires a
full DB/process/storage snapshot immediately before and after *each*
object-list authorization invocation. Wrap every main actor19/matrix request in
that immediate oracle (including the unavailable branch where applicable),
without relying on a later aggregate snapshot.

## Passing observations

- The test uses configured production raw HTTP and deterministic actor IDs; the
  first isolated actor18 request reaches an intended authorization RED before
  revoke, main prepare, and every combined-PDF assertion.
- Isolated revoke is exact and committed; retained RBAC schemas, rows and
  counters are compared through the complete database snapshot.
- Actor19's restricted DB principal is a useful handler-read sentinel, and
  header/cookie/legacy identity spoofing does not supply authority.
- Combined-PDF assertions remain present and downstream; no legacy two-HTML
  fallback or assertion weakening was introduced.
- V3 cleanup is attempt-all, preserves foreign DB/user/filesystem decoys, and
  rethrows the primary failure only after cleanup checks.
- Expected statuses, error bodies, security headers, redaction and fixture
  identities are literal and are not derived from production responses.

## Independent reproduction

```text
$ php -l tests/InstallationProcess/pilot_e2e_flow_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_e2e_flow_001_test.php

$ php tests/InstallationProcess/pilot_e2e_flow_001_test.php
PHP Fatal error: Uncaught TestFailure: isolated actor18 exact grant admits first list
Expected: 200
Actual: 403
... pilot_e2e_flow_001_test.php(104) ...
... pilot_e2e_flow_001_test.php(145): pefIsolatedRbacRevokeV3() ...
```

Exit status: `255`. Classification: **INTENDED_RED**. Database/schema setup,
server startup and the authorization-read snapshot completed; the exact local
grant is denied by current production behavior. PDF assertions are not reached.

## Gate decision

The amended RED is correctly located and several important security oracles are
strong, but the two contract-critical snapshot requirements above are not yet
fully proved. Gate 3 is not approved. Correct the test only, capture a new exact
hash and RED evidence, then request another fresh independent review before any
GREEN implementation.

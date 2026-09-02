# Independent Gate 3 test rereview v5 — PILOT-E2E-RBAC-FIXTURES-001 v2

Date: 2026-09-02  
Reviewer: fresh independent agent `/root/e2e_rbac_test_review`  
Gate: Gate 3 rereview after v4 findings  
Verdict: **APPROVED**

The reviewer authored neither the executable contract nor the corrected test
or production implementation and did not edit tests or production during this
rereview.

## Exact reviewed inputs

```text
147227bde8b9afe126ee374417a9c7f5a3bac84c5e13b10d7dc1b1d9a525ee1f  specs/PILOT-E2E-RBAC-FIXTURES-001.md
78fbdd1b453009ab1e9a85a59e2a382dd2c2b5bfc5c0c405c6d53c41ef404c96  openspec/changes/pilot-e2e-rbac-fixtures/proposal.md
848e238b73b9120cfca4884e54ee827e725f130326207d711662a527574777a1  openspec/changes/pilot-e2e-rbac-fixtures/design.md
fdb2db734e01ea292504ae76d18f9e503e83c34bee352c1503905dadaef3e4b6  openspec/changes/pilot-e2e-rbac-fixtures/specs/verification/pilot-e2e-rbac-fixtures/spec.md
db0e3ad82402ceca9fa878a834ff74d7593ed7b2e29b6fac46300b1c3d9ed877  openspec/changes/pilot-e2e-rbac-fixtures/tasks.md
a243553c4ca1d79c1692dec91df2c562d7f3510c493b19613df4ae821c4e2abc  tests/InstallationProcess/pilot_e2e_flow_001_test.php
```

The executable and OpenSpec specification hashes match the owner-approved v2
contract. The tasks hash differs from the approval record only because Gate
progress is recorded append-only after approval; it does not alter behavior.

## Resolution of v4 findings

### Exact prepare delta — resolved

`pefAssertApprovedPrepareDelta()` now rejects table creation/deletion, requires
exact `SHOW CREATE` for every table and complete equality for every unapproved
table including metadata and counters. It independently constructs complete
literal expected rows for the installation case transition, assignment order,
installer membership and append-only event from fixed fixture inputs. Artifact
metadata pins the literal type, filename and media type plus size/hash tied to
the sole new owned blob.

The owned-storage delta is exactly one `sha256` directory, two digest shards and
one regular blob. Paths must be the content-addressed paths derived from the
blob's independently computed byte hash; directory/file modes, size and hash
are exact, and every pre-existing tree fact remains unchanged. The downstream
combined-PDF assertions independently constrain the semantic PDF content, page
shape, metadata and download bytes. There is no broad mutable-table or owned-
tree escape left.

Exact RBAC user, role, assignment and permission tables are additionally
compared as complete schema/metadata/counter/row snapshots after prepare.

### Immediate authorization snapshots — resolved

Main actor18 admission/repeat, main actor19 restricted-principal denial, every
missing/empty/invalid/unknown/inactive/near-match actor request, the unavailable
authorization request, and all isolated admission/repeat/revoked/actor19 calls
now execute inside `pefAssertAuthorizationReadOnly()`. Each invocation has its
own full DB/public-process/owned-storage snapshot immediately before and after
the raw HTTP request. The aggregate main-manifest check remains as an additional
longer-range guard and is no longer the sole oracle.

## Other review results

- Canonical actor18 has one exact `objects.read` grant; actor19 remains
  legacy-only and the restricted four-table DB principal proves denial before
  list-handler reads.
- The isolated committed revoke removes only role 5301's exact permission row,
  retains near-match/inactive controls, and preserves all schemas, counters,
  other rows, projection and storage.
- Response equality removes only transport `Date`, `Connection` and `Host`;
  authorization errors, security headers, header inventory, correlation and
  redaction are independently literal.
- Expected DB rows come from fixture/contract literals. Dynamic artifact size
  and digest are independently computed from the one new byte file and then
  cross-checked against metadata; they are not copied from a production result
  DTO. This is not a self-attesting success oracle.
- Combined-PDF assertions remain unchanged and strictly downstream. The current
  failure cannot be mistaken for PDF behavior.
- V3 cleanup is attempt-all across server handles, DB connection, task DB/user
  and task-owned roots, preserves foreign DB/user/filesystem decoys, and reports
  cleanup errors without silently replacing the primary result.

## Independent reproduction

Syntax check passed. Two consecutive executions produced the same result:

```text
PHP Fatal error: Uncaught TestFailure: isolated actor18 exact grant admits first list
Expected: 200
Actual: 403
... pilot_e2e_flow_001_test.php(109) ...
... pilot_e2e_flow_001_test.php(150): pefIsolatedRbacRevokeV3() ...
```

Each execution exited `255`. Classification: **INTENDED_RED**. The isolated
MariaDB schema, production server, exact RBAC fixture and immediate read-only
snapshot are established before the assertion. Failure occurs on the first
actor18 authorization call, before revoke, main prepare and all PDF assertions.
Post-run inspection found no `t_pef%` schemas, no `pef%` DB principals and no
matching owned/foreign artifact roots.

## Gate decision

The corrected verifier is traceable to the exact owner-approved amended
contract, deterministic, sensitive to the authorization defect, independently
constrains the permitted prepare mutation, preserves the separate combined-PDF
dependency and owns cleanup. Gate 3 is **APPROVED** for verifier hash
`a243553c4ca1d79c1692dec91df2c562d7f3510c493b19613df4ae821c4e2abc`.
Minimal GREEN may proceed under a separately tasked implementer.

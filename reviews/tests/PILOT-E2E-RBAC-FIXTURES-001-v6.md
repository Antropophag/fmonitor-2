# Independent Gate 3 test rereview v6 — PILOT-E2E-RBAC-FIXTURES-001 v2

Date: 2026-09-02  
Reviewer: fresh independent agent `/root/e2e_rbac_test_rereview_v6`  
Gate: Gate 3 rereview after stale configured-UI oracle correction  
Verdict: **APPROVED**

The reviewer authored neither the executable contract nor the corrected test or
production implementation and did not edit tests or production during this
rereview.

## Exact reviewed inputs

```text
147227bde8b9afe126ee374417a9c7f5a3bac84c5e13b10d7dc1b1d9a525ee1f  specs/PILOT-E2E-RBAC-FIXTURES-001.md
78fbdd1b453009ab1e9a85a59e2a382dd2c2b5bfc5c0c405c6d53c41ef404c96  openspec/changes/pilot-e2e-rbac-fixtures/proposal.md
848e238b73b9120cfca4884e54ee827e725f130326207d711662a527574777a1  openspec/changes/pilot-e2e-rbac-fixtures/design.md
fdb2db734e01ea292504ae76d18f9e503e83c34bee352c1503905dadaef3e4b6  openspec/changes/pilot-e2e-rbac-fixtures/specs/verification/pilot-e2e-rbac-fixtures/spec.md
df581ab9c2bfa986ca0b5293c7c50e9be01c66f4088bdaf7869a8104f5a1f967  openspec/changes/pilot-e2e-rbac-fixtures/tasks.md
a3f00d1e36d9d4bf5c2ff5c61734a79bf42c2e3d19df57c23e3ff43b01690da6  tests/InstallationProcess/pilot_e2e_flow_001_test.php
c6b45a4e6e09ffc408c6d57dbc9096f99729f58a4fe78b46f3bb1de4f4637e00  docs/operations/pilot-e2e-rbac-fixtures-red-evidence.md
```

The executable contract and OpenSpec behavior hashes remain the exact
owner-approved v2 inputs. The tasks hash records later gate progress only.

## Rereview result

The corrected verifier now pins the configured object-list response using its
actual semantic table boundary: the exact six headers `Объект и источник`,
`Адрес`, `Плановый период`, `Состояние`, `Следующий шаг`, and the unlabeled row
action column. It locates object 4512 through parsed DOM and requires exactly
one ordinary canonical `href=/pilot/objects/4512`; a visible-text match alone
cannot satisfy admission. This removes the stale shared-shell navigation oracle
without deriving success from a production result DTO.

Removing the calls to the broad `pefRedesignCommon()` helper does not weaken the
security slice. Actor 18 still reaches the real configured list through exact
`objects.read`; actor 19 uses the restricted four-table principal and fails
before a list-handler read; the missing/empty/invalid/unknown/inactive/near-match
matrix, ambient-header/cookie resistance, unavailable-authority response,
redaction, exact headers and correlation logging all remain asserted. Canonical
card routing is now tested directly by following the exact list href and
requiring the inherited nonmigrated card `200`, which is the relevant route
boundary for this slice. The removed helper remains outside the RBAC authority
or routing oracle.

Every authorization request remains enclosed by its own complete
DB/process/storage equality assertion. The isolated committed revoke still
removes only actor 18 role 5301's exact `objects.read` row, preserves controls
and foreign decoys, and is followed by an exact `403`. Main RBAC rows, schemas,
counters and the complete manifest remain unchanged before the downstream
journey.

The approved prepare boundary is intact: only the literal installation-case,
assignment-order, installer, artifact-metadata, append-only event and one
content-addressed blob delta is accepted; all other tables, schemas, counters
and pre-existing storage facts must remain exact. Cleanup remains attempt-all
and postconditions prove owned DB principals, schema and roots are absent while
foreign decoys survive.

Combined-PDF assertions are unchanged and remain strictly downstream: one PDF
artifact, exact metadata, persisted bytes/hash, HTTP GET/HEAD parity, three-page
shape, semantic marker order and absence of the legacy appendix route are still
required. The observed failure occurs before prepare and before any PDF oracle.

## Independent reproduction

```text
$ php -l tests/InstallationProcess/pilot_e2e_flow_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_e2e_flow_001_test.php

$ php tests/InstallationProcess/pilot_e2e_flow_001_test.php
PHP Fatal error: Uncaught TestFailure: nonmigrated card retains predecessor admission after local list RBAC
Expected: 200
Actual: 403
... pilot_e2e_flow_001_test.php(38) ...
... pilot_e2e_flow_001_test.php(157): pefText() ...
```

Exit status: `255`. Classification: **INTENDED_RED**. Actor 18 has already
passed list authorization, the exact six-column DOM and canonical object href.
Production then wrongly applies the migrated list authority boundary to the
nonmigrated card route. The failure is therefore the legitimate card `403` RED,
not setup, stale UI, prepare or combined-PDF behavior.

Post-run inspection found no matching `t_pef%` schemas, `pef%` DB principals or
owned `pef`/`pev` artifact roots.

## Gate decision

No findings. The corrected verifier is deterministic, traceable to the
owner-approved amended contract, sensitive to the exact current RBAC boundary,
and retains the complete snapshot, prepare-delta, cleanup and combined-PDF
obligations. Gate 3 is **APPROVED** for verifier hash
`a3f00d1e36d9d4bf5c2ff5c61734a79bf42c2e3d19df57c23e3ff43b01690da6`.
Minimal GREEN may proceed under a separately tasked implementer.

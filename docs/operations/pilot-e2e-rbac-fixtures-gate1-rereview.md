# Independent Gate 1 rereview — PILOT-E2E-RBAC-FIXTURES-001

Date: 2026-09-02  
Prior review SHA-256:
`58b7722e38b37add8884ed522ed1662da724d008175daaa7ba9d00c079ec6220`  
Reviewed executable spec SHA-256:
`3424c3a33067804b3508b61f6ea564c44c3130eaeb28f3686c834fba0a302368`  
Verdict: **CHANGES_REQUIRED**

## Closure audit

The correction closes three parts of the prior review:

- the process-environment boundary is named and feasible: one server process
  receives fixed `FMONITOR_AUTH_USER_ID`, request headers/body/cookies cannot
  select it, and `REMOTE_USER` remains descriptive predecessor input only;
- repeat comparison now removes exactly transport-owned `Date`, `Connection`
  and the documented local-SAPI `Host`, while comparing every remaining
  application header;
- 401/403/503 bodies and security headers, external correlation ID, internal
  safe category, forbidden headers and redacted facts are now literal.

The restricted DB principal is also a sound read sentinel: it may read only the
four canonical RBAC tables, so reaching object/process/legacy reads cannot
silently succeed. Artifact/RBAC ordering and task-owned cleanup remain properly
separated.

Strict validation passes:

```text
Change 'pilot-e2e-rbac-fixtures' is valid
```

Two contradictions remain in the executable spec.

## Gate-blocking findings

### 1. Actor 19 cannot produce the specified 403 when the trusted actor ID is unset

Section 1 explicitly says actor 19's process unsets
`FMONITOR_AUTH_USER_ID`. The approved authorization seam treats missing trusted
actor as `AUTHENTICATION_REQUIRED`, and current production maps it to exact 401.
`REMOTE_USER=reader@shlz.ru` cannot rescue or select the actor. This contradicts
the actor matrix and required legacy-only 403.

For the intended “authenticated numeric actor without a complete local grant”
proof, the actor-19 process must receive fixed trusted
`FMONITOR_AUTH_USER_ID=19`, while local user/role/grant rows for 19 remain absent.
That produces `ACCESS_DENIED`/403 and still proves legacy email/role are not
authority. Keep a separate absent/empty/invalid trusted-ID case for exact 401.
Update the executable spec, OpenSpec design/delta/tasks and the negative-server
description consistently.

### 2. The full snapshot cannot be byte-equivalent across the intended revoke

Section 3 requires a full before/after snapshot containing SHOW CREATE, ordered
rows and AUTO_INCREMENT for every task DB table, but also requires committed
deletion of the exact role-permission row and says existing facts/counters remain
byte-equivalent. A full ordered-row snapshot necessarily changes.

Define one exact expected-delta comparison:

- schema metadata and every AUTO_INCREMENT are identical;
- every non-RBAC table and all audit/history rows are byte-identical;
- local users, roles and user-role assignments are byte-identical;
- role-permissions after revoke equal before minus exactly
  `(5301, 'objects.read')`, with no other row difference;
- storage tree and process/artifact projection are identical;
- a repeated unchanged positive branch is full byte-equivalent with no deletion.

The main branch's separate before/artifact-boundary grant comparison should
remain full equality. This makes the intended mutation observable without
allowing the test to omit an affected namespace.

## Confirmed feasibility and boundaries

- `ProductionLocalObjectListAuthorization` already accepts the fixed process
  environment actor and requests exact `objects.read`; no production actor
  injection seam is required.
- A DB user restricted to RBAC SELECT can distinguish authorization denial from
  an accidentally reached list handler: the latter becomes infrastructure
  failure rather than the expected 403.
- Actor 18 role 5301 containing only `objects.read` is compatible with separate
  process capabilities for prepare/register/open. Card/artifact/control remain
  predecessor contracts and are not evidence of local-RBAC migration.
- Correlation is correctly external only as opaque `X-Correlation-ID`; the safe
  internal category is not exposed. Combined-PDF assertions remain downstream
  and unchanged.
- Gate order remains correct, but this rereview does not authorize Gate 2.

## Reviewed hashes

```text
3424c3a33067804b3508b61f6ea564c44c3130eaeb28f3686c834fba0a302368  specs/PILOT-E2E-RBAC-FIXTURES-001.md
9929249efb3f5f8afbd7f0757ee1681207b19dcea45bb00c90df4f3c2f3d0e5a  openspec/changes/pilot-e2e-rbac-fixtures/proposal.md
bdc5b4fb4f4dfbb62e03d69d7ec6595602b85ec5115fb14366dc3d4be5d0be5c  openspec/changes/pilot-e2e-rbac-fixtures/design.md
d2380bec2e1993d167340644e40a9fa34d8d8b984298bf3073f66cca93bf0e5b  openspec/changes/pilot-e2e-rbac-fixtures/tasks.md
f57bbea09e331d0459e2320a64ef2a59ed73bd71c5cfc186b961df12896aaafb  openspec/changes/pilot-e2e-rbac-fixtures/specs/verification/pilot-e2e-rbac-fixtures/spec.md
ec332fb306326eeb3e8689c90703e8f9dde688d18868051f51d591e62bf2ee06  tests/InstallationProcess/pilot_e2e_flow_001_test.php
```

After correcting actor 19's fixed trusted ID and the revoke expected-delta
manifest across all controlling artifacts, obtain a narrow fresh independent
Gate 1 rereview before owner approval.

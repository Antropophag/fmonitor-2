# Independent Gate 1 rereview v4 — PILOT-E2E-RBAC-FIXTURES-001

Date: 2026-09-02  
Prior rereview SHA-256:
`e5b981649b99a2da2838a5b73633018261fea604c40d8437ed9184aed83ac8ea`  
Reviewed executable spec SHA-256:
`83dee68e5df98c3a51d895e4d8c0d2f712cfc4e3bd3ce0f2af3d6217510f0217`  
Verdict: **READY_FOR_OWNER_APPROVAL**

## Final closure audit

The authoritative executable spec has a new hash and the last contradiction is
closed:

- the isolated revoke branch alone observes the exact permission row before
  DELETE and its sole absence afterward;
- the main branch separately observes that same grant present before its first
  list request and present with a full-equal manifest at the artifact boundary.

All findings from reviews `58b7…`, `28db…`, `b53c…` and `e5b9…` are now closed.

### Actor and authority matrix

- Actor 18 is fixed by process environment at the existing trusted test
  entrypoint boundary, has active local user/role 5301 and only exact
  `objects.read`.
- Actor 19 is independently fixed to trusted numeric ID 19, has legacy
  descriptive facts but no local user/role/grant, and therefore proves exact
  403 without legacy/name fallback.
- Missing/empty/invalid trusted actor is a distinct exact 401 matrix.
- Request headers, body and cookies cannot select or override the trusted actor;
  direct `REMOTE_USER` remains predecessor description only.

### Sentinel, repeat and snapshots

- The negative server DB principal can SELECT only the four RBAC tables. Any
  object/process/legacy list read becomes infrastructure failure rather than the
  expected denial, making the public sentinel red-capable.
- Repeat equality removes only `Date`, `Connection` and the documented local
  SAPI `Host`; every application status/body/header remains byte-exact.
- The revoke expected delta is exactly one role-permission row. SHOW CREATE,
  ordered rows outside that delta, AUTO_INCREMENT, audit/history, process
  projection, artifact metadata and owned storage identity remain equal.
- Main grant preservation is a separate full-equality assertion and cannot be
  satisfied by the isolated branch cleanup.

### Failure, cleanup and downstream boundary

- 401/403/503 bodies, literal CSP, permissions policy, COOP, content length,
  redaction, forbidden headers and opaque external correlation are fixed.
  Internal output is limited to the same correlation and a safe category;
  RBAC/SQL/path facts remain internal and unexposed.
- Task-owned DB users/databases, processes, sessions and mutable/artifact roots
  have attempt-all cleanup; foreign decoys remain. Cleanup cannot replace the
  captured primary verdict.
- Prepare/register/open/card/artifact/control retain predecessor authorization
  contracts and process capabilities. No broad local role is introduced.
- RBAC proofs precede artifact assertions. Combined-PDF behavior remains owned
  by `PILOT-E2E-COMBINED-PDF-001` and is neither repaired nor weakened here.

## Validation and gate decision

```text
Change 'pilot-e2e-rbac-fixtures' is valid
```

The spec is exact, falsifiable and implementable against the current public E2E
seam. It is ready for explicit owner approval of hash `83dee…`. This review does
not itself approve Gate 1 as product owner and does not authorize Gate 2 until
that explicit approval is recorded. After approval, intended RED and independent
test review must still precede fixture implementation.

## Reviewed hashes

```text
83dee68e5df98c3a51d895e4d8c0d2f712cfc4e3bd3ce0f2af3d6217510f0217  specs/PILOT-E2E-RBAC-FIXTURES-001.md
9929249efb3f5f8afbd7f0757ee1681207b19dcea45bb00c90df4f3c2f3d0e5a  openspec/changes/pilot-e2e-rbac-fixtures/proposal.md
bdc5b4fb4f4dfbb62e03d69d7ec6595602b85ec5115fb14366dc3d4be5d0be5c  openspec/changes/pilot-e2e-rbac-fixtures/design.md
d2380bec2e1993d167340644e40a9fa34d8d8b984298bf3073f66cca93bf0e5b  openspec/changes/pilot-e2e-rbac-fixtures/tasks.md
f57bbea09e331d0459e2320a64ef2a59ed73bd71c5cfc186b961df12896aaafb  openspec/changes/pilot-e2e-rbac-fixtures/specs/verification/pilot-e2e-rbac-fixtures/spec.md
```

No reviewed artifact, code or test was edited during this rereview.

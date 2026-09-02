# Independent Gate 1 review — PILOT-E2E-RBAC-FIXTURES-001

Date: 2026-09-02  
Reviewed executable spec SHA-256:
`529c39cdcc59b03812dcccc758a4321d6ac2813869a2b4324b4cdba9d92dda00`  
Verdict: **CHANGES_REQUIRED**

## Assessment

The actor matrix and slice boundary are directionally correct and consistent
with the approved local-RBAC authority decision:

- actor 18 has one active role `5301` with only exact `objects.read`;
- actor 19 remains legacy-only and cannot obtain list authority from email,
  legacy role or authentication alone;
- prepare/register/open capabilities remain separate process facts;
- revoke is isolated from the main journey and combined PDF remains a downstream
  dependency rather than an authorization fallback.

Strict OpenSpec validation passes:

```text
Change 'pilot-e2e-rbac-fixtures' is valid
```

The executable Gate 1 artifact is not yet deterministic enough for owner
approval. Four findings require correction.

## Gate-blocking findings

### 1. Actor propagation has two incompatible authorized seams

Section 1 says actor 18 SHALL enter through the local authentication boundary,
while OpenSpec design decision 2 and task 3.1 allow “real local auth/session seam
либо approved trusted test boundary”. The current E2E test supplies
`REMOTE_USER`; the new contract explicitly rejects that as authority. Gate 2
cannot choose between performing a real login/cookie exchange and injecting an
already authenticated numeric actor at a named trusted boundary.

Select one exact public setup. If a test boundary is allowed, name its input,
trust owner and production-unavailability proof, and require the same numeric
actor consumed by `ProductionLocalObjectListAuthorization`. If real LocalAuth is
required, specify prerequisite user credential/session-storage setup, exact
login/cookie steps and cleanup dependency. Direct environment `REMOTE_USER`,
email/name and request headers must remain explicitly insufficient in either
choice.

### 2. “Byte-identical headers” is not executable over the real HTTP seam

Section 3 requires two repeated requests to return byte-identical security
headers. The real PHP server emits transport-owned `Date`, `Connection` and the
documented local-SAPI `Host` behavior; Date can change between requests. Define
the comparison as exact application status/body/header allowlist with explicit
normalization/exclusion of approved transport headers, or require exact values
only for the stable security/CSP/content headers. Do not let the test silently
drop arbitrary headers.

### 3. Sentinel and snapshot scopes are not defined

The spec repeatedly requires “list handler/read sentinel untouched”, “facts and
counters byte-equivalent” and “main grant/roles byte-equivalent initial state”,
but does not enumerate their authoritative manifests. The revoke deliberately
changes one RBAC row, so an undifferentiated database snapshot cannot remain
byte-equivalent. Gate 1 must list:

- the exact protected list-read sentinel and how a real public HTTP denial proves
  it was not read (for example, an independently hostile/missing list dependency
  that would return 503 if reached);
- tables, rows, schema metadata and AUTO_INCREMENT values included in the
  domain/history snapshot, explicitly excluding only the intended revoked row;
- the exact main-fixture role/user/grant query whose before/artifact-boundary
  bytes are compared;
- expected absence of auth/domain audit rows and which existing audit/history
  tables are observed.

Without these manifests, a test can pass while omitting a mutated table or can
fail merely because the intended permission row was deleted.

### 4. Failure/redaction outcomes are not exact enough

Section 4 says missing actor → 401, several denials → 403 and unavailable →
“generic 503 safe correlation”, but does not pin status body, content type,
Retry-After, CSP/security header set, HEAD behavior or forbidden response/log
tokens. `LOCAL-RBAC-AUTH-CONTRACT-001` distinguishes response mapping from an
internal stable diagnostic category and opaque correlation; “safe correlation”
must not accidentally authorize exposing it in the response.

Reference exact inherited scenarios/IDs or restate the literal 401/403/503
responses and stable application headers. Enumerate redacted facts: actor IDs,
role/permission values, SQL/table/prefix, DB credentials, legacy identity and
exception details. Specify whether no log is expected or only the approved
category plus opaque correlation is allowed.

## Non-blocking implementation/gate observations

- Actor 18/19 IDs are feasible in the current fixture: legacy rows already use
  18/19 and process capabilities are keyed independently. A canonical local user
  18 can coexist; actor 19 must have no local user row in its negative branch.
- The current reader branch also exercises card/download and process-derived
  queue behavior. Those predecessor assertions may remain, but its list request
  must become the exact 403 proof and must not be reused as evidence that card or
  artifact routes migrated to local RBAC.
- Isolated revoke cleanup before main setup is the correct ordering. Cleanup
  failure must be reported alongside, not overwrite, the captured primary RBAC
  or downstream artifact result.
- Gate ordering is correct: exact corrected spec → independent Gate 1 review →
  owner approval → intended RED → independent test review → fixture GREEN →
  full verification → independent code review. This review does not authorize
  Gate 2.

## Reviewed hashes

```text
529c39cdcc59b03812dcccc758a4321d6ac2813869a2b4324b4cdba9d92dda00  specs/PILOT-E2E-RBAC-FIXTURES-001.md
9929249efb3f5f8afbd7f0757ee1681207b19dcea45bb00c90df4f3c2f3d0e5a  openspec/changes/pilot-e2e-rbac-fixtures/proposal.md
bdc5b4fb4f4dfbb62e03d69d7ec6595602b85ec5115fb14366dc3d4be5d0be5c  openspec/changes/pilot-e2e-rbac-fixtures/design.md
d2380bec2e1993d167340644e40a9fa34d8d8b984298bf3073f66cca93bf0e5b  openspec/changes/pilot-e2e-rbac-fixtures/tasks.md
f57bbea09e331d0459e2320a64ef2a59ed73bd71c5cfc186b961df12896aaafb  openspec/changes/pilot-e2e-rbac-fixtures/specs/verification/pilot-e2e-rbac-fixtures/spec.md
847c46583d7bc9f6617273623f586f66519429a4e4df27fab2c7e24385e91cad  openspec/changes/define-local-rbac-auth-contract/specs/local-rbac-auth-contract/spec.md
ec332fb306326eeb3e8689c90703e8f9dde688d18868051f51d591e62bf2ee06  tests/InstallationProcess/pilot_e2e_flow_001_test.php
2c9ae79f73e5a3bf8d93c81fad3f431bd810a5d63c2648fa7dfab16f646839ab  specs/PILOT-E2E-FLOW-001.md
c895577284369a33dbb58b12b3aba1fc1761d2ffacf3b1d32d8dc9d2db3fa3b5  docs/operations/pilot-e2e-rbac-combined-pdf-planning-rereview-v2.md
```

After these four corrections, obtain a fresh independent Gate 1 review of the
new exact executable-spec hash before requesting owner approval.

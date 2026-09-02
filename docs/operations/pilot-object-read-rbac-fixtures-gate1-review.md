# PILOT-OBJECT-READ-RBAC-FIXTURES-001 — independent Gate 1 review

Date: 2026-09-02  
Reviewer: fresh separately tasked agent `/root/completion_test_review`  
Verdict: **CHANGES_REQUIRED**

The reviewer did not author or edit the executable specification, OpenSpec
artifacts, code or tests. This record is the only edit. Gate 2 is not authorized.

## Reviewed hashes

```text
19f42ebbc911f67547059e939817a3bc7d8eea8392bed1b425b50b494a972a7e  specs/PILOT-OBJECT-READ-RBAC-FIXTURES-001.md
f13c27c2ee0d706954f5eee081bb717612abeac5e0386f0881a875c229bc1392  specs/LOCAL-RBAC-AUTH-CONTRACT-001.md
3f42ee848b3166a3961c7540d32319f608353e9b3749561cf7f2c30549172aa2  specs/PILOT-OBJECT-LIST-001.md
07d83894e9be75a3d8276d5701661fdc41f671f312d30ed3f5832892cb063b89  specs/PILOT-HTTP-AUTH-001.md
8c5e703a4429092ee3087a30d250b946468e10d007fefb5e4b57ee7f9eca44ee  reviews/tests/LOCAL-RBAC-AUTH-CONTRACT-001.md
d9a85d0335480bbba626cd4b4f49262f47ad9c441316ca4cde7a5e2abbb90c3c  reviews/code/LOCAL-RBAC-AUTH-CONTRACT-001.md
6d696e1d7ca678522d2855b4ef4f609ddd26b6206bd4b61b1a4576170cac487a  docs/operations/pilot-object-prepare-rbac-fixtures-planning-rereview-v2.md
a534ca9cf726c01c1dbd0d3faeb3c4560197c23d6f2fc1b654afe49741c6e4cc  openspec/changes/pilot-object-read-rbac-fixtures/proposal.md
bd2cb1b9e48e2b8d8959d88d67b4591297d447f94856179ebaf8a1f18a7e891a  openspec/changes/pilot-object-read-rbac-fixtures/design.md
3128529b18a6226a6f66ebce2159bdf48ffb194f396869132cab179df99aabc2  openspec/changes/pilot-object-read-rbac-fixtures/specs/verification/pilot-object-read-rbac-fixtures/spec.md
34b0246214a3cea8b38d63a81613f3e972dd36f35cd1be0c0a46f871f80ccce6  openspec/changes/pilot-object-read-rbac-fixtures/tasks.md
f8b32cc785486182fd960969840764cde8c7df130c036ed5a4ebc4e06cfc3382  tests/InstallationProcess/pilot_object_list_001_test.php
bfbf9f1a9ced25873dcb189e384829680033ebf571565f7b0c2c80661bbb7c7a  tests/InstallationProcess/local_rbac_objects_route_admission_001_test.php
```

## Required findings

### R1 — actor cases are not all feasible at the declared raw HTTP seam

The sole public seam is a real `GET /pilot/objects` in a separate process whose
trusted actor is supplied by `FMONITOR_AUTH_USER_ID`. Process environment values
are strings or absent; they cannot be a PHP non-string. Section 3 nevertheless
requires a public non-string actor case without naming an injected application
request seam.

Either remove non-string from this raw-process fixture contract (it remains
covered by the already approved application seam), or explicitly add a separate
typed entrypoint/application injection scenario and keep it distinct from the
real HTTP RED. Do not claim a shell environment produced a non-string value.

The executable spec should also distinguish malformed strings such as empty,
whitespace, signed, decimal and overflow values from absent, zero and negative,
with exact actor environment maps and no fallback channels.

### R2 — legacy-only outcomes collapse distinct 401 and 403 contracts

The scenario “legacy-only actor” permits generic `401/403`, which is not an exact
expected value:

- missing `FMONITOR_AUTH_USER_ID` plus a valid/spoofed `REMOTE_USER` must be
  `401 Authentication required.\n` because no trusted local actor exists;
- positive trusted local ID whose canonical local user/grant is absent must be
  `403 Access denied.\n`, even if a matching active legacy user/role exists.

Specify both public cases separately. The response cannot choose either status
based on implementation convenience, and legacy rows must be independently
snapshotted to prove they were ignored rather than absent.

### R3 — unavailable correlation outcome is not exact

Section 3 says only “503 with safe 12-hex correlation contract”. The stable
route contract is more precise and Gate 2 needs the exact public/internal seam:

- status 503, `Content-Type: text/plain; charset=UTF-8`, body
  `Service unavailable.\n`, no-store and declared content length;
- exact correlation header name `X-Correlation-ID`, exactly one lowercase
  12-hex value, and explicit Retry-After presence/absence according to the
  approved local route mapping;
- exactly one internal event with the same ID and one safe category from the
  closed allowlist, with no user/role/permission/schema/SQL/config values;
- distinct schema-invalid and read-failed fixtures where required, before list
  handler execution and with zero mutation.

Without this, a random unrelated 503 or mismatched internal/external correlation
can satisfy the draft.

### R4 — positive “fixed object fixture” and representation are unspecified

The actor/RBAC facts are exact, but the object-list positive fixture is not. The
spec says “fixed object fixture” and inherits `PILOT-OBJECT-LIST-001` without
naming imported/legacy rows, expected order, exclusion row, empty-list branch,
status/links/escaped values, or exact header/security/CSP contract.

Gate 2 must derive expected values from an approved worked example, not current
production DOM. Add a bounded test-owned list fixture with exact case/legacy
facts and independently determined visible order/links/landmarks, plus an
explicit non-imported legacy decoy. Cite the exact inherited list/UI/CSP
sections for headers and DOM. Keep authorization assertions separate: first
prove exact admission/handler sentinel, then compare the approved representation.

### R5 — handler sentinel, snapshots and cleanup need concrete ownership

The draft correctly demands a handler/read sentinel and full DB/filesystem
snapshot, but leaves them abstract. Make the Gate 1 contract executable by
pinning:

- the test-owned sentinel mechanism that changes only if the real list reader is
  invoked, and its expected count for positive versus denied cases;
- the exact table/catalog/AUTO_INCREMENT, legacy/session/log/audit rows and
  task-owned artifact roots included in before/after state;
- which runtime DB principal proves denial occurs before protected list reads;
- random task database/user/root naming, ownership marker validation, server
  reap/connection close order and absence verification in cleanup.

The sentinel must not be a private-method assertion or a production code hook.
A least-privilege DB principal or independently observed read barrier is
preferable. Cleanup must execute even when the first assertion fails and must
not rely on an unresolved prefix/glob.

## Confirmed properties

- Scope is correctly limited to exact `GET /pilot/objects`; HEAD, card, prepare,
  shell, login/session and other permissions remain out of scope.
- Actor 18, canonical active/activated user, active role 5101, assignment and
  byte-exact `objects.read` form a feasible positive local authority fixture.
- Canonical landed identity/access schema or an independently asserted manifest
  avoids reduced legacy-only tables and self-confirming expected DDL.
- Missing/inactive user/activation/role, no assignment, exact-grant absence,
  case/space/prefix/wildcard near matches, committed revoke and unavailable
  categories correctly remain negative branches with no fallback.
- Exact route isolation for trailing slash, zero, encoded/extra/unknown suffix
  is appropriate and cannot be broadened by a grant.
- Repeat/revoke semantics are consistent with current committed-snapshot
  authorization. Administrative revoke audit remains outside the slice while
  authorization itself is read-only.
- Expected representation is correctly required to come from approved list/UI/
  CSP contracts rather than production output. The route change introduces no
  product/domain behavior.
- Gate order is correct: reviewed exact spec, explicit owner approval,
  demonstrated public RED, independent test review, fixture-only GREEN,
  verification and independent code review. The DRAFT marker properly forbids
  Gate 2.

## Verification

```text
openspec validate pilot-object-read-rbac-fixtures --strict
Change 'pilot-object-read-rbac-fixtures' is valid

git diff --check -- <reviewed spec and OpenSpec package>
exit 0, empty output
```

## Verdict

**CHANGES_REQUIRED.** The authorization model and exact GET-only boundary are
sound, but actor feasibility, legacy 401/403, correlation diagnostics, positive
object representation and sentinel/snapshot cleanup must be made executable
before owner approval. Any normative change requires a new hash and fresh
independent Gate 1 review; Gate 2 remains prohibited.

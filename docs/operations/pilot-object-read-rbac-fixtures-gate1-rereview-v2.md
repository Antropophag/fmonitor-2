# PILOT-OBJECT-READ-RBAC-FIXTURES-001 — independent Gate 1 rereview v2

Date: 2026-09-02  
Reviewer: fresh separately tasked agent `/root/completion_test_review`  
Verdict: **READY_FOR_OWNER_APPROVAL**

The reviewer did not author or edit the executable specification, OpenSpec
artifacts, code or tests. This verdict supersedes the prior two
`CHANGES_REQUIRED` records for the exact spec below. It permits an explicit
owner decision; Gate 2 remains prohibited until that decision is recorded.

## Reviewed hashes

```text
e3858f094c1f5c4411887b7a242122f714ec6d488febca87bd591553f5b05828  specs/PILOT-OBJECT-READ-RBAC-FIXTURES-001.md
da5cfdbcc94f32d60f34f76bde6a451ba2418b31d396ef60346c4ba30b51c586  docs/operations/pilot-object-read-rbac-fixtures-gate1-review.md
f198c51037b15190c337a14d3ca47b7f0c430e191c4d3a8af5e6b54b314f8a42  docs/operations/pilot-object-read-rbac-fixtures-gate1-rereview.md
f13c27c2ee0d706954f5eee081bb717612abeac5e0386f0881a875c229bc1392  specs/LOCAL-RBAC-AUTH-CONTRACT-001.md
3f42ee848b3166a3961c7540d32319f608353e9b3749561cf7f2c30549172aa2  specs/PILOT-OBJECT-LIST-001.md
07d83894e9be75a3d8276d5701661fdc41f671f312d30ed3f5832892cb063b89  specs/PILOT-HTTP-AUTH-001.md
```

## Final finding closure

The unavailable response now fixes the two previously ambiguous application
headers as exact singletons:

```text
Permissions-Policy: camera=(), microphone=(), geolocation=()
Cross-Origin-Opener-Policy: same-origin
```

The same contract already pins one Content-Type, Content-Length 21, no-store,
nosniff, no-referrer, DENY, byte-exact BASE CSP and one lowercase 12-hex
`X-Correlation-ID`; it excludes Retry-After, cookie, redirect, challenge, CORS,
Server/X-Powered-By and unspecified application headers. Internal logger
cardinality, correlation equality, safe category and redaction remain exact.
Gate 2 no longer needs to derive either policy value from production output.

## Reconfirmed complete contract

- Scope remains exact production-entrypoint `GET /pilot/objects`; HEAD, card,
  prepare, shell, login/session, other permissions and domain behavior are
  explicitly outside this fixture slice.
- Actor 18 has exact canonical active/activated user, active role 5101,
  assignment and byte-exact `objects.read`. Canonical landed schema/independent
  manifest is required; reduced ad-hoc or legacy authority is forbidden.
- Every process has an explicit environment. Positive sets the trusted local ID;
  negative cases unset/replace it and cannot inherit `REMOTE_USER`, cookies or
  a prior positive process.
- Raw-process string cases are feasible and exact. Missing/malformed trusted
  actor returns 401; positive unknown/non-granted/inactive canonical actor
  returns 403. Legacy-only `REMOTE_USER` is separately fixed at 401, while
  application-only non-string coverage remains in its approved seam.
- Inactive user/activation/role, no assignment, missing exact permission,
  case/space/prefix/wildcard near matches, schema/read unavailable and committed
  revoke remain sensitive and cannot use cached/legacy authority.
- The three-object positive fixture has independently fixed visible facts and
  order `4513,4512,4515`, one exact link/status per item, and explicit exclusion
  of non-imported legacy 4999. Approved list/UI/CSP contracts own representation
  expectations, not current renderer output.
- Unknown/zero/trailing/encoded/extra suffixes retain their inherited route
  results before list reads and cannot be widened by `objects.read`.
- A restricted denial DB user can read only canonical authorization tables;
  any protected list/process/legacy read becomes observable failure. Complete
  SHOW CREATE/rows/AUTO_INCREMENT and CSS/file snapshots prove zero mutation,
  schema repair, cookie/session/audit or artifact change.
- Repeat and revoke use new invocations. Cleanup attempts owned server/pipes,
  DB resources/user/database and verified mutable root in `finally`, while
  foreign decoy DB/file bytes and metadata remain unchanged.
- Gates remain ordered: exact owner approval, intended public RED, independent
  test review, fixture-only GREEN, focused/full architecture/lint verification,
  independent code review and Done evidence. The DRAFT marker correctly blocks
  Gate 2 now.

## Verification

```text
openspec validate pilot-object-read-rbac-fixtures --strict
Change 'pilot-object-read-rbac-fixtures' is valid

git diff --check -- specs/PILOT-OBJECT-READ-RBAC-FIXTURES-001.md
exit 0, empty output
```

## Owner approval boundary

The owner may approve exact executable-spec SHA-256:

```text
e3858f094c1f5c4411887b7a242122f714ec6d488febca87bd591553f5b05828  specs/PILOT-OBJECT-READ-RBAC-FIXTURES-001.md
```

Any normative change requires a new hash and fresh independent Gate 1 review.
Verdict: **READY_FOR_OWNER_APPROVAL**.

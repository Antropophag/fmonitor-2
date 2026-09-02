# PILOT-OBJECT-READ-RBAC-FIXTURES-001 — independent Gate 1 rereview

Date: 2026-09-02  
Reviewer: fresh separately tasked agent `/root/completion_test_review`  
Verdict: **CHANGES_REQUIRED**

The reviewer did not author or edit the executable specification, OpenSpec
artifacts, code or tests. This rereview supersedes the first verdict only where
closure is explicit. Gate 2 remains unauthorized.

## Reviewed hashes

```text
accde682b96cbdd29f8fb5fb7b1a6b785babb19eb52500f3ae5cb3e8d7ba1dec  specs/PILOT-OBJECT-READ-RBAC-FIXTURES-001.md
da5cfdbcc94f32d60f34f76bde6a451ba2418b31d396ef60346c4ba30b51c586  docs/operations/pilot-object-read-rbac-fixtures-gate1-review.md
f13c27c2ee0d706954f5eee081bb717612abeac5e0386f0881a875c229bc1392  specs/LOCAL-RBAC-AUTH-CONTRACT-001.md
3f42ee848b3166a3961c7540d32319f608353e9b3749561cf7f2c30549172aa2  specs/PILOT-OBJECT-LIST-001.md
07d83894e9be75a3d8276d5701661fdc41f671f312d30ed3f5832892cb063b89  specs/PILOT-HTTP-AUTH-001.md
a534ca9cf726c01c1dbd0d3faeb3c4560197c23d6f2fc1b654afe49741c6e4cc  openspec proposal.md
bd2cb1b9e48e2b8d8959d88d67b4591297d447f94856179ebaf8a1f18a7e891a  openspec design.md
3128529b18a6226a6f66ebce2159bdf48ffb194f396869132cab179df99aabc2  openspec delta spec.md
34b0246214a3cea8b38d63a81613f3e972dd36f35cd1be0c0a46f871f80ccce6  openspec tasks.md
```

## Closed findings

- Raw-process actor cases are now feasible: only absent/empty or explicit string
  values `0`, `-1`, `abc`, ` 18`, `18 ` are required. Non-string input is
  correctly delegated to the already approved injected application-seam test.
- Legacy authority is split exactly: absent trusted actor plus legacy
  `REMOTE_USER` returns 401; positive trusted ID with no canonical user returns
  403. Legacy rows remain decoys and cannot choose the result.
- Correlation behavior now pins status/body/content type/content length,
  no-store, CSP, one lowercase 12-hex `X-Correlation-ID`, no Retry-After/cookie/
  redirect/challenge/CORS/server disclosure, and one internal event with the
  same ID plus exact safe schema/read category and a closed redaction surface.
- The positive object fixture is independent and concrete: three imported
  `needs_assignment_order` cases have exact visible facts/order/links/status;
  legacy 4999 is an explicit non-imported exclusion. Expectations are traced to
  approved list/UI/CSP contracts rather than renderer output.
- A least-privilege denial DB user may read only canonical authorization tables,
  making any protected object/process/legacy read observable as DB failure.
  Snapshots include SHOW CREATE, complete ordered rows, AUTO_INCREMENT and
  CSS/file metadata. Cleanup inventory includes server/pipes, DB resources/user/
  database and ownership-verified mutable root, with foreign decoy comparison.
- Repeat, committed revoke, exact route/suffix isolation, zero session/cookie/
  audit/schema repair, GET-only scope and Gates 1–5 remain coherent.

## Remaining exact-header ambiguity

The unavailable contract says “standard permissions/COOP” while simultaneously
requiring no unspecified application headers. That phrase does not give Gate 2
an independently determined byte-exact value for either header.

Replace it with the inherited literal values:

```text
Permissions-Policy: camera=(), microphone=(), geolocation=()
Cross-Origin-Opener-Policy: same-origin
```

Also state that each occurs exactly once, just like Content-Type, CSP and
X-Correlation-ID. Transport headers outside application control may remain
explicitly excluded from the comparison. Without this correction a test can
accept missing/changed policy headers by interpreting “standard” from current
production—the exact self-confirming behavior this fixture contract forbids.

No other normative change is required by this rereview.

## Verification

```text
openspec validate pilot-object-read-rbac-fixtures --strict
Change 'pilot-object-read-rbac-fixtures' is valid

git diff --check -- specs/PILOT-OBJECT-READ-RBAC-FIXTURES-001.md
exit 0, empty output
```

## Verdict

**CHANGES_REQUIRED.** All substantive actor, status, correlation, representation,
sentinel, snapshot and cleanup findings are closed. Pin the two remaining
security-header values and cardinality, then request a fresh rereview at the new
spec hash. Gate 2 remains prohibited until explicit owner approval after an
independent `READY_FOR_OWNER_APPROVAL` verdict.

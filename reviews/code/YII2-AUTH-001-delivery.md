# Independent Gate 5 — YII2-AUTH-001 delivery

- Reviewer: Codex root in the fresh PR-closeout session, not an author of the
  reviewed implementation or tests. The owner explicitly requested this reviewer
  and no subagents in this session.
- Reviewed source: `694a2020d1d025dfa2351ab183b70c325d4539ed`.
- Baseline: `8b8104908a77ec399809a16026740843aa53a990`.
- Diff: `git diff 8b8104908a77ec399809a16026740843aa53a990...694a2020d1d025dfa2351ab183b70c325d4539ed`.
- Verdict: **APPROVED**. Merge still requires the full exact-head CI result.

## Standards

No blocking documented-standard violation. Reviewed the previously excluded
views/assets, Yii lifecycle, Docker/nginx composition, architecture checker and
verification registration, together with the authentication integration. Views
escape dynamic identity/catalog values; asset delivery uses an explicit allowlist
and rejects symlinks. Asset bundles use the Yii page lifecycle and same-origin CSP.
The public shlz stylesheet is built from the pinned export. Pilot CSS, favicon and
six font assets are byte-identical to the existing oracle assets.

The checker covers Yii controllers, configuration and both entrypoints, forbids
legacy runtime dependencies and web migration calls, and narrowly permits only
session_write_close in ReliableSession. The approved boundary tests include
positive controls and use an empty baseline; baseline.json is unchanged.
Both authentication suites are registered in the integration inventory; no old
suite is removed. Historical approvals and evidence are preserved.

Non-blocking judgement call: compressed controllers/templates reduce readability;
the existing core review already records the dense login method. No refactoring
is needed for this bounded delivery. Diff whitespace diagnostics report only two
extra final blank lines in existing candidate additions; no behavior is affected.

## Spec

No blocking finding against YII2-AUTH-001 and the accepted migration design.
Existing core approval is retained: current AuthController SHA-256 is
`cd69f56555e03fe197563fd95659756b5670dc3967f5cc29aa46cd819d8b4554`, exactly the
corrective source independently approved in YII2-AUTH-001-core.md. Canonical
permissions, identity renewal, Argon2id preservation, CSRF, safe response handling
and close-before-attempt-cleanup remain intact. The protected roles reader makes
no domain/RBAC writes. Invitation/admin writes and other routes remain out of
scope; this approval does not claim stand cutover or completion of #71/#76.

## Verification evidence and limits

- Reused Gate 3 records: YII2-AUTH-001.md, YII2-AUTH-001-session-failure.md,
  YII2-AUTH-001-boundary.md. Reviewed current tests against these approvals.
- Reused core record's successful authentication and late persistence suites;
  no implementation or test changes were made in this review session.
- Inspected preserved nginx access logs at 2026-09-09 17:12:11 UTC: login GET200,
  email POST200, password POST303, protected roles GET200. Saved response headers
  in /tmp/fm2-auth-h1, h2, h3 confirm 200/200/303; saved roles HTML has its marker.
  Cookies and primary response artifacts remain outside git.
- Preserved smoke image:
  `sha256:acd86733b1a6d8cdff08170b743141ec870095c07edaec88121d437cab383e6f`.
  Compared every changed app/config/deploy source file against the running
  fm2-auth-pr-php image: all hashes match the reviewed checkout. No smoke rerun.
- Full exact-head CI remains required, including architecture and both registered
  authentication suites. No local full verification or new browser run is claimed.

The subsequent review-record/task-checkbox commit changes documentation only;
this source approval carries forward only while code/tests/config remain identical.

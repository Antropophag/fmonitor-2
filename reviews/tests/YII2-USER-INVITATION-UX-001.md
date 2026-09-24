# YII2-USER-INVITATION-UX-001 — Gate 3 test review

- Reviewer: `/root/issue250_gate3` (independent; did not author the specification,
  OpenSpec artifacts, or tests)
- Review role package:
  `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T183954Z-939d449fee/package.json`
  (`sha256:666143bbfa89b1108126a80287b3974df7b0866dc5adf7ebfa30b8d438c03501`)
- Exact candidate source: `9bec5a117f0543957663efe7facc7a9cc359cb1cd8b98206282dab16b19f3ed6`
- Executable source: `fefae4e233cbfb193f49ae4653245fb03aa9c4c9cad248807184db4e663a0038`
- Base commit: `d9dddb31f9c6e07092bcf6d4c04df761a1a13ccd`
- Reconstructible snapshot: package `snapshot/source.patch`
  (`sha256:b946a6e9d0bd0c2023c6977a99696ed097d7601ff7d001024b5ae3b887d131ce`)
- Verification plan: package `verification-plan.json`
  (`sha256:6540b34d489f9fd74638a7b0f488c2b8acd60fd478b9aee495ef814eff2d8ff8`),
  lane `CRITICAL`, required reviews `gate3`, `final`
- Verdict: **CHANGES_REQUESTED**

## Evidence reviewed

The normative specification, all four OpenSpec planning artifacts, verification
input and plan, both PHP test entrypoints, the Chromium script, the prepared
snapshot, and both retained exact-source RED records were reviewed.

- HTTP RED: `php tests/Yii2/yii2_user_access_001_test.php`, record
  `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790275182277865000-e5b7748ad27e417d83486e72b4bbd3da.json`.
  It exits `255` at the new retained-email assertion, which is an intended missing
  behavior rather than setup failure.
- Browser RED: `php tests/Yii2/yii2_user_access_browser_001_test.php`, record
  `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790275182277491000-a29b7b9eac614b13b343f479c14143ad.json`.
  It exits `255` because the create link is not an absolute trusted-origin URL,
  also an intended missing behavior rather than setup failure.
- Bounded static checks performed by this reviewer: both PHP entrypoints pass
  `php -l`, `user_access_browser.mjs` passes `node --check`, and `git diff --check`
  passes. No full suite was run.

The RED records are source-consistent with the package, but an intended first
failure does not compensate for the acceptance gaps below.

## Findings

### 1. BLOCKER — unavailable/invalid trusted-origin behavior has no executable test

Locations: `specs/YII2-USER-INVITATION-UX-001.md:30,39-42`,
`openspec/changes/complete-user-invitation-flow/specs/user-access/complete-invitation-flow/spec.md:18-22`,
`tests/Yii2/yii2_user_access_001_test.php:19-30`.

The HTTP fixture is started only with a valid trusted host, and the test exercises
only successful create/reissue. It never removes or corrupts trusted scheme/host,
never asserts the exact safe actionable outcome, never proves that no relative
path/copy-success is presented, and never proves the specified mutation boundary:
an already completed owner mutation is not automatically repeated. The verification
plan nevertheless maps `unavailable-origin` into this test.

Correction: add isolated public-HTTP cases for missing and representative invalid
trusted scheme/host values. Assert the exact user-visible reason, absence of an
externally sendable/copy-success link, the precise persisted-fact delta from the
single accepted owner operation, and absence of automatic retry/reissue. If the
intended behavior is to reject before any owner mutation instead, resolve that Gate
1 contradiction explicitly in the normative specification before changing tests.

### 2. HIGH — the security test does not challenge the raw request `Host`

Locations: `specs/YII2-USER-INVITATION-UX-001.md:15-18,29`,
`tests/Yii2/yii2_user_access_001_test.php:19-23`.

The test supplies `Forwarded` and `X-Forwarded-*`, but omits the explicitly named
untrusted `Host`. Its ordinary request host is also the same `127.0.0.1:port` value
configured as trusted origin, so an implementation that incorrectly derives the
link from the request `Host` can pass.

Correction: send a conflicting raw `Host` header over the same fixture connection
and prove that create and reissue both retain the configured origin and never
render the attacker value.

### 3. HIGH — the token-log assertion is not valid against the selected log boundary

Locations: `specs/YII2-USER-INVITATION-UX-001.md:29,42`,
`openspec/changes/complete-user-invitation-flow/design.md:11`,
`tests/Yii2/UserAccessFixture.php:58-63`,
`tests/Yii2/yii2_user_access_001_test.php:62`.

The fixture redirects both PHP built-in-server access output and application
output to `server.log`, while the test itself performs GET requests whose query
contains each raw activation token. PHP's built-in access logger records the
request target before application code can redact it. Therefore the final
`server.log` substring assertion is liable to fail because of the test transport,
even after a conforming application change; conversely, it does not identify
which application logging seam is protected. The contract's unqualified “server
log”/“logs” wording also conflicts with the excluded deployment boundary when the
token remains in a GET query.

Correction: first make Gate 1 name the owned and observable logging boundary
(for example, application logs, with access-log redaction/deployment handled by a
separately scoped contract if required). Then capture that channel separately and
assert absence there, or change the fixture transport/logging setup so the asserted
file is a valid oracle. Do not weaken the security requirement merely to obtain
GREEN.

### 4. HIGH — the browser matrix does not test the promised no-JS and Clipboard-absent fallback

Locations: `specs/YII2-USER-INVITATION-UX-001.md:32,41-42`,
`openspec/changes/complete-user-invitation-flow/specs/user-access/complete-invitation-flow/spec.md:32-36`,
`tests/Yii2/user_access_browser.mjs:8-29`.

The single administrator context always has JavaScript enabled and always installs
a `navigator.clipboard` object. It covers Promise rejection and resolution, but
not an absent Clipboard API, not the server-rendered field/instruction with JS
disabled, and not keyboard operation. Narrow-viewport coverage asserts only that
the field and button are visible; it does not establish that the manual instruction
is present or usable. Thus an implementation that creates fallback content only
after JS rejection can pass despite violating the contract.

Correction: add a clean JavaScript-disabled context (or an equivalent pre-script
HTML oracle) that proves the readonly/selectable URL and manual instruction are
visible, add a Clipboard-absent context, exercise the copy control from the
keyboard, and assert the fallback remains visible/usable at the narrow viewport.

### 5. MEDIUM — rejected-form accessibility can pass while marking the wrong field

Locations: `specs/YII2-USER-INVITATION-UX-001.md:33`,
`openspec/changes/complete-user-invitation-flow/specs/user-access/complete-invitation-flow/spec.md:38-42`,
`tests/Yii2/yii2_user_access_001_test.php:18`.

For three different invalid inputs the test only searches the whole page for any
`aria-invalid="true"` and any `autofocus`. It does not prove that the correct email
or full-name control is marked/focused, that the marked control references its
description, or that valid sibling fields are not mislabeled. A page-level hidden
element or the wrong field satisfies the assertion.

Correction: parse/select each named field and independently assert its retained
escaped value, expected `aria-invalid`, matching `aria-describedby` target and
focus marker for each rejection class; assert the unaffected field is not marked
invalid. Cover the known duplicate/general-safe-reason branch if that is one of
the inherited owner's known invalid results.

### 6. MEDIUM — recipient-facing wording and adjacent rejection preservation are not asserted

Locations: `specs/YII2-USER-INVITATION-UX-001.md:35`,
`tests/Yii2/user_access_browser.mjs:30-35`.

The clean recipient context proves revoked-old-token rejection and successful
activation, but neither test proves that administrator/recipient text describes
manual handoff rather than a sent email. The acceptance row also names expired,
used, guest/blocked/unauthorized and escaping regressions; the new tests do not
identify which inherited assertions preserve each item or add focused assertions.

Correction: assert the manual-transfer wording and absence of an email-sent claim.
For every adjacent behavior claimed by this slice, either point to a concrete
unchanged executable assertion in the reviewed test matrix or add the focused
public-seam assertion; narrow the normative acceptance row if an item is genuinely
outside this vertical slice rather than leaving unverifiable prose.

## Decision

Gate 3 does not pass. The two retained RED executions are legitimate but the test
candidate is not yet complete or independently reliable for its mapped acceptance
surface. Return to Gate 1 for the mutation/log-boundary ambiguities and to Gate 2
for the missing and insufficient assertions. After corrections, regenerate the
verification plan/package if bound inputs changed and obtain fresh exact-source
INTENDED_RED evidence plus a new independent Gate 3 review. Production
implementation, final review, CI, publication, merge and deployment remain
`UNKNOWN`.

---

# Gate 3 delta rereview v2

- Reviewer: `/root/issue250_gate3` (independent; authored neither contract nor tests)
- Supersedes the verdict above for the corrected candidate only
- Corrected exact source: `3d36ba8d92e5825d11b70298d3c00059c528ba4e92bcaa160be44d98227b6e0d`
- Corrected executable source: `f8f8592049f379509330d2909f6da3f7005f534c79ef7048bcc61fc9a3bafe03`
- Role package:
  `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T184509Z-8a451776c6/package.json`
  (`sha256:f823f698fee86fcacdf2b4ccc25577b928c5333ee2b8e43f4d7bc2b94481c6a7`)
- Delta: package `delta.patch`
  (`sha256:aed8ed540c1f0827689063c2e3579177bfea91f08f5078487e08599dc11ef775`)
- Reconstructible snapshot: package `snapshot/source.patch`
  (`sha256:eb835874fac19ff9732f08ea66406a6f89b9ba4af292463dc7371e1bca554c3e`)
- Verification plan: package `verification-plan.json`
  (`sha256:307d7f6ef03cb022cf8c36a29d2e8a2fe071321fa91df25369e2904e0bde720a`),
  lane `CRITICAL`, required reviews `gate3`, `final`
- Verdict: **CHANGES_REQUESTED**

## Fresh evidence

- HTTP INTENDED_RED: `php tests/Yii2/yii2_user_access_001_test.php`, record
  `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790275466432194000-1d7caec701664965b9eb6a85026b7e12.json`.
  It exits `255` at the retained-email assertion; source, executable source,
  environment and command blob match this package.
- Browser INTENDED_RED: `php tests/Yii2/yii2_user_access_browser_001_test.php`,
  record
  `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790275466429185000-a1a9f974b52647428de2ef00c45c9497.json`.
  It exits `255` at the absolute trusted-origin assertion; source, executable
  source, environment and command blob match this package.
- Reviewer reran only bounded static checks: both PHP entrypoints pass `php -l`,
  the browser module passes `node --check`, and `git diff --check` passes. The
  prohibited local full suite was not run.

## Prior-finding dispositions

1. **PARTIALLY FIXED / OPEN.** Gate 1 now unambiguously selects rejection before
   owner mutation, and the HTTP test proves HTTP 503, safe reason, no link/copy
   success and no fact delta for an empty trusted scheme. However, the contract
   still covers trusted scheme/host that are “absent or unsuitable”, while the
   test exercises only an empty scheme. It never challenges a present but invalid
   configured scheme or host, so permissive validation of malformed host material
   can pass. See open finding A below.
2. **FIXED.** Create and reissue now send a conflicting raw `Host`, expect
   fail-closed admission, and prove no identity/invitation fact delta. The
   normative contract explicitly records this existing admission behavior;
   forwarding headers remain independently challenged on the successful path.
3. **FIXED.** The normative spec and design now limit the owned guarantee to
   application/process output and explicitly exclude deployment access-log
   redaction. Assertions inspect process output before the first token-bearing
   activation navigation, so the fixture access logger cannot create a false
   failure.
4. **FIXED.** The browser test now covers rejected Clipboard Promise, absent
   Clipboard API, keyboard activation, successful resolved copy, a narrow
   viewport, and a JavaScript-disabled narrow context with readonly URL and
   server-rendered manual instruction.
5. **PARTIALLY FIXED / OPEN.** Assertions are now scoped to the expected email or
   full-name input, cover the duplicate branch, and prove the sibling is not
   marked invalid. They still accept a dangling `aria-describedby` reference.
   See open finding B below.
6. **FIXED.** The browser test asserts manual-handoff wording and absence of an
   email-sent claim. The contract narrows the browser recipient expectation to
   revoked-old/new-token activation and identifies the inherited HTTP/edge tests
   that retain the remaining activation, authorization and escaping matrix.

## Open findings

### A. HIGH — invalid configured origin remains uncovered

Locations: `specs/YII2-USER-INVITATION-UX-001.md:32`,
`openspec/changes/complete-user-invitation-flow/specs/user-access/complete-invitation-flow/spec.md:18-22`,
`tests/Yii2/yii2_user_access_001_test.php:69`.

The only unavailable-origin fixture sets
`FMONITOR_TRUSTED_REQUEST_SCHEME=''`. It covers absence but not a present,
syntactically invalid configured origin. An implementation that rejects empty
values yet accepts a host containing a path/userinfo/control material, or an
unsupported scheme, can satisfy the current test while violating the security
contract.

Correction: add at least one representative invalid configured host and one
unsupported/present-invalid scheme through the same isolated public HTTP seam.
For each, assert the exact safe 503 result, absence of sendable/copy-success
content, and unchanged facts. A table-driven fixture is sufficient.

### B. MEDIUM — field error description is not proven to exist

Locations: `specs/YII2-USER-INVITATION-UX-001.md:35`,
`tests/Yii2/yii2_user_access_001_test.php:20`.

The corrected assertion proves that the expected input literally contains
`aria-describedby="invite-form-error"`, but it never proves that the response has
an element with `id="invite-form-error"` containing the actionable error. A
dangling reference passes, so the accessible association required by the contract
is not yet observed.

Correction: assert exactly one referenced description element exists, is the
visible/actionable invite error, and has non-empty safe text; keep the existing
field-specific and unaffected-sibling assertions.

## Delta decision

The correction substantially improves the matrix and four findings are fully
resolved, but both open items are foreseeable acceptance gaps in the corrected
tests. Gate 3 remains **CHANGES_REQUESTED**. Return to Gate 2, then prepare a new
exact-source package and fresh intended RED evidence for another independent
rereview. Production implementation, final review, CI, publication, merge and
deployment remain `UNKNOWN`.

---

# Gate 3 restart — test setup delta v4

- Reviewer: `/root/issue250_gate3` (independent; authored neither reviewed test
  correction nor production implementation)
- Review purpose: Gate 3 restart for the root-authored test-only delta discovered
  during Gate 4; this is not a Gate 5 production verdict
- Prior approved test snapshot: package
  `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T184914Z-dadc74f4bd/snapshot`
- Current exact source: `d98dbf27c288edb4e4e1a59c7aed10758af9d59e8ca8a31efd29da0ae57eae7b`
- Current executable source: `3cad3fe9fdc89c0575e5b0548b2265f56506e58db82fa083b009c720e44c9232`
- Review package:
  `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T190121Z-eb85fc498f/package.json`
  (`sha256:30357f6d31e71db961d67548af7c08e7587b9970bdd7d9ac70b5bb4402694a3a`)
- Package delta: `delta.patch`
  (`sha256:32ab9f1cd44bcdb79071b2b6ef76bdebb167d97166bbf0de823c4ebdf5cfe25e`)
- Current reconstructible snapshot: `snapshot/source.patch`
  (`sha256:e79fcac0dfd22d1d729ba409a2ad10db172bc7a636c8a802ff4334d1c3b1b285`)
- Verification plan: package `verification-plan.json`
  (`sha256:a380ff037838765a72ab7182210eaa2c1897656520871d594be3370dded04abd`),
  lane `CRITICAL`, required reviews `gate3`, `final`
- Verdict: **APPROVED**

## Harness limitation and review boundary

The package is formally final-review/Gate-5-shaped because the v1 verification
plan has no stable command identity and the current harness cannot attach the
historical corrected-test RED or test-delta lineage as a native Gate 3 package.
That packaging limitation is recorded rather than treated as approval evidence.

This verdict reviews only these two root-authored test corrections against the
previously approved test snapshot:

1. nested `originCases` environment arrays plus authenticated, same-port fixture
   restart in `tests/Yii2/yii2_user_access_001_test.php`;
2. bounded waiting for the asynchronous clipboard-success status in
   `tests/Yii2/user_access_browser.mjs`.

The package also contains executor-authored production and lifecycle changes.
Those bytes were visible for source binding but were not approved by this Gate 3
restart; they still require the independent final review selected by the plan.

## Test-delta findings

No blocking findings.

### Origin fixture correction

The original flat `originCases` entries did not provide the intended environment
map, and an unauthenticated POST could stop at auth/CSRF rather than reach trusted-
origin admission. The correction now:

- uses an actual nested environment map for each missing/invalid origin case;
- starts a valid fixture, logs in through the public seam and obtains a real CSRF;
- stops and restarts on the exact same port so the trusted-host/session identity
  remains coherent;
- preserves cookies and CSRF across that restart;
- applies only the tested invalid environment on restart; and
- then asserts public HTTP 503, safe reason, no invitation/copy presentation and
  no complete fact delta.

This makes each case reach the intended boundary without changing the contract or
weakening any expectation. The malformed configured host case supplies its
matching raw Host, so it continues to isolate configuration validation rather
than the separate raw-Host mismatch guard.

### Clipboard timing correction

Clipboard `writeText()` resolves asynchronously and production updates the live
status after the promise settles. The former immediate text read was a race. The
correction uses Playwright's bounded exact-text `waitFor()` for
`Ссылка скопирована.` after keyboard activation, then retains the independent
assertion that the clipboard received the exact displayed URL. It neither adds an
unbounded delay nor accepts intermediate/manual/error text, so the truthful-copy
oracle is preserved and made deterministic.

## Evidence

- Historical corrected-test pre-production INTENDED_RED:
  `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790276298350418000-cbf93e941fb640a19c71ee0ae6b41cc7.json`.
  It runs the corrected HTTP command blob
  `d70aa2dd3506bcdb287272eadac19c017a2a17e11b30ed6c4f9f91b4a6286930`
  against pre-production source and exits `255` at the absent
  `invite-form-error` target. This is an intended product failure, not either
  corrected setup bug.
- Fresh exact-source HTTP GREEN:
  `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790276460106323000-28f49ffe2f2144359b2b488a0d29ad3c.json`,
  exit `0`, same HTTP command blob, expected PASS marker.
- Fresh exact-source browser GREEN:
  `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790276460106341000-6d1113021735470ab3715aa2eb96cf64.json`,
  exit `0`, expected browser PASS marker. Its exact executable-source binding
  covers the changed browser module even though the unchanged PHP wrapper command
  blob alone cannot express that lineage.
- Both GREEN records bind source
  `d98dbf27c288edb4e4e1a59c7aed10758af9d59e8ca8a31efd29da0ae57eae7b`,
  executable source
  `3cad3fe9fdc89c0575e5b0548b2265f56506e58db82fa083b009c720e44c9232`
  and environment
  `c097b71807c953141825964edba292c021e615fbf4a878904de9e6cf9b56ab33`.
- Reviewer bounded static checks: corrected HTTP test passes `php -l`, browser
  module passes `node --check`, and `git diff --check` passes. No full suite was
  run.

## Decision

The two test-only setup corrections are **APPROVED** for Gate 3 restart. They
restore intended fixture reachability and deterministic observation without
changing normative expectations. The approval applies to the test delta bound to
the exact current source above; it does not approve executor production code or
replace Gate 5. Final review, remaining planner-selected focused obligations,
exact-source CI, publication, merge and deployment remain `UNKNOWN`.

---

# Gate 3 delta rereview v3

- Reviewer: `/root/issue250_gate3` (independent; authored neither contract nor tests)
- Supersedes the v2 verdict for this corrected candidate
- Exact source: `d1ecd4eb7368c588b1d6e1b9cebf6c08a4796526ab4b2b74a1ad514dcc8bb9d3`
- Executable source: `8e30748aa430f1ef9d5c30b602efa896bc479f3a1268ca975d7e2a6aac9253cc`
- Role package:
  `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T184914Z-dadc74f4bd/package.json`
  (`sha256:29377e24db110cd27b8cc04ff956483f1c7d7a66438649075607ff06d8d55624`)
- Delta: package `delta.patch`
  (`sha256:e1b425e05dabac36982cb961a0cb2c960081c186fbde520ee218aac09a984769`)
- Reconstructible snapshot: package `snapshot/source.patch`
  (`sha256:f9c0f455854419177d91385062d62780f13db8ecb79a7bcb2a56cb9debc6c9bf`)
- Verification plan: package `verification-plan.json`
  (`sha256:6f9f4798090e8d8dcd3ce6bda72ce6c1d76a7bba0401b5e79705992563a051c9`),
  lane `CRITICAL`, required reviews `gate3`, `final`
- Verdict: **APPROVED**

## Fresh exact-source evidence

- HTTP INTENDED_RED: `php tests/Yii2/yii2_user_access_001_test.php`, record
  `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790275737451582000-3b300d5965cf402189b242c3b4408744.json`.
  It exits `255` because the existing UI has no `invite-form-error` description
  target. This is the intended missing behavior, not a setup failure.
- Browser INTENDED_RED: `php tests/Yii2/yii2_user_access_browser_001_test.php`,
  record
  `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790275737456474000-bc7d5a6368bb4a60aebd37c11a2d6d25.json`.
  It exits `255` because the existing create link is not the required absolute
  trusted-origin URL. This is also an intended missing behavior.
- Both records match the package candidate source, executable source,
  environment and registered command blobs. The changed HTTP test digest is
  `45d5ffc501729b3160a67088b84c193285d3cb4a90e467ffcacbf629059ea936`.
- Reviewer bounded checks: both PHP entrypoints pass `php -l`, the browser module
  passes `node --check`, and `git diff --check` passes. No full suite was run.

## v2 open-finding dispositions

### A. FIXED — present-invalid configured origins are exercised

The public HTTP matrix now covers three isolated bootstrap/admission cases:
missing scheme, present unsupported scheme `ftp`, and present malformed host
`bad/host` with a matching raw Host so the configured-origin validator—not a
mere Host mismatch—is the relevant boundary. Every case requires HTTP 503,
`SERVICE_UNAVAILABLE`, no activation/copy-success presentation and an unchanged
complete facts snapshot. This closes the absent-versus-invalid validation gap.

### B. FIXED — the described field error is an observable real target

For every invalid email/full-name/duplicate case, the test now requires exactly
one `id="invite-form-error"` element, requires it to be a paragraph with non-empty
rendered text, and retains the field-specific `aria-describedby`, `aria-invalid`
and focus assertions plus the unaffected-sibling and no-invented-duplicate
checks. A dangling ARIA reference can no longer pass.

## Decision

All six original findings are now resolved: findings 2, 3, 4 and 6 retained their
v2 fixed disposition, and open A/B close findings 1 and 5. The corrected tests are
traceable to the normative contract, exercise public HTTP/browser seams, preserve
security and fact-boundary assertions, remain deterministic and isolated, and
have valid exact-source intended RED evidence.

Gate 3 is **APPROVED** for exact source
`d1ecd4eb7368c588b1d6e1b9cebf6c08a4796526ab4b2b74a1ad514dcc8bb9d3`.
This approval authorizes progression to Gate 4 only; implementation correctness,
GREEN focused checks, final review, exact-source CI, publication, merge and
deployment remain `UNKNOWN`.

---

## Latest Gate 3 verdict pointer

The chronologically latest decision is **Gate 3 restart — test setup delta v4**
above: **APPROVED** for the two root-authored test corrections bound to exact
source `d98dbf27c288edb4e4e1a59c7aed10758af9d59e8ca8a31efd29da0ae57eae7b`.
The v3 section immediately preceding this pointer is retained as historical
approval of the earlier snapshot and does not supersede v4.

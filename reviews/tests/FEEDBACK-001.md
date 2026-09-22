# Test review: FEEDBACK-001

- Reviewer: independent `gpt-5.6-sol` Gate 3 agent `/root/gate3`
- Test author: root agent
- Reviewed source: base `41573bf75a067eafc1422a24d2761b45805274cc` + retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T181504Z-a4161d6c3f/snapshot/source.patch`, SHA-256 `80a75a41201a90a98909d0912173dd86bb312a15a5805759c5b783cb3e3b6f90`; candidate source `1ec20a209c6eaaa2d4a944cfc49d800b0ceb6850b7ef53ab1ba147403a4a391f`
- Agreed review scope / prior findings disposition (for rereview): first Gate 3 review of issue #31; complete FEEDBACK-001 contract, OpenSpec design, focused tests, required v25 frontier corrections, verification registrations, and retained RED evidence
- Specification: `specs/FEEDBACK-001.md`, A1-A7
- Public seam: `FeedbackApplication::submit/listing/recordResult`; Yii2 `/pilot/feedback` and `/pilot/admin/feedback`; canonical migration and runtime backup/restore seams
- Red command and intended failure: `php tests/Yii2/yii2_feedback_001_test.php` fails at `INTENDED_RED FEEDBACK-001 application seam missing`; `php tests/Yii2/yii2_feedback_browser_001_test.php` fails at `INTENDED_RED FEEDBACK-001 browser route absent` (HTTP 404). Both failures are attributable to the absent behavior rather than setup.
- Verdict: `CHANGES_REQUESTED`

## Findings

1. **HIGH — the v25 direct-consumer/frontier matrix is incomplete and contains stale terminal assertions.** `tests/InstallationProcess/assignment_order_original_attempt_audit_schema_001_test.php:93` still expects terminal version 24 although the same case expects a v25 repeat; `tests/InstallationProcess/inspection_evidence_schema_001_test.php:312` expects 24 while its message and applied-version list say v25; `rapid-pilot/verify-calendar-projections.php:21` still requires the exact v24 catalogue. The planned production consumer `app/demo/PilotDemoDatabase.php:58` also requires v24 and is absent from the planned boundary list. These exact-frontier consumers will reject a correct v25 implementation. Correct every current-frontier expectation to the literal v25 contract, include the demo database consumer in the implementation package, and retain focused evidence for the changed consumers. Historical v24 profiles must remain unchanged.

2. **HIGH — A7 does not prove that populated feedback history and replay identities survive the public backup/restore seam.** `tests/Yii2/feedback_schema_matrix.php:4-9` checks catalogue replay and compares live table inventories with `RuntimeRecoverySchemaV25`; the modified recovery tests add empty feedback tables to counts/inventories. None submits a feedback root and result, backs up, restores into a fresh target, and verifies exact facts and replay behavior. Add a current-v25 public backup-to-fresh-restore scenario populated through `FeedbackApplication`; compare roots, results, request identities and AUTO_INCREMENT frontiers, then replay both command kinds and assert the original receipts and zero duplicates.

3. **HIGH — A3-A5 transport rejection and recovery behavior is only partially traced.** `tests/Yii2/feedback_http_matrix.php:5-28` omits HEAD behavior, an exact wrong-method matrix, guest POST/CSRF handling, result validation/not-found/conflict/storage-failure mappings, and no-write assertions for several 400/403 cases. Its submit 422 and 503 checks retain only part of the required retry state: the valid description, request ID, and normalized safe page context are not all asserted, and result retry retention is absent. Add exact status/method checks and before/after fact assertions for each rejection; verify all valid retry fields remain while exception, SQL, and credential details do not escape.

4. **HIGH — A6 navigation and browser coverage omits most required surfaces and the review journey.** `tests/Yii2/feedback_http_matrix.php:29-32` checks only users, roles, and the feedback page, while A6 names queue, object card, preparation/original/opening, construction control, installer directory, users/roles, and the shared checklist shell. `tests/Yii2/feedback_browser.mjs:6-17` checks only form overflow, textarea labelling, and a single Tab at two widths; it does not exercise submission/confirmation/return, authorized admin listing/result, admin-only affordance, or geometry against primary actions. Exercise every named shell with the specified authorization visibility and add bounded 360px/desktop browser journeys for submission and review, including keyboard activation and overlap/overflow checks.

5. **HIGH — the concurrency oracle does not establish an actual race.** `tests/Yii2/FeedbackFixture.php:23-38` starts workers without an arrival/release barrier, so `tests/Yii2/yii2_feedback_001_test.php:59-62` may pass through ordinary sequential replay/collision. The same-result case also only compares the two returned arrays and never independently asserts `saved`, exactly one appended result, or the preserved receipt. Add deterministic synchronization at the competing insert boundary (for example, a coordinated database lock/trigger protocol while still invoking the public owner) and prove same-ID/same-payload, same-ID/different-payload, and different result IDs under overlap with literal fact counts and receipts. Also cover the A4 result collision where the same request ID is reused for a different `feedbackId`, not only different result text.

6. **MEDIUM — the privacy and schema oracles can agree with an over-collecting implementation.** `tests/Yii2/yii2_feedback_001_test.php:16` searches stored row JSON for a few strings but does not supply distinct hostile IP/header/full-name/email/phone values through the HTTP boundary. `tests/Yii2/feedback_schema_matrix.php:6-8` compares database inventory with production recovery inventory, allowing the migration and recovery profile to share the same erroneous extra metadata. Add literal expected feedback column, nullability, key/index and relationship assertions derived independently from A1/A4/A5/A7; send recognizable forbidden metadata at the transport boundary and assert that only the allowlisted facts are stored and returned. Use literal feedback table and AUTO_INCREMENT membership expectations in addition to production-to-production comparisons.

The specification and design clearly assign command ownership to the Yii composition seam and business facts/persistence to `MariaDbFeedback`; that proposed ownership is consistent with the repository's single-owner and append-only rules. No architecture baseline change is justified at Gate 3. The tests use isolated MariaDB/Yii fixtures and public seams, and the retained RED results are deterministic at the present missing-seam boundary, but the six gaps above block implementation.

## Required changes

Resolve findings 1-6, regenerate the prepared exact-source package because tests/planned boundaries change, rerun the two focused RED commands, and submit the corrected delta for independent Gate 3 rereview.

## Rereview — corrected candidate 2026-09-14

- Reviewed source: base `41573bf75a067eafc1422a24d2761b45805274cc` + retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T182524Z-c07d6801fc/snapshot/source.patch`, SHA-256 `56f50f1dc9c3987285bc537e3dd2b78d08b23826100fdc28f723e64560dab493`; candidate source `83af759099a6b5220bf35f04e9df0771dc2873bd48561361a4e0ec6dce4dca59`
- Corrected delta from prior reviewed source: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T182524Z-c07d6801fc/delta.patch`, SHA-256 `284c36bc0d2f209265e5ad88588e93deb40d1f5af685359b619620878a18a7b9`
- RED evidence: `php tests/Yii2/yii2_feedback_001_test.php` still fails at the missing `FeedbackApplication`; `php tests/Yii2/yii2_feedback_browser_001_test.php` still fails at the missing route with HTTP 404. Package records `1789410282910008000-a4f2abc9bfff483090d3a17c32b1909d` and `1789410282910026000-068496beeb304e6dbeadeb7c551b6b35` bind both intended failures to this candidate.
- Verdict: `APPROVED`

### Findings disposition

1. **Resolved.** Stale current-frontier assertions now require v25 in both identified installation tests and the rapid-pilot calendar verifier. `app/demo/PilotDemoDatabase.php` is explicitly in the planned implementation boundary, and its literal 73-table consumer expectation is covered by the corrected demo bootstrap test. Historical recovery profiles remain unchanged.
2. **Resolved.** `tests/Runtime/runtime_jobs_recovery_001_test.php` creates a feedback root and result through `FeedbackApplication`, carries their rows and AUTO_INCREMENT state through the real backup/restore seam, and replays both request identities against the restored database with the original receipts and no added facts.
3. **Resolved.** `tests/Yii2/feedback_http_matrix.php` now covers HEAD, exact 405 behavior for known routes, guest/CSRF outcomes, nonscalar payloads, 403/404/409/422/503 mappings, no-write invariants, retained submit/result retry fields, and sanitized infrastructure failures. The normative spec now makes the known-route 405 outcome explicit.
4. **Resolved.** `tests/Yii2/yii2_feedback_browser_001_test.php` exercises every named incumbent shell. The Playwright journey covers ordinary-user visibility, keyboard navigation and submission, confirmation/return, admin-only review access and result recording at 360px and 1280px, with overflow/control-hit geometry and screenshots.
5. **Resolved.** The concurrency fixture holds both feedback tables, observes both public worker calls waiting through `SHOW PROCESSLIST`, and only then releases them. Literal root/result count and receipt assertions cover identical commands, conflicting payloads, independent notes, and reuse of a result request identity against another feedback root.
6. **Resolved.** The schema matrix now has literal column/type/nullability, primary/unique-key, result-root FK, table-membership and AUTO_INCREMENT expectations. Distinct hostile HTTP fields and headers demonstrate that disallowed identity, contact, network, document and client-version metadata is neither stored nor returned.

No new findings. The corrected tests remain deterministic and isolated, derive expectations independently from FEEDBACK-001/design, exercise the agreed public seams, and are sensitive to plausible privacy, replay, concurrency, migration, recovery, authorization and UI regressions. Gate 3 may advance to implementation against this exact candidate.

## Gate 3 test-delta review — 2026-09-14

- Reviewed source: retained package `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T184435Z-50547ada31/package.json`; candidate source `262d0104e05c14096db25773e43567e32d339438d6531969333c67e4632cf777`; snapshot patch SHA-256 `0487dd1c5360a1d47cb9c083d89eedd9aa293c0a1eff8bd60ce5094320b4a988`; delta patch SHA-256 `c1d4e2ddec485a64383aebce88fe83e5e270af1dc42ddb96c44b8e0c6ad898ce`
- Scope: test/spec delta after the preceding approval; production implementation in the package was not reviewed for this Gate 3 decision.
- Evidence: owner suite GREEN record `1789411426800362000-368a25a1e496479e9e55963b6e0dcd1c`; browser suite intended RED record `1789411450753249000-b668049e28c347babdd811ca0fd53818`, failing specifically because the implemented description textarea is shorter than the independently asserted 96px multiline minimum. Historical preimplementation RED remains retained above.
- Verdict: `APPROVED`

### Findings

None. The four fixture corrections repair test execution without weakening an acceptance expectation: non-empty nested arrays survive `http_build_query` and still exercise nonscalar rejection; explicit process termination replaces a nonexistent fixture helper; binary table ordering matches the public immutable recovery inventory; and substring matching observes the saved result inside its rendered audit context. The added browser assertions give A6's usable, unobtrusive feedback entry a bounded oracle: the navigation target must expose visible text or an icon and measure at least 24px in both dimensions, and the sole description field must provide at least 96px for multiline input. These checks operate at 360px and 1280px through the real Yii/browser seam and are sensitive to the observed blank-mobile-navigation and compressed-textarea regressions without prescribing production markup or CSS.

The test delta is approved for the executor's bounded UI correction. Any further expectation or test-mechanic change requires another independent delta review before Gate 5.

## Gate 3 clipping-oracle restart — 2026-09-14

- Reviewed source: retained package `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T185301Z-80c0e40d37/package.json`; candidate source `7bea0cf7e2e4b417f6c101db186836aadbeb6433515c3f964c448add2a210c26`; snapshot patch SHA-256 `52c4697b9722e547e5bd0e5f45edf779052c3288bf1572e9f733ae2528f74e49`; delta patch SHA-256 `6950de783f39c9e662efb4c2072021a8628998aeb8eb77b9c85bc50254cea1e7`
- Scope: only the `tests/Yii2/feedback_browser.mjs` geometry-oracle delta prompted by the independent Gate 5 visual finding; production changes present in the package are outside this decision.
- Evidence: browser intended RED record `1789411951862951000-dbf008098dde492982c143d36776c91e` fails at `multiline text must remain visible through field wrappers`; owner GREEN record `1789411953027931000-202a4e4e3c4a486b8b362597ae9f55b3` confirms the nonvisual acceptance suite remains green. The visual defect and screenshots are recorded in `reviews/code/FEEDBACK-001.md`.
- Verdict: `APPROVED`

### Findings

None. The earlier test measured the textarea's own box and could pass while an ancestor with clipped overflow exposed only a one-line strip. The correction intersects the textarea rectangle with every scrolling or clipping ancestor and requires at least 96px of visible vertical content. It applies through the existing ordinary and admin journeys at both 360px and 1280px, so it covers every description/result textarea already exercised. The expected visible height is unchanged; the delta closes a false GREEN in the oracle and reproduces the observed defect for the intended reason without prescribing a CSS fix.

This test delta is approved. The executor may correct the feedback-local wrapper geometry; any further test or expectation change requires another independent Gate 3 delta review.

## Post-CI test-delta review — 2026-09-14

- Reviewed source: checkpoint/reconstruction base `cf0ba05b9f70251ef87bbd6da7ba733643942802` plus retained package `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T192108Z-0a387d2c17/package.json`; candidate source `a71000454186c028fcadc4527d4119fb2ef9b34cf9d048605f709187d9af99e0`; snapshot patch SHA-256 `061e9a6ff6b782818ab2879e3bd240e0e1967f9aa7320c7c1b8bd0f6d614b648`; delta patch SHA-256 `450e9316f36e45c920ae122f7988aab8b4aba4d9318f43407b222e22c2f9884e`
- Scope: six test-only corrections after exact-source CI run `34884408428`; no production, normative specification, CI workflow, planner, or harness change is included in this verdict.
- Failed-run evidence: complete failed-job and `REGRESSION_FAILURE` inventory `/Users/antropophag/.local/share/fmonitor-2/issue-31/ci-34884408428-failure-inventory.json` was inspected. The test changes correspond to the five stale direct-consumer expectations and the activation-proxy ordering failure; `verify` and Quality Graph were aggregate failures.
- Focused evidence: process readiness `1789413508559339000-57d9a1d1c5fd47b7b9a761c919b00579`; schema readiness `1789413508569686000-95215f46685a48eabd982b4f33b1b2e2`; workforce catalogue `1789413508563225000-d083521c4cec4d32ba5ad1afcee3f3ae`; public web asset contract `1789413508586065000-4e4018caffc54c49ab38fa088562cff9`; verification composition `1789413508585658000-ff7178db67164bb3957e06ae7cf9aa1d`; activation proxy `1789413634604893000-ce2e5593f3b946b7b13ff5daf95c1ef5`. All six are GREEN for the corrected source. The package also retains current owner/browser GREEN.
- Verdict: `APPROVED`

### Findings

None. The workforce inventory adds exactly the two canonical v25 feedback tables; both readiness fixtures advance only their current terminal schema literal from 24 to 25; the asset contract updates the exact hash for the already independently reviewed CSS bytes; and the verification composition adds the already registered feedback browser test to the literal e2e command list. These changes reconcile direct consumers with delivered behavior and preserve their exact-list/hash sensitivity.

The activation-proxy change also preserves its privacy and operational contract. Baseline and current reproductions show that concurrent nginx workers can emit the same three completed access records in different orders. Comparing the exact sorted `(method, uri)` multiset removes only the unsupported ordering assumption; the test still requires exactly three records, the precise route multiplicities, status and upstream status, valid timing fields, three distinct request IDs, and absence of every prohibited request field. No behavior expectation is weakened.

The six-file post-CI test delta is approved. The failed CI run remains historical failure evidence; a new exact-source CI result is still required before publication readiness can be claimed.

## Issue #172 Gate 3 review — 2026-09-22

- Reviewer: independent `gpt-5.6-sol / low` Gate 3 agent `/root/issue172_gate3`
- Test author: root agent; production executor has not authored this review
- Reviewed source: base `0504d2589835f2583dc9afdbc47e4694e2573365` + retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260922T155510Z-1a59a4367f/snapshot/source.patch`, SHA-256 `89f3bc1dc32ff15985037d55309e9a15f79ad75457cef4a05245f875a93de917`; exact candidate source `5e8489838049210b2083eec90cbb33c6ac56f4713a527a77c5960c0f6f32cd28`
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260922T155510Z-1a59a4367f/package.json`; package SHA-256 `e1f1fc687acf96ce8972662e69d1c19d4b5d8a567665715c8edef7d9d9b22455`; required-context SHA-256 `a9b337bf23169622b06b9614f7ec37cd265a640a3a5b2d945fdf66d0df7593fe`; verification-plan SHA-256 `e466a1d2f1591b9520315a69442754fa8e64ca78c01388c67f4a142af12619a2`
- Scope: issue #172 FEEDBACK-001 A2/A4/A5/A8/A9 context/build/replay correction and its connected application/HTTP/browser tests; planner lane `CRITICAL`, required reviews `gate3` and `final`
- Public seam: `FeedbackApplication::submit/listing`; Yii feedback form, submit, confirmation/return and protected operator list; existing strict runtime-readiness seam as non-regression witness
- Evidence: owner RED record `1790092469846410000-190bbd1906e440ad9db7c95bfa2b578b` fails because `FeedbackApplication::buildIdentityFile` is absent; browser RED record `1790092477191199000-c279a12b8ab1490494826c2a585782bf` fails on preservation of `/pilot/calendar`; readiness GREEN record `1790092486276837000-4a812486b42c40c0951c604e70bb54db` reports `PASS: RUNTIME-READINESS-LOAD-001 startup-bound constant-work readiness`. All three records bind candidate source `5e8489838049210b2083eec90cbb33c6ac56f4713a527a77c5960c0f6f32cd28` and executable source `3a58e633fff1687194cef36170ecfb4fb3b0e40f38cea69bc016c814bb70d6fc`.
- Verdict: `CHANGES_REQUESTED`

### Findings

1. **HIGH — the fail-soft build-identity oracle omits required distrust cases and does not establish the no-heavy-fallback boundary.** FEEDBACK-001 A2/A9 and the delta requirement say that an absent, unreadable, mutable, linked, or invalid immutable build file must yield `unknown`, and that feedback HTTP must not hash source or invoke Git, Docker/API, or external requests. `tests/Yii2/yii2_feedback_001_test.php:43-46` exercises only missing, mode-`0644`, and symlink files. It does not test malformed/oversized/non-ASCII content, an unreadable file, or a regular file with link count greater than one, so implementations accepting those explicitly distrusted inputs can pass. Its implementation-text checks prohibit only the literal strings `RuntimeBuildIdentity::read` and `RecursiveDirectoryIterator`; a direct hash walk, alternate runtime-identity invocation, shell/Git/Docker/API call, or external request still passes. Correct the application/HTTP test at the public owner seam with literal `unknown` expectations for every specified invalid file class (including hard-link and invalid-content cases, with a deterministic unreadable-file fixture appropriate to the test environment), and add a bounded behavioral dependency/hot-path oracle that fails if submit performs source traversal/hashing, process execution, Docker/API, or network access rather than relying on two source substrings. Preserve the existing independent strict-readiness GREEN witness.

The route table has independent positive expectations for each newly allowed path and representative sensitive negatives; object identity is derived only for object routes. Replay across builds, historical listing, client-version rejection, actor authorization, append-only facts, HTTP CSRF/method/rejection behavior, connected desktop/mobile journeys, and deterministic concurrent replay retain adequate sensitivity. Both REDs fail at missing issue behavior rather than fixture setup, and the readiness check is GREEN, but the uncovered trust/load boundary above is material to the stated security and steady-request contract.

### Required changes

Resolve finding 1, regenerate the prepared exact-source package because tests change, capture refreshed owner/browser RED and unchanged readiness evidence, and submit the corrected delta for independent Gate 3 rereview. No production implementation is authorized by this verdict.

## Issue #172 Gate 3 rereview — 2026-09-22

- Reviewer: independent `gpt-5.6-sol / low` Gate 3 agent `/root/issue172_gate3`
- Reviewed source: base `0504d2589835f2583dc9afdbc47e4694e2573365` + retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260922T160114Z-dfc515d70b/snapshot/source.patch`, SHA-256 `41c436b1335f466cd8621b412fea80997d83fcd5addfcbcb6d3a85db0184c649`; exact candidate source `9b00a4238173c8b031259d9954d2a5bc2d3f901c9c75463867bb766fc4390381`
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260922T160114Z-dfc515d70b/package.json`, SHA-256 `54e8cdea23bb47d14f7b3b80c98a575655eb72f28c492767c6b440ae0a83fe21`; verification-plan SHA-256 `ca125b62649afbc7df80e480efe1bb49af94bbe3af7864dd4f94c8845963f0ad`
- Corrected delta from candidate `5e8489838049210b2083eec90cbb33c6ac56f4713a527a77c5960c0f6f32cd28`: append-only prior review record plus the build-identity expectations at `tests/Yii2/yii2_feedback_001_test.php:46-50`; no production change is present
- Evidence: owner intended RED `1790092831409524000-1be3813268c047a6be99d09c601a8742` (`buildIdentityFile` seam absent); browser intended RED `1790092840211897000-bccdd3266ac642a39a6551b5724607e4` (`/pilot/calendar` context not retained); readiness GREEN `1790092848059131000-ec491c86e68c41f9ace5026a631d128e` (`PASS: RUNTIME-READINESS-LOAD-001 startup-bound constant-work readiness`). All bind exact candidate `9b00a4238173c8b031259d9954d2a5bc2d3f901c9c75463867bb766fc4390381` and executable source `8e7d4337d910f72d4509175b4b9d897989ff5fb0dc446891e5cfde6f51c4b3a7`.
- Verdict: `CHANGES_REQUESTED`

### Prior finding disposition

1. **Partially fixed, still open.** The corrected matrix now independently expects `unknown` for a multiply linked regular file, malformed 64-character non-hex content, and a mode-`0000` unreadable file; missing, mutable and symlink cases remain covered. The expanded dependency exclusions reject the named Runtime reader/source fallback, directory iterator, common process/network functions, `.git`, and Docker socket literals. Fresh RED and strict-readiness GREEN records are exact-source bound.

### Findings

1. **HIGH — the required behavioral hot-path oracle still does not execute the public submit path and uses an environment-sensitive wall-clock threshold.** `tests/Yii2/yii2_feedback_001_test.php:50` times twenty calls to `feedbackOwner(...)`; each call constructs a Yii database connection and component but never invokes `submit()`. An implementation may therefore defer source traversal, hashing, process execution or network access until `submit()` and still pass this assertion. Conversely, the literal `<1.0` second limit includes twenty environment-dependent connection/component constructions and can fail on a slow CI/database host even when identity fallback is constant work. The expanded source-substring list is useful architecture protection but is not the behavioral oracle required by the previous finding and can be bypassed through an unlisted helper/adapter. Replace or complement this with a deterministic bounded probe that invokes `FeedbackApplication::submit` with a missing identity file while instrumenting or isolating forbidden filesystem/process/network dependencies; assert a saved `unknown` result and exact bounded dependency interactions rather than elapsed wall time. Keep the static dependency exclusions as defense in depth.

No other new finding was introduced by the corrected delta. The file distrust cases, refreshed RED failures, and readiness non-regression are otherwise adequate.

### Required changes

Resolve the remaining behavioral/determinism gap in finding 1, regenerate the exact-source package, and capture refreshed owner RED plus unchanged browser RED/readiness GREEN evidence before another Gate 3 rereview. Production implementation remains blocked.

## Issue #172 Gate 3 rereview #3 — 2026-09-22

- Reviewer: independent `gpt-5.6-sol / low` Gate 3 agent `/root/issue172_gate3`
- Reviewed source: base `0504d2589835f2583dc9afdbc47e4694e2573365` + retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260922T160524Z-6e7a86d2d2/snapshot/source.patch`, SHA-256 `4e8483c5a60de70b0f75c3956b5e80626fe376dfaba104fcf5de01e6a11980fb`; exact candidate source `370e41327c2ec60563b6ef89d7ec55654c4889b563c92a6eb6106d7a016f31ae`
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260922T160524Z-6e7a86d2d2/package.json`, SHA-256 `eb2ebde32423789a9e76a15d6427f93a49df1c9066bf628697ed961238c81b1d`; verification-plan SHA-256 `09c6bb9f891e02d4782bbf52a4915cd993d8201e2211ff0af147fc86795ced04`
- Corrected delta from candidate `9b00a4238173c8b031259d9954d2a5bc2d3f901c9c75463867bb766fc4390381`: removes the wall-clock assertion and adds the isolated `dependencyProbe` worker plus its public submit/listing assertion; no production code is changed
- Evidence: owner intended RED `1790093076000209000-f52a97af52714791b33741e91e9ce6bc` (`buildIdentityFile` seam absent); browser intended RED `1790093082984329000-dd3a8ccebc8a4793a2ba4663d24ed125` (`/pilot/calendar` context not retained); readiness GREEN `1790093091508266000-61d323a1d4834c209b628975631cba86` (`PASS: RUNTIME-READINESS-LOAD-001 startup-bound constant-work readiness`). All bind exact candidate `370e41327c2ec60563b6ef89d7ec55654c4889b563c92a6eb6106d7a016f31ae` and executable source `e5d06a066475253dc727eb422534f597c832916e82108b6158bd2523787cdcb9`.
- Verdict: `CHANGES_REQUESTED`

### Prior finding disposition

1. **Partially fixed, still open.** The environment-sensitive timing assertion is removed. The worker now warms required classes and the DB, disables URL stream wrappers, restricts `open_basedir` to the fixture directory, promotes filesystem warnings to exceptions, invokes real `submit` and `listing`, and independently requires `saved`, persisted `unknown`, and the normalized calendar path. This closes deferred filesystem/source traversal through ordinary PHP file APIs and verifies the public persistence path.

### Findings

1. **HIGH — process and non-stream network execution remain unobserved, while the asserted interaction count is a constant.** `tests/Yii2/feedback_worker.php:10-16` makes stream-wrapper and out-of-root filesystem access fail, but `open_basedir` and wrapper removal do not constrain subprocesses or socket-extension calls. The static exclusions at `tests/Yii2/yii2_feedback_001_test.php:49` omit direct PHP process primitives `exec`, `system`, `passthru` and `pcntl_exec`, and non-stream socket primitives such as `socket_create`/`socket_connect`; an implementation that obtains Git/Docker/API/build identity through one of those paths can still return `saved` and pass the probe. Moreover, `forbiddenInteractions` is emitted as the literal `0` at `feedback_worker.php:16`; it is not an observed counter, so the final zero-interaction assertion adds no sensitivity. Extend the fail-closed dependency boundary to every available process-execution and network primitive (or make the production reader depend on an injected narrow file-reader seam whose fake records exact calls), and remove the fabricated counter or replace it with actual recorded interactions. The public probe must continue to prove `saved`/`unknown`/persisted path without wall-clock thresholds.

No other new finding was introduced. File distrust coverage, route/replay expectations, deterministic RED causes and strict readiness non-regression remain adequate.

### Required changes

Resolve finding 1, regenerate the package, and refresh the exact-source evidence. Production implementation remains blocked by Gate 3.

## Issue #172 Gate 3 rereview #4 — 2026-09-22

- Reviewer: independent `gpt-5.6-sol / low` Gate 3 agent `/root/issue172_gate3`
- Reviewed source: base `0504d2589835f2583dc9afdbc47e4694e2573365` + retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260922T160921Z-84e1cfca8b/snapshot/source.patch`, SHA-256 `f3151dc8f560c20bdb1b69630bb1c591015fe91f8f2a073e201e46a2afdeb62a`; exact candidate source `a1e0c3f837b17bc6036222bf29f9dd18add5206d52a37ae4250f37d706da03b8`
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260922T160921Z-84e1cfca8b/package.json`, SHA-256 `8a809604a2fcd2b9d16b00afcaae103505a56d6ea34a79e236b67a74f4fe7c7a`; verification-plan SHA-256 `89927db90000350a1fae7449409761b8071dd7cd89c025eae741b5850442d9ea`
- Corrected delta from candidate `370e41327c2ec60563b6ef89d7ec55654c4889b563c92a6eb6106d7a016f31ae`: removes the fabricated interaction counter and expands fail-closed source exclusions to direct process/fork, curl/stream/socket, Git/Docker and literal external-URI mechanisms; no production code is changed
- Evidence: owner intended RED `1790093312932467000-ece84543e2eb4870904f90df2ecb8af0` (`buildIdentityFile` seam absent); browser intended RED `1790093320215102000-6acd2fc8d46b4a5989391f5f48518194` (`/pilot/calendar` context not retained); readiness GREEN `1790093328869822000-895948855e2e4ddd926f8f33efcfd5b7` (`PASS: RUNTIME-READINESS-LOAD-001 startup-bound constant-work readiness`). All bind exact candidate `a1e0c3f837b17bc6036222bf29f9dd18add5206d52a37ae4250f37d706da03b8` and executable source `578ab244989e2b1b6c85c5aaa7f8b7390bc516725d3a699508a90075de4de300`.
- Verdict: `APPROVED`

### Prior finding disposition

1. **Resolved.** The worker's successful public `submit` plus persisted `listing` under warmed DB/classes, fixture-only `open_basedir`, removed network wrappers and warning-to-failure handling is the observable behavioral boundary; it must produce `saved`, `unknown` and `/pilot/calendar`. The source oracle now independently fails closed on the direct process execution/fork, curl/stream/socket, Git/Docker and literal external URI mechanisms relevant to this bounded private reader. The prior hardcoded interaction count has been removed rather than presented as observed evidence.

### Findings

None. The complete test candidate traces FEEDBACK-001 A2/A4/A5/A8/A9 through the public application and connected Yii/browser seams. Expected values are independently literal for the route/object-ID matrix, full build identities, distrust cases and replay across builds. Authorization, CSRF/method rejection, privacy, append-only replay/concurrency and administrative historical display remain covered. The owner and browser REDs are attributable to the missing behavior, not setup; the separate strict readiness contract remains GREEN for the same exact source. Tests are isolated from production systems and no wall-clock expectation remains.

### Required changes

None. Gate 3 may advance to implementation against exact candidate `a1e0c3f837b17bc6036222bf29f9dd18add5206d52a37ae4250f37d706da03b8`; later test or expectation changes require a new independently reviewed delta.

## Post-implementation Gate 3 test-delta review — 2026-09-22

- Reviewer: independent `gpt-5.6-sol / low` Gate 3 agent `/root/issue172_gate3`
- Review type: test-mechanic delta only; this is **not** Gate 5 or a production-code verdict
- Approved expectation baseline: package `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260922T160921Z-84e1cfca8b/package.json`, candidate `a1e0c3f837b17bc6036222bf29f9dd18add5206d52a37ae4250f37d706da03b8`
- Reviewed source: base `0504d2589835f2583dc9afdbc47e4694e2573365` + retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260922T162256Z-84ff4efc1a/snapshot/source.patch`, SHA-256 `f1042404cc74df04a79f0b5ac92ad08a763d63269a867e43abd8ebeb76dacf94`; exact candidate source `1294b04fd2854c01b12fcebffbde5e013002912454f088385d8642028d0e4a4b`
- Current-GREEN package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260922T162256Z-84ff4efc1a/package.json`, SHA-256 `49304d014a16d5ce63fe3f26875230970b760eeec61aa198a18f3349d1f4d584`; verification-plan SHA-256 `2ae429d3e7c11cb1a2e77bbb799f8f559f9ca73f70dd26fb73e19fb0d578a1ce`
- Test-mechanic delta: `FeedbackFixture::feedbackBuildFile` temporarily restores owner-write mode before rewriting its same worktree-local fixture and returns it to `0444`; dependency probe warms Yii schema/transaction dependencies before isolation, adds only the repository `vendor/` dependency root to `open_basedir`, and reports exception messages; browser persistence actor literals change from obsolete InspectionFixture IDs `97/94` to the actual UserAccessFixture identities `9401/9101`
- GREEN evidence: owner/application/HTTP/concurrency/schema suite `1790094118834001000-22757f5162a24cdb90367d7b4755ded9`; connected browser suite `1790094129764861000-09c2daa569d8467796770965461dfe41`; strict readiness non-regression `1790094151884626000-843dc7f62af84b41b79dacfbb777f5e4`. All exit 0, have empty stderr, and bind exact candidate `1294b04fd2854c01b12fcebffbde5e013002912454f088385d8642028d0e4a4b` plus executable source `2ffdf9b2e379dac9f623d07eacecb3ee1e4108eef1c40fa8fcc94b0e3d3038fd`.
- Verdict: `APPROVED`

### Findings

None. Temporarily making an existing test-owned file writable changes only fixture setup; the file is again `0444` before the application reads it, so the immutable-file expectation remains effective. Warming schema/transaction classes removes an autoload false failure after `open_basedir` is tightened; allowing only `vendor/` preserves the intended exclusion of application source, configuration, repository metadata and arbitrary filesystem roots, while disabled URL wrappers, promoted warnings and static process/network exclusions remain unchanged. Adding exception messages improves failure diagnosis without changing success conditions. The browser actor correction strengthens the real persistence attribution assertion by matching the fixture identities actually authenticated; row counts, roles, journeys and authorization expectations are unchanged.

The three GREEN results therefore demonstrate the previously approved expectations rather than weaker substitutes: build distrust and dependency isolation still execute, the application/HTTP matrix remains complete, the browser performs two real submit/review journeys, and strict readiness remains independently GREEN.

### Required changes

None. The test-mechanic delta is approved for exact candidate `1294b04fd2854c01b12fcebffbde5e013002912454f088385d8642028d0e4a4b`. A separate independent Gate 5 review is still required for production code and overall final acceptance.

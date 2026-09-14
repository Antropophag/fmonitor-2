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

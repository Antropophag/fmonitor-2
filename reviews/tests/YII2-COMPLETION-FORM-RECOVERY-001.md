# YII2-COMPLETION-FORM-RECOVERY-001 — Gate 3 test review

- Reviewer: `/root/completion_gate3` (independent; did not author the specification or tests)
- Test/spec commit: `abe991d1c9ca46bbcafe92aa89540a1b6769c377`
- Candidate source: `1514838f37cd360f4827f3a11d465ff909351a06879c89c7a354a657e8e2b8d5`
- Base: `bced877aec8a8802e97037749ca4251d3098df1a`
- Prepared verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260922T171528Z-fc49f6c8db/verification-plan.json` (`CRITICAL`; Gate 3 and final review required)
- Specification: `specs/YII2-COMPLETION-FORM-RECOVERY-001.md` (`git hash-object`: `1818af06ee43b9fda5d0e62c155edfa768b68672`)
- Primary test: `tests/Yii2/yii2_completion_form_recovery_001_test.php` (`git hash-object`: `29db614166092782fc7284ed7f31bc8f6e017339`)
- Public seam: authenticated Yii object-card completion POST/rendering and the real completion browser behavior
- RED record: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790097418765060000-a97cb3c1f76e4077b33a43c558fb9315.json`
- Verdict: **CHANGES_REQUESTED**

## Findings

1. **The required browser scenario is not executable.** The contract requires one
   connected browser scenario covering per-form in-flight suppression, `422/409`
   fragment replacement, correction disclosure, tab activation, scrolling, focus,
   unknown network/non-HTML outcomes, value preservation, warning copy, re-enable,
   no automatic retry, and navigation only after confirmed success. No
   `tests/Yii2/completion_form_recovery_browser.mjs` exists or is invoked. Lines
   73–78 of the PHP test only search the JavaScript source for three strings; an
   inert or behaviorally wrong asset can pass those assertions. This also leaves
   the browser-specific access-loss rule unproved: retained input must not permit a
   forbidden command after capability/activity/session loss.

2. **A2's recognized-conflict and fail-closed matrix is materially incomplete.**
   The test reaches only one post-success `FACT_ALREADY_RECORDED` shape. It does not
   exercise `CASE_NOT_WORKING`, `CHECKLIST_INCOMPLETE`, `PTO_REQUIRED`, or
   `FACT_NOT_FOUND`, nor prove that each retains the original `409`, renders the
   explanation inside only the submitted form, and creates no facts. It also does
   not assert that capability/activity loss and expired session remain separate
   access results without protected card data, or that `404`, malformed transport,
   CSRF, and unknown action never become a retained-value card. Existing documentary
   tests cover portions of transport authorization/status, but do not assert these
   new presentation boundaries and are not selected as focused obligations by this
   plan.

3. **Several A1 presentation branches can pass without satisfying the contract.**
   Checking only that `id="completion"` occurs does not prove its containing panel
   is active and visible. There is no form-level/general-error case proving live or
   alert semantics and a fallback focus target when no field error exists. The
   sibling leakage assertion searches only the first retained value and does not
   test another object; therefore declaration details/reason could leak while the
   assertion passes. The correction cases do prove open `details`, field association,
   retained values, and field focus for the chosen validation errors.

4. **A4 regression ownership is stated but not made executable by the candidate.**
   The new registered test verifies successful `303` retries and two appended
   correction rows, but it does not select or invoke focused regressions for the
   object-details editor and the corrected #236 status behavior named by A4. It also
   does not independently assert root immutability/history contents after correction
   (only row count), or snapshot unrelated state against accidental controller/view
   mutation. The plan's selected focused commands contain neither named adjacent
   regression, so their coverage is deferred to an unspecified full-CI matrix.

5. **The RED evidence is valid but reaches only the first server branch.** The
   source-bound record reaches the authenticated real completion POST and fails for
   the intended product reason: current `422` is not object-card HTML. It exits at
   line 19 during `record_pto`, before declaration validation, corrections, domain
   conflict, success/history, or the source-only asset assertions. There is no RED
   evidence for the materially separate browser behavior family, so absent, broken,
   or vacuous browser setup cannot currently be distinguished from a meaningful
   behavior failure.

## What is acceptable already

The specification and OpenSpec delta agree on the seam and bounded scope. The PHP
test uses the real authenticated Yii HTTP seam, real persistence fixture, exact
`422`/`409`/`303` values, independently stated Russian messages, DOM queries for
field association/focus and correction disclosure, and before/after fact checks.
The fixture supplies an isolated database and cleanup, the dynamic future date is
timezone-explicit, `php -l` passes, and `git diff --check` is clean. The copied-vendor
RED record is a product RED rather than the earlier dependency-setup failure.

## Required changes before rereview

- Add and invoke the planned real browser verifier, exercising the connected A3
  flow including response-loss/non-HTML outcomes, exact submit counts, re-enable,
  retry, focus/tab/scroll behavior, and access loss before retry.
- Table-drive all five recognized A2 conflicts at the applicable form/state seams,
  plus the access/session and non-card transport boundaries, with no-fact and
  no-protected-content assertions.
- Cover active/visible completion panel behavior, the general-error fallback focus
  path, and leakage of every submitted field across sibling forms and another object.
- Bind the named editor and #236 regressions into focused execution (or identify
  exact existing registered owners in the verification plan), and strengthen
  correction assertions for immutable roots and exact append-only history.
- Capture source-bound RED evidence that reaches the separate browser family and
  later materially distinct server branches without an earlier assertion masking
  their setup and sensitivity.

Production implementation is not authorized by this review.

# YII2-COMPLETION-FORM-RECOVERY-001 — Gate 3 rereview v2

- Reviewer: `/root/completion_gate3` (independent; did not author specification or tests)
- Corrected test commit: `c41aed955e0e8f73959d4d0e2ab8cfda843d15a6`
- Prior immutable review: `378b28501c57439823257a3accb63c936ed38e82`
- Candidate source: `8da7ce310dc1062e45aedc1c49939bf55ffd98ad621467395e21b86a7ec43600`
- Base: `bced877aec8a8802e97037749ca4251d3098df1a`
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260922T172309Z-6e34f45459/verification-plan.json` (`CRITICAL`; Gate 3 and final review required)
- PHP test: `tests/Yii2/yii2_completion_form_recovery_001_test.php` (`git hash-object`: `ad83a5c6f0841adbca17cf5ee5a71575b6b188bf`)
- Browser test: `tests/Yii2/completion_form_recovery_browser.mjs` (`git hash-object`: `8c001a384eb84720f1e68eefb40a96adfe342801`)
- Corrected RED: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790097800245006000-2b41abbaba5b4ad09824ebc4a217ae9f.json`
- Verdict: **CHANGES_REQUESTED**

## Prior findings disposition

1. **Executable browser scenario — partially resolved, still blocking.** A real
   Playwright process is now invoked from the registered PHP acceptance test. It
   proves one in-flight POST for two immediate submits, response-loss copy and value
   preservation, re-enable, a real `422` fragment replacement, correction disclosure,
   field focus, successful navigation, and exactly one appended correction. It does
   not exercise invalid/non-HTML results, a browser-consumed `409`, explicit tab
   activation/section scrolling, or capability/activity/session loss between the
   unknown outcome and retry. Those are explicit A3/A2 browser boundaries, and the
   prior required changes named non-HTML outcomes and access loss before retry.

2. **A2 matrix — domain reachability resolved; presentation/access matrix remains
   partial.** All five named conflicts are now induced with literal independent
   messages, original statuses, and no-fact checks. However only
   `FACT_ALREADY_RECORDED` is DOM-scoped to the submitted form and checked for the
   general focus target. `CHECKLIST_INCOMPLETE`, `PTO_REQUIRED`, `FACT_NOT_FOUND`,
   and `CASE_NOT_WORKING` use body-global string assertions; several do not assert
   HTML/card content type or retained submitted values. An implementation that puts
   those errors outside the submitted form, loses its values, or returns a different
   HTML surface can pass. Capability denial is checked server-side, but the submitted
   reason itself is not checked for absence, and no browser-retained-input retry is
   attempted after authorization loss. The session assertion accepts four statuses,
   including `303`, without proving a specific separate login/session outcome.
   Malformed transport, CSRF, and unknown action still have no assertion that they
   do not become a retained-value card.

3. **A1 presentation — substantially resolved, with isolation still partial.** The
   corrected test proves a visible completion tabpanel and a form-level general-error
   focus target. The helper still checks sibling leakage using only the first retained
   value, so later declaration details/reason fields could bleed into another form.
   The `404` case proves its submitted marker is not echoed for a nonexistent object,
   but does not render a second accessible object and prove that retained state is
   absent there. This is narrower than the contract's every-field/form/object
   isolation rule.

4. **A4 adjacent regression ownership and exact history — unresolved.** Neither the
   verification plan nor the corrected test binds the named object-details editor and
   #236 status regressions into focused execution or identifies their exact registered
   owners. Correction success still checks only total correction-row count, not that
   roots remain immutable and the two exact append-only history records contain the
   expected old/new values and reasons.

5. **RED reachability — server family improved; browser family still unobserved.**
   The corrected source-bound RED is deterministic and reaches the first recognized
   domain rejection, failing because `CHECKLIST_INCOMPLETE` is still plain text rather
   than card HTML. That is a meaningful product RED and confirms the independent
   incomplete-checklist fixture. It exits at PHP line 18 before every later server
   case and before Playwright starts. There remains no source-bound RED demonstrating
   that the new browser fixture launches and fails on missing behavior rather than
   setup, selector, or orchestration defects.

## Quality checks and strengths

Both corrected test files pass their language syntax checks and the delta passes
`git diff --check`. Fixtures use private databases/artifact directories with
`finally` cleanup; the browser config is mode `0600`; the browser has a bounded
timeout. Expected statuses, messages, navigation and persistence counts are literal
contract-derived values rather than copied from production. These improvements are
material, but do not close the remaining sensitive boundaries above.

## Required changes before rereview

- Extend the connected browser journey to cover invalid/non-HTML unconfirmed
  results, `409` fragment replacement, observable tab/scroll behavior, and a denied
  retry after capability/activity/session loss, with exact POST counts and preserved
  DOM assertions.
- Apply one shared DOM/card assertion to every recognized conflict: submitted form,
  retained fields only there, general error/focus target, original `409`, and no facts.
  Tighten session expectations and prove malformed/CSRF/unknown-action responses do
  not render retained submitted data.
- Check every retained field for sibling leakage and use a second accessible object
  to prove cross-object isolation.
- Bind the named editor/#236 regression owners into the focused plan and assert exact
  immutable roots plus append-only correction contents.
- Capture a source-bound RED that reaches the Playwright family independently of the
  earlier server assertion.

Production implementation remains unauthorized by this Gate 3 rereview.

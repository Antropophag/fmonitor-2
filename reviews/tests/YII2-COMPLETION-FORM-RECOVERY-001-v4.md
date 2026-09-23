# YII2-COMPLETION-FORM-RECOVERY-001 — Gate 3 final rereview v4

- Reviewer: `/root/completion_gate3` (independent; did not author specification or tests)
- Exact test candidate: `5ecb887010c12bf613a0df52a9e8e05b55f00975`
- Prior immutable v3 review: `5e57b7375323e46ce7e048d078ed69362f61d279`
- Candidate source: `ecf177ae080e93a676b9028f71dbb3306bcb49a6fdcbf2fdd52c39d24e0ec33a`
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260922T174547Z-66e5aaee4c/verification-plan.json` (`CRITICAL`; Gate 3 and final review required)
- Server test: `tests/Yii2/yii2_completion_form_recovery_001_test.php` (`git hash-object`: `81490a308c56e6ec14d2585dbfbec63d5ad08b38`)
- Browser runner: `tests/Yii2/yii2_completion_form_recovery_browser_001_test.php` (`git hash-object`: `61a2593c31f0b750f1b1679256152760f6713b2a`)
- Browser script: `tests/Yii2/completion_form_recovery_browser.mjs` (`git hash-object`: `ce82e5d057e02d82e4685e0e594ef3a6cd964140`)
- Exact browser RED: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790099149202889000-b3ab7c9283fc472785b9a8deb1b8c17d.json`
- Verdict: **APPROVED**

## Disposition of v3 required changes

1. **Non-HTML synchronization — resolved.** The second routed POST now resolves an
   explicit promise only after the synthetic `503 text/plain` response has been
   fulfilled. The scenario then waits until the actual correction-form submit
   control is present and unlocked before beginning the `422` submission. It no
   longer treats the warning left by the preceding network-abort phase as proof that
   request two completed. The route continues observing every POST, retaining the
   exact five-request oracle and the separate quiet-period no-retry assertion.

2. **`409` correction presentation — resolved.** After the real
   `FACT_NOT_FOUND` response replaces the card, the browser independently proves the
   general error has focus, submitted declaration details and reason remain, the
   correction `details` is open, the containing readiness tab is selected, and the
   focused target is inside the viewport. These assertions no longer rely on the
   preceding `422` branch to cover status-specific rendering behavior.

## Full-contract assessment

The complete test pair remains traceable to A1–A4 and exercises the authenticated
Yii HTTP seam plus a separately registered real-browser seam. The server matrix
covers all four forms, field and general errors, all five named conflicts,
authorization/session and malformed transport boundaries, exact non-success and
success statuses, no-fact rejection, immutable roots, and action/object isolation.
Unavailable state/access forms are not reconstructed after current authorization or
domain state removes the command; this is consistent with the specification's
prohibition on using retained browser input to execute a forbidden action.

The browser matrix covers one-form in-flight exclusion, network and non-HTML unknown
results, retained values, unlock/no automatic retry, `422` and `409` card replacement,
focus/disclosure/tab/scroll behavior, confirmed navigation, exactly one appended
correction, and immutable roots. Fixtures and artifacts are private and cleaned in
`finally`; orchestration is timeout-bounded and expected values are independent.
Registered exact-source CI owners cover the adjacent object-editor and #236 stage
regressions identified by A4.

The exact RED remains valid: Chromium completes the native two-step login and reaches
the real object card, then fails specifically because pre-change production lacks the
required `data-completion-form="correct_declaration"` hook. The generic harness label
does not make this a regression or setup failure.

PHP and Node syntax checks pass, and `git diff --check` is clean. No blocking Gate 3
findings remain.

The separate executor is authorized to implement against this exact test candidate.
This approval does not approve production code, replace independent final review,
or make exact-source CI GREEN.

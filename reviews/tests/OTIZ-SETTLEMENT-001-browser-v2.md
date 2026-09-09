# OTIZ-SETTLEMENT-001 — full Yii browser Gate 3 v2

- Date: `2026-09-10`
- Reviewer: separately tasked agent `/root/settlement_review`
- Specification/test author: root
- Reviewed test correction: `e89fbde3`
- Prior review: `reviews/tests/OTIZ-SETTLEMENT-001-browser-v1.md`
- Production WIP: explicitly excluded
- Verdict: **APPROVED**

Both v1 findings are closed. The fixture now stores a representative
fund/progress/pool calculation trace, one worker allocation with KTU1.00 and
amount100000, and one open warning. Native Chromium opens the trace and requires
its labels/value, allocation identity/KTU, and issue message/owner. Final DB
comparisons require snapshot, object calculation, allocation and issue rows to
remain unchanged.

Before valid commands, Chromium submits an over-budget discipline200000 through
the actual rendered form and requires the retained error with no ledger row. It
then writes a private readiness file and waits. The parent observes that exact
checkpoint, independently queries closure/event/receipt counts as `[0,0,0]`, and
only then releases the browser. The bounded rendezvous prevents later successful
facts from masking an invalid-command write and adds no production hook.

All retained v1 properties remain: protected-login return, native rendered form
clicks and distinct UUID/CSRF values, DML-only runtime, exact
100000/10000/90000/-10000 money oracle, linked reversal, three receipts, four
events, page-error/HTTP-failure checks, deadlines and cleanup. Numeric grouping
accepts ordinary or nonbreaking whitespace without weakening numeric values.

Fresh RED remains the earlier missing login return: Chromium reaches Yii login
but returns to `/pilot/objects` instead of the requested snapshot, browser exit1
and parent exit255. The correction is downstream and reviewed in source. The
saved evidence reports post-commit plan `CHANGE_VERIFICATION_OK`. A later plan
check in the shared implementation WIP is stale and is not evidence against the
frozen test-only candidate.

Reviewed corrected identities:

```text
c3b1cbe16beb4eb552fb607ccdb85f140d02ed061ab40b023903f950b39eb1ee  tests/Yii2/yii2_otiz_settlement_browser_001_test.php
f355c328b387186224aa76dbb46913014ba81c461a68270c42cbf28e20191856  tests/Yii2/otiz_settlement_browser.mjs
0b21ab0937bd364c9e5ae27e7cf731b6d6fc3a405c2a344c41bb208efc8a2bcd  docs/operations/otiz-settlement-browser-red-2026-09-10.md
```

Full browser Gate 3 is **APPROVED**. Minimal production correction may make the
approved journey GREEN without changing expectations. Compatibility ownership,
old-writer deletion, broader regressions, final Gate 5 and one exact-source full
CI remain separate delivery obligations.

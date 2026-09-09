# OTIZ-SETTLEMENT-001 — Yii HTTP commands Gate 3 v2

- Date: `2026-09-09`
- Reviewer: separately tasked agent `/root/settlement_review`
- Reviewed exact candidate: `a3b42b55dfa4f4480ab6cc3cc978fa019bfcc9ad`
- Prior review: `reviews/tests/OTIZ-SETTLEMENT-001-http-v1.md`
- Verdict: **CHANGES_REQUESTED**

The correction closes the v1 admission and discipline-parser findings. It now
uses real anonymous/authenticated Yii requests, verifies login redirects, CSRF
denial, current byte-exact permission denial, malformed discipline fields with
no facts, exact discipline closure/receipt fields, ignored paid/deadline
injection, and redirect-back GET success. The first protected GET produces an
honest missing-route RED: expected `303 /pilot/login`, actual `404`, exit `255`.
The exact-candidate plan check returns `CHANGE_VERIFICATION_OK`.

Two blocking sensitivity gaps remain:

1. Successful complete and reverse requests assert only redirects. A stub route
   can emit `?paid=1` or `?reversed=1` without calling the canonical owner and
   pass. Assert the exact paid closure, completion event and receipt after
   complete; then the exact linked negative reversal components, reversal event
   and receipt after reverse.
2. The malformed complete and reverse requests are not followed immediately by
   closure/event/receipt equality checks. Require unchanged facts for each
   refusal, as already done for discipline, CSRF, guest and permission denial.

Also cover a nonpositive/malformed path target for complete and reverse
(`snapshotId`/`closureId`), or bind this acceptance to an existing exact
route-parser test that proves those paths cannot reach the owner. The current
matrix checks invalid operation UUID and empty reverse basis but not route target
parsing.

Guest/current-permission checks are acceptable as representative shared Yii
admission checks for this POST increment, subject to Gate 5 inspection of every
route. General authenticated GET page fidelity and the full stage 3.1
browser/read-return flow remain outside this approval and still open.

Reviewed identities:

```text
85fd6470aafe18150b34251a939cdfb50f82e1a6d63808a47fd11533f044152a  tests/Yii2/yii2_otiz_settlement_001_test.php
1f8022ab172cf546ee3149b259d8f8e5c466312600267e3da482b4e99d1c106f  docs/operations/otiz-settlement-red-evidence-2026-09-09.md
```

Gate 3 remains **CHANGES_REQUESTED**. HTTP production wiring is not authorized
until exact owner delegation and refusal no-facts sensitivity are added and
independently rereviewed.

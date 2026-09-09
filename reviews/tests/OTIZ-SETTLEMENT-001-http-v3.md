# OTIZ-SETTLEMENT-001 — Yii HTTP commands Gate 3 v3

- Date: `2026-09-09`
- Reviewer: separately tasked agent `/root/settlement_review`
- Reviewed exact candidate: `a497220081788dde8d1abc471ed2cbe6188dd262`
- Prior review: `reviews/tests/OTIZ-SETTLEMENT-001-http-v2.md`
- Scope: three POST command adapters and their shared admission/parsing boundary
- Verdict: **APPROVED**

All v2 findings are closed. The complete adapter now requires the exact remaining
paid closure `90000`, zero discipline/deadline, fixed basis, operation-specific
`completed` receipt/result, and canonical completion events. The reverse adapter
requires the exact linked negative discipline `-10000`, zero other components,
new basis, empty artifact, operation-specific `reversed` receipt/result, and the
reversal event. These assertions prevent redirect-only stubs from passing.

Invalid complete and reverse calls now compare closure/event/receipt count
triplets immediately before and after every refusal. Nonpositive snapshot and
closure path targets are included alongside invalid UUID and empty basis.
Retained coverage also proves guest/session handling, Yii CSRF, byte-exact
current permission, discipline parsing, exact closure/receipt translation, and
ignored arbitrary paid/deadline fields.

The saved RED remains valid at the earliest missing public boundary: anonymous
snapshot GET expects `303 /pilot/login` and receives `404`, exit `255`. The
downstream matrix is statically complete and is not falsely claimed as reached
in RED. Exact-candidate plan validation returned `CHANGE_VERIFICATION_OK`, and
`git diff --check b5f816ba..a4972200` passed.

Reviewed identities:

```text
8fe7bd9de047d1c8354af03da76edd6b58e256334a9150641ab1050a6e7dfd65  tests/Yii2/yii2_otiz_settlement_001_test.php
1f8022ab172cf546ee3149b259d8f8e5c466312600267e3da482b4e99d1c106f  docs/operations/otiz-settlement-red-evidence-2026-09-09.md
```

Gate 3 is approved for this bounded three-POST-command increment. Minimal Yii
HTTP wiring may proceed without changing approved expectations. This does not
approve the complete stage 3.1 authenticated GET/browser/read-return flow,
compatibility adapter or old-writer removal, final Gate 5, or full CI.

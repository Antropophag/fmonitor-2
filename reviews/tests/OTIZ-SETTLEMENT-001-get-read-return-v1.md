# OTIZ-SETTLEMENT-001 — GET/read-return Gate 3 v1

- Date: `2026-09-09`
- Reviewer: separately tasked agent `/root/settlement_review`
- Reviewed preimplementation candidate: `a497220081788dde8d1abc471ed2cbe6188dd262`
- Artifact: `tests/Yii2/yii2_otiz_settlement_001_test.php`
- Scope: snapshot GET admission, command-form read, and redirect return
- Verdict: **CHANGES_REQUESTED**

The review uses only the committed preimplementation bytes. Later uncommitted
GET implementation was explicitly excluded.

The guest assertion is a valid public-boundary RED: anonymous
`GET /pilot/otiz/snapshots/301` expects `303 /pilot/login` and receives `404`
with no Location, exit `255`. The authenticated test also requires status 200,
the retained closure and complete action strings, an `operationId` token, and a
200 GET after the successful discipline redirect.

That oracle is insufficient for OpenSpec task 3.1, which requires unchanged
UI/browser flow, and for the design's explicit UI non-goal. A new simplified or
nearly blank page containing the two action strings and `operationId` can pass
while discarding the working legacy snapshot page. Gate 4 minimality would then
authorize precisely that incompatible result.

Before GET wiring approval, add bounded semantic legacy-parity assertions for:

- snapshot identity/status and the displayed object/money facts needed to make
  the payment decision;
- closure history and the reverse action after a closure exists;
- export/navigation and the retained success flash after redirect return;
- exact required closure/complete/reverse form actions and fields, including
  CSRF and operation IDs;
- preservation of the controls/content relied on by the existing browser flow,
  with negatives that catch a stripped compatibility page.

This need not freeze irrelevant whitespace or duplicate the later full browser
journey. It must make a materially simplified page fail at the raw-HTTP seam.
After corrected test-only bytes and fresh RED evidence, request rereview. The
full stage 3.1 browser verification remains a later obligation even if this raw
GET increment is approved.

Gate 3 for GET/read-return remains **CHANGES_REQUESTED**. The separate three-POST
Gate 3 approval at `reviews/tests/OTIZ-SETTLEMENT-001-http-v3.md` is unaffected.

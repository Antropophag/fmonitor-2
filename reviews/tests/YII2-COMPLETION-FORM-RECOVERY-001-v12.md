# YII2-COMPLETION-FORM-RECOVERY-001 — Gate 1/Gate 3 rereview v12

- Reviewer: `/root/completion_gate3` (independent; did not author specification or tests)
- Exact response commit: `48ffdbb4a504481194c48c7480ce77b289f21701`
- Prior v11 review: `d059af4bbe4f944ed72d9bb2cbbdddf230f6f3c4`
- Corrected OpenSpec design: `openspec/changes/recover-yii-completion-form-errors/design.md` (`git hash-object`: `a0f2bbd76e85f2f03852527de45f03ddaaa0d8e2`)
- Corrected server test: `tests/Yii2/yii2_completion_form_recovery_001_test.php` (`git hash-object`: `ff3161b5c93a606432bfbf90f241a1644108c77b`)
- Verdict: **CHANGES_REQUESTED**

## v11 disposition

The OpenSpec design contradiction is resolved. It now matches the normative
conditional-retention rule: available commands receive form-local retained state;
unavailable state conflicts receive an accessible completion-section alert without
recreating the form; access/session and malformed/infrastructure failures remain
separate fail-closed outcomes.

The `FACT_ALREADY_RECORDED` test also now requires the original `409`, HTML card,
visible exact explanation, no facts, absence of the unavailable
`record_declaration` form, and one focusable section alert. This correctly removes
the prior opposite form-retention oracle.

## Remaining finding

1. **The submitted marker is no longer required, but is not prohibited from leaking
   elsewhere in the card.** The v11 required change explicitly called for absence of
   both the unavailable record form and the submitted marker. The corrected test
   asserts only that no `record_declaration` form exists. An implementation could
   echo `Д-CONFLICT` in the section alert, another form, hidden markup, or elsewhere
   on the refreshed card and still pass. That conflicts with the corrected design's
   “only the section alert from refreshed facts” rule and leaves the owner requirement
   against showing unavailable retained data without an executable oracle.

## Required change before reapproval

Add an assertion that the `FACT_ALREADY_RECORDED` response body does not contain the
unique submitted marker `Д-CONFLICT`, while retaining the exact status/message,
no-form, focusable section-alert, and no-fact assertions.

The exact response commit changes only OpenSpec design and the server test; PHP lint
and `git diff --check` pass. No production change was reviewed. Gate 1/Gate 3 remains
closed pending the single leakage assertion above.

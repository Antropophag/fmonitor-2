# OTIZ-SETTLEMENT-001 — GET/read-return Gate 3 v4

- Date: `2026-09-10`
- Reviewer: separately tasked agent `/root/settlement_review`
- Test author: root, under the current session authorship rule
- Reviewed test-only candidate: `a7f3a83dc0d35f539389a035bf82cd6cc871aa6b`
- Prior review: `reviews/tests/OTIZ-SETTLEMENT-001-get-read-return-v3.md`
- Verdict: **APPROVED**

All v3 findings are closed. `yosForm()` parses the document, requires exactly one
form for the exact action and asserts POST. Each exact closure, complete and
reverse form must own one enabled hidden nonempty CSRF control and one enabled
hidden canonical lowercase v4 operation UUID. Complete and reverse requests use
the CSRF extracted from their own rendered forms; discipline already uses its
own form token. A token elsewhere cannot satisfy these assertions.

The non-legacy visible artifact expectation is removed. Exact persisted artifact
verification remains in the command assertion. The retained read-return oracle
still requires the success flash, closure object/amount/basis and exact
closure-ID reverse form.

Evidence records the authorship/date transition and an intended WIP sensitivity
RED at the missing retained heading, exit `255`. The prototype is not reviewed
or represented as test-first source. The downstream form assertions are
reviewed statically. The regenerated plan check returned
`CHANGE_VERIFICATION_OK`; its actual-path snapshot includes the intended
uncommitted implementation boundaries and is not used as evidence that those
production bytes are approved.

Reviewed committed identities:

```text
260b87caa0b1109028f90dfdca48a37aa92518bb0105a7729025aa78ade5cfa4  tests/Yii2/yii2_otiz_settlement_001_test.php
651f8abcdd78b5817c0e5b39ab08421af4f3aa412d5724872e48265bac61b0ab  docs/operations/otiz-settlement-red-evidence-2026-09-09.md
```

Gate 3 is approved for this bounded raw-HTTP GET/read-return increment. Minimal
retained UI implementation may proceed without changing these expectations.
Original trace/allocation/issues parity, real browser login-submit-return,
rapid compatibility ownership/old-writer removal, focused regressions, final
Gate 5 and exact-source full CI remain separate delivery obligations.

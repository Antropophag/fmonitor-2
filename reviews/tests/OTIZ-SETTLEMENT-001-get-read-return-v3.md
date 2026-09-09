# OTIZ-SETTLEMENT-001 — GET/read-return Gate 3 v3

- Date: `2026-09-10`
- Reviewer: separately tasked agent `/root/settlement_review`
- Historical test author: executor `/root/settlement`
- Reviewed test-only candidate: `7b8d03484c97b4abf87c13a40898f4ed12522e6e`
- Prior review: `reviews/tests/OTIZ-SETTLEMENT-001-get-read-return-v2.md`
- Verdict: **CHANGES_REQUESTED**

The correction closes the prior global-field and empty read-return findings in
part. Forms are now bounded by exact action. After discipline redirect, the test
requires the success flash, object, amount and basis, then locates the reverse
form by the exact persisted closure ID. RED chronology remains honest and the
uncommitted prototype is not reviewed.

The complete remaining findings for this increment are:

1. `yosForm()` scopes only by action and does not assert `method="post"`.
   A GET form can pass even though the retained browser command contract is POST.
2. Each form checks field names but not usable values. Require a nonempty valid
   server CSRF value and canonical lowercase UUID operation ID within each exact
   closure, complete and reverse form. Otherwise direct scripted POSTs may pass
   while the rendered browser forms cannot submit successfully.
3. The redirect-page expectation requires visible `artifact-1`, but the retained
   legacy closure renderer at `rapid-pilot/Otiz.php:485-486` displays closure
   components and basis, not artifact. The design declares UI changes a non-goal.
   Remove this visible-copy expectation or obtain a normative UI decision before
   requiring new presentation behavior.

The flash, closure amount/basis, exact reverse action and scoped control-name
assertions should remain. Per the current session authorship rule, future test
corrections are authored by root; historical authorship above is not rewritten.

Reviewed identities:

```text
404a4aa14e2346bc7ec2bfbb250e2074ebfa072ea7844b0491155490f6020096  tests/Yii2/yii2_otiz_settlement_001_test.php
ab4acf259b32c03191768e6306fb66af05a65c0ff3fe852659c81d5c3ef142e7  docs/operations/otiz-settlement-red-evidence-2026-09-09.md
```

GET/read-return Gate 3 remains **CHANGES_REQUESTED**. Core and POST approvals
remain intact; full parity/browser/removal/final Gate 5/CI obligations remain
separate.

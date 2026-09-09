# OTIZ-SETTLEMENT-001 — consolidated final-correction Gate 3

- Date: `2026-09-10`
- Reviewer: separately tasked agent `/root/final_review`
- Test/specification author: root coordinator
- Reviewed test candidate: `4b7d551f93b3df98b8c684d69e064c8c733fc4ff`
- Production baseline: `906f1d25935cd207bbcd6c8c03b8d9858a0069b2`
- Verdict: **APPROVED**

## Complete findings

No findings.

The specification amendment is bounded to the retained settlement surface. It
requires caller-owned operation UUIDs on all three rapid forms, working read-only
Yii financial navigation/export, the existing reverse-error destination, and the
historical calculation/ledger interpretations already supplied by the rapid
parity oracle. It explicitly leaves publication/calculate/accept ownership out of
this increment and introduces no alternate formula or financial writer.

The tests are sensitive at public seams. The packaged-runtime test extracts the
three actual rendered UUIDs, proves they are distinct and canonical, and submits
both missing and malformed IDs to every retained settlement route while checking
that closures, events, and receipts remain unchanged. The browser test clicks all
three rendered financial links, returns through real snapshot links, downloads a
real XLSX, and inspects its archive contents. The Yii HTTP test follows the
existing reverse-error redirect and uses a separate historical fixture to prove
85% basis-point formatting, both admission/exclusion notes, legacy KTU 1.00,
closure date, and exact 70000/20000/10000 components without writes.

The recorded RED executions reached valid schema, authentication, packaging, and
browser setup before failing at the intended absent behavior: reverse-error GET
404, financial-navigation GET 404, and missing retained form `operationId`.
Evidence: `docs/operations/otiz-final-corrections-red-2026-09-10.md`.

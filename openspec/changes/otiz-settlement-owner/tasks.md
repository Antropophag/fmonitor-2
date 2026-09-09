## 1. Contract and RED

- [x] 1.1 Independently review OTIZ-SETTLEMENT-001, A02 examples, financial object basis and exact rejection mapping; record Gate1/Gate3 evidence.
- [x] 1.2 Add public-seam RED for 100000→150000 cross-snapshot budget, replay/conflict, concurrent overspend, rollback and reversal uniqueness; prove current rapid adapter fails for missing behavior.

## 2. Persistence owner

- [x] 2.1 Add canonical migration for object lock/operation receipts and verify clean/predecessor/repeat/conflict without changing closure history.
- [x] 2.2 Implement one Yii DB transaction owner for settle/reverse and make focused DB tests GREEN under DML-only runtime credentials.

## 3. Adapters and removal

- [ ] 3.1 Wire Yii HTTP and temporary rapid compatibility routes to the application seam; verify authorization, CSRF, exact redirects/errors and unchanged UI/browser flow.
- [x] 3.2 Delete replaced closure/payment/reverse SQL, transaction and event helpers from rapid-pilot and pass architecture checks without baseline expansion.

## 4. Delivery

- [x] 4.1 Run focused OTIZ calculation/publication/register regressions and verify formulas/#66 outputs are unchanged.
- [ ] 4.2 Obtain independent Gate5, run one exact-source full CI, merge with rollback notes, and keep #76 open for remaining migration slices.


## Evidence

Фактические результаты и ссылки на независимые reviews собраны в
`docs/operations/otiz-settlement-delivery-2026-09-10.md`.
Пункт3.1 закрывается после GREEN объединённой финальной коррекции;
пункт4.2 — только после финального review, exact-source CI и merge.
#76 остаётся открытым для остальных частей перехода на Yii2.

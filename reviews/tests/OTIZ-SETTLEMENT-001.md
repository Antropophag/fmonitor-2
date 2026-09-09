# Independent test review: OTIZ-SETTLEMENT-001 initial money increment

- Reviewer: root agent; not the test author.
- Test author: /root/foundation_tests, gpt-5.6-sol/low.
- Reviewed commit: 6eed2485.
- Public seam: OtizSettlement recordDiscipline/completeSnapshotPayments/reverse,
  explicit deployment OtizSettlementSchemaMigration::apply(Yii Connection,prefix).
- RED: `php tests/Otiz/settlement_owner_001_test.php`, exit255 after real Yii
  bootstrap and isolated canonical-v23 DB setup; missing public ledger migration.
- Verdict: `APPROVED` for the initial deterministic monetary increment.

## Findings

Expected amounts100000/150000/50000 are independent literal examples of existing
accrued-minus-global-closures rule and ADR0002 object-level finance ownership.
The test has downstream real operations, not only missing class assertions: A02,
S2 built after prior closure, exact replay, changed-fingerprint conflict, unauthorized
actor denial, no-change replay after reversal releases budget, new operation consuming
that budget, over-budget discipline, mixed historical-component reversal, and
immutable accepted rows. No runtime auto-DDL or real stand is used.

No-change replay after a reversal is particularly significant: merely recomputing a
zero budget without a receipt would pay again and fail this expectation. Yii and
mysqli are used in separate fixture/application connections, not mixed inside one
production transaction. The owning production transaction must use one Yii connection.

## Required followup before delivery

Process-level concurrent complete/reversal/discipline and rollback after late failure
remain mandatory separate RED/reviews. Initial GREEN is not #70/#76 completion.
Fixture chronology cleanup: make the built-after snapshot report/acceptance timestamps
no later than the supplied2026-10-02 clock; monetary expected values remain unchanged.
This is fixture correctness, not a change to approved money behavior.

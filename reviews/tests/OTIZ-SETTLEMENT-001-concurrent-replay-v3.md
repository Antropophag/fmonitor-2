# OTIZ-SETTLEMENT-001 — concurrent exact reversal replay Gate 3 v3

- Date: `2026-09-09`
- Reviewer: separately tasked agent `/root/settlement_review`
- Test author: executor `/root/settlement`; reviewer authored neither test nor production
- Reviewed exact candidate: `0b802126fcd420bad74bf3c9bca4d6f48037940f`
- Correction baseline: `2b0ca215cb685bb1c0e1162f3bc5e8b343bd1b60`
- Public seam: `FMonitor2\Otiz\OtizSettlement::reverse()` from two worker processes
- Verdict: **APPROVED**

The v2 blocking finding is closed. The coordinator holds a write table lock on
the operation-receipt table before releasing either worker. It queries
`information_schema.PROCESSLIST` until two connections in the disposable test
database are visibly blocked on receipt-table queries, then releases the lock.
A five-second failure to observe both becomes an explicit `SETUP_FAILURE`.
Therefore both commands have entered the initial missing-receipt lookup before
either can proceed; scheduler timing cannot turn the case into sequential replay.

The retained expectations are independently derived: same actor, same UUID and
same fingerprint return the identical successful reversal result twice and
append exactly one linked reversal. The nearby distinct actor/UUID case still
requires one success and `ALREADY_REVERSED`, preserving the semantic distinction.

Author evidence records exit `255` at the intended `[true,true]` assertion with
actual `[true,false]` (and the prior opposite scheduling order). The plan check
was independently rerun on the frozen candidate and returned literal
`CHANGE_VERIFICATION_OK`. `git diff --check` for the correction passed.

Reviewed identities:

```text
dc65f5c43fb7ce14b013599e001c3ade9bdaa8652a16a2f025def83955d85838  tests/Otiz/settlement_concurrency_001_test.php
d324f88ccb5d29340ab43d3b632c247db89b15f690b1a40c9a3f79d474dfef1f  docs/operations/otiz-settlement-red-evidence-2026-09-09.md
```

Gate 3 for this supplemental concurrent exact-replay increment is approved.
Gate 4 may implement only the minimal production correction without changing
the approved expectations. Focused GREEN/regression evidence, independent Gate
5, and one exact-source full CI remain required.

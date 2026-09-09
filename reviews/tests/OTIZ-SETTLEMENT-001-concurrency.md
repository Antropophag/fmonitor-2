# Independent test review: OTIZ-SETTLEMENT-001 concurrency and rollback

- Reviewer: root agent, not author.
- Test author: /root/auth_plan, gpt-5.6-sol/low.
- Reviewed commit: 5d5aa205.
- Public seam: OtizSettlement operations in independent PHP processes/Yii connections.
- RED: `php tests/Otiz/settlement_concurrency_001_test.php`, exit255;
  expected two valid public outcomes, actual true/false from infrastructure deadlock.
  Root repeated after valid canonical-v23 and explicit ledger migration setup.
- Verdict: `APPROVED`.

## Findings

Two distinct actors and distinct roles prevent a shared authorization-row lock from
masking a missing financial-object lock. A test-only DB insert delay forces useful
overlap; the acceptable outcomes derive independently from100000/150000 ceilings,
not scheduler order. The final global signed total is150000. Reversal race requires
one exact negative record and stable refusal/replay. Second-object trigger failure
proves partial closures/events/receipt all roll back and the same opId remains usable.
Case-sensitive permission and caller-owned transaction rejection preserve existing
canonical authorization and public transaction ownership guarantees.

Harness review corrected unbounded reads and unregistered worker cleanup: output
collection is nonblocking with15-second deadline and all processes tracked for
cleanup. No production failure flag or application-private callback is introduced.
Only the unique test DB is created/dropped. Tests remain independently authored
from financial production code.

## Required changes

None in this supplemental test. Production must resolve the demonstrated deadlock,
ensure reads observe the financial ledger after lock acquisition, preserve exact
permissions, and reject nesting in an already active caller transaction. Full
HTTP wiring, old-writer removal and final CI are still separate delivery obligations.

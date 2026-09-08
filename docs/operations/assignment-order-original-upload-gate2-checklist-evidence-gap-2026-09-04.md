# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — Gate 2 checklist evidence gap

Date: `2026-09-04`

RED author: separately tasked agent `/root/assignment_original_red`

Outcome: **GATE 1 AMENDMENT REQUIRED FOR REMAINING TASK 2.2**

The worker safe-log identity approved at `12cde955` is constructible. The next
MariaDB evidence step exposes a contradictory closed observation contract.

Section 13 requires every rejection/conflict/failure verifier to snapshot
checklist availability together with order/composition, case/opening, tasks,
events, audit and decoy facts. The independently approved task-2.1 test also
contains a sensitivity-proven checklist family. However section 16 defines
`unchangedProcess` as an exact top-level JSON shape with no additional keys:

```text
{schema,orderCompositionSha256,caseSha256,openingSha256,tasksSha256,decoySha256}
```

There is no `checklistSha256`. The approved production evidence reader returns
only that closed shape and MUST reject/avoid additional fields. Therefore a
fresh-connection MariaDB verifier cannot prove checklist availability unchanged
without either querying private/downstream tables itself or silently treating
tasks as checklist evidence. Both would violate the independent-reader and exact
shape contracts.

The smallest amendment adds exact `checklistSha256` to the closed
`aoou-process-v1` shape and states what canonical checklist-availability facts
it covers. If checklist availability is deliberately derived from another
listed digest, that derivation and its complete input set must instead be made
normative and sensitivity-testable.

No production, test, OpenSpec or review file was edited. Part 1 `b0f1e60`
remains valid. Full task 2.2 cannot honestly satisfy the mandatory no-downstream-
mutation matrix through the approved evidence reader until this contradiction
is resolved.

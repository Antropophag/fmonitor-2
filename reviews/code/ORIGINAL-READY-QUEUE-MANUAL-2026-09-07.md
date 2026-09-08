# Original-ready queue reconciliation — independent code review

Reviewer: `/root/photo_review`; implementation author: `/root`.
Verdict: **APPROVED**.

## Standards

No finding. This is a bounded read-model correction in `MariaDbObjectQueue`; it
creates no facts and adds no runtime DDL. Prefix handling remains explicit. The same
joined current-selection/current-original basis feeds total count, filters and row
projection, limiting drift between pagination and visible status.

## Behavior

No finding. Native readiness requires the latest selection's exact order,
composition identity/hash and current original revision. A prior root or application
cannot authorize a new pending composition. The application/registered fallback is
retained only when no native selection lineage exists. Both
`needs_assignment_order` and `assignment_order_prepared` states support the current
flow, whose selection/upload commands do not rewrite process state. The supplemental
fix makes a stale prior application irrelevant when labeling a newer pending native
selection, matching the SQL filter/count basis.

Exact reviewed production artifact:

```text
c04c4f3fae75c4853819a14804f00326b681f642ff150b3c1e5dd0fc9e6e723f  app/PilotHttp/MariaDbObjectQueue.php
```

Independent focused test, PHP lint and diff-check PASS. Author's calendar regression
PASS is recorded at SHA-256
`498038cae97bcb89cfb4afe44d15b1d4fd0b95246d28eb8a2b6a15ed7cf7bf23`.
This approval covers the patch only; deployment and full verification remain separate.

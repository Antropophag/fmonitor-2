# PREMIUM-SNAPSHOT-DETERMINISM-001 — planning rereview

Date: 2026-09-01  
Reviewer: fresh independent agent `premium_determinism_planning_rereview_0901i`  
Change: `characterize-premium-snapshot-determinism`  
Prior review: `docs/operations/premium-snapshot-determinism-planning-review.md`  
Verdict: **READY_FOR_GATE1_DRAFT**

## Scope reviewed

The rereview checked the corrected proposal, delta specification, design and
tasks against the prior planning findings, approved evidence package and its
rereview, `PremiumCalculation.php`, the current focused verifier, product/pilot
truth and `docs/development-process.md`. No planning artifact or code was edited
by this review.

## Closure of prior findings

1. **Aggregate-overflow claim — closed.** The delta spec now keeps unbounded
   payment aggregation as a documented source risk outside the executable
   matrix. The design records the exact 64-bit scale: with the per-row limit of
   `1_000_000_000_000`, first positive overflow requires at least 9,223,373
   rows. It explicitly rejects resource-amplifying execution and assigns any
   target count/aggregate guard and error contract to a separate hardening
   decision. Tasks 1.1 and 3.1–3.3 consistently require a non-executable risk
   statement and forbid unbounded allocations/processes. No artifact retains
   the false small-row-count or promised overflow-outcome claim.
2. **Gate 1 independent review — closed.** Task 1.2 requires a fresh independent
   Gate 1 review, correction of findings and a different fresh rereviewer after
   any correction, ending in a durable `READY_FOR_OWNER_REVIEW` verdict. Task
   1.3 places explicit owner approval after that verdict; task 2.1 starts RED
   only afterward. The package does not authorize approval, RED or
   implementation itself.

## Coherence result

- All four required OpenSpec artifacts are present and mutually consistent.
- The public oracle remains the sole `PremiumCalculation::calculate()` method;
  HTTP, DB, actor, clock, filesystem, persistence and snapshot acceptance stay
  outside the slice.
- Literal v1 coefficients, reasons, permissive dates/shapes and signed-row
  behavior remain strictly `PILOT_ONLY`. Only reproducibility from dated sourced
  operands is carried as `PRODUCT_ACCEPTED`; target financial semantics remain
  under GRILL-001.
- Gate 1 owns literal independently worked expectations. Subsequent tasks retain
  the required executable-spec approval, intended RED, fresh independent test
  review, minimal GREEN, regression/architecture checks and fresh independent
  code review ordering.
- Ownership is verification-only. `PremiumDecisions` remains a future candidate,
  and no production dependency, persistence owner, runtime DDL or new
  rapid-pilot domain logic is introduced.

## Verification evidence

- `openspec validate characterize-premium-snapshot-determinism --strict` — PASS
- `git diff --check` — PASS
- `make architecture-check` — PASS (`ARCHITECTURE CHECK PASSED (6 rules)`)

## Exit condition

The planning package is ready for drafting the exact Gate 1 executable
specification. This verdict authorizes Gate 1 drafting only. A fresh independent
Gate 1 review, any required correction plus different fresh rereview, and
explicit owner approval are still required before RED; implementation remains
unauthorized.

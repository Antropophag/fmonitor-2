# PREMIUM-SNAPSHOT-DETERMINISM-001 — planning review

Date: 2026-09-01  
Reviewer: fresh independent agent `premium_determinism_planning_review_0901h`  
Change: `characterize-premium-snapshot-determinism`  
Verdict: **CHANGES_REQUESTED**

## Scope reviewed

The review checked `proposal.md`, the delta specification, `design.md` and
`tasks.md` against the approved evidence package and both evidence reviews,
`PremiumCalculation.php`, the current focused verifier, product/pilot truth and
the mandatory delivery gates. No planning artifact or production/verifier code
was edited by this review.

## Blocking findings

1. **The proposed aggregate-overflow execution is not bounded as described.**
   `design.md` requires a “literal small row count near integer max”, but every
   accepted payment row is bounded to `abs(amountCents) <= 1_000_000_000_000`.
   On the required 64-bit runtime, reaching `PHP_INT_MAX` therefore requires at
   least 9,223,373 positive maximum rows. Because `calculate()` accepts a PHP
   array rather than an iterator, constructing that input is substantial memory
   amplification, directly contradicting the stated small/bounded design and
   its risk mitigation. Revise the design, delta requirement and task 3.1/3.3
   so Gate 1 chooses an honestly bounded, deterministic observation. Acceptable
   planning may characterize the mathematically reachable unchecked hazard by
   source/runtime preflight without executing it, or specify a genuinely
   resource-bounded child-process experiment with explicit row bound, memory
   limit, timeout, expected setup classification and no claim that it reaches
   overflow when it does not. It must not promise current return/Throwable
   behavior at an unreachable “small row count”.

2. **Gate 1 lacks an explicit independent specification review before owner
   approval and RED.** `tasks.md` moves from authoring the executable spec in
   1.1 directly to owner approval in 1.2. The operating workflow for this slice
   requires a fresh independent Gate 1 review, correction, and fresh rereview
   when findings are raised, with a durable `READY_FOR_OWNER_REVIEW` verdict.
   Add these steps before explicit owner approval and preserve the rule that no
   RED starts before that approval.

## Confirmed planning qualities

- The source oracle and current seam are accurate: the characterization calls
  the sole public `PremiumCalculation::calculate()` method and does not use
  private helpers, HTTP, DB, clock, filesystem or persistence side channels.
- The package does not make `PremiumDecisions` an implemented or approved seam;
  it remains an explicitly named future ownership candidate. No new domain
  logic is assigned to `rapid-pilot`.
- The five formula steps, comparison-date rule, validation shapes, payment
  separation, exclusion normalization, reason ordering, permissive dates and
  noncanonical echo behavior agree with the reviewed implementation/evidence.
- `PILOT_ONLY` formula coefficients, reason vocabulary, signed-row behavior and
  permissive input quirks are separated from the `PRODUCT_ACCEPTED`
  reproducibility requirement. Target financial semantics, authorization,
  persistence/hash, allocation, acceptance and payment effects stay excluded
  and under GRILL-001 where applicable.
- Expected values are assigned to literal, hand-worked Gate 1 examples rather
  than production constants or a mirrored formula. Gates 2–5 otherwise retain
  intended RED, independent test review, minimal GREEN, regression/architecture
  checks and fresh independent code review ordering.

## Exit condition

Correct both blocking findings, run strict validation and obtain a new fresh
independent planning review. `READY_FOR_GATE1_DRAFT` is not granted by this
record. This review authorizes neither Gate 1 approval, RED nor implementation.

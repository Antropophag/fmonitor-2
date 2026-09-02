# CHARACTERIZE-PREMIUM-SNAPSHOT-DETERMINISM-001 — Gate 1 rereview v3

Date: 2026-09-01  
Reviewer: fresh independent agent `premium_determinism_gate1_rereview_0901m`  
Artifact: `specs/CHARACTERIZE-PREMIUM-SNAPSHOT-DETERMINISM-001.md`  
Prior reviews: `docs/operations/premium-snapshot-determinism-gate1-review.md`,
`docs/operations/premium-snapshot-determinism-gate1-rereview.md`,
`docs/operations/premium-snapshot-determinism-gate1-rereview-v2.md`  
Verdict: **CHANGES_REQUESTED**

## Scope reviewed

The current Gate 1 draft was checked independently against all prior Gate 1
reviews, the source-evidence package, strict-valid OpenSpec planning and the
public `PremiumCalculation::calculate` implementation. The review mapped every
claimed execution/mutation to the finite `CASE_ID` grammar and reread the full
arithmetic, validation, transcript, isolation and `PILOT_ONLY` contract. No
specification, production code or test code was edited.

## Closed prior findings

### Zero boundary — closed

The zero case retains the primary late dates, fixes Kss at `9000`, and requires
the exact ordered reasons `DEADLINE_PENALTY,NO_NEW_AMOUNT`. That matches the
public calculator.

### Failure outcome rendering — closed

The current regression line contains one `outcome=` field and no longer has the
earlier `expected=expected=` / `actual=actual=` double-prefix ambiguity. Its
four values are mutually exclusive at the harness boundary:
`return_mismatch`, `unexpected_invalid_argument`, `unexpected_throwable`, and
`missing_result`. `missing_result` is explicitly the unchanged result-sentinel
condition after a bounded call completes without a captured return or
Throwable. Exception copy remains stderr-only, so the normalized stdout line
is single-line and stable.

## Blocking finding

### The `CASE_ID` catalogue is still not bijective with every claimed mutation

The refined grammar fixed the blank/short/lower/long exclusion-code collision
and removed impossible numeric range IDs from date operands, but the prose
still claims distinct executions that map to one ID or have no exact ID/input:

- each numeric operand rejects both a numeric string and a float, while both
  would be `operand.<name>.value_type`; these are two claimed one-axis
  mutations but one transcript identity;
- required date values reject both an impossible date and a non-`Y-m-d` value,
  while both would be `operand.<date-name>.value_date`;
- the accepted surrounding-whitespace provenance contrast does not state the
  exact member/token mutation, and the only apparent ID,
  `contrast.blank_source`, contradicts the acceptance (a blank source is one of
  the rejected operand/payment/exclusion cases);
- the accepted extra-field/key-order contrast does not fix the exact extra
  field names, values and insertion positions for operand/source/payment input,
  so `contrast.extras` is not a bijection to one reproducible mutation despite
  the requirement to preserve key order.

Consequently a RED author can execute materially different matrices and emit
the same normalized `CASE_ID`, or choose different accepted whitespace/extras
fixtures, while claiming conformance. This fails the requested exact finite
catalogue and Gate 1's independently renderable examples.

Refine the catalogue and prose together so every executed mutation has exactly
one ID and every generated ID denotes exactly one literal mutation. At minimum,
split numeric string/float and impossible-date/format-date IDs (or select one
literal mutation and stop claiming the other), and define exact accepted
whitespace and extras inputs with accurately named IDs.

## Whole-contract findings

- Primary, date, maximum, truncation, corrected zero and payment-floor
  arithmetic match the current public source and the independently worked
  values.
- The five exact trace rows, reason order, nine hash payloads, exclusions,
  future-date observations and ordinary copy-on-write result match source.
- Setup failure, regression, successful transcript order and success-marker
  suppression are otherwise exact and repeatable.
- The oracle remains isolated and strictly `PILOT_ONLY`; it does not promote
  formula coefficients, Kss/KTU policy, evidence admission, signed reversal,
  HTTP, authorization, persistence, allocation, acceptance or payment release
  to target semantics.
- The unbounded aggregate-overflow case is correctly documentary rather than a
  resource-amplifying execution.

## Required next step

Correct only the catalogue/prose bijection above, then assign a different fresh
independent Gate 1 rereviewer. RED and implementation remain unauthorized until
`READY_FOR_OWNER_REVIEW` and explicit owner approval.

## Verification evidence

- `openspec validate characterize-premium-snapshot-determinism --strict` — PASS
  (`Change 'characterize-premium-snapshot-determinism' is valid`).
- `git diff --check` — PASS.
- `make architecture-check` — PASS (`ARCHITECTURE CHECK PASSED (6 rules)`).

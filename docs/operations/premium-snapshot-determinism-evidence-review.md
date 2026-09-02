# PREMIUM-SNAPSHOT-DETERMINISM-001 — source-evidence review

Date: 2026-09-01  
Reviewer: fresh independent agent `premium_determinism_evidence_review_0901f`  
Reviewed artifact: `docs/operations/premium-snapshot-determinism-behavior-evidence.md`  
Verdict: **CHANGES_REQUESTED**

## Scope and evidence checked

The review compared every behavior claim with:

- `rapid-pilot/legacy-migration/PremiumCalculation.php`;
- `rapid-pilot/verify-premium-calculation.php`;
- `rapid-pilot/legacy-migration/HistoricalPremiumReplayAdapter.php`;
- the premium references in `verify-otiz-workflow.php` and
  `verify-native-operational-live-scenario.php`;
- PB-08/PB-09 in `docs/operations/pilot-behavior-inventory.md`;
- `PREMIUM-SNAPSHOT-DETERMINISM-001` and GRILL-001 in
  `docs/operations/migration-backlog-and-grill.md`;
- the product requirement for a reproducible dated premium slice and the pilot
  exclusion of official premium calculation from the first increment.

The boundary classification is correct: the literal v1 formula, coefficients,
reason codes and permissive validation are `PILOT_ONLY`; only reproducibility
from dated sourced operands is product-supported. HTTP calculation, persisted
snapshot construction/hash, allocation, acceptance, closure and financial
authority remain outside this pure-function discovery and under GRILL-001 where
applicable.

## Required corrections

1. **The claimed exclusion list constraint is not implemented.** The note says
   exclusions are a list, but `calculate()` performs a plain `foreach` and
   accepts associative/non-list arrays. It then emits a newly indexed normalized
   list. Amend the observed contract and add this shape quirk to the Gate 1
   freeze-versus-contrast decision. Do not promote permissive associative input
   to target semantics accidentally.

2. **The overflow question is incomplete.** On the repository's expected
   64-bit PHP runtime, each declared multiplication maximum is `2e16`, below
   `PHP_INT_MAX`; the two formula products are therefore safe within the stated
   per-operand ranges. The unchecked risk is the unbounded number of signed
   payment rows: `$sum += amountCents` can overflow from integer to float and
   then violate the declared `int` return type (or otherwise fail before the
   explicit net-negative check). Replace the generic multiplication question
   with an exact runtime assumption/check plus a payment-count/aggregate-bound
   gap. A characterization must not claim all accepted per-row inputs are safely
   executable without bounding the aggregate.

## Additional facts/risks to capture before OpenSpec

- `paymentEvidence` itself need not be a list, but both named members must exist
  as lists; missing either member is rejected by `payments(null, ...)`. Extra
  top-level keys and extra row keys are accepted and echoed unchanged.
- Extra operand facts and extra fields inside operand facts are accepted and
  echoed in `operandEvidence`; the six required facts are validated, but the
  overall input shape is not canonicalized. Array insertion order can therefore
  alter serialized output even when the six calculation values are equal. This
  matters if a later layer derives a content hash from the result.
- Closure and payout row dates, operand effective dates, and exclusion effective
  dates are syntax-checked only. They are not constrained to the report date or
  ordered, and payment rows are summed in caller order. Future-dated evidence is
  already mentioned, but the same lack of temporal filtering applies to closure
  and payout dates.
- Individual payment rows may be negative; only the final net is constrained to
  nonnegative. The note describes signed rows correctly, but Gate 1 should
  distinguish this pilot reversal-like arithmetic from approved payment or
  reversal semantics.
- The result is deterministic for the same in-memory PHP arrays and runtime.
  This is narrower than a canonical snapshot-content guarantee: echoed input
  ordering and untouched extra fields prevent treating semantically equivalent
  arrays as identical serialized content.
- PHP arrays use copy-on-write value semantics. The evidence should avoid
  suggesting an externally observable reference alias unless inputs actually
  contain references; the relevant observable gap is unchanged echo versus
  canonical normalization.

## Confirmed claims

The version literal, six required operands, exact-date validation, provenance
checks, numeric ranges, comparison-date selection, UTC day calculation, five
integer formula steps, floors, payout independence, discrepancy behavior,
reason order and existing verifier's literal expected values all match source.
The verifier's word “approved” is indeed unsupported and must not appear in the
PILOT_ONLY executable specification.

## Exit condition

Revise the evidence to correct exclusion shape and overflow scope and to record
the canonicalization/temporal-filtering facts above. A fresh independent review
can then issue `READY_FOR_OPENSPEC`; no RED or implementation is authorized by
this review.

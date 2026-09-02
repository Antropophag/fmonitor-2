# Premium snapshot determinism — initial behavior evidence

This note starts discovery for `PREMIUM-SNAPSHOT-DETERMINISM-001`. It records
the current pure calculation oracle only. Exact premium/Kss/KTU norms remain
under GRILL-001 and are not approved target financial semantics.

## Boundary and classification

- Current seam: static pure function
  `PremiumCalculation::calculate(operands, paymentEvidence, exclusions)` in
  `rapid-pilot/legacy-migration/PremiumCalculation.php`.
- Current version literal: `premium-calculation-v1`.
- Target ownership candidate: `PremiumDecisions::calculatePremiumSnapshot`.
- Classification: `PILOT_ONLY` formula characterization plus
  `PRODUCT_ACCEPTED` requirement that a snapshot calculation be reproducible
  from dated sourced operands.
- This discovery does not characterize OTIZ HTTP calculation, persistence,
  allocation, acceptance or payment closure. It does not approve the literal
  formula coefficients.

The pure function has no DB, filesystem, network, actor, authorization or clock
dependency. Exact replay of the same typed arrays returns the same nested array.
PHP integer arithmetic (`intdiv`) determines kopeck truncation.

## Required input evidence

Six named operands are mandatory: `reportDate`, `premiumCents`, `shaftBp`,
`progressBp`, `deadlineDate`, `completionDate`. Every operand is an array with
`value`, exact `effectiveDate` and source provenance containing nonblank `label`,
nonblank `locator` and lowercase 64-hex `contentSha256`.

Dates must be exact `Y-m-d`. Money is integer `0..1_000_000_000_000`;
`shaftBp` is integer `0..20_000`; progress is integer `0..10_000`;
completion is exact date or `null`. Closure and actual-payout evidence must be
lists of dated, sourced integer amounts with absolute value at most
`1_000_000_000_000`. Net closure and net actual payout cannot be negative.

Exclusions are iterated without `array_is_list`: both list and associative arrays
are accepted when every value has code matching `[A-Z][A-Z0-9_]{2,79}`, exact
effective date and source provenance. They are normalized into a numeric result
list containing only `code`, `effectiveDate`, `source`.

Operand and payment arrays are returned with original extra fields and key/order
shape; the function does not canonicalize them. PHP arrays use copy-on-write, so
ordinary later caller mutation does not establish a shared-reference alias, but
explicit reference-containing inputs and serialization stability need separate
tests before any content-hash contract. Missing `closures` or `actualPayouts`
members are rejected because each becomes a non-list `null` input.

## Observed PILOT_ONLY calculation

The comparison date is completion when non-null and no later than report date;
otherwise report date. Whole UTC day difference after deadline is clamped to
zero. Current Kss is `max(0, 10000 - 100 * daysLate)`.

Current five steps are:

1. `fund = intdiv(premiumCents * shaftBp, 10000)`;
2. `progressAmount = intdiv(fund * progressBp, 10000)`;
3. current Kss above;
4. `accrued = intdiv(progressAmount * Kss, 10000)`;
5. `pool = max(0, accrued - netClosures)`.

Remaining fund is `max(0, fund - netClosures)`. Exclusions preserve calculated
amounts but set distributable amount to zero. Actual payouts never backsolve
progress or entitlement; when the actual-payout list is nonempty their net is
compared with accrued and exposed as a discrepancy.

Reason ordering is stable: `DEADLINE_PENALTY`, `NO_NEW_AMOUNT`,
`PAYOUT_DISCREPANCY`, `CALCULATION_EXCLUDED`, each only when its condition holds.
The result also echoes version, operands, five-step trace, normalized exclusions,
payment evidence, progress and Kss.

All evidence dates receive syntax validation only: the function does not filter
operand effective dates, closures, payouts or exclusions against `reportDate`.
Thus future-dated evidence can participate when supplied by the caller. All
coefficients, reason vocabulary and this permissive dating are observations, not
approved target rules. Signed payment rows are accepted within the per-row
bound; treating a negative closure as a reversal is caller/pilot behavior, not
approval of target reversal semantics.

## Existing verifier and gaps

`rapid-pilot/verify-premium-calculation.php` uses one literal case:

- premium `10_000_000`, shaft `8000`, progress `5000`;
- report `2026-08-31`, deadline `2026-08-21`, no completion;
- one closure `500_000`, one actual payout `123_456`.

It expects fund `8_000_000`, Kss `9000`, accrued `3_600_000`, pool
`3_100_000`, remaining `7_500_000`, discrepancy `-3_476_544`, exact replay,
payout independence and exclusion-driven distributable zero. The verifier text
incorrectly calls the one-percentage-point-per-day penalty “approved”; any
focused characterization must label it `PILOT_ONLY`.

The current verifier does not sensitively cover:

- missing/type/range/provenance/date rejection for every input family;
- zero/maximum operands; formula products at the declared maxima remain within
  signed 64-bit PHP integers, but payment-row count is unbounded and aggregate
  `$sum += amountCents` has no overflow guard;
- before/on/after-deadline completion selection and Kss floor at zero;
- empty versus zero-net actual-payout list (`null` versus integer discrepancy);
- signed closure/reversal rows, negative-net rejection and pool/remaining floors;
- reason ordering/cardinality, associative exclusion acceptance and exclusion
  normalization/ordering;
- source whitespace acceptance, exact hash case, future evidence dates, missing
  payment members and lack of report-date filtering;
- echoed extra fields/key order, copy-on-write/reference-containing inputs and
  their impact on later canonical snapshot hashing;
- isolation from HTTP snapshot construction, allocations and persistence hash.

`verify-native-operational-live-scenario.php` embeds a separate persistence
wrapper around the pure result. `verify-otiz-workflow.php` covers a broad HTTP
workflow. Neither is a substitute for a focused pure-function oracle, and their
snapshot/persistence behavior belongs to separate slices.

## Discovery questions before OpenSpec

1. Determine the smallest literal matrix that catches arithmetic/order/reason
   drift without implying target approval of v1 coefficients.
2. Establish a bounded payment-row/aggregate contract and PHP overflow outcome;
   the declared formula operand products themselves fit signed 64-bit runtime.
3. Decide whether characterization should freeze current permissive provenance
   dating/whitespace/array-shape quirks or record them only as target contrasts.
4. Keep target norms, Kss/KTU, authorization, snapshot persistence/allocation,
   acceptance and payment consequences explicitly outside this discovery.

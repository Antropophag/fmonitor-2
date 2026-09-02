# CHARACTERIZE-PREMIUM-SNAPSHOT-DETERMINISM-001 — Gate 1 review

Date: 2026-09-01  
Reviewer: fresh independent agent `premium_determinism_gate1_review_0901j`  
Artifact: `specs/CHARACTERIZE-PREMIUM-SNAPSHOT-DETERMINISM-001.md`  
Verdict: **CHANGES_REQUESTED**

## Scope reviewed

The draft was checked independently against the source-evidence package,
reviewed OpenSpec planning, `PremiumCalculation.php`, the current premium
verifier, product/pilot boundaries and `docs/development-process.md`. The review
recomputed the stated arithmetic, date boundaries, reason order, payment floors,
row-count overflow threshold and the nine displayed SHA-256 values. No spec or
production/test code was edited by this review.

## Blocking findings

### 1. Exact trace expectations still depend on production source

The primary example requires five trace rows with “current literal formula
strings” but does not state those five strings or the complete exact row shapes.
Gate 1 requires independently fixed expected values; a RED author would have to
copy the strings and field layout from `PremiumCalculation.php`, contrary to the
draft's own independence rule. Record the five full expected trace rows,
including exact keys, key order, formula literals and result fields, in the
specification itself.

### 2. Provenance hashes lack their hashed payload contract

All nine displayed hashes are correct SHA-256 values of the lowercase token
text (`report`, `premium`, `shaft`, `progress`, `deadline`, `completion`,
`closure`, `payout`, `exclusion`). The draft says the verifier may compute
“fixture hashes” but never identifies those exact input bytes. A verifier cannot
independently reproduce the table without guessing or consulting its author.
State the exact UTF-8 payload for each hash (or state the deterministic token
rule explicitly), with no newline/encoding ambiguity. Then require either the
precomputed literals or the independently computed equality consistently; do
not leave this as an unspecified optional operation.

### 3. Several compound cases are not executable exact examples

The all-four-reasons case says only “a constructed case” and fixes no complete
input/result. The future-date contrast says future evidence “influence/echo
normally” without selecting exact operand/payment/exclusion mutations and exact
observable outputs. The copy-on-write claim does not say which caller value is
mutated after which call or which returned path must remain unchanged. These
acceptance statements therefore permit materially different tests and do not
meet Gate 1's observable worked-example requirement. Give one exact literal
mutation and complete relevant expected values for each claim.

### 4. Validation cases do not consistently identify an exact rejected family

The oracle intentionally does not assert exception messages, so each rejection
must be made observable by a precisely named one-axis mutation. However,
“invalid source” for every operand, “invalid date or provenance” for payments,
and “invalid source” for exclusions do not specify which nested member/value is
changed. “Associative instead of list payment rows” is also ambiguous between
an associative collection and an associative row (valid rows are associative).
Define the exact mutation(s), especially source label/locator/hash failures and
the non-list payment collection, and name the case/family in the transcript.

### 5. Transcript contract is not exact enough for repeatability/sensitivity

The draft lists milestone categories but not their exact normalized lines,
ordering, or the required `SETUP_FAILURE` and `REGRESSION` prefixes/bodies.
Consequently two implementations can satisfy the prose while emitting different
transcripts, and canonical aggregation cannot assert the promised normalized
contract independently. Specify the exact stable transcript (or an exact
ordered schema rendered from literals), including how a failed named case is
classified, while keeping exception English diagnostic-only.

## Independently confirmed details

- Nominal values are correct: days late `10`, Kss `9000`, fund `8,000,000`,
  progress amount `4,000,000`, accrued `3,600,000`, pool `3,100,000`, remaining
  `7,500,000`, actual paid `123,456`, discrepancy `-3,476,544`.
- Date/completion and Kss-floor rows match the current UTC-day implementation.
- Maximum and truncation arithmetic is correct and stays inside signed 64-bit.
- Payment floors, discrepancy nullability, exclusions and the stated reason
  order match the current calculator.
- First positive aggregate overflow with individually accepted `1e12` rows is
  indeed at row `9,223,373`; keeping this resource-amplifying case non-executable
  is appropriate.
- The seam and exclusions/payment/operand echo observations match source.
- The draft maintains a strict `PILOT_ONLY` boundary and does not promote v1
  coefficients, signed reversals, permissive dates, reason vocabulary, HTTP,
  persistence, allocation, acceptance or payment release to target semantics.

## Required next step

Correct only the executable-spec ambiguities above, then assign a different
fresh independent Gate 1 rereviewer. RED and implementation remain unauthorized
until `READY_FOR_OWNER_REVIEW` and explicit owner approval.

## Verification evidence

- `openspec validate characterize-premium-snapshot-determinism --strict` — PASS
- `git diff --check` — PASS
- `make architecture-check` — PASS (`ARCHITECTURE CHECK PASSED (6 rules)`)

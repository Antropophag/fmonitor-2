# CHARACTERIZE-PREMIUM-SNAPSHOT-DETERMINISM-001 — Gate 1 rereview v5

Date: 2026-09-01  
Reviewer: fresh independent agent `premium_determinism_gate1_rereview_0901o`  
Artifact: `specs/CHARACTERIZE-PREMIUM-SNAPSHOT-DETERMINISM-001.md`  
Prior reviews: `docs/operations/premium-snapshot-determinism-gate1-review.md`,
`docs/operations/premium-snapshot-determinism-gate1-rereview.md`,
`docs/operations/premium-snapshot-determinism-gate1-rereview-v2.md`,
`docs/operations/premium-snapshot-determinism-gate1-rereview-v3.md`,
`docs/operations/premium-snapshot-determinism-gate1-rereview-v4.md`  
Verdict: **READY_FOR_OWNER_REVIEW**

## Scope reviewed

The current Gate 1 draft was checked independently against every prior Gate 1
finding, the behavior evidence, strict-valid OpenSpec planning and the public
`PremiumCalculation::calculate` source. This pass mapped every stated payment,
exclusion and provenance mutation one-to-one to its exact literal and finite
`CASE_ID`, then reread the complete arithmetic, validation, transcript,
isolation and `PILOT_ONLY` contract. No specification, production code or test
code was edited.

## Closed v4 finding

The remaining catalogue ambiguity is closed:

- closure and actual-payout `amount_type` cases now select their own primary
  decimal strings, `500000` and `123456`; both `amount_above_max` cases select
  the exact integer `1000000000001`, and the family-qualified IDs identify each
  mutation one-to-one;
- exclusion `element`, `code_blank`, `code_short`, `code_lower`, `code_long` and
  `date` now select exact replacements: `scalar`, `''`, `AB`, `abc`, 81 ASCII
  `A` bytes and `2026-02-30`, with a distinct finite ID for each;
- uppercase, short and non-hex provenance hashes are exact mutations (64 ASCII
  `A`, 63 lowercase `a`, 64 lowercase `g`) and have separate `hash_upper`,
  `hash_short` and `hash_nonhex` IDs for every operand, payment and exclusion
  family.

Every retained displayed catalogue expansion corresponds to one stated literal
execution, and every named execution has one stable ID. The earlier zero-case,
trace, fixture-hash, compound-case, date/type, accepted-contrast and failure-line
ambiguities also remain closed.

## Whole-contract findings

- Primary, date, zero, maximum, truncation, payment-floor and all-reasons values
  match the current public source and the independently fixed examples.
- Exact trace row shapes/order, nine fixture payload hashes, validation families,
  permissive contrasts, replay and stdout failure grammar are complete enough
  to author a sensitive repeatable RED without consulting private helpers.
- Setup failure and regression remain distinct; canonical registration and
  success-marker suppression are exact.
- Isolation is explicit: literal in-memory arrays only, no DB, HTTP, actor,
  clock, filesystem, session, snapshot persistence, allocation or payment
  command behavior.
- The specification remains strictly `PILOT_ONLY` and does not approve current
  coefficients, Kss/KTU policy, evidence-date admission, signed reversals,
  reason vocabulary, acceptance, payment release or other target financial
  semantics.

No new blocking ambiguity was found. RED and implementation remain unauthorized
until explicit owner approval of Gate 1.

## Verification evidence

- `openspec validate characterize-premium-snapshot-determinism --strict` — PASS
  (`Change 'characterize-premium-snapshot-determinism' is valid`).
- `git diff --check` — PASS.
- `make architecture-check` — PASS (`ARCHITECTURE CHECK PASSED (6 rules)`).

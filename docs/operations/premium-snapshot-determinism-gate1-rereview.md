# CHARACTERIZE-PREMIUM-SNAPSHOT-DETERMINISM-001 — Gate 1 rereview

Date: 2026-09-01  
Reviewer: fresh independent agent `premium_determinism_gate1_rereview_0901k`  
Artifact: `specs/CHARACTERIZE-PREMIUM-SNAPSHOT-DETERMINISM-001.md`  
Prior review: `docs/operations/premium-snapshot-determinism-gate1-review.md`  
Verdict: **CHANGES_REQUESTED**

## Scope reviewed

The corrected Gate 1 draft was reread independently against the prior five
blocking findings, current calculator source, evidence, strict-valid OpenSpec
planning and the delivery process. Hashes, date differences, arithmetic,
reason order, echo/normalization observations and the copy-on-write example
were recomputed. No specification, production code or test code was edited.

## Closed prior findings

1. **Trace strings/shapes/order — closed.** All five rows now state exact ordered
   keys, formula literals and independently computed result fields.
2. **Hash bytes — closed.** The nine lowercase token byte strings, UTF-8/ASCII
   encoding and absence of BOM/whitespace/NUL/LF are explicit; all nine stated
   SHA-256 values recompute exactly.
3. **All-reasons/future/COW cases — closed.** Each now names literal mutations
   and exact observable values. The all-reasons arithmetic and order match the
   source, future closure/exclusion dates are accepted as stated, and ordinary
   post-call PHP copy-on-write preserves the returned primary values.
4. **One-axis rejection mutations — closed.** Operand, payment and exclusion
   mutations now identify the exact member/value and distinguish an associative
   payment collection from its valid associative row.

## Blocking findings

### 1. Zero-boundary expectation contradicts its inherited dates

The zero case changes only `(premium,shaft,progress)` to `(0,0,0)`, says Kss is
“from dates”, and requires reasons exactly `['NO_NEW_AMOUNT']`. Under the
primary dates retained by that wording (`report=2026-08-31`,
`deadline=2026-08-21`, `completion=null`), the calculator produces Kss `9000`
and reasons exactly `['DEADLINE_PENALTY','NO_NEW_AMOUNT']`. This was reproduced
through the public calculator seam. Make the case executable by either stating
same-day/non-late date literals or retaining primary dates and correcting the
exact ordered reasons. Also state the resulting exact Kss.

### 2. Regression transcript is still not an exact renderable contract

The successful and setup-failure lines are exact, but the regression line uses
`<CASE_ID>`, `<TOKEN>` and `<TYPE_TOKEN>` without enumerating the promised fixed
case catalogue or defining deterministic rendering/escaping for expected and
actual tokens. A RED author can therefore choose materially different case IDs
and serializations while claiming conformance, so repeatability and aggregate
sensitivity cannot be independently asserted from Gate 1. Record the finite
case IDs and an exact one-line token-rendering grammar (including array/null/
exception/missing-value handling), or replace these fields with a fully fixed
line contract. Exception English may remain diagnostic-only.

## Independently confirmed values and boundaries

- Primary: fund `8,000,000`, accrued `3,600,000`, closed `500,000`, pool
  `3,100,000`, remaining `7,500,000`, distributable `3,100,000`, Kss `9000`,
  discrepancy `-3,476,544`.
- Completion differences are `5`, `10`, and capped-to-report `10`; the floor
  example is `133` late days and Kss `0`.
- Maximum and truncation results are correct and remain within signed 64-bit.
- Payment floors, signed-net replay, negative-net rejection, exclusion
  normalization and four-reason ordering match current source behavior.
- First positive payment aggregate overflow at `1e12` per row is row
  `9,223,373`; retaining it as a non-executable source risk is appropriate.
- The draft remains strictly `PILOT_ONLY` and does not promote formula,
  evidence-date, reversal, HTTP, persistence, acceptance or release semantics.

## Required next step

Correct the two executable-spec ambiguities above, then assign another different
fresh independent Gate 1 rereviewer. RED and implementation remain unauthorized
until `READY_FOR_OWNER_REVIEW` and explicit owner approval.

## Verification evidence

- `openspec validate characterize-premium-snapshot-determinism --strict` — PASS
  (`Change 'characterize-premium-snapshot-determinism' is valid`).
- `git diff --check` — PASS.
- `make architecture-check` — PASS (`ARCHITECTURE CHECK PASSED (6 rules)`).

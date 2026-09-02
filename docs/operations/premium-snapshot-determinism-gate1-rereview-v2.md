# CHARACTERIZE-PREMIUM-SNAPSHOT-DETERMINISM-001 — Gate 1 rereview v2

Date: 2026-09-01  
Reviewer: fresh independent agent `premium_determinism_gate1_rereview_0901l`  
Artifact: `specs/CHARACTERIZE-PREMIUM-SNAPSHOT-DETERMINISM-001.md`  
Prior reviews:
`docs/operations/premium-snapshot-determinism-gate1-review.md`,
`docs/operations/premium-snapshot-determinism-gate1-rereview.md`  
Verdict: **CHANGES_REQUESTED**

## Scope reviewed

The current draft was checked independently against both prior Gate 1 reviews,
the evidence package, strict-valid OpenSpec planning and the public calculator
source. The zero-boundary arithmetic/reasons, the finite `CASE_ID` expansion and
the complete stdout failure grammar were reread literally. No specification,
production code or test code was edited.

## Closed prior finding

### Zero boundary — closed

The case now explicitly retains the primary late dates, fixes Kss at `9000` and
requires reasons exactly
`['DEADLINE_PENALTY','NO_NEW_AMOUNT']`. This matches the public calculator seam
and removes the earlier contradiction.

## Blocking findings

### 1. The advertised finite case catalogue does not name every stated case

The grammar is syntactically finite, but it is not an exact catalogue of the
matrix above it. Several separately stated one-axis executions collapse onto a
single ID: numeric string and float both have only `value_type`; negative and
above-limit values both have only `value_range`; and the explicitly stated
blank exclusion code has no distinct member alongside `code_short`. Conversely,
the Cartesian operand expansion creates `value_type` and `value_range` IDs for
all six operands even though the prose defines different value families for
required dates and nullable completion. This conflicts with the preceding
requirement that the transcript name the case family/member and prevents a
reviewer from mapping each named execution to one exact stable ID.

Record an explicit finite ID list, or refine the grammar so each executed
mutation has one unambiguous ID and every generated ID has one exact mutation.

### 2. The stdout token grammar retains a double-prefix ambiguity

The line template contains `expected=<TOKEN> actual=<TYPE_TOKEN>`, but the next
paragraph defines `<TOKEN>` as the full field `expected=contract` and each
`<TYPE_TOKEN>` as a full field beginning with `actual=`. Literal substitution
therefore renders `expected=expected=contract` and
`actual=actual=return-mismatch`, while the likely intended line has each prefix
once. This is not an exact renderable grammar.

Define token values without field prefixes (for example `<TOKEN> ::= contract`
and `<TYPE_TOKEN> ::= return-mismatch | ...`), or define the complete fields and
remove `expected=`/`actual=` from the template. Also assign the currently listed
`no-result` token an exact triggering condition or remove it; the prose maps
return/expected-validation/unexpected throws but does not say when `no-result`
is selected.

## Independently reconfirmed boundaries

- Primary arithmetic, trace shapes/formula strings, reason order and nine hash
  payloads remain consistent with source and evidence.
- The corrected zero case has Kss `9000` and the two ordered reasons above.
- Payment/exclusion/date contrasts, copy-on-write observation and non-executed
  aggregate-overflow risk remain coherent and strictly `PILOT_ONLY`.
- No HTTP, persistence, allocation, acceptance, payment release or target
  financial semantics have been promoted.

## Required next step

Correct the catalogue-to-matrix mapping and failure-token grammar, then assign a
different fresh independent Gate 1 rereviewer. RED and implementation remain
unauthorized pending `READY_FOR_OWNER_REVIEW` and explicit owner approval.

## Verification evidence

- `openspec validate characterize-premium-snapshot-determinism --strict` — PASS
  (`Change 'characterize-premium-snapshot-determinism' is valid`).
- `git diff --check` — PASS.
- `make architecture-check` — PASS (`ARCHITECTURE CHECK PASSED (6 rules)`).

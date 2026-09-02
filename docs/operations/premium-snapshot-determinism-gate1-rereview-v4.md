# CHARACTERIZE-PREMIUM-SNAPSHOT-DETERMINISM-001 — Gate 1 rereview v4

Date: 2026-09-01  
Reviewer: fresh independent agent `premium_determinism_gate1_rereview_0901n`  
Artifact: `specs/CHARACTERIZE-PREMIUM-SNAPSHOT-DETERMINISM-001.md`  
Prior reviews: `docs/operations/premium-snapshot-determinism-gate1-review.md`,
`docs/operations/premium-snapshot-determinism-gate1-rereview.md`,
`docs/operations/premium-snapshot-determinism-gate1-rereview-v2.md`,
`docs/operations/premium-snapshot-determinism-gate1-rereview-v3.md`  
Verdict: **CHANGES_REQUESTED**

## Scope reviewed

The current draft was checked independently against all prior Gate 1 findings,
the behavior evidence, strict-valid OpenSpec planning and the public
`PremiumCalculation::calculate` source. This pass mapped the refined `CASE_ID`
grammar back to every claimed validation/contrast execution and checked the
new literals and insertion order. No specification, production code or test
code was edited.

## Closed v3 finding

The four specific v3 ambiguities are closed. Numeric strings and floats now
have separate IDs and exact values for all three numeric operands. Impossible
and format-invalid value dates now have separate IDs and exact literals for all
three date operands. The accepted whitespace contrast identifies the PREMIUM
label, exact leading/trailing ASCII spaces and unchanged echoed path. The
extras and top-level key-order contrasts now fix field names, values, insertion
positions and exact operand order. Their IDs are accurately named.

## Blocking finding

### Remaining catalogue entries still lack one exact mutation

The catalogue is still not bijective with the executable prose:

- `payment.{closures,actualPayouts}.amount_above_max` has no exact literal in
  the one-axis matrix. The earlier summary says only absolute amount above
  `1e12`; it does not select a value. `actualPayouts.amount_type` is also
  ambiguous because “equivalent cases” may mean the stated string `500000` or
  the string form `123456` of that row's primary value.
- `exclusion.element`, `code_blank`, `code_lower`, `code_long`, and `date` do
  not fix the exact replacement literals. Only `code_short=ab` is literal.
  Therefore multiple materially different mutations can emit each listed ID.
- The prose additionally states that short and non-hex provenance hashes reject,
  while each operand/payment/exclusion family exposes only one `hash` ID and
  the named one-axis mutation fixes that ID to 64 uppercase `A`. Either make
  short/non-hex observations non-executed source notes, select one hash
  mutation, or give every executed mutation its own ID and exact literal.

Fix these remaining cases by selecting exact literals (and stating the exact
actual-payout type mutation), then give every retained executed provenance-hash
mutation one unique ID. After that, assign another different fresh reviewer.

## Whole-contract findings

- Primary/date/boundary/payment arithmetic, exact trace, reasons and hashes
  remain consistent with the public source.
- Whitespace, future-date, extras, key-order and copy-on-write contrasts are now
  exact and source-consistent.
- Failure outcome rendering remains deterministic and contradiction-free.
- The draft remains isolated and strictly `PILOT_ONLY`; it does not promote
  current calculator behavior to target financial semantics.
- RED and implementation remain unauthorized pending a
  `READY_FOR_OWNER_REVIEW` verdict and explicit owner approval.

## Verification evidence

- `openspec validate characterize-premium-snapshot-determinism --strict` — PASS
  (`Change 'characterize-premium-snapshot-determinism' is valid`).
- `git diff --check` — PASS.
- `make architecture-check` — PASS (`ARCHITECTURE CHECK PASSED (6 rules)`).

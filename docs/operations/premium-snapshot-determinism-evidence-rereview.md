# PREMIUM-SNAPSHOT-DETERMINISM-001 — source-evidence rereview

Date: 2026-09-01  
Reviewer: fresh independent agent `premium_determinism_evidence_rereview_0901g`  
Reviewed artifact: `docs/operations/premium-snapshot-determinism-behavior-evidence.md`  
Prior review: `docs/operations/premium-snapshot-determinism-evidence-review.md`  
Verdict: **READY_FOR_OPENSPEC**

## Independent evidence checked

The rereview compared the corrected note directly with:

- `rapid-pilot/legacy-migration/PremiumCalculation.php`;
- `rapid-pilot/verify-premium-calculation.php`;
- `rapid-pilot/legacy-migration/HistoricalPremiumReplayAdapter.php`;
- the premium use in `verify-otiz-workflow.php` and
  `verify-native-operational-live-scenario.php`;
- PB-08/PB-09 in `docs/operations/pilot-behavior-inventory.md`;
- `PREMIUM-SNAPSHOT-DETERMINISM-001` and GRILL-001 in
  `docs/operations/migration-backlog-and-grill.md`;
- `PRODUCT.md`, `CONTEXT.md`, and the pilot specification and data model.

## Closure of prior findings

1. **Exclusion shape — closed.** The note now says that `calculate()` iterates
   exclusions without `array_is_list`, accepts list or associative input, and
   emits a newly indexed normalized list. It explicitly leaves freezing this
   permissive quirk to Gate 1 instead of presenting it as target semantics.
2. **Overflow scope — closed.** The note identifies that the declared formula
   products fit a signed 64-bit PHP integer and isolates the actual unchecked
   risk: unbounded payment-row count can overflow `$sum += amountCents` before
   the explicit net check or violate the `int` return contract. It requests a
   bounded aggregate contract rather than implying safe execution for every
   sequence of individually valid rows.
3. **Top-level and echoed shape — closed.** Missing `closures` or
   `actualPayouts` are correctly described as rejected, while extra operand and
   payment fields, key shape and insertion order remain echoed and
   noncanonical. The note correctly narrows determinism to replay of the same
   typed PHP arrays and does not claim a canonical content-hash contract.
4. **Temporal filtering — closed.** Operand, payment and exclusion dates are
   accurately described as syntax-only validated and not filtered against the
   report date. Future-dated supplied evidence can therefore participate.
5. **Signed rows and alias wording — closed.** The note distinguishes
   pilot reversal-like signed arithmetic from approved reversal semantics and
   accurately frames ordinary PHP copy-on-write versus explicit
   reference-containing inputs.

## Independent accuracy and boundary result

The six required facts, exact date and provenance validation, integer ranges,
comparison-date selection, UTC day calculation, Kss floor, five `intdiv` steps,
closure and remaining floors, payout independence, discrepancy nullability,
reason ordering, exclusion normalization and the literal verifier values all
match source. The verifier's unsupported word “approved” is identified rather
than inherited.

The classification is appropriately strict. The literal v1 coefficients,
reason vocabulary, permissive dates and input-shape quirks remain
`PILOT_ONLY`. Only the product-supported need for reproducibility from dated,
sourced operands is classified `PRODUCT_ACCEPTED`. HTTP calculation, snapshot
construction/hash and persistence, allocations, acceptance, closure/payment
effects, authorization and financial authority are explicitly outside this
discovery and remain subject to their own slices or GRILL-001.

No source mismatch or boundary leak remains that would block an OpenSpec
planning package. This verdict authorizes planning only; it does not approve a
Gate 1 executable specification, RED, implementation, or target financial
semantics.

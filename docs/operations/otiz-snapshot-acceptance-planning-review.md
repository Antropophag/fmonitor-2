# OTIZ snapshot acceptance — independent planning review

- Reviewer: Codex subagent `/root/otiz_planning_review_0901a`
- Date: 2026-09-01
- Independence: read-only reviewer; did not author or edit the reviewed planning artifacts
- Scope: `proposal.md`, delta spec, `design.md`, `tasks.md`, project/product context,
  pilot contracts, delivery process and behavior-evidence note for
  `characterize-otiz-snapshot-acceptance`
- Validation: `openspec validate --strict characterize-otiz-snapshot-acceptance` — passed

## Findings

1. **Gate-blocking — conditional `unique_reversal` repair is not executable.**
   Evidence requires both the conditional `information_schema` probe and repair
   of a missing index on an already existing payment-closures table. The delta
   spec's only DDL scenario starts with all twelve tables absent, so table
   creation can yield the index without proving the conditional repair branch.
   `tasks.md` likewise asks only for presence of the index. Add a distinct
   authorized bad-CSRF fixture with the table present and `unique_reversal`
   absent, and require the request to restore exactly that index before CSRF
   rejection without business mutation.

2. **Gate-blocking — cleanup/failure behavior bypasses reviewed RED.** Task 3.4
   introduces success/failure isolation probes and their harness support only
   after task 3.2 approves the expanded RED and task 3.3 reaches GREEN. Yet the
   delta spec makes cleanup, ambient preservation and
   `SETUP_FAILURE`/`REGRESSION` classification normative behavior. Move these
   probes into the demonstrated expanded RED in 3.1 and its independent review
   in 3.2; only then permit their minimal GREEN implementation.

3. **Required clarification before Gate 1 — exact HTTP literals.** The package
   promises exact status, `Location`, `Cache-Control`, content type and plain
   bodies, but the delta scenarios abbreviate several of them (notably the auth
   redirect and unauthorized body). This may be delegated to the executable
   specification, but task 1.1 should explicitly require every case to copy the
   exact evidence-backed literals, including trailing LF and header presence or
   absence, so the promise cannot degrade into partial assertions.

The remaining package is coherent and properly bounded `PILOT_ONLY`: it does
not promote broad `otiz.manage`, pilot blocker semantics, replay policy,
runtime DDL, financial meaning or the candidate target seam. Literal fixtures,
three-field snapshot mutation, single append-only event, unchanged child rows,
independent Moscow timestamp bounds, real two-worker winner-neutral concurrency,
unique port/cookie/session ownership, GC-off, decoy preservation, bounded
cleanup, canonical single registration and fresh independent reviews are all
represented.

## Verdict

CHANGES_REQUESTED

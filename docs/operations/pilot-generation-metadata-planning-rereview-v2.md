# Pilot generation metadata planning rereview v2

- Review date: `2026-09-02`
- Reviewer task: fresh independent superseding rereview of the four current
  OpenSpec artifacts for `separate-pilot-generation-metadata`
- Reviewed artifacts: `proposal.md`, `design.md`, `tasks.md`, and
  `specs/operations/pilot-generation-metadata/spec.md`
- Independence: the reviewer did not author or edit the reviewed OpenSpec
  artifacts, runtime code, tests, decision records, or operations status.

## Authoritative inputs checked

- `PRODUCT.md`, `CONTEXT.md`, and `docs/development-process.md`;
- `docs/operations/pilot-generation-metadata-evidence.md`;
- `docs/operations/test-user-release-contour-decision.md`;
- `docs/operations/test-user-data-reset-decision.md`;
- current `docs/operations/status.md`;
- the four current OpenSpec artifacts named above.

This record supersedes the earlier planning rereview verdict for the current
artifact state. It does not rewrite that historical review.

## Findings

No blocking defect was found in the current four-artifact planning package.

1. **The selected release contour is consistent across all artifacts.**
   Compose `make up` is the only TEST-USER acceptance seam. The standalone CLI
   remains a synthetic harness, is explicitly excluded as a readiness oracle,
   and must be isolated in every supported topology. Proposal, design, delta
   spec, and tasks all preserve this boundary.

2. **The future deployment direction is recorded without inventing production
   approval.** The artifacts retain the owner-approved Compose approach for a
   future working deployment while keeping credentials, backup, scaling,
   availability, and the production operational runbook outside this change.

3. **The approved data/reset decision is incorporated at the correct
   boundary.** Ordinary startup/restart preserves state; only explicit
   ownership-proved `make reset` may be destructive. Fixture contents remain a
   separately gated deterministic fictional/native seed concern. The package
   neither imports personal/legacy data nor promotes reset into HTTP,
   production migrations, or ordinary startup.

4. **The setup lifecycle remains coherent and fail-closed.** Clean creation is
   prepare → separately gated prerequisites → readiness proof → finalize;
   existing ready state is validation-only. Ambiguous/incomplete state requires
   explicit recovery or reset. Consumer validation uses logical DB identity
   rather than impossible literal endpoint equality and retains an in-boundary
   recheck before state-changing work.

5. **SSD/TDD gates and predecessor constraints are preserved.** Planning does
   not approve an executable Gate 1 specification, RED, implementation, or
   destructive action. Task 1.1 correctly remains unchecked because the owner
   decisions are only part of the task: DDL-free predecessors, topology/runbook
   proof, and strict validation still must be confirmed before Gate 1.

6. **Non-blocking operations-status follow-up is required.** The current status
   still says the four artifact revisions require workflow confirmation and
   describes the data/reset rereview as pending. Those statements are stale
   after the explicit owner permissions and completed decision rereview. This
   does not contradict the four reviewed artifacts, but the delivery owner
   should supersede the status text before presenting this planning state as
   current operations truth.

## Verification

- `openspec validate separate-pilot-generation-metadata --strict`: PASS
  (`Change 'separate-pilot-generation-metadata' is valid`).
- `git diff --check`: PASS.
- `make architecture-check`: PASS (`6 rules`).

## Verdict

**READY_FOR_GATE1_WHEN_PREDECESSORS_LAND**

The four current planning artifacts faithfully encode the explicit owner
decisions and remain within setup-only, planning-only scope. Gate 1 may proceed
only after the declared DDL-free predecessors and remaining task 1.1 evidence
land; this verdict does not authorize RED or implementation.

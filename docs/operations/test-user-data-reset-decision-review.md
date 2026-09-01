# Independent review — TEST-USER data and reset owner decision

- Review date: `2026-09-02`
- Reviewer: fresh independent agent `test_user_data_reset_review_20260902k`
- Reviewed artifact: `docs/operations/test-user-data-reset-decision.md`
- Live-state cross-check: `docs/operations/status.md`
- Owner answer reviewed: explicit “Да” to deterministic synthetic/native data,
  no real personal data, state-preserving `make up`, and ownership-verified
  explicit `make reset`
- Verdict: `CHANGES_REQUESTED`

## Scope review

The decision record faithfully captures the owner's answer for the first
Compose TEST-USER contour:

- fixtures are deterministic, fictional, versioned and governed by a future
  separately approved seed contract;
- real personal data, production-source imports and sanitised legacy cutover
  are excluded;
- ordinary `make up`, stop and restart preserve Compose-owned state;
- deletion is available only through an explicit operator `make reset`, after
  exact Compose ownership and target-environment proof, with fail-closed
  handling of ambiguous, foreign or unresolved targets.

The boundaries are explicit and sufficient. This decision resolves only
`GRILL-004` source selection, personal-data exclusion and reset semantics. It
does **not** approve fixture contents or `TEST-USER-FIXTURE-SEED-001` Gate 1,
generation Gate 1, RED, production code, any destructive production operation,
or legacy cutover. It therefore supplies planning input without bypassing the
mandatory delivery gates.

## Required correction

`docs/operations/status.md` is internally inconsistent. Its current `DONE`
section records `APPROVED_SYNTHETIC_NATIVE_RESET_POLICY` and states that the
decision resolves `GRILL-004`, while the generation-metadata paragraph later
states that the same decision record/review are still pending. The decision
record now exists, and this review exists after this write, so the latter live
claim is stale.

Update only that current-status sentence to say that the owner decision record
exists and this independent review returned `CHANGES_REQUESTED` solely for the
status inconsistency (or, after this correction, route the unchanged decision
record to a fresh rereviewer). Preserve the separate true statement that
generation Gate 1 remains pending and that no RED or implementation has
started.

No decision, OpenSpec artifact, executable specification, test, production code
or destructive command needs changing for this finding.

## Verification

The reviewer inspected the owner decision, current operations status,
`GRILL-004`, product/context constraints and the mandatory SSD/TDD process.

- `git diff --check` — PASS (exit 0, no output).
- `make architecture-check` — PASS (`ARCHITECTURE CHECK PASSED (6 rules)`).

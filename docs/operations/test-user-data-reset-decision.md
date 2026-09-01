# TEST-USER data and reset owner decision

- Decision date: `2026-09-02`
- Decision owner: project owner
- Decision: `APPROVED_SYNTHETIC_NATIVE_RESET_POLICY`

## Decision

The first TEST-USER Compose contour uses deterministic synthetic/native data
only. Real personal data and sanitised legacy cutover are excluded from this
release contour.

Ordinary `make up`, stop and restart preserve the Compose-owned database,
generation state and artifacts. Destruction is available only through the
explicit operator command `make reset`.

`make reset` may remove only resources whose exact Compose ownership and target
environment have been proved before deletion. Ambiguous, foreign or unresolved
targets fail closed without deletion. Reset reports the affected owned resources
and that a new create/bootstrap is required.

## Scope boundaries

- Fixture contents must be fictional, deterministic and versioned under their
  own approved seed contract.
- No real names, personnel records, production documents, production source
  imports or other personal data may enter the TEST-USER contour.
- This decision resolves GRILL-004 for first-contour source selection, personal-
  data exclusion and reset semantics.
- It does not approve a fixture seed specification, generation Gate 1, RED,
  implementation, destructive production operation or legacy cutover.

## Provenance

The owner explicitly answered “Да” to the proposed policy: deterministic
synthetic data only, no real personal data, state-preserving `make up`, and an
explicit `make reset` limited to ownership-verified Compose resources.

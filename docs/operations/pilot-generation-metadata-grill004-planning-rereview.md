# Pilot generation metadata GRILL-004 planning rereview

- Review date: `2026-09-02`
- Reviewer task: fresh independent review after GRILL-004 reconciliation
- Change: `separate-pilot-generation-metadata`
- Independence: the reviewer did not author or edit the reviewed OpenSpec
  artifacts, executable specifications, tests, or production code.

## Exact reviewed artifacts

- `proposal.md` —
  `424de5203e2482de0182a8796afa3205cd57320e2a50a7231ea4b1d7f559f06b`
- `design.md` —
  `4861529ccece02cfd05fb9f28d051fdb0300428cd4a0fbbba0c73c2281057a95`
- `tasks.md` —
  `d3308b11659ac20daec1577e3a8d123b1cf9f884a2de8a7abbbc7c288502440a`
- `specs/operations/pilot-generation-metadata/spec.md` —
  `601108c2a02731992feb0a74cf2faf6dc61edca82cbff43bcac045517df2f404`

## Sources and prior findings checked

The review compared all four artifacts with `PRODUCT.md`, `CONTEXT.md`,
`docs/development-process.md`, generation-metadata evidence and prior planning
reviews, the Compose-contour owner decision, and
`docs/operations/test-user-data-reset-decision.md`. It also checked the current
diff rather than treating prior verdicts as approval of the amended proposal.

## Findings

No blocking defect was found.

1. GRILL-004 is now stated consistently: ordinary `make up`/restart preserves
   generation, database state and artifacts; destructive cleanup exists only at
   the explicit ownership-proved `make reset` seam and fails closed for an
   ambiguous or foreign target.
2. The package does not claim fixture approval. It records deterministic
   synthetic/native data and the exclusion of real personal data for the
   approved contour, while literal fixture contents remain owned by the
   separately gated `seed-test-user-fixtures` contract. Legacy cutover,
   production imports and product facts remain out of scope.
3. Compose `make up` remains the sole TEST-USER readiness contour. The standalone
   demo remains a disjoint synthetic harness; production credentials, backup,
   scaling and operational production approval are not inferred.
4. The setup lifecycle is coherent across proposal, design, delta spec and
   tasks: prepare, separately gated prerequisites, readiness proof and atomic
   publication; validation-only restart; explicit recovery/reset; unified
   fail-closed consumer validation and pre-write identity recheck.
5. No migration version or production catalogue order is introduced. DDL-free
   predecessors, exact Gate 1, demonstrated RED, independent reviews and full
   verification remain explicit future gates.

## Verification

- `openspec validate separate-pilot-generation-metadata --strict` — PASS
  (`Change 'separate-pilot-generation-metadata' is valid`).
- `git diff --check` — PASS (exit 0, no output).

## Verdict

**READY_FOR_GATE1_WHEN_PREDECESSORS_LAND**

The amended planning package correctly incorporates the approved data/reset
policy without absorbing fixture semantics. This verdict authorizes no Gate 1,
RED, implementation or destructive operation.

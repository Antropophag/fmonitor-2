# Independent identity/access technical Gate 1 rereview

- Date: `2026-09-02`
- Reviewer: fresh independent agent `identity_gate1_rereview_20260902ap`
- Executable specification: `IDENTITY-ACCESS-SCHEMA-001 v0.1`
- OpenSpec change: `canonicalize-identity-access-schema`
- Supersedes review: `docs/operations/identity-access-gate1-review.md`
- Verdict: `CHANGES_REQUESTED`

## Scope and independence

I independently rereviewed the corrected executable specification against the
previous three blocking findings, the landed workforce v5 runner, the current
25/26 composed-prefix contract, all four identity/access OpenSpec artifacts and
the owner-approved restartable exact-compatible partial-recovery decision. I
did not edit the specification, OpenSpec planning, production code, tests or
operations status.

This record is a technical Gate 1 rereview only. It does not grant owner
approval, authorize RED or implementation, or resolve `GRILL-002`.

## Closed findings

1. The executable specification now correctly binds the next migration after
   landed workforce v5 to literal canonical version `6`. Clean, repeat,
   conflict, CLI and Done sections use `schemaVersion=6`, and the contract
   requires fresh reconciliation and Gate 1 review if another predecessor is
   inserted before implementation.
2. The executable specification correctly describes 25 accepted ASCII bytes
   and 26 rejected before DB connection/access as the **current** inherited
   runner contract. The historical 32-byte contract is retained only as
   superseded history, and the independent workforce-local 37/38 boundary is
   not changed.
3. The executable specification correctly says restartable exact-compatible
   partial recovery agrees with the owner-approved, strict-valid OpenSpec
   policy. Full-family preflight, dependency-safe creation of only missing
   members, and zero mutation for any incompatible member remain coherent.

## Blocking findings

### 1. OpenSpec still contradicts the literal v6 executable contract

The prior review required coherent reconciliation of OpenSpec wording affected
by the now-literal v6 baseline. That reconciliation has not landed in the
current worktree:

- `proposal.md` says a number such as `v6` is not assumed;
- `design.md` decision 4 says the exact integer is not written in planning and
  explicitly rejects naming `v6`;
- the delta spec requires a sequential variable without a fixed number;
- `tasks.md` requires implementation not to fix the supposedly invented `v6`.

These statements directly contradict the executable specification's normative
literal `6` result and the landed v5 predecessor. Strict OpenSpec validation
checks document shape, not cross-artifact semantic agreement, so its PASS does
not close this conflict. All four OpenSpec artifacts must be updated coherently
to literal v6/current landed-v5 wording and validated again before another Gate
1 rereview.

### 2. Several applied-version expectations remain symbolic in executable examples

The correction fixes `schemaVersion`, but not every public `appliedVersions`
expectation is literal as requested by the previous review:

- the general success result says `appliedVersions` includes “it”;
- example B says “no identity version” rather than literal `6` is absent;
- example C says it “returns identity in appliedVersions” rather than literal
  `6` is present;
- Gate 2 matrix item 1 calls the literal expectation a “derived `6` sequence”.

Gate 2 tests must not derive this expected value from the implementation or an
identity alias. Replace these phrases with explicit presence/absence of literal
`6` and remove “derived” from the test contract.

## Rechecked accepted technical content

- The nine-table ordered ownership list and all nine column manifests remain
  consistent with the captured MariaDB evidence and current DDL owners,
  including exact enum/default/nullability/AUTO_INCREMENT semantics.
- The normalized compatibility fingerprint, exact PK/unique/secondary index
  structures and five documented FK relationships are internally coherent;
  generated index/FK names and AUTO_INCREMENT counters remain correctly
  non-semantic.
- Clean, complete/populated, partial, interrupted repeat and incompatible
  family behavior remains deterministic. Conflict lists, prefix isolation,
  collation handling, no-seed/no-rebuild behavior and no-runtime-DDL boundary
  do not introduce another contradiction.
- `GRILL-002` remains scoped to RBAC authority, authorization and audit behavior;
  this schema-ownership slice neither resolves nor silently approves it.

## Required next step

Reconcile the four OpenSpec artifacts to landed v5 / literal v6 and make every
`appliedVersions` expectation in the executable specification literal. Then
assign a fresh independent Gate 1 rereviewer. Technical readiness must still be
followed by explicit owner approval recorded in the executable specification.

**RED and implementation remain prohibited until that owner approval.**

## Verification

Current-tree results:

- `openspec validate canonicalize-identity-access-schema --strict` — PASS
  (`Change 'canonicalize-identity-access-schema' is valid`);
- `git diff --check` — PASS;
- `make architecture-check` — PASS (`7 rules`).

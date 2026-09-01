# Independent identity/access technical Gate 1 rereview v2

- Date: `2026-09-02`
- Reviewer: fresh independent agent `identity_gate1_rereview_20260902aq`
- Executable specification: `IDENTITY-ACCESS-SCHEMA-001 v0.1`
- OpenSpec change: `canonicalize-identity-access-schema`
- Supersedes review: `docs/operations/identity-access-gate1-rereview.md`
- Verdict: `CHANGES_REQUESTED`

## Scope and independence

I independently rereviewed the corrected executable specification and all four
OpenSpec artifacts against the previous blocking findings, landed workforce v5,
the current composed-prefix 25/26 boundary and the owner-approved restartable
exact-compatible partial-recovery policy. I did not edit the specification,
OpenSpec planning, production code, tests or operations status.

This is a technical Gate 1 rereview only. It does not grant owner approval,
authorize RED or implementation, or resolve `GRILL-002`.

## Closed findings

1. The executable specification now uses literal canonical version `6`
   coherently after landed workforce v5. Its public results and Gate 2 matrix
   specify exact clean `appliedVersions:[1,2,3,4,5,6]`, partial
   `appliedVersions:[6]`, repeat `appliedVersions:[]`, and conflict
   `schemaVersion=6`; no expected value is derived from implementation.
2. Proposal, delta spec, tasks and the normative decisions/migration plan in
   design now bind registration to literal v6 after v5 and require fresh
   reconciliation plus Gate 1 review if a predecessor is inserted.
3. The executable specification and planning retain the current composed
   25-byte accepted / 26-byte pre-DB rejection contract without changing the
   separate workforce-local 37/38 boundary.
4. Full-family preflight and restartable exact-compatible partial recovery are
   reconciled: only missing members are created in dependency order after every
   present member proves compatible; any incompatible member produces a
   zero-mutation conflict.

## Blocking finding

### Design context still contains the rejected dynamic/anti-v6 rationale

The opening paragraph of `design.md` still says the runner has a dynamically
changing migration set and that identity/access cannot be tied to an invented
number. That prose directly contradicts the same document's corrected decision
4, which binds the slice to literal v6 after landed v5 and prohibits silent
dynamic renumbering. It also contradicts the executable specification and the
other three corrected OpenSpec artifacts.

Replace that stale context sentence with current landed-v5/literal-v6 wording.
If a predecessor is inserted before implementation, the already documented
fresh reconciliation and repeated Gate 1 process remains the correct path.

## Rechecked accepted technical content

- The nine-table ownership list, literal manifests, normalized compatibility
  fingerprint, indexes and five foreign-key relationships remain coherent with
  the cited evidence.
- Clean, populated complete, partial, interrupted recovery, conflict, prefix
  isolation, collation and no-runtime-DDL contracts remain deterministic and
  do not authorize seed, rebuild or hidden repair.
- `GRILL-002` remains limited to RBAC authority, authorization and audit
  behavior. The schema-ownership slice neither resolves nor silently approves
  those semantics.

## Required next step

Correct the stale design context and assign another fresh independent Gate 1
rereviewer. Explicit owner approval must follow a technically ready verdict
before Gate 2 begins.

**RED and implementation remain prohibited until technical readiness and owner
approval are both recorded.**

## Verification

Current-tree results:

- `openspec validate canonicalize-identity-access-schema --strict` — PASS
  (`Change 'canonicalize-identity-access-schema' is valid`);
- `git diff --check` — PASS;
- `make architecture-check` — PASS (`7 rules`).

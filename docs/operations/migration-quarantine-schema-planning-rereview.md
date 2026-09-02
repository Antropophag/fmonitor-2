# Independent migration-quarantine schema planning rereview

Date: 2026-09-01  
Reviewer: fresh agent `migration_quarantine_planning_rereview_0901u`  
Change: `canonicalize-migration-quarantine-schema`

## Scope and method

This was a fresh planning-only rereview after the previous independent
`CHANGES_REQUESTED` verdict. I did not author or modify the OpenSpec package,
production code, verifier code or evidence. I checked the full proposal, delta
spec, design and tasks independently against the current artifact instructions,
product/process constraints, ownership evidence and current runtime/migration
seams. I also verified both prior blocking findings explicitly.

This review does not approve Gate 1, RED, implementation, quarantine semantics
or release promotion.

## Prior findings resolved

1. **Explicit behavior-slice contract — resolved.** `proposal.md` now names
   `CANONICALIZE-MIGRATION-QUARANTINE-SCHEMA-001`, the production migration
   operator/runner actor, both runtime DDL owners plus disposable MariaDB
   fingerprint as the source oracle, `bin/fmonitor2-migrate.php` delegating to
   `app/InstallationProcess` as the target public seam, the bounded release
   value and explicit non-goals. It does not elevate quarantine taxonomy,
   outcomes or financial use into target semantics.
2. **Concrete owning module — resolved.** `design.md` now locates canonical
   persistence ownership in `app/InstallationProcess`, names
   `bin/fmonitor2-migrate.php` as the public migration seam, restricts allowed
   dependencies to DB abstraction/schema-fingerprint primitives, keeps
   rapid-pilot as an adapter and records the expected architecture-ratchet
   reduction. Keeping the literal owner class and migration version symbolic
   until predecessors land is appropriate and does not make the module boundary
   ambiguous.

## Independent whole-package findings

- Proposal capability path and the single delta-spec path agree.
- The spec preserves the exact three-table ownership family, eight compatible
  present/absent states, family-wide preflight before DDL, populated rows and
  counters, decoys, implicit-commit restartability, plain-TEXT JSON, absence of
  FK/CHECK, explicit resolved collation and the 27-byte catalogue ceiling.
- Conflict paths are zero-mutation; repeat behavior preserves a single migration
  ledger entry; runtime registration/read/decision paths become DDL-free and
  state-changing paths fail closed before business writes when schema is absent
  or incompatible.
- Authorization and quarantine behavior remain inherited behavior rather than
  being silently redesigned by this ownership slice. Audit/history and
  idempotency are observable through the migration ledger, append-only preserved
  data and repeat scenarios.
- Tasks retain the mandatory order: executable-spec owner approval, demonstrated
  RED, fresh independent test review, minimal GREEN, regression/architecture/full
  verification and fresh independent code review. They explicitly defer exact
  ordering/version evidence until predecessors land.
- No package artifact authorizes RED or implementation now, and unresolved
  taxonomy, outcomes, correction/retention, cutover and financial semantics
  remain `NEEDS_GRILL`.

## Verdict

`READY_WHEN_PREDECESSORS_LAND`

The planning package is coherent and independently reviewed. Before Gate 1/RED,
the landed predecessor catalogue must be refreshed and task 1.1 must replace the
symbolic runner order and migration version with exact evidence. Owner approval
of the later executable specification remains mandatory.

## Verification

- `openspec validate canonicalize-migration-quarantine-schema --strict` — PASS
- `git diff --check` — PASS
- `make architecture-check` — PASS (`6 rules`)

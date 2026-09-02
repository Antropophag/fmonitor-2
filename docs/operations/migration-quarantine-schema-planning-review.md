# Independent migration-quarantine schema planning review

Date: 2026-09-01  
Reviewer: fresh agent `migration_quarantine_planning_review_0901t`  
Change: `canonicalize-migration-quarantine-schema`

## Scope and method

This was a planning-only independent review. I did not author or modify the
OpenSpec package, production code, verifier code or evidence. I compared the
proposal, delta spec, design and tasks with:

- `PRODUCT.md`, `CONTEXT.md` and `docs/development-process.md`;
- `docs/operations/migration-quarantine-schema-evidence.md` and its independent
  `READY_FOR_OPENSPEC` review;
- the current runtime owners/read model, production migration runner and
  runtime-DDL migration plan;
- the current JSON instructions for every OpenSpec artifact.

This review does not approve Gate 1, RED, implementation, quarantine semantics
or release promotion.

## Blocking findings

1. **The proposal omits the mandatory explicit behavior-slice contract.** The
   proposal instructions require the planning artifact to name the behavior
   slice, actor, source oracle, target public seam and release value. Unlike the
   adjacent migrated-evidence planning package, this proposal only describes
   changes and mentions migration tooling/OTIZ indirectly in `Impact`. It does
   not identify an operator actor, enumerate the two runtime DDL owners plus the
   isolated MariaDB fingerprint as the source oracle, name the canonical
   migration runner as the target public seam, or state the release value as
   one explicit contract. Add that bounded block without promoting quarantine
   taxonomy, outcomes or financial use.

2. **The design does not name the owning module concretely.** The design
   instruction requires the owning module, allowed dependencies, persistence
   owner, rapid-pilot adapter and architecture-check impact. Decisions 1 and 4
   cover the latter four concerns, but “current production migration
   module/catalogue” is not an identifiable module boundary. The current
   worktree has the production seam at `bin/fmonitor2-migrate.php` and migration
   implementations under `app/InstallationProcess/`; the design must name the
   intended canonical owner at that level (while keeping the literal class and
   migration version symbolic until predecessors land). This removes ambiguity
   about whether schema ownership could remain in `rapid-pilot/legacy-migration`.

## Confirmed non-blocking properties

- The capability path and single delta spec agree with the proposal.
- Requirements preserve the exact three-member family, all eight compatible
  present/absent states, family-wide preflight, populated rows/counters/decoys,
  implicit-DDL restartability, plain-TEXT JSON, no FK/CHECK, explicit resolved
  collation, the 27-byte catalogue ceiling and DDL-free runtime consumers.
- The package remains ownership-only and PILOT_ONLY; unresolved taxonomy,
  outcome, correction/retention, cutover and financial semantics stay
  `NEEDS_GRILL` rather than becoming target requirements.
- Tasks preserve the required Gate order: executable spec/owner approval, RED,
  fresh independent test review, minimal GREEN, regression/architecture/full
  verification and fresh independent code review. RED and implementation remain
  forbidden while predecessors and Gate 1 approval are absent.
- Keeping the migration version and exact catalogue position symbolic is
  consistent with the authoritative predecessor state.

## Verdict

`CHANGES_REQUESTED`

The technical scope is coherent and strict validation passes, but the two
mandatory artifact-instruction omissions above must be corrected. A fresh
independent reviewer must assess the revised planning package; this review must
not be converted to approval by the artifact author.

## Verification

- `openspec validate canonicalize-migration-quarantine-schema --strict` — PASS
- `git diff --check` — PASS
- `make architecture-check` — PASS (`6 rules`)


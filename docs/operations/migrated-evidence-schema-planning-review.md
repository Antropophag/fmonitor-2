# Independent migrated-evidence schema planning review

Reviewed: 2026-09-01  
Change: `canonicalize-migrated-evidence-schema`  
Reviewer: fresh independent agent `migrated_evidence_planning_review_0901r`  
Verdict: **READY_WHEN_PREDECESSORS_LAND**

## Sources inspected

- `AGENTS.md`, `PRODUCT.md`, `CONTEXT.md`, and
  `docs/development-process.md`;
- `docs/operations/migrated-evidence-schema-evidence.md` and both independent
  evidence reviews;
- all four OpenSpec planning artifacts and the current OpenSpec status and
  artifact instructions;
- the three runtime DDL owners, importer/batch/backfill entry points, and the
  relevant reconciliation, historical-premium and OTIZ consumers;
- `docs/operations/runtime-ddl-migration-plan.md` and current canonical
  migration fingerprint/test conventions.

## Review result

1. **Scope and family are coherent.** Proposal, delta spec, design and tasks
   consistently cover only the exact six members documented by evidence:
   `fm2_history_source_snapshots`, `fm2_history_import_quarantine`,
   `fm2_migrated_evidence_projection`, `fm2_migrated_evidence_conflicts`,
   `fm2_migrated_evidence_decision_state`, and
   `fm2_migrated_evidence_decisions`. The 28-byte shared prefix ceiling is
   retained and no table, FK, import, backfill, reconciliation or premium
   semantic redesign is introduced.
2. **Compatibility is sufficiently specified for planning.** The package
   requires family-wide preflight before DDL, all 64 independently
   absent/exact-compatible states, separate incompatible-member and semantic
   conflict cases, and preservation of schemas, ordered rows, counters,
   migration ledger and ambient decoys. This matches MariaDB per-statement
   implicit-commit recovery rather than assuming atomic DDL.
3. **Collation and JSON semantics remain exact without freezing an observed
   environment.** The two inherited-default tables and four explicit-utf8mb4
   tables resolve separate validated collation sources. The projection JSON
   alias retains binary collation and semantic `json_valid` CHECK while
   generated presentation names remain non-normative; other LONGTEXT JSON
   payloads do not acquire new checks.
4. **Runtime cutover is represented.** Importer, projection/backfill store,
   decision ledger and the OTIZ constructor chain become DDL-free and fail
   closed on absent/incompatible schema. Gate 1 and RED tasks require coverage
   of DDL-denied consumers, while focused import, reconciliation, decision,
   historical-premium and OTIZ regressions protect the existing data behavior.
   Gate 1 should enumerate the exact entry-point/method matrix from the evidence
   so the shared precondition is not proven only through one constructor chain;
   this is already within task 1.1 and is not a planning blocker.
5. **Dependencies and delivery gates are not bypassed.** The literal next
   migration version and catalogue order remain deliberately unresolved until
   the earlier canonical families actually land. Tasks then require an exact
   Gate 1 specification, fresh technical review, explicit owner approval,
   demonstrated RED by a fresh author, a different independent test reviewer,
   minimal GREEN, regression/architecture/full verification, and a fresh
   independent code review before sync/archive. No RED or implementation task
   is marked complete or authorized by this planning verdict.
6. **Done and architecture are aligned.** Done requires the runtime-DDL debt
   ratchet to decrease for the six family CREATE statements without new
   exceptions/hotspots, preservation evidence, updated operations inventory,
   forward-only recovery, strict verification and explicit Gate 5 approval.

## Blocking condition

Planning is ready, but Gate 1 literalization and all later delivery work remain
`BLOCKED_PREDECESSORS`. After the real predecessors land, refresh the exact
catalogue, symbolic-next version and current 28-byte ceiling before asking for
owner approval. This verdict does not approve Gate 1, RED, implementation, data
backfill or migrated-evidence admission semantics.

## Verification

- `openspec validate canonicalize-migrated-evidence-schema --strict` — PASS
- `git diff --check` — PASS
- `make architecture-check` — PASS (`6 rules`)


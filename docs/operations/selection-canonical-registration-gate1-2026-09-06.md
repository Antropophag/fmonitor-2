# Independent Gate 1 review — SELECTION-CANONICAL-REGISTRATION-001

- Verdict: **APPROVED**
- Reviewer: `/root/selection_native_review` (`gpt-5.6-sol`, `low`; separately tasked, did not author the specification or planning notes)
- Reviewed commit: `b42cc9b8cd1465fda66d5389901f4b698b687231`
- Specification SHA-256: `727be0b6302f6b33458201025263480b64cf5ddaf7c47f3f137218dbe58d9add`
- Verified runner source commit: `d1bcddc`
- Current runner SHA-256: `55de833cdd3db3d60a00ac0f711639c7a5f5b44b6a87d9b1c5230ed4b93f6c70`
- Registry tasks SHA-256: `9dbcb9715c2e8a26310ae13668b9fe5370de2b82ce16e481e8adbe34408cfb36`
- Selection tasks SHA-256: `4a7dc1fbced6f1332baa04ac58a314ee1d1e349f96f4e70c355209889dd94f52`
- Review date: 2026-09-06

## Findings

No blocking ambiguity or contradiction was found.

The actual canonical catalogue is contiguous through v13, whose owner is `OriginalAttemptAuditSchemaMigration`. Assigning v14 to `AssignmentOrderIdentityRegistryMigration::apply` and v15 to `AssignmentOrderSelectionSchemaMigration::apply` is therefore exact and preserves dependency order: selection cannot run without the registry family it references. The contract requires rechecking this frontier before implementation and review amendment if it moves.

The public CLI outcomes match `CanonicalMigrationApplication`: successful fresh setup reports versions 1–15; canonical v13 reports only 14/15; registry-only recovery reports 15; and a full repeat reports none. A conflict returns immediately with exit 2 and the exact current migration version, so v14 conflict cannot invoke selection and v15 conflict cannot report successful version 15. Existing database/config/unexpected-failure exit mappings remain sanitized.

Postconditions are observable through the approved public registry completion and selection readiness facades plus schema fingerprints and row/frontier snapshots. The contract preserves receipts and AUTO_INCREMENT frontier, forbids repair and fact creation, and requires repeat row/receipt identity. Its RED matrix uses the real CLI while retaining engine-level matrices rather than duplicating them.

The production change is limited to imports and ordered registration of the two already approved engines. No schema definition, validator, command, application writer, migration version gap, preview state, route, or UI activation belongs to this slice. The fresh-launch override removes legacy-writer conversion and mixed-rollout prerequisites; it does not weaken append-only behavior or permit old writers in the new portal.

The two OpenSpec task notes accurately record the fresh frontier and leave all checkboxes open pending this slice's own gates. Consumer updates are limited to expectations whose advertised final version or `appliedVersions` necessarily changes.

## Gate decision

Gate 1 is **APPROVED** for `SELECTION-CANONICAL-REGISTRATION-001` at the exact commit and hashes above. Gate 2 may author the bounded real-CLI RED and controls in section 4. No runner implementation is authorized until demonstrated RED and independent Gate 3 approval. Portal activation, full verification, CI, deployment, restart, and golden-path work remain separate.

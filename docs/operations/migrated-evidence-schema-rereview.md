# Independent migrated-evidence schema evidence rereview

Reviewed: 2026-09-01  
Artifact: `docs/operations/migrated-evidence-schema-evidence.md`  
Prior review: `docs/operations/migrated-evidence-schema-review.md`  
Reviewer: fresh independent agent `migrated_evidence_schema_rereview_0901q`  
Verdict: **READY_FOR_OPENSPEC**

## Sources inspected

- the current evidence artifact and prior independent review;
- `rapid-pilot/legacy-migration/LegacyHistoryMigration.php`;
- `rapid-pilot/legacy-migration/MigratedEvidenceProjectionStore.php`;
- `rapid-pilot/legacy-migration/MigratedEvidenceDecisionLedger.php`;
- `rapid-pilot/legacy-migration/MigratedEvidenceReconciliation.php`;
- `rapid-pilot/import-legacy-history.php`,
  `rapid-pilot/batch-import-legacy-history.php`, and
  `rapid-pilot/backfill-migrated-evidence-projection.php`;
- direct OTIZ, historical-premium, reconciliation, workforce-profiling and
  verification references found by exact class/table search;
- `docs/operations/runtime-ddl-migration-plan.md` and current canonical
  migration fingerprint conventions.

## Closure of prior findings

1. **MariaDB semantic fingerprint — closed.** The evidence now records the
   observed server version, engine, semantic defaults and `EXTRA`, the three
   auto-increment counters after populated inserts, full-column ascending
   visible BTREE index semantics, absence of foreign keys, and the MariaDB
   `JSON` expansion to binary-collated `LONGTEXT` plus the semantic
   `json_valid(conflict_codes_json)` CHECK. The six ordered source manifests
   retain the exact column and index compositions needed for planning.
2. **Conditional charset/collation truth — closed.** The artifact correctly
   separates four source DDLs that request `utf8mb4` without an explicit
   collation from the two tables that inherit both database charset and
   collation. It labels the concrete collation names as an isolated
   environment observation and requires future migration planning to resolve
   and emit both policies explicitly rather than freezing or normalizing the
   observed names.
3. **Backfill atomicity — closed.** The evidence identifies the caller's
   pre-existing transaction, the implicit commit caused by `CREATE TABLE IF
   NOT EXISTS`, and the resulting independently interruptible projection and
   decision-state mutations. It keeps restartability/atomicity of data
   backfill outside schema ownership.
4. **Consumers and behavioral prerequisites — closed.** All three direct CLI
   entry points and the reconciliation, projection, decision, OTIZ,
   historical-premium and workforce-profiling consumers are named. Physical
   absence of foreign keys is distinguished from optional workforce
   enrichment and identity/access plus broad `otiz.manage` authorization
   dependencies.
5. **Compatible-state/conflict preservation matrix — closed.** The contract
   explicitly requires exactly `2^6 = 64` independently absent-or-exact
   compatible family states. Incompatible member positions and representative
   fingerprint conflicts are separate cases, with family schemas, rows,
   counters, migration ledger and ambient decoys all required unchanged.

## Rereview conclusion

The corrected evidence is internally consistent with the current runtime DDL
owners and consumers. It remains an ownership-only, non-semantic discovery,
keeps data backfill/reconciliation out of migration execution, preserves the
28-byte prefix ceiling, and accurately leaves implementation blocked on
predecessor migrations and final catalogue ordering. No new blocking error was
found. The evidence may proceed to an OpenSpec planning package; this verdict
does not approve RED or implementation.

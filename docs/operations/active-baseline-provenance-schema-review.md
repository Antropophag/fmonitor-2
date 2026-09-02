# Independent active-baseline/provenance schema evidence review

Date: 2026-09-02  
Reviewer: fresh agent `active_provenance_schema_review_0902a`  
Artifact: `docs/operations/active-baseline-provenance-schema-evidence.md`

## Scope and method

This was an evidence review only. I did not author or modify the evidence,
runtime code, verifier code or an OpenSpec artifact. I checked the document
against the authoritative dirty worktree, including:

- `LegacyMigrationRouter.php`, especially `LegacyActiveBaselineTarget` and
  `MigrationClassificationProvenanceTarget`;
- `ActiveBaselineOperationalCaseConnector.php` and the checklist-template
  association owner it invokes;
- native import, historical import, active-baseline import and connector entry
  points;
- queue, Pilot HTTP, OTIZ/native-premium, template-linking, profiling,
  projection and native-generation consumers;
- focused active-baseline/connector/projection verifiers, the runtime-DDL plan,
  current TEST-USER status and GRILL-004 scope;
- the recorded isolated MariaDB 11.4.7 manifest observation and literal source
  DDL for all three tables.

This review asks only whether the ownership discovery is accurate and complete
enough for planning. It does not approve a release contour, migration behavior,
legacy cutover, classification taxonomy, Gate 1, RED or implementation.

## Findings

No blocking findings.

1. **The asserted split is required by actual reach.**
   `fm2_migration_classification_provenance` is written for
   `operational_case`, `historical_snapshot` and `active_baseline` outputs. The
   existing native-candidate import constructs this owner, and queue, native
   premium, template-linking, profiling and native-generation paths consume its
   operational-case rows. In contrast, `fm2_legacy_active_baselines` and
   `fm2_active_case_provenance` exist specifically to capture and connect the
   optional legacy-active cutover. Keeping classification provenance as a
   release-supporting one-table slice and making the two-table legacy-active
   family conditional avoids both false coupling directions. The latter may
   depend on the former; that does not make them one deployment unit.

2. **Owners and exact manifests match the source DDL.** The evidence preserves
   ordered columns, signedness, lengths, NOT NULL shape, AUTO_INCREMENT only on
   classification and baseline IDs, and all primary/unique/secondary index
   compositions. It correctly records no foreign keys, no CHECK constraints,
   no semantic column defaults and plain TEXT/LONGTEXT JSON payload storage.
   InnoDB plus requested `DEFAULT CHARSET=utf8mb4` without an explicit collation
   is source-accurate.

3. **The MariaDB observation is properly bounded.** The observed
   `utf8mb4_uca1400_ai_ci` resolution is identified as environment evidence,
   not portable target approval. Populated literal readability, visible
   full-column ascending BTREE indexes and next counters of `2` for both
   AUTO_INCREMENT tables cover the semantic facts needed to plan
   populated-preserving ownership transfer. Future planning still has to make
   the environment/default preflight and emitted collation policy executable.

4. **The 25-byte prefix ceiling is correct for the current ASCII-only prefix
   contract.** The longest basename,
   `fm2_migration_classification_provenance`, is 39 ASCII bytes; MariaDB's
   64-character identifier limit leaves 25 ASCII prefix characters/bytes.
   This is stricter than all earlier 27/28/29/32-byte catalogue drafts and must
   be reconciled globally before an affected Gate 1 can be approved.

5. **Transaction and failure-window claims match execution order.** Baseline
   DDL precedes its data transaction. Connector inspection precedes apply;
   active-case DDL precedes its data transaction, while installation-case,
   operational template-association and provenance inserts are intended to
   share that transaction. The association target's remaining runtime DDL can
   implicitly commit the chain. Classification reconciliation neither opens
   nor joins the preceding output transaction and runs after output creation,
   so output-without-provenance is a real interruption/failure window. The
   evidence correctly requires planning to characterize and close or
   explicitly preserve that window rather than silently claiming atomicity.

6. **Dependencies and release classification are bounded correctly.** The
   classification table must precede the current import-backed native case
   contour, while the two legacy-active tables require canonical process and
   checklist-template ownership and remain conditional on GRILL-004/cutover.
   Physical absence of FKs is not confused with these application-level
   ordering dependencies. Literal categories, reason codes, fabricated
   `working` state and cutover admission remain PILOT_ONLY evidence, not target
   financial or process semantics.

7. **The proposed verification surface covers the ownership risk.** Exact
   fingerprints, populated preservation, compatible absent/present states,
   independent conflicts, counter preservation, runner ordering, DDL-denied
   consumers and interrupted implicit-commit recovery are appropriate future
   executable requirements. Keeping the connector's fabricated opening facts
   and data backfill behavior in separate behavior work prevents an ownership
   migration from approving domain semantics accidentally.

## Residual planning cautions (non-blocking)

- Replace the runtime plan's single three-table backlog item with the split
  explicitly; leaving both the old combined name and the new packages active
  would make ordering and Done accounting ambiguous.
- The classification OpenSpec should name every current output kind as observed
  PILOT_ONLY compatibility, while its executable migration contract should test
  schema states independently of approving that taxonomy.
- The optional legacy-active package should not enter RED merely because its
  evidence is ready. GRILL-004/cutover selection and the normal owner Gate 1
  approval remain separate prerequisites.
- Exact migration versions and predecessor numbers must stay symbolic until
  preceding canonical migrations land.

## Verdict

`READY_FOR_OPENSPEC`

The evidence is source-traceable, the one-table/two-table boundary reflects
real owners and consumers, and the MariaDB, prefix, transaction and dependency
claims are sufficiently exact for planning-only OpenSpec work. This verdict
does not authorize RED or implementation.

## Verification

- `git diff --check` — PASS
- `make architecture-check` — PASS (6 rules)

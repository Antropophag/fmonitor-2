# Runtime DDL ownership inventory and canonical migration plan

Evidence snapshot: 2026-09-02. This is an ownership plan, not approval of pilot
semantics or of the schemas copied below. The canonical production runner is
`bin/fmonitor2-migrate.php`; it currently ends at schema version 11.

## Scope and evidence limits

- Canonical v1 owns installation cases, assignment orders, order participants,
  order artifacts, process tasks and process events.
- Canonical v2 owns `fm2_workforce_catalog`; v3 creates
  `fm2_process_user_capabilities`; v4 changes that table's capability check.
- `tools/architecture/baseline.json` records 40 non-canonical production DDL
  statements. That number is a ratchet, not a table count.
- `tools/architecture/check.py` deliberately excludes the directory
  `rapid-pilot/legacy-migration/`. The runtime DDL there is therefore inventoried
  below but is not represented by the 40-statement baseline.
- Test/verifier fixture DDL is outside this plan. `bin/fmonitor2-pilot-demo.php`
  and the legacy fixture table in `rapid-pilot/docker-bootstrap.php` are
  environment setup, not production schema ownership. They must consume the
  canonical process migrations rather than be copied into them.
- Existing `CREATE TABLE IF NOT EXISTS` is not sufficient compatibility
  evidence. Each additive migration needs the v1-v4 pattern: literal schema
  fingerprint, conflict result, clean-DB and already-present verification.

## Runtime-owned table families

| Family and tables | Current runtime owner / call sites | Related behavior | Dependency and release criticality | Existing characterization / verifier | Proposed OpenSpec change |
|---|---|---|---|---|---|
| Workforce history candidate v5: `fm2_workforce_observations`, `fm2_workforce_sync_runs`, `fm2_workforce_sync_metadata`, plus additive changes to canonical `fm2_workforce_catalog` | `app/InstallationProcess/BitrixWorkforceHistorySchemaMigration.php` has a strict `schemaVersion => 5` contract, but it is absent from `bin/fmonitor2-migrate.php`; instead `rapid-pilot/docker-bootstrap.php` and `import-production-installers.php` invoke it directly | dated кадровый source observations/sync evidence needed by assignment and inspection attribution | Depends on canonical v2 and must be registered before other new versions. Release-critical because a clean canonical run currently stops before tables used by the importer | `tests/InstallationProcess/bitrix_workforce_schema_001_test.php` covers clean, repeat, partial and conflict forms; runner-order/clean-checkout coverage is still required | `register-workforce-history-canonical-v5` |
| Identity/access: `fm2_pilot_users`, `fm2_pilot_roles`, `fm2_pilot_role_permissions`, `fm2_pilot_user_roles`, `fm2_pilot_auth_credentials`, `fm2_pilot_invitations`, `fm2_pilot_user_role_events`, `fm2_pilot_auth_attempts`, `fm2_pilot_user_status_events` | `rapid-pilot/IdentityBootstrap.php::rebuild`; status-event table is still created on the request path in `rapid-pilot/UserAccessView.php` | login/invitation, role grants, block/unblock, exact capability derivation | Depends only on an empty process namespace; required before test-user authorization can be claimed. Destructive `rebuild` remains an explicit bootstrap operation, never a canonical migration | `verify-auth-hot-path.php` plus identity/access verifiers reached by the rapid-pilot suite; add clean-schema fingerprint and block/unblock-without-DDL cases | `canonicalize-identity-access-schema` |
| Checklist template binding: `fm2_checklist_template_snapshots`, `fm2_checklist_template_associations` | `rapid-pilot/legacy-migration/LegacyChecklistTemplateSnapshot.php`, `ChecklistTemplateAssociation.php`; consumed by native checklist binding and checklist sync | immutable checklist definition and binding for PB-04/PB-05 | Must precede checklist operations. Release-critical because item facts must name their template. Directory is excluded from the architecture baseline | `verify-native-checklist-template-binding.php`, `verify-active-baseline-case-connector.php`; add schema fingerprint and pre-existing-conflict cases | `canonicalize-checklist-template-schema` |
| Inspection evidence: `fm2_checklist_revisions`, `fm2_checklist_operations` (including the three template columns), `fm2_checklist_operation_installers` (including `assignment_source`), `fm2_checklist_photos` | request-reachable `app/PilotHttp/ChecklistSync.php::ensureSchema`; invoked by checklist HTTP flows and directly by verifiers | PB-04 item completion/attribution and PB-05 photo attach/revoke | Depends on canonical process v1, workforce v2 and checklist-template schema. Release-critical; first implementation dependency for calibration and photo slices | `verify-checklist-offline-behavior.mjs`, `verify-checklist-current-crew.php`, `verify-native-checklist-template-binding.php`, `verify-checklist-offline-prefetch.php`; preserve upgrade characterization for both current `ALTER` additions | `canonicalize-inspection-evidence-schema` |
| Inspection planning: `fm2_pilot_inspection_schedules`, `fm2_pilot_inspection_schedule_events` | `rapid-pilot/InspectionSchedule.php::ensureSchema`; called by scheduling and `rapid-pilot/Calendar.php`, and during docker bootstrap | PB-06 schedule inspection and calendar projection | Depends on process v1 and registered assignment data. Table ownership is release-critical; schedule semantics remain blocked by GRILL-001 and need not block moving the unchanged schema | `verify-calendar-projections.php` is indirect and currently has a known bootstrap regression; add focused duplicate/date/event characterization before migration | `canonicalize-inspection-planning-schema` |
| Installation completion: `fm2_pilot_completion_facts` | request-reachable `rapid-pilot/CompletionFlow.php::ensureSchema` | PB-07 PTO/declaration facts | Depends on process v1 and inspection evidence. Potentially release-critical, but the accepted fact types/correction model determine the durable schema | `verify-completion-flow.php`; add schema conflict and append-only correction scenarios after product decision | `canonicalize-installation-completion-schema` |
| Pilot object detail snapshots: `fm2_pilot_object_details`, `fm2_pilot_object_detail_quarantine` | top-level DDL in `rapid-pilot/import-production-object-details.php` | object-card input snapshot/quarantine used by native projections and premium operands | Depends on imported legacy object identity; release-supporting, not a critical state-changing seam. Can follow inspection-critical tables | native operational/live scenario verifiers exercise consumers; add importer clean/repeat/conflict characterization | `canonicalize-object-detail-snapshot-schema` |
| Premium decisions/payment closure: `fm2_pilot_otiz_snapshots`, `fm2_pilot_otiz_snapshot_objects`, `fm2_pilot_otiz_snapshot_allocations`, `fm2_pilot_otiz_snapshot_issues`, `fm2_pilot_otiz_snapshot_evidence`, `fm2_pilot_otiz_payment_closures`, `fm2_pilot_otiz_events` | request-reachable `rapid-pilot/Otiz.php::ensureSchema`, including conditional runtime `ALTER ... ADD UNIQUE KEY unique_reversal` | PB-08 through PB-12 | Depends on process, workforce, checklist, completion, object details and migrated evidence. Not release-critical if owner accepts preview-only recommendation; otherwise critical and blocked on financial semantics/authorization | `verify-premium-calculation.php`, `verify-native-operational-otiz-inputs.php`, `verify-native-operational-live-scenario.php`, `verify-otiz-workflow.php`; retain characterization of upgrading a closure table without `unique_reversal` | `canonicalize-premium-decision-schema` |
| Classification provenance: `fm2_migration_classification_provenance` | Canonical v11 migration owns schema; `MigrationClassificationProvenanceTarget` in `rapid-pilot/legacy-migration/LegacyMigrationRouter.php` owns DML after read-only readiness | immutable routing proof for native operational cases and historical snapshots | Landed release-supporting dependency for the import-backed native-only TEST-USER contour, independent of selecting legacy-active cutover | exact schema/race/prefix/runtime readiness verifier plus native-only, object queue/origin, template, native OTIZ and import regressions are GREEN; fresh Gate 5 rereview APPROVED | `canonicalize-classification-provenance-schema` — DONE |
| Legacy-active source/binding provenance: `fm2_legacy_active_baselines`, `fm2_active_case_provenance` | `LegacyActiveBaselineTarget`, `ActiveBaselineOperationalCaseConnector` | active-baseline capture and connection into canonical cases | Depends on process v1, classification provenance and checklist-template binding; promote only if GRILL-004 selects legacy-active cutover | `verify-active-baseline-read-model.php`, `verify-active-baseline-case-connector.php`, `verify-migrated-active-process-projection.php` | `canonicalize-active-baseline-provenance-schema` |
| Historical evidence ingestion/projection: `fm2_history_source_snapshots`, `fm2_history_import_quarantine`, `fm2_migrated_evidence_projection`, `fm2_migrated_evidence_conflicts`, `fm2_migrated_evidence_decision_state`, `fm2_migrated_evidence_decisions` | `rapid-pilot/legacy-migration/LegacyHistoryMigration.php`, `MigratedEvidenceProjectionStore.php`, `MigratedEvidenceDecisionLedger.php` | PB-13 migrated evidence reconciliation/decision ledger | Depends on legacy source snapshots; DEMO_ONLY/migration control under current inventory, so after release-critical families unless premium preview consumes it | migrated-evidence projection/reconciliation/differential/decision-ledger verifiers | `canonicalize-migrated-evidence-schema` |
| Migration quarantine: `fm2_migration_quarantine_registry`, `fm2_migration_quarantine_observations`, `fm2_migration_quarantine_decisions` | `rapid-pilot/legacy-migration/MigrationQuarantineRegistry.php`, `MigrationQuarantineDecisionLedger.php` | PB-13 quarantine registration and audit decisions | Independent of operational state except actor identifiers; DEMO_ONLY/migration control | `verify-migration-quarantine-registry.php`, `verify-migration-quarantine-read-model.php`, decision-ledger coverage through OTIZ workflow | `canonicalize-migration-quarantine-schema` |
| Generation sentinel: `fm2_pilot_generation_sentinel` | `rapid-pilot/docker-bootstrap.php`; generation reset also drops canonical capability data and legacy fixtures | disposable environment generation/fingerprint, not product behavior | Setup-only. Keep in a test/bootstrap namespace or a separately named environment migration; do not put destructive reset/drop behavior in the production runner | docker bootstrap and generation checks in the harness; add explicit repeat/reset contract if retained | `separate-pilot-generation-metadata` |

`rapid-pilot/import-production-installers.php` also performs runtime
`ALTER TABLE ... CONVERT TO CHARACTER SET` on canonical v2
`fm2_workforce_catalog` immediately before invoking the unregistered v5 class.
The v5 compatibility contract requires the database-default collation, so the
importer currently repairs schema before the migration can inspect it. Move any
approved conversion into explicit canonical migration logic or fail closed;
the importer must not silently own it. Track this in
`register-workforce-history-canonical-v5`, using the existing collation-conflict
characterization.

## Ordered additive migration backlog

The numbers below are dependency order, not pre-approved permanent schema
version numbers. A slice may combine several successive runner versions, but it
must remain additive and independently verifiable.

1. **`register-workforce-history-canonical-v5`** — register the existing strict
   v5 class in the production runner, decide the explicit collation upgrade
   policy, and remove direct schema invocation/repair from importer and docker
   bootstrap. This is a runner ownership gap, not permission to renumber or
   rewrite the already-characterized schema silently.
2. **`canonicalize-identity-access-schema`** — move all nine identity/access
   tables into canonical ownership; keep destructive seed/rebuild outside the
   migration. Remove request-path creation only after clean and populated schema
   verification is green.
3. **`canonicalize-checklist-template-schema`** — canonicalize immutable
   snapshots and associations before operational checklist tables.
4. **`canonicalize-inspection-evidence-schema`** — reproduce both fresh and
   already-upgraded forms of the current checklist schema, then remove all DDL
   from `ChecklistSync`. This is the highest-value operational migration for the
   calibration and photo slices.
5. **`canonicalize-inspection-planning-schema`** — move both scheduling tables;
   this ownership-only slice can proceed while scheduling product semantics are
   in GRILL, provided it does not assert those semantics.
6. **`canonicalize-installation-completion-schema`** — execute only after the
   completion GRILL decides whether correction/supersession needs extra columns
   or facts. Do not freeze the current two-value enum by accident.
7. **`canonicalize-object-detail-snapshot-schema`** — remove importer-owned DDL
   without redesigning storage.
8. **`canonicalize-premium-decision-schema`** — promote the seven OTIZ tables
   only for the approved release scope; preserve the reversal uniqueness upgrade
   as explicit migration logic, not request-time repair.
9. **DONE: `canonicalize-classification-provenance-schema` (canonical v11)** —
   the native operational import proof is canonically owned before import-backed
   TEST-USER bootstrap. Its 39-byte basename sets the current full-catalogue
   prefix ceiling to 25 bytes. Slice verification and fresh Gate 5 rereview are
   GREEN; unrelated repository-wide intentional REDs remain outside this item.
10. **`canonicalize-active-baseline-provenance-schema`** — only the baseline and
   active-case tables, and only if fresh test deployment consumes active legacy
   baselines under GRILL-004.
11. **`canonicalize-migrated-evidence-schema`** then
   **`canonicalize-migration-quarantine-schema`** — migration-control families;
   keep behind operational slices unless premium preview or fixture cutover
   proves them necessary.
12. **`separate-pilot-generation-metadata`** — last, because it improves harness
    ownership but does not make a state-changing user path safe.

Every slice Done definition includes: OpenSpec artifacts; approved executable
schema contract; demonstrated RED; independent test review; additive migration
and runner registration; clean schema, safe repeat, compatible-present and
incompatible-present DB cases; relevant behavior characterization; removal of
the corresponding runtime DDL; `make architecture-check`; regression/full
verification; independent code review. No slice may update the architecture
baseline merely to hide moved or changed DDL.

## Exact UNKNOWN / GRILL blockers

1. **Completion fact model (blocks only
   `canonicalize-installation-completion-schema`).** Is the durable model really
   one immutable `pto_act` and one immutable `declaration` per case, or must a
   correction supersede an earlier fact? Is declaration mandatory for completed
   state, and is pilot 85/15 accepted? Current product material does not answer
   these questions; copying `UNIQUE(case,fact_type)` plus the enum could prevent
   the recommended append-only correction model.
2. **Financial release scope and meanings (blocks production promotion of
   `canonicalize-premium-decision-schema`, not pure calculation
   characterization).** Are acceptance and payment/discipline/deadline closure
   state-changing release capabilities? What does closure mean, what evidence is
   mandatory, and which roles may calculate, accept, close and reverse? These are
   GRILL-001 questions 4-6.
3. **Inspection cadence does not block DDL ownership.** GRILL-001 question 1
   blocks `INSPECTION-SCHEDULE-001` behavior, but the two existing scheduling
   tables can be moved unchanged because doing so asserts no cadence,
   reschedule, cancellation or overdue rule.
4. **Migration tooling release need (decision needed only before backlog items
   8-9).** Will the fresh test environment be seeded from active/historical
   legacy evidence, or from stable native fixtures? Native fixtures allow these
   DEMO_ONLY schemas to remain post-release debt; a legacy cutover makes the
   applicable provenance/quarantine tables deployment-critical.

No product decision blocks workforce-v5 registration, identity,
checklist-template, inspection-evidence or object-detail schema ownership
discovery. Those slices can enter READY after their OpenSpec/schema contracts
and independent test assignments exist.

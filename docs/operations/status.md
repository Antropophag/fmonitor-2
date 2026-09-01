# TEST-USER-READY operations status

Срез: 2026-09-02. Целевая дата: 2026-09-09.

## Status

**AMBER / bootstrap operational, release path incomplete.** OpenSpec, architecture ratchet and reproducible test DB/migration harness работают. Critical inspection/completion/OTIZ paths ещё принадлежат rapid-pilot; runtime DDL и UI-owned SQL остаются baselined migration debt.

## DONE

- Catalogue-wide process-prefix reconciliation is independently accepted as
  `READY_FOR_PLANNING_UPDATES`. The release-supporting 39-byte
  `fm2_migration_classification_provenance` basename fixes the future composed
  process-prefix ceiling at 25 bytes with 26 rejected before DB access. The
  current record also inventories the approved/pending 32-byte runner and
  composition contracts, preserves the workforce family's direct 37/38
  boundary, and keeps the independently routed legacy prefix outside this
  process-catalogue narrowing. Historical evidence/reviews remain unchanged.
- Owner-authorized prefix updates to classification-provenance,
  inspection-planning, migrated-evidence and migration-quarantine OpenSpec
  changes are complete and independently accepted as
  `READY_WHEN_PREDECESSORS_LAND`. All four use composed 25-byte success / 26-byte
  rejection before DB connection/access and retain wider family-local arithmetic
  only as direct-family evidence. No Gate 1, RED or implementation was implied.
- Owner decision `APPROVED_COMPOSE_CONTOUR` resolves GRILL-008: Compose
  `make up` is the TEST-USER release contour; the standalone CLI remains only a
  separately isolated synthetic fixture harness. The append-only decision record
  is `docs/operations/test-user-release-contour-decision.md`. Generation OpenSpec
  revisions still require their workflow confirmation. GRILL-004 is now also
  resolved by a separate synthetic/native-data and ownership-checked reset
  decision; all Gate 1/RED/implementation approvals remain separate.
- Owner decision `APPROVED_SYNTHETIC_NATIVE_RESET_POLICY` resolves GRILL-004 for
  the first TEST-USER contour: deterministic fictional native fixtures only,
  no real personal data or legacy cutover, state-preserving `make up`, and
  explicit `make reset` limited to ownership-proved Compose resources. Fixture
  seed Gate 1 remains separate and no destructive production behavior is
  approved.

- Repository audit with primary-source citations.
- OpenSpec 1.10 lifecycle configured and linked to mandatory SSD/TDD gates.
- Short root constitution/navigation; rapid-pilot new-domain-logic exception removed.
- Six-rule architecture guard with current debt/hotspot/public-seam baseline.
- Disposable MariaDB up/down/reset and canonical v4 migration verified on a clean DB.
- Initial inventory (13 capabilities), 7-module map, vertical backlog and GRILL-001.
- Calendar Grid approved dependency pin restored; fresh independent Gate 5 review approved.
- Calendar characterization bootstrap/cleanup restored; fresh independent harness review approved.
- Production process directory now supports the canonical local-RBAC directory while preserving the legacy v4 contract; fail-closed compatibility implementation independently approved.
- Ownership discovery completed for identity/access (9 tables), checklist templates (2 tables), and inspection evidence (4 tables); exact ordering and migration acceptance scenarios captured.
- `canonicalize-checklist-template-schema` OpenSpec proposal/spec/design/tasks created and strict validation passes.
- `canonicalize-inspection-evidence-schema` OpenSpec proposal/spec/design/tasks created and strict validation passes; it preserves both observed additive upgrade forms and explicitly defers unresolved correction/photo semantics.
- `SECURITY-GLOBAL-CALL-RATCHET-001` restored: all PilotHttp global runtime calls are explicitly qualified, unit suite is green, and fresh independent Gate 5 review approved.
- Architecture debt fingerprints are now PHP-token-aware, stable across global-call qualification, sensitive to literals/comments/class and attribute identity, and independently approved through adversarial RED/GREEN/review; debt counts remain unchanged.
- `HARNESS-OTIZ-ISOLATION-001` completed through RED, independent test review, GREEN and two independent code/integration reviews. Characterization now runs the OTIZ workflow twice from private native fixtures, proves blocked→explicit evidence→ready, preserves ambient decoys and leaves no tables behind.
- `HARNESS-FULL-AGGREGATION-001` completed through RED, independent test-review iterations, GREEN and fresh code review. `make verify` reports every ordered stage, classifies setup failures, skips setup-blocked DB/E2E work explicitly, and ends with one machine-readable aggregate result.
- `HARNESS-CANONICAL-MIGRATION-STAGE-001` upgraded that contract to nine stages through fresh RED/review/GREEN/review: clean reset is followed by the real canonical runner, `make migrate` is non-destructive/idempotent, and reset/migration failures explicitly block only DB-dependent stages.
- `HARNESS-OTIZ-CANONICAL-COMPAT-001` completed after the migration stage exposed an integration regression. OTIZ characterization now runs twice beside canonical v1-v4 schema/sentinel facts, preserves canonical state, cleans partial/fault-injected fixtures, and is independently approved.
- `CHARACTERIZE-INSPECTION-PHOTO-UPLOAD-001` completed through RED/review/GREEN/review. The pilot oracle now deterministically proves accepted upload, exact replay idempotency and retryable atomic storage failure through public `ChecklistSync` seams; SQL/storage namespaces are safe under sequential and concurrent agents. Authorization/revoke/dimensions/caption remain explicitly outside this characterization.
- `HARNESS-FRESH-TEST-LIFECYCLE-001` completed through RED/review/GREEN/review. `make fresh-test-verify` delegates once to authoritative verification, always runs public teardown on normal completion, preserves failure evidence/status markers, and leaves Compose empty; lower-level `make verify` remains available for investigation.
- `HARNESS-IMAGE-CANONICAL-RUNNER-001` completed through built-image RED/review/GREEN/review. The non-root pilot image now packages the unchanged `bin/fmonitor2-migrate.php`; invoking it in-image proves exact configuration failure semantics, while entrypoint/startup remain unchanged and no sensitive repository trees are added.
- `CHARACTERIZE-INSPECTION-PHOTO-REJECTIONS-001` completed through Gate 1, three RED/review iterations, GREEN and fresh Gate 5 approval. The canonical characterization stage now proves four stable zero-mutation rejection boundaries (MIME/content, size, SHA-256 and control-character filename), deterministic transcript, bounded concurrent-safe namespaces, cleanup/decoy preservation, and distinct setup-vs-regression classification including real MariaDB DDL denial. Two independent reviews caught and closed missing-directory and fixture-failure sensitivity gaps; no target authorization/revoke/10-photo semantics were promoted.
- `CHARACTERIZE-INSPECTION-PHOTO-LIMIT-CONCURRENCY-001` completed all five gates with fresh agents and final Gate 5 approval. The PILOT_ONLY oracle uses two real child processes/connections and a start barrier to prove winner-neutral 9→10 serialization, exactly one rejected overflow with zero loser DB/blob mutation, and same-content-at-cap duplicate behavior. Three Gate 2 review iterations closed echo-only, collision/failure cleanup, timeout/reaping and invalid-UUID sensitivity gaps; Gate 5 removed an accidental exact Russian-message pin. The characterization stage now has 13 green checks, with target limit/overflow/replay semantics isolated in GRILL-006.
- `CHARACTERIZE-INSPECTION-PHOTO-REVOKE-001` completed all five gates with fresh independent review. The PILOT_ONLY oracle proves upload→revoke revision/history/blob retention, projection omission, exact sequential replay, already-revoked zero mutation and the current SQLSTATE `23000`/vendor `1062` identical-content re-upload failure without pinning exception text. Gate 3 caught and closed missing revision-1 projection, incomplete immutable metadata/payload evidence and early-failure cleanup gaps. Target revoke/correction/re-upload/retention semantics remain isolated in GRILL-007.
- `CHARACTERIZE-INSPECTION-SCHEDULE-DUPLICATE-001 v0.2` completed all five gates after deliberately discarding an overfit 1000-line concurrency harness. The compact PILOT_ONLY oracle now proves six real form POST exchanges through the current handler: exact schedule/event creation, sequential duplicate no-op, and CSRF/capability/invalid-date/ineligible-case zero-mutation rejections. Test-owned loopback logs and per-request full-schema/raw-row snapshots prevent transcript or final-state fabrication. Concurrency, cadence, reassignment and reschedule/cancel remain deferred to GRILL-001 instead of being promoted.

## IN_PROGRESS

- `CHARACTERIZE-OTIZ-SNAPSHOT-ACCEPTANCE-001 v0.1`: focused evidence and the
  four-artifact OpenSpec planning package are complete, strict-valid and passed
  a fresh correction/rereview cycle. Exact Gate 1 fixes literal child/auth/RBAC
  fixtures, real LocalAuth/router exchange, auth → broad `otiz.manage` → twelve-
  table constructor DDL plus conditional `unique_reversal` repair → CSRF → row
  lock, exact HTTP/facts, independent Moscow timestamps, replay and observable
  two-worker lock overlap. Three fresh Gate 1 reviews closed fixture,
  concurrency and cleanup-fault sensitivity gaps; final verdict is
  `READY_FOR_OWNER_REVIEW`. OpenSpec task 1.1 is complete; task 1.2 and RED await
  explicit owner approval. Target financial semantics remain under GRILL-001.
- `CHARACTERIZE-PREMIUM-SNAPSHOT-DETERMINISM-001 v0.1`: independently reviewed
  evidence, four-artifact OpenSpec planning package and exact Gate 1 spec are
  complete. Planning correction removed an impossible resource-bounded overflow
  claim and added mandatory Gate review order. Six fresh Gate 1 passes closed
  trace/hash, compound fixtures, validation literals, transcript grammar and
  one-to-one CASE_ID gaps; final verdict is `READY_FOR_OWNER_REVIEW`. Tasks 1.1
  and 1.2 are complete; task 1.3 and RED await explicit owner approval. Formula
  v1 remains strictly `PILOT_ONLY`; no financial behavior or code changed.
- Separate declaration-recording discovery is complete. Evidence in
  `docs/operations/completion-declaration-recording-behavior-evidence.md`
  fixes the exact PTO prerequisite, date/details validation and trimming,
  duplicate/concurrency behavior, broad admission, live clock, runtime DDL and
  projection-only terminal effect. Dedicated OpenSpec proposal/delta-spec/
  design/tasks are now 4/4 and strict-valid. Three fresh review passes closed
  shared LocalAuth session-directory ownership, explicit worker UTF-8 and
  session-GC hazards; planning verdict was `READY_FOR_GATE1_DRAFT`. Exact Gate 1
  `CHARACTERIZE-COMPLETION-DECLARATION-RECORDING-001 v0.1` now fixes disjoint
  short DB namespaces, every form operand, literal Unicode hashes/limits,
  PTO fingerprints, admission/replay/concurrency/DDL, projections and shared
  session safety. A fresh correction review found and closed namespace-reset and
  incomplete-request-matrix gaps; final rereview returned
  `READY_FOR_OWNER_REVIEW`. Task 1.1 is complete (1/13); task 1.2 and RED await
  explicit owner approval. It remains strictly `PILOT_ONLY` and does not merge
  PTO or approve declaration-as-100%.
- `characterize-completion-pto-recording`: focused source evidence now separates
  the PTO mutation from declaration and proves the reachable HTTP ordering,
  broad active-user admission, replay/concurrency behavior, live Moscow clock
  and request-triggered DDL. Proposal/delta-spec/design/tasks are 4/4,
  strict-valid and passed fresh independent re-review as
  `READY_FOR_GATE1_DRAFT`. Exact executable Gate 1
  `CHARACTERIZE-COMPLETION-PTO-RECORDING-001 v0.1` now fixes literal users,
  cases, progress operands, HTTP outcomes, replay/order/admission/DDL and
  multi-worker concurrency plus live-clock normalization. A fresh correction
  review removed three unobservable/underdetermined assertions and final fresh
  rereview returned `READY_FOR_OWNER_REVIEW`. Task 1.1 is complete (1/13);
  task 1.2 and RED await explicit owner approval, while target PTO/declaration
  semantics remain under GRILL-001.
- Fresh golden-journey and `item_completed` audits prove the current E2E ends at
  a textual “first inspection” next step and performs no engineer mutation.
  Exact evidence is now captured in
  `docs/operations/inspection-item-completion-behavior-evidence.md`. It exposes
  payload-unaware operation replay, acceptance of lower stale revisions, both
  serialized same-base commands succeeding, repeated-item projection overwrite,
  runtime DDL before HTTP authorization and projection-side legacy attribution
  backfill. These are PILOT_ONLY migration hazards, not target requirements.
- `CHARACTERIZE-INSPECTION-ITEM-COMPLETION-001 v0.1`: planning OpenSpec is 4/4
  and strict-valid. The exact Gate 1 draft now fixes real HTTP session/CSRF,
  accepted/replay/stale/ahead/concurrent operations, crew history, legacy
  backfill, four rejection boundaries, a stable transcript and closed cleanup
  ownership. Fresh independent technical review returned
  `READY_FOR_OWNER_REVIEW`; task 1.1 is complete (1/13), while task 1.2 and RED
  remain prohibited until explicit owner approval.
- `characterize-inspection-attribution-correction`: current
  `item_installers_changed` evidence now proves it is a PILOT_ONLY visible
  attribution replacement, not the approved `completion_retracted` correction:
  it has no reason/reference, leaves progress complete and uses last-row
  projection. Proposal/delta-spec/design/tasks are 4/4, strict-valid and passed
  fresh independent planning review. Exact executable Gate 1 v0.1 now fixes
  literal accepted/replay/stale/ahead/drift/concurrency/rejection/backfill facts,
  real HTTP/session/CSRF, stable transcript and closed ownership. A fresh
  correction/rereview cycle returned `READY_FOR_OWNER_REVIEW`; task 1.1 is
  complete (1/13), task 1.2 and RED await explicit owner approval.
- `characterize-inspection-section-completion`: focused evidence proves the
  current aggregate transition is append-only but browser-auto-enqueued,
  hard-coded-history based, repeatable and not invalidated after last-photo
  revoke. Proposal/delta-spec/design/tasks are 4/4, strict-valid and passed a
  fresh independent planning review. Exact Gate 1 v0.1 now fixes real section-8
  item/photo/completion HTTP, raw/no-weight/projection facts, replay/repeat/
  concurrency/revoke contrasts, six rejection fixtures, browser-source evidence
  and closed ownership. Three correction reviews resolved payload bytes,
  prerequisite determinism and a valid unopened-card state; final fresh review
  returned `READY_FOR_OWNER_REVIEW`. Task 1.1 is complete (1/13); task 1.2 and
  RED await explicit owner approval.
- `CHARACTERIZE-OBJECT-DETAIL-IMPORT-001 v0.1`: planning OpenSpec is 4/4 and
  strict-valid. Exact executable Gate 1 draft now fixes a fictional six-field
  worked example, independent material/quarantine hashes, real operator CLI,
  clean detail+quarantine, serial replay, transactional conflict, two source
  rejections, isolation and bounded cleanup. A fresh read-only consistency
  review returned `READY_FOR_OWNER_REVIEW`; OpenSpec task 1.1 is complete and
  task 1.2/RED remains prohibited until explicit owner approval. The approved
  synthetic/native data policy excludes production import from the TEST-USER
  contour and does not change this private fictional oracle.
- `REGISTER-WORKFORCE-HISTORY-CANONICAL-V5`: Gate 1 and qualifying public-CLI
  RED are complete. Independent test review v2 was superseded after GREEN exposed
  that a SELECT-only MariaDB principal cannot see FK rules; a fresh correction
  added `REFERENCES` metadata visibility without DDL privilege, and fresh v3
  rereview returned `APPROVED` after proving the first denied mutation is v5
  DDL and the public result is exact `MIGRATION_FAILED`. Minimal GREEN now
  registers v5 after v4, reports `schemaVersion=5`, enforces composed 25/26,
  runs the canonical command before Compose bootstrap, and leaves bootstrap/
  importer as read-only v5 consumers with no workforce ALTER/direct apply.
  Full runner matrix, workforce v0.3, catalog, production composition,
  deployment/auth characterization, strict OpenSpec, diff-check and architecture
  6/6 are green. A subsequent full `make verify` exposed a new canonical-test-DB
  collation RED: v2 catalog creation does not inherit an explicit database-
  default collation, so strict v5 reports conflict and blocks migrate/db/e2e plus
  one canonical-compat characterization. The new public-runner collation RED
  was independently approved. Minimal GREEN now makes both clean v2 and v5 DDL
  use the exact database-default utf8mb4 collation; full matrix/schema/catalog/
  composition are green. Stale inherited v4 harness expectations were updated
  to the approved v5 catalogue and independently approved without weakening
  earlier conflict assertions. `make verify` is back to the established
  baseline only: eight DB regressions and the same duplicated E2E artifact
  failure. Subsequent reviews also caught and closed the remaining runtime v2
  fallback plus alias/indirect-DDL escapes in the absolute architecture rule.
  The final adversarial suite is 18/18 green, architecture is 7/7, and fresh
  Gate 5 code rereview v4 returned `APPROVED`. All OpenSpec tasks are complete;
  the slice is DONE and ready for its dedicated commit.
- Exact owner-review artifacts are prepared for checklist-template and inspection-evidence ownership with OpenSpec tasks unchecked. The checklist-template draft now separates its family-local 29-byte arithmetic from the composed 25/26 pre-DB-access contract. A fresh cross-spec prefix rereview returned `READY_FOR_FRESH_OWNER_REVIEW_WHEN_PREDECESSORS_LAND`; `CHECKLIST-TEMPLATE-SCHEMA-001 v0.1` remains explicitly `DRAFT/BLOCKED_PREDECESSORS` until workforce and identity/access land, and its literal version/catalogue still requires another fresh review and owner approval before RED.
- Planning-only OpenSpec change `canonicalize-identity-access-schema` now has proposal/delta-spec/design/tasks and strict validation green for all nine existing tables, populated preservation, prefix isolation, separate destructive seed/rebuild and no runtime DDL. Exact evidence is captured, but the current OpenSpec all-or-none partial-family policy still contradicts the proposed restartable exact-compatible partial recovery policy. Updating those existing OpenSpec artifacts requires owner confirmation under the repository workflow; no RED starts while they disagree.
- Identity/access Gate 1 evidence is captured in `docs/operations/identity-access-schema-evidence.md` from source owners and an isolated populated MariaDB 11.4.7 namespace. It proves bootstrap creates 8/9 tables while `fm2_pilot_user_status_events` is request-lazy, records exact columns/indexes/FKs/defaults/collation and normalized hashes without secrets, and leaves zero fixture tables with Compose down.
- Proposed `IDENTITY-ACCESS-SCHEMA-001 v0.1` translates that evidence into nine exact manifests, semantic compatibility for MariaDB-generated names, deterministic symbols for clean creation, superseding composed 25/26 pre-DB-access boundaries, sequential `V_identity`, populated preservation, restartable compatible-partial recovery, incompatible-family zero mutation, and runtime-no-DDL/destructive-seed separation. Fresh cross-spec prefix rereview accepted the boundary update; the known OpenSpec partial-policy contradiction remains `NEEDS_GRILL` under GRILL-005, so RED remains prohibited.
- Calibration change `migrate-inspection-item-completion`: proposal/spec/design/tasks ready; fresh behavior/auth evidence corrected its earlier “NEEDS_GRILL none” claim. Gate 1 now explicitly requires approval of `inspection.item.complete` and GRILL-003 object scope before RED.
- Checklist-template and inspection-evidence canonicalization are prepared for Gate 1; the local-RBAC schema contract remains pending GRILL-002.
- Inspection-evidence schema ownership is also implementation-ready through Gate 1 and is the direct prerequisite for the calibration item-completion slice.
- Full-suite discovery is active. DB matrix is down to eight isolated regressions: three shared CSP paths, four local-RBAC fixture paths, and the combined-PDF E2E artifact path.
- `INSPECTION-PHOTO-UPLOAD-001` behavior discovery is complete: validation/idempotency/storage rules are inventoried, the focused success/replay/storage-failure oracle is executable, and only exact evidence authorization remains a product decision before target Gate 1.
- The first focused photo-upload characterization tracer is green; broader rejection/limit/concurrency characterization and target authorization clauses remain separate work.
- `INSPECTION-EVIDENCE-SCHEMA-001` passed a fresh correction/review cycle and is technically `READY_WHEN_PREDECESSORS_LAND`. Its four exact manifests and two additive predecessor forms now include safe explicit database-default collation, exact MariaDB default/generated/index metadata, corrected 34-byte basename math, and a 16-row Gate 2 matrix covering all 36 compatible partial combinations, malformed predecessors, atomic conflicts, prefixes, sequencing and DDL-denied runtime. It remains `DRAFT/BLOCKED_PREDECESSORS`: no version literal, owner approval, RED or implementation is permitted until workforce, identity/access and checklist-template actually land and another fresh review confirms the catalogue.
  Its prefix cases now use the superseding composed 25/26 pre-DB-access boundary
  while preserving the family-local 30-byte arithmetic; fresh cross-spec
  rereview accepted the correction without changing predecessor or Gate guards.
- Inspection-planning ownership discovery and OpenSpec planning are complete. `canonicalize-inspection-planning-schema` has proposal/delta-spec/design/tasks, 4/4 artifacts and strict validation green. Exact MariaDB evidence proves two runtime-owned tables and DDL before scheduling authorization plus Calendar/control read paths. Current planning inherits composed 25/26 pre-DB-access boundaries while preserving the 36-byte basename's family-local 28/29 arithmetic; it remains planning-only and `BLOCKED_PREDECESSORS` until earlier canonical schema families land and an exact versioned Gate 1 artifact is approved.
- Migrated-evidence ownership discovery and planning are complete.
  `canonicalize-migrated-evidence-schema` has proposal/delta-spec/design/tasks,
  4/4 artifacts and strict validation green. Corrected MariaDB 11.4.7 evidence
  fixes six overlapping runtime-owned tables, split inherited/default-utf8mb4
  collations, the projection JSON CHECK, populated counters, all 64 compatible
  partial states and separate family-wide conflict preservation. Fresh planning
  review returned `READY_WHEN_PREDECESSORS_LAND`. Versioned Gate 1/RED remain
  `BLOCKED_PREDECESSORS`; the owner-authorized planning update now uses composed
  25/26 pre-DB-access boundaries and keeps family-local 28/29 only as evidence.
  Import/backfill/reconciliation semantics are unchanged.
- Migration-quarantine ownership discovery and planning are complete.
  `canonicalize-migration-quarantine-schema` has proposal/delta-spec/design/tasks,
  4/4 artifacts and strict validation green. Disposable MariaDB 11.4.7 evidence
  fixes the exact three-table manifests, runtime owners, populated counters and
  all eight compatible partial states. Current planning uses composed 25/26
  pre-DB-access boundaries and preserves the 37-byte observations basename's
  family-local 27/28 arithmetic only as evidence. Initial planning review found
  and corrections closed missing explicit actor/oracle/seam/value and canonical
  owner naming; fresh rereview returned `READY_WHEN_PREDECESSORS_LAND`.
  Versioned Gate 1/RED remain `BLOCKED_PREDECESSORS`; quarantine taxonomy,
  outcomes, retention, cutover and financial use remain outside this PILOT_ONLY
  ownership slice.
- Pilot-generation metadata evidence is complete and independently rereviewed
  `READY_FOR_OPENSPEC`. It now distinguishes the deployed Compose sentinel/
  manifest lifecycle from the standalone synthetic owner/ready/active + table-
  comment lifecycle, proves default host/image topology separation
  (`78d99d34` versus `9c1c9cba`) and limits protocol collision to explicit
  co-location. `separate-pilot-generation-metadata` has 4/4 strict-valid
  planning artifacts. Its first planning review returned `CHANGES_REQUESTED`;
  durable prepare/readiness/finalize/recovery, logical DB identity and
  fresh-volume orchestration were corrected. Owner chose the
  sole TEST-USER contour as Compose `make up`, kept standalone as a synthetic
  harness and directed the future working deployment toward the same Compose
  approach and explicitly authorized all four OpenSpec revisions. GRILL-004
  fixture/reset policy is resolved in an append-only decision record with fresh
  `READY_FOR_FIXTURE_GATE1_PLANNING` rereview. Updated generation planning is
  strict-valid; fresh superseding rereview v2 returned
  `READY_FOR_GATE1_WHEN_PREDECESSORS_LAND`. Generation Gate 1, RED and
  implementation remain pending predecessor landing.
- `seed-test-user-fixtures` planning is 4/4 and strict-valid under the approved
  fictional/native data and reset policy. Initial review required separating
  initializer-owned seed semantics from generation-owned opaque prerequisite
  publication, a distinct one-way fixture initializer boundary, and an exact
  predecessor frontier; those corrections are present. The first rereview found
  only a missing explicit optional exclusion for installation-completion; it is
  now added alongside premium, migrated-evidence, quarantine and legacy-active.
  Fresh v2 planning rereview returned
  `READY_FOR_GATE1_PLANNING_WHEN_PREDECESSORS_LAND`. Executable Gate 1, RED and
  implementation remain prohibited until the listed predecessors land.
- Active-baseline/provenance inventory has been corrected after exact MariaDB
  evidence and fresh independent review. The previous three-table family was
  invalid: `fm2_migration_classification_provenance` is a release-supporting
  native/historical/active-baseline proof used by import, queue/origin,
  native-only and OTIZ consumers, while baseline + active-case provenance remain
  outside the approved synthetic/native TEST-USER contour. Its 39-byte basename lowers the full-catalogue
  process-prefix ceiling to 25 bytes; runtime plan/order now records the split.
- `canonicalize-classification-provenance-schema` has proposal/spec/design/tasks,
  4/4 artifacts and strict validation green. Initial planning review found and
  corrections closed missing `active_baseline` compatibility and the observable
  `PILOT_ONLY_OUTPUT_WITHOUT_PROVENANCE` contrast. Fresh rereview returned
  `READY_WHEN_PREDECESSORS_LAND`; the current delta now requires 26-byte/invalid
  rejection before DB connection/access, not merely before mutation. Literal version/Gate 1/RED remain blocked only
  by predecessor landing; classification taxonomy and import atomicity were not
  promoted to target semantics.
- Object-detail schema evidence passed two fresh correction/rereview cycles and
  reached `READY_FOR_OPENSPEC_UPDATE`. Owner-authorized revisions to all four
  existing `canonicalize-object-detail-snapshot-schema` artifacts now reconcile
  the composed 25/26 pre-DB-access boundary, retain family-local 30 only as
  explanatory arithmetic, and emit the validated exact database-default
  utf8mb4 collation. Strict validation and architecture checks pass; a fresh
  independent planning rereview returned `READY_WHEN_PREDECESSORS_LAND`. No
  literal version, Gate 1 approval, RED or implementation has started.

## READY

- Continue behavior/schema discovery beyond the prepared workforce → identity → checklist-template → inspection-evidence → inspection-planning dependency chain while their explicit approvals/versions are pending.
- Continue generation-metadata planning only after fresh rereview; ownership/
  separation discovery and release-contour choice are complete. It remains
  setup-only and must not import destructive reset behavior into production
  migrations.
- Keep optional active-baseline provenance behind GRILL-004; its ownership
  discovery is complete and the Compose release-contour decision does not
  promote legacy-active cutover.
- Continue object-detail snapshot schema planning from its existing evidence;
  keep importer capture semantics separate from the approved fictional fixture
  contour and do not import production personal data.
- `INSPECTION-PHOTO-UPLOAD-001` target spec preparation resumes immediately after GRILL-003/006 authorization and replay/limit decisions.

## NEEDS_GRILL

- Gate 1 approval for `CHARACTERIZE-OTIZ-SNAPSHOT-ACCEPTANCE-001 v0.1` as a
  strictly `PILOT_ONLY` oracle. Recommended: `APPROVED`; it changes no product
  or financial behavior and keeps exact authority, evidence sufficiency,
  separation of duties and payment consequences under GRILL-001. This blocks
  only its RED/GREEN gates.
- Gate 1 approval for `CHARACTERIZE-PREMIUM-SNAPSHOT-DETERMINISM-001 v0.1` as a
  strictly `PILOT_ONLY` pure-calculator oracle. Recommended: `APPROVED`; it
  changes no formula or financial behavior and leaves target norms, evidence
  admission, authorization, persistence and release under GRILL-001. This
  blocks only its RED/GREEN gates.

- Gate 1 approval for
  `CHARACTERIZE-COMPLETION-DECLARATION-RECORDING-001 v0.1` as a strictly
  `PILOT_ONLY` oracle. Recommended: `APPROVED`; it changes no product behavior,
  keeps PTO execution/race separate and leaves terminal/evidence/authorization/
  correction semantics under GRILL-001. This blocks only its RED/GREEN gates.
- Gate 1 approval for `CHARACTERIZE-COMPLETION-PTO-RECORDING-001 v0.1` as a
  strictly `PILOT_ONLY` oracle. Recommended: `APPROVED`; it changes no product
  behavior and explicitly keeps threshold/documents, authorization, correction,
  declaration and durable schema under GRILL-001. This blocks only its RED/GREEN
  characterization gates.
- Gate 1 approval for `CHARACTERIZE-INSPECTION-SECTION-COMPLETION-001 v0.1`
  as a strictly PILOT_ONLY oracle. Recommended: `APPROVED`; it changes no
  product behavior and explicitly excludes automatic/repeated/stale-photo pilot
  hazards from target requirements. This blocks only its RED/GREEN gates.
- Gate 1 approval for
  `CHARACTERIZE-INSPECTION-ATTRIBUTION-CORRECTION-001 v0.1` as a PILOT_ONLY
  contrast. Recommended: `APPROVED`; the draft changes no product behavior and
  explicitly prevents pilot replacement semantics from becoming requirements.
  Target `completion_retracted` and any separate per-item attribution adjustment
  remain independent owner decisions; this blocks only characterization RED.
- Gate 1 approval for `CHARACTERIZE-INSPECTION-ITEM-COMPLETION-001 v0.1` as a
  strictly `PILOT_ONLY` oracle. Recommended: `APPROVED`; it records current
  defects as migration contrast without promoting them to target behavior. This
  blocks only its RED/GREEN characterization gates.
- Gate 1 approval for `CHARACTERIZE-OBJECT-DETAIL-IMPORT-001 v0.1` as a strictly
  `PILOT_ONLY` serial oracle. Recommended: `APPROVED`; it uses fictional private
  fixtures, changes no importer/product behavior and explicitly excludes
  concurrency, transitions, production data, authorization and financial
  semantics. This blocks only its RED/GREEN characterization gates.
- GRILL-005: reconcile identity/access partial-family policy. Recommended: update the existing OpenSpec change to exact-compatible partial recovery, because MariaDB DDL commits per statement and fail-closed partial handling would make a transient interrupted migration operator-unrecoverable despite every existing member matching exactly. This blocks only identity/access Gate 1 and RED.
- GRILL-006: approve target photo evidence limit scope, overflow UX and client-operation payload-conflict semantics. Recommended: a per-section active-photo limit, deterministic non-retryable overflow that remains removable locally, and conflict (not duplicate) when one operation id is reused with different payload. This blocks target photo-upload Gate 1 but not the PILOT_ONLY concurrency characterization now in preparation.
- GRILL-007: approve photo revoke authorization, mandatory reason/confirmation, completed-section correction, identical-content re-upload and retention. Recommended: exact revoke capability plus current assignment; bounded mandatory reason; ordinary last-photo revoke rejected until replacement, elevated audited correction separate; identical bytes allowed as a new evidence fact while reusing blob storage; retain revoked blobs through the test-user release. This blocks target revoke/UX only, not the PILOT_ONLY revoke characterization.
- Gate 1 approval for `INSPECTION-ITEM-COMPLETE-001`, including capability name `inspection.item.complete`.
- GRILL-001 questions in `migration-backlog-and-grill.md`: inspection cadence; completion 85/15/documents; completion roles/corrections; OTIZ release scope; payment-closure meaning/evidence; financial separation of duties.
- GRILL-002: approve local RBAC as authoritative, route-specific same-origin JavaScript CSP, and the single combined-PDF assignment-order contract. These decisions block only the eight isolated HTTP/E2E regressions.
- GRILL-003: require both `inspection.photo.upload` and current engineer assignment, and reject queued uploads received after reassignment. This blocks evidence-mutation authorization clauses, not characterization/schema work.

## BLOCKED_EXTERNAL

- None for bootstrap. Product decisions block only the named slices; other READY work continues.

## Discovered risks

- Golden E2E proves no post-open engineer mutation. Current item-completion pilot
  semantics contradict the draft target on payload conflicts, stale revision and
  concurrency; `projection()` can also write historical installer attribution
  from current crew. Migration without the focused characterization could either
  preserve defects or falsely green target behavior.
- Target item-completion authorization sources disagree: the OpenSpec draft asks
  for active user + `inspection.item.complete`, while GRILL-003 recommends that
  exact capability plus current assignment. No RED may silently choose between
  them; current pilot's `checklist.edit OR current engineer` remains PILOT_ONLY.
- Canonical runner v4 owns only part of deployed schema; 40 runtime-DDL debts are baselined.
- The prepared 27/28/29-byte migration drafts are not a valid final
  full-catalogue ceiling: classification provenance has a 39-byte basename and
  requires 25 bytes. Before any affected Gate 1 approval, predecessor wording/
  tests must be reconciled so a longer namespace cannot pass early migrations
  and fail only at this later release-supporting table.
- Packaging is no longer a blocker to migration-before-runtime orchestration, but container startup still uses the legacy bootstrap/runtime-DDL path until the release-critical canonical schema families are approved and registered.
- 221 SQL-ownership and 66 rapid-pilot-mutation debts remain; baseline forbids growth but does not make them safe.
- Authorization is exact for assignment/opening, broad or absent for completion/OTIZ.
- Financial 85/15 and payment-closure semantics are not approved requirements.
- Fresh authoritative nine-stage `make verify` on 2026-09-01 after scheduling characterization: environment/reset, canonical v1-v4 migration, architecture, lint, all 32 unit verifiers, all 15 characterizations, and diff-check pass. DB still reports exactly the same 8 regressions: demo bootstrap + HTTP auth + SHLZ assets (shared CSP contract), object card/list/prepare/UI shell (local-RBAC contract/fixtures), and E2E combined-PDF artifact contract. The independent E2E stage reports that same artifact contract. Terminal aggregation remains correctly `count=2 stages=db-test,e2e-test`; no assertion was weakened, no new regression appeared, and Compose plus all scheduling-owned artifact namespaces were removed afterward.
- Fresh real `make fresh-test-verify` independently reviewed on 2026-09-01: it preserved the same `FULL_VERIFICATION_FAILURE count=2 stages=db-test,e2e-test`, emitted `FRESH_TEST_VERIFY_FAILURE verify_status=2 teardown_status=0`, and left no Compose services.
- The DB regressions expose three unapproved historical contract changes rather than implementation-only fixture drift; they must not be greened by weakening assertions.

## Next autonomous actions

1. On owner approval of `CHARACTERIZE-OBJECT-DETAIL-IMPORT-001 v0.1`, assign a
   fresh RED author and then a different fresh independent test reviewer; do not
   modify the importer before Gate 3 approval.
2. On owner response, resolve GRILL-002 and immediately migrate CSP/local-RBAC/combined-PDF contracts without weakening authorization.
3. Execute Gate 2 for approved `WORKFORCE-CANONICAL-RUNNER-001 v0.1` with a fresh RED author, then a different fresh independent test reviewer; canonical `make verify` currently truthfully reports v4 and no implementation begins before Gate 3 approval.
4. Extend photo-upload characterization only where it unlocks the target slice; keep GRILL-003 authorization and target-only dimensions/caption REDs unresolved until Gate 1.
5. On owner approval, immediately assign fresh RED and independent test-review agents for calibration and evidence slices.
6. Draft the smallest versioned fictional test-user fixture-seed Gate 1 under the approved no-personal-data policy; keep destructive reset operator-only and normal restart state-preserving.

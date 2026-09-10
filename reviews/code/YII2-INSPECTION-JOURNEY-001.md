# YII2-INSPECTION-JOURNEY-001 — independent Gate 5 review

Reviewer: separately tasked `/root/inspection_reviewer`; reviewer authored neither production nor tests.

Reviewed exact source: base checkpoint `752b62a42682f7775697939b0afa353cb89531e9` (main base `5822cde3ab327c728db2cc7affb661d0946123cc`), restored checkout `/private/tmp/fmonitor-76-inspection-gate5`, retained patch `/Users/antropophag/.local/state/fmonitor2/review-snapshots/76-inspection-gate5/source.patch`. Independently verified patch SHA-256: `e93a1e3be66730397b438dc08a40c43b9240dcbc50c15f3872b0a612c839ba9f`. Plan: `/Users/antropophag/.local/state/fmonitor2/deliveries/76-inspection-20260910/gate5-plan.json`.

## Findings

### HIGH — the new Yii persistence owner bypasses Yii DAO and silently opens a second connection

Location: `specs/YII2-INSPECTION-JOURNEY-001.md:122-126`; `docs/adr/0003-yii2-application-framework.md:19-22,34-42`; `app/YiiRuntime/Controllers/ChecklistController.php:105`; `app/InspectionEvidence/MariaDbYiiChecklist.php:17-38`; all SQL in `MariaDbYiiChecklist{Read,Mutation,Photo,Persistence}.php`.

The normative A9 contract requires new read/write boundaries to use Yii DAO with explicit atomicity. ADR 0003 likewise says new DB boundaries use Yii DAO/Query Builder and the candidate amendment explicitly states that the new Yii path does so. The exact implementation contradicts both statements: `ChecklistController::owner()` passes `Yii::$app->db` (a `yii\db\Connection`) to `MariaDbYiiChecklist`, but the constructor accepts an untyped `object`, recognizes only `mysqli`, ignores the supplied Yii connection otherwise, and opens a new `mysqli` from `FMONITOR_DB_*`. Every read, authorization query, mutation, and transaction then runs through that new mysqli connection.

This is confirmed directly on the frozen snapshot and is not a naming/style hypothesis. It makes the owner depend on a second, environment-discovered connection rather than the configured Yii DB component, contradicts the recorded design, and allows the Yii request to be correctly configured while inspection fails or targets different connection settings. The focused tests pass because their environment supplies matching mysqli credentials; they do not make the implementation use Yii DAO.

Correction: make the Yii owner accept and retain the configured `yii\db\Connection`, convert its query and transaction helpers to Yii DAO/commands, and keep the entire non-item mutation on that one Yii-owned connection. Preserve `ChecklistSync` compatibility by a thin mysqli-backed adapter or an explicitly separated persistence implementation that delegates the same application policy; do not let the Yii path discover credentials. Update the ADR text only if necessary to describe the actual corrected composition, without weakening A9.

### HIGH — migrated projection drops the current-employment overlay for recorded installer snapshots

Location: `specs/YII2-INSPECTION-JOURNEY-001.md:40-42,54-62`; inherited `specs/CHECKLIST-CURRENT-CREW-001.md`; pre-migration `app/PilotHttp/ChecklistSync.php` at checkpoint `752b62a4`, projection lines 37-40; `app/InspectionEvidence/MariaDbYiiChecklistRead.php:26-35`; `app/PilotHttp/checklist.js` display logic.

The frozen owner correctly retains immutable recorded personnel fields and computes `currentlyAssigned`, but it always exposes `employmentStatus` and `dismissalEffectiveAt` from the historical operation snapshot. The unchanged pre-migration projection first copied historical `employmentStatus` to `employmentStatusSnapshot`, then, when that installer remains in current crew, overlaid display `employmentStatus` and `dismissalEffectiveAt` from the current crew. The browser consumes `employmentStatus` to label an installer as dismissed and uses `currentlyAssigned` separately to label one who left the order.

This exact code comparison confirms a user-visible compatibility regression: an installer who remains in the latest order but whose current employment state changes to dismissed will still display the old employed status after migration. Immutable attribution is not the issue; the old projection deliberately carried both historical and current values. The exploratory current-crew verifier with setup failure is not used as evidence.

Correction: while building each recorded installer projection, preserve the stored fields in the snapshot fields, set `currentlyAssigned` from current crew membership, and, when present, overlay display `employmentStatus` and `dismissalEffectiveAt` from the corresponding current crew entry as the previous projection did. Add or repair a focused fixture that distinguishes historical snapshot status from current display status without rewriting accepted rows.

## Owner boundary and baseline assessment

The application ownership shape itself is acceptable: `YiiChecklist::accept` is the explicit public mutation; `MariaDbYiiChecklistMutation::accept` contains the state-changing dispatch; `ChecklistController` translates HTTP and does not write process facts; old `ChecklistSync` delegates non-item operations to the same owner and retains the separately accepted `InspectionRecording::completeItem` seam for item completion. Transactions and authorization checks are inside the owner. The readable facade split across read/mutation/photo/persistence traits does not create additional public mutation entry points.

The proposed architecture baseline delta is **approved as scoped**: it adds exactly `YiiChecklist.php::accept` and `MariaDbYiiChecklistMutation.php::accept` to `public_seams`, matching the explicit interface and scanner-visible trait implementation. No SQL/dependency/hotspot allowance is added and no unrelated baseline regeneration is present. ADR 0003 gives the required reason for these two accepted entries. This approval does not waive the DAO defect above.

## Other reviewed behavior

Against the approved tests and affected unchanged owners, I found no further confirmed defect beyond the two findings above in route/method/CSRF handling, current identity/capability checks, item-completion delegation, append-only operation history, replay/concurrency, photo validation/storage/revoke rules, queue ordering/filter data, response/CSP/assets, service worker scope, offline sequencing, shell navigation, or object return path. `checklist.js` and the Yii asset copy are byte-identical, and their shared `bulkQueue` serializes photo enqueue after the bulk item batch while the server keeps exact item revision checks. The supplied HTTP, concurrency, and browser logs show the final 11-revision journey GREEN.

Related unchanged sources inspected included `InspectionEvidence` production factory/environment, `ChecklistSync` resolver and item owner, Yii identity store/session validation, `MariaDbConstructionControlQueue`, checklist mutation facts, prior queue/preopening fixtures, native item-completion rejection/precedence/replay/MariaDB/wiring tests, and ADR 0003.

## Evidence and command accounting

Review commands were read-only: patch SHA-256; `git rev-parse`, status, diff stat/name inventory; exact production/config/ADR/baseline diff; full reads of the new owner interface/facade/traits, controller/views/assets, changed adapter, identity reload/authorization code, architecture scanner, plan, delivery record, and supplied logs. No passing test, architecture check, RED, local full suite, or CI was rerun because the source inspection provides concrete evidence for the blocker and all reported checks already have same-source GREEN evidence. No stand or primary data was touched.

## Verdict

**CHANGES_REQUESTED.** Gate 5 does not advance. The two-entry public-seam baseline change is acceptable, but the frozen candidate violates its normative Yii DAO boundary and loses the pre-migration current-employment projection overlay. After the bounded corrections, obtain a fresh Gate 5 review of that delta and affected relationships before full CI.

# YII2-INSPECTION-JOURNEY-001 — independent Gate 5 correction review

Reviewer: separately tasked `/root/inspection_reviewer`; reviewer authored neither production nor tests.

Reviewed only the production/test correction delta and affected unchanged relationships from the prior full Gate 5 snapshot `/private/tmp/fmonitor-76-inspection-gate5` (`e93a1e3be66730397b438dc08a40c43b9240dcbc50c15f3872b0a612c839ba9f`) to frozen `/private/tmp/fmonitor-76-inspection-gate5-final`, base checkpoint `752b62a42682f7775697939b0afa353cb89531e9`. Retained source `/Users/antropophag/.local/state/fmonitor2/review-snapshots/76-inspection-gate5-final/source.patch` independently matches SHA-256 `15a85d72c878e0d01d4dc2c814d7dd9dd713caa8949064f4676e269b63ec7db8`. Plan: `gate5-final-plan.json` in the external delivery directory.

## Findings

No findings.

## Correction assessment

The configured-DB defect is resolved. `MariaDbYiiChecklist` now retains either the passed `yii\db\Connection` or the explicitly supplied compatibility `mysqli`; it performs no environment credential discovery. `one`, `all`, and `execute` route Yii calls through `createCommand` with correct one-based positional bindings, and `begin`/`commit`/`rollback` retain the exact Yii transaction object. Revision initialization occurs after the case lock inside the non-item transaction. The approved regression evidence observes commands plus one commit and one rollback on the injected component while unrelated `FMONITOR_DB_*` values are invalid.

The existing item-completion owner remains whole. For `item_completed`, `ChecklistController` opens the existing `PreopeningResources`, resolves current application composition, creates the accepted native `InspectionRecording`, injects it into the Yii checklist owner, and closes the caller-owned resource in `finally` after result projection. The Yii facade neither opens a second native connection nor wraps the native owner in a Yii transaction. The mysqli compatibility path in `ChecklistSync` continues to use the passed connection and may construct the same native owner when no recording is injected.

The projection defect is resolved without rewriting history. Recorded `fio`, position, employment snapshot, source timestamp, and detached dismissal value remain sourced from immutable operation-installer rows. When the installer remains in current crew, only display `employmentStatus` and `dismissalEffectiveAt` are overlaid from that current crew; `currentlyAssigned` stays independent. Legacy registered-order crew again overlays live workforce catalog values, while application composition remains authoritative when present.

The related engineer parity is restored with the same application → latest `control_engineer_changed` event → legacy `responsstroicontrol` precedence as the unchanged queue owner, including matching name fallback. Browser evidence distinguishes two no-application legacy cases from the application-owned case.

The direct-owner authorization gap is resolved before replay and before transaction creation. `access` rejects absent, blocked, and non-active profiles; `roleAccess` now joins an active user as well as active roles. Thus non-item replay by blocked or invited actors returns `forbidden`, creates no process fact, and opens no mutation transaction. Item completion continues to recheck authorization in the inherited native owner.

## Scope retained from full Gate 5

The explicit single mutation owner and the exact two-entry `public_seams` baseline delta remain approved and unchanged; no architecture audit or baseline expansion was reopened. No new product policy, schema, transport result, or test expectation was introduced by the correction. Review of the affected unchanged relationships included Yii connection/transaction semantics, `PreopeningResources` connection lifetime, `ProductionInspectionEvidenceFactory`, `ChecklistSync`, current application reader, workforce/order schemas, canonical role authorization, queue fallback, projection consumer fields, and controller exception/finally paths.

## Evidence and command accounting

Supplied exact-source logs are GREEN for configured Yii query/commit/rollback and authorization, historical/current projection and detachment, full HTTP journey, concurrency, browser eleven-revision/offline/queue journey, and legacy adapter smoke. The supplied actual architecture check is GREEN and the final plan is fresh. Review commands were read-only: digest verification, old/new file inventory and diffs, and full reads of the corrected facade, persistence, mutation, photo/read traits, controller, native resource owner, ADR delta, plan, and logs. No passing test, registration check, full local suite, CI, stand, or primary data was rerun or touched.

## Verdict

**APPROVED.** Both prior Gate 5 findings and the affected authorization/engineer parity risks are corrected on retained snapshot `15a85d72c878e0d01d4dc2c814d7dd9dd713caa8949064f4676e269b63ec7db8`. Gate 5 may advance. Full exact-source CI and merge/deployment decisions remain separate.

# YII2-INSPECTION-JOURNEY-001 — Gate 5 whitespace delta

Reviewer: separately tasked `/root/inspection_reviewer`; reviewer authored neither implementation nor tests.

Reviewed exact frozen checkout `/private/tmp/fmonitor-76-inspection-whitespace`, base `1cec690e7503fd7702a683d185e031f5dc67d309`. Retained patch `/Users/antropophag/.local/state/fmonitor2/review-snapshots/76-inspection-whitespace/source.patch` independently matches SHA-256 `fa21181ba978eae018dd06b74b365571119b314e203b9a0939aa9dbc0a59fd4b`.

## Findings

No findings.

The complete delta contains exactly 18 replacements across `MariaDbYiiChecklistPersistence.php`, `MariaDbYiiChecklistPhoto.php`, and `MariaDbYiiChecklistRead.php`; each replacement removes spaces from an otherwise empty line. No executable token, line position, file mode, test, configuration, or behavior changes. `git diff --check 1cec690e7503fd7702a683d185e031f5dc67d309` is clean. The supplied token comparison excluding `T_WHITESPACE` is consistent with the inspected diff.

No tests were rerun because this is a confirmed whitespace-only correction to the already approved source.

## Verdict

**APPROVED.** The whitespace delta may be folded into the implementation checkpoint. Prior Gate 5 approval remains applicable; full CI and PR publication remain separate.

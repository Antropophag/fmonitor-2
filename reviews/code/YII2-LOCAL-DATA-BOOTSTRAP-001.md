# Independent Gate 5 code review — YII2-LOCAL-DATA-BOOTSTRAP-001

- Reviewer: separately tasked agent `/root/gate5_review`; authored none of the reviewed specification, tests, or implementation.
- Review date: 2026-09-14.
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T161707Z-7c9443f424/package.json`.
- Exact reviewed source: reconstructible dirty snapshot over base `44880bbe4df579789a094fda526e6a4415598b29`; harness source `0985451eaedafe751000ea9b54e7d705da9c5508c26ccf31350308827d0308f6`, executable source `c0cedb31ff3e0a014745de8aa028a36fc692325ac26d5615e4258dd2bf18ca2b`.
- Reviewed delta: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T161707Z-7c9443f424/delta.patch`, SHA-256 `47ed6f1bc62bfa3145670000b9ccc127846359bd2094b7220af88cd704603d4c`.
- Contracts: `specs/YII2-IMPORTS-WORKFORCE-001.md`, `specs/YII2-LOCAL-DATA-BOOTSTRAP-001.md`.
- Approved Gate 3 record: `reviews/tests/YII2-LOCAL-DATA-BOOTSTRAP-001.md` (final correction approval on source `cb8c216611c17348af9af54ec9077b511fd8e47ab48907f08a9f29e3d575b8ef`).

## Complete findings

1. **CRITICAL — native-candidate selection is not equivalent to `LegacyObjectClassification`, so active, completed, quarantined, and planning-ineligible objects can be imported as new operational cases.** Locations: `app/InstallationProcess/LegacySourceSnapshot.php:15-24`; established classifier `app/Otiz/LegacyObjectClassification.php:15-55`; retained routing oracle `rapid-pilot/legacy-migration/LegacyMigrationRouter.php:9-22`; contract A3. The new source snapshot excludes only an actual-start value, checklist events, attributions, and three empty identity fields. It ignores the classifier's `ptoactdate`, finished status `259`, positive `fact_percent`, `workstarted`, malformed dates/counts and quarantine outcome. It also does not enforce the retained native-import planning-date grammar. `LegacyImportConsole` then fabricates `native_candidate` provenance with empty reasons instead of persisting the classifier result. A row with `fact_percent=50`, `workstarted=1`, a PTO act, or finished status but no start/events is therefore selected and mislabeled as native. Correction: move/use the production-owned classification and route policy (without loading `rapid-pilot`), reject quarantine, apply the complete operational-case eligibility predicate, and persist the exact derived classification/provenance. Add differential cases against the established classifier/router, including all start/completion/quarantine signals and planning-date boundaries.

2. **CRITICAL — the target import is neither atomic nor safely resumable; failures publish partial durable facts.** Location: `app/YiiRuntime/LegacyImportConsole.php:55-65`; contract A3's all-or-nothing, partial-failure, replay, and append-only requirements. The template insert and legacy mirror insert run in autocommit. Each `PilotCaseImporter::import([$id], ...)` opens and commits its own transaction, after which provenance, detail, and association are again independent autocommit writes. A failure on any later object or relation returns non-zero while earlier template/mirror/case/fact rows remain published. This also permits a case to become visible before its required provenance/detail/template association exists. Correction: expose one application owner that validates the whole snapshot first and commits the complete target fact set through one explicit transaction (or a specified durable run/staging protocol with proven reconciliation); do not nest per-object committed imports. Add deterministic faults after every write boundary and prove either no new facts or a complete, safely reconciled result on retry.

3. **HIGH — replay silently accepts conflicting immutable facts instead of reconciling them fail-closed.** Location: `app/YiiRuntime/LegacyImportConsole.php:58-65`; append-only/history and replay requirements in A3. Template replay checks its hash, but mirror rows are accepted merely by matching `id`, and provenance, detail, and template association use `INSERT IGNORE` without reading back and comparing the complete immutable identity/content. Existing conflicting address/planning data, classification provenance, detail payload, association cutoff, or template identity can therefore be reported as `LEGACY_IMPORT_COMPLETED`. This differs from existing owners such as `MigrationClassificationProvenanceTarget::reconcile` and `MariaDbPilotSnapshotImporter`, which compare stored hashes/content and reject conflicts. Correction: delegate every fact family to its canonical reconcile owner or perform equivalent locked content comparisons inside the single transaction; count `alreadyPresent` only after the entire expected fact graph is proven identical. Add conflict fixtures for each immutable fact family and assert non-success with no mutation.

## Boundary and evidence assessment

The Yii route is registered through the canonical console configuration and its controller remains a thin transport adapter. The reviewed source/package checks also support that the new command and production image load Yii/application files without loading `rapid-pilot` or `app/demo`. The source connection explicitly starts a repeatable-read, consistent, read-only transaction; private files are validated as regular mode-`0600` inputs; secret values are not placed directly in Make argv or Compose environment. No contrary security or Make ordering finding was observed.

The package attaches 18 source-bound focused records, all `GREEN`, including the native legacy DB test, Make orchestration/config tests, package/load closure, architecture guards, schema checks, and retained case/workforce regressions. No canonical local full suite was run, consistent with the owner decision. CI and deployment remain `UNKNOWN`. The current happy-path DB fixture does not exercise the classifier signals, target write faults, or conflicting replays above, so GREEN evidence does not resolve these findings and the approved Gate 3 tests need behavior-sensitive additions; those expectation/test changes restart Gate 2/3 before implementation correction.

## Verdict

`CHANGES_REQUESTED`

Gate 5 does not pass for source `0985451eaedafe751000ea9b54e7d705da9c5508c26ccf31350308827d0308f6`. Return to Gate 2/3 for the missing eligibility, atomic-failure, and conflict-replay assertions, then correct the production implementation and submit one fresh exact-source package with focused GREEN evidence. CI and deployment remain `UNKNOWN`; no publication or deployment is approved.

---

## Correction Gate 5 review — 2026-09-14

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T162730Z-645f00302f/package.json`.
- Exact corrected source: base `44880bbe4df579789a094fda526e6a4415598b29`, harness source `60e57e761a0cef2b5a1d15e5110b1bf8a78fef5412f05f88bbed2965ef8512f4`, executable source `cf3949ff5ac35f04a9c8bdd5abbfd0018d3981682456a8702fab67c9f65560df`.
- Corrected delta SHA-256: `85217071d305c7d045b6c676b8f0f78382f258623b1d940af69f90c0b6ff93e8`.
- Verification plan SHA-256: `35ded2089169738c366689d2bae4ac0a15d78f27430027629a467c92afe95497`.
- Correction Gate 3: `APPROVED` for exact test source `7469cef37fec4cb81b16e5eda8078e02c75dd30f601a6641b8aea71c617f59a7`.

### Prior-finding resolution

Prior findings 2 and 3 are resolved. `LegacyImportApplication` now owns one target transaction around template, mirror, case, provenance, detail, and association writes. Every retained fact is selected with a lock and compared with the expected immutable content before success; any exception rolls the transaction back. The corrected DB test injects a mid-import detail failure, proves zero rows in all material target tables, then proves successful retry, and separately proves that a conflicting mirror fails without changing case history.

Prior finding 1 is partially resolved. `LegacyImportRouting` delegates to the production `app/Otiz/LegacyObjectClassification.php`, rejects non-native and quarantined results, and persists the derived classification instead of fabricating it. The corrected test covers actual start, progress, started flag, PTO, finished status, and missing identity. Production load closure remains clean: this delegation does not load `rapid-pilot`.

### Complete correction findings

1. **HIGH — automatic import still omits the inherited planning eligibility required by A3.** Locations: `app/InstallationProcess/LegacySourceSnapshot.php:15-28`; `app/InstallationProcess/LegacyImportRouting.php:15-20`; `specs/YII2-LOCAL-DATA-BOOTSTRAP-001.md:59-62`; inherited `specs/PILOT-CASE-IMPORT-001.md:78-84`. `LegacyObjectClassification` determines historical/native routing, but it deliberately does not validate `plannedStartDate`, `plannedFinishDate`, or the pilot cutoff. The new routing predicate consequently admits any non-quarantined `native_candidate`, including rows with missing/malformed planning dates or `workdatestart < 2026-10-01`. The explicit case-import owner rejects those cases under its inherited contract; the automatic bootstrap must not broaden eligibility. The corrected test gives every candidate valid `2026-10-01`/`2026-10-20` planning dates and therefore cannot detect this gap. Correction: compose the classifier/router result with the exact normalized planning eligibility from `PILOT-CASE-IMPORT-001` (including adjusted-finish fallback and cutoff), without loading `rapid-pilot`, and add missing/malformed/before-boundary planning rows plus the exact cutoff boundary to the public-seam DB test. Because this changes the approved correction test, return through Gate 2/3.

### Evidence and verdict

The correction package attaches 18 exact-source focused records and all are `GREEN`. They cover the correction DB oracle, Make/config seams, production artifact/load closure, architecture/schema controls, and retained case/workforce behavior. No full local suite was run, as required by the owner decision. CI and deployment remain `UNKNOWN`.

`CHANGES_REQUESTED`

Correction Gate 5 remains blocked only on the inherited planning-eligibility gap above. Atomic rollback/retry, immutable conflict reconciliation, classifier parity for historical signals, Yii routing, source read-only behavior, secret containment, Make ordering, and production exclusion of `rapid-pilot` are approved for exact source `60e57e761a0cef2b5a1d15e5110b1bf8a78fef5412f05f88bbed2965ef8512f4`.

---

## Final planning-eligibility correction Gate 5 — APPROVED, 2026-09-14

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T163417Z-c125edcfe3/package.json`.
- Exact corrected source: base `44880bbe4df579789a094fda526e6a4415598b29`, harness source `6dd160937440aefa29b113d9d7f901acb364f38b879b3393fe06be28e0fface4`, executable source `570607bb01003cd188d9d802ddb8215b452da44820297d10f3a80c5a498a2b59`.
- Corrected delta SHA-256: `96de7f3458e990ee7535c63ea750044c9906da917efe617e31f678b29b04522f`.
- Verification plan SHA-256: `cae9079edc9b9a8e759b4d908b309e26c05a387a367572cc9c9342ce9bd6e279`.
- Planning-eligibility correction Gate 3: `APPROVED` for exact test source `601a94e49e76b0cf01f601398f0871714b0783116c89239daca2f51b68f5c0df`.

### Complete findings

None.

The final correction resolves the sole remaining finding. After production-owned historical classification and quarantine routing, `LegacySourceSnapshot` now normalizes the planned start and both planned-finish candidates, applies the adjusted-finish fallback, requires both resulting dates, and enforces the exact inclusive `2026-10-01` planned-start boundary before admitting a row. Malformed planning/completion dates fail closed for that candidate. The regression fixture independently proves accepted boundary rows `501` and `508` and excludes row `509` one day before the boundary, row `510` without a start, and row `511` without either finish source; exact imported IDs and result counts make weakening the filter observable.

The complete reviewed candidate now delegates historical eligibility to the production `LegacyObjectClassification`, persists its exact classification provenance, and composes it with inherited pilot planning eligibility. `LegacyImportApplication` retains one transaction for the complete target fact graph, locks and compares existing immutable mirror/template/provenance/detail/association facts, rolls injected failures back to zero facts, and supports a proven complete retry. Yii routing remains a thin canonical console adapter. The source transaction is repeatable-read/read-only. Private integration configuration and secret redaction, Make ordering/fail-fast/retry behavior, workforce owner reuse, and built-image/transitive load exclusion of `rapid-pilot` remain covered and conformant.

The prepared package attaches 18 exact-source focused evidence records, all `GREEN`. No canonical local full suite was run, consistent with the owner decision. CI and deployment remain `UNKNOWN`; this approval does not represent CI, publication, merge, or deployment approval.

### Verdict

`APPROVED`

Gate 5 passes for exact source `6dd160937440aefa29b113d9d7f901acb364f38b879b3393fe06be28e0fface4`. No code-review findings remain within the two-contract bounded scope.

---

## Post-approval publication-inventory delta review — APPROVED, 2026-09-14

- Baseline: approved Gate 5 source `6dd160937440aefa29b113d9d7f901acb364f38b879b3393fe06be28e0fface4` from package `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T163417Z-c125edcfe3/package.json`.
- Exact current harness source: `50f1de72a9585610ed699e24df721657408932f27f7434a0b307e61253d012fd`; executable source `e586b6c25e7cf60ed59ee30e503a206df4023bba89aa531f3a4553ed4a495c25`.
- Reviewed files and SHA-256: `tools/verification/suites.tsv` `e27ea04f374e05a02639f8d8bcb529a4bac1d64c4ef6e73eb75c7e41e70684e1`; `tools/verification/categories.json` `706e6a286e6161d09159daafc903956f8814e22f518da0dcf4dbfe7edc8ab0dc`; `tests/Verification/verification_ci_001_test.py` `50cc396d9f3a8a56a8edaa53a8ee0a1ebb0c7e016d9804e72c92f20b901e137f`.

### Complete findings

None.

The delta registers exactly the four previously reviewed tests in the canonical suite and category inventories: two unit checks, one e2e Make-orchestration check, and one DB/integration check. The existing hard-coded e2e expectation is updated for the single new e2e member. Categories and suite lanes agree, no production/specification behavior changes, and no reviewed acceptance expectation changes.

I independently ran `python3 tests/Verification/verification_inventory_001_test.py` (16/16 GREEN) and `python3 tests/Verification/verification_ci_001_test.py` (16/16 GREEN) against the exact current worktree. No full local suite was run. CI and deployment remain `UNKNOWN`.

### Verdict

`APPROVED`

The inventory-only publication delta is approved. The prior Gate 5 approval remains valid for production/test behavior; no findings remain.

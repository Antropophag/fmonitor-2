# Test review: ERP-EQUIPMENT-FACTS-OPERATIONAL-001

- Reviewer: `/root/erp_gate3` (`gpt-5.6-sol/low`), independent of specification and test authorship
- Test author: root
- Reviewed source: base `45fa7dee3ae8703295546142c4ce08c7f1d662de` plus prepared candidate source `249849e5174157d93bd9e45c7f99f0e485f87e817e1a00d2b834517bfdd7ebb6`; package `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T091601Z-6a215c567b/package.json`
- Agreed review scope / prior findings disposition: initial Gate 3 review of the complete operational ERP acceptance matrix; no prior findings
- Specification: `specs/ERP-EQUIPMENT-FACTS-001.md` and `openspec/changes/operationalize-erp-equipment-sync/specs/erp-equipment-facts-operations/spec.md`
- Public seams: durable job claim/handler/worker, scheduler tick, canonical manual command, bounded ERP source transport, `EquipmentFactsApplication`, runtime startup/readiness, protected object-card read, migration/recovery
- Red commands and intended failures: prepared exact-source records show intended RED for `tests/Jobs/erp_equipment_facts_worker_001_test.php` at ERP v1 claim rejection; `tests/InstallationProcess/erp_equipment_facts_source_scope_001_test.php` at the old global-source contract; `tests/Deployment/erp_equipment_facts_runtime_001_test.py` at absent direct env wiring; `tests/Runtime/erp_equipment_facts_readiness_001_test.php` at optional jobs profile; and `tests/Yii2/yii2_erp_equipment_facts_operational_001_test.php` at absent direct-env composition. Existing mapped owner/scheduler/schema/card tests are GREEN.
- Verdict: `CHANGES_REQUESTED`

## Findings

1. **BLOCKER — runtime configuration and fail-fast acceptance is checked only as source text.** `tests/Deployment/erp_equipment_facts_runtime_001_test.py:21-49` searches strings in four files and a Makefile. It never executes `tools/delivery/local-runtime-env`, Compose configuration, or a worker/scheduler startup seam with complete, missing, empty, or out-of-range values. Consequently it can pass while validation accepts an empty secret, rejects valid direct values, leaks values, or enters a restart loop. It also does not establish Dockerfile/template/generated parity despite that being in the accepted scope.

2. **BLOCKER — readiness is not tested through the public readiness seam.** `tests/Runtime/erp_equipment_facts_readiness_001_test.php:6-29` inspects Compose/Makefile text and searches `MariaDbJobsHealth.php` for two literals. It does not observe health/readiness for a healthy contour or for missing, crashed, stale, or misconfigured scheduler/worker; it cannot detect a false GREEN from the actual health controller. The specification makes truthful jobs-aware readiness a production admission boundary, so static token checks are insufficient.

3. **BLOCKER — manual run and card acceptance is not exercised.** `tests/Yii2/yii2_erp_equipment_facts_operational_001_test.php:6-23` checks controller/view source strings only. It does not invoke the documented manual command through its public CLI seam, prove the same canonical composition/application owner is used, or read an authorized object card and independently assert the three dates and `lastSuccessfulSyncAt`. A no-op implementation containing the searched tokens would pass.

4. **BLOCKER — the SQL oracle is materially incomplete.** `tests/InstallationProcess/erp_equipment_facts_source_scope_001_test.php:34-55` requires only one `[1c-erp].[` occurrence per query and selected join/filter substrings. It does not prove that every referenced ERP table is fully qualified, nor assert `sale.Номер`, grouping by that field, `MAX` for both order dates, or `MIN(etap.ДатаОтгрузки)` after sentinel filtering. The first query is classified only by the presence of generic `max(` (`:13`), so wrong aliases, aggregates, grouping, or partially unqualified SQL can pass. These are the exact production defects this slice must catch.

5. **BLOCKER — durable-job success/retry/hourly execution coverage is incomplete at the real seam.** `tests/Jobs/erp_equipment_facts_worker_001_test.php:43-73` proves the former claim rejection and an unreachable-source response, but uses an unreachable application DB and therefore cannot prove that `SOURCE_UNAVAILABLE` is persisted as a failed run through `EquipmentFactsApplication`, that a completed application receipt settles a durable job, or that scheduler startup enqueues and the worker executes the current slot. The existing scheduler GREEN covers enqueue/idempotency but not the required scheduler-to-worker terminal execution. A disposable-DB public queue/worker regression is required, including one job for repeated same-slot ticks and safe retry classification after the failed run exists.

6. **BLOCKER — batch atomicity across chunks is not made sensitive by the new source regression.** The fake transport in `tests/InstallationProcess/erp_equipment_facts_source_scope_001_test.php:10-23` always succeeds. No test makes a later chunk fail after an earlier chunk returned records and then verifies through `EquipmentFactsApplication` that projection, history, diagnostics, and last-success are unchanged while a safe failed run is recorded. Existing single-batch owner regressions do not establish the new multi-chunk composition boundary.

7. **MAJOR — generated-source and state-preserving deployment assertions are too weak.** The deployment test checks only Compose token presence and rejects `down --volumes` using a line filter that includes all tab-prefixed Makefile recipes, without exercising the ordinary deployment target or checking preservation of sessions/artifacts/data. It does not compare canonical templates with generated Dockerfile/Compose output. The accepted migration/recovery frontier is mapped to existing GREEN tests, but the new jobs/config additions are not shown to participate in that recovery/startup behavior.

## Required changes

- Replace or supplement source-token assertions with deterministic executable tests of the actual config loader/startup and public readiness endpoints, covering complete config, missing/empty required values, invalid bounds, healthy processes, and missing/crashed/stale processes without real ERP access.
- Add public CLI/HTTP regressions for the manual canonical run and authorized object-card read with independently specified dates and last-success time.
- Strengthen the fake-transport SQL oracle to enumerate every expected fully-qualified table/alias and independently assert selected columns, exact joins, `GROUP BY sale.Номер`, both `MAX` aggregates, and filtered `MIN` semantics.
- Add a disposable-DB scheduler/queue/handler/worker regression proving completed settlement, persisted safe failed run plus retryable settlement, first-start current-slot execution, and same-slot job idempotency.
- Add a later-chunk failure scenario proving whole-batch no-write atomicity and safe diagnostics, and an executable generated-source parity/state-preserving startup-recovery check for the changed runtime artifacts.
- Capture fresh intended RED evidence for every corrected/new Gate 2 command at the exact candidate source, refresh the verification plan/package, and return the complete corrected candidate for independent rereview before implementation.

---

## Rereview — corrected candidate `506e5dfae9dcae0afa6e8caee6875395298d67234980f2a72bf81c775422285e`

- Reviewer: `/root/erp_gate3` (`gpt-5.6-sol/low`), still independent of specification, tests, support fixtures, and implementation
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T092424Z-bb3926ca27/package.json`
- Evidence reviewed: all 16 package records are bound to exact candidate source `506e5dfae9dcae0afa6e8caee6875395298d67234980f2a72bf81c775422285e` and executable source `71cd7816c49212e610e019860f2cf3c4a2c85dcb4043068694c4d5be1ecf83f1`; five changed commands retain intended RED and eleven mapped controls are GREEN.
- Verdict: `CHANGES_REQUESTED`

### Prior findings disposition

1. **OPEN / partially fixed — runtime configuration and service fail-fast.** `tests/Deployment/erp_equipment_facts_runtime_001_test.py:82-121` now executes `local-runtime-env --validate`, proves non-disclosure for valid, missing/empty secret and zero-bound cases, and invokes canonical generated-source parity. This fixes config-loader sensitivity and parity. It still does not execute a worker/scheduler startup seam under missing/empty ERP configuration or prove the required failure occurs before the Compose restart policy can create a background restart-loop. `make -n up` proves only recipe text, not startup rejection behavior.

2. **OPEN — public readiness seam.** `tests/Runtime/erp_equipment_facts_readiness_001_test.php` is unchanged from the rejected candidate (same package command blob `660ffebbe...`). It still reads Compose/Makefile/class source and searches literals; it never calls the health controller/CLI or health repository with independently controlled healthy, missing, crashed, stale, and configuration-invalid states. False public GREEN remains undetectable.

3. **OPEN — manual run and card seam.** `tests/Yii2/yii2_erp_equipment_facts_operational_001_test.php` is unchanged (same command blob `60531c89...`). It still searches controller/view strings and does not invoke the manual CLI, assert its safe receipt/canonical composition, or read an authorized rendered object card with independent dates and last-success values. Existing GREEN console/card tests establish predecessor behavior, not the newly required direct-env/bounded operational route end to end.

4. **FIXED — SQL oracle.** `tests/InstallationProcess/erp_equipment_facts_source_scope_001_test.php:35-65` now enumerates every `FROM`/`JOIN` reference and requires the fully-qualified prefix, plus exact selected sale number, both `MAX` expressions, exact joins/filters, `GROUP BY sale.Номер`, filtered sentinel/NULL predicates, and `MIN(etap.ДатаОтгрузки)`. Expectations derive directly from the confirmed contract and remain fake-transport deterministic.

5. **FIXED for Gate 3 scope — durable job success/retry/current-slot execution.** `tests/Jobs/erp_equipment_facts_worker_001_test.php:79-140` now uses a disposable MariaDB fixture, the public scheduler and queue, a real `JobWorkerProcess`/claim/handler chain, and the application owner. It proves one same-slot job, completed settlement, persisted safe failed run, retryable queue settlement, and allowlisted failure. Support executables are bounded to the test and do not access real ERP.

6. **FIXED — later-chunk atomicity.** `tests/InstallationProcess/erp_equipment_facts_source_scope_001_test.php:70-108` makes the third transport call fail after an earlier chunk returned data, records a safe failed run through `ErpEquipmentFactsDelivery`/`EquipmentFactsApplication`, and independently snapshots projection, history, diagnostics, and last-success to prove no partial mutation.

7. **PARTIALLY FIXED; remaining portion folds into finding 1.** The deployment test now executes canonical render parity and dry-runs the ordinary target, proving jobs inclusion and absence of a destructive volume command in that path. Existing mapped migration/recovery tests cover the inherited additive database frontier. Actual preservation is appropriately a later stand qualification, but service-level invalid-config startup behavior remains untested as stated in finding 1.

### Complete current findings

1. **BLOCKER — no executable process-level fail-fast regression.** Add a deterministic public startup/entrypoint or Compose-level test showing missing/empty required ERP values and invalid bounds exit `CONFIGURATION_INVALID` before a long-running worker/scheduler or restart-loop begins; include a valid-config control. It must not contact real ERP or disclose values.

2. **BLOCKER — jobs-aware readiness still lacks a public behavioral oracle.** Exercise the actual health CLI/controller (or its public health repository seam used by both) with controlled healthy, missing/crashed, stale-worker, stale-scheduler, and invalid-configuration states. Assert non-GREEN and exact allowlisted component reasons where required, plus one healthy control. Static source/Compose token checks may remain as wiring checks but cannot be the admission oracle.

3. **BLOCKER — canonical manual run and protected card read still lack executable coverage.** Invoke the public manual command with a deterministic fake source/disposable DB and verify its safe receipt and owner-produced persisted facts; then invoke the authorized card read/render seam and assert independently chosen readiness/first/full shipment dates and `lastSuccessfulSyncAt`. Also retain a rejection/absence control so token-only or bypass implementations fail.

### Required changes for next rereview

- Correct the three open blockers above without weakening the accepted contract or replacing public behavior with source inspection.
- Capture fresh exact-source intended RED evidence for the changed/new commands, refresh the verification plan/package, and return every prior finding with an explicit disposition.
- Do not begin production implementation until an independent Gate 3 candidate receives `APPROVED`.

---

## Third rereview — rebuilt candidate `a8cd1a042d44d2e4015cfa339bb24e16a097d98bc307a60ca77302cc450039ee`

- Reviewer: `/root/erp_gate3` (`gpt-5.6-sol/low`), independent of specification, tests, support fixtures, and implementation
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T092943Z-9ee06b8426/package.json`
- Evidence reviewed: all 16 acceptance records are source-bound to exact candidate `a8cd1a042d44d2e4015cfa339bb24e16a097d98bc307a60ca77302cc450039ee` and executable source `c3e2a95ac2b16f50f7d6332d8c78bb07bfe69970f517f0c6ea91481461d7f950`; five changed commands are intended RED for the missing production behavior and eleven predecessor/control commands are GREEN.
- Verdict: `APPROVED`

### Remaining findings disposition

1. **FIXED — executable process-level fail-fast.** `tests/Jobs/erp_equipment_facts_worker_001_test.php:79-94` now invokes the real public `bin/fmonitor2-job-handler.php` process for each missing secret and each invalid bounded value. Every case independently expects exit `64`, empty stderr, and exact safe `CONFIGURATION_INVALID` JSON. The valid structurally complete control at `:60-70` proceeds past configuration into the retryable source path, so the rejection is sensitive to configuration rather than fixture setup. The process exits synchronously; it does not enter a background loop or contact real ERP for invalid cases.

2. **FIXED — public jobs-aware readiness.** `tests/Runtime/erp_equipment_facts_readiness_001_test.php` runs the public `bin/fmonitor2-jobs.php health` command against a disposable Jobs schema. It independently proves missing scheduler/worker non-GREEN with exact allowlisted reasons, fresh dual heartbeats GREEN, each missing/crashed component non-GREEN, and missing ERP configuration fail-fast with exact safe output. The test uses current UTC for deterministic freshness and no production system.

3. **FIXED — canonical manual run and protected card read.** `tests/Yii2/yii2_erp_equipment_facts_operational_001_test.php` invokes the real `bin/yii erp-equipment-facts-sync/run` command against a disposable database and unreachable synthetic source, asserts the safe failed receipt, verifies the same run persisted through `EquipmentFactsApplication`, and checks forbidden connection/order material is absent. It then writes independently chosen facts through the sole application owner, authenticates through the existing HTTP fixture, and asserts the authorized `/pilot/objects/4512` response contains all three independently chosen display dates, the independently chosen last-success date, and source label. Existing mapped console/card controls cover predecessor command and read behavior.

### Complete findings

None. The rebuilt Gate 2 candidate is traceable to the normative contract, exercises the sensitive state-changing and operational boundaries through public seams, uses independently specified expected values, covers the relevant rejected cases, remains deterministic/disposable, and contains no real credentials or raw live ERP evidence.

### Gate 3 conclusion

`APPROVED` for implementation against exact candidate source `a8cd1a042d44d2e4015cfa339bb24e16a097d98bc307a60ca77302cc450039ee`. This approval covers tests/specification only; it does not declare implementation GREEN, approve deployment/publication, waive exact-source CI, or replace independent Gate 5.

---

## Gate 3 correction-delta review — source `24dbf65adaabc8e5bc0dd2dc5682ae97ab2af663144e9b50d0384e45b8f7d658`

- Reviewer: `/root/erp_gate3` (`gpt-5.6-sol/low`), independent of root-owned test corrections and executor-owned implementation
- Compared against: previously approved Gate 3 candidate `a8cd1a042d44d2e4015cfa339bb24e16a097d98bc307a60ca77302cc450039ee`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T100253Z-95dbef4ef0/package.json`
- Review scope: root-owned test/fixture corrections only; production implementation is explicitly outside this delta review and remains for Gate 5
- Verdict: `APPROVED`

### Delta assessment

1. **Case-fold correction — approved, no weakening.** `tests/InstallationProcess/erp_equipment_facts_source_scope_001_test.php:60-62` applies the same Unicode-aware `mb_strtolower` normalization to the independently specified `GROUP BY sale.Номер` and `MIN(etap.ДатаОтгрузки)` expectations that was already applied to actual SQL and the surrounding contract fragments. Required identifiers, aggregates, predicates, fully-qualified tables, parameter counts and chunk bounds remain asserted.

2. **Predecessor delivery regression — approved and strengthened.** `tests/InstallationProcess/erp_equipment_facts_delivery_001_test.php` now calls the bounded `fetch(candidateOrderNumbers)` API, asserts exact candidate parameters and read-only bounds, verifies two-query aggregate/stage behavior, sentinel exclusion, duplicate/orphan/invalid-row rejection, safe technical failure, streaming/bounded production transport markers and absence of `fetchAll`. The update aligns the predecessor consumer with the accepted public API without relaxing expected records or privacy.

3. **Console/direct-env expectations — approved, no weakening.** `tests/Yii2/yii2_erp_equipment_facts_console_001_test.php` replaces obsolete password/HMAC file expectations with required direct env keys, explicitly rejects the old keys, constructs a real validated `JobsRuntimeConfiguration`, and retains completed/failed/exception normalization, run identity, canonical actor and non-disclosure assertions.

4. **Jobs runtime fixture ERP environment — approved setup correction.** `tests/Jobs/jobs_runtime_cli_001_test.php` supplies synthetic complete ERP configuration now required by the shared runtime validator. The existing public schedule, health, failed-list and retry assertions are unchanged; no expected failure or business result was weakened.

5. **Pilot Compose fixture ERP environment — approved setup correction.** `tests/Deployment/pilot_jobs_compose_001_test.py` supplies only synthetic ERP values needed for the newly mandatory jobs configuration. It retains isolated resources, real Compose startup/restart, service health, persisted job/scheduler facts, slot uniqueness and secret non-disclosure. No real ERP access or credential is introduced.

### Evidence and findings

Harness records for exact candidate source `24dbf65adaabc8e5bc0dd2dc5682ae97ab2af663144e9b50d0384e45b8f7d658` show the complete final run of all 34 local obligations GREEN, including the five corrected consumers, focused ERP matrix, migration/recovery frontier, architecture guard, generated-source parity and verification inventory. An earlier same-source `production_migration_runner_001_test.php` regression failure remains preserved and was followed by a complete run in which that command and every other obligation passed; this review does not erase that history.

Current test-delta findings: None. The corrections repair setup/API alignment revealed during implementation and retain or increase sensitivity to the approved contract.

### Correction-delta conclusion

`APPROVED` for the root-owned test correction delta at exact source `24dbf65adaabc8e5bc0dd2dc5682ae97ab2af663144e9b50d0384e45b8f7d658`. This is not a production implementation review, Gate 5 verdict, CI approval, publication approval or deployment approval.

---

## Final live-evidence test/spec delta rereview — commit `614237cfe68d7b1343549519c22e06e05eac73e6`

- Reviewer: `/root/erp_gate3` (`gpt-5.6-sol/low`), independent of root-owned contract/tests and executor-owned implementation
- Exact candidate source: `cc2d2cf9053391e5981907eb3738530b97b4ae10ccd5048cc38e455c81d80061`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T104509Z-0a26809aa0/package.json`
- Scope: live-evidence-derived normative/test corrections only; production implementation and operational qualification are excluded and remain Gate 5 concerns
- Verdict: `APPROVED`

### Delta assessment

1. **Actual legacy default database plus fully-qualified schema — approved.** The normative contract now distinguishes the configured real legacy default database from the `[1c-erp].[...]` schema qualification and forbids assuming `1c-erp` is the login's default database. `tests/InstallationProcess/erp_equipment_facts_delivery_001_test.php:15-18,24` requires the DSN to use validated configured database data, rejects a hard-coded `Database=1c-erp`, while the existing source-scope oracle continues to require every ERP table to start with `[1c-erp].[`. This corrects the live connectivity fact without weakening query qualification or secret handling.

2. **TLS trust — approved, fail-closed scope retained.** The delivery regression requires both `Encrypt=yes` and explicit `TrustServerCertificate=yes`, and rejects `Encrypt=no`. This records the owner-observed corporate-certificate compatibility decision while preserving transport encryption. No test permits plaintext transport or leaks DSN/credentials.

3. **Date-only conversion and sentinel normalization — approved and strengthened.** The order/stage query oracle now requires SQL Server `CONVERT(varchar(10), MAX/MIN(...))` date-only projections and two order-aggregate `NULLIF` normalizations. The contract explicitly maps aggregate `0001-01-01` to authoritative `NULL`; independent date expectations, stage predicate filtering and explicit-clear semantics remain intact.

4. **Stage rows outside the order aggregate — approved semantic clarification.** The order aggregate is explicitly authoritative for record presence. The new regression proves an orphan stage is ignored without creating a synthetic record, clear, diagnostic or batch failure, while the valid order record completes with nullable first shipment. This matches live query behavior and does not permit absent orders to clear projections; invalid dates and duplicate authoritative order rows remain whole-batch `SOURCE_INVALID`.

5. **Process readiness separated from operator queue health — approved and more precise.** The contract now requires fresh/configured worker and scheduler for `process-health`, while append-only dead/overdue job facts remain visible through the existing operator `health` seam. `tests/Runtime/erp_equipment_facts_readiness_001_test.php:56-70` proves a historical dead job keeps operator health non-GREEN with `dead_jobs` but does not permanently poison repaired process readiness. Missing/stale heartbeats and invalid ERP configuration still fail process readiness. Compose/Yii tests require service healthchecks to use `jobs/process-health`, retaining `jobs/health` for operator diagnostics.

6. **Bitrix configuration reuse — approved, ownership preserved.** Jobs console tests reuse the established synthetic Bitrix fixture/config path for the worker and explicitly prove scheduler/operator health do not read worker-only Bitrix configuration, while worker startup still rejects its absence. The correction avoids a parallel secret/config mechanism and retains staging cleanup and non-disclosure assertions.

### Findings and conclusion

Current Gate 3 test/spec delta findings: None. The six corrections are traceable to live evidence, keep expected values independent, strengthen observable transport/date/readiness boundaries, preserve authoritative no-clear/privacy semantics and do not relax rejected cases.

`APPROVED` for the final root-owned test/spec delta at commit `614237cfe68d7b1343549519c22e06e05eac73e6`, candidate source `cc2d2cf9053391e5981907eb3738530b97b4ae10ccd5048cc38e455c81d80061`. This verdict is not Gate 5, does not approve implementation quality, live deployment/qualification, CI, publication or merge.

---

## CI failure-inventory test correction review — commit `8671f1648b4b4c400f6d63c450c2610a5ef703d0`

- Reviewer: `/root/erp_gate3` (`gpt-5.6-sol/low`), independent of the correction author and production implementation
- Triggering CI: run `35590570374`; complete supplied inventory comprised 11 failed tests across unit, e2e and integration
- Scope: test/fixture expectations and the syntax-only multiline restoration of the explicit `reset` target; no production behavior review
- Local correction evidence: all 11 exact failed commands pass at the reviewed commit
- Verdict: `APPROVED`

### Delta assessment

1. **Required synthetic ERP fixture configuration — approved.** The affected local integration, bootstrap, stand, browser, runtime and trusted-scheme fixtures now supply the same complete direct-env ERP keys and bounded controls required by the approved runtime contract. Values are visibly synthetic, no live ERP is contacted merely by Compose/config validation, `.env` fixture mode remains `0600`, and password/HMAC values remain covered by non-disclosure assertions where outputs are inspected.

2. **Complete ordinary jobs contour and process-health expectations — approved.** Quickstart, Jobs runtime and pilot startup tests now require worker and scheduler in the ordinary topology, remove the obsolete opt-in-profile expectation, and require service healthchecks/startup qualification through `jobs/process-health`. Existing command ownership, no-ports, migration dependency, graceful stop, no bootstrap/DDL proxy, heartbeat and worker-private Bitrix mount checks remain intact. Operator `jobs/health` coverage is not removed.

3. **Bitrix fixture and leakage handling — approved.** The quickstart fixture reuses the canonical local-integration configuration helper and a mode-restricted synthetic Bitrix config. Worker-only secret requirements remain tested; scheduler and health paths remain independent of worker-only Bitrix config. `local_integration_env_001_test.py` stops treating the literal structural department list `[72,71]` as a secret, but continues to reject the actual legacy host/port/name/user/password, timestamp and Bitrix token URL canaries from rendered output. This is not a credential/privacy weakening.

4. **Lifecycle/failure ordering — approved and strengthened.** The stateful quickstart oracle adds jobs services and `jobs/process-health` to the exact ordered lifecycle and failure-injection map. It still rejects unexpected operations, effects after failure and unauthorized volume removal, and continues checking state preservation and secret absence.

5. **Explicit reset target formatting — approved, no behavioral expansion.** Converting `reset: ; command` to the canonical multiline recipe restores target parsing expected by the architecture/Make consumers. The command remains the same explicit destructive `reset` operation; ordinary `up`, `down` and recovery paths do not gain `--volumes`.

### Findings and conclusion

Current CI correction test-delta findings: None. Each change maps directly to one or more failures in the supplied complete inventory; no assertion was deleted to hide the new jobs/config contract, and the security/privacy boundary continues to reject actual secret canaries and isolate worker-only Bitrix material.

`APPROVED` for the CI-derived test correction delta at exact commit `8671f1648b4b4c400f6d63c450c2610a5ef703d0`. This verdict is not Gate 5, does not review production implementation, and does not convert the failed CI run into GREEN or authorize another CI run, publication, deployment or merge.

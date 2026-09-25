# Independent Gate 5 review — installer utilization observations

## Rereview — final three CI corrections

- Verdict: **APPROVED**
- Reviewer: `gpt-5.6-sol/low /root/gate5_review`; independent from implementation and test authorship.
- Reviewed HEAD: `b093aa87`.
- Exact candidate source: `f1ac28ed561ee78d154af315912a2e8e8b5a6f3fae7065b4fed09d196586cabe`.
- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260925T043516Z-07a9a1c425/package.json`.
- Plan SHA-256: `9dbf1eccd44b74be761840d6a0c4a32ad309d55da3b4e9e0d0b3326c8b888842`.
- Failed CI log inspected: `/tmp/fm258-ci-36092782353-failed.log` (4,773 lines).

### Failure disposition

The second run contained exactly three `REGRESSION_FAILURE` entries, all independently reproduced and corrected:

1. `yii2_sidebar_state_icons_001_test.php` duplicated the `installers.read` permission now seeded by `ObjectQueueFixture`. The capture test no longer inserts the duplicate, and the shared fixture uses an idempotent permission seed. This changes fixture setup only and retains the authenticated shell/browser assertions.
2. `assignment_order_original_database_setup_001_test.php` relied on MariaDB's transient `INNODB_TRX.trx_state` label being exactly `LOCK WAIT`; the run observed `RUNNING` while the authoritative lock-wait relation already existed. The oracle now joins `INNODB_LOCK_WAITS` to requesting/blocking transactions and asserts the exact blocker connection, non-null wait start, serializable isolation, target table and `FOR UPDATE`. This is a stronger causal lock oracle, not a relaxed concurrency test.
3. `yii2_object_card_presentation_001_test.php` requires the installer-directory source to contain the literal position header. The view now spells the five stock shlz table headers explicitly; row data, escaping and layout are unchanged.

Exact-source harness records are `GREEN` for those three regression tests and for the stage-2 domain, HTTP and browser acceptance tests. `git diff --check` is clean. No production domain, persistence, authorization, scheduler, migration or observation behavior changed in this correction.

No blocking specification, security, standards or test-maintenance finding remains for candidate `f1ac28ed561ee78d154af315912a2e8e8b5a6f3fae7065b4fed09d196586cabe`. A subsequent full exact-source CI result remains the publication authority; this review does not perform or infer merge.

---

## Rereview — exact-source CI correction

- Verdict: **APPROVED**
- Reviewer: `gpt-5.6-sol/low /root/gate5_review`; independent from implementation and test authorship.
- Reviewed HEAD: `b48f7001`.
- Exact candidate source: `6853b8862c496924cf822eb1f5dae7904e7696b5e321a83fe1043b382ced5200`.
- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260925T040015Z-96572822ec/package.json`.
- Plan SHA-256: `d27f008bef1018b796b1400260fd8f9283c88443596fdf075c167076715de12f`.
- Failed CI log inspected: `/tmp/fm258-ci-36086608427-failed.log` (12,023 lines); complete `REGRESSION_FAILURE`, architecture, unit, e2e, integration and governance inventory reviewed before disposition.

### CI failure disposition

The failed run exposed four correction families rather than a new contradiction in the A–M behavior:

1. The v35 migration used a members table name that exceeded MariaDB's identifier limit with the supported 25-byte prefix. The canonical schema, owner and recovery profile now consistently use `fm2_installer_utilization_members`; the prefix-bound migration and runtime recovery expectations cover the corrected literal inventory.
2. Existing schema/recovery tests still treated v34 or earlier profiles as the terminal frontier. Their historical-profile assertions remain intact while current catalogue, replay, backup/restore and auto-increment expectations advance explicitly to v35. The changes are mechanical frontier maintenance, not reduced assertions.
3. Existing dashboard/directory/browser fixtures omitted the newly required permissions or complete workforce/assignment facts, and the installer table had dropped required shlz table tokens and reset/filter state. Fixtures now establish real complete inputs; incomplete utilization renders an explicit unavailable block rather than zero counts, while unrelated dashboard content remains readable. The directory restores stock shlz tokens, stable filters/reset and honest empty-source behavior.
4. The governance bootstrap failure was a cascade from the registered focused Yii test failing inside its outer clean-worktree fixture. The corrected runtime-jobs/recovery and Yii fixtures were rerun individually GREEN; no governance policy was weakened. Architecture ownership additions are narrow to the new migration, capture handler and dashboard's read-only scope query, with the global architecture check retained.

No failed-job or `REGRESSION_FAILURE` entry from the supplied log was left without a corresponding correction or explicit rerun disposition. The reported classification/inspection-item parallel timeout noise was checked as environment contention; the affected tests passed when rerun individually. The runtime-jobs outer-worktree binding fixture is also reported GREEN after correction.

### Final assessment

The correction preserves the previously approved immutable capture, successful-workforce gate, source coverage/freshness, actual capture time, scope denial, saved-detail reproduction, null-share handling, one-query history, scheduler authority and UI requirements. It adds no forbidden assignment/PTO writer, financial, calendar, checklist, inspection-schedule or construction-control-list mutation.

All three acceptance records in the prepared package are exact-source `GREEN` for the domain, HTTP and browser seams. The wider prior failed-run inventory has been corrected and independently rerun as described above. `git diff --check` is clean. A new exact-source full CI run remains required for publication admission; this review does not claim that the post-correction full matrix has already completed.

No blocking specification, security, migration/recovery or standards finding remains for this candidate.

---

## Rereview — corrected final candidate

- Verdict: **APPROVED**
- Reviewer: `gpt-5.6-sol/low /root/gate5_review`; independent from implementation and test authorship.
- Reviewed HEAD: `55fbe272`.
- Exact candidate source: `8bb6327787fbdfa943f45717fae8b77c81c511fbb7acd72b9a9065acb449be67`.
- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260925T022043Z-8596560efe/package.json`.
- Plan SHA-256: `bfe9017ce7db6ec370ecf337bfc4081b570d0f2b952cb2af4ad3115b2d32d402`.

### Prior findings disposition

1. **Freshness/coverage — RESOLVED.** The canonical v35 header persists `source_updated_at` and `source_coverage`; capture validates and saves the complete projection metadata, history/detail return it, and the dashboard renders the current complete-source timestamp. Empty or metadata-less workforce input now fails closed.
2. **Successful workforce synchronization — RESOLVED.** Both scheduler admission and the dedicated worker handler require a completed same-day `workforce.sync` job. Without that success no capture job is created, and handler-side revalidation makes bypass/stale queued work retryable.
3. **Restricted object scope — RESOLVED.** Dashboard and direct saved-detail actions now reject the active construction-control scoped role before reading counts or members. The HTTP fixture grants that role `installers.read`, proving denial despite both nominal read permissions.
4. **Exact capture time — RESOLVED.** The worker reads database `UTC_TIMESTAMP(6)` at execution and persists its Moscow representation rather than synthesizing the 03:17 due time. The owner fixture binds a distinct execution timestamp.
5. **Zero denominator — RESOLVED.** An empty workforce projection is unavailable; the generic share calculation also returns `null` for zero and suppresses deltas/trend.
6. **Reason ordering — RESOLVED.** Saved reasons are sorted by `(objectId, documentIdentity)` as required.
7. **Generic handler responsibility — RESOLVED.** `JobHandlerRuntime` delegates installer capture to the dedicated `InstallerUtilizationCaptureJobHandler`; the new seam is included in verification ownership.
8. **History N+1 — RESOLVED.** `history()` reads the bounded row set once and passes it to receipt projection instead of re-querying per point.

### Final assessment

The corrected implementation retains atomic header/member publication, date uniqueness, race/replay identity, immutable saved drill-down, source fail-closed behavior, 366-real-point history, exact comparison arithmetic, explicit user/background authority, v35 production/recovery registration, local shlz-ui grouped bars and the bounded installer-card presentation changes. No forbidden financial, assignment/PTO-writer, calendar, checklist, inspection-schedule or construction-control-list change was introduced.

All 23 evidence records in the prepared package were inspected and are `GREEN` on exact source `8bb6327787fbdfa943f45717fae8b77c81c511fbb7acd72b9a9065acb449be67`. They cover the focused domain, HTTP/browser, migration/recovery, jobs, architecture and change-verification obligations. `git diff --check` is clean. Exact-source GitHub CI remains a separate publication prerequisite and is not inferred by this review.

No blocking standards or specification findings remain for this candidate.

---

- Verdict: **CHANGES_REQUESTED**
- Reviewer: `gpt-5.6-sol/low /root/gate5_review`; independent from implementation and test authorship.
- Implementation author: `gpt-5.6-sol/low /root/executor` (root-authorized test corrections are recorded in the delivery history).
- Base: `99bd0974150617a01e195cec28f7d886f1ede761`.
- Reviewed HEAD: `3eae38307cabe6cd227d7c3aaf258632c0e0a09f`.
- Exact candidate source: `dd9a00772f7ca577a45a964b5e9574221c31242755601ceac4174f08605c1058`.
- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260925T015125Z-cc66caa307/package.json`.
- Plan SHA-256: `f02d80605a205e4b5a778dd6b89abc5ffac35f852b82bb213b245b396659b4a5`.

## Spec findings

1. **HIGH — source freshness/coverage is neither captured nor shown.** Contract clauses 3 and 6 require every immutable observation to retain source freshness/coverage and the current summary to display it. `InstallerUtilizationObservationSchemaMigration` has no such columns, `MariaDbInstallerUtilizationObservations::capture()` persists only time and counts, `observationProjection()` returns neither field, and the dashboard renders only the three counts. Historical observations therefore cannot reproduce the source-quality basis that qualified them.

2. **HIGH — daily capture is not gated by successful workforce synchronization.** The actor contract and clause 14 require the versioned capture job to be queued after a successful штатный workforce-sync slot. `JobsSchedulerProcess` only calls the two schedulers in order; `MariaDbInstallerUtilizationScheduler` unconditionally queues at 03:17. The jobs execute independently, so delayed, retrying, or failed workforce sync can still be followed by a stale capture. The test proves only increasing job ids, not successful completion.

3. **HIGH — restricted object scope can receive partial analytics instead of mandatory 403.** The actor contract requires both dashboard and every saved-detail URL to reject a limited-scope actor. `DashboardController` checks only `objects.read` and `installers.read`; the projection then deliberately applies construction-control object scoping. A scoped actor granted both permissions can therefore receive partial current counts, while saved detail is admitted by the same two string checks. The fixture's “partial” actor is denied because it lacks `installers.read`, so it does not exercise this boundary.

4. **HIGH — stored capture time is not the exact capture time.** Clause 3 requires exact capture time. `JobHandlerRuntime` always passes `<captureDate>T03:17:00+03:00`, including delayed and retried executions, recording the scheduled slot rather than when capture actually ran.

5. **MEDIUM — zero denominator is converted to a factual `0%`.** Clause 2 says a zero/unavailable denominator must not yield `0%`. `share()` returns `0.0`, and comparison/rendering treats it as an ordinary percentage and may invent a trend for an empty, otherwise valid catalogue.

6. **MEDIUM — saved reason ordering does not match clause 12.** The contract orders reasons by `(object_id, document_identity)`, while `observationProjection()` sorts by `(type, objectId, documentIdentity)`. Mixed current/upcoming reasons may therefore be persisted in a different deterministic order than specified.

## Standards findings

1. **Maintainability — possible Divergent Change / Feature Envy.** The installer branch in `JobHandlerRuntime` validates domain authority, opens two database adapters, composes the observation owner, synthesizes capture time, invokes capture, and maps retry behavior. It duplicates concrete observation wiring in `DashboardController::observations()`. A dedicated handler/composition seam would keep the generic dispatcher from owning installer-specific details.

2. **Maintainability — possible Divergent Change and N+1 history reads.** `MariaDbInstallerUtilizationObservations` combines live projection, transactional write/retry, persistence, detail reads, serialization and comparison. `history()` calls `receipt()` for every row, and every `receipt()` re-queries the full 366-row history, producing an avoidable N+1 query pattern.

## Evidence assessment

All 23 records in the prepared package were inspected: each is `GREEN`, bound to exact source `dd9a00772f7ca577a45a964b5e9574221c31242755601ceac4174f08605c1058`, and covers the planner-selected focused migration, runtime-recovery, jobs, workforce, Yii/browser, architecture and change-verification commands. This evidence supports the implemented paths but does not exercise the semantic gaps above. The package/harness reports exact-source GitHub CI as `UNKNOWN`; this review does not promote UNKNOWN to GREEN.

No forbidden-scope modifications were found in financial calculations, assignment/PTO writers, calendar, `ChecklistController`, `inspection-schedule.js`, or the construction-control list. Migration v35 is registered in the production catalogue and recovery frontier, daily-date uniqueness and transactional rollback are present, and the saved-detail implementation reads persisted members rather than today's projection. These positive properties do not resolve the blocking contract findings.

## Required changes

- Persist and render explicit source freshness/coverage, with fail-closed validation.
- Establish a durable successful-workforce-sync dependency before enqueueing/running the daily capture.
- Explicitly reject limited-object-scope actors on dashboard and detail, with a fixture granting both required permissions.
- Record real execution time separately from the scheduled slot.
- Represent zero-denominator shares/deltas as unavailable and order saved reasons by the normative tuple.
- Add focused regression coverage for each corrected boundary, prepare a fresh exact-source reviewer package, and obtain a new independent final review before CI/merge admission.

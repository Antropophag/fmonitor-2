# Independent Gate 5 review — installer utilization observations

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

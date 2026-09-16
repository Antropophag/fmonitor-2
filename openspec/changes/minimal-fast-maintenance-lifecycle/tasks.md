## 1. Contract and RED

- [x] 1.1 Root publishes `FAST-MAINTENANCE-LIFECYCLE-001`, current-goal/delivery metadata and `verification-input.json`; verify `openspec validate minimal-fast-maintenance-lifecycle --strict` and prepared planner obligations are complete
- [x] 1.2 Root adds one public harness routing regression covering cases A–N, including intended RED, staleness and measured #160 before/after proxy; verify the retained Gate 2 run fails only because lifecycle routing is absent
- [x] 1.3 Obtain planner-required independent Gate 3 review of the complete spec/test/RED candidate; verify verdict is `APPROVED` before implementation

## 2. Minimal lifecycle implementation

- [x] 2.1 Executor adds additive typed FAST maintenance declaration and deterministic `FAST_MAINTENANCE | OPENSPEC_REQUIRED` reasons after authoritative planner selection; verify all negative semantic/sensitive/non-FAST cases fail closed
- [x] 2.2 Executor integrates requirement path/digest freshness and compact lifecycle fields into existing prepare/state/package records without a new registry; verify changed requirement bytes make state stale
- [x] 2.3 Executor preserves explicit OpenSpec and legacy input behavior; verify existing OpenSpec prepare and proposal tooling regressions remain GREEN

## 3. Evidence and review

- [x] 3.1 Run only planner-selected bounded local checks and the historical measurement; verify regression, final review, CI and traceability guarantees are reported separately from token telemetry
- [x] 3.2 Obtain independent final review of the exact candidate; verify `APPROVED` and resolve every finding before publication
- [ ] 3.3 Run one existing exact-source GitHub CI consumer, record complete failed-job/`REGRESSION_FAILURE` inventory if needed, and update compact disposition to PR-ready only on matching GREEN source
- [ ] 3.4 Prepare the branch/PR without merge, deployment or settings changes; verify issue #162 links #145 and final delivery state distinguishes implementation, review, CI and publication

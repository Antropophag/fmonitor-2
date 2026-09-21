# YII2-OPERATIONAL-DASHBOARD-REFINEMENT-001 — Gate 5 review after rebase

- Reviewer: independent `/root/dashboard_final_rebased` agent; did not author the specification, tests, or implementation.
- Review date: 2026-09-21.
- Reviewed commit: `3407b64b7f9153658d59b5ba9d790966d107e76d` over rebased main `80130fbb3bb7998a1a0fc6e88429699244715540` (merged PR #218), plus this final review record.
- Candidate source: `8c3692f3cf0877e5c2063ae218a0803b3475fda766e11a19c89cec71ab310462`.
- Executable source: `78a4e8f4eaf35529030b207dd3b5b372bb0f490bdaf6ff66e9a1ecdb95a65764`.
- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T141507Z-34e86be4ff/package.json`.
- Reconstructible snapshot: base commit `3407b64b7f9153658d59b5ba9d790966d107e76d`, patch SHA-256 `5fea41189059032625115287f8590a1c179ec6c5593f4258fe5e0616839197de`.
- Verification plan: package `verification-plan.json`, SHA-256 `f36bedd5e53a554ad39e5c902b52daa254d378aa458910d9a9a12cd0dfcb48f5`; planner lane `CRITICAL`, required reviews `gate3, final`.
- Verdict: **APPROVED**.

## Findings

No open findings.

## Prior Gate 5 findings

- The earlier missing-obligation finding is resolved. The final rebased package contains exactly six freshly rerun GREEN records, all bound to candidate source `8c3692f3cf0877e5c2063ae218a0803b3475fda766e11a19c89cec71ab310462`: dashboard acceptance/browser, object card, object queue, change-verification, architecture guard, and profiled main navigation.
- The invalid-calendar-date finding is resolved in production and regression coverage. Dashboard and drill-down share `MariaDbYiiObjectQueue::plannedStartDateExpression()`, which rejects impossible month/day combinations, and the public-seam fixture includes `2026-09-31` exclusions.
- The lifecycle/Gate 3 finding is resolved. `reviews/tests/YII2-OPERATIONAL-DASHBOARD-REFINEMENT-001.md` now records the independent `/root/dashboard_gate3_rereview` decision as **APPROVED**, with all eight test-review findings explicitly disposed and intended-RED evidence bound to the reviewed pre-implementation test source. The rebase did not change the approved tests/specification.

## Rebase and scope assessment

- The production commit is a direct child of `80130fbb`; `git diff --check 80130fbb...3407b64b` is clean.
- No ERP implementation/test path from merged PR #218 is changed by this candidate. The dashboard diff is layered on the PR #218 base, so the ERP operational work is preserved rather than replaced.
- The reviewed implementation remains bounded and read-only: shared start-risk predicates drive aggregate and drill-down, malformed inputs fail closed, the DTO stays fixed-size, and no schema/writer/job is introduced.
- The strengthened tests and independent Gate 3 approval cover the previous test-review concerns: risk boundaries and invalid dates, proportional/CSP rendering, semantic palette and icon paint, KPI/chart geometry, mobile obstruction, all required viewport artifacts, and shared content-derived asset versioning.

## Evidence reviewed

- Normative contract R1–R8, OpenSpec proposal/design/delta/tasks, final APPROVED Gate 3 review, prior Gate 5 rejection, complete production/test diff from rebased main, and all six final package evidence records.
- GREEN records: `1789999964692552000-126e89eaeab6413293cb5972a0a31e9c`, `1789999964686627000-9739cc82ca71418eb90f96e552c1292f`, `1789999964689056000-dc577a5e3b804dca9c68cac10e54ae8c`, `1789999964696367000-8d6e7bf5e1ce461990892e22cef18304`, `1789999964705606000-e88ebf0759684a1898131dafe81121b2`, and `1790000077950662000-b27e4e129b73415fb3625806f683b264`.
- The prohibited local full suite was not run. Exact-source GitHub CI, PR publication, merge, deployment, and enforcement remain `UNKNOWN`/pending and are not inferred GREEN.

## Correction-delta rereview after CI run 35611168525

- Rereviewed final commit: `2a43b13791638c0169cbf03f7c0df185951c605f`, still a direct child of merged PR #218 base `80130fbb3bb7998a1a0fc6e88429699244715540`.
- Delta reviewed against the previously approved implementation commit `3407b64b7f9153658d59b5ba9d790966d107e76d`: `app/YiiRuntime/Views/dashboard.php`, two predecessor consumer expectations, verification input, and delivery/review records only.
- Final correction verdict: **APPROVED**. No open findings; all prior findings remain resolved.

### Correction disposition

1. **SVG census compatibility — resolved without semantic change.** The bar mark now emits the same `svg > rect` structure through Yii `Html::tag()`. The same calculated `x`, `y`, `width`, `height`, `rx`, class, view box, preserve-aspect-ratio, and hidden accessibility attribute are retained. The approved dashboard acceptance/browser test is byte-for-byte unchanged across the correction and remains GREEN.
2. **Immutable asset consumer — resolved.** The production-web-cutover fixture changes only the expected `pilot.css` SHA-256 to the final reviewed asset bytes; transport, MIME, cache, CSP, and every other asset expectation remain unchanged.
3. **Predecessor navigation consumer — resolved.** The minimal-dashboard assertion now expects the already approved operational-section ordering (`objects`, `calendar`, then `dashboard`) without weakening membership, current-page, permission, deterministic HTTP, or browser assertions.
4. **Verification inventory — resolved.** Both correction consumers are explicitly registered in `verification-input.json`, so the delta is not hidden from planning.
5. **Focused correction evidence — GREEN.** The reported final-source checks are `yii2_sidebar_state_icons_001_test.php`, `yii2_operational_dashboard_bar_charts_001_test.php`, `yii2_production_web_cutover_001_test.php`, `yii2_minimal_operational_dashboard_001_test.php`, and `git diff --check`. These checks directly cover the census, unchanged R1–R8 behavior, exact CSS asset bytes, predecessor navigation, and patch hygiene.
6. **ERP preservation — confirmed.** `2a43b137` remains based directly on `80130fbb`; the correction delta changes no ERP/Bitrix/SMTP operational implementation or test path. PR #218 remains intact.

### CI status boundary

GitHub run `35611168525` is retained as failed evidence for pre-correction PR head `0211c8190d9874ccb3192cf672d84bbf2b9bd647`, not as GREEN evidence for final commit `2a43b137`. Its complete failed inventory includes `e2e`, `Integration (1/2)`, aggregate `verify`, and `Quality Graph`; the correction addresses the identified dashboard census and stale-consumer failures. A new exact-source CI run for `2a43b137` remains required before merge readiness. This Gate 5 approval covers the bounded correction delta and does not relabel the failed earlier run or infer final CI GREEN.

## CI-setup correction rereview

- Reviewed uncommitted delta: `.github/actions/setup-runtime/action.yml`, its explicit verification-input registration, and the delivery-record evidence note.
- Correction verdict: **APPROVED**. No product, dashboard-contract, test-oracle, permission, persistence, or ERP finding is introduced; all earlier Gate 3/Gate 5 findings remain resolved.

### Safety and scope disposition

1. **Exact pin remains fail-closed.** The fast path is selected only when `platform.python_version()` exactly equals repository-provided `PYTHON_VERSION` (`3.12.11`). The following `Verify pinned Python` step independently asserts the same exact equality before any test category runs.
2. **Fallback is preserved.** A missing `python3`, a version mismatch, or a probe failure enters the existing `uv python install "$PYTHON_VERSION"` and `uv python find --no-project "$PYTHON_VERSION"` path. The change therefore avoids a redundant external download only when the runner already supplies the exact pinned interpreter; it does not weaken the pin or silently accept a nearby major/minor/patch version.
3. **PATH handling is bounded.** The fast path appends only the directory containing the already resolved `python3` executable to `GITHUB_PATH`. It does not mutate the runner interpreter, dependency pins, caches, credentials, or application runtime.
4. **Failure evidence is attributable.** Quality Graph run `35614232072`, attempt 1, and its one same-source retry (attempt 2) target head `cc85a0ce3e1192179a0ff77b6414c60cb2c1cce1`. Both failing `Integration (1/2)` setup steps report the same external download URL and `504 Gateway Timeout` after three retries, before integration tests began. Other attempt-2 categories that completed setup (`fast`, `unit`, `governance`, `e2e`, and `Integration (2/2)`) were GREEN; aggregate `verify`/`Quality Graph` failed because the required integration result was absent. The correction is therefore proportionate to the observed setup-only failure and is not a test bypass.
5. **Verification coverage is explicit.** `.github/actions/setup-runtime/action.yml` is added to this change's `planned_paths`. Reported focused checks `tests/Verification/yii2_dependency_setup_001_test.py`, `tests/Verification/architecture_guard_001_test.py`, and `tests/Verification/change_verification_001_test.py` are GREEN, covering the shared dependency bootstrap contract, architecture policy, and verification-plan registration.
6. **Prior scope remains intact.** The delta contains no code/test/spec change outside the shared CI setup registration and evidence record. The dashboard correction approval, PR #218 ERP preservation, and R1–R8 evidence remain valid.

This approval covers the bounded setup correction. Because the correction itself changes the exact PR source, a successful exact-source GitHub CI run after commit remains required for merge readiness; neither failed attempt is relabeled GREEN.

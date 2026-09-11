# Test review: YII2-JOBS-CONSOLE-001

- Reviewer: `/root/gate3_yii2_jobs_console`
- Test author: `/root`
- Review role: independent Gate 3 reviewer; no production implementation authored or reviewed
- Reviewed source: base `547cf15a9b2055ac26d50bce17fe82f284c41768` + retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T100902Z-eb14c08eb7/snapshot/source.patch`, patch SHA-256 `1552ce0bc1b1a6f30695031340357c00893a184f184611b48ba4c006886cfb4c`, harness source digest `7645415710c28906f3551fabb1b6ca373f77998e2ab02958ceae8ab6264320c6`
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T100902Z-eb14c08eb7/package.json`, SHA-256 `4d43ca9c6c40375a174086b51f6454f04f13fa2077f9033c9ccfe7a2b180c649`
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T100902Z-eb14c08eb7/verification-plan.json`, SHA-256 `eca46bd7e6dd15e4ccac215cb261ee20e47ef01b70a8f04955fc947f7e2f0144`
- Normative specification: `specs/YII2-JOBS-CONSOLE-001.md`, acceptance A1-A6
- Public seam: production Yii2 console `jobs/worker`, `jobs/scheduler`, `jobs/health` and isolated production Compose lifecycle
- Prior findings: none; initial review
- Verdict: `CHANGES_REQUESTED`

## Evidence inspected

- `php tests/Yii2/yii2_jobs_console_001_test.php` -> `INTENDED_RED`, record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789121284796052000-a6cebacb4e9646379546b70184962528.json`. The retained run matches source `7645415710c28906f3551fabb1b6ca373f77998e2ab02958ceae8ab6264320c6`, has no source drift, and fails deterministically at the first intended boundary mismatch: Compose still invokes `rapid-pilot/jobs-entrypoint.php worker` instead of `php bin/yii jobs/worker --interactive=0`. This is an intended missing-behavior failure, not setup failure.
- `python3 tests/Deployment/pilot_jobs_compose_001_test.py` -> `GREEN`, record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789121289743394000-4758b6d420574388ab827be7cb4e0653.json`. The retained run matches the same exact source with no drift and proves the existing rapid-pilot oracle's isolated delivery, heartbeat health, durable lifecycle, restart and schedule-key deduplication behavior.
- The package, snapshot manifest, verification input, generated plan, normative spec, root-authored test and existing oracle were reviewed as one bound candidate. The planned full CI remains future integration evidence and is not treated as Gate 3 approval or GREEN.

## Findings

1. **HIGH — The explicit A1/A2/A6 rejection matrix is incomplete.** Locations: `specs/YII2-JOBS-CONSOLE-001.md:11-19,35-37`; `tests/Yii2/yii2_jobs_console_001_test.php:26-35`. The new test exercises one valid manifest followed by only a missing-manifest rejection. It does not exercise an invalid route, invalid option, relative state root, unreadable/not-ready manifest, multiple ready manifests, or invalid/empty process prefix, although the contract explicitly requires closed exit `64` behavior for these inputs and A6 names ambiguous manifest and invalid prefix. A permissive implementation that validates only manifest existence would pass. Correction: add table-driven real-subprocess cases for each distinct validation family, asserting exact exit/stdout/stderr and no staged file or persisted facts.

2. **HIGH — A1/A4 success and health-state behavior is not sensitive at the declared Yii seam.** Locations: `specs/YII2-JOBS-CONSOLE-001.md:13,25-27,35-37`; `tests/Yii2/yii2_jobs_console_001_test.php:26-31`; existing `tests/Deployment/pilot_jobs_compose_001_test.py`. Every direct Yii invocation is deliberately pointed at an unavailable DB and only checks the generic exit-70 response. The existing Compose oracle checks container health and durable rows, but does not assert the exact Yii command's success JSON, healthy JSON, queue counters, or distinct stale/missing-heartbeat unhealthy projection. A controller that hard-codes the tested failure JSON while returning malformed success/health output can pass. Correction: execute the real Yii commands against an isolated Jobs fixture and assert independently derived exact worker/scheduler success output, healthy health output/counters, and stale and missing heartbeat responses, including one-object/newline stdout and empty sanitized stderr.

3. **HIGH — A2's secret-access boundary and no-facts rejection rule are not observed.** Locations: `specs/YII2-JOBS-CONSOLE-001.md:17-19`; `tests/Yii2/yii2_jobs_console_001_test.php:26-35`. Passing the same readable Bitrix config to all three commands and checking only that its token is absent from output does not prove that scheduler and health never read it. The test's only rejection-side state assertion inspects the staging temp directory; it does not snapshot job, heartbeat, scheduler, or workforce facts before and after rejection. Correction: make the Bitrix config access-observable (for example, an unreadable/failing sentinel that worker must need while scheduler/health remain unaffected) and compare all named persisted fact families before/after representative invalid invocations. Exercise worker staging cleanup on normal and rejected/exceptional exits, not only the current unavailable-DB path.

4. **HIGH — The claimed transitive runtime-closure check is only a shallow source-text allowlist.** Locations: `specs/YII2-JOBS-CONSOLE-001.md:29-31,35-37`; `tests/Yii2/yii2_jobs_console_001_test.php:37-38`. The regex reads four predetermined files, so a transitive dependency loaded by another PHP file, Composer/bootstrap configuration, a shell command, an indirect/dynamic include, or another `rapid-pilot` entrypoint is invisible. It also fixes planned file names while claiming to observe the public runtime seam. Correction: instrument the real Yii subprocess/Compose boundary and assert its actual loaded-file/process closure contains no `rapid-pilot` PHP or shell entrypoint; keep a static architecture check only as supplemental evidence.

5. **HIGH — A3's failure/recovery matrix is only partially covered by the existing oracle.** Locations: `specs/YII2-JOBS-CONSOLE-001.md:21-23,35-37`; existing `tests/Deployment/pilot_jobs_compose_001_test.py`. The oracle establishes ordinary delivery, persistence across `docker compose restart`, healthy recovery, and no duplicate `schedule_key`, but it does not make lease loss, retry, outbox deduplication, bounded SIGTERM shutdown, or in-flight claim disposition observable. Declaring `stop_signal`/grace-period in rendered Compose and issuing `restart` does not prove the process exits within the bound or preserves the promised lease behavior. Correction: extend the isolated lifecycle fixture or bind focused existing Jobs tests in the verification input to cover each inherited behavior, with an in-flight termination/lease scenario and observable timing/fact assertions. If a behavior is intentionally delegated entirely to already-existing focused tests, identify those exact tests and expected evidence in the plan rather than relying on a broad future regression statement.

## Confirmed without findings

- The OpenSpec change, owner goal, normative spec, verification input and generated plan are coherently linked to issue #76 and `YII2-JOBS-CONSOLE-001`; the owner scope is bound before implementation.
- The public entrypoints and durable-history intent are stated, the production implementation boundary is separated from `rapid-pilot`, and deployment/stand cutover remains explicitly out of scope.
- The retained RED is exact-source, deterministic, and fails for the intended old Compose entrypoint. The existing oracle is exact-source GREEN and useful, but it does not close the complete new-seam acceptance matrix above.
- The new test is registered in `tools/verification/suites.tsv`; the generated plan selects focused governance/unit checks and one future full exact-source CI. `UNKNOWN` integration/CI/deployment state is not approval.

## Required changes

Correct all five findings in one complete Gate 2 candidate, capture new exact-source intended RED (and bound existing-oracle evidence), regenerate the package if its inputs change, and submit it for independent Gate 3 rereview before production implementation.

---

## Gate 3 rereview v2 — 2026-09-11

- Reviewer: `/root/gate3_yii2_jobs_console`
- Test author: `/root`
- Reviewed source: base `547cf15a9b2055ac26d50bce17fe82f284c41768` + retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T101553Z-f95c2825b3/snapshot/source.patch`, patch SHA-256 `88583d781fdee978a979c6e1115dcf79c73f3dcb714a69d1eccdf99b646c2970`, harness source digest `b9d68d5061cb1674d1c7121c10f8b1ea36d52a81252602e227adf3f90b72eaa4`
- Correction delta: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T101553Z-f95c2825b3/delta.patch`, SHA-256 `bf0e322efc8678787adfc19ad415a307f3efc4ddfa82c9a1081b90bb93c751d4`
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T101553Z-f95c2825b3/package.json`, SHA-256 `b0c8ea051a59a6fc1da4e687f8a195d1347289d97a580da712eb6b270986978e`
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T101553Z-f95c2825b3/verification-plan.json`, SHA-256 `ed26abae8a42e48c705c0d91c37236512f9b74e8682831eb0edf553e22bbd339`
- Previous reviewed snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T100902Z-eb14c08eb7/snapshot`
- Prior verdict superseded for this exact corrected candidate: `CHANGES_REQUESTED`
- Verdict: `CHANGES_REQUESTED`

### Exact-source evidence

All seven retained records match source `b9d68d5061cb1674d1c7121c10f8b1ea36d52a81252602e227adf3f90b72eaa4` at start and end with `source_drift=false`:

- `php tests/Yii2/yii2_jobs_console_001_test.php` -> `INTENDED_RED`, record `1789121616034746000-65d8a2c97a0643e49943d212faf6ec65`; it still fails first for the intended old production Compose entrypoint.
- `python3 tests/Deployment/pilot_jobs_compose_001_test.py` -> `GREEN`, record `1789121698935738000-e23e2895aad24afd8e9a9dd51af2fb2e`.
- `php tests/Jobs/jobs_runtime_cli_001_test.php` -> `GREEN`, record `1789121628110996000-7f748e14a8fd4555a684201729271c6d`.
- `php tests/Jobs/worker_signal_runtime_001_test.php` -> `GREEN`, record `1789121628091748000-5350b02d5b364679b5075c14482f39c0`.
- `php tests/Jobs/worker_lease_loss_process_001_test.php` -> `GREEN`, record `1789121628088177000-a31f0e71399e47918e93e09d95c92aa6`.
- `php tests/Jobs/jobs_runtime_workforce_retry_cli_001_test.php` -> `GREEN`, record `1789121628099515000-0f55ac8790a54b22a12970ecec99d1ec`.
- `php tests/Jobs/outbox_delivery_lifecycle_001_test.php` -> `GREEN`, record `1789121628102492000-b23995ff418d48f0bdbc38d3931c4bfc`.

### Prior findings disposition

1. **Partially corrected.** Unknown route/option, relative root, missing/not-ready/ambiguous manifest, and empty/invalid prefix are now exercised with exact closed transport assertions. The normative A6 matrix still explicitly requires an unreadable manifest, but no unreadable-file case exists.
2. **Not corrected at the declared new seam.** Existing `bin/fmonitor2-jobs.php` tests cover owner success and health projections, but the new Yii commands are still exercised only against an unavailable DB. Thus Yii success serialization and healthy/stale/missing-heartbeat projections remain insensitive.
3. **Partially corrected.** The missing-secret sentinel distinguishes worker access from scheduler/health non-access. Persisted no-facts behavior on Yii rejection is still not observed, and cleanup is still covered only for the unavailable-DB exit rather than a successful/controlled completion.
4. **Partially corrected.** `get_included_files()` now observes actual PHP includes from a real Yii subprocess, resolving the shallow PHP allowlist problem. It cannot observe a spawned PHP/shell entrypoint, while A5 explicitly excludes transitive PHP and shell entrypoints; rendered Compose checks only the top-level command.
5. **Corrected.** The verification input now binds exact existing tests for runtime CLI behavior, bounded SIGTERM/in-flight disposition, lease loss, workforce retry, and outbox retry/deduplication. Their retained exact-source GREEN evidence plus the Compose oracle closes the inherited Jobs-owner matrix without duplicating it in the Yii transport test.

### Findings

1. **HIGH — Yii success and health projections remain untested at the public Yii seam.** Locations: `specs/YII2-JOBS-CONSOLE-001.md:11-13,25-27,35-37`; `tests/Yii2/yii2_jobs_console_001_test.php:26-30`; `tests/Jobs/jobs_runtime_cli_001_test.php:6-11`. The bound Jobs CLI test verifies the pre-existing `bin/fmonitor2-jobs.php` owner, not `php bin/yii jobs/...`. Every new Yii invocation with a valid manifest is forced to DB failure and asserts only exit 70. An adapter can corrupt success JSON, omit health counters, or map healthy/stale/missing heartbeat states incorrectly while delegating failures correctly and still pass. Correction: run real Yii worker/scheduler/health commands against an isolated database/Jobs fixture and assert the exact success JSON and healthy, stale-heartbeat, and missing-heartbeat JSON/exits at that seam. The existing owner tests may supply independently derived expected values but cannot substitute for exercising the adapter.

2. **HIGH — Rejection immutability and the remaining configuration case are not demonstrated.** Locations: `specs/YII2-JOBS-CONSOLE-001.md:15-19,35-37`; `tests/Yii2/yii2_jobs_console_001_test.php:33-40`. The corrected cases run with an unreachable database, so no before/after comparison can prove that invalid Yii invocations create or modify no job, heartbeat, scheduler, or workforce facts. The new A6 wording also explicitly promises an unreadable manifest, but the test covers absent and semantic-invalid files only. Cleanup is asserted for DB failure/rejection, not the promised successful or controlled process completion. Correction: use the isolated DB fixture to snapshot every named fact family around representative rejected invocations, add a genuinely unreadable manifest case with a portable access-observable fixture, and assert staged-secret cleanup after a controlled successful/normal worker completion as well as failure.

3. **MEDIUM — Runtime closure remains blind to spawned rapid-pilot entrypoints.** Locations: `specs/YII2-JOBS-CONSOLE-001.md:29-31,35-37`; `tests/Yii2/yii2_jobs_console_001_test.php:16-22,42`. The included-file trace correctly catches PHP includes in the Yii process, and the Compose model excludes `rapid-pilot` from top-level entrypoint/healthcheck arrays. Neither check catches a Yii/runtime component executing `php rapid-pilot/...`, `sh rapid-pilot/...`, or another indirect child process. Such an implementation violates the explicit PHP/shell transitive-production-load contract while passing. Correction: add process-execution instrumentation/a controlled sentinel that fails if any rapid-pilot PHP or shell entrypoint is opened or spawned during the real Yii/Compose invocation, or narrow the normative claim to the closure that can be observed reliably and justify the boundary.

### Confirmed without additional findings

- The corrected package and generated plan are coherently rebound; all seven evidence records are exact-source and the intended RED is deterministic rather than setup-related.
- Secret non-access by scheduler/health is now sensitive through a missing worker-only config while worker rejects it.
- Existing Jobs-owner tests are appropriate independent oracles for lease, retry, outbox, signal and durable lifecycle behavior and are explicitly bound rather than merely mentioned as future regression.
- CI, Gate 5, merge, deployment and stand cutover remain `UNKNOWN` and are not implied by this rereview.

### Required changes

Correct all three current findings and submit a newly bound exact-source package for Gate 3 rereview before production implementation.

---

## Gate 3 rereview v3 — 2026-09-11

- Reviewer: `/root/gate3_yii2_jobs_console`
- Test author: `/root`
- Reviewed source: base `547cf15a9b2055ac26d50bce17fe82f284c41768` + retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T102122Z-769fcc51a2/snapshot/source.patch`, patch SHA-256 `f5f1594a6e7ca4bc14d95be59b3b4b8f8d29387f2089349f575b0fde51dd4df2`, harness source digest `37cfb7983d860416e94bc286608103024e8ac6c6c1eb23e509d1777672a6d369`
- Correction delta: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T102122Z-769fcc51a2/delta.patch`, SHA-256 `16e8f7df78ff5b670f7e2014a5da10f5c679fbfcfbef4438e002d79fd0a8daed`
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T102122Z-769fcc51a2/package.json`, SHA-256 `0984e0f71777dcf929fdf246a18c4f32482e900cd5c89f770fc70da029d4b68b`
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T102122Z-769fcc51a2/verification-plan.json`, SHA-256 `918b533ecbe23bd0afb239ba15f6096aa8fa2b84b803cf136fb5ce1afe79e7e0`
- Previous reviewed snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T101553Z-f95c2825b3/snapshot`
- Prior verdict superseded for this exact rebuilt candidate: `CHANGES_REQUESTED`
- Verdict: `CHANGES_REQUESTED`

### Exact-source evidence

All eight retained records match source `37cfb7983d860416e94bc286608103024e8ac6c6c1eb23e509d1777672a6d369` at start and end with no source drift. `tests/Yii2/yii2_jobs_console_001_test.php` is intended RED on the old Compose entrypoint (record `1789122007933229000-6ef972db634b4fb092ea62a30384bd43`); the new DB-backed `tests/Yii2/yii2_jobs_console_db_001_test.php` is independently intended RED because the absent Yii jobs route does not emit a JSON object (record `1789122000538821000-64512dc9e3684b6f91e8a3ec86434d28`). The Compose oracle and the five bound Jobs owner tests are GREEN in records `1789122020001470000-76eafeb9030d4e0db6bad9122c2e9cbe`, `1789122020002997000-ab0ca164a552437b8046a02134e1635a`, `1789122020001471000-f71ec585ef784e00abb6fdb0acd29c94`, `1789122020005331000-d9a9c45beeed4eeeb4b1715497ed68de`, `1789122020011363000-65904a1f509e4332aba0054e816566b4`, and `1789122020008117000-b9a9adc1ee1742e4a5acea1a9c7337f7`.

### Prior findings disposition

1. **Partially corrected.** The DB-backed real Yii test now makes healthy and missing-heartbeat health behavior observable at the new seam, but it still does not exercise worker or scheduler successful/graceful completion output at that seam.
2. **Partially corrected.** Whole-schema fact snapshots now prove invalid route/option rejection is non-mutating, and a non-file manifest robustly exercises the unreadable input family. The test still does not prove staged-token cleanup after successful/graceful worker completion.
3. **Corrected.** The contract is now explicitly bounded to actual included PHP files plus declarative Compose and repository-owned process-construction sites. The included-file trace and complete `app/Jobs`, Yii command, launcher and handler inventory are sensitive to that narrowed claim; arbitrary external OS children are expressly excluded rather than implicitly approved.

Earlier rejection-matrix and inherited Jobs lifecycle findings remain resolved: unknown route/options, relative root, missing/not-ready/ambiguous/non-file manifest and invalid prefixes are covered; scheduler/health are sensitive to accidental Bitrix-config reads; exact bound existing tests cover SIGTERM, in-flight disposition, lease loss, retry and outbox deduplication; Compose covers durable restart and schedule-key deduplication.

### Finding

1. **HIGH — A1/A2 still lack successful worker/scheduler completion coverage through the Yii seam.** Locations: `specs/YII2-JOBS-CONSOLE-001.md:11-19,35-37`; `tests/Yii2/yii2_jobs_console_001_test.php:26-31,39-40`; `tests/Yii2/yii2_jobs_console_db_001_test.php:9-14`; `tests/Deployment/pilot_jobs_compose_001_test.py`. A1 states that success for the accepted Yii commands emits exactly one closed JSON object plus newline, and A2 requires the staged worker token file to be removed on every completion. The rebuilt DB test exercises only `jobs/health`; direct worker/scheduler Yii invocations still cover unavailable-DB failure only. The Compose oracle proves services run and restart, but neither captures/asserts their graceful exit JSON nor inspects the staged token after a successful/graceful worker completion. A Yii adapter could format worker/scheduler success incorrectly or clean the staged secret only on exceptions and all current tests would pass. Correction: run real `php bin/yii jobs/worker` and `jobs/scheduler` against the isolated DB under bounded controlled termination/completion, assert each command's exact successful/graceful exit/stdout/stderr contract, and assert the worker's staging directory is empty afterward. This single public-seam scenario may reuse the established signal/release fixture and expected owner result.

### Confirmed without additional findings

- Both intended REDs are deterministic missing-behavior failures, not setup failures, and the complete verification input/plan/inventory is coherently rebound to the exact snapshot.
- DB-backed health expectations are independently established by inserted heartbeat facts and explicit counter values; invalid invocations compare all prefixed tables before/after.
- Runtime-closure scope is now precise and its executable checks match the narrowed normative statement.
- CI, Gate 5, merge, deployment and stand cutover remain `UNKNOWN`; this review grants none of them.

### Required change

Add the one bounded real-Yii worker/scheduler graceful-success and worker-secret-cleanup scenario, capture exact-source intended RED, and submit the refreshed package for Gate 3 rereview before implementation.

---

## Gate 3 rereview v4 — 2026-09-11

- Reviewer: `/root/gate3_yii2_jobs_console`
- Test author: `/root`
- Reviewed source: base `547cf15a9b2055ac26d50bce17fe82f284c41768` + retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T102458Z-e798054813/snapshot/source.patch`, patch SHA-256 `678dac02ba1151612dcb30b7dd5901a58c6a9f76a3b639aef34cef2fb39341da`, harness source digest `cd6446bd6df6c562036634067a6b638c9fc1f2eef3447de95c610f75e873938b`
- Focused delta: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T102458Z-e798054813/delta.patch`, SHA-256 `946bd7ccdbf52bf02773d73d28da885e20277126e2fe87a8eb5d5fe7ad907d78`
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T102458Z-e798054813/package.json`, SHA-256 `12c0a64f90523741d8c716c05d72ab151ca7ce95384a6da0889d0f6fca51512a`
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T102458Z-e798054813/verification-plan.json`, SHA-256 `9d4723fa26695c3ec7ab33a004b9bd92ded5a7efd7d3e2d23014875c399c28a9`
- Previous reviewed snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T102122Z-769fcc51a2/snapshot`
- Prior verdict superseded for this exact focused candidate: `CHANGES_REQUESTED`
- Verdict: `CHANGES_REQUESTED`

### Evidence and prior-finding disposition

All eight package records match exact source `cd6446bd6df6c562036634067a6b638c9fc1f2eef3447de95c610f75e873938b` with no source drift. The two Yii tests retain intended missing-route/old-entrypoint RED (`1789122243200875000-c0cac111ef5a48fcadaded96d7aa9eda` and `1789122233464027000-5db40e16486240a6820c7c9b25340caa`); the Compose oracle and five explicitly bound Jobs-owner tests remain GREEN (`1789122243209847000-72ed1b9495b14813a66f9ba59573a591`, `1789122243210049000-893ce8e96471437da14d89975e418d92`, `1789122243212290000-816cb18ceda74740a671e3e277798131`, `1789122243222341000-15443423edf045899d13f5e4ae3a7dd4`, `1789122243238527000-bb6b69638b0f451a9c13ce1e2b134357`, and `1789122243229678000-85d2881782b64c79a6f5c8b9c2f1ebb7`).

The sole v3 behavioral finding is substantively corrected: the new public-seam processes wait for their independently observable heartbeats, receive SIGTERM, and assert exact exit/stdout/stderr for worker and scheduler; the worker additionally asserts an empty staging directory after normal graceful exit.

### Finding

1. **HIGH — The new fixture deterministically inserts duplicate heartbeat primary keys after the long-running commands succeed.** Location: `tests/Yii2/yii2_jobs_console_db_001_test.php:11-13`. `yjdStop()` for worker waits until `worker:pilot` exists, and the scheduler call waits until `scheduler:pilot` exists. Immediately afterward, line 13 executes a plain `INSERT INTO ...fm2_worker_heartbeats VALUES('worker:pilot', ...),('scheduler:pilot', ...)`. Both keys therefore already exist by construction. Once the Yii routes are implemented far enough to satisfy the two new graceful-process assertions, this statement will fail with a duplicate-key database exception before the healthy/missing-heartbeat checks; the intended RED currently masks that latent setup failure by stopping earlier. Correction: update the two existing heartbeat rows to the controlled current timestamp (or use an explicit upsert), then preserve the existing health assertions. Capture refreshed intended RED on the missing Yii behavior.

### Confirmed without additional findings

- The focused long-process helper has bounded readiness and graceful-exit deadlines, signals the real Yii subprocess, captures exact output, and distinguishes setup timeout from behavioral mismatch.
- Expected worker/scheduler JSON is literal and independent of the planned controller implementation; secret cleanup is observed through an isolated `TMPDIR`.
- All earlier rejection, no-facts, health, secret-read, runtime-closure, restart, lease, retry, outbox and signal findings remain resolved by the complete candidate.
- CI, Gate 5, merge, deployment and stand cutover remain `UNKNOWN` and receive no approval here.

### Required change

Replace the duplicate heartbeat insert with a deterministic update/upsert and refresh the exact-source RED package. No further behavioral expansion is requested for the agreed slice.

---

## Gate 3 fixture-determinism rereview v6 — 2026-09-11

- Reviewer: `/root/gate3_yii2_jobs_console`
- Test author: `/root`
- Reviewed source: base `547cf15a9b2055ac26d50bce17fe82f284c41768` + retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T103638Z-78afdff72f/snapshot/source.patch`, patch SHA-256 `a7c6413af9231efbcb75dcf92b9c0c79358d8f93a3dae31c4d7e009912a662c1`, harness source digest `019c69abd126c56faa3c7e653f2ae5b5c731839515d7456163ca06357505b414`
- Fixture-only delta: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T103638Z-78afdff72f/delta.patch`, SHA-256 `b7ac978b9fb87a663e2a89760fc5fe4aa342d1128fb0f24e5556e7ec9649af09`
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T103638Z-78afdff72f/package.json`, SHA-256 `096b48e59c9bd17ffad99c63e90429b0638bb21e2a4b35f09a0a5a7f67d79fb6`
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T103638Z-78afdff72f/verification-plan.json`, SHA-256 `bceb4a55a7866b92346d537d02ab38c4b1a5c56ca72b97b832841415ce8c51bc`
- Previous approved preimplementation snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T102723Z-e964ad30cf/snapshot`
- Verdict: `APPROVED`

### Findings

None. Updating only ready jobs' `available_at_utc` to the same controlled current instant used for heartbeat refresh removes time-of-hour dependence from the expected `overdueReady=0`. It does not erase, complete or otherwise alter a job lifecycle, and the later rejection snapshot is taken after this fixture normalization. Scheduler creation/deduplication, durable restart, retry and lease behavior remain independently covered by the unchanged bound oracle tests.

### Evidence and scope

All eight retained records match source `019c69abd126c56faa3c7e653f2ae5b5c731839515d7456163ca06357505b414` with no source drift: Yii console and DB tests are intended RED in records `1789122945811523000-8ee00d6e5d6846b198e2a2eecdb96b0e` and `1789122945803237000-f320004891e0497e91691ca26f601246`; the Compose oracle and five Jobs-owner tests are GREEN in records `1789122945831414000-07de30982af649e9a998a5cf4b7075fd`, `1789122945802836000-d22ef25040624c0788de9d11aeebeb76`, `1789122945805268000-654a4aad553c4b7dbde48eead542555f`, `1789122945817999000-91039e5c1e584a138a811c64f5df5cef`, `1789122945811957000-8e8d516f317e44d5a80f134e35bb9cfd`, and `1789122945837274000-4ec4b8093e084a1db488a1cfa4b496ce`.

The full prior Gate 3 behavior matrix remains approved for this corrected exact source: Yii worker/scheduler graceful success and cleanup, health success/failure, closed rejection and no facts, configuration validation, secret isolation, bounded runtime closure, and the inherited durable Jobs lifecycle are all sensitive and deterministic. This approval advances only to Gate 4; Gate 5, CI, merge, deployment and stand cutover remain `UNKNOWN`.

---

## Post-Gate 5 CI correction test-delta review — 2026-09-11

- Reviewer: `/root/gate3_yii2_jobs_console`
- Scope: changed expectations in `tests/InstallationProcess/pilot_jobs_startup_001_test.php`, `tests/Runtime/yii2_runtime_001_test.py`, and `tests/Verification/quality_graph_current_workflow_001_test.py`; production Yii/Jobs tests and implementation remain unchanged
- Reviewed exact source digest: `749b9df2c0ff0ea6ecc6a809e89a89815754649bfabd593ebb9081497025d495`
- CI failure inventory: run `34592086087`
- Verdict: `CHANGES_REQUESTED`

### Finding

1. **HIGH — The workflow test does not assert the essential harness-mode publication guard.** Locations: `tools/delivery/render-current-quality-graph.py:65`; `tests/Verification/quality_graph_current_workflow_001_test.py:67-83`. The correction changes `quality-results.if` to include `needs.plan.outputs.mode != 'harness'` and adds `harness` to `needs`, but the test asserts only `always()`, pull-request event and the new dependency list. Because drift validation deliberately treats the renderer as authority, the renderer and generated workflow can regress together by dropping the mode predicate; all current assertions and generated-hash checks would still pass, and `quality-results` would again run during harness mode against skipped category results. Correction: assert the exact harness exclusion (at minimum `needs.plan.outputs.mode != 'harness'`) within the `quality-results` block, alongside the dependency assertion, then rerun the focused workflow test on the rebound source.

### Corrected expectations confirmed

- The startup test now expects the exact approved Yii worker/scheduler entrypoints and health command while retaining signal, grace, stale-readiness and runtime lifecycle assertions; it does not weaken behavior.
- Adding `bin/fmonitor2-yii.php` to the Yii lifecycle source set makes the existing `yii\\console\\Application` ownership assertion observe the real delegated bootstrap rather than the thin `bin/yii` wrapper.
- The aggregate command assertion correctly requires both `--mode "$MODE"` and the existing full/results inputs.

### Evidence

The changed startup, runtime and current-workflow tests are exact-source GREEN in records `1789125378097115000-7e807893173d489e8fd060d60fa6eb99`, `1789125379772668000-8b0a2aec635847b3b1ab5614c9ad7495`, and `1789125380849879000-6dbe6f4b2df54a56a5e4303ff0a2ae81`. Verification CI and inventory are GREEN in records `1789125381869405000-baf8e2dfb82a408487941a2037ac40f6` and `1789125410109900000-6b39dcc93eae4bd29c5bacd139e51926`. All five records match source `749b9df2c0ff0ea6ecc6a809e89a89815754649bfabd593ebb9081497025d495` at start/end with no drift. GREEN does not close the missing sensitivity assertion above.

### Required change

Add the one exact harness-mode exclusion assertion and submit the rebound test delta for focused Gate 3 rereview. No other test changes are requested.

---

## CI workflow test-sensitivity rereview — 2026-09-11

- Reviewer: `/root/gate3_yii2_jobs_console`
- Scope: sole correction in `tests/Verification/quality_graph_current_workflow_001_test.py`
- Exact source digest: `bbc0e1b3c692aee2f0e2c33bb6e8e47aea9f6cfcd9a2a9e85b8959f6756741a0`
- Verdict: `APPROVED`

### Findings

None. The `quality-results`-scoped assertion now explicitly requires `needs.plan.outputs.mode != 'harness'` alongside the harness dependency. A renderer/generated-workflow regression that republishes skipped category outcomes during harness mode is therefore observable even if both generated artifacts drift together. The aggregate-mode and all prior reporting, permissions, node-count and artifact expectations remain intact.

### Evidence

`python3 tests/Verification/quality_graph_current_workflow_001_test.py` is GREEN on the exact source with no drift: record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789125539453626000-9db9c708d74d471e896eb4571a16b971.json` (5 tests, 0 failures). The sole prior Gate 3 finding is resolved; no further test changes are required for this CI correction delta.

---

## Gate 3 rereview v5 — 2026-09-11

- Reviewer: `/root/gate3_yii2_jobs_console`
- Test author: `/root`
- Reviewed source: base `547cf15a9b2055ac26d50bce17fe82f284c41768` + retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T102723Z-e964ad30cf/snapshot/source.patch`, patch SHA-256 `df828a0c9cd1cff778b3b74e383ca0ca744e3d90e251d3f549390b56ecc63278`, harness source digest `a52b6d6caf5e06bb3013ab36cd0ffa50772dca8eae376d786a1c398dca920fcf`
- Fixture-only delta: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T102723Z-e964ad30cf/delta.patch`, SHA-256 `8d3e5fe119b2f45223257c9addfa35ffb153b2d981403d44fd26c8c07d74e969`
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T102723Z-e964ad30cf/package.json`, SHA-256 `46498263431be9b95bdb3a8510087da386e61a423369d6074b4fd82785fd5359`
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T102723Z-e964ad30cf/verification-plan.json`, SHA-256 `ae4ee8d29a5df3f5c0378288bf1014f76e968d9e5c668489ddd47c1119f27071`
- Previous reviewed snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T102458Z-e798054813/snapshot`
- Prior verdict superseded for this exact fixture-corrected candidate: `CHANGES_REQUESTED`
- Verdict: `APPROVED`

### Evidence and disposition

All eight retained records match exact source `a52b6d6caf5e06bb3013ab36cd0ffa50772dca8eae376d786a1c398dca920fcf` with no source drift. The Yii boundary tests remain intended RED for the absent Yii jobs route/old Compose entrypoints in records `1789122384370761000-3c9960cddf5947ffae6402a5da3a3dc7` and `1789122384370882000-52ac7c13619b4e0294c26c80225a0f7e`. The existing Compose and Jobs-owner oracles remain exact-source GREEN in records `1789122384372325000-2a08b8965a3940f9b64407663a6558ea`, `1789122384380650000-6ba1926c4ed142e89d4e94d47dc95013`, `1789122384386828000-c83da2ce0a3f4f7eb42ea53120191aef`, `1789122384392121000-6c00a66914ef46f9825eeae9bd5ce5c3`, `1789122384394572000-466fd7e868c3492290ad540f8a392331`, and `1789122384404356000-1d3f01c73b1045aa8c6328d02e17a686`.

The sole v4 fixture defect is corrected: `ON DUPLICATE KEY UPDATE observed_at_utc=VALUES(observed_at_utc)` deterministically refreshes the worker and scheduler rows already created by the graceful-process scenarios, preserving the subsequent healthy and missing-heartbeat assertions without weakening them.

### Findings

None. The complete candidate was rechecked for traceability, public-seam sensitivity, exact JSON/exit/stderr behavior, validation and no-facts rejection, worker-only secret access and cleanup, healthy/missing heartbeat projection, graceful SIGTERM, lease loss, retry/outbox deduplication, durable Compose restart/schedule deduplication, deterministic fixtures, runtime closure and verification-plan/inventory binding. All prior findings are resolved.

### Required changes

None. Gate 3 is approved for exact source `a52b6d6caf5e06bb3013ab36cd0ffa50772dca8eae376d786a1c398dca920fcf`. This approval advances the slice to Gate 4 implementation only; Gate 5, CI, merge, deployment and stand cutover remain `UNKNOWN` and are not approved or implied.

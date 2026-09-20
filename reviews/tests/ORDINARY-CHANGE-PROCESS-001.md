# Test review: ORDINARY-CHANGE-PROCESS-001

- Reviewer: independent Gate 3 agent `/root/ordinary_gate3`
- Test author: root
- Reviewed base: `ae0a9596ee3b18952832949015d3d345459dfc05`
- Candidate source: `337d9398391ffb74e3bc1a4b8a74fa42fe487d61d363fe154d5572324c14f369`
- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T163420Z-aa9b07ae75/package.json`
- Specification: `specs/ORDINARY-CHANGE-PROCESS-001.md`
- Public seam: `python3 tools/delivery/harness.py prepare|state`, focused evidence, reviewer package, and existing exact-source CI consumer
- RED command: `python3 tests/Delivery/ordinary_change_process_001_test.py`
- Retained RED record: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789922019137388000-6d2e51b5cdb849a4baefede438b6b2be.json`
- RED result: exit 1; 7 tests ran, with four failures at the absent ordinary lifecycle, FAST closure, mandatory-check seam, and correction-contract behavior
- Verdict: `CHANGES_REQUESTED`

## Findings

1. **BLOCKING — the acceptance does not exercise the required end-to-end public seam.** `tests/Delivery/ordinary_change_process_001_test.py:162-177` stops at root `harness.py prepare`. It never supplies focused evidence, prepares a reviewer package, invokes `state`/admission, or reaches the CI selection consumer required by `specs/ORDINARY-CHANGE-PROCESS-001.md:57`. Cases F and J/K (`:247-260`) merely search implementation source for marker strings, so inert or dead code could satisfy them. Replace these source inspections with behavioral assertions through the public harness/evidence/reviewer/CI route, including fail-closed mandatory-check outcomes and correction recomputation.

2. **BLOCKING — sensitive exclusions and mixed-file precedence are not adequately tested.** `ordinary_change_process_001_test.py:210-230` covers an auth path, a migration path, and an author-declared `UNKNOWN` presentation change. It does not prove that a sensitive method in a mixed file overrides an `ORDINARY` claim, nor does it behaviorally cover test execution/admission/evidence-validity changes (matrix H). Add deterministic deltas for mixed-file sensitive detection and the protected policy/test-validity boundaries; assert Gate 3 + final and non-compact routing without relying on the author's sensitivity declaration.

3. **BLOCKING — FAST closure case C is incomplete.** `ordinary_change_process_001_test.py:197-208` proves only that one changed regression is selected. The fixture has no mapped consumer and exercises no required environment check, so it cannot detect an implementation that omits either part of the closure required by contract section 2.3. Add independent consumer and environment obligations, assert all are selected when complete, and assert incomplete ownership/oracle mapping falls back to full CI while retaining final-only ceremony.

4. **BLOCKING — historical replay is not a replay evaluation and no unseen analogue is present.** `ordinary_change_process_001_test.py:262-276` resolves three Git diffs, assigns their classes from a hard-coded dictionary, and checks filenames; it never submits the deltas to the planner/harness or observes lifecycle, reviews, lane, or selected checks. The fixture name `fixture-without-issue-or-path-whitelist` does not provide the independently chosen unseen module/path required by `specs/ORDINARY-CHANGE-PROCESS-001.md:58-59`; all its paths are authored into the fixture policy. Materialize deterministic replay descriptors/fixtures for #187/#194/#209, evaluate them through the public route, and add an analogous example not used to author the rule. Keep identifiers absent from production policy.

5. **BLOCKING — matrix cases A/B/G/I and E are conflated or only partially observed.** `ordinary_change_process_001_test.py:179-195` reuses one helper without proving that a new/changed application regression remains eligible (G) or that a previously unseen module/path is admitted without a whitelist edit (I). Case E (`:232-245`) checks a low-level plan mismatch and reprepare, but does not prove rejection at reviewer or CI admission. Split these into observable cases whose expected ceremony and CI results are derived from the contract.

6. **BLOCKING — expected-value independence and RED completeness are insufficient.** Source-token assertions at `:247-260` use the future implementation's chosen names as their oracle, and the historical test labels outcomes without observing them. The single aggregate RED fails early in A and does not establish that the missing public behavior reaches every negative branch; fixture reachability is not separately recorded. Use contract-derived result objects/statuses and independently authored fixture expectations, then retain refreshed exact-source RED that inventories intended failures across the complete matrix.

7. **MAJOR — OpenSpec task state is not review-ready.** `openspec/changes/extend-ordinary-change-process/tasks.md` has no explicit Done definition despite `openspec/config.yaml` requiring one, and tasks 1.2–1.4 remain unchecked although a verification input, prepared package, RED test, and historical lookup now exist. Add a clear Done definition and update task completion honestly after the corrected artifacts/evidence exist.

## Required correction

Correct the executable matrix and OpenSpec task record as described above, retain a refreshed exact-source intended RED with complete acceptance evidence, prepare a new Gate 3 reviewer package, and request independent rereview before implementation begins.

---

## Gate 3 delta rereview v2

- Reviewer: independent Gate 3 agent `/root/ordinary_gate3`
- Test author / correction author: root
- Reviewed candidate source: `14e4cbb1ba58ed3ce05d631c4bd64510da3a9f3ccf4d5600d9f4ec7ef4704c88`
- Corrected reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T164146Z-d9618972fa/package.json`
- Delta: package `delta.patch` from the prior reviewer snapshot
- Refreshed RED: `python3 tests/Delivery/ordinary_change_process_001_test.py`; retained record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789922440161416000-8e1aae56dfbe4ad4808faa7f300bd2bb.json`; 8 tests ran, 5 intended failures
- Verdict: `CHANGES_REQUESTED`

### Prior findings disposition

1. **Partially fixed; remains blocking.** Source-token assertions were replaced with behavioral harness/CI calls. `evidence_for()` and `reviewer_and_ci()` now run focused obligations, prepare a reviewer package, and call the real CI planner (`tests/Delivery/ordinary_change_process_001_test.py:181-210`); F and J/K are behavioral (`:318-351`). However only C invokes the full focused-evidence → reviewer-package → CI-selection route. The contract requires that route across the acceptance fixtures, while the remaining ordinary, sensitive, correction, replay and unseen cases stop at prepare or a lower-level consumer. Extend the route assertion across the matrix, including aggregate/state admission where relevant.

2. **Fixed.** Mixed-file presentation content containing a sensitive `grantPermission` method is submitted under an `ORDINARY` claim and must retain Gate 3 + final (`:286-291`). A delivery/admission policy path is also required to remain sensitive (`:293-296`), and E proves stale reviewer preparation is rejected (`:298-316`).

3. **Partially fixed; remains blocking.** C now requires the mapped consumer and verifies the real CI selection (`:231-250`), while B proves incomplete mapping falls back to full CI without Gate 3 (`:252-262`). The fixture still has no required environment probe/check for its FAST unit profile and asserts no environment obligation. An implementation that drops required environment closure can still pass. Add a deterministic required environment check and assert its selection/execution alongside the changed regression and consumer.

4. **Partially fixed; remains blocking.** `replays.json` and `:353-388` add descriptors, keep issue identifiers out of policy, invoke prepare, and add a genuinely unseen glob-matched path. But the real historical diffs are only checked for non-emptiness/two filenames; evaluation is then performed on synthetic generic fixture paths with the descriptor's own class copied into the declaration. The test does not evaluate the actual historical delta paths, independently validate their class, or observe each replay's lane/selected checks. Materialize replay inputs from each actual Git delta (or a faithful independently reviewed projection), assert class + ceremony + CI result, and keep the expected matrix independent from the declaration under test.

5. **Partially fixed; remains blocking.** B and E now have distinct observable cases, and the unseen I case is present. G still changes the application helper but not its regression, so it does not independently prove that a new/changed application regression remains compact-eligible. Add a dedicated G fixture that changes the application regression while execution/admission policy remains unchanged, and route it through reviewer/CI selection.

6. **Partially fixed; remains blocking.** Implementation-name/source-token oracles are gone and the refreshed run has five clear intended failures. Branch reachability is still incomplete: A stops on its first presentation assertion before READ/refactor/G; the historical test stops on its first replay before the other replays and unseen case; package fixture reachability remains unrecorded. Split or structure cases so every contract branch executes independently in RED and retain evidence that inventories each intended missing behavior.

7. **Partially fixed.** A clear Done definition was added and 1.2/1.3 are honestly complete. Task 1.4 is marked complete prematurely because the historical replay evaluation remains synthetic and incomplete as described above. Leave 1.4 open until faithful replay/class/ceremony/CI assertions exist; 1.5 correctly remains open pending approval.

### Required changes for v3

1. Exercise and assert a required FAST environment check.
2. Evaluate faithful historical replay deltas with independently expected class, ceremony, lane and selected-check results.
3. Run the public focused-evidence/reviewer/CI route across the acceptance fixtures, including a distinct changed-application-regression case G.
4. Make every A–L/replay/unseen branch independently reachable in RED and retain refreshed exact-source evidence.
5. Correct task 1.4 state and request another independent Gate 3 delta rereview before implementation.

---

## Owner-authorized resume disposition before v3

Owner clarification 2026-09-20 authorizes continuation after STOP and narrows
historical evidence: #187/#194/#209 require factual diff/class/risk/preserved-check
assessment, not restored historical environments, lifecycle or CI. No prior
verdict/evidence is rewritten.

1. **Fixed — end-to-end public route.** `evidence_for`, `reviewer_and_ci` and
   `positive_route` (`tests/Delivery/ordinary_change_process_001_test.py:185-247`)
   use the shipped harness runner, reviewer preparation and CI selector. Separate
   presentation, read and test/refactor methods (`:248-256`) each invoke it; FAST
   and FULL selector branches are asserted independently.
2. **Fixed — sensitive neighbors.** Permissions, schema/write, unknown sensitive
   claim, changed sensitive method in a mixed view, admission policy and a
   sensitive post-prepare delta remain separate behavioral cases (`:295-348`).
3. **Fixed — FAST environment closure.** A lightweight template-runtime
   environment executable is registered as a real consumer obligation. Case C
   asserts selection of changed regression, consumer and environment check,
   executes the environment check through harness with the required variable,
   and proves its controlled missing-environment invocation fails (`:258-281`).
   No MariaDB/browser service is added to a unit presentation fixture.
4. **Removed by owner clarification / remaining part fixed.** The v2 demand for
   three full historical replay lifecycles is explicitly withdrawn. The retained
   descriptor now records actual merge commit, independently stated
   applicable/inapplicable class, rationale and preserved checks; the test reads
   each real Git diff and does not claim re-execution (`:384-408`). The unseen
   analogue remains a separate shipped prepare/reviewer/CI route and its exact
   path is absent from policy (`:409-425`).
5. **Fixed — changed regression G and separated cases.** G changes the registered
   regression bytes relative to fixture base, asserts the path in actual delta,
   executes it as mandatory evidence, then prepares final reviewer package and
   selects FULL CI (`positive_route(... change_regression=True)`, `:218-256`).
6. **Fixed — independent RED reachability.** Presentation, read, test/refactor,
   incomplete mapping, complete FAST, sensitive groups, post-prepare, CI failure,
   correction, historical assessment and unseen analogue are distinct unittest
   methods (`:248-425`); failure in one cannot hide the others. Assertions require
   contract-specific results rather than accepting arbitrary nonzero outcomes.
7. **Fixed — lifecycle record.** Done definition remains present; task 1.4 stays
   open until this corrected evidence is accepted, and 1.5 stays open until the
   independent v3 verdict.

At this point no known v2 blocking gap remains under the clarified acceptance.
Production tooling has not been implemented.

---

## Gate 3 delta rereview v3

- Reviewer: independent Gate 3 agent `/root/ordinary_gate3_v3`
- Test author / correction author: root
- Reviewed candidate source: `5fb32d44f74efefd0cdadc04385f6b5c77ae67794fbcb9cfa2bc0a0163c9b6d4`
- Corrected reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T165333Z-92c01c7720/package.json`
- Delta: package `delta.patch` from the prior reviewer snapshot
- Refreshed exact-source RED: `python3 tests/Delivery/ordinary_change_process_001_test.py`; retained record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789923161020929000-04c62a4166a6404e987200ed26e8da90.json`; 11 tests ran, 7 specific intended failures
- Verdict: `CHANGES_REQUESTED`

### Prior findings disposition

1. **Fixed.** The three positive ordinary classes are separate tests and use the shipped `prepare -> focused evidence -> final reviewer package -> existing CI selector` route.
2. **Fixed.** Permission, schema/write, unknown risk, a sensitive mixed-file method, admission policy and post-prepare sensitive delta are separate behavioral cases.
3. **Fixed.** Case C selects the changed regression, affected consumer and lightweight environment executable, runs the selected obligations with the controlled environment, and separately proves the missing-environment failure.
4. **Partially fixed under the owner's clarified scope; remains blocking for factual accuracy and expected-value independence.** Full historical lifecycle/environment/CI replay is no longer required. However `tests/fixtures/delivery/ordinary-change-process/replays.json:3` says the real #187 merge contains `PilotHttp/auth-sensitive` neighbors and requires auth qualification. The actual `e245ba1c^..e245ba1c` changed-path inventory contains no `app/PilotHttp`, `app/IdentityAccess`, or other auth path; it contains verification policy, InstallationProcess reads, Yii views/tests, review records and a delivery fixture. The assessment therefore is not factual as required by contract section 6.2. In addition, `tests/Delivery/ordinary_change_process_001_test.py:388-407` copies `class`, `applicable`, `rationale` and `required_checks` from the descriptor into `observed`; it does not compare those claims with an independently authored expected assessment. An arbitrary or factually wrong class/risk/check claim therefore passes, contrary to Gate 2 expected-value independence.
5. **Fixed.** G changes the registered application regression bytes and includes that path in the actual delta before running the public route.
6. **Fixed.** The focused run reaches 11 independently named cases; seven fail for specific missing ordinary/correction behavior while the already-supported sensitive, stale-source, CI-admission and historical-resolution cases pass.
7. **Fixed.** The Done definition is present, task 1.4 remains open pending acceptance of corrected historical evidence, and task 1.5 remains open pending Gate 3 approval.

### Required correction

Correct the #187 factual path/risk/preserved-check assessment, and make the historical expectations independent from the descriptor values under test so a wrong class, applicability, rationale or required-check claim fails. Retain a refreshed exact-source RED package and request another independent Gate 3 delta review. No production implementation is authorized by this verdict.

### v3 blocker correction disposition

- **Fixed, first attempt for this identified cause.** #187 now describes its real
  first-parent merge diff: read projections, Yii views/tests and verification
  policy, with no PilotHttp/auth claim or auth qualification. The whole historical
  diff is inapplicable to the ordinary shortcut because it includes admission
  policy; its product subset remains a presentation/read example.
- **Fixed expected-value independence.** The test owns a separate expected matrix
  for commit, class, applicability, exact rationale, preserved checks and required
  factual paths for #187/#194/#209, then compares the descriptor and real Git diff
  against it. Descriptor values are no longer copied into expected observations.

---

## Gate 3 delta rereview v4

- Reviewer: independent Gate 3 agent `/root/ordinary_gate3_v3` (recorded role `ordinary_gate3_v4`)
- Test author / correction author: root
- Reviewed candidate source: `ee43f8e0727898e07d060a2859f628e1cb0d6d868a2a11260d8f28826c9053c9`
- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T165916Z-738f492659/package.json`
- Delta: package `delta.patch` from the v3 reviewer snapshot
- Exact-source RED record: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789923505992139000-99d1c85ac1a34f40865329a3cf216a91.json`
- Focused reviewer check: `python3 -m unittest tests.Delivery.ordinary_change_process_001_test.OrdinaryChangeProcess.test_historical_diffs_are_assessed_not_claimed_as_reexecuted` — 1 test, GREEN
- Verdict: `APPROVED`

### v3 blocker disposition

1. **Fixed — factual #187 assessment.** The descriptor and independent expectation now match the real first-parent `e245ba1c^..e245ba1c` inventory: verification policy, InstallationProcess read projections, Yii views/tests, review records and a delivery fixture. No PilotHttp/auth path or auth qualification is claimed. The complete historical diff remains inapplicable to the ordinary shortcut because it changes admission policy, while its product subset remains a presentation/read example.
2. **Fixed — expected-value independence and regression safety.** The test-owned matrix independently fixes commit, class, applicability, exact rationale, required checks and required factual paths for #187/#194/#209. It compares every descriptor claim with that matrix and verifies required path subsets against each real first-parent Git diff, so descriptor or commit drift fails without requiring historical lifecycle/environment/CI replay.
3. **No regression found.** The exact-source focused suite remains intended RED with 11 independently named tests and 7 specific missing-behavior failures; the corrected historical case is GREEN. The package's governance obligation is also GREEN.

Nonblocking editorial observation: the assertion message near `tests/Delivery/ordinary_change_process_001_test.py:424` still says `sensitive/auth history`; its assertion checks only #187 applicability and the actual descriptor/expected rationale correctly contains no auth claim. This wording does not affect Gate 2 coverage or approval.

Gate 3 is approved for this exact source. Production implementation may proceed through the prepared executor role; final review and selected exact-source CI remain required.

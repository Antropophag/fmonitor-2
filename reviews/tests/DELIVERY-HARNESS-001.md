# DELIVERY-HARNESS-001 — Gate 3 review

- Gate: 3 — independent specification and test review
- Reviewer: separately tasked `/root/consumer_evidence`; reviewer did not author the contract or tests
- Candidate snapshot: `/tmp/fmonitor-harness-gate3`, patch SHA-256 `dc6d14b14071b2934306963f39b7183f8db87d595d233ba9709067cb14e5f378`, base `4bab010f157d352f50d6b94050d80cb49d40a8a9`
- Restored source: `/private/tmp/fmonitor-harness-gate3-review`
- Additional reviewed test-only delta: live `tests/Verification/change_verification_001_test.py`, including diagnostic execution and fixture copy correction; RED `/tmp/fmonitor-harness-baseline/planner-red-v2.log`
- Additional reviewed planning delta: `.codex/**` and `.agents/**` are explicit `delivery-policy` paths; expanded valid plan `/tmp/fmonitor-harness-baseline/gate3-plan-v2.json`
- Contract: `specs/DELIVERY-HARNESS-001.md` and `openspec/changes/automatic-delivery-harness/`
- Verification plan: `/tmp/fmonitor-harness-baseline/gate3-plan.json`
- Verdict: **CHANGES_REQUESTED**

## Qualifying evidence

`delivery_harness_001_test.py` fails because the public `harness.py` seam is absent. This is qualifying intended RED for the covered behaviors, although the missing executable naturally collapses several cases onto the same absence. The planner consumer RED independently reaches the existing planner and names all five missing consumer obligations. The added diagnostic RED independently reaches the existing planner runner and proves that the requested `--diagnostic` interface is absent; its fixture-copy correction changes setup only, not an expectation. These are implementation failures, not product-test or environment failures.

The tests already provide useful independent coverage for full stdout/stderr preservation, compact success output, child and pipeline exit codes, signal/setup/intended-RED precedence, no GREEN reuse after source/fixture/environment changes, merged/unknown GitHub state, basic hook routing, snapshot restoration/delta/whitespace, reviewer evidence refusal, refresh, and the confirmed InspectionEvidence/ChecklistSync consumer family. Product tests were not run by this review.

## Blocking findings

1. **The supported Codex integration is not yet specified and tested as an external interface.** The hook test invokes `harness.py hook` directly with synthetic input and accepts arbitrary plain-text output. It does not require the supported event-specific JSON shape (`hookSpecificOutput.hookEventName` plus `additionalContext` where needed), a repository/local hook configuration, trust/configuration-preserving installation, an external receipt, or `doctor` transition from configured to actually observed. It also does not prove that a read-only prompt performs no delivery/check execution. The measured installed-client baseline explicitly says the real hook smoke is `NOT_TRIGGERED`; therefore direct CLI output cannot satisfy the acceptance criterion that ordinary task/start/resume integration really fires. Add executable contract coverage for the real documented payload/output shapes, non-delivery read-only behavior, additive one-time setup and observed receipt. Retain a final installed-client smoke as an explicit delivery check, reporting UNKNOWN if it still cannot be exercised.

2. **The measurement contract is mostly asserted by key presence, so double counting and misleading telemetry can pass.** Current tests require only one duplicate tool/subagent example, a `token_telemetry` key/status, a `coverage` key and stable check count. They do not exercise review-return counting; root versus subagent/session identity; cumulative watermarks versus deltas; duplicate/out-of-order cumulative samples; total input/output with cached-input/reasoning subsets; unavailable individual fields; or the stated rule that overlapping fields are not summed. They also do not assert explicit coverage scopes for checks, agent tasks/calls, review returns and model-visible output. Add literal multi-session/root-subagent samples and expected programmatic report values, including UNKNOWN partial coverage, duplicate records/events and cumulative-to-delta behavior. The test oracle must make an implementation that sums cached input or reasoning into totals fail.

3. **Runner failure/diagnostic semantics remain materially under-covered.** The live diagnostic test correctly requires three retained results with exits `[7, 8, 0]` and a nonzero aggregate. Still absent are: a failure whose required marker/error fragment lies beyond the excerpt boundary; an assertion that the compact result retains necessary failure fragments while the complete bytes remain in the saved logs; timeout/unclassifiable UNKNOWN precedence; and reason classification for initial and justified repeat executions. The four-run no-reuse test supplies no repeat reason and never asserts recorded reasons, so an implementation can label every run `initial` while claiming compliance. Add these bounded synthetic cases. The contract's diagnostic wording should also require structured per-command outcomes, so matching filenames printed incidentally cannot satisfy the inventory.

4. **`state` does not prove exact-source PR/CI binding.** The sole successful fake returns a merged PR whose head, merge commit and repository HEAD are all the same, then asserts only `MERGED` and absence of “publish”. It does not distinguish dirty source identity, open implementation, open PR, successful CI, merge and deployment; reject/mark UNKNOWN a CI result for another SHA; or expose PR head/CI SHA in the result. The unavailable case checks field presence but not that stale cached state is labelled as last success rather than live. Add exact literal states for at least open exact-head CI, mismatched/stale CI, merged PR and GitHub failure, with checked timestamps and immutable worktree checks.

5. **Prepared-context and review-package coverage is insufficient for R3.** Only the root role is successfully prepared. No test verifies executor/reviewer role-specific bounded contents, contract/rules/source links, plan digest and freshness, evidence source match, stale evidence exclusion, or propagation of original findings into a rereview package. The reviewer negative only searches for the word `evidence`; the delta test checks two diff lines but not the previous snapshot identity or findings. There is no plan-drift case proving `prepare` invokes the explicit refresh path before capture, no stale approval rejection after source changes, and no root-before-RED case that reports a future test as intentionally absent. Add package-manifest assertions and negative source/evidence/plan mismatch cases. Keep preparation a completeness check, never an approval.

6. **Resolved during review — mandatory integration artifacts are now bound.** The initial verification input and plan omitted hook, repository-skill, AGENTS/current-goal and context-helper paths. Root first demonstrated the existing fail-closed UNKNOWN boundary, then added only `.codex/**` and `.agents/**` to the normative `delivery-policy` boundary. Expanded `gate3-plan-v2.json` now binds `.codex/hooks.json`, the existing repository OpenSpec apply skill, `AGENTS.md`, `current-delivery-goal.md`, `harness_context.py` and the original delivery tools. This resolves the scope defect without adding execution behavior or a second inventory. The policy diff is largely JSON reformatting; its semantic planning change is the two added patterns.

## Required correction

Root should amend the contract/OpenSpec and tests for blocking findings 1–5, capture a fresh reconstructible snapshot and qualifying RED, and request a Gate 3 delta review before implementation. The existing expectations should remain: no product migration, no new orchestrator, no GREEN cache, sequential shared-fixture consumers, preserved independent Gates 3/5, and final full CI.

## Gate 3 delta v3

- Candidate snapshot: `/tmp/fmonitor-harness-gate3-v3`, patch SHA-256 `86ec60830172d7b70f7fdc607033cdcc0acf356c6d479e63f5f19f2215ac76a5`, base `4bab010f157d352f50d6b94050d80cb49d40a8a9`
- Restored source: `/private/tmp/fmonitor-harness-gate3-v3-review`
- Plan: `/tmp/fmonitor-harness-baseline/gate3-plan-v3.json`
- RED: `/tmp/fmonitor-harness-baseline/red-v3.log` and `planner-red-v3.log`
- Verdict: **CHANGES_REQUESTED**

The concrete-v1 contract and expanded tests resolve original findings 1–4. In particular, the tests now pin documented Codex hook JSON, repository-only configuration, observed receipts and inert read-only prompts; ignore unsupported injected usage and keep token totals/deltas and coverage explicitly UNKNOWN; deduplicate session-scoped tools/subagents and count only a documented review return; preserve late failure markers, timeout/UNKNOWN precedence and repeat reasons; return a structured complete diagnostic inventory; and distinguish clean exact-head CI, dirty source, mismatched PR head, merged publication state and unavailable GitHub. Requiring a fictional cumulative telemetry importer would violate the installed-client boundary, so it is correctly deferred until a supported transport exists. The real interactive hook smoke remains a mandatory final validation and is not replaced by these synthetic tests.

One bounded part of original finding 5 remains:

1. **Reviewer evidence is exact-source but not shown to be relevant to the plan.** `test_role_packages_current_evidence_and_future_tests` creates evidence by running `python -c 'print("EXAMPLE_OK")'`, then requires the reviewer package to accept it. That argv is not the mapped acceptance command `python3 tests/Verification/example_test.py`. An implementation can therefore accept any successful same-source record as “necessary evidence,” contrary to R3's applicable evidence requirement, while excluding stale source correctly. Change the positive fixture to execute the mapped plan command and add a same-source successful unrelated argv that reviewer preparation rejects as irrelevant. Keep the existing exact-source mismatch, role fields, plan refresh, future-test and delta/findings assertions.

After that test-only correction and qualifying RED, a narrow delta review can approve Gate 3; no broader redesign or telemetry importer is requested.

## Gate 3 delta v4

- Candidate snapshot: `/tmp/fmonitor-harness-gate3-v4`, patch SHA-256 `f4a548d661c57d03be7e4948f87bdb258b188fb9aa4b4c17cafadd121aade407`, base `4bab010f157d352f50d6b94050d80cb49d40a8a9`
- Restored source: `/private/tmp/fmonitor-harness-gate3-v4-review`
- Plan: `/tmp/fmonitor-harness-baseline/gate3-plan-v4.json`
- RED: `/tmp/fmonitor-harness-baseline/red-v4.log`
- Verdict: **APPROVED**

The remaining evidence-relevance finding is resolved. The positive reviewer evidence now comes from the exact mapped acceptance argv `python3 tests/Verification/example_test.py`. A successful same-source `python -c` record is independently presented and must be rejected. The normative clarification requires coverage of every mapped acceptance argv, permits only resolution of the executable path, and prohibits substitution of another test. This distinguishes source freshness from evidence relevance and would fail an implementation that accepts an arbitrary GREEN record.

The accompanying changes in `verification_ci_001_test.py`, `verification_native_suites_001_test.py` and the change-verification fixture are setup adaptations for the newly shared runner: they copy the delivery tool tree, create a real Git base, place evidence outside the fixture repository and pass the absolute Python runtime. Existing runner/CI expectations are unchanged. The v4 RED remains attributable to the absent harness behavior; no product test or production implementation was introduced.

All prior blocking findings are closed for the bounded v1 contract and tests. Implementation may proceed against this exact approved specification/test candidate. Gate 3 approval does not approve implementation, replace independent Gate 5, prove the final real Codex hook smoke, or waive focused checks and full exact-source CI.

## Gate 3 registration delta

- Artifact: `tests/Verification/verification_inventory_001_test.py`
- Verdict: **APPROVED**

The single added expectation registers `python3\ttests/Verification/delivery_harness_001_test.py\n` in the existing unit-suite inventory mechanism. It matches the approved test path and runtime, introduces no second inventory or behavioral expectation, and leaves the surrounding registered suites unchanged. Scoped whitespace validation is clean. No broader Gate 3 rereview or product execution is required for this registration-only delta.

## Supplemental Gate 3: interruption, gate and environment

- Frozen delta: `/tmp/fmonitor-harness-supplemental-gate3/manifest.json`, parent `/tmp/fmonitor-harness-gate3-v4`
- Contract SHA-256: `8e3768f0ac9dbafbfb41cde8409eeab16603e450cab8b78f953746d5eef5c6bb`
- Test SHA-256: `c80cf38edc4b1745eba0c3fd23f548fc97429be2fe623a55b9622c72ce6d9a78`
- Evidence: `/tmp/fmonitor-harness-baseline/red-gate-environment.log`
- Verdict: **APPROVED**

The new expectations close material original semantics without redesigning the harness. Parent SIGTERM must preserve partial output and an INTERRUPTED record; an explicit SETUP_FAILURE or UNKNOWN marker cannot become GREEN merely because the child exits zero. Gate 3 accepts mapped intended-RED evidence while Gate 5 defaults to and requires GREEN, neither package grants approval, and every current mapped acceptance command must have relevant evidence. Reviewer evidence must also match the substantial environment. Whitespace inspection now explicitly includes an untracked file.

The interruption and gate cases happen to pass against the executor's in-progress draft. They are regression coverage for previously approved requirements, not Gate 5 evidence or implementation approval. The environment-mismatch case exits nonzero because the draft incorrectly accepts stale-environment evidence; this is the qualifying RED that the executor must fix after this approval. No review GREEN is claimed for that failure. The frozen spec/test bytes are approved for implementation; independent Gate 5 remains required.

## Native installation fallback Gate 3

- Frozen delta: `/tmp/fmonitor-harness-install-gate3/manifest.json`
- Contract SHA-256: `f5c3181acac97997282a46b2d8ae134ecc771965a89f67ab0ad8fae6ad3ce000`
- Test SHA-256: `91e9274c53c7772b111e8308716d1b0cf26ae876fce9cf0f4c6e7dfded68d9f3`
- Plan: `/tmp/fmonitor-harness-baseline/install-plan.json`
- RED: `/tmp/fmonitor-harness-baseline/install-red.log`
- Verdict: **CHANGES_REQUESTED**

The missing `install` subcommand is qualifying RED at the existing public CLI, and the user-level fallback is a justified bounded response to the real native smoke: installed Codex loaded a user hook while the trusted project hook was not observed. The contract correctly keeps the repository hook definition canonical, requires a small non-LLM dispatcher, preserves native trust, and forbids a new backend or global `config.toml` mutation.

One safety requirement is not sensitive in the proposed test. The foreign fixture contains only a `Stop` event, while the installer writes `SessionStart`, `UserPromptSubmit`, `PostToolUse`, `SubagentStart` and `SubagentStop`. An implementation may replace every pre-existing handler inside those same event groups and still pass, contrary to the explicit requirement to preserve foreign settings/events. Add an owner handler to at least one event the harness also installs (preferably `UserPromptSubmit`) and require it to remain byte-equivalent and ordered after install and reinstall. Keep the existing top-level preservation, invalid-JSON atomicity, idempotence and repo/unrelated dispatch checks.

The worktree/common-directory statement should also be exercised if it is retained as normative: create a linked worktree for the registered repository and require dispatch there, while an unrelated Git repository remains silent. Alternatively narrow the v1 statement to the exact repository paths actually registered and leave worktree routing for a separately tested extension. This is part of the target's separate-worktree workflow, so prose alone is insufficient.

No implementation of the fallback is approved until these bounded expectations receive a delta review. Existing approved implementation work outside this held integration delta may continue.

### Native installation fallback delta v2

- RED: `/tmp/fmonitor-harness-baseline/install-red-v2.log`
- Verdict: **APPROVED**

Both requested sensitivities are now present. The fixture puts a foreign `UserPromptSubmit` handler in the same event group the harness extends, requires that exact dictionary to remain first, and requires byte-identical configuration after reinstall. The installed dispatcher must produce the task context from a detached linked worktree sharing the registered Git common directory, while a separately initialized unrelated Git repository produces no output. The existing disjoint event/top-level preservation, invalid-JSON immutability, repo cleanliness and idempotence checks remain intact.

The focused test still fails only because the public `install` command is absent, so `install-red-v2.log` is qualifying RED. Gate 3 is approved for this bounded native fallback. This does not approve its implementation or replace the required real installed-client smoke and independent Gate 5.

## Gate 3 — CI outcome marker grammar

- CI evidence: `/tmp/fmonitor-harness-ci-34496967157.json` and `/tmp/fmonitor-harness-ci-34496967157-failed.log`
- RED: `/tmp/fmonitor-harness-baseline/ci-marker-red.log`
- Verdict: **APPROVED**

The real CI inventory isolates two false harness failures in Integration 1: successful product suites emitted domain identifiers containing `UNKNOWN`; verify failed only as a consequence. All other jobs were GREEN. The correction keeps product tests and their output unchanged.

The contract now limits control markers to a line prefix, optional horizontal indentation, and `:` or end-of-line. The executable tests pin both exact CI domain strings and ordinary prose as GREEN, then place real UNKNOWN and SETUP_FAILURE marker lines after domain words and 20 KiB of noise. They require marker precedence over intended RED/child exit and require the exact late diagnostic in the compact excerpt while preserving the full stderr bytes. The four current failures are qualifying runner RED, including the prior bug where an earlier domain `SETUP_FAILURE` substring masks a later explicit UNKNOWN marker.

This bounded spec/test delta is approved. Implementation must honor both spaces and tabs as horizontal indentation; independent Gate 5 delta and a new full exact-source CI are still required.

## Gate 3 real consumer-owner correction

- Test: `tests/Verification/change_verification_001_test.py::test_harness_refresh_and_confirmed_consumers`
- RED: `/tmp/fmonitor-harness-baseline/consumer-real-owner-red.log`
- Verdict: **APPROVED**

The corrected test replaces the invented InspectionEvidence owner with existing `MariaDbYiiChecklist.php` and split `MariaDbYiiChecklistAdmission.php`, asserts those source paths exist, and retains the existing exact `ChecklistSync.php` case. Each real owner must select the same five registered schema/upload/rejection/revoke/concurrency checks. The local `PilotHttp/LocalView.php` negative and refresh/check assertions remain unchanged, so this does not broaden all PilotHttp or E2E.

The focused failure names all five absent consumers for the first real owner and is qualifying RED for the Gate 5 finding. The executor may implement supported owner-pattern matching against this approved correction; Gate 5 must independently review the resulting policy/planner delta.

## Supplemental Gate 3: aggregate zero-exit setup regression

- Tests: `tests/Verification/verification_ci_001_test.py` and `tests/Verification/verification_native_suites_001_test.py`
- Evidence: `/tmp/fmonitor-harness-baseline/ci-outcome-red.log` and `/tmp/fmonitor-harness-baseline/native-outcome-red.log`
- Verdict: **APPROVED**

Each existing runner contract adds one bounded synthetic case whose executable prints `SETUP_FAILURE` and exits zero. The native suite and CI category must still return nonzero; selected commands/results remain visible. This directly protects the already approved rule that a setup outcome cannot become GREEN through a zero child exit, at both aggregation layers. Existing runner expectations and semantic scope are unchanged.

Both focused cases currently pass because the executor draft already contains the corresponding correction. Despite the historical `*-red.log` filenames, their contents are GREEN regression evidence and are not represented here as RED or as implementation/Gate 5 approval.

## PR89 bounded corrections — Gate 3

- Root package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260910T161944Z-b7d8f1c0df/package.json`
- Base: `98c6eceeac8d606e0215b1d59354d2d2c9f5d8b4`
- Plan SHA-256: `51588b37413b49b00de8788b79ad0677943eea94be5bbeefad1cf7fef4fb60ed`
- RED: `/tmp/pr89-fixes-harness-red.log`, `/tmp/pr89-fixes-planner-red.log`, `/tmp/pr89-fixes-short-ci-red.log`
- Verdict: **APPROVED**

The bounded matrix is complete for the requested corrections:

1. Optional `gate3_expected` remains inside each existing acceptance mapping, has exactly the mapped test keys and only GREEN/INTENDED_RED values, becomes plan-bound input, and defaults conservatively to all intended RED. Mixed Gate 3 requires every command's declared outcome and at least one actual RED; one RED cannot substitute for a different RED obligation, all-GREEN cannot pass Gate 3, and Gate 5 still requires all GREEN.
2. The exact absolute plan returned by `prepare` must support check, stale rejection, refresh, recheck and focused run. Tests reject an untrusted copy, lexical parent traversal, symlink escape and a tampered source-input traversal without leaking the external sentinel. Repo-relative source/spec/test/input validation remains unchanged.
3. Two linked worktrees prepare interleaved distinct changes into a shared evidence home. Resume context and `state.active_binding` must return only each canonical worktree's package/mutable plan; dispatcher Git-common-dir authorization remains shared. This directly detects the observed overwrite class.
4. GREEN CLI delivery is limited to `id`, `outcome` and `record_path`; retained records still carry complete provenance, paths, byte counts and summary size. Six short successful commands must deliver less diagnostic JSON than their unchanged saved streams. Existing failure excerpts and CI full-stream assertions remain.

The five harness failures, two planner failures and one short-output failure are qualifying missing/incorrect behavior, not setup failures. The `record_path.resolve()` adjustment only canonicalizes the macOS `/var` alias in an assertion and changes no expectation. The earlier real-CI UNKNOWN marker defect has separate approved tests/evidence and is not used to explain these RED cases. No product behavior, architecture migration, approval automation, merge or deployment enters this scope. Implementation may proceed; full locally available regression, independent Gate 5 and a new exact-source full CI remain mandatory.

### Nested make retained-output assertion

- Test: `tests/Verification/verification_ci_001_test.py::test_make_category_does_not_leak_into_nested_make`
- Evidence: `/tmp/pr89-nested-make-retained-output.log`
- Verdict: **APPROVED**

The existing success and make-environment leak guards are unchanged. The literal `inner-ok` is now required in the full retained stdout of the unique runner record whose argv names the exact mapped PHP test, rather than in compact interactive stdout. This preserves the behavioral assertion while conforming to the approved successful-output contract; it neither weakens the nested-make check nor hides failures.

### Namespaced active-plan public seam

- Test: `tests/Verification/delivery_harness_001_test.py::Harness.test_active_bindings_survive_interleaved_worktrees`
- RED record: `1789060470415731000-86c58e226a9c4eb69963fa22f60d361f`
- RED summary: `/tmp/pr89-active-plan-red.json`
- Verdict: **APPROVED**

For each independently prepared linked worktree, the test now passes its own `state.active_binding.plan` to the actual `change-verification.py check --plan` CLI from that worktree and requires success. Existing distinct plan/change/package and resume-context assertions remain. The focused run fails exactly because the trusted resolver rejects the generated namespaced state filename; the harness correctly records that expected failure as INTENDED_RED with no source drift. This directly covers the Gate 5 HIGH finding without broadening path trust or architecture scope. Implementation may admit only the exact generated 20-hex active-plan filename grammar; a fresh Gate 5 delta remains required.
